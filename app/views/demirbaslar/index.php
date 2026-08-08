<?php
$h = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$money = fn($v) => number_format((float)($v ?? 0), 2, ',', '.');
$flash = $flash ?? [];
$kayitlar = $kayitlar ?? [];
?>
<style>
  .asset-page{display:grid;gap:18px}.asset-alert{padding:12px 14px;border-radius:10px;font-weight:700}.asset-alert.success{background:rgba(46,204,113,.15);color:var(--success);border:1px solid rgba(46,204,113,.28)}.asset-alert.error{background:rgba(231,76,60,.15);color:var(--danger);border:1px solid rgba(231,76,60,.28)}
  .asset-card{background:var(--card-bg);border:1px solid var(--border);border-radius:14px;box-shadow:0 12px 28px rgba(8,69,38,.06);overflow:hidden}.asset-head{padding:16px 18px;background:linear-gradient(135deg,var(--accent2),var(--accent));color:#fff}.asset-head h2{font-size:18px;margin:0;font-weight:900}.asset-head p{margin:3px 0 0;color:rgba(255,255,255,.78);font-size:12px}.asset-form{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px;padding:16px}.asset-field{display:grid;gap:5px}.asset-field.wide{grid-column:span 2}.asset-field label{font-size:12px;font-weight:800;color:var(--text2)}.asset-field input,.asset-field textarea{border:1px solid var(--border);border-radius:9px;padding:9px 10px;font-size:13px;outline:none}.asset-field textarea{resize:vertical;min-height:42px}.asset-field input:focus,.asset-field textarea:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(30,140,85,.13)}.asset-btn{border:0;border-radius:9px;padding:10px 14px;font-size:13px;font-weight:900;text-decoration:none;display:inline-flex;gap:7px;align-items:center;justify-content:center}.asset-btn.primary{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff}.asset-btn.danger{background:rgba(231,76,60,.15);color:var(--danger)}.asset-actions{display:flex;align-items:end}.asset-table-wrap{overflow-x:auto}.asset-table{width:100%;border-collapse:collapse;min-width:760px}.asset-table th{background:var(--surface-2);color:var(--muted);font-size:12px;text-align:left;padding:11px 14px;border-bottom:1px solid var(--border)}.asset-table td{padding:12px 14px;border-bottom:1px solid var(--border);font-size:13px;color:var(--text2)}.asset-table b{color:var(--text)}.asset-empty{padding:34px;text-align:center;color:var(--muted)}.asset-empty i{display:block;font-size:28px;color:var(--accent);margin-bottom:10px}@media(max-width:900px){.asset-form{grid-template-columns:1fr}.asset-field.wide{grid-column:auto}.asset-btn{width:100%}}
</style>
<div class="asset-page">
  <?php if (!empty($flash)): ?><div class="asset-alert <?= $h($flash['tip'] ?? 'success') ?>"><?= $h($flash['mesaj'] ?? '') ?></div><?php endif; ?>
  <section class="asset-card">
    <div class="asset-head"><h2><i class="fa-solid fa-chair"></i> Yeni Demirbaş</h2><p>Araç, cihaz, ekipman ve garanti bilgilerini takip edin.</p></div>
    <form class="asset-form" method="post" action="<?= BASE_URL ?>/demirbas/kaydet">
      <div class="asset-field"><label>Demirbaş Adı *</label><input name="ad" required></div>
      <div class="asset-field"><label>Seri No / Plaka</label><input name="seri_no"></div>
      <div class="asset-field"><label>Alış Tarihi</label><input name="alis_tarihi" type="date"></div>
      <div class="asset-field"><label>Fiyat</label><input name="fiyat" type="number" step="0.01" min="0"></div>
      <div class="asset-actions"><button class="asset-btn primary"><i class="fa-solid fa-check"></i> Kaydet</button></div>
      <div class="asset-field wide"><label>Açıklama</label><textarea name="aciklama"></textarea></div>
    </form>
  </section>
  <section class="asset-card">
    <div class="asset-head"><h2>Demirbaş Listesi</h2><p><?= count($kayitlar) ?> kayıt</p></div>
    <?php if (empty($kayitlar)): ?><div class="asset-empty"><i class="fa-solid fa-circle-info"></i> Henüz demirbaş kaydı yok.</div>
    <?php else: ?><div class="asset-table-wrap"><table class="asset-table"><thead><tr><th>Demirbaş</th><th>Seri No</th><th>Alış Tarihi</th><th>Fiyat</th><th></th></tr></thead><tbody>
      <?php foreach ($kayitlar as $row): ?><tr><td><b><?= $h($row['ad']) ?></b><br><?= $h($row['aciklama']) ?></td><td><?= $h($row['seri_no']) ?></td><td><?= $h($row['alis_tarihi'] ?: '-') ?></td><td><?= $money($row['fiyat']) ?> TL</td><td><a class="asset-btn danger" href="<?= BASE_URL ?>/demirbas/sil/<?= (int)$row['id'] ?>" onclick="return confirm('Demirbaş silinsin mi?')"><i class="fa-solid fa-trash"></i></a></td></tr><?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
  </section>
</div>
