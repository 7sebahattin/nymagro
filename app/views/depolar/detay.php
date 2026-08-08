<?php
/**
 * View: depolar/detay.php
 */
$fmt = fn($n) => number_format((float)$n, 2, ',', '.');
?>
<style>
  .page-container { padding: 20px; font-family: 'Segoe UI', system-ui, sans-serif; background: var(--surface-2); min-height: 100vh; }
  
  /* Header */
  .depo-name-header { background: #337ab7; color: #fff; padding: 10px 15px; border-radius: 4px; font-size: 14px; font-weight: 600; margin-bottom: 20px; text-transform: uppercase; }
  
  /* Value Card */
  .value-card { background: #f07f75; color: #fff; width: 300px; border-radius: 8px; padding: 15px; display: flex; align-items: center; gap: 15px; margin-bottom: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
  .value-icon { font-size: 32px; background: rgba(255,255,255,0.2); width: 60px; height: 60px; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
  .value-body { flex: 1; }
  .value-title { font-size: 13px; font-weight: 600; opacity: 0.9; margin-bottom: 4px; }
  .value-amount { font-size: 18px; font-weight: 700; }

  /* Buttons */
  .btn-row { display: flex; gap: 10px; margin-bottom: 25px; }
  .btn-d { border: none; padding: 8px 16px; border-radius: 4px; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; color: #fff; }
  .btn-cyan { background: #5bc0de; }
  .btn-green { background: #5cb85c; }
  .btn-cyan:hover, .btn-green:hover { filter: brightness(0.9); }

  /* Table Section */
  .stok-section { background: var(--card-bg); border: 1px solid var(--border); border-radius: 4px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
  .section-header { background: #3a4a5a; color: #fff; padding: 12px 15px; font-size: 13px; font-weight: 700; display: flex; justify-content: space-between; align-items: center; }
  
  .filter-row { padding: 10px 15px; background: var(--card-bg); border-bottom: 1px solid var(--border); display: flex; justify-content: flex-end; align-items: center; gap: 10px; }
  .filter-row label { font-size: 12px; color: var(--muted); }
  .filter-input { border: 1px solid var(--border); border-radius: 3px; padding: 4px 8px; font-size: 13px; outline: none; }

  .stok-table { width: 100%; border-collapse: collapse; }
  .stok-table th { border-bottom: 2px solid var(--border); padding: 10px 15px; text-align: left; font-size: 12px; font-weight: 600; color: var(--text2); }
  .stok-table td { padding: 12px 15px; border-bottom: 1px solid var(--border); font-size: 13px; color: var(--text); }
  .stok-table tr:hover { background: var(--surface-2); }
  .urun-link { color: #5bc0de; text-decoration: none; font-weight: 600; }
  .urun-link:hover { text-decoration: underline; }

  /* Modal Overlay */
  .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; display: none; align-items: center; justify-content: center; }
  .modal-overlay.open { display: flex; }
  .modal-box { background: var(--card-bg); border-radius: 6px; width: 450px; max-width: 90%; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
  .modal-header { background: var(--surface-2); padding: 15px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; border-radius: 6px 6px 0 0; }
  .modal-title { font-weight: 700; font-size: 15px; color: var(--text); }
  .modal-close { cursor: pointer; border: none; background: none; font-size: 20px; color: var(--muted); }
  .modal-body { padding: 20px; }
  .modal-footer { padding: 15px; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
  
  .fg { margin-bottom: 15px; }
  .flabel { display: block; font-size: 12px; font-weight: 600; color: var(--text2); margin-bottom: 5px; }
  .finput { width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 4px; font-size: 13px; outline: none; }
  .btn-submit { background: #337ab7; color: #fff; border: none; padding: 8px 20px; border-radius: 4px; cursor: pointer; font-weight: 600; }
  .btn-delete { background: #d9534f; color: #fff; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 12px; text-decoration: none; }
</style>

<div class="page-container">
  <?php if (!empty($flash)): ?>
    <div style="padding:10px; background:rgba(46,204,113,.15); border:1px solid rgba(46,204,113,.28); color:var(--success); border-radius:4px; margin-bottom:20px; font-size:13px;">
      <?= htmlspecialchars($flash['mesaj']) ?>
    </div>
  <?php endif; ?>

  <div class="depo-name-header">
    <?= mb_strtoupper(htmlspecialchars($depo['ad'])) ?>
  </div>

  <div class="value-card">
    <div class="value-icon"><i class="fa-solid fa-money-bill-1"></i></div>
    <div class="value-body">
      <div class="value-title">Toplam Stok Değeri</div>
      <div class="value-amount">TL <?= $fmt($toplamDeger) ?></div>
    </div>
  </div>

  <div class="btn-row">
    <button class="btn-d btn-cyan" onclick="openModal('editModal')">
      <i class="fa-solid fa-pen"></i> Güncelle
    </button>
    <a href="<?= BASE_URL ?>/depo/sayim/<?= $depo['id'] ?>" class="btn-d btn-green">
      <i class="fa-solid fa-calculator"></i> Stok Sayımı Yap
    </a>
  </div>

  <div class="stok-section">
    <div class="section-header">
      DEPODAKİ ÜRÜNLER
      <i class="fa-solid fa-chevron-down" style="cursor:pointer; font-size:12px;"></i>
    </div>
    
    <div class="filter-row">
      <label>Bul:</label>
      <input type="text" id="tableFilter" class="filter-input" placeholder="filtre" onkeyup="filterTable()">
    </div>

    <table class="stok-table" id="productTable">
      <thead>
        <tr>
          <th>Kod</th>
          <th>Marka</th>
          <th>Ürün</th>
          <th>Miktar</th>
          <th>Değeri (TL)</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($stoklar)): ?>
          <tr><td colspan="5" style="text-align:center; padding:30px; color:var(--muted);">Bu depoda ürün bulunmamaktadır.</td></tr>
        <?php else: ?>
          <?php foreach ($stoklar as $s): ?>
            <tr>
              <td style="color:var(--muted); font-size:11px;"><?= htmlspecialchars($s['stok_kodu'] ?: '-') ?></td>
              <td style="color:var(--muted); font-size:12px;"><?= htmlspecialchars($s['marka'] ?: '-') ?></td>
              <td>
                <a href="<?= BASE_URL ?>/urun/detay/<?= $s['urun_id'] ?>" class="urun-link">
                  <?= htmlspecialchars($s['urun_adi']) ?>
                </a>
              </td>
              <td><?= number_format($s['miktar'], 0) ?> <?= htmlspecialchars($s['birim']) ?></td>
              <td style="text-align:right; font-weight:600; color:var(--text);">
                <?= $fmt((float)$s['miktar'] * (float)$s['alis_fiyati']) ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Güncelle -->
<div class="modal-overlay" id="editModal">
  <div class="modal-box">
    <div class="modal-header">
      <div class="modal-title">Depo Bilgilerini Güncelle</div>
      <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
    </div>
    <form action="<?= BASE_URL ?>/depo/guncelle/<?= $depo['id'] ?>" method="POST">
      <div class="modal-body">
        <div class="fg">
          <label class="flabel">Depo Adı</label>
          <input type="text" name="ad" class="finput" value="<?= htmlspecialchars($depo['ad']) ?>" required>
        </div>
        <div class="fg">
          <label class="flabel">Yetkili</label>
          <input type="text" name="yetkili" class="finput" value="<?= htmlspecialchars($depo['yetkili'] ?? '') ?>">
        </div>
        <div class="fg">
          <label class="flabel">Telefon</label>
          <input type="text" name="telefon" class="finput" value="<?= htmlspecialchars($depo['telefon'] ?? '') ?>">
        </div>
        <div class="fg">
          <label class="flabel">Adres</label>
          <textarea name="adres" class="finput" style="height:80px;"><?= htmlspecialchars($depo['adres'] ?? '') ?></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <?php if ($depo['id'] != 1 && $toplamDeger <= 0): ?>
          <a href="<?= BASE_URL ?>/depo/sil/<?= $depo['id'] ?>" class="btn-delete" onclick="return confirm('Bu depoyu silmek istediğinize emin misiniz?')">Depoyu Sil</a>
        <?php else: ?>
           <span style="font-size:11px; color:var(--muted);">
             <?php if ($depo['id'] == 1): ?>Ana depo silinemez.<?php else: ?>İçinde stok olan depo silinemez.<?php endif; ?>
           </span>
        <?php endif; ?>
        <button type="submit" class="btn-submit">Kaydet</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function filterTable() {
  const input = document.getElementById("tableFilter");
  const filter = input.value.toUpperCase();
  const table = document.getElementById("productTable");
  const tr = table.getElementsByTagName("tr");

  for (let i = 1; i < tr.length; i++) {
    const td = tr[i].getElementsByTagName("td")[2]; // Ürün sütunu
    if (td) {
      const txtValue = td.textContent || td.innerText;
      if (txtValue.toUpperCase().indexOf(filter) > -1) {
        tr[i].style.display = "";
      } else {
        tr[i].style.display = "none";
      }
    }
  }
}
</script>
