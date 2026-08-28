<?php
/**
 * View: satislar/index.php
 * Beklenen değişkenler:
 *   $faturalar   array  — fatura listesi (cari_unvan ile JOIN)
 *   $toplam      int    — toplam kayıt
 *   $ozetler     array  — adet, toplam_tutar, bekleyen_tutar, iptal_adet
 *   $arama       string
 *   $durum       string — filtre
 *   $donem       string — '1ay'|'bugun'|'haftaici'|'bu_ay'|'bu_yil'|'tumu'
 *   $iptalleri   bool
 *   $sayfa       int
 *   $sayfaSayisi int
 *   $limit       int
 *   $flash       array
 */
$fmt    = fn($n) => number_format((float)$n, 2, ',', '.') . ' ₺';
$fmtTar = fn($d) => $d ? date('d.m.Y', strtotime($d)) : '—';

$donemEtiketler = [
    'bugun'    => 'Bugün',
    'haftaici' => 'Bu Hafta',
    '1ay'      => 'Son 1 Ay',
    'bu_ay'    => 'Bu Ay',
    'bu_yil'   => 'Bu Yıl',
    'tumu'     => 'Tümü',
];

$durumRenk = [
    'taslak'       => ['#94a3b8','#fff','Taslak'],
    'onaylandi'    => ['#3b82f6','#fff','Onaylandı'],
    'odendi'       => ['#22c55e','#fff','Ödendi'],
    'kismi_odendi' => ['#f59e0b','#fff','Kısmi Ödendi'],
    'iptal'        => ['#64748b','#fff','İptal'],
];

$belgeTipi = $belgeTipi ?? 'satis';
$depoAdlari = array_column($depolar ?? [], 'ad', 'id');
$belgeTipTabi = in_array($belgeTipi, ['irsaliye', 'perakende', 'numune'], true);
$qStr = fn(array $extra=[]) => http_build_query(array_filter(array_merge(
    ['ara'=>$arama,'durum'=>$durum,'donem'=>$donem,'sayfa'=>$sayfa,'iptalleri'=>$iptalleri?'1':'','tip'=>$belgeTipTabi?$belgeTipi:''],
    $extra
), fn($v)=>$v!==''&&$v!==null&&$v!==false));
?>
<style>
  /* ── Action Btns ── */
  .action-btns { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:14px; }
  .btn-action { display:inline-flex; align-items:center; gap:6px; padding:7px 16px; border:none; border-radius:5px; font-size:13px; font-weight:600; cursor:pointer; transition:filter .15s,box-shadow .15s; text-decoration:none; white-space:nowrap; color:#fff; }
  .btn-action:hover { filter:brightness(1.12); box-shadow:0 3px 10px rgba(0,0,0,.18); color:#fff; }
  .btn-yeni    { background:#27ae60; }
  .btn-mavi    { background:#2980b9; }

  /* ── Özet Kartlar ── */
  .ozet-row { display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:12px; margin-bottom:16px; }
  .ozet-kart { background:var(--card-bg); border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,.07); padding:14px 18px; }
  .ozet-kart .ok-label { font-size:11px; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.5px; }
  .ozet-kart .ok-value { font-size:20px; font-weight:800; color:var(--text); margin-top:4px; }
  .ozet-kart .ok-value.green { color:#16a34a; }
  .ozet-kart .ok-value.amber { color:#d97706; }
  .ozet-kart .ok-value.red   { color:var(--danger); }

  /* ── Filter Panel ── */
  .filter-panel { background:var(--card-bg); border:1px solid var(--border); border-radius:8px 8px 0 0; border-bottom:none; }
  .filter-row { display:flex; align-items:center; gap:10px; flex-wrap:wrap; padding:10px 14px; }
  .filter-row + .filter-row { border-top:1px solid var(--border); }

  /* Dönem dropdown */
  .donem-wrap { position:relative; }
  .btn-donem { display:inline-flex; align-items:center; gap:6px; padding:6px 14px; background:#334155; color:var(--text); border:none; border-radius:6px; font-size:13px; font-weight:500; cursor:pointer; transition:background .15s; }
  .btn-donem:hover { background:#1e293b; }
  .donem-dropdown { position:absolute; top:calc(100% + 3px); left:0; z-index:300; background:var(--ink); border:1px solid var(--border2); border-radius:6px; box-shadow:0 6px 20px rgba(0,0,0,.1); min-width:150px; display:none; padding:4px 0; }
  .donem-dropdown.open { display:block; }
  .donem-item { padding:7px 16px; font-size:13px; color:var(--text2); cursor:pointer; display:block; text-decoration:none; }
  .donem-item:hover { background:var(--surface-2); }
  .donem-item.active { font-weight:700; color:var(--text); background:var(--surface-2); }

  /* Durum select */
  .filter-select { padding:6px 10px; border:1px solid var(--border2); border-radius:6px; font-size:13px; color:var(--text2); background:var(--card-bg); cursor:pointer; outline:none; }
  .filter-select:focus { border-color:#4ade80; }

  /* İptalleri toggle */
  .iptalli-wrap { display:flex; align-items:center; gap:7px; margin-left:auto; font-size:13px; color:var(--text2); font-weight:500; white-space:nowrap; }
  .toggle-switch { position:relative; width:38px; height:21px; flex-shrink:0; }
  .toggle-switch input { opacity:0; width:0; height:0; }
  .toggle-slider { position:absolute; inset:0; background:#cbd5e1; border-radius:21px; cursor:pointer; transition:background .2s; }
  .toggle-slider::before { content:''; position:absolute; width:15px; height:15px; left:3px; top:3px; background:var(--card-bg); border-radius:50%; transition:transform .2s; box-shadow:0 1px 3px rgba(0,0,0,.2); }
  .toggle-switch input:checked + .toggle-slider { background:#22c55e; }
  .toggle-switch input:checked + .toggle-slider::before { transform:translateX(17px); }

  /* Arama */
  .search-label { font-size:13px; color:var(--text2); font-weight:600; white-space:nowrap; margin-left:auto; }
  .search-input-wrap { position:relative; }
  .search-input-wrap i { position:absolute; left:10px; top:50%; transform:translateY(-50%); color:var(--muted); font-size:12px; pointer-events:none; }
  .search-txt { padding:6px 12px 6px 30px; border:1px solid var(--border2); border-radius:6px; font-size:13px; color:var(--text2); background:var(--card-bg); outline:none; width:260px; transition:border-color .2s,box-shadow .2s; }
  .search-txt:focus { border-color:#4ade80; box-shadow:0 0 0 3px rgba(74,222,128,.12); }
  .search-txt::placeholder { color:var(--muted); font-style:italic; }

  /* ── Table ── */
  .table-card { background:var(--card-bg); border:1px solid var(--border); border-radius:0 0 8px 8px; overflow:hidden; }
  .sales-table { width:100%; border-collapse:collapse; }
  .sales-table thead tr { background:#1e293b; }
  .sales-table thead th { padding:11px 14px; font-size:12.5px; font-weight:600; color:var(--muted); white-space:nowrap; border-right:1px solid rgba(255,255,255,.06); }
  .sales-table thead th:last-child { border-right:none; }
  .sales-table tbody tr.data-row { border-bottom:1px solid var(--border); transition:background .12s; }
  .sales-table tbody tr.data-row:hover { background:var(--surface-2); }

  /* Müşteri arama modal listesi */
  .musteri-item { padding:10px; border-bottom:1px solid var(--border); cursor:pointer; }
  .musteri-item:hover { background:var(--surface-2); }
  .sales-table tbody tr.data-row.expanded { background:rgba(59,130,246,.15); }
  .sales-table tbody td { padding:9px 14px; font-size:13px; color:var(--text2); vertical-align:middle; }

  /* toggle btn */
  .td-toggle { width:40px; text-align:center; }
  .row-toggle { width:22px; height:22px; border-radius:50%; border:2px solid; display:inline-flex; align-items:center; justify-content:center; font-size:15px; font-weight:700; cursor:pointer; transition:background .12s; line-height:1; background:transparent; }
  .row-toggle.plus  { border-color:#22c55e; color:#22c55e; }
  .row-toggle.minus { border-color:#ef4444; color:#ef4444; }
  .row-toggle.plus:hover  { background:rgba(34,197,94,.08); }
  .row-toggle.minus:hover { background:rgba(239,68,68,.08); }

  .cust-link { color:var(--info); text-decoration:none; font-weight:500; font-size:13px; }
  .cust-link:hover { text-decoration:underline; }
  .td-amount { text-align:right; font-weight:600; font-size:13px; color:var(--text); }
  .status-badge { display:inline-block; padding:3px 10px; border-radius:4px; font-size:12px; font-weight:600; white-space:nowrap; }

  /* Detail row */
  .detail-row { display:none; }
  .detail-row.open { display:table-row; }
  .detail-row > td { padding:0; border-bottom:2px solid var(--border); background:var(--surface-2); }
  .detail-inner { padding:10px 14px 14px 54px; }
  .detail-btns { display:flex; gap:6px; margin-bottom:12px; flex-wrap:wrap; }
  .odeme-liste { margin-top:12px; }
  .odeme-liste-baslik { font-size:12px; font-weight:700; color:var(--muted); margin-bottom:6px; text-transform:uppercase; letter-spacing:.03em; }
  .odeme-satir { display:flex; align-items:center; gap:10px; padding:6px 10px; border:1px solid var(--border); border-radius:6px; margin-bottom:5px; font-size:12.5px; background:var(--surface); }
  .odeme-satir .oe-tutar { font-weight:700; color:#16a34a; margin-left:auto; }
  .odeme-satir .oe-aksiyon { display:flex; gap:6px; }
  .odeme-satir .oe-aksiyon a, .odeme-satir .oe-aksiyon button { font-size:11.5px; padding:3px 8px; border-radius:4px; border:none; cursor:pointer; text-decoration:none; }
  .odeme-satir .oe-duzenle { background:#3b82f6; color:#fff; }
  .odeme-satir .oe-sil { background:#ef4444; color:#fff; }
  .btn-det { display:inline-flex; align-items:center; gap:5px; padding:5px 13px; border:none; border-radius:5px; font-size:12.5px; font-weight:600; cursor:pointer; transition:filter .15s; text-decoration:none; color:#fff; }
  .btn-det:hover { filter:brightness(1.12); color:#fff; }
  .btn-det.red   { background:#ef4444; }
  .btn-det.blue  { background:#3b82f6; }
  .btn-det.slate { background:#64748b; }

  .detail-prod-table { border-collapse:collapse; min-width:500px; max-width:700px; }
  .detail-prod-table thead th { padding:5px 10px; font-size:12px; font-weight:700; color:var(--muted); border-bottom:2px solid var(--border); text-align:left; }
  .detail-prod-table tbody td { padding:6px 10px; font-size:13px; color:var(--text2); border-bottom:1px solid var(--border); }
  .detail-prod-table tbody tr:last-child td { border-bottom:none; }
  .td-num { text-align:right; }

  /* Pagination */
  .pag-bar { display:flex; align-items:center; justify-content:space-between; padding:10px 14px; border-top:1px solid var(--border); background:var(--surface-2); }
  .pag-info { font-size:12.5px; color:var(--muted); }
  .pag-btns { display:flex; gap:3px; }
  .pag-btn { width:30px; height:30px; border:1px solid var(--border); border-radius:6px; background:var(--card-bg); font-size:12px; color:var(--text2); cursor:pointer; display:inline-flex; align-items:center; justify-content:center; transition:background .12s,border-color .12s; text-decoration:none; }
  .pag-btn:hover { background:var(--surface-2); }
  .pag-btn.active { background:#1e293b; border-color:var(--text); color:#4ade80; font-weight:700; }

  /* Empty state */
  .empty-state { text-align:center; padding:48px 16px; color:var(--muted); font-size:14px; }
  .empty-state i { font-size:32px; display:block; margin-bottom:10px; }

  .alert-success { background:rgba(46,204,113,.15); border:1px solid rgba(46,204,113,.28); color:var(--success); padding:12px 18px; border-radius:8px; margin-bottom:14px; font-size:13px; display:flex; align-items:center; gap:8px; }
  .alert-error   { background:rgba(231,76,60,.15); border:1px solid rgba(231,76,60,.28); color:var(--danger); padding:12px 18px; border-radius:8px; margin-bottom:14px; font-size:13px; display:flex; align-items:center; gap:8px; }
</style>

<?php if (!empty($flash)): ?>
  <div class="alert-<?= $flash['tip'] === 'success' ? 'success' : 'error' ?>">
    <i class="fa-solid fa-<?= $flash['tip'] === 'success' ? 'check-circle' : 'circle-exclamation' ?>"></i>
    <?= htmlspecialchars($flash['mesaj']) ?>
  </div>
<?php endif; ?>

<!-- Belge Tipi Sekmeleri: "Yeni Fatura" ekranındaki Fatura/İrsaliye Kaydet
     butonlarıyla kaydedilen belgeler burada ayrı sekmelerde gözlemlenebilir. -->
<div class="tab-scroll" style="margin-bottom:14px; border-bottom:1px solid var(--border);">
  <a href="<?= BASE_URL ?>/satis" style="padding:9px 16px; font-size:13px; font-weight:600; text-decoration:none; border-bottom:2px solid <?= $belgeTipi === 'satis' ? '#27ae60' : 'transparent' ?>; color:<?= $belgeTipi === 'satis' ? 'var(--text)' : 'var(--muted)' ?>;">
    <i class="fa-solid fa-file-invoice-dollar"></i> Faturalar
  </a>
  <a href="<?= BASE_URL ?>/satis?tip=irsaliye" style="padding:9px 16px; font-size:13px; font-weight:600; text-decoration:none; border-bottom:2px solid <?= $belgeTipi === 'irsaliye' ? '#27ae60' : 'transparent' ?>; color:<?= $belgeTipi === 'irsaliye' ? 'var(--text)' : 'var(--muted)' ?>;">
    <i class="fa-solid fa-truck"></i> İrsaliyeler
  </a>
  <a href="<?= BASE_URL ?>/satis?tip=perakende" style="padding:9px 16px; font-size:13px; font-weight:600; text-decoration:none; border-bottom:2px solid <?= $belgeTipi === 'perakende' ? '#27ae60' : 'transparent' ?>; color:<?= $belgeTipi === 'perakende' ? 'var(--text)' : 'var(--muted)' ?>;">
    <i class="fa-solid fa-cash-register"></i> Perakende
  </a>
  <a href="<?= BASE_URL ?>/satis?tip=numune" style="padding:9px 16px; font-size:13px; font-weight:600; text-decoration:none; border-bottom:2px solid <?= $belgeTipi === 'numune' ? '#27ae60' : 'transparent' ?>; color:<?= $belgeTipi === 'numune' ? 'var(--text)' : 'var(--muted)' ?>;">
    <i class="fa-solid fa-flask"></i> Numuneler
  </a>
</div>

<!-- Action Buttons — sekme değiştikçe o belge tipine uygun "ekle" aksiyonu gösterilir.
     Öncesinde bu blok yalnızca belgeTipi==='satis' için render ediliyordu; İrsaliyeler/
     Perakende/Numuneler sekmelerinde hiçbir "ekle" butonu yoktu — kullanıcı o sekmeye
     yalnızca Faturalar sekmesindeki "Yeni Fatura" ekranından, doğru butona basarak
     ulaşabiliyordu. Diğer belge tipleri de aynı paylaşılan /satis/ekle ekranından
     (veya perakende'de kendi özel ekranından) kaydedildiği için hedef URL'ler aynı
     kalıyor; yalnızca giriş noktası her sekmede görünür hale getiriliyor. -->
<?php if (Rbac::currentUserCan('SATIS_CREATE')): ?>
<div class="action-btns">
  <?php if ($belgeTipi === 'satis'): ?>
    <a href="<?= BASE_URL ?>/satis/perakende" class="btn-action" style="background:#5cb85c; color:#fff;">
      <i class="fa-solid fa-plus"></i> Perakende Satış Gir
    </a>
    <button type="button" class="btn-action" style="background:#5bc0de; color:#fff;" data-bs-toggle="modal" data-bs-target="#yeniMusteriModal">
      <i class="fa-solid fa-plus"></i> Yeni Müşteriye Satış Gir
    </button>
    <button type="button" class="btn-action" style="background:#d9534f; color:#fff;" data-bs-toggle="modal" data-bs-target="#kayitliMusteriModal">
      <i class="fa-solid fa-plus"></i> Kayıtlı Müşteriye Satış Gir
    </button>
    <a href="javascript:void(0)" class="btn-action" style="background:#f0ad4e; color:#fff;" onclick="alert('Serbest Meslek Makbuzları özelliği yakında eklenecek.')">
      <i class="fa-solid fa-file-invoice"></i> Serbest Meslek Makbuzları
    </a>
  <?php elseif ($belgeTipi === 'irsaliye'): ?>
    <a href="<?= BASE_URL ?>/satis/ekle" class="btn-action" style="background:#5bc0de; color:#fff;">
      <i class="fa-solid fa-plus"></i> Yeni İrsaliye Ekle
    </a>
  <?php elseif ($belgeTipi === 'perakende'): ?>
    <a href="<?= BASE_URL ?>/satis/perakende" class="btn-action" style="background:#5cb85c; color:#fff;">
      <i class="fa-solid fa-plus"></i> Perakende Satış Gir
    </a>
  <?php elseif ($belgeTipi === 'numune'): ?>
    <a href="<?= BASE_URL ?>/satis/ekle" class="btn-action" style="background:#8e44ad; color:#fff;">
      <i class="fa-solid fa-plus"></i> Yeni Numune Fişi Kaydet
    </a>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- Özet Kartlar -->
<div class="ozet-row">
  <div class="ozet-kart">
    <div class="ok-label"><?= ['irsaliye' => 'Toplam İrsaliye', 'numune' => 'Toplam Numune'][$belgeTipi] ?? 'Toplam Belge' ?></div>
    <div class="ok-value"><?= number_format((int)($ozetler['adet'] ?? 0)) ?></div>
  </div>
  <div class="ozet-kart">
    <div class="ok-label">Toplam Tutar</div>
    <div class="ok-value green"><?= $fmt($ozetler['toplam_tutar'] ?? 0) ?></div>
  </div>
  <?php if ($belgeTipi === 'irsaliye'): ?>
  <div class="ozet-kart">
    <div class="ok-label">Faturalandırılan</div>
    <div class="ok-value green"><?= number_format((int)($ozetler['faturalandirilan_adet'] ?? 0)) ?></div>
  </div>
  <div class="ozet-kart">
    <div class="ok-label">Faturalandırılmayan</div>
    <div class="ok-value amber"><?= number_format((int)($ozetler['faturalandirilmayan_adet'] ?? 0)) ?></div>
  </div>
  <?php elseif ($belgeTipi === 'numune'): ?>
    <!-- Numune belgeleri cari borç/gelir doğurmadığı için "Bekleyen Tahsilat"
         kartı burada anlamsız — Toplam Tutar kartı (yukarıda) yeterli. -->
  <?php else: ?>
  <div class="ozet-kart">
    <div class="ok-label">Bekleyen Tahsilat</div>
    <div class="ok-value amber"><?= $fmt($ozetler['bekleyen_tutar'] ?? 0) ?></div>
  </div>
  <?php endif; ?>
</div>

<!-- Filter Panel -->
<form method="GET" action="<?= BASE_URL ?>/satis" id="filterForm">
  <div class="filter-panel">

    <!-- Satır 1: dönem + durum + iptalleri -->
    <div class="filter-row">
      <!-- Dönem -->
      <div class="donem-wrap">
        <button type="button" class="btn-donem" id="donemBtn">
          <?= htmlspecialchars($donemEtiketler[$donem] ?? 'Tümü') ?>
          <i class="fa-solid fa-chevron-down" style="font-size:9px;"></i>
        </button>
        <div class="donem-dropdown" id="donemDrop">
          <?php foreach ($donemEtiketler as $k => $etiket): ?>
            <a href="<?= BASE_URL ?>/satis?<?= $qStr(['donem'=>$k,'sayfa'=>1]) ?>"
               class="donem-item <?= $donem === $k ? 'active' : '' ?>">
              <?= $etiket ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Durum -->
      <select class="filter-select" name="durum" onchange="this.form.submit()">
        <option value="" <?= $durum === '' ? 'selected' : '' ?>>Tüm Durumlar</option>
        <option value="taslak"       <?= $durum === 'taslak'       ? 'selected' : '' ?>>Taslak</option>
        <option value="onaylandi"    <?= $durum === 'onaylandi'    ? 'selected' : '' ?>>Onaylandı</option>
        <option value="odendi"       <?= $durum === 'odendi'       ? 'selected' : '' ?>>Ödendi</option>
        <option value="kismi_odendi" <?= $durum === 'kismi_odendi' ? 'selected' : '' ?>>Kısmi Ödendi</option>
        <option value="iptal"        <?= $durum === 'iptal'        ? 'selected' : '' ?>>İptal</option>
      </select>

      <input type="hidden" name="donem"  value="<?= htmlspecialchars($donem) ?>">
      <input type="hidden" name="sayfa"  value="1">
      <?php if ($belgeTipTabi): ?>
        <input type="hidden" name="tip" value="<?= htmlspecialchars($belgeTipi) ?>">
      <?php endif; ?>

      <!-- İptalleri toggle -->
      <div class="iptalli-wrap">
        <span>İptalleri de göster</span>
        <label class="toggle-switch">
          <input type="checkbox" name="iptalleri" value="1"
                 <?= $iptalleri ? 'checked' : '' ?>
                 onchange="this.form.submit()" />
          <span class="toggle-slider"></span>
        </label>
      </div>
    </div>

    <!-- Satır 2: Arama -->
    <div class="filter-row" style="justify-content:flex-end;">
      <span class="search-label">Ara:</span>
      <div class="search-input-wrap">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" name="ara" class="search-txt"
               placeholder="müşteri / fatura no... (en az 3 karakter)"
               value="<?= htmlspecialchars($arama) ?>"
               oninput="if(this.value.length===0||this.value.length>=3) this.form.submit()" />
      </div>
    </div>

  </div><!-- /filter-panel -->
</form>

<!-- Table -->
<div class="table-card">
  <div style="overflow-x: auto;">
    <table class="sales-table" style="min-width: 700px;">
      <thead>
        <tr>
          <th style="width:40px;"></th>
          <th>Tarih</th>
          <th>Müşteri</th>
          <th><?= ['irsaliye' => 'İrsaliye No', 'perakende' => 'Fiş No', 'numune' => 'Numune No'][$belgeTipi] ?? 'Fatura No' ?></th>
          <th style="text-align:right;">Tutar</th>
          <th>Durum</th>
        </tr>
      </thead>
    <tbody>
      <?php if (empty($faturalar)): ?>
        <tr>
          <td colspan="6" class="empty-state">
            <i class="fa-solid fa-file-invoice"></i>
            <?php if ($arama !== ''): ?>
              Arama sonucu bulunamadı.
            <?php elseif ($belgeTipi === 'irsaliye'): ?>
              Bu dönemde irsaliye yok.
            <?php elseif ($belgeTipi === 'perakende'): ?>
              Bu dönemde perakende satış yok.
            <?php elseif ($belgeTipi === 'numune'): ?>
              Bu dönemde numune verilmemiş.
            <?php else: ?>
              Bu dönemde satış faturası yok.
            <?php endif; ?>
          </td>
        </tr>
      <?php else: ?>
        <?php foreach ($faturalar as $f):
          $dr = $durumRenk[$f['durum']] ?? ['#94a3b8','#fff',$f['durum']];
        ?>
          <!-- Ana satır -->
          <tr class="data-row" id="row-<?= $f['id'] ?>">
            <td class="td-toggle">
              <button type="button"
                      class="row-toggle plus"
                      onclick="toggleDetail(<?= $f['id'] ?>, this)">+</button>
            </td>
            <td><?= $fmtTar($f['fatura_tarihi']) ?></td>
            <td>
              <?php if (($f['sevk_turu'] ?? '') === 'depolar_arasi'): ?>
                <span style="color:var(--text2);"><i class="fa-solid fa-right-left" style="color:var(--muted);"></i> <?= htmlspecialchars($depoAdlari[(int)($f['hedef_depo_id'] ?? 0)] ?? 'Depolar arası') ?></span>
              <?php elseif ($f['cari_unvan']): ?>
                <a href="<?= BASE_URL ?>/musteri/detay/<?= $f['cari_id'] ?>"
                   class="cust-link"><?= htmlspecialchars($f['cari_unvan']) ?></a>
              <?php else: ?>
                <span style="color:var(--muted);">—</span>
              <?php endif; ?>
            </td>
            <td><strong><?= htmlspecialchars($f['fatura_no']) ?></strong></td>
            <td class="td-amount"><?= $fmt($f['genel_toplam']) ?></td>
            <td>
              <span class="status-badge"
                    style="background:<?= $dr[0] ?>;color:<?= $dr[1] ?>;">
                <?= $dr[2] ?>
              </span>
              <?php if ($belgeTipi === 'irsaliye' && ($f['sevk_turu'] ?? 'musteri') !== 'depolar_arasi'): ?>
                <?php if ((int)($f['irsaliye_kullanildi'] ?? 0) === 1): ?>
                  <span class="status-badge" style="background:#16a34a;color:#fff;" title="Bu irsaliye bir satış faturasına dönüştürüldü.">Faturalandı</span>
                <?php else: ?>
                  <span class="status-badge" style="background:#94a3b8;color:#fff;">Faturalandırılmadı</span>
                <?php endif; ?>
              <?php endif; ?>
            </td>
          </tr>

          <!-- Detay satırı -->
          <tr class="detail-row" id="detail-<?= $f['id'] ?>">
            <td colspan="6">
              <div class="detail-inner">
                <div class="detail-btns">
                  <a href="<?= BASE_URL ?>/satis/detay/<?= $f['id'] ?>" class="btn-det blue">
                    <i class="fa-solid fa-eye"></i> Görüntüle / Düzenle
                  </a>
                  <a href="<?= BASE_URL ?>/satis/fatura/<?= $f['id'] ?>/print" target="_blank" rel="noopener" class="btn-det" style="background:#efa341;">
                    <i class="fa-solid fa-print"></i> Yazdır
                  </a>
                  <?php if ($belgeTipi === 'satis' && !empty($f['cari_id']) && (float)($f['kalan_tutar'] ?? 0) > 0.004 && Rbac::currentUserCan('NAKIT_CREATE')): ?>
                    <button type="button" class="btn-det" style="background:#5cb85c;"
                            onclick="odemeEkleAc(<?= (int)$f['cari_id'] ?>, '<?= htmlspecialchars(addslashes($f['cari_unvan'] ?? '')) ?>', <?= (float)$f['kalan_tutar'] ?>, '<?= htmlspecialchars(addslashes($f['fatura_no'] ?? '')) ?>')">
                      <i class="fa-solid fa-turkish-lira-sign"></i> Ödeme Ekle
                    </button>
                  <?php endif; ?>
                  <?php $belgeAdi = ['irsaliye' => 'İrsaliye', 'perakende' => 'Perakende satış', 'numune' => 'Numune fişi'][$belgeTipi] ?? 'Fatura'; ?>
                  <?php if ($f['durum'] === 'taslak' && Rbac::currentUserCan('SATIS_UPDATE')): ?>
                    <a href="#"
                       class="btn-det"
                       style="background:#3b82f6;"
                       onclick="return nymPost('<?= BASE_URL ?>/satis/durum/<?= $f['id'] ?>', '<?= $belgeAdi ?> onaylansın mı?')">
                      <i class="fa-solid fa-check"></i> Onayla
                    </a>
                  <?php endif; ?>
                  <?php if ($belgeTipi === 'irsaliye' && $f['durum'] !== 'iptal' && ($f['sevk_turu'] ?? 'musteri') !== 'depolar_arasi' && (int)($f['irsaliye_kullanildi'] ?? 0) === 0 && Rbac::currentUserCan('SATIS_CREATE')): ?>
                    <a href="<?= BASE_URL ?>/satis/ekle?kaynak_irsaliye_id=<?= $f['id'] ?>" class="btn-det" style="background:#16a34a;">
                      <i class="fa-solid fa-file-invoice-dollar"></i> Faturalandır
                    </a>
                  <?php endif; ?>
                  <?php if ($f['durum'] !== 'iptal' && Rbac::currentUserCan('SATIS_UPDATE')): ?>
                    <a href="#"
                       class="btn-det red"
                       onclick="return nymPost('<?= BASE_URL ?>/satis/iptal/<?= $f['id'] ?>', '<?= $belgeAdi ?> iptal edilsin mi?')">
                      <i class="fa-solid fa-ban"></i> İptal Et
                    </a>
                  <?php endif; ?>
                  <?php if (Rbac::currentUserCan('SATIS_DELETE')): ?>
                  <a href="#"
                     class="btn-det slate"
                     onclick="return nymPost('<?= BASE_URL ?>/satis/sil/<?= $f['id'] ?>', '<?= $belgeAdi ?> silinsin mi?')">
                    <i class="fa-solid fa-trash-can"></i> Sil
                  </a>
                  <?php endif; ?>
                </div>
                <div style="font-size:12px; color:var(--muted);">
                  <?php if ($belgeTipi === 'irsaliye'): ?>
                    <?php if (($f['sevk_turu'] ?? 'musteri') === 'depolar_arasi'): ?>
                      Sevk türü: <strong style="color:var(--text2);">Depolar Arası</strong> —
                      <?= htmlspecialchars($depoAdlari[(int)($f['depo_id'] ?? 0)] ?? '?') ?>
                      <i class="fa-solid fa-arrow-right" style="font-size:10px;"></i>
                      <?= htmlspecialchars($depoAdlari[(int)($f['hedef_depo_id'] ?? 0)] ?? '?') ?>
                    <?php else: ?>
                      Sevk türü: <strong style="color:var(--text2);">Müşteriye Sevk</strong> — çıkış deposu: <?= htmlspecialchars($depoAdlari[(int)($f['depo_id'] ?? 0)] ?? '?') ?>
                    <?php endif; ?>
                    &nbsp;|&nbsp;
                  <?php endif; ?>
                  Vade: <?= $fmtTar($f['vade_tarihi']) ?> &nbsp;|&nbsp;
                  KDV: <?= $fmt($f['kdv_tutari']) ?> &nbsp;|&nbsp;
                  Kalan: <strong style="color:#d97706;"><?= $fmt($f['kalan_tutar']) ?></strong>
                  <?php if ($f['aciklama']): ?>
                    &nbsp;|&nbsp; <?= htmlspecialchars($f['aciklama']) ?>
                  <?php endif; ?>
                </div>
                <?php if ($belgeTipi === 'satis' && !empty($f['cari_id'])): ?>
                  <div id="odemeler-<?= $f['id'] ?>" class="odeme-liste" data-yuklendi="0" data-fatura-id="<?= $f['id'] ?>"></div>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
  </div>

  <!-- Pagination -->
  <div class="pag-bar">
    <span class="pag-info">
      <?php
        $bas = $toplam > 0 ? (($sayfa - 1) * $limit + 1) : 0;
        $bit = min($sayfa * $limit, $toplam);
        $kayitAdi = ['irsaliye' => 'irsaliyeden', 'perakende' => 'perakende satıştan', 'numune' => 'numuneden'][$belgeTipi] ?? 'faturadan';
        echo "{$toplam} {$kayitAdi} {$bas}–{$bit} gösteriliyor";
      ?>
    </span>
    <?php if ($sayfaSayisi > 1): ?>
      <div class="pag-btns">
        <?php for ($i = 1; $i <= $sayfaSayisi; $i++): ?>
          <a href="<?= BASE_URL ?>/satis?<?= $qStr(['sayfa'=>$i]) ?>"
             class="pag-btn <?= $i === $sayfa ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
(function () {
  /* ── Dönem Dropdown ── */
  const donemBtn  = document.getElementById('donemBtn');
  const donemDrop = document.getElementById('donemDrop');
  if (donemBtn && donemDrop) {
    donemBtn.addEventListener('click', e => { e.stopPropagation(); donemDrop.classList.toggle('open'); });
    document.addEventListener('click', () => donemDrop.classList.remove('open'));
  }

  /* ── Satır expand/collapse ── */
  window.toggleDetail = function (id, btn) {
    const detailRow = document.getElementById('detail-' + id);
    const mainRow   = document.getElementById('row-' + id);
    if (!detailRow) return;
    const isOpen = detailRow.classList.toggle('open');
    mainRow.classList.toggle('expanded', isOpen);
    btn.textContent = isOpen ? '−' : '+';
    btn.classList.toggle('plus',  !isOpen);
    btn.classList.toggle('minus',  isOpen);
    if (isOpen) odemeleriYukle(id);
  };

  /* ── Faturaya uygulanan ödemeler (lazy-load, ilk açılışta) ── */
  function odemeleriYukle(faturaId) {
    const kutu = document.getElementById('odemeler-' + faturaId);
    if (!kutu || kutu.dataset.yuklendi === '1') return;
    kutu.dataset.yuklendi = '1';
    fetch('<?= BASE_URL ?>/satis/odemeleri/' + faturaId)
      .then(r => r.json())
      .then(res => {
        if (res.status !== 'success' || !res.uygulamalar.length) return;
        let html = '<div class="odeme-liste-baslik">Bu Faturaya Uygulanan Ödemeler</div>';
        res.uygulamalar.forEach(u => {
          html += '<div class="odeme-satir">'
            + '<span>' + (u.tarih ? u.tarih.substring(0, 10).split('-').reverse().join('.') : '') + '</span>'
            + '<span>' + (u.kasa_adi || '') + '</span>'
            + '<span>' + (u.odeme_yontemi || '') + '</span>'
            + '<span class="oe-tutar">' + Number(u.tutar).toFixed(2).replace('.', ',') + ' ₺</span>'
            + '<span class="oe-aksiyon">'
            + '<a class="oe-duzenle" href="<?= BASE_URL ?>/hesap/detay/' + u.kasa_id + '"><i class="fa-solid fa-pen"></i> Düzenle</a>'
            + '<button type="button" class="oe-sil" onclick="odemeUygulamasiSil(' + u.kasa_hareket_id + ', ' + faturaId + ')"><i class="fa-solid fa-trash-can"></i> Sil</button>'
            + '</span>'
            + '</div>';
        });
        kutu.innerHTML = html;
      })
      .catch(() => { kutu.dataset.yuklendi = '0'; });
  }

  window.odemeUygulamasiSil = function (kasaHareketId, faturaId) {
    if (!confirm('Bu ödeme hareketini silmek istediğinizden emin misiniz?\nFatura bakiyesi otomatik güncellenir.')) return;
    fetch('<?= BASE_URL ?>/hesap/hareketSil/' + kasaHareketId, { method: 'POST' })
      .then(r => r.json())
      .then(res => {
        if (res.success) {
          location.reload();
        } else {
          alert(res.message || 'Silinemedi.');
        }
      })
      .catch(() => alert('Bağlantı hatası.'));
  };

  /* ── Ödeme Ekle (fatura satırından hızlı tahsilat) ── */
  window.odemeEkleAc = function (cariId, cariUnvan, kalanTutar, faturaNo) {
    document.getElementById('oeCariId').value = cariId;
    document.getElementById('oeBaslik').textContent = 'Tahsilat — ' + cariUnvan + ' (' + faturaNo + ')';
    document.getElementById('oeTutar').value = kalanTutar.toFixed(2).replace('.', ',');
    document.getElementById('oeAciklama').value = 'Fatura ' + faturaNo + ' tahsilatı';
    new bootstrap.Modal(document.getElementById('odemeEkleModal')).show();
  };

  window.odemeKaydet = function () {
    const fd = new FormData();
    fd.append('cari_id', document.getElementById('oeCariId').value);
    fd.append('islem_tipi', 'giris');
    fd.append('kasa_id', document.getElementById('oeKasaId').value);
    fd.append('odeme_yontemi', document.getElementById('oeOdemeYontemi').value);
    fd.append('tutar', document.getElementById('oeTutar').value.replace(',', '.'));
    fd.append('tarih', document.getElementById('oeTarih').value);
    fd.append('saat', document.getElementById('oeSaat').value);
    fd.append('aciklama', document.getElementById('oeAciklama').value);

    if (!fd.get('kasa_id')) { alert('Kasa/Hesap seçiniz.'); return; }
    if (!fd.get('odeme_yontemi')) { alert('Ödeme yöntemi seçiniz.'); return; }
    if (!fd.get('tutar') || parseFloat(fd.get('tutar')) <= 0) { alert('Geçerli bir tutar giriniz.'); return; }

    fetch('<?= BASE_URL ?>/nakit/kaydet', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(res => {
        if (res.status === 'success') {
          location.reload();
        } else {
          alert('Hata: ' + (res.message || 'İşlem kaydedilemedi.'));
        }
      })
      .catch(() => alert('İşlem kaydedilemedi!'));
  };
})();
</script>

<!-- Ödeme Ekle Modal (fatura satırından hızlı tahsilat) -->
<div class="modal fade" id="odemeEkleModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none; border-radius:4px;">
      <div class="modal-header" style="background:#5cb85c; color:#fff; border-bottom:none;">
        <h5 class="modal-title" id="oeBaslik" style="font-size:15px; font-weight:600;">Tahsilat</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:20px;">
        <input type="hidden" id="oeCariId">
        <div class="mb-3">
          <label style="font-weight:600; font-size:13px; color:var(--text2);">Kasa / Hesap</label>
          <select id="oeKasaId" class="form-select" style="font-size:13px;">
            <option value="">Seçiniz</option>
            <?php foreach (($kasaHesaplar ?? []) as $kh): ?>
              <option value="<?= (int)$kh['id'] ?>"><?= htmlspecialchars($kh['hesap_adi'] . ' (' . $kh['para_birimi'] . ')') ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label style="font-weight:600; font-size:13px; color:var(--text2);">Ödeme Yöntemi</label>
          <select id="oeOdemeYontemi" class="form-select" style="font-size:13px;">
            <option value="">Seçiniz</option>
            <?php foreach (['Nakit', 'Havale/EFT', 'Kredi Kartı', 'Çek', 'Senet', 'Virman'] as $oy): ?>
              <option value="<?= $oy ?>"><?= $oy ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="row mb-3">
          <div class="col-md-6">
            <label style="font-weight:600; font-size:13px; color:var(--text2);">Tarih</label>
            <input type="date" id="oeTarih" class="form-control" value="<?= date('Y-m-d') ?>" style="font-size:13px;">
          </div>
          <div class="col-md-6">
            <label style="font-weight:600; font-size:13px; color:var(--text2);">Saat</label>
            <input type="time" id="oeSaat" class="form-control" value="<?= date('H:i') ?>" style="font-size:13px;">
          </div>
        </div>
        <div class="mb-3">
          <label style="font-weight:600; font-size:13px; color:var(--text2);">Tutar</label>
          <div class="input-group">
            <input type="text" id="oeTutar" class="form-control" placeholder="0,00" style="font-size:13px;">
            <span class="input-group-text">TL</span>
          </div>
          <div style="font-size:11px; color:var(--muted);">Bu faturanın kalan tutarıyla önceden dolduruldu, değiştirebilirsiniz.</div>
        </div>
        <div class="mb-3">
          <label style="font-weight:600; font-size:13px; color:var(--text2);">Açıklama</label>
          <textarea id="oeAciklama" class="form-control" rows="2" style="font-size:13px;"></textarea>
        </div>
      </div>
      <div class="modal-footer" style="justify-content:flex-end;">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
        <button type="button" class="btn btn-success" onclick="odemeKaydet()">Kaydet</button>
      </div>
    </div>
  </div>
</div>

<!-- Yeni Müşteri Modal -->
<div class="modal fade" id="yeniMusteriModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none; border-radius:4px;">
      <div class="modal-header" style="background:#58d68d; color:#fff; border-bottom:none;">
        <h5 class="modal-title" style="font-size:16px; font-weight:600;">Yeni Müşteri Ekle</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:20px;">
        <div style="background:rgba(243,156,18,.15); color:var(--warning); padding:10px; border:1px solid rgba(243,156,18,.28); border-radius:4px; font-size:13px; margin-bottom:16px;">
          Önceden müşteri kaydı olmayan carilerinizi hızlıca buradan ekleyebilirsiniz. Bu ekranda yer almayan diğer detaylı bilgileri daha sonra müşteriler sayfasından güncelleyebilirsiniz.
        </div>
        <form id="formHizliMusteri">
            <input type="hidden" name="tip" value="musteri">
            <div class="mb-3">
                <label style="font-weight:600; font-size:13px; color:var(--text2);">İsim / Unvan</label>
                <input type="text" class="form-control" name="unvan" required style="font-size:13px;">
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label style="font-weight:600; font-size:13px; color:var(--text2);">Telefon</label>
                    <input type="text" class="form-control" name="telefon" style="font-size:13px;" placeholder="(___) ___ __ __">
                </div>
                <div class="col-md-6">
                    <label style="font-weight:600; font-size:13px; color:var(--text2);">Para Birimi</label>
                    <select class="form-select" name="para_birimi" style="font-size:13px;">
                        <option value="TRY">TL</option>
                        <option value="USD">USD</option>
                        <option value="EUR">EUR</option>
                    </select>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label style="font-weight:600; font-size:13px; color:var(--text2);">Vergi Dairesi</label>
                    <input type="text" class="form-control" name="vergi_dairesi" style="font-size:13px;">
                </div>
                <div class="col-md-6">
                    <label style="font-weight:600; font-size:13px; color:var(--text2);">Vergi / TC Kimlik No</label>
                    <input type="text" class="form-control" name="vergi_no" style="font-size:13px;">
                </div>
            </div>
            <div class="mb-3">
                <label style="font-weight:600; font-size:13px; color:var(--text2);">E-Posta</label>
                <input type="email" class="form-control" name="eposta" style="font-size:13px;">
                <div style="font-size:11px; color:var(--muted);">virgül ile ayırarak birden fazla adres girebilirsiniz.</div>
            </div>
            <div class="mb-3">
                <label style="font-weight:600; font-size:13px; color:var(--text2);">Adres</label>
                <textarea class="form-control" name="adres" rows="3" style="font-size:13px;"></textarea>
            </div>
        </form>
      </div>
      <div class="modal-footer" style="justify-content:flex-end;">
        <button type="button" class="btn btn-warning" data-bs-dismiss="modal" style="color:#fff; border:none; background:#f0ad4e;"><i class="fa-solid fa-times"></i> Vazgeç</button>
        <button type="button" class="btn btn-success" id="btnHizliMusteriKaydet" style="border:none; background:#5cb85c;"><i class="fa-solid fa-check"></i> Devam Et</button>
      </div>
    </div>
  </div>
</div>

<!-- Kayıtlı Müşteri Seçimi Modal -->
<div class="modal fade" id="kayitliMusteriModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none; border-radius:4px; overflow:hidden;">
      <div class="modal-header" style="background:#5bd49f; color:#fff; border-bottom:none; padding:12px 20px;">
        <h5 class="modal-title" style="font-size:16px; font-weight:400;">Müşteri Arama</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div style="background:rgba(243,156,18,.13); padding:10px 20px; font-size:13px; color:var(--warning); border-bottom:1px solid rgba(243,156,18,.30);">
        Satış yapacağınız müşteri ya da tedarikçiyi bulun
      </div>
      <div class="modal-body" style="padding:20px; background:var(--surface-2);">
         <input type="text" class="form-control" id="musteriSearchInput" placeholder="Arama yapın..." autocomplete="off" style="margin-bottom:10px; font-size:13px;">
         <div id="musteriListesi" style="background:var(--card-bg); border:1px solid var(--border); max-height:300px; overflow-y:auto; font-size:13px; color:var(--text);">
            <div style="padding:10px; text-align:center; color:var(--muted);"><i class="fa-solid fa-spinner fa-spin"></i> Yükleniyor...</div>
         </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Hizli Musteri Kaydet
    const btnHizli = document.getElementById('btnHizliMusteriKaydet');
    if (btnHizli) {
        btnHizli.addEventListener('click', async function() {
            const form = document.getElementById('formHizliMusteri');
            if(!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            const formData = new FormData(form);
            try {
                btnHizli.disabled = true;
                btnHizli.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Bekleyin...';
                
                const response = await fetch('<?= BASE_URL ?>/musteri/hizli_ekle', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();
                if(res.status === 'success') {
                    window.location.href = '<?= BASE_URL ?>/satis/ekle?cari_id=' + res.id;
                } else {
                    alert('Hata: ' + (res.message || 'Bilinmeyen hata'));
                    btnHizli.disabled = false;
                    btnHizli.innerHTML = '<i class="fa-solid fa-check"></i> Devam Et';
                }
            } catch (err) {
                console.error(err);
                alert('Ağ hatası!');
                btnHizli.disabled = false;
                btnHizli.innerHTML = '<i class="fa-solid fa-check"></i> Devam Et';
            }
        });
    }

    // 2. Kayitli Musteri Secimi
    const modalEl = document.getElementById('kayitliMusteriModal');
    const mSearch = document.getElementById('musteriSearchInput');
    const mList = document.getElementById('musteriListesi');
    let allMusteriler = [];

    if (modalEl) {
        modalEl.addEventListener('show.bs.modal', async function() {
            mSearch.value = '';
            mList.innerHTML = '<div style="padding:10px; text-align:center; color:var(--muted);"><i class="fa-solid fa-spinner fa-spin"></i> Yükleniyor...</div>';
            try {
                // To list all customers, we can query with an empty string or 'all' if supported.
                // Assuming musteriBul returns top 50 if q is empty or at least a list.
                // Let's create an endpoint or just fetch all. I will update `satis/musteriBul` to return all if no q.
                const response = await fetch('<?= BASE_URL ?>/satis/musteriBul?q=all');
                allMusteriler = await response.json();
                renderList(allMusteriler);
            } catch(e) {
                mList.innerHTML = '<div style="padding:10px; color:red;">Yükleme hatası!</div>';
            }
        });

        mSearch.addEventListener('input', function() {
            const val = this.value.trim().toLowerCase();
            if(!val) {
                renderList(allMusteriler);
            } else {
                const filtered = allMusteriler.filter(m => m.unvan.toLowerCase().includes(val));
                renderList(filtered);
            }
        });
        
        function renderList(list) {
            mList.innerHTML = '';
            if(list.length === 0) {
                mList.innerHTML = '<div style="padding:10px; color:var(--muted);">Sonuç bulunamadı.</div>';
                return;
            }
            list.forEach(item => {
                const div = document.createElement('div');
                div.className = 'musteri-item';
                div.innerHTML = item.unvan;
                div.addEventListener('click', () => {
                    window.location.href = '<?= BASE_URL ?>/satis/ekle?cari_id=' + item.id;
                });
                mList.appendChild(div);
            });
        }
    }
});
</script>
