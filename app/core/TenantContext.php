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
        'fatura_odeme_uygulamalari',
    ];

    private const PERIOD_TABLES = [
        'faturalar', 'fatura_kalemleri', 'kasa_hareketleri', 'masraflar',
        'gelen_efaturalar', 'gelen_efatura_odemeleri', 'gelen_efatura_import_batches',
        'gelen_efatura_notlari', 'stok_hareketleri', 'cek_senet_portfoyu',
        'personel_hareketleri',
        'krediler', 'kredi_odeme_plani', 'demirbaslar', 'projeler',
        'fatura_odeme_uygulamalari',
    ];

    private const WRITE_TABLES = [
        'cariler', 'urunler_hizmetler', 'depolar', 'urun_stok_depo',
        'kasa_banka', 'faturalar', 'fatura_kalemleri', 'kasa_hareketleri',
        'masraflar', 'cari_dokumanlar', 'gelen_efaturalar', 'gelen_efatura_kalemleri',
        'gelen_efatura_odemeleri', 'gelen_efatura_import_batches',
        'gelen_efatura_notlari',
        'stok_hareketleri', 'cek_senet_portfoyu', 'personel_hareketleri',
        'krediler', 'kredi_odeme_plani', 'demirbaslar', 'projeler',
        'fatura_odeme_uygulamalari',
    ];

    private const PUBLIC_ROUTES = [
        'companies', 'periods', 'cikis', 'profil',
    ];

    public static function bootstrap(): void
    {
        // Şema her istekte (giriş yapılmamış olsa bile) hazır olmalı — tablo
        // yoksa giriş ekranı bile açılamaz.
        self::ensureSchema();

        // Şirket/dönem seçimi KULLANICIYA aittir. Giriş yapılmamışken bunları
        // çalıştırmak, oturumu olmayan bir ziyaretçi adına (eskiden userId()
        // varsayılan olarak 1 döndürdüğü için "1 numaralı kullanıcı" adına)
        // şirket ataması ve oturum seçimi yapılmasına yol açıyordu; giriş
        // yapıldığında bu yabancı seçim oturumda kalıyor ve kullanıcı
        // "Bu şirkete erişim yetkiniz yok." ekranına kilitleniyordu.
        if (!self::isLoggedIn()) {
            return;
        }

        self::ensureDefaultTenant();
        self::ensureActiveSelection();
    }

    /** Oturum açılmış mı? (AuthGuard yüklenmemiş olabilecek bağlamlarda güvenli.) */
    private static function isLoggedIn(): bool
    {
        if (class_exists('AuthGuard')) {
            return AuthGuard::isLoggedIn();
        }
        return !empty($_SESSION['user_id']) && !empty($_SESSION['user_logged_in']);
    }

    public static function requireActiveForRoute(array $url): void
    {
        $section = strtolower($url[0] ?? '');
        if ($section === '' || in_array($section, self::PUBLIC_ROUTES, true)) {
            return;
        }

        // Erişilemeyen bir şirket oturumda kalmışsa (ör. kullanıcının şirket
        // ataması kaldırıldı) kullanıcıyı ham 403 sayfasında bırakma — seçimi
        // temizleyip "Şirket Değiştir" ekranına gönder. Aksi halde kullanıcının
        // kendi başına çıkabileceği bir yol yoktu (bkz. companies/switch).
        if (self::activeCompanyId() && !self::userCanAccessCompany(self::activeCompanyId())) {
            self::clearActiveSelection();
            $_SESSION['flash'] = [
                'tip' => 'error',
                'mesaj' => 'Aktif şirketinize erişim yetkiniz kalmadı. Lütfen yetkili olduğunuz bir şirket seçin.',
            ];
        }

        if (!self::activeCompanyId() || !self::activePeriodId()) {
            header('Location: ' . BASE_URL . '/companies/switch');
            exit;
        }
    }

    /** Oturumdaki aktif şirket/dönem seçimini temizler. */
    public static function clearActiveSelection(): void
    {
        unset(
            $_SESSION['active_company_id'],
            $_SESSION['active_period_id'],
            $_SESSION['active_tenant_user_id']
        );
    }

    /**
     * Aktif şirket/dönem seçimini oturuma yazar.
     *
     * Seçimi HANGİ kullanıcının yaptığını da kaydeder: bu olmadan bir sonraki
     * istekte ensureActiveSelection() seçimi "sahipsiz" görüp yok sayar ve
     * kullanıcıyı varsayılan şirkete geri düşürürdü. Şirket/dönem seçen her
     * yol bu metodu kullanmalıdır.
     */
    public static function setActiveSelection(int $companyId, ?int $periodId = null): void
    {
        $_SESSION['active_company_id'] = $companyId;
        $_SESSION['active_tenant_user_id'] = self::userId();
        if ($periodId !== null && $periodId > 0) {
            $_SESSION['active_period_id'] = $periodId;
        } else {
            unset($_SESSION['active_period_id']);
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

    /**
     * Oturumdaki kullanıcının id'si; giriş yapılmamışsa 0.
     *
     * ESKİDEN varsayılan 1 dönüyordu. Bu, giriş yapmamış bir ziyaretçinin
     * "1 numaralı kullanıcı" sanılmasına yol açıyordu: bootstrap sırasında
     * o kullanıcı adına şirket ataması yapılıyor ve oturuma onun şirketi
     * yazılıyordu. Giriş yapan BAŞKA bir kullanıcı bu seçimi devralıp
     * erişemediği bir şirkete kilitleniyordu. 0 dönmek doğru davranıştır:
     * hiçbir user_companies satırı 0 ile eşleşmez, yani anonim istek
     * hiçbir şirkete erişemez (fail-closed).
     */
    public static function userId(): int
    {
        return (int)($_SESSION['user_id'] ?? 0);
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
        if ($companyId <= 0 || self::userId() <= 0) {
            return false;
        }
        $row = Database::getInstance()->selectOne(
            "SELECT id FROM user_companies WHERE user_id = :uid AND company_id = :cid LIMIT 1",
            [':uid' => self::userId(), ':cid' => $companyId]
        );
        return $row !== null;
    }

    public static function roleForCompany(int $companyId): ?array
    {
        if ($companyId <= 0 || self::userId() <= 0) {
            return null;
        }
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

        // İLK KURULUM ataması — SADECE sistemde henüz hiçbir şirket ataması
        // yokken çalışır.
        //
        // ESKİDEN bu blok, giriş yapan HER kullanıcıya id'si en küçük şirketin
        // 'owner' rolünü sessizce veriyordu. Sonuçları:
        //   1) Yetki yükselmesi: yeni açılan her kullanıcı, kendisine hiç
        //      atanmamış bir şirkette şirket/dönem yönetebilen 'owner' oluyordu.
        //   2) Kafa karışıklığı: kullanıcı "Şirket Değiştir" ekranında hiç
        //      atanmadığı (ve çoğu zaman pasif olan) bir şirketi görüyor,
        //      seçemiyor ve başka seçeneği olmadığı için sıkışıp kalıyordu.
        // Artık şirket ataması yalnızca açık bir yönetici işlemiyle yapılır
        // (Kullanıcı Yönetimi > kullanıcı formundaki "Yetkili Şirketler").
        $anyAssignmentExists = $db->selectOne("SELECT id FROM user_companies LIMIT 1");
        if (!$anyAssignmentExists && self::userId() > 0) {
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

    /**
     * SAF KARAR FONKSİYONU — hangi şirketin aktif olacağını belirler.
     *
     * Veritabanı/oturum'a dokunmaz; bu sayede tüm varyasyonlarıyla test
     * edilebilir (bkz. tests/regression/tenant_secim_invariants.php).
     *
     * Kurallar:
     *  1) Giriş yapılmamışsa (userId <= 0) hiçbir şirket seçilmez.
     *  2) Oturumdaki seçim BAŞKA bir kullanıcıya aitse yok sayılır — aynı
     *     tarayıcı oturumunda kullanıcı değiştiğinde önceki kullanıcının
     *     şirketi devralınmaz.
     *  3) Oturumdaki şirket kullanıcının erişebildiği ve AKTİF bir şirketse
     *     korunur.
     *  4) Aksi halde erişilebilir aktif şirketlerden ilki seçilir
     *     (önce is_default, sonra en küçük id).
     *  5) Hiçbiri yoksa null döner — çağıran taraf oturumdaki seçimi
     *     TEMİZLEMEK zorundadır (eski hata: temizlenmeyip erişilemeyen id
     *     oturumda kalıyordu ve kullanıcı her sayfada 403 görüyordu).
     *
     * @param array<int,array{id:int|string,status:string,is_default?:int|string}> $accessibleCompanies
     */
    public static function resolveCompanySelection(
        int $userId,
        ?int $sessionCompanyId,
        int $sessionOwnerId,
        array $accessibleCompanies
    ): ?int {
        if ($userId <= 0) {
            return null;
        }

        $active = array_values(array_filter(
            $accessibleCompanies,
            fn(array $c): bool => ($c['status'] ?? '') === 'active'
        ));

        if ($sessionCompanyId !== null && $sessionCompanyId > 0 && $sessionOwnerId === $userId) {
            foreach ($active as $c) {
                if ((int)$c['id'] === $sessionCompanyId) {
                    return $sessionCompanyId;
                }
            }
        }

        usort($active, function (array $a, array $b): int {
            $byDefault = (int)($b['is_default'] ?? 0) <=> (int)($a['is_default'] ?? 0);
            return $byDefault !== 0 ? $byDefault : ((int)$a['id'] <=> (int)$b['id']);
        });

        return $active ? (int)$active[0]['id'] : null;
    }

    /**
     * SAF KARAR FONKSİYONU — seçili şirket için hangi dönemin aktif olacağı.
     *
     * Oturumdaki dönem bu şirkete aitse korunur; değilse önce 'open' olanlar
     * arasından en yeni mali yıl, o da yoksa herhangi bir dönem seçilir.
     *
     * @param array<int,array{id:int|string,status:string,fiscal_year:int|string}> $companyPeriods
     */
    public static function resolvePeriodSelection(?int $sessionPeriodId, array $companyPeriods): ?int
    {
        if ($sessionPeriodId !== null && $sessionPeriodId > 0) {
            foreach ($companyPeriods as $p) {
                if ((int)$p['id'] === $sessionPeriodId) {
                    return $sessionPeriodId;
                }
            }
        }

        $enYeni = static function (array $list): ?int {
            if (!$list) {
                return null;
            }
            usort($list, function (array $a, array $b): int {
                $byYear = (int)$b['fiscal_year'] <=> (int)$a['fiscal_year'];
                return $byYear !== 0 ? $byYear : ((int)$b['id'] <=> (int)$a['id']);
            });
            return (int)$list[0]['id'];
        };

        $acik = array_values(array_filter($companyPeriods, fn(array $p): bool => ($p['status'] ?? '') === 'open'));
        return $enYeni($acik) ?? $enYeni(array_values($companyPeriods));
    }

    private static function ensureActiveSelection(): void
    {
        $db = Database::getInstance();
        $userId = self::userId();

        $accessible = $db->select(
            "SELECT c.id, c.status, uc.is_default
             FROM user_companies uc
             JOIN companies c ON c.id = uc.company_id
             WHERE uc.user_id = :uid AND c.deleted_at IS NULL",
            [':uid' => $userId]
        );

        $companyId = self::resolveCompanySelection(
            $userId,
            self::activeCompanyId(),
            (int)($_SESSION['active_tenant_user_id'] ?? 0),
            $accessible
        );

        if ($companyId === null) {
            // Seçilebilir şirket yok: oturumdaki (erişilemeyen/pasif) seçimi
            // MUTLAKA temizle, aksi halde kullanıcı çıkışsız bir 403'e kilitlenir.
            self::clearActiveSelection();
            return;
        }

        $periods = $db->select(
            "SELECT id, status, fiscal_year FROM accounting_periods WHERE company_id = :cid",
            [':cid' => $companyId]
        );
        $periodId = self::resolvePeriodSelection(self::activePeriodId(), $periods);

        self::setActiveSelection($companyId, $periodId);
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
