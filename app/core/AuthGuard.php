<?php
/**
 * AuthGuard
 * --------------------------------------------------------
 * Oturum yönetimi + şifre doğrulama + şema bootstrap.
 *
 * Ana akış (Router içinde çağrılır):
 *   AuthGuard::bootstrap();         // users tablosu + admin seed
 *   AuthGuard::requireLogin($url);  // korumasız rotalar dışında login zorunlu
 */
final class AuthGuard
{
    /** Oturum gerektirmeyen URL segmentleri */
    private const PUBLIC_SEGMENTS = ['', 'home', 'auth', 'giris', 'cikis', 'tr', 'en', 'ru', 'sitemap.xml', 'robots.txt'];

    /** Statik / direkt erişilebilen yardımcı sayfalar */
    private const PUBLIC_ASSETS = ['css', 'js', 'img', 'uploads'];

    /** Brute-force koruması: bu sayıda üst üste başarısız girişten sonra hesap geçici kilitlenir. */
    private const MAX_FAILED_ATTEMPTS = 5;
    private const LOCK_MINUTES = 15;

    public static function bootstrap(): void
    {
        self::ensureUsersTable();
        self::ensureUserProfileColumns();
        self::ensureDefaultAdmin();
    }

    public static function requireLogin(array $url): void
    {
        $segment = strtolower($url[0] ?? '');

        if (in_array($segment, self::PUBLIC_SEGMENTS, true)) {
            return;
        }
        if (in_array($segment, self::PUBLIC_ASSETS, true)) {
            return;
        }

        if (!self::isLoggedIn()) {
            $next = self::currentRelativePath();
            header('Location: ' . BASE_URL . '/giris' . ($next ? '?next=' . urlencode($next) : ''));
            exit;
        }

        // Rol/izin değişiklikleri VEYA pasifleştirme/kilitleme anında etkili olsun diye
        // her istekte hafif bir DB kontrolü yapılır (tek satır, indeksli PK sorgusu).
        self::revalidateActiveSession();
    }

    /**
     * Aktif oturumun kullanıcısı hâlâ 'active' mi diye kontrol eder.
     * Pasifleştirilmiş/kilitlenmiş bir kullanıcının mevcut oturumu ANINDA sonlandırılır
     * (spec §21/§65: rol/izin veya durum değişikliğinde eski yetkilerle devam etmesin).
     * Rol adı/etiketi de senkron tutulur (rol değişikliği menüde hemen yansısın).
     */
    private static function revalidateActiveSession(): void
    {
        if (!self::isLoggedIn()) {
            return;
        }
        $row = Database::getInstance()->selectOne(
            "SELECT status, role, full_name, avatar_path FROM users WHERE id = :id",
            [':id' => self::userId()]
        );
        if (!$row || $row['status'] !== 'active') {
            self::logout();
            $next = self::currentRelativePath();
            header('Location: ' . BASE_URL . '/giris?expired=1' . ($next ? '&next=' . urlencode($next) : ''));
            exit;
        }
        $_SESSION['user_role']        = $row['role'];
        $_SESSION['user_full_name']   = $row['full_name'];
        $_SESSION['user_avatar_path'] = $row['avatar_path'] ?? '';
    }

    private static function currentRelativePath(): string
    {
        $uri   = (string)($_SERVER['REQUEST_URI'] ?? '');
        $path  = (string)(parse_url($uri, PHP_URL_PATH) ?? '');
        $query = (string)(parse_url($uri, PHP_URL_QUERY) ?? '');

        $basePath = (string)(parse_url(BASE_URL, PHP_URL_PATH) ?? '');
        $basePath = rtrim(str_replace('\\', '/', $basePath), '/');
        $path     = str_replace('\\', '/', $path);

        if ($basePath !== '') {
            $pathLower = strtolower($path);
            $baseLower = strtolower($basePath);
            if ($pathLower === $baseLower) {
                $path = '';
            } elseif (str_starts_with($pathLower, $baseLower . '/')) {
                $path = substr($path, strlen($basePath) + 1);
            }
        }

        $path = ltrim($path, '/');
        if (str_starts_with(strtolower($path), 'public/')) {
            $path = substr($path, 7);
        }

        return $path . ($query !== '' ? '?' . $query : '');
    }

    public static function isLoggedIn(): bool
    {
        return !empty($_SESSION['user_id']) && !empty($_SESSION['user_logged_in']);
    }

    public static function userId(): int
    {
        return (int)($_SESSION['user_id'] ?? 0);
    }

    public static function userName(): string
    {
        return (string)($_SESSION['user_full_name'] ?? '');
    }

    public static function userRole(): string
    {
        return (string)($_SESSION['user_role'] ?? 'user');
    }

    public static function attempt(string $username, string $password): array
    {
        self::bootstrap();
        $username = trim($username);
        $db = Database::getInstance();

        if ($username === '' || $password === '') {
            return ['ok' => false, 'message' => 'Kullanıcı adı ve şifre zorunlu.'];
        }

        // Not: status filtresi burada UYGULANMAZ — hesabın pasif/kilitli olduğunu
        // ayırt edip kullanıcıya anlamlı bir mesaj verebilmek için önce buluyoruz.
        $user = $db->selectOne(
            "SELECT * FROM users WHERE (username = :u OR email = :u) LIMIT 1",
            [':u' => $username]
        );

        if (!$user) {
            if (class_exists('Audit')) {
                Audit::loginHistory('login_failed', null, $username, 'invalid_credentials');
            }
            return ['ok' => false, 'message' => 'Kullanıcı adı veya şifre hatalı.'];
        }

        if ($user['status'] === 'passive') {
            if (class_exists('Audit')) {
                Audit::loginHistory('login_failed', (int)$user['id'], $username, 'inactive_user');
            }
            return ['ok' => false, 'message' => 'Hesabınız pasif durumda. Sistem yöneticinize başvurun.'];
        }

        if ($user['status'] === 'locked') {
            if (class_exists('Audit')) {
                Audit::loginHistory('login_failed', (int)$user['id'], $username, 'account_locked');
            }
            return ['ok' => false, 'message' => 'Hesabınız kilitlendi. Sistem yöneticinize başvurun.'];
        }

        $lockedUntil = $user['locked_until'] ?? null;
        if ($lockedUntil && strtotime($lockedUntil) > time()) {
            if (class_exists('Audit')) {
                Audit::loginHistory('login_failed', (int)$user['id'], $username, 'rate_limited');
            }
            $kalan = (int)ceil((strtotime($lockedUntil) - time()) / 60);
            return ['ok' => false, 'message' => "Çok sayıda başarısız giriş nedeniyle hesabınız geçici olarak kilitlendi. Yaklaşık {$kalan} dakika sonra tekrar deneyin."];
        }

        if (!password_verify($password, $user['password_hash'])) {
            $failedCount = (int)($user['failed_login_count'] ?? 0) + 1;
            $update = ['failed_login_count' => $failedCount];
            if ($failedCount >= self::MAX_FAILED_ATTEMPTS) {
                $update['locked_until'] = date('Y-m-d H:i:s', time() + self::LOCK_MINUTES * 60);
            }
            $db->update('users', $update, ['id' => $user['id']]);

            if (class_exists('Audit')) {
                Audit::loginHistory('login_failed', (int)$user['id'], $username, 'invalid_credentials');
            }
            if ($failedCount >= self::MAX_FAILED_ATTEMPTS) {
                return ['ok' => false, 'message' => 'Çok sayıda başarısız giriş nedeniyle hesabınız ' . self::LOCK_MINUTES . ' dakika kilitlendi.'];
            }
            return ['ok' => false, 'message' => 'Kullanıcı adı veya şifre hatalı.'];
        }

        // Aktif şirket/dönem seçimi ÖNCEKİ oturuma aittir — yeni kullanıcıya
        // devredilemez. (session_regenerate_id() oturum verisini korur, sadece
        // id'yi değiştirir; bu yüzden açıkça temizlemek gerekir.) Aksi halde
        // giriş yapan kullanıcı, erişim yetkisi olmayan bir şirkete kilitlenip
        // "Bu şirkete erişim yetkiniz yok." ekranından çıkamıyordu.
        unset($_SESSION['active_company_id'], $_SESSION['active_period_id'], $_SESSION['active_tenant_user_id']);

        // Session açık → bilgileri kaydet
        $_SESSION['user_id']        = (int)$user['id'];
        $_SESSION['user_logged_in'] = true;
        $_SESSION['user_username']  = $user['username'];
        $_SESSION['user_full_name'] = $user['full_name'];
        $_SESSION['user_role']      = $user['role'];
        $_SESSION['user_email']     = $user['email'] ?? '';
        $_SESSION['user_avatar_path'] = $user['avatar_path'] ?? '';
        session_regenerate_id(true);
        if (class_exists('Csrf')) {
            Csrf::rotate();
        }

        $db->update('users', [
            'last_login_at'      => date('Y-m-d H:i:s'),
            'failed_login_count' => 0,
            'locked_until'       => null,
        ], ['id' => (int)$user['id']]);

        if (class_exists('Audit')) {
            Audit::loginHistory('login_success', (int)$user['id'], $username, null);
        }

        return ['ok' => true, 'user' => $user];
    }

    public static function logout(): void
    {
        if (self::isLoggedIn() && class_exists('Audit')) {
            Audit::loginHistory('logout', self::userId(), self::userName(), null);
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function isSuperAdmin(): bool
    {
        return self::isLoggedIn() && class_exists('Rbac') && Rbac::isSuperAdmin(self::userId());
    }

    // ─── Şema yardımcıları ────────────────────────────────

    private static function ensureUsersTable(): void
    {
        $db = Database::getInstance();
        $db->query("
            CREATE TABLE IF NOT EXISTS users (
                id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
                username      VARCHAR(80)  NOT NULL,
                email         VARCHAR(150) NULL,
                password_hash VARCHAR(255) NOT NULL,
                full_name     VARCHAR(150) NOT NULL,
                role          VARCHAR(50) NOT NULL DEFAULT 'user',
                status        ENUM('active','passive','locked') NOT NULL DEFAULT 'active',
                last_login_at DATETIME NULL,
                created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_users_username (username),
                KEY idx_users_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci
        ");
    }

    /**
     * İlk kurulumda hiç kullanıcı yoksa bir Süper Yönetici seed eder.
     *
     * GÜVENLİK: `production` ortamında ASLA sabit/bilinen bir şifre
     * (ör. eski "admin123") kullanılmaz — kriptografik olarak güçlü,
     * rastgele bir şifre üretilir ve YALNIZCA bir kez error_log()'a
     * yazılır (ekrana/response'a asla basılmaz). Development/test'te
     * (APP_ENV production değilse) eski kolay şifre korunur — yerel
     * geliştirme kolaylığı için, spec'in izin verdiği şekilde.
     *
     * Zaten bir kullanıcı (ör. mevcut canlı Süper Admin) varsa bu metot
     * HİÇBİR ŞEYE dokunmaz — mevcut hesap/şifre asla değiştirilmez.
     */
    private static function ensureDefaultAdmin(): void
    {
        $db  = Database::getInstance();
        $row = $db->selectOne("SELECT COUNT(*) AS n FROM users");
        if ((int)($row['n'] ?? 0) > 0) {
            return;
        }

        $isProduction = defined('APP_ENV') && APP_ENV === 'production';
        $password = $isProduction ? self::generateStrongPassword() : 'admin123';

        $db->insert('users', [
            'username'      => 'admin',
            'email'         => 'admin@nymagro.com',
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'full_name'     => 'Sistem Yöneticisi',
            'role'          => 'super_admin',
            'status'        => 'active',
        ]);

        if ($isProduction) {
            error_log(
                "[NYMAGRO KURULUM] İlk Süper Yönetici hesabı oluşturuldu.\n" .
                "  Kullanıcı adı : admin\n" .
                "  Geçici şifre  : {$password}\n" .
                "  Bu şifre başka HİÇBİR yerde (ekranda, veritabanında düz metin olarak, " .
                "başka bir logda) görüntülenmez. Şimdi giriş yapıp Profil > Şifre Değiştir " .
                "üzerinden değiştirin. Bu log satırını okuduktan sonra sunucu log dosyanızdan silin."
            );
        }
    }

    private static function generateStrongPassword(): string
    {
        // 24 karakter, kriptografik RNG, karışık karakter seti — kolay
        // kopyalanabilir (belirsiz karakter yok) ama tahmin edilemez.
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%^&*';
        $password = '';
        for ($i = 0; $i < 24; $i++) {
            $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $password;
    }

    private static function ensureUserProfileColumns(): void
    {
        $db = Database::getInstance();
        $columns = [
            'avatar_path' => "ALTER TABLE users ADD COLUMN avatar_path VARCHAR(255) NULL AFTER full_name",
            'phone'       => "ALTER TABLE users ADD COLUMN phone VARCHAR(50) NULL AFTER email",
            'title'       => "ALTER TABLE users ADD COLUMN title VARCHAR(120) NULL AFTER full_name",
            'bio'         => "ALTER TABLE users ADD COLUMN bio TEXT NULL AFTER title",
        ];

        foreach ($columns as $column => $sql) {
            $exists = $db->selectOne(
                "SELECT COUNT(*) AS n
                 FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'users'
                   AND COLUMN_NAME = :column",
                [':column' => $column]
            );
            if ((int)($exists['n'] ?? 0) === 0) {
                $db->query($sql);
            }
        }
    }
}
