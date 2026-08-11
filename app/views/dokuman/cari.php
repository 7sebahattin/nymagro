<?php
$cari = $cari ?? [];
$cariTip = $cariTip ?? 'musteri';
$dokumanlar = $dokumanlar ?? [];
$flash = $flash ?? [];
$isMusteri = $cariTip === 'musteri';
$entityTitle = $isMusteri ? 'Müşteri' : 'Tedarikçi';
$backUrl = BASE_URL . '/' . ($isMusteri ? 'musteri' : 'tedarikci') . '/detay/' . (int)($cari['id'] ?? 0);
$uploadUrl = BASE_URL . '/dokuman/yukle';

$formatBytes = static function ($bytes): string {
    $bytes = (int)$bytes;
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2, ',', '.') . ' MB';
    }
    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 1, ',', '.') . ' KB';
    }
    return $bytes . ' B';
};

$formatDate = static function ($value): string {
    $ts = strtotime((string)$value);
    return $ts ? date('d.m.Y H:i', $ts) : '-';
};
?>

<style>
.doc-page{display:flex;flex-direction:column;gap:18px}
.doc-actions{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.doc-btn{display:inline-flex;align-items:center;gap:8px;border:0;border-radius:4px;padding:10px 14px;color:#fff;text-decoration:none;font-size:13px;font-weight:800;box-shadow:0 5px 13px rgba(15,23,42,.18)}
.doc-btn:hover{color:#fff;filter:brightness(1.05);transform:translateY(-1px)}
.doc-green{background:#40b94f}.doc-orange{background:#f3a635}.doc-blue{background:#2f83c7}.doc-red{background:#cf4444}
.doc-profile{max-width:820px;min-height:116px;border-radius:4px;background:
  linear-gradient(90deg,rgba(255,255,255,.36),rgba(255,255,255,.08)),
  repeating-linear-gradient(90deg,rgba(166,111,45,.10) 0 18px,rgba(255,255,255,.12) 18px 36px),
  linear-gradient(135deg,#f5d9a7,#f1c982);
  box-shadow:0 10px 25px rgba(15,23,42,.18);display:flex;align-items:center;gap:22px;padding:20px 28px}
.doc-avatar{width:74px;height:74px;border-radius:50%;background:#d8d8d8;border:6px solid rgba(255,255,255,.72);display:flex;align-items:center;justify-content:center;color:#7b7b7b;font-size:28px;font-weight:900;flex:0 0 auto}
.doc-profile h2{margin:0;color:#6f6f78;font-size:24px;font-weight:900;text-transform:uppercase;line-height:1.2}
.doc-profile small{display:block;margin-top:7px;color:#7d7d86;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.1em}
.doc-shell{display:grid;grid-template-columns:minmax(300px,420px) minmax(0,1fr);gap:22px;align-items:start}
.doc-card{background:var(--card-bg);border-radius:5px;box-shadow:0 14px 30px rgba(15,23,42,.16);padding:20px}
.doc-card h3{margin:0 0 16px;color:var(--text);font-size:16px;font-weight:900}
.doc-form{display:flex;flex-direction:column;gap:13px}
.doc-form label{font-size:12px;font-weight:900;color:var(--text2);margin-bottom:6px}
.doc-form .form-control{border-color:var(--border);border-radius:6px}
.doc-help{font-size:12px;color:var(--muted);line-height:1.45}
.doc-info{background:rgba(243,156,18,.15);color:var(--warning);border-radius:4px;padding:18px;font-size:14px;line-height:1.5;box-shadow:0 10px 22px rgba(15,23,42,.12)}
.doc-table{width:100%;border-collapse:collapse;font-size:13px}
.doc-table th{color:var(--muted);font-weight:900;border-bottom:1px solid var(--border);padding:10px}
.doc-table td{border-bottom:1px solid var(--border);padding:12px 10px;color:var(--text2);vertical-align:middle}
.doc-name{font-weight:800;color:var(--text)}
.doc-desc{display:block;color:var(--muted);font-size:12px;margin-top:3px}
.doc-row-actions{display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap}
.doc-alert{border-radius:6px;padding:12px 14px;font-size:13px;font-weight:700}
.doc-alert.success{background:rgba(46,204,113,.15);color:var(--success)}.doc-alert.error{background:rgba(231,76,60,.15);color:var(--danger)}
@media(max-width:980px){.doc-shell{grid-template-columns:1fr}.doc-profile{max-width:none}}
@media(max-width:680px){
  .doc-actions{display:grid;grid-template-columns:1fr 1fr}.doc-btn{justify-content:center}
  .doc-profile{padding:18px;gap:14px}.doc-avatar{width:58px;height:58px;font-size:22px}.doc-profile h2{font-size:18px}
  .doc-table,.doc-table tbody,.doc-table tr,.doc-table td{display:block;width:100%}
  .doc-table thead{display:none}.doc-table tr{border-bottom:1px solid var(--border);padding:10px 0}.doc-table td{border-bottom:0;padding:6px 0}
  .doc-row-actions{justify-content:flex-start}
}
</style>

<div class="doc-page">
  <?php if (!empty($flash)): ?>
    <div class="doc-alert <?= htmlspecialchars($flash['tip'] ?? 'success') ?>"><?= htmlspecialchars($flash['mesaj'] ?? '') ?></div>
  <?php endif; ?>

  <div class="doc-actions">
    <?php if (Rbac::currentUserCan('DOKUMAN_CREATE')): ?>
    <a class="doc-btn doc-green" href="#uploadForm"><i class="fa-solid fa-plus"></i> Yeni Belge Yükle</a>
    <?php endif; ?>
    <a class="doc-btn doc-orange" href="<?= htmlspecialchars($backUrl) ?>"><i class="fa-solid fa-reply"></i> Geri Dön</a>
  </div>

  <div class="doc-profile">
    <div class="doc-avatar"><i class="fa-solid <?= $isMusteri ? 'fa-user' : 'fa-industry' ?>"></i></div>
    <div>
      <h2><?= htmlspecialchars($cari['unvan'] ?? '-') ?></h2>
      <small><?= htmlspecialchars($entityTitle) ?> döküman arşivi</small>
    </div>
  </div>

  <div class="doc-shell">
    <?php if (Rbac::currentUserCan('DOKUMAN_CREATE')): ?>
    <section class="doc-card" id="uploadForm">
      <h3><i class="fa-regular fa-file-lines"></i> Belge Yükle</h3>
      <form class="doc-form" method="post" action="<?= htmlspecialchars($uploadUrl) ?>" enctype="multipart/form-data">
        <input type="hidden" name="cari_id" value="<?= (int)($cari['id'] ?? 0) ?>">
        <input type="hidden" name="cari_tip" value="<?= htmlspecialchars($cariTip) ?>">
        <div>
          <label>Belge Başlığı</label>
          <input type="text" name="baslik" class="form-control" placeholder="Örn. Sözleşme, dekont, teklif">
        </div>
        <div>
          <label>Açıklama</label>
          <textarea name="aciklama" class="form-control" rows="3" placeholder="İsteğe bağlı kısa not"></textarea>
        </div>
        <div>
          <label>Dosya *</label>
          <input type="file" name="dosya" class="form-control" required>
          <div class="doc-help mt-2">PDF, görsel, Word, Excel, metin ve CSV dosyaları yüklenebilir. En fazla 15 MB.</div>
        </div>
        <button type="submit" class="doc-btn doc-green"><i class="fa-solid fa-upload"></i> Yükle</button>
      </form>
    </section>
    <?php endif; ?>

    <section class="doc-card">
      <h3><i class="fa-solid fa-folder-open"></i> Kayıtlı Belgeler</h3>
      <?php if (empty($dokumanlar)): ?>
        <div class="doc-info">
          <?= htmlspecialchars($entityTitle) ?> ile ilgili henüz yüklenmiş belge yok. Sözleşme, teklif, banka dekontu veya resim gibi dosyaları buraya ekleyebilirsiniz.
        </div>
      <?php else: ?>
        <table class="doc-table">
          <thead>
            <tr>
              <th>Belge</th>
              <th>Boyut</th>
              <th>Tarih</th>
              <th style="text-align:right">İşlem</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($dokumanlar as $doc): ?>
              <tr>
                <td>
                  <span class="doc-name"><?= htmlspecialchars(($doc['baslik'] ?? '') !== '' ? $doc['baslik'] : $doc['orijinal_ad']) ?></span>
                  <span class="doc-desc"><?= htmlspecialchars($doc['orijinal_ad'] ?? '-') ?></span>
                  <?php if (!empty($doc['aciklama'])): ?>
                    <span class="doc-desc"><?= htmlspecialchars($doc['aciklama']) ?></span>
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($formatBytes($doc['boyut'] ?? 0)) ?></td>
                <td><?= htmlspecialchars($formatDate($doc['created_at'] ?? '')) ?></td>
                <td>
                  <div class="doc-row-actions">
                    <a class="doc-btn doc-blue" href="<?= BASE_URL ?>/dokuman/indir/<?= (int)$doc['id'] ?>"><i class="fa-solid fa-download"></i> İndir</a>
                    <?php if (Rbac::currentUserCan('DOKUMAN_DELETE')): ?>
                    <a class="doc-btn doc-red" href="#" onclick="return nymPost('<?= BASE_URL ?>/dokuman/sil/<?= (int)$doc['id'] ?>?tip=<?= urlencode($cariTip) ?>', 'Bu dökümanı listeden kaldırmak istiyor musunuz?')"><i class="fa-solid fa-xmark"></i> Sil</a>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </section>
  </div>
</div>
