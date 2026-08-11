<?php

final class TakvimController extends Controller
{
    private Database $db;

    private array $eventTypes = [
        'overdue-cheque' => ['label' => 'Vadesi Geçen Çek/Senet', 'class' => 'danger', 'icon' => 'fa-file-invoice-dollar'],
        'overdue-expense' => ['label' => 'Vadesi Geçen Masraf', 'class' => 'blue', 'icon' => 'fa-receipt'],
        'upcoming-cheque' => ['label' => 'Yaklaşan Çek/Senet', 'class' => 'green', 'icon' => 'fa-file-circle-check'],
        'upcoming-expense' => ['label' => 'Yaklaşan Masraf', 'class' => 'cyan', 'icon' => 'fa-clock'],
        'invoice' => ['label' => 'Alış Satış Faturaları', 'class' => 'orange', 'icon' => 'fa-file-lines'],
        'shipping' => ['label' => 'Sevk Tarihleri', 'class' => 'mint', 'icon' => 'fa-truck-fast'],
        'credit' => ['label' => 'Kredi Ödemeleri', 'class' => 'purple', 'icon' => 'fa-building-columns'],
        'note' => ['label' => 'Notlarınız', 'class' => 'note', 'icon' => 'fa-note-sticky'],
        'other' => ['label' => 'Diğer', 'class' => 'slate', 'icon' => 'fa-circle-dot'],
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        $this->ensureNotesTable();

        $month = $this->monthFromRequest();
        $first = new DateTimeImmutable($month . '-01');
        $start = $first->modify('monday this week');
        $last = $first->modify('last day of this month');
        $end = $last->modify('sunday this week');

        $events = $this->eventsBetween($start->format('Y-m-d'), $end->format('Y-m-d'));

        $this->view('takvim/index', [
            'pageTitle' => 'Takvim',
            'activeMenu' => 'takvim',
            'topbarTitle' => 'Takvim',
            'topbarIcon' => 'fa-regular fa-calendar-days',
            'month' => $month,
            'first' => $first,
            'start' => $start,
            'end' => $end,
            'eventsByDate' => $this->groupByDate($events),
            'eventTypes' => $this->eventTypes,
            'flash' => $this->getFlash(),
        ]);
    }

    public function not_kaydet(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('takvim');
        }
        $this->ensureNotesTable();

        $date = trim((string)($_POST['tarih'] ?? ''));
        $title = trim((string)($_POST['baslik'] ?? ''));
        $desc = trim((string)($_POST['aciklama'] ?? ''));
        $month = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? substr($date, 0, 7) : date('Y-m');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $title === '') {
            $this->setFlash('danger', 'Not tarihi ve başlığı zorunlu.');
            $this->redirect('takvim?ay=' . $month);
        }

        $this->db->insert('takvim_notlari', [
            'company_id' => TenantContext::activeCompanyId(),
            'period_id' => TenantContext::activePeriodId(),
            'user_id' => AuthGuard::userId(),
            'tarih' => $date,
            'baslik' => substr($title, 0, 180),
            'aciklama' => $desc,
            'renk' => 'note',
        ]);

        $this->setFlash('success', 'Takvim notu kaydedildi.');
        $this->redirect('takvim?ay=' . $month);
    }

    public function not_sil(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('takvim');
        }

        $cid = TenantContext::activeCompanyId();
        $pid = TenantContext::activePeriodId();
        $uid = AuthGuard::userId();

        $note = $this->db->selectOne(
            "SELECT id, tarih, baslik, aciklama FROM takvim_notlari
             WHERE id = :id AND user_id = :uid AND company_id = :cid AND period_id = :pid
             LIMIT 1",
            [':id' => $id, ':uid' => $uid, ':cid' => $cid, ':pid' => $pid]
        );
        if ($note) {
            // Savunma-derinliği: SELECT ile zaten şirket/dönem/kullanıcı eşleşmesi
            // doğrulandı, ama silme sorgusu da AYNI filtreleri taşır (ham query()
            // TenantContext'in otomatik company_id enjeksiyonundan geçmez).
            $this->db->query(
                "DELETE FROM takvim_notlari WHERE id = :id AND user_id = :uid AND company_id = :cid AND period_id = :pid",
                [':id' => $id, ':uid' => $uid, ':cid' => $cid, ':pid' => $pid]
            );
            Audit::log('DELETE', 'TAKVIM', $id, $note, null, "Takvim notu silindi: {$note['baslik']}");
            $this->setFlash('success', 'Takvim notu silindi.');
            $this->redirect('takvim?ay=' . substr((string)$note['tarih'], 0, 7));
        }
        $this->setFlash('danger', 'Not bulunamadı.');
        $this->redirect('takvim');
    }

    private function monthFromRequest(): string
    {
        $month = (string)($_GET['ay'] ?? date('Y-m'));
        return preg_match('/^\d{4}-\d{2}$/', $month) ? $month : date('Y-m');
    }

    private function eventsBetween(string $start, string $end): array
    {
        return array_merge(
            $this->invoiceEvents($start, $end),
            $this->expenseEvents($start, $end),
            $this->chequeEvents($start, $end),
            $this->creditEvents($start, $end),
            $this->noteEvents($start, $end)
        );
    }

    private function invoiceEvents(string $start, string $end): array
    {
        if (!$this->tableExists('faturalar')) return [];
        $rows = $this->db->select(
            "SELECT f.id, f.fatura_tarihi, f.belge_tipi, f.fatura_no, f.genel_toplam,
                    COALESCE(c.unvan, 'Cari yok') AS cari_unvan
             FROM faturalar f
             LEFT JOIN cariler c ON c.id = f.cari_id
             WHERE f.silindi_mi = 0 AND f.durum <> 'iptal'
               AND f.fatura_tarihi BETWEEN :start AND :end
               AND f.company_id = :cid AND f.period_id = :pid
             ORDER BY f.fatura_tarihi, f.id",
            [':start' => $start, ':end' => $end, ':cid' => TenantContext::activeCompanyId(), ':pid' => TenantContext::activePeriodId()]
        );

        return array_map(function (array $r): array {
            $isSale = in_array($r['belge_tipi'], ['satis', 'perakende', 'siparis', 'irsaliye', 'proforma'], true);
            return [
                'date' => $r['fatura_tarihi'],
                'type' => 'invoice',
                'title' => ($isSale ? 'Satış' : 'Alış') . ': ' . ($r['cari_unvan'] ?: $r['fatura_no']),
                'meta' => $this->money($r['genel_toplam'] ?? 0),
                'url' => BASE_URL . '/' . ($isSale ? 'satis' : 'alis') . '/detay/' . (int)$r['id'],
            ];
        }, $rows);
    }

    private function expenseEvents(string $start, string $end): array
    {
        if (!$this->tableExists('masraflar')) return [];
        $rows = $this->db->select(
            "SELECT id, vade_tarihi, aciklama, tutar, odeme_durumu
             FROM masraflar
             WHERE silindi_mi = 0
               AND vade_tarihi IS NOT NULL AND vade_tarihi <> '0000-00-00'
               AND vade_tarihi BETWEEN :start AND :end
               AND company_id = :cid AND period_id = :pid
             ORDER BY vade_tarihi, id",
            [':start' => $start, ':end' => $end, ':cid' => TenantContext::activeCompanyId(), ':pid' => TenantContext::activePeriodId()]
        );
        $today = date('Y-m-d');
        return array_map(fn(array $r): array => [
            'date' => $r['vade_tarihi'],
            'type' => ((string)$r['vade_tarihi'] < $today && ($r['odeme_durumu'] ?? '') !== 'odendi') ? 'overdue-expense' : 'upcoming-expense',
            'title' => 'Masraf: ' . (($r['aciklama'] ?? '') ?: 'Vade'),
            'meta' => $this->money($r['tutar'] ?? 0),
            'url' => BASE_URL . '/masraf',
        ], $rows);
    }

    private function chequeEvents(string $start, string $end): array
    {
        if (!$this->tableExists('cek_senet_portfoyu')) return [];
        $rows = $this->db->select(
            "SELECT p.id, p.tip, p.vade_tarihi, p.belge_no, p.tutar, p.durum,
                    COALESCE(c.unvan, 'Cari yok') AS cari_unvan
             FROM cek_senet_portfoyu p
             LEFT JOIN cariler c ON c.id = p.cari_id AND c.company_id = p.company_id
             WHERE p.silindi_mi = 0
               AND p.vade_tarihi BETWEEN :start AND :end
               AND p.company_id = :cid AND p.period_id = :pid
             ORDER BY p.vade_tarihi, p.id",
            [':start' => $start, ':end' => $end, ':cid' => TenantContext::activeCompanyId(), ':pid' => TenantContext::activePeriodId()]
        );
        $today = date('Y-m-d');
        return array_map(fn(array $r): array => [
            'date' => $r['vade_tarihi'],
            'type' => ((string)$r['vade_tarihi'] < $today && !in_array((string)($r['durum'] ?? ''), ['tahsil', 'odendi', 'kapandi'], true)) ? 'overdue-cheque' : 'upcoming-cheque',
            'title' => ucfirst((string)$r['tip']) . ': ' . (($r['cari_unvan'] ?? '') ?: ($r['belge_no'] ?? '')),
            'meta' => $this->money($r['tutar'] ?? 0),
            'url' => BASE_URL . '/rapor/financial/' . (($r['tip'] ?? '') === 'cek' ? 'cekler' : 'senetler'),
        ], $rows);
    }

    private function creditEvents(string $start, string $end): array
    {
        foreach (['kredi_odemeleri', 'kredi_odeme_plani'] as $table) {
            if (!$this->tableExists($table)) continue;
            $dateColumn = $this->columnExists($table, 'odeme_tarihi') ? 'odeme_tarihi' : ($this->columnExists($table, 'tarih') ? 'tarih' : '');
            if ($dateColumn === '') continue;
            $amountColumn = $this->columnExists($table, 'tutar') ? 'tutar' : ($this->columnExists($table, 'taksit_tutari') ? 'taksit_tutari' : '0');
            $hasCompanyId = $this->columnExists($table, 'company_id');
            $hasPeriodId = $this->columnExists($table, 'period_id');
            $tenantSql = ($hasCompanyId ? ' AND company_id = :cid' : '') . ($hasPeriodId ? ' AND period_id = :pid' : '');
            $params = [':start' => $start, ':end' => $end];
            if ($hasCompanyId) $params[':cid'] = TenantContext::activeCompanyId();
            if ($hasPeriodId) $params[':pid'] = TenantContext::activePeriodId();
            $rows = $this->db->select(
                "SELECT {$dateColumn} AS tarih, {$amountColumn} AS tutar
                 FROM {$table}
                 WHERE {$dateColumn} BETWEEN :start AND :end{$tenantSql}
                 ORDER BY {$dateColumn}",
                $params
            );
            return array_map(fn(array $r): array => [
                'date' => $r['tarih'],
                'type' => 'credit',
                'title' => 'Kredi ödemesi',
                'meta' => $this->money($r['tutar'] ?? 0),
                'url' => BASE_URL . '/kredi',
            ], $rows);
        }
        return [];
    }

    private function noteEvents(string $start, string $end): array
    {
        $rows = $this->db->select(
            "SELECT id, tarih, baslik, aciklama
             FROM takvim_notlari
             WHERE tarih BETWEEN :start AND :end
               AND company_id = :cid AND period_id = :pid AND user_id = :uid
             ORDER BY tarih, id",
            [':start' => $start, ':end' => $end, ':cid' => TenantContext::activeCompanyId(), ':pid' => TenantContext::activePeriodId(), ':uid' => AuthGuard::userId()]
        );
        return array_map(fn(array $r): array => [
            'date' => $r['tarih'],
            'type' => 'note',
            'title' => $r['baslik'],
            'meta' => $r['aciklama'] ?? '',
            'url' => '',
            'note_id' => (int)$r['id'],
        ], $rows);
    }

    private function groupByDate(array $events): array
    {
        $grouped = [];
        foreach ($events as $event) {
            $grouped[$event['date']][] = $event;
        }
        return $grouped;
    }

    private function ensureNotesTable(): void
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS takvim_notlari (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                company_id INT UNSIGNED NULL,
                period_id INT UNSIGNED NULL,
                user_id INT UNSIGNED NOT NULL,
                tarih DATE NOT NULL,
                baslik VARCHAR(180) NOT NULL,
                aciklama TEXT NULL,
                renk VARCHAR(40) NOT NULL DEFAULT 'note',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_tn_date (tarih),
                KEY idx_tn_scope (company_id, period_id, user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci
        ");
    }

    private function tableExists(string $table): bool
    {
        $row = $this->db->selectOne(
            "SELECT COUNT(*) AS n FROM INFORMATION_SCHEMA.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table",
            [':table' => $table]
        );
        return (int)($row['n'] ?? 0) > 0;
    }

    private function columnExists(string $table, string $column): bool
    {
        $row = $this->db->selectOne(
            "SELECT COUNT(*) AS n FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column",
            [':table' => $table, ':column' => $column]
        );
        return (int)($row['n'] ?? 0) > 0;
    }

    private function money($value): string
    {
        return number_format((float)$value, 2, ',', '.') . ' ₺';
    }
}
