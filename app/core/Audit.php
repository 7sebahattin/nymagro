<?php
/**
 * Audit
 * --------------------------------------------------------
 * Gizli/append-only audit log servisi. Şema Rbac::ensureSchema()
 * içinde oluşturulur (audit_logs, login_history) — burada sadece
 * yazma/okuma API'si var.
 *
 * ÖNEMLİ:
 *  - Şifre/hash/token gibi hassas alanlar ASLA yazılmaz (maskeSensitive()).
 *  - Bu sınıf sadece INSERT yapar; audit_logs/login_history için
 *    UPDATE/DELETE metodu YOKTUR (kasıtlı — "değiştirilemez log").
 */
final class Audit
{
    private const SENSITIVE_KEYS = [
        'password', 'password_hash', 'sifre', 'confirm_password', 'current_password',
        'new_password', 'token', 'api_key', 'secret', 'session_id', 'csrf_token',
    ];

    /**
     * @param string     $action      CREATE|UPDATE|DELETE|LOGIN|LOGOUT|FAILED_LOGIN|
     *                                PASSWORD_RESET|ROLE_CHANGE|PERMISSION_CHANGE|
     *                                USER_ENABLE|USER_DISABLE|USER_CREATE|SETTINGS_CHANGE|
     *                                BULK_OPERATION|UNAUTHORIZED_ACCESS_ATTEMPT
     * @param string|null $module     Modül kodu (MUSTERI, USER, ROLE, ...)
     * @param int|string|null $recordId
     * @param array|null $before
     * @param array|null $after
     */
    public static function log(
        string $action,
        ?string $module = null,
        int|string|null $recordId = null,
        ?array $before = null,
        ?array $after = null,
        ?string $description = null,
        bool $success = true,
        ?int $userIdOverride = null
    ): void {
        $db = Database::getInstance();

        $userId = $userIdOverride ?? (class_exists('AuthGuard') && AuthGuard::isLoggedIn() ? AuthGuard::userId() : null);
        $userSnapshot = null;
        if ($userId) {
            $userSnapshot = (class_exists('AuthGuard') ? AuthGuard::userName() : null);
            if (!$userSnapshot) {
                $row = $db->selectOne("SELECT full_name, username FROM users WHERE id = :id", [':id' => $userId]);
                $userSnapshot = $row ? ($row['full_name'] . ' (' . $row['username'] . ')') : null;
            }
        }

        $companyId = (class_exists('TenantContext') && TenantContext::activeCompanyId()) ? TenantContext::activeCompanyId() : null;

        try {
            $db->insert('audit_logs', [
                'user_id'       => $userId,
                'user_snapshot' => $userSnapshot,
                'action'        => $action,
                'module'        => $module,
                'record_id'     => $recordId !== null ? (string)$recordId : null,
                'company_id'    => $companyId,
                'before_json'   => $before !== null ? json_encode(self::maskSensitive($before), JSON_UNESCAPED_UNICODE) : null,
                'after_json'    => $after !== null ? json_encode(self::maskSensitive($after), JSON_UNESCAPED_UNICODE) : null,
                'description'   => $description !== null ? mb_substr($description, 0, 500) : null,
                'success'       => $success ? 1 : 0,
                'ip_address'    => self::clientIp(),
                'user_agent'    => mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                'request_id'    => self::requestId(),
            ]);
        } catch (Throwable $e) {
            // Audit yazımı iş işlemini asla düşürmemeli; sadece sunucu logiuna yaz.
            error_log('Audit log yazılamadı: ' . $e->getMessage());
        }
    }

    /**
     * Login/logout/başarısız giriş kayıtları — audit_logs'tan ayrı tutulur
     * (spec: "Oturum/Giriş Takibi" ayrı bir tablo/ekran).
     */
    public static function loginHistory(
        string $event, // login_success | login_failed | logout
        ?int $userId,
        ?string $usernameAttempted,
        ?string $failReason = null
    ): void {
        $db = Database::getInstance();
        try {
            $db->insert('login_history', [
                'user_id'            => $userId,
                'username_attempted' => $usernameAttempted !== null ? mb_substr($usernameAttempted, 0, 150) : null,
                'event'              => $event,
                'fail_reason'        => $failReason,
                'ip_address'         => self::clientIp(),
                'user_agent'         => mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                'session_ref'        => session_id() ? hash('sha256', session_id()) : null,
            ]);
        } catch (Throwable $e) {
            error_log('Login history yazılamadı: ' . $e->getMessage());
        }
    }

    /** Sadece değişen alanları döndürür: alan => ['old' => ..., 'new' => ...] */
    public static function diff(?array $before, ?array $after): array
    {
        $before ??= [];
        $after ??= [];
        $out = [];
        $keys = array_unique(array_merge(array_keys($before), array_keys($after)));
        foreach ($keys as $key) {
            $old = $before[$key] ?? null;
            $new = $after[$key] ?? null;
            if ($old != $new) {
                $out[$key] = ['old' => $old, 'new' => $new];
            }
        }
        return $out;
    }

    public static function maskSensitive(array $data): array
    {
        foreach ($data as $key => $value) {
            $lower = strtolower((string)$key);
            foreach (self::SENSITIVE_KEYS as $sensitive) {
                if (str_contains($lower, $sensitive)) {
                    $data[$key] = '***';
                    break;
                }
            }
        }
        return $data;
    }

    private static function clientIp(): ?string
    {
        $candidates = ['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        foreach ($candidates as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim(explode(',', (string)$_SERVER[$key])[0]);
                if ($ip !== '') return mb_substr($ip, 0, 64);
            }
        }
        return null;
    }

    private static function requestId(): string
    {
        static $id = null;
        if ($id === null) {
            $id = bin2hex(random_bytes(8));
        }
        return $id;
    }
}
