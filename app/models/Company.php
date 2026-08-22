<?php

class Company
{
    private Database $db;

    private array $companyFillable = [
        'company_name', 'short_name', 'tax_number', 'tax_office', 'mersis_no',
        'trade_registry_no', 'address', 'city', 'district', 'country', 'phone',
        'email', 'website', 'logo_path', 'currency', 'status',
    ];

    private array $settingsFillable = [
        'invoice_prefix', 'purchase_invoice_prefix', 'quote_prefix',
        'default_currency', 'default_vat_rate', 'fiscal_year_start_month',
        'fiscal_year_end_month', 'stock_tracking_enabled', 'allow_negative_stock', 'e_invoice_enabled',
        'theme_color', 'is_storefront_source',
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function listForUser(int $userId): array
    {
        return $this->db->select(
            "SELECT c.*, uc.role, uc.can_switch_company, uc.can_manage_company, uc.can_manage_period, uc.is_default,
                    ap.period_name AS active_period_name, ap.status AS active_period_status
             FROM user_companies uc
             JOIN companies c ON c.id = uc.company_id
             LEFT JOIN accounting_periods ap ON ap.id = (
                SELECT ap2.id FROM accounting_periods ap2
                WHERE ap2.company_id = c.id AND ap2.status = 'open'
                ORDER BY ap2.fiscal_year DESC LIMIT 1
             )
             WHERE uc.user_id = :uid AND c.deleted_at IS NULL
             ORDER BY c.company_name",
            [':uid' => $userId]
        );
    }

    /** Kullanıcının girişte otomatik açılacak varsayılan şirketini ayarlar. */
    public function setDefault(int $userId, int $companyId): void
    {
        if (!TenantContext::userCanAccessCompany($companyId)) {
            throw new RuntimeException('Bu şirkete erişim yetkiniz yok.');
        }

        $this->db->begin();
        try {
            $this->db->query(
                "UPDATE user_companies SET is_default = 0 WHERE user_id = :uid",
                [':uid' => $userId]
            );
            $this->db->query(
                "UPDATE user_companies SET is_default = 1 WHERE user_id = :uid AND company_id = :cid",
                [':uid' => $userId, ':cid' => $companyId]
            );
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->pdo()->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function all(): array
    {
        return $this->db->select(
            "SELECT c.*, COUNT(ap.id) AS period_count
             FROM companies c
             LEFT JOIN accounting_periods ap ON ap.company_id = c.id
             WHERE c.deleted_at IS NULL
             GROUP BY c.id
             ORDER BY c.company_name"
        );
    }

    /** Kullanıcının erişebildiği şirketler (dönem sayısıyla birlikte). */
    public function allForUser(int $userId): array
    {
        return $this->db->select(
            "SELECT c.*, COUNT(ap.id) AS period_count, MAX(uc.is_default) AS is_default
             FROM companies c
             JOIN user_companies uc ON uc.company_id = c.id AND uc.user_id = :uid
             LEFT JOIN accounting_periods ap ON ap.company_id = c.id
             WHERE c.deleted_at IS NULL
             GROUP BY c.id
             ORDER BY c.company_name",
            [':uid' => $userId]
        );
    }

    public function find(int $id): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM companies WHERE id = :id AND deleted_at IS NULL",
            [':id' => $id]
        );
    }

    public function settings(int $companyId): array
    {
        $row = $this->db->selectOne(
            "SELECT * FROM company_settings WHERE company_id = :cid",
            [':cid' => $companyId]
        );
        return $row ?? [];
    }

    public function create(array $data): int
    {
        $company = array_intersect_key($data, array_flip($this->companyFillable));
        $company['company_name'] = trim((string)($company['company_name'] ?? ''));
        if ($company['company_name'] === '') {
            throw new InvalidArgumentException('Şirket adı zorunludur.');
        }
        $company['short_name'] = trim((string)($company['short_name'] ?? '')) ?: $this->shortName($company['company_name']);
        $company['country'] = $company['country'] ?? 'Türkiye';
        $company['currency'] = $company['currency'] ?? 'TRY';
        $company['status'] = $company['status'] ?? 'active';
        $company['created_by'] = TenantContext::userId();

        $this->db->begin();
        try {
            $id = $this->db->insert('companies', $company);
            $this->db->insert('user_companies', [
                'user_id' => TenantContext::userId(),
                'company_id' => $id,
                'role' => 'owner',
                'can_switch_company' => 1,
                'can_manage_company' => 1,
                'can_manage_period' => 1,
            ]);
            $this->saveSettings($id, $data);

            $year = (int)date('Y');
            $this->createPeriod($id, [
                'period_name' => (string)$year,
                'fiscal_year' => $year,
                'start_date' => "{$year}-01-01",
                'end_date' => "{$year}-12-31",
                'status' => 'open',
                'is_active' => 1,
            ]);
            $this->db->commit();
            return $id;
        } catch (Throwable $e) {
            if ($this->db->pdo()->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function updateCompany(int $id, array $data): void
    {
        $company = array_intersect_key($data, array_flip($this->companyFillable));
        $company['company_name'] = trim((string)($company['company_name'] ?? ''));
        if ($company['company_name'] === '') {
            throw new InvalidArgumentException('Şirket adı zorunludur.');
        }
        $company['short_name'] = trim((string)($company['short_name'] ?? '')) ?: $this->shortName($company['company_name']);
        $this->db->update('companies', $company, ['id' => $id]);
        $this->saveSettings($id, $data);
    }

    public function archive(int $id): array
    {
        $counts = $this->usageCounts($id);
        $hasMovements = array_sum($counts) > 0;
        $this->db->update('companies', ['status' => 'passive'], ['id' => $id]);
        return ['archived' => !$hasMovements, 'counts' => $counts];
    }

    public function periods(int $companyId): array
    {
        return $this->db->select(
            "SELECT * FROM accounting_periods WHERE company_id = :cid ORDER BY fiscal_year DESC",
            [':cid' => $companyId]
        );
    }

    public function findPeriod(int $id): ?array
    {
        return $this->db->selectOne(
            "SELECT ap.*, c.company_name
             FROM accounting_periods ap
             JOIN companies c ON c.id = ap.company_id
             WHERE ap.id = :id",
            [':id' => $id]
        );
    }

    public function createPeriod(int $companyId, array $data): int
    {
        $year = (int)($data['fiscal_year'] ?? date('Y'));
        return $this->db->insert('accounting_periods', [
            'company_id' => $companyId,
            'period_name' => trim((string)($data['period_name'] ?? $year)),
            'fiscal_year' => $year,
            'start_date' => $data['start_date'] ?? "{$year}-01-01",
            'end_date' => $data['end_date'] ?? "{$year}-12-31",
            'status' => $data['status'] ?? 'open',
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function updatePeriod(int $id, array $data): void
    {
        $period = $this->findPeriod($id);
        if (!$period) {
            throw new RuntimeException('Dönem bulunamadı.');
        }

        $year = (int)($data['fiscal_year'] ?? $period['fiscal_year']);
        $this->db->update('accounting_periods', [
            'period_name' => trim((string)($data['period_name'] ?? $period['period_name'])),
            'fiscal_year' => $year,
            'start_date' => $data['start_date'] ?? $period['start_date'],
            'end_date' => $data['end_date'] ?? $period['end_date'],
            'status' => $data['status'] ?? $period['status'],
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'notes' => $data['notes'] ?? null,
        ], ['id' => $id]);
    }

    public function setPeriodStatus(int $id, string $status): void
    {
        $current = $this->findPeriod($id);
        if ($current && $current['status'] === 'archived' && $status !== 'archived') {
            throw new RuntimeException('Arşivlenmiş bir dönem tekrar açılamaz.');
        }

        $data = ['status' => $status];
        if ($status === 'closed') {
            $data['closing_date'] = date('Y-m-d');
            $data['closed_by'] = TenantContext::userId();
        }
        if ($status === 'open') {
            $data['closing_date'] = null;
            $data['closed_by'] = null;
        }
        $this->db->update('accounting_periods', $data, ['id' => $id]);
    }

    public function closeSummary(int $periodId): array
    {
        $period = $this->findPeriod($periodId);
        if (!$period) {
            throw new RuntimeException('Dönem bulunamadı.');
        }
        $params = [':cid' => (int)$period['company_id'], ':pid' => $periodId];
        $invoice = $this->db->selectOne(
            "SELECT
                COALESCE(SUM(CASE WHEN belge_tipi IN ('satis','perakende') AND durum <> 'iptal' THEN genel_toplam ELSE 0 END),0) AS toplam_satis,
                COALESCE(SUM(CASE WHEN belge_tipi = 'alis' AND durum <> 'iptal' THEN genel_toplam ELSE 0 END),0) AS toplam_alis,
                COALESCE(SUM(CASE WHEN durum = 'taslak' THEN 1 ELSE 0 END),0) AS taslak_fatura,
                COALESCE(SUM(CASE WHEN kalan_tutar > 0 AND durum NOT IN ('iptal','taslak') THEN 1 ELSE 0 END),0) AS odenmemis_fatura
             FROM faturalar WHERE company_id = :cid AND period_id = :pid AND silindi_mi = 0",
            $params
        ) ?? [];
        $cash = $this->db->selectOne(
            "SELECT
                COALESCE(SUM(CASE WHEN islem_tipi = 'giris' THEN tutar ELSE 0 END),0) AS toplam_tahsilat,
                COALESCE(SUM(CASE WHEN islem_tipi = 'cikis' THEN tutar ELSE 0 END),0) AS toplam_odeme
             FROM kasa_hareketleri WHERE company_id = :cid AND period_id = :pid AND silindi_mi = 0",
            $params
        ) ?? [];
        return array_merge($invoice, $cash, [
            'negatif_stok' => $this->negativeStockCount((int)$period['company_id']),
            'not' => 'Bu fazda dönem kapama dönemi closed yapar; devir kayıtları period_opening_balances altyapısı ile ikinci faza hazırdır.',
        ]);
    }

    private function saveSettings(int $companyId, array $data): void
    {
        $settings = array_intersect_key($data, array_flip($this->settingsFillable));
        $settings['invoice_prefix'] = trim((string)($settings['invoice_prefix'] ?? '')) ?: $this->prefix($data['short_name'] ?? $data['company_name'] ?? 'CMP');
        $settings['purchase_invoice_prefix'] = trim((string)($settings['purchase_invoice_prefix'] ?? '')) ?: $settings['invoice_prefix'] . 'A';
        $settings['quote_prefix'] = trim((string)($settings['quote_prefix'] ?? '')) ?: $settings['invoice_prefix'] . 'T';
        $settings['default_currency'] = $settings['default_currency'] ?? ($data['currency'] ?? 'TRY');
        $settings['default_vat_rate'] = (float)($settings['default_vat_rate'] ?? 20);
        $settings['fiscal_year_start_month'] = (int)($settings['fiscal_year_start_month'] ?? 1);
        $settings['fiscal_year_end_month'] = (int)($settings['fiscal_year_end_month'] ?? 12);
        $settings['stock_tracking_enabled'] = !empty($settings['stock_tracking_enabled']) ? 1 : 0;
        $settings['allow_negative_stock'] = !empty($settings['allow_negative_stock']) ? 1 : 0;
        $settings['e_invoice_enabled'] = !empty($settings['e_invoice_enabled']) ? 1 : 0;
        $settings['theme_color'] = $this->normalizeThemeColor((string)($settings['theme_color'] ?? 'emerald'));
        $settings['is_storefront_source'] = !empty($settings['is_storefront_source']) ? 1 : 0;

        $exists = $this->db->selectOne("SELECT id FROM company_settings WHERE company_id = :cid", [':cid' => $companyId]);
        if ($exists) {
            $this->db->update('company_settings', $settings, ['company_id' => $companyId]);
        } else {
            $settings['company_id'] = $companyId;
            $this->db->insert('company_settings', $settings);
        }

        if ($settings['is_storefront_source'] === 1) {
            $this->db->query(
                "UPDATE company_settings SET is_storefront_source = 0 WHERE company_id <> :cid",
                [':cid' => $companyId]
            );
        }
    }

    /** nymagro.com ile ürün senkronu yapılan tek "vitrin şirketi"nin id'si (yoksa null). */
    public function storefrontCompanyId(): ?int
    {
        $row = $this->db->selectOne(
            "SELECT company_id FROM company_settings WHERE is_storefront_source = 1 LIMIT 1"
        );
        return $row ? (int)$row['company_id'] : null;
    }

    private function usageCounts(int $companyId): array
    {
        $counts = [];
        foreach (['cariler', 'urunler_hizmetler', 'faturalar', 'kasa_hareketleri', 'stok_hareketleri'] as $table) {
            if (!TenantContext::tableExists($table) || !TenantContext::hasColumn($table, 'company_id')) {
                $counts[$table] = 0;
                continue;
            }
            $row = $this->db->selectOne("SELECT COUNT(*) AS n FROM `{$table}` WHERE company_id = :cid", [':cid' => $companyId]);
            $counts[$table] = (int)($row['n'] ?? 0);
        }
        return $counts;
    }

    private function negativeStockCount(int $companyId): int
    {
        if (!TenantContext::tableExists('urunler_hizmetler') || !TenantContext::hasColumn('urunler_hizmetler', 'company_id')) {
            return 0;
        }
        $row = $this->db->selectOne(
            "SELECT COUNT(*) AS n FROM urunler_hizmetler WHERE company_id = :cid AND stok_miktari < 0 AND silindi_mi = 0",
            [':cid' => $companyId]
        );
        return (int)($row['n'] ?? 0);
    }

    private function shortName(string $name): string
    {
        return mb_substr(trim($name), 0, 80);
    }

    private function prefix(string $name): string
    {
        $clean = preg_replace('/[^A-Za-z0-9]/', '', strtoupper($name));
        return substr($clean !== '' ? $clean : 'CMP', 0, 3);
    }

    private function normalizeThemeColor(string $color): string
    {
        $allowed = ['emerald', 'blue', 'violet', 'amber', 'rose', 'cyan', 'slate'];
        return in_array($color, $allowed, true) ? $color : 'emerald';
    }
}
