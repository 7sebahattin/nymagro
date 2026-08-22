<?php

final class TenantContext
{
    private static array $columnCache = [];
    private static array $tableCache = [];

    private const COMPANY_TABLES = [
        'cariler', 'urunler_hizmetler', 'depolar', 'urun_stok_depo',
        'kasa_banka', 'personeller', 'masraf_kategoriler', 'tanimlar',
        'varyantlar', 'varyant_degerleri', 'urun_varyant_degerleri',
        'faturalar', 'fatura_kalemleri', 'kasa_hareketleri', 'masraflar',
        'masraf_belgeler', 'cari_dokumanlar',
        'gelen_efaturalar', 'gelen_efatura_kalemleri', 'gelen_efatura_odemeleri',
        'gelen_efatura_import_batches', 'gelen_efatura_notlari', 'stok_hareketleri',
        'cek_senet_portfoyu', 'personel_hareketleri',
        'krediler', 'kredi_odeme_plani', 'demirbaslar', 'projeler',
    ];

    private const PERIOD_TABLES = [
        'faturalar', 'fatura_kalemleri', 'kasa_hareketleri', 'masraflar',
        'gelen_efaturalar', 'gelen_efatura_odemeleri', 'gelen_efatura_import_batches',
        'gelen_efatura_notlari', 'stok_hareketleri', 'cek_senet_portfoyu',
        'personel_hareketleri',
        'krediler', 'kredi_odeme_plani', 'demirbaslar', 'projeler',
    ];

    private const WRITE_TABLES = [
        'cariler', 'urunler_hizmetler', 'depolar', 'urun_stok_depo',
        'kasa_banka', 'faturalar', 'fatura_kalemleri', 'kasa_hareketleri',
        'masraflar', 'cari_dokumanlar', 'gelen_efaturalar', 'gelen_efatura_kalemleri',
        'gelen_efatura_odemeleri', 'gelen_efatura_import_batches',
        'gelen_efatura_notlari',
        'stok_hareketleri', 'cek_senet_portfoyu', 'personel_hareketleri',
        'krediler', 'kredi_odeme_plani', 'demirbaslar', 'projeler',
    ];

    private const PUBLIC_ROUTES = [
        'companies', 'periods', 'cikis', 'profil',
    ];

    public static function bootstrap(): void
    {
        self::ensureSchema();
        self::ensureDefaultTenant();
        self::ensureActiveSelection();
    }

    public static function requireActiveForRoute(array $url): void
    {
        $section = strtolower($url[0] ?? '');
        if ($section === '' || in_array($section, self::PUBLIC_ROUTES, true)) {
            return;
        }

        if (!self::activeCompanyId() || !self::activePeriodId()) {
            header('Location: ' . BASE_URL . '/companies/switch');
            exit;
        }

        if (!self::userCanAccessCompany(self::activeCompanyId())) {
            unset($_SESSION['active_company_id'], $_SESSION['active_period_id']);
            http_response_code(403);
            die('Bu şirkete erişim yetkiniz yok.');
        }
    }

    public static function activeCompanyId(): ?int
    {
        return !empty($_SESSION['active_company_id']) ? (int)$_SESSION['active_company_id'] : null;
    }

    public static function activePeriodId(): ?int
    {
        return !empty($_SESSION['active_period_id']) ? (int)$_SESSION['active_period_id'] : null;
    }

    public static function userId(): int
    {
        return (int)($_SESSION['user_id'] ?? 1);
    }

    public static function activeCompany(): ?array
    {
        $id = self::activeCompanyId();
        if (!$id) {
            return null;
        }

        return Database::getInstance()->selectOne(
            "SELECT * FROM companies WHERE id = :id AND deleted_at IS NULL",
            [':id' => $id]
        );
    }

    public static function activePeriod(): ?array
    {
        $id = self::activePeriodId();
        if (!$id) {
            return null;
        }

        return Database::getInstance()->selectOne(
            "SELECT * FROM accounting_periods WHERE id = :id",
            [':id' => $id]
        );
    }

    public static function activeCompanySettings(): array
    {
        $companyId = self::activeCompanyId();
        if (!$companyId) {
            return [];
        }

        return Database::getInstance()->selectOne(
            "SELECT * FROM company_settings WHERE company_id = :cid",
            [':cid' => $companyId]
        ) ?? [];
    }

    public static function isActivePeriodWritable(): bool
    {
        $period = self::activePeriod();
        return $period !== null && $period['status'] === 'open';
    }

    public static function assertWritablePeriod(string $table = ''): void
    {
        if ($table !== '' && !in_array($table, self::WRITE_TABLES, true)) {
            return;
        }

        if (!self::activeCompanyId() || !self::activePeriodId()) {
            throw new RuntimeException('Aktif şirket ve aktif dönem seçilmeden işlem yapılamaz.');
        }

        if (!self::isActivePeriodWritable()) {
            throw new RuntimeException('Seçili dönem kapalı/kilitli olduğu için kayıt ekleme, düzenleme veya silme yapılamaz.');
        }
    }

    public static function tenantAwareInsert(string $table, array $data): array
    {
        if (self::hasColumn($table, 'company_id') && !array_key_exists('company_id', $data)) {
            $companyId = self::activeCompanyId();
            if ($companyId) {
                $data['company_id'] = $companyId;
            }
        }

        if (self::hasColumn($table, 'period_id') && !array_key_exists('period_id', $data)) {
            $periodId = self::activePeriodId();
            if ($periodId) {
                $data['period_id'] = $periodId;
            }
        }

        return $data;
    }

    public static function tenantAwareWhere(string $table, array $where): array
    {
        if (self::hasColumn($table, 'company_id') && !array_key_exists('company_id', $where)) {
            $companyId = self::activeCompanyId();
            if ($companyId) {
                $where['company_id'] = $companyId;
            }
        }

        return $where;
    }

    public static function companyFilter(string $alias = ''): array
    {
        $companyId = self::activeCompanyId();
        if (!$companyId) {
            return ['1 = 0', []];
        }
        $col = $alias !== '' ? "{$alias}.company_id" : 'company_id';
        return ["{$col} = :tenant_company_id", [':tenant_company_id' => $companyId]];
    }

    public static function periodFilter(string $alias = ''): array
    {
        $periodId = self::activePeriodId();
        if (!$periodId) {
            return ['1 = 0', []];
        }
        $col = $alias !== '' ? "{$alias}.period_id" : 'period_id';
        return ["{$col} = :tenant_period_id", [':tenant_period_id' => $periodId]];
    }

    public static function companyAndPeriodFilter(string $alias = ''): array
    {
        [$companySql, $companyParams] = self::companyFilter($alias);
        [$periodSql, $periodParams] = self::periodFilter($alias);
        return ["{$companySql} AND {$periodSql}", $companyParams + $periodParams];
    }

    public static function hasColumn(string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        if (array_key_exists($key, self::$columnCache)) {
            return self::$columnCache[$key];
        }

        if (!self::tableExists($table)) {
            return self::$columnCache[$key] = false;
        }

        $row = Database::getInstance()->selectOne(
            "SELECT COUNT(*) AS n
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column",
            [':table' => $table, ':column' => $column]
        );
        return self::$columnCache[$key] = ((int)($row['n'] ?? 0) > 0);
    }

    public static function tableExists(string $table): bool
    {
        if (array_key_exists($table, self::$tableCache)) {
            return self::$tableCache[$table];
        }

        $row = Database::getInstance()->selectOne(
            "SELECT COUNT(*) AS n
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table",
            [':table' => $table]
        );
        return self::$tableCache[$table] = ((int)($row['n'] ?? 0) > 0);
    }

    public static function userCanAccessCompany(int $companyId): bool
    {
        $row = Database::getInstance()->selectOne(
            "SELECT id FROM user_companies WHERE user_id = :uid AND company_id = :cid LIMIT 1",
            [':uid' => self::userId(), ':cid' => $companyId]
        );
        return $row !== null;
    }

    public static function roleForCompany(int $companyId): ?array
    {
        return Database::getInstance()->selectOne(
            "SELECT * FROM user_companies WHERE user_id = :uid AND company_id = :cid LIMIT 1",
            [':uid' => self::userId(), ':cid' => $companyId]
        );
    }

    public static function canManageCompany(int $companyId): bool
    {
        $role = self::roleForCompany($companyId);
        return $role !== null && ((int)$role['can_manage_company'] === 1 || in_array($role['role'], ['owner', 'admin'], true));
    }

    public static function canManagePeriod(int $companyId): bool
    {
        $role = self::roleForCompany($companyId);
        return $role !== null && ((int)$role['can_manage_period'] === 1 || in_array($role['role'], ['owner', 'admin', 'accountant'], true));
    }

    private static function ensureSchema(): void
    {
        $db = Database::getInstance();

        $db->query("CREATE TABLE IF NOT EXISTS companies (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            company_name VARCHAR(255) NOT NULL,
            short_name VARCHAR(80) NOT NULL,
            tax_number VARCHAR(30) NULL,
            tax_office VARCHAR(150) NULL,
            mersis_no VARCHAR(50) NULL,
            trade_registry_no VARCHAR(50) NULL,
            address TEXT NULL,
            city VARCHAR(100) NULL,
            district VARCHAR(100) NULL,
            country VARCHAR(80) NOT NULL DEFAULT 'Türkiye',
            phone VARCHAR(40) NULL,
            email VARCHAR(150) NULL,
            website VARCHAR(150) NULL,
            logo_path VARCHAR(255) NULL,
            currency VARCHAR(5) NOT NULL DEFAULT 'TRY',
            status ENUM('active','passive','archived') NOT NULL DEFAULT 'active',
            created_by INT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY idx_companies_status (status),
            KEY idx_companies_deleted (deleted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");

        $db->query("CREATE TABLE IF NOT EXISTS accounting_periods (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id INT UNSIGNED NOT NULL,
            period_name VARCHAR(50) NOT NULL,
            fiscal_year INT NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            status ENUM('open','locked','closed','archived') NOT NULL DEFAULT 'open',
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            closing_date DATE NULL,
            closed_by INT UNSIGNED NULL,
            notes TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_period_company_year (company_id, fiscal_year),
            KEY idx_period_company_status (company_id, status),
            CONSTRAINT fk_period_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");

        $db->query("CREATE TABLE IF NOT EXISTS user_companies (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT UNSIGNED NOT NULL,
            company_id INT UNSIGNED NOT NULL,
            role ENUM('owner','admin','accountant','sales','viewer') NOT NULL DEFAULT 'owner',
            can_switch_company TINYINT(1) NOT NULL DEFAULT 1,
            can_manage_company TINYINT(1) NOT NULL DEFAULT 1,
            can_manage_period TINYINT(1) NOT NULL DEFAULT 1,
            is_default TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_user_company (user_id, company_id),
            KEY idx_user_companies_company (company_id),
            CONSTRAINT fk_user_company_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");

        if (!self::hasColumn('user_companies', 'is_default')) {
            $db->query("ALTER TABLE user_companies ADD COLUMN is_default TINYINT(1) NOT NULL DEFAULT 0 AFTER can_manage_period");
            self::$columnCache['user_companies.is_default'] = true;
        }

        $db->query("CREATE TABLE IF NOT EXISTS company_settings (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id INT UNSIGNED NOT NULL,
            invoice_prefix VARCHAR(20) NOT NULL DEFAULT 'SAT',
            purchase_invoice_prefix VARCHAR(20) NOT NULL DEFAULT 'ALI',
            quote_prefix VARCHAR(20) NOT NULL DEFAULT 'TEK',
            default_currency VARCHAR(5) NOT NULL DEFAULT 'TRY',
            default_vat_rate DECIMAL(5,2) NOT NULL DEFAULT 20.00,
            fiscal_year_start_month TINYINT UNSIGNED NOT NULL DEFAULT 1,
            fiscal_year_end_month TINYINT UNSIGNED NOT NULL DEFAULT 12,
            stock_tracking_enabled TINYINT(1) NOT NULL DEFAULT 1,
            e_invoice_enabled TINYINT(1) NOT NULL DEFAULT 0,
            theme_color VARCHAR(20) NOT NULL DEFAULT 'emerald',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_company_settings_company (company_id),
            CONSTRAINT fk_company_settings_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");

        if (!self::hasColumn('company_settings', 'theme_color')) {
            $db->query("ALTER TABLE company_settings ADD COLUMN theme_color VARCHAR(20) NOT NULL DEFAULT 'emerald' AFTER e_invoice_enabled");
            self::$columnCache['company_settings.theme_color'] = true;
        }

        // Stoğu aşan satışa izin verilsin mi? Varsayılan 1 = izin ver — mevcut
        // kurulumların davranışı DEĞİŞMEZ. Kapatmadan önce eldeki stokların
        // doğruluğu gözden geçirilmelidir; halihazırda negatif stok varsa bu
        // ayarı kapatmak o ürünlerin satışını anında durdurur.
        if (!self::hasColumn('company_settings', 'allow_negative_stock')) {
            $db->query("ALTER TABLE company_settings ADD COLUMN allow_negative_stock TINYINT(1) NOT NULL DEFAULT 1 AFTER stock_tracking_enabled");
            self::$columnCache['company_settings.allow_negative_stock'] = true;
        }

        if (!self::hasColumn('company_settings', 'is_storefront_source')) {
            $db->query("ALTER TABLE company_settings ADD COLUMN is_storefront_source TINYINT(1) NOT NULL DEFAULT 0 AFTER theme_color");
            self::$columnCache['company_settings.is_storefront_source'] = true;
        }

        // Kurulum: henüz hiçbir şirket "vitrin şirketi" (nymagro.com ile ürün
        // senkronu yapılan şirket) olarak işaretlenmemişse, adı "Nymagro" ile
        // eşleşen şirketi otomatik işaretle. Zaten bir vitrin şirketi
        // ayarlanmışsa (yönetici Şirket Ayarları'ndan değiştirmiş olabilir)
        // bir daha dokunulmaz.
        $mevcutVitrin = $db->selectOne("SELECT company_id FROM company_settings WHERE is_storefront_source = 1 LIMIT 1");
        if (!$mevcutVitrin) {
            $nymagro = $db->selectOne(
                "SELECT id FROM companies
                 WHERE deleted_at IS NULL AND (company_name LIKE 'Nymagro%' OR short_name LIKE 'Nymagro%')
                 ORDER BY id LIMIT 1"
            );
            if ($nymagro) {
                $cid = (int)$nymagro['id'];
                $satirVarMi = $db->selectOne("SELECT id FROM company_settings WHERE company_id = :cid", [':cid' => $cid]);
                if ($satirVarMi) {
                    $db->query("UPDATE company_settings SET is_storefront_source = 1 WHERE company_id = :cid", [':cid' => $cid]);
                } else {
                    $db->insert('company_settings', ['company_id' => $cid, 'is_storefront_source' => 1]);
                }
            }
        }

        $db->query("CREATE TABLE IF NOT EXISTS period_opening_balances (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id INT UNSIGNED NOT NULL,
            period_id INT UNSIGNED NOT NULL,
            account_type ENUM('customer','supplier','cash','bank','stock') NOT NULL,
            related_id INT UNSIGNED NULL,
            debit DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            credit DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            balance DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            currency VARCHAR(5) NOT NULL DEFAULT 'TRY',
            notes TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_opening_company_period (company_id, period_id),
            KEY idx_opening_type_related (account_type, related_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");

        foreach (self::COMPANY_TABLES as $table) {
            if (self::tableExists($table) && !self::hasColumn($table, 'company_id')) {
                $db->query("ALTER TABLE `{$table}` ADD COLUMN company_id INT UNSIGNED NULL AFTER id");
                self::$columnCache[$table . '.company_id'] = true;
            }
        }

        foreach (self::PERIOD_TABLES as $table) {
            if (self::tableExists($table) && !self::hasColumn($table, 'period_id')) {
                $db->query("ALTER TABLE `{$table}` ADD COLUMN period_id INT UNSIGNED NULL AFTER company_id");
                self::$columnCache[$table . '.period_id'] = true;
            }
        }

        foreach (self::COMPANY_TABLES as $table) {
            if (self::tableExists($table) && self::hasColumn($table, 'company_id')) {
                self::addIndex($table, "idx_{$table}_company", 'company_id');
            }
        }

        foreach (self::PERIOD_TABLES as $table) {
            if (self::tableExists($table) && self::hasColumn($table, 'period_id')) {
                self::addIndex($table, "idx_{$table}_period", 'period_id');
            }
        }

        self::ensureTenantUniqueIndexes();
    }

    private static function ensureDefaultTenant(): void
    {
        $db = Database::getInstance();

        $company = $db->selectOne("SELECT * FROM companies WHERE deleted_at IS NULL ORDER BY id LIMIT 1");
        if (!$company) {
            $companyId = $db->insert('companies', [
                'company_name' => 'Varsayılan Şirket',
                'short_name' => 'VARSAYILAN',
                'country' => 'Türkiye',
                'currency' => 'TRY',
                'status' => 'active',
                'created_by' => self::userId(),
            ]);
            $company = $db->selectOne("SELECT * FROM companies WHERE id = :id", [':id' => $companyId]);
        }

        $companyId = (int)$company['id'];
        $period = $db->selectOne(
            "SELECT * FROM accounting_periods WHERE company_id = :cid ORDER BY fiscal_year DESC LIMIT 1",
            [':cid' => $companyId]
        );
        if (!$period) {
            $year = (int)date('Y');
            $periodId = $db->insert('accounting_periods', [
                'company_id' => $companyId,
                'period_name' => (string)$year,
                'fiscal_year' => $year,
                'start_date' => "{$year}-01-01",
                'end_date' => "{$year}-12-31",
                'status' => 'open',
                'is_active' => 1,
            ]);
            $period = $db->selectOne("SELECT * FROM accounting_periods WHERE id = :id", [':id' => $periodId]);
        }

        $periodId = (int)$period['id'];

        $existingUserCompany = $db->selectOne(
            "SELECT id FROM user_companies WHERE user_id = :uid AND company_id = :cid LIMIT 1",
            [':uid' => self::userId(), ':cid' => $companyId]
        );
        if (!$existingUserCompany) {
            $db->insert('user_companies', [
                'user_id' => self::userId(),
                'company_id' => $companyId,
                'role' => 'owner',
                'can_switch_company' => 1,
                'can_manage_company' => 1,
                'can_manage_period' => 1,
                'is_default' => 1,
            ]);
        }

        $settings = $db->selectOne("SELECT id FROM company_settings WHERE company_id = :cid", [':cid' => $companyId]);
        if (!$settings) {
            $prefix = self::prefixFromCompany((string)$company['short_name']);
            $db->insert('company_settings', [
                'company_id' => $companyId,
                'invoice_prefix' => $prefix,
                'purchase_invoice_prefix' => $prefix . 'A',
                'quote_prefix' => $prefix . 'T',
                'default_currency' => $company['currency'] ?? 'TRY',
            ]);
        }

        foreach (self::COMPANY_TABLES as $table) {
            if (self::tableExists($table) && self::hasColumn($table, 'company_id')) {
                $db->query("UPDATE `{$table}` SET company_id = :cid WHERE company_id IS NULL", [':cid' => $companyId]);
            }
        }

        foreach (self::PERIOD_TABLES as $table) {
            if (self::tableExists($table) && self::hasColumn($table, 'period_id')) {
                $db->query("UPDATE `{$table}` SET period_id = :pid WHERE period_id IS NULL", [':pid' => $periodId]);
            }
        }
    }

    private static function ensureActiveSelection(): void
    {
        $db = Database::getInstance();
        $companyId = self::activeCompanyId();

        if (!$companyId || !self::userCanAccessCompany($companyId)) {
            $company = $db->selectOne(
                "SELECT c.*
                 FROM user_companies uc
                 JOIN companies c ON c.id = uc.company_id
                 WHERE uc.user_id = :uid AND c.status = 'active' AND c.deleted_at IS NULL
                 ORDER BY uc.is_default DESC, c.id LIMIT 1",
                [':uid' => self::userId()]
            );
            if ($company) {
                $_SESSION['active_company_id'] = (int)$company['id'];
            }
        }

        $companyId = self::activeCompanyId();
        if (!$companyId) {
            return;
        }

        $periodId = self::activePeriodId();
        $periodValid = false;
        if ($periodId) {
            $periodValid = $db->selectOne(
                "SELECT id FROM accounting_periods WHERE id = :pid AND company_id = :cid LIMIT 1",
                [':pid' => $periodId, ':cid' => $companyId]
            ) !== null;
        }

        if (!$periodValid) {
            $period = $db->selectOne(
                "SELECT * FROM accounting_periods
                 WHERE company_id = :cid AND status = 'open'
                 ORDER BY fiscal_year DESC LIMIT 1",
                [':cid' => $companyId]
            ) ?: $db->selectOne(
                "SELECT * FROM accounting_periods
                 WHERE company_id = :cid
                 ORDER BY fiscal_year DESC LIMIT 1",
                [':cid' => $companyId]
            );
            if ($period) {
                $_SESSION['active_period_id'] = (int)$period['id'];
            }
        }
    }

    private static function addIndex(string $table, string $index, string $columns): void
    {
        if (!self::indexExists($table, $index)) {
            try {
                Database::getInstance()->query("ALTER TABLE `{$table}` ADD INDEX `{$index}` ({$columns})");
            } catch (PDOException $e) {
                if ($e->getCode() !== '42000' || !str_contains($e->getMessage(), 'Duplicate key name')) {
                    throw $e;
                }
            }
        }
    }

    private static function addUniqueIndex(string $table, string $index, string $columns): void
    {
        if (!self::indexExists($table, $index)) {
            try {
                Database::getInstance()->query("ALTER TABLE `{$table}` ADD UNIQUE KEY `{$index}` ({$columns})");
            } catch (PDOException $e) {
                if ($e->getCode() !== '42000' || !str_contains($e->getMessage(), 'Duplicate key name')) {
                    throw $e;
                }
            }
        }
    }

    private static function dropIndexIfExists(string $table, string $index): void
    {
        if (self::indexExists($table, $index)) {
            Database::getInstance()->query("ALTER TABLE `{$table}` DROP INDEX `{$index}`");
        }
    }

    private static function indexExists(string $table, string $index): bool
    {
        $row = Database::getInstance()->selectOne(
            "SELECT COUNT(*) AS n
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND INDEX_NAME = :idx",
            [':table' => $table, ':idx' => $index]
        );
        return (int)($row['n'] ?? 0) > 0;
    }

    private static function ensureTenantUniqueIndexes(): void
    {
        if (self::tableExists('faturalar') && self::hasColumn('faturalar', 'company_id') && self::hasColumn('faturalar', 'period_id')) {
            self::dropIndexIfExists('faturalar', 'uq_fatura_no_tip');
            self::addUniqueIndex('faturalar', 'uq_fatura_company_period_no_tip', 'company_id, period_id, fatura_no, belge_tipi');
        }

        if (self::tableExists('cariler') && self::hasColumn('cariler', 'company_id')) {
            self::dropIndexIfExists('cariler', 'uq_cariler_kodu');
            self::addUniqueIndex('cariler', 'uq_cariler_company_kodu', 'company_id, cari_kodu');
        }

        if (self::tableExists('urunler_hizmetler') && self::hasColumn('urunler_hizmetler', 'company_id')) {
            self::dropIndexIfExists('urunler_hizmetler', 'uq_urun_stok_kodu');
            self::dropIndexIfExists('urunler_hizmetler', 'uq_urun_barkod');
            self::addUniqueIndex('urunler_hizmetler', 'uq_urun_company_stok_kodu', 'company_id, stok_kodu');
            self::addUniqueIndex('urunler_hizmetler', 'uq_urun_company_barkod', 'company_id, barkod');
        }

        if (self::tableExists('tanimlar') && self::hasColumn('tanimlar', 'company_id')) {
            self::dropIndexIfExists('tanimlar', 'uq_tanimlar_tur_ad');
            self::addUniqueIndex('tanimlar', 'uq_tanimlar_company_tur_ad', 'company_id, tur, ad, silindi_mi');
        }
    }

    private static function prefixFromCompany(string $name): string
    {
        $clean = preg_replace('/[^A-Za-z0-9]/', '', strtoupper($name));
        return substr($clean !== '' ? $clean : 'CMP', 0, 3);
    }
}
