<style>
/* YENI EKLENEN HEAD STYLLERI */
/* ── Breadcrumb ── */
    .breadcrumb-bar { display: flex; align-items: center; gap: 6px; margin-bottom: 16px; font-size: 12.5px; color: var(--muted); }
    .breadcrumb-bar a { color: var(--muted); text-decoration: none; }
    .breadcrumb-bar a:hover { color: #4ade80; }
    .breadcrumb-bar i { font-size: 10px; }

    /* ── Action Bar ── */
    .action-bar { display: flex; align-items: center; gap: 10px; margin-bottom: 22px; }
    .btn-save { display: inline-flex; align-items: center; gap: 7px; background: #22c55e; color: #fff; border: none; padding: 9px 20px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: background .2s, box-shadow .2s; }
    .btn-save:hover { background: #16a34a; box-shadow: 0 4px 12px rgba(34,197,94,.3); }
    .btn-back { display: inline-flex; align-items: center; gap: 7px; background: #f59e0b; color: #fff; border: none; padding: 9px 20px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; transition: background .2s; }
    .btn-back:hover { background: #d97706; color: #fff; }

    /* ── Form Card ── */
    .form-card { background: var(--card-bg); border-radius: 14px; box-shadow: 0 2px 16px rgba(0,0,0,.08); overflow: hidden; }

    /* ── Tabs ── */
    .form-tabs { display: flex; border-bottom: 2px solid var(--border); background: var(--card-bg); overflow-x: auto; scrollbar-width: none; }
    .form-tabs::-webkit-scrollbar { display: none; }
    .tab-btn {
      display: inline-flex; align-items: center; gap: 7px;
      padding: 14px 22px; font-size: 12.5px; font-weight: 600;
      color: var(--muted); background: none; border: none; cursor: pointer;
      border-bottom: 3px solid transparent; margin-bottom: -2px;
      white-space: nowrap; text-transform: uppercase; letter-spacing: .5px;
      transition: color .2s, border-color .2s;
    }
    .tab-btn:hover { color: var(--text); }
    .tab-btn.active { color: #f59e0b; border-bottom-color: #f59e0b; }
    .tab-btn i { font-size: 13px; }

    /* ── Tab Panels ── */
    .tab-panel { display: none; padding: 28px; }
    .tab-panel.active { display: block; }

    /* ── Form Fields ── */
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px 28px; }
    .form-group { display: flex; flex-direction: column; gap: 5px; margin-bottom: 18px; }
    .form-group:last-child { margin-bottom: 0; }
    .form-label { font-size: 12px; font-weight: 600; color: var(--text2); text-transform: uppercase; letter-spacing: .5px; display: flex; align-items: center; gap: 6px; }
    .form-label .help-icon { color: var(--muted); font-size: 12px; cursor: help; }
    .form-input { padding: 9px 12px 9px 36px; border: 1.5px solid var(--border); border-radius: 8px; font-size: 13.5px; color: var(--text); background: var(--card-bg); outline: none; transition: border-color .2s, box-shadow .2s; width: 100%; }
    .form-input:focus { border-color: #f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,.12); }
    .form-input.no-icon { padding-left: 12px; }
    .form-textarea { padding: 10px 12px; border: 1.5px solid var(--border); border-radius: 8px; font-size: 13.5px; color: var(--text); background: var(--card-bg); outline: none; transition: border-color .2s, box-shadow .2s; width: 100%; resize: vertical; min-height: 90px; }
    .form-textarea:focus { border-color: #f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,.12); }
    .form-select { padding: 9px 12px; border: 1.5px solid var(--border); border-radius: 8px; font-size: 13.5px; color: var(--text); background: var(--card-bg); outline: none; transition: border-color .2s; width: 100%; cursor: pointer; }
    .form-select:focus { border-color: #f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,.12); }
    .input-wrap { position: relative; }
    .input-wrap i.field-icon { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--text2); font-size: 13px; pointer-events: none; }
    .form-hint { font-size: 11.5px; color: var(--muted); margin-top: 3px; }

    /* ── Kimlik Layout ── */
    .kimlik-layout { display: flex; gap: 28px; align-items: flex-start; }
    .kimlik-fields { flex: 1; }

    /* ── Resim Upload ── */
    .img-upload-area { width: 130px; height: 130px; border: 2px dashed var(--border2); border-radius: 12px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; cursor: pointer; transition: border-color .2s, background .2s; color: #f59e0b; background: rgba(243,156,18,.15); }
    .img-upload-area:hover { border-color: #f59e0b; background: rgba(245,158,11,.06); }
    .img-upload-area i { font-size: 28px; }
    .img-upload-area span { font-size: 12px; font-weight: 600; }
    .img-upload-area input[type="file"] { display: none; }

    /* ── Toggle Switch ── */
    .toggle-row { display: flex; align-items: center; gap: 12px; }
    .toggle-switch { position: relative; width: 44px; height: 24px; flex-shrink: 0; }
    .toggle-switch input { opacity: 0; width: 0; height: 0; }
    .toggle-track {
      position: absolute; inset: 0; background: var(--surface-2);
      border-radius: 24px; cursor: pointer; transition: background .25s;
    }
    .toggle-track::after {
      content: ''; position: absolute; left: 3px; top: 3px;
      width: 18px; height: 18px; border-radius: 50%; background: var(--card-bg);
      transition: transform .25s; box-shadow: 0 1px 4px rgba(0,0,0,.18);
    }
    .toggle-switch input:checked + .toggle-track { background: #f59e0b; }
    .toggle-switch input:checked + .toggle-track::after { transform: translateX(20px); }
    .toggle-label { font-size: 13px; color: var(--text2); }

    /* ── Sınıflandırma ── */
    .label-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 5px; }
    .label-row .form-label { margin: 0; }
    .btn-sinif { display: inline-flex; align-items: center; gap: 5px; background: #f59e0b; color: #fff; border: none; padding: 3px 10px; border-radius: 5px; font-size: 11px; font-weight: 600; cursor: pointer; transition: background .2s; }
    .btn-sinif:hover { background: #d97706; }

    /* ── Toast ── */
    .toast-container { position: fixed; bottom: 24px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 8px; }
    .toast { background: #1e293b; color: var(--text); padding: 12px 18px; border-radius: 10px; font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 10px; box-shadow: 0 8px 24px rgba(0,0,0,.25); animation: toastIn .3s ease; }
    .toast.success { border-left: 4px solid #4ade80; }
    .toast.error   { border-left: 4px solid #ef4444; }
    @keyframes toastIn { from { transform: translateX(40px); opacity: 0; } to { transform: none; opacity: 1; } }

    
    
    
  
  
  .nav-link.active:focus { color: #4ade80; outline: none; }
</style>

<!-- Breadcrumb -->
    <div class="breadcrumb-bar">
      <a href="<?= BASE_URL ?>/tedarikci"><i class="fa-solid fa-house"></i> Tedarikçiler</a>
      <i class="fa-solid fa-chevron-right"></i>
      <span>Yeni Tedarikçi Ekle</span>
    </div>

    <!-- Action Bar -->
    <div class="action-bar">
      <button class="btn-save" id="btnSave">
        <i class="fa-solid fa-floppy-disk"></i> Kaydet
      </button>
      <a href="<?= BASE_URL ?>/tedarikci" class="btn-back">
        <i class="fa-solid fa-arrow-left"></i> Geri Dön
      </a>
    </div>

    <!-- Form Card -->
    <form id="tedarikciForm" class="form-card" onsubmit="return false;">

      <!-- Tabs: Kimlik → Cari → İletişim → Diğer (müşteriden farklı sıra, Şubeler yok) -->
      <div class="form-tabs" role="tablist">
        <button class="tab-btn active" data-tab="kimlik" role="tab">
          <i class="fa-solid fa-id-card"></i> Kimlik Bilgileri
        </button>
        <button class="tab-btn" data-tab="cari" role="tab">
          <i class="fa-solid fa-lira-sign"></i> Cari
        </button>
        <button class="tab-btn" data-tab="iletisim" role="tab">
          <i class="fa-solid fa-envelope"></i> İletişim
        </button>
        <button class="tab-btn" data-tab="diger" role="tab">
          <i class="fa-solid fa-sliders"></i> Diğer
        </button>
      </div>

      <!-- ════ TAB: KİMLİK BİLGİLERİ ════ -->
      <div class="tab-panel active" id="panel-kimlik">
        <div class="kimlik-layout">
          <div class="kimlik-fields">
            <div class="form-group">
              <label class="form-label">İsmi / Unvanı</label>
              <div class="input-wrap">
                <i class="fa-solid fa-user field-icon"></i>
                <input type="text" name="unvan" class="form-input" id="tedarikci_adi" placeholder="Tedarikçi adı veya firma unvanı" />
              </div>
            </div>
          </div>
          <div>
            <label class="form-label" style="margin-bottom:8px;">Fotoğraf</label>
            <label class="img-upload-area" for="imgUpload" id="imgUploadLabel">
              <i class="fa-solid fa-camera"></i>
              <span>Resim Ekle</span>
              <input type="file" id="imgUpload" accept="image/*" />
            </label>
          </div>
        </div>
      </div>

      <!-- ════ TAB: CARİ ════ -->
      <!-- Farklılıklar: toggle switch, Tedarikçi Para Birimi, Açılış Bakiyesi metni ters,
           Risk limiti / İskonto / Özel fiyat listesi YOK -->
      <div class="tab-panel" id="panel-cari">
        <div class="form-row">
          <!-- Sol -->
          <div>
            <div class="form-group">
              <label class="form-label">Vergi Dairesi</label>
              <div class="input-wrap">
                <i class="fa-solid fa-building-columns field-icon"></i>
                <input type="text" name="vergi_dairesi" class="form-input" placeholder="Vergi dairesi adı" />
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Vergi / TC Kimlik No</label>
              <div class="input-wrap">
                <i class="fa-solid fa-hashtag field-icon"></i>
                <input type="text" name="vergi_no" class="form-input" placeholder="10 veya 11 haneli numara" maxlength="11" />
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">
                Vergiden Muaf?
                <i class="fa-solid fa-circle-question help-icon" title="İhracat, diplomatik vb. muafiyet durumları"></i>
              </label>
              <div class="toggle-row">
                <label class="toggle-switch">
                  <input type="checkbox" id="vergiMuaf" name="vergi_muaf" value="1" />
                  <span class="toggle-track"></span>
                </label>
                <span class="toggle-label" id="muafLabel">Hayır</span>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Banka Bilgileri</label>
              <textarea name="banka_bilgileri" class="form-textarea" placeholder="tedarikçinizin banka hesap bilgilerini girebilirsiniz" rows="4"></textarea>
            </div>
          </div>
          <!-- Sağ -->
          <div>
            <div class="form-group">
              <label class="form-label">
                Tedarikçi Para Birimi
                <i class="fa-solid fa-circle-question help-icon" title="Tedarikçi ile işlem yapılacak para birimi"></i>
              </label>
              <select name="para_birimi" class="form-select">
                <option value="TL">TL — Türk Lirası</option>
                <option value="USD">USD — ABD Doları</option>
                <option value="EUR">EUR — Euro</option>
                <option value="GBP">GBP — İngiliz Sterlini</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">
                Vade (gün)
                <i class="fa-solid fa-circle-question help-icon" title="Fatura ödeme vadesi gün sayısı"></i>
              </label>
              <div class="input-wrap">
                <i class="fa-solid fa-calendar-days field-icon"></i>
                <input type="number" name="vade_gun" class="form-input" placeholder="0" min="0" />
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">
                Açılış Bakiyesi
                <i class="fa-solid fa-circle-question help-icon" title="Tedarikçi size borçlu ise eksi girin"></i>
              </label>
              <div class="input-wrap">
                <i class="fa-solid fa-lira-sign field-icon"></i>
                <input type="number" name="bakiye" class="form-input" placeholder="0,00" step="0.01" />
              </div>
              <!-- Müşteri'den farklı: "tedarikçi size borçlu ise eksi girin" -->
              <span class="form-hint">Tedarikçi size borçlu ise eksi girin.</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ════ TAB: İLETİŞİM ════ -->
      <!-- Müşteriden farklı layout: Yetkili Kişi + Telefon yan yana üstte,
           E-Posta + Diğer Erişim Bilgileri yan yana, Adres sol altta -->
      <div class="tab-panel" id="panel-iletisim">
        <div class="form-row">
          <!-- Sol -->
          <div>
            <div class="form-group">
              <label class="form-label">Yetkili Kişi</label>
              <div class="input-wrap">
                <i class="fa-solid fa-user-tie field-icon"></i>
                <input type="text" name="yetkili_kisi" class="form-input" placeholder="Ad Soyad" />
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">E-Posta</label>
              <div class="input-wrap">
                <i class="fa-solid fa-envelope field-icon"></i>
                <input type="email" name="eposta" class="form-input" placeholder="ornek@domain.com" />
              </div>
              <span class="form-hint">Virgül ile ayırarak birden fazla adres girebilirsiniz.</span>
            </div>
            <div class="form-group">
              <label class="form-label">Adres</label>
              <textarea name="adres" class="form-textarea" placeholder="Açık adres" rows="4"></textarea>
            </div>
          </div>
          <!-- Sağ -->
          <div>
            <div class="form-group">
              <label class="form-label">Telefon</label>
              <div class="input-wrap">
                <i class="fa-solid fa-phone field-icon"></i>
                <input type="tel" name="telefon" class="form-input" placeholder="05XX XXX XX XX" />
              </div>
            </div>
            <div class="form-group" style="flex:1;">
              <label class="form-label">Diğer Erişim Bilgileri</label>
              <textarea name="diger_erisim_bilgileri" class="form-textarea" placeholder="Sabit telefon, faks vb." rows="7"></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- ════ TAB: DİĞER ════ -->
      <div class="tab-panel" id="panel-diger">
        <div class="form-row">
          <!-- Sol -->
          <div>
            <div class="form-group">
              <div class="label-row">
                <label class="form-label">
                  Sınıflandırma 1
                  <i class="fa-solid fa-circle-question help-icon" title="Tedarikçi gruplama kategorisi"></i>
                </label>
                <button class="btn-sinif" id="btnAddSinif1">
                  <i class="fa-solid fa-plus"></i> yeni sınıflandırma ekle
                </button>
              </div>
              <select class="form-select" name="sinif_1" id="sinif1Select">
                <option value="">— Seçiniz —</option>
                <?php foreach (($sinif1ler ?? []) as $s): ?>
                  <option value="<?= htmlspecialchars($s['ad']) ?>"><?= htmlspecialchars($s['ad']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <div class="label-row">
                <label class="form-label">
                  Sınıflandırma 2
                  <i class="fa-solid fa-circle-question help-icon" title="İkincil gruplama kategorisi"></i>
                </label>
                <button class="btn-sinif" id="btnAddSinif2">
                  <i class="fa-solid fa-plus"></i> yeni sınıflandırma ekle
                </button>
              </div>
              <select class="form-select" name="sinif_2" id="sinif2Select">
                <option value="">— Seçiniz —</option>
                <?php foreach (($sinif2ler ?? []) as $s): ?>
                  <option value="<?= htmlspecialchars($s['ad']) ?>"><?= htmlspecialchars($s['ad']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">
                Kodu
                <i class="fa-solid fa-circle-question help-icon" title="Muhasebe kodu veya barkod"></i>
              </label>
              <div class="input-wrap">
                <i class="fa-solid fa-barcode field-icon"></i>
                <input type="text" name="cari_kodu" class="form-input" placeholder="varsa muhasebe kodu veya barkod girebilirsiniz" />
              </div>
            </div>
          </div>
          <!-- Sağ -->
          <div>
            <div class="form-group" style="height:100%;">
              <label class="form-label">Not</label>
              <textarea name="notlar" class="form-textarea" placeholder="Tedarikçi hakkında notlar..." style="min-height:180px; height:calc(100% - 28px);"></textarea>
            </div>
          </div>
        </div>
      </div>

    </form><!-- /form-card -->

<div class="toast-container" id="toastContainer"></div>


<script>
(function () {
  'use strict';

  /* ── Tab Switching ── */
  const tabBtns   = document.querySelectorAll('.tab-btn');
  const tabPanels = document.querySelectorAll('.tab-panel');

  tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      tabBtns.forEach(b => b.classList.remove('active'));
      tabPanels.forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      document.getElementById(`panel-${btn.dataset.tab}`).classList.add('active');
    });
  });

  /* ── Resim Önizleme ── */
  const imgUpload = document.getElementById('imgUpload');
  if (imgUpload) {
      imgUpload.addEventListener('change', function () {
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

  /* ── Toggle Switch Label ── */
  const vergiMuaf = document.getElementById('vergiMuaf');
  if (vergiMuaf) {
      vergiMuaf.addEventListener('change', function () {
        document.getElementById('muafLabel').textContent = this.checked ? 'Evet' : 'Hayır';
      });
  }

  /* ── Toast ── */
  function showToast(msg, type = 'success') {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.innerHTML = `<i class="fa-solid fa-${type === 'success' ? 'check-circle' : 'circle-exclamation'}"></i> ${msg}`;
    container.appendChild(t);
    setTimeout(() => t.remove(), 3000);
  }

  /* ── Kaydet (AJAX) ── */
  const btnSave = document.getElementById('btnSave');
  const form = document.getElementById('tedarikciForm');
  
  if (btnSave && form) {
    btnSave.addEventListener('click', async function(e) {
      e.preventDefault();
      
      const adi = document.getElementById('tedarikci_adi').value.trim();
      if (!adi) {
        showToast('Tedarikçi adı / unvanı zorunludur.', 'error');
        // Switch back to kimlik tab
        tabBtns.forEach(b => b.classList.remove('active'));
        tabPanels.forEach(p => p.classList.remove('active'));
        const kimlikBtn = document.querySelector('[data-tab="kimlik"]');
        if (kimlikBtn) kimlikBtn.classList.add('active');
        const pKimlik = document.getElementById('panel-kimlik');
        if (pKimlik) pKimlik.classList.add('active');
        document.getElementById('tedarikci_adi').focus();
        return;
      }
      
      const formData = new FormData(form);
      
      try {
        const response = await fetch('<?= BASE_URL ?>/tedarikci/kaydet', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message, 'success');
            setTimeout(() => {
                window.location.href = '<?= BASE_URL ?>/tedarikci';
            }, 1200);
        } else {
            showToast(result.message, 'error');
        }
      } catch (error) {
        console.error(error);
        showToast('Sunucu ile iletişim kurulamadı.', 'error');
      }
    });
  }

  /* ── Yeni sınıflandırma ekle ── */
  function yeniSiniflandirmaEkle(tur, selectEl) {
    const ad = prompt('Yeni sınıflandırma adı:');
    if (!ad || !ad.trim()) return;
    const body = new URLSearchParams({ tur: tur, ad: ad.trim(), renk: 'green' });
    fetch('<?= BASE_URL ?>/tanim/kaydetAjax', { method: 'POST', body: body })
      .then(res => res.json())
      .then(data => {
        if (!data.success) { showToast(data.error || 'Kaydedilemedi.', 'error'); return; }
        if (!selectEl.querySelector(`option[value="${CSS.escape(data.ad)}"]`)) {
          const opt = document.createElement('option');
          opt.value = data.ad;
          opt.textContent = data.ad;
          selectEl.appendChild(opt);
        }
        selectEl.value = data.ad;
      })
      .catch(() => showToast('Kaydedilemedi.', 'error'));
  }

  const btnAddSinif1 = document.getElementById('btnAddSinif1');
  if (btnAddSinif1) {
    btnAddSinif1.addEventListener('click', () => yeniSiniflandirmaEkle('tedarikci_sinif_1', document.getElementById('sinif1Select')));
  }
  const btnAddSinif2 = document.getElementById('btnAddSinif2');
  if (btnAddSinif2) {
    btnAddSinif2.addEventListener('click', () => yeniSiniflandirmaEkle('tedarikci_sinif_2', document.getElementById('sinif2Select')));
  }

})();
</script>
