<?php

require_once MODELS_PATH . '/User.php';

final class ProfilController extends Controller
{
    private User $users;

    public function __construct()
    {
        $this->users = new User();
    }

    public function index(): void
    {
        $user = $this->currentUser();
        $this->view('profil/index', [
            'pageTitle' => 'Hesabım',
            'activeMenu' => '',
            'topbarTitle' => 'Hesabım',
            'topbarIcon' => 'fa-solid fa-circle-user',
            'user' => $user,
            'activeCompany' => class_exists('TenantContext') ? TenantContext::activeCompany() : null,
            'activePeriod' => class_exists('TenantContext') ? TenantContext::activePeriod() : null,
            'flash' => $this->getFlash(),
        ]);
    }

    public function guncelle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('profil');
        }

        $userId = AuthGuard::userId();
        $avatarResult = $this->handleAvatarUpload($userId);
        if (!$avatarResult['ok']) {
            $this->setFlash('danger', $avatarResult['message']);
            $this->redirect('profil');
        }

        $result = $this->users->updateProfile($userId, $_POST);
        if ($result['ok']) {
            $this->syncSession($this->users->find($userId));
        }

        $this->setFlash($result['ok'] ? 'success' : 'danger', $result['message']);
        $this->redirect('profil');
    }

    public function avatar_sil(): void
    {
        $this->users->removeAvatar(AuthGuard::userId());
        $_SESSION['user_avatar_path'] = '';
        $this->setFlash('success', 'Profil fotoğrafınız kaldırıldı.');
        $this->redirect('profil');
    }

    public function sifre_degistir(): void
    {
        $this->view('profil/sifre', [
            'pageTitle' => 'Şifre Değiştir',
            'activeMenu' => '',
            'topbarTitle' => 'Şifre Değiştir',
            'topbarIcon' => 'fa-solid fa-lock',
            'user' => $this->currentUser(),
            'flash' => $this->getFlash(),
        ]);
    }

    public function sifre_guncelle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('profil/sifre-degistir');
        }

        $result = $this->users->changePassword(
            AuthGuard::userId(),
            $_POST['current_password'] ?? '',
            $_POST['new_password'] ?? '',
            $_POST['confirm_password'] ?? ''
        );

        $this->setFlash($result['ok'] ? 'success' : 'danger', $result['message']);
        $this->redirect($result['ok'] ? 'profil' : 'profil/sifre-degistir');
    }

    private function currentUser(): array
    {
        $user = $this->users->find(AuthGuard::userId());
        if (!$user) {
            AuthGuard::logout();
            $this->redirect('giris');
        }
        return $user;
    }

    private function handleAvatarUpload(int $userId): array
    {
        if (empty($_FILES['avatar']['name'])) {
            return ['ok' => true];
        }

        $file = $_FILES['avatar'];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'message' => 'Profil fotoğrafı yüklenemedi. Lütfen tekrar deneyin.'];
        }
        if (($file['size'] ?? 0) > 3 * 1024 * 1024) {
            return ['ok' => false, 'message' => 'Profil fotoğrafı en fazla 3 MB olabilir.'];
        }

        $tmp = (string)$file['tmp_name'];
        $info = @getimagesize($tmp);
        if ($info === false) {
            return ['ok' => false, 'message' => 'Yüklenen dosya geçerli bir görsel değil.'];
        }

        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];
        $mime = (string)($info['mime'] ?? '');
        if (!isset($allowed[$mime])) {
            return ['ok' => false, 'message' => 'Profil fotoğrafı JPG, PNG, WEBP veya GIF olmalı.'];
        }

        $dir = ROOT_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'users';
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return ['ok' => false, 'message' => 'Profil fotoğrafı klasörü oluşturulamadı.'];
        }

        $name = 'user_' . $userId . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
        $dest = $dir . DIRECTORY_SEPARATOR . $name;
        if (!move_uploaded_file($tmp, $dest)) {
            return ['ok' => false, 'message' => 'Profil fotoğrafı kaydedilemedi.'];
        }

        $avatarPath = BASE_URL . '/uploads/users/' . $name;
        $this->users->updateAvatar($userId, $avatarPath);
        $_SESSION['user_avatar_path'] = $avatarPath;
        return ['ok' => true];
    }

    private function syncSession(?array $user): void
    {
        if (!$user) {
            return;
        }
        $_SESSION['user_username'] = $user['username'] ?? '';
        $_SESSION['user_full_name'] = $user['full_name'] ?? '';
        $_SESSION['user_email'] = $user['email'] ?? '';
        $_SESSION['user_role'] = $user['role'] ?? '';
        $_SESSION['user_avatar_path'] = $user['avatar_path'] ?? '';
    }
}
