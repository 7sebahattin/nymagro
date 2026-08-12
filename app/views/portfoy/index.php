<?php
$h = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$money = fn($v) => number_format((float)($v ?? 0), 2, ',', '.');
$flash = $flash ?? [];
$kayitlar = $kayitlar ?? [];
$cariler = $cariler ?? [];
$tip = $tip ?? 'cek';
$label = $label ?? ($tip === 'cek' ? 'Çek Portföyü' : 'Senet Portföyü');
$base = $tip === 'cek' ? 'cek' : 'senet';
$durumlar = ['bekliyor' => 'Bekliyor', 'tahsil' => 'Tahsil', 'odendi' => 'Ödendi', 'ciro' => 'Ciro', 'iade' => 'İade', 'kapandi' => 'Kapandı'];
?>
<style>
  .pf-page{display:grid;gap:18px}.pf-alert{padding:12px 14px;border-radius:10px;font-weight:700}.pf-alert.success{background:rgba(46,204,113,.15);color:var(--success);border:1px solid rgba(46,204,113,.28)}.pf-alert.error{background:rgba(231,76,60,.15);color:var(--danger);border:1px solid rgba(231,76,60,.28)}
  .pf-card{background:var(--card-bg);border:1px solid var(--border);border-radius:14px;box-shadow:0 12px 28px rgba(8,69,38,.06);overflow:hidden}.pf-head{padding:16px 18px;background:linear-gradient(135deg,var(--accent2),var(--accent));color:#fff}.pf-head h2{font-size:18px;margin:0;font-weight:900}.pf-head p{margin:3px 0 0;color:rgba(255,255,255,.78);font-size:12px}.pf-form{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px;padding:16px}.pf-field{display:grid;gap:5px}.pf-field.wide{grid-column:span 2}.pf-field label{font-size:12px;font-weight:800;color:var(--text2)}.pf-field input,.pf-field select,.pf-field textarea{border:1px solid var(--border);border-radius:9px;padding:9px 10px;font-size:13px;outline:none}.pf-field textarea{resize:vertical;min-height:42px}.pf-field input:focus,.pf-field select:focus,.pf-field textarea:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(30,140,85,.13)}.pf-actions{display:flex;align-items:end}.pf-btn{border:0;border-radius:9px;padding:10px 14px;font-size:13px;font-weight:900;text-decoration:none;display:inline-flex;gap:7px;align-items:center;justify-content:center}.pf-btn.primary{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff}.pf-btn.danger{background:rgba(231,76,60,.15);color:var(--danger)}.pf-table-wrap{overflow-x:auto}.pf-table{width:100%;border-collapse:collapse;min-width:820px}.pf-table th{background:var(--surface-2);color:var(--muted);font-size:12px;text-align:left;padding:11px 14px;border-bottom:1px solid var(--border)}.pf-table td{padding:12px 14px;border-bottom:1px solid var(--border);font-size:13px;color:var(--text2)}.pf-table b{color:var(--text)}.pf-status{display:inline-flex;border-radius:999px;padding:4px 9px;background:rgba(46,204,113,.15);color:var(--accent);font-size:11px;font-weight:900}.pf-empty{padding:34px;text-align:center;color:var(--muted)}.pf-empty i{display:block;font-size:28px;color:var(--accent);margin-bottom:10px}@media(max-width:1000px){.pf-form{grid-template-columns:1fr}.pf-field.wide{grid-column:auto}.pf-btn{width:100%}}
</style>
<div class="pf-page">
  <?php if (!empty($flash)): ?><div class="pf-alert <?= $h($flash['tip'] ?? 'success') ?>"><?= $h($flash['mesaj'] ?? '') ?></div><?php endif; ?>
  <?php if (Rbac::currentUserCan('PORTFOY_CREATE')): ?>
  <section class="pf-card">
    <div class="pf-head"><h2><i class="fa-solid <?= $tip === 'cek' ? 'fa-money-check' : 'fa-file-invoice-dollar' ?>"></i> Yeni <?= $h($tip === 'cek' ? 'Çek' : 'Senet') ?></h2><p>Vade tarihleri takvim ekranına otomatik düşer.</p></div>
    <form class="pf-form" method="post" action="<?= BASE_URL ?>/<?= $base ?>/kaydet">
      <div class="pf-field"><label>Belge No *</label><input name="belge_no" required></div>
      <div class="pf-field"><label>Cari</label><select name="cari_id"><option value="">Cari yok</option><?php foreach ($cariler as $cari): ?><option value="<?= (int)$cari['id'] ?>"><?= $h($cari['unvan']) ?></option><?php endforeach; ?></select></div>
      <div class="pf-field"><label>Vade Tarihi</label><input name="vade_tarihi" type="date" value="<?= date('Y-m-d') ?>"></div>
      <div class="pf-field"><label>Tutar</label><input name="tutar" type="number" min="0" step="0.01" required></div>
      <div class="pf-field"><label>Durum</label><select name="durum"><?php foreach ($durumlar as $key => $text): ?><option value="<?= $h($key) ?>"><?= $h($text) ?></option><?php endforeach; ?></select></div>
      <div class="pf-actions"><button class="pf-btn primary"><i class="fa-solid fa-check"></i> Kaydet</button></div>
      <div class="pf-field wide"><label>Açıklama</label><textarea name="aciklama"></textarea></div>
    </form>
  </section>
  <?php endif; ?>
  <section class="pf-card">
    <div class="pf-head"><h2><?= $h($label) ?></h2><p><?= count($kayitlar) ?> kayıt</p></div>
    <?php if (empty($kayitlar)): ?><div class="pf-empty"><i class="fa-solid fa-circle-info"></i> Henüz kayıt yok.</div>
    <?php else: ?><div class="pf-table-wrap"><table class="pf-table"><thead><tr><th>Belge</th><th>Cari</th><th>Vade</th><th>Tutar</th><th>Durum</th><th></th></tr></thead><tbody>
      <?php foreach ($kayitlar as $row): ?><tr><td><b><?= $h($row['belge_no']) ?></b><br><?= $h($row['aciklama']) ?></td><td><?= $h($row['cari_unvan'] ?: '-') ?></td><td><?= $h($row['vade_tarihi']) ?></td><td><?= $money($row['tutar']) ?> TL</td><td>
        <?php if (Rbac::currentUserCan('PORTFOY_UPDATE')): ?>
        <form method="post" action="<?= BASE_URL ?>/<?= $base ?>/durum/<?= (int)$row['id'] ?>" onchange="this.requestSubmit()">
          <select name="durum" class="pf-status" style="border:0;cursor:pointer">
            <?php foreach ($durumlar as $key => $text): ?><option value="<?= $h($key) ?>" <?= $row['durum'] === $key ? 'selected' : '' ?>><?= $h($text) ?></option><?php endforeach; ?>
          </select>
        </form>
        <?php else: ?>
          <span class="pf-status"><?= $h($durumlar[$row['durum']] ?? $row['durum']) ?></span>
        <?php endif; ?>
      </td><td><?php if (Rbac::currentUserCan('PORTFOY_DELETE')): ?><a class="pf-btn danger" href="#" onclick="return nymPost('<?= BASE_URL ?>/<?= $base ?>/sil/<?= (int)$row['id'] ?>', 'Kayıt silinsin mi?')"><i class="fa-solid fa-trash"></i></a><?php endif; ?></td></tr><?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
  </section>
</div>
