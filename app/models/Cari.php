<?php
/**
 * Cari Model
 * --------------------------------------------------------
 * Hem Müşteri hem Tedarikçi kayıtlarını yönetir.
 * `tip` sütunu: 'musteri' | 'tedarikci' | 'her_ikisi'
 *
 * Güncelleme (v2):
 *  - aktif_mi, eticaret_mi filtre desteği
 *  - sadeceBakiyeli filtresi (kalan_tutar > 0 olanlar)
 *  - acik_bakiye  → faturalar.kalan_tutar üzerinden anlık hesap
 *  - cek_senet_bakiye → cek_senet_portfoyu üzerinden hesap
 *
 * Tüm sorgular Database::query() (prepared statements) üzerinden çalışır.
 * Doğrudan string birleştirme / kullanıcı girdisi KULLANILMAZ.
 */
class Cari
{
    private Database $db;

    /** ekle() / guncelle() için izin verilen sütunlar */
    private array $fillable = [
        'tip', 'unvan', 'yetkili_kisi', 'vergi_dairesi', 'vergi_no',
        'tc_kimlik_no', 'telefon', 'cep_telefon', 'eposta',
        'adres', 'il', 'ilce', 'ulke', 'cari_kodu',
        'bakiye', 'para_birimi', 'notlar', 'company_id',
        'sinif_1', 'sinif_2', 'resim_yolu',
        // 'aktif_mi', 'eticaret_mi',   // v2: tabloya eklenince aktif edilecek
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->ensureSiniflandirmaColumns();
        $this->ensureResimYoluColumn();
    }

    /** cariler.sinif_1/sinif_2 yoksa ekler (müşteri/tedarikçi sınıflandırma). */
    private function ensureSiniflandirmaColumns(): void
    {
        try {
            foreach (['sinif_1', 'sinif_2'] as $col) {
                $var = $this->db->selectOne(
                    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cariler' AND COLUMN_NAME = :col",
                    [':col' => $col]
                );
                if (!$var) {
                    $this->db->query("ALTER TABLE cariler ADD COLUMN {$col} VARCHAR(100) NULL");
                }
            }
        } catch (\Throwable $e) { /* sessizce geç */ }
    }

    /** cariler.resim_yolu yoksa ekler (müşteri/tedarikçi fotoğrafı). */
    private function ensureResimYoluColumn(): void
    {
        try {
            $var = $this->db->selectOne(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cariler' AND COLUMN_NAME = 'resim_yolu'"
            );
            if (!$var) {
                $this->db->query("ALTER TABLE cariler ADD COLUMN resim_yolu VARCHAR(255) NULL");
            }
        } catch (\Throwable $e) { /* sessizce geç */ }
    }

    // ──────────────────────────────────────────────────────
    // LİSTELEME
    // ──────────────────────────────────────────────────────

    /**
     * Sayfalanmış, filtrelenmiş cari listesi.
     * acik_bakiye  : faturalardaki kalan_tutar toplamından anlık hesaplanır.
     * cek_senet_bakiye : portföydeki bekleyen çek/senetlerin toplamı.
     *
     * @param string $tip            'musteri' | 'tedarikci' | 'her_ikisi'
     * @param string $arama          Unvan / vergi no / cari kodu araması
     * @param int    $limit          Sayfa başı kayıt sayısı
     * @param int    $offset         Atlanan kayıt sayısı
     * @param string $aktifMi        '' (hepsi) | '1' (aktif) | '0' (pasif)
     * @param string $eticaretMi     '' (hepsi) | '1' (e-ticaret)
     * @param bool   $sadeceBakiyeli true → yalnızca açık bakiyesi > 0 olanlar
     */
    public function listele(
        string $tip            = 'musteri',
        string $arama          = '',
        int    $limit          = 50,
        int    $offset         = 0,
        string $aktifMi        = '',
        string $eticaretMi     = '',
        bool   $sadeceBakiyeli = false
    ): array {
        [$where, $params] = $this->buildWhere($tip, $arama, $aktifMi, $eticaretMi, $sadeceBakiyeli);

        $params[':limit']  = $limit;
        $params[':offset'] = $offset;

        $balanceSql = $tip === 'tedarikci'
            ? "COALESCE((
                    SELECT SUM(CASE
                        WHEN f.belge_tipi = 'alis' THEN f.genel_toplam
                        WHEN f.belge_tipi = 'iade_alis' THEN -f.genel_toplam
                        ELSE 0 END)
                    FROM faturalar f
                    WHERE f.cari_id = c.id
                      AND f.silindi_mi = 0
                      AND f.company_id = :tenant_company_id
                      AND f.period_id = :tenant_period_id
                      AND f.durum NOT IN ('iptal', 'taslak')
               ), 0) - COALESCE((
                    SELECT SUM(CASE
                        WHEN kh.islem_tipi = 'cikis' THEN kh.tutar
                        WHEN kh.islem_tipi = 'giris' THEN -kh.tutar
                        ELSE 0 END)
                    FROM kasa_hareketleri kh
                    WHERE kh.cari_id = c.id AND kh.silindi_mi = 0
                      AND kh.company_id = :tenant_company_id
                      AND kh.period_id = :tenant_period_id
               ), 0)"
            : "COALESCE((
                    SELECT SUM(CASE
                        WHEN f.belge_tipi IN ('satis', 'perakende') THEN f.genel_toplam
                        WHEN f.belge_tipi = 'iade_satis' THEN -f.genel_toplam
                        ELSE 0 END)
                    FROM faturalar f
                    WHERE f.cari_id = c.id
                      AND f.silindi_mi = 0
                      AND f.company_id = :tenant_company_id
                      AND f.period_id = :tenant_period_id
                      AND f.durum NOT IN ('iptal', 'taslak')
               ), 0) - COALESCE((
                    SELECT SUM(CASE
                        WHEN kh.islem_tipi = 'giris' THEN kh.tutar
                        WHEN kh.islem_tipi = 'cikis' THEN -kh.tutar
                        ELSE 0 END)
                    FROM kasa_hareketleri kh
                    WHERE kh.cari_id = c.id AND kh.silindi_mi = 0
                      AND kh.company_id = :tenant_company_id
                      AND kh.period_id = :tenant_period_id
               ), 0)";

        $sql = "SELECT
                    c.id, c.unvan, c.telefon, c.cep_telefon,
                    c.bakiye, c.para_birimi, c.vergi_no, c.eposta,
                    c.olusturulma_tarihi,
                    -- Anlık açık bakiye: (Satışlar + Ödemeler) - (Alışlar + Tahsilatlar)
                    -- Veya daha basit: (Borç İşlemleri - Alacak İşlemleri)
                    ({$balanceSql}) AS acik_bakiye,
                    -- Portföyde bekleyen çek/senet toplamı (tablo henüz oluşturulmadı)
                    0 AS cek_senet_bakiye
                FROM cariler c
                WHERE {$where}
                ORDER BY c.unvan ASC
                LIMIT :limit OFFSET :offset";

        return $this->db->select($sql, $params);
    }

    /**
     * Filtreye göre toplam kayıt sayısı (pagination için).
     * Aynı WHERE koşullarını kullanır; sadece COUNT alır.
     */
    public function say(
        string $tip            = 'musteri',
        string $arama          = '',
        string $aktifMi        = '',
        string $eticaretMi     = '',
        bool   $sadeceBakiyeli = false
    ): int {
        [$where, $params] = $this->buildWhere($tip, $arama, $aktifMi, $eticaretMi, $sadeceBakiyeli);

        $row = $this->db->selectOne(
            "SELECT COUNT(*) AS toplam FROM cariler c WHERE {$where}",
            $params
        );
        return (int)($row['toplam'] ?? 0);
    }

    /** Liste sayfası özet kutuları için (toplam bakiye, müşteri sayısı vb.) */
    public function ozetler(string $tip = 'musteri'): array
    {
        $row = $this->db->selectOne(
            "SELECT
                 COUNT(*) AS toplam_kayit,
                 SUM(CASE WHEN silindi_mi = 0 THEN 1 ELSE 0 END) AS aktif_sayisi,
                 0 AS eticaret_sayisi,
                 SUM(CASE WHEN bakiye > 0 THEN 1 ELSE 0 END) AS borclu_sayisi,
                 COALESCE(SUM(bakiye), 0) AS toplam_bakiye
             FROM cariler
             WHERE tip IN (:tip, 'her_ikisi') AND company_id = :cid",
            [':tip' => $tip, ':cid' => TenantContext::activeCompanyId()]
        );
        return $row ?? [];
    }

    // ──────────────────────────────────────────────────────
    // TEKİL KAYIT
    // ──────────────────────────────────────────────────────

    public function getir(int $id): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM cariler WHERE id = :id AND silindi_mi = 0 AND company_id = :cid",
            [':id' => $id, ':cid' => TenantContext::activeCompanyId()]
        );
    }

    // ──────────────────────────────────────────────────────
    // KAYIT OLUŞTUR
    // ──────────────────────────────────────────────────────

    /**
     * Yeni cari kaydı ekler.
     * @return int Eklenen kaydın id'si
     */
    public function ekle(array $veri): int
    {
        $temiz = array_intersect_key($veri, array_flip($this->fillable));

        if (empty($temiz['unvan'])) {
            throw new InvalidArgumentException('Müşteri/tedarikçi unvanı boş olamaz.');
        }

        $temiz['tip']      = $temiz['tip']      ?? 'musteri';
        
        // Boş veya set edilmemiş cari_kodu → NULL (UNIQUE constraint çakışmasını önler)
        if (!isset($temiz['cari_kodu']) || trim((string)$temiz['cari_kodu']) === '') {
            $temiz['cari_kodu'] = null;
        }

        return $this->db->insert('cariler', $temiz);
    }

    // ──────────────────────────────────────────────────────
    // GÜNCELLE
    // ──────────────────────────────────────────────────────

    public function guncelle(int $id, array $veri): int
    {
        $temiz = array_intersect_key($veri, array_flip($this->fillable));

        if (empty($temiz)) {
            return 0;
        }

        return $this->db->update('cariler', $temiz, ['id' => $id]);
    }

    // ──────────────────────────────────────────────────────
    // SOFT DELETE
    // ──────────────────────────────────────────────────────

    public function sil(int $id): int
    {
        return $this->db->softDelete('cariler', $id);
    }

    // ──────────────────────────────────────────────────────
    // GEÇMİŞ / İLİŞKİLİ VERİLER
    // ──────────────────────────────────────────────────────

    /** Müşterinin satış geçmişi (faturalar + kalemleri) */
    public function satisGecmisi(int $cariId): array
    {
        $gecmis = $this->db->select(
            "SELECT f.* FROM faturalar f
             WHERE f.cari_id = :cid AND f.belge_tipi = 'satis' AND f.silindi_mi = 0
               AND f.company_id = :company_id AND f.period_id = :period_id
             ORDER BY f.fatura_tarihi DESC, f.id DESC LIMIT 50",
            [':cid' => $cariId, ':company_id' => TenantContext::activeCompanyId(), ':period_id' => TenantContext::activePeriodId()]
        );

        foreach ($gecmis as &$f) {
            $f['kalemler'] = $this->db->select(
                "SELECT * FROM fatura_kalemleri WHERE fatura_id = :fid AND silindi_mi = 0",
                [':fid' => $f['id']]
            );
        }
        return $gecmis;
    }

    /** Tedarikçinin alış geçmişi (faturalar + kalemleri) */
    public function alisGecmisi(int $cariId): array
    {
        $gecmis = $this->db->select(
            "SELECT f.* FROM faturalar f
             WHERE f.cari_id = :cid AND f.belge_tipi = 'alis' AND f.silindi_mi = 0
               AND f.company_id = :company_id AND f.period_id = :period_id
             ORDER BY f.fatura_tarihi DESC, f.id DESC LIMIT 50",
            [':cid' => $cariId, ':company_id' => TenantContext::activeCompanyId(), ':period_id' => TenantContext::activePeriodId()]
        );

        foreach ($gecmis as &$f) {
            $f['kalemler'] = $this->db->select(
                "SELECT * FROM fatura_kalemleri WHERE fatura_id = :fid AND silindi_mi = 0",
                [':fid' => $f['id']]
            );
        }
        return $gecmis;
    }

    /** Ödeme/tahsilat geçmişi */
    public function odemeGecmisi(int $cariId): array
    {
        return $this->db->select(
            "SELECT kh.*, kb.hesap_adi AS kasa_adi
             FROM kasa_hareketleri kh
             LEFT JOIN kasa_banka kb ON kb.id = kh.kasa_id
             WHERE kh.cari_id = :cid AND kh.silindi_mi = 0
               AND kh.company_id = :company_id AND kh.period_id = :period_id
             ORDER BY kh.tarih DESC, kh.id DESC LIMIT 50",
            [':cid' => $cariId, ':company_id' => TenantContext::activeCompanyId(), ':period_id' => TenantContext::activePeriodId()]
        );
    }

    public function getirTipli(int $id, string $tip): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM cariler
             WHERE id = :id
               AND tip IN (:tip, 'her_ikisi')
               AND silindi_mi = 0
               AND company_id = :cid",
            [':id' => $id, ':tip' => $tip, ':cid' => TenantContext::activeCompanyId()]
        );
    }

    public function ekstreHareketleri(int $cariId, string $tip, string $baslangic, string $bitis): array
    {
        $rows = $this->ekstreHareketleriHam($cariId, $baslangic, $bitis);
        return $this->normalizeEkstreRows($rows, $tip);
    }

    public function ekstreDevirBakiyesi(int $cariId, string $tip, string $baslangic): float
    {
        $previousDay = date('Y-m-d', strtotime($baslangic . ' -1 day'));
        $rows = $this->ekstreHareketleriHam($cariId, null, $previousDay);
        $balance = 0.0;
        foreach ($this->normalizeEkstreRows($rows, $tip) as $row) {
            $balance += (float)$row['borc'] - (float)$row['alacak'];
        }
        return $balance;
    }

    private function ekstreHareketleriHam(int $cariId, ?string $baslangic, ?string $bitis): array
    {
        $invoiceDateSql = '';
        $cashDateSql = '';
        $params = [
            ':cid1' => $cariId,
            ':cid2' => $cariId,
            ':company_id1' => TenantContext::activeCompanyId(),
            ':company_id2' => TenantContext::activeCompanyId(),
            ':period_id1' => TenantContext::activePeriodId(),
            ':period_id2' => TenantContext::activePeriodId(),
        ];

        if ($baslangic !== null && $baslangic !== '') {
            $invoiceDateSql .= ' AND f.fatura_tarihi >= :baslangic1';
            $cashDateSql .= ' AND DATE(kh.tarih) >= :baslangic2';
            $params[':baslangic1'] = $baslangic;
            $params[':baslangic2'] = $baslangic;
        }

        if ($bitis !== null && $bitis !== '') {
            $invoiceDateSql .= ' AND f.fatura_tarihi <= :bitis1';
            $cashDateSql .= ' AND DATE(kh.tarih) <= :bitis2';
            $params[':bitis1'] = $bitis;
            $params[':bitis2'] = $bitis;
        }

        return $this->db->select(
            "SELECT
                f.fatura_tarihi AS tarih,
                f.vade_tarihi AS vade,
                f.belge_tipi AS kaynak_tipi,
                f.fatura_no AS belge_no,
                f.aciklama,
                NULL AS miktar,
                NULL AS odeme,
                NULL AS fiyat,
                f.genel_toplam AS tutar,
                f.id AS kaynak_id
             FROM faturalar f
             WHERE f.cari_id = :cid1
               AND f.silindi_mi = 0
               AND f.durum <> 'iptal'
               AND f.company_id = :company_id1
               AND f.period_id = :period_id1
               {$invoiceDateSql}
             UNION ALL
             SELECT
                DATE(kh.tarih) AS tarih,
                DATE(kh.tarih) AS vade,
                CONCAT('nakit_', kh.islem_tipi) AS kaynak_tipi,
                '' AS belge_no,
                kh.aciklama,
                NULL AS miktar,
                COALESCE(kb.hesap_adi, CASE WHEN kh.islem_tipi = 'giris' THEN 'Tahsilat' ELSE 'Ödeme' END) AS odeme,
                NULL AS fiyat,
                kh.tutar AS tutar,
                kh.id AS kaynak_id
             FROM kasa_hareketleri kh
             LEFT JOIN kasa_banka kb ON kb.id = kh.kasa_id
             WHERE kh.cari_id = :cid2
               AND kh.silindi_mi = 0
               AND kh.company_id = :company_id2
               AND kh.period_id = :period_id2
               {$cashDateSql}
             ORDER BY tarih ASC, kaynak_id ASC",
            $params
        );
    }

    private function normalizeEkstreRows(array $rows, string $tip): array
    {
        $normalized = [];
        foreach ($rows as $row) {
            $movement = $this->ekstreMovement((string)($row['kaynak_tipi'] ?? ''), $tip, (float)($row['tutar'] ?? 0));
            $row['hareket'] = $movement['hareket'];
            $row['borc'] = $movement['borc'];
            $row['alacak'] = $movement['alacak'];
            $normalized[] = $row;
        }
        return $normalized;
    }

    private function ekstreMovement(string $kaynakTipi, string $tip, float $tutar): array
    {
        $isMusteri = $tip === 'musteri';
        $labels = [
            'satis' => 'Satış',
            'perakende' => 'Satış',
            'alis' => 'Alış',
            'iade_satis' => 'Satış İadesi',
            'iade_alis' => 'Alış İadesi',
            'nakit_giris' => 'Tahsilat',
            'nakit_cikis' => 'Ödeme',
        ];

        $borcTypes = $isMusteri
            ? ['satis', 'perakende', 'iade_alis', 'nakit_cikis']
            : ['alis', 'iade_satis', 'nakit_giris'];

        return [
            'hareket' => $labels[$kaynakTipi] ?? ucfirst(str_replace('_', ' ', $kaynakTipi)),
            'borc' => in_array($kaynakTipi, $borcTypes, true) ? $tutar : 0.0,
            'alacak' => in_array($kaynakTipi, $borcTypes, true) ? 0.0 : $tutar,
        ];
    }

    // ──────────────────────────────────────────────────────
    // YARDIMCILAR
    // ──────────────────────────────────────────────────────

    /** Unvan benzersizlik kontrolü */
    public function unvanMevcutMu(string $unvan, int $haricId = 0): bool
    {
        $row = $this->db->selectOne(
            "SELECT id FROM cariler WHERE unvan = :u AND silindi_mi = 0 AND id != :hid AND company_id = :cid LIMIT 1",
            [':u' => $unvan, ':hid' => $haricId, ':cid' => TenantContext::activeCompanyId()]
        );
        return $row !== null;
    }

    // ──────────────────────────────────────────────────────
    // PRIVATE — WHERE üretici
    // ──────────────────────────────────────────────────────

    /**
     * listele() ve say() için ortak WHERE koşulu üretir.
     * @return array [string $whereStr, array $params]
     */
    private function buildWhere(
        string $tip,
        string $arama,
        string $aktifMi,
        string $eticaretMi,
        bool   $sadeceBakiyeli
    ): array {
        $where  = [];
        $params = [];

        if ($aktifMi === '0') {
            $where[] = 'c.silindi_mi = 1';
        } elseif ($aktifMi === '1') {
            $where[] = 'c.silindi_mi = 0';
        } else {
            $where[] = 'c.silindi_mi IN (0, 1)';
        }
        $where[] = 'c.company_id = :tenant_company_id';
        $params[':tenant_company_id'] = TenantContext::activeCompanyId();
        $params[':tenant_period_id'] = TenantContext::activePeriodId();

        // Tip filtresi
        if ($tip === 'her_ikisi') {
            $where[] = "c.tip IN ('musteri','tedarikci','her_ikisi')";
        } else {
            $where[]       = "c.tip IN (:tip, 'her_ikisi')";
            $params[':tip'] = $tip;
        }

        // Arama (unvan / vergi no / cari kodu)
        if ($arama !== '') {
            $like = '%' . $arama . '%';
            $where[]           = "(c.unvan LIKE :arama1 OR c.vergi_no LIKE :arama2 OR c.cari_kodu LIKE :arama3)";
            $params[':arama1'] = $like;
            $params[':arama2'] = $like;
            $params[':arama3'] = $like;
        }

        // aktif_mi ve eticaret_mi sütunları henüz tabloda yok, filtre devre dışı

        // Sadece bakiyesi olanlar (correlated subquery — index ile hızlı çalışır)
        if ($sadeceBakiyeli) {
            $belgeTipi = $tip === 'tedarikci' ? 'alis' : 'satis';
            $where[] = "(
                SELECT COALESCE(SUM(f.kalan_tutar), 0)
                FROM   faturalar f
                WHERE  f.cari_id    = c.id
                  AND  f.belge_tipi = '{$belgeTipi}'
                  AND  f.silindi_mi = 0
                  AND  f.company_id = :tenant_company_id
                  AND  f.period_id = :tenant_period_id
                  AND  f.durum NOT IN ('iptal', 'odendi')
            ) > 0";
        }

        return [implode(' AND ', $where), $params];
    }
}
