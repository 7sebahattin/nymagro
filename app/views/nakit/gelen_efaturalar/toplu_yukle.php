<style>
.gef-box{background:#fff;border:1px solid #e2e8f0;border-radius:6px;padding:22px;max-width:760px}
.gef-btn{border:0;border-radius:4px;padding:8px 14px;font-size:13px;font-weight:700;color:#fff;text-decoration:none;display:inline-flex;gap:6px;align-items:center;background:#337ab7;cursor:pointer}
.gef-btn.gray{background:#64748b}.gef-hint{background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:14px;margin-top:18px;color:#475569;font-size:13px}
.gef-file{width:100%;border:1px solid #cbd5e1;border-radius:5px;padding:10px;margin:10px 0 16px}
</style>

<?php if (!empty($flash)): ?>
<div class="alert alert-<?= $flash['tip'] === 'success' ? 'success' : 'danger' ?> mb-3"><?= htmlspecialchars($flash['mesaj']) ?></div>
<?php endif; ?>

<div class="gef-box">
  <h4 style="margin:0 0 8px">Excel ile Gelen E-Fatura Yükle</h4>
  <p style="color:#64748b;margin-bottom:16px">Sistem “Giderler” sayfasındaki A:AJ kolonlarını okur, çok satırlı faturaları tek fatura ve çok kalem olarak gruplar. Kayıt yapılmadan önce önizleme açılır.</p>

  <form method="post" enctype="multipart/form-data" action="<?= BASE_URL ?>/nakit/gelen-e-faturalar/toplu-yukle">
    <label style="font-weight:700;font-size:13px">XLSX Dosyası</label>
    <input class="gef-file" type="file" name="excel" accept=".xlsx" required>
    <button class="gef-btn" type="submit"><i class="fa-solid fa-upload"></i> Dosyayı Oku ve Önizle</button>
    <a class="gef-btn gray" href="<?= BASE_URL ?>/nakit/gelen-e-faturalar"><i class="fa-solid fa-rotate-left"></i> Geri Dön</a>
  </form>

  <div class="gef-hint">
    <strong>Okuma mantığı:</strong> Q kolonu doluysa yeni fatura başlar. Q boş ama W veya Y doluysa satır bir önceki faturanın kalemi kabul edilir. Excel serial tarihler otomatik gerçek tarihe çevrilir.
  </div>
</div>
