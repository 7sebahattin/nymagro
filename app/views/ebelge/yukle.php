<?php
/** @var array $limitler */
$h  = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$mb = fn($b) => number_format((float)$b / 1048576, 0, ',', '.');
?>
<style>
.ebl-box{background:var(--card-bg);border:1px solid var(--border);border-radius:6px;padding:22px;max-width:820px}
.ebl-btn{border:0;border-radius:4px;padding:8px 14px;font-size:13px;font-weight:700;color:#fff;text-decoration:none;display:inline-flex;gap:6px;align-items:center;background:#337ab7;cursor:pointer}
.ebl-btn.gray{background:#64748b}
.ebl-file{width:100%;border:1px solid var(--border2);border-radius:5px;padding:10px;margin:10px 0 16px}
.ebl-hint{background:var(--surface-2);border:1px solid var(--border);border-radius:6px;padding:14px;margin-top:18px;color:var(--text2);font-size:13px}
.ebl-hint ul{margin:8px 0 0;padding-left:18px}
.ebl-hint li{margin-bottom:4px}
</style>

<?php if (!empty($flash)): ?>
  <div class="alert alert-<?= $flash['tip'] === 'success' ? 'success' : ($flash['tip'] === 'warning' ? 'warning' : 'danger') ?> mb-3"><?= $h($flash['mesaj']) ?></div>
<?php endif; ?>

<?php if (empty($semaHazir)): ?>
  <div class="alert alert-danger mb-3">e-Belge tabloları hazır değil; yükleme yapılamaz. Sunucu hata günlüğünü kontrol edin.</div>
<?php endif; ?>

<div class="ebl-box">
  <h4 style="margin:0 0 8px">e-Belge (XML) Yükle</h4>
  <p style="color:var(--muted);margin-bottom:16px">
    TÜRMOB / Luca üzerinden indirdiğiniz <strong>e-Fatura</strong>, <strong>e-Arşiv Fatura</strong> ve
    <strong>e-İrsaliye</strong> XML dosyalarını tek tek ya da ZIP arşivi olarak yükleyebilirsiniz.
    Belgeler yalnızca <em>içeri alınır ve doğrulanır</em>; muhasebe kayıtları (fatura, stok, cari)
    bu adımda <strong>oluşturulmaz</strong>.
  </p>

  <form method="post" enctype="multipart/form-data" action="<?= BASE_URL ?>/ebelge/yukle">
    <?= Csrf::fieldHtml() ?>
    <label style="font-weight:700;font-size:13px">XML veya ZIP dosyası</label>
    <input class="ebl-file" type="file" name="ebelge" accept=".xml,.zip,text/xml,application/xml,application/zip" required>
    <button class="ebl-btn" type="submit"><i class="fa-solid fa-upload"></i> Yükle ve Ayrıştır</button>
    <a class="ebl-btn gray" href="<?= BASE_URL ?>/ebelge"><i class="fa-solid fa-rotate-left"></i> Geri Dön</a>
  </form>

  <div class="ebl-hint">
    <strong>Nasıl çalışır?</strong>
    <ul>
      <li>Dosya güvenlik denetiminden geçirilir (DTD/varlık içeren, kök elemanı UBL olmayan ya da
          sınırları aşan dosyalar reddedilir).</li>
      <li>Ham XML, SHA-256 özetiyle adlandırılarak saklanır — <strong>aynı dosya ikinci kez yüklenirse
          mükerrer kayıt oluşmaz</strong>, "daha önce yüklenmiş" bilgisi verilir.</li>
      <li>Aynı ETTN (UUID) taşıyan bir belge sistemde varsa yeni kayıt açılmaz.</li>
      <li>e-İrsaliye belgeleri şimdilik yalnızca <em>izleme</em> amaçlı saklanır; aktarımı kapalıdır.</li>
    </ul>
    <div style="margin-top:10px">
      <strong>Sınırlar:</strong>
      XML en fazla <?= $mb($limitler['xml']) ?> MB ·
      ZIP en fazla <?= $mb($limitler['zip']) ?> MB ·
      ZIP içinde en fazla <?= (int)$limitler['entries'] ?> dosya.
      Sunucunuzun <code>upload_max_filesize</code> ayarı bunlardan düşükse önce o sınır devreye girer.
    </div>
  </div>
</div>
