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

    /**
     * Geçici teşhis sayfası: Numune/İrsaliye/Perakende belgelerinin bazı
     * kullanıcılarda listelerde/raporlarda görünmeme şikayetini araştırmak
     * için TÜM şirket/dönemler genelinde (tenant filtresi UYGULANMADAN,
     * kasıtlı olarak) salt-okunur bir döküm gösterir. Sadece Süper
     * Yönetici erişebilir — AUDIT_VIEW izninden BAĞIMSIZ, ayrıca kontrol
     * edilir, çünkü bu sayfa normalde asla görünmemesi gereken şirketler
     * arası veriyi gösterir.
     *
     * Kök neden bulunup kalıcı düzeltme yapıldıktan sonra bu metot ve
     * ilgili view kaldırılabilir.
     */
    public function teshis(): void
    {
        if (!Rbac::isSuperAdmin(TenantContext::userId())) {
            http_response_code(403);
            die('Bu sayfaya yalnızca Süper Yönetici erişebilir.');
        }

        $db = Database::getInstance();

        // faturalar.belge_tipi ENUM ise ve 'numune'/'irsaliye'/'perakende' bu
        // ENUM'da tanımlı DEĞİLSE, MySQL (strict olmayan modda) bu değerleri
        // sessizce boş string'e çevirip INSERT'i BAŞARILI gösterebilir — satır
        // gerçekten yazılır (bu yüzden stok hareketi de kalıcı olur) ama hiçbir
        // zaman belge_tipi='numune' filtresine uymaz. Bunu doğrudan sınamak için:
        $kolonTanimi = $db->selectOne(
            "SELECT COLUMN_TYPE, IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'faturalar' AND COLUMN_NAME = 'belge_tipi'"
        );
        $kolonTanimiDurum = $db->selectOne(
            "SELECT COLUMN_TYPE, IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'faturalar' AND COLUMN_NAME = 'durum'"
        );
        $dagitimTumTipler = $db->query(
            "SELECT belge_tipi, COUNT(*) AS adet FROM faturalar GROUP BY belge_tipi ORDER BY adet DESC"
        )->fetchAll();
        $dagitimTumDurumlar = $db->query(
            "SELECT durum, COUNT(*) AS adet FROM faturalar GROUP BY durum ORDER BY adet DESC"
        )->fetchAll();

        $companies = $db->query("SELECT id, company_name, status, deleted_at FROM companies ORDER BY id")->fetchAll();
        $periods = $db->query(
            "SELECT id, company_id, period_name, fiscal_year, status, is_active
             FROM accounting_periods ORDER BY company_id, fiscal_year"
        )->fetchAll();
        $dagilim = $db->query(
            "SELECT company_id, period_id, belge_tipi, durum, silindi_mi, COUNT(*) AS adet,
                    MIN(fatura_tarihi) AS en_eski, MAX(fatura_tarihi) AS en_yeni
             FROM faturalar
             WHERE belge_tipi IN ('numune','irsaliye','perakende')
             GROUP BY company_id, period_id, belge_tipi, durum, silindi_mi
             ORDER BY company_id, period_id, belge_tipi"
        )->fetchAll();
        $sonKayitlar = $db->query(
            "SELECT f.id, f.company_id, f.period_id, f.belge_tipi, f.fatura_no, f.fatura_tarihi,
                    f.durum, f.silindi_mi, f.cari_id, c.unvan AS cari_unvan, f.genel_toplam
             FROM faturalar f
             LEFT JOIN cariler c ON c.id = f.cari_id
             WHERE f.belge_tipi IN ('numune','irsaliye','perakende')
             ORDER BY f.id DESC
             LIMIT 30"
        )->fetchAll();
        $ariyor = trim((string)($_GET['cari'] ?? ''));
        $cariFaturalari = [];
        $eslesenCariler = [];
        if ($ariyor !== '') {
            $eslesenCariler = $db->select(
                "SELECT id, unvan, tip, company_id, silindi_mi FROM cariler WHERE unvan LIKE :q",
                [':q' => '%' . $ariyor . '%']
            );
            foreach ($eslesenCariler as $c) {
                $cariFaturalari[$c['id']] = $db->select(
                    "SELECT id, company_id, period_id, belge_tipi, fatura_no, fatura_tarihi, durum, silindi_mi, genel_toplam
                     FROM faturalar WHERE cari_id = :cid ORDER BY id DESC",
                    [':cid' => $c['id']]
                );
            }
        }
        $noAriyor = trim((string)($_GET['no'] ?? ''));
        $noEslesenler = [];
        if ($noAriyor !== '') {
            $noEslesenler = $db->select(
                "SELECT f.id, f.company_id, f.period_id, f.belge_tipi, f.fatura_no, f.fatura_tarihi,
                        f.durum, f.silindi_mi, f.cari_id, c.unvan AS cari_unvan, f.genel_toplam
                 FROM faturalar f
                 LEFT JOIN cariler c ON c.id = f.cari_id
                 WHERE f.fatura_no LIKE :q
                 ORDER BY f.id DESC",
                [':q' => '%' . $noAriyor . '%']
            );
        }

        $oturumCompanyId = TenantContext::activeCompanyId();
        $oturumPeriodId = TenantContext::activePeriodId();

        $this->view('audit/teshis', [
            'pageTitle'       => 'Teşhis: Numune/İrsaliye/Perakende',
            'activeMenu'      => 'audit',
            'topbarTitle'     => 'Teşhis Aracı (Geçici)',
            'topbarIcon'      => 'fa-solid fa-magnifying-glass',
            'kolonTanimi'     => $kolonTanimi,
            'kolonTanimiDurum' => $kolonTanimiDurum,
            'dagitimTumTipler' => $dagitimTumTipler,
            'dagitimTumDurumlar' => $dagitimTumDurumlar,
            'companies'       => $companies,
            'periods'         => $periods,
            'dagilim'         => $dagilim,
            'sonKayitlar'     => $sonKayitlar,
            'ariyor'          => $ariyor,
            'eslesenCariler'  => $eslesenCariler,
            'cariFaturalari'  => $cariFaturalari,
            'noAriyor'        => $noAriyor,
            'noEslesenler'    => $noEslesenler,
            'oturumCompanyId' => $oturumCompanyId,
            'oturumPeriodId'  => $oturumPeriodId,
        ]);
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
