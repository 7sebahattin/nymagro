<?php
/**
 * UserAdmin
 * --------------------------------------------------------
 * Süper Yönetici'nin "Kullanıcılar" ekranı için model.
 * NOT: Kullanıcının KENDİ profilini düzenlemesi app/models/User.php
 * üzerinden yürür (bu sınıfa dokunulmadı) — bu model sadece
 * YÖNETİCİ tarafından başka kullanıcılar üzerinde yapılan
 * işlemler içindir (oluşturma, rol/durum değişikliği, şifre
 * sıfırlama, kalıcı silme).
 */
final class UserAdmin
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /** user_companies.role ENUM'unun izin verdiği değerler (şema ile birebir). */
    public const COMPANY_ROLES = [
        'owner'      => 'Sahip (şirket + dönem yönetebilir)',
        'admin'      => 'Yönetici (şirket + dönem yönetebilir)',
        'accountant' => 'Muhasebeci (dönem yönetebilir)',
        'sales'      => 'Satış (yönetim yetkisi yok)',
        'viewer'     => 'Standart (yönetim yetkisi yok)',
    ];

    public const DEFAULT_COMPANY_ROLE = 'viewer';

    public function roles(): array
    {
        return $this->db->select("SELECT id, code, name, is_system FROM roles ORDER BY is_system DESC, name ASC");
    }

    /** Atanabilecek (silinmemiş) tüm şirketler. */
    public function assignableCompanies(): array
    {
        return $this->db->select(
            "SELECT id, company_name, short_name, status
             FROM companies WHERE deleted_at IS NULL
             ORDER BY status = 'active' DESC, company_name"
        );
    }

    /** Bir kullanıcının şirket atamaları: company_id => satır. */
    public function companyAssignments(int $userId): array
    {
        $rows = $this->db->select(
            "SELECT uc.company_id, uc.role, uc.is_default, c.company_name, c.status
             FROM user_companies uc
             JOIN companies c ON c.id = uc.company_id
             WHERE uc.user_id = :uid AND c.deleted_at IS NULL
             ORDER BY c.company_name",
            [':uid' => $userId]
        );
        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['company_id']] = $row;
        }
        return $map;
    }

    /** Birden çok kullanıcı için şirket adları: user_id => ['A', 'B']. */
    public function companyNamesByUser(array $userIds): array
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        if (!$userIds) {
            return [];
        }
        $ph = [];
        $params = [];
        foreach ($userIds as $i => $uid) {
            $ph[] = ':u' . $i;
            $params[':u' . $i] = $uid;
        }
        $rows = $this->db->select(
            "SELECT uc.user_id, c.company_name
             FROM user_companies uc
             JOIN companies c ON c.id = uc.company_id
             WHERE uc.user_id IN (" . implode(',', $ph) . ") AND c.deleted_at IS NULL
             ORDER BY c.company_name",
            $params
        );
        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['user_id']][] = (string)$row['company_name'];
        }
        return $map;
    }

    /** POST'tan gelen şirket rolünü doğrular; geçersizse güvenli varsayılana düşer. */
    public static function normalizeCompanyRole($role): string
    {
        $role = is_string($role) ? trim($role) : '';
        return isset(self::COMPANY_ROLES[$role]) ? $role : self::DEFAULT_COMPANY_ROLE;
    }

    /**
     * Gönderilen şirket id listesini, gerçekten var olan (silinmemiş)
     * şirketlerle kesiştirir — uydurma id'ler sessizce elenir.
     *
     * @return int[]
     */
    public function filterExistingCompanyIds(array $companyIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $companyIds), fn(int $id): bool => $id > 0)));
        if (!$ids) {
            return [];
        }
        $ph = [];
        $params = [];
        foreach ($ids as $i => $id) {
            $ph[] = ':c' . $i;
            $params[':c' . $i] = $id;
        }
        $rows = $this->db->select(
            "SELECT id FROM companies WHERE deleted_at IS NULL AND id IN (" . implode(',', $ph) . ")",
            $params
        );
        return array_map(fn(array $r): int => (int)$r['id'], $rows);
    }

    /**
     * Kullanıcının şirket atamalarını verilen listeye eşitler (ekle + çıkar).
     * Zaten var olan atamaların rolü de güncellenir. is_default korunur;
     * kullanıcının hiç varsayılanı kalmadıysa ilk şirket varsayılan yapılır.
     *
     * NOT: Çağıran taraf transaction açmış olabilir — burada ayrıca açılmaz.
     *
     * @param int[] $companyIds
     */
    public function syncCompanies(int $userId, array $companyIds, string $role): void
    {
        $role = self::normalizeCompanyRole($role);
        $target = $this->filterExistingCompanyIds($companyIds);
        $current = array_keys($this->companyAssignments($userId));

        $canManage = in_array($role, ['owner', 'admin'], true) ? 1 : 0;
        $canPeriod = in_array($role, ['owner', 'admin', 'accountant'], true) ? 1 : 0;

        foreach (array_diff($current, $target) as $companyId) {
            $this->db->query(
                "DELETE FROM user_companies WHERE user_id = :uid AND company_id = :cid",
                [':uid' => $userId, ':cid' => (int)$companyId]
            );
        }

        foreach ($target as $companyId) {
            if (in_array($companyId, $current, true)) {
                $this->db->query(
                    "UPDATE user_companies
                     SET role = :role, can_manage_company = :cm, can_manage_period = :cp
                     WHERE user_id = :uid AND company_id = :cid",
                    [
                        ':role' => $role, ':cm' => $canManage, ':cp' => $canPeriod,
                        ':uid' => $userId, ':cid' => $companyId,
                    ]
                );
                continue;
            }
            $this->db->insert('user_companies', [
                'user_id'            => $userId,
                'company_id'         => $companyId,
                'role'               => $role,
                'can_switch_company' => 1,
                'can_manage_company' => $canManage,
                'can_manage_period'  => $canPeriod,
                'is_default'         => 0,
            ]);
        }

        // Varsayılan şirket kalmadıysa (ör. varsayılan olan atama kaldırıldı)
        // ilk şirketi varsayılan yap — aksi halde kullanıcı girişte hiçbir
        // şirkete otomatik düşemez.
        if ($target) {
            $hasDefault = $this->db->selectOne(
                "SELECT id FROM user_companies WHERE user_id = :uid AND is_default = 1 LIMIT 1",
                [':uid' => $userId]
            );
            if (!$hasDefault) {
                $this->db->query(
                    "UPDATE user_companies SET is_default = 1 WHERE user_id = :uid AND company_id = :cid",
                    [':uid' => $userId, ':cid' => $target[0]]
                );
            }
        }
    }

    public function count(array $filters): int
    {
        [$where, $params] = $this->buildWhere($filters);
        $row = $this->db->selectOne("SELECT COUNT(*) AS n FROM users u {$where}", $params);
        return (int)($row['n'] ?? 0);
    }

    public function list(array $filters, int $limit, int $offset): array
    {
        [$where, $params] = $this->buildWhere($filters);
        $sql = "SELECT u.id, u.username, u.email, u.full_name, u.status, u.last_login_at, u.created_at,
                       u.locked_until, r.name AS role_name, r.code AS role_code
                FROM users u
                LEFT JOIN roles r ON r.id = u.role_id
                {$where}
                ORDER BY u.created_at DESC
                LIMIT {$limit} OFFSET {$offset}";
        return $this->db->select($sql, $params);
    }

    private function buildWhere(array $filters): array
    {
        $conds = [];
        $params = [];

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $conds[] = "(u.full_name LIKE :q OR u.username LIKE :q OR u.email LIKE :q)";
            $params[':q'] = '%' . $q . '%';
        }
        $roleId = (int)($filters['role_id'] ?? 0);
        if ($roleId > 0) {
            $conds[] = "u.role_id = :role_id";
            $params[':role_id'] = $roleId;
        }
        $status = trim((string)($filters['status'] ?? ''));
        if (in_array($status, ['active', 'passive', 'locked'], true)) {
            $conds[] = "u.status = :status";
            $params[':status'] = $status;
        }

        $where = $conds ? ('WHERE ' . implode(' AND ', $conds)) : '';
        return [$where, $params];
    }

    public function find(int $id): ?array
    {
        return $this->db->selectOne(
            "SELECT u.*, r.name AS role_name, r.code AS role_code
             FROM users u LEFT JOIN roles r ON r.id = u.role_id
             WHERE u.id = :id",
            [':id' => $id]
        );
    }

    public function existsUsernameOrEmail(string $username, string $email, ?int $excludeId = null): bool
    {
        $sql = "SELECT id FROM users WHERE (username = :u OR (:e <> '' AND email = :e))";
        $params = [':u' => $username, ':e' => $email];
        if ($excludeId !== null) {
            $sql .= " AND id <> :ex";
            $params[':ex'] = $excludeId;
        }
        $sql .= " LIMIT 1";
        return (bool)$this->db->selectOne($sql, $params);
    }

    /** @return array{ok:bool, message:string, id?:int} */
    public function create(array $data, int $actorId): array
    {
        $username = trim((string)($data['username'] ?? ''));
        $email    = trim((string)($data['email'] ?? ''));
        $fullName = trim((string)($data['full_name'] ?? ''));
        $password = (string)($data['password'] ?? '');
        $confirm  = (string)($data['password_confirm'] ?? '');
        $roleId   = (int)($data['role_id'] ?? 0);
        $status   = in_array($data['status'] ?? '', ['active', 'passive'], true) ? $data['status'] : 'active';
        $note     = trim((string)($data['note'] ?? ''));

        if ($username === '' || !preg_match('/^[A-Za-z0-9_.-]{3,80}$/', $username)) {
            return ['ok' => false, 'message' => 'Kullanıcı adı en az 3 karakter olmalı; harf, rakam, nokta, tire ve alt çizgi kullanılabilir.'];
        }
        if ($fullName === '') {
            return ['ok' => false, 'message' => 'Ad soyad alanı zorunlu.'];
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => 'Geçerli bir e-posta adresi yazın.'];
        }
        // Yeni kullanıcı oluşturma da aynı şifre politikasına tabidir.
        require_once MODELS_PATH . '/User.php';
        if (mb_strlen($password) < User::MIN_PASSWORD_LENGTH) {
            return ['ok' => false, 'message' => 'Şifre en az ' . User::MIN_PASSWORD_LENGTH . ' karakter olmalı.'];
        }
        if (User::yayginSifreMi($password)) {
            return ['ok' => false, 'message' => 'Bu şifre çok yaygın kullanılıyor; tahmin edilmesi kolaydır. Lütfen başka bir şifre seçin.'];
        }
        if ($password !== $confirm) {
            return ['ok' => false, 'message' => 'Şifre ve tekrar alanı eşleşmiyor.'];
        }
        $role = $roleId > 0 ? $this->db->selectOne("SELECT * FROM roles WHERE id = :id", [':id' => $roleId]) : null;
        if (!$role) {
            return ['ok' => false, 'message' => 'Geçerli bir rol seçin.'];
        }
        if ($this->existsUsernameOrEmail($username, $email)) {
            return ['ok' => false, 'message' => 'Bu kullanıcı adı veya e-posta zaten kayıtlı.'];
        }

        // Şirket ataması kullanıcının uygulamaya GİREBİLMESİ için zorunludur:
        // hiç şirketi olmayan kullanıcı giriş yapar ama "Şirket Değiştir"
        // ekranında boş liste görüp hiçbir sayfaya geçemez. Bu yüzden
        // oluşturma anında en az bir şirket seçilmiş olmalıdır.
        $companyIds = $this->filterExistingCompanyIds((array)($data['company_ids'] ?? []));
        if (!$companyIds) {
            return ['ok' => false, 'message' => 'En az bir yetkili şirket seçmelisiniz; şirketi olmayan kullanıcı sisteme giriş yapsa da hiçbir ekranı açamaz.'];
        }
        $companyRole = self::normalizeCompanyRole($data['company_role'] ?? null);

        $this->db->begin();
        try {
            $id = $this->db->insert('users', [
                'username'      => $username,
                'email'         => $email !== '' ? $email : null,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'full_name'     => $fullName,
                'role'          => $role['code'],
                'role_id'       => $role['id'],
                'status'        => $status,
                'bio'           => $note !== '' ? $note : null,
            ]);
            $this->syncCompanies($id, $companyIds, $companyRole);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            return ['ok' => false, 'message' => 'Kullanıcı oluşturulamadı: ' . $e->getMessage()];
        }

        Audit::log('USER_CREATE', 'USER', $id, null, [
            'username' => $username, 'email' => $email, 'full_name' => $fullName,
            'role' => $role['code'], 'status' => $status,
            'company_ids' => implode(',', $companyIds), 'company_role' => $companyRole,
        ], "Yeni kullanıcı oluşturuldu: {$username} (rol: {$role['name']}, " . count($companyIds) . " şirket)", true, $actorId);

        return ['ok' => true, 'message' => 'Kullanıcı oluşturuldu.', 'id' => $id];
    }

    /** @return array{ok:bool, message:string} */
    public function update(int $id, array $data, int $actorId): array
    {
        $before = $this->find($id);
        if (!$before) {
            return ['ok' => false, 'message' => 'Kullanıcı bulunamadı.'];
        }

        $username = trim((string)($data['username'] ?? ''));
        $email    = trim((string)($data['email'] ?? ''));
        $fullName = trim((string)($data['full_name'] ?? ''));
        $phone    = trim((string)($data['phone'] ?? ''));
        $title    = trim((string)($data['title'] ?? ''));
        $roleId   = (int)($data['role_id'] ?? 0);

        if ($username === '' || !preg_match('/^[A-Za-z0-9_.-]{3,80}$/', $username)) {
            return ['ok' => false, 'message' => 'Kullanıcı adı en az 3 karakter olmalı; harf, rakam, nokta, tire ve alt çizgi kullanılabilir.'];
        }
        if ($fullName === '') {
            return ['ok' => false, 'message' => 'Ad soyad alanı zorunlu.'];
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => 'Geçerli bir e-posta adresi yazın.'];
        }
        if ($this->existsUsernameOrEmail($username, $email, $id)) {
            return ['ok' => false, 'message' => 'Bu kullanıcı adı veya e-posta başka bir kullanıcıda kayıtlı.'];
        }

        $role = $roleId > 0 ? $this->db->selectOne("SELECT * FROM roles WHERE id = :id", [':id' => $roleId]) : null;
        if (!$role) {
            return ['ok' => false, 'message' => 'Geçerli bir rol seçin.'];
        }

        $roleChanging = ((int)$before['role_id'] !== (int)$role['id']);
        if ($roleChanging && (string)$before['role_code'] === 'super_admin' && Rbac::isLastActiveSuperAdmin($id)) {
            return ['ok' => false, 'message' => 'Bu işlem yapılamaz. Sistemde en az bir aktif Süper Yönetici bulunmalıdır.'];
        }
        if ($roleChanging && $id === $actorId) {
            return ['ok' => false, 'message' => 'Kendi rolünüzü değiştiremezsiniz.'];
        }

        $companyIdsBefore = array_keys($this->companyAssignments($id));
        $companyIds = $this->filterExistingCompanyIds((array)($data['company_ids'] ?? []));
        if (!$companyIds) {
            return ['ok' => false, 'message' => 'En az bir yetkili şirket seçmelisiniz; şirketi olmayan kullanıcı sisteme giriş yapsa da hiçbir ekranı açamaz.'];
        }
        // Yönetici kendi şirket erişimini kazara kaldırıp kendini kilitleyemesin.
        if ($id === $actorId) {
            $kaybedilen = array_diff($companyIdsBefore, $companyIds);
            if ($kaybedilen) {
                return ['ok' => false, 'message' => 'Kendi şirket erişiminizi kaldıramazsınız; aksi halde sisteme giremez duruma gelirsiniz.'];
            }
        }
        $companyRole = self::normalizeCompanyRole($data['company_role'] ?? null);

        $this->db->begin();
        try {
            $this->db->update('users', [
                'username'  => $username,
                'email'     => $email !== '' ? $email : null,
                'phone'     => $phone !== '' ? $phone : null,
                'full_name' => $fullName,
                'title'     => $title !== '' ? $title : null,
                'role'      => $role['code'],
                'role_id'   => $role['id'],
            ], ['id' => $id]);
            $this->syncCompanies($id, $companyIds, $companyRole);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            return ['ok' => false, 'message' => 'Güncellenemedi: ' . $e->getMessage()];
        }

        if (array_diff($companyIdsBefore, $companyIds) || array_diff($companyIds, $companyIdsBefore)) {
            Audit::log('UPDATE', 'USER', $id,
                ['company_ids' => implode(',', $companyIdsBefore)],
                ['company_ids' => implode(',', $companyIds), 'company_role' => $companyRole],
                "{$username} kullanıcısının yetkili şirketleri güncellendi.", true, $actorId);
        }

        Audit::log('UPDATE', 'USER', $id,
            ['username' => $before['username'], 'email' => $before['email'], 'full_name' => $before['full_name']],
            ['username' => $username, 'email' => $email, 'full_name' => $fullName],
            "Kullanıcı bilgileri güncellendi: {$username}", true, $actorId);

        if ($roleChanging) {
            Audit::log('ROLE_CHANGE', 'USER', $id,
                ['role' => $before['role_code'], 'role_name' => $before['role_name']],
                ['role' => $role['code'], 'role_name' => $role['name']],
                "{$before['username']} kullanıcısının rolü değiştirildi: {$before['role_name']} → {$role['name']}", true, $actorId);
        }

        return ['ok' => true, 'message' => 'Kullanıcı güncellendi.'];
    }

    /** @return array{ok:bool, message:string} */
    public function setStatus(int $id, string $status, int $actorId): array
    {
        if (!in_array($status, ['active', 'passive', 'locked'], true)) {
            return ['ok' => false, 'message' => 'Geçersiz durum.'];
        }
        $user = $this->find($id);
        if (!$user) {
            return ['ok' => false, 'message' => 'Kullanıcı bulunamadı.'];
        }
        if ($id === $actorId && $status !== 'active') {
            return ['ok' => false, 'message' => 'Kendi hesabınızı pasifleştiremez/kilitleyemezsiniz.'];
        }
        if ($status !== 'active' && $user['role_code'] === 'super_admin' && Rbac::isLastActiveSuperAdmin($id)) {
            return ['ok' => false, 'message' => 'Bu işlem yapılamaz. Sistemde en az bir aktif Süper Yönetici bulunmalıdır.'];
        }
        if ((string)$user['status'] === $status) {
            return ['ok' => true, 'message' => 'Durum zaten güncel.'];
        }

        $this->db->begin();
        try {
            $update = ['status' => $status, 'status_changed_at' => date('Y-m-d H:i:s')];
            if ($status === 'active') {
                $update['failed_login_count'] = 0;
                $update['locked_until'] = null;
            }
            $this->db->update('users', $update, ['id' => $id]);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            return ['ok' => false, 'message' => 'Durum güncellenemedi: ' . $e->getMessage()];
        }

        $action = $status === 'active' ? 'USER_ENABLE' : 'USER_DISABLE';
        $label = ['active' => 'Aktif', 'passive' => 'Pasif', 'locked' => 'Kilitli'][$status];
        Audit::log($action, 'USER', $id,
            ['status' => $user['status']], ['status' => $status],
            "{$user['username']} kullanıcısının durumu değiştirildi: {$label}", true, $actorId);

        return ['ok' => true, 'message' => 'Kullanıcı durumu güncellendi.'];
    }

    /** @return array{ok:bool, message:string} */
    public function resetPassword(int $id, string $newPassword, string $confirmPassword, int $actorId): array
    {
        $user = $this->find($id);
        if (!$user) {
            return ['ok' => false, 'message' => 'Kullanıcı bulunamadı.'];
        }
        // Şifre politikası tek yerde tanımlıdır (User::MIN_PASSWORD_LENGTH) —
        // yönetici sıfırlaması kullanıcının kendi değiştirmesinden daha gevşek olamaz.
        require_once MODELS_PATH . '/User.php';
        if (mb_strlen($newPassword) < User::MIN_PASSWORD_LENGTH) {
            return ['ok' => false, 'message' => 'Yeni şifre en az ' . User::MIN_PASSWORD_LENGTH . ' karakter olmalı.'];
        }
        if (User::yayginSifreMi($newPassword)) {
            return ['ok' => false, 'message' => 'Bu şifre çok yaygın kullanılıyor; tahmin edilmesi kolaydır. Lütfen başka bir şifre seçin.'];
        }
        if ($newPassword !== $confirmPassword) {
            return ['ok' => false, 'message' => 'Şifre ve tekrar alanı eşleşmiyor.'];
        }

        $this->db->begin();
        try {
            $this->db->update('users', [
                'password_hash'      => password_hash($newPassword, PASSWORD_DEFAULT),
                'failed_login_count' => 0,
                'locked_until'       => null,
            ], ['id' => $id]);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            return ['ok' => false, 'message' => 'Şifre sıfırlanamadı: ' . $e->getMessage()];
        }

        // Şifre değeri ASLA audit log'a yazılmaz.
        Audit::log('PASSWORD_RESET', 'USER', $id, null, null,
            "{$user['username']} kullanıcısının şifresi Süper Yönetici tarafından sıfırlandı.", true, $actorId);

        return ['ok' => true, 'message' => 'Şifre sıfırlandı.'];
    }

    /** Kalıcı silme — spec §54: sadece pasif kullanıcılarda, Süper Yönetici + ekstra onay ile. */
    public function delete(int $id, int $actorId): array
    {
        $user = $this->find($id);
        if (!$user) {
            return ['ok' => false, 'message' => 'Kullanıcı bulunamadı.'];
        }
        if ($id === $actorId) {
            return ['ok' => false, 'message' => 'Kendi hesabınızı silemezsiniz.'];
        }
        if ((string)$user['status'] !== 'passive') {
            return ['ok' => false, 'message' => 'Sadece pasif durumdaki kullanıcılar kalıcı olarak silinebilir. Önce kullanıcıyı pasifleştirin.'];
        }
        // Not: setStatus() zaten "son aktif Süper Yönetici pasifleştirilemez" kuralını
        // uyguladığı için, pasif durumdaki bir kullanıcı hiçbir zaman sistemin TEK aktif
        // Süper Yönetici'si olamaz — bu invariant burada yeniden garanti altına alınır.
        if ($user['role_code'] === 'super_admin' && Rbac::activeSuperAdminCount() < 1) {
            return ['ok' => false, 'message' => 'Bu işlem yapılamaz. Sistemde en az bir aktif Süper Yönetici bulunmalıdır.'];
        }

        $this->db->begin();
        try {
            $this->db->query("DELETE FROM users WHERE id = :id", [':id' => $id]);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            return ['ok' => false, 'message' => 'Silinemedi: ' . $e->getMessage()];
        }

        Audit::log('USER_DELETE', 'USER', $id, [
            'username' => $user['username'], 'full_name' => $user['full_name'], 'role' => $user['role_code'],
        ], null, "{$user['username']} kullanıcısı kalıcı olarak silindi.", true, $actorId);

        return ['ok' => true, 'message' => 'Kullanıcı kalıcı olarak silindi.'];
    }

    // ─── Aktivite / Giriş Geçmişi ───────────────────────────

    public function activityCount(int $userId, array $filters = []): int
    {
        [$where, $params] = $this->activityWhere($userId, $filters);
        $row = $this->db->selectOne("SELECT COUNT(*) AS n FROM audit_logs {$where}", $params);
        return (int)($row['n'] ?? 0);
    }

    public function activity(int $userId, array $filters, int $limit, int $offset): array
    {
        [$where, $params] = $this->activityWhere($userId, $filters);
        return $this->db->select(
            "SELECT * FROM audit_logs {$where} ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}",
            $params
        );
    }

    private function activityWhere(int $userId, array $filters): array
    {
        $conds = ['user_id = :uid'];
        $params = [':uid' => $userId];

        if (!empty($filters['company_id'])) {
            $conds[] = 'company_id = :company_id';
            $params[':company_id'] = (int)$filters['company_id'];
        }
        if (!empty($filters['period_id'])) {
            $conds[] = 'period_id = :period_id';
            $params[':period_id'] = (int)$filters['period_id'];
        }
        if (!empty($filters['record_id'])) {
            $conds[] = 'record_id = :record_id';
            $params[':record_id'] = trim((string)$filters['record_id']);
        }
        if (!empty($filters['module'])) {
            $conds[] = 'module = :module';
            $params[':module'] = $filters['module'];
        }
        if (!empty($filters['action'])) {
            $conds[] = 'action = :action';
            $params[':action'] = $filters['action'];
        }
        if (!empty($filters['start'])) {
            $conds[] = 'created_at >= :start';
            $params[':start'] = $filters['start'] . ' 00:00:00';
        }
        if (!empty($filters['end'])) {
            $conds[] = 'created_at <= :end';
            $params[':end'] = $filters['end'] . ' 23:59:59';
        }

        return ['WHERE ' . implode(' AND ', $conds), $params];
    }

    public function loginHistoryCount(int $userId): int
    {
        $row = $this->db->selectOne("SELECT COUNT(*) AS n FROM login_history WHERE user_id = :id", [':id' => $userId]);
        return (int)($row['n'] ?? 0);
    }

    public function loginHistory(int $userId, int $limit, int $offset): array
    {
        return $this->db->select(
            "SELECT * FROM login_history WHERE user_id = :id ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}",
            [':id' => $userId]
        );
    }
}
