<?php
$company = $company ?? [];
$settings = $settings ?? [];
$companyName = $company['company_name'] ?? $company['short_name'] ?? APP_NAME;
$logoPath = trim((string)($company['logo_path'] ?? ''));
$logoUrl = $logoPath !== '' && !empty($company['id']) ? BASE_URL . '/companies/logo/' . (int)$company['id'] : '';
$money = static fn($value) => number_format((float)$value, 2, ',', '.') . ' TL';
$date = static fn($value) => $value ? date('d.m.Y', strtotime($value)) : '-';
$printUrl = BASE_URL . '/satis/fatura/' . (int)$fatura['id'] . '/print';
?>
<style>
    .invoice-actions { display: flex; gap: 10px; margin-bottom: 18px; }
    .btn-action { padding: 8px 16px; border-radius: 6px; font-weight: 700; font-size: 13px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; border: 0; }
    .btn-print { background: #111827; color: #fff; }
    .btn-back { background: #94a3b8; color: #fff; }

    .invoice-print-area { background: var(--card-bg); border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); padding: 28px; margin-bottom: 20px; position: relative; }
    .invoice-header { display: grid; grid-template-columns: 1.3fr .8fr; gap: 24px; border-bottom: 2px solid #1e293b; padding-bottom: 18px; margin-bottom: 18px; }
    .company-brand { display: flex; align-items: flex-start; gap: 10px; }
    .company-logo { width: 48px; height: 48px; object-fit: contain; border: 1px solid var(--border); border-radius: 6px; padding: 4px; background: var(--card-bg); flex: 0 0 auto; }
    .company-logo-fallback { width: 48px; height: 48px; border-radius: 6px; background: #1e293b; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 16px; flex: 0 0 auto; }
    .company-info h2 { color: var(--text); margin: 0 0 5px; font-size: 24px; }
    .company-info p { margin: 2px 0; color: var(--text2); font-size: 13px; }
    .invoice-meta { text-align: right; }
    .invoice-meta h3 { color: #16a34a; margin: 0 0 8px; font-size: 22px; }
    .invoice-meta p { color: var(--text2); font-size: 13px; margin: 4px 0; }
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 28px; margin-bottom: 22px; }
    .info-box { border: 1px solid var(--border); border-radius: 7px; padding: 13px 14px; }
    .info-box h4 { font-size: 12px; color: var(--muted); text-transform: uppercase; margin: 0 0 9px; border-bottom: 1px solid var(--border); padding-bottom: 6px; }
    .info-box p { font-size: 14px; color: var(--text2); line-height: 1.55; margin: 3px 0; }
    .items-table { width: 100%; border-collapse: collapse; margin-bottom: 22px; table-layout: fixed; }
    .items-table th { text-align: left; background: var(--surface-2); padding: 10px; font-size: 12px; color: var(--muted); border: 1px solid var(--border); }
    .items-table td { padding: 10px; border: 1px solid var(--border); font-size: 13.5px; color: var(--text2); vertical-align: top; }
    .items-table .text-right { text-align: right; white-space: nowrap; }
    .items-table .line-name { word-break: break-word; }
    .totals-area { display: flex; justify-content: flex-end; break-inside: avoid; page-break-inside: avoid; }
    .totals-box { width: 290px; border: 1px solid #1e293b; border-radius: 7px; overflow: hidden; }
    .total-row { display: flex; justify-content: space-between; padding: 9px 12px; border-bottom: 1px solid var(--border); }
    .total-row.grand { border-bottom: none; font-weight: 800; font-size: 17px; color: #fff; background: #1e293b; }

    @page { size: A4; margin: 10mm; }
    @media print {
        html, body { background: var(--card-bg) !important; margin: 0 !important; padding: 0 !important; }
        body * { visibility: hidden; }
        .sidebar, .topbar, .mobile-bottom-bar, .invoice-actions, .no-print { display: none !important; }
        .page-wrapper { margin-left: 0 !important; display: block !important; min-height: auto !important; }
        .main-content { padding: 0 !important; }
        .invoice-print-area, .invoice-print-area * { visibility: visible; }
        .invoice-print-area {
            position: absolute;
            left: 0;
            top: 0;
            width: 190mm !important;
            margin: 0 auto !important;
            padding: 0 !important;
            box-shadow: none !important;
            border-radius: 0 !important;
            color-adjust: exact;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            font-size: 11px;
        }
        .invoice-header, .info-grid, .totals-area { break-inside: avoid; page-break-inside: avoid; }
        .items-table { margin-bottom: 10px; }
        .items-table thead { display: table-header-group; }
        .items-table tr { break-inside: avoid; page-break-inside: avoid; }
        .items-table th, .items-table td { padding: 6px 7px; font-size: 10.5px; }
        .info-grid { gap: 10px; margin-bottom: 12px; }
        .info-box { padding: 8px 9px; }
        .company-logo, .company-logo-fallback { width: 34px; height: 34px; font-size: 12px; }
        .company-info h2 { font-size: 18px; }
        .invoice-meta h3 { font-size: 17px; }
        .totals-box { width: 70mm; }
        .total-row { padding: 6px 9px; }
        .total-row.grand { font-size: 14px; }
    }
</style>

<div class="invoice-actions no-print">
    <a href="<?= BASE_URL ?>/satis" class="btn-action btn-back"><i class="fa-solid fa-arrow-left"></i> Geri Don</a>
    <?php if (($fatura['durum'] ?? '') !== 'iptal'): ?>
    <a href="<?= BASE_URL ?>/satis/duzenle/<?= (int)$fatura['id'] ?>" class="btn-action btn-print">
        <i class="fa-solid fa-pen"></i> Duzenle
    </a>
    <?php endif; ?>
    <?php if (($fatura['durum'] ?? '') === 'taslak' && Rbac::currentUserCan('SATIS_UPDATE')): ?>
    <a href="#" class="btn-action" style="background:#3b82f6;color:#fff;"
       onclick="return nymPost('<?= BASE_URL ?>/satis/durum/<?= (int)$fatura['id'] ?>', 'Fatura onaylansın mı?')">
        <i class="fa-solid fa-check"></i> Onayla
    </a>
    <?php endif; ?>
    <button type="button" onclick="window.open('<?= htmlspecialchars($printUrl) ?>', '_blank', 'noopener')" class="btn-action btn-print">
        <i class="fa-solid fa-print"></i> Yazdir
    </button>
</div>

<div class="invoice-print-area">
    <div class="invoice-header">
        <div class="company-info">
            <div class="company-brand">
                <?php if ($logoUrl): ?>
                    <img class="company-logo" src="<?= htmlspecialchars($logoUrl) ?>" alt="">
                <?php else: ?>
                    <div class="company-logo-fallback"><?= htmlspecialchars(mb_substr($companyName, 0, 1, 'UTF-8')) ?></div>
                <?php endif; ?>
                <div>
                    <h2><?= htmlspecialchars($companyName) ?></h2>
                    <?php if (!empty($company['address'])): ?><p><?= nl2br(htmlspecialchars($company['address'])) ?></p><?php endif; ?>
                    <p><?= htmlspecialchars(trim(($company['district'] ?? '') . ' ' . ($company['city'] ?? '') . ' ' . ($company['country'] ?? ''))) ?></p>
                    <p><?= htmlspecialchars(trim(($company['phone'] ?? '') . ' ' . ($company['email'] ?? ''))) ?></p>
                    <?php if (!empty($company['tax_number']) || !empty($company['tax_office'])): ?>
                        <p>Vergi: <?= htmlspecialchars(trim(($company['tax_office'] ?? '') . ' / ' . ($company['tax_number'] ?? ''), ' /')) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="invoice-meta">
            <h3>SATIS FATURASI</h3>
            <p><strong>No:</strong> <?= htmlspecialchars($fatura['fatura_no']) ?></p>
            <p><strong>Tarih:</strong> <?= $date($fatura['fatura_tarihi'] ?? null) ?></p>
            <p><strong>Vade:</strong> <?= $date($fatura['vade_tarihi'] ?? null) ?></p>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-box">
            <h4>Musteri Bilgileri</h4>
            <p><strong><?= htmlspecialchars($fatura['cari_unvan'] ?? '-') ?></strong></p>
            <p><?= nl2br(htmlspecialchars($fatura['cari_adres'] ?? '')) ?></p>
            <p>Tel: <?= htmlspecialchars($fatura['cari_tel'] ?? '-') ?></p>
            <?php if (!empty($fatura['cari_eposta'])): ?><p>E-posta: <?= htmlspecialchars($fatura['cari_eposta']) ?></p><?php endif; ?>
        </div>
        <div class="info-box">
            <h4>Odeme Bilgileri</h4>
            <p><strong>Durum:</strong> <?= htmlspecialchars(strtoupper((string)($fatura['durum'] ?? '-'))) ?></p>
            <p><strong>Para Birimi:</strong> <?= htmlspecialchars($fatura['para_birimi'] ?? $settings['default_currency'] ?? 'TRY') ?><?php if (!empty($fatura['para_birimi']) && $fatura['para_birimi'] !== 'TRY' && !empty($fatura['kur'])): ?> (Kur: <?= number_format((float)$fatura['kur'], 4, ',', '.') ?>)<?php endif; ?></p>
            <p><strong>Aciklama:</strong> <?= htmlspecialchars($fatura['aciklama'] ?? '-') ?></p>
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 42%;">Urun / Hizmet</th>
                <th class="text-right">Miktar</th>
                <th class="text-right">Birim Fiyat</th>
                <th class="text-right">KDV</th>
                <th class="text-right">Toplam</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($kalemler as $k): ?>
                <?php
                    $lineTotal = isset($k['toplam'])
                        ? (float)$k['toplam']
                        : ((float)($k['miktar'] ?? 0) * (float)($k['birim_fiyat'] ?? 0) * (1 + ((float)($k['kdv_orani'] ?? 0) / 100)));
                ?>
                <tr>
                    <td class="line-name"><?= htmlspecialchars($k['urun_adi'] ?? '-') ?></td>
                    <td class="text-right"><?= number_format((float)($k['miktar'] ?? 0), 2, ',', '.') ?></td>
                    <td class="text-right"><?= $money($k['birim_fiyat'] ?? 0) ?></td>
                    <td class="text-right">%<?= number_format((float)($k['kdv_orani'] ?? 0), 0, ',', '.') ?></td>
                    <td class="text-right"><?= $money($lineTotal) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals-area">
        <div class="totals-box">
            <div class="total-row">
                <span>Ara Toplam</span>
                <span><?= $money($fatura['ara_toplam'] ?? 0) ?></span>
            </div>
            <div class="total-row">
                <span>KDV Toplam</span>
                <span><?= $money($fatura['kdv_tutari'] ?? 0) ?></span>
            </div>
            <?php if (!empty($fatura['iskonto_tutari'])): ?>
                <div class="total-row">
                    <span>Iskonto</span>
                    <span><?= $money($fatura['iskonto_tutari']) ?></span>
                </div>
            <?php endif; ?>
            <div class="total-row grand">
                <span>GENEL TOPLAM</span>
                <span><?= $money($fatura['genel_toplam'] ?? 0) ?></span>
            </div>
            <?php if (!empty($fatura['para_birimi']) && $fatura['para_birimi'] !== 'TRY' && isset($fatura['genel_toplam_doviz'])): ?>
                <div class="total-row">
                    <span>Döviz Tutarı</span>
                    <span><?= number_format((float)$fatura['genel_toplam_doviz'], 2, ',', '.') ?> <?= htmlspecialchars($fatura['para_birimi']) ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
