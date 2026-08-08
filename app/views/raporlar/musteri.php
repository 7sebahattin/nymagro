<?php
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$money = fn($v) => number_format((float)$v, 2, ',', '.') . ' TL';
$dateFmt = function ($v) {
    if (!$v || $v === '1000-01-01') return '-';
    $ts = strtotime((string)$v);
    return $ts ? date('d.m.Y', $ts) : $v;
};
$filters = $filters ?? [];
$availableColumns = $availableColumns ?? [];
$selectedColumns = $filters['columns'] ?? array_keys($availableColumns);
$rows = $rows ?? [];
$summary = $summary ?? [];
$query = $_GET;
unset($query['url']);
$query['hazirla'] = 1;
$excelUrl = BASE_URL . '/rapor/musteri-listesi/export-excel?' . http_build_query($query);
$pdfUrl = BASE_URL . '/rapor/musteri-listesi/export-pdf?' . http_build_query($query);
$tipLabels = ['musteriler' => 'Müşteriler', 'tedarikciler' => 'Tedarikçiler', 'tum' => 'Müşteri + Tedarikçi'];
$activeCompany = class_exists('TenantContext') ? TenantContext::activeCompany() : null;
$activePeriod = class_exists('TenantContext') ? TenantContext::activePeriod() : null;
$printMeta = array_filter([
    $activeCompany['company_name'] ?? null,
    $activePeriod['period_name'] ?? null,
    date('d.m.Y H:i'),
]);
$companyName = $activeCompany['company_name'] ?? APP_NAME;
$companyLogoPath = trim((string)($activeCompany['logo_path'] ?? ''));
$companyLogoUrl = $companyLogoPath !== '' && !empty($activeCompany['id']) ? BASE_URL . '/companies/logo/' . (int)$activeCompany['id'] : '';
?>

<style>
.customer-report-card{border:1px solid var(--border);border-radius:4px;background:var(--card-bg);box-shadow:0 1px 3px rgba(15,23,42,.06);margin-bottom:18px}
.customer-report-header{background:#337ab7;color:#fff;padding:10px 15px;border-radius:3px 3px 0 0;display:flex;align-items:center;justify-content:space-between}
.customer-report-header h6{margin:0;font-size:13px;font-weight:700;letter-spacing:.2px;text-transform:uppercase}
.customer-report-body{padding:22px 26px}
.form-label-right{text-align:right;font-size:13px;color:var(--muted);font-weight:600;padding-top:7px}
.form-control-custom,.form-select-custom{height:34px;border:1px solid var(--border2);border-radius:4px;font-size:13px;color:var(--text2)}
.help-text{font-size:11px;color:var(--muted);margin-top:4px}
.btn-rapor{background:#d9534f;border-color:#d43f3a;color:#fff;font-size:13px;font-weight:600;padding:7px 18px;border-radius:4px}
.btn-rapor:hover{background:#c9302c;color:#fff}
.columns-box{border:1px solid var(--border2);border-radius:4px;max-height:220px;overflow:auto;padding:8px;background:var(--card-bg)}
.columns-box label{display:flex;gap:8px;align-items:center;font-size:12px;margin:0;padding:4px 2px;color:var(--text2)}
.summary-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:10px;margin-bottom:16px}
.summary-card{background:var(--card-bg);border:1px solid var(--border);border-radius:6px;padding:12px}
.summary-card .label{font-size:12px;color:var(--muted)}
.summary-card .value{font-size:17px;font-weight:700;color:var(--text);margin-top:3px}
.report-note{border-left:4px solid #f59e0b;background:rgba(243,156,18,.15);color:var(--warning);padding:10px 12px;border-radius:4px;font-size:13px;margin-bottom:14px}
.filter-pill{background:var(--surface-2);color:var(--text2);border-radius:999px;padding:5px 10px;font-size:12px;display:inline-block;margin:0 6px 6px 0}
.table-wrap{overflow:auto;border:1px solid var(--border);border-radius:6px;background:var(--card-bg)}
.customer-report-table{width:100%;border-collapse:collapse;font-size:12px;min-width:980px}
.customer-report-table th{background:var(--surface-2);color:var(--text2);font-weight:700;border-bottom:1px solid var(--border);padding:9px;white-space:nowrap;cursor:pointer}
.customer-report-table td{border-bottom:1px solid var(--border);padding:8px;vertical-align:top}
.text-money{text-align:right;font-variant-numeric:tabular-nums}
.badge-soft{display:inline-block;border-radius:999px;padding:3px 8px;font-size:11px;font-weight:700}
.badge-debt{background:rgba(231,76,60,.15);color:var(--danger)}.badge-credit{background:rgba(46,204,113,.15);color:var(--success)}.badge-zero{background:var(--surface-2);color:var(--text2)}
.actions a{display:inline-block;margin-right:6px;font-size:12px;text-decoration:none}
.customer-report-print-area{display:flex;flex-direction:column;gap:14px}
.customer-report-print-title{background:var(--card-bg);border:1px solid var(--border);border-radius:6px;padding:12px 14px;margin-bottom:2px}
.customer-report-print-brand{display:flex;align-items:center;gap:10px;min-width:0}
.customer-report-print-logo{width:38px;height:38px;object-fit:contain;border:1px solid var(--border);border-radius:6px;padding:3px;background:var(--card-bg);flex:0 0 auto}
.customer-report-print-logo-fallback{width:38px;height:38px;border-radius:6px;background:#1e293b;color:#fff;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:800;flex:0 0 auto}
.customer-report-print-title h1{margin:0;color:var(--text);font-size:20px;line-height:1.2;font-weight:800}
.customer-report-print-title p{margin:5px 0 0;color:var(--muted);font-size:12px;font-weight:600}
.customer-report-tools{display:flex;align-items:center;margin-bottom:1rem}
@media print{
  @page{size:A4 landscape;margin:8mm}
  html,body{background:var(--card-bg)!important;margin:0!important;padding:0!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}
  body *{visibility:hidden}
  .customer-report-print-area,.customer-report-print-area *{visibility:visible}
  .sidebar,.topbar,.mobile-bottom-bar,.no-print{display:none!important}
  .page-wrapper{display:block!important;margin-left:0!important;min-height:auto!important}
  .main-content,.content,.container-fluid{margin:0!important;padding:0!important;max-width:none!important}
  .customer-report-print-area{position:absolute;left:0;top:0;width:100%;gap:6px}
  .customer-report-print-title,.customer-report-card,.summary-card,.report-note,.table-wrap{box-shadow:none!important;border-radius:0!important;break-inside:avoid;page-break-inside:avoid}
  .customer-report-print-title{padding:6px 0 7px;border-width:0 0 1px}
  .customer-report-print-brand{gap:6px}
  .customer-report-print-logo,.customer-report-print-logo-fallback{width:24px;height:24px;font-size:9px;padding:2px}
  .customer-report-print-title h1{font-size:15px}
  .customer-report-print-title p{font-size:9px;margin-top:2px}
  .report-note{padding:5px 7px;font-size:9px;line-height:1.25;margin-bottom:2px}
  .customer-report-print-area .summary-grid,
  .customer-report-print-area .report-note{display:none!important}
  .summary-grid{grid-template-columns:repeat(4,1fr)!important;gap:5px;margin-bottom:2px}
  .summary-card{padding:5px 6px}
  .summary-card .label{font-size:8px}
  .summary-card .value{font-size:11px;margin-top:1px}
  .customer-report-card{border:0!important;margin:0!important}
  .customer-report-header{background:#1e293b!important;color:#fff!important;padding:5px 0;border-radius:0!important;border-bottom:1px solid #1e293b}
  .customer-report-header h6{font-size:10px}
  .customer-report-body{padding:5px 0 0}
  .filter-pill{font-size:7.5px;padding:2px 5px;margin:0 3px 3px 0;border:1px solid var(--border)}
  .table-wrap{overflow:visible!important;border:0!important;background:transparent!important}
  .customer-report-table{width:100%!important;min-width:0!important;border-collapse:collapse!important;table-layout:auto;font-size:7.5px}
  .customer-report-table thead{display:table-header-group}
  .customer-report-table tr,.customer-report-table td,.customer-report-table th{break-inside:avoid;page-break-inside:avoid}
  .customer-report-table th{background:#1e293b!important;color:#fff!important;padding:3px 4px;font-size:7.2px;white-space:normal}
  .customer-report-table td{color:var(--text)!important;padding:3px 4px;font-size:7.2px;border-color:var(--border)!important}
  .badge-soft{border:1px solid currentColor;padding:1px 4px;font-size:7px}
}
@media(max-width:768px){.form-label-right{text-align:left;padding-top:0;margin-bottom:4px}.customer-report-body{padding:16px}}
</style>

<div class="customer-report-card">
  <div class="customer-report-header">
    <h6>Rapor Kriterleri</h6>
    <i class="fa-solid fa-users"></i>
  </div>
  <div class="customer-report-body">
    <form method="get" action="<?= BASE_URL ?>/rapor/musteri-listesi" id="customerReportForm">
      <input type="hidden" name="hazirla" value="1">
      <div class="row">
        <div class="col-md-6 pe-md-4">
          <div class="row mb-3">
            <div class="col-md-4 form-label-right">Tipi</div>
            <div class="col-md-8">
              <select name="tip" class="form-select form-select-custom">
                <?php foreach ($tipLabels as $key => $label): ?>
                  <option value="<?= $h($key) ?>" <?= ($filters['tip'] ?? 'musteriler') === $key ? 'selected' : '' ?>><?= $h($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-4 form-label-right">Cari arama</div>
            <div class="col-md-8">
              <input name="cari_search" class="form-control form-control-custom" value="<?= $h($filters['cari_search'] ?? '') ?>" placeholder="Ünvan, cari kodu, telefon veya e-posta">
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-4 form-label-right">Sınıflandırma 1</div>
            <div class="col-md-8">
              <select name="siniflandirma1" class="form-select form-select-custom">
                <option value="">Tüm sınıflar</option>
                <?php foreach (($options['class1'] ?? []) as $opt): ?>
                  <option value="<?= $h($opt['value']) ?>" <?= ($filters['siniflandirma1'] ?? '') === $opt['value'] ? 'selected' : '' ?>><?= $h($opt['value']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-4 form-label-right">Sınıflandırma 2</div>
            <div class="col-md-8">
              <select name="siniflandirma2" class="form-select form-select-custom">
                <option value="">Tüm sınıflar</option>
                <?php foreach (($options['class2'] ?? []) as $opt): ?>
                  <option value="<?= $h($opt['value']) ?>" <?= ($filters['siniflandirma2'] ?? '') === $opt['value'] ? 'selected' : '' ?>><?= $h($opt['value']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-4 form-label-right">Satış Elemanı</div>
            <div class="col-md-8">
              <select name="satis_elemani_id" class="form-select form-select-custom">
                <option value="0">Tüm kullanıcılar</option>
                <?php foreach (($options['sales_people'] ?? []) as $person): ?>
                  <option value="<?= (int)$person['id'] ?>" <?= (int)($filters['satis_elemani_id'] ?? 0) === (int)$person['id'] ? 'selected' : '' ?>><?= $h($person['ad']) ?></option>
                <?php endforeach; ?>
              </select>
              <div class="help-text">Satış elemanı bağlantısı varsa cariler bu alana göre filtrelenir.</div>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-4 form-label-right">Kaynak</div>
            <div class="col-md-8">
              <select name="kaynak" class="form-select form-select-custom">
                <option value="">Tüm kaynaklar</option>
                <?php foreach (($options['sources'] ?? []) as $opt): ?>
                  <option value="<?= $h($opt['value']) ?>" <?= ($filters['kaynak'] ?? '') === $opt['value'] ? 'selected' : '' ?>><?= $h($opt['value']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>

        <div class="col-md-6 ps-md-4">
          <div class="row mb-3">
            <div class="col-md-4 form-label-right">Durumu</div>
            <div class="col-md-8">
              <select name="durum" class="form-select form-select-custom">
                <option value="hepsi" <?= ($filters['durum'] ?? 'hepsi') === 'hepsi' ? 'selected' : '' ?>>Hepsi</option>
                <option value="aktif" <?= ($filters['durum'] ?? '') === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                <option value="pasif" <?= ($filters['durum'] ?? '') === 'pasif' ? 'selected' : '' ?>>Pasif / Silinmiş</option>
              </select>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-4 form-label-right">Cari Para Birimi</div>
            <div class="col-md-8">
              <select name="para_birimi" class="form-select form-select-custom">
                <option value="">Tümünü göster</option>
                <?php foreach (($options['currencies'] ?? []) as $opt): ?>
                  <option value="<?= $h($opt['value']) ?>" <?= ($filters['para_birimi'] ?? '') === $opt['value'] ? 'selected' : '' ?>><?= $h($opt['value']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-4 form-label-right">Bakiye Tarihi</div>
            <div class="col-md-8">
              <input type="date" name="bakiye_tarihi" class="form-control form-control-custom" value="<?= $h($filters['bakiye_tarihi'] ?? date('Y-m-d')) ?>">
              <div class="help-text">Seçilen tarih ve öncesindeki satış, alış, tahsilat ve ödeme hareketleri hesaplanır.</div>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-4 form-label-right">Kolonlar</div>
            <div class="col-md-8">
              <div class="columns-box" id="columnsBox">
                <?php foreach ($availableColumns as $key => $label): ?>
                  <label>
                    <input type="checkbox" name="columns[]" value="<?= $h($key) ?>" <?= in_array($key, $selectedColumns, true) ? 'checked' : '' ?>>
                    <span><?= $h($label) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
              <div class="help-text"><span id="columnCount"><?= count($selectedColumns) ?></span> kolon seçildi.</div>
            </div>
          </div>

          <div class="row mb-2">
            <div class="col-md-8 offset-md-4">
              <?php
              $checks = [
                  'acik_borclular' => 'Açık hesap borcu olanlar',
                  'cek_senet_borclular' => 'Çek/Senet borcu olanlar',
                  'alacaklilar' => 'Alacaklılar',
                  'risk_asimi' => 'Risk limitini aşanlar',
                  'satisi_olmayanlar' => 'Satışı olmayanları göster',
              ];
              foreach ($checks as $name => $label):
              ?>
                <label class="d-flex justify-content-between align-items-center mb-2 text-muted" style="font-size:13px;cursor:pointer">
                  <span><?= $h($label) ?></span>
                  <input class="form-check-input m-0" name="<?= $h($name) ?>" value="1" type="checkbox" <?= !empty($filters[$name]) ? 'checked' : '' ?>>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

      <div class="row mt-4 mb-1">
        <div class="col-12 text-center">
          <button type="submit" class="btn btn-rapor"><i class="fa-solid fa-bolt"></i> Raporu Hazırla</button>
          <a href="<?= BASE_URL ?>/rapor/musteri-listesi" class="btn btn-outline-secondary btn-sm ms-2">Temizle</a>
        </div>
      </div>
    </form>
  </div>
</div>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger"><?= $h($error) ?></div>
<?php endif; ?>

<?php if (!empty($prepared)): ?>
  <div class="customer-report-print-area">
    <div class="customer-report-print-title">
      <div class="customer-report-print-brand">
        <?php if ($companyLogoUrl): ?>
          <img class="customer-report-print-logo" src="<?= $h($companyLogoUrl) ?>" alt="">
        <?php else: ?>
          <div class="customer-report-print-logo-fallback"><?= $h(mb_substr($companyName, 0, 1, 'UTF-8')) ?></div>
        <?php endif; ?>
        <div>
          <h1>Müşteri Listesi Raporu</h1>
          <?php if ($printMeta): ?><p><?= $h(implode(' | ', $printMeta)) ?></p><?php endif; ?>
        </div>
      </div>
    </div>

  <?php if (!empty($note)): ?>
    <div class="report-note"><?= $h($note) ?></div>
  <?php endif; ?>

  <div class="summary-grid">
    <?php foreach ($summary as $label => $value): ?>
      <div class="summary-card">
        <div class="label"><?= $h($label) ?></div>
        <div class="value"><?= $h($value) ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="customer-report-card">
    <div class="customer-report-header">
      <h6>Rapor Sonuçları</h6>
      <div class="no-print">
        <a href="<?= $h($excelUrl) ?>" class="btn btn-sm btn-light"><i class="fa-solid fa-file-excel"></i> Excel'e Aktar</a>
        <a href="<?= $h($pdfUrl) ?>" class="btn btn-sm btn-light"><i class="fa-solid fa-file-pdf"></i> PDF'e Aktar</a>
        <button class="btn btn-sm btn-light" onclick="window.print()" type="button"><i class="fa-solid fa-print"></i> Yazdır</button>
      </div>
    </div>
    <div class="customer-report-body">
      <div class="mb-2">
        <span class="filter-pill">Tip: <?= $h($tipLabels[$filters['tip']] ?? 'Müşteriler') ?></span>
        <span class="filter-pill">Durum: <?= $h(ucfirst($filters['durum'] ?? 'hepsi')) ?></span>
        <span class="filter-pill">Bakiye Tarihi: <?= $h($dateFmt($filters['bakiye_tarihi'] ?? date('Y-m-d'))) ?></span>
        <span class="filter-pill">Para Birimi: <?= $h(($filters['para_birimi'] ?? '') ?: 'Tümü') ?></span>
      </div>

      <div class="row align-items-center mb-3 customer-report-tools no-print">
        <div class="col-md-6">
          <input id="reportSearch" class="form-control form-control-sm" placeholder="Sonuçlarda ara">
        </div>
        <div class="col-md-6 text-md-end mt-2 mt-md-0">
          <select id="pageSize" class="form-select form-select-sm d-inline-block" style="width:120px">
            <option value="25">25 kayıt</option>
            <option value="50">50 kayıt</option>
            <option value="100">100 kayıt</option>
            <option value="999999">Tümü</option>
          </select>
        </div>
      </div>

      <?php if (!$rows): ?>
        <div class="alert alert-info mb-0">Seçilen kriterlere uygun cari kayıt bulunamadı.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="customer-report-table" id="customerReportTable">
            <thead>
              <tr>
                <?php foreach ($selectedColumns as $column): ?>
                  <th><?= $h($availableColumns[$column] ?? $column) ?></th>
                <?php endforeach; ?>
                <th class="no-print">İşlemler</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $row): ?>
                <tr>
                  <?php foreach ($selectedColumns as $column): ?>
                    <?php
                    $value = $row[$column] ?? '';
                    $isMoney = in_array($column, ['risk_limiti','acik_hesap_borcu','cek_senet_borcu','toplam_borc','toplam_alacak','net_bakiye'], true);
                    $isDate = in_array($column, ['son_satis_tarihi','son_tahsilat_tarihi','son_islem_tarihi'], true);
                    ?>
                    <td class="<?= $isMoney ? 'text-money' : '' ?>">
                      <?php if ($column === 'bakiye_durumu'): ?>
                        <?php $badge = $value === 'Borçlu' ? 'badge-debt' : ($value === 'Alacaklı' ? 'badge-credit' : 'badge-zero'); ?>
                        <span class="badge-soft <?= $badge ?>"><?= $h($value) ?></span>
                      <?php elseif ($isMoney): ?>
                        <?= $h($money($value)) ?>
                      <?php elseif ($isDate): ?>
                        <?= $h($dateFmt($value)) ?>
                      <?php else: ?>
                        <?= $h($value ?: '-') ?>
                      <?php endif; ?>
                    </td>
                  <?php endforeach; ?>
                  <td class="actions no-print">
                    <?php $base = ($row['ham_tip'] ?? '') === 'tedarikci' ? 'tedarikci' : 'musteri'; ?>
                    <a href="<?= BASE_URL ?>/<?= $base ?>/detay/<?= (int)$row['id'] ?>">Detay</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
  </div>
<?php endif; ?>

<script>
(function(){
  const box = document.getElementById('columnsBox');
  const count = document.getElementById('columnCount');
  if (box && count) {
    box.addEventListener('change', function(){
      count.textContent = box.querySelectorAll('input[type="checkbox"]:checked').length;
    });
  }

  const search = document.getElementById('reportSearch');
  const pageSize = document.getElementById('pageSize');
  const table = document.getElementById('customerReportTable');
  if (!table) return;
  const rows = Array.from(table.querySelectorAll('tbody tr'));
  function applyFilter(){
    const q = (search?.value || '').toLocaleLowerCase('tr-TR');
    const limit = parseInt(pageSize?.value || '25', 10);
    let shown = 0;
    rows.forEach(row => {
      const ok = row.textContent.toLocaleLowerCase('tr-TR').includes(q);
      shown += ok ? 1 : 0;
      row.style.display = ok && shown <= limit ? '' : 'none';
    });
  }
  search?.addEventListener('input', applyFilter);
  pageSize?.addEventListener('change', applyFilter);
  applyFilter();
})();
</script>
