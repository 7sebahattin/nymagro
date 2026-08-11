<?php
/**
 * UserController
 * --------------------------------------------------------
 * Süper Yönetici > Kullanıcılar. Kapalı sistem: burada oluşturulmayan
 * hiç kimse giriş yapamaz (public register yok).
 *
 * URL şeması:
 *   GET  /kullanicilar                    → index()    listeleme + filtre
 *   GET  /kullanicilar/ekle               → ekle()     yeni kullanıcı formu
 *   POST /kullanicilar/kaydet             → kaydet()
 *   GET  /kullanicilar/{id}/duzenle       → duzenle()  düzenleme formu
 *   POST /kullanicilar/{id}/guncelle      → guncelle()
 *   POST /kullanicilar/{id}/durum         → durum()    aktif/pasif/kilitli
 *   GET|POST /kullanicilar/{id}/sifre-sifirla → sifre_sifirla()
 *   POST /kullanicilar/{id}/sil           → sil()      kalıcı silme (sadece pasif kullanıcı)
 *   GET  /kullanicilar/{id}/aktivite      → aktivite() kullanıcının audit geçmişi
 *   GET  /kullanicilar/{id}/giris-gecmisi → giris_gecmisi()
 */
require_once MODELS_PATH . '/UserAdmin.php';
require_once MODELS_PATH . '/Company.php';

final class UserController extends Controller
{
    private UserAdmin $users;
    private Company $company;

    public function __construct()
    {
        $this->users = new UserAdmin();
        $this->company = new Company();
    }

    public function index(): void
    {
        $filters = [
            'q'       => trim((string)($_GET['q'] ?? '')),
            'role_id' => (int)($_GET['role_id'] ?? 0),
            'status'  => trim((string)($_GET['status'] ?? '')),
        ];
        $sayfa  = max(1, (int)($_GET['sayfa'] ?? 1));
        $limit  = 25;
        $offset = ($sayfa - 1) * $limit;

        $toplam = $this->users->count($filters);

        $this->view('kullanicilar/index', [
            'pageTitle'   => 'Kullanıcılar',
            'activeMenu'  => 'kullanicilar',
            'topbarTitle' => 'Kullanıcı Yönetimi',
            'topbarIcon'  => 'fa-solid fa-users-gear',
            'kayitlar'    => $this->users->list($filters, $limit, $offset),
            'roller'      => $this->users->roles(),
            'filters'     => $filters,
            'toplam'      => $toplam,
            'sayfa'       => $sayfa,
            'sayfaSayisi' => (int)ceil($toplam / $limit),
            'flash'       => $this->getFlash(),
        ]);
    }

    public function ekle(): void
    {
        $this->view('kullanicilar/form', [
            'pageTitle'   => 'Yeni Kullanıcı',
            'activeMenu'  => 'kullanicilar',
            'topbarTitle' => 'Yeni Kullanıcı',
            'topbarIcon'  => 'fa-solid fa-user-plus',
            'kullanici'   => null,
            'roller'      => $this->users->roles(),
            'flash'       => $this->getFlash(),
        ]);
    }

    public function kaydet(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('kullanicilar');
        }
        $result = $this->users->create($_POST, AuthGuard::userId());
        $this->setFlash($result['ok'] ? 'success' : 'danger', $result['message']);
        $this->redirect($result['ok'] ? 'kullanicilar' : 'kullanicilar/ekle');
    }

    public function duzenle(int $id = 0): void
    {
        $kullanici = $this->users->find($id);
        if (!$kullanici) {
            $this->setFlash('danger', 'Kullanıcı bulunamadı.');
            $this->redirect('kullanicilar');
        }

        $this->view('kullanicilar/form', [
            'pageTitle'   => 'Kullanıcı Düzenle',
            'activeMenu'  => 'kullanicilar',
            'topbarTitle' => 'Kullanıcı Düzenle',
            'topbarIcon'  => 'fa-solid fa-user-pen',
            'kullanici'   => $kullanici,
            'roller'      => $this->users->roles(),
            'flash'       => $this->getFlash(),
        ]);
    }

    public function guncelle(int $id = 0): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('kullanicilar');
        }
        $result = $this->users->update($id, $_POST, AuthGuard::userId());
        $this->setFlash($result['ok'] ? 'success' : 'danger', $result['message']);
        $this->redirect($result['ok'] ? 'kullanicilar' : 'kullanicilar/' . $id . '/duzenle');
    }

    public function durum(int $id = 0): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('kullanicilar');
        }
        $status = trim((string)($_POST['status'] ?? ''));
        $result = $this->users->setStatus($id, $status, AuthGuard::userId());
        $this->setFlash($result['ok'] ? 'success' : 'danger', $result['message']);
        $this->redirect('kullanicilar');
    }

    public function sifre_sifirla(int $id = 0): void
    {
        $kullanici = $this->users->find($id);
        if (!$kullanici) {
            $this->setFlash('danger', 'Kullanıcı bulunamadı.');
            $this->redirect('kullanicilar');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->users->resetPassword(
                $id,
                (string)($_POST['new_password'] ?? ''),
                (string)($_POST['confirm_password'] ?? ''),
                AuthGuard::userId()
            );
            $this->setFlash($result['ok'] ? 'success' : 'danger', $result['message']);
            $this->redirect($result['ok'] ? 'kullanicilar' : 'kullanicilar/' . $id . '/sifre-sifirla');
        }

        $this->view('kullanicilar/sifre_sifirla', [
            'pageTitle'   => 'Şifre Sıfırla',
            'activeMenu'  => 'kullanicilar',
            'topbarTitle' => 'Şifre Sıfırla',
            'topbarIcon'  => 'fa-solid fa-key',
            'kullanici'   => $kullanici,
            'flash'       => $this->getFlash(),
        ]);
    }

    public function sil(int $id = 0): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('kullanicilar');
        }
        $confirmText = trim((string)($_POST['confirm_username'] ?? ''));
        $kullanici = $this->users->find($id);
        if ($kullanici && $confirmText !== $kullanici['username']) {
            $this->setFlash('danger', 'Silme onayı için kullanıcı adını doğru yazmalısınız.');
            $this->redirect('kullanicilar');
        }
        $result = $this->users->delete($id, AuthGuard::userId());
        $this->setFlash($result['ok'] ? 'success' : 'danger', $result['message']);
        $this->redirect('kullanicilar');
    }

    public function aktivite(int $id = 0): void
    {
        $kullanici = $this->users->find($id);
        if (!$kullanici) {
            $this->setFlash('danger', 'Kullanıcı bulunamadı.');
            $this->redirect('kullanicilar');
        }

        $filters = [
            'company_id' => (int)($_GET['company_id'] ?? 0),
            'period_id'  => (int)($_GET['period_id'] ?? 0),
            'record_id'  => trim((string)($_GET['record_id'] ?? '')),
            'module'     => trim((string)($_GET['module'] ?? '')),
            'action'     => trim((string)($_GET['action'] ?? '')),
            'start'      => trim((string)($_GET['start'] ?? '')),
            'end'        => trim((string)($_GET['end'] ?? '')),
        ];
        $sayfa  = max(1, (int)($_GET['sayfa'] ?? 1));
        $limit  = 30;
        $offset = ($sayfa - 1) * $limit;
        $toplam = $this->users->activityCount($id, $filters);

        $this->view('kullanicilar/aktivite', [
            'pageTitle'   => $kullanici['full_name'] . ' - Aktivite Geçmişi',
            'activeMenu'  => 'kullanicilar',
            'topbarTitle' => 'Aktivite Geçmişi',
            'topbarIcon'  => 'fa-solid fa-clock-rotate-left',
            'kullanici'   => $kullanici,
            'kayitlar'    => $this->users->activity($id, $filters, $limit, $offset),
            'filters'     => $filters,
            'toplam'      => $toplam,
            'sayfa'       => $sayfa,
            'sayfaSayisi' => (int)ceil($toplam / $limit),
            'moduleLabels' => Rbac::moduleLabels(),
            'sirketler'   => $this->company->all(),
            'donemler'    => !empty($filters['company_id']) ? $this->company->periods((int)$filters['company_id']) : [],
            'flash'       => $this->getFlash(),
        ]);
    }

    public function giris_gecmisi(int $id = 0): void
    {
        $kullanici = $this->users->find($id);
        if (!$kullanici) {
            $this->setFlash('danger', 'Kullanıcı bulunamadı.');
            $this->redirect('kullanicilar');
        }

        $sayfa  = max(1, (int)($_GET['sayfa'] ?? 1));
        $limit  = 30;
        $offset = ($sayfa - 1) * $limit;
        $toplam = $this->users->loginHistoryCount($id);

        $this->view('kullanicilar/giris_gecmisi', [
            'pageTitle'   => $kullanici['full_name'] . ' - Giriş Geçmişi',
            'activeMenu'  => 'kullanicilar',
            'topbarTitle' => 'Giriş Geçmişi',
            'topbarIcon'  => 'fa-solid fa-right-to-bracket',
            'kullanici'   => $kullanici,
            'kayitlar'    => $this->users->loginHistory($id, $limit, $offset),
            'toplam'      => $toplam,
            'sayfa'       => $sayfa,
            'sayfaSayisi' => (int)ceil($toplam / $limit),
            'flash'       => $this->getFlash(),
        ]);
    }
}
