<?php
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$fmt = fn($n) => number_format((float)$n, 2, ',', '.') . ' TL';

$items = $items ?? [];
$toplam = (int)($toplam ?? count($items));
$ozetler = $ozetler ?? [];
$arama = $arama ?? '';
$aktifMi = $aktifMi ?? '';
$eticaretMi = $eticaretMi ?? '';
$sadeceBakiyeli = !empty($sadeceBakiyeli);
$sayfa = max(1, (int)($sayfa ?? 1));
$sayfaSayisi = max(1, (int)($sayfaSayisi ?? 1));
$limit = max(1, (int)($limit ?? 25));
$flash = $flash ?? [];

$cfg = array_merge([
    'route' => 'musteri',
    'activeMenu' => 'musteriler',
    'entityPlural' => 'Müşteriler',
    'entitySingular' => 'Müşteri',
    'nameHeader' => 'İsim / Unvan',
    'totalLabel' => 'Toplam Müşteri',
    'activeLabel' => 'Aktif Müşteri',
    'thirdLabel' => 'E-ticaret Entegre',
    'thirdValueKey' => 'eticaret_sayisi',
    'thirdIcon' => 'fa-cart-shopping',
    'icon' => 'fa-users',
    'rowIcon' => 'fa-user',
    'emptyText' => 'Henüz müşteri kaydı yok.',
    'emptySearchText' => 'Aramanızla eşleşen müşteri bulunamadı.',
    'firstAddText' => 'İlk Müşteriyi Ekle',
    'addText' => 'Yeni Müşteri Ekle',
    'excelText' => 'Excelden Müşteri Yükle',
    'footerName' => 'müşteriden',
    'theme' => 'customer',
], $cariListConfig ?? []);

$baseParams = [];
if ($arama !== '') $baseParams['ara'] = $arama;
if ($aktifMi !== '') $baseParams['aktif'] = $aktifMi;
if ($eticaretMi !== '') $baseParams['eticaret'] = $eticaretMi;
if ($sadeceBakiyeli) $baseParams['bakiyeli'] = '1';

$buildUrl = function (array $extra = []) use ($baseParams, $cfg): string {
    $p = array_merge($baseParams, $extra);
    return BASE_URL . '/' . $cfg['route'] . ($p ? '?' . http_build_query($p) : '');
};
?>

<style>
.cari-page{--navy:#2c3e6b;--navy-dark:#1e2d52;--accent:#17a2b8;--accent-dark:#138496;--green:#22c55e;--green-dark:#16a34a;--orange:#f59e0b;--orange-dark:#d97706}
.cari-page.theme-supplier{--accent:#f59e0b;--accent-dark:#d97706}
.cari-action-bar{display:flex;align-items:center;gap:10px;margin-bottom:20px;flex-wrap:wrap}
.cari-btn-add,.cari-btn-excel{display:inline-flex;align-items:center;gap:7px;color:#fff;border:none;padding:9px 20px;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;transition:background .2s,box-shadow .2s}
.cari-btn-add{background:var(--green)}.cari-btn-add:hover{background:var(--green-dark);color:#fff;box-shadow:0 4px 14px rgba(34,197,94,.35)}
.cari-btn-excel{background:var(--orange)}.cari-btn-excel:hover{background:var(--orange-dark);color:#fff}
.cari-badge-total{margin-left:auto;display:inline-flex;align-items:center;gap:7px;background:var(--green-dark);color:#fff;padding:8px 16px;border-radius:7px;font-size:13px;font-weight:700}
.cari-filter-bar{display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap}
.cari-filter-select{padding:8px 32px 8px 12px;border:1.5px solid var(--border);border-radius:7px;font-size:13px;color:var(--text2);background:var(--card-bg);cursor:pointer;outline:none;transition:border-color .2s}
.cari-filter-select:focus{border-color:var(--accent);box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 14%,transparent)}
.cari-search-wrap{margin-left:auto;display:flex;align-items:center;gap:8px}
.cari-search-label{font-size:13px;color:var(--muted);font-weight:500;white-space:nowrap}
.cari-search-inner{position:relative}.cari-search-inner i{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:13px}
.cari-search-input{padding:8px 14px 8px 36px;border:1.5px solid var(--border);border-radius:7px;font-size:13px;color:var(--text);background:var(--card-bg);outline:none;width:230px;transition:border-color .2s,box-shadow .2s}
.cari-search-input:focus{border-color:var(--accent);box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 14%,transparent)}
.cari-summary-cards{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:18px}
.cari-sum-card{background:var(--card-bg);border-radius:9px;padding:14px 20px;display:flex;align-items:center;gap:14px;box-shadow:0 1px 6px rgba(0,0,0,.07)}
.cari-sum-icon{width:42px;height:42px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
.cari-sum-val{font-size:22px;font-weight:800;color:var(--text);line-height:1}
.cari-sum-label{font-size:11px;color:var(--muted);font-weight:600;text-transform:uppercase;margin-top:3px}
.cari-table-card{background:var(--card-bg);border-radius:10px;box-shadow:0 2px 14px rgba(0,0,0,.07);overflow:hidden}
.cari-table-wrap{overflow-x:auto}.cari-table{width:100%;border-collapse:collapse;min-width:820px}
.cari-table thead tr{background:var(--navy)}.cari-table thead th{padding:13px 16px;color:rgba(255,255,255,.75);font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;white-space:nowrap}
.cari-table thead th a{color:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:4px}.cari-table thead th a:hover{color:#fff}
.cari-sort-icon{font-size:10px;margin-left:4px;color:rgba(255,255,255,.4)}
.cari-table tbody tr{border-bottom:1px solid var(--border);transition:background .12s}.cari-table tbody tr:hover{background:var(--surface-2)}.cari-page.theme-customer .cari-table tbody tr:hover{background:rgba(46,204,113,.10)}
.cari-table tbody td{padding:10px 16px;font-size:13.5px;color:var(--text2);vertical-align:middle}
.cari-name-btn{display:inline-flex;align-items:center;gap:6px;background:var(--accent);color:#fff;font-size:12.5px;font-weight:600;padding:6px 14px;border-radius:5px;text-decoration:none;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;transition:background .15s,box-shadow .15s}
.cari-name-btn:hover{background:var(--accent-dark);color:#fff;box-shadow:0 3px 10px color-mix(in srgb,var(--accent) 32%,transparent)}
.cari-name-btn i{font-size:11px;opacity:.85}
.cari-badge-active,.cari-badge-passive,.cari-badge-extra{display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600}
.cari-badge-active{background:rgba(46,204,113,.15);color:var(--success)}.cari-badge-passive{background:rgba(231,76,60,.15);color:var(--danger)}.cari-badge-extra{background:rgba(59,130,246,.15);color:var(--info);margin-left:4px}
.cari-amount{font-size:13px;font-weight:700;color:var(--text)}.cari-amount.zero{color:var(--muted);font-weight:400}.cari-amount.pos{color:var(--success)}.cari-amount.neg{color:var(--danger)}.cari-amount.purple{color:var(--accent)}
.td-right{text-align:right}.td-center{text-align:center}
.cari-action-btn{display:inline-flex;align-items:center;justify-content:center;width:30px;height:28px;border-radius:5px;font-size:12px;text-decoration:none;margin-right:4px;transition:background .15s}
.cari-action-view{background:rgba(59,130,246,.13);color:var(--info)}.cari-action-view:hover{background:rgba(59,130,246,.22);color:var(--info)}
.cari-action-delete{border:1px solid rgba(231,76,60,.4);color:var(--danger);background:var(--card-bg)}.cari-action-delete:hover{background:rgba(231,76,60,.15);color:var(--danger)}
.cari-table-empty{padding:52px;text-align:center;color:var(--muted);font-size:14px}
.cari-table-footer{padding:13px 18px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;font-size:12.5px;color:var(--muted)}
.cari-pagination{display:flex;gap:4px}.cari-pg-btn{padding:5px 11px;border:1px solid var(--border);border-radius:6px;background:var(--card-bg);color:var(--text2);font-size:12px;cursor:pointer;text-decoration:none;transition:all .15s}
.cari-pg-btn:hover,.cari-pg-btn.active{background:var(--navy);color:#fff;border-color:var(--navy)}
.cari-alert-success,.cari-alert-error{padding:12px 18px;border-radius:8px;margin-bottom:16px;font-size:13px;display:flex;align-items:center;gap:8px}
.cari-alert-success{background:rgba(46,204,113,.15);border:1px solid rgba(46,204,113,.28);color:var(--success)}.cari-alert-error{background:rgba(231,76,60,.15);border:1px solid rgba(231,76,60,.28);color:var(--danger)}
@media(max-width:768px){.cari-summary-cards{grid-template-columns:1fr 1fr}.cari-filter-bar{flex-direction:column;align-items:flex-start}.cari-search-wrap{margin-left:0;width:100%}.cari-search-inner,.cari-search-input{width:100%}.cari-badge-total{margin-left:0}}
@media(max-width:560px){.cari-summary-cards{grid-template-columns:1fr}}
</style>

<div class="cari-page theme-<?= $cfg['theme'] === 'supplier' ? 'supplier' : 'customer' ?>">
  <?php if (!empty($flash)): ?>
    <div class="cari-alert-<?= ($flash['tip'] ?? '') === 'success' ? 'success' : 'error' ?>">
      <i class="fa-solid fa-<?= ($flash['tip'] ?? '') === 'success' ? 'check-circle' : 'circle-exclamation' ?>"></i>
      <?= $h($flash['mesaj'] ?? '') ?>
    </div>
  <?php endif; ?>

  <div class="cari-summary-cards">
    <div class="cari-sum-card">
      <div class="cari-sum-icon" style="background:rgba(59,130,246,.15);color:var(--info)"><i class="fa-solid <?= $h($cfg['icon']) ?>"></i></div>
      <div><div class="cari-sum-val"><?= number_format((int)($ozetler['toplam_kayit'] ?? 0)) ?></div><div class="cari-sum-label"><?= $h($cfg['totalLabel']) ?></div></div>
    </div>
    <div class="cari-sum-card">
      <div class="cari-sum-icon" style="background:rgba(46,204,113,.15);color:var(--success)"><i class="fa-solid fa-user-check"></i></div>
      <div><div class="cari-sum-val"><?= number_format((int)($ozetler['aktif_sayisi'] ?? 0)) ?></div><div class="cari-sum-label"><?= $h($cfg['activeLabel']) ?></div></div>
    </div>
    <div class="cari-sum-card">
      <div class="cari-sum-icon" style="background:rgba(243,156,18,.15);color:#d97706"><i class="fa-solid <?= $h($cfg['thirdIcon']) ?>"></i></div>
      <div><div class="cari-sum-val"><?= number_format((int)($ozetler[$cfg['thirdValueKey']] ?? 0)) ?></div><div class="cari-sum-label"><?= $h($cfg['thirdLabel']) ?></div></div>
    </div>
  </div>

  <div class="cari-action-bar">
    <a href="<?= BASE_URL ?>/<?= $h($cfg['route']) ?>/ekle" class="cari-btn-add"><i class="fa-solid fa-plus"></i> <?= $h($cfg['addText']) ?></a>
    <button class="cari-btn-excel" type="button" onclick="alert('Excelden toplu yükleme özelliği yakında eklenecek.')"><i class="fa-solid fa-file-excel"></i> <?= $h($cfg['excelText']) ?></button>
    <div class="cari-badge-total"><i class="fa-solid <?= $h($cfg['icon']) ?>"></i><span><?= number_format($toplam) ?></span> <?= $h($cfg['totalLabel']) ?></div>
  </div>

  <form method="GET" action="<?= BASE_URL ?>/<?= $h($cfg['route']) ?>" id="filterForm">
    <div class="cari-filter-bar">
      <select name="aktif" class="cari-filter-select" onchange="document.getElementById('filterForm').submit()">
        <option value="" <?= $aktifMi === '' ? 'selected' : '' ?>>Tüm <?= $h($cfg['entityPlural']) ?></option>
        <option value="1" <?= $aktifMi === '1' ? 'selected' : '' ?>>Aktif <?= $h($cfg['entityPlural']) ?></option>
        <option value="0" <?= $aktifMi === '0' ? 'selected' : '' ?>>Pasif <?= $h($cfg['entityPlural']) ?></option>
      </select>
      <select name="eticaret" class="cari-filter-select" onchange="document.getElementById('filterForm').submit()">
        <option value="" <?= $eticaretMi === '' ? 'selected' : '' ?>>Tüm Tipler</option>
        <option value="1" <?= $eticaretMi === '1' ? 'selected' : '' ?>>Entegrasyonlu</option>
      </select>
      <select name="bakiyeli" class="cari-filter-select" onchange="document.getElementById('filterForm').submit()">
        <option value="" <?= !$sadeceBakiyeli ? 'selected' : '' ?>>Hepsini Göster</option>
        <option value="1" <?= $sadeceBakiyeli ? 'selected' : '' ?>>Bakiyesi Olanlar (&gt; 0)</option>
      </select>
      <div class="cari-search-wrap">
        <span class="cari-search-label">Ara:</span>
        <div class="cari-search-inner">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" name="ara" id="araInput" class="cari-search-input" placeholder="arama... (en az 3 karakter)" value="<?= $h($arama) ?>" autocomplete="off">
        </div>
      </div>
    </div>
  </form>

  <div class="cari-table-card">
    <div class="cari-table-wrap">
      <table class="cari-table">
        <thead>
          <tr>
            <th><a href="<?= $buildUrl(['sirala' => 'unvan', 'yon' => 'ASC']) ?>"><?= $h($cfg['nameHeader']) ?> <i class="fa-solid fa-sort cari-sort-icon"></i></a></th>
            <th class="td-right">Açık Bakiye <i class="fa-solid fa-circle-info cari-sort-icon" title="Fatura ve ödeme hareketlerinden hesaplanır"></i></th>
            <th class="td-right">Çek / Senet <i class="fa-solid fa-circle-info cari-sort-icon" title="Portföydeki bekleyen çek/senet toplamı"></i></th>
            <th class="td-center">Durum</th>
            <th class="td-right">Telefon</th>
            <th class="td-right">İşlem</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($items)): ?>
            <tr>
              <td colspan="6" class="cari-table-empty">
                <i class="fa-solid fa-inbox" style="font-size:32px;margin-bottom:10px;display:block;opacity:.4"></i>
                <?= $arama !== '' ? $h($cfg['emptySearchText']) : $h($cfg['emptyText']) ?>
                <?php if ($arama === ''): ?>
                  <div style="margin-top:12px">
                    <a href="<?= BASE_URL ?>/<?= $h($cfg['route']) ?>/ekle" class="cari-btn-add" style="display:inline-flex"><i class="fa-solid fa-plus"></i> <?= $h($cfg['firstAddText']) ?></a>
                  </div>
                <?php endif; ?>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($items as $item): ?>
              <?php
              $acikBakiye = (float)($item['acik_bakiye'] ?? $item['bakiye'] ?? 0);
              $cekSenet = (float)($item['cek_senet_bakiye'] ?? 0);
              $isAktif = (int)($item['aktif_mi'] ?? 1) === 1;
              $isEticaret = (int)($item['eticaret_mi'] ?? 0) === 1;
              $telefon = $item['telefon'] ?: ($item['cep_telefon'] ?? '—');
              ?>
              <tr>
                <td>
                  <a href="<?= BASE_URL ?>/<?= $h($cfg['route']) ?>/detay/<?= (int)$item['id'] ?>" class="cari-name-btn" title="<?= $h($item['unvan'] ?? '') ?>">
                    <i class="fa-solid <?= $h($cfg['rowIcon']) ?>"></i><?= $h($item['unvan'] ?? '-') ?>
                  </a>
                </td>
                <td class="td-right">
                  <?php if ($acikBakiye > 0): ?>
                    <span class="cari-amount pos"><?= $fmt($acikBakiye) ?></span>
                  <?php elseif ($acikBakiye < 0): ?>
                    <span class="cari-amount neg"><?= $fmt($acikBakiye) ?></span>
                  <?php else: ?>
                    <span class="cari-amount zero">0,00 TL</span>
                  <?php endif; ?>
                </td>
                <td class="td-right">
                  <?php if ($cekSenet > 0): ?>
                    <span class="cari-amount purple"><?= $fmt($cekSenet) ?></span>
                  <?php else: ?>
                    <span class="cari-amount zero">0,00 TL</span>
                  <?php endif; ?>
                </td>
                <td class="td-center">
                  <span class="<?= $isAktif ? 'cari-badge-active' : 'cari-badge-passive' ?>"><?= $isAktif ? 'Aktif' : 'Pasif' ?></span>
                  <?php if ($isEticaret): ?><span class="cari-badge-extra">Entegre</span><?php endif; ?>
                </td>
                <td class="td-right" style="color:var(--muted);font-size:13px"><?= $h($telefon ?: '—') ?></td>
                <td class="td-right">
                  <a href="<?= BASE_URL ?>/<?= $h($cfg['route']) ?>/detay/<?= (int)$item['id'] ?>" class="cari-action-btn cari-action-view" title="Görüntüle"><i class="fa-solid fa-eye"></i></a>
                  <a href="<?= BASE_URL ?>/<?= $h($cfg['route']) ?>/sil/<?= (int)$item['id'] ?>" class="cari-action-btn cari-action-delete" title="Sil" onclick="return confirm('<?= $h(addslashes($item['unvan'] ?? 'Kayıt')) ?> silinsin mi?\n\nBu işlem geri alınamaz.')"><i class="fa-solid fa-trash-can"></i></a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <div class="cari-table-footer">
      <span>
        <?php if ($toplam > 0): ?>
          <?php $baslangic = ($sayfa - 1) * $limit + 1; $bitis = min($sayfa * $limit, $toplam); ?>
          <strong><?= number_format($toplam) ?></strong> <?= $h($cfg['footerName']) ?> <strong><?= $baslangic ?>-<?= $bitis ?></strong> gösteriliyor
        <?php else: ?>
          Kayıt bulunamadı
        <?php endif; ?>
      </span>
      <?php if ($sayfaSayisi > 1): ?>
        <div class="cari-pagination">
          <?php if ($sayfa > 1): ?><a href="<?= $buildUrl(['sayfa' => $sayfa - 1]) ?>" class="cari-pg-btn"><i class="fa-solid fa-chevron-left"></i></a><?php endif; ?>
          <?php for ($i = max(1, $sayfa - 2); $i <= min($sayfaSayisi, $sayfa + 2); $i++): ?>
            <a href="<?= $buildUrl(['sayfa' => $i]) ?>" class="cari-pg-btn <?= $i === $sayfa ? 'active' : '' ?>"><?= $i ?></a>
          <?php endfor; ?>
          <?php if ($sayfa < $sayfaSayisi): ?><a href="<?= $buildUrl(['sayfa' => $sayfa + 1]) ?>" class="cari-pg-btn"><i class="fa-solid fa-chevron-right"></i></a><?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
(function(){
  const input = document.getElementById('araInput');
  const form = document.getElementById('filterForm');
  let timer;
  if (!input || !form) return;
  input.addEventListener('input', function(){
    clearTimeout(timer);
    const val = this.value.trim();
    if (val.length === 0 || val.length >= 3) {
      timer = setTimeout(() => form.submit(), 350);
    }
  });
  input.addEventListener('keydown', function(e){
    if (e.key === 'Enter') {
      clearTimeout(timer);
      form.submit();
    }
  });
})();
</script>
