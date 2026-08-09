<?php
/**
 * View: satislar/duzenle.php
 * Beklenen değişkenler:
 *   $fatura    array  — düzenlenen fatura kaydı
 *   $kalemler  array  — faturanın mevcut kalemleri
 *   $hatalar   array
 *   $eski      array  — form alanlarını doldurmak için (fatura ile aynı anahtarlar)
 *   $cari      array|null
 */
$faturaNo = $fatura['fatura_no'] ?? '';
$bugun = $eski['fatura_tarihi'] ?? date('d.m.Y');
$val = fn(string $k, string $def='') => htmlspecialchars($eski[$k] ?? $def, ENT_QUOTES);
$err = function(string $k) use ($hatalar): string {
    if (empty($hatalar[$k])) return '';
    return '<span style="color:#ef4444;font-size:11.5px;margin-top:3px;display:block;">'
         . htmlspecialchars($hatalar[$k]) . '</span>';
};
?>
<style>
  /* ── Action Buttons ── */
  .top-action-row { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px; }
  .top-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border:none; border-radius:5px; font-size:13px; font-weight:600; color:#fff; cursor:pointer; text-decoration:none; transition:filter .15s,transform .1s; box-shadow:0 2px 4px rgba(0,0,0,.15); }
  .top-btn:hover { filter:brightness(1.1); transform:translateY(-1px); color:#fff; }
  .btn-kaydet   { background:#da4c4c; }
  .btn-taslak   { background:#57b764; }
  .btn-geridon  { background:#efa341; }

  /* ── Panels ── */
  .panels-container { display:flex; gap:20px; align-items:flex-start; }
  .panel { background:var(--card-bg); border-radius:6px; box-shadow:0 4px 15px rgba(0,0,0,.08); overflow:hidden; flex:1; }
  .panel-left  { max-width:420px; }
  .p-header { padding:13px 18px; font-size:13.5px; font-weight:700; color:#fff; text-transform:uppercase; }
  .p-header-blue  { background:#2f73b6; }
  .p-header-green { background:#5dbf68; }
  .p-body { padding:20px 18px; display:flex; flex-direction:column; gap:12px; }

  /* Form groups */
  .fg { display:flex; align-items:flex-start; gap:14px; }
  .fg label { width:90px; text-align:right; font-size:12.5px; font-weight:600; color:var(--text2); padding-top:8px; flex-shrink:0; }
  .fg-inp-wrap { flex:1; display:flex; gap:8px; align-items:center; }
  .fi { flex:1; padding:7px 10px; border:1px solid var(--border2); border-radius:3px; font-size:13px; background:#fff; color:#1a1a2e; outline:none; box-shadow:inset 0 1px 3px rgba(0,0,0,.04); transition:border-color .2s; width:100%; }
  .fi:focus { border-color:#2f73b6; }
  textarea.fi { resize:vertical; min-height:70px; }
  .is-err .fi { border-color:#ef4444; }
  select.fi option { background:#fff; color:#1a1a2e; }

  /* Müşteri seçimi */
  .musteri-secim-wrap { flex:1; position:relative; }
  .musteri-input { width:100%; }
  .musteri-ac-btn { padding:7px 12px; border:1px solid #2f73b6; border-radius:3px; background:#2f73b6; color:#fff; font-size:12px; font-weight:600; cursor:pointer; white-space:nowrap; flex-shrink:0; }
  .musteri-ac-btn:hover { background:#245d9a; }
  .ms-dropdown { position:absolute; top:100%; left:0; right:0; background:var(--ink); border:1.5px solid var(--border2); border-top:none; border-radius:0 0 6px 6px; box-shadow:0 6px 16px rgba(0,0,0,.12); z-index:200; max-height:200px; overflow-y:auto; display:none; }
  .ms-dropdown.open { display:block; }
  .ms-item { padding:8px 12px; font-size:13px; color:var(--text2); cursor:pointer; border-bottom:1px solid var(--border); }
  .ms-item:hover { background:rgba(46,204,113,.10); }
  .ms-item:last-child { border-bottom:none; }
  .ms-hint { padding:8px 12px; font-size:12.5px; color:var(--muted); font-style:italic; }

  /* ── Ürün paneli ── */
  .urun-search-wrap { margin-bottom:12px; position:relative; }
  .urun-input { width:100%; padding:8px 12px; border:1px solid var(--border2); border-radius:3px; font-size:13px; color:var(--text); outline:none; }
  .urun-input:focus { border-color:#5dbf68; }
  .urun-dropdown { position:absolute; top:100%; left:0; right:0; background:var(--ink); border:1.5px solid var(--border2); border-top:none; border-radius:0 0 6px 6px; box-shadow:0 6px 16px rgba(0,0,0,.12); z-index:200; max-height:200px; overflow-y:auto; display:none; }
  .urun-dropdown.open { display:block; }
  .urun-item { padding:8px 12px; font-size:13px; color:var(--text2); cursor:pointer; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; }
  .urun-item:hover { background:rgba(46,204,113,.10); }
  .urun-item-price { font-size:12px; color:var(--muted); }

  /* Kalemler tablosu */
  .kalemler-tablo { width:100%; border-collapse:collapse; font-size:13px; }
  .kalemler-tablo thead tr { background:var(--surface-2); }
  .kalemler-tablo thead th { padding:7px 8px; font-size:11.5px; font-weight:700; color:var(--muted); text-align:left; border-bottom:2px solid var(--border); }
  .kalemler-tablo tbody tr { border-bottom:1px solid var(--border); }
  .kalemler-tablo tbody tr:last-child { border-bottom:none; }
  .kalemler-tablo tbody td { padding:6px 5px; vertical-align:top; }
  .kalem-input { width:100%; padding:5px 7px; border:1px solid var(--border); border-radius:3px; font-size:12.5px; background:#fff; color:#1a1a2e; outline:none; }
  .kalem-input:focus { border-color:#5dbf68; }
  select.kalem-input option { background:#fff; color:#1a1a2e; }
  .td-r { text-align:right; }
  .td-sil { width:34px; text-align:center; }
  .btn-kalem-sil { background:none; border:none; color:#ef4444; cursor:pointer; font-size:14px; padding:2px 5px; }
  .btn-kalem-sil:hover { background:rgba(239,68,68,.08); border-radius:3px; }

  /* Toplam Özet */
  .totals-box { margin-top:16px; border-top:2px solid var(--border); padding-top:12px; }
  .totals-row { display:flex; justify-content:flex-end; gap:10px; font-size:13px; color:var(--text2); margin-bottom:6px; }
  .totals-row span:first-child { font-weight:600; }
  .totals-row span:last-child  { min-width:110px; text-align:right; font-weight:700; color:var(--text); }
  .totals-row.genel span:last-child { font-size:16px; color:#16a34a; }

  /* Ödeme sekli */
  .odeme-sekli-sel { padding:7px 10px; border:1px solid var(--border2); border-radius:3px; font-size:13px; color:var(--text); outline:none; }

  .alert-error { background:rgba(231,76,60,.15); border:1px solid rgba(231,76,60,.28); color:var(--danger); padding:12px 18px; border-radius:8px; margin-bottom:14px; font-size:13px; }

  @media (max-width:900px) { .panels-container { flex-direction:column; } .panel-left { max-width:100%; } }
</style>

<!-- Breadcrumb -->
<div style="display:flex;align-items:center;gap:6px;margin-bottom:14px;font-size:12.5px;color:var(--muted);">
  <a href="<?= BASE_URL ?>/satis" style="color:var(--muted);text-decoration:none;">
    <i class="fa-solid fa-shopping-cart"></i> Satışlar
  </a>
  <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
  <span>Fatura Düzenle — <?= htmlspecialchars($faturaNo) ?></span>
</div>

<?php if (!empty($hatalar['kalemler'])): ?>
  <div class="alert-error">
    <i class="fa-solid fa-circle-exclamation"></i>
    <?= htmlspecialchars($hatalar['kalemler']) ?>
  </div>
<?php endif; ?>

<form id="faturaForm" method="POST" action="<?= BASE_URL ?>/satis/guncelle/<?= (int)$fatura['id'] ?>">


<!-- Action Buttons -->
<div class="top-action-row">
  <button type="submit" name="belge_tipi" value="<?= htmlspecialchars($fatura['belge_tipi']) ?>" class="top-btn btn-kaydet">
    <i class="fa-solid fa-check"></i> Güncelle
  </button>
  <a href="<?= BASE_URL ?>/satis/detay/<?= (int)$fatura['id'] ?>" class="top-btn btn-geridon">
    <i class="fa-solid fa-reply"></i> Geri Dön
  </a>
</div>

<div class="panels-container">

  <!-- ══ Sol Panel: Müşteri & Belge Bilgisi ══ -->
  <div class="panel panel-left">
    <div class="p-header p-header-blue" id="phMusteri"><?= isset($cari) && $cari ? htmlspecialchars($cari['unvan']) : 'PERAKENDE' ?></div>
    <div class="p-body">

      <!-- Müşteri Seçimi -->
      <div class="fg">
        <label>Müşteri</label>
        <div class="fg-inp-wrap" style="position:relative; flex:1;">
          <div class="musteri-secim-wrap">
            <input type="text" id="musteriAraInput" class="fi musteri-input"
                   placeholder="müşteri ara..." autocomplete="off" />
            <div class="ms-dropdown" id="msDrop"></div>
          </div>
          <button type="button" class="musteri-ac-btn" onclick="document.getElementById('musteriAraInput').focus()">
            <i class="fa-solid fa-search"></i>
          </button>
        </div>
      </div>
      <input type="hidden" name="cari_id" id="cariIdHidden" value="<?= isset($cari) && $cari ? $cari['id'] : $val('cari_id') ?>">

      <!-- Fatura No -->
      <div class="fg <?= !empty($hatalar['fatura_no']) ? 'is-err' : '' ?>">
        <label>Fatura No</label>
        <div class="fg-inp-wrap">
          <input type="text" name="fatura_no" class="fi" readonly
                 value="<?= $val('fatura_no', $faturaNo) ?>" />
        </div>
      </div>
      <?= $err('fatura_no') ?>

      <!-- Tarih -->
      <div class="fg <?= !empty($hatalar['fatura_tarihi']) ? 'is-err' : '' ?>">
        <label>Tarihi</label>
        <div class="fg-inp-wrap">
          <input type="text" name="fatura_tarihi" class="fi"
                 value="<?= $val('fatura_tarihi', $bugun) ?>"
                 placeholder="gg.aa.yyyy" />
        </div>
      </div>

      <!-- Vade Tarihi -->
      <div class="fg">
        <label>Vadesi</label>
        <div class="fg-inp-wrap">
          <input type="text" name="vade_tarihi" class="fi"
                 value="<?= $val('vade_tarihi', $bugun) ?>"
                 placeholder="gg.aa.yyyy" />
        </div>
      </div>

      <!-- Ödeme Şekli -->
      <div class="fg">
        <label>Ödeme</label>
        <div class="fg-inp-wrap">
          <select name="odeme_sekli" class="fi odeme-sekli-sel" style="padding-left:8px;">
            <?php foreach (['Nakit','Kredi Kartı','Havale/EFT','Çek','Senet','Vadeli'] as $os): ?>
              <option value="<?= $os ?>" <?= ($eski['odeme_sekli'] ?? '') === $os ? 'selected' : '' ?>>
                <?= $os ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Depo Seçimi -->
      <div class="fg">
        <label>Depo</label>
        <div class="fg-inp-wrap">
          <select name="depo_id" class="fi">
             <option value="1">Ana Depo</option>
          </select>
        </div>
      </div>

      <!-- Açıklama -->
      <div class="fg">
        <label>Açıklama</label>
        <div class="fg-inp-wrap">
          <textarea name="aciklama" class="fi" rows="3"
                    placeholder="isteğe bağlı"><?= $val('aciklama') ?></textarea>
        </div>
      </div>

    </div>
  </div>

  <!-- ══ Sağ Panel: Ürünler ══ -->
  <div class="panel panel-right">
    <div class="p-header p-header-green">ÜRÜN / HİZMETLER</div>
    <div class="p-body">

      <!-- Ürün Arama -->
      <div class="urun-search-wrap">
        <input type="text" id="urunAraInput" class="urun-input"
               placeholder="Ürün adı, kodu veya barkod ile arayın..."
               autocomplete="off" />
        <div class="urun-dropdown" id="urunDrop"></div>
      </div>
      <a href="#" data-bs-toggle="modal" data-bs-target="#yeniUrunModal" style="font-size:11.5px;color:#5dbf68;text-decoration:none;display:block;margin-bottom:14px;">
        listede olmayan ürün eklemek için tıklayın
      </a>

      <!-- Kalemler Tablosu -->
      <div style="overflow-x: auto;">
        <table class="kalemler-tablo" id="kalemlerTablo" style="min-width: 600px;">
          <thead>
            <tr>
              <th style="width:35%;">Ürün / Hizmet</th>
              <th style="width:10%;">Miktar</th>
              <th style="width:12%;">Birim</th>
              <th style="width:14%;">Birim Fiyat</th>
              <th style="width:8%;">KDV%</th>
              <th style="width:8%;">İsk%</th>
              <th class="td-r" style="width:12%;">Toplam</th>
              <th class="td-sil"></th>
            </tr>
          </thead>
          <tbody id="kalemlerBody">
            <!-- Dinamik satırlar JS ile eklenir -->
            <tr id="emptyRow">
              <td colspan="8" style="padding:20px;text-align:center;color:var(--muted);font-size:13px;">
                <i class="fa-solid fa-box-open" style="font-size:24px;margin-bottom:8px;display:block;color:var(--text2);"></i>
                Henüz ürün eklenmedi. Yukarıdan ürün arayın veya tıklayın.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Toplamlar -->
      <div class="totals-box" id="totalsBox" style="display:none;">
        <div class="totals-row">
          <span>Ara Toplam:</span>
          <span id="tAraToplam">0,00 ₺</span>
        </div>
        <div class="totals-row">
          <span>İskonto:</span>
          <span id="tIskonto">0,00 ₺</span>
        </div>
        <div class="totals-row">
          <span>KDV:</span>
          <span id="tKdv">0,00 ₺</span>
        </div>
        <div class="totals-row genel">
          <span>Genel Toplam:</span>
          <span id="tGenel">0,00 ₺</span>
        </div>
      </div>

    </div>
  </div>

</div>
</form>

<script>
(function () {
  'use strict';

  const BASE = '<?= BASE_URL ?>';
  let kalemSayac = 0;

  /* ══════════════════════════════════════
     MÜŞTERİ ARAMA (autocomplete)
  ══════════════════════════════════════ */
  const msInput  = document.getElementById('musteriAraInput');
  const msDrop   = document.getElementById('msDrop');
  const cariHid  = document.getElementById('cariIdHidden');
  const phBaslik = document.getElementById('phMusteri');
  let msTimer;

  msInput.addEventListener('input', function () {
    clearTimeout(msTimer);
    const q = this.value.trim();
    if (q.length < 2) { msDrop.classList.remove('open'); msDrop.innerHTML=''; return; }
    msTimer = setTimeout(() => fetchMusteri(q), 300);
  });

  msInput.addEventListener('blur', () => setTimeout(() => msDrop.classList.remove('open'), 200));

  function fetchMusteri(q) {
    fetch(BASE + '/satis/musteriBul?q=' + encodeURIComponent(q))
      .then(r => r.json())
      .then(data => {
        msDrop.innerHTML = '';
        if (!data.length) {
          msDrop.innerHTML = '<div class="ms-hint">Sonuç yok</div>';
        } else {
          data.forEach(m => {
            const d = document.createElement('div');
            d.className = 'ms-item';
            d.textContent = m.unvan;
            d.addEventListener('mousedown', () => {
              cariHid.value  = m.id;
              msInput.value  = m.unvan;
              phBaslik.textContent = m.unvan.toUpperCase();
              msDrop.classList.remove('open');
            });
            msDrop.appendChild(d);
          });
        }
        msDrop.classList.add('open');
      })
      .catch(() => {});
  }

  document.addEventListener('click', e => {
    if (!msInput.contains(e.target)) msDrop.classList.remove('open');
  });

  /* ══════════════════════════════════════
     ÜRÜN ARAMA (autocomplete)
  ══════════════════════════════════════ */
  const urunInput = document.getElementById('urunAraInput');
  const urunDrop  = document.getElementById('urunDrop');
  let urunTimer;

  urunInput.addEventListener('input', function () {
    clearTimeout(urunTimer);
    const q = this.value.trim();
    if (q.length < 2) { urunDrop.classList.remove('open'); urunDrop.innerHTML=''; return; }
    urunTimer = setTimeout(() => fetchUrun(q), 300);
  });

  urunInput.addEventListener('blur', () => setTimeout(() => urunDrop.classList.remove('open'), 200));

  function fetchUrun(q) {
    fetch(BASE + '/satis/urunBul?q=' + encodeURIComponent(q))
      .then(r => r.json())
      .then(data => {
        urunDrop.innerHTML = '';
        if (!data.length) {
          urunDrop.innerHTML = '<div class="ms-hint">Sonuç yok — <a href="' + BASE + '/urun/ekle" target="_blank" style="color:#5dbf68;">yeni ürün ekle</a></div>';
        } else {
          data.forEach(u => {
            const d = document.createElement('div');
            d.className = 'urun-item';
            d.innerHTML = `<span>${u.ad} <small style="color:var(--muted);">(${u.birim})</small></span>
                           <span class="urun-item-price">${formatPara(u.satis_fiyati)} ₺</span>`;
            d.addEventListener('mousedown', () => {
              kalemEkle(u);
              urunInput.value = '';
              urunDrop.classList.remove('open');
            });
            urunDrop.appendChild(d);
          });
        }
        urunDrop.classList.add('open');
      })
      .catch(() => {});
  }

  document.addEventListener('click', e => {
    if (!urunInput.contains(e.target)) urunDrop.classList.remove('open');
  });

  /* ══════════════════════════════════════
     KALEM YÖNETİMİ
  ══════════════════════════════════════ */
  const tbody     = document.getElementById('kalemlerBody');
  const emptyRow  = document.getElementById('emptyRow');
  const totalsBox = document.getElementById('totalsBox');

  function kalemEkle(urun) {
    const idx = kalemSayac++;
    emptyRow.style.display = 'none';
    totalsBox.style.display = 'block';

    const tr = document.createElement('tr');
    tr.id = 'kalem-' + idx;
    const koliIci = parseFloat(urun.koli_ici_adet || 0);
    tr.dataset.koliIciAdet = koliIci;
    tr.innerHTML = `
      <td>
        <input type="hidden" name="kalem_urun_id[]" value="${urun.id || ''}">
        <input type="text" name="kalem_urun_adi[]" class="kalem-input"
               value="${escHtml(urun.ad)}" required />
      </td>
      <td class="td-miktar">
        <input type="number" name="kalem_miktar[]" class="kalem-input"
               value="${parseFloat(urun.miktar||1)}" min="0.001" step="any"
               oninput="satirHesapla('${idx}')" style="width:65px;" />
        ${koliIci > 0 ? `
        <select name="kalem_giris_tipi[]" class="kalem-input" style="width:65px;margin-top:4px;font-size:11px;padding:3px;" onchange="satirHesapla('${idx}')">
          <option value="adet">Adet</option>
          <option value="koli">Koli</option>
        </select>
        <div id="koliHint-${idx}" style="font-size:10px;color:var(--muted);margin-top:2px;display:none;white-space:nowrap;"></div>
        ` : `<input type="hidden" name="kalem_giris_tipi[]" value="adet">`}
      </td>
      <td>
        <input type="text" name="kalem_birim[]" class="kalem-input"
               value="${escHtml(urun.birim || 'Adet')}" style="width:65px;" />
      </td>
      <td>
        <input type="number" name="kalem_birim_fiyat[]" class="kalem-input"
               value="${parseFloat(urun.satis_fiyati||urun.birim_fiyat||0).toFixed(2)}" min="0" step="any"
               oninput="satinHesapla('${idx}')" />
      </td>
      <td>
        <select name="kalem_kdv_orani[]" class="kalem-input" onchange="satinHesapla('${idx}')"
                style="padding:4px 4px;">
          ${[0,1,8,10,18,20].map(k => `<option value="${k}" ${k===(parseFloat(urun.kdv_orani)||20)?'selected':''}>${k}</option>`).join('')}
        </select>
      </td>
      <td>
        <input type="number" name="kalem_iskonto_orani[]" class="kalem-input"
               value="${parseFloat(urun.iskonto_orani||0)}" min="0" max="100" step="any"
               oninput="satinHesapla('${idx}')" style="width:50px;" />
      </td>
      <td class="td-r" id="toplam-${idx}" style="font-weight:700;white-space:nowrap;">
        ${formatPara(urun.satis_fiyati||urun.birim_fiyat||0)} ₺
      </td>
      <td class="td-sil">
        <button type="button" class="btn-kalem-sil" onclick="kalemSil('${idx}')">
          <i class="fa-solid fa-times"></i>
        </button>
      </td>`;
    tbody.insertBefore(tr, emptyRow);
    genelHesapla();
    return idx;
  }

  window.kalemEkle = kalemEkle;

  function efektifMiktar(tr) {
    const miktarGirilen = parseFloat(tr.querySelector('[name="kalem_miktar[]"]')?.value) || 0;
    const girisTipiEl   = tr.querySelector('[name="kalem_giris_tipi[]"]');
    const girisTipi     = girisTipiEl ? girisTipiEl.value : 'adet';
    const koliIci       = parseFloat(tr.dataset.koliIciAdet || '0');
    return (girisTipi === 'koli' && koliIci > 0) ? miktarGirilen * koliIci : miktarGirilen;
  }

  window.satinHesapla = function(idx) {
    const tr         = document.getElementById('kalem-' + idx);
    if (!tr) return;
    const miktar     = efektifMiktar(tr);
    const fiyat      = parseFloat(tr.querySelector('[name="kalem_birim_fiyat[]"]').value)   || 0;
    const kdv        = parseFloat(tr.querySelector('[name="kalem_kdv_orani[]"]').value)      || 0;
    const iskonto    = parseFloat(tr.querySelector('[name="kalem_iskonto_orani[]"]').value)  || 0;
    const ara        = miktar * fiyat;
    const iskontoT   = ara * (iskonto / 100);
    const kdvTabani  = ara - iskontoT;
    const kdvT       = kdvTabani * (kdv / 100);
    const toplam     = kdvTabani + kdvT;
    document.getElementById('toplam-' + idx).textContent = formatPara(toplam) + ' ₺';

    const hint = document.getElementById('koliHint-' + idx);
    if (hint) {
      const girisTipiEl = tr.querySelector('[name="kalem_giris_tipi[]"]');
      const koliIci = parseFloat(tr.dataset.koliIciAdet || '0');
      if (girisTipiEl && girisTipiEl.value === 'koli' && koliIci > 0) {
        hint.textContent = '= ' + miktar.toLocaleString('tr-TR', {maximumFractionDigits: 3}) + ' adet';
        hint.style.display = 'block';
      } else {
        hint.style.display = 'none';
      }
    }
    genelHesapla();
  };

  // Alias: satırHesapla → satinHesapla
  window.satirHesapla = window.satinHesapla;

  window.kalemSil = function(idx) {
    const tr = document.getElementById('kalem-' + idx);
    if (tr) tr.remove();
    const kalms = tbody.querySelectorAll('tr:not(#emptyRow)');
    if (!kalms.length) { emptyRow.style.display=''; totalsBox.style.display='none'; }
    genelHesapla();
  };

  function genelHesapla() {
    let araToplam = 0, iskontoT = 0, kdvT = 0;
    tbody.querySelectorAll('tr:not(#emptyRow)').forEach(tr => {
      const idx     = tr.id.replace('kalem-','');
      const miktar  = efektifMiktar(tr);
      const fiyat   = parseFloat(tr.querySelector('[name="kalem_birim_fiyat[]"]')?.value)  || 0;
      const kdv     = parseFloat(tr.querySelector('[name="kalem_kdv_orani[]"]')?.value)     || 0;
      const isk     = parseFloat(tr.querySelector('[name="kalem_iskonto_orani[]"]')?.value) || 0;
      const ara     = miktar * fiyat;
      const it      = ara * (isk / 100);
      const kt      = (ara - it) * (kdv / 100);
      araToplam += ara;
      iskontoT  += it;
      kdvT      += kt;
    });
    const genel = araToplam - iskontoT + kdvT;
    document.getElementById('tAraToplam').textContent = formatPara(araToplam) + ' ₺';
    document.getElementById('tIskonto').textContent   = formatPara(iskontoT)  + ' ₺';
    document.getElementById('tKdv').textContent       = formatPara(kdvT)      + ' ₺';
    document.getElementById('tGenel').textContent     = formatPara(genel)     + ' ₺';
  }

  /* ══════════════════════════════════════
     YARDIMCI FONKSİYONLAR
  ══════════════════════════════════════ */
  function formatPara(n) {
    return parseFloat(n||0).toLocaleString('tr-TR', {minimumFractionDigits:2, maximumFractionDigits:2});
  }
  function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  /* ══════════════════════════════════════
     MEVCUT KALEMLERİ ÖN YÜKLE (düzenleme)
  ══════════════════════════════════════ */
  const mevcutKalemler = <?= json_encode(array_map(fn($k) => [
      'id'            => $k['urun_id'],
      'ad'            => $k['urun_adi'],
      'birim'         => $k['birim'],
      'birim_fiyat'   => $k['birim_fiyat'],
      'kdv_orani'     => $k['kdv_orani'],
      'iskonto_orani' => $k['iskonto_orani'],
      'miktar'        => $k['miktar'],
      'koli_ici_adet' => $k['koli_ici_adet'] ?? null,
  ], $kalemler ?? []), JSON_UNESCAPED_UNICODE) ?>;
  mevcutKalemler.forEach(k => kalemEkle(k));

})();
</script>

<!-- Yeni Ürün Modal -->
<div class="modal fade" id="yeniUrunModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none; border-radius:4px;">
      <div class="modal-header" style="background:#58d68d; color:#fff; border-bottom:none;">
        <h5 class="modal-title" style="font-size:16px; font-weight:600;">Yeni Ürün Kaydı</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:20px;">
        <div style="background:rgba(243,156,18,.15); color:var(--warning); padding:10px; border:1px solid rgba(243,156,18,.28); border-radius:4px; font-size:13px; margin-bottom:16px;">
          Ürün listenizde tanımlı olmayan bir ürünü buradan ekleyebilirsiniz.
          Kaydettiğiniz ürünün marka, kategori, alış fiyatı vb gibi detay bilgilerini daha sonra "Ürün Tanımları" sayfasından güncelleyebilirsiniz.
        </div>
        <form id="formHizliUrun">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label style="font-weight:600; font-size:13px; color:var(--text2);">Ürün Adı</label>
                    <input type="text" class="form-control" name="ad" required style="font-size:13px;">
                </div>
                <div class="col-md-6">
                    <label style="font-weight:600; font-size:13px; color:var(--text2);">Ürün Tipi</label>
                    <select class="form-select" name="tip" style="font-size:13px;">
                        <option value="stoklu">Stoklu ürün</option>
                        <option value="hizmet">Hizmet</option>
                    </select>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label style="font-weight:600; font-size:13px; color:var(--text2);">Marka</label>
                    <input type="text" class="form-control" name="marka" style="font-size:13px;">
                </div>
                <div class="col-md-6">
                    <label style="font-weight:600; font-size:13px; color:var(--text2);">Kategori</label>
                    <input type="text" class="form-control" name="kategori" style="font-size:13px;">
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label style="font-weight:600; font-size:13px; color:var(--text2);">Ürün Kodu</label>
                    <input type="text" class="form-control" name="urun_kodu" placeholder="varsa ürün kodu girin" style="font-size:13px;">
                </div>
                <div class="col-md-6">
                    <label style="font-weight:600; font-size:13px; color:var(--text2);">Barkodu</label>
                    <input type="text" class="form-control" name="barkod" placeholder="varsa barkod girin" style="font-size:13px;">
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label style="font-weight:600; font-size:13px; color:var(--text2);">Birim Fiyatı</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="satis_fiyati" required style="font-size:13px;">
                        <span class="input-group-text" style="font-size:13px;">TL</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <label style="font-weight:600; font-size:13px; color:var(--text2);">KDV Dahil mi?</label>
                    <select class="form-select" name="kdv_dahil_mi" style="font-size:13px;">
                        <option value="0">KDV hariç</option>
                        <option value="1">KDV dahil</option>
                    </select>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label style="font-weight:600; font-size:13px; color:var(--text2);">KDV Oranı (%)</label>
                    <select class="form-select" name="kdv_orani" style="font-size:13px;">
                        <option value="20">20</option>
                        <option value="10">10</option>
                        <option value="1">1</option>
                        <option value="0">0</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label style="font-weight:600; font-size:13px; color:var(--text2);">Birimi</label>
                    <select class="form-select" name="birim" style="font-size:13px;">
                        <option value="Adet">Adet</option>
                        <option value="Kg">Kg</option>
                        <option value="Lt">Lt</option>
                        <option value="Mt">Mt</option>
                    </select>
                </div>
            </div>
        </form>
      </div>
      <div class="modal-footer" style="justify-content:flex-end;">
        <button type="button" class="btn btn-warning" data-bs-dismiss="modal" style="color:#fff; border:none; background:#f0ad4e;"><i class="fa-solid fa-times"></i> Vazgeç</button>
        <button type="button" class="btn btn-success" id="btnHizliUrunKaydet" style="border:none; background:#d9534f;"><i class="fa-solid fa-check"></i> Kaydet</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnHizliUrun = document.getElementById('btnHizliUrunKaydet');
    if (btnHizliUrun) {
        btnHizliUrun.addEventListener('click', async function() {
            const form = document.getElementById('formHizliUrun');
            if(!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            const formData = new FormData(form);
            try {
                btnHizliUrun.disabled = true;
                btnHizliUrun.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Bekleyin...';
                
                const response = await fetch('<?= BASE_URL ?>/urun/hizli_ekle', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();
                if(res.status === 'success') {
                    // Close modal
                    const modalEl = document.getElementById('yeniUrunModal');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                    
                    // Add item to table
                    if (window.kalemEkle) {
                        window.kalemEkle(res.urun);
                    }
                    
                    // Reset form
                    form.reset();
                    btnHizliUrun.disabled = false;
                    btnHizliUrun.innerHTML = '<i class="fa-solid fa-check"></i> Kaydet';
                } else {
                    alert('Hata: ' + (res.message || 'Bilinmeyen hata'));
                    btnHizliUrun.disabled = false;
                    btnHizliUrun.innerHTML = '<i class="fa-solid fa-check"></i> Kaydet';
                }
            } catch (err) {
                console.error(err);
                alert('Ağ hatası!');
                btnHizliUrun.disabled = false;
                btnHizliUrun.innerHTML = '<i class="fa-solid fa-check"></i> Kaydet';
            }
        });
    }
});
</script>
