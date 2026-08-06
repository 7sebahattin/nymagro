<?php
$h = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$money = fn($v) => number_format((float)($v ?? 0), 2, ',', '.');
$flash = $flash ?? [];
$kredi = $kredi ?? [];
$taksitler = $taksitler ?? [];
?>
<style>
  .nm-page{display:grid;gap:18px}.nm-alert{padding:12px 14px;border-radius:10px;font-weight:700}.nm-alert.success{background:#ecfdf5;color:#047857;border:1px solid #a7f3d0}.nm-alert.error{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca}
  .nm-panel{background:#fff;border:1px solid #cfe1cd;border-radius:14px;box-shadow:0 12px 28px rgba(8,69,38,.06);overflow:hidden}.nm-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 18px;background:linear-gradient(135deg,#084526,#1e8c55);color:#fff}.nm-head h2{font-size:18px;margin:0;font-weight:900}.nm-head p{margin:3px 0 0;color:#cfe1cd;font-size:12px}
  .nm-btn{border:0;border-radius:9px;padding:10px 14px;font-size:13px;font-weight:900;text-decoration:none;display:inline-flex;gap:7px;align-items:center;justify-content:center}.nm-btn.primary{background:linear-gradient(135deg,#0d623a,#d97a0c);color:#fff}.nm-btn.muted{background:#f1f5f9;color:#64748b;cursor:default}
  .nm-table-wrap{overflow-x:auto}.nm-table{width:100%;border-collapse:collapse;min-width:640px}.nm-table th{background:#f8fafc;color:#64748b;font-size:12px;text-align:left;padding:11px 14px;border-bottom:1px solid #e2e8f0}.nm-table td{padding:12px 14px;border-bottom:1px solid #f1f5f9;font-size:13px;color:#334155}.nm-badge{padding:3px 9px;border-radius:99px;font-size:12px;font-weight:800}.nm-badge.odendi{background:#ecfdf5;color:#047857}.nm-badge.bekliyor{background:#fff7ed;color:#c2410c}
</style>

<div class="nm-page">
  <?php if (!empty($flash)): ?><div class="nm-alert <?= $h($flash['tip'] ?? 'success') ?>"><?= $h($flash['mesaj'] ?? '') ?></div><?php endif; ?>

  <a class="nm-btn muted" href="<?= BASE_URL ?>/kredi" style="width:fit-content"><i class="fa-solid fa-arrow-left"></i> Kredi Listesi</a>

  <section class="nm-panel">
    <div class="nm-head">
      <div><h2><i class="fa-solid fa-building-columns"></i> <?= $h($kredi['ad'] ?? '') ?></h2>
        <p>Kalan Borç: <?= $money($kredi['kalan_borc'] ?? 0) ?> TL &middot; Kalan Taksit: <?= (int)($kredi['kalan_taksit_sayisi'] ?? 0) ?></p>
      </div>
    </div>
    <?php if (empty($taksitler)): ?>
      <div style="padding:34px;text-align:center;color:#64748b">Ödeme planı bulunamadı.</div>
    <?php else: ?>
      <div class="nm-table-wrap"><table class="nm-table">
        <thead><tr><th>Taksit No</th><th>Vade Tarihi</th><th>Tutar</th><th>Durum</th><th></th></tr></thead>
        <tbody><?php foreach ($taksitler as $t): ?><tr>
          <td><b>#<?= (int)$t['taksit_no'] ?></b></td>
          <td><?= $h($t['odeme_tarihi']) ?></td>
          <td><?= $money($t['tutar']) ?> TL</td>
          <td>
            <?php if ((int)$t['odendi'] === 1): ?>
              <span class="nm-badge odendi">Ödendi</span>
            <?php else: ?>
              <span class="nm-badge bekliyor">Bekliyor</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ((int)$t['odendi'] !== 1): ?>
              <a class="nm-btn primary" href="<?= BASE_URL ?>/kredi/taksitOde/<?= (int)$t['id'] ?>?kredi_id=<?= (int)$kredi['id'] ?>" onclick="return confirm('Bu taksit ödendi olarak işaretlensin mi?')"><i class="fa-solid fa-check"></i> Öde</a>
            <?php endif; ?>
          </td>
        </tr><?php endforeach; ?></tbody>
      </table></div>
    <?php endif; ?>
  </section>
</div>
