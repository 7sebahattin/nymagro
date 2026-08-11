<?php
/**
 * RoleController
 * --------------------------------------------------------
 * Süper Yönetici > Roller. Rol oluşturma/düzenleme, modül bazlı
 * izin (VIEW/CREATE/UPDATE/DELETE) atama.
 *
 * URL şeması:
 *   GET  /roller                  → index()
 *   GET  /roller/ekle             → ekle()
 *   POST /roller/kaydet           → kaydet()
 *   GET  /roller/{id}/duzenle     → duzenle()
 *   POST /roller/{id}/guncelle    → guncelle()
 *   POST /roller/{id}/sil         → sil()
 */
require_once MODELS_PATH . '/RoleAdmin.php';

final class RoleController extends Controller
{
    private RoleAdmin $roles;

    public function __construct()
    {
        $this->roles = new RoleAdmin();
    }

    public function index(): void
    {
        $this->view('roller/index', [
            'pageTitle'   => 'Roller',
            'activeMenu'  => 'roller',
            'topbarTitle' => 'Rol Yönetimi',
            'topbarIcon'  => 'fa-solid fa-user-shield',
            'kayitlar'    => $this->roles->list(),
            'flash'       => $this->getFlash(),
        ]);
    }

    public function ekle(): void
    {
        $this->view('roller/form', [
            'pageTitle'   => 'Yeni Rol',
            'activeMenu'  => 'roller',
            'topbarTitle' => 'Yeni Rol',
            'topbarIcon'  => 'fa-solid fa-shield-halved',
            'rol'         => null,
            'katalog'     => Rbac::permissionCatalogue(),
            'flash'       => $this->getFlash(),
        ]);
    }

    public function kaydet(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('roller');
        }
        $result = $this->roles->create($_POST, AuthGuard::userId());
        $this->setFlash($result['ok'] ? 'success' : 'danger', $result['message']);
        $this->redirect($result['ok'] ? 'roller' : 'roller/ekle');
    }

    public function duzenle(int $id = 0): void
    {
        $rol = $this->roles->find($id);
        if (!$rol) {
            $this->setFlash('danger', 'Rol bulunamadı.');
            $this->redirect('roller');
        }
        $this->view('roller/form', [
            'pageTitle'   => 'Rol Düzenle',
            'activeMenu'  => 'roller',
            'topbarTitle' => 'Rol Düzenle',
            'topbarIcon'  => 'fa-solid fa-shield-halved',
            'rol'         => $rol,
            'katalog'     => Rbac::permissionCatalogue(),
            'flash'       => $this->getFlash(),
        ]);
    }

    public function guncelle(int $id = 0): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('roller');
        }
        $result = $this->roles->update($id, $_POST, AuthGuard::userId());
        $this->setFlash($result['ok'] ? 'success' : 'danger', $result['message']);
        $this->redirect($result['ok'] ? 'roller' : 'roller/' . $id . '/duzenle');
    }

    public function sil(int $id = 0): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('roller');
        }
        $result = $this->roles->delete($id, AuthGuard::userId());
        $this->setFlash($result['ok'] ? 'success' : 'danger', $result['message']);
        $this->redirect('roller');
    }
}
