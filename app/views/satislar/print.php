<?php
$company = $company ?? [];
$settings = $settings ?? [];
$companyName = $company['company_name'] ?? $company['short_name'] ?? APP_NAME;
$companyAddress = trim((string)($company['address'] ?? ''));
$companyLocation = trim(implode(' / ', array_filter([
    $company['district'] ?? '',
    $company['city'] ?? '',
    $company['country'] ?? '',
])));
$companyPhone = $company['phone'] ?? '';
$companyEmail = $company['email'] ?? '';
$taxInfo = trim(implode(' / ', array_filter([
    $company['tax_office'] ?? '',
    $company['tax_number'] ?? '',
])));
$logoPath = trim((string)($company['logo_path'] ?? ''));
$logoUrl = $logoPath !== '' && !empty($company['id']) ? BASE_URL . '/companies/logo/' . (int)$company['id'] : '';
$money = static fn($value) => number_format((float)$value, 2, ',', '.') . ' TL';
$date = static fn($value) => $value ? date('d.m.Y', strtotime($value)) : '-';
$rowCount = count($kalemler ?? []);
?>
<style>
  .invoice-print-area {
    width: 190mm;
    max-width: 100%;
    margin: 0 auto;
    background: #fff;
    padding: 9mm;
    border: 1px solid #d8dee8;
    border-radius: 8px;
    box-shadow: 0 16px 45px rgba(15, 23, 42, .12);
    font-size: 11.5px;
    line-height: 1.35;
  }
  .invoice-print-header { display: grid; grid-template-columns: 1.35fr .85fr; gap: 18px; padding-bottom: 12px; border-bottom: 2px solid #111827; break-inside: avoid; page-break-inside: avoid; }
  .invoice-brand { display: flex; gap: 12px; align-items: flex-start; min-width: 0; }
  .invoice-logo { width: 54px; height: 54px; object-fit: contain; border: 1px solid #e5e7eb; border-radius: 6px; padding: 4px; }
  .invoice-logo-fallback { width: 54px; height: 54px; border-radius: 6px; background: #111827; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 18px; flex-shrink: 0; }
  .invoice-company h1 { margin: 0 0 5px; font-size: 20px; line-height: 1.15; color: #111827; }
  .invoice-company p { margin: 1px 0; color: #374151; }
  .invoice-title-box { text-align: right; border-left: 1px solid #e5e7eb; padding-left: 16px; }
  .invoice-title-box h2 { margin: 0 0 10px; font-size: 19px; letter-spacing: .4px; color: #111827; }
  .invoice-meta-line { display: flex; justify-content: space-between; gap: 12px; margin: 4px 0; color: #374151; }
  .invoice-meta-line strong { color: #111827; }
  .invoice-party-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 12px; break-inside: avoid; page-break-inside: avoid; }
  .invoice-box { border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px 11px; min-height: 82px; }
  .invoice-box h3 { margin: 0 0 7px; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: #64748b; }
  .invoice-box p { margin: 2px 0; color: #1f2937; word-break: break-word; }
  .invoice-items-wrap { margin-top: 12px; break-inside: auto; }
  .invoice-items { width: 100%; border-collapse: collapse; table-layout: fixed; }
  .invoice-items thead { display: table-header-group; }
  .invoice-items tfoot { display: table-footer-group; }
  .invoice-items th { background: #f1f5f9; color: #334155; border: 1px solid #dbe3ee; padding: 7px 6px; font-size: 10.5px; text-align: left; }
  .invoice-items td { border: 1px solid #e5e7eb; padding: <?= $rowCount > 12 ? '5px 6px' : '7px 6px' ?>; vertical-align: top; color: #111827; }
  .invoice-items tr { break-inside: avoid; page-break-inside: avoid; }
  .invoice-items .num { text-align: right; white-space: nowrap; }
  .invoice-items .line-name { word-break: break-word; }
  .invoice-summary-row { display: grid; grid-template-columns: 1fr 72mm; gap: 12px; align-items: start; margin-top: 12px; break-inside: avoid; page-break-inside: avoid; }
  .invoice-note { border: 1px solid #e5e7eb; border-radius: 6px; min-height: 55px; padding: 9px 10px; color: #374151; }
  .invoice-note h3 { margin: 0 0 5px; font-size: 11px; text-transform: uppercase; color: #64748b; }
  .invoice-totals { border: 1px solid #111827; border-radius: 6px; overflow: hidden; break-inside: avoid; page-break-inside: avoid; }
  .invoice-total-line { display: flex; justify-content: space-between; gap: 10px; padding: 7px 10px; border-bottom: 1px solid #e5e7eb; }
  .invoice-total-line:last-child { border-bottom: 0; }
  .invoice-total-line.grand { background: #111827; color: #fff; font-size: 15px; font-weight: 800; }
  .invoice-sign-row { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-top: 14px; break-inside: avoid; page-break-inside: avoid; }
  .invoice-sign { height: 58px; border: 1px dashed #cbd5e1; border-radius: 6px; padding: 8px; color: #64748b; font-size: 10.5px; }
  @media print {
    .invoice-print-area { padding: 0; border: 0; font-size: <?= $rowCount > 12 ? '10.5px' : '11px' ?>; color-adjust: exact; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .invoice-company h1 { font-size: 18px; }
    .invoice-title-box h2 { font-size: 17px; }
    .invoice-party-grid, .invoice-summary-row, .invoice-sign-row { gap: 9px; }
    .invoice-box { padding: 8px 9px; min-height: 70px; }
    .invoice-note { min-height: 45px; }
    .invoice-sign { height: 48px; }
  }
</style>

<section class="invoice-print-area">
  <header class="invoice-print-header">
    <div class="invoice-brand">
      <?php if ($logoUrl): ?>
        <img class="invoice-logo" src="<?= htmlspecialchars($logoUrl) ?>" alt="">
      <?php else: ?>
        <div class="invoice-logo-fallback"><?= htmlspecialchars(mb_substr($companyName, 0, 1, 'UTF-8')) ?></div>
      <?php endif; ?>
      <div class="invoice-company">
        <h1><?= htmlspecialchars($companyName) ?></h1>
        <?php if ($companyAddress): ?><p><?= nl2br(htmlspecialchars($companyAddress)) ?></p><?php endif; ?>
        <?php if ($companyLocation): ?><p><?= htmlspecialchars($companyLocation) ?></p><?php endif; ?>
        <?php if ($companyPhone || $companyEmail): ?><p><?= htmlspecialchars(trim($companyPhone . '  ' . $companyEmail)) ?></p><?php endif; ?>
        <?php if ($taxInfo): ?><p>Vergi: <?= htmlspecialchars($taxInfo) ?></p><?php endif; ?>
      </div>
    </div>
    <div class="invoice-title-box">
      <h2>SATIS FATURASI</h2>
      <div class="invoice-meta-line"><span>Fatura No</span><strong><?= htmlspecialchars($fatura['fatura_no'] ?? '-') ?></strong></div>
      <div class="invoice-meta-line"><span>Tarih</span><strong><?= $date($fatura['fatura_tarihi'] ?? null) ?></strong></div>
      <div class="invoice-meta-line"><span>Vade</span><strong><?= $date($fatura['vade_tarihi'] ?? null) ?></strong></div>
      <div class="invoice-meta-line"><span>Durum</span><strong><?= htmlspecialchars(strtoupper((string)($fatura['durum'] ?? '-'))) ?></strong></div>
    </div>
  </header>

  <div class="invoice-party-grid">
    <div class="invoice-box">
      <h3>Musteri Bilgileri</h3>
      <p><strong><?= htmlspecialchars($fatura['cari_unvan'] ?? '-') ?></strong></p>
      <?php if (!empty($fatura['cari_adres'])): ?><p><?= nl2br(htmlspecialchars($fatura['cari_adres'])) ?></p><?php endif; ?>
      <p>Tel: <?= htmlspecialchars($fatura['cari_tel'] ?? '-') ?></p>
      <?php if (!empty($fatura['cari_eposta'])): ?><p>E-posta: <?= htmlspecialchars($fatura['cari_eposta']) ?></p><?php endif; ?>
    </div>
    <div class="invoice-box">
      <h3>Odeme / Belge Bilgileri</h3>
      <p>Belge tipi: <?= htmlspecialchars($fatura['belge_tipi'] ?? 'satis') ?></p>
      <p>Para birimi: <?= htmlspecialchars($fatura['para_birimi'] ?? $settings['default_currency'] ?? 'TRY') ?><?php if (!empty($fatura['para_birimi']) && $fatura['para_birimi'] !== 'TRY' && !empty($fatura['kur'])): ?> (Kur: <?= number_format((float)$fatura['kur'], 4, ',', '.') ?>)<?php endif; ?></p>
      <p>Aciklama: <?= htmlspecialchars($fatura['aciklama'] ?? '-') ?></p>
    </div>
  </div>

  <div class="invoice-items-wrap">
    <table class="invoice-items">
      <thead>
        <tr>
          <th style="width: 8mm;">#</th>
          <th>Urun / Hizmet</th>
          <th style="width: 22mm;" class="num">Miktar</th>
          <th style="width: 27mm;" class="num">Birim</th>
          <th style="width: 18mm;" class="num">KDV</th>
          <th style="width: 30mm;" class="num">Toplam</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($kalemler as $index => $k): ?>
          <?php
            $lineTotal = isset($k['toplam'])
                ? (float)$k['toplam']
                : ((float)($k['miktar'] ?? 0) * (float)($k['birim_fiyat'] ?? 0) * (1 + ((float)($k['kdv_orani'] ?? 0) / 100)));
          ?>
          <tr>
            <td><?= $index + 1 ?></td>
            <td class="line-name"><?= htmlspecialchars($k['urun_adi'] ?? '-') ?></td>
            <td class="num"><?= number_format((float)($k['miktar'] ?? 0), 2, ',', '.') ?></td>
            <td class="num"><?= $money($k['birim_fiyat'] ?? 0) ?></td>
            <td class="num">%<?= number_format((float)($k['kdv_orani'] ?? 0), 0, ',', '.') ?></td>
            <td class="num"><?= $money($lineTotal) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="invoice-summary-row">
    <div class="invoice-note">
      <h3>Not / Aciklama</h3>
      <?= nl2br(htmlspecialchars($fatura['aciklama'] ?? '')) ?>
    </div>
    <div class="invoice-totals">
      <div class="invoice-total-line"><span>Ara Toplam</span><strong><?= $money($fatura['ara_toplam'] ?? 0) ?></strong></div>
      <div class="invoice-total-line"><span>KDV Toplam</span><strong><?= $money($fatura['kdv_tutari'] ?? 0) ?></strong></div>
      <?php if (!empty($fatura['iskonto_tutari'])): ?>
        <div class="invoice-total-line"><span>Iskonto</span><strong><?= $money($fatura['iskonto_tutari']) ?></strong></div>
      <?php endif; ?>
      <div class="invoice-total-line grand"><span>GENEL TOPLAM</span><strong><?= $money($fatura['genel_toplam'] ?? 0) ?></strong></div>
      <?php if (!empty($fatura['para_birimi']) && $fatura['para_birimi'] !== 'TRY' && isset($fatura['genel_toplam_doviz'])): ?>
        <div class="invoice-total-line"><span>Döviz Tutarı</span><strong><?= number_format((float)$fatura['genel_toplam_doviz'], 2, ',', '.') ?> <?= htmlspecialchars($fatura['para_birimi']) ?></strong></div>
      <?php endif; ?>
    </div>
  </div>

  <div class="invoice-sign-row">
    <div class="invoice-sign">Teslim Alan / Imza</div>
    <div class="invoice-sign">Duzenleyen / Kase - Imza</div>
  </div>
</section>
