<?php
/**
 * View: urunler/detay.php
 * Beklenen değişkenler:
 *   $urun array
 *   $satisGecmisi array
 *   $alisGecmisi array
 *   $manuelHareketler array
 */
$fmt = fn($n) => number_format((float)$n, 2, ',', '.');
?>
<style>
  /* Base & Layout */
  *, *::before, *::after { box-sizing: border-box; }
  .page-container { font-family: 'Segoe UI', system-ui, sans-serif; }
  
  /* Flash Messages */
  .alert { padding: 12px 18px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; display: flex; align-items: center; gap: 8px; }
  .alert-success { background: rgba(46,204,113,.15); border: 1px solid rgba(46,204,113,.28); color: var(--success); }
  .alert-error { background: rgba(231,76,60,.15); border: 1px solid rgba(231,76,60,.28); color: var(--danger); }

  /* Breadcrumb & Header */
  .detail-header { background: #337ab7; color: #fff; padding: 15px 20px; border-radius: 4px 4px 0 0; font-size: 16px; font-weight: 600; }
  .detail-tags { background: var(--card-bg); padding: 10px 20px; border: 1px solid var(--border); border-top: none; border-radius: 0 0 4px 4px; display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px; }
  
  .d-badge { font-size: 10px; font-weight: 700; padding: 4px 8px; border-radius: 3px; text-transform: uppercase; color: #fff; }
  .bg-red { background: #d9534f; }
  .bg-blue { background: #337ab7; }
  .bg-green { background: #5cb85c; }
  .bg-orange { background: #f0ad4e; }
  
  /* 4 Stat Cards */
  .stat-cards-container { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px; }
  .stat-card { color: #fff; border-radius: 4px; display: flex; align-items: center; padding: 15px 20px; gap: 15px; position: relative; overflow: hidden; }
  .stat-card-icon { font-size: 32px; opacity: 0.8; }
  .stat-card-body { flex: 1; text-align: center; }
  .stat-card-title { font-size: 13px; font-weight: 600; opacity: 0.9; margin-bottom: 5px; }
  .stat-card-val { font-size: 18px; font-weight: 700; }
  .stat-card-arrow { position: absolute; right: 15px; bottom: 15px; font-size: 12px; opacity: 0.7; }
  
  /* Responsive Stat Cards */
  @media (max-width: 1024px) {
    .stat-cards-container { grid-template-columns: repeat(2, 1fr); }
  }
  @media (max-width: 576px) {
    .stat-cards-container { grid-template-columns: 1fr; }
  }

  /* Action Buttons */
  .actions-row { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
  .btn-act { border: none; padding: 8px 16px; border-radius: 3px; color: #fff; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
  .btn-act:hover { filter: brightness(1.1); color: #fff; }
  .btn-act-orange { background: #f0ad4e; }
  .btn-act-green { background: #5cb85c; }
  .btn-act-dark { background: #34495e; }
  .btn-act-red { background: #d9534f; }
  
  .btn-dropdown-wrap { position: relative; display: inline-block; }
  .dropdown-menu-custom { position: absolute; top: calc(100% + 2px); right: 0; background: var(--ink); border: 1px solid var(--border2); border-radius: 4px; box-shadow: 0 6px 12px rgba(0,0,0,.175); min-width: 190px; z-index: 300; display: none; overflow: hidden; }
  .dropdown-menu-custom.open { display: block; }
  .dd-item { display: flex; align-items: center; gap: 8px; padding: 9px 16px; font-size: 13px; color: var(--text); text-decoration: none; cursor: pointer; }
  .dd-item:hover { background: var(--surface-2); color: var(--text); }
  .dd-item-danger { color: var(--danger); }
  .dd-item-danger:hover { background: rgba(231,76,60,.15); color: var(--danger); }
  
  /* Accordion Panels */
  .accordion-panel { border: 1px solid var(--border); border-radius: 4px; margin-bottom: 10px; background: var(--card-bg); }
  .accordion-header { background: #3a4a5a; color: #fff; padding: 12px 15px; font-size: 13px; font-weight: 700; cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
  .accordion-header:hover { background: #2c3845; }
  .accordion-body { padding: 15px; display: none; }
  .accordion-body.open { display: block; }
  .accordion-caret { transition: transform 0.2s; background: var(--card-bg); color: var(--text); width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; border-radius: 3px; }
  .accordion-panel.open .accordion-caret { transform: rotate(180deg); }
  
  .empty-data { padding: 20px; text-align: center; color: var(--muted); font-size: 13px; }

  /* History Tables */
  .hist-table { width: 100%; border-collapse: collapse; font-size: 13px; color: var(--text); }
  .hist-table th { background: var(--surface-2); padding: 8px 12px; border-bottom: 2px solid var(--border); text-align: left; }
  .hist-table td { padding: 8px 12px; border-bottom: 1px solid var(--border); }
  .hist-table tr:hover { background: var(--surface-2); }
  
  /* Modal Overlay */
  .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; display: none; align-items: center; justify-content: center; }
  .modal-overlay.open { display: flex; }
  .modal-box { background: var(--ink); border: 1px solid var(--border2); border-radius: 6px; width: 400px; max-width: 90%; box-shadow: 0 5px 15px rgba(0,0,0,0.3); overflow: hidden; }
  .modal-header { background: var(--surface-2); padding: 15px; border-bottom: 1px solid var(--border); font-weight: 600; font-size: 15px; display: flex; justify-content: space-between; align-items: center; }
  .modal-close { cursor: pointer; border: none; background: none; font-size: 16px; color: var(--muted); }
  .modal-body { padding: 20px; }
  .modal-footer { padding: 15px; border-top: 1px solid var(--border); background: var(--surface-2); text-align: right; }
  .form-group { margin-bottom: 15px; }
  .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px; color: var(--text); }
  .form-control { width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 4px; font-size: 13px; }
  .stok-giris-tipi { display: flex; gap: 14px; }
  .stok-giris-tipi-opt { display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: var(--text); cursor: pointer; }
  .stok-giris-tipi-opt input { width: auto; margin: 0; }
  .btn-modal { padding: 8px 16px; border: none; border-radius: 4px; font-size: 13px; font-weight: 600; cursor: pointer; color: #fff; }
  .btn-cancel { background: #999; margin-right: 10px; }
  .btn-save { background: #5cb85c; }
</style>

<div class="page-container">

  <?php $flash = $flash ?? []; ?>
  <?php if (!empty($flash)): ?>
    <div class="alert alert-<?= $flash['tip'] === 'success' ? 'success' : 'error' ?>">
      <i class="fa-solid fa-<?= $flash['tip'] === 'success' ? 'check-circle' : 'circle-exclamation' ?>"></i>
      <?= htmlspecialchars($flash['mesaj']) ?>
    </div>
  <?php endif; ?>
  
  <!-- Header & Tags -->
  <div class="detail-header">
    <?= mb_strtoupper(htmlspecialchars($urun['ad'])) ?>
  </div>
  <div class="detail-tags">
    <?php if (!empty($urun['stok_kodu'])): ?>
      <span class="d-badge bg-red"><?= htmlspecialchars($urun['stok_kodu']) ?></span>
    <?php endif; ?>
    <?php if ($urun['tip'] === 'urun'): ?>
      <span class="d-badge bg-blue">TİCARİ MAL</span>
    <?php endif; ?>
    <?php if (!empty($urun['marka'])): ?>
      <span class="d-badge bg-green"><?= htmlspecialchars($urun['marka']) ?></span>
    <?php endif; ?>
    <span class="d-badge bg-orange">KDV %<?= number_format((float)$urun['kdv_orani'], 2, ',', '.') ?></span>
  </div>

  <!-- Stat Cards -->
  <div class="stat-cards-container">
    <div class="stat-card" style="background: #f17b71;"> <!-- Reddish -->
      <div class="stat-card-icon"><i class="fa-solid fa-money-bill-1"></i></div>
      <div class="stat-card-body">
        <div class="stat-card-title">Alış Fiyatı</div>
        <div class="stat-card-val">TL <?= $fmt($urun['alis_fiyati']) ?></div>
      </div>
    </div>
    
    <div class="stat-card" style="background: #5bc0de;"> <!-- Cyan -->
      <div class="stat-card-icon"><i class="fa-solid fa-tag"></i></div>
      <div class="stat-card-body">
        <div class="stat-card-title">Satış Fiyatı</div>
        <div class="stat-card-val">TL <?= $fmt($urun['satis_fiyati']) ?></div>
      </div>
    </div>
    
    <div class="stat-card" style="background: #62c499;"> <!-- Greenish -->
      <div class="stat-card-icon"><i class="fa-solid fa-gavel"></i></div>
      <div class="stat-card-body">
        <div class="stat-card-title">Toplam Stok</div>
        <div class="stat-card-val"><?= number_format((float)$urun['stok_miktari'], 0, ',', '.') ?> <?= htmlspecialchars($urun['birim']) ?></div>
      </div>
      <i class="fa-solid fa-arrow-right stat-card-arrow"></i>
    </div>
    
    <div class="stat-card" style="background: #eebb54;"> <!-- Yellowish -->
      <div class="stat-card-icon"><i class="fa-solid fa-eye"></i></div>
      <div class="stat-card-body">
        <div class="stat-card-title">Stok Değeri</div>
        <div class="stat-card-val">₺ <?= $fmt((float)$urun['stok_miktari'] * (float)$urun['alis_fiyati']) ?></div>
      </div>
    </div>
  </div>

  <!-- Actions -->
  <div class="actions-row">
    <a href="<?= BASE_URL ?>/urun/duzenle/<?= $urun['id'] ?>" class="btn-act btn-act-orange"><i class="fa-solid fa-pen"></i> Güncelle</a>
    
    <button type="button" class="btn-act btn-act-green" onclick="openModal('stokModal')"><i class="fa-solid fa-arrow-down"></i> Stoklara Giriş Yap</button>

    <a href="<?= BASE_URL ?>/urun/stokEkstresi/<?= $urun['id'] ?>" class="btn-act btn-act-dark"><i class="fa-solid fa-list"></i> Stok Ekstresi</a>

    <div class="btn-dropdown-wrap">
      <button type="button" class="btn-act btn-act-red" id="btnDigerIslemler"><i class="fa-solid fa-bars"></i> Diğer İşlemler <i class="fa-solid fa-caret-down" style="margin-left:4px;"></i></button>
      <div class="dropdown-menu-custom" id="digerIslemlerMenu">
        <a href="<?= BASE_URL ?>/urun/ekle?copy_id=<?= $urun['id'] ?>" class="dd-item"><i class="fa-regular fa-copy"></i> Bu üründen kopyala</a>
        <a href="<?= BASE_URL ?>/urun/varyantlar" class="dd-item"><i class="fa-solid fa-sliders"></i> Varyantlar</a>
        <a href="<?= BASE_URL ?>/urun/sil/<?= $urun['id'] ?>" class="dd-item dd-item-danger"
           onclick="return confirm('&quot;<?= htmlspecialchars(addslashes($urun['ad']), ENT_QUOTES) ?>&quot; silinecek. Emin misiniz?')"><i class="fa-solid fa-trash"></i> Ürünü sil</a>
      </div>
    </div>
  </div>

  <!-- Accordions -->
  <div class="accordion-panel" id="accSatislar">
    <div class="accordion-header" onclick="toggleAccordion('accSatislar')">
      <div>ÖNCEKİ SATIŞLAR</div>
      <div class="accordion-caret"><i class="fa-solid fa-chevron-down"></i></div>
    </div>
    <div class="accordion-body">
      <?php if (empty($satisGecmisi)): ?>
        <div class="empty-data">Bu ürüne ait önceki satış kaydı bulunmamaktadır.</div>
      <?php else: ?>
        <table class="hist-table">
          <thead>
            <tr>
              <th>Tarih</th>
              <th>Belge Tipi</th>
              <th>Fatura No</th>
              <th>Cari / Müşteri</th>
              <th>Miktar</th>
              <th>Birim Fiyat</th>
              <th>Toplam</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($satisGecmisi as $sg): ?>
              <tr>
                <td><?= htmlspecialchars($sg['fatura_tarihi']) ?></td>
                <td><?= htmlspecialchars($sg['belge_tipi']) ?></td>
                <td><?= htmlspecialchars($sg['fatura_no']) ?></td>
                <td><?= htmlspecialchars($sg['cari_unvan'] ?? 'Perakende Müşteri') ?></td>
                <td><?= number_format((float)$sg['miktar'], 2, ',', '.') ?></td>
                <td><?= $fmt($sg['birim_fiyat']) ?></td>
                <td><?= $fmt($sg['toplam']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>

  <div class="accordion-panel" id="accAlislar">
    <div class="accordion-header" onclick="toggleAccordion('accAlislar')">
      <div>ÖNCEKİ ALIŞLAR</div>
      <div class="accordion-caret"><i class="fa-solid fa-chevron-down"></i></div>
    </div>
    <div class="accordion-body">
      <?php if (empty($alisGecmisi)): ?>
        <div class="empty-data">Bu ürüne ait önceki alış kaydı bulunmamaktadır.</div>
      <?php else: ?>
        <table class="hist-table">
          <thead>
            <tr>
              <th>Tarih</th>
              <th>Belge Tipi</th>
              <th>Fatura No</th>
              <th>Tedarikçi</th>
              <th>Miktar</th>
              <th>Birim Fiyat</th>
              <th>Toplam</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($alisGecmisi as $ag): ?>
              <tr>
                <td><?= htmlspecialchars($ag['fatura_tarihi']) ?></td>
                <td><?= htmlspecialchars($ag['belge_tipi']) ?></td>
                <td><?= htmlspecialchars($ag['fatura_no']) ?></td>
                <td><?= htmlspecialchars($ag['cari_unvan']) ?></td>
                <td><?= number_format((float)$ag['miktar'], 2, ',', '.') ?></td>
                <td><?= $fmt($ag['birim_fiyat']) ?></td>
                <td><?= $fmt($ag['toplam']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>

  <div class="accordion-panel" id="accDepoStok">
    <div class="accordion-header" onclick="toggleAccordion('accDepoStok')">
      <div>DEPO BAZLI STOK DAĞILIMI <i class="fa-solid fa-warehouse" style="color:#3b82f6; margin-left:8px;"></i></div>
      <div class="accordion-caret"><i class="fa-solid fa-chevron-down"></i></div>
    </div>
    <div class="accordion-body">
      <?php if (empty($depoStoklari)): ?>
        <div class="empty-data">Bu ürün henüz hiçbir depoda stoklanmamış.</div>
      <?php else: ?>
        <table class="hist-table">
          <thead>
            <tr>
              <th>Depo Adı</th>
              <th>Mevcut Miktar</th>
              <th>Birim</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($depoStoklari as $ds): ?>
              <tr>
                <td style="font-weight:600; color:var(--text);"><?= htmlspecialchars($ds['depo_adi']) ?></td>
                <td style="font-weight:700; color:#3b82f6;"><?= number_format((float)$ds['miktar'], 2, ',', '.') ?></td>
                <td><?= htmlspecialchars($urun['birim']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>

  <div class="accordion-panel" id="accStok">
    <div class="accordion-header" onclick="toggleAccordion('accStok')">
      <div>MANUEL STOK HAREKETLERİ</div>
      <div class="accordion-caret"><i class="fa-solid fa-chevron-down"></i></div>
    </div>
    <div class="accordion-body">
      <?php if (empty($manuelHareketler)): ?>
        <div class="empty-data">Bu ürüne ait manuel stok hareketi bulunmamaktadır.</div>
      <?php else: ?>
        <table class="hist-table">
          <thead>
            <tr>
              <th>Tarih</th>
              <th>İşlem Tipi</th>
              <th>Depo</th>
              <th>Miktar</th>
              <th>Açıklama</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($manuelHareketler as $mh): ?>
              <tr>
                <td><?= htmlspecialchars($mh['olusturulma_tarihi']) ?></td>
                <td>
                   <span class="d-badge <?= $mh['islem_tipi'] === 'giris' ? 'bg-green' : 'bg-red' ?>">
                     <?= htmlspecialchars($mh['islem_tipi']) ?>
                   </span>
                </td>
                <td><i class="fa-solid fa-warehouse" style="font-size:11px; color:var(--muted); margin-right:4px;"></i> <?= htmlspecialchars($mh['depo_adi'] ?: 'Belirtilmemiş') ?></td>
                <td style="font-weight:700;"><?= number_format((float)$mh['miktar'], 2, ',', '.') ?></td>
                <td><?= htmlspecialchars($mh['aciklama']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>

</div>

<!-- Modal: Stok Giriş -->
<div class="modal-overlay" id="stokModal">
  <div class="modal-box">
    <div class="modal-header">
      Stok Girişi Yap
      <button type="button" class="modal-close" onclick="closeModal('stokModal')">&times;</button>
    </div>
    <form action="<?= BASE_URL ?>/urun/stokGiris/<?= $urun['id'] ?>" method="POST">
      <div class="modal-body">
        <?php $koliIciAdet = (float)($urun['koli_ici_adet'] ?? 0); ?>
        <?php if ($koliIciAdet > 0): ?>
        <div class="form-group">
          <label>Giriş Şekli</label>
          <div class="stok-giris-tipi">
            <label class="stok-giris-tipi-opt">
              <input type="radio" name="giris_tipi" value="adet" checked onchange="stokGirisTipiDegisti()"> Adet
            </label>
            <label class="stok-giris-tipi-opt">
              <input type="radio" name="giris_tipi" value="koli" onchange="stokGirisTipiDegisti()">
              Koli <span style="color:var(--muted);font-weight:400;">(1 koli = <?= rtrim(rtrim(number_format($koliIciAdet, 3, '.', ''), '0'), '.') ?> <?= htmlspecialchars($urun['birim']) ?>)</span>
            </label>
          </div>
        </div>
        <?php endif; ?>
        <div class="form-group">
          <label id="stokMiktarLabel">Miktar (<?= htmlspecialchars($urun['birim']) ?>)</label>
          <input type="number" name="miktar" id="stokMiktarInput" class="form-control" step="0.01" min="0.01" required placeholder="0.00" <?= $koliIciAdet > 0 ? 'oninput="stokGirisHesapla()"' : '' ?>>
          <?php if ($koliIciAdet > 0): ?>
            <div id="stokAdetOnizleme" style="font-size:12px;color:var(--muted);margin-top:5px;display:none;"></div>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label>Giriş Yapılacak Depo</label>
          <select name="depo_id" class="form-control" required>
            <?php foreach ($depolar as $d): ?>
              <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['ad']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Açıklama</label>
          <textarea name="aciklama" class="form-control" rows="3" placeholder="Giriş sebebi vb..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-modal btn-cancel" onclick="closeModal('stokModal')">İptal</button>
        <button type="submit" class="btn-modal btn-save">Kaydet</button>
      </div>
    </form>
  </div>
</div>

<script>
function toggleAccordion(id) {
  const panel = document.getElementById(id);
  const body = panel.querySelector('.accordion-body');
  
  if (panel.classList.contains('open')) {
    panel.classList.remove('open');
    body.classList.remove('open');
  } else {
    panel.classList.add('open');
    body.classList.add('open');
  }
}

function openModal(id) {
  document.getElementById(id).classList.add('open');
}

function closeModal(id) {
  document.getElementById(id).classList.remove('open');
}

var STOK_KOLI_ICI_ADET = <?= json_encode((float)($urun['koli_ici_adet'] ?? 0)) ?>;
var STOK_URUN_BIRIM = <?= json_encode($urun['birim'] ?? 'Adet') ?>;

function stokGirisTipiDegisti() {
  var secili = document.querySelector('input[name="giris_tipi"]:checked');
  var tip = secili ? secili.value : 'adet';
  var label = document.getElementById('stokMiktarLabel');
  var input = document.getElementById('stokMiktarInput');
  if (!label || !input) return;
  label.textContent = tip === 'koli' ? 'Koli Sayısı' : ('Miktar (' + STOK_URUN_BIRIM + ')');
  stokGirisHesapla();
}

function stokGirisHesapla() {
  var onizleme = document.getElementById('stokAdetOnizleme');
  if (!onizleme) return;
  var secili = document.querySelector('input[name="giris_tipi"]:checked');
  var tip = secili ? secili.value : 'adet';
  if (tip === 'koli') {
    var koliSayisi = parseFloat(document.getElementById('stokMiktarInput').value) || 0;
    var adet = koliSayisi * STOK_KOLI_ICI_ADET;
    onizleme.textContent = '= ' + adet.toLocaleString('tr-TR', {maximumFractionDigits: 3}) + ' ' + STOK_URUN_BIRIM;
    onizleme.style.display = 'block';
  } else {
    onizleme.style.display = 'none';
  }
}

/* "Diğer İşlemler" açılır menüsü — dışarı tıklayınca kapanır */
(function () {
  const btn  = document.getElementById('btnDigerIslemler');
  const menu = document.getElementById('digerIslemlerMenu');
  if (!btn || !menu) return;

  btn.addEventListener('click', (e) => {
    e.stopPropagation();
    menu.classList.toggle('open');
  });
  document.addEventListener('click', () => menu.classList.remove('open'));
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') menu.classList.remove('open');
  });
})();
</script>
