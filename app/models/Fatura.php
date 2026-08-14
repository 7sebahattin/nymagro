<?php
/**
 * Model: Fatura
 * --------------------------------------------------------
 * faturalar + fatura_kalemleri tablolarını yönetir.
 * belge_tipi: 'satis' | 'alis' | 'iade_satis' | 'iade_alis' | 'perakende'
 * durum:      'taslak' | 'onaylandi' | 'odendi' | 'kismi_odendi' | 'iptal'
 */

class Fatura
{
    private Database $db;

    private array $fillable = [
        'belge_tipi', 'fatura_no', 'cari_id', 'fatura_tarihi', 'vade_tarihi',
        'ara_toplam', 'iskonto_tutari', 'kdv_tutari', 'genel_toplam',
        'odenen_tutar', 'kalan_tutar', 'para_birimi', 'kur',
        'ara_toplam_doviz', 'iskonto_tutari_doviz', 'kdv_tutari_doviz', 'genel_toplam_doviz',
        'durum', 'odeme_sekli', 'aciklama', 'company_id', 'period_id', 'depo_id',
        'created_by',
        // İrsaliye sevk türü ve irsaliye→fatura dönüştürme alanları (bkz. ensureIrsaliyeSevkColumns).
        'sevk_turu', 'hedef_depo_id', 'kaynak_irsaliye_id', 'irsaliye_kullanildi',
        // Teklif (proforma)→satış dönüştürme bağlantısı (bkz. ensureTeklifDonusumColumns).
        'kaynak_teklif_id', 'teklif_kullanildi',
    ];

    private array $kalemFillable = [
        'fatura_id', 'urun_id', 'urun_adi', 'aciklama', 'miktar', 'birim',
        'birim_fiyat', 'iskonto_orani', 'iskonto_tutari',
        'kdv_orani', 'kdv_tutari', 'ara_toplam', 'toplam', 'sira_no', 'company_id', 'period_id',
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
        // DDL (ALTER TABLE) MySQL/MariaDB'de örtük commit yapar — açık bir
        // transaction içindeyken çalıştırılırsa dış transaction'ı erken/
        // sessizce commit eder ve sonraki rollBack() tutarsız kalır (örn.
        // Nakit::hareketEkle() kendi transaction'ı içinde
        // recomputeCariBalance() için new Fatura() oluşturuyor). Bu yüzden
        // idempotent şema migrasyonları yalnızca hiçbir transaction açık
        // değilken çalıştırılır; aksi halde bir sonraki transaction-dışı
        // çağrıda tekrar denenir.
        if (!$this->db->inTransaction()) {
            $this->ensureDepoIdColumn();
            $this->ensureDovizColumns();
            $this->ensureCreatedByColumn();
            $this->ensureIrsaliyeSevkColumns();
            $this->ensureTeklifDonusumColumns();
            $this->ensurePerformansIndexleri();
        }
    }

    /**
     * İrsaliyenin sevk türünü (müşteriye / depolar arası) ve irsaliye→fatura
     * dönüştürme bağlantısını saklamak için gerekli kolonlar (idempotent).
     * - sevk_turu / hedef_depo_id: yalnızca belge_tipi='irsaliye' satırlarında anlamlıdır.
     * - kaynak_irsaliye_id: bir irsaliyeden doldurularak oluşturulan 'satis' faturasında,
     *   kaynak irsaliyenin id'sini tutar — bu sayede mal irsaliye kesilirken zaten
     *   depodan düşürüldüğü için fatura anında stok İKİNCİ KEZ düşürülmez.
     * - irsaliye_kullanildi: bir irsaliyenin daha önce bir faturaya dönüştürülüp
     *   dönüştürülmediğini (aynı irsaliyenin iki kez faturalandırılmasını önlemek için) tutar.
     */
    private function ensureIrsaliyeSevkColumns(): void
    {
        $kolonlar = [
            'sevk_turu'           => "VARCHAR(20) NULL DEFAULT NULL AFTER depo_id",
            'hedef_depo_id'       => "INT NULL DEFAULT NULL AFTER sevk_turu",
            'kaynak_irsaliye_id'  => "INT NULL DEFAULT NULL AFTER hedef_depo_id",
            'irsaliye_kullanildi' => "TINYINT(1) NOT NULL DEFAULT 0 AFTER kaynak_irsaliye_id",
        ];
        try {
            foreach ($kolonlar as $ad => $tanim) {
                $var = $this->db->selectOne(
                    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'faturalar' AND COLUMN_NAME = :ad",
                    [':ad' => $ad]
                );
                if (!$var) {
                    $this->db->query("ALTER TABLE faturalar ADD COLUMN {$ad} {$tanim}");
                }
            }
        } catch (\Throwable $e) {
            // Sessizce geç — tablo henüz yoksa veya yetki yoksa uygulamayı kilitleme.
        }
    }

    /**
     * Bir teklifin (proforma) hangi satış faturasına dönüştüğünü izlemek için
     * gerekli kolonlar (idempotent).
     * - kaynak_teklif_id: bir teklifden doldurularak oluşturulan 'satis' faturasında,
     *   kaynak teklifin id'sini tutar.
     * - teklif_kullanildi: bir teklifin daha önce bir satışa dönüştürülüp
     *   dönüştürülmediğini (aynı teklifin iki kez satışa dönüştürülmesini önlemek
     *   için) tutar; Teklifler Raporu'ndaki "Satışa dönüştü mü?" sütunu buradan okunur.
     */
    private function ensureTeklifDonusumColumns(): void
    {
        $kolonlar = [
            'kaynak_teklif_id' => "INT NULL DEFAULT NULL AFTER irsaliye_kullanildi",
            'teklif_kullanildi' => "TINYINT(1) NOT NULL DEFAULT 0 AFTER kaynak_teklif_id",
        ];
        try {
            foreach ($kolonlar as $ad => $tanim) {
                $var = $this->db->selectOne(
                    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'faturalar' AND COLUMN_NAME = :ad",
                    [':ad' => $ad]
                );
                if (!$var) {
                    $this->db->query("ALTER TABLE faturalar ADD COLUMN {$ad} {$tanim}");
                }
            }
        } catch (\Throwable $e) {
            // Sessizce geç — tablo henüz yoksa veya yetki yoksa uygulamayı kilitleme.
        }
    }

    /**
     * fatura_kalemleri.fatura_id ve faturalar/fatura_kalemleri.cari_id/urun_id
     * hiç index'lenmemişti — her fatura görüntüleme/düzenleme/yazdırma ve her
     * recomputeCariBalance() çağrısı tam tablo taraması yapıyordu (EXPLAIN ile
     * doğrulandı). Idempotent: yalnızca eksikse ALTER atar.
     */
    private function ensurePerformansIndexleri(): void
    {
        $this->ensureIndex('fatura_kalemleri', 'idx_fk_fatura', '(fatura_id, company_id, period_id)');
        $this->ensureIndex('fatura_kalemleri', 'idx_fk_urun', '(urun_id, company_id, period_id)');
        $this->ensureIndex('faturalar', 'idx_faturalar_cari', '(cari_id, company_id, period_id)');
    }

    /** Bir index yoksa idempotent olarak ekler (INFORMATION_SCHEMA.STATISTICS kontrolü). */
    private function ensureIndex(string $table, string $indexName, string $columnsSql): void
    {
        try {
            $var = $this->db->selectOne(
                "SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND INDEX_NAME = :i",
                [':t' => $table, ':i' => $indexName]
            );
            if (!$var) {
                $this->db->query("ALTER TABLE {$table} ADD INDEX {$indexName} {$columnsSql}");
            }
        } catch (\Throwable $e) {
            // Sessizce geç — tablo henüz yoksa veya yetki yoksa uygulamayı kilitleme.
        }
    }

    /**
     * faturalar.created_by yoksa ekler (fatura listelerinde "Kullanıcı: Sistem"
     * her zaman sabit metin gösteriliyordu — gerçek oluşturan kullanıcı hiç
     * saklanmıyordu). Idempotent: yalnızca eksikse ALTER atar.
     */
    private function ensureCreatedByColumn(): void
    {
        try {
            $var = $this->db->selectOne(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'faturalar' AND COLUMN_NAME = 'created_by'"
            );
            if (!$var) {
                $this->db->query("ALTER TABLE faturalar ADD COLUMN created_by INT UNSIGNED NULL");
            }
        } catch (\Throwable $e) {
            // Sessizce geç — tablo henüz yoksa veya yetki yoksa uygulamayı kilitleme.
        }
    }

    /**
     * faturalar.depo_id yoksa ekler (iptal/silmede hangi depoya stok geri
     * yazılacağını bilebilmek için — eskiden depo bilgisi hiç saklanmıyordu).
     * Idempotent: INFORMATION_SCHEMA kontrolü ile yalnızca eksikse ALTER atar.
     */
    private function ensureDepoIdColumn(): void
    {
        try {
            $var = $this->db->selectOne(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'faturalar' AND COLUMN_NAME = 'depo_id'"
            );
            if (!$var) {
                $this->db->query("ALTER TABLE faturalar ADD COLUMN depo_id INT NULL AFTER period_id");
            }
        } catch (\Throwable $e) {
            // Sessizce geç — tablo henüz yoksa veya yetki yoksa uygulamayı kilitleme.
        }
    }

    /**
     * Döviz faturaları için TL karşılığının yanında orijinal döviz
     * toplamlarını da saklayabilmek için gerekli kolonlar (idempotent).
     * ara_toplam/kdv_tutari/genel_toplam vb. HER ZAMAN TL'dir (cari bakiye,
     * kasa hareketleri ve raporlar bunu varsayıyor) — bu 4 kolon sadece
     * para_birimi TRY değilken doldurulan, orijinal döviz tutarlarıdır.
     */
    private function ensureDovizColumns(): void
    {
        $kolonlar = [
            'ara_toplam_doviz'     => 'DECIMAL(18,2) NULL DEFAULT NULL AFTER genel_toplam',
            'iskonto_tutari_doviz' => 'DECIMAL(18,2) NULL DEFAULT NULL AFTER ara_toplam_doviz',
            'kdv_tutari_doviz'     => 'DECIMAL(18,2) NULL DEFAULT NULL AFTER iskonto_tutari_doviz',
            'genel_toplam_doviz'   => 'DECIMAL(18,2) NULL DEFAULT NULL AFTER kdv_tutari_doviz',
        ];
        try {
            foreach ($kolonlar as $ad => $tanim) {
                $var = $this->db->selectOne(
                    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'faturalar' AND COLUMN_NAME = :ad",
                    [':ad' => $ad]
                );
                if (!$var) {
                    $this->db->query("ALTER TABLE faturalar ADD COLUMN {$ad} {$tanim}");
                }
            }
        } catch (\Throwable $e) {
            // Sessizce geç — tablo henüz yoksa veya yetki yoksa uygulamayı kilitleme.
        }
    }

    // ─── Listeleme ──────────────────────────────────────────────────────

    public function listele(
        string $belge_tipi = 'satis',
        string $arama      = '',
        string $durum      = '',
        string $donem      = '1ay',
        bool   $iptalleri  = false,
        int    $limit      = 50,
        int    $offset     = 0
    ): array {
        [$where, $params] = $this->buildWhere($belge_tipi, $arama, $durum, $donem, $iptalleri);

        $sql = "SELECT f.*, c.unvan AS cari_unvan, u.full_name AS olusturan_adi
                FROM faturalar f
                LEFT JOIN cariler c ON c.id = f.cari_id
                LEFT JOIN users u ON u.id = f.created_by
                WHERE {$where}
                ORDER BY f.fatura_tarihi DESC, f.id DESC
                LIMIT :limit OFFSET :offset";

        $params[':limit']  = $limit;
        $params[':offset'] = $offset;

        return $this->db->select($sql, $params);
    }

    public function say(
        string $belge_tipi = 'satis',
        string $arama      = '',
        string $durum      = '',
        string $donem      = '1ay',
        bool   $iptalleri  = false
    ): int {
        [$where, $params] = $this->buildWhere($belge_tipi, $arama, $durum, $donem, $iptalleri);
        $row = $this->db->selectOne(
            "SELECT COUNT(*) AS n FROM faturalar f
             LEFT JOIN cariler c ON c.id = f.cari_id
             WHERE {$where}",
            $params
        );
        return (int)($row['n'] ?? 0);
    }

    /** Toplamlar (liste sayfasındaki özet kutuları için). */
    public function ozetToplamlar(string $belge_tipi = 'satis', string $donem = '1ay'): array
    {
        [$where, $params] = $this->buildWhere($belge_tipi, '', '', $donem, false);
        $row = $this->db->selectOne(
            "SELECT
               COUNT(*) AS adet,
               COALESCE(SUM(genel_toplam),0) AS toplam_tutar,
               COALESCE(SUM(kalan_tutar),0)  AS bekleyen_tutar,
               COALESCE(SUM(CASE WHEN durum='iptal' THEN 1 ELSE 0 END),0) AS iptal_adet
             FROM faturalar f
             LEFT JOIN cariler c ON c.id = f.cari_id
             WHERE {$where}",
            $params
        );
        return $row ?? [];
    }

    // ─── Tekil ──────────────────────────────────────────────────────────

    public function getir(int $id): ?array
    {
        return $this->db->selectOne(
            "SELECT f.*, c.unvan AS cari_unvan, c.telefon AS cari_tel,
                    c.eposta AS cari_eposta, c.adres AS cari_adres
             FROM faturalar f
             LEFT JOIN cariler c ON c.id = f.cari_id
             WHERE f.id = :id AND f.silindi_mi = 0 AND f.company_id = :cid",
            [':id' => $id, ':cid' => TenantContext::activeCompanyId()]
        );
    }

    public function kalemleriGetir(int $faturaId): array
    {
        return $this->db->select(
            "SELECT fk.*, u.koli_ici_adet
             FROM fatura_kalemleri fk
             LEFT JOIN urunler_hizmetler u ON u.id = fk.urun_id
             WHERE fk.fatura_id = :fid AND fk.silindi_mi = 0
               AND fk.company_id = :cid
             ORDER BY fk.sira_no, fk.id",
            [':fid' => $faturaId, ':cid' => TenantContext::activeCompanyId()]
        );
    }

    /**
     * Belgenin tam satırına göre ilgili Rbac/Audit modülünü döner. irsaliye her iki
     * taraftan da (Satışlar/Alışlar ekranı) aynı belge_tipi ile kaydedildiği için
     * yalnızca belge_tipi yetmez — sevk_turu='tedarikci' ise alış tarafı sayılır.
     */
    private function moduleForBelgeTipi(array $fatura): string
    {
        $belgeTipi = $fatura['belge_tipi'] ?? '';
        if (in_array($belgeTipi, ['alis', 'iade_alis'], true)) {
            return 'ALIS';
        }
        if ($belgeTipi === 'irsaliye' && ($fatura['sevk_turu'] ?? '') === 'tedarikci') {
            return 'ALIS';
        }
        return 'SATIS';
    }

    /**
     * Bir faturanın kalemleri kaydedilirken hangi depo(lar)da hangi yönde stok
     * hareketi yazılacağını belirler. Her eleman ['tip'=>'giris'|'cikis','depo_id'=>int].
     * Boş dizi → bu belge hiç stok hareketi yaratmaz.
     *
     * Kurallar (gerçek irsaliye/fatura pratiğine göre — bkz. Mikro/Logo'daki
     * "irsaliyeyi faturaya çağırma" akışı):
     *  - alış / satış iadesi          → giriş (mal işletmeye döner/girer)
     *  - satış / alış iadesi / perakende → çıkış (mal işletmeden çıkar)
     *  - irsaliye (müşteriye sevk)    → çıkış — mal fiilen depodan sevk edilir;
     *    cari borcu/gelir henüz oluşmaz, o faturada oluşur.
     *  - irsaliye (tedarikçiden alım)  → giriş — mal tedarikçiden fiilen teslim
     *    alınır; cari borcu/gider henüz oluşmaz, o alış faturasında oluşur.
     *  - irsaliye (depolar arası sevk) → kaynak depoda çıkış + hedef depoda giriş
     *    (toplam şirket stoğu değişmez, yalnızca depo dağılımı değişir).
     *  - bir irsaliyeden doldurularak oluşturulan satış/alış faturası
     *    (kaynak_irsaliye_id dolu) → HİÇ stok hareketi yaratmaz; mal irsaliye
     *    kesilirken zaten depodan düşürülmüş/depoya girmişti, aksi halde aynı
     *    sevkiyat iki kez stok hareketi yaratır.
     *  - proforma / sipariş           → hiç etkilemez (henüz bağlayıcı belge değil).
     */
    private function stokHareketPlani(array $fatura, int $depoId): array
    {
        $belgeTipi = $fatura['belge_tipi'] ?? '';

        if (in_array($belgeTipi, ['satis', 'alis'], true) && !empty($fatura['kaynak_irsaliye_id'])) {
            return [];
        }

        if ($belgeTipi === 'irsaliye') {
            $sevkTuru = $fatura['sevk_turu'] ?? 'musteri';
            if ($sevkTuru === 'depolar_arasi') {
                $hedefDepoId = !empty($fatura['hedef_depo_id']) ? (int)$fatura['hedef_depo_id'] : null;
                // Geçersiz/eksik hedef depo (veya kaynakla aynı depo) durumunda hiç
                // hareket yazma — yalnızca "çıkış" yazıp "giriş"i atlamak, karşılığı
                // olmayan bir stok kaybı yaratır. Kontrolcü zaten bunu doğrular; bu,
                // ikinci bir güvenlik katmanıdır.
                if (!$hedefDepoId || $hedefDepoId === $depoId) {
                    return [];
                }
                return [
                    ['tip' => 'cikis', 'depo_id' => $depoId],
                    ['tip' => 'giris', 'depo_id' => $hedefDepoId],
                ];
            }
            if ($sevkTuru === 'tedarikci') {
                return [['tip' => 'giris', 'depo_id' => $depoId]];
            }
            return [['tip' => 'cikis', 'depo_id' => $depoId]];
        }

        if (in_array($belgeTipi, ['alis', 'iade_satis'], true)) {
            return [['tip' => 'giris', 'depo_id' => $depoId]];
        }
        if (in_array($belgeTipi, ['satis', 'iade_alis', 'perakende'], true)) {
            return [['tip' => 'cikis', 'depo_id' => $depoId]];
        }

        return [];
    }

    /** stokHareketPlani() açıklamasında kullanılacak kısa, insan-okunur belge etiketi. */
    private function stokAciklamaEtiketi(string $belgeTipi): string
    {
        return match ($belgeTipi) {
            'alis'      => 'Alış Faturası',
            'irsaliye'  => 'İrsaliye',
            'iade_satis', 'iade_alis' => 'İade Faturası',
            'perakende' => 'Perakende Satış',
            default     => 'Satış Faturası',
        };
    }

    /**
     * Bir kaynak belgeyi (irsaliye/teklif) "kullanıldı" bayrağıyla işaretler
     * (veya iptal/silme sırasında tersini yapar). Guard'lı UPDATE ile aynı
     * kaynağın iki kez kullanılması engellenir — işaretleme (kullanildi=true)
     * sırasında 0 satır etkilenirse (zaten kullanılmış/silinmiş/başka belgeye
     * bağlanmış) $hataMesaji ile exception fırlatılır; işareti geri alırken
     * (kullanildi=false) sessizce geçilir.
     */
    private function kaynakBelgeIsaretle(int $kaynakId, string $beklenenBelgeTipi, string $kolon, bool $kullanildi, string $hataMesaji = ''): void
    {
        $stmt = $this->db->query(
            "UPDATE faturalar SET {$kolon} = :yeni
             WHERE id = :kid AND company_id = :cid AND belge_tipi = :btip
               AND {$kolon} = :eski AND silindi_mi = 0",
            [
                ':yeni' => $kullanildi ? 1 : 0,
                ':kid'  => $kaynakId,
                ':cid'  => TenantContext::activeCompanyId(),
                ':btip' => $beklenenBelgeTipi,
                ':eski' => $kullanildi ? 0 : 1,
            ]
        );
        if ($kullanildi && $stmt->rowCount() === 0) {
            throw new RuntimeException($hataMesaji);
        }
    }

    // ─── Kayıt ──────────────────────────────────────────────────────────

    /**
     * Fatura + kalemlerini tek transaction içinde kaydeder.
     * @param  array  $fatura   — faturalar sütunları
     * @param  array  $kalemler — her eleman: [urun_id, urun_adi, miktar, birim_fiyat, kdv_orani, iskonto_orani, birim]
     * @return int    Eklenen fatura ID'si
     */
    public function ekle(array $fatura, array $kalemler, int $depoId = 1): int
    {
        TenantContext::assertWritablePeriod('faturalar');
        require_once MODELS_PATH . '/Urun.php';
        $uModel = new Urun();

        $this->db->begin();
        try {
            $temizFatura = array_intersect_key($fatura, array_flip($this->fillable));
            $temizFatura['company_id'] = TenantContext::activeCompanyId();
            $temizFatura['period_id'] = TenantContext::activePeriodId();
            $temizFatura['depo_id'] = $depoId;
            $this->assertCariBelongsToCompany($temizFatura['cari_id'] ?? null);
            $faturaId    = $this->db->insert('faturalar', $temizFatura);

            // Bir irsaliyeden/teklifden doldurularak oluşturulan faturaysa, kaynak
            // belgeyi "kullanıldı" olarak işaretle — aynı kaynak iki kez
            // kullanılamaz (guarded UPDATE: satır zaten kullanılmışsa exception atar).
            if (!empty($temizFatura['kaynak_irsaliye_id'])) {
                $this->kaynakBelgeIsaretle((int)$temizFatura['kaynak_irsaliye_id'], 'irsaliye', 'irsaliye_kullanildi', true, 'Seçilen irsaliye bulunamadı veya zaten faturalandırılmış.');
            }
            if (!empty($temizFatura['kaynak_teklif_id'])) {
                $this->kaynakBelgeIsaretle((int)$temizFatura['kaynak_teklif_id'], 'proforma', 'teklif_kullanildi', true, 'Seçilen teklif bulunamadı veya zaten satışa dönüştürülmüş.');
            }

            $stokPlani = $this->stokHareketPlani($fatura, $depoId);

            foreach ($kalemler as $sira => $k) {
                $miktar      = (float)($k['miktar']       ?? 1);
                $birimFiyat  = (float)($k['birim_fiyat']  ?? 0);
                $kdvOrani    = (float)($k['kdv_orani']    ?? 20);
                $iskontoOran = (float)($k['iskonto_orani'] ?? 0);

                $araToplam    = $miktar * $birimFiyat;
                $iskontoTutar = $araToplam * ($iskontoOran / 100);
                $kdvTabani    = $araToplam - $iskontoTutar;
                $kdvTutari    = $kdvTabani * ($kdvOrani / 100);
                $toplam       = $kdvTabani + $kdvTutari;

                $kalemVeri = [
                    'fatura_id'      => $faturaId,
                    'urun_id'        => !empty($k['urun_id']) ? (int)$k['urun_id'] : null,
                    'urun_adi'       => $k['urun_adi'] ?? '',
                    'aciklama'       => $k['aciklama'] ?? null,
                    'miktar'         => $miktar,
                    'birim'          => $k['birim'] ?? 'Adet',
                    'birim_fiyat'    => $birimFiyat,
                    'iskonto_orani'  => $iskontoOran,
                    'iskonto_tutari' => round($iskontoTutar, 2),
                    'kdv_orani'      => $kdvOrani,
                    'kdv_tutari'     => round($kdvTutari, 2),
                    'ara_toplam'     => round($araToplam, 2),
                    'toplam'         => round($toplam, 2),
                    'sira_no'        => $sira + 1,
                    'company_id'      => TenantContext::activeCompanyId(),
                    'period_id'       => TenantContext::activePeriodId(),
                ];
                $this->assertProductBelongsToCompany($kalemVeri['urun_id']);

                $this->db->insert('fatura_kalemleri', $kalemVeri);

                // Stok güncelleme (belge tipine ve — irsaliyede — sevk türüne göre; bkz. stokHareketPlani())
                if ($kalemVeri['urun_id']) {
                    $moveDescBase = $this->stokAciklamaEtiketi($fatura['belge_tipi']) . ' #' . $fatura['fatura_no'];
                    foreach ($stokPlani as $hareket) {
                        $uModel->stokHareketiEkle($kalemVeri['urun_id'], $miktar, $hareket['tip'], $moveDescBase, $hareket['depo_id']);
                    }
                }
            }

            // Stok güncelleme bitti, şimdi CARİ BAKİYE güncelle
            if ((int)$fatura['cari_id'] > 0) {
                $this->recomputeCariBalance((int)$fatura['cari_id']);
            }

            $this->db->commit();
            Audit::log('CREATE', $this->moduleForBelgeTipi($temizFatura), $faturaId, null, $temizFatura, "Fatura oluşturuldu: " . ($temizFatura['fatura_no'] ?? ''));
            return $faturaId;
        } catch (\Throwable $e) {
            if ($this->db->pdo()->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Var olan bir faturayı ve kalemlerini günceller: eski stok/bakiye etkisini
     * geri alır, kalemleri değiştirir, yeni stok/bakiye etkisini uygular.
     * İptal edilmiş faturalar düzenlenemez.
     */
    public function guncelle(int $id, array $fatura, array $kalemler, int $depoId = 1): int
    {
        TenantContext::assertWritablePeriod('faturalar');
        require_once MODELS_PATH . '/Urun.php';
        $uModel = new Urun();

        $this->db->begin();
        try {
            $mevcut = $this->getir($id);
            if (!$mevcut) {
                throw new RuntimeException('Fatura bulunamadı.');
            }
            if ($mevcut['durum'] === 'iptal') {
                throw new RuntimeException('İptal edilmiş fatura düzenlenemez.');
            }

            // Eski kalemlerin stok/bakiye etkisini geri al
            $this->reverseStockAndBalance($mevcut);

            // Eski kalemleri kaldır
            $this->db->update('fatura_kalemleri', ['silindi_mi' => 1], ['fatura_id' => $id]);

            $temizFatura = array_intersect_key($fatura, array_flip($this->fillable));
            $temizFatura['depo_id'] = $depoId;
            $this->assertCariBelongsToCompany($temizFatura['cari_id'] ?? null);
            $this->db->update('faturalar', $temizFatura, ['id' => $id]);
            Audit::log('UPDATE', $this->moduleForBelgeTipi($mevcut), $id, $mevcut, $temizFatura, "Fatura güncellendi: " . ($temizFatura['fatura_no'] ?? $mevcut['fatura_no']));

            // reverseStockAndBalance() yukarıda kaynak belgeyi "kullanılmadı"
            // durumuna geri almıştı (iptal/silme senaryosu için) — bu fatura hâlâ
            // aynı kaynağa bağlıysa (düzenleme, bağlantı değişmedi) tekrar
            // "kullanıldı" olarak işaretle; guard sayesinde başka bir faturaya
            // bu arada bağlanmışsa hata fırlatır.
            if (!empty($temizFatura['kaynak_irsaliye_id'])) {
                $this->kaynakBelgeIsaretle((int)$temizFatura['kaynak_irsaliye_id'], 'irsaliye', 'irsaliye_kullanildi', true, 'Kaynak irsaliye bulunamadı veya bu arada başka bir faturaya bağlanmış.');
            }
            if (!empty($temizFatura['kaynak_teklif_id'])) {
                $this->kaynakBelgeIsaretle((int)$temizFatura['kaynak_teklif_id'], 'proforma', 'teklif_kullanildi', true, 'Kaynak teklif bulunamadı veya bu arada başka bir satışa bağlanmış.');
            }

            $stokPlani = $this->stokHareketPlani($fatura, $depoId);

            foreach ($kalemler as $sira => $k) {
                $miktar      = (float)($k['miktar']       ?? 1);
                $birimFiyat  = (float)($k['birim_fiyat']  ?? 0);
                $kdvOrani    = (float)($k['kdv_orani']    ?? 20);
                $iskontoOran = (float)($k['iskonto_orani'] ?? 0);

                $araToplam    = $miktar * $birimFiyat;
                $iskontoTutar = $araToplam * ($iskontoOran / 100);
                $kdvTabani    = $araToplam - $iskontoTutar;
                $kdvTutari    = $kdvTabani * ($kdvOrani / 100);
                $toplam       = $kdvTabani + $kdvTutari;

                $kalemVeri = [
                    'fatura_id'      => $id,
                    'urun_id'        => !empty($k['urun_id']) ? (int)$k['urun_id'] : null,
                    'urun_adi'       => $k['urun_adi'] ?? '',
                    'aciklama'       => $k['aciklama'] ?? null,
                    'miktar'         => $miktar,
                    'birim'          => $k['birim'] ?? 'Adet',
                    'birim_fiyat'    => $birimFiyat,
                    'iskonto_orani'  => $iskontoOran,
                    'iskonto_tutari' => round($iskontoTutar, 2),
                    'kdv_orani'      => $kdvOrani,
                    'kdv_tutari'     => round($kdvTutari, 2),
                    'ara_toplam'     => round($araToplam, 2),
                    'toplam'         => round($toplam, 2),
                    'sira_no'        => $sira + 1,
                    'company_id'      => TenantContext::activeCompanyId(),
                    'period_id'       => TenantContext::activePeriodId(),
                ];
                $this->assertProductBelongsToCompany($kalemVeri['urun_id']);

                $this->db->insert('fatura_kalemleri', $kalemVeri);

                if ($kalemVeri['urun_id']) {
                    $moveDesc = $this->stokAciklamaEtiketi($fatura['belge_tipi']) . ' #' . ($fatura['fatura_no'] ?? $mevcut['fatura_no']) . ' düzenleme';
                    foreach ($stokPlani as $hareket) {
                        $uModel->stokHareketiEkle($kalemVeri['urun_id'], $miktar, $hareket['tip'], $moveDesc, $hareket['depo_id']);
                    }
                }
            }

            if ((int)($fatura['cari_id'] ?? 0) > 0) {
                $this->recomputeCariBalance((int)$fatura['cari_id']);
            }

            $this->db->commit();
            return $id;
        } catch (\Throwable $e) {
            if ($this->db->pdo()->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Cari bakiyesini faturalar VE kasa hareketleri (tahsilat/ödeme) üzerinden
     * yeniden hesaplar ve yazar. Tek doğruluk kaynağı — Nakit modeli de
     * (tahsilat/ödeme kaydından sonra) bunu çağırır, kendi kopya sorgusunu
     * tutmaz; aksi hâlde iki farklı yerde iki farklı bakiye formülü birbirini
     * geçersiz kılabilir.
     *
     * Satış/perakende (+), alış (-), iadeler karşıt yöndeki faturanın
     * işaretiyle (iade_satis -, iade_alis +) katkı yapar; tahsilat bakiyeyi
     * azaltır (-), ödeme artırır (+).
     */
    public function recomputeCariBalance(int $cariId): void
    {
        $this->db->query("UPDATE cariler SET bakiye = (
            SELECT COALESCE(SUM(CASE
                WHEN belge_tipi IN ('satis', 'perakende') THEN genel_toplam
                WHEN belge_tipi = 'alis' THEN -genel_toplam
                WHEN belge_tipi = 'iade_satis' THEN -genel_toplam
                WHEN belge_tipi = 'iade_alis' THEN genel_toplam
                ELSE 0 END), 0)
            FROM faturalar
            WHERE cari_id = :cid AND silindi_mi = 0 AND durum <> 'iptal'
              AND company_id = :company_id
        ) - (
            SELECT COALESCE(SUM(tutar), 0) FROM kasa_hareketleri
            WHERE cari_id = :cid3 AND islem_tipi = 'giris' AND silindi_mi = 0 AND company_id = :company_id3
        ) + (
            SELECT COALESCE(SUM(tutar), 0) FROM kasa_hareketleri
            WHERE cari_id = :cid4 AND islem_tipi = 'cikis' AND silindi_mi = 0 AND company_id = :company_id4
        ) WHERE id = :cid2 AND company_id = :company_id2", [
            ':cid' => $cariId,
            ':cid2' => $cariId,
            ':cid3' => $cariId,
            ':cid4' => $cariId,
            ':company_id' => TenantContext::activeCompanyId(),
            ':company_id2' => TenantContext::activeCompanyId(),
            ':company_id3' => TenantContext::activeCompanyId(),
            ':company_id4' => TenantContext::activeCompanyId(),
        ]);
    }

    /**
     * Bir faturanın kalemlerindeki stok hareketlerini ters yönde uygular
     * (iptal veya silme anında stoğu geri almak için) ve cari bakiyesini
     * yeniden hesaplar.
     */
    private function reverseStockAndBalance(array $fatura): void
    {
        $depoId = !empty($fatura['depo_id']) ? (int)$fatura['depo_id'] : 1;
        $stokPlani = $this->stokHareketPlani($fatura, $depoId);

        if (!empty($stokPlani)) {
            require_once MODELS_PATH . '/Urun.php';
            $uModel = new Urun();
            $moveDesc = $this->stokAciklamaEtiketi($fatura['belge_tipi']) . ' #' . $fatura['fatura_no'] . ' iptal/silme — stok geri alma';

            $kalemler = $this->kalemleriGetir((int)$fatura['id']);
            foreach ($kalemler as $kalem) {
                if (empty($kalem['urun_id'])) {
                    continue;
                }
                foreach ($stokPlani as $hareket) {
                    $tersTip = $hareket['tip'] === 'giris' ? 'cikis' : 'giris';
                    $uModel->stokHareketiEkle((int)$kalem['urun_id'], (float)$kalem['miktar'], $tersTip, $moveDesc, $hareket['depo_id']);
                }
            }
        }

        // İrsaliyeden/teklifden dönüştürülen bir fatura iptal/silinirse, kaynak
        // belge yeniden "kullanılmadı" durumuna döner ki tekrar kullanılabilsin.
        if (!empty($fatura['kaynak_irsaliye_id'])) {
            $this->kaynakBelgeIsaretle((int)$fatura['kaynak_irsaliye_id'], 'irsaliye', 'irsaliye_kullanildi', false);
        }
        if (!empty($fatura['kaynak_teklif_id'])) {
            $this->kaynakBelgeIsaretle((int)$fatura['kaynak_teklif_id'], 'proforma', 'teklif_kullanildi', false);
        }

        if ((int)$fatura['cari_id'] > 0) {
            $this->recomputeCariBalance((int)$fatura['cari_id']);
        }
    }

    // ─── Durum Değiştirme ────────────────────────────────────────────────

    /** Faturayı iptal eder; daha önce yazılmış stok hareketini ve cari bakiyesini geri alır. */
    public function iptalEt(int $id): int
    {
        $this->db->begin();
        try {
            $fatura = $this->getir($id);
            if (!$fatura) {
                $this->db->rollBack();
                return 0;
            }
            if ($fatura['durum'] === 'iptal') {
                // Zaten iptal — tekrar stok/bakiye geri alma (çift ters kayıt olmasın)
                $this->db->commit();
                return 0;
            }
            $sonuc = $this->db->update('faturalar', ['durum' => 'iptal'], ['id' => $id]);
            $this->reverseStockAndBalance($fatura);
            Audit::log('UPDATE', $this->moduleForBelgeTipi($fatura), $id, ['durum' => $fatura['durum']], ['durum' => 'iptal'], "Fatura iptal edildi: " . ($fatura['fatura_no'] ?? ''));
            $this->db->commit();
            return $sonuc;
        } catch (\Throwable $e) {
            if ($this->db->pdo()->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /** Taslak faturayı onaylar (stok/bakiye zaten kayıt anında işlendiği için burada tekrar dokunulmaz). */
    public function onaylaEt(int $id): int
    {
        $this->db->begin();
        try {
            $fatura = $this->getir($id);
            if (!$fatura) {
                $this->db->rollBack();
                return 0;
            }
            if ($fatura['durum'] !== 'taslak') {
                $this->db->commit();
                return 0;
            }
            $sonuc = $this->db->update('faturalar', ['durum' => 'onaylandi'], ['id' => $id]);
            Audit::log('UPDATE', $this->moduleForBelgeTipi($fatura), $id, ['durum' => 'taslak'], ['durum' => 'onaylandi'], "Fatura onaylandı: " . ($fatura['fatura_no'] ?? ''));
            $this->db->commit();
            return $sonuc;
        } catch (\Throwable $e) {
            if ($this->db->pdo()->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function onayla(int $id): int
    {
        return $this->db->update('faturalar', ['durum' => 'onaylandi'], ['id' => $id]);
    }

    /** Faturayı siler; hâlâ 'iptal' değilse (yani stoğa/bakiyeye hâlâ etkisi varsa) önce geri alır. */
    public function sil(int $id): int
    {
        $this->db->begin();
        try {
            $fatura = $this->getir($id);
            if (!$fatura) {
                $this->db->rollBack();
                return 0;
            }
            if ($fatura['durum'] !== 'iptal') {
                $this->reverseStockAndBalance($fatura);
            }
            $sonuc = $this->db->softDelete('faturalar', $id);
            Audit::log('DELETE', $this->moduleForBelgeTipi($fatura), $id, $fatura, null, "Fatura silindi: " . ($fatura['fatura_no'] ?? ''));
            $this->db->commit();
            return $sonuc;
        } catch (\Throwable $e) {
            if ($this->db->pdo()->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    // ─── Fatura No Üretimi ───────────────────────────────────────────────

    /**
     * Örn: SAT-2024-00042
     * belge_tipi: satis → SAT, alis → ALI, perakende → PRK, vb.
     */
    public function faturaNoUret(string $belge_tipi = 'satis'): string
    {
        $settings = $this->db->selectOne(
            "SELECT * FROM company_settings WHERE company_id = :cid",
            [':cid' => TenantContext::activeCompanyId()]
        ) ?? [];
        $prefix = match($belge_tipi) {
            'alis'        => $settings['purchase_invoice_prefix'] ?? 'ALI',
            'satis'       => $settings['invoice_prefix'] ?? 'SAT',
            'perakende'   => 'PRK',
            'iade_satis'  => 'IDS',
            'iade_alis'   => 'IDA',
            'proforma'    => 'PRO',
            'siparis'     => 'SIP',
            'irsaliye'    => 'IRS',
            default       => 'SAT',
        };
        $period = TenantContext::activePeriod();
        $yil = (string)($period['fiscal_year'] ?? date('Y'));

        $row = $this->db->selectOne(
            "SELECT MAX(CAST(SUBSTRING_INDEX(fatura_no, '-', -1) AS UNSIGNED)) AS maks
             FROM faturalar
             WHERE belge_tipi = :tip AND fatura_no LIKE :prefix
               AND company_id = :company_id AND period_id = :period_id",
            [
                ':tip' => $belge_tipi,
                ':prefix' => "{$prefix}-{$yil}-%",
                ':company_id' => TenantContext::activeCompanyId(),
                ':period_id' => TenantContext::activePeriodId(),
            ]
        );

        $siradaki = (int)($row['maks'] ?? 0) + 1;
        return sprintf('%s-%s-%06d', $prefix, $yil, $siradaki);
    }

    // ─── İrsaliye → Fatura Dönüştürme ─────────────────────────────────────

    /**
     * Henüz faturalandırılmamış, müşteriye sevk türündeki irsaliyeleri döner
     * ("Yeni Fatura" ekranındaki "İrsaliyeden Doldur" seçimi için). İptal edilmiş
     * ve depolar arası (müşterisiz) irsaliyeler listeye girmez.
     */
    /**
     * @param string $sevkTuru 'musteri' (Satışlar ekranı — müşteriye sevk edilen,
     *   henüz faturalandırılmamış irsaliyeler) veya 'tedarikci' (Alışlar ekranı —
     *   tedarikçiden teslim alınan, henüz faturalandırılmamış irsaliyeler).
     */
    public function faturalandirilmamisIrsaliyeler(?int $cariId = null, int $limit = 100, string $sevkTuru = 'musteri'): array
    {
        $sevkTuru = $sevkTuru === 'tedarikci' ? 'tedarikci' : 'musteri';
        $params = [
            ':company_id' => TenantContext::activeCompanyId(),
            ':period_id' => TenantContext::activePeriodId(),
            ':limit' => $limit,
        ];
        $cariSql = '';
        if ($cariId) {
            $cariSql = ' AND f.cari_id = :cari_id';
            $params[':cari_id'] = $cariId;
        }
        // 'musteri' değerinin yanı sıra NULL da tarihsel olarak "müşteriye sevk"
        // anlamına gelir (sevk_turu kolonu sonradan eklendi — bkz. ensureIrsaliyeSevkColumns).
        $sevkTuruSql = $sevkTuru === 'tedarikci' ? "f.sevk_turu = 'tedarikci'" : "(f.sevk_turu IS NULL OR f.sevk_turu = 'musteri')";
        return $this->db->select(
            "SELECT f.id, f.fatura_no, f.fatura_tarihi, f.cari_id, f.genel_toplam,
                    COALESCE(c.unvan, 'Cari yok') AS cari_unvan
             FROM faturalar f
             LEFT JOIN cariler c ON c.id = f.cari_id
             WHERE f.silindi_mi = 0 AND f.belge_tipi = 'irsaliye'
               AND {$sevkTuruSql}
               AND f.irsaliye_kullanildi = 0 AND f.durum <> 'iptal'
               AND f.company_id = :company_id AND f.period_id = :period_id
               {$cariSql}
             ORDER BY f.fatura_tarihi DESC, f.id DESC
             LIMIT :limit",
            $params
        );
    }

    /** Henüz satışa dönüştürülmemiş teklifleri (proforma) döner ("Yeni Fatura" ekranındaki "Tekliften Doldur" seçimi için). */
    public function faturalandirilmamisTeklifler(?int $cariId = null, int $limit = 100): array
    {
        $params = [
            ':company_id' => TenantContext::activeCompanyId(),
            ':period_id' => TenantContext::activePeriodId(),
            ':limit' => $limit,
        ];
        $cariSql = '';
        if ($cariId) {
            $cariSql = ' AND f.cari_id = :cari_id';
            $params[':cari_id'] = $cariId;
        }
        return $this->db->select(
            "SELECT f.id, f.fatura_no, f.fatura_tarihi, f.cari_id, f.genel_toplam,
                    COALESCE(c.unvan, 'Cari yok') AS cari_unvan
             FROM faturalar f
             LEFT JOIN cariler c ON c.id = f.cari_id
             WHERE f.silindi_mi = 0 AND f.belge_tipi = 'proforma'
               AND f.teklif_kullanildi = 0 AND f.durum <> 'iptal'
               AND f.company_id = :company_id AND f.period_id = :period_id
               {$cariSql}
             ORDER BY f.fatura_tarihi DESC, f.id DESC
             LIMIT :limit",
            $params
        );
    }

    // ─── AJAX Yardımcı ──────────────────────────────────────────────────

    /** Müşteri / cari arama (satış formunda autocomplete için). */
    public function cariAra(string $q, string $tip = 'musteri', int $limit = 20): array
    {
        $like = '%' . $q . '%';

        if ($q === '') {
            // Boş arama: tüm kayıtları getir
            return $this->db->select(
                "SELECT id, unvan, telefon, bakiye, para_birimi
                 FROM cariler
                 WHERE silindi_mi = 0
                   AND company_id = :company_id
                   AND (tip = :tip OR tip = 'her_ikisi')
                 ORDER BY unvan
                 LIMIT :limit",
                [':tip' => $tip, ':limit' => $limit, ':company_id' => TenantContext::activeCompanyId()]
            );
        }

        return $this->db->select(
            "SELECT id, unvan, telefon, bakiye, para_birimi
             FROM cariler
             WHERE silindi_mi = 0
               AND company_id = :company_id
               AND (tip = :tip OR tip = 'her_ikisi')
               AND (unvan LIKE :q1 OR cari_kodu LIKE :q2 OR vergi_no LIKE :q3)
             ORDER BY unvan
             LIMIT :limit",
            [':tip' => $tip, ':q1' => $like, ':q2' => $like, ':q3' => $like, ':limit' => $limit, ':company_id' => TenantContext::activeCompanyId()]
        );
    }

    /** Ürün arama (satış formunda autocomplete için). */
    public function urunAra(string $q, int $limit = 20): array
    {
        $like = '%' . $q . '%';
        return $this->db->select(
            "SELECT id, ad, tip, birim, satis_fiyati, alis_fiyati, kdv_orani, stok_miktari, para_birimi, koli_ici_adet
             FROM urunler_hizmetler
             WHERE silindi_mi = 0
               AND company_id = :company_id
               AND (ad LIKE :q1 OR stok_kodu LIKE :q2 OR barkod LIKE :q3)
             ORDER BY ad
             LIMIT :limit",
            [':q1' => $like, ':q2' => $like, ':q3' => $like, ':limit' => $limit, ':company_id' => TenantContext::activeCompanyId()]
        );
    }

    /**
     * Verilen ürün id'leri için koli_ici_adet değerlerini toplu getirir.
     * Satış/alış fatura kalemlerinde "Koli" girişini adete çevirmek için
     * sunucu tarafında yetkili kaynak — istemciden gelen değere güvenilmez.
     * @return array<int,float> [urun_id => koli_ici_adet] (0'dan büyük olanlar)
     */
    public function koliIciAdetMap(array $urunIdler): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $urunIdler))));
        if (empty($ids)) {
            return [];
        }
        $params = [':company_id' => TenantContext::activeCompanyId()];
        $placeholders = [];
        foreach ($ids as $i => $id) {
            $key = ":id{$i}";
            $placeholders[] = $key;
            $params[$key] = $id;
        }
        $rows = $this->db->select(
            "SELECT id, koli_ici_adet FROM urunler_hizmetler
             WHERE id IN (" . implode(',', $placeholders) . ") AND company_id = :company_id",
            $params
        );
        $map = [];
        foreach ($rows as $row) {
            $deger = (float)($row['koli_ici_adet'] ?? 0);
            if ($deger > 0) {
                $map[(int)$row['id']] = $deger;
            }
        }
        return $map;
    }

    /**
     * Bir fatura kalemi için girilen miktarı, giriş tipine göre adete çevirir.
     * "koli" ve ürünün geçerli bir koli_ici_adet'i varsa çarpar; aksi halde
     * girileni adet olarak aynen kabul eder (mevcut davranış — geriye dönük uyumlu).
     */
    public static function kalemMiktarCevir(?int $urunId, float $miktarGirilen, string $girisTipi, array $koliMap): float
    {
        if ($girisTipi === 'koli' && $urunId !== null && ($koliMap[$urunId] ?? 0) > 0) {
            return $miktarGirilen * $koliMap[$urunId];
        }
        return $miktarGirilen;
    }

    // ─── Private Helpers ────────────────────────────────────────────────

    private function buildWhere(
        string $belge_tipi,
        string $arama,
        string $durum,
        string $donem,
        bool   $iptalleri
    ): array {
        $conds  = ['f.silindi_mi = 0', 'f.belge_tipi = :belge_tipi'];
        $params = [':belge_tipi' => $belge_tipi];
        $conds[] = 'f.company_id = :tenant_company_id';
        $conds[] = 'f.period_id = :tenant_period_id';
        $params[':tenant_company_id'] = TenantContext::activeCompanyId();
        $params[':tenant_period_id'] = TenantContext::activePeriodId();

        if (!$iptalleri) {
            $conds[] = "f.durum <> 'iptal'";
        }
        if ($durum !== '') {
            $conds[]       = 'f.durum = :durum';
            $params[':durum'] = $durum;
        }
        if ($arama !== '') {
            $conds[]       = "(c.unvan LIKE :arama1 OR f.fatura_no LIKE :arama2)";
            $params[':arama1'] = '%' . $arama . '%';
            $params[':arama2'] = '%' . $arama . '%';
        }

        // Dönem filtresi
        switch ($donem) {
            case 'bugun':
                $conds[] = 'f.fatura_tarihi = CURDATE()'; break;
            case 'haftaici':
                $conds[] = 'f.fatura_tarihi >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)'; break;
            case '1ay':
                $conds[] = 'f.fatura_tarihi >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)'; break;
            case 'bu_ay':
                $conds[] = 'MONTH(f.fatura_tarihi) = MONTH(CURDATE()) AND YEAR(f.fatura_tarihi) = YEAR(CURDATE())'; break;
            case 'bu_yil':
                $conds[] = 'YEAR(f.fatura_tarihi) = YEAR(CURDATE())'; break;
            // 'tumu' → filtre yok
        }

        return [implode(' AND ', $conds), $params];
    }

    private function assertCariBelongsToCompany($cariId): void
    {
        if (empty($cariId)) {
            return;
        }
        $row = $this->db->selectOne(
            "SELECT id FROM cariler WHERE id = :id AND company_id = :cid AND silindi_mi = 0",
            [':id' => (int)$cariId, ':cid' => TenantContext::activeCompanyId()]
        );
        if (!$row) {
            throw new RuntimeException('Seçilen cari aktif şirkete ait değil.');
        }
    }

    private function assertProductBelongsToCompany($urunId): void
    {
        if (empty($urunId)) {
            return;
        }
        $row = $this->db->selectOne(
            "SELECT id FROM urunler_hizmetler WHERE id = :id AND company_id = :cid AND silindi_mi = 0",
            [':id' => (int)$urunId, ':cid' => TenantContext::activeCompanyId()]
        );
        if (!$row) {
            throw new RuntimeException('Fatura kalemindeki ürün aktif şirkete ait değil.');
        }
    }
}
