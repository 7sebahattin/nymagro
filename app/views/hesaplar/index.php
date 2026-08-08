<?php
/**
 * Hesaplarım — index view
 * $grouped : ['kasa'=>[...], 'banka'=>[...], 'pos'=>[...], 'kredi_karti'=>[...], ...]
 * $flash   : ['type'=>..., 'message'=>...]
 */

// Tip → Başlık haritası
$tipBaslik = [
    'kasa'        => 'KASA TANIMLARI',
    'banka'       => 'BANKA HESAPLARI',
    'pos'         => 'POS HESAPLARI',
    'kredi_karti' => 'KREDİ KARTLARI',
    'ortak'       => 'ŞİRKET ORTAKLARI HESAPLARI',
    'veresiye'    => 'VERESİYE HESAPLARI',
];

// Sol ve sağ sütun tipleri
$solTipler  = ['kasa', 'pos', 'kredi_karti'];
$sagTipler  = ['banka', 'ortak', 'veresiye'];

// Bilinmeyen tipleri sol sütuna ekle
foreach (array_keys($grouped ?? []) as $t) {
    if (!in_array($t, $solTipler) && !in_array($t, $sagTipler)) {
        $solTipler[] = $t;
    }
}

$fmt = fn(float $n, string $pb = 'TRY') =>
    $pb . ' ' . number_format($n, 2, ',', '.');
?>
<style>
  /* ── GENEL ── */
  :root {
    --navy:  #2c3e6b;
    --teal:  #17a2b8;
    --green: #22c55e;
  }

  .h-action-row { display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
  .btn-tgreen { background: #5cb85c; color: #fff; padding: 7px 14px; border: none; border-radius: 4px; font-size: 12.5px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
  .btn-tgreen:hover { background: #4cae4c; color: #fff; }
  .btn-teal { background: #5bc0de; color: #fff; padding: 7px 14px; border: none; border-radius: 4px; font-size: 12.5px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
  .btn-teal:hover { background: #46b8da; color: #fff; }
  .yeni-badge { background: var(--card-bg); color: #5bc0de; font-size: 10px; padding: 1px 6px; border-radius: 8px; font-weight: 700; }

  /* ── GRID ── */
  .hesap-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: start; }
  @media(max-width:992px) { .hesap-grid { grid-template-columns: 1fr; } }

  /* ── KART ── */
  .h-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 6px; margin-bottom: 16px; box-shadow: 0 1px 4px rgba(0,0,0,.06); }
  .h-card-header {
    background: var(--navy); color: #fff; padding: 11px 16px;
    font-size: 12.5px; font-weight: 700; letter-spacing: .4px;
    border-radius: 6px 6px 0 0;
    display: flex; justify-content: space-between; align-items: center;
  }
  .h-card-header .toplam { font-size: 12px; opacity: .85; font-weight: 600; }
  .h-card-body { padding: 14px 16px; display: flex; flex-wrap: wrap; gap: 10px; min-height: 54px; }

  /* ── HESAP ÖĞESI ── */
  .h-item {
    cursor: pointer; display: inline-flex; flex-direction: column; align-items: center;
    justify-content: center; background: var(--surface-2); border: 1px solid var(--border);
    border-radius: 5px; padding: 10px 16px; min-width: 120px;
    text-decoration: none; transition: box-shadow .15s, border-color .15s;
  }
  .h-item:hover { box-shadow: 0 2px 10px rgba(0,0,0,.12); border-color: var(--text2); }
  .h-item-title { font-size: 11px; color: var(--muted); text-align: center; margin-bottom: 5px; text-transform: uppercase; font-weight: 600; }
  .h-item-val { font-size: 13px; font-weight: 700; color: var(--text); }
  .h-item-val.negatif { color: #dc2626; }
  .h-item-val.pozitif { color: #16a34a; }

  .h-empty { font-size: 12px; color: var(--muted); font-style: italic; padding: 4px; }

  /* ── MODAL OVERLAY ── */
  .ym-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,.55);
    z-index: 900; display: none; align-items: center; justify-content: center;
  }
  .ym-overlay.open { display: flex; }
  .ym-modal {
    background: var(--card-bg); border-radius: 8px; width: 540px; max-width: 96vw;
    box-shadow: 0 8px 40px rgba(0,0,0,.25); overflow: hidden;
    display: flex; flex-direction: column; max-height: 94vh;
  }
  .ym-header {
    background: var(--navy); color: #fff; padding: 14px 20px;
    font-size: 15px; font-weight: 700;
    display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;
  }
  .ym-close { background: none; border: none; color: #fff; font-size: 22px; cursor: pointer; line-height: 1; opacity: .8; }
  .ym-close:hover { opacity: 1; }
  .ym-body { padding: 18px 22px; overflow-y: auto; display: flex; flex-direction: column; gap: 13px; }
  .ym-group { display: flex; flex-direction: column; gap: 4px; }
  .ym-group label { font-size: 12.5px; font-weight: 600; color: var(--text2); }
  .ym-input, .ym-select, .ym-textarea {
    padding: 7px 10px; border: 1px solid var(--border2); border-radius: 5px;
    font-size: 13px; color: var(--text); outline: none; background: var(--card-bg);
    transition: border-color .2s; width: 100%; box-sizing: border-box;
  }
  .ym-input:focus, .ym-select:focus, .ym-textarea:focus { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(23,162,184,.12); }
  .ym-textarea { resize: vertical; min-height: 70px; }
  .ym-select { cursor: pointer; }
  .ym-row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  .ym-footer { padding: 12px 22px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 8px; flex-shrink: 0; }
  .ym-btn-iptal { display: inline-flex; align-items: center; gap: 5px; padding: 8px 18px; background: #64748b; color: #fff; border: none; border-radius: 5px; font-size: 13px; font-weight: 600; cursor: pointer; }
  .ym-btn-iptal:hover { background: #475569; }
  .ym-btn-kaydet { display: inline-flex; align-items: center; gap: 5px; padding: 8px 18px; background: #5cb85c; color: #fff; border: none; border-radius: 5px; font-size: 13px; font-weight: 600; cursor: pointer; }
  .ym-btn-kaydet:hover { background: #4cae4c; }

  /* ── TOAST ── */
  .toast-container { position: fixed; bottom: 22px; right: 22px; z-index: 9999; display: flex; flex-direction: column; gap: 7px; pointer-events: none; }
  .toast-msg { display: flex; align-items: center; gap: 9px; padding: 11px 16px; border-radius: 8px; font-size: 13px; font-weight: 500; box-shadow: 0 3px 14px rgba(0,0,0,.15); background: var(--card-bg); pointer-events: auto; }
  .toast-msg.success { border-left: 4px solid #22c55e; }
  .toast-msg.error   { border-left: 4px solid #ef4444; }
  .toast-msg.info    { border-left: 4px solid #3b82f6; }

  /* ── sidebar ── */
  .profile-dropdown.show { display: flex !important; }
  .profile-dropdown a:hover { background: rgba(0,0,0,.05) !important; }
  .nav-link:focus { color: var(--muted); outline: none; }
  .nav-link.active:focus { color: #4ade80; outline: none; }
</style>

<?php if (!empty($flash)): ?>
<div class="alert alert-<?= $flash['tip'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show mb-3" role="alert">
  <?= htmlspecialchars($flash['mesaj']) ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Aksiyon Satırı -->
<div class="h-action-row">
  <div class="dropdown">
    <button class="btn-tgreen dropdown-toggle" data-bs-toggle="dropdown">
      <i class="fa-solid fa-plus"></i> Yeni Hesap Ekle
    </button>
    <ul class="dropdown-menu shadow">
      <li><a class="dropdown-item" href="#" onclick="openYeniHesap('kasa','Kasa');return false;">Kasa Ekle</a></li>
      <li><a class="dropdown-item" href="#" onclick="openYeniHesap('banka','Banka Hesabı');return false;">Banka Hesabı Ekle</a></li>
      <li><a class="dropdown-item" href="#" onclick="openYeniHesap('pos','POS Hesabı');return false;">POS Hesabı Ekle</a></li>
      <li><a class="dropdown-item" href="#" onclick="openYeniHesap('ortak','Ortaklar Hesabı');return false;">Ortaklar Hesabı Ekle</a></li>
      <li><a class="dropdown-item" href="#" onclick="openYeniHesap('veresiye','Veresiye Hesabı');return false;">Veresiye Hesabı Ekle</a></li>
      <li><a class="dropdown-item" href="#" onclick="openYeniHesap('kredi_karti','Kredi Kartı');return false;">Kredi Kartı Ekle</a></li>
    </ul>
  </div>
  <button class="btn-teal" disabled title="Yakında">
    <i class="fa-solid fa-plus"></i> Yeni Banka Entegrasyonu Ekle <span class="yeni-badge">yakında</span>
  </button>
</div>

<!-- Hesap Grid -->
<div class="hesap-grid">
  <!-- Sol Sütun -->
  <div class="col-l">
    <?php foreach ($solTipler as $tip):
      $hesaplar = $grouped[$tip] ?? [];
      $baslik   = $tipBaslik[$tip] ?? strtoupper($tip);
      $toplamTL = array_sum(array_map(fn($h) => (float)$h['guncel_bakiye'], array_filter($hesaplar, fn($h) => $h['para_birimi'] === 'TRY')));
    ?>
    <div class="h-card">
      <div class="h-card-header">
        <span><?= $baslik ?></span>
        <?php if (!empty($hesaplar)): ?>
        <span class="toplam"><?= number_format($toplamTL, 2, ',', '.') ?> TL</span>
        <?php endif; ?>
      </div>
      <div class="h-card-body">
        <?php if (empty($hesaplar)): ?>
          <span class="h-empty">Henüz hesap eklenmedi.</span>
        <?php else: foreach ($hesaplar as $h):
          $val = (float)$h['guncel_bakiye'];
          $cls = $val < 0 ? 'negatif' : ($val > 0 ? 'pozitif' : '');
        ?>
          <a href="<?= BASE_URL ?>/hesap/detay/<?= $h['id'] ?>" class="h-item">
            <span class="h-item-title"><?= htmlspecialchars($h['hesap_adi']) ?></span>
            <span class="h-item-val <?= $cls ?>"><?= $fmt($val, $h['para_birimi']) ?></span>
          </a>
        <?php endforeach; endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Sağ Sütun -->
  <div class="col-r">
    <?php foreach ($sagTipler as $tip):
      $hesaplar = $grouped[$tip] ?? [];
      $baslik   = $tipBaslik[$tip] ?? strtoupper($tip);

      // Para birimi toplamları
      $toplamlar = [];
      foreach ($hesaplar as $h) {
          $pb = $h['para_birimi'];
          $toplamlar[$pb] = ($toplamlar[$pb] ?? 0) + (float)$h['guncel_bakiye'];
      }
      $toplamStr = implode(' | ', array_map(
          fn($pb, $t) => number_format($t, 2, ',', '.') . ' ' . $pb,
          array_keys($toplamlar), $toplamlar
      ));
    ?>
    <div class="h-card">
      <div class="h-card-header">
        <span><?= $baslik ?></span>
        <?php if ($toplamStr): ?>
        <span class="toplam"><?= $toplamStr ?></span>
        <?php endif; ?>
      </div>
      <div class="h-card-body">
        <?php if (empty($hesaplar)): ?>
          <span class="h-empty">Henüz hesap eklenmedi.</span>
        <?php else: foreach ($hesaplar as $h):
          $val = (float)$h['guncel_bakiye'];
          $cls = $val < 0 ? 'negatif' : ($val > 0 ? 'pozitif' : '');
        ?>
          <a href="<?= BASE_URL ?>/hesap/detay/<?= $h['id'] ?>" class="h-item">
            <span class="h-item-title"><?= htmlspecialchars($h['hesap_adi']) ?></span>
            <span class="h-item-val <?= $cls ?>"><?= $fmt($val, $h['para_birimi']) ?></span>
          </a>
        <?php endforeach; endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ── MODAL: YENİ HESAP ─────────────────────────────── -->
<div class="ym-overlay" id="mYeniHesap">
  <div class="ym-modal">
    <div class="ym-header">
      <span id="mYeniTitle">Yeni Hesap Ekle</span>
      <button class="ym-close" onclick="closeYeniHesap()">&times;</button>
    </div>
    <div class="ym-body">
      <input type="hidden" id="mHesapTip" value="kasa">

      <div class="ym-group">
        <label>Hesap Adı <span style="color:#ef4444;">*</span></label>
        <input type="text" id="mHesapAdi" class="ym-input" placeholder="Hesap adını giriniz">
      </div>

      <div class="ym-group" id="mBankaGrup">
        <label>Banka Adı</label>
        <input type="text" id="mBankaAdi" class="ym-input" placeholder="Banka adı (opsiyonel)">
      </div>

      <div class="ym-row2">
        <div class="ym-group">
          <label>Para Birimi</label>
          <select id="mParaBirimi" class="ym-select">
            <option value="TRY">TRY — Türk Lirası</option>
            <option value="USD">USD — Dolar</option>
            <option value="EUR">EUR — Euro</option>
            <option value="GBP">GBP — Sterlin</option>
          </select>
        </div>
        <div class="ym-group">
          <label>Açılış Bakiyesi</label>
          <input type="text" id="mAcilisBakiye" class="ym-input" value="0" placeholder="0,00">
        </div>
      </div>

      <div class="ym-group" id="mKrediLimitGrup" style="display:none;">
        <label>Kredi Limiti</label>
        <input type="text" id="mKrediLimit" class="ym-input" placeholder="0,00">
      </div>

      <div class="ym-row2" id="mHesapNoGrup">
        <div class="ym-group">
          <label>Şube</label>
          <input type="text" id="mSube" class="ym-input" placeholder="Şube adı">
        </div>
        <div class="ym-group">
          <label>Hesap No</label>
          <input type="text" id="mHesapNo" class="ym-input" placeholder="Hesap numarası">
        </div>
      </div>

      <div class="ym-group" id="mIbanGrup">
        <label>IBAN</label>
        <input type="text" id="mIban" class="ym-input" placeholder="TR00 0000 0000 0000 0000 0000 00">
      </div>

      <div class="ym-group">
        <label>Açıklama</label>
        <textarea id="mAciklama" class="ym-textarea" placeholder="Opsiyonel not"></textarea>
      </div>
    </div>
    <div class="ym-footer">
      <button class="ym-btn-iptal" onclick="closeYeniHesap()">
        <i class="fa-solid fa-xmark"></i> Vazgeç
      </button>
      <button class="ym-btn-kaydet" id="btnHesapKaydet" onclick="hesapKaydet()">
        <i class="fa-solid fa-check"></i> Kaydet
      </button>
    </div>
  </div>
</div>

<!-- Toast -->
<div class="toast-container" id="toastContainer"></div>

<script>
const BASE = '<?= BASE_URL ?>';

/* ── MODAL AÇ/KAPAT ─────────────────────────── */
function openYeniHesap(tip, title) {
  document.getElementById('mHesapTip').value = tip;
  document.getElementById('mYeniTitle').innerText = title + ' Ekle';

  // Alan görünürlükleri
  const showBanka  = ['banka', 'kredi_karti', 'pos'].includes(tip);
  const showKredi  = tip === 'kredi_karti';
  const showHesapNo = ['banka', 'pos'].includes(tip);
  const showIban   = tip === 'banka';

  document.getElementById('mBankaGrup').style.display     = showBanka   ? 'flex' : 'none';
  document.getElementById('mKrediLimitGrup').style.display = showKredi  ? 'flex' : 'none';
  document.getElementById('mHesapNoGrup').style.display   = showHesapNo ? 'grid' : 'none';
  document.getElementById('mIbanGrup').style.display      = showIban    ? 'flex' : 'none';

  // Formu sıfırla
  ['mHesapAdi','mBankaAdi','mSube','mHesapNo','mIban','mAciklama'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.value = '';
  });
  document.getElementById('mAcilisBakiye').value = '0';
  document.getElementById('mParaBirimi').value   = 'TRY';

  document.getElementById('mYeniHesap').classList.add('open');
  document.getElementById('mHesapAdi').focus();
}

function closeYeniHesap() {
  document.getElementById('mYeniHesap').classList.remove('open');
}

// ESC ile kapat
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') closeYeniHesap();
});

/* ── KAYDET ─────────────────────────────────── */
function hesapKaydet() {
  const adi = document.getElementById('mHesapAdi').value.trim();
  if (!adi) {
    showToast('Hesap adı boş olamaz!', 'error');
    document.getElementById('mHesapAdi').focus();
    return;
  }

  const btn = document.getElementById('btnHesapKaydet');
  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Kaydediliyor...';

  const fd = new FormData();
  fd.append('tip',             document.getElementById('mHesapTip').value);
  fd.append('hesap_adi',       adi);
  fd.append('banka_adi',       document.getElementById('mBankaAdi')?.value.trim()    || '');
  fd.append('sube',            document.getElementById('mSube')?.value.trim()        || '');
  fd.append('hesap_no',        document.getElementById('mHesapNo')?.value.trim()     || '');
  fd.append('iban',            document.getElementById('mIban')?.value.trim()        || '');
  fd.append('para_birimi',     document.getElementById('mParaBirimi').value);
  fd.append('acilis_bakiyesi', document.getElementById('mAcilisBakiye').value.replace(',','.'));
  fd.append('aciklama',        document.getElementById('mAciklama').value.trim());

  fetch(BASE + '/hesap/hesapKaydet', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        showToast(res.message, 'success');
        closeYeniHesap();
        setTimeout(() => location.reload(), 900);
      } else {
        showToast(res.message || 'Bir hata oluştu.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Kaydet';
      }
    })
    .catch(() => {
      showToast('Sunucuya bağlanılamadı.', 'error');
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-check"></i> Kaydet';
    });
}

/* ── TOAST ──────────────────────────────────── */
function showToast(msg, type = 'info') {
  const icons = { success: 'check-circle', error: 'circle-xmark', info: 'circle-info' };
  const c  = document.getElementById('toastContainer');
  const el = document.createElement('div');
  el.className = 'toast-msg ' + type;
  el.innerHTML = `<i class="fa-solid fa-${icons[type] || 'circle-info'}"></i> ${msg}`;
  c.appendChild(el);
  setTimeout(() => el.remove(), 3500);
}

/* ── SİDEBAR ACCORDION ───────────────────────── */
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
