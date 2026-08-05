<?php

/**
 * Gelen E-Fatura ve Gider Takip Modeli
 */
class GelenEFatura
{
    private Database $db;
    private array $columns = [
        'A' => 'belge_turu',
        'B' => 'duzenleme_tarihi',
        'C' => 'tedarikci_calisan_adi',
        'D' => 'fatura_ismi',
        'E' => 'kategori',
        'F' => 'doviz_tipi',
        'G' => 'doviz_kuru',
        'H' => 'vade_tarihi',
        'I' => 'genel_toplam',
        'J' => 'genel_toplam_tl',
        'K' => 'toplam_odenen',
        'L' => 'toplam_odenen_tl',
        'M' => 'son_odeme_hesabi',
        'N' => 'son_odeme_tarihi',
        'O' => 'cebinden_odeyen_calisan',
        'P' => 'harcayan',
        'Q' => 'fis_fatura_no',
        'R' => 'toplam_kdv',
        'S' => 'toplam_tevkifat',
        'T' => 'stopaj',
        'U' => 'toplam_oiv',
        'V' => 'toplam_otv',
        'W' => 'urun_hizmet',
        'X' => 'urun_hizmet_kodu',
        'Y' => 'urun_hizmet_aciklamasi',
        'Z' => 'miktar',
        'AA' => 'birim_fiyati',
        'AB' => 'indirim',
        'AC' => 'kdv_orani',
        'AD' => 'kdv_tutari',
        'AE' => 'tevkifat_orani',
        'AF' => 'tevkifat_tutari',
        'AG' => 'otv',
        'AH' => 'oiv',
        'AI' => 'net_tutar',
        'AJ' => 'net_tutar_tl',
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->ensureTables();
    }

    public function ensureTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS gelen_efatura_import_batches (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            dosya_adi VARCHAR(255) NOT NULL,
            toplam_satir INT UNSIGNED NOT NULL DEFAULT 0,
            bulunan_fatura_sayisi INT UNSIGNED NOT NULL DEFAULT 0,
            bulunan_kalem_sayisi INT UNSIGNED NOT NULL DEFAULT 0,
            basarili_fatura INT UNSIGNED NOT NULL DEFAULT 0,
            hatali_satir INT UNSIGNED NOT NULL DEFAULT 0,
            duplicate_fatura INT UNSIGNED NOT NULL DEFAULT 0,
            yukleyen_kullanici INT UNSIGNED NULL,
            import_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            durum VARCHAR(30) NOT NULL DEFAULT 'onizleme',
            hata_ozeti TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_geib_durum (durum),
            KEY idx_geib_tarih (import_tarihi)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS gelen_efaturalar (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            belge_turu VARCHAR(80) NULL,
            duzenleme_tarihi DATE NULL,
            vade_tarihi DATE NULL,
            tedarikci_id INT UNSIGNED NULL,
            tedarikci_calisan_adi VARCHAR(255) NULL,
            fatura_ismi VARCHAR(255) NULL,
            kategori VARCHAR(150) NULL,
            doviz_tipi VARCHAR(10) NOT NULL DEFAULT 'TRY',
            doviz_kuru DECIMAL(18,6) NOT NULL DEFAULT 1.000000,
            genel_toplam DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            genel_toplam_tl DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            toplam_odenen DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            toplam_odenen_tl DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            kalan_tutar DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            kalan_tutar_tl DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            son_odeme_hesabi VARCHAR(150) NULL,
            son_odeme_tarihi DATE NULL,
            cebinden_odeyen_calisan VARCHAR(255) NULL,
            harcayan VARCHAR(255) NULL,
            fis_fatura_no VARCHAR(100) NULL,
            toplam_kdv DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            toplam_tevkifat DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            stopaj DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            toplam_oiv DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            toplam_otv DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            odeme_durumu VARCHAR(30) NOT NULL DEFAULT 'odenmedi',
            vade_durumu VARCHAR(30) NOT NULL DEFAULT 'vade_yok',
            fatura_durumu VARCHAR(30) NOT NULL DEFAULT 'aktif',
            pdf_path VARCHAR(255) NULL,
            kaynak VARCHAR(30) NOT NULL DEFAULT 'excel',
            import_batch_id INT UNSIGNED NULL,
            notlar TEXT NULL,
            created_by INT UNSIGNED NULL,
            updated_by INT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            silindi_mi TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY idx_ge_fatura_no (fis_fatura_no),
            KEY idx_ge_tedarikci (tedarikci_id),
            KEY idx_ge_duzenleme (duzenleme_tarihi),
            KEY idx_ge_vade (vade_tarihi),
            KEY idx_ge_odeme (odeme_durumu),
            KEY idx_ge_batch (import_batch_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS gelen_efatura_kalemleri (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            gelen_efatura_id INT UNSIGNED NOT NULL,
            urun_hizmet VARCHAR(255) NULL,
            urun_hizmet_kodu VARCHAR(100) NULL,
            urun_hizmet_aciklamasi TEXT NULL,
            miktar DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
            birim_fiyati DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
            indirim DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            kdv_orani DECIMAL(8,4) NOT NULL DEFAULT 0.0000,
            kdv_tutari DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            tevkifat_orani VARCHAR(30) NULL,
            tevkifat_tutari DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            otv DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            oiv DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            net_tutar DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            net_tutar_tl DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_gek_fatura (gelen_efatura_id),
            CONSTRAINT fk_gek_fatura FOREIGN KEY (gelen_efatura_id) REFERENCES gelen_efaturalar(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS gelen_efatura_odemeleri (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            gelen_efatura_id INT UNSIGNED NOT NULL,
            odeme_tarihi DATE NULL,
            odeme_tutari DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            odeme_tutari_tl DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            odeme_yontemi VARCHAR(80) NULL,
            odeme_hesabi VARCHAR(150) NULL,
            kasa_banka_hesap_id INT UNSIGNED NULL,
            aciklama TEXT NULL,
            dekont_path VARCHAR(255) NULL,
            created_by INT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_geo_fatura (gelen_efatura_id),
            KEY idx_geo_tarih (odeme_tarihi),
            CONSTRAINT fk_geo_fatura FOREIGN KEY (gelen_efatura_id) REFERENCES gelen_efaturalar(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS gelen_efatura_notlari (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            gelen_efatura_id INT UNSIGNED NOT NULL,
            not_metni TEXT NOT NULL,
            created_by INT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_gen_fatura (gelen_efatura_id),
            CONSTRAINT fk_gen_fatura FOREIGN KEY (gelen_efatura_id) REFERENCES gelen_efaturalar(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");

        $this->ensureTenantColumns();
    }

    public function listele(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        [$where, $params] = $this->buildListWhere($filters);
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;

        return $this->db->select(
            "SELECT ge.*, c.unvan AS tedarikci_unvan,
                    (SELECT COUNT(*) FROM gelen_efatura_kalemleri k
                       WHERE k.gelen_efatura_id = ge.id
                         AND k.company_id = ge.company_id) AS kalem_sayisi,
                    (SELECT COUNT(*) FROM gelen_efatura_notlari n
                       WHERE n.gelen_efatura_id = ge.id
                         AND n.company_id = ge.company_id) AS not_sayisi
             FROM gelen_efaturalar ge
             LEFT JOIN cariler c ON c.id = ge.tedarikci_id AND c.company_id = ge.company_id
             WHERE {$where}
             ORDER BY ge.duzenleme_tarihi DESC, ge.id DESC
             LIMIT :limit OFFSET :offset",
            $params
        );
    }

    public function say(array $filters = []): int
    {
        [$where, $params] = $this->buildListWhere($filters);
        $row = $this->db->selectOne("SELECT COUNT(*) AS toplam FROM gelen_efaturalar ge WHERE {$where}", $params);
        return (int)($row['toplam'] ?? 0);
    }

    public function ozetler(array $filters = []): array
    {
        [$where, $params] = $this->buildListWhere($filters);
        $row = $this->db->selectOne(
            "SELECT
                COUNT(*) AS fatura_sayisi,
                COALESCE(SUM(genel_toplam_tl), 0) AS toplam_borc_tl,
                COALESCE(SUM(toplam_odenen_tl), 0) AS toplam_odenen_tl,
                COALESCE(SUM(kalan_tutar_tl), 0) AS kalan_borc_tl,
                COALESCE(SUM(CASE WHEN vade_durumu = 'vadesi_gecti' THEN kalan_tutar_tl ELSE 0 END), 0) AS vadesi_gecen_tl,
                COALESCE(SUM(CASE WHEN duzenleme_tarihi >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN genel_toplam_tl ELSE 0 END), 0) AS bu_ay_tl,
                SUM(CASE WHEN pdf_path IS NULL OR pdf_path = '' THEN 1 ELSE 0 END) AS pdf_eksik_sayisi,
                SUM(CASE WHEN odeme_durumu = 'kismi_odendi' THEN 1 ELSE 0 END) AS kismi_odenen_sayisi
             FROM gelen_efaturalar ge
             WHERE {$where}",
            $params
        );
        return $row ?? [];
    }

    public function detay(int $id): ?array
    {
        return $this->db->selectOne(
            "SELECT ge.*, c.unvan AS tedarikci_unvan
             FROM gelen_efaturalar ge
             LEFT JOIN cariler c ON c.id = ge.tedarikci_id AND c.company_id = ge.company_id
             WHERE ge.id = :id AND ge.silindi_mi = 0
               AND ge.company_id = :company_id AND ge.period_id = :period_id",
            [':id' => $id, ':company_id' => TenantContext::activeCompanyId(), ':period_id' => TenantContext::activePeriodId()]
        );
    }

    public function kalemler(int $id): array
    {
        return $this->db->select(
            "SELECT k.*
             FROM gelen_efatura_kalemleri k
             JOIN gelen_efaturalar ge ON ge.id = k.gelen_efatura_id
             WHERE k.gelen_efatura_id = :id
               AND k.company_id = :company_id AND ge.company_id = :company_id
               AND ge.period_id = :period_id
             ORDER BY k.id",
            [':id' => $id, ':company_id' => TenantContext::activeCompanyId(), ':period_id' => TenantContext::activePeriodId()]
        );
    }

    public function odemeler(int $id): array
    {
        return $this->db->select(
            "SELECT o.*
             FROM gelen_efatura_odemeleri o
             JOIN gelen_efaturalar ge ON ge.id = o.gelen_efatura_id
             WHERE o.gelen_efatura_id = :id
               AND o.company_id = :company_id AND ge.company_id = :company_id
               AND o.period_id = :period_id AND ge.period_id = :period_id
             ORDER BY o.odeme_tarihi DESC, o.id DESC",
            [':id' => $id, ':company_id' => TenantContext::activeCompanyId(), ':period_id' => TenantContext::activePeriodId()]
        );
    }

    public function notlar(int $id): array
    {
        return $this->db->select(
            "SELECT n.*
             FROM gelen_efatura_notlari n
             JOIN gelen_efaturalar ge ON ge.id = n.gelen_efatura_id
             WHERE n.gelen_efatura_id = :id
               AND n.company_id = :company_id AND ge.company_id = :company_id
               AND n.period_id = :period_id AND ge.period_id = :period_id
             ORDER BY n.id DESC",
            [':id' => $id, ':company_id' => TenantContext::activeCompanyId(), ':period_id' => TenantContext::activePeriodId()]
        );
    }

    public function tedarikciler(): array
    {
        return $this->db->select(
            "SELECT id, unvan
             FROM cariler
             WHERE silindi_mi = 0 AND company_id = :company_id AND tip IN ('tedarikci', 'her_ikisi')
             ORDER BY unvan",
            [':company_id' => TenantContext::activeCompanyId()]
        );
    }

    public function parseExcel(string $path, string $fileName): array
    {
        $zip = null;
        try {
            if (class_exists('ZipArchive')) {
                $zip = new ZipArchive();
                if ($zip->open($path) !== true) {
                    throw new RuntimeException('Excel dosyası açılamadı.');
                }
                $shared = $this->readSharedStrings($zip);
                $sheetPath = $this->findSheetPath($zip, 'Giderler');
                $sheetXml = $zip->getFromName($sheetPath);
                if ($sheetXml === false) {
                    throw new RuntimeException('Giderler sayfası okunamadı.');
                }
            } else {
                $entries = $this->readZipEntries($path);
                $shared = $this->readSharedStringsFromXml($entries['xl/sharedStrings.xml'] ?? '');
                $sheetPath = $this->findSheetPathFromXml(
                    $entries['xl/workbook.xml'] ?? '',
                    $entries['xl/_rels/workbook.xml.rels'] ?? '',
                    'Giderler'
                );
                $sheetXml = $entries[$sheetPath] ?? null;
                if (!$sheetXml) {
                    throw new RuntimeException('Giderler sayfası okunamadı.');
                }
            }
            $rows = $this->readSheetRows($sheetXml, $shared);
            $invoices = [];
            $current = null;
            $totalRows = 0;
            $badRows = 0;
            $warnings = [];

            foreach ($rows as $rowNo => $row) {
                if ($rowNo === 1) {
                    continue;
                }
                $totalRows++;
                $mapped = $this->mapExcelRow($row);
                $hasInvoiceNo = trim((string)($mapped['fis_fatura_no'] ?? '')) !== '';
                $hasLine = trim((string)($mapped['urun_hizmet'] ?? '')) !== '' || trim((string)($mapped['urun_hizmet_aciklamasi'] ?? '')) !== '';
                $hasMain = $hasInvoiceNo || trim((string)($mapped['tedarikci_calisan_adi'] ?? '')) !== '' || trim((string)($mapped['genel_toplam_tl'] ?? '')) !== '';

                if (!$hasMain && !$hasLine) {
                    continue;
                }

                if ($hasInvoiceNo || ($hasMain && $current === null)) {
                    if ($current !== null) {
                        $invoices[] = $this->finishParsedInvoice($current);
                    }
                    $current = $this->newParsedInvoice($mapped, $rowNo);
                } elseif ($hasMain && $current !== null) {
                    foreach ($mapped as $key => $value) {
                        if ($value !== '' && $value !== null && !in_array($key, ['urun_hizmet', 'urun_hizmet_kodu', 'urun_hizmet_aciklamasi', 'miktar', 'birim_fiyati', 'indirim', 'kdv_orani', 'kdv_tutari', 'tevkifat_orani', 'tevkifat_tutari', 'otv', 'oiv', 'net_tutar', 'net_tutar_tl'], true)) {
                            $current[$key] = $value;
                        }
                    }
                } elseif ($hasLine && $current === null) {
                    $badRows++;
                    $warnings[] = "{$rowNo}. satır bir fatura ana kaydı olmadan kalem gibi görünüyor.";
                    continue;
                }

                if ($hasLine && $current !== null) {
                    $current['kalemler'][] = $this->buildLineItem($mapped);
                }
            }

            if ($current !== null) {
                $invoices[] = $this->finishParsedInvoice($current);
            }

            $duplicateCount = 0;
            foreach ($invoices as &$invoice) {
                $duplicate = $this->findDuplicate($invoice);
                if ($duplicate) {
                    $duplicateCount++;
                    $invoice['duplicate_id'] = (int)$duplicate['id'];
                    $invoice['is_duplicate'] = true;
                    $invoice['durum'] = 'duplicate';
                    $invoice['uyari'][] = 'Daha önce yüklenmiş olabilir. Varsayılan işlem: atla.';
                    $invoice['islem'] = 'atla';
                }
            }
            unset($invoice);

            return [
                'dosya_adi' => $fileName,
                'toplam_satir' => $totalRows,
                'bulunan_fatura_sayisi' => count($invoices),
                'bulunan_kalem_sayisi' => array_sum(array_map(fn($i) => count($i['kalemler']), $invoices)),
                'hatali_satir' => $badRows,
                'duplicate_fatura' => $duplicateCount,
                'uyarilar' => $warnings,
                'invoices' => $invoices,
            ];
        } finally {
            if ($zip instanceof ZipArchive) {
                $zip->close();
            }
        }
    }

    public function kaydetPreview(array $preview, ?int $userId = null): array
    {
        $this->db->begin();
        try {
            $batchId = $this->db->insert('gelen_efatura_import_batches', [
                'company_id' => TenantContext::activeCompanyId(),
                'period_id' => TenantContext::activePeriodId(),
                'dosya_adi' => $preview['dosya_adi'] ?? 'excel',
                'toplam_satir' => (int)($preview['toplam_satir'] ?? 0),
                'bulunan_fatura_sayisi' => (int)($preview['bulunan_fatura_sayisi'] ?? 0),
                'bulunan_kalem_sayisi' => (int)($preview['bulunan_kalem_sayisi'] ?? 0),
                'basarili_fatura' => 0,
                'hatali_satir' => (int)($preview['hatali_satir'] ?? 0),
                'duplicate_fatura' => (int)($preview['duplicate_fatura'] ?? 0),
                'yukleyen_kullanici' => $userId,
                'durum' => 'kaydedildi',
                'hata_ozeti' => implode("\n", $preview['uyarilar'] ?? []),
            ]);

            $saved = 0;
            $skipped = 0;
            foreach (($preview['invoices'] ?? []) as $invoice) {
                if (($invoice['islem'] ?? 'kaydet') === 'atla') {
                    $skipped++;
                    continue;
                }
                $id = $this->insertInvoice($invoice, $batchId, $userId, 'excel');
                if ($id > 0) {
                    $saved++;
                }
            }

            $this->db->update('gelen_efatura_import_batches', ['basarili_fatura' => $saved], ['id' => $batchId]);
            $this->db->commit();
            return ['batch_id' => $batchId, 'saved' => $saved, 'skipped' => $skipped];
        } catch (Throwable $e) {
            $this->db->rollBack();
            error_log('Gelen E-Fatura import kaydetme hatası: ' . $e->getMessage());
            throw $e;
        }
    }

    public function recalculatePreviewInvoice(array $invoice): array
    {
        foreach (['duzenleme_tarihi', 'vade_tarihi', 'son_odeme_tarihi'] as $field) {
            if (isset($invoice[$field])) {
                $invoice[$field] = $this->parseDate($invoice[$field]);
            }
        }
        foreach (['doviz_kuru', 'genel_toplam', 'genel_toplam_tl', 'toplam_odenen', 'toplam_odenen_tl', 'toplam_kdv'] as $field) {
            if (isset($invoice[$field])) {
                $invoice[$field] = $this->money($invoice[$field]);
            }
        }
        return $this->finishParsedInvoice($invoice);
    }

    public function manuelKaydet(array $data, ?int $userId = null): int
    {
        $invoice = $this->finishParsedInvoice([
            'belge_turu' => $data['belge_turu'] ?? 'Fatura',
            'duzenleme_tarihi' => $this->parseDate($data['duzenleme_tarihi'] ?? null),
            'vade_tarihi' => $this->parseDate($data['vade_tarihi'] ?? null),
            'tedarikci_id' => !empty($data['tedarikci_id']) ? (int)$data['tedarikci_id'] : null,
            'tedarikci_calisan_adi' => trim((string)($data['tedarikci_calisan_adi'] ?? '')),
            'fatura_ismi' => trim((string)($data['fatura_ismi'] ?? '')),
            'kategori' => trim((string)($data['kategori'] ?? '')),
            'doviz_tipi' => strtoupper(trim((string)($data['doviz_tipi'] ?? 'TRY'))),
            'doviz_kuru' => $this->money($data['doviz_kuru'] ?? 1),
            'genel_toplam' => $this->money($data['genel_toplam'] ?? 0),
            'genel_toplam_tl' => $this->money($data['genel_toplam_tl'] ?? 0),
            'toplam_odenen' => $this->money($data['toplam_odenen'] ?? 0),
            'toplam_odenen_tl' => $this->money($data['toplam_odenen_tl'] ?? 0),
            'son_odeme_hesabi' => trim((string)($data['son_odeme_hesabi'] ?? '')),
            'son_odeme_tarihi' => $this->parseDate($data['son_odeme_tarihi'] ?? null),
            'fis_fatura_no' => trim((string)($data['fis_fatura_no'] ?? '')),
            'toplam_kdv' => $this->money($data['toplam_kdv'] ?? 0),
            'toplam_tevkifat' => $this->money($data['toplam_tevkifat'] ?? 0),
            'stopaj' => $this->money($data['stopaj'] ?? 0),
            'toplam_oiv' => $this->money($data['toplam_oiv'] ?? 0),
            'toplam_otv' => $this->money($data['toplam_otv'] ?? 0),
            'notlar' => trim((string)($data['notlar'] ?? '')),
            'kalemler' => [[
                'urun_hizmet' => trim((string)($data['urun_hizmet'] ?? $data['fatura_ismi'] ?? '')),
                'urun_hizmet_kodu' => trim((string)($data['urun_hizmet_kodu'] ?? '')),
                'urun_hizmet_aciklamasi' => trim((string)($data['urun_hizmet_aciklamasi'] ?? '')),
                'miktar' => $this->money($data['miktar'] ?? 1),
                'birim_fiyati' => $this->money($data['birim_fiyati'] ?? 0),
                'indirim' => $this->money($data['indirim'] ?? 0),
                'kdv_orani' => $this->money($data['kdv_orani'] ?? 0),
                'kdv_tutari' => $this->money($data['kdv_tutari'] ?? 0),
                'tevkifat_orani' => trim((string)($data['tevkifat_orani'] ?? '')),
                'tevkifat_tutari' => $this->money($data['tevkifat_tutari'] ?? 0),
                'otv' => $this->money($data['otv'] ?? 0),
                'oiv' => $this->money($data['oiv'] ?? 0),
                'net_tutar' => $this->money($data['net_tutar'] ?? $data['genel_toplam'] ?? 0),
                'net_tutar_tl' => $this->money($data['net_tutar_tl'] ?? $data['genel_toplam_tl'] ?? 0),
            ]],
        ]);

        return $this->insertInvoice($invoice, null, $userId, 'manuel');
    }

    public function odemeEkle(int $invoiceId, array $data, ?int $userId = null): void
    {
        $invoice = $this->detay($invoiceId);
        if (!$invoice) {
            throw new RuntimeException('Fatura bulunamadı.');
        }

        $tutarTl = $this->money($data['odeme_tutari_tl'] ?? 0);
        if ($tutarTl <= 0) {
            throw new InvalidArgumentException('Ödeme tutarı sıfırdan büyük olmalıdır.');
        }
        if ($tutarTl > (float)$invoice['kalan_tutar_tl'] + 0.01) {
            throw new InvalidArgumentException('Ödeme tutarı kalan tutardan büyük olamaz.');
        }

        $rate = (float)$invoice['doviz_kuru'] > 0 ? (float)$invoice['doviz_kuru'] : 1;
        $tutar = $this->money($data['odeme_tutari'] ?? ($tutarTl / $rate));
        $hesapId = !empty($data['kasa_banka_hesap_id']) ? (int)$data['kasa_banka_hesap_id'] : null;
        $odemeTarihi = $this->parseDate($data['odeme_tarihi'] ?? date('Y-m-d'));

        $this->db->begin();
        try {
            $this->db->insert('gelen_efatura_odemeleri', [
                'gelen_efatura_id' => $invoiceId,
                'company_id' => TenantContext::activeCompanyId(),
                'period_id' => TenantContext::activePeriodId(),
                'odeme_tarihi' => $odemeTarihi,
                'odeme_tutari' => $tutar,
                'odeme_tutari_tl' => $tutarTl,
                'odeme_yontemi' => $data['odeme_yontemi'] ?? 'odeme',
                'odeme_hesabi' => trim((string)($data['odeme_hesabi'] ?? '')),
                'kasa_banka_hesap_id' => $hesapId,
                'aciklama' => trim((string)($data['aciklama'] ?? '')),
                'created_by' => $userId,
            ]);

            $this->refreshPaymentTotals($invoiceId);
            if ($hesapId) {
                $this->createCashMovement($hesapId, $invoiceId, $tutarTl, $odemeTarihi, trim((string)($data['aciklama'] ?? 'Gelen e-fatura ödemesi')));
            }
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            error_log('Gelen E-Fatura ödeme hatası: ' . $e->getMessage());
            throw $e;
        }
    }

    public function notEkle(int $invoiceId, string $note, ?int $userId = null): void
    {
        $note = trim($note);
        if ($note === '') {
            return;
        }
        if (!$this->detay($invoiceId)) {
            throw new RuntimeException('Fatura bulunamadı.');
        }
        $this->db->insert('gelen_efatura_notlari', [
            'gelen_efatura_id' => $invoiceId,
            'company_id' => TenantContext::activeCompanyId(),
            'period_id' => TenantContext::activePeriodId(),
            'not_metni' => $note,
            'created_by' => $userId,
        ]);
        $this->db->update('gelen_efaturalar', ['notlar' => $note], ['id' => $invoiceId]);
    }

    public function pdfGuncelle(int $invoiceId, string $path): void
    {
        $this->db->update('gelen_efaturalar', ['pdf_path' => $path], ['id' => $invoiceId]);
    }

    public function pasifYap(int $invoiceId): void
    {
        $this->db->update('gelen_efaturalar', ['fatura_durumu' => 'iptal', 'silindi_mi' => 1], ['id' => $invoiceId]);
    }

    public function exportRows(array $filters = []): array
    {
        return $this->listele($filters, 5000, 0);
    }

    private function buildListWhere(array $filters): array
    {
        $where = [
            'ge.silindi_mi = 0',
            'ge.company_id = :tenant_company_id',
            'ge.period_id = :tenant_period_id',
        ];
        $params = [
            ':tenant_company_id' => TenantContext::activeCompanyId(),
            ':tenant_period_id' => TenantContext::activePeriodId(),
        ];

        $map = [
            'baslangic' => ['ge.duzenleme_tarihi >= :baslangic', ':baslangic'],
            'bitis' => ['ge.duzenleme_tarihi <= :bitis', ':bitis'],
            'vade_baslangic' => ['ge.vade_tarihi >= :vade_baslangic', ':vade_baslangic'],
            'vade_bitis' => ['ge.vade_tarihi <= :vade_bitis', ':vade_bitis'],
            'belge_turu' => ['ge.belge_turu = :belge_turu', ':belge_turu'],
            'kategori' => ['ge.kategori = :kategori', ':kategori'],
            'doviz_tipi' => ['ge.doviz_tipi = :doviz_tipi', ':doviz_tipi'],
            'odeme_durumu' => ['ge.odeme_durumu = :odeme_durumu', ':odeme_durumu'],
            'vade_durumu' => ['ge.vade_durumu = :vade_durumu', ':vade_durumu'],
            'kaynak' => ['ge.kaynak = :kaynak', ':kaynak'],
            'import_batch_id' => ['ge.import_batch_id = :import_batch_id', ':import_batch_id'],
        ];
        foreach ($map as $key => [$sql, $param]) {
            if (($filters[$key] ?? '') !== '') {
                $where[] = $sql;
                $params[$param] = $filters[$key];
            }
        }

        if (($filters['ara'] ?? '') !== '') {
            $where[] = "(ge.tedarikci_calisan_adi LIKE :ara1 OR ge.fis_fatura_no LIKE :ara2 OR ge.fatura_ismi LIKE :ara3)";
            $like = '%' . $filters['ara'] . '%';
            $params[':ara1'] = $like;
            $params[':ara2'] = $like;
            $params[':ara3'] = $like;
        }
        if (($filters['min_tutar'] ?? '') !== '') {
            $where[] = 'ge.genel_toplam_tl >= :min_tutar';
            $params[':min_tutar'] = (float)$filters['min_tutar'];
        }
        if (($filters['max_tutar'] ?? '') !== '') {
            $where[] = 'ge.genel_toplam_tl <= :max_tutar';
            $params[':max_tutar'] = (float)$filters['max_tutar'];
        }
        if (($filters['pdf'] ?? '') === 'var') {
            $where[] = "ge.pdf_path IS NOT NULL AND ge.pdf_path <> ''";
        } elseif (($filters['pdf'] ?? '') === 'yok') {
            $where[] = "(ge.pdf_path IS NULL OR ge.pdf_path = '')";
        }
        if (($filters['not'] ?? '') === 'var') {
            $where[] = "ge.notlar IS NOT NULL AND ge.notlar <> ''";
        } elseif (($filters['not'] ?? '') === 'yok') {
            $where[] = "(ge.notlar IS NULL OR ge.notlar = '')";
        }
        if (!empty($filters['vadesi_gecen'])) {
            $where[] = "ge.vade_durumu = 'vadesi_gecti'";
        }
        if (!empty($filters['odenmeyen'])) {
            $where[] = "ge.odeme_durumu = 'odenmedi'";
        }
        if (!empty($filters['kismi_odenen'])) {
            $where[] = "ge.odeme_durumu = 'kismi_odendi'";
        }
        if (!empty($filters['pdf_eksik'])) {
            $where[] = "(ge.pdf_path IS NULL OR ge.pdf_path = '')";
        }

        return [implode(' AND ', $where), $params];
    }

    private function ensureTenantColumns(): void
    {
        $companyTables = [
            'gelen_efatura_import_batches',
            'gelen_efaturalar',
            'gelen_efatura_kalemleri',
            'gelen_efatura_odemeleri',
            'gelen_efatura_notlari',
        ];
        $periodTables = [
            'gelen_efatura_import_batches',
            'gelen_efaturalar',
            'gelen_efatura_odemeleri',
            'gelen_efatura_notlari',
        ];

        foreach ($companyTables as $table) {
            if ($this->tableExists($table) && !$this->columnExists($table, 'company_id')) {
                $this->db->query("ALTER TABLE `{$table}` ADD COLUMN company_id INT UNSIGNED NULL AFTER id");
            }
        }
        foreach ($periodTables as $table) {
            if ($this->tableExists($table) && !$this->columnExists($table, 'period_id')) {
                $this->db->query("ALTER TABLE `{$table}` ADD COLUMN period_id INT UNSIGNED NULL AFTER company_id");
            }
        }

        $companyId = TenantContext::activeCompanyId();
        $periodId = TenantContext::activePeriodId();
        $this->db->query("UPDATE gelen_efaturalar SET company_id = :cid WHERE company_id IS NULL", [':cid' => $companyId]);
        $this->db->query("UPDATE gelen_efaturalar SET period_id = :pid WHERE period_id IS NULL", [':pid' => $periodId]);
        $this->db->query("UPDATE gelen_efatura_import_batches SET company_id = :cid WHERE company_id IS NULL", [':cid' => $companyId]);
        $this->db->query("UPDATE gelen_efatura_import_batches SET period_id = :pid WHERE period_id IS NULL", [':pid' => $periodId]);
        $this->db->query(
            "UPDATE gelen_efatura_kalemleri k
             JOIN gelen_efaturalar ge ON ge.id = k.gelen_efatura_id
             SET k.company_id = ge.company_id
             WHERE k.company_id IS NULL"
        );
        $this->db->query(
            "UPDATE gelen_efatura_odemeleri o
             JOIN gelen_efaturalar ge ON ge.id = o.gelen_efatura_id
             SET o.company_id = ge.company_id, o.period_id = ge.period_id
             WHERE o.company_id IS NULL OR o.period_id IS NULL"
        );
        $this->db->query(
            "UPDATE gelen_efatura_notlari n
             JOIN gelen_efaturalar ge ON ge.id = n.gelen_efatura_id
             SET n.company_id = ge.company_id, n.period_id = ge.period_id
             WHERE n.company_id IS NULL OR n.period_id IS NULL"
        );
    }

    private function readSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }
        return $this->readSharedStringsFromXml($xml);
    }

    private function readSharedStringsFromXml(string $xml): array
    {
        if ($xml === '') {
            return [];
        }
        $sx = simplexml_load_string($xml);
        $strings = [];
        foreach ($sx->si as $si) {
            if (isset($si->t)) {
                $strings[] = (string)$si->t;
            } else {
                $text = '';
                foreach ($si->r as $run) {
                    $text .= (string)$run->t;
                }
                $strings[] = $text;
            }
        }
        return $strings;
    }

    private function findSheetPath(ZipArchive $zip, string $preferred): string
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        return $this->findSheetPathFromXml($workbookXml ?: '', $relsXml ?: '', $preferred);
    }

    private function findSheetPathFromXml(string $workbookXml, string $relsXml, string $preferred): string
    {
        if ($workbookXml === false || $relsXml === false) {
            return 'xl/worksheets/sheet1.xml';
        }
        if ($workbookXml === '' || $relsXml === '') {
            return 'xl/worksheets/sheet1.xml';
        }

        $workbook = simplexml_load_string($workbookXml);
        $rels = simplexml_load_string($relsXml);
        $relMap = [];
        foreach ($rels->Relationship as $rel) {
            $attrs = $rel->attributes();
            $relMap[(string)$attrs['Id']] = (string)$attrs['Target'];
        }

        $selectedRid = null;
        $firstRid = null;
        foreach ($workbook->sheets->sheet as $sheet) {
            $attrs = $sheet->attributes();
            $ridAttrs = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            $rid = (string)$ridAttrs['id'];
            $firstRid = $firstRid ?: $rid;
            if (mb_strtolower((string)$attrs['name'], 'UTF-8') === mb_strtolower($preferred, 'UTF-8')) {
                $selectedRid = $rid;
                break;
            }
        }
        $target = $relMap[$selectedRid ?: $firstRid] ?? 'worksheets/sheet1.xml';
        return str_starts_with($target, '/') ? ltrim($target, '/') : 'xl/' . ltrim($target, '/');
    }

    private function readZipEntries(string $path): array
    {
        $data = file_get_contents($path);
        if ($data === false) {
            throw new RuntimeException('Excel dosyası okunamadı.');
        }

        $eocdPos = strrpos($data, "\x50\x4b\x05\x06");
        if ($eocdPos === false) {
            throw new RuntimeException('XLSX zip merkezi dizini bulunamadı.');
        }

        $cdSize = unpack('V', substr($data, $eocdPos + 12, 4))[1];
        $cdOffset = unpack('V', substr($data, $eocdPos + 16, 4))[1];
        $pos = $cdOffset;
        $end = $cdOffset + $cdSize;
        $entries = [];

        while ($pos < $end && substr($data, $pos, 4) === "\x50\x4b\x01\x02") {
            $method = unpack('v', substr($data, $pos + 10, 2))[1];
            $compressedSize = unpack('V', substr($data, $pos + 20, 4))[1];
            $nameLen = unpack('v', substr($data, $pos + 28, 2))[1];
            $extraLen = unpack('v', substr($data, $pos + 30, 2))[1];
            $commentLen = unpack('v', substr($data, $pos + 32, 2))[1];
            $localOffset = unpack('V', substr($data, $pos + 42, 4))[1];
            $name = substr($data, $pos + 46, $nameLen);

            if (substr($data, $localOffset, 4) !== "\x50\x4b\x03\x04") {
                $pos += 46 + $nameLen + $extraLen + $commentLen;
                continue;
            }
            $localNameLen = unpack('v', substr($data, $localOffset + 26, 2))[1];
            $localExtraLen = unpack('v', substr($data, $localOffset + 28, 2))[1];
            $dataStart = $localOffset + 30 + $localNameLen + $localExtraLen;
            $compressed = substr($data, $dataStart, $compressedSize);

            if ($method === 0) {
                $entries[$name] = $compressed;
            } elseif ($method === 8) {
                $inflated = gzinflate($compressed);
                if ($inflated === false) {
                    throw new RuntimeException($name . ' zip girdisi açılamadı.');
                }
                $entries[$name] = $inflated;
            }

            $pos += 46 + $nameLen + $extraLen + $commentLen;
        }

        return $entries;
    }

    private function readSheetRows(string $xml, array $shared): array
    {
        $sx = simplexml_load_string($xml);
        $rows = [];
        foreach ($sx->sheetData->row as $row) {
            $rowNo = (int)$row['r'];
            $rows[$rowNo] = [];
            foreach ($row->c as $cell) {
                $ref = preg_replace('/\d+/', '', (string)$cell['r']);
                $type = (string)$cell['t'];
                $value = isset($cell->v) ? (string)$cell->v : '';
                if ($type === 's') {
                    $value = $shared[(int)$value] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = (string)$cell->is->t;
                }
                $rows[$rowNo][$ref] = trim($value);
            }
        }
        return $rows;
    }

    private function mapExcelRow(array $row): array
    {
        $mapped = [];
        foreach ($this->columns as $col => $field) {
            $mapped[$field] = $row[$col] ?? '';
        }
        return $mapped;
    }

    private function newParsedInvoice(array $row, int $rowNo): array
    {
        return [
            'row_no' => $rowNo,
            'belge_turu' => trim((string)$row['belge_turu']),
            'duzenleme_tarihi' => $this->parseDate($row['duzenleme_tarihi']),
            'vade_tarihi' => $this->parseDate($row['vade_tarihi']),
            'tedarikci_id' => $this->matchSupplier(trim((string)$row['tedarikci_calisan_adi'])),
            'tedarikci_calisan_adi' => trim((string)$row['tedarikci_calisan_adi']),
            'fatura_ismi' => trim((string)$row['fatura_ismi']),
            'kategori' => trim((string)$row['kategori']),
            'doviz_tipi' => strtoupper(trim((string)($row['doviz_tipi'] ?: 'TRY'))),
            'doviz_kuru' => $this->money($row['doviz_kuru'] ?: 1),
            'genel_toplam' => $this->money($row['genel_toplam']),
            'genel_toplam_tl' => $this->money($row['genel_toplam_tl']),
            'toplam_odenen' => $this->money($row['toplam_odenen']),
            'toplam_odenen_tl' => $this->money($row['toplam_odenen_tl']),
            'son_odeme_hesabi' => trim((string)$row['son_odeme_hesabi']),
            'son_odeme_tarihi' => $this->parseDate($row['son_odeme_tarihi']),
            'cebinden_odeyen_calisan' => trim((string)$row['cebinden_odeyen_calisan']),
            'harcayan' => trim((string)$row['harcayan']),
            'fis_fatura_no' => trim((string)$row['fis_fatura_no']),
            'toplam_kdv' => $this->money($row['toplam_kdv']),
            'toplam_tevkifat' => $this->money($row['toplam_tevkifat']),
            'stopaj' => $this->money($row['stopaj']),
            'toplam_oiv' => $this->money($row['toplam_oiv']),
            'toplam_otv' => $this->money($row['toplam_otv']),
            'notlar' => '',
            'uyari' => [],
            'durum' => 'yeni',
            'islem' => 'kaydet',
            'kalemler' => [],
        ];
    }

    private function finishParsedInvoice(array $invoice): array
    {
        $currency = strtoupper($invoice['doviz_tipi'] ?: 'TRY');
        if (in_array($currency, ['TL', 'TRL'], true)) {
            $currency = 'TRY';
        }
        $rate = (float)$invoice['doviz_kuru'];
        if ($currency === 'TRY' || $rate <= 0) {
            $rate = 1;
        }

        $invoice['doviz_tipi'] = $currency;
        $invoice['doviz_kuru'] = $rate;
        if ((float)$invoice['genel_toplam_tl'] <= 0 && (float)$invoice['genel_toplam'] > 0) {
            $invoice['genel_toplam_tl'] = round((float)$invoice['genel_toplam'] * $rate, 2);
        }
        if ((float)$invoice['toplam_odenen_tl'] <= 0 && (float)$invoice['toplam_odenen'] > 0) {
            $invoice['toplam_odenen_tl'] = round((float)$invoice['toplam_odenen'] * $rate, 2);
        }
        $invoice['kalan_tutar'] = max(0, round((float)$invoice['genel_toplam'] - (float)$invoice['toplam_odenen'], 2));
        $invoice['kalan_tutar_tl'] = max(0, round((float)$invoice['genel_toplam_tl'] - (float)$invoice['toplam_odenen_tl'], 2));
        $invoice['odeme_durumu'] = $this->paymentStatus((float)$invoice['genel_toplam_tl'], (float)$invoice['toplam_odenen_tl']);
        $invoice['vade_durumu'] = $this->dueStatus($invoice['vade_tarihi'], (float)$invoice['kalan_tutar_tl']);

        if ((float)$invoice['toplam_odenen_tl'] > 0 && empty($invoice['son_odeme_tarihi'])) {
            $invoice['uyari'][] = 'Ödeme tarihi eksik.';
        }
        if (empty($invoice['tedarikci_id']) && trim((string)$invoice['tedarikci_calisan_adi']) !== '') {
            $invoice['uyari'][] = 'Tedarikçi eşleşmedi; metin olarak kaydedilecek.';
        }
        if (empty($invoice['kalemler']) && ((float)$invoice['genel_toplam'] > 0 || trim((string)$invoice['fatura_ismi']) !== '')) {
            $invoice['kalemler'][] = [
                'urun_hizmet' => $invoice['fatura_ismi'] ?: $invoice['belge_turu'],
                'urun_hizmet_kodu' => '',
                'urun_hizmet_aciklamasi' => '',
                'miktar' => 1,
                'birim_fiyati' => (float)$invoice['genel_toplam'],
                'indirim' => 0,
                'kdv_orani' => 0,
                'kdv_tutari' => (float)$invoice['toplam_kdv'],
                'tevkifat_orani' => '',
                'tevkifat_tutari' => (float)$invoice['toplam_tevkifat'],
                'otv' => (float)$invoice['toplam_otv'],
                'oiv' => (float)$invoice['toplam_oiv'],
                'net_tutar' => (float)$invoice['genel_toplam'],
                'net_tutar_tl' => (float)$invoice['genel_toplam_tl'],
            ];
        }
        return $invoice;
    }

    private function buildLineItem(array $row): array
    {
        return [
            'urun_hizmet' => trim((string)$row['urun_hizmet']),
            'urun_hizmet_kodu' => trim((string)$row['urun_hizmet_kodu']),
            'urun_hizmet_aciklamasi' => trim((string)$row['urun_hizmet_aciklamasi']),
            'miktar' => $this->money($row['miktar']),
            'birim_fiyati' => $this->money($row['birim_fiyati']),
            'indirim' => $this->money($row['indirim']),
            'kdv_orani' => $this->money($row['kdv_orani']),
            'kdv_tutari' => $this->money($row['kdv_tutari']),
            'tevkifat_orani' => trim((string)$row['tevkifat_orani']),
            'tevkifat_tutari' => $this->money($row['tevkifat_tutari']),
            'otv' => $this->money($row['otv']),
            'oiv' => $this->money($row['oiv']),
            'net_tutar' => $this->money($row['net_tutar']),
            'net_tutar_tl' => $this->money($row['net_tutar_tl']),
        ];
    }

    private function insertInvoice(array $invoice, ?int $batchId, ?int $userId, string $source): int
    {
        $companyId = TenantContext::activeCompanyId();
        $periodId = TenantContext::activePeriodId();

        $id = $this->db->insert('gelen_efaturalar', [
            'company_id' => $companyId,
            'period_id' => $periodId,
            'belge_turu' => $invoice['belge_turu'] ?? null,
            'duzenleme_tarihi' => $invoice['duzenleme_tarihi'] ?? null,
            'vade_tarihi' => $invoice['vade_tarihi'] ?? null,
            'tedarikci_id' => $invoice['tedarikci_id'] ?? null,
            'tedarikci_calisan_adi' => $invoice['tedarikci_calisan_adi'] ?? null,
            'fatura_ismi' => $invoice['fatura_ismi'] ?? null,
            'kategori' => $invoice['kategori'] ?? null,
            'doviz_tipi' => $invoice['doviz_tipi'] ?? 'TRY',
            'doviz_kuru' => $invoice['doviz_kuru'] ?? 1,
            'genel_toplam' => $invoice['genel_toplam'] ?? 0,
            'genel_toplam_tl' => $invoice['genel_toplam_tl'] ?? 0,
            'toplam_odenen' => $invoice['toplam_odenen'] ?? 0,
            'toplam_odenen_tl' => $invoice['toplam_odenen_tl'] ?? 0,
            'kalan_tutar' => $invoice['kalan_tutar'] ?? 0,
            'kalan_tutar_tl' => $invoice['kalan_tutar_tl'] ?? 0,
            'son_odeme_hesabi' => $invoice['son_odeme_hesabi'] ?? null,
            'son_odeme_tarihi' => $invoice['son_odeme_tarihi'] ?? null,
            'cebinden_odeyen_calisan' => $invoice['cebinden_odeyen_calisan'] ?? null,
            'harcayan' => $invoice['harcayan'] ?? null,
            'fis_fatura_no' => $invoice['fis_fatura_no'] ?? null,
            'toplam_kdv' => $invoice['toplam_kdv'] ?? 0,
            'toplam_tevkifat' => $invoice['toplam_tevkifat'] ?? 0,
            'stopaj' => $invoice['stopaj'] ?? 0,
            'toplam_oiv' => $invoice['toplam_oiv'] ?? 0,
            'toplam_otv' => $invoice['toplam_otv'] ?? 0,
            'odeme_durumu' => $invoice['odeme_durumu'] ?? 'odenmedi',
            'vade_durumu' => $invoice['vade_durumu'] ?? 'vade_yok',
            'fatura_durumu' => 'aktif',
            'kaynak' => $source,
            'import_batch_id' => $batchId,
            'notlar' => $invoice['notlar'] ?? null,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        foreach (($invoice['kalemler'] ?? []) as $line) {
            $line['gelen_efatura_id'] = $id;
            $line['company_id'] = $companyId;
            $this->db->insert('gelen_efatura_kalemleri', $line);
        }

        if ((float)($invoice['toplam_odenen_tl'] ?? 0) > 0) {
            $this->db->insert('gelen_efatura_odemeleri', [
                'gelen_efatura_id' => $id,
                'company_id' => $companyId,
                'period_id' => $periodId,
                'odeme_tarihi' => $invoice['son_odeme_tarihi'] ?? null,
                'odeme_tutari' => $invoice['toplam_odenen'] ?? 0,
                'odeme_tutari_tl' => $invoice['toplam_odenen_tl'] ?? 0,
                'odeme_yontemi' => 'excel',
                'odeme_hesabi' => $invoice['son_odeme_hesabi'] ?? '',
                'aciklama' => 'Excel import ödeme bilgisi',
                'created_by' => $userId,
            ]);
        }

        return $id;
    }

    private function refreshPaymentTotals(int $invoiceId): void
    {
        $row = $this->db->selectOne(
            "SELECT ge.genel_toplam, ge.genel_toplam_tl, ge.doviz_kuru,
                    COALESCE(SUM(o.odeme_tutari), 0) AS odeme,
                    COALESCE(SUM(o.odeme_tutari_tl), 0) AS odeme_tl
             FROM gelen_efaturalar ge
             LEFT JOIN gelen_efatura_odemeleri o ON o.gelen_efatura_id = ge.id
                AND o.company_id = ge.company_id AND o.period_id = ge.period_id
             WHERE ge.id = :id AND ge.company_id = :company_id AND ge.period_id = :period_id
             GROUP BY ge.id",
            [':id' => $invoiceId, ':company_id' => TenantContext::activeCompanyId(), ':period_id' => TenantContext::activePeriodId()]
        );
        if (!$row) {
            return;
        }
        $kalan = max(0, (float)$row['genel_toplam'] - (float)$row['odeme']);
        $kalanTl = max(0, (float)$row['genel_toplam_tl'] - (float)$row['odeme_tl']);
        $this->db->update('gelen_efaturalar', [
            'toplam_odenen' => (float)$row['odeme'],
            'toplam_odenen_tl' => (float)$row['odeme_tl'],
            'kalan_tutar' => $kalan,
            'kalan_tutar_tl' => $kalanTl,
            'odeme_durumu' => $this->paymentStatus((float)$row['genel_toplam_tl'], (float)$row['odeme_tl']),
        ], ['id' => $invoiceId]);
    }

    private function createCashMovement(int $hesapId, int $invoiceId, float $amountTl, ?string $date, string $description): void
    {
        if (!$this->tableExists('kasa_hareketleri')) {
            return;
        }
        $data = [
            'cari_id' => null,
            'tutar' => $amountTl,
            'tarih' => $date ?: date('Y-m-d'),
            'aciklama' => $description,
            'para_birimi' => 'TRY',
        ];

        if ($this->columnExists('kasa_hareketleri', 'kasa_id')) {
            $data['kasa_id'] = $hesapId;
        }
        if ($this->columnExists('kasa_hareketleri', 'kasa_banka_id')) {
            $data['kasa_banka_id'] = $hesapId;
        }
        if ($this->columnExists('kasa_hareketleri', 'islem_tipi')) {
            $data['islem_tipi'] = 'cikis';
        }
        if ($this->columnExists('kasa_hareketleri', 'hareket_tipi')) {
            $data['hareket_tipi'] = 'odeme';
        }
        if ($this->columnExists('kasa_hareketleri', 'belge_no')) {
            $data['belge_no'] = 'GEF-' . $invoiceId;
        }
        if ($this->columnExists('kasa_hareketleri', 'fatura_id')) {
            $data['fatura_id'] = null;
        }
        if ($this->columnExists('kasa_hareketleri', 'kur')) {
            $data['kur'] = 1;
        }

        if (!isset($data['kasa_id']) && !isset($data['kasa_banka_id'])) {
            return;
        }
        $this->db->insert('kasa_hareketleri', $data);

        if ($this->tableExists('kasa_banka') && $this->columnExists('kasa_banka', 'guncel_bakiye')) {
            $this->db->query(
                "UPDATE kasa_banka SET guncel_bakiye = guncel_bakiye - :tutar WHERE id = :id AND company_id = :company_id",
                [':tutar' => $amountTl, ':id' => $hesapId, ':company_id' => TenantContext::activeCompanyId()]
            );
        }
    }

    private function findDuplicate(array $invoice): ?array
    {
        $no = trim((string)($invoice['fis_fatura_no'] ?? ''));
        $name = trim((string)($invoice['tedarikci_calisan_adi'] ?? ''));
        $date = $invoice['duzenleme_tarihi'] ?? null;
        $total = (float)($invoice['genel_toplam_tl'] ?? 0);
        $firstItem = trim((string)($invoice['kalemler'][0]['urun_hizmet'] ?? ''));

        if ($no !== '' && $name !== '' && $date !== null) {
            $row = $this->db->selectOne(
                "SELECT id FROM gelen_efaturalar
                 WHERE silindi_mi = 0 AND fis_fatura_no = :no AND tedarikci_calisan_adi = :name
                   AND duzenleme_tarihi = :date AND ABS(genel_toplam_tl - :total) < 0.01
                   AND company_id = :company_id AND period_id = :period_id
                 LIMIT 1",
                [':no' => $no, ':name' => $name, ':date' => $date, ':total' => $total, ':company_id' => TenantContext::activeCompanyId(), ':period_id' => TenantContext::activePeriodId()]
            );
            if ($row) {
                return $row;
            }
        }
        if ($no !== '') {
            $row = $this->db->selectOne(
                "SELECT id FROM gelen_efaturalar
                 WHERE silindi_mi = 0 AND fis_fatura_no = :no AND ABS(genel_toplam_tl - :total) < 0.01
                   AND company_id = :company_id AND period_id = :period_id
                 LIMIT 1",
                [':no' => $no, ':total' => $total, ':company_id' => TenantContext::activeCompanyId(), ':period_id' => TenantContext::activePeriodId()]
            );
            if ($row) {
                return $row;
            }
        }
        if ($name !== '' && $date !== null && $firstItem !== '') {
            return $this->db->selectOne(
                "SELECT ge.id FROM gelen_efaturalar ge
                 JOIN gelen_efatura_kalemleri k ON k.gelen_efatura_id = ge.id
                 WHERE ge.silindi_mi = 0 AND ge.tedarikci_calisan_adi = :name
                   AND ge.duzenleme_tarihi = :date AND ABS(ge.genel_toplam_tl - :total) < 0.01
                   AND ge.company_id = :company_id AND ge.period_id = :period_id
                   AND k.company_id = :company_id
                   AND k.urun_hizmet = :item
                 LIMIT 1",
                [':name' => $name, ':date' => $date, ':total' => $total, ':item' => $firstItem, ':company_id' => TenantContext::activeCompanyId(), ':period_id' => TenantContext::activePeriodId()]
            );
        }
        return null;
    }

    private function matchSupplier(string $name): ?int
    {
        if ($name === '' || !$this->tableExists('cariler')) {
            return null;
        }
        $row = $this->db->selectOne(
            "SELECT id FROM cariler WHERE silindi_mi = 0 AND company_id = :company_id AND tip IN ('tedarikci', 'her_ikisi') AND unvan = :name LIMIT 1",
            [':name' => $name, ':company_id' => TenantContext::activeCompanyId()]
        );
        if ($row) {
            return (int)$row['id'];
        }
        $normalized = $this->normalizeName($name);
        $candidates = $this->db->select(
            "SELECT id, unvan FROM cariler WHERE silindi_mi = 0 AND company_id = :company_id AND tip IN ('tedarikci', 'her_ikisi')",
            [':company_id' => TenantContext::activeCompanyId()]
        );
        foreach ($candidates as $candidate) {
            if ($this->normalizeName($candidate['unvan']) === $normalized) {
                return (int)$candidate['id'];
            }
        }
        return null;
    }

    private function parseDate($value): ?string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        if (is_numeric($value) && (float)$value > 20000) {
            $days = (int)floor((float)$value);
            return gmdate('Y-m-d', strtotime('1899-12-30 +' . $days . ' days'));
        }
        $formats = ['d.m.Y', 'Y-m-d', 'd/m/Y', 'd-m-Y'];
        foreach ($formats as $format) {
            $dt = DateTime::createFromFormat($format, $value);
            if ($dt instanceof DateTime) {
                return $dt->format('Y-m-d');
            }
        }
        $ts = strtotime($value);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    private function money($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }
        $value = trim((string)$value);
        $value = str_replace(["\xc2\xa0", ' '], '', $value);
        $value = preg_replace('/[^0-9,\.\-]/', '', $value);
        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif (str_contains($value, ',')) {
            $value = str_replace(',', '.', $value);
        }
        return round((float)$value, 4);
    }

    private function paymentStatus(float $total, float $paid): string
    {
        if ($paid <= 0.0001) {
            return 'odenmedi';
        }
        if ($paid + 0.0001 < $total) {
            return 'kismi_odendi';
        }
        return 'odendi';
    }

    private function dueStatus(?string $dueDate, float $remainingTl): string
    {
        if ($remainingTl <= 0.0001) {
            return 'odendi';
        }
        if (!$dueDate) {
            return 'vade_yok';
        }
        $today = new DateTime(date('Y-m-d'));
        $due = new DateTime($dueDate);
        if ($due < $today) {
            return 'vadesi_gecti';
        }
        $days = (int)$today->diff($due)->format('%a');
        return $days <= 7 ? 'vadesi_yaklasiyor' : 'vadesinde';
    }

    private function normalizeName(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');
        $from = ['ı', 'ğ', 'ü', 'ş', 'ö', 'ç'];
        $to = ['i', 'g', 'u', 's', 'o', 'c'];
        $value = str_replace($from, $to, $value);
        return preg_replace('/[^a-z0-9]/', '', $value) ?? '';
    }

    private function tableExists(string $table): bool
    {
        $row = $this->db->selectOne(
            "SELECT COUNT(*) AS c FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t",
            [':t' => $table]
        );
        return (int)($row['c'] ?? 0) > 0;
    }

    private function columnExists(string $table, string $column): bool
    {
        $row = $this->db->selectOne(
            "SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c",
            [':t' => $table, ':c' => $column]
        );
        return (int)($row['c'] ?? 0) > 0;
    }
}
