<?php
$h = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$money = fn($v) => number_format((float)($v ?? 0), 2, ',', '.');
$flash = $flash ?? [];
$kayitlar = $kayitlar ?? [];
$hesaplar = $hesaplar ?? [];
?>
<style>
  .nm-page{display:grid;gap:18px}.nm-alert{padding:12px 14px;border-radius:10px;font-weight:700}.nm-alert.success{background:rgba(46,204,113,.15);color:var(--success);border:1px solid rgba(46,204,113,.28)}.nm-alert.error{background:rgba(231,76,60,.15);color:var(--danger);border:1px solid rgba(231,76,60,.28)}
  .nm-panel{background:var(--card-bg);border:1px solid var(--border);border-radius:14px;box-shadow:0 12px 28px rgba(8,69,38,.06);overflow:hidden}.nm-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 18px;background:linear-gradient(135deg,var(--accent2),var(--accent));color:#fff}.nm-head h2{font-size:18px;margin:0;font-weight:900}.nm-head p{margin:3px 0 0;color:rgba(255,255,255,.78);font-size:12px}
  .nm-form{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;padding:16px}.nm-field{display:grid;gap:5px}.nm-field.wide{grid-column:span 2}.nm-field label{font-size:12px;font-weight:800;color:var(--text2)}.nm-field input,.nm-field select,.nm-field textarea{width:100%;border:1px solid var(--border);border-radius:9px;padding:9px 10px;font-size:13px;color:var(--text);outline:none}.nm-field textarea{min-height:42px;resize:vertical}.nm-field input:focus,.nm-field select:focus,.nm-field textarea:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(30,140,85,.13)}.nm-actions{display:flex;align-items:end}.nm-btn{border:0;border-radius:9px;padding:10px 14px;font-size:13px;font-weight:900;text-decoration:none;display:inline-flex;gap:7px;align-items:center;justify-content:center}.nm-btn.primary{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff}.nm-btn.danger{background:rgba(231,76,60,.15);color:var(--danger)}
  .nm-table-wrap{overflow-x:auto}.nm-table{width:100%;border-collapse:collapse;min-width:820px}.nm-table th{background:var(--surface-2);color:var(--muted);font-size:12px;text-align:left;padding:11px 14px;border-bottom:1px solid var(--border)}.nm-table td{padding:12px 14px;border-bottom:1px solid var(--border);font-size:13px;color:var(--text2)}.nm-table b{color:var(--text)}.nm-empty{padding:34px;text-align:center;color:var(--muted)}.nm-empty i{display:block;font-size:28px;color:var(--accent);margin-bottom:10px}
  @media(max-width:900px){.nm-form{grid-template-columns:1fr}.nm-field.wide{grid-column:auto}.nm-actions{align-items:stretch}.nm-btn{width:100%}}
</style>

<div class="nm-page">
  <?php if (!empty($flash)): ?><div class="nm-alert <?= $h($flash['tip'] ?? 'success') ?>"><?= $h($flash['mesaj'] ?? '') ?></div><?php endif; ?>

  <?php if (Rbac::currentUserCan('KREDI_CREATE')): ?>
  <section class="nm-panel">
    <div class="nm-head">
      <div><h2><i class="fa-solid fa-building-columns"></i> Yeni Kredi</h2><p>Kredi kaydı oluşturunca ödeme planı otomatik takvime bağlanır.</p></div>
    </div>
    <form class="nm-form" method="post" action="<?= BASE_URL ?>/kredi/kaydet">
      <div class="nm-field"><label>Kredi Adı *</label><input name="ad" required></div>
      <div class="nm-field"><label>Kalan Borç</label><input name="kalan_borc" type="number" step="0.01" min="0" required></div>
      <div class="nm-field"><label>Kalan Taksit</label><input name="kalan_taksit_sayisi" type="number" min="1" max="144" value="1" required></div>
      <div class="nm-field"><label>İlk Taksit Tarihi</label><input name="ilk_taksit_tarihi" type="date" value="<?= date('Y-m-d') ?>"></div>
      <div class="nm-field"><label>Ödeme Takvimi</label><select name="odeme_takvimi"><option value="aylik">Her Ay</option><option value="uc_aylik">3 Ayda Bir</option><option value="yillik">Yılda Bir</option></select></div>
      <div class="nm-field"><label>Ödenen Hesap</label><select name="hesap_id"><option value="">Hesap seçin</option><?php foreach ($hesaplar as $hesap): ?><option value="<?= (int)$hesap['id'] ?>"><?= $h($hesap['hesap_adi']) ?></option><?php endforeach; ?></select></div>
      <div class="nm-field wide"><label>Notlar</label><textarea name="notlar"></textarea></div>
      <div class="nm-actions"><button class="nm-btn primary"><i class="fa-solid fa-check"></i> Kaydet</button></div>
    </form>
  </section>
  <?php endif; ?>

  <section class="nm-panel">
    <div class="nm-head"><div><h2>Kredi Listesi</h2><p><?= count($kayitlar) ?> kayıt</p></div></div>
    <?php if (empty($kayitlar)): ?>
      <div class="nm-empty"><i class="fa-solid fa-circle-info"></i> Henüz kredi kaydı yok.</div>
    <?php else: ?>
      <div class="nm-table-wrap"><table class="nm-table">
        <thead><tr><th>Kredi</th><th>Kalan Borç</th><th>Taksit</th><th>Sıradaki Taksit</th><th>Takvim</th><th></th></tr></thead>
        <tbody><?php foreach ($kayitlar as $row): ?><tr>
          <td><b><?= $h($row['ad']) ?></b><br><span><?= $h($row['notlar']) ?></span></td>
          <td><?= $money($row['kalan_borc']) ?> TL</td>
          <td><?= (int)$row['kalan_taksit_sayisi'] ?></td>
          <td><?= $h($row['siradaki_taksit'] ?: '-') ?></td>
          <td><?= $h($row['odeme_takvimi']) ?></td>
          <td style="display:flex;gap:8px">
            <a class="nm-btn primary" href="<?= BASE_URL ?>/kredi/detay/<?= (int)$row['id'] ?>"><i class="fa-solid fa-list-check"></i> Taksitler</a>
            <?php if (Rbac::currentUserCan('KREDI_DELETE')): ?>
            <a class="nm-btn danger" href="#" onclick="return nymPost('<?= BASE_URL ?>/kredi/sil/<?= (int)$row['id'] ?>', 'Kredi silinsin mi?')"><i class="fa-solid fa-trash"></i></a>
            <?php endif; ?>
          </td>
        </tr><?php endforeach; ?></tbody>
      </table></div>
    <?php endif; ?>
  </section>
</div>
