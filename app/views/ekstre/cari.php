<?php
$cari = $cari ?? [];
$cariTip = $cariTip ?? 'musteri';
$rows = $rows ?? [];
$baslangic = $baslangic ?? date('Y-m-d', strtotime('-1 month'));
$bitis = $bitis ?? date('Y-m-d');
$toplamBorc = (float)($toplamBorc ?? 0);
$toplamAlacak = (float)($toplamAlacak ?? 0);
$bakiye = (float)($bakiye ?? 0);
$backUrl = BASE_URL . '/' . ($cariTip === 'musteri' ? 'musteri' : 'tedarikci') . '/detay/' . (int)($cari['id'] ?? 0);
$currentUrl = BASE_URL . '/ekstre/cari/' . $cariTip . '/' . (int)($cari['id'] ?? 0);
$excelUrl = BASE_URL . '/ekstre/excel/' . $cariTip . '/' . (int)($cari['id'] ?? 0) . '?baslangic=' . urlencode($baslangic) . '&bitis=' . urlencode($bitis);

$money = static fn($value): string => number_format((float)$value, 2, ',', '.');
$dateTr = static function($value): string {
    if (empty($value)) return '';
    $ts = strtotime((string)$value);
    return $ts ? date('d.m.Y', $ts) : '';
};
?>

<style>
.ext-page{background:var(--card-bg);min-height:calc(100vh - 104px);padding-bottom:24px}
.ext-back{display:inline-flex;align-items:center;gap:8px;background:#52b9d7;color:#fff;text-decoration:none;border-radius:4px;padding:10px 14px;font-weight:800;font-size:13px;box-shadow:0 5px 12px rgba(15,23,42,.16);margin-bottom:20px}
.ext-back:hover{color:#fff;filter:brightness(1.04)}
.ext-panel{border-radius:2px;overflow:hidden;border:1px solid var(--border);background:var(--card-bg)}
.ext-head{background:#357db7;color:#fff;padding:16px;font-size:14px;font-weight:900;text-transform:uppercase}
.ext-filter{display:flex;align-items:center;justify-content:center;gap:10px;padding:15px 16px;flex-wrap:wrap}
.ext-filter label{font-size:13px;color:var(--muted);margin-right:12px}
.ext-filter input{height:34px;border:1px solid var(--border2);border-radius:4px;padding:6px 12px;min-width:170px;color:var(--text2)}
.ext-filter span{height:34px;display:inline-flex;align-items:center;border:1px solid var(--border2);border-radius:3px;padding:0 12px;color:var(--muted);background:var(--surface-2)}
.ext-filter button{height:34px;border:0;border-radius:4px;background:#d84a43;color:#fff;font-weight:800;padding:0 14px;font-size:13px}
.ext-summary{display:grid;grid-template-columns:repeat(3,minmax(180px,1fr));gap:30px;padding:0 16px 20px}
.ext-card{height:60px;border-radius:4px;color:#fff;display:grid;grid-template-columns:76px 1fr;align-items:center;overflow:hidden}
.ext-card i{font-size:32px;text-align:center;color:rgba(255,255,255,.9)}
.ext-card-body{text-align:center;padding-right:18px}
.ext-card-title{font-size:12px;font-weight:800;margin-bottom:6px}
.ext-card-value{font-size:18px;font-weight:500}
.ext-red{background:#ff7f73}.ext-blue{background:#58b7dd}.ext-green{background:#61cda1}
.ext-tools{display:flex;gap:14px;padding:0 16px 20px;flex-wrap:wrap}
.ext-tool{border:0;border-radius:4px;color:#fff;font-weight:800;font-size:13px;padding:9px 13px;box-shadow:0 5px 12px rgba(15,23,42,.2);text-decoration:none}
.ext-tool:hover{color:#fff;filter:brightness(1.04)}.ext-tool.green{background:#41ad46}.ext-tool.orange{background:#f0a33a}.ext-tool.dark{background:#334155}
.ext-table-wrap{padding:0 16px 22px;overflow:auto}
.ext-table{width:100%;border-collapse:collapse;min-width:980px;font-size:11px}
.ext-table th{background:rgba(59,130,246,.15);color:#126083;border:1px solid #c3d7e2;font-weight:900;text-align:left;padding:11px 10px}
.ext-table td{border:1px solid #d6dde4;color:var(--text2);padding:10px;vertical-align:top}
.ext-table .num{text-align:right;white-space:nowrap}
.ext-empty{text-align:center;color:var(--muted);padding:22px}
.ext-print-head{display:none}
@media(max-width:760px){
  .ext-filter{justify-content:flex-start}.ext-filter input{min-width:0;width:calc(50% - 24px)}
  .ext-summary{grid-template-columns:1fr;gap:12px}.ext-tools{gap:8px}.ext-tool{flex:1;text-align:center}
}
@media print{
  @page{size:landscape;margin:8mm}
  body{display:block!important;background:var(--card-bg)!important}
  .sidebar,.topbar,.mobile-bottom-bar,.ext-back,.ext-head,.ext-filter,.ext-summary,.ext-tools{display:none!important}
  .page-wrapper{margin-left:0!important;display:block!important;min-height:auto!important}
  .main-content{padding:0!important}
  .ext-page{background:var(--card-bg)!important;min-height:auto!important;padding:0!important}
  .ext-panel{border:0!important;background:var(--card-bg)!important;box-shadow:none!important}
  .ext-print-head{display:block!important;margin:0 0 12px!important;color:var(--text)!important}
  .ext-print-head h1{font-size:15px!important;font-weight:700!important;text-align:center!important;margin:0 0 6px!important;text-transform:uppercase!important}
  .ext-print-head div{font-size:11px!important;text-align:center!important;margin:0!important}
  .ext-table-wrap{padding:0!important;overflow:visible!important}
  .ext-table{width:100%!important;min-width:0!important;font-size:9px!important;border-collapse:collapse!important}
  .ext-table th{background:var(--card-bg)!important;color:var(--text)!important;border:1px solid #999!important;padding:5px!important}
  .ext-table td{color:var(--text)!important;border:1px solid var(--border2)!important;padding:5px!important}
  .ext-table .num{text-align:right!important;white-space:nowrap!important}
  a[href]::after{content:""!important}
}
</style>

<div class="ext-page">
  <a class="ext-back" href="<?= htmlspecialchars($backUrl) ?>"><i class="fa-solid fa-reply"></i> Geri Dön</a>

  <section class="ext-panel">
    <div class="ext-head"><?= htmlspecialchars($cari['unvan'] ?? '-') ?> HESAP EKSTRESİ</div>

    <form class="ext-filter" method="get" action="<?= htmlspecialchars($currentUrl) ?>">
      <label>Tarih Aralığı</label>
      <input type="date" name="baslangic" value="<?= htmlspecialchars($baslangic) ?>">
      <span>-</span>
      <input type="date" name="bitis" value="<?= htmlspecialchars($bitis) ?>">
      <button type="submit"><i class="fa-solid fa-bolt"></i> Raporu Hazırla</button>
    </form>

    <div class="ext-summary">
      <div class="ext-card ext-red">
        <i class="fa-regular fa-money-bill-1"></i>
        <div class="ext-card-body">
          <div class="ext-card-title">Toplam Borç</div>
          <div class="ext-card-value"><?= $money($toplamBorc) ?></div>
        </div>
      </div>
      <div class="ext-card ext-blue">
        <i class="fa-regular fa-money-bill-1"></i>
        <div class="ext-card-body">
          <div class="ext-card-title">Toplam Alacak</div>
          <div class="ext-card-value"><?= $money($toplamAlacak) ?></div>
        </div>
      </div>
      <div class="ext-card ext-green">
        <i class="fa-regular fa-money-bill-1"></i>
        <div class="ext-card-body">
          <div class="ext-card-title">Bakiye</div>
          <div class="ext-card-value"><?= $money($bakiye) ?></div>
        </div>
      </div>
    </div>

    <div class="ext-tools">
      <a class="ext-tool green" href="<?= htmlspecialchars($excelUrl) ?>"><i class="fa-solid fa-list"></i> Excel</a>
      <button class="ext-tool orange" type="button" onclick="window.print()"><i class="fa-solid fa-print"></i> PDF</button>
      <a class="ext-tool dark" href="mailto:?subject=Hesap%20Ekstresi&body=Hesap%20ekstresi%20ektedir."><i class="fa-regular fa-envelope"></i> Gönder</a>
    </div>

    <div class="ext-table-wrap">
      <div class="ext-print-head">
        <h1><?= htmlspecialchars($cari['unvan'] ?? '-') ?> Hesap Ekstresi</h1>
        <div>Tarih Aralığı: <?= htmlspecialchars($dateTr($baslangic)) ?> - <?= htmlspecialchars($dateTr($bitis)) ?></div>
      </div>
      <table class="ext-table">
        <thead>
          <tr>
            <th>Tarih</th>
            <th>Vade</th>
            <th>Hareket</th>
            <th>Belge No</th>
            <th>Açıklama</th>
            <th class="num">Miktar</th>
            <th>Ödeme</th>
            <th class="num">Fiyat</th>
            <th class="num">Borç</th>
            <th class="num">Alacak</th>
            <th class="num">Bakiye</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="11" class="ext-empty">Bu tarih aralığında hareket bulunamadı.</td></tr>
          <?php else: ?>
            <?php foreach ($rows as $row): ?>
              <tr>
                <td><?= htmlspecialchars($dateTr($row['tarih'] ?? '')) ?></td>
                <td><?= htmlspecialchars($dateTr($row['vade'] ?? '')) ?></td>
                <td><?= htmlspecialchars($row['hareket'] ?? '') ?></td>
                <td><?= htmlspecialchars($row['belge_no'] ?? '') ?></td>
                <td><?= htmlspecialchars($row['aciklama'] ?? '') ?></td>
                <td class="num"><?= htmlspecialchars((string)($row['miktar'] ?? '')) ?></td>
                <td><?= htmlspecialchars($row['odeme'] ?? '') ?></td>
                <td class="num"><?= htmlspecialchars((string)($row['fiyat'] ?? '')) ?></td>
                <td class="num"><?= ((float)($row['borc'] ?? 0) !== 0.0) ? $money($row['borc']) : '' ?></td>
                <td class="num"><?= ((float)($row['alacak'] ?? 0) !== 0.0) ? $money($row['alacak']) : '' ?></td>
                <td class="num"><?= $money($row['bakiye'] ?? 0) ?> TL</td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>
