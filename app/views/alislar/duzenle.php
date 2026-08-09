<?php
/**
 * View: alislar/duzenle.php
 * Beklenen değişkenler: $fatura, $kalemler, $eski, $cari, $faturaNo, $bugun, $hatalar
 */
$val = fn(string $k, string $def='') => htmlspecialchars($eski[$k] ?? $def, ENT_QUOTES);
?>
<style>
    /* ── Action Buttons ── */
    .top-action-row { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px; }
    .top-btn {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 8px 16px; border: none; border-radius: 5px;
      font-size: 13px; font-weight: 600; color: #fff; cursor: pointer;
      text-decoration: none; transition: filter .15s;
    }
    .top-btn:hover { filter: brightness(1.1); color: #fff; }
    .btn-proforma   { background: #57b764; }
    .btn-irsaliye   { background: #4ca2da; }
    .btn-fatura     { background: #da4c4c; }
    .btn-geridon    { background: #efa341; }

    /* ── Panels ── */
    .panels-container { display: flex; gap: 20px; align-items: flex-start; }
    .panel { background: var(--card-bg); border-radius: 5px; box-shadow: 0 4px 15px rgba(0,0,0,.08); overflow: hidden; flex: 1; }
    .panel-left { max-width: 440px; }
    .panel-left .p-header { background: #2f73b6; color: #fff; padding: 14px 20px; font-size: 13.5px; font-weight: 700; text-transform: uppercase; }
    .panel-right .p-header { background: #5dbf68; color: #fff; padding: 14px 20px; font-size: 13.5px; font-weight: 700; text-transform: uppercase; }
    .p-body { padding: 24px 20px; display: flex; flex-direction: column; gap: 14px; }
    
    .fg { display: flex; align-items: flex-start; gap: 16px; }
    .fg label { width: 90px; text-align: right; font-size: 12.5px; font-weight: 600; color: var(--text2); padding-top: 8px; flex-shrink: 0; }
    .fi { flex: 1; padding: 7px 10px; border: 1px solid var(--border2); border-radius: 3px; font-size: 13px; color: var(--text); outline: none; width: 100%; }
    .fi:focus { border-color: #2f73b6; }
    select[name="kalem_giris_tipi[]"] { background: #fff; color: #1a1a2e; }
    select[name="kalem_giris_tipi[]"] option { background: #fff; color: #1a1a2e; }

    .ms-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: var(--ink); border: 1px solid var(--border2); z-index: 200; max-height: 200px; overflow-y: auto; display: none; border-radius: 0 0 4px 4px; box-shadow: 0 4px 12px rgba(0,0,0,.1); }
    .ms-dropdown.open { display: block; }
    .ms-item { padding: 8px 12px; font-size: 13px; cursor: pointer; border-bottom: 1px solid var(--border); }
    .ms-item:hover { background: rgba(46,204,113,.10); }

    .kalemler-tablo { width: 100%; border-collapse: collapse; font-size: 13px; }
    .kalemler-tablo thead th { padding: 7px 8px; background: var(--surface-2); font-size: 11px; text-align: left; color: var(--muted); border-bottom: 2px solid var(--border); }
    .td-r { text-align: right; }
    .btn-sil { background: none; border: none; color: #ef4444; cursor: pointer; }

    .totals-box { margin-top: 16px; border-top: 2px solid var(--border); padding-top: 12px; }
    .totals-row { display: flex; justify-content: flex-end; gap: 10px; font-size: 13px; margin-bottom: 6px; }
    .totals-row.genel { font-size: 16px; font-weight: 800; color: #16a34a; border-top: 1px solid var(--border); padding-top: 8px; }

    @media (max-width: 900px) { .panels-container { flex-direction: column; } .panel-left { max-width: 100%; } }
</style>

<form id="faturaForm" method="POST" action="<?= BASE_URL ?>/alis/guncelle/<?= (int)$fatura['id'] ?>">

<div class="top-action-row">
  <button type="submit" name="belge_tipi" value="<?= htmlspecialchars($fatura['belge_tipi']) ?>" class="top-btn btn-fatura"><i class="fa-solid fa-check"></i> Güncelle</button>
  <a href="<?= BASE_URL ?>/alis/detay/<?= (int)$fatura['id'] ?>" class="top-btn btn-geridon"><i class="fa-solid fa-reply"></i> Geri Dön</a>
</div>

<div class="panels-container">
  <!-- Left Panel -->
  <div class="panel panel-left">
    <?php 
      $displayTitle = 'TEDARİKÇİ SEÇİN';
      if (isset($cari) && $cari) $displayTitle = $cari['unvan'];
      elseif (!empty($tedarikciAdi)) $displayTitle = $tedarikciAdi;
    ?>
    <div class="p-header" id="phMusteri"><?= htmlspecialchars($displayTitle) ?></div>
    <div class="p-body">
      <div class="fg">
        <label>Tedarikçi</label>
        <div style="position:relative; flex:1;">
          <input type="text" id="musteriAraInput" class="fi" placeholder="tedarikçi ara..." autocomplete="off" value="<?= htmlspecialchars($cari['unvan'] ?? $tedarikciAdi ?? '') ?>">
          <div class="ms-dropdown" id="msDrop"></div>
        </div>
      </div>
      <input type="hidden" name="cari_id" id="cariIdHidden" value="<?= $cari['id'] ?? '' ?>">

      <div class="fg">
        <label>Fatura No</label>
        <input type="text" name="fatura_no" class="fi" readonly value="<?= $val('fatura_no', $faturaNo) ?>">
      </div>

      <div class="fg">
        <label>Tarihi</label>
        <input type="text" name="fatura_tarihi" class="fi" value="<?= $val('fatura_tarihi', $bugun) ?>">
      </div>

      <div class="fg">
        <label>Depo</label>
        <select name="depo_id" class="fi">
          <option value="1">Ana Depo</option>
        </select>
      </div>

      <div class="fg">
        <label>Açıklama</label>
        <textarea name="aciklama" class="fi" style="min-height:80px;"><?= $val('aciklama') ?></textarea>
      </div>
    </div>
  </div>

  <!-- Right Panel -->
  <div class="panel panel-right">
    <div class="p-header">ALINAN ÜRÜNLER</div>
    <div class="p-body">
      <div style="position:relative; margin-bottom:12px;">
        <input type="text" id="urunAraInput" class="fi" placeholder="Ürün adı veya kodu ile arayın...">
        <div class="ms-dropdown" id="urunDrop"></div>
      </div>

      <div style="overflow-x: auto;">
        <table class="kalemler-tablo">
          <thead>
            <tr>
              <th>Ürün / Hizmet</th>
              <th style="width:70px;">Miktar</th>
              <th style="width:110px;">Fiyat</th>
              <th style="width:50px;">KDV</th>
              <th class="td-r" style="width:100px;">Toplam</th>
              <th style="width:30px;"></th>
            </tr>
          </thead>
          <tbody id="kalemlerBody">
            <tr id="emptyRow"><td colspan="6" style="text-align:center; padding:30px; color:var(--muted);">Ürün eklemek için yukarıdan arama yapın.</td></tr>
          </tbody>
        </table>
      </div>

      <div class="totals-box" id="totalsBox" style="display:none;">
        <div class="totals-row"><span>Ara Toplam:</span> <span id="tAra">0,00 ₺</span></div>
        <div class="totals-row"><span>KDV:</span> <span id="tKdv">0,00 ₺</span></div>
        <div class="totals-row genel"><span>GENEL TOPLAM:</span> <span id="tGenel">0,00 ₺</span></div>
      </div>
    </div>
  </div>
</div>
</form>

<script>
(function() {
  const BASE = '<?= BASE_URL ?>';
  const msInput = document.getElementById('musteriAraInput'), msDrop = document.getElementById('msDrop');
  const urunInput = document.getElementById('urunAraInput'), urunDrop = document.getElementById('urunDrop');
  const tbody = document.getElementById('kalemlerBody'), emptyRow = document.getElementById('emptyRow');
  let sayac = 0;

  // Autocomplete helpers
  msInput.addEventListener('input', e => {
    const q = e.target.value.trim(); if(q.length<2) return msDrop.classList.remove('open');
    fetch(BASE + '/tedarikci/tedarikciBul?q=' + encodeURIComponent(q))
      .then(r => r.json()).then(data => {
        msDrop.innerHTML = '';
        data.forEach(m => {
          const d = document.createElement('div'); d.className='ms-item'; d.textContent=m.unvan;
          d.onclick = () => { document.getElementById('cariIdHidden').value=m.id; msInput.value=m.unvan; document.getElementById('phMusteri').textContent=m.unvan.toUpperCase(); msDrop.classList.remove('open'); };
          msDrop.appendChild(d);
        });
        msDrop.classList.add('open');
      });
  });

  urunInput.addEventListener('input', e => {
    const q = e.target.value.trim(); if(q.length<2) return urunDrop.classList.remove('open');
    fetch(BASE + '/satis/urunBul?q=' + encodeURIComponent(q))
      .then(r => r.json()).then(data => {
        urunDrop.innerHTML = '';
        data.forEach(u => {
          const d = document.createElement('div'); d.className='ms-item'; d.textContent=u.ad;
          d.onclick = () => { kalemEkle(u); urunInput.value=''; urunDrop.classList.remove('open'); };
          urunDrop.appendChild(d);
        });
        urunDrop.classList.add('open');
      });
  });

  function kalemEkle(u) {
    emptyRow.style.display = 'none';
    document.getElementById('totalsBox').style.display = 'block';
    const id = sayac++;
    const tr = document.createElement('tr');
    tr.id = 'k-' + id;
    const koliIci = parseFloat(u.koli_ici_adet || 0);
    tr.dataset.koliIciAdet = koliIci;
    tr.innerHTML = `
      <td><input type="hidden" name="kalem_urun_id[]" value="${u.id}"><input type="text" name="kalem_urun_adi[]" class="fi" style="padding:4px;" value="${u.ad}"></td>
      <td>
        <input type="number" name="kalem_miktar[]" class="fi" style="padding:4px;" value="${u.miktar || 1}" step="any" oninput="hesapla()">
        ${koliIci > 0 ? `
        <select name="kalem_giris_tipi[]" class="fi" style="padding:2px;font-size:11px;margin-top:3px;" onchange="hesapla()">
          <option value="adet">Adet</option>
          <option value="koli">Koli</option>
        </select>
        <div class="koli-hint" style="font-size:10px;color:var(--muted);margin-top:2px;display:none;white-space:nowrap;"></div>
        ` : `<input type="hidden" name="kalem_giris_tipi[]" value="adet">`}
      </td>
      <td><input type="number" name="kalem_birim_fiyat[]" class="fi" style="padding:4px;" value="${u.birim_fiyat || u.alis_fiyati || 0}" step="any" oninput="hesapla()"></td>
      <td><input type="number" name="kalem_kdv_orani[]" class="fi" style="padding:4px;" value="${u.kdv_orani || 20}" oninput="hesapla()"></td>
      <td class="td-r" id="top-${id}" style="font-weight:700;">0,00 ₺</td>
      <td><button type="button" class="btn-sil" onclick="this.closest('tr').remove(); hesapla();">×</button></td>
    `;
    tbody.appendChild(tr);
    hesapla();
  }

  window.hesapla = function() {
    let ara = 0, kdv = 0;
    tbody.querySelectorAll('tr:not(#emptyRow)').forEach(tr => {
      const mGirilen = parseFloat(tr.querySelector('[name="kalem_miktar[]"]').value) || 0;
      const girisTipiEl = tr.querySelector('[name="kalem_giris_tipi[]"]');
      const girisTipi = girisTipiEl ? girisTipiEl.value : 'adet';
      const koliIci = parseFloat(tr.dataset.koliIciAdet || '0');
      const m = (girisTipi === 'koli' && koliIci > 0) ? mGirilen * koliIci : mGirilen;
      const f = parseFloat(tr.querySelector('[name="kalem_birim_fiyat[]"]').value) || 0;
      const k = parseFloat(tr.querySelector('[name="kalem_kdv_orani[]"]').value) || 0;
      const lAra = m * f; const lKdv = lAra * (k/100);
      ara += lAra; kdv += lKdv;
      tr.querySelector('.td-r').textContent = (lAra + lKdv).toLocaleString('tr-TR', {minimumFractionDigits:2}) + ' ₺';

      const hint = tr.querySelector('.koli-hint');
      if (hint) {
        if (girisTipi === 'koli' && koliIci > 0) {
          hint.textContent = '= ' + m.toLocaleString('tr-TR', {maximumFractionDigits: 3}) + ' adet';
          hint.style.display = 'block';
        } else {
          hint.style.display = 'none';
        }
      }
    });
    document.getElementById('tAra').textContent = ara.toLocaleString('tr-TR', {minimumFractionDigits:2}) + ' ₺';
    document.getElementById('tKdv').textContent = kdv.toLocaleString('tr-TR', {minimumFractionDigits:2}) + ' ₺';
    document.getElementById('tGenel').textContent = (ara + kdv).toLocaleString('tr-TR', {minimumFractionDigits:2}) + ' ₺';
  };

  const mevcutKalemler = <?= json_encode(array_map(fn($k) => [
      'id'            => $k['urun_id'],
      'ad'            => $k['urun_adi'],
      'birim_fiyat'   => $k['birim_fiyat'],
      'kdv_orani'     => $k['kdv_orani'],
      'miktar'        => $k['miktar'],
      'koli_ici_adet' => $k['koli_ici_adet'] ?? null,
  ], $kalemler ?? []), JSON_UNESCAPED_UNICODE) ?>;
  mevcutKalemler.forEach(k => kalemEkle(k));
})();
</script>