<?php
/** @var DateTimeImmutable $first */
/** @var DateTimeImmutable $start */
/** @var DateTimeImmutable $end */
/** @var array $eventsByDate */
/** @var array $eventTypes */
/** @var array $flash */
$h = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);
$monthNames = [1=>'Ocak',2=>'Şubat',3=>'Mart',4=>'Nisan',5=>'Mayıs',6=>'Haziran',7=>'Temmuz',8=>'Ağustos',9=>'Eylül',10=>'Ekim',11=>'Kasım',12=>'Aralık'];
$weekDays = ['Pts','Sal','Çar','Per','Cum','Cmt','Paz'];
$currentMonth = $first->format('Y-m');
$legendColors = [
    'danger' => '#ef4444',
    'blue' => '#2563eb',
    'green' => '#16a34a',
    'cyan' => '#06b6d4',
    'orange' => '#f59e0b',
    'mint' => '#10b981',
    'purple' => '#1e8c55',
    'note' => '#0d623a',
    'slate' => '#334155',
];
$prevMonth = $first->modify('-1 month')->format('Y-m');
$nextMonth = $first->modify('+1 month')->format('Y-m');
$today = date('Y-m-d');
$totalEvents = array_sum(array_map('count', $eventsByDate));
$upcomingEvents = [];
foreach ($eventsByDate as $date => $items) {
    if ($date >= $today) {
        foreach ($items as $event) {
            $upcomingEvents[] = $event + ['date' => $date];
        }
    }
}
usort($upcomingEvents, fn($a, $b) => strcmp($a['date'], $b['date']));
$upcomingEvents = array_slice($upcomingEvents, 0, 6);
?>
<style>
  .calendar-page{display:grid;grid-template-columns:minmax(0,1fr) 290px;gap:16px;align-items:start;font-size:90%}
  .calendar-card{background:#fff;border:1px solid #cfe1cd;border-radius:18px;box-shadow:0 14px 38px rgba(8,69,38,.08);overflow:hidden}
  .calendar-hero{padding:22px 24px;background:linear-gradient(135deg,#084526,#0d623a 58%,#d97a0c);color:#fff;display:flex;justify-content:space-between;gap:16px;align-items:center}
  .calendar-title h2{font-size:24px;font-weight:900;margin:0;letter-spacing:-.3px}
  .calendar-title p{margin:4px 0 0;color:#cfe1cd;font-size:13px}
  .calendar-nav{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}
  .calendar-nav a,.calendar-nav button{height:38px;border:1px solid rgba(255,255,255,.28);background:rgba(255,255,255,.14);color:#fff;border-radius:10px;padding:0 12px;font-size:12.5px;font-weight:800;text-decoration:none;display:inline-flex;align-items:center;gap:7px}
  .calendar-nav button{cursor:pointer}
  .calendar-nav a:hover,.calendar-nav button:hover{background:rgba(255,255,255,.24);color:#fff}
  .calendar-body{padding:14px}
  .calendar-weekdays{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:7px;margin-bottom:7px}
  .calendar-weekdays div{text-align:center;color:#64748b;font-size:12px;font-weight:900;text-transform:uppercase}
  .calendar-grid{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:7px}
  .calendar-day{min-height:112px;border:1px solid #ecf5eb;border-radius:13px;background:#fff;padding:8px;display:flex;flex-direction:column;gap:5px;cursor:pointer;transition:border-color .15s,box-shadow .15s,transform .15s}
  .calendar-day:hover{border-color:#a9d4be;box-shadow:0 10px 24px rgba(13,98,58,.10);transform:translateY(-1px)}
  .calendar-day.other{background:#fafafa;color:#a1a1aa}
  .calendar-day.today{border-color:#1e8c55;box-shadow:0 0 0 3px rgba(30,140,85,.12)}
  .day-head{display:flex;justify-content:space-between;align-items:center;gap:8px}
  .day-num{font-size:13px;font-weight:900;color:#1e1b4b}
  .calendar-day.other .day-num{color:#a1a1aa}
  .day-dot{width:7px;height:7px;border-radius:50%;background:#1e8c55;opacity:0}
  .calendar-day.has-events .day-dot{opacity:1}
  .day-events{display:flex;flex-direction:column;gap:4px;min-width:0}
  .cal-event{display:flex;align-items:center;gap:5px;min-width:0;border-radius:8px;padding:4px 6px;color:#fff;text-decoration:none;font-size:10.5px;font-weight:800;line-height:1.2;box-shadow:0 5px 12px rgba(0,0,0,.10)}
  .cal-event:hover{color:#fff;filter:brightness(1.05)}
  .cal-event i{font-size:10px;opacity:.92;flex-shrink:0}
  .cal-event span{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;min-width:0}
  .event-danger{background:#ef4444}.event-blue{background:#2563eb}.event-green{background:#16a34a}.event-cyan{background:#06b6d4}.event-orange{background:#f59e0b}.event-mint{background:#10b981}.event-purple{background:#1e8c55}.event-note{background:#fff;color:#084526;border:1px solid #cfe1cd}.event-note:hover{color:#084526}.event-slate{background:#334155}
  .calendar-sidebar{display:grid;gap:14px}
  .side-card{background:#fff;border:1px solid #cfe1cd;border-radius:18px;box-shadow:0 14px 38px rgba(8,69,38,.08);padding:18px}
  .side-card h3{font-size:15px;font-weight:900;color:#05301c;margin:0 0 12px;display:flex;align-items:center;gap:8px}
  .stat-row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
  .stat-box{padding:14px;border-radius:14px;background:#f5faf4;border:1px solid #ecf5eb}
  .stat-box b{display:block;font-size:24px;line-height:1;color:#0d623a}
  .stat-box span{display:block;font-size:11px;color:#64748b;font-weight:800;margin-top:6px}
  .legend-list{display:flex;flex-wrap:wrap;gap:8px}
  .legend-pill{--legend-color:#1e8c55;display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:7px 10px;background:color-mix(in srgb,var(--legend-color) 11%,#fff);border:1px solid color-mix(in srgb,var(--legend-color) 28%,#fff);font-size:11.5px;font-weight:800;color:#312e81}
  .legend-pill::before{content:'';width:9px;height:9px;border-radius:50%;background:var(--legend-color);box-shadow:0 0 0 3px color-mix(in srgb,var(--legend-color) 14%,transparent)}
  .legend-pill i{font-size:10px}
  .upcoming{display:grid;gap:8px}
  .upcoming-item{display:flex;gap:10px;padding:10px;border-radius:12px;background:#f5faf4;text-decoration:none;color:#1e1b4b;border:1px solid #ecf5eb;min-width:0}
  .upcoming-date{width:48px;flex-shrink:0;text-align:center;border-radius:10px;background:#fff;border:1px solid #cfe1cd;padding:6px 4px}
  .upcoming-date b{display:block;color:#0d623a;font-size:16px;line-height:1}
  .upcoming-date span{font-size:10px;font-weight:900;color:#64748b}
  .upcoming-title{font-size:12.5px;font-weight:900;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .upcoming-meta{font-size:11px;color:#64748b;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .cal-alert{border-radius:12px;padding:12px 14px;margin-bottom:16px;font-size:13px;font-weight:800}
  .cal-alert.success{background:#ecfdf5;color:#166534;border:1px solid #bbf7d0}
  .cal-alert.danger{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}
  .note-modal{position:fixed;inset:0;background:rgba(6,58,34,.54);display:none;align-items:center;justify-content:center;padding:18px;z-index:1200;backdrop-filter:blur(5px)}
  .note-modal.open{display:flex}
  .note-dialog{width:min(460px,100%);background:#fff;border-radius:18px;box-shadow:0 28px 80px rgba(6,58,34,.32);overflow:hidden}
  .note-head{padding:18px 20px;background:linear-gradient(135deg,#0d623a,#d97a0c);color:#fff;display:flex;justify-content:space-between;align-items:center}
  .note-head h3{font-size:16px;font-weight:900;margin:0}
  .note-close{width:32px;height:32px;border:0;border-radius:9px;background:rgba(255,255,255,.18);color:#fff}
  .note-form{padding:20px;display:grid;gap:12px}
  .note-form label{font-size:12px;font-weight:900;color:#475569}
  .note-form input,.note-form textarea{width:100%;border:1.5px solid #cfe1cd;border-radius:10px;padding:11px 12px;font:inherit;font-size:13.5px;color:#1e1b4b;outline:none}
  .note-form textarea{min-height:90px;resize:vertical}
  .note-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:4px}
  .note-btn{border:0;border-radius:10px;padding:10px 14px;font-size:13px;font-weight:900}
  .note-btn.primary{background:linear-gradient(135deg,#0d623a,#d97a0c);color:#fff}
  .note-btn.ghost{background:#fff;color:#084526;border:1.5px solid #cfe1cd}
  @media(max-width:1050px){.calendar-page{grid-template-columns:1fr}.calendar-sidebar{grid-template-columns:repeat(2,minmax(0,1fr))}.calendar-sidebar .side-card:last-child{grid-column:1/-1}}
  @media(max-width:680px){.calendar-page{font-size:100%}.calendar-hero{flex-direction:column;align-items:flex-start}.calendar-nav{justify-content:flex-start}.calendar-body{padding:12px;overflow-x:auto}.calendar-weekdays,.calendar-grid{min-width:720px}.calendar-day{min-height:104px}.calendar-sidebar{grid-template-columns:1fr}.note-actions{flex-direction:column}.note-btn{width:100%}}
  @media print{
    @page{size:landscape;margin:10mm}
    body{background:#fff!important}
    .sidebar,.topbar,.mobile-bottom-bar,.sidebar-backdrop,.calendar-sidebar,.calendar-nav,.note-modal{display:none!important}
    .page-wrapper{margin-left:0!important;width:100%!important}
    .main-content{padding:0!important}
    .calendar-page{display:block;font-size:78%}
    .calendar-card{box-shadow:none;border:0;border-radius:0}
    .calendar-hero{background:#fff!important;color:#111827!important;padding:0 0 8px}
    .calendar-title p{color:#475569!important}
    .calendar-body{padding:0;overflow:visible}
    .calendar-weekdays,.calendar-grid{min-width:0!important;gap:4px}
    .calendar-day{min-height:82px;break-inside:avoid;border-color:#d4d4d8}
    .cal-event{box-shadow:none;-webkit-print-color-adjust:exact;print-color-adjust:exact}
  }
</style>

<?php if (!empty($flash)): ?>
  <div class="cal-alert <?= ($flash['tip'] ?? '') === 'success' ? 'success' : 'danger' ?>">
    <?= $h($flash['mesaj'] ?? '') ?>
  </div>
<?php endif; ?>

<div class="calendar-page">
  <section class="calendar-card">
    <div class="calendar-hero">
      <div class="calendar-title">
        <h2><?= $h($monthNames[(int)$first->format('n')] . ' ' . $first->format('Y')) ?></h2>
        <p>Finansal vadeler, faturalar ve kişisel notlar tek takvimde.</p>
      </div>
      <div class="calendar-nav">
        <a href="<?= BASE_URL ?>/takvim?ay=<?= $h(date('Y-m')) ?>"><i class="fa-solid fa-calendar-day"></i> Bugün</a>
        <a href="<?= BASE_URL ?>/takvim?ay=<?= $h($prevMonth) ?>"><i class="fa-solid fa-chevron-left"></i></a>
        <a href="<?= BASE_URL ?>/takvim?ay=<?= $h($nextMonth) ?>"><i class="fa-solid fa-chevron-right"></i></a>
        <button type="button" onclick="window.print()"><i class="fa-solid fa-print"></i> Yazdır</button>
      </div>
    </div>

    <div class="calendar-body">
      <div class="calendar-weekdays">
        <?php foreach ($weekDays as $day): ?><div><?= $h($day) ?></div><?php endforeach; ?>
      </div>
      <div class="calendar-grid">
        <?php for ($d = $start; $d <= $end; $d = $d->modify('+1 day')):
          $date = $d->format('Y-m-d');
          $items = $eventsByDate[$date] ?? [];
          $isOther = $d->format('Y-m') !== $currentMonth;
          $isToday = $date === $today;
        ?>
          <div class="calendar-day <?= $isOther ? 'other' : '' ?> <?= $isToday ? 'today' : '' ?> <?= $items ? 'has-events' : '' ?>" data-date="<?= $h($date) ?>">
            <div class="day-head">
              <div class="day-num"><?= $h($d->format('j')) ?></div>
              <span class="day-dot"></span>
            </div>
            <div class="day-events">
              <?php foreach (array_slice($items, 0, 4) as $event):
                $type = $eventTypes[$event['type']] ?? $eventTypes['other'];
                $content = '<i class="fa-solid ' . $h($type['icon']) . '"></i><span>' . $h($event['title']) . '</span>';
              ?>
                <?php if (!empty($event['url'])): ?>
                  <a class="cal-event event-<?= $h($type['class']) ?>" href="<?= $h($event['url']) ?>" title="<?= $h(($event['title'] ?? '') . ' ' . ($event['meta'] ?? '')) ?>"><?= $content ?></a>
                <?php else: ?>
                  <span class="cal-event event-<?= $h($type['class']) ?>" title="<?= $h(($event['title'] ?? '') . ' ' . ($event['meta'] ?? '')) ?>"><?= $content ?></span>
                <?php endif; ?>
              <?php endforeach; ?>
              <?php if (count($items) > 4): ?>
                <span class="cal-event event-slate"><i class="fa-solid fa-plus"></i><span><?= count($items) - 4 ?> kayıt daha</span></span>
              <?php endif; ?>
            </div>
          </div>
        <?php endfor; ?>
      </div>
    </div>
  </section>

  <aside class="calendar-sidebar">
    <div class="side-card">
      <h3><i class="fa-solid fa-signal"></i> Özet</h3>
      <div class="stat-row">
        <div class="stat-box"><b><?= (int)$totalEvents ?></b><span>Bu görünümde kayıt</span></div>
        <div class="stat-box"><b><?= count($upcomingEvents) ?></b><span>Yaklaşan kayıt</span></div>
      </div>
    </div>
    <div class="side-card">
      <h3><i class="fa-solid fa-tags"></i> Kategoriler</h3>
      <div class="legend-list">
        <?php foreach ($eventTypes as $type):
          $legendColor = $legendColors[$type['class'] ?? ''] ?? '#1e8c55';
        ?>
          <span class="legend-pill" style="--legend-color:<?= $h($legendColor) ?>"><i class="fa-solid <?= $h($type['icon']) ?>"></i><?= $h($type['label']) ?></span>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="side-card">
      <h3><i class="fa-solid fa-bell"></i> Yaklaşanlar</h3>
      <div class="upcoming">
        <?php if (!$upcomingEvents): ?>
          <div style="font-size:13px;color:#64748b;">Yaklaşan kayıt bulunmuyor.</div>
        <?php endif; ?>
        <?php foreach ($upcomingEvents as $event):
          $dt = new DateTimeImmutable($event['date']);
          $href = !empty($event['url']) ? $event['url'] : '#';
        ?>
          <a class="upcoming-item" href="<?= $h($href) ?>">
            <div class="upcoming-date"><b><?= $h($dt->format('j')) ?></b><span><?= $h($monthNames[(int)$dt->format('n')]) ?></span></div>
            <div style="min-width:0;">
              <div class="upcoming-title"><?= $h($event['title']) ?></div>
              <div class="upcoming-meta"><?= $h($event['meta'] ?? '') ?></div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </aside>
</div>

<div class="note-modal" id="noteModal" aria-hidden="true">
  <div class="note-dialog">
    <div class="note-head">
      <h3>Takvim Notu</h3>
      <button class="note-close" type="button" id="noteClose"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form class="note-form" method="post" action="<?= BASE_URL ?>/takvim/not-kaydet">
      <div>
        <label for="noteDate">Tarih</label>
        <input id="noteDate" name="tarih" type="date" required>
      </div>
      <div>
        <label for="noteTitle">Başlık</label>
        <input id="noteTitle" name="baslik" maxlength="180" required>
      </div>
      <div>
        <label for="noteDesc">Açıklama</label>
        <textarea id="noteDesc" name="aciklama"></textarea>
      </div>
      <div class="note-actions">
        <button class="note-btn ghost" type="button" id="noteCancel">Vazgeç</button>
        <button class="note-btn primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Kaydet</button>
      </div>
    </form>
  </div>
</div>

<script>
(function(){
  const modal = document.getElementById('noteModal');
  const dateInput = document.getElementById('noteDate');
  const titleInput = document.getElementById('noteTitle');
  const close = () => {
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
  };
  document.querySelectorAll('.calendar-day').forEach(day => {
    day.addEventListener('click', e => {
      if (e.target.closest('a')) return;
      dateInput.value = day.dataset.date;
      titleInput.value = '';
      modal.classList.add('open');
      modal.setAttribute('aria-hidden', 'false');
      setTimeout(() => titleInput.focus(), 40);
    });
  });
  document.getElementById('noteClose').addEventListener('click', close);
  document.getElementById('noteCancel').addEventListener('click', close);
  modal.addEventListener('click', e => { if (e.target === modal) close(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape' && modal.classList.contains('open')) close(); });
})();
</script>
