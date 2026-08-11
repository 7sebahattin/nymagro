<?php
/**
 * AuditAdmin
 * --------------------------------------------------------
 * Süper Yönetici > Audit Log / Giriş Geçmişi ekranları için
 * SADECE OKUMA amaçlı sorgular. audit_logs / login_history
 * tablolarına yazma bu sınıf üzerinden YAPILMAZ (bkz. Audit.php) —
 * append-only ilkesi burada da korunur.
 */
final class AuditAdmin
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function count(array $filters): int
    {
        [$where, $params] = $this->buildWhere($filters);
        $row = $this->db->selectOne("SELECT COUNT(*) AS n FROM audit_logs {$where}", $params);
        return (int)($row['n'] ?? 0);
    }

    public function list(array $filters, int $limit, int $offset): array
    {
        [$where, $params] = $this->buildWhere($filters);
        return $this->db->select(
            "SELECT * FROM audit_logs {$where} ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}",
            $params
        );
    }

    private function buildWhere(array $filters): array
    {
        $conds = [];
        $params = [];

        if (!empty($filters['user_id'])) {
            $conds[] = 'user_id = :uid';
            $params[':uid'] = (int)$filters['user_id'];
        }
        if (!empty($filters['module'])) {
            $conds[] = 'module = :module';
            $params[':module'] = $filters['module'];
        }
        if (!empty($filters['action'])) {
            $conds[] = 'action = :action';
            $params[':action'] = $filters['action'];
        }
        if (($filters['success'] ?? '') !== '') {
            $conds[] = 'success = :success';
            $params[':success'] = (int)$filters['success'];
        }
        if (!empty($filters['ip'])) {
            $conds[] = 'ip_address = :ip';
            $params[':ip'] = $filters['ip'];
        }
        if (!empty($filters['q'])) {
            $conds[] = '(description LIKE :q OR user_snapshot LIKE :q OR record_id LIKE :q)';
            $params[':q'] = '%' . $filters['q'] . '%';
        }
        if (!empty($filters['start'])) {
            $conds[] = 'created_at >= :start';
            $params[':start'] = $filters['start'] . ' 00:00:00';
        }
        if (!empty($filters['end'])) {
            $conds[] = 'created_at <= :end';
            $params[':end'] = $filters['end'] . ' 23:59:59';
        }

        return [$conds ? ('WHERE ' . implode(' AND ', $conds)) : '', $params];
    }

    public function distinctModules(): array
    {
        $rows = $this->db->select("SELECT DISTINCT module FROM audit_logs WHERE module IS NOT NULL ORDER BY module");
        return array_column($rows, 'module');
    }

    public function distinctActions(): array
    {
        $rows = $this->db->select("SELECT DISTINCT action FROM audit_logs ORDER BY action");
        return array_column($rows, 'action');
    }

    // ─── Giriş Geçmişi (tüm kullanıcılar) ───────────────────

    public function loginHistoryCount(array $filters): int
    {
        [$where, $params] = $this->loginHistoryWhere($filters);
        $row = $this->db->selectOne("SELECT COUNT(*) AS n FROM login_history {$where}", $params);
        return (int)($row['n'] ?? 0);
    }

    public function loginHistoryList(array $filters, int $limit, int $offset): array
    {
        [$where, $params] = $this->loginHistoryWhere($filters);
        return $this->db->select(
            "SELECT lh.*, u.full_name FROM login_history lh
             LEFT JOIN users u ON u.id = lh.user_id
             {$where}
             ORDER BY lh.created_at DESC LIMIT {$limit} OFFSET {$offset}",
            $params
        );
    }

    private function loginHistoryWhere(array $filters): array
    {
        $conds = [];
        $params = [];
        if (!empty($filters['event'])) {
            $conds[] = 'lh.event = :event';
            $params[':event'] = $filters['event'];
        }
        if (!empty($filters['q'])) {
            $conds[] = '(lh.username_attempted LIKE :q OR lh.ip_address LIKE :q)';
            $params[':q'] = '%' . $filters['q'] . '%';
        }
        if (!empty($filters['start'])) {
            $conds[] = 'lh.created_at >= :start';
            $params[':start'] = $filters['start'] . ' 00:00:00';
        }
        if (!empty($filters['end'])) {
            $conds[] = 'lh.created_at <= :end';
            $params[':end'] = $filters['end'] . ' 23:59:59';
        }
        return [$conds ? ('WHERE ' . implode(' AND ', $conds)) : '', $params];
    }

    // ─── Dashboard özet sayıları (§19/§70) ──────────────────

    public function securitySummary(): array
    {
        $today = date('Y-m-d');
        return [
            'users_total'    => (int)($this->db->selectOne("SELECT COUNT(*) AS n FROM users")['n'] ?? 0),
            'users_active'   => (int)($this->db->selectOne("SELECT COUNT(*) AS n FROM users WHERE status='active'")['n'] ?? 0),
            'users_passive'  => (int)($this->db->selectOne("SELECT COUNT(*) AS n FROM users WHERE status='passive'")['n'] ?? 0),
            'users_locked'   => (int)($this->db->selectOne("SELECT COUNT(*) AS n FROM users WHERE status='locked' OR (locked_until IS NOT NULL AND locked_until > NOW())")['n'] ?? 0),
            'actions_today'  => (int)($this->db->selectOne("SELECT COUNT(*) AS n FROM audit_logs WHERE created_at >= :d", [':d' => $today . ' 00:00:00'])['n'] ?? 0),
            'actions_24h'    => (int)($this->db->selectOne("SELECT COUNT(*) AS n FROM audit_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")['n'] ?? 0),
            'actions_7d'     => (int)($this->db->selectOne("SELECT COUNT(*) AS n FROM audit_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['n'] ?? 0),
            'failed_logins_24h' => (int)($this->db->selectOne("SELECT COUNT(*) AS n FROM login_history WHERE event='login_failed' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")['n'] ?? 0),
            'unauthorized_24h'  => (int)($this->db->selectOne("SELECT COUNT(*) AS n FROM audit_logs WHERE action='UNAUTHORIZED_ACCESS_ATTEMPT' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")['n'] ?? 0),
        ];
    }

    public function recentCriticalActions(int $limit = 10): array
    {
        $criticalActions = ['ROLE_CHANGE', 'PERMISSION_CHANGE', 'USER_DISABLE', 'USER_ENABLE', 'USER_DELETE',
            'USER_CREATE', 'PASSWORD_RESET', 'DELETE', 'BULK_OPERATION', 'SETTINGS_CHANGE'];
        $placeholders = implode(',', array_fill(0, count($criticalActions), '?'));
        $stmt = $this->db->pdo()->prepare(
            "SELECT * FROM audit_logs WHERE action IN ({$placeholders}) ORDER BY created_at DESC LIMIT " . (int)$limit
        );
        $stmt->execute($criticalActions);
        return $stmt->fetchAll();
    }

    public function recentActiveUsers(int $limit = 8): array
    {
        return $this->db->select(
            "SELECT user_id, user_snapshot, MAX(created_at) AS son_islem, COUNT(*) AS islem_sayisi
             FROM audit_logs
             WHERE user_id IS NOT NULL AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             GROUP BY user_id, user_snapshot
             ORDER BY son_islem DESC
             LIMIT {$limit}"
        );
    }
}
