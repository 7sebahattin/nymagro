<?php
/**
 * Masraf Kalemleri — Kategori Yönetimi
 * ────────────────────────────────────────────────────────
 * $kategoriler : ağaç yapısı [{id,ad,renk,altlar:[...]}]
 * $flash       : array
 */

$renkler = ['#5cb85c','#5bc0de','#f0ad4e','#d9534f','#2f7350','#34495e','#e67e22','#2980b9','#1abc9c','#e74c3c','#3498db','#2a6b4a'];
?>
<style>
  :root { --navy:#2c3e6b; --green:#5cb85c; --red:#d9534f; --orange:#f0ad4e; }

  .mk-action { display:flex; gap:8px; margin-bottom:20px; flex-wrap:wrap; }
  .btn-mk-ekle { background:var(--green); color:#fff; padding:7px 15px; border:none; border-radius:4px; font-size:12.5px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:6px; }
  .btn-mk-ekle:hover { filter:brightness(1.1); }
  .btn-mk-masraf { background:var(--red); color:#fff; padding:7px 15px; border:none; border-radius:4px; font-size:12.5px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:6px; text-decoration:none; }
  .btn-mk-masraf:hover { filter:brightness(1.1); color:#fff; }

  /* Grid */
  .mk-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; align-items:start; }
  @media(max-width:800px) { .mk-grid { grid-template-columns:1fr; } }

  .mk-card { background:#fff; border:1px solid #e2e8f0; border-radius:6px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.06); margin-bottom:16px; }
  .mk-header {
    background:var(--navy); color:#fff;
    padding:10px 16px; font-size:13px; font-weight:700; text-transform:uppercase;
    display:flex; justify-content:space-between; align-items:center;
  }
  .mk-header-btns { display:flex; gap:5px; }
  .btn-althesap { background:#f0ad4e; color:#fff; border:none; font-size:11px; font-weight:600; padding:3px 8px; border-radius:3px; cursor:pointer; }
  .btn-althesap:hover { filter:brightness(1.1); }
  .btn-kat-sil  { background:rgba(255,255,255,.2); color:#fff; border:none; font-size:11px; padding:3px 8px; border-radius:3px; cursor:pointer; }
  .btn-kat-sil:hover  { background:rgba(255,0,0,.4); }
  .btn-kat-duzenle { background:rgba(255,255,255,.2); color:#fff; border:none; font-size:11px; padding:3px 8px; border-radius:3px; cursor:pointer; }
  .btn-kat-duzenle:hover { background:rgba(255,255,255,.35); }

  .mk-body { padding:14px 16px; display:flex; flex-wrap:wrap; gap:7px; min-height:50px; }

  .mk-badge {
    display:inline-flex; align-items:center; gap:4px;
    padding:5px 10px; font-size:12px; font-weight:600;
    border-radius:4px; color:#fff; white-space:nowrap; cursor:default;
    position:relative;
  }
  .mk-badge .badge-del {
    display:none; position:absolute; top:-6px; right:-6px;
    width:16px; height:16px; background:#ef4444; color:#fff;
    border-radius:50%; font-size:11px; border:none; cursor:pointer;
    align-items:center; justify-content:center; font-weight:700; line-height:1;
  }
  .mk-badge:hover .badge-del { display:flex; }

  .mk-empty { font-size:12px; color:#94a3b8; font-style:italic; }

  /* ── MODALLER ── */
  .mk-overlay { position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:900; display:none; align-items:center; justify-content:center; }
  .mk-overlay.open { display:flex; }
  .mk-modal { background:#fff; border-radius:7px; width:440px; max-width:96vw; box-shadow:0 8px 40px rgba(0,0,0,.25); overflow:hidden; display:flex; flex-direction:column; }
  .mk-mhdr { background:var(--navy); color:#fff; padding:13px 18px; font-size:14px; font-weight:700; display:flex; justify-content:space-between; align-items:center; }
  .mk-mhdr.green { background:var(--green); }
  .mk-mhdr.blue  { background:#337ab7; }
  .mk-mclose { background:none; border:none; color:#fff; font-size:20px; cursor:pointer; opacity:.8; }
  .mk-mclose:hover { opacity:1; }
  .mk-mbody { padding:20px 22px; display:flex; flex-direction:column; gap:14px; }
  .mk-mrow { display:grid; grid-template-columns:100px 1fr; align-items:center; gap:10px; }
  .mk-mrow label { font-size:12.5px; font-weight:600; color:#374151; }
  .mk-minp, .mk-msel { padding:7px 10px; border:1px solid #cbd5e1; border-radius:5px; font-size:13px; color:#1e293b; outline:none; width:100%; box-sizing:border-box; }
  .mk-minp:focus, .mk-msel:focus { border-color:var(--navy); }
  .mk-mftr { padding:12px 22px; border-top:1px solid #f1f5f9; display:flex; justify-content:flex-end; gap:8px; }
  .mk-mbtn { display:inline-flex; align-items:center; gap:5px; padding:7px 16px; border:none; border-radius:5px; font-size:13px; font-weight:600; cursor:pointer; }
  .mk-mbtn:hover { filter:brightness(1.1); }
  .mk-mbtn.gray  { background:#64748b; color:#fff; }
  .mk-mbtn.green { background:var(--green); color:#fff; }
  .mk-mbtn.blue  { background:#337ab7; color:#fff; }

  /* Renk paleti */
  .renk-paleti { display:flex; flex-wrap:wrap; gap:6px; margin-top:4px; }
  .renk-sec { width:24px; height:24px; border-radius:50%; border:2px solid transparent; cursor:pointer; transition:transform .1s; }
  .renk-sec:hover  { transform:scale(1.2); }
  .renk-sec.active { border-color:#1e293b; }

  /* Toast */
  .toast-container { position:fixed; bottom:22px; right:22px; z-index:9999; display:flex; flex-direction:column; gap:7px; pointer-events:none; }
  .toast-msg { display:flex; align-items:center; gap:9px; padding:11px 16px; border-radius:8px; font-size:13px; font-weight:500; box-shadow:0 3px 14px rgba(0,0,0,.15); background:#fff; pointer-events:auto; }
  .toast-msg.success { border-left:4px solid #22c55e; }
  .toast-msg.error   { border-left:4px solid #ef4444; }

  .nav-link:focus { color:#94a3b8; outline:none; }
  .nav-link.active:focus { color:#4ade80; outline:none; }
</style>

<?php if (!empty($flash)): ?>
<div class="alert alert-<?= $flash['tip'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show mb-3" role="alert">
  <?= htmlspecialchars($flash['mesaj']) ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Aksiyon -->
<div class="mk-action">
  <button class="btn-mk-ekle" onclick="openAnaModal()">
    <i class="fa-solid fa-plus"></i> Yeni Ana Masraf Kalemi Ekle
  </button>
  <a href="<?= BASE_URL ?>/masraf/ekle" class="btn-mk-masraf">
    <i class="fa-solid fa-plus"></i> Yeni Masraf Gir
  </a>
</div>

<!-- Kategori Grid -->
<?php
// Sol ve sağ kolonlara böl
$solKat = array_filter($kategoriler, fn($k, $i) => $i % 2 === 0, ARRAY_FILTER_USE_BOTH);
$sagKat = array_filter($kategoriler, fn($k, $i) => $i % 2 !== 0, ARRAY_FILTER_USE_BOTH);
?>
<div class="mk-grid" id="mkGrid">
  <div id="solKol">
    <?php foreach ($solKat as $kat): ?>
    <?= renderKat($kat) ?>
    <?php endforeach; ?>
  </div>
  <div id="sagKol">
    <?php foreach ($sagKat as $kat): ?>
    <?= renderKat($kat) ?>
    <?php endforeach; ?>
  </div>
</div>

<?php
function renderKat(array $kat): string {
    $renkler = ['#5cb85c','#5bc0de','#f0ad4e','#d9534f','#2f7350','#34495e','#e67e22','#2980b9','#1abc9c','#e74c3c'];
    ob_start();
    ?>
    <div class="mk-card" id="kat-<?= $kat['id'] ?>">
      <div class="mk-header" style="background:<?= htmlspecialchars($kat['renk']) ?>;">
        <span><?= htmlspecialchars($kat['ad']) ?></span>
        <div class="mk-header-btns">
          <button class="btn-kat-duzenle"
                  onclick="openDuzenleModal(<?= $kat['id'] ?>, '<?= addslashes($kat['ad']) ?>', '<?= $kat['renk'] ?>', true)">
            <i class="fa-solid fa-pen"></i>
          </button>
          <button class="btn-althesap"
                  onclick="openAltModal(<?= $kat['id'] ?>, '<?= addslashes($kat['ad']) ?>')">
            Alt Hesap Ekle +
          </button>
          <button class="btn-kat-sil"
                  onclick="katSil(<?= $kat['id'] ?>, '<?= addslashes($kat['ad']) ?>', true)">
            <i class="fa-solid fa-trash"></i>
          </button>
        </div>
      </div>
      <div class="mk-body" id="katBody-<?= $kat['id'] ?>">
        <?php if (empty($kat['altlar'])): ?>
        <span class="mk-empty">Henüz alt kalem eklenmedi.</span>
        <?php else: foreach ($kat['altlar'] as $alt): ?>
        <span class="mk-badge" style="background:<?= htmlspecialchars($alt['renk']) ?>;"
              id="alt-<?= $alt['id'] ?>">
          <?= htmlspecialchars($alt['ad']) ?>
          <button class="badge-del" title="Sil"
                  onclick="katSil(<?= $alt['id'] ?>, '<?= addslashes($alt['ad']) ?>', false)">×</button>
        </span>
        <?php endforeach; endif; ?>
      </div>
    </div>
    <?php
    return ob_get_clean();
}
?>

<!-- ── MODAL: Yeni Ana Kategori ── -->
<div class="mk-overlay" id="mAna">
  <div class="mk-modal">
    <div class="mk-mhdr">
      <span>Yeni Ana Masraf Kalemi</span>
      <button class="mk-mclose" onclick="closeModal('mAna')">&times;</button>
    </div>
    <div class="mk-mbody">
      <div class="mk-mrow">
        <label>Kategori Adı <span style="color:#ef4444;">*</span></label>
        <input type="text" id="mAnaAd" class="mk-minp" placeholder="Kategori adı...">
      </div>
      <div class="mk-mrow" style="align-items:flex-start;">
        <label>Renk</label>
        <div>
          <input type="hidden" id="mAnaRenk" value="#2c3e6b">
          <div class="renk-paleti" id="mAnaPaleti">
            <?php foreach ($renkler as $r): ?>
            <div class="renk-sec" style="background:<?= $r ?>;" data-renk="<?= $r ?>"
                 onclick="secRenk(this,'mAnaRenk','mAnaPaleti')"></div>
            <?php endforeach; ?>
            <div class="renk-sec" style="background:#2c3e6b;" data-renk="#2c3e6b"
                 onclick="secRenk(this,'mAnaRenk','mAnaPaleti')"></div>
          </div>
          <div style="display:flex; align-items:center; gap:8px; margin-top:8px;">
            <span style="font-size:12px; color:#64748b;">Özel:</span>
            <input type="color" id="mAnaRenkPicker" value="#2c3e6b"
                   oninput="document.getElementById('mAnaRenk').value=this.value; highlightNone('mAnaPaleti')"
                   style="width:32px; height:28px; border:1px solid #cbd5e1; border-radius:4px; cursor:pointer;">
          </div>
        </div>
      </div>
    </div>
    <div class="mk-mftr">
      <button class="mk-mbtn gray" onclick="closeModal('mAna')">Vazgeç</button>
      <button class="mk-mbtn green" id="btnAnaKaydet" onclick="anaKaydet()">
        <i class="fa-solid fa-check"></i> Kaydet
      </button>
    </div>
  </div>
</div>

<!-- ── MODAL: Alt Kategori Ekle ── -->
<div class="mk-overlay" id="mAlt">
  <div class="mk-modal">
    <div class="mk-mhdr green">
      <span>Alt Kalem Ekle — <span id="mAltParentAd"></span></span>
      <button class="mk-mclose" onclick="closeModal('mAlt')">&times;</button>
    </div>
    <div class="mk-mbody">
      <input type="hidden" id="mAltParentId" value="">
      <div class="mk-mrow">
        <label>Kalem Adı <span style="color:#ef4444;">*</span></label>
        <input type="text" id="mAltAd" class="mk-minp" placeholder="Alt kalem adı...">
      </div>
      <div class="mk-mrow" style="align-items:flex-start;">
        <label>Renk</label>
        <div>
          <input type="hidden" id="mAltRenk" value="#5cb85c">
          <div class="renk-paleti" id="mAltPaleti">
            <?php foreach ($renkler as $r): ?>
            <div class="renk-sec" style="background:<?= $r ?>;" data-renk="<?= $r ?>"
                 onclick="secRenk(this,'mAltRenk','mAltPaleti')"></div>
            <?php endforeach; ?>
          </div>
          <div style="display:flex; align-items:center; gap:8px; margin-top:8px;">
            <span style="font-size:12px; color:#64748b;">Özel:</span>
            <input type="color" id="mAltRenkPicker" value="#5cb85c"
                   oninput="document.getElementById('mAltRenk').value=this.value; highlightNone('mAltPaleti')"
                   style="width:32px; height:28px; border:1px solid #cbd5e1; border-radius:4px; cursor:pointer;">
          </div>
        </div>
      </div>
    </div>
    <div class="mk-mftr">
      <button class="mk-mbtn gray" onclick="closeModal('mAlt')">Vazgeç</button>
      <button class="mk-mbtn green" id="btnAltKaydet" onclick="altKaydet()">
        <i class="fa-solid fa-check"></i> Ekle
      </button>
    </div>
  </div>
</div>

<!-- ── MODAL: Düzenle ── -->
<div class="mk-overlay" id="mDuzenle">
  <div class="mk-modal">
    <div class="mk-mhdr blue">
      <span>Kategori Düzenle</span>
      <button class="mk-mclose" onclick="closeModal('mDuzenle')">&times;</button>
    </div>
    <div class="mk-mbody">
      <input type="hidden" id="mDuzId">
      <div class="mk-mrow">
        <label>Ad <span style="color:#ef4444;">*</span></label>
        <input type="text" id="mDuzAd" class="mk-minp" placeholder="Kategori adı...">
      </div>
      <div class="mk-mrow" style="align-items:flex-start;">
        <label>Renk</label>
        <div>
          <input type="hidden" id="mDuzRenk" value="#5bc0de">
          <div class="renk-paleti" id="mDuzPaleti">
            <?php foreach ($renkler as $r): ?>
            <div class="renk-sec" style="background:<?= $r ?>;" data-renk="<?= $r ?>"
                 onclick="secRenk(this,'mDuzRenk','mDuzPaleti')"></div>
            <?php endforeach; ?>
            <div class="renk-sec" style="background:#2c3e6b;" data-renk="#2c3e6b"
                 onclick="secRenk(this,'mDuzRenk','mDuzPaleti')"></div>
          </div>
          <div style="display:flex; align-items:center; gap:8px; margin-top:8px;">
            <span style="font-size:12px; color:#64748b;">Özel:</span>
            <input type="color" id="mDuzRenkPicker" value="#5bc0de"
                   oninput="document.getElementById('mDuzRenk').value=this.value; highlightNone('mDuzPaleti')"
                   style="width:32px; height:28px; border:1px solid #cbd5e1; border-radius:4px; cursor:pointer;">
          </div>
        </div>
      </div>
    </div>
    <div class="mk-mftr">
      <button class="mk-mbtn gray" onclick="closeModal('mDuzenle')">Vazgeç</button>
      <button class="mk-mbtn blue" id="btnDuzKaydet" onclick="duzenleKaydet()">
        <i class="fa-solid fa-check"></i> Güncelle
      </button>
    </div>
  </div>
</div>

<!-- Toast -->
<div class="toast-container" id="toastContainer"></div>

<script>
const BASE = '<?= BASE_URL ?>';

/* ── MODAL ──────────────────────────────────────────── */
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') document.querySelectorAll('.mk-overlay.open').forEach(el => el.classList.remove('open'));
});

/* ── ANA KATEGORİ MODAL ─────────────────────────────── */
function openAnaModal() {
  document.getElementById('mAnaAd').value = '';
  document.getElementById('mAnaRenk').value = '#2c3e6b';
  highlightNone('mAnaPaleti');
  openModal('mAna');
  setTimeout(() => document.getElementById('mAnaAd').focus(), 100);
}

function anaKaydet() {
  const ad   = document.getElementById('mAnaAd').value.trim();
  const renk = document.getElementById('mAnaRenk').value;
  if (!ad) { showToast('Kategori adı boş olamaz.', 'error'); return; }

  const btn = document.getElementById('btnAnaKaydet');
  btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

  const fd = new FormData();
  fd.append('ad',        ad);
  fd.append('renk',      renk);
  fd.append('parent_id', '');

  fetch(BASE + '/masraf/kategoriKaydet', { method:'POST', body:fd })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        showToast(res.message, 'success');
        closeModal('mAna');
        setTimeout(() => location.reload(), 800);
      } else {
        showToast(res.message, 'error');
      }
    })
    .catch(() => showToast('Bağlantı hatası.', 'error'))
    .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-check"></i> Kaydet'; });
}

/* ── ALT KATEGORİ MODAL ─────────────────────────────── */
function openAltModal(parentId, parentAd) {
  document.getElementById('mAltParentId').value = parentId;
  document.getElementById('mAltParentAd').textContent = parentAd;
  document.getElementById('mAltAd').value = '';
  document.getElementById('mAltRenk').value = '#5cb85c';
  highlightNone('mAltPaleti');
  openModal('mAlt');
  setTimeout(() => document.getElementById('mAltAd').focus(), 100);
}

function altKaydet() {
  const ad       = document.getElementById('mAltAd').value.trim();
  const renk     = document.getElementById('mAltRenk').value;
  const parentId = document.getElementById('mAltParentId').value;
  if (!ad) { showToast('Kalem adı boş olamaz.', 'error'); return; }

  const btn = document.getElementById('btnAltKaydet');
  btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

  const fd = new FormData();
  fd.append('ad',        ad);
  fd.append('renk',      renk);
  fd.append('parent_id', parentId);

  fetch(BASE + '/masraf/kategoriKaydet', { method:'POST', body:fd })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        showToast(res.message, 'success');
        closeModal('mAlt');
        // DOM'a badge ekle
        const body = document.getElementById('katBody-' + parentId);
        if (body) {
          const empty = body.querySelector('.mk-empty');
          if (empty) empty.remove();
          const badge = document.createElement('span');
          badge.className = 'mk-badge';
          badge.id = 'alt-' + res.id;
          badge.style.background = renk;
          badge.innerHTML = ad + `<button class="badge-del" title="Sil" onclick="katSil(${res.id},'${ad.replace(/'/g,"\\'")}',false)">×</button>`;
          body.appendChild(badge);
        }
      } else {
        showToast(res.message, 'error');
      }
    })
    .catch(() => showToast('Bağlantı hatası.', 'error'))
    .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-check"></i> Ekle'; });
}

/* ── DÜZENLE MODAL ──────────────────────────────────── */
function openDuzenleModal(id, ad, renk, isAna) {
  document.getElementById('mDuzId').value  = id;
  document.getElementById('mDuzAd').value  = ad;
  document.getElementById('mDuzRenk').value = renk;
  document.getElementById('mDuzRenkPicker').value = renk;
  // Renk paletinde aktif işaret
  document.querySelectorAll('#mDuzPaleti .renk-sec').forEach(el => {
    el.classList.toggle('active', el.dataset.renk === renk);
  });
  openModal('mDuzenle');
  setTimeout(() => document.getElementById('mDuzAd').focus(), 100);
}

function duzenleKaydet() {
  const id   = document.getElementById('mDuzId').value;
  const ad   = document.getElementById('mDuzAd').value.trim();
  const renk = document.getElementById('mDuzRenk').value;
  if (!ad) { showToast('Ad boş olamaz.', 'error'); return; }

  const btn = document.getElementById('btnDuzKaydet');
  btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

  const fd = new FormData();
  fd.append('id',   id);
  fd.append('ad',   ad);
  fd.append('renk', renk);

  fetch(BASE + '/masraf/kategoriGuncelle', { method:'POST', body:fd })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        showToast(res.message, 'success');
        closeModal('mDuzenle');
        setTimeout(() => location.reload(), 800);
      } else {
        showToast(res.message, 'error');
      }
    })
    .catch(() => showToast('Bağlantı hatası.', 'error'))
    .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-check"></i> Güncelle'; });
}

/* ── KATEGORİ SİL ────────────────────────────────────── */
function katSil(id, ad, isAna) {
  const msg = isAna
    ? `"${ad}" ana kategorisini silmek istediğinizden emin misiniz?\nAlt kalemleri olmayan kategoriler silinebilir.`
    : `"${ad}" kalemini silmek istediğinizden emin misiniz?`;
  if (!confirm(msg)) return;

  fetch(BASE + '/masraf/kategoriSil/' + id)
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        showToast(res.message, 'success');
        // DOM'dan kaldır
        const el = document.getElementById(isAna ? ('kat-' + id) : ('alt-' + id));
        if (el) el.remove();
      } else {
        showToast(res.message, 'error');
      }
    })
    .catch(() => showToast('Bağlantı hatası.', 'error'));
}

/* ── RENK PALETİ ─────────────────────────────────────── */
function secRenk(el, hiddenId, paletiId) {
  document.getElementById(hiddenId).value = el.dataset.renk;
  document.querySelectorAll('#' + paletiId + ' .renk-sec').forEach(r => r.classList.remove('active'));
  el.classList.add('active');
}
function highlightNone(paletiId) {
  document.querySelectorAll('#' + paletiId + ' .renk-sec').forEach(r => r.classList.remove('active'));
}

/* ── TOAST ───────────────────────────────────────────── */
function showToast(msg, type = 'info') {
  const icons = { success:'check-circle', error:'circle-xmark', info:'circle-info' };
  const c  = document.getElementById('toastContainer');
  const el = document.createElement('div');
  el.className = 'toast-msg ' + type;
  el.innerHTML = `<i class="fa-solid fa-${icons[type] || 'circle-info'}"></i> ${msg}`;
  c.appendChild(el);
  setTimeout(() => el.remove(), 3500);
}

/* ── SİDEBAR ACCORDION ───────────────────────────────── */
document.querySelectorAll('.submenu').forEach(sub => {
  const link = document.querySelector(`[data-bs-target="#${sub.id}"]`);
  if (!link) return;
  sub.addEventListener('show.bs.collapse', () => {
    link.setAttribute('aria-expanded', 'true');
    document.querySelectorAll('.submenu.show').forEach(o => {
      if (o.id !== sub.id) bootstrap.Collapse.getInstance(o)?.hide();
    });
  });
  sub.addEventListener('hide.bs.collapse', () => link.setAttribute('aria-expanded', 'false'));
});
</script>
