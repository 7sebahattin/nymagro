<?php
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$moneyKeys = ['toplam','tutar','kdv','fiyat','kar','bakiye','borc','tahsilat','odeme','satis','alis'];
$isMoney = function ($key) use ($moneyKeys) {
    foreach ($moneyKeys as $needle) {
        if (str_contains($key, $needle) && !str_contains($key, 'miktar') && !str_contains($key, 'sayisi')) {
            return true;
        }
    }
    return false;
};
$fmt = function ($key, $value) use ($e, $isMoney) {
    if ($value === null || $value === '') return '-';
    if (is_numeric($value)) {
        if ($isMoney($key)) return number_format((float)$value, 2, ',', '.') . ' TL';
        return number_format((float)$value, 2, ',', '.');
    }
    return $e($value);
};
$has = fn($name) => in_array($name, $filtersEnabled ?? [], true);
$base = rtrim(BASE_URL, '/');
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
.report-page { display: flex; flex-direction: column; gap: 14px; }
.report-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.report-btn-sm { display: inline-flex; align-items: center; gap: 6px; border: 0; border-radius: 6px; padding: 8px 12px; font-size: 13px; font-weight: 700; text-decoration: none; cursor: pointer; }
.report-primary { background: #1e293b; color: #fff; }
.report-muted { background: var(--surface-2); color: var(--text); }
.filter-box { background: var(--card-bg); border: 1px solid var(--border); border-radius: 8px; padding: 12px; }
.filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 10px; align-items: end; }
.filter-field label { display: block; font-size: 12px; color: var(--text2); font-weight: 700; margin-bottom: 4px; }
.filter-field input, .filter-field select { width: 100%; height: 36px; border: 1px solid var(--border2); border-radius: 6px; padding: 6px 9px; font-size: 13px; background: var(--card-bg); }
.summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 10px; }
.summary-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 8px; padding: 12px; }
.summary-card span { display: block; color: var(--muted); font-size: 12px; font-weight: 700; }
.summary-card strong { display: block; color: var(--text); font-size: 18px; margin-top: 5px; }
.report-note { background: rgba(243,156,18,.15); border: 1px solid rgba(243,156,18,.30); color: #9a3412; border-radius: 8px; padding: 12px; font-size: 13px; line-height: 1.45; }
.table-wrap { overflow-x: auto; background: var(--card-bg); border: 1px solid var(--border); border-radius: 8px; }
.report-table { width: 100%; border-collapse: collapse; min-width: 920px; }
.report-table th { background: #1e293b; color: var(--text2); text-align: left; padding: 10px; font-size: 12px; white-space: nowrap; }
.report-table td { border-top: 1px solid var(--border); padding: 9px 10px; font-size: 13px; color: var(--text); vertical-align: top; }
.report-table tfoot td { background: var(--surface-2); font-weight: 800; }
.empty-state { text-align: center; padding: 36px 12px; color: var(--muted); font-size: 14px; }
.chart-bars { display: flex; gap: 8px; align-items: end; min-height: 170px; padding: 14px; border: 1px solid var(--border); border-radius: 8px; background: var(--card-bg); overflow-x: auto; }
.chart-bar { min-width: 80px; display: flex; flex-direction: column; justify-content: end; align-items: center; gap: 6px; color: var(--text2); font-size: 11px; }
.chart-bar i { display: block; width: 38px; background: #2563eb; border-radius: 4px 4px 0 0; }
.report-print-area { display: flex; flex-direction: column; gap: 14px; }
.report-print-title { background: var(--card-bg); border: 1px solid var(--border); border-radius: 8px; padding: 12px 14px; }
.report-print-brand { display: flex; align-items: center; gap: 10px; min-width: 0; }
.report-print-logo { width: 38px; height: 38px; object-fit: contain; border: 1px solid var(--border); border-radius: 6px; padding: 3px; background: var(--card-bg); flex: 0 0 auto; }
.report-print-logo-fallback { width: 38px; height: 38px; border-radius: 6px; background: #1e293b; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 800; flex: 0 0 auto; }
.report-print-title h1 { margin: 0; color: var(--text); font-size: 20px; line-height: 1.2; font-weight: 800; }
.report-print-title p { margin: 5px 0 0; color: var(--muted); font-size: 12px; font-weight: 600; }
@media print {
  @page { size: A4 landscape; margin: 8mm; }
  html, body { background: var(--card-bg) !important; margin: 0 !important; padding: 0 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  body * { visibility: hidden; }
  .report-print-area, .report-print-area * { visibility: visible; }
  .sidebar, .topbar, .mobile-bottom-bar, .report-actions, .filter-box, .no-print { display: none !important; }
  .page-wrapper { display: block !important; margin-left: 0 !important; min-height: auto !important; }
  .main-content, .content, .container-fluid { margin: 0 !important; padding: 0 !important; max-width: none !important; }
  .report-page { display: block !important; gap: 0 !important; margin: 0 !important; padding: 0 !important; }
  .report-print-area { position: absolute; left: 0; top: 0; width: 100%; gap: 7px; }
  .report-print-title, .summary-card, .report-note, .chart-bars, .table-wrap { box-shadow: none !important; border-radius: 0 !important; break-inside: avoid; page-break-inside: avoid; }
  .report-print-title { padding: 6px 0 7px; border-width: 0 0 1px; }
  .report-print-brand { gap: 6px; }
  .report-print-logo, .report-print-logo-fallback { width: 24px; height: 24px; font-size: 9px; padding: 2px; }
  .report-print-title h1 { font-size: 15px; }
  .report-print-title p { font-size: 9px; margin-top: 2px; }
  .report-note { padding: 5px 7px; font-size: 9px; line-height: 1.25; }
  .summary-grid { grid-template-columns: repeat(4, 1fr) !important; gap: 5px; }
  .summary-card { padding: 5px 6px; }
  .summary-card span { font-size: 8px; }
  .summary-card strong { font-size: 11px; margin-top: 1px; }
  .chart-bars { min-height: 90px; padding: 5px; overflow: visible; }
  .chart-bar { min-width: 44px; font-size: 7px; gap: 2px; }
  .chart-bar i { width: 22px; max-height: 78px; }
  .table-wrap { overflow: visible !important; border: 0 !important; background: transparent !important; }
  .report-table { width: 100% !important; min-width: 0 !important; border-collapse: collapse !important; table-layout: auto; font-size: 8px; }
  .report-table thead { display: table-header-group; }
  .report-table tfoot { display: table-row-group; }
  .report-table tr, .report-table td, .report-table th { break-inside: avoid; page-break-inside: avoid; }
  .report-table th { background: #1e293b !important; color: #fff !important; padding: 3px 4px; font-size: 7.5px; white-space: normal; }
  .report-table td { color: var(--text) !important; padding: 3px 4px; font-size: 7.5px; border-color: var(--border) !important; }
  .report-table tfoot td { background: var(--surface-2) !important; font-weight: 800; }
  .empty-state { padding: 10px 4px; font-size: 9px; }
  .report-print-area .summary-grid,
  .report-print-area .report-note,
  .report-print-area .chart-bars { display: none !important; }
}
@media (max-width: 640px) { .summary-card strong { font-size: 15px; } .report-btn-sm { flex: 1; justify-content: center; } }
</style>

<div class="report-page">
    <div class="report-actions no-print">
        <a class="report-btn-sm report-muted" href="<?= $base ?>/rapor/satis_alis"><i class="fa-solid fa-arrow-left"></i> Geri dön</a>
        <button class="report-btn-sm report-muted" type="button" onclick="window.print()"><i class="fa-solid fa-file-pdf"></i> PDF</button>
        <button class="report-btn-sm report-muted" type="button" onclick="exportReportCsv()"><i class="fa-solid fa-file-excel"></i> Excel</button>
    </div>

    <form class="filter-box no-print" method="get">
        <div class="filter-grid">
            <?php if ($has('date')): ?>
                <div class="filter-field">
                    <label>Dönem</label>
                    <select name="period">
                        <?php foreach (['' => 'Özel/Tümü', 'today' => 'Bugün', 'yesterday' => 'Dün', 'last_7' => 'Son 7 gün', 'last_30' => 'Son 30 gün', 'this_month' => 'Bu ay', 'last_month' => 'Geçen ay', 'last_6' => 'Son 6 ay', 'this_year' => 'Bu yıl'] as $k => $v): ?>
                            <option value="<?= $e($k) ?>" <?= ($filters['period'] ?? '') === $k ? 'selected' : '' ?>><?= $e($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-field"><label>Başlangıç tarihi</label><input type="date" name="start_date" value="<?= $e($filters['start_date'] ?? '') ?>"></div>
                <div class="filter-field"><label>Bitiş tarihi</label><input type="date" name="end_date" value="<?= $e($filters['end_date'] ?? '') ?>"></div>
            <?php endif; ?>

            <?php if ($has('customer')): ?>
                <div class="filter-field"><label>Müşteri</label><select name="customer_id"><option value="">Tümü</option><?php foreach (($options['customers'] ?? []) as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)($filters['customer_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= $e($c['unvan']) ?></option><?php endforeach; ?></select></div>
            <?php endif; ?>
            <?php if ($has('supplier')): ?>
                <div class="filter-field"><label>Tedarikçi</label><select name="supplier_id"><option value="">Tümü</option><?php foreach (($options['suppliers'] ?? []) as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)($filters['supplier_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= $e($c['unvan']) ?></option><?php endforeach; ?></select></div>
            <?php endif; ?>
            <?php if ($has('product')): ?>
                <div class="filter-field"><label>Ürün</label><select name="product_id"><option value="">Tümü</option><?php foreach (($options['products'] ?? []) as $p): ?><option value="<?= (int)$p['id'] ?>" <?= (int)($filters['product_id'] ?? 0) === (int)$p['id'] ? 'selected' : '' ?>><?= $e($p['ad']) ?></option><?php endforeach; ?></select></div>
            <?php endif; ?>
            <?php if ($has('category')): ?>
                <div class="filter-field"><label>Kategori</label><select name="category"><option value="">Tümü</option><?php foreach (($options['categories'] ?? []) as $cat): ?><option value="<?= $e($cat['ad']) ?>" <?= ($filters['category'] ?? '') === $cat['ad'] ? 'selected' : '' ?>><?= $e($cat['ad']) ?></option><?php endforeach; ?></select></div>
            <?php endif; ?>
            <?php if ($has('status')): ?>
                <div class="filter-field"><label>Durum</label><select name="status"><option value="">Tümü</option><?php foreach (['taslak','onaylandi','odendi','kismi_odendi','iptal'] as $s): ?><option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= $e($s) ?></option><?php endforeach; ?></select></div>
            <?php endif; ?>
            <?php if ($has('payment')): ?>
                <div class="filter-field"><label>Ödeme durumu</label><select name="payment_status"><option value="">Tümü</option><option value="paid" <?= ($filters['payment_status'] ?? '') === 'paid' ? 'selected' : '' ?>>Ödendi</option><option value="partial" <?= ($filters['payment_status'] ?? '') === 'partial' ? 'selected' : '' ?>>Kısmi</option><option value="unpaid" <?= ($filters['payment_status'] ?? '') === 'unpaid' ? 'selected' : '' ?>>Ödenmedi</option></select></div>
            <?php endif; ?>
            <?php if ($has('invoice')): ?><div class="filter-field"><label>Fatura no</label><input name="invoice_no" value="<?= $e($filters['invoice_no'] ?? '') ?>"></div><?php endif; ?>
            <?php if ($has('amount')): ?><div class="filter-field"><label>Minimum tutar</label><input type="number" step="0.01" name="min_amount" value="<?= $e($filters['min_amount'] ?? '') ?>"></div><div class="filter-field"><label>Maksimum tutar</label><input type="number" step="0.01" name="max_amount" value="<?= $e($filters['max_amount'] ?? '') ?>"></div><?php endif; ?>
            <?php if ($has('min_qty')): ?><div class="filter-field"><label>Minimum satış miktarı</label><input type="number" step="0.001" name="min_qty" value="<?= $e($filters['min_qty'] ?? '') ?>"></div><?php endif; ?>
            <?php if ($has('customer_search')): ?><div class="filter-field"><label>Müşteri adı</label><input name="customer_search" value="<?= $e($filters['customer_search'] ?? '') ?>"></div><?php endif; ?>
            <?php if ($has('balance')): ?><div class="filter-field"><label>Bakiye filtresi</label><select name="balance_filter"><option value="">Tümü</option><option value="debtors" <?= ($filters['balance_filter'] ?? '') === 'debtors' ? 'selected' : '' ?>>Sadece borçlular</option><option value="paid" <?= ($filters['balance_filter'] ?? '') === 'paid' ? 'selected' : '' ?>>Tahsilatı tamamlananlar</option></select></div><?php endif; ?>
            <?php if ($has('stock')): ?><div class="filter-field"><label>Stok filtresi</label><select name="stock_filter"><option value="">Tümü</option><option value="in_stock" <?= ($filters['stock_filter'] ?? '') === 'in_stock' ? 'selected' : '' ?>>Stokta olanlar</option><option value="out_of_stock" <?= ($filters['stock_filter'] ?? '') === 'out_of_stock' ? 'selected' : '' ?>>Stokta olmayanlar</option><option value="critical" <?= ($filters['stock_filter'] ?? '') === 'critical' ? 'selected' : '' ?>>Kritik stoktakiler</option></select></div><?php endif; ?>
            <?php if ($has('return_type')): ?><div class="filter-field"><label>İade türü</label><select name="return_type"><option value="">Tümü</option><option value="iade_satis" <?= ($filters['return_type'] ?? '') === 'iade_satis' ? 'selected' : '' ?>>Satış iadesi</option><option value="iade_alis" <?= ($filters['return_type'] ?? '') === 'iade_alis' ? 'selected' : '' ?>>Alış iadesi</option></select></div><?php endif; ?>
            <?php if ($has('account')): ?>
                <div class="filter-field"><label>Kasa/Banka</label><select name="account_id"><option value="">Tümü</option><?php foreach (($options['accounts'] ?? []) as $a): ?><option value="<?= (int)$a['id'] ?>" <?= (int)($filters['account_id'] ?? 0) === (int)$a['id'] ? 'selected' : '' ?>><?= $e($a['tip'] . ' - ' . $a['hesap_adi']) ?></option><?php endforeach; ?></select></div>
            <?php endif; ?>
            <?php if ($has('transaction_type')): ?>
                <div class="filter-field"><label>İşlem tipi</label><select name="transaction_type"><option value="">Tümü</option><option value="giris" <?= ($filters['transaction_type'] ?? '') === 'giris' ? 'selected' : '' ?>>Giriş</option><option value="cikis" <?= ($filters['transaction_type'] ?? '') === 'cikis' ? 'selected' : '' ?>>Çıkış</option></select></div>
            <?php endif; ?>
            <?php if ($has('cari_search')): ?><div class="filter-field"><label>Cari adı</label><input name="cari_search" value="<?= $e($filters['cari_search'] ?? '') ?>"></div><?php endif; ?>
            <?php if ($has('description')): ?><div class="filter-field"><label>Açıklama arama</label><input name="description" value="<?= $e($filters['description'] ?? '') ?>"></div><?php endif; ?>
            <?php if ($has('month_year')): ?>
                <div class="filter-field"><label>Ay</label><select name="month"><option value="">Tümü</option><?php for ($i = 1; $i <= 12; $i++): ?><option value="<?= $i ?>" <?= (int)($filters['month'] ?? 0) === $i ? 'selected' : '' ?>><?= $i ?></option><?php endfor; ?></select></div>
                <div class="filter-field"><label>Yıl</label><input type="number" name="year" min="2000" max="2100" value="<?= $e($filters['year'] ?? '') ?>"></div>
            <?php endif; ?>
            <?php if ($has('vat_rate')): ?><div class="filter-field"><label>KDV oranı</label><input type="number" step="0.01" name="vat_rate" value="<?= $e($filters['vat_rate'] ?? '') ?>"></div><?php endif; ?>
            <?php if ($has('invoice_type')): ?><div class="filter-field"><label>Fatura türü</label><select name="invoice_type"><option value="">Tümü</option><option value="satis" <?= ($filters['invoice_type'] ?? '') === 'satis' ? 'selected' : '' ?>>Satış</option><option value="alis" <?= ($filters['invoice_type'] ?? '') === 'alis' ? 'selected' : '' ?>>Alış</option></select></div><?php endif; ?>
            <?php if ($has('expense_category')): ?>
                <div class="filter-field"><label>Masraf kategorisi</label><select name="expense_category_id"><option value="">Tümü</option><?php foreach (($options['expense_categories'] ?? []) as $cat): ?><option value="<?= (int)$cat['id'] ?>" <?= (int)($filters['expense_category_id'] ?? 0) === (int)$cat['id'] ? 'selected' : '' ?>><?= $e($cat['ad']) ?></option><?php endforeach; ?></select></div>
            <?php endif; ?>
            <?php if ($has('user')): ?>
                <div class="filter-field"><label>Kullanıcı</label><select name="user_id"><option value="">Tümü</option><?php foreach (($options['users'] ?? []) as $u): ?><option value="<?= (int)$u['id'] ?>" <?= (int)($filters['user_id'] ?? 0) === (int)$u['id'] ? 'selected' : '' ?>><?= $e($u['ad']) ?></option><?php endforeach; ?></select></div>
            <?php endif; ?>
            <?php if ($has('min_days')): ?><div class="filter-field"><label>Minimum gecikme günü</label><input type="number" name="min_days" value="<?= $e($filters['min_days'] ?? '') ?>"></div><?php endif; ?>
            <?php if ($has('overdue_flags')): ?>
                <div class="filter-field"><label>Vade filtresi</label><select name="only_overdue"><option value="">Tümü</option><option value="1" <?= !empty($filters['only_overdue']) ? 'selected' : '' ?>>Sadece vadesi geçenler</option></select></div>
                <div class="filter-field"><label>Risk filtresi</label><select name="high_risk"><option value="">Tümü</option><option value="1" <?= !empty($filters['high_risk']) ? 'selected' : '' ?>>Sadece yüksek riskliler</option></select></div>
            <?php endif; ?>
            <?php if ($has('transaction_group')): ?><div class="filter-field"><label>BA / BS</label><select name="transaction_group"><option value="">Tümü</option><option value="BA" <?= ($filters['transaction_group'] ?? '') === 'BA' ? 'selected' : '' ?>>BA</option><option value="BS" <?= ($filters['transaction_group'] ?? '') === 'BS' ? 'selected' : '' ?>>BS</option></select></div><?php endif; ?>
            <?php if ($has('tax_no')): ?><div class="filter-field"><label>Vergi no</label><input name="tax_no" value="<?= $e($filters['tax_no'] ?? '') ?>"></div><?php endif; ?>
            <?php if ($has('group_by')): ?><div class="filter-field"><label>Görünüm</label><select name="group_by"><option value="monthly" <?= ($filters['group_by'] ?? '') === 'monthly' ? 'selected' : '' ?>>Aylık</option><option value="daily" <?= ($filters['group_by'] ?? '') === 'daily' ? 'selected' : '' ?>>Günlük</option><option value="yearly" <?= ($filters['group_by'] ?? '') === 'yearly' ? 'selected' : '' ?>>Yıllık</option></select></div><?php endif; ?>
            <?php if ($has('account_type')): ?><div class="filter-field"><label>Hesap türü</label><select name="account_type"><option value="">Tümü</option><?php foreach (['Müşteri','Tedarikçi','Kasa','Banka'] as $t): ?><option value="<?= $e($t) ?>" <?= ($filters['account_type'] ?? '') === $t ? 'selected' : '' ?>><?= $e($t) ?></option><?php endforeach; ?></select></div><?php endif; ?>
            <?php if ($has('balance_state')): ?><div class="filter-field"><label>Bakiye durumu</label><select name="balance_state"><option value="">Tümü</option><?php foreach (['Borçlu','Alacaklı','Sıfır'] as $t): ?><option value="<?= $e($t) ?>" <?= ($filters['balance_state'] ?? '') === $t ? 'selected' : '' ?>><?= $e($t) ?></option><?php endforeach; ?></select></div><?php endif; ?>
            <?php if ($has('employee_search')): ?><div class="filter-field"><label>Çalışan adı</label><input name="employee_search" value="<?= $e($filters['employee_search'] ?? '') ?>"></div><?php endif; ?>
            <?php if ($has('employee')): ?>
                <div class="filter-field"><label>Çalışan</label><select name="employee_id"><option value="">Tümü</option><?php foreach (($options['employees'] ?? []) as $emp): ?><option value="<?= (int)$emp['id'] ?>" <?= (int)($filters['employee_id'] ?? 0) === (int)$emp['id'] ? 'selected' : '' ?>><?= $e($emp['ad']) ?></option><?php endforeach; ?></select></div>
            <?php endif; ?>
            <?php if ($has('bank')): ?><div class="filter-field"><label>Banka</label><input name="bank" value="<?= $e($filters['bank'] ?? '') ?>"></div><?php endif; ?>
            <?php if ($has('voucher_type')): ?><div class="filter-field"><label>Fiş türü</label><select name="voucher_type"><option value="">Tümü</option><option value="borc" <?= ($filters['voucher_type'] ?? '') === 'borc' ? 'selected' : '' ?>>Borç</option><option value="alacak" <?= ($filters['voucher_type'] ?? '') === 'alacak' ? 'selected' : '' ?>>Alacak</option></select></div><?php endif; ?>
            <button class="report-btn-sm report-primary" type="submit"><i class="fa-solid fa-filter"></i> Filtrele</button>
            <a class="report-btn-sm report-muted" href="?"><i class="fa-solid fa-eraser"></i> Temizle</a>
        </div>
    </form>

    <div class="report-print-area">
        <div class="report-print-title">
            <div class="report-print-brand">
                <?php if ($companyLogoUrl): ?>
                    <img class="report-print-logo" src="<?= $e($companyLogoUrl) ?>" alt="">
                <?php else: ?>
                    <div class="report-print-logo-fallback"><?= $e(mb_substr($companyName, 0, 1, 'UTF-8')) ?></div>
                <?php endif; ?>
                <div>
                    <h1><?= $e($title ?? 'Rapor') ?></h1>
                    <?php if ($printMeta): ?><p><?= $e(implode(' | ', $printMeta)) ?></p><?php endif; ?>
                </div>
            </div>
        </div>

    <?php if (!empty($description)): ?>
        <div class="report-note" style="background:rgba(59,130,246,.15);border-color:#bfdbfe;color:var(--info);"><?= $e($description) ?></div>
    <?php endif; ?>

    <?php if (!empty($summary)): ?>
        <div class="summary-grid">
            <?php foreach ($summary as $label => $value): ?>
                <div class="summary-card"><span><?= $e($label) ?></span><strong><?= $e($value) ?></strong></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($note)): ?><div class="report-note"><?= $e($note) ?></div><?php endif; ?>

    <?php if (($title ?? '') === '6 Aylık Satışlar Raporu' && !empty($rows)): ?>
        <?php $max = max(array_map(fn($r) => (float)($r['toplam_satis'] ?? 0), $rows)) ?: 1; ?>
        <div class="chart-bars">
            <?php foreach ($rows as $r): $h = max(8, ((float)$r['toplam_satis'] / $max) * 140); ?>
                <div class="chart-bar"><i style="height: <?= (int)$h ?>px"></i><strong><?= $e($r['ay']) ?></strong><span><?= number_format((float)$r['toplam_satis'], 0, ',', '.') ?></span></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="table-wrap">
        <table class="report-table" id="reportTable">
            <thead><tr><?php foreach ($columns as $label): ?><th><?= $e($label) ?></th><?php endforeach; ?></tr></thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="<?= count($columns) ?>"><div class="empty-state">Bu filtreye uygun kayıt bulunamadı.</div></td></tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?><tr><?php foreach ($columns as $key => $label): ?><td><?= $fmt($key, $row[$key] ?? '') ?></td><?php endforeach; ?></tr><?php endforeach; ?>
            <?php endif; ?>
            </tbody>
            <?php if (!empty($rows)): ?>
                <tfoot><tr><?php $first = true; foreach ($columns as $key => $label): ?><td><?php if ($first) { echo 'Toplam'; $first = false; } elseif (is_numeric($rows[0][$key] ?? null)) { echo $fmt($key, array_sum(array_map(fn($r) => (float)($r[$key] ?? 0), $rows))); } ?></td><?php endforeach; ?></tr></tfoot>
            <?php endif; ?>
        </table>
    </div>
    </div>
</div>

<script>
function exportReportCsv() {
  const rows = [...document.querySelectorAll('#reportTable tr')].map(tr =>
    [...tr.children].map(td => '"' + td.innerText.replaceAll('"', '""') + '"').join(';')
  ).join('\n');
  const blob = new Blob(['\ufeff' + rows], {type: 'text/csv;charset=utf-8;'});
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'rapor.csv';
  a.click();
  URL.revokeObjectURL(a.href);
}
</script>
