<?php

class NakitModul
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->ensureSchema();
    }

    public function krediler(): array
    {
        return $this->db->select(
            "SELECT k.*,
                    COALESCE(SUM(CASE WHEN p.odendi = 0 AND p.silindi_mi = 0 THEN p.tutar ELSE 0 END), 0) AS kalan_plan_tutari,
                    MIN(CASE WHEN p.odendi = 0 AND p.silindi_mi = 0 THEN p.odeme_tarihi ELSE NULL END) AS siradaki_taksit
             FROM krediler k
             LEFT JOIN kredi_odeme_plani p ON p.kredi_id = k.id
             WHERE k.silindi_mi = 0 AND k.company_id = :cid AND k.period_id = :pid
             GROUP BY k.id
             ORDER BY k.id DESC",
            $this->tenantParams()
        );
    }

    public function krediEkle(array $data): int
    {
        $ad = trim((string)($data['ad'] ?? ''));
        if ($ad === '') {
            throw new InvalidArgumentException('Kredi adı boş olamaz.');
        }

        $tutar = $this->money($data['kalan_borc'] ?? 0);
        $taksitSayisi = max(1, min(144, (int)($data['kalan_taksit_sayisi'] ?? 1)));
        $ilkTarih = $this->dateOrToday($data['ilk_taksit_tarihi'] ?? '');
        $periyot = in_array(($data['odeme_takvimi'] ?? 'aylik'), ['aylik', 'uc_aylik', 'yillik'], true) ? $data['odeme_takvimi'] : 'aylik';

        $this->db->begin();
        try {
            $id = $this->db->insert('krediler', [
                'ad' => $ad,
                'kalan_borc' => $tutar,
                'kalan_taksit_sayisi' => $taksitSayisi,
                'ilk_taksit_tarihi' => $ilkTarih,
                'odeme_takvimi' => $periyot,
                'hesap_id' => !empty($data['hesap_id']) ? (int)$data['hesap_id'] : null,
                'notlar' => trim((string)($data['notlar'] ?? '')),
                'company_id' => TenantContext::activeCompanyId(),
                'period_id' => TenantContext::activePeriodId(),
            ]);

            $taksitTutari = $taksitSayisi > 0 ? round($tutar / $taksitSayisi, 2) : $tutar;
            $date = new DateTimeImmutable($ilkTarih);
            $step = $periyot === 'yillik' ? '+1 year' : ($periyot === 'uc_aylik' ? '+3 months' : '+1 month');
            for ($i = 1; $i <= $taksitSayisi; $i++) {
                $amount = $i === $taksitSayisi ? round($tutar - ($taksitTutari * ($taksitSayisi - 1)), 2) : $taksitTutari;
                $this->db->insert('kredi_odeme_plani', [
                    'kredi_id' => $id,
                    'taksit_no' => $i,
                    'odeme_tarihi' => $date->format('Y-m-d'),
                    'tutar' => $amount,
                    'odendi' => 0,
                    'company_id' => TenantContext::activeCompanyId(),
                    'period_id' => TenantContext::activePeriodId(),
                ]);
                $date = $date->modify($step);
            }

            $this->db->commit();
            return $id;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function krediSil(int $id): void
    {
        $this->db->update('krediler', ['silindi_mi' => 1], ['id' => $id]);
        $this->db->update('kredi_odeme_plani', ['silindi_mi' => 1], ['kredi_id' => $id]);
    }

    public function krediGetir(int $id): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM krediler WHERE id = :id AND silindi_mi = 0 AND company_id = :cid AND period_id = :pid",
            array_merge([':id' => $id], $this->tenantParams())
        );
    }

    public function taksitler(int $krediId): array
    {
        return $this->db->select(
            "SELECT * FROM kredi_odeme_plani
             WHERE kredi_id = :kid AND silindi_mi = 0 AND company_id = :cid AND period_id = :pid
             ORDER BY taksit_no ASC",
            array_merge([':kid' => $krediId], $this->tenantParams())
        );
    }

    /** Bir taksiti ödendi olarak işaretler, krediyi günceller ve varsa kasa hareketi oluşturur. */
    public function taksitOde(int $planId): void
    {
        $plan = $this->db->selectOne(
            "SELECT * FROM kredi_odeme_plani WHERE id = :id AND silindi_mi = 0 AND company_id = :cid AND period_id = :pid",
            array_merge([':id' => $planId], $this->tenantParams())
        );
        if (!$plan) {
            throw new InvalidArgumentException('Taksit bulunamadı.');
        }
        if ((int)$plan['odendi'] === 1) {
            return; // zaten ödenmiş, tekrar işlenmesin
        }

        $kredi = $this->krediGetir((int)$plan['kredi_id']);
        if (!$kredi) {
            throw new InvalidArgumentException('Kredi bulunamadı.');
        }

        $this->db->begin();
        try {
            $this->db->update('kredi_odeme_plani', ['odendi' => 1], ['id' => $planId]);

            $this->db->query(
                "UPDATE krediler
                 SET kalan_borc = GREATEST(kalan_borc - :tutar, 0),
                     kalan_taksit_sayisi = GREATEST(kalan_taksit_sayisi - 1, 0)
                 WHERE id = :id",
                [':tutar' => $plan['tutar'], ':id' => $kredi['id']]
            );

            if (!empty($kredi['hesap_id'])) {
                $this->db->insert('kasa_hareketleri', [
                    'kasa_id'       => (int)$kredi['hesap_id'],
                    'kasa_banka_id' => (int)$kredi['hesap_id'],
                    'hareket_tipi'  => 'kredi_odeme',
                    'islem_tipi'    => 'cikis',
                    'tutar'         => $plan['tutar'],
                    'tarih'         => date('Y-m-d H:i:s'),
                    'aciklama'      => trim($kredi['ad'] . ' - Taksit #' . $plan['taksit_no']),
                    'para_birimi'   => 'TRY',
                ]);

                $this->db->query(
                    "UPDATE kasa_banka SET guncel_bakiye = guncel_bakiye - :tutar WHERE id = :id AND company_id = :cid",
                    [':tutar' => $plan['tutar'], ':id' => (int)$kredi['hesap_id'], ':cid' => TenantContext::activeCompanyId()]
                );
            }

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function demirbaslar(): array
    {
        return $this->listele('demirbaslar', 'alis_tarihi DESC, id DESC');
    }

    public function demirbasEkle(array $data): int
    {
        $ad = trim((string)($data['ad'] ?? ''));
        if ($ad === '') {
            throw new InvalidArgumentException('Demirbaş adı boş olamaz.');
        }
        return $this->db->insert('demirbaslar', [
            'ad' => $ad,
            'aciklama' => trim((string)($data['aciklama'] ?? '')),
            'seri_no' => trim((string)($data['seri_no'] ?? '')),
            'alis_tarihi' => $this->nullableDate($data['alis_tarihi'] ?? ''),
            'fiyat' => $this->money($data['fiyat'] ?? 0),
            'company_id' => TenantContext::activeCompanyId(),
            'period_id' => TenantContext::activePeriodId(),
        ]);
    }

    public function demirbasSil(int $id): void
    {
        $this->softDelete('demirbaslar', $id);
    }

    public function projeler(): array
    {
        return $this->listele('projeler', 'id DESC');
    }

    public function projeEkle(array $data): int
    {
        $ad = trim((string)($data['ad'] ?? ''));
        if ($ad === '') {
            throw new InvalidArgumentException('Proje adı boş olamaz.');
        }
        return $this->db->insert('projeler', [
            'ad' => $ad,
            'aciklama' => trim((string)($data['aciklama'] ?? '')),
            'durum' => in_array(($data['durum'] ?? 'aktif'), ['aktif', 'pasif', 'tamamlandi'], true) ? $data['durum'] : 'aktif',
            'company_id' => TenantContext::activeCompanyId(),
            'period_id' => TenantContext::activePeriodId(),
        ]);
    }

    public function projeSil(int $id): void
    {
        $this->softDelete('projeler', $id);
    }

    public function portfoy(string $tip): array
    {
        return $this->db->select(
            "SELECT p.*, c.unvan AS cari_unvan
             FROM cek_senet_portfoyu p
             LEFT JOIN cariler c ON c.id = p.cari_id AND c.company_id = p.company_id
             WHERE p.silindi_mi = 0 AND p.tip = :tip AND p.company_id = :cid AND p.period_id = :pid
             ORDER BY p.vade_tarihi ASC, p.id DESC",
            [':tip' => $tip] + $this->tenantParams()
        );
    }

    public function portfoyEkle(string $tip, array $data): int
    {
        if (!in_array($tip, ['cek', 'senet'], true)) {
            throw new InvalidArgumentException('Geçersiz portföy türü.');
        }
        $belgeNo = trim((string)($data['belge_no'] ?? ''));
        if ($belgeNo === '') {
            throw new InvalidArgumentException('Belge numarası boş olamaz.');
        }
        return $this->db->insert('cek_senet_portfoyu', [
            'tip' => $tip,
            'belge_no' => $belgeNo,
            'cari_id' => !empty($data['cari_id']) ? (int)$data['cari_id'] : null,
            'vade_tarihi' => $this->dateOrToday($data['vade_tarihi'] ?? ''),
            'tutar' => $this->money($data['tutar'] ?? 0),
            'durum' => in_array(($data['durum'] ?? 'bekliyor'), ['bekliyor', 'tahsil', 'odendi', 'ciro', 'iade', 'kapandi'], true) ? $data['durum'] : 'bekliyor',
            'aciklama' => trim((string)($data['aciklama'] ?? '')),
            'company_id' => TenantContext::activeCompanyId(),
            'period_id' => TenantContext::activePeriodId(),
        ]);
    }

    public function portfoySil(int $id): void
    {
        $this->softDelete('cek_senet_portfoyu', $id);
    }

    public function cariler(): array
    {
        if (!TenantContext::tableExists('cariler')) {
            return [];
        }
        return $this->db->select(
            "SELECT id, unvan FROM cariler WHERE silindi_mi = 0 AND company_id = :cid ORDER BY unvan",
            [':cid' => TenantContext::activeCompanyId()]
        );
    }

    private function listele(string $table, string $order): array
    {
        return $this->db->select(
            "SELECT * FROM {$table} WHERE silindi_mi = 0 AND company_id = :cid AND period_id = :pid ORDER BY {$order}",
            $this->tenantParams()
        );
    }

    private function softDelete(string $table, int $id): void
    {
        $this->db->update($table, ['silindi_mi' => 1], ['id' => $id]);
    }

    private function tenantParams(): array
    {
        return [
            ':cid' => TenantContext::activeCompanyId(),
            ':pid' => TenantContext::activePeriodId(),
        ];
    }

    private function money(mixed $value): float
    {
        $raw = str_replace(' ', '', (string)$value);
        if (str_contains($raw, ',')) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        }
        return (float)$raw;
    }

    private function nullableDate(mixed $value): ?string
    {
        $value = trim((string)$value);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }

    private function dateOrToday(mixed $value): string
    {
        return $this->nullableDate($value) ?? date('Y-m-d');
    }

    private function ensureSchema(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS krediler (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id INT UNSIGNED NULL,
            period_id INT UNSIGNED NULL,
            ad VARCHAR(180) NOT NULL,
            kalan_borc DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            kalan_taksit_sayisi INT UNSIGNED NOT NULL DEFAULT 1,
            ilk_taksit_tarihi DATE NULL,
            odeme_takvimi ENUM('aylik','uc_aylik','yillik') NOT NULL DEFAULT 'aylik',
            hesap_id INT UNSIGNED NULL,
            notlar TEXT NULL,
            silindi_mi TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_krediler_tenant (company_id, period_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS kredi_odeme_plani (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id INT UNSIGNED NULL,
            period_id INT UNSIGNED NULL,
            kredi_id INT UNSIGNED NOT NULL,
            taksit_no INT UNSIGNED NOT NULL,
            odeme_tarihi DATE NOT NULL,
            tutar DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            odendi TINYINT(1) NOT NULL DEFAULT 0,
            silindi_mi TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_kredi_plan_tenant (company_id, period_id),
            KEY idx_kredi_plan_kredi (kredi_id),
            KEY idx_kredi_plan_tarih (odeme_tarihi)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS demirbaslar (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id INT UNSIGNED NULL,
            period_id INT UNSIGNED NULL,
            ad VARCHAR(180) NOT NULL,
            aciklama TEXT NULL,
            seri_no VARCHAR(120) NULL,
            alis_tarihi DATE NULL,
            fiyat DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            silindi_mi TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_demirbas_tenant (company_id, period_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS projeler (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id INT UNSIGNED NULL,
            period_id INT UNSIGNED NULL,
            ad VARCHAR(180) NOT NULL,
            aciklama TEXT NULL,
            durum ENUM('aktif','pasif','tamamlandi') NOT NULL DEFAULT 'aktif',
            silindi_mi TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_projeler_tenant (company_id, period_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS cek_senet_portfoyu (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id INT UNSIGNED NULL,
            period_id INT UNSIGNED NULL,
            tip ENUM('cek','senet') NOT NULL,
            belge_no VARCHAR(80) NOT NULL,
            cari_id INT UNSIGNED NULL,
            vade_tarihi DATE NOT NULL,
            tutar DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            durum ENUM('bekliyor','tahsil','odendi','ciro','iade','kapandi') NOT NULL DEFAULT 'bekliyor',
            aciklama TEXT NULL,
            silindi_mi TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_cek_senet_tenant (company_id, period_id),
            KEY idx_cek_senet_vade (vade_tarihi),
            KEY idx_cek_senet_tip (tip)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");
    }
}
