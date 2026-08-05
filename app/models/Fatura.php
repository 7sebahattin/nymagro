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
        'durum', 'odeme_sekli', 'aciklama', 'company_id', 'period_id',
    ];

    private array $kalemFillable = [
        'fatura_id', 'urun_id', 'urun_adi', 'aciklama', 'miktar', 'birim',
        'birim_fiyat', 'iskonto_orani', 'iskonto_tutari',
        'kdv_orani', 'kdv_tutari', 'ara_toplam', 'toplam', 'sira_no', 'company_id', 'period_id',
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
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

        $sql = "SELECT f.*, c.unvan AS cari_unvan
                FROM faturalar f
                LEFT JOIN cariler c ON c.id = f.cari_id
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
            "SELECT * FROM fatura_kalemleri
             WHERE fatura_id = :fid AND silindi_mi = 0
               AND company_id = :cid
             ORDER BY sira_no, id",
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

                // Stok güncelleme (Sadece resmi belgeler için)
                if ($kalemVeri['urun_id'] && in_array($fatura['belge_tipi'], ['satis', 'alis', 'iade_satis', 'iade_alis'])) {
                    $moveDesc = ($fatura['belge_tipi'] === 'alis' ? 'Alış Faturası' : 'Satış Faturası') . ' #' . $fatura['fatura_no'];
                    $uModel->stokHareketiEkle($kalemVeri['urun_id'], $miktar, $islemTipi, $moveDesc, $depoId);
                }
            }

            // Stok güncelleme bitti, şimdi CARİ BAKİYE güncelle
            $cariId = (int)$fatura['cari_id'];
            if ($cariId > 0) {
                // Cari bakiyesini faturalar üzerinden hesapla ve güncelle
                // Satış (+) , Alış (-) mantığıyla (veya tam tersi ticari yöne göre)
                // Bu sistemde bakiye: (Alacak - Borç) veya (Borç - Alacak)
                // Genelde: Borç = Satış Faturaları, Alacak = Alış Faturaları + Tahsilatlar
                $this->db->query("UPDATE cariler SET bakiye = (
                    SELECT COALESCE(SUM(CASE 
                        WHEN belge_tipi IN ('satis', 'perakende') THEN genel_toplam 
                        WHEN belge_tipi = 'alis' THEN -genel_toplam 
                        ELSE 0 END), 0)
                    FROM faturalar 
                    WHERE cari_id = :cid AND silindi_mi = 0 AND durum <> 'iptal'
                      AND company_id = :company_id
                ) WHERE id = :cid2 AND company_id = :company_id2", [
                    ':cid' => $cariId,
                    ':cid2' => $cariId,
                    ':company_id' => TenantContext::activeCompanyId(),
                    ':company_id2' => TenantContext::activeCompanyId(),
                ]);
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

    // ─── Durum Değiştirme ────────────────────────────────────────────────

    public function iptalEt(int $id): int
    {
        return $this->db->update('faturalar', ['durum' => 'iptal'], ['id' => $id]);
    }

    public function onayla(int $id): int
    {
        return $this->db->update('faturalar', ['durum' => 'onaylandi'], ['id' => $id]);
    }

    public function sil(int $id): int
    {
        return $this->db->softDelete('faturalar', $id);
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
            "SELECT id, ad, tip, birim, satis_fiyati, alis_fiyati, kdv_orani, stok_miktari, para_birimi
             FROM urunler_hizmetler
             WHERE silindi_mi = 0
               AND company_id = :company_id
               AND (ad LIKE :q1 OR stok_kodu LIKE :q2 OR barkod LIKE :q3)
             ORDER BY ad
             LIMIT :limit",
            [':q1' => $like, ':q2' => $like, ':q3' => $like, ':limit' => $limit, ':company_id' => TenantContext::activeCompanyId()]
        );
    }

    // ─── Private Helpers ────────────────────────────────────────────────

    private function buildWhere(
        string $belge_tipi,
        string $arama,
        string $durum,
        string $donem,
        bool   $iptalleri
    ): array {
        if ($belge_tipi === 'satis') {
            $conds  = ['f.silindi_mi = 0', "f.belge_tipi IN ('satis', 'proforma', 'irsaliye')"];
            $params = [];
        } else {
            $conds  = ['f.silindi_mi = 0', 'f.belge_tipi = :belge_tipi'];
            $params = [':belge_tipi' => $belge_tipi];
        }
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
