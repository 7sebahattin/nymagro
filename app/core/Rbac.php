<?php
/**
 * Rbac
 * --------------------------------------------------------
 * Rol Tabanlı Yetkilendirme (RBAC) çekirdeği.
 *
 * Model:  User → role_id → roles → role_permissions → permissions
 *
 * Not: `users.role` (eski ENUM/VARCHAR alanı) geriye dönük uyumluluk
 * için AYNEN korunur — oturum/menü etiketleri hâlâ ondan okur. Yeni
 * yetkilendirme mantığı ise `role_id` üzerinden çalışır. İkisi
 * senkron tutulur (bkz. seedDefaults() ve UserController).
 *
 * Şirket/dönem erişimi (TenantContext + user_companies) bu sınıfın
 * KAPSAMI DIŞINDADIR — o, "hangi şirkete girebilir" sorusuna cevap
 * verir; Rbac ise "girdiği modülde ne yapabilir" sorusuna cevap verir.
 */
final class Rbac
{
    /** Her istekte bir kez hesaplanır (oturum içine YAZILMAZ — rol değişikliği anında etkili olsun diye). */
    private static array $permissionCache = [];
    private static array $superAdminCache = [];

    /** Kritik izinler — varsayılan olarak sadece Super Admin şablonuna verilir. */
    private const RESERVED_PREFIXES = ['USER_', 'ROLE_', 'AUDIT_'];

    /**
     * Modül kodu => [etiket, izin tipi]
     * izin tipi: 'crud' (VIEW/CREATE/UPDATE/DELETE) | 'view' (sadece VIEW)
     */
    private const MODULES = [
        'MUSTERI'   => ['Müşteriler', 'crud'],
        'TEDARIKCI' => ['Tedarikçiler', 'crud'],
        'PERSONEL'  => ['Personel', 'crud'],
        'URUN'      => ['Ürün / Hizmet Tanımları', 'crud'],
        'DEPO'      => ['Depolar', 'crud'],
        'SATIS'     => ['Satışlar', 'crud'],
        'ALIS'      => ['Alışlar', 'crud'],
        'TEKLIF'    => ['Teklifler', 'crud'],
        'HESAP'     => ['Kasa / Banka Hesapları', 'crud'],
        'MASRAF'    => ['Masraflar', 'crud'],
        'NAKIT'     => ['Gelen E-Faturalar', 'crud'],
        'EBELGE'    => ['e-Belge (XML) İçe Alma', 'crud'],
        'KREDI'     => ['Krediler', 'crud'],
        'DEMIRBAS'  => ['Demirbaşlar', 'crud'],
        'PROJE'     => ['Projeler', 'crud'],
        'PORTFOY'   => ['Çek / Senet Portföyü', 'crud'],
        'TANIM'     => ['Tanımlar', 'crud'],
        'FIHRIST'   => ['Fihrist', 'crud'],
        'TAKVIM'    => ['Takvim', 'crud'],
        'DOKUMAN'   => ['Cari Dokümanları', 'crud'],
        'SITE'      => ['Site Yönetimi', 'crud'],
        'RAPOR'     => ['Raporlar', 'view'],
        'EKSTRE'    => ['Cari Ekstresi', 'view'],
    ];

    /** Özel (CRUD kalıbına uymayan) izinler: kod => [modül, etiket] */
    private const EXTRA_PERMISSIONS = [
        'USER_VIEW'            => ['USER', 'Kullanıcıları Görüntüleme'],
        'USER_CREATE'          => ['USER', 'Kullanıcı Oluşturma'],
        'USER_UPDATE'          => ['USER', 'Kullanıcı Bilgisi/Rolü Güncelleme'],
        'USER_DISABLE'         => ['USER', 'Kullanıcı Aktif/Pasif Etme'],
        'USER_DELETE'          => ['USER', 'Kullanıcı Kalıcı Silme'],
        'USER_PASSWORD_RESET'  => ['USER', 'Kullanıcı Şifresi Sıfırlama'],
        'ROLE_VIEW'            => ['ROLE', 'Rolleri Görüntüleme'],
        'ROLE_CREATE'          => ['ROLE', 'Rol Oluşturma'],
        'ROLE_UPDATE'          => ['ROLE', 'Rol/İzin Güncelleme'],
        'ROLE_DELETE'          => ['ROLE', 'Rol Silme'],
        'AUDIT_VIEW'           => ['AUDIT', 'Audit Log / Giriş Geçmişi Görüntüleme'],
    ];

    private const MODULE_TITLES = [
        'USER'    => 'Kullanıcı Yönetimi',
        'ROLE'    => 'Rol Yönetimi',
        'AUDIT'   => 'Audit / Güvenlik',
        // Aşağıdaki ikisi CONTROLLER_MODULE/permission kataloğunda YOK (Company/Period
        // TenantContext ile yönetiliyor, Rbac izin gerektirmiyor) — sadece audit_logs'ta
        // okunabilir bir etiket görünsün diye burada tanımlı.
        'COMPANY' => 'Şirket Yönetimi',
        'PERIOD'  => 'Dönem Yönetimi',
    ];

    // ─── Bootstrap ──────────────────────────────────────────

    public static function bootstrap(): void
    {
        self::ensureSchema();
        self::seedDefaults();
    }

    private static function ensureSchema(): void
    {
        $db = Database::getInstance();

        $db->query("CREATE TABLE IF NOT EXISTS roles (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            code VARCHAR(50) NOT NULL,
            name VARCHAR(120) NOT NULL,
            description VARCHAR(255) NULL,
            is_system TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_roles_code (code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");

        $db->query("CREATE TABLE IF NOT EXISTS permissions (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            code VARCHAR(60) NOT NULL,
            module VARCHAR(30) NOT NULL,
            action VARCHAR(20) NOT NULL,
            label VARCHAR(150) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_permissions_code (code),
            KEY idx_permissions_module (module)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");

        $db->query("CREATE TABLE IF NOT EXISTS role_permissions (
            role_id INT UNSIGNED NOT NULL,
            permission_id INT UNSIGNED NOT NULL,
            PRIMARY KEY (role_id, permission_id),
            KEY idx_role_permissions_permission (permission_id),
            CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");

        // NOT: her ALTER'dan sonra sütun önbelleği (self::$columnCache) MANUEL
        // olarak güncellenir. Aksi halde aynı PHP isteği içinde (ör. bir alt
        // sınıf aynı sütunu tekrar sorgularsa) önbellek hâlâ "yok" değerini
        // döndürür ve ikinci bir ALTER denemesi "Duplicate column" hatasıyla
        // patlar — bu, gerçek bir MySQL üzerinde art arda iki bootstrap()
        // çağrısıyla doğrulanan bir hatanın düzeltmesidir.
        if (!self::hasColumn('users', 'role_id')) {
            $db->query("ALTER TABLE users ADD COLUMN role_id INT UNSIGNED NULL AFTER role");
            self::$columnCache['users.role_id'] = true;
        }
        if (!self::hasColumn('users', 'failed_login_count')) {
            $db->query("ALTER TABLE users ADD COLUMN failed_login_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER last_login_at");
            self::$columnCache['users.failed_login_count'] = true;
        }
        if (!self::hasColumn('users', 'locked_until')) {
            $db->query("ALTER TABLE users ADD COLUMN locked_until DATETIME NULL AFTER failed_login_count");
            self::$columnCache['users.locked_until'] = true;
        }
        if (!self::hasColumn('users', 'status_changed_at')) {
            $db->query("ALTER TABLE users ADD COLUMN status_changed_at DATETIME NULL AFTER status");
            self::$columnCache['users.status_changed_at'] = true;
        }

        // users.role eskiden ENUM('super_admin','admin','accountant','user') idi.
        // Yeni/özel rol kodlarını (VARCHAR) da barındırabilmesi için genişletiyoruz.
        // Mevcut değerler korunur — sadece kolon TİPİ değişir, veri değişmez.
        $roleColumnType = $db->selectOne(
            "SELECT COLUMN_TYPE AS t FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role'"
        );
        if ($roleColumnType && str_starts_with(strtolower((string)$roleColumnType['t']), 'enum')) {
            $db->query("ALTER TABLE users MODIFY COLUMN role VARCHAR(50) NOT NULL DEFAULT 'user'");
        }

        if (!self::hasColumn('users', 'status') || true) {
            // status zaten ENUM('active','passive'); 'locked' durumunu da destekleyelim.
            $statusColumnType = $db->selectOne(
                "SELECT COLUMN_TYPE AS t FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'status'"
            );
            $t = strtolower((string)($statusColumnType['t'] ?? ''));
            if ($t !== '' && !str_contains($t, "'locked'")) {
                $db->query("ALTER TABLE users MODIFY COLUMN status ENUM('active','passive','locked') NOT NULL DEFAULT 'active'");
            }
        }

        $db->query("CREATE TABLE IF NOT EXISTS audit_logs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT UNSIGNED NULL,
            user_snapshot VARCHAR(150) NULL,
            action VARCHAR(40) NOT NULL,
            module VARCHAR(30) NULL,
            record_id VARCHAR(40) NULL,
            company_id INT UNSIGNED NULL,
            before_json MEDIUMTEXT NULL,
            after_json MEDIUMTEXT NULL,
            description VARCHAR(500) NULL,
            success TINYINT(1) NOT NULL DEFAULT 1,
            ip_address VARCHAR(64) NULL,
            user_agent VARCHAR(255) NULL,
            request_id VARCHAR(64) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_audit_created (created_at),
            KEY idx_audit_user (user_id),
            KEY idx_audit_module (module),
            KEY idx_audit_action (action),
            KEY idx_audit_company (company_id),
            KEY idx_audit_record (module, record_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");

        if (!self::hasColumn('audit_logs', 'period_id')) {
            $db->query("ALTER TABLE audit_logs ADD COLUMN period_id INT UNSIGNED NULL AFTER company_id");
            self::$columnCache['audit_logs.period_id'] = true;
            $db->query("ALTER TABLE audit_logs ADD KEY idx_audit_period (period_id)");
        }

        $db->query("CREATE TABLE IF NOT EXISTS login_history (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT UNSIGNED NULL,
            username_attempted VARCHAR(150) NULL,
            event VARCHAR(20) NOT NULL,
            fail_reason VARCHAR(40) NULL,
            ip_address VARCHAR(64) NULL,
            user_agent VARCHAR(255) NULL,
            session_ref VARCHAR(64) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_login_history_user (user_id),
            KEY idx_login_history_created (created_at),
            KEY idx_login_history_event (event)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");
    }

    /**
     * İzin kataloğunu ve varsayılan rolleri hazırlar. İDEMPOTENT'tir:
     * - permissions: kodda olup tabloda olmayanlar eklenir (var olanlara dokunulmaz).
     * - roles: sadece tablo TAMAMEN boşsa ilk kurulum seed'i çalışır.
     */
    private static function seedDefaults(): void
    {
        $db = Database::getInstance();

        $existing = $db->select("SELECT code FROM permissions");
        $existingCodes = array_column($existing, 'code');

        foreach (self::permissionCatalogue() as $perm) {
            if (in_array($perm['code'], $existingCodes, true)) {
                continue;
            }
            $db->insert('permissions', [
                'code'   => $perm['code'],
                'module' => $perm['module'],
                'action' => $perm['action'],
                'label'  => $perm['label'],
            ]);
        }

        $roleCount = (int)($db->selectOne("SELECT COUNT(*) AS n FROM roles")['n'] ?? 0);
        if ($roleCount === 0) {
            self::seedInitialRoles();
        }

        // Kullanıcıların role_id'si boşsa, mevcut `role` string'inden eşle.
        $unlinked = $db->select("SELECT id, role FROM users WHERE role_id IS NULL");
        foreach ($unlinked as $u) {
            $roleRow = $db->selectOne("SELECT id FROM roles WHERE code = :c", [':c' => $u['role']]);
            if (!$roleRow) {
                // Bilinmeyen/eksik rol string'i → güvenli varsayılan: 'user' şablonu.
                $roleRow = $db->selectOne("SELECT id FROM roles WHERE code = 'user'");
            }
            if ($roleRow) {
                $db->update('users', ['role_id' => (int)$roleRow['id']], ['id' => $u['id']]);
            }
        }
    }

    private static function seedInitialRoles(): void
    {
        $db = Database::getInstance();
        $allCodes = array_column(self::permissionCatalogue(), 'code');
        $nonCriticalCodes = array_values(array_filter($allCodes, fn($c) => !self::isReserved($c)));

        // 1) Süper Yönetici — sistem rolü, silinemez, tüm izinler.
        $superAdminId = $db->insert('roles', [
            'code' => 'super_admin', 'name' => 'Süper Yönetici',
            'description' => 'Sistemdeki tüm modüllere ve yönetim işlevlerine tam erişim.',
            'is_system' => 1,
        ]);
        self::grantAll($superAdminId, $allCodes);

        // 2) Mevcut (miras) roller — mevcut kullanıcıların GÜNLÜK İŞLERİNİ (görüntüleme/
        //    ekleme/düzenleme) KORUR, ama DELETE'i otomatik vermez.
        //    Neden: migrasyon öncesi hiçbir yetki kontrolü yoktu, dolayısıyla teorik
        //    olarak herkes her şeyi silebiliyordu — ama bu bir TASARIM değil, sadece
        //    kontrolün hiç var olmamasıydı. Silme, kalıcı ve geri alınamaz bir işlem
        //    olduğu için "varsayılan olarak kimseye verilmeyen, Süper Yönetici'nin
        //    bilinçli olarak açtığı" bir yetki olmalı (spec §24/§71). Bu yüzden VIEW/
        //    CREATE/UPDATE miras rollerde otomatik açık kalır (çalışma akışı bozulmaz),
        //    DELETE ise açık DEĞİLDİR — gerekiyorsa Süper Yönetici Roller ekranından
        //    saniyeler içinde açabilir.
        $nonCriticalNoDelete = array_values(array_filter($nonCriticalCodes, fn($c) => !str_ends_with($c, '_DELETE')));
        foreach ([
            'admin'      => 'Yönetici (miras)',
            'accountant' => 'Muhasebe (miras)',
            'user'       => 'Kullanıcı (miras)',
        ] as $code => $name) {
            $rid = $db->insert('roles', [
                'code' => $code, 'name' => $name,
                'description' => 'Mevcut kullanıcıların görüntüleme/ekleme/düzenleme erişimini koruyan geçiş rolü. Silme yetkisi YOKTUR — gerekiyorsa Roller ekranından açılmalı.',
                'is_system' => 0,
            ]);
            self::grantAll($rid, $nonCriticalNoDelete);
        }

        // 3) Yeni şablon roller (spec'te istenen) — Süper Yönetici bunları
        //    yeni personele bilinçli olarak atayabilir.
        $viewOnly = array_values(array_filter($nonCriticalCodes, fn($c) => str_ends_with($c, '_VIEW')));
        $createUpdateView = array_values(array_filter($nonCriticalCodes, function ($c) {
            return str_ends_with($c, '_VIEW') || str_ends_with($c, '_CREATE') || str_ends_with($c, '_UPDATE');
        }));
        $financeModules = ['HESAP', 'MASRAF', 'NAKIT', 'KREDI', 'SATIS', 'ALIS', 'RAPOR', 'EKSTRE', 'PORTFOY'];
        $financePerms = array_values(array_filter($nonCriticalCodes, function ($c) use ($financeModules) {
            foreach ($financeModules as $m) {
                if (str_starts_with($c, $m . '_')) return true;
            }
            return false;
        }));

        $rid = $db->insert('roles', [
            'code' => 'data_entry', 'name' => 'Veri Giriş',
            'description' => 'Görüntüleme, ekleme ve düzenleme yapabilir; silme yetkisi yoktur.',
            'is_system' => 0,
        ]);
        self::grantAll($rid, $createUpdateView);

        $rid = $db->insert('roles', [
            'code' => 'viewer', 'name' => 'Görüntüleme',
            'description' => 'Sadece görüntüleme yetkisi vardır.',
            'is_system' => 0,
        ]);
        self::grantAll($rid, $viewOnly);

        $rid = $db->insert('roles', [
            'code' => 'muhasebe', 'name' => 'Muhasebe',
            'description' => 'Finans modüllerinde görüntüleme/ekleme/düzenleme; silme yetkisi yoktur.',
            'is_system' => 0,
        ]);
        self::grantAll($rid, array_values(array_filter($financePerms, function ($c) {
            return str_ends_with($c, '_VIEW') || str_ends_with($c, '_CREATE') || str_ends_with($c, '_UPDATE');
        })));
    }

    private static function grantAll(int $roleId, array $permissionCodes): void
    {
        $db = Database::getInstance();
        $rows = $db->select("SELECT id, code FROM permissions");
        $map = [];
        foreach ($rows as $r) {
            $map[$r['code']] = (int)$r['id'];
        }
        foreach ($permissionCodes as $code) {
            if (!isset($map[$code])) continue;
            $db->query(
                "INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (:r, :p)",
                [':r' => $roleId, ':p' => $map[$code]]
            );
        }
    }

    public static function isReserved(string $permissionCode): bool
    {
        foreach (self::RESERVED_PREFIXES as $prefix) {
            if (str_starts_with($permissionCode, $prefix)) return true;
        }
        return false;
    }

    // ─── Katalog ────────────────────────────────────────────

    /** @return array<int, array{code:string, module:string, action:string, label:string}> */
    public static function permissionCatalogue(): array
    {
        $out = [];
        foreach (self::MODULES as $moduleCode => [$label, $type]) {
            $actions = $type === 'crud' ? ['VIEW', 'CREATE', 'UPDATE', 'DELETE'] : ['VIEW'];
            $actionLabels = ['VIEW' => 'Görüntüleme', 'CREATE' => 'Ekleme', 'UPDATE' => 'Düzenleme', 'DELETE' => 'Silme'];
            foreach ($actions as $action) {
                $out[] = [
                    'code'   => $moduleCode . '_' . $action,
                    'module' => $moduleCode,
                    'action' => $action,
                    'label'  => $label . ' - ' . $actionLabels[$action],
                ];
            }
        }
        foreach (self::EXTRA_PERMISSIONS as $code => [$moduleCode, $label]) {
            $parts = explode('_', $code);
            $action = end($parts);
            $out[] = ['code' => $code, 'module' => $moduleCode, 'action' => $action, 'label' => $label];
        }
        return $out;
    }

    public static function moduleTitle(string $moduleCode): string
    {
        return self::MODULE_TITLES[$moduleCode] ?? (self::MODULES[$moduleCode][0] ?? $moduleCode);
    }

    /** @return array<string,string> modül kodu => Türkçe etiket (menü sırasına yakın) */
    public static function moduleLabels(): array
    {
        $out = [];
        foreach (self::MODULES as $code => [$label, ]) {
            $out[$code] = $label;
        }
        foreach (self::MODULE_TITLES as $code => $label) {
            $out[$code] = $label;
        }
        return $out;
    }

    // ─── Yetki sorgulama ────────────────────────────────────

    public static function isSuperAdmin(int $userId): bool
    {
        if ($userId <= 0) return false;
        if (array_key_exists($userId, self::$superAdminCache)) {
            return self::$superAdminCache[$userId];
        }
        $row = Database::getInstance()->selectOne(
            "SELECT r.code FROM users u
             LEFT JOIN roles r ON r.id = u.role_id
             WHERE u.id = :id",
            [':id' => $userId]
        );
        return self::$superAdminCache[$userId] = ($row && $row['code'] === 'super_admin');
    }

    /** @return string[] izin kodları */
    public static function permissionCodesForUser(int $userId): array
    {
        if ($userId <= 0) return [];
        if (isset(self::$permissionCache[$userId])) {
            return self::$permissionCache[$userId];
        }
        if (self::isSuperAdmin($userId)) {
            return self::$permissionCache[$userId] = array_column(self::permissionCatalogue(), 'code');
        }
        $rows = Database::getInstance()->select(
            "SELECT p.code FROM users u
             JOIN role_permissions rp ON rp.role_id = u.role_id
             JOIN permissions p ON p.id = rp.permission_id
             WHERE u.id = :id",
            [':id' => $userId]
        );
        return self::$permissionCache[$userId] = array_column($rows, 'code');
    }

    public static function can(int $userId, string $permissionCode): bool
    {
        return in_array($permissionCode, self::permissionCodesForUser($userId), true);
    }

    public static function currentUserCan(string $permissionCode): bool
    {
        if (!class_exists('AuthGuard') || !AuthGuard::isLoggedIn()) return false;
        return self::can(AuthGuard::userId(), $permissionCode);
    }

    /** Bir kullanıcının aktif (locked_until dahil) tek Süper Yönetici olup olmadığını kontrol eder. */
    public static function isLastActiveSuperAdmin(int $userId): bool
    {
        $db = Database::getInstance();
        $count = (int)($db->selectOne(
            "SELECT COUNT(*) AS n FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE r.code = 'super_admin' AND u.status = 'active' AND u.id <> :id",
            [':id' => $userId]
        )['n'] ?? 0);
        return $count === 0 && self::isSuperAdmin($userId);
    }

    public static function activeSuperAdminCount(): int
    {
        $db = Database::getInstance();
        return (int)($db->selectOne(
            "SELECT COUNT(*) AS n FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE r.code = 'super_admin' AND u.status = 'active'"
        )['n'] ?? 0);
    }

    // ─── Router entegrasyonu: Controller/Method → izin kodu ─

    /** RBAC kapsamı dışındaki controller'lar (kendi erişim kontrolü var ya da herkese açık). */
    private const UNMANAGED_CONTROLLERS = [
        'AuthController', 'HomeController', 'WebsiteController', 'SitemapController',
        'ProfilController', 'CompanyController', 'PeriodController', 'DashboardController',
    ];

    private const CONTROLLER_MODULE = [
        'MusteriController'   => 'MUSTERI',
        'TedarikciController' => 'TEDARIKCI',
        'PersonelController'  => 'PERSONEL',
        'UrunController'      => 'URUN',
        'DepoController'      => 'DEPO',
        'SatisController'     => 'SATIS',
        'AlisController'      => 'ALIS',
        'TeklifController'    => 'TEKLIF',
        'HesapController'     => 'HESAP',
        'MasrafController'    => 'MASRAF',
        'NakitController'     => 'NAKIT',
        'EBelgeController'    => 'EBELGE',
        'KrediController'     => 'KREDI',
        'DemirbasController'  => 'DEMIRBAS',
        'ProjeController'     => 'PROJE',
        'PortfoyController'   => 'PORTFOY',
        'TanimController'     => 'TANIM',
        'RaporController'     => 'RAPOR',
        'FihristController'   => 'FIHRIST',
        'TakvimController'    => 'TAKVIM',
        'DokumanController'   => 'DOKUMAN',
        'EkstreController'    => 'EKSTRE',
        'SiteController'      => 'SITE',
        'UserController'      => 'USER',
        'RoleController'      => 'ROLE',
        'AuditController'     => 'AUDIT',
    ];

    /** Modül geneli için sabit aksiyon (ör. sadece görüntüleme modülleri). */
    private const FORCE_VIEW_CONTROLLERS = ['RaporController', 'EkstreController'];

    /** Genel sınıflandırıcının yanlış tahmin ettiği yöntemler için istisna: Controller::method => ACTION */
    private const METHOD_OVERRIDES = [
        'KrediController::taksitOde'    => 'UPDATE',
        'MasrafController::odemeYap'    => 'UPDATE',
        'MasrafController::kopyala'     => 'CREATE',
        'DepoController::sayimKaydet'   => 'UPDATE',
        'SiteController::ayarlarKaydet' => 'UPDATE',
        'UrunController::stokGiris'     => 'UPDATE',
        'NakitController::gelen_e_faturalar' => 'VIEW',
        'NakitController::gelen_e_faturalar_create' => 'CREATE',
        'NakitController::gelen_e_faturalar_delete' => 'DELETE',
        'NakitController::kaydet'       => 'CREATE',
        'UserController::durum'         => 'DISABLE',
        'UserController::sifre_sifirla' => 'PASSWORD_RESET',
    ];

    /** Katalogda (CONTROLLER_MODULE veya UNMANAGED_CONTROLLERS) olmayan bir controller'a erişilmeye çalışıldığında dönen sabit — hiçbir rolde (Süper Yönetici dahil) bu kodun karşılığı YOKTUR, dolayısıyla erişim FAIL-CLOSED reddedilir. */
    private const UNCATALOGUED_SENTINEL = '__RBAC_UNCATALOGUED__';

    /**
     * Bir controller'ın RBAC kataloğunda (CONTROLLER_MODULE ya da UNMANAGED_CONTROLLERS)
     * tanımlı olup olmadığını döner. Geliştirme/test ortamında yeni bir controller
     * bu iki listeden birine eklenmeden kullanılırsa fail-closed reddedilir (bkz.
     * requiredPermissionFor() / authorizeOrDeny()) — "kataloglanmamış = izinli"
     * değil, "kataloglanmamış = reddedildi" ilkesi.
     */
    public static function isCatalogued(string $controllerName): bool
    {
        return in_array($controllerName, self::UNMANAGED_CONTROLLERS, true)
            || isset(self::CONTROLLER_MODULE[$controllerName]);
    }

    /**
     * Controller+method için gerekli izin kodunu döndürür.
     * null dönerse: bu controller AÇIKÇA (UNMANAGED_CONTROLLERS'da) RBAC kapsamı
     * dışıdır (sadece login yeterli — herkese açık ya da kendi erişim modeli var).
     * self::UNCATALOGUED_SENTINEL dönerse: bu controller HİÇBİR listede kayıtlı
     * değil — fail-closed, kimseye (Süper Yönetici dahil) izin verilmez.
     */
    public static function requiredPermissionFor(string $controllerName, string $methodName): ?string
    {
        if (in_array($controllerName, self::UNMANAGED_CONTROLLERS, true)) {
            return null;
        }
        $module = self::CONTROLLER_MODULE[$controllerName] ?? null;
        if ($module === null) {
            // Kayıt altına alınmamış (kataloglanmamış) bir controller — fail-closed:
            // yeni eklenen bir controller RBAC listesine kaydedilmeden sessizce
            // "sadece login yeterli" moduna düşmesin diye erişim REDDEDİLİR.
            return self::UNCATALOGUED_SENTINEL;
        }

        if (in_array($controllerName, self::FORCE_VIEW_CONTROLLERS, true)) {
            return $module . '_VIEW';
        }

        $overrideKey = $controllerName . '::' . $methodName;
        if (isset(self::METHOD_OVERRIDES[$overrideKey])) {
            return $module . '_' . self::METHOD_OVERRIDES[$overrideKey];
        }

        return $module . '_' . self::classifyAction($methodName);
    }

    private static function classifyAction(string $method): string
    {
        $m = strtolower($method);

        if (str_ends_with($m, 'sil')) {
            return 'DELETE';
        }
        if ($m === 'iptal' || str_ends_with($m, 'iptal') || $m === 'durum' || str_ends_with($m, 'durum')) {
            return 'UPDATE';
        }
        if ($m === 'duzenle' || str_ends_with($m, 'guncelle')) {
            return 'UPDATE';
        }
        if (str_ends_with($m, 'kaydet') || str_contains($m, 'kaydet')
            || $m === 'ekle' || str_ends_with($m, 'ekle') || str_contains($m, '_ekle')
            || str_ends_with($m, 'yukle') || str_contains($m, 'yukle')) {
            return 'CREATE';
        }
        return 'VIEW';
    }

    /**
     * Router tarafından, controller/method belirlendikten sonra, action çağrılmadan
     * ÖNCE çağrılır. Yetki yoksa 403 basar ve süreci sonlandırır.
     */
    public static function authorizeOrDeny(string $controllerName, string $methodName): void
    {
        $permissionCode = self::requiredPermissionFor($controllerName, $methodName);
        if ($permissionCode === null) {
            return;
        }

        // Fail-closed: kataloglanmamış bir controller — Süper Yönetici DAHİL kimseye
        // izin verilmez (permissionCatalogue()'da hiçbir zaman karşılığı olmayan bir
        // sentinel koddur). Geliştirici geri bildirimi için dev/test'te error_log'a
        // açık bir uyarı yazılır; production'da kullanıcıya sadece genel 403 gösterilir.
        if ($permissionCode === self::UNCATALOGUED_SENTINEL) {
            $isDev = !defined('APP_ENV') || APP_ENV !== 'production';
            if ($isDev) {
                error_log("[RBAC YAPILANDIRMA HATASI] '{$controllerName}' Rbac::CONTROLLER_MODULE veya "
                    . "UNMANAGED_CONTROLLERS listesinde tanımlı değil — fail-closed olarak reddedildi. "
                    . "Bu controller'ı kataloğa ekleyin (bkz. app/core/Rbac.php).");
            }
            if (class_exists('AuthGuard') && AuthGuard::isLoggedIn() && class_exists('Audit')) {
                Audit::log('UNAUTHORIZED_ACCESS_ATTEMPT', null, null, null, null,
                    "{$controllerName}::{$methodName} → RBAC kataloğunda tanımsız controller, fail-closed reddedildi.", false);
            }
            self::renderForbidden($isDev ? "'{$controllerName}' RBAC kataloğunda tanımlı değil (yapılandırma hatası)." : null);
        }

        if (!class_exists('AuthGuard') || !AuthGuard::isLoggedIn()) {
            return; // AuthGuard::requireLogin() zaten bu isteği login'e yönlendirmiş olmalı.
        }

        $userId = AuthGuard::userId();
        if (self::can($userId, $permissionCode)) {
            return;
        }

        if (class_exists('Audit')) {
            $moduleCode = explode('_', $permissionCode, 2)[0];
            Audit::log('UNAUTHORIZED_ACCESS_ATTEMPT', $moduleCode, null, null, null,
                "{$controllerName}::{$methodName} → {$permissionCode} izni gerekliydi, reddedildi.", false);
        }

        self::renderForbidden();
    }

    private static function renderForbidden(?string $devDetail = null): void
    {
        http_response_code(403);
        $detail = $devDetail !== null
            ? '<p style="background:#fff3cd;border:1px solid #ffe08a;border-radius:8px;padding:10px 12px;font-size:13px;color:#7a5b00;text-align:left;">'
                . '<strong>Geliştirici notu (sadece dev/test):</strong> ' . htmlspecialchars($devDetail) . '</p>'
            : '';
        echo '<!doctype html><html lang="tr"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>Yetkisiz İşlem</title>'
            . '<style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#f5f6f8;'
            . 'display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}'
            . '.box{background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,.08);padding:40px;'
            . 'max-width:460px;text-align:center}h1{font-size:20px;color:#c0392b;margin:0 0 12px}'
            . 'p{color:#444;line-height:1.5}a{display:inline-block;margin-top:16px;color:#0D623A;'
            . 'text-decoration:none;font-weight:600}</style></head><body>'
            . '<div class="box"><h1>Yetkisiz İşlem</h1>'
            . '<p>Bu işlemi gerçekleştirmek için yetkiniz bulunmuyor.</p>'
            . $detail
            . '<a href="' . htmlspecialchars(BASE_URL) . '/dashboard">Ana sayfaya dön</a></div>'
            . '</body></html>';
        exit;
    }

    // ─── Yardımcılar ────────────────────────────────────────

    private static array $columnCache = [];

    private static function hasColumn(string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        if (array_key_exists($key, self::$columnCache)) {
            return self::$columnCache[$key];
        }
        $row = Database::getInstance()->selectOne(
            "SELECT COUNT(*) AS n FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c",
            [':t' => $table, ':c' => $column]
        );
        return self::$columnCache[$key] = ((int)($row['n'] ?? 0) > 0);
    }
}
