<?php
$h = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$money = fn($v) => number_format((float)($v ?? 0), 2, ',', '.');
$flash = $flash ?? [];
$kredi = $kredi ?? [];
$taksitler = $taksitler ?? [];
?>
<style>
  .nm-page{display:grid;gap:18px}.nm-alert{padding:12px 14px;border-radius:10px;font-weight:700}.nm-alert.success{background:rgba(46,204,113,.15);color:var(--success);border:1px solid rgba(46,204,113,.28)}.nm-alert.error{background:rgba(231,76,60,.15);color:var(--danger);border:1px solid rgba(231,76,60,.28)}
  .nm-panel{background:var(--card-bg);border:1px solid var(--border);border-radius:14px;box-shadow:0 12px 28px rgba(8,69,38,.06);overflow:hidden}.nm-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 18px;background:linear-gradient(135deg,var(--accent2),var(--accent));color:#fff}.nm-head h2{font-size:18px;margin:0;font-weight:900}.nm-head p{margin:3px 0 0;color:rgba(255,255,255,.78);font-size:12px}
  .nm-btn{border:0;border-radius:9px;padding:10px 14px;font-size:13px;font-weight:900;text-decoration:none;display:inline-flex;gap:7px;align-items:center;justify-content:center}.nm-btn.primary{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff}.nm-btn.muted{background:var(--surface-2);color:var(--muted);cursor:default}
  .nm-table-wrap{overflow-x:auto}.nm-table{width:100%;border-collapse:collapse;min-width:640px}.nm-table th{background:var(--surface-2);color:var(--muted);font-size:12px;text-align:left;padding:11px 14px;border-bottom:1px solid var(--border)}.nm-table td{padding:12px 14px;border-bottom:1px solid var(--border);font-size:13px;color:var(--text2)}.nm-badge{padding:3px 9px;border-radius:99px;font-size:12px;font-weight:800}.nm-badge.odendi{background:rgba(46,204,113,.15);color:var(--success)}.nm-badge.bekliyor{background:rgba(243,156,18,.15);color:var(--warning)}
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
      <div style="padding:34px;text-align:center;color:var(--muted)">Ödeme planı bulunamadı.</div>
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
            <?php if ((int)$t['odendi'] !== 1 && Rbac::currentUserCan('KREDI_UPDATE')): ?>
              <a class="nm-btn primary" href="#" onclick="return nymPost('<?= BASE_URL ?>/kredi/taksitOde/<?= (int)$t['id'] ?>?kredi_id=<?= (int)$kredi['id'] ?>', 'Bu taksit ödendi olarak işaretlensin mi?')"><i class="fa-solid fa-check"></i> Öde</a>
            <?php endif; ?>
          </td>
        </tr><?php endforeach; ?></tbody>
      </table></div>
    <?php endif; ?>
  </section>
</div>
