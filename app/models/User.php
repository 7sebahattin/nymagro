<?php

final class User
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function find(int $id): ?array
    {
        return $this->db->selectOne(
            "SELECT id, username, email, phone, full_name, title, bio, avatar_path,
                    role, status, last_login_at, created_at
             FROM users
             WHERE id = :id
             LIMIT 1",
            [':id' => $id]
        );
    }

    public function updateProfile(int $id, array $data): array
    {
        $username = trim((string)($data['username'] ?? ''));
        $email    = trim((string)($data['email'] ?? ''));
        $fullName = trim((string)($data['full_name'] ?? ''));
        $phone    = trim((string)($data['phone'] ?? ''));
        $title    = trim((string)($data['title'] ?? ''));
        $bio      = trim((string)($data['bio'] ?? ''));

        if ($username === '' || !preg_match('/^[A-Za-z0-9_.-]{3,80}$/', $username)) {
            return ['ok' => false, 'message' => 'Kullanıcı adı en az 3 karakter olmalı; harf, rakam, nokta, tire ve alt çizgi kullanılabilir.'];
        }
        if ($fullName === '') {
            return ['ok' => false, 'message' => 'Ad soyad alanı zorunlu.'];
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => 'Geçerli bir e-posta adresi yazın.'];
        }

        $exists = $this->db->selectOne(
            "SELECT id FROM users
             WHERE id <> :id
               AND (username = :username OR (:email <> '' AND email = :email))
             LIMIT 1",
            [':id' => $id, ':username' => $username, ':email' => $email]
        );
        if ($exists) {
            return ['ok' => false, 'message' => 'Bu kullanıcı adı veya e-posta başka bir kullanıcıda kayıtlı.'];
        }

        $this->db->update('users', [
            'username' => $username,
            'email' => $email !== '' ? $email : null,
            'phone' => $phone !== '' ? $phone : null,
            'full_name' => $fullName,
            'title' => $title !== '' ? $title : null,
            'bio' => $bio !== '' ? $bio : null,
        ], ['id' => $id]);

        return ['ok' => true, 'message' => 'Hesap bilgileriniz güncellendi.'];
    }

    public function updateAvatar(int $id, string $avatarPath): void
    {
        $this->db->update('users', ['avatar_path' => $avatarPath], ['id' => $id]);
    }

    public function removeAvatar(int $id): void
    {
        $this->db->update('users', ['avatar_path' => null], ['id' => $id]);
    }

    public function changePassword(int $id, string $currentPassword, string $newPassword, string $confirmPassword): array
    {
        $currentPassword = (string)$currentPassword;
        $newPassword = (string)$newPassword;
        $confirmPassword = (string)$confirmPassword;

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            return ['ok' => false, 'message' => 'Tüm şifre alanları zorunlu.'];
        }
        if (strlen($newPassword) < 6) {
            return ['ok' => false, 'message' => 'Yeni şifre en az 6 karakter olmalı.'];
        }
        if ($newPassword !== $confirmPassword) {
            return ['ok' => false, 'message' => 'Yeni şifre ve tekrar alanı eşleşmiyor.'];
        }

        $user = $this->db->selectOne(
            "SELECT password_hash FROM users WHERE id = :id LIMIT 1",
            [':id' => $id]
        );
        if (!$user || !password_verify($currentPassword, (string)$user['password_hash'])) {
            return ['ok' => false, 'message' => 'Mevcut şifreniz hatalı.'];
        }

        $this->db->update('users', [
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
        ], ['id' => $id]);

        return ['ok' => true, 'message' => 'Şifreniz güncellendi.'];
    }
}
