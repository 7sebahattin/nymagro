<?php
/**
 * View: musteriler/ekle.php
 * Beklenen değişkenler:
 *   $hatalar  array  — ['alan' => 'hata mesajı']
 *   $eski     array  — önceki POST verileri (hata sonrası form doldurmak için)
 */
$eski   = $eski   ?? [];
$hatalar = $hatalar ?? [];
$duzenleId  = $duzenleId ?? null;
$duzenleMod = $duzenleId !== null;

// Eski değer helper (XSS temizli)
$val = fn(string $key, string $default = '') =>
    htmlspecialchars($eski[$key] ?? $default);

// Hata helper
$err = fn(string $key) =>
    isset($hatalar[$key])
        ? '<span class="field-error"><i class="fa-solid fa-triangle-exclamation"></i> ' . htmlspecialchars($hatalar[$key]) . '</span>'
        : '';
?>
<style>
  .action-bar { display:flex; align-items:center; gap:10px; margin-bottom:22px; }
  .btn-save { display:inline-flex; align-items:center; gap:7px; background:#22c55e; color:#fff; border:none; padding:9px 20px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; transition:background .2s,box-shadow .2s; }
  .btn-save:hover { background:#16a34a; box-shadow:0 4px 12px rgba(34,197,94,.3); }
  .btn-back { display:inline-flex; align-items:center; gap:7px; background:#0ea5e9; color:#fff; border:none; padding:9px 20px; border-radius:8px; font-size:13px; font-weight:600; text-decoration:none; transition:background .2s; }
  .btn-back:hover { background:#0284c7; color:#fff; }
  .breadcrumb-bar { display:flex; align-items:center; gap:6px; margin-bottom:16px; font-size:12.5px; color:var(--muted); }
  .breadcrumb-bar a { color:var(--muted); text-decoration:none; }
  .breadcrumb-bar a:hover { color:#4ade80; }
  .form-card { background:var(--card-bg); border-radius:14px; box-shadow:0 2px 16px rgba(0,0,0,.08); overflow:hidden; }
  .form-tabs { display:flex; border-bottom:2px solid var(--border); background:var(--card-bg); overflow-x:auto; scrollbar-width:none; }
  .form-tabs::-webkit-scrollbar { display:none; }
  .tab-btn { display:inline-flex; align-items:center; gap:7px; padding:14px 20px; font-size:12.5px; font-weight:600; color:var(--muted); background:none; border:none; cursor:pointer; border-bottom:3px solid transparent; margin-bottom:-2px; white-space:nowrap; text-transform:uppercase; letter-spacing:.5px; transition:color .2s,border-color .2s; }
  .tab-btn:hover { color:var(--text); }
  .tab-btn.active { color:#0ea5e9; border-bottom-color:#0ea5e9; }
  .tab-panel { display:none; padding:28px; }
  .tab-panel.active { display:block; }
  .form-row { display:grid; grid-template-columns:1fr 1fr; gap:20px 28px; }
  .form-group { display:flex; flex-direction:column; gap:5px; margin-bottom:18px; }
  .form-group:last-child { margin-bottom:0; }
  .form-label { font-size:12px; font-weight:600; color:var(--text2); text-transform:uppercase; letter-spacing:.5px; display:flex; align-items:center; gap:6px; }
  .form-input { padding:9px 12px 9px 36px; border:1.5px solid var(--border); border-radius:8px; font-size:13.5px; color:var(--text); background:var(--card-bg); outline:none; transition:border-color .2s,box-shadow .2s; width:100%; }
  .form-input:focus { border-color:#0ea5e9; box-shadow:0 0 0 3px rgba(14,165,233,.12); }
  .form-input.no-icon { padding-left:12px; }
  .form-input.is-error { border-color:#ef4444; }
  .form-textarea { padding:10px 12px; border:1.5px solid var(--border); border-radius:8px; font-size:13.5px; color:var(--text); background:var(--card-bg); outline:none; transition:border-color .2s,box-shadow .2s; width:100%; resize:vertical; min-height:90px; }
  .form-textarea:focus { border-color:#0ea5e9; box-shadow:0 0 0 3px rgba(14,165,233,.12); }
  .form-select { padding:9px 12px; border:1.5px solid var(--border); border-radius:8px; font-size:13.5px; color:var(--text); background:var(--card-bg); outline:none; width:100%; cursor:pointer; }
  .form-select:focus { border-color:#0ea5e9; box-shadow:0 0 0 3px rgba(14,165,233,.12); }
  .input-wrap { position:relative; }
  .input-wrap i.icon { position:absolute; left:11px; top:50%; transform:translateY(-50%); color:var(--text2); font-size:13px; pointer-events:none; }
  .form-hint { font-size:11.5px; color:var(--muted); }
  .field-error { font-size:11.5px; color:#ef4444; display:flex; align-items:center; gap:4px; }
  .kimlik-layout { display:flex; gap:28px; align-items:flex-start; }
  .kimlik-fields { flex:1; }
  .img-upload-area { width:130px; height:130px; border:2px dashed var(--border2); border-radius:12px; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px; cursor:pointer; transition:border-color .2s,background .2s; color:#0ea5e9; background:var(--surface-2); }
  .img-upload-area:hover { border-color:#0ea5e9; background:rgba(14,165,233,.05); }
  .img-upload-area i { font-size:28px; }
  .img-upload-area span { font-size:12px; font-weight:600; }
  .empty-state { text-align:center; padding:48px 0; color:var(--muted); }
  .empty-state i { font-size:40px; margin-bottom:12px; display:block; }
  .alert-error { background:rgba(231,76,60,.15); border:1px solid rgba(231,76,60,.28); color:var(--danger); padding:12px 18px; border-radius:8px; margin-bottom:16px; font-size:13px; }
  @media (max-width:768px) { .form-row { grid-template-columns:1fr; } .kimlik-layout { flex-direction:column; } }
</style>

<!-- Breadcrumb -->
<div class="breadcrumb-bar">
  <a href="<?= BASE_URL ?>/musteri"><i class="fa-solid fa-house"></i> Müşteriler</a>
  <i class="fa-solid fa-chevron-right"></i>
  <span><?= $duzenleMod ? 'Müşteri Düzenle' : 'Yeni Müşteri Ekle' ?></span>
</div>

<!-- Action Bar -->
<div class="action-bar">
  <button type="submit" form="musteriForm" class="btn-save">
    <i class="fa-solid fa-floppy-disk"></i> <?= $duzenleMod ? 'Güncelle' : 'Kaydet' ?>
  </button>
  <a href="<?= BASE_URL ?>/musteri<?= $duzenleMod ? '/detay/' . (int)$duzenleId : '' ?>" class="btn-back">
    <i class="fa-solid fa-arrow-left"></i> Geri Dön
  </a>
</div>

<?php if (!empty($hatalar['genel'])): ?>
  <div class="alert-error">
    <i class="fa-solid fa-circle-exclamation"></i>
    <?= htmlspecialchars($hatalar['genel']) ?>
  </div>
<?php endif; ?>

<!-- Form Card -->
<form id="musteriForm" method="POST"
      action="<?= $duzenleMod ? BASE_URL . '/musteri/guncelle/' . (int)$duzenleId : BASE_URL . '/musteri/kaydet' ?>"
      enctype="multipart/form-data">

  <div class="form-card">

    <!-- Tabs -->
    <div class="form-tabs" role="tablist">
      <button type="button" class="tab-btn active" data-tab="kimlik"><i class="fa-solid fa-id-card"></i> Kimlik Bilgileri</button>
      <button type="button" class="tab-btn" data-tab="iletisim"><i class="fa-solid fa-envelope"></i> İletişim</button>
      <button type="button" class="tab-btn" data-tab="cari"><i class="fa-solid fa-lira-sign"></i> Cari</button>
      <button type="button" class="tab-btn" data-tab="diger"><i class="fa-solid fa-sliders"></i> Diğer</button>
      <button type="button" class="tab-btn" data-tab="subeler"><i class="fa-solid fa-code-branch"></i> Şubeler</button>
    </div>

    <!-- ════ TAB: KİMLİK ════ -->
    <div class="tab-panel active" id="panel-kimlik">
      <div class="kimlik-layout">
        <div class="kimlik-fields">
          <div class="form-group">
            <label class="form-label">İsmi / Unvanı <span style="color:#ef4444;">*</span></label>
            <div class="input-wrap">
              <i class="fa-solid fa-user icon"></i>
              <input type="text" name="unvan" id="unvan"
                     class="form-input <?= isset($hatalar['unvan']) ? 'is-error' : '' ?>"
                     placeholder="Müşteri adı veya firma unvanı"
                     value="<?= $val('unvan') ?>" autofocus />
            </div>
            <?= $err('unvan') ?>
          </div>
        </div>
        <div>
          <label class="form-label" style="margin-bottom:8px;">Fotoğraf</label>
          <?php $mevcutResim = $eski['resim_yolu'] ?? ''; ?>
          <label class="img-upload-area" for="imgUpload" id="imgUploadLabel"
                 <?php if ($mevcutResim !== ''): ?>style="border-style:solid;padding:0;"<?php endif; ?>>
            <?php if ($mevcutResim !== ''): ?>
              <img src="<?= BASE_URL ?>/<?= htmlspecialchars($mevcutResim) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:10px;" alt="Mevcut fotoğraf" />
            <?php else: ?>
              <i class="fa-solid fa-camera"></i>
              <span>Resim Ekle</span>
            <?php endif; ?>
            <input type="file" id="imgUpload" name="resim" accept="image/*" style="display:none;" />
          </label>
        </div>
      </div>
    </div>

    <!-- ════ TAB: İLETİŞİM ════ -->
    <div class="tab-panel" id="panel-iletisim">
      <div class="form-row">
        <div>
          <div class="form-group">
            <label class="form-label">E-Posta</label>
            <div class="input-wrap">
              <i class="fa-solid fa-envelope icon"></i>
              <input type="email" name="eposta"
                     class="form-input <?= isset($hatalar['eposta']) ? 'is-error' : '' ?>"
                     placeholder="ornek@domain.com"
                     value="<?= $val('eposta') ?>" />
            </div>
            <?= $err('eposta') ?>
          </div>
          <div class="form-group">
            <label class="form-label">Cep Telefonu</label>
            <div class="input-wrap">
              <i class="fa-solid fa-phone icon"></i>
              <input type="tel" name="cep_telefon" class="form-input"
                     placeholder="05XX XXX XX XX"
                     value="<?= $val('cep_telefon') ?>" />
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Diğer / Sabit Hat</label>
            <div class="input-wrap">
              <i class="fa-solid fa-phone icon"></i>
              <input type="tel" name="telefon" class="form-input"
                     placeholder="0212 XXX XX XX"
                     value="<?= $val('telefon') ?>" />
            </div>
          </div>
        </div>
        <div>
          <div class="form-group">
            <label class="form-label">Yetkili Kişi</label>
            <div class="input-wrap">
              <i class="fa-solid fa-user-tie icon"></i>
              <input type="text" name="yetkili_kisi" class="form-input"
                     placeholder="Ad Soyad"
                     value="<?= $val('yetkili_kisi') ?>" />
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Adres</label>
            <textarea name="adres" class="form-textarea" placeholder="Açık adres" rows="4"><?= $val('adres') ?></textarea>
          </div>
        </div>
      </div>
    </div>

    <!-- ════ TAB: CARİ ════ -->
    <div class="tab-panel" id="panel-cari">
      <div class="form-row">
        <div>
          <div class="form-group">
            <label class="form-label">Vergi Dairesi</label>
            <div class="input-wrap">
              <i class="fa-solid fa-building-columns icon"></i>
              <input type="text" name="vergi_dairesi" class="form-input"
                     placeholder="Vergi dairesi adı"
                     value="<?= $val('vergi_dairesi') ?>" />
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Vergi / TC Kimlik No</label>
            <div class="input-wrap">
              <i class="fa-solid fa-hashtag icon"></i>
              <input type="text" name="vergi_no"
                     class="form-input <?= isset($hatalar['vergi_no']) ? 'is-error' : '' ?>"
                     placeholder="10 veya 11 haneli numara" maxlength="11"
                     value="<?= $val('vergi_no') ?>" />
            </div>
            <?= $err('vergi_no') ?>
          </div>
          <div class="form-group">
            <label class="form-label">TC Kimlik No (ayrıca)</label>
            <div class="input-wrap">
              <i class="fa-solid fa-id-card icon"></i>
              <input type="text" name="tc_kimlik_no" class="form-input"
                     placeholder="11 haneli TC" maxlength="11"
                     value="<?= $val('tc_kimlik_no') ?>" />
            </div>
          </div>
        </div>
        <div>
          <div class="form-group">
            <label class="form-label">Müşteri Para Birimi</label>
            <select name="para_birimi" class="form-select">
              <?php foreach (['TRY'=>'TL — Türk Lirası','USD'=>'USD — ABD Doları','EUR'=>'EUR — Euro','GBP'=>'GBP — Sterlin'] as $k=>$v): ?>
                <option value="<?= $k ?>" <?= ($eski['para_birimi'] ?? 'TRY') === $k ? 'selected' : '' ?>><?= $v ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php if ($duzenleMod): ?>
          <div class="form-group">
            <label class="form-label">Açık Bakiye</label>
            <div class="input-wrap">
              <i class="fa-solid fa-lira-sign icon"></i>
              <input type="text" class="form-input no-icon" disabled
                     value="<?= number_format((float)($eski['bakiye'] ?? 0), 2, ',', '.') ?> TL" />
            </div>
            <span class="form-hint">Bakiye; faturalar ile tahsilat/ödeme kayıtlarından otomatik hesaplanır, buradan düzenlenemez.</span>
          </div>
          <?php else: ?>
          <div class="form-group">
            <label class="form-label">Açılış Bakiyesi</label>
            <div class="input-wrap">
              <i class="fa-solid fa-lira-sign icon"></i>
              <input type="number" name="bakiye" class="form-input"
                     placeholder="0,00" step="0.01"
                     value="<?= $val('bakiye', '0') ?>" />
            </div>
            <span class="form-hint">Müşteri sizden alacaklı ise eksi girin.</span>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- ════ TAB: DİĞER ════ -->
    <div class="tab-panel" id="panel-diger">
      <div class="form-row">
        <div>
          <div class="form-group">
            <label class="form-label">Kodu</label>
            <div class="input-wrap">
              <i class="fa-solid fa-barcode icon"></i>
              <input type="text" name="cari_kodu" class="form-input"
                     placeholder="Muhasebe kodu veya barkod"
                     value="<?= $val('cari_kodu') ?>" />
            </div>
          </div>
        </div>
        <div>
          <div class="form-group" style="height:100%;">
            <label class="form-label">Not</label>
            <textarea name="notlar" class="form-textarea"
                      placeholder="Müşteri hakkında notlar..."
                      style="min-height:160px;"><?= $val('notlar') ?></textarea>
          </div>
        </div>
      </div>
    </div>

    <!-- ════ TAB: ŞUBELER ════ -->
    <div class="tab-panel" id="panel-subeler">
      <div class="empty-state">
        <i class="fa-solid fa-code-branch" style="color:var(--text2);"></i>
        <p>Henüz şube eklenmemiş. Müşteriyi kaydettikten sonra şube ekleyebilirsiniz.</p>
      </div>
    </div>

  </div><!-- /form-card -->
</form>

<script>
(function(){
  'use strict';

  /* ── Tab Switching ── */
  const tabBtns   = document.querySelectorAll('.tab-btn');
  const tabPanels = document.querySelectorAll('.tab-panel');
  tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      tabBtns.forEach(b => b.classList.remove('active'));
      tabPanels.forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      document.getElementById('panel-' + btn.dataset.tab).classList.add('active');
    });
  });

  /* ── Resim Önizleme ── */
  const imgInput = document.getElementById('imgUpload');
  if (imgInput) {
    imgInput.addEventListener('change', function () {
      const file = this.files[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = e => {
        const label = document.getElementById('imgUploadLabel');
        label.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;border-radius:10px;" alt="önizleme" />`;
      };
      reader.readAsDataURL(file);
    });
  }

  /* ── Hata varsa ilgili tab'a geç ── */
  <?php if (!empty($hatalar) && !isset($hatalar['genel'])): ?>
    const firstError = document.querySelector('.is-error');
    if (firstError) {
      const panel = firstError.closest('.tab-panel');
      if (panel) {
        tabBtns.forEach(b => b.classList.remove('active'));
        tabPanels.forEach(p => p.classList.remove('active'));
        const tabId = panel.id.replace('panel-', '');
        const btn = document.querySelector('[data-tab="' + tabId + '"]');
        if (btn) btn.classList.add('active');
        panel.classList.add('active');
      }
    }
  <?php endif; ?>

})();
</script>
