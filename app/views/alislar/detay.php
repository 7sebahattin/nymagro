<?php
/**
 * View: alislar/detay.php
 * BizimHesap Tasarımı Korunarak Dinamikleştirildi
 */
if (!isset($fatura)) die('Fatura verisi eksik.');
$company = $company ?? [];
$companyName = $company['company_name'] ?? $company['short_name'] ?? APP_NAME;
$companyLogoPath = trim((string)($company['logo_path'] ?? ''));
$companyLogoUrl = $companyLogoPath !== '' && !empty($company['id']) ? BASE_URL . '/companies/logo/' . (int)$company['id'] : '';
?>
<style>
    /* ── Action Buttons Row ── */
    .top-action-row { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 12px; }
    .btn-act {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 6px 14px; border: none; border-radius: 4px;
      font-size: 12.5px; font-weight: 600; color: #fff; cursor: pointer;
      text-decoration: none; transition: filter .15s;
    }
    .btn-act:hover { filter: brightness(1.1); color: #fff; text-decoration: none; }
    
    .b-yazdir { background: #f0ad4e; }
    .b-duzenle { background: #414e62; }
    .b-iptal { background: #d9534f; }
    .b-paylas { background: #5cb85c; }
    .b-kopyala { background: #14603c; }
    .b-tedarikci { background: #5bc0de; }

    /* ── Panels Container ── */
    .panels-container { display: flex; gap: 20px; align-items: flex-start; }
    .panel { background: var(--card-bg); border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,.08); flex: 1; display: flex; flex-direction: column; overflow:hidden; }
    
    /* ── Left Panel ── */
    .panel-left { max-width: 440px; }
    .panel-left .p-header { background: #337ab7; color: #fff; padding: 12px 20px; font-size: 13.5px; font-weight: 700; text-transform: uppercase; }
    
    .ro-group { display: flex; align-items: baseline; gap: 16px; padding: 7px 24px; font-size: 13px; border-bottom: 1px solid var(--border); }
    .ro-group label { width: 90px; text-align: right; color: var(--text); font-weight: 700; flex-shrink: 0; }
    .ro-group .val { color: var(--text2); flex: 1; }
    .badge-fatura { background: #5cb85c; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: 700; }

    /* ── Right Panel ── */
    .panel-right .p-header { background: #5cb85c; color: #fff; padding: 12px 20px; font-size: 13.5px; font-weight: 700; text-transform: uppercase; }
    
    .ro-table { width: 100%; border-collapse: collapse; }
    .ro-table thead { background: var(--surface-2); border-bottom: 1px solid var(--border); }
    .ro-table th { font-size: 11px; color: var(--muted); font-weight: 600; padding: 8px 12px; text-align: right; }
    .ro-table th:first-child, .ro-table th:nth-child(2) { text-align: left; }
    .ro-table td { padding: 12px 12px; font-size: 12px; color: var(--text2); text-align: right; border-bottom: 1px solid var(--border); }
    .ro-table td:first-child, .ro-table td:nth-child(2) { text-align: left; }
    
    .totals-area { padding: 20px 24px; text-align: right; font-size: 12px; color: var(--muted); line-height: 2; }
    .totals-area .val { color: var(--text2); display: inline-block; width: 120px; text-align: right; }
    .grand-total { font-size: 14px; font-weight: 700; color: var(--text); margin-top: 8px; border-top: 1px solid var(--border); padding-top: 8px; }
    .grand-total .val { color: #16a34a; font-size: 18px; }

    .purchase-print-area { display: block; }
    .purchase-print-title { display: none; }

    @page { size: A4 landscape; margin: 8mm; }
    @media print {
      html, body { background: var(--card-bg) !important; margin: 0 !important; padding: 0 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      body * { visibility: hidden; }
      .purchase-print-area, .purchase-print-area * { visibility: visible; }
      .sidebar, .topbar, .mobile-bottom-bar, .top-action-row, .no-print { display: none !important; }
      .page-wrapper { display: block !important; margin-left: 0 !important; min-height: auto !important; }
      .main-content, .content, .container-fluid { margin: 0 !important; padding: 0 !important; max-width: none !important; }
      .purchase-print-area { position: absolute; left: 0; top: 0; width: 100%; }
      .purchase-print-title { display: flex; align-items: center; gap: 7px; border-bottom: 1px solid #1e293b; padding-bottom: 6px; margin-bottom: 6px; break-inside: avoid; page-break-inside: avoid; }
      .purchase-print-logo { width: 26px; height: 26px; object-fit: contain; border: 1px solid var(--border); border-radius: 5px; padding: 2px; background: var(--card-bg); }
      .purchase-print-logo-fallback { width: 26px; height: 26px; border-radius: 5px; background: #1e293b; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 800; }
      .purchase-print-title h1 { margin: 0; font-size: 15px; color: var(--text); line-height: 1.1; }
      .purchase-print-title p { margin: 2px 0 0; color: var(--muted); font-size: 9px; }
      .panels-container { display: grid !important; grid-template-columns: 0.8fr 1.4fr; gap: 6mm; align-items: start; }
      .panel { box-shadow: none !important; border: 1px solid var(--border); border-radius: 0 !important; overflow: visible !important; break-inside: avoid; page-break-inside: avoid; }
      .panel-left { max-width: none !important; }
      .panel-left .p-header, .panel-right .p-header { background: #1e293b !important; color: #fff !important; padding: 5px 8px; font-size: 9px; }
      .ro-group { padding: 4px 8px; gap: 8px; font-size: 8px; }
      .ro-group label { width: 70px; }
      .ro-table { width: 100% !important; table-layout: auto; }
      .ro-table thead { display: table-header-group; }
      .ro-table tr { break-inside: avoid; page-break-inside: avoid; }
      .ro-table th { background: var(--surface-2) !important; padding: 4px 5px; font-size: 7.5px; white-space: normal; }
      .ro-table td { padding: 4px 5px; font-size: 7.5px; }
      .totals-area { padding: 6px 8px; font-size: 8px; line-height: 1.55; break-inside: avoid; page-break-inside: avoid; }
      .totals-area .val { width: 75px; }
      .grand-total { font-size: 9px; margin-top: 4px; padding-top: 4px; }
      .grand-total .val { font-size: 10px; }
    }

    @media (max-width: 1000px) { .panels-container { flex-direction: column; } .panel-left { max-width: 100%; } }
</style>

<!-- Action Buttons -->
<div class="top-action-row no-print">
  <button class="btn-act b-yazdir" onclick="nymYazdir()"><i class="fa-solid fa-print"></i> Yazdır</button>
  <?php if (($fatura['durum'] ?? '') !== 'iptal' && Rbac::currentUserCan('ALIS_UPDATE')): ?>
  <a href="<?= BASE_URL ?>/alis/duzenle/<?= (int)$fatura['id'] ?>" class="btn-act b-duzenle"><i class="fa-solid fa-pen"></i> Düzenle</a>
  <?php endif; ?>
  <?php if (($fatura['durum'] ?? '') !== 'iptal' && Rbac::currentUserCan('ALIS_UPDATE')): ?>
  <button type="button" class="btn-act b-iptal" onclick="return nymPost('<?= BASE_URL ?>/alis/iptal/<?= $fatura['id'] ?>', 'İptal edilsin mi?')"><i class="fa-solid fa-xmark"></i> İptal Et</button>
  <?php endif; ?>
  <button class="btn-act b-paylas" onclick="alisPaylas(this)"><i class="fa-regular fa-envelope"></i> Paylaş</button>
  <button class="btn-act b-tedarikci" onclick="window.location.href='<?= BASE_URL ?>/tedarikci/detay/<?= (int)$fatura['cari_id'] ?>'"><i class="fa-solid fa-user"></i> Tedarikçi Sayfası</button>
  <a href="<?= BASE_URL ?>/alis" class="btn-act" style="background:#64748b;"><i class="fa-solid fa-reply"></i> Geri Dön</a>
</div>

<div class="purchase-print-area">
<div class="purchase-print-title">
  <?php if ($companyLogoUrl): ?>
    <img class="purchase-print-logo" src="<?= htmlspecialchars($companyLogoUrl) ?>" alt="">
  <?php else: ?>
    <div class="purchase-print-logo-fallback"><?= htmlspecialchars(mb_substr($companyName, 0, 1, 'UTF-8')) ?></div>
  <?php endif; ?>
  <div>
    <h1>Alış Faturası</h1>
    <p><?= htmlspecialchars($companyName) ?> | <?= htmlspecialchars($fatura['fatura_no'] ?? '-') ?></p>
  </div>
</div>
<div class="panels-container">
  <!-- Left Info -->
  <div class="panel panel-left">
    <div class="p-header"><?= htmlspecialchars($fatura['cari_unvan'] ?? 'BİLİNMEYEN TEDARİKÇİ') ?></div>
    <div style="padding: 15px 0;">
      <div class="ro-group"><label>Belge No</label><div class="val"><?= htmlspecialchars($fatura['fatura_no']) ?></div></div>
      <div class="ro-group"><label>Durumu</label><div class="val"><span class="badge-fatura"><?= $fatura['durum'] === 'onaylandi' ? 'Faturalaşmış' : 'Taslak' ?></span></div></div>
      <div class="ro-group"><label>Tarihi</label><div class="val"><?= date('d.m.Y', strtotime($fatura['fatura_tarihi'])) ?></div></div>
      <div class="ro-group"><label>Vadesi</label><div class="val"><?= $fatura['vade_tarihi'] ? date('d.m.Y', strtotime($fatura['vade_tarihi'])) : '-' ?></div></div>
      <div class="ro-group"><label>Ödeme</label><div class="val"><?= htmlspecialchars($fatura['odeme_sekli'] ?? '-') ?></div></div>
      <div class="ro-group"><label>Açıklama</label><div class="val"><?= nl2br(htmlspecialchars($fatura['aciklama'] ?? '')) ?></div></div>
    </div>
  </div>

  <!-- Right Details -->
  <div class="panel panel-right">
    <div class="p-header">DETAYLAR</div>
    <div style="overflow-x: auto;">
      <table class="ro-table">
        <thead>
          <tr>
            <th style="width: 40px;">#</th>
            <th>Açıklama</th>
            <th>Miktar</th>
            <th>Fiyat</th>
            <th>Tutar</th>
            <th>KDV</th>
            <th>Net</th>
          </tr>
        </thead>
        <tbody>
          <?php $i=1; foreach($kalemler as $k): ?>
            <tr>
              <td><?= $i++ ?></td>
              <td><?= htmlspecialchars($k['urun_adi']) ?></td>
              <td><?= number_format($k['miktar'], 2, ',', '.') ?> <?= $k['birim'] ?></td>
              <td><?= number_format($k['birim_fiyat'], 2, ',', '.') ?></td>
              <td><?= number_format($k['ara_toplam'], 2, ',', '.') ?></td>
              <td>%<?= (int)$k['kdv_orani'] ?></td>
              <td><strong><?= number_format($k['toplam'], 2, ',', '.') ?></strong></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="totals-area">
      <div>Brüt Toplam <span class="val"><?= number_format($fatura['ara_toplam'], 2, ',', '.') ?> TL</span></div>
      <div>İndirim <span class="val"><?= number_format($fatura['iskonto_tutari'], 2, ',', '.') ?> TL</span></div>
      <div>KDV Toplam <span class="val"><?= number_format($fatura['kdv_tutari'], 2, ',', '.') ?> TL</span></div>
      <div class="grand-total">
        GENEL TOPLAM <span class="val"><?= number_format($fatura['genel_toplam'], 2, ',', '.') ?> TL</span>
      </div>
      <?php if (!empty($fatura['para_birimi']) && $fatura['para_birimi'] !== 'TRY' && isset($fatura['genel_toplam_doviz'])): ?>
      <div>
        Döviz Tutarı <span class="val"><?= number_format((float)$fatura['genel_toplam_doviz'], 2, ',', '.') ?> <?= htmlspecialchars($fatura['para_birimi']) ?><?php if (!empty($fatura['kur'])): ?> (Kur: <?= number_format((float)$fatura['kur'], 4, ',', '.') ?>)<?php endif; ?></span>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
</div>

<script>
function alisPaylas(btn) {
  const link = window.location.href.split('?')[0];
  navigator.clipboard.writeText(link).then(function () {
    const orjinal = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-check"></i> Kopyalandı';
    setTimeout(function () { btn.innerHTML = orjinal; }, 1800);
  }).catch(function () {
    prompt('Fatura linkini kopyalayın:', link);
  });
}
</script>

<?php if (!empty($_GET['print'])): ?>
<script>
window.addEventListener('load', function () {
  nymYazdir();
});
</script>
<?php endif; ?>
