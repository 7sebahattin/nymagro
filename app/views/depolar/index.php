<?php
/**
 * View: depolar/index.php
 */
?>
<style>
  .page-container { padding: 20px; font-family: 'Segoe UI', sans-serif; }
  
  /* Top Bar */
  .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
  .btn-new { background: #22c55e; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
  .btn-new:hover { background: #16a34a; }

  /* Grid */
  .depo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
  
  /* Card */
  .depo-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 20px; border: 1px solid #e2e8f0; position: relative; transition: transform 0.2s; }
  .depo-card:hover { transform: translateY(-3px); box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
  
  .depo-icon { width: 45px; height: 45px; background: #eff6ff; color: #3b82f6; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 15px; }
  .depo-name { font-size: 16px; font-weight: 700; color: #1e293b; margin-bottom: 5px; display: block; text-decoration: none; }
  .depo-name:hover { color: #3b82f6; }
  
  .depo-info { font-size: 13px; color: #64748b; margin-bottom: 4px; display: flex; align-items: center; gap: 6px; }
  .depo-info i { width: 14px; text-align: center; font-size: 12px; }
  
  .depo-actions { margin-top: 15px; padding-top: 15px; border-top: 1px solid #f1f5f9; display: flex; gap: 10px; }
  .btn-action { font-size: 12px; font-weight: 600; text-decoration: none; padding: 6px 12px; border-radius: 6px; }
  .btn-view { background: #f1f5f9; color: #475569; }
  .btn-view:hover { background: #e2e8f0; }
  .btn-delete { background: #fef2f2; color: #ef4444; }
  .btn-delete:hover { background: #fee2e2; }

  /* Modal */
  .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; display: none; align-items: center; justify-content: center; }
  .modal-overlay.open { display: flex; }
  .modal-box { background: #fff; border-radius: 12px; width: 450px; max-width: 90%; box-shadow: 0 10px 25px rgba(0,0,0,0.2); overflow: hidden; }
  .modal-header { background: #f8fafc; padding: 15px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
  .modal-title { font-weight: 700; color: #1e293b; font-size: 15px; }
  .modal-close { cursor: pointer; border: none; background: none; font-size: 20px; color: #94a3b8; }
  .modal-body { padding: 20px; }
  .modal-footer { padding: 15px 20px; background: #f8fafc; border-top: 1px solid #e2e8f0; text-align: right; }
  
  .fg { margin-bottom: 15px; }
  .flabel { display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 5px; }
  .finput { width: 100%; padding: 10px 12px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 13.5px; outline: none; }
  .finput:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
  
  .btn-submit { background: #3b82f6; color: #fff; border: none; padding: 9px 20px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; }
  .btn-submit:hover { background: #2563eb; }
</style>

<div class="page-container">
  <?php if (!empty($flash)): ?>
    <div style="padding:12px; border-radius:8px; margin-bottom:20px; font-size:13px; background:<?= $flash['tip'] === 'success' ? '#dcfce7' : '#fee2e2' ?>; color:<?= $flash['tip'] === 'success' ? '#166534' : '#991b1b' ?>; border:1px solid <?= $flash['tip'] === 'success' ? '#bbf7d0' : '#fecaca' ?>;">
      <?= htmlspecialchars($flash['mesaj']) ?>
    </div>
  <?php endif; ?>

  <div class="top-bar">
    <div style="font-size: 13px; color: #64748b;">Tanımlı depolarınızı yönetebilir ve stok durumlarını inceleyebilirsiniz.</div>
    <button class="btn-new" onclick="openModal('depoModal')">
      <i class="fa-solid fa-plus"></i> Yeni Depo Ekle
    </button>
  </div>

  <div class="depo-grid">
    <?php foreach ($depolar as $d): ?>
      <div class="depo-card">
        <div class="depo-icon">
          <i class="fa-solid fa-warehouse"></i>
        </div>
        <a href="<?= BASE_URL ?>/depo/detay/<?= $d['id'] ?>" class="depo-name"><?= htmlspecialchars($d['ad']) ?></a>
        
        <div class="depo-info">
          <i class="fa-solid fa-user"></i> <?= htmlspecialchars($d['yetkili'] ?: 'Yetkili belirtilmemiş') ?>
        </div>
        <div class="depo-info">
          <i class="fa-solid fa-phone"></i> <?= htmlspecialchars($d['telefon'] ?: '-') ?>
        </div>
        <div class="depo-info">
          <i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($d['adres'] ?: 'Adres belirtilmemiş') ?>
        </div>
        
        <div class="depo-actions">
          <a href="<?= BASE_URL ?>/depo/detay/<?= $d['id'] ?>" class="btn-action btn-view">Stok Durumu</a>
          <?php if ($d['id'] != 1): ?>
            <a href="<?= BASE_URL ?>/depo/sil/<?= $d['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Bu depoyu silmek istediğinize emin misiniz?')">Sil</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Modal -->
<div class="modal-overlay" id="depoModal">
  <div class="modal-box">
    <div class="modal-header">
      <div class="modal-title">Yeni Depo Ekle</div>
      <button class="modal-close" onclick="closeModal('depoModal')">&times;</button>
    </div>
    <form action="<?= BASE_URL ?>/depo/kaydet" method="POST">
      <div class="modal-body">
        <div class="fg">
          <label class="flabel">Depo Adı *</label>
          <input type="text" name="ad" class="finput" required placeholder="Örn: Şube Depo, Araç Stok...">
        </div>
        <div class="fg">
          <label class="flabel">Yetkili</label>
          <input type="text" name="yetkili" class="finput" placeholder="Ad Soyad">
        </div>
        <div class="fg">
          <label class="flabel">Telefon</label>
          <input type="text" name="telefon" class="finput" placeholder="05XX XXX XX XX">
        </div>
        <div class="fg">
          <label class="flabel">Adres</label>
          <textarea name="adres" class="finput" style="height: 80px;" placeholder="Depo açık adresi"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-action btn-view" onclick="closeModal('depoModal')" style="border:none; cursor:pointer;">Vazgeç</button>
        <button type="submit" class="btn-submit">Depoyu Oluştur</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
</script>
