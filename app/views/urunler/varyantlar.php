<?php
/**
 * View: urunler/varyantlar.php
 * Beklenen değişkenler:
 *   $varyantlar array
 *   $flash array
 */
?>
<style>
  /* Base & Layout */
  *, *::before, *::after { box-sizing: border-box; }
  .page-container { font-family: 'Segoe UI', system-ui, sans-serif; padding: 20px; }
  
  /* Flash Messages */
  .alert { padding: 12px 18px; border-radius: 4px; margin-bottom: 20px; font-size: 13px; display: flex; align-items: center; gap: 8px; }
  .alert-success { background: #dcfce7; border: 1px solid #bbf7d0; color: #166534; }
  .alert-error { background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; }

  /* Top Actions */
  .top-actions { margin-bottom: 30px; }
  .btn-add-variant { 
    background: #5cb85c; 
    color: #fff; 
    border: none; 
    padding: 8px 16px; 
    border-radius: 3px; 
    font-size: 13px; 
    font-weight: 600; 
    cursor: pointer; 
    display: inline-flex; 
    align-items: center; 
    gap: 6px; 
  }
  .btn-add-variant:hover { background: #4cae4c; }

  /* Grid Layout for Variant Cards */
  .variants-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
  }
  @media (max-width: 900px) {
    .variants-grid { grid-template-columns: 1fr; }
  }

  /* Variant Card */
  .v-card {
    background: transparent;
    border-radius: 4px;
    overflow: hidden;
  }
  .v-card-header {
    background: #254b6b; /* Dark blue from screenshot */
    color: #fff;
    padding: 12px 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-radius: 4px 4px 0 0;
  }
  .v-card-title {
    font-size: 14px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  .btn-add-val {
    background: #f0ad4e;
    color: #fff;
    border: none;
    padding: 4px 10px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
  }
  .btn-add-val:hover { background: #ec971f; }
  
  .v-card-body {
    background: #e8eaec; /* Light grey from screenshot */
    padding: 20px;
    border: 1px solid #ddd;
    border-top: none;
    border-radius: 0 0 4px 4px;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    min-height: 100px;
  }
  
  /* Variant Value Pill */
  .v-val-pill {
    background: #f8f9fa;
    border: 1px solid #ccc;
    color: #555;
    padding: 8px 16px;
    border-radius: 4px;
    font-size: 13px;
    font-weight: 600;
    display: inline-block;
  }

  /* Modals */
  .modal-overlay { 
    position: fixed; 
    top: 0; left: 0; right: 0; bottom: 0; 
    background: rgba(0,0,0,0.5); 
    z-index: 1000; 
    display: none; 
    align-items: center; 
    justify-content: center; 
  }
  .modal-overlay.open { display: flex; }
  .modal-box { 
    background: #fff; 
    border-radius: 4px; 
    width: 500px; 
    max-width: 90%; 
    box-shadow: 0 5px 15px rgba(0,0,0,0.3); 
    overflow: hidden; 
  }
  .modal-header { 
    background: #62d6a5; /* Mint green from screenshot */
    color: #fff;
    padding: 15px 20px; 
    font-weight: 600; 
    font-size: 16px; 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
  }
  .modal-close { 
    cursor: pointer; 
    border: none; 
    background: none; 
    font-size: 18px; 
    color: #fff; 
    font-weight: bold;
  }
  .modal-body { padding: 30px 20px; }
  .modal-footer { 
    padding: 15px 20px; 
    border-top: 1px solid #eee; 
    text-align: right; 
  }
  
  .form-group { display: flex; align-items: flex-start; gap: 15px; margin-bottom: 5px; }
  .form-group label { width: 100px; font-size: 14px; color: #555; margin-top: 8px; }
  .form-input-wrap { flex: 1; }
  .form-control { width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 3px; font-size: 14px; outline: none; }
  .form-control:focus { border-color: #62d6a5; }
  .form-hint { font-size: 11px; color: #999; margin-top: 5px; display: block; }
  
  .btn-submit { 
    background: #5cb85c; 
    color: #fff; 
    border: none; 
    padding: 8px 16px; 
    border-radius: 3px; 
    font-size: 14px; 
    font-weight: 600; 
    cursor: pointer; 
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
  .btn-submit:hover { background: #4cae4c; }

</style>

<div class="page-container">

  <?php if (!empty($flash)): ?>
    <div class="alert alert-<?= $flash['tip'] === 'success' ? 'success' : 'error' ?>">
      <i class="fa-solid fa-<?= $flash['tip'] === 'success' ? 'check-circle' : 'circle-exclamation' ?>"></i>
      <?= htmlspecialchars($flash['mesaj']) ?>
    </div>
  <?php endif; ?>

  <div class="top-actions">
    <button type="button" class="btn-add-variant" onclick="openModal('modalVaryant')">
      <i class="fa-solid fa-check"></i> Yeni Varyant Ekle
    </button>
  </div>

  <div class="variants-grid">
    <?php if (empty($varyantlar)): ?>
      <div style="color: #999; font-size: 14px;">Henüz hiç varyant eklenmemiş.</div>
    <?php endif; ?>
    
    <?php foreach ($varyantlar as $v): ?>
      <div class="v-card">
        <div class="v-card-header">
          <div class="v-card-title"><?= htmlspecialchars($v['ad']) ?></div>
          <button type="button" class="btn-add-val" onclick="openValModal(<?= $v['id'] ?>, '<?= htmlspecialchars($v['ad'], ENT_QUOTES) ?>')">
            Varyant Değeri Ekle +
          </button>
        </div>
        <div class="v-card-body">
          <?php if (empty($v['degerler'])): ?>
            <span style="color:#aaa; font-size:13px; font-style:italic;">Değer yok</span>
          <?php endif; ?>
          <?php foreach ($v['degerler'] as $d): ?>
            <div class="v-val-pill"><?= htmlspecialchars($d['deger']) ?></div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

</div>

<!-- Modal: Yeni Varyant Ekle -->
<div class="modal-overlay" id="modalVaryant">
  <div class="modal-box">
    <div class="modal-header">
      Varyant Ekle
      <button type="button" class="modal-close" onclick="closeModal('modalVaryant')">&times;</button>
    </div>
    <form action="<?= BASE_URL ?>/urun/varyantEkle" method="POST">
      <div class="modal-body">
        <div class="form-group">
          <label>Varyant İsmi</label>
          <div class="form-input-wrap">
            <input type="text" name="varyant_ismi" class="form-control" required autocomplete="off">
            <span class="form-hint">Örneğin: "Renk", "Ebat", "Beden" vs...</span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn-submit">
          <i class="fa-solid fa-check"></i> Kaydet
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Varyant Değeri Ekle -->
<div class="modal-overlay" id="modalVaryantDeger">
  <div class="modal-box">
    <div class="modal-header">
      <span id="mvTitle">Varyant Değeri Ekle</span>
      <button type="button" class="modal-close" onclick="closeModal('modalVaryantDeger')">&times;</button>
    </div>
    <form action="" id="formVaryantDeger" method="POST">
      <div class="modal-body">
        <div class="form-group">
          <label>Varyant Değeri</label>
          <div class="form-input-wrap">
            <input type="text" name="deger" class="form-control" required autocomplete="off">
            <span class="form-hint">Örneğin: "10 L", "Kırmızı", "XL" vs...</span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn-submit">
          <i class="fa-solid fa-check"></i> Kaydet
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(id) {
  document.getElementById(id).classList.add('open');
}

function closeModal(id) {
  document.getElementById(id).classList.remove('open');
}

function openValModal(id, ad) {
  document.getElementById('mvTitle').innerText = ad + ' Değeri Ekle';
  document.getElementById('formVaryantDeger').action = '<?= BASE_URL ?>/urun/varyantDegeriEkle/' + id;
  openModal('modalVaryantDeger');
}
</script>
