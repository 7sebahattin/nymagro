<?php
/**
 * View: depolar/sayim.php
 */
?>
<style>
  .page-container { padding: 20px; font-family: 'Segoe UI', system-ui, sans-serif; background: var(--surface-2); min-height: 100vh; }
  .sayim-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 4px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
  .sayim-header { background: #3a4a5a; color: #fff; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
  .sayim-title { font-size: 15px; font-weight: 700; }
  
  .sayim-table { width: 100%; border-collapse: collapse; }
  .sayim-table th { background: var(--surface-2); padding: 12px 15px; border-bottom: 2px solid var(--border); text-align: left; font-size: 12px; font-weight: 600; color: var(--text2); }
  .sayim-table td { padding: 10px 15px; border-bottom: 1px solid var(--border); font-size: 13px; vertical-align: middle; }
  
  .input-sayim { width: 100px; padding: 6px 10px; border: 1px solid var(--border); border-radius: 4px; outline: none; font-weight: 700; text-align: center; color: #337ab7; }
  .input-sayim:focus { border-color: #337ab7; box-shadow: 0 0 0 3px rgba(51,122,183,0.1); }
  
  .btn-save { background: #5cb85c; color: #fff; border: none; padding: 10px 25px; border-radius: 4px; font-size: 14px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
  .btn-save:hover { background: #4cae4c; }
  
  .info-box { background: rgba(59,130,246,.15); color: var(--info); border: 1px solid rgba(59,130,246,.28); padding: 15px; border-radius: 4px; margin-bottom: 20px; font-size: 13px; }
</style>

<div class="page-container">
  <div class="info-box">
    <i class="fa-solid fa-circle-info" style="margin-right:8px;"></i>
    <b>Nasıl Yapılır?</b> Depodaki fiziksel ürünleri sayın ve "Sayım Miktarı" sütununa yazın. 
    Kaydettiğinizde, sistemdeki miktar ile girdiğiniz miktar arasındaki fark otomatik olarak stok girişi veya çıkışı olarak işlenecektir.
  </div>

  <form action="<?= BASE_URL ?>/depo/sayimKaydet/<?= $depo['id'] ?>" method="POST">
    <div class="sayim-card">
      <div class="sayim-header">
        <div class="sayim-title"><?= htmlspecialchars($depo['ad']) ?> - Stok Sayımı</div>
        <button type="submit" class="btn-save">
          <i class="fa-solid fa-check"></i> Sayımı Kaydet ve Stokları Güncelle
        </button>
      </div>
      
      <table class="sayim-table">
        <thead>
          <tr>
            <th>Ürün Adı</th>
            <th>Stok Kodu</th>
            <th style="width:150px; text-align:center;">Sistemdeki Miktar</th>
            <th style="width:150px; text-align:center;">Sayım Miktarı</th>
            <th>Birim</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($stoklar)): ?>
            <tr><td colspan="5" style="text-align:center; padding:30px; color:var(--muted);">Bu depoda sayılacak ürün bulunamadı.</td></tr>
          <?php else: ?>
            <?php foreach ($stoklar as $s): ?>
              <tr>
                <td style="font-weight:600;"><?= htmlspecialchars($s['urun_adi']) ?></td>
                <td style="color:var(--muted); font-size:12px;"><?= htmlspecialchars($s['stok_kodu'] ?: '-') ?></td>
                <td style="text-align:center; color:var(--muted);">
                  <?= number_format($s['miktar'], 0) ?>
                </td>
                <td style="text-align:center;">
                  <input type="number" name="sayim[<?= $s['urun_id'] ?>]" class="input-sayim" value="<?= (int)$s['miktar'] ?>" min="0" step="1">
                </td>
                <td style="color:var(--muted);"><?= htmlspecialchars($s['birim']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
      
      <div style="padding:20px; text-align:right; background:var(--surface-2); border-top:1px solid var(--border);">
        <a href="<?= BASE_URL ?>/depo/detay/<?= $depo['id'] ?>" style="color:var(--muted); font-size:13px; margin-right:20px; text-decoration:none;">Vazgeç</a>
        <button type="submit" class="btn-save">
          <i class="fa-solid fa-check"></i> Sayımı Kaydet ve Stokları Güncelle
        </button>
      </div>
    </div>
  </form>
</div>
