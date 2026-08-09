<?php
/**
 * View: urunler/ekle.php
 * Beklenen değişkenler:
 *   $eski     array  — POST verisi (hata sonrası doldurma)
 *   $hatalar  array  — ['alan' => 'mesaj']
 */

// Eski değeri XSS-güvenli döndürür
$val = fn(string $k, string $def = '') =>
    htmlspecialchars($eski[$k] ?? $def, ENT_QUOTES);
$isStorefront = $isStorefront ?? false;

// Hata mesajı döndürür
$err = function (string $k) use ($hatalar): string {
    if (empty($hatalar[$k])) return '';
    return '<span style="color:#ef4444;font-size:11.5px;margin-top:3px;display:block;">'
         . htmlspecialchars($hatalar[$k]) . '</span>';
};

// Hatalı sekmeyi bul
$tabHatalar = [
    'tanim'  => ['ad', 'tip', 'birim'],
    'fiyat'  => ['satis_fiyati', 'alis_fiyati', 'kdv_orani'],
    'diger'  => ['stok_kodu', 'barkod', 'kategori', 'marka'],
];
$ilkHataliTab = 'tanim';
if (!empty($hatalar)) {
    foreach ($tabHatalar as $tab => $alanlar) {
        foreach ($alanlar as $alan) {
            if (!empty($hatalar[$alan])) { $ilkHataliTab = $tab; break 2; }
        }
    }
}
?>
<style>
  .breadcrumb-bar { display:flex; align-items:center; gap:6px; margin-bottom:16px; font-size:12.5px; color:var(--muted); }
  .breadcrumb-bar a { color:var(--muted); text-decoration:none; }
  .breadcrumb-bar a:hover { color:#4ade80; }

  .action-bar { display:flex; align-items:center; gap:10px; margin-bottom:20px; }
  .btn-save { display:inline-flex; align-items:center; gap:7px; background:#22c55e; color:#fff; border:none; padding:9px 20px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; transition:background .2s,box-shadow .2s; }
  .btn-save:hover { background:#16a34a; box-shadow:0 4px 12px rgba(34,197,94,.3); }
  .btn-back { display:inline-flex; align-items:center; gap:7px; background:#0ea5e9; color:#fff; border:none; padding:9px 20px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; text-decoration:none; transition:background .2s; }
  .btn-back:hover { background:#0284c7; color:#fff; }

  .form-card { background:var(--card-bg); border-radius:14px; box-shadow:0 2px 16px rgba(0,0,0,.08); overflow:hidden; }

  /* Tabs */
  .form-tabs { display:flex; border-bottom:2px solid var(--border); background:var(--card-bg); overflow-x:auto; scrollbar-width:none; }
  .form-tabs::-webkit-scrollbar { display:none; }
  .tab-btn { display:inline-flex; align-items:center; gap:7px; padding:14px 18px; font-size:12px; font-weight:700; color:var(--muted); background:none; border:none; cursor:pointer; border-bottom:3px solid transparent; margin-bottom:-2px; white-space:nowrap; text-transform:uppercase; letter-spacing:.6px; transition:color .2s,border-color .2s; }
  .tab-btn:hover { color:var(--text); }
  .tab-btn.active { color:#22c55e; border-bottom-color:#22c55e; }
  .tab-btn i { font-size:12px; }
  .tab-btn .tab-err { display:inline-block; width:7px; height:7px; border-radius:50%; background:#ef4444; margin-left:4px; }

  /* Panels */
  .tab-panel { display:none; padding:28px; }
  .tab-panel.active { display:block; }

  /* Grid */
  .form-row-2 { display:grid; grid-template-columns:1fr 1fr; gap:20px 40px; }
  .form-col { display:flex; flex-direction:column; }
  .fg { display:flex; flex-direction:column; gap:5px; margin-bottom:18px; }
  .fg:last-child { margin-bottom:0; }
  .flabel { font-size:12px; font-weight:600; color:var(--text2); text-transform:uppercase; letter-spacing:.5px; display:flex; align-items:center; gap:6px; margin-bottom:2px; }
  .flabel .hi { color:var(--muted); font-size:12px; cursor:help; }
  .flabel-row { display:flex; align-items:center; justify-content:space-between; margin-bottom:5px; }
  .flabel-row .flabel { margin-bottom:0; }
  .btn-inline-add { display:inline-flex; align-items:center; gap:4px; background:#22c55e; color:#fff; border:none; padding:3px 10px; border-radius:5px; font-size:11px; font-weight:700; cursor:pointer; transition:background .2s; }
  .btn-inline-add:hover { background:#16a34a; }

  /* Inputs */
  .finput, .fselect { padding:9px 12px 9px 36px; border:1.5px solid var(--border); border-radius:8px; font-size:13.5px; color:var(--text); background:var(--card-bg); outline:none; transition:border-color .2s,box-shadow .2s; width:100%; }
  .finput:focus, .fselect:focus { border-color:#22c55e; box-shadow:0 0 0 3px rgba(34,197,94,.12); }
  .finput.no-icon { padding-left:12px; }
  .fselect { padding-left:12px; appearance:auto; cursor:pointer; }
  .fselect option { background:var(--ink); color:var(--text); }
  .ftextarea { padding:10px 12px; border:1.5px solid var(--border); border-radius:8px; font-size:13.5px; color:var(--text); background:var(--card-bg); outline:none; transition:border-color .2s,box-shadow .2s; width:100%; resize:vertical; min-height:90px; }
  .ftextarea:focus { border-color:#22c55e; box-shadow:0 0 0 3px rgba(34,197,94,.12); }
  .fwrap { position:relative; }
  .fwrap i.fi { position:absolute; left:11px; top:50%; transform:translateY(-50%); color:var(--text2); font-size:13px; pointer-events:none; }
  .fhint { font-size:11.5px; color:var(--muted); margin-top:3px; line-height:1.5; }
  .is-error .finput, .is-error .fselect, .is-error .ftextarea { border-color:#ef4444 !important; }

  /* Price group */
  .price-group { display:flex; gap:0; }
  .price-group .finput { border-radius:8px 0 0 8px; border-right:none; flex:1; }
  .price-group .fselect { border-radius:0 8px 8px 0; width:80px; padding-left:8px; font-weight:700; color:var(--text2); }

  /* Canlı fiyat/KDV önizlemesi */
  .price-preview { display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:12px; margin-top:22px; padding:16px; background:var(--surface-2); border:1px solid var(--border); border-radius:10px; }
  .pp-item { display:flex; flex-direction:column; gap:4px; }
  .pp-label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; color:var(--muted); }
  .pp-val { font-size:15px; font-weight:800; color:var(--text); }
  .pp-val.pp-neg { color:#ef4444; }
  .pp-val.pp-pos { color:#22c55e; }

  /* Checkbox */
  .check-row { display:flex; align-items:center; gap:8px; }
  .custom-cb { width:16px; height:16px; accent-color:#22c55e; cursor:pointer; }
  .check-label { font-size:13px; color:var(--text2); cursor:pointer; }

  /* Info box */
  .info-box { background:rgba(46,204,113,.10); border:1px solid rgba(46,204,113,.28); border-radius:8px; padding:12px 16px; font-size:13px; color:var(--success); margin-bottom:20px; line-height:1.6; }

  /* Images */
  .images-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(130px,1fr)); gap:14px; }
  .img-slot { width:100%; aspect-ratio:1; border:2px dashed var(--border2); border-radius:12px; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:6px; cursor:pointer; transition:border-color .2s,background .2s; color:var(--muted); background:var(--surface-2); position:relative; overflow:hidden; }
  .img-slot:hover { border-color:#22c55e; background:rgba(34,197,94,.04); color:#22c55e; }
  .img-slot i { font-size:24px; }
  .img-slot span { font-size:11px; font-weight:600; }
  .img-slot input[type="file"] { display:none; }
  .img-main-badge { position:absolute; bottom:4px; left:4px; background:#22c55e; color:#fff; font-size:9px; font-weight:700; padding:2px 6px; border-radius:4px; }

  /* Placeholder tab panels */
  .tab-placeholder { padding:60px; text-align:center; color:var(--muted); }
  .tab-placeholder i { font-size:36px; margin-bottom:12px; display:block; color:var(--text2); }
  .tab-placeholder p { font-size:14px; }

  @media (max-width:900px) { .form-row-2 { grid-template-columns:1fr; } }
</style>

<!-- Breadcrumb -->
<div class="breadcrumb-bar">
  <a href="<?= BASE_URL ?>/urun"><i class="fa-solid fa-tags"></i> Ürünler</a>
  <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
  <span>Düzenle</span>
</div>

<!-- Action bar (outside form — submit via JS) -->
<div class="action-bar">
  <button class="btn-save" type="button" onclick="document.getElementById('urunForm').submit()">
    <i class="fa-solid fa-floppy-disk"></i> Kaydet
  </button>
  <a href="<?= BASE_URL ?>/urun" class="btn-back">
    <i class="fa-solid fa-arrow-left"></i> Geri Dön
  </a>
</div>

<form id="urunForm" method="POST" action="<?= BASE_URL ?>/urun/guncelle/<?= $eski['id'] ?>" enctype="multipart/form-data">

  <div class="form-card">

    <!-- ── Tabs ── -->
    <div class="form-tabs" role="tablist">
      <button type="button" class="tab-btn active" data-tab="tanim">
        <i class="fa-solid fa-tag"></i> Ürün / Hizmet Tanımı
        <?php if (!empty($hatalar) && array_intersect(array_keys($hatalar), $tabHatalar['tanim'])): ?>
          <span class="tab-err"></span>
        <?php endif; ?>
      </button>
      <button type="button" class="tab-btn" data-tab="fiyat">
        <i class="fa-solid fa-lira-sign"></i> Fiyatlandırma
        <?php if (!empty($hatalar) && array_intersect(array_keys($hatalar), $tabHatalar['fiyat'])): ?>
          <span class="tab-err"></span>
        <?php endif; ?>
      </button>
      <button type="button" class="tab-btn" data-tab="diger">
        <i class="fa-solid fa-list-ul"></i> Diğer Bilgiler
        <?php if (!empty($hatalar) && array_intersect(array_keys($hatalar), $tabHatalar['diger'])): ?>
          <span class="tab-err"></span>
        <?php endif; ?>
      </button>
      <button type="button" class="tab-btn" data-tab="resimler">
        <i class="fa-regular fa-images"></i> Resimler
      </button>
      <button type="button" class="tab-btn" data-tab="varyant">
        <i class="fa-solid fa-sliders"></i> Varyant
      </button>
      <button type="button" class="tab-btn" data-tab="bagli">
        <i class="fa-solid fa-link"></i> Bağlı Ürünler
      </button>
    </div>

    <!-- ══════════════════════════════════════════════════
         TAB 1: ÜRÜN / HİZMET TANIMI
    ══════════════════════════════════════════════════ -->
    <div class="tab-panel <?= $ilkHataliTab === 'tanim' || !isset($eski['ad']) ? 'active' : '' ?>" id="panel-tanim">
      <div class="form-row-2">
        <!-- Sol sütun -->
        <div class="form-col">
          <div class="fg <?= !empty($hatalar['ad']) ? 'is-error' : '' ?>">
            <label class="flabel" for="ad">Ürün Adı <span style="color:#ef4444;">*</span></label>
            <div class="fwrap">
              <i class="fa-solid fa-tag fi"></i>
              <input type="text" id="ad" name="ad" class="finput"
                     placeholder="Ürün veya hizmet adı girin"
                     value="<?= $val('ad') ?>" required />
            </div>
            <?= $err('ad') ?>
          </div>

          <div class="fg">
            <label class="flabel" for="tip">Ürün Tipi</label>
            <select id="tip" name="tip" class="fselect">
              <option value="urun"   <?= ($eski['tip'] ?? '') === 'urun'    ? 'selected' : '' ?>>Stoklu ürün</option>
              <option value="hizmet" <?= ($eski['tip'] ?? '') === 'hizmet'  ? 'selected' : '' ?>>Hizmet</option>
            </select>
          </div>
        </div>

        <!-- Sağ sütun -->
        <div class="form-col">
          <div class="fg">
            <label class="flabel" for="birim">Satış Birimi</label>
            <select id="birim" name="birim" class="fselect">
              <?php foreach (['Adet','Kg','Litre','Metre','Paket','Kutu','Ton','Hizmet'] as $b): ?>
                <option value="<?= $b ?>" <?= ($eski['birim'] ?? 'Adet') === $b ? 'selected' : '' ?>>
                  <?= $b ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="fg">
            <label class="flabel">E-Ticaret Ürünü?</label>
            <div class="check-row" style="margin-top:4px;">
              <input type="checkbox" class="custom-cb" id="eticaretCb" name="eticaret" value="1"
                     <?= !empty($eski['eticaret']) ? 'checked' : '' ?>
                     <?= $isStorefront ? '' : 'disabled' ?> />
              <label class="check-label" for="eticaretCb">Evet, e-ticaret sitesinde göster</label>
            </div>
            <?php if ($isStorefront): ?>
              <span class="fhint" style="margin-top:6px;">
                İşaretlerseniz ürün Site Yönetimi'nde pasif/taslak olarak oluşturulur; tescil no, doz tablosu gibi detayları tamamlayıp elle yayınlarsınız.
              </span>
            <?php else: ?>
              <span class="fhint" style="margin-top:6px;">
                Bu özelliği kullanmak için Şirket Ayarları'nda bu şirketi "vitrin şirketi" olarak işaretleyin.
              </span>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════
         TAB 2: FİYATLANDIRMA
    ══════════════════════════════════════════════════ -->
    <div class="tab-panel" id="panel-fiyat">
      <div class="form-row-2">
        <!-- Sol: Satış -->
        <div class="form-col">
          <div class="fg <?= !empty($hatalar['satis_fiyati']) ? 'is-error' : '' ?>">
            <label class="flabel">Satış Fiyatı</label>
            <div class="price-group">
              <input type="number" name="satis_fiyati" class="finput no-icon"
                     placeholder="0,00" min="0" step="0.01"
                     value="<?= $val('satis_fiyati', '0') ?>" />
              <select name="para_birimi" class="fselect">
                <?php foreach (['TRY','USD','EUR','GBP'] as $pb): ?>
                  <option value="<?= $pb ?>" <?= ($eski['para_birimi'] ?? 'TRY') === $pb ? 'selected' : '' ?>>
                    <?= $pb === 'TRY' ? 'TL' : $pb ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <?= $err('satis_fiyati') ?>
          </div>

          <div class="fg">
            <label class="flabel">Satış KDV Oranı (%)</label>
            <select name="kdv_orani" class="fselect">
              <?php foreach ([0, 1, 8, 10, 18, 20] as $kdv): ?>
                <option value="<?= $kdv ?>" <?= (int)($eski['kdv_orani'] ?? 20) === $kdv ? 'selected' : '' ?>>
                  %<?= $kdv ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="fg">
            <label class="flabel">Satış Fiyatına KDV Dahil mi?</label>
            <select name="kdv_dahil" class="fselect">
              <option value="0" <?= ($eski['kdv_dahil'] ?? '0') === '0' ? 'selected' : '' ?>>KDV hariç</option>
              <option value="1" <?= ($eski['kdv_dahil'] ?? '0') === '1' ? 'selected' : '' ?>>KDV dahil</option>
            </select>
          </div>
        </div>

        <!-- Sağ: Alış -->
        <div class="form-col">
          <div class="fg">
            <label class="flabel">Alış Fiyatı</label>
            <div class="price-group">
              <input type="number" name="alis_fiyati" class="finput no-icon"
                     placeholder="0,00" min="0" step="0.01"
                     value="<?= $val('alis_fiyati', '0') ?>" />
              <select name="alis_para_birimi" class="fselect">
                <?php foreach (['TRY','USD','EUR','GBP'] as $pb): ?>
                  <option value="<?= $pb ?>" <?= ($eski['alis_para_birimi'] ?? 'TRY') === $pb ? 'selected' : '' ?>>
                    <?= $pb === 'TRY' ? 'TL' : $pb ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="fg">
            <label class="flabel">Alış KDV Oranı (%)</label>
            <select name="alis_kdv_orani" class="fselect">
              <?php foreach ([0, 1, 8, 10, 18, 20] as $kdv): ?>
                <option value="<?= $kdv ?>" <?= (int)($eski['alis_kdv_orani'] ?? 20) === $kdv ? 'selected' : '' ?>>
                  %<?= $kdv ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="fg">
            <label class="flabel">Alış İskontosu (%)</label>
            <input type="number" name="alis_iskonto" class="finput no-icon"
                   placeholder="0" min="0" max="100" step="0.01"
                   value="<?= $val('alis_iskonto', '0') ?>" />
          </div>
        </div>
      </div>

      <div class="price-preview" id="pricePreview">
        <div class="pp-item">
          <span class="pp-label">Satış — KDV Hariç</span>
          <span class="pp-val" id="ppSatisHaric">0,00</span>
        </div>
        <div class="pp-item">
          <span class="pp-label">Satış — KDV Dahil</span>
          <span class="pp-val" id="ppSatisDahil">0,00</span>
        </div>
        <div class="pp-item">
          <span class="pp-label">Alış — İskonto Sonrası Net</span>
          <span class="pp-val" id="ppAlisNet">0,00</span>
        </div>
        <div class="pp-item">
          <span class="pp-label">Kâr Marjı (KDV hariç)</span>
          <span class="pp-val" id="ppKarMarji">%0,00</span>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════
         TAB 3: DİĞER BİLGİLER
    ══════════════════════════════════════════════════ -->
    <div class="tab-panel" id="panel-diger">
      <div class="form-row-2">
        <!-- Sol -->
        <div class="form-col">
          <div class="fg">
            <div class="flabel-row">
              <label class="flabel">Kategori</label>
              <button type="button" class="btn-inline-add" id="btnNewKat">
                <i class="fa-solid fa-plus"></i> yeni kategori ekle
              </button>
            </div>
            <input type="text" name="kategori" id="kategoriInput" class="finput no-icon"
                   placeholder="Kategori giriniz veya seçiniz"
                   value="<?= $val('kategori') ?>" list="katList" />
            <datalist id="katList">
              <?php foreach (($tanimKategoriler ?? []) as $k): ?>
                <option value="<?= htmlspecialchars($k['ad']) ?>"></option>
              <?php endforeach; ?>
            </datalist>
          </div>

          <div class="fg">
            <div class="flabel-row">
              <label class="flabel">Marka</label>
              <button type="button" class="btn-inline-add" id="btnNewMarka">
                <i class="fa-solid fa-plus"></i> yeni marka ekle
              </button>
            </div>
            <input type="text" name="marka" id="markaInput" class="finput no-icon"
                   placeholder="Marka giriniz veya seçiniz"
                   value="<?= $val('marka') ?>" list="markaList" />
            <datalist id="markaList">
              <?php foreach (($tanimMarkalar ?? []) as $m): ?>
                <option value="<?= htmlspecialchars($m['ad']) ?>"></option>
              <?php endforeach; ?>
            </datalist>
          </div>

          <div class="fg <?= !empty($hatalar['stok_kodu']) ? 'is-error' : '' ?>">
            <label class="flabel">Ürün Kodu (Stok Kodu)</label>
            <input type="text" name="stok_kodu" class="finput no-icon"
                   placeholder="varsa ürün kodu girin"
                   value="<?= $val('stok_kodu') ?>" />
            <?= $err('stok_kodu') ?>
          </div>

          <div class="fg">
            <label class="flabel">Fatura Başlığı</label>
            <input type="text" name="fatura_basligi" class="finput no-icon"
                   placeholder="isteğe bağlı"
                   value="<?= $val('fatura_basligi') ?>" />
            <span class="fhint">Boş bırakılırsa faturada ürün adı kullanılır.</span>
          </div>

          <div class="fg">
            <label class="flabel">Açıklama</label>
            <textarea name="aciklama" class="ftextarea" rows="4"
                      placeholder="isteğe bağlı not girebilirsiniz."><?= $val('aciklama') ?></textarea>
          </div>
        </div>

        <!-- Sağ -->
        <div class="form-col">
          <div class="fg <?= !empty($hatalar['barkod']) ? 'is-error' : '' ?>">
            <label class="flabel">
              Barkod
              <i class="fa-solid fa-circle-question hi" title="Ürün barkod numarası"></i>
            </label>
            <div class="fwrap">
              <i class="fa-solid fa-barcode fi"></i>
              <input type="text" name="barkod" id="barkodInput" class="finput"
                     placeholder="varsa barkod no girin"
                     value="<?= $val('barkod') ?>" />
            </div>
            <?= $err('barkod') ?>
            <a href="#" class="flink" id="barkodUret" style="margin-top:4px;font-size:12px;">
              (barkod üretmek için tıklayın)
            </a>
          </div>

          <div class="fg">
            <label class="flabel">
              Stok Takibi
              <i class="fa-solid fa-circle-question hi" title="Ürün stok takip yöntemi"></i>
            </label>
            <select name="stok_takibi" class="fselect">
              <option value="normal">Normal stok takibi</option>
              <option value="seri">Seri numarasıyla takip</option>
              <option value="lot">Lot numarasıyla takip</option>
              <option value="yok">Stok takibi yok</option>
            </select>
          </div>

          <div class="fg">
            <label class="flabel">
              Kritik Stok Miktarı
              <i class="fa-solid fa-circle-question hi" title="Bu miktarın altına düşünce uyarı verilir"></i>
            </label>
            <div class="fwrap">
              <i class="fa-solid fa-triangle-exclamation fi"></i>
              <input type="number" name="kritik_stok" class="finput"
                     placeholder="0" min="0" step="0.001"
                     value="<?= $val('kritik_stok', '0') ?>" />
            </div>
          </div>

          <div class="fg">
            <label class="flabel">Başlangıç Stok Miktarı</label>
            <div class="fwrap">
              <i class="fa-solid fa-boxes-stacked fi"></i>
              <input type="number" name="stok_miktari" class="finput"
                     placeholder="0" min="0" step="0.001"
                     value="<?= $val('stok_miktari', '0') ?>" />
            </div>
          </div>

          <div class="fg">
            <label class="flabel">GTIP</label>
            <input type="text" name="gtip" class="finput no-icon"
                   placeholder="varsa GTIP kodu girin"
                   value="<?= $val('gtip') ?>" />
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════
         TAB 4: RESİMLER
    ══════════════════════════════════════════════════ -->
    <div class="tab-panel" id="panel-resimler">
      <div class="info-box">
        <i class="fa-solid fa-circle-info" style="margin-right:6px;"></i>
        İlk eklediğiniz resim ana resim olarak gösterilir. Resimleri sürükleyerek sıralayabilirsiniz.
      </div>
      <div class="images-grid" id="imagesGrid">
        <!-- Ana resim slotu -->
        <label class="img-slot" id="mainImgSlot">
          <input type="file" name="resim_ana" accept="image/*" id="mainImgInput" />
          <i class="fa-solid fa-image"></i>
          <span>Ana Resim</span>
          <div class="img-main-badge">ANA</div>
        </label>
        <!-- Ek resim slotları -->
        <?php for ($s = 1; $s <= 5; $s++): ?>
        <label class="img-slot">
          <input type="file" name="resim_ek[]" accept="image/*" />
          <i class="fa-regular fa-image"></i>
          <span>Resim <?= $s ?></span>
        </label>
        <?php endfor; ?>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════
         TAB 5: VARYANT
    ══════════════════════════════════════════════════ -->
    <div class="tab-panel" id="panel-varyant">
      <div class="info-box">
        <i class="fa-solid fa-circle-info" style="margin-right:6px;"></i>
        Bu ürün için geçerli olan varyant değerlerini seçiniz.
      </div>
      
      <?php if (empty($varyantlar)): ?>
        <div class="tab-placeholder">
          <i class="fa-solid fa-sliders"></i>
          <p>Henüz tanımlanmış bir varyant bulunamadı. <a href="<?= BASE_URL ?>/urun/varyantlar">Varyant Yönetimi</a> sayfasından varyant ekleyebilirsiniz.</p>
        </div>
      <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 25px;">
          <?php foreach ($varyantlar as $v): ?>
            <div class="variant-group">
              <h4 style="font-size: 13px; font-weight: 700; color: var(--text); margin-bottom: 12px; border-bottom: 1px solid var(--border); padding-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">
                <?= htmlspecialchars($v['ad']) ?>
              </h4>
              <div style="display: flex; flex-wrap: wrap; gap: 12px;">
                <?php if (empty($v['degerler'])): ?>
                   <span style="font-size: 12px; color: var(--muted); font-style: italic;">Bu varyant için henüz değer tanımlanmamış.</span>
                <?php else: ?>
                  <?php foreach ($v['degerler'] as $d): ?>
                    <label style="display: flex; align-items: center; gap: 8px; background: var(--surface-2); border: 1px solid var(--border); padding: 6px 12px; border-radius: 6px; cursor: pointer; transition: background .2s;">
                      <input type="checkbox" name="varyant_degerleri[]" value="<?= $d['id'] ?>" 
                             class="custom-cb" <?= in_array($d['id'], $eski['varyant_degerleri'] ?? []) ? 'checked' : '' ?> />
                      <span style="font-size: 13px; font-weight: 600; color: var(--text2);"><?= htmlspecialchars($d['deger']) ?></span>
                    </label>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- ══════════════════════════════════════════════════
         TAB 6: BAĞLI ÜRÜNLER
    ══════════════════════════════════════════════════ -->
    <div class="tab-panel" id="panel-bagli">
      <div class="tab-placeholder">
        <i class="fa-solid fa-link"></i>
        <p>Bağlı ürün/aksesuar tanımlaması ürün kaydedildikten sonra yapılabilir.</p>
      </div>
    </div>

  </div><!-- /form-card -->
</form>

<script>
(function () {
  'use strict';

  /* ── Tab Switching ── */
  const tabs   = document.querySelectorAll('.tab-btn');
  const panels = document.querySelectorAll('.tab-panel');

  function activateTab(name) {
    tabs.forEach(t => t.classList.toggle('active', t.dataset.tab === name));
    panels.forEach(p => p.classList.toggle('active', p.id === 'panel-' + name));
  }

  tabs.forEach(t => t.addEventListener('click', () => activateTab(t.dataset.tab)));

  /* ── Sayfa yüklendiğinde hatalı sekmeye git ── */
  <?php if (!empty($hatalar)): ?>
  activateTab('<?= $ilkHataliTab ?>');
  <?php endif; ?>

  /* ── Barkod üret ── */
  const barkodUret  = document.getElementById('barkodUret');
  const barkodInput = document.getElementById('barkodInput');
  if (barkodUret && barkodInput) {
    barkodUret.addEventListener('click', function (e) {
      e.preventDefault();
      // EAN-13 benzeri rastgele barkod üret
      const rnd = Array.from({length: 12}, () => Math.floor(Math.random() * 10)).join('');
      let sum = 0;
      rnd.split('').forEach((d, i) => { sum += parseInt(d) * (i % 2 === 0 ? 1 : 3); });
      const check = (10 - (sum % 10)) % 10;
      barkodInput.value = rnd + check;
    });
  }

  /* ── Ana resim önizleme ── */
  const mainInput = document.getElementById('mainImgInput');
  const mainSlot  = document.getElementById('mainImgSlot');
  if (mainInput && mainSlot) {
    mainInput.addEventListener('change', function () {
      const file = this.files[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = e => {
        let img = mainSlot.querySelector('img');
        if (!img) {
          img = document.createElement('img');
          mainSlot.innerHTML = '';
          const badge = document.createElement('div');
          badge.className = 'img-main-badge';
          badge.textContent = 'ANA';
          mainSlot.appendChild(img);
          mainSlot.appendChild(badge);
        }
        img.src = e.target.result;
      };
      reader.readAsDataURL(file);
    });
  }

  /* ── Yeni kategori / marka ekle ── */
  function yeniTanimEkle(tur, inputEl, datalistId) {
    const ad = prompt('Yeni değer:');
    if (!ad || !ad.trim()) return;
    const body = new URLSearchParams({ tur: tur, ad: ad.trim(), renk: 'green' });
    fetch('<?= BASE_URL ?>/tanim/kaydetAjax', { method: 'POST', body: body })
      .then(res => res.json())
      .then(data => {
        if (!data.success) { alert(data.error || 'Kaydedilemedi.'); return; }
        const dl = document.getElementById(datalistId);
        if (dl && !dl.querySelector(`option[value="${CSS.escape(data.ad)}"]`)) {
          const opt = document.createElement('option');
          opt.value = data.ad;
          dl.appendChild(opt);
        }
        inputEl.value = data.ad;
      })
      .catch(() => alert('Kaydedilemedi.'));
  }

  const btnNewKat = document.getElementById('btnNewKat');
  if (btnNewKat) {
    btnNewKat.addEventListener('click', () => yeniTanimEkle('urun_kategori', document.getElementById('kategoriInput'), 'katList'));
  }
  const btnNewMarka = document.getElementById('btnNewMarka');
  if (btnNewMarka) {
    btnNewMarka.addEventListener('click', () => yeniTanimEkle('urun_marka', document.getElementById('markaInput'), 'markaList'));
  }

  /* ── Fiyatlandırma: canlı KDV / iskonto / kâr marjı önizlemesi ── */
  (function () {
    const $ = (n) => document.querySelector(`[name="${n}"]`);
    const satisFiyati = $('satis_fiyati');
    const kdvOraniSel = $('kdv_orani');
    const kdvDahilSel = $('kdv_dahil');
    const alisFiyati  = $('alis_fiyati');
    const alisIskonto = $('alis_iskonto');
    if (!satisFiyati || !kdvOraniSel || !kdvDahilSel || !alisFiyati || !alisIskonto) return;

    const elHaric = document.getElementById('ppSatisHaric');
    const elDahil = document.getElementById('ppSatisDahil');
    const elNet   = document.getElementById('ppAlisNet');
    const elMarji = document.getElementById('ppKarMarji');

    const num = (el) => {
      const v = parseFloat(String(el.value).replace(',', '.'));
      return isFinite(v) ? v : 0;
    };
    const tl = (n) => n.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function hesapla() {
      const kdvOrani = num(kdvOraniSel) / 100;
      const girilenSatis = num(satisFiyati);
      const kdvDahilGirildi = kdvDahilSel.value === '1';

      // Kullanıcı "KDV Dahil" seçtiyse girilen tutar zaten dahil kabul edilir;
      // "KDV Hariç" seçtiyse girilen tutar taban fiyat kabul edilir.
      const satisHaric = kdvDahilGirildi ? girilenSatis / (1 + kdvOrani) : girilenSatis;
      const satisDahil = kdvDahilGirildi ? girilenSatis : girilenSatis * (1 + kdvOrani);

      const alisNet = num(alisFiyati) * (1 - num(alisIskonto) / 100);

      elHaric.textContent = tl(satisHaric);
      elDahil.textContent = tl(satisDahil);
      elNet.textContent   = tl(alisNet);

      let marji = 0;
      if (satisHaric > 0) {
        marji = ((satisHaric - alisNet) / satisHaric) * 100;
      }
      elMarji.textContent = '%' + tl(marji);
      elMarji.classList.toggle('pp-neg', marji < 0);
      elMarji.classList.toggle('pp-pos', marji > 0);
    }

    [satisFiyati, kdvOraniSel, kdvDahilSel, alisFiyati, alisIskonto].forEach((el) => {
      el.addEventListener('input', hesapla);
      el.addEventListener('change', hesapla);
    });
    hesapla();
  })();
})();
</script>
