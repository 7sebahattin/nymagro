<?php
/**
 * Masraf Ekle / Düzenle
 * ─────────────────────────────────────────────────────
 * $kategoriler : alt kategoriler flat list [id, ad, renk, ana_adi]
 * $hesaplar    : kasa_banka listesi
 * $personeller : personel listesi
 *
 * GET ?duzenle=ID → mevcut masrafı yükle
 */

// Düzenleme modu
$duzenleId = (int)($_GET['duzenle'] ?? 0);
$mevcut    = null;
if ($duzenleId > 0) {
    require_once MODELS_PATH . '/Masraf.php';
    $mevcut = (new Masraf())->getir($duzenleId);
}

$isEdit = ($mevcut !== null);
$val    = fn(string $k, $def = '') => $mevcut[$k] ?? $def;

// Kategorileri grupla (ana kategori başlıklar)
$katGrup = [];
foreach ($kategoriler as $k) {
    $katGrup[$k['ana_adi'] ?? 'Diğer'][] = $k;
}
?>
<style>
  :root { --navy:#2c3e6b; --blue:#337ab7; --green:#5cb85c; --teal:#5bc0de; }

  .me-action { display:flex; gap:8px; margin-bottom:20px; flex-wrap:wrap; }
  .btn-mgrn  { background:var(--green); color:#fff; padding:7px 15px; border:none; border-radius:4px; font-size:13px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:6px; }
  .btn-mgrn:hover { filter:brightness(1.1); }
  .btn-back  { background:#64748b; color:#fff; padding:7px 15px; border:none; border-radius:4px; font-size:13px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:6px; text-decoration:none; }
  .btn-back:hover { filter:brightness(1.1); color:#fff; }

  /* Panels */
  .me-panels { display:flex; gap:20px; align-items:flex-start; }
  @media(max-width:900px) { .me-panels { flex-direction:column; } }
  .me-panel { background:var(--card-bg); border-radius:6px; box-shadow:0 1px 4px rgba(0,0,0,.08); flex:1; overflow:hidden; }
  .me-panel.p-left  { flex:55; }
  .me-panel.p-right { flex:45; }

  .mh-blue  { background:var(--blue);  color:#fff; padding:12px 18px; font-size:13px; font-weight:700; text-transform:uppercase; }
  .mh-green { background:var(--green); color:#fff; padding:12px 18px; font-size:13px; font-weight:700; text-transform:uppercase; }

  .mb-body { padding:22px 24px; display:flex; flex-direction:column; gap:16px; }
  .mf-row { display:flex; align-items:flex-start; gap:14px; }
  .mf-row label { font-size:13px; font-weight:600; color:var(--text2); text-align:right; width:130px; flex-shrink:0; padding-top:8px; }
  .mf-ctrl { flex:1; min-width:0; }
  .mf-inp, .mf-sel, .mf-textarea {
    width:100%; padding:8px 10px; border:1px solid var(--border2); border-radius:4px;
    font-size:13px; color:var(--text); outline:none; box-sizing:border-box;
  }
  .mf-inp:focus, .mf-sel:focus, .mf-textarea:focus { border-color:var(--blue); box-shadow:0 0 0 2px rgba(51,122,183,.12); }
  .mf-textarea { resize:vertical; min-height:80px; }
  .mf-hint  { font-size:11px; color:var(--muted); margin-top:3px; }
  .mf-hint a { color:#5cb85c; text-decoration:none; cursor:pointer; }

  /* Tutar preview */
  .tutar-preview { background:var(--surface-2); border:1px solid var(--border); border-radius:5px; padding:10px 14px; display:flex; flex-direction:column; gap:4px; margin-top:4px; }
  .tutar-row { display:flex; justify-content:space-between; font-size:12.5px; color:var(--text2); }
  .tutar-row.total { font-weight:700; font-size:14px; border-top:1px solid var(--border); padding-top:6px; margin-top:2px; }

  /* Tekrarlayan panel */
  .tekrar-panel { background:rgba(243,156,18,.13); border:1px solid rgba(243,156,18,.30); border-radius:5px; padding:12px 14px; display:none; gap:10px; flex-direction:column; }
  .tekrar-panel.show { display:flex; }

  /* Toast */
  .toast-container { position:fixed; bottom:22px; right:22px; z-index:9999; display:flex; flex-direction:column; gap:7px; pointer-events:none; }
  .toast-msg { display:flex; align-items:center; gap:9px; padding:11px 16px; border-radius:8px; font-size:13px; font-weight:500; box-shadow:0 3px 14px rgba(0,0,0,.15); background:var(--ink); border:1px solid var(--border2); pointer-events:auto; }
  .toast-msg.success { border-left:4px solid #22c55e; }
  .toast-msg.error   { border-left:4px solid #ef4444; }

  .nav-link:focus { color:var(--muted); outline:none; }
  .nav-link.active:focus { color:#4ade80; outline:none; }
</style>

<!-- Aksiyon -->
<div class="me-action">
  <button class="btn-mgrn" id="btnKaydet" onclick="masrafKaydet()">
    <i class="fa-solid fa-check"></i> <?= $isEdit ? 'Güncelle' : 'Kaydet' ?>
  </button>
  <a href="<?= BASE_URL ?>/masraf" class="btn-back">
    <i class="fa-solid fa-rotate-left"></i> Geri Dön
  </a>
</div>

<?php if ($isEdit): ?>
<input type="hidden" id="duzenleId" value="<?= $duzenleId ?>">
<?php endif; ?>

<div class="me-panels">
  <!-- Sol Panel: Hesap Kalemi -->
  <div class="me-panel p-left">
    <div class="mh-blue">HESAP KALEMİ</div>
    <div class="mb-body">

      <div class="mf-row">
        <label>Masraf Kalemi <span style="color:#ef4444;">*</span></label>
        <div class="mf-ctrl">
          <select id="fKategori" class="mf-sel" required>
            <option value="">— Masraf kalemi seçin —</option>
            <?php foreach ($katGrup as $anaAd => $altlar): ?>
            <optgroup label="<?= htmlspecialchars($anaAd) ?>">
              <?php foreach ($altlar as $k): ?>
              <option value="<?= $k['id'] ?>"
                      data-renk="<?= $k['renk'] ?>"
                      data-ana="<?= htmlspecialchars($k['ana_adi'] ?? '') ?>"
                      data-ad="<?= htmlspecialchars($k['ad'] ?? '') ?>"
                      <?= (int)$val('kategori_id') === (int)$k['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($k['ad']) ?>
              </option>
              <?php endforeach; ?>
            </optgroup>
            <?php endforeach; ?>
          </select>
          <div class="mf-hint">
            <a href="<?= BASE_URL ?>/masraf/kalemler" target="_blank">Listeyi düzenlemek için tıklayın</a>
          </div>
        </div>
      </div>

      <div class="mf-row" id="personelRow" style="display:none;">
        <label>Çalışan <span style="color:#ef4444;">*</span></label>
        <div class="mf-ctrl">
          <select id="fPersonelId" class="mf-sel">
            <option value="">— Çalışan seçin —</option>
            <?php foreach (($personeller ?? []) as $p): ?>
              <option value="<?= (int)$p['id'] ?>" <?= (int)$val('personel_id') === (int)$p['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['ad']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="mf-hint">Personel gideri seçildiğinde bu masraf çalışan hareketleriyle bağlanır.</div>
        </div>
      </div>

      <div class="mf-row" id="personelTipRow" style="display:none;">
        <label>Personel Gider Tipi</label>
        <div class="mf-ctrl">
          <select id="fPersonelTip" class="mf-sel">
            <option value="maas">Maaş</option>
            <option value="sgk">SGK</option>
            <option value="sigorta">Sigorta</option>
            <option value="yemek">Yemek</option>
            <option value="yol">Yol</option>
            <option value="prim">Prim</option>
            <option value="ek_odeme">Ek ödeme</option>
            <option value="avans">Avans</option>
            <option value="kesinti">Kesinti</option>
            <option value="odeme">Diğer ödeme</option>
          </select>
        </div>
      </div>

      <div class="mf-row">
        <label>İşlem Tarihi <span style="color:#ef4444;">*</span></label>
        <div class="mf-ctrl">
          <input type="date" id="fIslemTarih" class="mf-inp" value="<?= $val('islem_tarihi', date('Y-m-d')) ?>">
        </div>
      </div>

      <div class="mf-row">
        <label>Fiş / Belge No</label>
        <div class="mf-ctrl">
          <input type="text" id="fBelgeNo" class="mf-inp" placeholder="Opsiyonel" value="<?= htmlspecialchars($val('belge_no')) ?>">
          <div class="mf-hint">(isteğe bağlı)</div>
        </div>
      </div>

      <div class="mf-row">
        <label>Açıklama</label>
        <div class="mf-ctrl">
          <textarea id="fAciklama" class="mf-textarea" placeholder="Masraf hakkında not..."><?= htmlspecialchars($val('aciklama')) ?></textarea>
        </div>
      </div>

      <div class="mf-row">
        <label>Notlar</label>
        <div class="mf-ctrl">
          <input type="text" id="fNotlar" class="mf-inp" placeholder="Opsiyonel ek not" value="<?= htmlspecialchars($val('notlar')) ?>">
        </div>
      </div>

    </div>
  </div>

  <!-- Sağ Panel: Tutar -->
  <div class="me-panel p-right">
    <div class="mh-green">TUTAR</div>
    <div class="mb-body">

      <div class="mf-row">
        <label>Ödeme Durumu</label>
        <div class="mf-ctrl">
          <select id="fOdemeDurum" class="mf-sel" onchange="toggleOdemeTarih()">
            <option value="bekliyor" <?= $val('odeme_durumu','bekliyor') === 'bekliyor' ? 'selected' : '' ?>>Daha sonra ödenecek</option>
            <option value="odendi"  <?= $val('odeme_durumu') === 'odendi'  ? 'selected' : '' ?>>Ödendi</option>
          </select>
        </div>
      </div>

      <div class="mf-row" id="vadeTarihRow">
        <label>Vade Tarihi</label>
        <div class="mf-ctrl">
          <input type="date" id="fVadeTarih" class="mf-inp" value="<?= $val('vade_tarihi') ?>">
          <div class="mf-hint">Ödeme son tarihi</div>
        </div>
      </div>

      <div class="mf-row" id="odemeTarihRow" style="display:none;">
        <label>Ödeme Tarihi</label>
        <div class="mf-ctrl">
          <input type="date" id="fOdemeTarih" class="mf-inp" value="<?= $val('odeme_tarihi', date('Y-m-d')) ?>">
        </div>
      </div>

      <div class="mf-row">
        <label>Ödeme Hesabı</label>
        <div class="mf-ctrl">
          <select id="fKasaId" class="mf-sel">
            <option value="">— Hesap seçin —</option>
            <?php foreach ($hesaplar as $h): ?>
            <option value="<?= $h['id'] ?>" <?= (int)$val('kasa_id') === (int)$h['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($h['hesap_adi']) ?> (<?= number_format($h['guncel_bakiye'], 2, ',', '.') ?> <?= $h['para_birimi'] ?>)
            </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="mf-row">
        <label>Tutar (Brüt) <span style="color:#ef4444;">*</span></label>
        <div class="mf-ctrl">
          <input type="text" id="fTutar" class="mf-inp" placeholder="0,00"
                 value="<?= $val('tutar') ? number_format((float)$val('tutar'), 2, ',', '.') : '' ?>"
                 oninput="hesaplaKDV()">
        </div>
      </div>

      <div class="mf-row">
        <label>KDV Oranı (%)</label>
        <div class="mf-ctrl">
          <select id="fKdvOrani" class="mf-sel" onchange="hesaplaKDV()">
            <?php foreach ([0, 1, 10, 20] as $o): ?>
            <option value="<?= $o ?>" <?= (int)$val('kdv_orani') === $o ? 'selected' : '' ?>>%<?= $o ?></option>
            <?php endforeach; ?>
          </select>
          <div class="mf-hint">Tutar KDV dahil girilecek</div>
        </div>
      </div>

      <!-- Tutar önizleme -->
      <div class="tutar-preview" id="tutarPreview" style="display:none;">
        <div class="tutar-row"><span>Vergi hariç tutar</span><span id="pvNet">—</span></div>
        <div class="tutar-row"><span id="pvKdvLabel">KDV (%0)</span><span id="pvKdv">—</span></div>
        <div class="tutar-row total"><span>KDV Dahil</span><span id="pvToplam">—</span></div>
      </div>

      <!-- Tekrarlayan masraf -->
      <div style="border-top:1px solid var(--border); padding-top:14px; display:flex; gap:10px; align-items:center;">
        <input type="checkbox" id="fTekrarlayan" style="transform:scale(1.2);"
               <?= $val('tekrarlayan') ? 'checked' : '' ?>
               onchange="toggleTekrar()">
        <label for="fTekrarlayan" style="font-size:13px; color:var(--text2); font-weight:600; cursor:pointer;">Tekrarlayan masraf kaydı oluştur</label>
        <span title="Kira ödemesi gibi düzenli ödemeleriniz için" style="color:var(--muted); cursor:help;">
          <i class="fa-solid fa-circle-question"></i>
        </span>
      </div>

      <div class="tekrar-panel" id="tekrarPanel">
        <div class="mf-row" style="margin:0;">
          <label style="padding-top:4px;">Periyot</label>
          <div class="mf-ctrl">
            <select id="fTekrarPeriyot" class="mf-sel">
              <option value="haftalik"  <?= $val('tekrar_periyot') === 'haftalik'  ? 'selected' : '' ?>>Her Hafta</option>
              <option value="aylik"     <?= $val('tekrar_periyot','aylik') === 'aylik'     ? 'selected' : '' ?>>Her Ay</option>
              <option value="uc_aylik"  <?= $val('tekrar_periyot') === 'uc_aylik'  ? 'selected' : '' ?>>Her 3 Ayda Bir</option>
              <option value="alti_aylik"<?= $val('tekrar_periyot') === 'alti_aylik'? 'selected' : '' ?>>Her 6 Ayda Bir</option>
              <option value="yillik"    <?= $val('tekrar_periyot') === 'yillik'    ? 'selected' : '' ?>>Her Yıl</option>
            </select>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- Toast -->
<div class="toast-container" id="toastContainer"></div>

<script>
const BASE    = '<?= BASE_URL ?>';
const IS_EDIT = <?= $isEdit ? 'true' : 'false' ?>;
const EDIT_ID = <?= $duzenleId ?: 0 ?>;

/* ── ÖDEME DURUMU TOGGLE ──────────────────────────── */
function toggleOdemeTarih() {
  const dur = document.getElementById('fOdemeDurum').value;
  document.getElementById('odemeTarihRow').style.display = dur === 'odendi' ? 'flex' : 'none';
  document.getElementById('vadeTarihRow').style.display  = dur === 'bekliyor' ? 'flex' : 'none';
}
toggleOdemeTarih(); // init

function normalizeText(s) {
  return (s || '').toLocaleLowerCase('tr-TR');
}

function guessPersonelTip(label) {
  const s = normalizeText(label);
  if (s.includes('maaş') || s.includes('maas')) return 'maas';
  if (s.includes('sgk')) return 'sgk';
  if (s.includes('sigorta')) return 'sigorta';
  if (s.includes('yemek')) return 'yemek';
  if (s.includes('yol') || s.includes('ulaşım') || s.includes('ulasim')) return 'yol';
  if (s.includes('prim')) return 'prim';
  if (s.includes('avans')) return 'avans';
  if (s.includes('kesinti')) return 'kesinti';
  if (s.includes('tazminat')) return 'ek_odeme';
  return 'odeme';
}

function togglePersonelSecimi() {
  const sel = document.getElementById('fKategori');
  const opt = sel.options[sel.selectedIndex];
  const ana = normalizeText(opt?.dataset?.ana || '');
  const ad = opt?.dataset?.ad || opt?.textContent || '';
  const isPersonel = ana.includes('personel') || normalizeText(ad).includes('personel');
  document.getElementById('personelRow').style.display = isPersonel ? 'flex' : 'none';
  document.getElementById('personelTipRow').style.display = isPersonel ? 'flex' : 'none';
  if (isPersonel) {
    document.getElementById('fPersonelTip').value = guessPersonelTip(ad);
  }
}
document.getElementById('fKategori').addEventListener('change', togglePersonelSecimi);
togglePersonelSecimi();

/* ── KDV HESAPLA ─────────────────────────────────── */
function hesaplaKDV() {
  const tutarStr = document.getElementById('fTutar').value.replace(',', '.').replace(/\s/g, '');
  const tutar    = parseFloat(tutarStr) || 0;
  const kdvOrani = parseFloat(document.getElementById('fKdvOrani').value) || 0;

  const preview  = document.getElementById('tutarPreview');
  if (tutar <= 0) { preview.style.display = 'none'; return; }

  const kdvTutar = tutar * kdvOrani / (100 + kdvOrani);
  const netTutar = tutar - kdvTutar;

  document.getElementById('pvNet').textContent      = fmt(netTutar) + ' ₺';
  document.getElementById('pvKdvLabel').textContent = 'KDV (%' + kdvOrani + ')';
  document.getElementById('pvKdv').textContent      = fmt(kdvTutar) + ' ₺';
  document.getElementById('pvToplam').textContent   = fmt(tutar) + ' ₺';
  preview.style.display = 'flex';
}
hesaplaKDV(); // init

function fmt(n) {
  return n.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

/* ── TEKRARLAYan TOGGLE ──────────────────────────── */
function toggleTekrar() {
  const panel = document.getElementById('tekrarPanel');
  panel.classList.toggle('show', document.getElementById('fTekrarlayan').checked);
}
if (document.getElementById('fTekrarlayan').checked) toggleTekrar();

/* ── KAYDET ──────────────────────────────────────── */
function masrafKaydet() {
  const katId = document.getElementById('fKategori').value;
  const tutar = document.getElementById('fTutar').value.replace(',', '.').replace(/\s/g,'');

  if (!katId) { showToast('Masraf kalemi seçiniz.', 'error'); return; }
  if (document.getElementById('personelRow').style.display !== 'none' && !document.getElementById('fPersonelId').value) {
    showToast('Personel gideri için çalışan seçiniz.', 'error');
    return;
  }
  if (!tutar || parseFloat(tutar) <= 0) { showToast('Geçerli bir tutar giriniz.', 'error'); return; }

  const btn = document.getElementById('btnKaydet');
  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Kaydediliyor...';

  const dur    = document.getElementById('fOdemeDurum').value;
  const fd     = new FormData();
  if (IS_EDIT) fd.append('id', EDIT_ID);
  fd.append('kategori_id',    katId);
  fd.append('kasa_id',        document.getElementById('fKasaId').value);
  fd.append('islem_tarihi',   document.getElementById('fIslemTarih').value);
  fd.append('vade_tarihi',    document.getElementById('fVadeTarih')?.value  || '');
  fd.append('odeme_tarihi',   document.getElementById('fOdemeTarih')?.value || '');
  fd.append('belge_no',       document.getElementById('fBelgeNo').value.trim());
  fd.append('aciklama',       document.getElementById('fAciklama').value.trim());
  fd.append('notlar',         document.getElementById('fNotlar').value.trim());
  fd.append('personel_id',    document.getElementById('personelRow').style.display !== 'none' ? document.getElementById('fPersonelId').value : '');
  fd.append('personel_hareket_tipi', document.getElementById('personelRow').style.display !== 'none' ? document.getElementById('fPersonelTip').value : '');
  fd.append('tutar',          tutar);
  fd.append('kdv_orani',      document.getElementById('fKdvOrani').value);
  fd.append('odeme_durumu',   dur);
  fd.append('tekrarlayan',    document.getElementById('fTekrarlayan').checked ? '1' : '0');
  fd.append('tekrar_periyot', document.getElementById('fTekrarPeriyot').value);

  const url = IS_EDIT ? (BASE + '/masraf/guncelle') : (BASE + '/masraf/kaydet');

  fetch(url, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        showToast(res.message, 'success');
        setTimeout(() => { window.location.href = BASE + '/masraf'; }, 900);
      } else {
        showToast(res.message || 'Hata oluştu.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-check"></i> <?= $isEdit ? 'Güncelle' : 'Kaydet' ?>';
      }
    })
    .catch(() => {
      showToast('Sunucuya bağlanılamadı.', 'error');
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-check"></i> <?= $isEdit ? 'Güncelle' : 'Kaydet' ?>';
    });
}

/* ── TOAST ───────────────────────────────────────── */
function showToast(msg, type = 'info') {
  const c  = document.getElementById('toastContainer');
  const el = document.createElement('div');
  el.className = 'toast-msg ' + type;
  el.innerHTML = `<i class="fa-solid fa-${type === 'success' ? 'check-circle' : 'circle-xmark'}"></i> ${msg}`;
  c.appendChild(el);
  setTimeout(() => el.remove(), 3500);
}

/* ── SİDEBAR ACCORDION ───────────────────────────── */
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
