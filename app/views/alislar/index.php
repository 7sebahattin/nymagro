<?php
/**
 * View: alislar/index.php
 * BizimHesap Tasarımı Korunarak Dinamikleştirildi
 */
?>
<style>
    /* ── ACTION BUTTONS ── */
    .action-btns { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
    .btn-action {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 7px 16px; border: none; border-radius: 5px;
      font-size: 13px; font-weight: 600; cursor: pointer;
      transition: filter .15s, box-shadow .15s; text-decoration: none; white-space: nowrap;
    }
    .btn-action:hover { filter: brightness(1.12); box-shadow: 0 3px 10px rgba(0,0,0,.18); color: #fff; }
    .btn-kayitli   { background: #c0392b; color: #fff; }
    .btn-yeni      { background: #5bc0de; color: #fff; }

    /* ── FILTER PANEL ── */
    .filter-panel { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px 8px 0 0; border-bottom: none; }
    .filter-row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; padding: 10px 14px; }
    .filter-row + .filter-row { border-top: 1px solid #f1f5f9; }

    /* Belge Tipi custom dropdown */
    .belge-tipi-wrap { position: relative; }
    .belge-tipi-btn {
      display: flex; align-items: center; justify-content: space-between; gap: 8px;
      padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 6px;
      background: #fff; font-size: 13px; color: #374151; cursor: pointer;
      min-width: 170px;
    }
    .belge-dropdown {
      position: absolute; top: calc(100% + 3px); left: 0; z-index: 300;
      background: #fff; border: 1px solid #cbd5e1; border-radius: 6px;
      box-shadow: 0 6px 20px rgba(0,0,0,.1); min-width: 210px;
      display: none; padding: 5px 0;
    }
    .belge-dropdown.open { display: block; }
    .belge-chk-item { display: flex; align-items: center; gap: 8px; padding: 7px 14px; font-size: 13px; color: #374151; cursor: pointer; }

    /* Dönem dropdown */
    .period-wrap { position: relative; }
    .btn-period {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 6px 14px; background: #334155; color: #e2e8f0;
      border: none; border-radius: 6px; font-size: 13px; font-weight: 500;
      cursor: pointer;
    }
    .period-dropdown {
      position: absolute; top: calc(100% + 3px); left: 0; z-index: 300;
      background: #fff; border: 1px solid #cbd5e1; border-radius: 6px;
      box-shadow: 0 6px 20px rgba(0,0,0,.1); min-width: 160px;
      display: none; padding: 4px 0;
    }
    .period-dropdown.open { display: block; }
    .period-item { padding: 7px 16px; font-size: 13px; color: #374151; cursor: pointer; }
    .period-item:hover { background: #f8fafc; }

    /* İptalleri toggle */
    .iptalli-wrap { display: flex; align-items: center; gap: 7px; margin-left: auto; font-size: 13px; color: #475569; font-weight: 500; }
    .toggle-switch { position: relative; width: 38px; height: 21px; }
    .toggle-switch input { opacity: 0; width: 0; height: 0; }
    .toggle-slider { position: absolute; inset: 0; background: #cbd5e1; border-radius: 21px; cursor: pointer; transition: background .2s; }
    .toggle-slider::before { content: ''; position: absolute; width: 15px; height: 15px; left: 3px; top: 3px; background: #fff; border-radius: 50%; transition: .2s; box-shadow: 0 1px 3px rgba(0,0,0,.2); }
    .toggle-switch input:checked + .toggle-slider { background: #22c55e; }
    .toggle-switch input:checked + .toggle-slider::before { transform: translateX(17px); }

    /* Arama */
    .search-label { font-size: 13px; color: #475569; font-weight: 600; margin-left: auto; }
    .search-type-select { padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; color: #374151; background: #fff; outline: none; }
    .search-txt { padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; color: #374151; background: #fff; outline: none; width: 260px; }

    /* ── TABLE ── */
    .table-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 0 0 8px 8px; overflow: hidden; }
    .sales-table { width: 100%; border-collapse: collapse; }
    .sales-table thead tr { background: #1e293b; }
    .sales-table thead th { padding: 11px 14px; font-size: 12.5px; font-weight: 600; color: #94a3b8; text-align: left; }
    .sales-table tbody tr.data-row { border-bottom: 1px solid #f1f5f9; cursor: pointer; }
    .sales-table tbody tr.data-row:hover { background: #f8fafc; }
    .sales-table tbody td { padding: 9px 14px; font-size: 13px; color: #374151; }

    .td-toggle { width: 40px; text-align: center; }
    .row-toggle {
      width: 20px; height: 20px; border-radius: 50%; border: 2px solid;
      display: inline-flex; align-items: center; justify-content: center;
      font-size: 14px; font-weight: 700; cursor: pointer;
    }
    .row-toggle.plus  { border-color: #22c55e; color: #22c55e; }
    .row-toggle.minus { border-color: #ef4444; color: #ef4444; }

    .status-badge { display: inline-block; padding: 3px 10px; border-radius: 4px; font-size: 12px; font-weight: 600; }
    .status-onaylandi { background: #22c55e; color: #fff; }
    .status-taslak    { background: #f59e0b; color: #fff; }

    /* Detail row */
    .detail-row { display: none; background: #f8fafc; }
    .detail-row.open { display: table-row; }
    .detail-inner { padding: 10px 14px 14px 54px; }
    .btn-det { padding: 5px 13px; border: none; border-radius: 5px; font-size: 12.5px; font-weight: 600; cursor: pointer; color: #fff; margin-right: 5px; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }

    /* Modal */
    .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.55); z-index: 1000; display: none; align-items: center; justify-content: center; }
    .modal-overlay.open { display: flex; }
    .modal-box { background: #fff; border-radius: 6px; width: 450px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
    .modal-head { background: #6ad0a5; color: #fff; padding: 14px 18px; font-size: 15px; font-weight: 700; display: flex; justify-content: space-between; }
    .modal-body { padding: 20px; display: flex; flex-direction: column; gap: 12px; }
    .modal-foot { padding: 12px 20px; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end; gap: 8px; }
</style>

<?php if (!empty($flash)): ?>
<div style="padding:12px 16px; margin-bottom:14px; border-radius:7px; font-size:13px;
     background:<?= $flash['tip'] === 'success' ? '#dcfce7' : '#fee2e2' ?>;
     color:<?= $flash['tip'] === 'success' ? '#166534' : '#991b1b' ?>;
     border:1px solid <?= $flash['tip'] === 'success' ? '#bbf7d0' : '#fecaca' ?>;">
  <i class="fa-solid fa-<?= $flash['tip'] === 'success' ? 'check-circle' : 'circle-exclamation' ?>"></i>
  <?= htmlspecialchars($flash['mesaj']) ?>
</div>
<?php endif; ?>

<!-- Action Buttons -->
<div class="action-btns">
  <button class="btn-action btn-kayitli" onclick="document.getElementById('modalKayitli').classList.add('open')">
    <i class="fa-solid fa-plus"></i> Kayıtlı Tedarikçiden Alış Gir
  </button>
  <button class="btn-action btn-yeni" onclick="document.getElementById('modalYeni').classList.add('open')">
    <i class="fa-solid fa-plus"></i> Yeni Tedarikçiden Alış Gir
  </button>
</div>

<!-- Filter Panel -->
<div class="filter-panel">
  <div class="filter-row">
    <div class="belge-tipi-wrap">
      <button class="belge-tipi-btn" onclick="toggleDrop('belgeDrop')">
        <span>Tüm Belge Tipleri</span>
        <i class="fa-solid fa-chevron-down"></i>
      </button>
      <div class="belge-dropdown" id="belgeDrop">
        <label class="belge-chk-item"><input type="checkbox" checked> Tümünü Seç</label>
        <label class="belge-chk-item"><input type="checkbox" checked> Faturalaşmış</label>
      </div>
    </div>

    <div class="period-wrap">
      <button class="btn-period" onclick="toggleDrop('periodDrop')">
        <?php
          $donemLabels = ['bugun'=>'Bugün','haftaici'=>'Son 7 Gün','1ay'=>'Son 1 Ay','bu_ay'=>'Bu Ay','bu_yil'=>'Bu Yıl','tumu'=>'Tümü'];
          echo $donemLabels[$donem ?? '1ay'] ?? 'Son 1 Ay';
        ?>
        <i class="fa-solid fa-chevron-down"></i>
      </button>
      <div class="period-dropdown" id="periodDrop">
        <?php foreach ($donemLabels as $val => $label): ?>
          <div class="period-item" onclick="goFilter('donem','<?= $val ?>')"><?= $label ?></div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="filter-row" style="justify-content:flex-end;">
    <span class="search-label">Ara:</span>
    <input type="text" id="araInput" class="search-txt" placeholder="tedarikçi adı veya belge no..." value="<?= htmlspecialchars($arama ?? '') ?>">
    <button onclick="doArama()" style="padding:6px 14px; background:#1e293b; color:#4ade80; border:none; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer;">
      <i class="fa-solid fa-search"></i>
    </button>
  </div>
</div>

<!-- Table -->
<div class="table-card">
  <table class="sales-table">
    <thead>
      <tr>
        <th style="width:40px;"></th>
        <th>Tarih</th>
        <th>İsim/Unvan</th>
        <th>Belge No</th>
        <th style="text-align:right;">Tutar</th>
        <th>Durumu</th>
      </tr>
    </thead>
    <tbody>
      <?php if(empty($faturalar)): ?>
        <tr><td colspan="6" style="text-align:center; padding:40px;">Kayıt bulunamadı.</td></tr>
      <?php else: ?>
        <?php foreach($faturalar as $f): ?>
          <tr class="data-row" onclick="toggleRow(<?= $f['id'] ?>)">
            <td class="td-toggle">
              <span class="row-toggle plus" id="toggle-btn-<?= $f['id'] ?>">+</span>
            </td>
            <td><?= date('d.m.Y', strtotime($f['fatura_tarihi'])) ?></td>
            <td><a href="#" style="color:#2563eb; text-decoration:none; font-weight:500;"><?= htmlspecialchars($f['cari_unvan'] ?? 'Belirtilmedi') ?></a></td>
            <td><?= htmlspecialchars($f['fatura_no']) ?></td>
            <td style="text-align:right; font-weight:600;"><?= number_format($f['genel_toplam'], 2, ',', '.') ?> TL</td>
            <td><span class="status-badge status-<?= $f['durum'] ?>"><?= $f['durum'] === 'onaylandi' ? 'Onaylandı' : 'Taslak' ?></span></td>
          </tr>
          <tr class="detail-row" id="detail-<?= $f['id'] ?>">
            <td colspan="6">
              <div class="detail-inner">
                <div style="margin-bottom:10px;">
                  <a href="<?= BASE_URL ?>/alis/detay/<?= $f['id'] ?>" class="btn-det" style="background:#5bc0de;"><i class="fa-solid fa-eye"></i> Detaya Git</a>
                  <a href="<?= BASE_URL ?>/alis/detay/<?= $f['id'] ?>?print=1" class="btn-det" style="background:#efa341;"><i class="fa-solid fa-print"></i> Yazdır</a>
                </div>
                <div style="font-size:12px; color:#64748b;">
                  Kullanıcı: <strong>Sistem</strong>
                </div>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Modal: Kayıtlı Tedarikçi Arama -->
<div id="modalKayitli" class="modal-overlay">
  <div class="modal-box" style="width: 550px; overflow: visible;">
    <div class="modal-head" style="background: #6ad0a5;">
      <span>Tedarikçi Arama</span>
      <span onclick="closeModals()" style="cursor:pointer; font-size: 24px;">X</span>
    </div>
    <div style="background: #fff8e1; padding: 12px 20px; font-size: 13px; color: #856404;">
      Alış yaptığınız tedarikçiyi bulun
    </div>
    <div style="background: #fff; padding: 20px;">
      <input type="text" id="kmSearch" style="width:100%; height:40px; font-size:14px; padding:8px 12px; border:2px solid #66afe9; border-radius:4px; outline:none; box-sizing:border-box;" placeholder="Aramak için yazın...">
      <div id="kmResults" style="max-height:250px; overflow-y:auto; border:1px solid #ddd; border-top:none; background:#fff;"></div>
    </div>
    <div style="background: #f9f9f9; padding: 10px 20px; border-top: 1px solid #eee; text-align: right;">
      <button onclick="closeModals()" style="background: #f0ad4e; color: #fff; border: none; padding: 6px 15px; border-radius: 4px; cursor: pointer; font-size: 12px;">Kapat</button>
    </div>
  </div>
</div>

<!-- Modal: Yeni Tedarikçi Ekle -->
<div id="modalYeni" class="modal-overlay">
  <div class="modal-box" style="width: 600px;">
    <div class="modal-head" style="background: #6ad0a5;">
      <span>Yeni Tedarikçi Ekle</span>
      <span onclick="closeModals()" style="cursor:pointer; font-size: 24px;">X</span>
    </div>
    <div class="modal-body" style="background: #fff; padding: 20px;">
      <div style="background: #fff8e1; padding: 10px; border-radius: 4px; font-size: 13px; color: #856404; margin-bottom: 15px;">
        Önceden tedarikçi kaydı olmayan carilerinizi hızlıca buradan ekleyebilirsiniz. Bu ekranda yer almayan diğer detaylı bilgileri daha sonra tedarikçiler sayfasından güncelleyebilirsiniz.
      </div>

      <div class="fg-modal" style="margin-bottom: 15px;">
        <label style="display:block; font-size:13px; font-weight:700; margin-bottom:5px;">İsim / Unvan</label>
        <input type="text" id="ym_unvan" class="fi" style="width:100%; border:1px solid #ddd;">
      </div>

      <div style="display:flex; gap:15px; margin-bottom:15px;">
        <div style="flex:1;">
          <label style="display:block; font-size:13px; font-weight:700; margin-bottom:5px;">Telefon</label>
          <input type="text" id="ym_telefon" class="fi" placeholder="(___) ___-____" style="width:100%; border:1px solid #ddd;">
        </div>
        <div style="flex:1;">
          <label style="display:block; font-size:13px; font-weight:700; margin-bottom:5px;">Para Birimi</label>
          <select id="ym_para_birimi" class="fi" style="width:100%; border:1px solid #ddd;">
            <option value="TRY">TL</option>
            <option value="USD">USD</option>
            <option value="EUR">EUR</option>
          </select>
        </div>
      </div>

      <div style="display:flex; gap:15px; margin-bottom:15px;">
        <div style="flex:1;">
          <label style="display:block; font-size:13px; font-weight:700; margin-bottom:5px;">Vergi Dairesi</label>
          <input type="text" id="ym_vergi_dairesi" class="fi" style="width:100%; border:1px solid #ddd;">
        </div>
        <div style="flex:1;">
          <label style="display:block; font-size:13px; font-weight:700; margin-bottom:5px;">Vergi / TC Kimlik No</label>
          <input type="text" id="ym_vergi_no" class="fi" style="width:100%; border:1px solid #ddd;">
        </div>
      </div>

      <div class="fg-modal" style="margin-bottom: 15px;">
        <label style="display:block; font-size:13px; font-weight:700; margin-bottom:5px;">E-Posta</label>
        <input type="text" id="ym_eposta" class="fi" style="width:100%; border:1px solid #ddd;">
        <small style="font-size:11px; color:#999;">virgül ile ayırarak birden fazla adres girebilirsiniz.</small>
      </div>

      <div class="fg-modal">
        <label style="display:block; font-size:13px; font-weight:700; margin-bottom:5px;">Adres</label>
        <textarea id="ym_adres" class="fi" style="width:100%; border:1px solid #ddd; min-height:60px;"></textarea>
      </div>
    </div>
    <div class="modal-foot" style="background: #fff; padding: 15px; border-top: 1px solid #eee; display: flex; justify-content: flex-end; gap: 10px;">
      <button onclick="closeModals()" style="background: #f0ad4e; color: #fff; border: none; padding: 8px 20px; border-radius: 4px; font-weight: 700; cursor: pointer;">X Vazgeç</button>
      <button onclick="saveAndGo()" style="background: #5cb85c; color: #fff; border: none; padding: 8px 20px; border-radius: 4px; font-weight: 700; cursor: pointer;">✓ Devam Et</button>
    </div>
  </div>
</div>

<script>
const BASE = '<?= BASE_URL ?>';
const currentParams = new URLSearchParams(window.location.search);

function toggleDrop(id) {
  document.querySelectorAll('.belge-dropdown, .period-dropdown').forEach(d => {
    if (d.id !== id) d.classList.remove('open');
  });
  document.getElementById(id).classList.toggle('open');
}
document.addEventListener('click', e => {
  if (!e.target.closest('.belge-tipi-wrap, .period-wrap')) {
    document.querySelectorAll('.belge-dropdown, .period-dropdown').forEach(d => d.classList.remove('open'));
  }
});

function goFilter(key, val) {
  currentParams.set(key, val);
  currentParams.delete('sayfa');
  window.location.href = BASE + '/alis?' + currentParams.toString();
}

function doArama() {
  const q = document.getElementById('araInput').value.trim();
  currentParams.set('ara', q);
  currentParams.delete('sayfa');
  window.location.href = BASE + '/alis?' + currentParams.toString();
}

document.getElementById('araInput').addEventListener('keydown', e => { if (e.key === 'Enter') doArama(); });

function closeModals() { document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('open')); }
function toggleRow(id) {
  const row = document.getElementById('detail-' + id);
  const btn = document.getElementById('toggle-btn-' + id);
  const isOpen = row.classList.contains('open');
  
  document.querySelectorAll('.detail-row').forEach(r => r.classList.remove('open'));
  document.querySelectorAll('.row-toggle').forEach(b => { b.textContent = '+'; b.className = 'row-toggle plus'; });
  
  if (!isOpen) {
    row.classList.add('open');
    btn.textContent = '−';
    btn.className = 'row-toggle minus';
  }
}

function saveAndGo() {
  const data = new FormData();
  data.append('unvan', document.getElementById('ym_unvan').value.trim());
  data.append('telefon', document.getElementById('ym_telefon').value.trim());
  data.append('para_birimi', document.getElementById('ym_para_birimi').value);
  data.append('vergi_dairesi', document.getElementById('ym_vergi_dairesi').value.trim());
  data.append('vergi_no', document.getElementById('ym_vergi_no').value.trim());
  data.append('eposta', document.getElementById('ym_eposta').value.trim());
  data.append('adres', document.getElementById('ym_adres').value.trim());

  if (!data.get('unvan')) return alert('Lütfen Tedarikçi İsmi giriniz.');

  fetch('<?= BASE_URL ?>/tedarikci/hizli_kaydet', {
    method: 'POST',
    body: data
  })
  .then(r => r.json())
  .then(res => {
    if (res.success) {
      window.location.href = '<?= BASE_URL ?>/alis/ekle?cari_id=' + res.id;
    } else {
      alert('Hata: ' + (res.message || 'Bir hata oluştu.'));
    }
  });
}

// Modal açıldığında tedarikçileri yükle
document.querySelector('.btn-kayitli').addEventListener('click', function() {
  document.getElementById('kmResults').innerHTML = '<div style="padding:12px; color:#888;">Yükleniyor...</div>';
  document.getElementById('kmSearch').value = '';
  loadTedarikci('');
});

function loadTedarikci(q) {
  var url = '<?= BASE_URL ?>/tedarikci/tedarikciBul?q=' + (q === '' ? 'all' : encodeURIComponent(q));
  fetch(url)
    .then(function(r) { return r.json(); })
    .then(function(data) {
      var res = document.getElementById('kmResults');
      if (!data || data.length === 0) {
        res.innerHTML = '<div style="padding:12px; color:#999;">Sonuç bulunamadı.</div>';
        return;
      }
      var html = '';
      for (var i = 0; i < data.length; i++) {
        html += '<div style="padding:10px 15px; border-bottom:1px solid #eee; cursor:pointer; font-size:14px;" onmouseover="this.style.background=\'#337ab7\';this.style.color=\'#fff\'" onmouseout="this.style.background=\'#fff\';this.style.color=\'#333\'" onclick="window.location.href=\'<?= BASE_URL ?>/alis/ekle?cari_id=' + data[i].id + '\'">' + data[i].unvan + '</div>';
      }
      res.innerHTML = html;
    })
    .catch(function(err) {
      document.getElementById('kmResults').innerHTML = '<div style="padding:12px; color:red;">Hata: ' + err.message + '</div>';
    });
}

// Tedarikçi Arama (Yazarken)
document.getElementById('kmSearch').addEventListener('input', function() {
  var q = this.value.trim();
  if (q.length < 2) {
    loadTedarikci('');
    return;
  }
  loadTedarikci(q);
});

window.onclick = function(e) {
  if (e.target.classList.contains('modal-overlay')) closeModals();
};
</script>
