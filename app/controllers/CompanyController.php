<?php

require_once MODELS_PATH . '/Company.php';

final class CompanyController extends Controller
{
    private Company $company;

    public function __construct()
    {
        $this->company = new Company();
    }

    public function index(): void
    {
        $this->view('companies/index', [
            'pageTitle' => 'Şirket Yönetimi',
            'activeMenu' => 'companies',
            'topbarTitle' => 'Şirket Yönetimi',
            'topbarIcon' => 'fa-solid fa-building',
            'companies' => $this->company->allForUser(TenantContext::userId()),
            'flash' => $this->getFlash(),
        ]);
    }

    public function switch(): void
    {
        $this->view('companies/switch', [
            'pageTitle' => 'Şirket Değiştir',
            'activeMenu' => 'companies',
            'topbarTitle' => 'Şirket Değiştir',
            'topbarIcon' => 'fa-solid fa-right-left',
            'companies' => $this->company->listForUser(TenantContext::userId()),
            'flash' => $this->getFlash(),
        ]);
    }

    public function select(int $companyId): void
    {
        if (!TenantContext::userCanAccessCompany($companyId)) {
            http_response_code(403);
            die('Bu şirkete erişim yetkiniz yok.');
        }

        $company = $this->company->find($companyId);
        if (!$company || $company['status'] !== 'active') {
            $this->setFlash('error', 'Pasif veya arşivlenmiş şirket seçilemez.');
            $this->redirect('companies/switch');
        }

        TenantContext::setActiveSelection($companyId);

        $periods = array_values(array_filter(
            $this->company->periods($companyId),
            fn(array $p): bool => in_array($p['status'], ['open', 'locked', 'closed'], true)
        ));

        $openPeriods = array_values(array_filter($periods, fn(array $p): bool => $p['status'] === 'open'));
        if (count($openPeriods) === 1) {
            TenantContext::setActiveSelection($companyId, (int)$openPeriods[0]['id']);
            $this->setFlash('success', 'Aktif şirket ve dönem değiştirildi.');
            $this->redirect('dashboard');
        }

        $this->redirect('companies/periods/' . $companyId);
    }

    public function setDefault(int $companyId): void
    {
        if (!TenantContext::userCanAccessCompany($companyId)) {
            http_response_code(403);
            die('Bu şirkete erişim yetkiniz yok.');
        }

        try {
            $this->company->setDefault(TenantContext::userId(), $companyId);
            $this->setFlash('success', 'Varsayılan şirket güncellendi. Bir sonraki girişte otomatik açılacak.');
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $referer = (string)($_SERVER['HTTP_REFERER'] ?? '');
        $this->redirect(str_contains($referer, '/companies/switch') ? 'companies/switch' : 'companies');
    }

    public function select_period(int $periodId): void
    {
        $period = $this->company->findPeriod($periodId);
        if (!$period || !TenantContext::userCanAccessCompany((int)$period['company_id'])) {
            http_response_code(403);
            die('Bu döneme erişim yetkiniz yok.');
        }

        // Pasif bir şirketin dönemi seçilerek select()'teki "pasif şirket
        // seçilemez" kuralı atlatılamamalı.
        $company = $this->company->find((int)$period['company_id']);
        if (!$company || $company['status'] !== 'active') {
            $this->setFlash('error', 'Pasif veya arşivlenmiş şirketin dönemi seçilemez.');
            $this->redirect('companies/switch');
        }

        TenantContext::setActiveSelection((int)$period['company_id'], $periodId);
        $this->setFlash('success', 'Aktif dönem değiştirildi.');
        $this->redirect('dashboard');
    }

    /**
     * Yeni şirket açma yetkisi.
     *
     * CompanyController Rbac::UNMANAGED_CONTROLLERS içinde olduğu için Router
     * bu uca izin kontrolü UYGULAMAZ; eskiden giriş yapmış HERHANGİ bir
     * kullanıcı şirket oluşturup (Company::create() oluşturana 'owner' verir)
     * kendini o şirketin sahibi yapabiliyordu. Yetki artık burada aranır.
     */
    private function assertCanCreateCompany(): void
    {
        if (AuthGuard::isSuperAdmin()) {
            return;
        }
        $activeId = TenantContext::activeCompanyId();
        if ($activeId && TenantContext::canManageCompany($activeId)) {
            return;
        }
        http_response_code(403);
        die('Yeni şirket oluşturma yetkiniz yok.');
    }

    public function create(): void
    {
        $this->assertCanCreateCompany();

        $this->view('companies/form', [
            'pageTitle' => 'Yeni Şirket',
            'activeMenu' => 'companies',
            'topbarTitle' => 'Yeni Şirket',
            'topbarIcon' => 'fa-solid fa-building-circle-plus',
            'company' => [],
            'settings' => [],
            'action' => BASE_URL . '/companies/store',
            'flash' => $this->getFlash(),
        ]);
    }

    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('companies/create');
        }
        $this->assertCanCreateCompany();

        try {
            $data = $_POST;
            $logoPath = $this->handleLogoUpload();
            if ($logoPath !== null) {
                $data['logo_path'] = $logoPath;
            }
            $id = $this->company->create($data);
            Audit::log('CREATE', 'COMPANY', $id, null, ['company_name' => $data['company_name'] ?? ''], 'Şirket oluşturuldu.');
            $this->setFlash('success', 'Şirket oluşturuldu.');
            $this->redirect('companies/periods/' . $id);
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
            $this->redirect('companies/create');
        }
    }

    public function edit(int $id): void
    {
        if (!TenantContext::canManageCompany($id)) {
            http_response_code(403);
            die('Şirket yönetim yetkiniz yok.');
        }

        $company = $this->company->find($id);
        if (!$company) {
            $this->redirect('companies');
        }

        $this->view('companies/form', [
            'pageTitle' => 'Şirket Düzenle',
            'activeMenu' => 'companies',
            'topbarTitle' => 'Şirket Düzenle',
            'topbarIcon' => 'fa-solid fa-building',
            'company' => $company,
            'settings' => $this->company->settings($id),
            'action' => BASE_URL . '/companies/update/' . $id,
            'flash' => $this->getFlash(),
        ]);
    }

    public function update(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !TenantContext::canManageCompany($id)) {
            http_response_code(403);
            die('Şirket yönetim yetkiniz yok.');
        }

        try {
            $before = $this->company->find($id);
            $data = $_POST;
            $logoPath = $this->handleLogoUpload($before['logo_path'] ?? null);
            if ($logoPath !== null) {
                $data['logo_path'] = $logoPath;
            }
            $this->company->updateCompany($id, $data);
            Audit::log('UPDATE', 'COMPANY', $id, $before, ['company_name' => $data['company_name'] ?? ''], 'Şirket bilgileri güncellendi.');
            $this->setFlash('success', 'Şirket bilgileri güncellendi.');
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }
        $this->redirect('companies/edit/' . $id);
    }

    public function delete(int $id): void
    {
        // Faz 1.5-A: arşivleme kalıcı ve önemli bir işlem — artık sadece POST + CSRF ile yapılabilir.
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('companies');
        }
        if (!TenantContext::canManageCompany($id)) {
            http_response_code(403);
            die('Şirket yönetim yetkiniz yok.');
        }

        $before = $this->company->find($id);
        if (!$before) {
            $this->setFlash('error', 'Şirket bulunamadı.');
            $this->redirect('companies');
        }

        // Sistemdeki SON aktif şirket pasife alınamaz: aktif şirketi kalmayan
        // hiçbir kullanıcı hiçbir ekranı açamaz (TenantContext yalnızca 'active'
        // şirket seçmeye izin verir) ve kimse bu durumu geri alamaz.
        if (($before['status'] ?? '') === 'active' && $this->company->activeCompanyCount() <= 1) {
            $this->setFlash('error', 'Bu, sistemdeki son aktif şirket. Pasife alınırsa hiç kimse panele giriş yapamaz. Önce başka bir şirketi aktifleştirin.');
            $this->redirect('companies');
        }

        $result = $this->company->archive($id);
        // archive() durumu HER ZAMAN 'passive' yapar (fiziksel silme yok) — sayaç
        // sadece kullanıcıya gösterilecek uyarı/başarı mesajını belirler.
        Audit::log('DELETE', 'COMPANY', $id, $before, ['status' => 'passive'], 'Şirket pasife alındı (arşivlendi).');

        // Pasife alınan şirket o an seçili şirketse oturumdaki seçimi bırakma —
        // aksi halde kullanıcı pasif bir şirkette çalışmayı sürdürürdü.
        if (TenantContext::activeCompanyId() === $id) {
            TenantContext::clearActiveSelection();
        }

        if (array_sum($result['counts']) > 0) {
            $this->setFlash('warning', 'Bu şirkete ait işlem kayıtları bulunduğu için şirket silinemez, pasife alındı.');
        } else {
            $this->setFlash('success', 'Şirket pasife alındı.');
        }
        $this->redirect('companies');
    }

    public function periods(int $companyId): void
    {
        if (!TenantContext::userCanAccessCompany($companyId)) {
            http_response_code(403);
            die('Bu şirkete erişim yetkiniz yok.');
        }

        $this->view('periods/index', [
            'pageTitle' => 'Dönem Yönetimi',
            'activeMenu' => 'companies',
            'topbarTitle' => 'Dönem Yönetimi',
            'topbarIcon' => 'fa-solid fa-calendar-days',
            'company' => $this->company->find($companyId),
            'periods' => $this->company->periods($companyId),
            'flash' => $this->getFlash(),
        ]);
    }

    public function logo(int $id): void
    {
        if (!TenantContext::userCanAccessCompany($id)) {
            http_response_code(403);
            exit;
        }

        $company = $this->company->find($id);
        $logoPath = trim((string)($company['logo_path'] ?? ''));
        if ($logoPath === '') {
            http_response_code(404);
            exit;
        }

        $uploadsBase = realpath(ROOT_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'company-logos');
        $logoFile = realpath(ROOT_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . ltrim($logoPath, '/\\'));
        if ($uploadsBase === false || $logoFile === false || !str_starts_with($logoFile, $uploadsBase)) {
            http_response_code(404);
            exit;
        }

        $mime = function_exists('mime_content_type') ? mime_content_type($logoFile) : 'application/octet-stream';
        if (!in_array($mime, ['image/png', 'image/jpeg', 'image/webp', 'image/gif'], true)) {
            http_response_code(415);
            exit;
        }

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($logoFile));
        header('Cache-Control: private, max-age=86400');
        readfile($logoFile);
        exit;
    }

    private function handleLogoUpload(?string $currentPath = null): ?string
    {
        $file = $_FILES['logo'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return $currentPath;
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Logo yüklenemedi. Hata kodu: ' . (int)$file['error']);
        }

        if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
            throw new RuntimeException('Logo dosyası 2MB boyutunu geçemez.');
        }

        $tmpPath = (string)($file['tmp_name'] ?? '');
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            throw new RuntimeException('Logo dosyası doğrulanamadı.');
        }

        $mime = null;
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = $finfo ? finfo_file($finfo, $tmpPath) : null;
            if ($finfo) {
                finfo_close($finfo);
            }
        } elseif (function_exists('mime_content_type')) {
            $mime = mime_content_type($tmpPath);
        }

        $allowed = [
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];
        if (!isset($allowed[$mime])) {
            throw new RuntimeException('Logo için sadece PNG, JPG, WEBP veya GIF yüklenebilir.');
        }

        $uploadDir = ROOT_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'company-logos';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
            throw new RuntimeException('Logo klasörü oluşturulamadı.');
        }

        $fileName = 'company-logo-' . date('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . $allowed[$mime];
        $target = $uploadDir . DIRECTORY_SEPARATOR . $fileName;
        if (!move_uploaded_file($tmpPath, $target)) {
            throw new RuntimeException('Logo dosyası kaydedilemedi.');
        }

        return 'uploads/company-logos/' . $fileName;
    }
}
