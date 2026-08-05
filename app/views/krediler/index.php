<?php
$h = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$money = fn($v) => number_format((float)($v ?? 0), 2, ',', '.');
$flash = $flash ?? [];
$kayitlar = $kayitlar ?? [];
$hesaplar = $hesaplar ?? [];
?>
<style>
  .nm-page{display:grid;gap:18px}.nm-alert{padding:12px 14px;border-radius:10px;font-weight:700}.nm-alert.success{background:#ecfdf5;color:#047857;border:1px solid #a7f3d0}.nm-alert.error{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca}
  .nm-panel{background:#fff;border:1px solid #e9d5ff;border-radius:14px;box-shadow:0 12px 28px rgba(76,29,149,.06);overflow:hidden}.nm-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 18px;background:linear-gradient(135deg,#4c1d95,#8b5cf6);color:#fff}.nm-head h2{font-size:18px;margin:0;font-weight:900}.nm-head p{margin:3px 0 0;color:#ddd6fe;font-size:12px}
  .nm-form{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;padding:16px}.nm-field{display:grid;gap:5px}.nm-field.wide{grid-column:span 2}.nm-field label{font-size:12px;font-weight:800;color:#475569}.nm-field input,.nm-field select,.nm-field textarea{width:100%;border:1px solid #ddd6fe;border-radius:9px;padding:9px 10px;font-size:13px;color:#1e1b4b;outline:none}.nm-field textarea{min-height:42px;resize:vertical}.nm-field input:focus,.nm-field select:focus,.nm-field textarea:focus{border-color:#8b5cf6;box-shadow:0 0 0 3px rgba(139,92,246,.13)}.nm-actions{display:flex;align-items:end}.nm-btn{border:0;border-radius:9px;padding:10px 14px;font-size:13px;font-weight:900;text-decoration:none;display:inline-flex;gap:7px;align-items:center;justify-content:center}.nm-btn.primary{background:linear-gradient(135deg,#7c3aed,#c026d3);color:#fff}.nm-btn.danger{background:#fee2e2;color:#b91c1c}
  .nm-table-wrap{overflow-x:auto}.nm-table{width:100%;border-collapse:collapse;min-width:820px}.nm-table th{background:#f8fafc;color:#64748b;font-size:12px;text-align:left;padding:11px 14px;border-bottom:1px solid #e2e8f0}.nm-table td{padding:12px 14px;border-bottom:1px solid #f1f5f9;font-size:13px;color:#334155}.nm-table b{color:#1e1b4b}.nm-empty{padding:34px;text-align:center;color:#64748b}.nm-empty i{display:block;font-size:28px;color:#8b5cf6;margin-bottom:10px}
  @media(max-width:900px){.nm-form{grid-template-columns:1fr}.nm-field.wide{grid-column:auto}.nm-actions{align-items:stretch}.nm-btn{width:100%}}
</style>

<div class="nm-page">
  <?php if (!empty($flash)): ?><div class="nm-alert <?= $h($flash['tip'] ?? 'success') ?>"><?= $h($flash['mesaj'] ?? '') ?></div><?php endif; ?>

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
          <td><a class="nm-btn danger" href="<?= BASE_URL ?>/kredi/sil/<?= (int)$row['id'] ?>" onclick="return confirm('Kredi silinsin mi?')"><i class="fa-solid fa-trash"></i></a></td>
        </tr><?php endforeach; ?></tbody>
      </table></div>
    <?php endif; ?>
  </section>
</div>
