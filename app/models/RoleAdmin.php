<?php
/**
 * RoleAdmin
 * --------------------------------------------------------
 * Rol ve izin yönetimi (Süper Yönetici > Roller).
 */
final class RoleAdmin
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function list(): array
    {
        return $this->db->select(
            "SELECT r.*, (SELECT COUNT(*) FROM users u WHERE u.role_id = r.id) AS user_count
             FROM roles r ORDER BY r.is_system DESC, r.name ASC"
        );
    }

    public function find(int $id): ?array
    {
        $role = $this->db->selectOne("SELECT * FROM roles WHERE id = :id", [':id' => $id]);
        if (!$role) return null;
        $rows = $this->db->select(
            "SELECT p.id, p.code FROM role_permissions rp JOIN permissions p ON p.id = rp.permission_id WHERE rp.role_id = :id",
            [':id' => $id]
        );
        $role['permission_ids'] = array_map('intval', array_column($rows, 'id'));
        $role['permission_codes'] = array_column($rows, 'code');
        return $role;
    }

    public function userCount(int $roleId): int
    {
        $row = $this->db->selectOne("SELECT COUNT(*) AS n FROM users WHERE role_id = :id", [':id' => $roleId]);
        return (int)($row['n'] ?? 0);
    }

    /** @return array{ok:bool, message:string, id?:int} */
    public function create(array $data, int $actorId): array
    {
        $name = trim((string)($data['name'] ?? ''));
        $description = trim((string)($data['description'] ?? ''));
        $permissionCodes = $this->resolvePermissionCodes($data['permissions'] ?? []);

        if ($name === '' || mb_strlen($name) < 3) {
            return ['ok' => false, 'message' => 'Rol adı en az 3 karakter olmalı.'];
        }
        $code = $this->slugify($name);
        if ($this->db->selectOne("SELECT id FROM roles WHERE code = :c", [':c' => $code])) {
            return ['ok' => false, 'message' => 'Bu isimde (veya çok benzer) bir rol zaten var.'];
        }

        $this->db->begin();
        try {
            $id = $this->db->insert('roles', [
                'code' => $code, 'name' => $name, 'description' => $description !== '' ? $description : null,
                'is_system' => 0,
            ]);
            $this->syncPermissions($id, $permissionCodes);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            return ['ok' => false, 'message' => 'Rol oluşturulamadı: ' . $e->getMessage()];
        }

        Audit::log('CREATE', 'ROLE', $id, null,
            ['name' => $name, 'permissions' => $permissionCodes],
            "Yeni rol oluşturuldu: {$name}", true, $actorId);

        return ['ok' => true, 'message' => 'Rol oluşturuldu.', 'id' => $id];
    }

    /** @return array{ok:bool, message:string} */
    public function update(int $id, array $data, int $actorId): array
    {
        $before = $this->find($id);
        if (!$before) {
            return ['ok' => false, 'message' => 'Rol bulunamadı.'];
        }
        if ((int)$before['is_system'] === 1) {
            return ['ok' => false, 'message' => 'Bu sistem rolü (Süper Yönetici) değiştirilemez.'];
        }

        $name = trim((string)($data['name'] ?? ''));
        $description = trim((string)($data['description'] ?? ''));
        $permissionCodes = $this->resolvePermissionCodes($data['permissions'] ?? []);

        if ($name === '' || mb_strlen($name) < 3) {
            return ['ok' => false, 'message' => 'Rol adı en az 3 karakter olmalı.'];
        }

        $beforeCodes = $this->db->select(
            "SELECT p.code FROM role_permissions rp JOIN permissions p ON p.id = rp.permission_id WHERE rp.role_id = :id",
            [':id' => $id]
        );
        $beforeCodes = array_column($beforeCodes, 'code');

        $this->db->begin();
        try {
            $this->db->update('roles', [
                'name' => $name, 'description' => $description !== '' ? $description : null,
            ], ['id' => $id]);
            $this->syncPermissions($id, $permissionCodes);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            return ['ok' => false, 'message' => 'Rol güncellenemedi: ' . $e->getMessage()];
        }

        Audit::log('PERMISSION_CHANGE', 'ROLE', $id,
            ['name' => $before['name'], 'permissions' => $beforeCodes],
            ['name' => $name, 'permissions' => $permissionCodes],
            "Rol güncellendi: {$name}", true, $actorId);

        return ['ok' => true, 'message' => 'Rol güncellendi.'];
    }

    /** @return array{ok:bool, message:string} */
    public function delete(int $id, int $actorId): array
    {
        $role = $this->find($id);
        if (!$role) {
            return ['ok' => false, 'message' => 'Rol bulunamadı.'];
        }
        if ((int)$role['is_system'] === 1) {
            return ['ok' => false, 'message' => 'Bu sistem rolü silinemez.'];
        }
        $userCount = $this->userCount($id);
        if ($userCount > 0) {
            return ['ok' => false, 'message' => "Bu rolde {$userCount} kullanıcı var. Önce kullanıcıları başka bir role aktarın."];
        }

        $this->db->begin();
        try {
            $this->db->query("DELETE FROM roles WHERE id = :id", [':id' => $id]);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            return ['ok' => false, 'message' => 'Rol silinemedi: ' . $e->getMessage()];
        }

        Audit::log('DELETE', 'ROLE', $id, ['name' => $role['name']], null,
            "Rol silindi: {$role['name']}", true, $actorId);

        return ['ok' => true, 'message' => 'Rol silindi.'];
    }

    private function syncPermissions(int $roleId, array $permissionCodes): void
    {
        $this->db->query("DELETE FROM role_permissions WHERE role_id = :id", [':id' => $roleId]);
        if (empty($permissionCodes)) return;

        $rows = $this->db->select("SELECT id, code FROM permissions");
        $map = [];
        foreach ($rows as $r) $map[$r['code']] = (int)$r['id'];

        foreach ($permissionCodes as $code) {
            if (!isset($map[$code])) continue;
            $this->db->query(
                "INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (:r, :p)",
                [':r' => $roleId, ':p' => $map[$code]]
            );
        }
    }

    /** POST'tan gelen ['MUSTERI_VIEW' => '1', ...] veya ['MUSTERI_VIEW', ...] biçimlerini normalize eder; sadece kataloğunda olan kodlar kabul edilir. */
    private function resolvePermissionCodes($raw): array
    {
        if (!is_array($raw)) return [];
        $valid = array_column(Rbac::permissionCatalogue(), 'code');
        $codes = array_is_list($raw) ? $raw : array_keys(array_filter($raw));
        return array_values(array_intersect($codes, $valid));
    }

    private function slugify(string $name): string
    {
        $map = ['ç' => 'c', 'Ç' => 'c', 'ğ' => 'g', 'Ğ' => 'g', 'ı' => 'i', 'İ' => 'i', 'ö' => 'o', 'Ö' => 'o', 'ş' => 's', 'Ş' => 's', 'ü' => 'u', 'Ü' => 'u'];
        $slug = strtr($name, $map);
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $slug));
        $slug = trim($slug, '_');
        return $slug !== '' ? $slug : ('rol_' . bin2hex(random_bytes(3)));
    }
}
