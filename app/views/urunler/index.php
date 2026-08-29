<?php
/**
 * View: urunler/index.php
 */
$fmt = fn($n) => number_format((float)$n, 2, ',', '.'); // without TL sign

$qStr = fn(array $extra = []) => http_build_query(array_filter(array_merge(
    ['ara' => $arama, 'tip' => $tipFlt, 'kategori' => $katFlt, 'marka' => $markaFlt, 'sayfa' => $sayfa],
    $extra
), fn($v) => $v !== '' && $v !== null));
?>
<style>
  /* ── Action Bar ── */
  .action-bar { display:flex; align-items:center; gap:8px; margin-bottom:15px; flex-wrap:wrap; }
  .btn-add-wrap { position:relative; display:inline-flex; }
  .btn-add-main { background:#5cb85c; color:#fff; border:none; padding:7px 12px; border-radius:4px 0 0 4px; font-size:12.5px; font-weight:600; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }
  .btn-add-caret { background:#4cae4c; color:#fff; border:none; padding:7px 8px; border-radius:0 4px 4px 0; cursor:pointer; font-size:11px; }
  .btn-add-main:hover, .btn-add-caret:hover { filter:brightness(1.1); }

  /* Dropdown */
  .dropdown-menu-custom { position:absolute; top:calc(100% + 2px); left:0; background:var(--ink); border:1px solid var(--border2); border-radius:4px; box-shadow:0 6px 12px rgba(0,0,0,.175); min-width:180px; z-index:300; display:none; }
  .dropdown-menu-custom.open { display:block; }
  .dd-item { display:block; padding:8px 16px; font-size:13px; color:var(--text); text-decoration:none; cursor:pointer; }
  .dd-item:hover { background:var(--surface-2); color:var(--text); }
  /* "Mevcut üründen kopyala" arama sonucu satırları */
  .copy-result-item { padding:10px 15px; border-bottom:1px solid var(--border); cursor:pointer; font-size:13px; color:var(--text); }
  .copy-result-item:last-child { border-bottom:none; }
  .copy-result-item:hover { background:var(--surface-2); }

  /* ── Filter Bar ── */
  .filter-bar { display:flex; align-items:center; gap:10px; margin-bottom:10px; flex-wrap:wrap; border-bottom: 1px solid var(--border); padding-bottom: 15px; }
  .filter-tabs { display:flex; border:1px solid var(--border); border-radius:4px; overflow:hidden; }
  .filter-tab-btn { padding:6px 12px; font-size:13px; color:var(--text2); background:var(--card-bg); border:none; cursor:pointer; text-decoration:none; display:inline-block; }
  .filter-tab-btn:not(:last-child) { border-right:1px solid var(--border); }
  .filter-tab-btn.active { background:var(--surface-2); box-shadow:inset 0 3px 5px rgba(0,0,0,.125); }
  
  .filter-select { padding:6px 10px; border:1px solid var(--border); border-radius:4px; font-size:13px; color:var(--text2); background:var(--card-bg); outline:none; min-width:140px; }
  
  .search-wrap { margin-left:auto; display:flex; align-items:center; gap:6px; }
  .search-label { font-size:13px; font-weight:600; color:var(--text); }
  .search-input { padding:5px 10px; border:1px solid var(--border); border-radius:3px; font-size:13px; width:200px; outline:none; }
  .search-input:focus { border-color:#66afe9; box-shadow:inset 0 1px 1px rgba(0,0,0,.075),0 0 8px rgba(102,175,233,.6); }

  /* ── Table ── */
  .table-card { background:var(--card-bg); border:1px solid var(--border); border-radius:4px; overflow-x:auto; }
  .products-table { width:100%; border-collapse:collapse; min-width:700px; }
  .products-table thead tr { background:#3a4a5a; color:#fff; font-size:12px; font-weight:600; }
  .products-table thead th { padding:10px 12px; border-right:1px solid #4bc0a5; text-align:left; }
  .products-table thead th:last-child { border-right:none; }
  
  .products-table tbody tr { border-bottom:2px solid var(--border2); }
  .products-table tbody td { padding:0; vertical-align:middle; font-size:13px; color:var(--text2); border-right:1px solid var(--border); }
  
  /* İlk hücre: Cyan arka plan */
  .td-name-cell { background:#5bc0de; color:#fff; padding:8px 12px; display:flex; align-items:center; gap:8px; min-height:36px; position:relative; }
  .td-name-cell a { color:#fff; text-decoration:none; font-weight:600; }
  .td-name-cell a:hover { text-decoration:underline; }
  
  .badge-ticari { background:#286090; color:#fff; font-size:9px; padding:2px 6px; border-radius:2px; font-weight:700; text-transform:uppercase; }
  .badge-brand { background:#f0ad4e; color:#3d2a06; font-size:9px; padding:2px 6px; border-radius:2px; font-weight:700; text-transform:uppercase; }
  .badge-tag { background:#d9534f; color:#fff; font-size:9px; padding:2px 6px; border-radius:2px; font-weight:700; text-transform:uppercase; position:absolute; right:10px; }
  
  .td-price-cell { padding:8px 12px; text-align:right; font-weight:600; background:var(--card-bg); color:var(--text2); }
  .td-stock-cell { padding:8px 12px; text-align:right; background:var(--card-bg); color:var(--text2); }
  
  .row-actions { display:none; gap:5px; position:absolute; right:50px; }
  .products-table tbody tr:hover .row-actions { display:flex; }
  .btn-edit-row { background:var(--card-bg); color:#5bc0de; border:none; padding:3px 6px; border-radius:3px; cursor:pointer; font-size:12px; text-decoration:none; }
  .btn-edit-row:hover { background:var(--surface-2); }
  .btn-del-row { background:#d9534f; color:#fff; border:none; padding:3px 6px; border-radius:3px; cursor:pointer; font-size:12px; text-decoration:none; }
  .btn-del-row:hover { filter:brightness(1.1); }

  .table-empty { padding:30px; text-align:center; color:var(--muted); background:var(--card-bg); }
  
  /* Pagination */
  .pag-bar { margin-top:15px; display:flex; align-items:center; justify-content:space-between; font-size:13px; color:var(--text2); }
  .pagination { display:flex; list-style:none; padding:0; margin:0; border:1px solid var(--border); border-radius:4px; overflow:hidden; }
  .page-item { border-right:1px solid var(--border); }
  .page-item:last-child { border-right:none; }
  .page-link { display:block; padding:6px 12px; text-decoration:none; color:var(--info); background:var(--card-bg); }
  .page-item.active .page-link { background:#337ab7; color:#fff; }
  .page-link:hover:not(.active) { background:var(--surface-2); }
</style>

<!-- Action Bar -->
<div class="action-bar">
  <?php if (Rbac::currentUserCan('URUN_CREATE')): ?>
  <div class="btn-add-wrap">
    <a href="<?= BASE_URL ?>/urun/ekle" class="btn-add-main">
      <i class="fa-solid fa-plus"></i> Yeni Ürün/Hizmet Ekle
    </a>
    <button class="btn-add-caret" id="btnAddCaret">
      <i class="fa-solid fa-chevron-down"></i>
    </button>
    <div class="dropdown-menu-custom" id="addDropdown">
      <a href="<?= BASE_URL ?>/urun/ekle" class="dd-item">Yeni ürün kaydı yap</a>
      <a href="javascript:void(0)" class="dd-item" onclick="openCopyModal()">Mevcut bir üründen kopyala</a>
    </div>
  </div>
  <?php endif; ?>

  <!-- Mevcut Üründen Kopyala Modalı -->
  <div class="modal-overlay" id="copyProductModal" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); display:none; align-items:center; justify-content:center; z-index:9999;">
    <div class="modal-box" style="background:var(--ink); border:1px solid var(--border2); width:500px; border-radius:8px; box-shadow:0 5px 15px rgba(0,0,0,0.3); overflow:hidden;">
      <div class="modal-header" style="padding:15px; background:var(--surface-2); border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
        <span style="font-weight:700; font-size:15px; color:var(--text);">Mevcut Üründen Kopyala</span>
        <button type="button" onclick="closeCopyModal()" style="border:none; background:none; font-size:20px; color:var(--muted); cursor:pointer;">&times;</button>
      </div>
      <div class="modal-body" style="padding:20px;">
        <div style="margin-bottom:15px;">
          <label style="display:block; font-size:13px; font-weight:600; color:var(--text2); margin-bottom:8px;">Kopyalanacak Ürünü Arayın (Min. 3 karakter)</label>
          <input type="text" id="copySearchInput" class="finput" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:4px;" placeholder="Ürün adı veya stok kodu..." onkeyup="searchForCopy(this.value)">
        </div>
        <div id="copySearchResults" style="max-height:300px; overflow-y:auto; border:1px solid var(--border); border-radius:4px; display:none;">
          <!-- Arama sonuçları buraya gelecek -->
        </div>
      </div>
    </div>
  </div>

  <script>
    function openCopyModal() {
        document.getElementById('copyProductModal').style.display = 'flex';
        document.getElementById('copySearchInput').focus();
    }

    function closeCopyModal() {
        document.getElementById('copyProductModal').style.display = 'none';
        document.getElementById('copySearchInput').value = '';
        document.getElementById('copySearchResults').style.display = 'none';
        document.getElementById('copySearchResults').innerHTML = '';
    }

    let searchTimer;
    function searchForCopy(q) {
        clearTimeout(searchTimer);
        if (q.length < 3) {
            document.getElementById('copySearchResults').style.display = 'none';
            return;
        }

        searchTimer = setTimeout(() => {
            fetch('<?= BASE_URL ?>/urun/searchAjax?q=' + encodeURIComponent(q))
                .then(res => res.json())
                .then(data => {
                    const resDiv = document.getElementById('copySearchResults');
                    resDiv.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(item => {
                            const div = document.createElement('div');
                            div.className = 'copy-result-item';
                            div.innerHTML = `<strong>${item.ad}</strong> <span style="color:var(--muted); font-size:11px;">(${item.stok_kodu || '-'})</span>`;
                            div.onclick = () => {
                                window.location.href = '<?= BASE_URL ?>/urun/ekle?copy_id=' + item.id;
                            };
                            resDiv.appendChild(div);
                        });
                        resDiv.style.display = 'block';
                    } else {
                        resDiv.innerHTML = '<div style="padding:15px; color:var(--muted); font-size:13px; text-align:center;">Sonuç bulunamadı.</div>';
                        resDiv.style.display = 'block';
                    }
                });
        }, 300);
    }
  </script>

</div>

<!-- Filter Bar -->
<form method="GET" action="<?= BASE_URL ?>/urun" id="filterForm">
  <div class="filter-bar">
    <div class="filter-tabs">
      <?php foreach (Urun::LISTE_TIP_SEKMELERI as $sekmeDeger => $sekmeAd): ?>
        <a href="<?= BASE_URL ?>/urun?<?= $qStr(['tip' => $sekmeDeger, 'sayfa' => 1]) ?>"
           class="filter-tab-btn <?= $tipFlt === $sekmeDeger ? 'active' : '' ?>"><?= htmlspecialchars($sekmeAd) ?></a>
      <?php endforeach; ?>
    </div>

    <select class="filter-select" name="kategori" onchange="this.form.submit()">
      <option value="">Tüm kategoriler</option>
      <?php foreach ($kategoriler as $k): ?>
        <option value="<?= htmlspecialchars($k['kategori']) ?>"
                <?= $katFlt === $k['kategori'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($k['kategori']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <select class="filter-select" name="marka" onchange="this.form.submit()">
      <option value="">Tüm markalar</option>
      <?php foreach ($markalar as $m): ?>
        <option value="<?= htmlspecialchars($m['marka']) ?>"
                <?= $markaFlt === $m['marka'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($m['marka']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <input type="hidden" name="tip" value="<?= htmlspecialchars($tipFlt) ?>">
    <input type="hidden" name="sayfa" value="1">

    <div class="search-wrap">
      <span class="search-label">Ara:</span>
      <input type="text" name="ara" class="search-input"
             placeholder="arama... (en az 3 karakter)"
             value="<?= htmlspecialchars($arama) ?>"
             oninput="if(this.value.length===0||this.value.length>=3) this.form.submit()" />
    </div>
  </div>
</form>

<!-- Table -->
<div class="table-card">
  <table class="products-table">
    <thead>
      <tr>
        <th style="width:70%;">Ürün Hizmet Adı <i class="fa-solid fa-sort" style="float:right; opacity:0.5; margin-top:2px;"></i></th>
        <th style="width:15%; text-align:center;">Satış Fiyatı <i class="fa-solid fa-sort" style="float:right; opacity:0.5; margin-top:2px;"></i></th>
        <th style="width:15%; text-align:center;">Stok Miktarı <i class="fa-solid fa-sort" style="float:right; opacity:0.5; margin-top:2px;"></i></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($urunler)): ?>
        <tr>
          <td colspan="3" class="table-empty">Arama sonucu bulunamadı.</td>
        </tr>
      <?php else: ?>
        <?php foreach ($urunler as $u): ?>
          <tr>
            <td>
              <div class="td-name-cell">
                <a href="<?= BASE_URL ?>/urun/detay/<?= $u['id'] ?>" style="display:flex; align-items:center; gap:8px; flex:1;">
                  <?= mb_strtoupper(htmlspecialchars($u['ad'])) ?>

                  <?php if ($u['tip'] === 'urun'): ?>
                    <span class="badge-ticari">TİCARİ MAL</span>
                  <?php endif; ?>

                  <?php if (!empty($u['marka'])): ?>
                    <span class="badge-brand"><?= mb_strtoupper(htmlspecialchars($u['marka'])) ?></span>
                  <?php endif; ?>

                  <?php if (!empty($u['kod'])): ?>
                     <span class="badge-tag"><?= mb_strtoupper(htmlspecialchars($u['kod'])) ?></span>
                  <?php endif; ?>

                  <?php if (!empty($u['eticaret'])): ?>
                    <span class="badge-ticari" style="background:#0d623a;" title="E-ticaret sitesinde gösteriliyor"><i class="fa-solid fa-globe"></i> Sitede</span>
                  <?php endif; ?>
                </a>

                <div class="row-actions">
                  <?php if (Rbac::currentUserCan('URUN_UPDATE')): ?>
                    <a href="<?= BASE_URL ?>/urun/duzenle/<?= $u['id'] ?>" class="btn-edit-row"><i class="fa-solid fa-pen"></i></a>
                  <?php endif; ?>
                  <?php if (Rbac::currentUserCan('URUN_DELETE')): ?>
                    <a href="#" class="btn-del-row" onclick="return nymPost('<?= BASE_URL ?>/urun/sil/<?= $u['id'] ?>', 'Emin misiniz?')"><i class="fa-solid fa-trash-can"></i></a>
                  <?php endif; ?>
                </div>
              </div>
            </td>
            <td class="td-price-cell">
               <?= $u['satis_fiyati'] > 0 ? $fmt($u['satis_fiyati']) : '' ?>
            </td>
            <td class="td-stock-cell">
               <?= $u['tip'] === 'urun' ? number_format((float)$u['stok_miktari'], 0, ',', '.') . ' ad' : '' ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<div class="pag-bar">
  <span>
    <?php
      $baslangic = $toplam > 0 ? (($sayfa - 1) * $limit + 1) : 0;
      $bitis     = min($sayfa * $limit, $toplam);
      echo "{$toplam} kayıttan {$baslangic}–{$bitis} gösteriliyor";
    ?>
  </span>
  
  <?php if ($sayfaSayisi > 1): ?>
    <ul class="pagination">
      <?php for ($i = 1; $i <= $sayfaSayisi; $i++): ?>
        <li class="page-item <?= $i === $sayfa ? 'active' : '' ?>">
          <a class="page-link" href="<?= BASE_URL ?>/urun?<?= $qStr(['sayfa' => $i]) ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  <?php endif; ?>
</div>

<script>
(function () {
  const caret = document.getElementById('btnAddCaret');
  const menu  = document.getElementById('addDropdown');
  if (caret && menu) {
    caret.addEventListener('click', function (e) {
      e.stopPropagation();
      menu.classList.toggle('open');
    });
    document.addEventListener('click', function () {
      menu.classList.remove('open');
    });
  }
})();
</script>
