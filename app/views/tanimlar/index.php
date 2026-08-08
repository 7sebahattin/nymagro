<?php
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$types = $types ?? [];
$grouped = $grouped ?? [];
$flash = $flash ?? [];
$cardsLeft = [
    'urun_marka' => ['Ürün Markalarınız', ''],
    'urun_kategori' => ['Ürün Kategorileriniz', ''],
    'raf_yeri' => ['Raf Yerleriniz', ''],
];
$cardsRight = [
    'musteri_sinif_1' => ['Müşteri Sınıflandırmanız 1', 'bg-creamy'],
    'musteri_sinif_2' => ['Müşteri Sınıflandırmanız 2', 'bg-creamy'],
    'tedarikci_sinif_1' => ['Tedarikçi Sınıflandırmanız 1', 'bg-mint'],
    'tedarikci_sinif_2' => ['Tedarikçi Sınıflandırmanız 2', 'bg-mint'],
    'fihrist_grup_1' => ['Fihrist Gruplarınız 1', ''],
    'fihrist_grup_2' => ['Fihrist Gruplarınız 2', ''],
];
$badgeClass = fn($renk) => [
    'red' => 'tn-badge-outline-red',
    'green' => 'tn-badge-green',
    'cyan' => 'tn-badge-cyan',
    'grey' => 'tn-badge-outline-grey',
][$renk] ?? 'tn-badge-green';
?>

<style>
.tn-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; align-items:start; }
.tn-card { background:var(--card-bg); border:1px solid var(--border); border-radius:4px; box-shadow:0 1px 3px rgba(0,0,0,.1); overflow:hidden; margin-bottom:20px; }
.tn-header { background:#337ab7; color:#fff; padding:10px 16px; font-size:13px; font-weight:700; text-transform:uppercase; display:flex; align-items:center; justify-content:space-between; gap:10px; }
.tn-add { border:0; background:rgba(255,255,255,.18); color:#fff; width:24px; height:24px; border-radius:4px; display:inline-flex; align-items:center; justify-content:center; }
.tn-add:hover { background:rgba(255,255,255,.28); }
.tn-body { padding:16px; display:flex; flex-wrap:wrap; gap:8px; min-height:52px; }
.tn-body.bg-creamy { background:rgba(243,156,18,.11); border-top:1px solid rgba(243,156,18,.28); }
.tn-body.bg-mint { background:rgba(46,204,113,.13); border-top:1px solid rgba(46,204,113,.28); }
.tn-empty { color:var(--muted); font-size:13px; font-style:italic; padding:4px 0; }
.tn-badge { padding:5px 12px; font-size:11.5px; font-weight:600; border-radius:3px; cursor:pointer; border:1px solid transparent; display:inline-flex; gap:8px; align-items:center; }
.tn-badge:hover { filter:brightness(1.06); box-shadow:0 2px 8px rgba(0,0,0,.12); }
.tn-badge-outline-red { background:var(--card-bg); color:#d9534f; border-color:#d9534f; }
.tn-badge-green { background:#5cb85c; color:#fff; }
.tn-badge-cyan { background:#5bc0de; color:#fff; }
.tn-badge-outline-grey { background:var(--card-bg); color:var(--text2); border-color:var(--border); }
.tn-alert { border-radius:6px; padding:10px 12px; margin-bottom:14px; font-size:13px; font-weight:700; }
.tn-alert.success { background:rgba(46,204,113,.15); border:1px solid #86efac; color:var(--success); }
.tn-alert.error { background:rgba(231,76,60,.15); border:1px solid #fca5a5; color:var(--danger); }
.tn-modal-backdrop { position:fixed; inset:0; background:rgba(15,23,42,.55); z-index:900; display:none; align-items:center; justify-content:center; padding:16px; }
.tn-modal-backdrop.open { display:flex; }
.tn-modal { width:420px; max-width:100%; background:var(--card-bg); border-radius:6px; box-shadow:0 16px 48px rgba(0,0,0,.28); overflow:hidden; }
.tn-modal-header { background:#337ab7; color:#fff; padding:12px 16px; display:flex; align-items:center; justify-content:space-between; font-weight:800; }
.tn-modal-body { padding:16px; display:grid; gap:12px; }
.tn-field label { display:block; font-size:12px; font-weight:800; color:var(--text2); margin-bottom:4px; }
.tn-field input, .tn-field select { width:100%; height:36px; border:1px solid var(--border2); border-radius:5px; padding:7px 9px; font-size:13px; }
.tn-modal-footer { padding:12px 16px; border-top:1px solid var(--border); display:flex; justify-content:space-between; gap:8px; }
.tn-btn { border:0; border-radius:5px; padding:8px 13px; font-size:13px; font-weight:700; display:inline-flex; align-items:center; gap:6px; text-decoration:none; cursor:pointer; }
.tn-btn.green { background:#5cb85c; color:#fff; }
.tn-btn.red { background:#d9534f; color:#fff; }
.tn-btn.grey { background:#64748b; color:#fff; }
@media(max-width:800px) { .tn-grid { grid-template-columns:1fr; } }
</style>

<?php if (!empty($flash)): ?>
  <div class="tn-alert <?= $e($flash['tip'] ?? '') ?>"><?= $e($flash['mesaj'] ?? '') ?></div>
<?php endif; ?>

<div style="margin-bottom:20px;">
  <div class="dropdown" style="display:inline-block;">
    <button class="btn btn-success btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="font-weight:600; padding:6px 12px; background:#5cb85c; border:none;">
      <i class="fa-solid fa-plus"></i> Yeni Tanım Ekle
    </button>
    <ul class="dropdown-menu" style="font-size:13px; border-radius:4px; box-shadow:0 6px 15px rgba(0,0,0,.15); padding:6px 0;">
      <?php foreach ($types as $type => $label): ?>
        <?php if (in_array($type, ['musteri_sinif_1','tedarikci_sinif_1','fihrist_grup_1','raf_yeri'], true)): ?>
          <li><hr class="dropdown-divider" style="margin:4px 0;"></li>
        <?php endif; ?>
        <li><button class="dropdown-item" type="button" onclick="openTanimModal('<?= $e($type) ?>')"><?= $e($label) ?> Ekle</button></li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>

<?php
$renderCard = function (string $type, string $title, string $bodyClass = '') use ($e, $grouped, $badgeClass) {
?>
  <div class="tn-card">
    <div class="tn-header">
      <span><?= $e($title) ?></span>
      <button class="tn-add" type="button" onclick="openTanimModal('<?= $e($type) ?>')" title="Yeni ekle"><i class="fa-solid fa-plus"></i></button>
    </div>
    <div class="tn-body <?= $e($bodyClass) ?>">
      <?php if (empty($grouped[$type])): ?>
        <span class="tn-empty">Henüz tanım yok.</span>
      <?php else: ?>
        <?php foreach ($grouped[$type] as $item): ?>
          <span class="tn-badge <?= $e($badgeClass($item['renk'])) ?>"
                onclick="openTanimModal('<?= $e($item['tur']) ?>', '<?= (int)$item['id'] ?>', '<?= $e($item['ad']) ?>', '<?= $e($item['renk']) ?>')">
            <?= $e($item['ad']) ?>
            <i class="fa-solid fa-pen-to-square"></i>
          </span>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
<?php
};
?>

<div class="tn-grid">
  <div>
    <?php foreach ($cardsLeft as $type => [$title, $bodyClass]) $renderCard($type, $title, $bodyClass); ?>
  </div>
  <div>
    <?php foreach ($cardsRight as $type => [$title, $bodyClass]) $renderCard($type, $title, $bodyClass); ?>
  </div>
</div>

<div class="tn-modal-backdrop" id="tanimModal">
  <form class="tn-modal" method="post" action="<?= BASE_URL ?>/tanim/kaydet">
    <div class="tn-modal-header">
      <span id="modalTitle">Yeni Tanım</span>
      <button type="button" class="tn-add" onclick="closeTanimModal()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="tn-modal-body">
      <input type="hidden" name="id" id="tanimId">
      <div class="tn-field">
        <label>Tanım Türü</label>
        <select name="tur" id="tanimTur" required>
          <?php foreach ($types as $type => $label): ?>
            <option value="<?= $e($type) ?>"><?= $e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="tn-field">
        <label>Tanım Adı</label>
        <input name="ad" id="tanimAd" required autocomplete="off">
      </div>
      <div class="tn-field">
        <label>Renk</label>
        <select name="renk" id="tanimRenk">
          <option value="green">Yeşil</option>
          <option value="cyan">Mavi</option>
          <option value="red">Kırmızı Çerçeve</option>
          <option value="grey">Gri Çerçeve</option>
        </select>
      </div>
    </div>
    <div class="tn-modal-footer">
      <a href="#" id="deleteLink" class="tn-btn red" style="visibility:hidden;" onclick="return confirm('Bu tanım silinsin mi?')"><i class="fa-solid fa-trash"></i> Sil</a>
      <div style="display:flex; gap:8px;">
        <button type="button" class="tn-btn grey" onclick="closeTanimModal()">Vazgeç</button>
        <button class="tn-btn green" type="submit"><i class="fa-solid fa-check"></i> Kaydet</button>
      </div>
    </div>
  </form>
</div>

<script>
const TANIM_TYPES = <?= json_encode($types, JSON_UNESCAPED_UNICODE) ?>;
const BASE_URL_TANIM = '<?= BASE_URL ?>';

function openTanimModal(type, id = '', name = '', color = 'green') {
  document.getElementById('tanimId').value = id;
  document.getElementById('tanimTur').value = type;
  document.getElementById('tanimAd').value = name;
  document.getElementById('tanimRenk').value = color || 'green';
  document.getElementById('modalTitle').textContent = id ? 'Tanımı Düzenle' : ((TANIM_TYPES[type] || 'Tanım') + ' Ekle');
  const del = document.getElementById('deleteLink');
  if (id) {
    del.href = BASE_URL_TANIM + '/tanim/sil/' + id;
    del.style.visibility = 'visible';
  } else {
    del.href = '#';
    del.style.visibility = 'hidden';
  }
  document.getElementById('tanimModal').classList.add('open');
  setTimeout(() => document.getElementById('tanimAd').focus(), 80);
}

function closeTanimModal() {
  document.getElementById('tanimModal').classList.remove('open');
}

document.getElementById('tanimModal').addEventListener('click', function (e) {
  if (e.target === this) closeTanimModal();
});
</script>
