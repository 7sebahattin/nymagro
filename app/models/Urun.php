<?php
/**
 * Model: Urun
 * --------------------------------------------------------
 * urunler_hizmetler tablosu üzerinde CRUD işlemleri.
 * tip: 'urun' | 'hizmet'
 */

class Urun
{
    private Database $db;

    /** Toplu atama için izin verilen sütunlar */
    private array $fillable = [
        'tip', 'stok_kodu', 'barkod', 'ad', 'aciklama', 'birim',
        'satis_fiyati', 'alis_fiyati', 'kdv_orani', 'para_birimi', 'kdv_dahil',
        'alis_para_birimi', 'alis_kdv_orani', 'alis_iskonto',
        'stok_miktari', 'kritik_stok', 'kategori', 'marka', 'resim_yolu',
        'company_id', 'eticaret', 'site_urun_id',
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->ensureSyncColumns();
    }

    /** Panel ürünü ↔ site_urunler senkronu için gereken kolonlar (idempotent). */
    private function ensureSyncColumns(): void
    {
        // 'aciklama' $fillable'da ve formda her zaman vardı ama bazı kurulumlarda
        // tabloya hiç eklenmemiş — ekleme/güncelleme "Unknown column" ile patlıyordu.
        $this->addColumnIfMissing('urunler_hizmetler', 'aciklama', 'TEXT NULL AFTER birim');
        $this->addColumnIfMissing('urunler_hizmetler', 'para_birimi', "VARCHAR(5) NOT NULL DEFAULT 'TRY' AFTER kdv_orani");
        $this->addColumnIfMissing('urunler_hizmetler', 'eticaret', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER resim_yolu');
        $this->addColumnIfMissing('urunler_hizmetler', 'site_urun_id', 'INT UNSIGNED NULL AFTER eticaret');

        // Fiyatlandırma formunda ("Alış KDV Oranı", "Alış İskontosu" vb.) var
        // olan alanlar hiçbir zaman şemaya eklenmemişti — form değerleri
        // hiçbir yere kaydedilmeden sessizce kayboluyordu.
        $this->addColumnIfMissing('urunler_hizmetler', 'kdv_dahil', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER kdv_orani');
        $this->addColumnIfMissing('urunler_hizmetler', 'alis_para_birimi', "VARCHAR(5) NOT NULL DEFAULT 'TRY' AFTER alis_fiyati");
        $this->addColumnIfMissing('urunler_hizmetler', 'alis_kdv_orani', 'DECIMAL(5,2) NOT NULL DEFAULT 20.00 AFTER alis_para_birimi');
        $this->addColumnIfMissing('urunler_hizmetler', 'alis_iskonto', 'DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER alis_kdv_orani');
    }

    private function addColumnIfMissing(string $table, string $column, string $definition): void
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $table) || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $column)) {
            throw new InvalidArgumentException('Geçersiz tablo veya kolon adı.');
        }
        $row = $this->db->selectOne("SHOW COLUMNS FROM {$table} LIKE " . $this->db->pdo()->quote($column));
        if (!$row) {
            $this->db->query("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
        }
    }

    /**
     * Sayfalanmış ürün listesi.
     * $tip: '' = hepsi, 'urun', 'hizmet'
     */
    public function listele(
        string $arama   = '',
        string $tip     = '',
        string $kategori= '',
        string $marka   = '',
        int    $limit   = 50,
        int    $offset  = 0
    ): array {
        [$where, $params] = $this->buildWhere($arama, $tip, $kategori, $marka);

        $sql = "SELECT * FROM urunler_hizmetler
                WHERE {$where}
                ORDER BY olusturulma_tarihi DESC
                LIMIT :limit OFFSET :offset";

        $params[':limit']  = $limit;
        $params[':offset'] = $offset;

        return $this->db->select($sql, $params);
    }

    /** Toplam kayıt sayısı (filtreyle birlikte). */
    public function say(
        string $arama   = '',
        string $tip     = '',
        string $kategori= '',
        string $marka   = ''
    ): int {
        [$where, $params] = $this->buildWhere($arama, $tip, $kategori, $marka);
        $row = $this->db->selectOne(
            "SELECT COUNT(*) AS n FROM urunler_hizmetler WHERE {$where}",
            $params
        );
        return (int)($row['n'] ?? 0);
    }

    /** Tekil kayıt getir. */
    public function getir(int $id): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM urunler_hizmetler WHERE id = :id AND silindi_mi = 0 AND company_id = :cid",
            [':id' => $id, ':cid' => TenantContext::activeCompanyId()]
        );
    }

    /** Yeni kayıt ekle. */
    public function ekle(array $veri): int
    {
        $temiz = array_intersect_key($veri, array_flip($this->fillable));
        return $this->db->insert('urunler_hizmetler', $temiz);
    }

    /** Kayıt güncelle. */
    public function guncelle(int $id, array $veri): int
    {
        $temiz = array_intersect_key($veri, array_flip($this->fillable));
        return $this->db->update('urunler_hizmetler', $temiz, ['id' => $id]);
    }

    /** Soft-delete. */
    public function sil(int $id): int
    {
        return $this->db->softDelete('urunler_hizmetler', $id);
    }

    /** Stok kodu/barkod benzersiz mi? */
    public function kodMevcutMu(string $alan, string $deger, int $haricId = 0): bool
    {
        $alan = in_array($alan, ['stok_kodu', 'barkod']) ? $alan : 'stok_kodu';
        $row = $this->db->selectOne(
            "SELECT id FROM urunler_hizmetler
             WHERE `{$alan}` = :deger AND silindi_mi = 0 AND id <> :haric AND company_id = :cid",
            [':deger' => $deger, ':haric' => $haricId, ':cid' => TenantContext::activeCompanyId()]
        );
        return $row !== null;
    }

    /** Mevcut kategorileri listele (distinct). */
    public function kategoriler(): array
    {
        return $this->db->select(
            "SELECT DISTINCT kategori FROM urunler_hizmetler
             WHERE silindi_mi = 0 AND company_id = :cid AND kategori IS NOT NULL AND kategori <> ''
             ORDER BY kategori"
            , [':cid' => TenantContext::activeCompanyId()]
        );
    }

    /** Mevcut markaları listele (distinct). */
    public function markalar(): array
    {
        return $this->db->select(
            "SELECT DISTINCT marka FROM urunler_hizmetler
             WHERE silindi_mi = 0 AND company_id = :cid AND marka IS NOT NULL AND marka <> ''
             ORDER BY marka"
            , [':cid' => TenantContext::activeCompanyId()]
        );
    }

    /** "Ürün Kopyala" arama kutusu için hızlı arama (ad veya stok kodu). */
    public function aramaKopya(string $q): array
    {
        return $this->db->select(
            "SELECT id, ad, stok_kodu, marka FROM urunler_hizmetler
             WHERE (ad LIKE :q OR stok_kodu LIKE :q) AND silindi_mi = 0 AND company_id = :cid
             ORDER BY ad ASC LIMIT 10",
            [':q' => "%{$q}%", ':cid' => TenantContext::activeCompanyId()]
        );
    }

    // ─── SİTE VİTRİNİ SENKRONU ────────────────────────────────────────────

    private const SITE_KATEGORI_ETIKETLERI = [
        'micro'        => 'Şelatlı Mikro Element',
        'macro'        => 'Makro Besin',
        'biostimulant' => 'Biyostimülant',
    ];

    /**
     * Site Yönetimi'nde eklenen/güncellenen bir ürünü panele yansıtır.
     * $siteUrun zaten panele bağlıysa (panel_urun_id) o satırı günceller,
     * değilse "vitrin şirketi" altında yeni bir ürün oluşturur. Fiyat/stok
     * gibi yalnız-panel alanlarına dokunmaz — sadece ad/açıklama/görsel/
     * kategori senkronlanır.
     */
    public function syncFromSiteUrun(array $siteUrun, int $storefrontCompanyId): int
    {
        $veri = [
            'ad'         => trim((string)($siteUrun['ad_tr'] ?? '')),
            'aciklama'   => trim((string)($siteUrun['aciklama_tr'] ?? '')) ?: null,
            'resim_yolu' => trim((string)($siteUrun['gorsel'] ?? '')) ?: null,
            'kategori'   => self::SITE_KATEGORI_ETIKETLERI[$siteUrun['kategori'] ?? 'micro']
                ?? self::SITE_KATEGORI_ETIKETLERI['micro'],
            'site_urun_id' => (int)($siteUrun['id'] ?? 0),
        ];
        if ($veri['ad'] === '') {
            return 0;
        }

        $mevcutId = (int)($siteUrun['panel_urun_id'] ?? 0);
        if ($mevcutId > 0) {
            $satirVarMi = $this->db->selectOne(
                "SELECT id FROM urunler_hizmetler WHERE id = :id AND company_id = :cid AND silindi_mi = 0",
                [':id' => $mevcutId, ':cid' => $storefrontCompanyId]
            );
            if ($satirVarMi) {
                $this->db->update(
                    'urunler_hizmetler',
                    array_intersect_key($veri, array_flip($this->fillable)),
                    ['id' => $mevcutId, 'company_id' => $storefrontCompanyId]
                );
                return $mevcutId;
            }
        }

        $veri['tip']          = 'urun';
        $veri['birim']        = 'Adet';
        $veri['satis_fiyati'] = 0;
        $veri['alis_fiyati']  = 0;
        $veri['stok_miktari'] = 0;
        $veri['kritik_stok']  = 0;
        $veri['kdv_orani']    = 20;
        $veri['para_birimi']  = 'TRY';
        $veri['eticaret']     = 1;
        $veri['company_id']   = $storefrontCompanyId;

        return $this->ekle($veri);
    }

    // ─── STOK VE GEÇMİŞ İŞLEMLERİ ───────────────────────────────────────

    /** Manuel stok hareketi ekle ve stok miktarını güncelle */
    public function stokHareketiEkle(int $urunId, float $miktar, string $islemTipi, string $aciklama = '', ?int $depoId = null): bool
    {
        try {
            $this->db->begin();

            // Hareketi kaydet
            $this->db->insert('stok_hareketleri', [
                'urun_id'    => $urunId,
                'depo_id'    => $depoId,
                'islem_tipi' => $islemTipi,
                'hareket_tipi' => $islemTipi,
                'miktar'     => $miktar,
                'aciklama'   => $aciklama,
                'company_id'  => TenantContext::activeCompanyId(),
                'period_id'   => TenantContext::activePeriodId(),
            ]);

            // Depo bazlı stok güncelle
            if ($depoId) {
                $op = $islemTipi === 'giris' ? '+' : '-';
                $sqlDepo = "INSERT INTO urun_stok_depo (urun_id, depo_id, miktar, company_id) 
                            VALUES (:uid, :did, :miktar, :company_id)
                            ON DUPLICATE KEY UPDATE miktar = miktar {$op} VALUES(miktar)";
                $this->db->query($sqlDepo, [
                    ':uid'    => $urunId, 
                    ':did'    => $depoId, 
                    ':miktar' => $miktar,
                    ':company_id' => TenantContext::activeCompanyId(),
                ]);
            }

            // Genel Stok miktarını güncelle (Toplam)
            $operator = $islemTipi === 'giris' ? '+' : '-';
            $sqlTotal = "UPDATE urunler_hizmetler SET stok_miktari = stok_miktari {$operator} :miktar WHERE id = :id AND company_id = :company_id";
            $this->db->query($sqlTotal, [':miktar' => $miktar, ':id' => $urunId, ':company_id' => TenantContext::activeCompanyId()]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /** Önceki Satışlar (faturalar tablosundan) */
    public function satisGecmisi(int $urunId): array
    {
        $sql = "SELECT f.fatura_no, f.fatura_tarihi, f.belge_tipi, c.unvan as cari_unvan, 
                       fk.miktar, fk.birim_fiyat, fk.toplam
                FROM fatura_kalemleri fk
                JOIN faturalar f ON fk.fatura_id = f.id
                LEFT JOIN cariler c ON f.cari_id = c.id
                WHERE fk.urun_id = :urun_id 
                  AND f.belge_tipi IN ('satis', 'perakende') 
                  AND f.silindi_mi = 0 AND fk.silindi_mi = 0
                  AND f.company_id = :company_id AND f.period_id = :period_id
                ORDER BY f.fatura_tarihi DESC, f.olusturulma_tarihi DESC
                LIMIT 50";
        return $this->db->select($sql, [':urun_id' => $urunId, ':company_id' => TenantContext::activeCompanyId(), ':period_id' => TenantContext::activePeriodId()]);
    }

    /** Önceki Alışlar (faturalar tablosundan) */
    public function alisGecmisi(int $urunId): array
    {
        $sql = "SELECT f.fatura_no, f.fatura_tarihi, f.belge_tipi, c.unvan as cari_unvan, 
                       fk.miktar, fk.birim_fiyat, fk.toplam
                FROM fatura_kalemleri fk
                JOIN faturalar f ON fk.fatura_id = f.id
                LEFT JOIN cariler c ON f.cari_id = c.id
                WHERE fk.urun_id = :urun_id 
                  AND f.belge_tipi = 'alis' 
                  AND f.silindi_mi = 0 AND fk.silindi_mi = 0
                  AND f.company_id = :company_id AND f.period_id = :period_id
                ORDER BY f.fatura_tarihi DESC, f.olusturulma_tarihi DESC
                LIMIT 50";
        return $this->db->select($sql, [':urun_id' => $urunId, ':company_id' => TenantContext::activeCompanyId(), ':period_id' => TenantContext::activePeriodId()]);
    }

    /** Manuel Stok Hareketleri */
    public function stokHareketleri(int $urunId): array
    {
        $sql = "SELECT sh.*, d.ad as depo_adi 
                FROM stok_hareketleri sh
                LEFT JOIN depolar d ON sh.depo_id = d.id
                WHERE sh.urun_id = :urun_id 
                  AND sh.company_id = :company_id AND sh.period_id = :period_id
                ORDER BY sh.olusturulma_tarihi DESC 
                LIMIT 100";
        return $this->db->select($sql, [':urun_id' => $urunId, ':company_id' => TenantContext::activeCompanyId(), ':period_id' => TenantContext::activePeriodId()]);
    }

    /**
     * Stok ekstresi — ürünün TÜM stok hareketleri, tarihe göre artan.
     *
     * NOT: Burada faturalar tablosu ayrıca birleştirilmez. Fatura::ekle() ve
     * iptal/silme akışları da stokHareketiEkle() çağırdığı için fatura
     * kaynaklı giriş/çıkışlar zaten stok_hareketleri içinde duruyor
     * (aciklama = "Alış Faturası #..." / "Satış Faturası #..."). Faturaları
     * ayrıca UNION etmek her faturayı iki kez sayardı; bu tablo tek
     * doğruluk kaynağıdır.
     */
    public function ekstreHareketleri(int $urunId, string $bas = '', string $bit = ''): array
    {
        $sql = "SELECT sh.id, sh.olusturulma_tarihi, sh.islem_tipi, sh.miktar,
                       sh.aciklama, d.ad AS depo_adi
                FROM stok_hareketleri sh
                LEFT JOIN depolar d ON sh.depo_id = d.id
                WHERE sh.urun_id = :urun_id
                  AND sh.company_id = :company_id AND sh.period_id = :period_id";
        $params = [
            ':urun_id'    => $urunId,
            ':company_id' => TenantContext::activeCompanyId(),
            ':period_id'  => TenantContext::activePeriodId(),
        ];
        if ($bas !== '') {
            $sql .= " AND sh.olusturulma_tarihi >= :bas";
            $params[':bas'] = $bas . ' 00:00:00';
        }
        if ($bit !== '') {
            $sql .= " AND sh.olusturulma_tarihi <= :bit";
            $params[':bit'] = $bit . ' 23:59:59';
        }
        $sql .= " ORDER BY sh.olusturulma_tarihi ASC, sh.id ASC";

        return $this->db->select($sql, $params);
    }

    /**
     * Ekstrede tarih filtresi varsa, başlangıç tarihinden önceki net stok
     * (devir bakiyesi). Filtre yoksa 0 döner.
     */
    public function ekstreDevirBakiyesi(int $urunId, string $bas = ''): float
    {
        if ($bas === '') {
            return 0.0;
        }

        $sql = "SELECT COALESCE(SUM(CASE WHEN islem_tipi = 'giris' THEN miktar ELSE 0 END), 0)
                     - COALESCE(SUM(CASE WHEN islem_tipi <> 'giris' THEN miktar ELSE 0 END), 0) AS devir
                FROM stok_hareketleri
                WHERE urun_id = :urun_id
                  AND company_id = :company_id AND period_id = :period_id
                  AND olusturulma_tarihi < :bas";

        $rows = $this->db->select($sql, [
            ':urun_id'    => $urunId,
            ':company_id' => TenantContext::activeCompanyId(),
            ':period_id'  => TenantContext::activePeriodId(),
            ':bas'        => $bas . ' 00:00:00',
        ]);

        return (float)($rows[0]['devir'] ?? 0);
    }

    /** Ürünün depolara göre stok dağılımını getirir */
    public function depoStoklariniGetir(int $urunId): array
    {
        $sql = "SELECT d.ad as depo_adi, usd.miktar 
                FROM urun_stok_depo usd
                JOIN depolar d ON usd.depo_id = d.id
                WHERE usd.urun_id = :urun_id AND d.silindi_mi = 0 AND usd.company_id = :company_id
                ORDER BY d.ad ASC";
        return $this->db->select($sql, [':urun_id' => $urunId, ':company_id' => TenantContext::activeCompanyId()]);
    }

    // ─── Private Helpers ──────────────────────────────────────────────────

    private function buildWhere(
        string $arama,
        string $tip,
        string $kategori,
        string $marka
    ): array {
        $conds  = ['silindi_mi = 0'];
        $params = [':company_id' => TenantContext::activeCompanyId()];
        $conds[] = 'company_id = :company_id';

        if ($arama !== '') {
            $conds[]        = "(ad LIKE :arama OR stok_kodu LIKE :arama OR barkod LIKE :arama)";
            $params[':arama'] = '%' . $arama . '%';
        }
        if ($tip !== '') {
            $conds[]      = "tip = :tip";
            $params[':tip'] = $tip;
        }
        if ($kategori !== '') {
            $conds[]          = "kategori = :kategori";
            $params[':kategori'] = $kategori;
        }
        if ($marka !== '') {
            $conds[]        = "marka = :marka";
            $params[':marka'] = $marka;
        }

        return [implode(' AND ', $conds), $params];
    }
}
