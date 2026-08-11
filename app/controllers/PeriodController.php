<?php

require_once MODELS_PATH . '/Company.php';

final class PeriodController extends Controller
{
    private Company $company;

    public function __construct()
    {
        $this->company = new Company();
    }

    public function create(): void
    {
        $companyId = (int)($_GET['company_id'] ?? TenantContext::activeCompanyId());
        if (!$companyId || !TenantContext::canManagePeriod($companyId)) {
            http_response_code(403);
            die('Dönem oluşturma yetkiniz yok.');
        }

        $year = (int)date('Y');
        $this->view('periods/form', [
            'pageTitle' => 'Yeni Dönem',
            'activeMenu' => 'companies',
            'topbarTitle' => 'Yeni Dönem',
            'topbarIcon' => 'fa-solid fa-calendar-plus',
            'company' => $this->company->find($companyId),
            'period' => [
                'company_id' => $companyId,
                'period_name' => (string)$year,
                'fiscal_year' => $year,
                'start_date' => "{$year}-01-01",
                'end_date' => "{$year}-12-31",
                'status' => 'open',
                'is_active' => 1,
            ],
            'action' => BASE_URL . '/periods/store',
            'flash' => $this->getFlash(),
        ]);
    }

    public function store(): void
    {
        $companyId = (int)($_POST['company_id'] ?? 0);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$companyId || !TenantContext::canManagePeriod($companyId)) {
            http_response_code(403);
            die('Dönem oluşturma yetkiniz yok.');
        }

        try {
            $newId = $this->company->createPeriod($companyId, $_POST);
            Audit::log('CREATE', 'PERIOD', $newId, null, ['period_name' => $_POST['period_name'] ?? ''], 'Dönem oluşturuldu.');
            $this->setFlash('success', 'Dönem oluşturuldu.');
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }
        $this->redirect('companies/periods/' . $companyId);
    }

    public function edit(int $id): void
    {
        $period = $this->company->findPeriod($id);
        if (!$period || !TenantContext::canManagePeriod((int)$period['company_id'])) {
            http_response_code(403);
            die('Dönem yönetim yetkiniz yok.');
        }

        $this->view('periods/form', [
            'pageTitle' => 'Dönem Düzenle',
            'activeMenu' => 'companies',
            'topbarTitle' => 'Dönem Düzenle',
            'topbarIcon' => 'fa-solid fa-calendar-days',
            'company' => $this->company->find((int)$period['company_id']),
            'period' => $period,
            'action' => BASE_URL . '/periods/update/' . $id,
            'flash' => $this->getFlash(),
        ]);
    }

    public function update(int $id): void
    {
        $period = $this->company->findPeriod($id);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$period || !TenantContext::canManagePeriod((int)$period['company_id'])) {
            http_response_code(403);
            die('Dönem yönetim yetkiniz yok.');
        }

        try {
            $this->company->updatePeriod($id, $_POST);
            Audit::log('UPDATE', 'PERIOD', $id, $period, ['period_name' => $_POST['period_name'] ?? ''], 'Dönem güncellendi.');
            $this->setFlash('success', 'Dönem güncellendi.');
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }
        $this->redirect('companies/periods/' . (int)$period['company_id']);
    }

    public function close(int $id): void
    {
        $this->setStatus($id, 'closed', 'Dönem kapatıldı.');
    }

    public function lock(int $id): void
    {
        $this->setStatus($id, 'locked', 'Dönem kilitlendi.');
    }

    public function reopen(int $id): void
    {
        $this->setStatus($id, 'open', 'Dönem yeniden açıldı.');
    }

    public function archive(int $id): void
    {
        $this->setStatus($id, 'archived', 'Dönem arşivlendi.');
    }

    public function close_summary(int $id): void
    {
        $period = $this->company->findPeriod($id);
        if (!$period || !TenantContext::canManagePeriod((int)$period['company_id'])) {
            http_response_code(403);
            die('Dönem yönetim yetkiniz yok.');
        }

        $this->view('periods/close_summary', [
            'pageTitle' => 'Dönem Kapama Özeti',
            'activeMenu' => 'companies',
            'topbarTitle' => 'Dönem Kapama Özeti',
            'topbarIcon' => 'fa-solid fa-lock',
            'period' => $period,
            'summary' => $this->company->closeSummary($id),
            'flash' => $this->getFlash(),
        ]);
    }

    private function setStatus(int $id, string $status, string $message): void
    {
        // Faz 1.5-A: dönem kapatma/kilitleme/açma/arşivleme önemli finansal
        // işlemlerdir — artık sadece POST + CSRF ile yapılabilir.
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('companies');
        }

        $period = $this->company->findPeriod($id);
        if (!$period || !TenantContext::canManagePeriod((int)$period['company_id'])) {
            http_response_code(403);
            die('Dönem yönetim yetkiniz yok.');
        }

        try {
            $this->company->setPeriodStatus($id, $status);
            Audit::log('SETTINGS_CHANGE', 'PERIOD', $id, ['status' => $period['status']], ['status' => $status], $message);
            $this->setFlash('success', $message);
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }
        $this->redirect('companies/periods/' . (int)$period['company_id']);
    }
}
