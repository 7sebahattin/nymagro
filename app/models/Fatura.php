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

    /** Stoğu etkileyen belge tipleri (fatura_kalemleri üzerinden stok_hareketleri yazan tipler). */
    private const STOK_ETKILEYEN_TIPLER = ['satis', 'alis', 'iade_satis', 'iade_alis', 'perakende'];

    private array $fillable = [
        'belge_tipi', 'fatura_no', 'cari_id', 'fatura_tarihi', 'vade_tarihi',
        'ara_toplam', 'iskonto_tutari', 'kdv_tutari', 'genel_toplam',
        'odenen_tutar', 'kalan_tutar', 'para_birimi', 'kur',
        'ara_toplam_doviz', 'iskonto_tutari_doviz', 'kdv_tutari_doviz', 'genel_toplam_doviz',
        'durum', 'odeme_sekli', 'aciklama', 'company_id', 'period_id', 'depo_id',
        'created_by',
    ];

    private array $kalemFillable = [
        'fatura_id', 'urun_id', 'urun_adi', 'aciklama', 'miktar', 'birim',
        'birim_fiyat', 'iskonto_orani', 'iskonto_tutari',
        'kdv_orani', 'kdv_tutari', 'ara_toplam', 'toplam', 'sira_no', 'company_id', 'period_id',
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->ensureDepoIdColumn();
        $this->ensureDovizColumns();
        $this->ensureCreatedByColumn();
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

            $islemTipi = in_array($fatura['belge_tipi'], ['alis', 'iade_satis']) ? 'giris' : 'cikis';

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

                // Stok güncelleme (yalnızca stoğu etkileyen belge tipleri için)
                if ($kalemVeri['urun_id'] && in_array($fatura['belge_tipi'], self::STOK_ETKILEYEN_TIPLER, true)) {
                    $moveDesc = ($fatura['belge_tipi'] === 'alis' ? 'Alış Faturası' : 'Satış Faturası') . ' #' . $fatura['fatura_no'];
                    $uModel->stokHareketiEkle($kalemVeri['urun_id'], $miktar, $islemTipi, $moveDesc, $depoId);
                }
            }

            // Stok güncelleme bitti, şimdi CARİ BAKİYE güncelle
            if ((int)$fatura['cari_id'] > 0) {
                $this->recomputeCariBalance((int)$fatura['cari_id']);
            }

            $this->db->commit();
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

            $islemTipi = in_array($fatura['belge_tipi'], ['alis', 'iade_satis'], true) ? 'giris' : 'cikis';

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

                if ($kalemVeri['urun_id'] && in_array($fatura['belge_tipi'], self::STOK_ETKILEYEN_TIPLER, true)) {
                    $moveDesc = 'Fatura #' . ($fatura['fatura_no'] ?? $mevcut['fatura_no']) . ' düzenleme';
                    $uModel->stokHareketiEkle($kalemVeri['urun_id'], $miktar, $islemTipi, $moveDesc, $depoId);
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
        if (!in_array($fatura['belge_tipi'], self::STOK_ETKILEYEN_TIPLER, true)) {
            return;
        }
        require_once MODELS_PATH . '/Urun.php';
        $uModel = new Urun();
        $depoId = !empty($fatura['depo_id']) ? (int)$fatura['depo_id'] : 1;
        $tersIslemTipi = in_array($fatura['belge_tipi'], ['alis', 'iade_satis'], true) ? 'cikis' : 'giris';

        $kalemler = $this->kalemleriGetir((int)$fatura['id']);
        foreach ($kalemler as $kalem) {
            if (empty($kalem['urun_id'])) {
                continue;
            }
            $moveDesc = 'Fatura #' . $fatura['fatura_no'] . ' iptal/silme — stok geri alma';
            $uModel->stokHareketiEkle((int)$kalem['urun_id'], (float)$kalem['miktar'], $tersIslemTipi, $moveDesc, $depoId);
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
