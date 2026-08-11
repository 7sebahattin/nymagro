<?php
/**
 * AuditController
 * --------------------------------------------------------
 * Süper Yönetici > Audit Log / Giriş Geçmişi. Bu ekran ve altındaki
 * TÜM endpoint'ler sadece AUDIT_VIEW iznine sahip kullanıcılara açıktır
 * (bkz. Rbac — normal kullanıcı menüde bunu görmez, URL'yi bilse bile
 * backend 403 döner).
 *
 * URL şeması:
 *   GET /audit                    → index()          audit log listesi
 *   GET /audit/giris-gecmisi      → giris_gecmisi()   tüm kullanıcıların giriş geçmişi
 *   GET /audit/export             → export()          filtrelenmiş audit log CSV
 */
require_once MODELS_PATH . '/AuditAdmin.php';
require_once MODELS_PATH . '/Company.php';

final class AuditController extends Controller
{
    private AuditAdmin $audit;
    private Company $company;

    public function __construct()
    {
        $this->audit = new AuditAdmin();
        $this->company = new Company();
    }

    public function index(): void
    {
        $filters = $this->readFilters();
        $sayfa  = max(1, (int)($_GET['sayfa'] ?? 1));
        $limit  = 40;
        $offset = ($sayfa - 1) * $limit;
        $toplam = $this->audit->count($filters);

        $this->view('audit/index', [
            'pageTitle'   => 'Audit Log',
            'activeMenu'  => 'audit',
            'topbarTitle' => 'Audit Log',
            'topbarIcon'  => 'fa-solid fa-shield-halved',
            'kayitlar'    => $this->audit->list($filters, $limit, $offset),
            'filters'     => $filters,
            'toplam'      => $toplam,
            'sayfa'       => $sayfa,
            'sayfaSayisi' => (int)ceil($toplam / $limit),
            'moduller'    => $this->audit->distinctModules(),
            'islemler'    => $this->audit->distinctActions(),
            'moduleLabels' => Rbac::moduleLabels(),
            'sirketler'   => $this->company->all(),
            'donemler'    => !empty($filters['company_id']) ? $this->company->periods((int)$filters['company_id']) : [],
            'flash'       => $this->getFlash(),
        ]);
    }

    public function giris_gecmisi(): void
    {
        $filters = [
            'event' => trim((string)($_GET['event'] ?? '')),
            'q'     => trim((string)($_GET['q'] ?? '')),
            'start' => trim((string)($_GET['start'] ?? '')),
            'end'   => trim((string)($_GET['end'] ?? '')),
        ];
        $sayfa  = max(1, (int)($_GET['sayfa'] ?? 1));
        $limit  = 40;
        $offset = ($sayfa - 1) * $limit;
        $toplam = $this->audit->loginHistoryCount($filters);

        $this->view('audit/giris_gecmisi', [
            'pageTitle'   => 'Giriş Geçmişi',
            'activeMenu'  => 'audit',
            'topbarTitle' => 'Giriş Geçmişi (Tüm Kullanıcılar)',
            'topbarIcon'  => 'fa-solid fa-right-to-bracket',
            'kayitlar'    => $this->audit->loginHistoryList($filters, $limit, $offset),
            'filters'     => $filters,
            'toplam'      => $toplam,
            'sayfa'       => $sayfa,
            'sayfaSayisi' => (int)ceil($toplam / $limit),
            'flash'       => $this->getFlash(),
        ]);
    }

    public function export(): void
    {
        $filters = $this->readFilters();
        $rows = $this->audit->list($filters, 5000, 0);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="audit-log-' . date('Ymd-His') . '.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Tarih', 'Kullanıcı', 'İşlem', 'Modül', 'Kayıt', 'Açıklama', 'Sonuç', 'IP'], ';');
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['created_at'], $r['user_snapshot'], $r['action'], $r['module'], $r['record_id'],
                $r['description'], $r['success'] ? 'Başarılı' : 'Başarısız', $r['ip_address'],
            ], ';');
        }
        fclose($out);

        Audit::log('BULK_OPERATION', 'AUDIT', null, null, ['row_count' => count($rows)],
            'Süper Yönetici audit log export etti.', true);
        exit;
    }

    private function readFilters(): array
    {
        return [
            'user_id'    => (int)($_GET['user_id'] ?? 0),
            'company_id' => (int)($_GET['company_id'] ?? 0),
            'period_id'  => (int)($_GET['period_id'] ?? 0),
            'record_id'  => trim((string)($_GET['record_id'] ?? '')),
            'module'     => trim((string)($_GET['module'] ?? '')),
            'action'     => trim((string)($_GET['action'] ?? '')),
            'success'    => $_GET['success'] ?? '',
            'ip'         => trim((string)($_GET['ip'] ?? '')),
            'q'          => trim((string)($_GET['q'] ?? '')),
            'start'      => trim((string)($_GET['start'] ?? '')),
            'end'        => trim((string)($_GET['end'] ?? '')),
        ];
    }
}
