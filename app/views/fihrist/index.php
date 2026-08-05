<style>
/* YENI EKLENEN HEAD STYLLERI */
/* ── Fihrist Styles ── */
    .btn-green { background-color: #5cb85c; border-color: #4cae4c; color: #fff; }
    .btn-green:hover { background-color: #449d44; border-color: #398439; color: #fff; }
    .btn-orange { background-color: #f0ad4e; border-color: #eea236; color: #fff; }
    .btn-orange:hover { background-color: #ec971f; border-color: #d58512; color: #fff; }

    .info-alert {
      background-color: #fcf8e3;
      border: 1px solid #faebcc;
      color: #8a6d3b;
      font-size: 13px;
      border-radius: 4px;
      padding: 16px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }

    /* Tabs */
    .card-tabs-header {
      background-color: #5cb85c;
      border-radius: 4px 4px 0 0;
      padding: 0;
    }
    .fihrist-tabs { border-bottom: none; }
    .fihrist-tabs .nav-link {
      color: #fff;
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      padding: 14px 24px;
      border: none;
      border-radius: 0;
      transition: background-color 0.2s;
    }
    .fihrist-tabs .nav-link:hover {
      background-color: rgba(255,255,255,0.1);
      color: #fff;
    }
    .fihrist-tabs .nav-link.active {
      background-color: #fff !important;
      color: #5cb85c !important;
      border-radius: 4px 4px 0 0;
    }

    /* Form Fields */
    .form-label-right {
      text-align: right;
      font-size: 13px;
      color: #777;
      font-weight: 500;
      padding-top: 6px;
    }
    .form-control-custom, .form-select-custom {
      font-size: 13px;
      border-radius: 4px;
      border: 1px solid #ccc;
      color: #555;
    }
    .form-control-custom:focus, .form-select-custom:focus {
      border-color: #66afe9;
      box-shadow: inset 0 1px 1px rgba(0,0,0,.075), 0 0 8px rgba(102,175,233,.6);
    }
    .add-link {
      font-size: 11px;
      color: #5cb85c;
      text-decoration: none;
      font-weight: 600;
      display: inline-block;
      margin-top: 4px;
    }
    .add-link:hover { text-decoration: underline; color: #449d44; }

    /* Input group with icon */
    .input-group-custom .input-group-text {
      background: #fff;
      border: 1px solid #ccc;
      border-right: none;
      color: #999;
      font-size: 13px;
    }
    .input-group-custom .form-control {
      border-left: none;
    }

    /* Image Upload Box */
    .img-upload-box {
      border: 2px dashed #ccc;
      border-radius: 4px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 30px 20px;
      cursor: pointer;
      color: #31b0d5;
      transition: border-color 0.2s, background-color 0.2s;
      width: 140px;
    }
    .img-upload-box:hover {
      border-color: #31b0d5;
      background-color: #f4fbfc;
    }
    .img-upload-box i { font-size: 32px; margin-bottom: 8px; }
    .img-upload-box span { font-size: 13px; font-weight: 600; }

    
    
    
  
  
  .nav-link.active:focus { color: #4ade80; outline: none; }
</style>

<style>
/* ── Fihrist Styles ── */
    .btn-green { background-color: #5cb85c; border-color: #4cae4c; color: #fff; }
    .btn-green:hover { background-color: #449d44; border-color: #398439; color: #fff; }
    .btn-orange { background-color: #f0ad4e; border-color: #eea236; color: #fff; }
    .btn-orange:hover { background-color: #ec971f; border-color: #d58512; color: #fff; }

    .info-alert {
      background-color: #fcf8e3;
      border: 1px solid #faebcc;
      color: #8a6d3b;
      font-size: 13px;
      border-radius: 4px;
      padding: 16px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);

    /* Tabs */
    .card-tabs-header {
      background-color: #5cb85c;
      border-radius: 4px 4px 0 0;
      padding: 0;

    .fihrist-tabs { border-bottom: none; }
    .fihrist-tabs .nav-link {
      color: #fff;
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      padding: 14px 24px;
      border: none;
      border-radius: 0;
      transition: background-color 0.2s;

    .fihrist-tabs .nav-link:hover {
      background-color: rgba(255,255,255,0.1);
      color: #fff;

    .fihrist-tabs .nav-link.active {
      background-color: #fff !important;
      color: #5cb85c !important;
      border-radius: 4px 4px 0 0;

    /* Form Fields */
    .form-label-right {
      text-align: right;
      font-size: 13px;
      color: #777;
      font-weight: 500;
      padding-top: 6px;

    .form-control-custom, .form-select-custom {
      font-size: 13px;
      border-radius: 4px;
      border: 1px solid #ccc;
      color: #555;

    .form-control-custom:focus, .form-select-custom:focus {
      border-color: #66afe9;
      box-shadow: inset 0 1px 1px rgba(0,0,0,.075), 0 0 8px rgba(102,175,233,.6);

    .add-link {
      font-size: 11px;
      color: #5cb85c;
      text-decoration: none;
      font-weight: 600;
      display: inline-block;
      margin-top: 4px;

    .add-link:hover { text-decoration: underline; color: #449d44; }

    /* Input group with icon */
    .input-group-custom .input-group-text {
      background: #fff;
      border: 1px solid #ccc;
      border-right: none;
      color: #999;
      font-size: 13px;

    .input-group-custom .form-control {
      border-left: none;

    /* Image Upload Box */
    .img-upload-box {
      border: 2px dashed #ccc;
      border-radius: 4px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 30px 20px;
      cursor: pointer;
      color: #31b0d5;
      transition: border-color 0.2s, background-color 0.2s;
      width: 140px;

    .img-upload-box:hover {
      border-color: #31b0d5;
      background-color: #f4fbfc;

    .img-upload-box i { font-size: 32px; margin-bottom: 8px; }
    .img-upload-box span { font-size: 13px; font-weight: 600; }

     
      .form-label-right { text-align: left; padding-top: 0; padding-bottom: 4px; }
      .img-upload-box { width: 100%; margin-top: 20px; }


  .nav-link.active:focus { color: #4ade80; outline: none; }
</style>

<!-- LİSTE GÖRÜNÜMÜ -->
    <div id="view-list">
      <div class="mb-3 d-flex gap-2">
        <button class="btn btn-green btn-sm px-3" id="btnYeniKart"><i class="fa-solid fa-plus"></i> Yeni Kart Ekle</button>
        <button class="btn btn-orange btn-sm px-3"><i class="fa-solid fa-file-excel"></i> Excel'e Aktar</button>
      </div>

      <div class="mb-3" style="max-width: 250px;">
        <select class="form-select form-select-custom">
          <option>Tüm Sınıflar</option>
        </select>
      </div>

      <div class="info-alert">
        Müşteri ya da tedarikçileriniz haricinde bilgilerinizi saklamak istediğiniz kişi yada firmaları (muhasebeciniz, köşedeki pideci, banka şubesi vs) burada kaydedebilirsiniz.<br><br>
        Yukarıdaki <span class="text-success fw-bold">'Yeni Kart Ekle'</span> düğmesini kullanarak kayıt ekleyebilirsiniz.
      </div>
    </div>

    <!-- EKLEME FORMU GÖRÜNÜMÜ (Varsayılan olarak gizli) -->
    <div id="view-form" style="display: none;">
      <div class="mb-3 d-flex gap-2">
        <button class="btn btn-green btn-sm px-3"><i class="fa-solid fa-check"></i> Kaydet</button>
        <button class="btn btn-orange btn-sm px-3" id="btnGeri"><i class="fa-solid fa-reply"></i> Geri Dön</button>
      </div>

      <div class="card border-0 shadow-sm mt-2">
        <!-- Tabs Header -->
        <div class="card-header card-tabs-header">
          <ul class="nav nav-tabs fihrist-tabs" id="fihristTab" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="kart-tab" data-bs-toggle="tab" data-bs-target="#kart-pane" type="button" role="tab">
                <i class="fa-solid fa-user me-2"></i> KART BİLGİLERİ
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="diger-tab" data-bs-toggle="tab" data-bs-target="#diger-pane" type="button" role="tab">
                <i class="fa-solid fa-bars me-2"></i> DİĞER BİLGİLER
              </button>
            </li>
          </ul>
        </div>

        <!-- Tabs Body -->
        <div class="card-body p-4" style="background-color: #fff;">
          <div class="tab-content" id="fihristTabContent">
            
            <!-- KART BİLGİLERİ -->
            <div class="tab-pane fade show active" id="kart-pane" role="tabpanel">
              <div class="row">
                <div class="col-lg-8">
                  <div class="row mb-3">
                    <div class="col-md-3 form-label-right">İsim / Unvan</div>
                    <div class="col-md-9">
                      <div class="input-group input-group-sm input-group-custom">
                        <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                        <input type="text" class="form-control form-control-custom">
                      </div>
                    </div>
                  </div>

                  <div class="row mb-3">
                    <div class="col-md-3 form-label-right">Sınıflandırma 1</div>
                    <div class="col-md-9">
                      <select class="form-select form-select-custom form-select-sm">
                        <option></option>
                      </select>
                      <div class="text-end">
                        <a href="#" class="add-link">+ yeni sınıflandırma ekle</a>
                      </div>
                    </div>
                  </div>

                  <div class="row mb-3">
                    <div class="col-md-3 form-label-right">Sınıflandırma 2</div>
                    <div class="col-md-9">
                      <select class="form-select form-select-custom form-select-sm">
                        <option></option>
                      </select>
                      <div class="text-end">
                        <a href="#" class="add-link">+ yeni sınıflandırma ekle</a>
                      </div>
                    </div>
                  </div>

                  <div class="row mb-3">
                    <div class="col-md-3 form-label-right">Yetkili Kişi</div>
                    <div class="col-md-9">
                      <div class="input-group input-group-sm input-group-custom">
                        <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                        <input type="text" class="form-control form-control-custom">
                      </div>
                    </div>
                  </div>

                  <div class="row mb-3">
                    <div class="col-md-3 form-label-right">E-Posta</div>
                    <div class="col-md-9">
                      <div class="input-group input-group-sm input-group-custom">
                        <span class="input-group-text"><i class="fa-regular fa-envelope"></i></span>
                        <input type="text" class="form-control form-control-custom">
                      </div>
                    </div>
                  </div>

                  <div class="row mb-3">
                    <div class="col-md-3 form-label-right">Telefonu</div>
                    <div class="col-md-9">
                      <div class="input-group input-group-sm input-group-custom mb-2">
                        <span class="input-group-text"><i class="fa-solid fa-phone"></i></span>
                        <input type="text" class="form-control form-control-custom">
                      </div>
                      <div class="input-group input-group-sm input-group-custom">
                        <span class="input-group-text"><i class="fa-solid fa-phone"></i></span>
                        <input type="text" class="form-control form-control-custom">
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Resim Ekle Kutusu -->
                <div class="col-lg-4 text-center mt-3 mt-lg-0">
                  <div class="img-upload-box mx-auto ms-lg-4">
                    <i class="fa-solid fa-camera"></i>
                    <span>Resim Ekle</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- DİĞER BİLGİLER -->
            <div class="tab-pane fade" id="diger-pane" role="tabpanel">
              <div class="row mb-3">
                <div class="col-md-2 form-label-right">Adres</div>
                <div class="col-md-8">
                  <textarea class="form-control form-control-custom" rows="3"></textarea>
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-2 form-label-right">Not</div>
                <div class="col-md-8">
                  <textarea class="form-control form-control-custom" rows="3"></textarea>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>

<script>
(function () {
  'use strict';
  
  // Sidebar accordion
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

  // View toggling logic
  const viewList = document.getElementById('view-list');
  const viewForm = document.getElementById('view-form');
  const btnYeniKart = document.getElementById('btnYeniKart');
  const btnGeri = document.getElementById('btnGeri');

  btnYeniKart.addEventListener('click', () => {
    viewList.style.display = 'none';
    viewForm.style.display = 'block';
  });

  btnGeri.addEventListener('click', () => {
    viewForm.style.display = 'none';
    viewList.style.display = 'block';
  });


  // Close profile dropdown when clicking outside
  document.addEventListener('click', function(e) {
    const profileDropdown = document.getElementById('profileDropdown');
    const userDropdownBtn = document.getElementById('userDropdownBtn');
    if (profileDropdown && userDropdownBtn && !profileDropdown.contains(e.target) && !userDropdownBtn.contains(e.target)) {
      profileDropdown.classList.remove('show');
    }
  });

})();
</script>