<?php
$h = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$flash = $flash ?? [];
$kayitlar = $kayitlar ?? [];
$durumlar = ['aktif' => 'Aktif', 'pasif' => 'Pasif', 'tamamlandi' => 'Tamamlandı'];
?>
<style>
  .project-page{display:grid;gap:18px}.project-alert{padding:12px 14px;border-radius:10px;font-weight:700}.project-alert.success{background:#ecfdf5;color:#047857;border:1px solid #a7f3d0}.project-alert.error{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca}
  .project-card{background:#fff;border:1px solid #e9d5ff;border-radius:14px;box-shadow:0 12px 28px rgba(76,29,149,.06);overflow:hidden}.project-head{padding:16px 18px;background:linear-gradient(135deg,#4c1d95,#8b5cf6);color:#fff}.project-head h2{font-size:18px;margin:0;font-weight:900}.project-head p{margin:3px 0 0;color:#ddd6fe;font-size:12px}.project-form{display:grid;grid-template-columns:1.2fr 2fr 180px 120px;gap:12px;padding:16px}.project-field{display:grid;gap:5px}.project-field label{font-size:12px;font-weight:800;color:#475569}.project-field input,.project-field select,.project-field textarea{border:1px solid #ddd6fe;border-radius:9px;padding:9px 10px;font-size:13px;outline:none}.project-field textarea{resize:vertical;min-height:42px}.project-field input:focus,.project-field select:focus,.project-field textarea:focus{border-color:#8b5cf6;box-shadow:0 0 0 3px rgba(139,92,246,.13)}.project-actions{display:flex;align-items:end}.project-btn{border:0;border-radius:9px;padding:10px 14px;font-size:13px;font-weight:900;text-decoration:none;display:inline-flex;gap:7px;align-items:center;justify-content:center}.project-btn.primary{background:linear-gradient(135deg,#7c3aed,#c026d3);color:#fff}.project-btn.danger{background:#fee2e2;color:#b91c1c}.project-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px;padding:16px}.project-item{border:1px solid #f3e8ff;border-radius:12px;padding:14px;background:#faf5ff}.project-item h3{font-size:15px;color:#1e1b4b;margin:0 0 8px;font-weight:900}.project-item p{font-size:13px;color:#64748b;min-height:34px}.status{display:inline-flex;border-radius:999px;padding:4px 9px;background:#ede9fe;color:#6d28d9;font-size:11px;font-weight:900}.project-empty{padding:34px;text-align:center;color:#64748b}.project-empty i{display:block;font-size:28px;color:#8b5cf6;margin-bottom:10px}@media(max-width:900px){.project-form{grid-template-columns:1fr}.project-btn{width:100%}}
</style>
<div class="project-page">
  <?php if (!empty($flash)): ?><div class="project-alert <?= $h($flash['tip'] ?? 'success') ?>"><?= $h($flash['mesaj'] ?? '') ?></div><?php endif; ?>
  <section class="project-card">
    <div class="project-head"><h2><i class="fa-solid fa-diagram-project"></i> Yeni Proje</h2><p>Gelir ve giderleri proje başlıkları altında izlemek için kayıt açın.</p></div>
    <form class="project-form" method="post" action="<?= BASE_URL ?>/proje/kaydet">
      <div class="project-field"><label>Proje Adı *</label><input name="ad" required></div>
      <div class="project-field"><label>Açıklama</label><textarea name="aciklama"></textarea></div>
      <div class="project-field"><label>Durum</label><select name="durum"><option value="aktif">Aktif</option><option value="pasif">Pasif</option><option value="tamamlandi">Tamamlandı</option></select></div>
      <div class="project-actions"><button class="project-btn primary"><i class="fa-solid fa-check"></i> Kaydet</button></div>
    </form>
  </section>
  <section class="project-card">
    <div class="project-head"><h2>Proje Listesi</h2><p><?= count($kayitlar) ?> kayıt</p></div>
    <?php if (empty($kayitlar)): ?><div class="project-empty"><i class="fa-solid fa-circle-info"></i> Henüz proje kaydı yok.</div>
    <?php else: ?><div class="project-grid"><?php foreach ($kayitlar as $row): ?><article class="project-item"><h3><?= $h($row['ad']) ?></h3><p><?= $h($row['aciklama']) ?></p><span class="status"><?= $h($durumlar[$row['durum']] ?? $row['durum']) ?></span><div style="margin-top:12px"><a class="project-btn danger" href="<?= BASE_URL ?>/proje/sil/<?= (int)$row['id'] ?>" onclick="return confirm('Proje silinsin mi?')"><i class="fa-solid fa-trash"></i> Sil</a></div></article><?php endforeach; ?></div><?php endif; ?>
  </section>
</div>
