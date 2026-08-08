<?php
$h = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$flash = $flash ?? [];
$kayitlar = $kayitlar ?? [];
$grouped = $grouped ?? [];
$siniflar1 = $grouped['fihrist_grup_1'] ?? [];
$siniflar2 = $grouped['fihrist_grup_2'] ?? [];
?>
<style>
/* ── Fihrist Styles ── */
    .btn-green { background-color: #5cb85c; border-color: #4cae4c; color: #fff; }
    .btn-green:hover { background-color: #449d44; border-color: #398439; color: #fff; }
    .btn-orange { background-color: #f0ad4e; border-color: #eea236; color: #fff; }
    .btn-orange:hover { background-color: #ec971f; border-color: #d58512; color: #fff; }

    .info-alert {
      background-color: rgba(243,156,18,.15);
      border: 1px solid rgba(243,156,18,.28);
      color: var(--warning);
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
      background-color: var(--card-bg) !important;
      color: #5cb85c !important;
      border-radius: 4px 4px 0 0;
    }

    /* Form Fields */
    .form-label-right {
      text-align: right;
      font-size: 13px;
      color: var(--muted);
      font-weight: 500;
      padding-top: 6px;
    }
    .form-control-custom, .form-select-custom {
      font-size: 13px;
      border-radius: 4px;
      border: 1px solid var(--border);
      color: var(--text2);
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
      background: var(--card-bg);
      border: 1px solid var(--border);
      border-right: none;
      color: var(--muted);
      font-size: 13px;
    }
    .input-group-custom .form-control {
      border-left: none;
    }

    .fihrist-list-table th { font-size: 12px; color: var(--muted); }
    .fihrist-list-table td { font-size: 13px; vertical-align: middle; }

    @media (max-width: 992px) {
      .form-label-right { text-align: left; padding-top: 0; padding-bottom: 4px; }
    }
</style>

<?php if (!empty($flash)): ?>
  <div class="alert alert-<?= $h($flash['tip'] === 'error' ? 'danger' : $flash['tip']) ?>"><?= $h($flash['mesaj'] ?? '') ?></div>
<?php endif; ?>

<!-- LİSTE GÖRÜNÜMÜ -->
<div id="view-list">
  <div class="mb-3 d-flex gap-2">
    <button type="button" class="btn btn-green btn-sm px-3" id="btnYeniKart"><i class="fa-solid fa-plus"></i> Yeni Kart Ekle</button>
  </div>

  <?php if (empty($kayitlar)): ?>
    <div class="info-alert">
      Müşteri ya da tedarikçileriniz haricinde bilgilerinizi saklamak istediğiniz kişi yada firmaları (muhasebeciniz, köşedeki pideci, banka şubesi vs) burada kaydedebilirsiniz.<br><br>
      Yukarıdaki <span class="text-success fw-bold">'Yeni Kart Ekle'</span> düğmesini kullanarak kayıt ekleyebilirsiniz.
    </div>
  <?php else: ?>
    <div class="card border-0 shadow-sm">
      <div class="table-responsive">
        <table class="table align-middle mb-0 fihrist-list-table">
          <thead class="table-light">
            <tr>
              <th>İsim / Unvan</th>
              <th>Sınıflandırma</th>
              <th>Yetkili</th>
              <th>E-Posta</th>
              <th>Telefon</th>
              <th class="text-end">İşlem</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($kayitlar as $k): ?>
            <tr>
              <td class="fw-semibold"><?= $h($k['unvan']) ?></td>
              <td><?= $h(trim(($k['siniflandirma_1'] ?? '') . ($k['siniflandirma_2'] ? ' / ' . $k['siniflandirma_2'] : ''))) ?: '-' ?></td>
              <td><?= $h($k['yetkili'] ?: '-') ?></td>
              <td><?= $h($k['eposta'] ?: '-') ?></td>
              <td><?= $h($k['telefon1'] ?: '-') ?></td>
              <td class="text-end">
                <button type="button" class="btn btn-sm btn-outline-dark btn-edit-kart"
                        data-id="<?= (int)$k['id'] ?>"
                        data-unvan="<?= $h($k['unvan']) ?>"
                        data-siniflandirma1="<?= $h($k['siniflandirma_1']) ?>"
                        data-siniflandirma2="<?= $h($k['siniflandirma_2']) ?>"
                        data-yetkili="<?= $h($k['yetkili']) ?>"
                        data-eposta="<?= $h($k['eposta']) ?>"
                        data-telefon1="<?= $h($k['telefon1']) ?>"
                        data-telefon2="<?= $h($k['telefon2']) ?>"
                        data-adres="<?= $h($k['adres']) ?>"
                        data-notlar="<?= $h($k['notlar']) ?>">
                  <i class="fa-solid fa-pen"></i>
                </button>
                <a class="btn btn-sm btn-outline-danger" href="<?= BASE_URL ?>/fihrist/sil/<?= (int)$k['id'] ?>" onclick="return confirm('Bu kart silinsin mi?')">
                  <i class="fa-solid fa-trash"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- EKLEME / DÜZENLEME FORMU GÖRÜNÜMÜ (Varsayılan olarak gizli) -->
<div id="view-form" style="display: none;">
  <form method="post" action="<?= BASE_URL ?>/fihrist/kaydet">
    <input type="hidden" name="id" id="fihristId" value="">
    <div class="mb-3 d-flex gap-2">
      <button type="submit" class="btn btn-green btn-sm px-3"><i class="fa-solid fa-check"></i> Kaydet</button>
      <button type="button" class="btn btn-orange btn-sm px-3" id="btnGeri"><i class="fa-solid fa-reply"></i> Geri Dön</button>
    </div>

    <div class="card border-0 shadow-sm mt-2">
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

      <div class="card-body p-4" style="background-color: var(--card-bg);">
        <div class="tab-content" id="fihristTabContent">

          <!-- KART BİLGİLERİ -->
          <div class="tab-pane fade show active" id="kart-pane" role="tabpanel">
            <div class="row">
              <div class="col-lg-8">
                <div class="row mb-3">
                  <div class="col-md-3 form-label-right">İsim / Unvan *</div>
                  <div class="col-md-9">
                    <div class="input-group input-group-sm input-group-custom">
                      <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                      <input type="text" name="unvan" id="f_unvan" class="form-control form-control-custom" required>
                    </div>
                  </div>
                </div>

                <div class="row mb-3">
                  <div class="col-md-3 form-label-right">Sınıflandırma 1</div>
                  <div class="col-md-9">
                    <select name="siniflandirma_1" id="f_siniflandirma1" class="form-select form-select-custom form-select-sm">
                      <option value="">Seçiniz</option>
                      <?php foreach ($siniflar1 as $s): ?>
                        <option value="<?= $h($s['ad']) ?>"><?= $h($s['ad']) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <div class="text-end">
                      <a href="<?= BASE_URL ?>/tanim" class="add-link">+ yeni sınıflandırma ekle</a>
                    </div>
                  </div>
                </div>

                <div class="row mb-3">
                  <div class="col-md-3 form-label-right">Sınıflandırma 2</div>
                  <div class="col-md-9">
                    <select name="siniflandirma_2" id="f_siniflandirma2" class="form-select form-select-custom form-select-sm">
                      <option value="">Seçiniz</option>
                      <?php foreach ($siniflar2 as $s): ?>
                        <option value="<?= $h($s['ad']) ?>"><?= $h($s['ad']) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <div class="text-end">
                      <a href="<?= BASE_URL ?>/tanim" class="add-link">+ yeni sınıflandırma ekle</a>
                    </div>
                  </div>
                </div>

                <div class="row mb-3">
                  <div class="col-md-3 form-label-right">Yetkili Kişi</div>
                  <div class="col-md-9">
                    <div class="input-group input-group-sm input-group-custom">
                      <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                      <input type="text" name="yetkili" id="f_yetkili" class="form-control form-control-custom">
                    </div>
                  </div>
                </div>

                <div class="row mb-3">
                  <div class="col-md-3 form-label-right">E-Posta</div>
                  <div class="col-md-9">
                    <div class="input-group input-group-sm input-group-custom">
                      <span class="input-group-text"><i class="fa-regular fa-envelope"></i></span>
                      <input type="email" name="eposta" id="f_eposta" class="form-control form-control-custom">
                    </div>
                  </div>
                </div>

                <div class="row mb-3">
                  <div class="col-md-3 form-label-right">Telefonu</div>
                  <div class="col-md-9">
                    <div class="input-group input-group-sm input-group-custom mb-2">
                      <span class="input-group-text"><i class="fa-solid fa-phone"></i></span>
                      <input type="text" name="telefon1" id="f_telefon1" class="form-control form-control-custom">
                    </div>
                    <div class="input-group input-group-sm input-group-custom">
                      <span class="input-group-text"><i class="fa-solid fa-phone"></i></span>
                      <input type="text" name="telefon2" id="f_telefon2" class="form-control form-control-custom">
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- DİĞER BİLGİLER -->
          <div class="tab-pane fade" id="diger-pane" role="tabpanel">
            <div class="row mb-3">
              <div class="col-md-2 form-label-right">Adres</div>
              <div class="col-md-8">
                <textarea name="adres" id="f_adres" class="form-control form-control-custom" rows="3"></textarea>
              </div>
            </div>
            <div class="row mb-3">
              <div class="col-md-2 form-label-right">Not</div>
              <div class="col-md-8">
                <textarea name="notlar" id="f_notlar" class="form-control form-control-custom" rows="3"></textarea>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </form>
</div>

<script>
(function () {
  'use strict';

  const viewList = document.getElementById('view-list');
  const viewForm = document.getElementById('view-form');
  const btnYeniKart = document.getElementById('btnYeniKart');
  const btnGeri = document.getElementById('btnGeri');
  const formFields = ['unvan', 'siniflandirma1', 'siniflandirma2', 'yetkili', 'eposta', 'telefon1', 'telefon2', 'adres', 'notlar'];

  function resetForm() {
    document.getElementById('fihristId').value = '';
    formFields.forEach(f => {
      const el = document.getElementById('f_' + f);
      if (el) el.value = '';
    });
  }

  function showForm() {
    viewList.style.display = 'none';
    viewForm.style.display = 'block';
  }

  btnYeniKart.addEventListener('click', () => {
    resetForm();
    showForm();
  });

  btnGeri.addEventListener('click', () => {
    viewForm.style.display = 'none';
    viewList.style.display = 'block';
  });

  document.querySelectorAll('.btn-edit-kart').forEach(btn => {
    btn.addEventListener('click', () => {
      document.getElementById('fihristId').value = btn.dataset.id || '';
      document.getElementById('f_unvan').value = btn.dataset.unvan || '';
      document.getElementById('f_siniflandirma1').value = btn.dataset.siniflandirma1 || '';
      document.getElementById('f_siniflandirma2').value = btn.dataset.siniflandirma2 || '';
      document.getElementById('f_yetkili').value = btn.dataset.yetkili || '';
      document.getElementById('f_eposta').value = btn.dataset.eposta || '';
      document.getElementById('f_telefon1').value = btn.dataset.telefon1 || '';
      document.getElementById('f_telefon2').value = btn.dataset.telefon2 || '';
      document.getElementById('f_adres').value = btn.dataset.adres || '';
      document.getElementById('f_notlar').value = btn.dataset.notlar || '';
      showForm();
    });
  });
})();
</script>
