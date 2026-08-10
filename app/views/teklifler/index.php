<?php
/**
 * View: teklifler/index.php
 * Beklenen değişkenler:
 *   $teklifler   array — Fatura::listele('proforma', ...) sonucu
 *   $toplam      int
 *   $arama       string
 *   $sayfa       int
 *   $sayfaSayisi int
 */
$fmt    = fn($n) => number_format((float)$n, 2, ',', '.') . ' ₺';
$fmtTar = fn($d) => $d ? date('d.m.Y', strtotime($d)) : '—';
$durumRenk = [
    'taslak'    => ['#94a3b8', '#fff', 'Taslak'],
    'onaylandi' => ['#3b82f6', '#fff', 'Onaylandı'],
    'iptal'     => ['#64748b', '#fff', 'İptal'],
];
?>
<style>
/* YENI EKLENEN HEAD STYLLERI */
/* ════════════════════════════════════
       ACTION BUTTONS (screenshot'taki butonlar)
    ════════════════════════════════════ */
    .action-btns { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
    .btn-action {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 7px 16px; border: none; border-radius: 5px;
      font-size: 13px; font-weight: 600; cursor: pointer;
      transition: filter .15s, box-shadow .15s; text-decoration: none; white-space: nowrap;
    }
    .btn-action:hover { filter: brightness(1.12); box-shadow: 0 3px 10px rgba(0,0,0,.18); }
    .btn-perakende { background: #27ae60; color: #fff; }
    .btn-yeni-must { background: #16a085; color: #fff; }
    .btn-kayitli   { background: #c0392b; color: #fff; }
    .btn-serbest   { background: #e67e22; color: #fff; }

    /* ════════════════════════════════════
       FILTER PANEL (beyaz kart)
    ════════════════════════════════════ */
    .filter-panel {
      background: var(--card-bg);
      border: 1px solid var(--border);
      border-radius: 8px 8px 0 0;
      border-bottom: none;
    }
    .filter-row {
      display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
      padding: 10px 14px;
    }
    .filter-row + .filter-row {
      border-top: 1px solid var(--border);
    }

    /* Belge Tipi custom dropdown */
    .belge-tipi-wrap { position: relative; }
    .belge-tipi-btn {
      display: flex; align-items: center; justify-content: space-between; gap: 8px;
      padding: 6px 12px; border: 1px solid var(--border2); border-radius: 6px;
      background: var(--card-bg); font-size: 13px; color: var(--text2); cursor: pointer;
      min-width: 170px; transition: border-color .15s;
    }
    .belge-tipi-btn:hover, .belge-tipi-btn.open { border-color: var(--muted); }
    .belge-tipi-btn i { font-size: 10px; color: var(--muted); transition: transform .2s; }
    .belge-tipi-btn.open i { transform: rotate(180deg); }
    .belge-dropdown {
      position: absolute; top: calc(100% + 3px); left: 0; z-index: 300;
      background: var(--ink); border: 1px solid var(--border2); border-radius: 6px;
      box-shadow: 0 6px 20px rgba(0,0,0,.1); min-width: 210px;
      display: none; padding: 5px 0;
    }
    .belge-dropdown.open { display: block; }
    .belge-chk-item {
      display: flex; align-items: center; gap: 8px;
      padding: 7px 14px; font-size: 13px; color: var(--text2); cursor: pointer;
    }
    .belge-chk-item:hover { background: var(--surface-2); }
    .belge-chk-item.indent { padding-left: 30px; }
    .belge-chk-item input[type="checkbox"] { width: 14px; height: 14px; accent-color: var(--text); cursor: pointer; flex-shrink: 0; }

    /* Son 1 Ay dropdown */
    .period-wrap { position: relative; }
    .btn-period {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 6px 14px; background: #334155; color: var(--text);
      border: none; border-radius: 6px; font-size: 13px; font-weight: 500;
      cursor: pointer; transition: background .15s;
    }
    .btn-period:hover { background: #1e293b; }
    .btn-period i { font-size: 9px; }
    .period-dropdown {
      position: absolute; top: calc(100% + 3px); left: 0; z-index: 300;
      background: var(--ink); border: 1px solid var(--border2); border-radius: 6px;
      box-shadow: 0 6px 20px rgba(0,0,0,.1); min-width: 160px;
      display: none; padding: 4px 0;
    }
    .period-dropdown.open { display: block; }
    .period-item { padding: 7px 16px; font-size: 13px; color: var(--text2); cursor: pointer; }
    .period-item:hover { background: var(--surface-2); }
    .period-item.active { font-weight: 700; color: var(--text); background: var(--surface-2); }

    /* İptalleri toggle */
    .iptalli-wrap { display: flex; align-items: center; gap: 7px; margin-left: auto; font-size: 13px; color: var(--text2); font-weight: 500; white-space: nowrap; }
    .toggle-switch { position: relative; width: 38px; height: 21px; flex-shrink: 0; }
    .toggle-switch input { opacity: 0; width: 0; height: 0; }
    .toggle-slider { position: absolute; inset: 0; background: #cbd5e1; border-radius: 21px; cursor: pointer; transition: background .2s; }
    .toggle-slider::before { content: ''; position: absolute; width: 15px; height: 15px; left: 3px; top: 3px; background: var(--card-bg); border-radius: 50%; transition: transform .2s; box-shadow: 0 1px 3px rgba(0,0,0,.2); }
    .toggle-switch input:checked + .toggle-slider { background: #22c55e; }
    .toggle-switch input:checked + .toggle-slider::before { transform: translateX(17px); }

    /* Arama satırı */
    .search-label { font-size: 13px; color: var(--text2); font-weight: 600; white-space: nowrap; margin-left: auto; }
    .search-type-select {
      padding: 6px 10px; border: 1px solid var(--border2); border-radius: 6px;
      font-size: 13px; color: var(--text2); background: var(--card-bg); cursor: pointer; outline: none;
    }
    .search-type-select:focus { border-color: #4ade80; }
    .search-input-wrap { position: relative; }
    .search-input-wrap i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: 12px; pointer-events: none; }
    .search-txt {
      padding: 6px 12px 6px 30px; border: 1px solid var(--border2); border-radius: 6px;
      font-size: 13px; color: var(--text2); background: var(--card-bg); outline: none;
      width: 260px; transition: border-color .2s, box-shadow .2s;
    }
    .search-txt:focus { border-color: #4ade80; box-shadow: 0 0 0 3px rgba(74,222,128,.12); }
    .search-txt::placeholder { color: var(--muted); font-style: italic; }

    /* ════════════════════════════════════
       TABLE
    ════════════════════════════════════ */
    .table-card {
      background: var(--card-bg);
      border: 1px solid var(--border);
      border-radius: 0 0 8px 8px;
      overflow: hidden;
    }
    .sales-table { width: 100%; border-collapse: collapse; }
    .sales-table thead tr { background: #1e293b; }
    .sales-table thead th {
      padding: 11px 14px; font-size: 12.5px; font-weight: 600;
      color: var(--muted); white-space: nowrap;
      border-right: 1px solid rgba(255,255,255,.06);
    }
    .sales-table thead th:last-child { border-right: none; }
    .th-sort { cursor: pointer; display: inline-flex; align-items: center; gap: 4px; user-select: none; }
    .th-sort:hover { color: var(--text); }
    .sort-ic { font-size: 10px; color: var(--text2); }

    /* Data rows */
    .sales-table tbody tr.data-row { border-bottom: 1px solid var(--border); transition: background .12s; }
    .sales-table tbody tr.data-row:hover { background: var(--surface-2); }
    .sales-table tbody tr.data-row.expanded { background: rgba(59,130,246,.15); }
    .sales-table tbody tr.data-row:last-child { border-bottom: none; }
    .sales-table tbody td { padding: 9px 14px; font-size: 13px; color: var(--text2); vertical-align: middle; }

    /* Toggle button */
    .td-toggle { width: 40px; text-align: center; }
    .row-toggle {
      width: 22px; height: 22px; border-radius: 50%;
      border: 2px solid; display: inline-flex; align-items: center; justify-content: center;
      font-size: 15px; font-weight: 700; cursor: pointer; transition: background .12s; line-height: 1;
      background: transparent;
    }
    .row-toggle.plus  { border-color: #22c55e; color: #22c55e; }
    .row-toggle.minus { border-color: #ef4444; color: #ef4444; }
    .row-toggle.plus:hover  { background: rgba(34,197,94,.08); }
    .row-toggle.minus:hover { background: rgba(239,68,68,.08); }

    /* Customer link */
    .cust-link { color: var(--info); text-decoration: none; font-weight: 500; font-size: 13px; }
    .cust-link:hover { text-decoration: underline; }

    /* Amount */
    .td-amount { text-align: right; font-weight: 600; font-size: 13px; color: var(--text); }

    /* Status badges */
    .status-badge { display: inline-block; padding: 3px 10px; border-radius: 4px; font-size: 12px; font-weight: 600; white-space: nowrap; }
    .status-faturalasms { background: #22c55e; color: #fff; }
    .status-bekliyor    { background: #f59e0b; color: #fff; }
    .status-siparis     { background: #3b82f6; color: #fff; }
    .status-iptal       { background: #94a3b8; color: #fff; }

    /* Detail row */
    .detail-row { display: none; }
    .detail-row.open { display: table-row; }
    .detail-row > td { padding: 0; border-bottom: 2px solid var(--border); background: var(--surface-2); }
    .detail-inner { padding: 10px 14px 14px 54px; }

    .detail-btns { display: flex; gap: 6px; margin-bottom: 12px; flex-wrap: wrap; }
    .btn-det {
      display: inline-flex; align-items: center; gap: 5px;
      padding: 5px 13px; border: none; border-radius: 5px;
      font-size: 12.5px; font-weight: 600; cursor: pointer; transition: filter .15s;
    }
    .btn-det:hover { filter: brightness(1.12); }
    .btn-det.red   { background: #ef4444; color: #fff; }
    .btn-det.green { background: #22c55e; color: #fff; }
    .btn-det.blue  { background: #3b82f6; color: #fff; }

    .detail-prod-table { border-collapse: collapse; min-width: 500px; max-width: 680px; }
    .detail-prod-table thead th { padding: 5px 10px; font-size: 12px; font-weight: 700; color: var(--muted); border-bottom: 2px solid var(--border); text-align: left; }
    .detail-prod-table tbody td { padding: 6px 10px; font-size: 13px; color: var(--text2); border-bottom: 1px solid var(--border); }
    .detail-prod-table tbody tr:last-child td { border-bottom: none; }
    .prod-thumb { width: 34px; height: 34px; background: var(--surface-2); border-radius: 5px; display: flex; align-items: center; justify-content: center; color: var(--muted); font-size: 14px; }
    .td-num { text-align: right; }
    .detail-user-line { margin-top: 10px; font-size: 12px; color: var(--muted); }
    .detail-user-line strong { color: var(--text2); }

    /* Empty state */
    .empty-state { text-align: center; padding: 48px 16px; color: var(--muted); font-size: 14px; }
    .empty-state i { font-size: 32px; display: block; margin-bottom: 10px; }

    /* Pagination */
    .pag-bar { display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-top: 1px solid var(--border); background: var(--surface-2); }
    .pag-info { font-size: 12.5px; color: var(--muted); }
    .pag-btns { display: flex; gap: 3px; }
    .pag-btn { width: 30px; height: 30px; border: 1px solid var(--border); border-radius: 6px; background: var(--card-bg); font-size: 12px; color: var(--text2); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background .12s, border-color .12s; }
    .pag-btn:hover { background: var(--surface-2); }
    .pag-btn.active { background: #1e293b; border-color: var(--text); color: #4ade80; font-weight: 700; }
    .pag-btn:disabled { opacity: .35; cursor: default; }

    /* Toast */
    .toast-container { position: fixed; bottom: 22px; right: 22px; z-index: 9999; display: flex; flex-direction: column; gap: 7px; }
    .toast-msg { display: flex; align-items: center; gap: 9px; padding: 11px 16px; border-radius: 8px; font-size: 13px; font-weight: 500; box-shadow: 0 3px 14px rgba(0,0,0,.13); }
    .toast-msg.success { background: var(--ink); border: 1px solid var(--border2); border-left: 4px solid #22c55e; }
    .toast-msg.error   { background: var(--ink); border: 1px solid var(--border2); border-left: 4px solid #ef4444; }
    .toast-msg.info    { background: var(--ink); border: 1px solid var(--border2); border-left: 4px solid #3b82f6; }

    
    

    /* ══ YENİ MÜŞTERİ MODAL ══ */
    .ym-overlay {
      position: fixed; inset: 0; background: rgba(0,0,0,.55);
      z-index: 800; display: none; align-items: center; justify-content: center;
    }
    .ym-overlay.open { display: flex; }
    .ym-modal {
      background: var(--ink); border: 1px solid var(--border2); border-radius: 6px; width: 520px; max-width: 96vw;
      box-shadow: 0 8px 40px rgba(0,0,0,.25); overflow: hidden;
      display: flex; flex-direction: column; max-height: 94vh;
    }
    .ym-header {
      background: #6ad0a5; color: #fff; padding: 14px 18px;
      font-size: 15px; font-weight: 700;
      display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;
    }
    .ym-close { background: none; border: none; color: #fff; font-size: 20px; cursor: pointer; line-height: 1; opacity: .85; }
    .ym-close:hover { opacity: 1; }
    .ym-body { padding: 18px 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px; }
    .ym-info { background: rgba(243,156,18,.13); border: 1px solid rgba(243,156,18,.30); border-radius: 5px; padding: 10px 14px; font-size: 13px; color: var(--warning); line-height: 1.55; }
    .ym-info strong { color: var(--warning); }
    .ym-group { display: flex; flex-direction: column; gap: 4px; }
    .ym-group label { font-size: 12.5px; font-weight: 600; color: var(--text2); }
    .ym-input, .ym-select, .ym-textarea {
      padding: 7px 10px; border: 1px solid var(--border2); border-radius: 5px;
      font-size: 13px; color: var(--text); outline: none; background: var(--card-bg);
      transition: border-color .2s; width: 100%;
    }
    .ym-input:focus, .ym-select:focus, .ym-textarea:focus { border-color: #2ecc71; box-shadow: 0 0 0 3px rgba(46,204,113,.12); }
    .ym-textarea { resize: vertical; min-height: 70px; }
    .ym-select { cursor: pointer; }
    .ym-hint { font-size: 11.5px; color: var(--muted); margin-top: 2px; }
    .ym-row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .ym-footer { padding: 12px 20px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 8px; flex-shrink: 0; }
    .ym-btn-vazgec { display: inline-flex; align-items: center; gap: 5px; padding: 8px 18px; background: #e67e22; color: #fff; border: none; border-radius: 5px; font-size: 13px; font-weight: 600; cursor: pointer; transition: filter .15s; }
    .ym-btn-vazgec:hover { filter: brightness(1.1); }
    .ym-btn-devam  { display: inline-flex; align-items: center; gap: 5px; padding: 8px 18px; background: #27ae60; color: #fff; border: none; border-radius: 5px; font-size: 13px; font-weight: 600; cursor: pointer; transition: filter .15s; }
    .ym-btn-devam:hover { filter: brightness(1.1); }
    
    
  
  
  .nav-link.active:focus { color: #4ade80; outline: none; }
</style>

<!-- ── Üst Aksiyon Çubuğu ── -->
    <div class="action-btns" style="display:flex; gap:10px; align-items:center; margin-bottom: 16px; flex-wrap:wrap;">
      <a href="<?= BASE_URL ?>/satis/ekle" class="btn-action" style="background:#5bc0de; color:#fff; padding:7px 12px; border-radius:3px; font-weight:600; font-size:12.5px; display:inline-flex; align-items:center; gap:6px;">
        <i class="fa-solid fa-plus"></i> Yeni Teklif Hazırla
      </a>
      <form method="GET" action="<?= BASE_URL ?>/teklif" style="margin-left:auto; display:flex; gap:6px;">
        <input type="text" name="ara" value="<?= htmlspecialchars($arama) ?>" placeholder="Müşteri veya belge no ara…" class="search-txt">
        <button type="submit" class="btn-action" style="background:#334155;">
          <i class="fa-solid fa-magnifying-glass"></i>
        </button>
      </form>
    </div>

    <?php if (empty($teklifler)): ?>
    <!-- Boş durum bilgi kutusu -->
    <div style="background-color: rgba(243,156,18,.15); border: 1px solid rgba(243,156,18,.28); color: var(--warning); padding: 15px 20px; border-radius: 4px; font-size: 13px; line-height: 1.6; margin-top: 10px;">
      <?= $arama !== '' ? 'Aramanızla eşleşen teklif bulunamadı.' : 'Hiç teklif kaydetmemişsiniz.' ?> Yukarıdaki <span style="color:#5cb85c; font-weight:600;">Yeni Teklif Hazırla</span> düğmesine tıklayarak müşterilerinize teklif hazırlayabilir, "Proforma Kaydet" ile kaydedip dilediğinizde gerçek satış faturasına dönüştürebilirsiniz.
    </div>
    <?php else: ?>
    <div class="table-card">
      <table class="sales-table">
        <thead>
          <tr>
            <th style="width:40px;"></th>
            <th>Tarih</th>
            <th>Müşteri</th>
            <th>Belge No</th>
            <th style="text-align:right;">Tutar</th>
            <th>Durum</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($teklifler as $t):
            $dr = $durumRenk[$t['durum']] ?? ['#94a3b8', '#fff', $t['durum']];
          ?>
            <tr class="data-row" id="row-<?= $t['id'] ?>">
              <td class="td-toggle">
                <button type="button" class="row-toggle plus" onclick="toggleDetail(<?= $t['id'] ?>, this)">+</button>
              </td>
              <td><?= $fmtTar($t['fatura_tarihi']) ?></td>
              <td>
                <?php if ($t['cari_unvan']): ?>
                  <a href="<?= BASE_URL ?>/musteri/detay/<?= (int)$t['cari_id'] ?>" class="cust-link"><?= htmlspecialchars($t['cari_unvan']) ?></a>
                <?php else: ?>
                  <span style="color:var(--muted);">—</span>
                <?php endif; ?>
              </td>
              <td><strong><?= htmlspecialchars($t['fatura_no']) ?></strong></td>
              <td class="td-amount"><?= $fmt($t['genel_toplam']) ?></td>
              <td><span class="status-badge" style="background:<?= $dr[0] ?>;color:<?= $dr[1] ?>;"><?= $dr[2] ?></span></td>
            </tr>
            <tr class="detail-row" id="detail-<?= $t['id'] ?>">
              <td colspan="6">
                <div class="detail-inner">
                  <div class="detail-btns">
                    <a href="<?= BASE_URL ?>/satis/duzenle/<?= $t['id'] ?>" class="btn-det blue"><i class="fa-solid fa-pen"></i> Görüntüle / Düzenle</a>
                    <a href="<?= BASE_URL ?>/satis/fatura/<?= $t['id'] ?>/print" target="_blank" rel="noopener" class="btn-det" style="background:#efa341;"><i class="fa-solid fa-print"></i> Yazdır</a>
                    <?php if ($t['durum'] !== 'iptal'): ?>
                      <a href="<?= BASE_URL ?>/satis/iptal/<?= $t['id'] ?>" class="btn-det red" onclick="return confirm('Teklif iptal edilsin mi?')"><i class="fa-solid fa-ban"></i> İptal Et</a>
                    <?php endif; ?>
                  </div>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php if ($sayfaSayisi > 1): ?>
        <div class="pag-bar">
          <span class="pag-info"><?= number_format($toplam) ?> kayıt</span>
          <div class="pag-btns">
            <?php for ($p = 1; $p <= $sayfaSayisi; $p++): ?>
              <a class="pag-btn <?= $p === $sayfa ? 'active' : '' ?>" href="?<?= http_build_query(['ara' => $arama, 'sayfa' => $p]) ?>"><?= $p ?></a>
            <?php endfor; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

<script>
  /* Sidebar accordion */
  document.querySelectorAll('.submenu').forEach(sub => {
    const link = document.querySelector(`[data-bs-target="#${sub.id}"]`);
    if (!link) return;
    sub.addEventListener('show.bs.collapse', () => {
      link.setAttribute('aria-expanded', 'true');
      document.querySelectorAll('.submenu.show').forEach(o => {
        if (o.id !== sub.id) bootstrap.Collapse.getInstance(o)?.hide();
      });
    });
    sub.addEventListener('hide.bs.collapse', () => link.setAttribute('aria-expanded', 'false'));
  });

  /* Satır expand/collapse */
  window.toggleDetail = function (id, btn) {
    const detailRow = document.getElementById('detail-' + id);
    const mainRow   = document.getElementById('row-' + id);
    if (!detailRow) return;
    const isOpen = detailRow.classList.toggle('open');
    mainRow.classList.toggle('expanded', isOpen);
    btn.textContent = isOpen ? '−' : '+';
    btn.classList.toggle('plus', !isOpen);
    btn.classList.toggle('minus', isOpen);
  };

  // Close profile dropdown when clicking outside
  document.addEventListener('click', function(e) {
    const profileDropdown = document.getElementById('profileDropdown');
    const userDropdownBtn = document.getElementById('userDropdownBtn');
    if (profileDropdown && userDropdownBtn && !profileDropdown.contains(e.target) && !userDropdownBtn.contains(e.target)) {
      profileDropdown.classList.remove('show');
    }
  });
</script>
