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

        if ($username === '' || $password === '') {
            return ['ok' => false, 'message' => 'Kullanıcı adı ve şifre zorunlu.'];
        }

        $user = Database::getInstance()->selectOne(
            "SELECT * FROM users
             WHERE (username = :u OR email = :u)
               AND status = 'active'
             LIMIT 1",
            [':u' => $username]
        );

        if (!$user) {
            return ['ok' => false, 'message' => 'Kullanıcı adı veya şifre hatalı.'];
        }

        if (!password_verify($password, $user['password_hash'])) {
            return ['ok' => false, 'message' => 'Kullanıcı adı veya şifre hatalı.'];
        }

        // Session açık → bilgileri kaydet
        $_SESSION['user_id']        = (int)$user['id'];
        $_SESSION['user_logged_in'] = true;
        $_SESSION['user_username']  = $user['username'];
        $_SESSION['user_full_name'] = $user['full_name'];
        $_SESSION['user_role']      = $user['role'];
        $_SESSION['user_email']     = $user['email'] ?? '';
        $_SESSION['user_avatar_path'] = $user['avatar_path'] ?? '';
        session_regenerate_id(true);

        Database::getInstance()->query(
            "UPDATE users SET last_login_at = NOW() WHERE id = :id",
            [':id' => (int)$user['id']]
        );

        return ['ok' => true, 'user' => $user];
    }

    public static function logout(): void
    {
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
                role          ENUM('super_admin','admin','accountant','user') NOT NULL DEFAULT 'user',
                status        ENUM('active','passive') NOT NULL DEFAULT 'active',
                last_login_at DATETIME NULL,
                created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_users_username (username),
                KEY idx_users_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci
        ");
    }

    private static function ensureDefaultAdmin(): void
    {
        $db  = Database::getInstance();
        $row = $db->selectOne("SELECT COUNT(*) AS n FROM users");
        if ((int)($row['n'] ?? 0) > 0) {
            return;
        }

        // Varsayılan yönetici → admin / admin123
        $db->insert('users', [
            'username'      => 'admin',
            'email'         => 'admin@nuvernatrade.com',
            'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
            'full_name'     => 'Sistem Yöneticisi',
            'role'          => 'super_admin',
            'status'        => 'active',
        ]);
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
