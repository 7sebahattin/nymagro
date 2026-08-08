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
  .depo-card { background: var(--card-bg); border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 20px; border: 1px solid var(--border); position: relative; transition: transform 0.2s; }
  .depo-card:hover { transform: translateY(-3px); box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
  
  .depo-icon { width: 45px; height: 45px; background: rgba(59,130,246,.15); color: #3b82f6; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 15px; }
  .depo-name { font-size: 16px; font-weight: 700; color: var(--text); margin-bottom: 5px; display: block; text-decoration: none; }
  .depo-name:hover { color: #3b82f6; }
  
  .depo-info { font-size: 13px; color: var(--muted); margin-bottom: 4px; display: flex; align-items: center; gap: 6px; }
  .depo-info i { width: 14px; text-align: center; font-size: 12px; }
  
  .depo-actions { margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--border); display: flex; gap: 10px; }
  .btn-action { font-size: 12px; font-weight: 600; text-decoration: none; padding: 6px 12px; border-radius: 6px; }
  .btn-view { background: var(--surface-2); color: var(--text2); }
  .btn-view:hover { background: var(--surface-2); }
  .btn-delete { background: rgba(231,76,60,.15); color: #ef4444; }
  .btn-delete:hover { background: rgba(231,76,60,.15); }

  /* Modal */
  .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; display: none; align-items: center; justify-content: center; }
  .modal-overlay.open { display: flex; }
  .modal-box { background: var(--ink); border: 1px solid var(--border2); border-radius: 12px; width: 450px; max-width: 90%; box-shadow: 0 10px 25px rgba(0,0,0,0.2); overflow: hidden; }
  .modal-header { background: var(--surface-2); padding: 15px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
  .modal-title { font-weight: 700; color: var(--text); font-size: 15px; }
  .modal-close { cursor: pointer; border: none; background: none; font-size: 20px; color: var(--muted); }
  .modal-body { padding: 20px; }
  .modal-footer { padding: 15px 20px; background: var(--surface-2); border-top: 1px solid var(--border); text-align: right; }
  
  .fg { margin-bottom: 15px; }
  .flabel { display: block; font-size: 12px; font-weight: 600; color: var(--text2); margin-bottom: 5px; }
  .finput { width: 100%; padding: 10px 12px; border: 1.5px solid var(--border); border-radius: 8px; font-size: 13.5px; outline: none; }
  .finput:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
  
  .btn-submit { background: #3b82f6; color: #fff; border: none; padding: 9px 20px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; }
  .btn-submit:hover { background: #2563eb; }
</style>

<div class="page-container">
  <?php if (!empty($flash)): ?>
    <div style="padding:12px; border-radius:8px; margin-bottom:20px; font-size:13px; background:<?= $flash['tip'] === 'success' ? 'rgba(46,204,113,.15)' : 'rgba(231,76,60,.15)' ?>; color:<?= $flash['tip'] === 'success' ? 'var(--success)' : 'var(--danger)' ?>; border:1px solid <?= $flash['tip'] === 'success' ? '#bbf7d0' : 'rgba(231,76,60,.28)' ?>;">
      <?= htmlspecialchars($flash['mesaj']) ?>
    </div>
  <?php endif; ?>

  <div class="top-bar">
    <div style="font-size: 13px; color: var(--muted);">Tanımlı depolarınızı yönetebilir ve stok durumlarını inceleyebilirsiniz.</div>
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
