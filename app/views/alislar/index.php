<?php
/**
 * View: alislar/index.php
 * BizimHesap Tasarımı Korunarak Dinamikleştirildi
 */
$belgeTipi = $belgeTipi ?? 'alis';
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
    .filter-panel { background: var(--card-bg); border: 1px solid var(--border); border-radius: 8px 8px 0 0; border-bottom: none; }
    .filter-row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; padding: 10px 14px; }
    .filter-row + .filter-row { border-top: 1px solid var(--border); }

    /* Dönem dropdown */
    .period-wrap { position: relative; }
    .btn-period {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 6px 14px; background: #334155; color: var(--text);
      border: none; border-radius: 6px; font-size: 13px; font-weight: 500;
      cursor: pointer;
    }
    .period-dropdown {
      position: absolute; top: calc(100% + 3px); left: 0; z-index: 300;
      background: var(--ink); border: 1px solid var(--border2); border-radius: 6px;
      box-shadow: 0 6px 20px rgba(0,0,0,.1); min-width: 160px;
      display: none; padding: 4px 0;
    }
    .period-dropdown.open { display: block; }
    .period-item { padding: 7px 16px; font-size: 13px; color: var(--text2); cursor: pointer; }
    .period-item:hover { background: var(--surface-2); }

    /* İptalleri toggle */
    .iptalli-wrap { display: flex; align-items: center; gap: 7px; margin-left: auto; font-size: 13px; color: var(--text2); font-weight: 500; }
    .toggle-switch { position: relative; width: 38px; height: 21px; }
    .toggle-switch input { opacity: 0; width: 0; height: 0; }
    .toggle-slider { position: absolute; inset: 0; background: #cbd5e1; border-radius: 21px; cursor: pointer; transition: background .2s; }
    .toggle-slider::before { content: ''; position: absolute; width: 15px; height: 15px; left: 3px; top: 3px; background: var(--card-bg); border-radius: 50%; transition: .2s; box-shadow: 0 1px 3px rgba(0,0,0,.2); }
    .toggle-switch input:checked + .toggle-slider { background: #22c55e; }
    .toggle-switch input:checked + .toggle-slider::before { transform: translateX(17px); }

    /* Arama */
    .search-label { font-size: 13px; color: var(--text2); font-weight: 600; margin-left: auto; }
    .search-type-select { padding: 6px 10px; border: 1px solid var(--border2); border-radius: 6px; font-size: 13px; color: var(--text2); background: var(--card-bg); outline: none; }
    .search-txt { padding: 6px 12px; border: 1px solid var(--border2); border-radius: 6px; font-size: 13px; color: var(--text2); background: var(--card-bg); outline: none; width: 260px; }

    /* ── TABLE ── */
    .table-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 0 0 8px 8px; overflow: hidden; }
    .sales-table { width: 100%; border-collapse: collapse; }
    .sales-table thead tr { background: #1e293b; }
    .sales-table thead th { padding: 11px 14px; font-size: 12.5px; font-weight: 600; color: var(--muted); text-align: left; }
    .sales-table tbody tr.data-row { border-bottom: 1px solid var(--border); cursor: pointer; }
    .sales-table tbody tr.data-row:hover { background: var(--surface-2); }
    .sales-table tbody td { padding: 9px 14px; font-size: 13px; color: var(--text2); }

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
    .detail-row { display: none; background: var(--surface-2); }
    .detail-row.open { display: table-row; }
    .detail-inner { padding: 10px 14px 14px 54px; }
    .btn-det { padding: 5px 13px; border: none; border-radius: 5px; font-size: 12.5px; font-weight: 600; cursor: pointer; color: #fff; margin-right: 5px; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }

    /* Modal */
    .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.55); z-index: 1000; display: none; align-items: center; justify-content: center; }
    .modal-overlay.open { display: flex; }
    .modal-box { background: var(--ink); border: 1px solid var(--border2); border-radius: 6px; width: 450px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
    .modal-head { background: #6ad0a5; color: #fff; padding: 14px 18px; font-size: 15px; font-weight: 700; display: flex; justify-content: space-between; }
    .modal-body { padding: 20px; display: flex; flex-direction: column; gap: 12px; }
    .modal-foot { padding: 12px 20px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 8px; }
</style>

<?php if (!empty($flash)): ?>
<div style="padding:12px 16px; margin-bottom:14px; border-radius:7px; font-size:13px;
     background:<?= $flash['tip'] === 'success' ? 'rgba(46,204,113,.15)' : 'rgba(231,76,60,.15)' ?>;
     color:<?= $flash['tip'] === 'success' ? 'var(--success)' : 'var(--danger)' ?>;
     border:1px solid <?= $flash['tip'] === 'success' ? '#bbf7d0' : 'rgba(231,76,60,.28)' ?>;">
  <i class="fa-solid fa-<?= $flash['tip'] === 'success' ? 'check-circle' : 'circle-exclamation' ?>"></i>
  <?= htmlspecialchars($flash['mesaj']) ?>
</div>
<?php endif; ?>

<!-- Belge Tipi Sekmeleri: "Yeni Alış Faturası" ekranındaki Fatura/İrsaliye Kaydet
     butonlarıyla kaydedilen belgeler burada ayrı sekmelerde gözlemlenebilir. -->
<div style="display:flex; gap:6px; margin-bottom:14px; border-bottom:1px solid var(--border);">
  <a href="<?= BASE_URL ?>/alis" style="padding:9px 16px; font-size:13px; font-weight:600; text-decoration:none; border-bottom:2px solid <?= $belgeTipi === 'alis' ? '#5bc0de' : 'transparent' ?>; color:<?= $belgeTipi === 'alis' ? 'var(--text)' : 'var(--muted)' ?>;">
    <i class="fa-solid fa-file-invoice-dollar"></i> Faturalar
  </a>
  <a href="<?= BASE_URL ?>/alis?tip=irsaliye" style="padding:9px 16px; font-size:13px; font-weight:600; text-decoration:none; border-bottom:2px solid <?= $belgeTipi === 'irsaliye' ? '#5bc0de' : 'transparent' ?>; color:<?= $belgeTipi === 'irsaliye' ? 'var(--text)' : 'var(--muted)' ?>;">
    <i class="fa-solid fa-truck"></i> İrsaliyeler
  </a>
</div>

<!-- Action Buttons — İrsaliyeler sekmesinde önceden hiçbir "ekle" butonu yoktu; kullanıcı
     o belgeyi yalnızca Faturalar sekmesindeki "Yeni Alış Faturası" ekranından "İrsaliye
     Kaydet" butonuna basarak oluşturabiliyordu (bkz. satislar/index.php'deki aynı düzeltme). -->
<?php if (Rbac::currentUserCan('ALIS_CREATE')): ?>
<div class="action-btns">
  <?php if ($belgeTipi === 'alis'): ?>
    <button class="btn-action btn-kayitli" onclick="document.getElementById('modalKayitli').classList.add('open')">
      <i class="fa-solid fa-plus"></i> Kayıtlı Tedarikçiden Alış Gir
    </button>
    <button class="btn-action btn-yeni" onclick="document.getElementById('modalYeni').classList.add('open')">
      <i class="fa-solid fa-plus"></i> Yeni Tedarikçiden Alış Gir
    </button>
  <?php elseif ($belgeTipi === 'irsaliye'): ?>
    <a href="<?= BASE_URL ?>/alis/ekle" class="btn-action btn-yeni">
      <i class="fa-solid fa-plus"></i> Yeni İrsaliye Ekle
    </a>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- Filter Panel -->
<div class="filter-panel">
  <div class="filter-row">
    <div class="period-wrap">
      <button class="btn-period" onclick="toggleDrop('durumDrop')">
        <?php
          $durumLabels = ['' => 'Tüm Durumlar', 'taslak' => 'Taslak', 'onaylandi' => 'Onaylandı', 'odendi' => 'Ödendi', 'kismi_odendi' => 'Kısmi Ödendi', 'iptal' => 'İptal'];
          echo $durumLabels[$durum ?? ''] ?? 'Tüm Durumlar';
        ?>
        <i class="fa-solid fa-chevron-down"></i>
      </button>
      <div class="period-dropdown" id="durumDrop">
        <?php foreach ($durumLabels as $val => $label): ?>
          <div class="period-item" onclick="goFilter('durum','<?= $val ?>')"><?= $label ?></div>
        <?php endforeach; ?>
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
        <th><?= $belgeTipi === 'irsaliye' ? 'İrsaliye No' : 'Belge No' ?></th>
        <th style="text-align:right;">Tutar</th>
        <th>Durumu</th>
      </tr>
    </thead>
    <tbody>
      <?php if(empty($faturalar)): ?>
        <tr><td colspan="6" style="text-align:center; padding:40px;"><?= $belgeTipi === 'irsaliye' ? 'Bu dönemde irsaliye yok.' : 'Kayıt bulunamadı.' ?></td></tr>
      <?php else: ?>
        <?php foreach($faturalar as $f): ?>
          <tr class="data-row" onclick="toggleRow(<?= $f['id'] ?>)">
            <td class="td-toggle">
              <span class="row-toggle plus" id="toggle-btn-<?= $f['id'] ?>">+</span>
            </td>
            <td><?= date('d.m.Y', strtotime($f['fatura_tarihi'])) ?></td>
            <td>
              <?php if (!empty($f['cari_id'])): ?>
                <a href="<?= BASE_URL ?>/tedarikci/detay/<?= (int)$f['cari_id'] ?>" onclick="event.stopPropagation()" style="color:var(--info); text-decoration:none; font-weight:500;"><?= htmlspecialchars($f['cari_unvan'] ?? 'Belirtilmedi') ?></a>
              <?php else: ?>
                <span><?= htmlspecialchars($f['cari_unvan'] ?? 'Belirtilmedi') ?></span>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($f['fatura_no']) ?></td>
            <td style="text-align:right; font-weight:600;"><?= number_format($f['genel_toplam'], 2, ',', '.') ?> TL</td>
            <td>
              <span class="status-badge status-<?= $f['durum'] ?>"><?= $f['durum'] === 'onaylandi' ? 'Onaylandı' : ($f['durum'] === 'iptal' ? 'İptal' : 'Taslak') ?></span>
              <?php if ($belgeTipi === 'irsaliye'): ?>
                <?php if ((int)($f['irsaliye_kullanildi'] ?? 0) === 1): ?>
                  <span class="status-badge" style="background:#16a34a;color:#fff;" title="Bu irsaliye bir alış faturasına dönüştürüldü.">Faturalandı</span>
                <?php else: ?>
                  <span class="status-badge" style="background:#94a3b8;color:#fff;">Faturalandırılmadı</span>
                <?php endif; ?>
              <?php endif; ?>
            </td>
          </tr>
          <tr class="detail-row" id="detail-<?= $f['id'] ?>">
            <td colspan="6">
              <div class="detail-inner">
                <div style="margin-bottom:10px;">
                  <a href="<?= BASE_URL ?>/alis/detay/<?= $f['id'] ?>" class="btn-det" style="background:#5bc0de;" onclick="event.stopPropagation()"><i class="fa-solid fa-eye"></i> Detaya Git</a>
                  <a href="<?= BASE_URL ?>/alis/detay/<?= $f['id'] ?>?print=1" class="btn-det" style="background:#efa341;" onclick="event.stopPropagation()"><i class="fa-solid fa-print"></i> Yazdır</a>
                  <?php if ($belgeTipi === 'alis' && !empty($f['cari_id']) && (float)($f['kalan_tutar'] ?? 0) > 0.004 && Rbac::currentUserCan('NAKIT_CREATE')): ?>
                    <button type="button" class="btn-det" style="background:#5cb85c;"
                            onclick="event.stopPropagation(); odemeEkleAc(<?= (int)$f['cari_id'] ?>, '<?= htmlspecialchars(addslashes($f['cari_unvan'] ?? '')) ?>', <?= (float)$f['kalan_tutar'] ?>, '<?= htmlspecialchars(addslashes($f['fatura_no'] ?? '')) ?>')">
                      <i class="fa-solid fa-turkish-lira-sign"></i> Ödeme Ekle
                    </button>
                  <?php endif; ?>
                  <?php if ($belgeTipi === 'irsaliye' && $f['durum'] !== 'iptal' && (int)($f['irsaliye_kullanildi'] ?? 0) === 0 && Rbac::currentUserCan('ALIS_CREATE')): ?>
                    <a href="<?= BASE_URL ?>/alis/ekle?kaynak_irsaliye_id=<?= $f['id'] ?>" class="btn-det" style="background:#16a34a;" onclick="event.stopPropagation()"><i class="fa-solid fa-file-invoice-dollar"></i> Faturalandır</a>
                  <?php endif; ?>
                  <?php if ($f['durum'] !== 'iptal' && Rbac::currentUserCan('ALIS_UPDATE')): ?>
                    <a href="#" class="btn-det" style="background:#ef4444;" onclick="event.stopPropagation(); return nymPost('<?= BASE_URL ?>/alis/iptal/<?= $f['id'] ?>', '<?= $belgeTipi === 'irsaliye' ? 'İrsaliye' : 'Fatura' ?> iptal edilsin mi?')"><i class="fa-solid fa-ban"></i> İptal Et</a>
                  <?php endif; ?>
                </div>
                <div style="font-size:12px; color:var(--muted);">
                  Kullanıcı: <strong><?= htmlspecialchars($f['olusturan_adi'] ?? 'Sistem') ?></strong>
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
    <div style="background: rgba(243,156,18,.13); padding: 12px 20px; font-size: 13px; color: var(--warning);">
      Alış yaptığınız tedarikçiyi bulun
    </div>
    <div style="background: var(--card-bg); padding: 20px;">
      <input type="text" id="kmSearch" style="width:100%; height:40px; font-size:14px; padding:8px 12px; border:2px solid #66afe9; border-radius:4px; outline:none; box-sizing:border-box;" placeholder="Aramak için yazın...">
      <div id="kmResults" style="max-height:250px; overflow-y:auto; border:1px solid var(--border); border-top:none; background:var(--card-bg);"></div>
    </div>
    <div style="background: var(--surface-2); padding: 10px 20px; border-top: 1px solid var(--border); text-align: right;">
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
    <div class="modal-body" style="background: var(--card-bg); padding: 20px;">
      <div style="background: rgba(243,156,18,.13); padding: 10px; border-radius: 4px; font-size: 13px; color: var(--warning); margin-bottom: 15px;">
        Önceden tedarikçi kaydı olmayan carilerinizi hızlıca buradan ekleyebilirsiniz. Bu ekranda yer almayan diğer detaylı bilgileri daha sonra tedarikçiler sayfasından güncelleyebilirsiniz.
      </div>

      <div class="fg-modal" style="margin-bottom: 15px;">
        <label style="display:block; font-size:13px; font-weight:700; margin-bottom:5px;">İsim / Unvan</label>
        <input type="text" id="ym_unvan" class="fi" style="width:100%; border:1px solid var(--border);">
      </div>

      <div style="display:flex; gap:15px; margin-bottom:15px;">
        <div style="flex:1;">
          <label style="display:block; font-size:13px; font-weight:700; margin-bottom:5px;">Telefon</label>
          <input type="text" id="ym_telefon" class="fi" placeholder="(___) ___-____" style="width:100%; border:1px solid var(--border);">
        </div>
        <div style="flex:1;">
          <label style="display:block; font-size:13px; font-weight:700; margin-bottom:5px;">Para Birimi</label>
          <select id="ym_para_birimi" class="fi" style="width:100%; border:1px solid var(--border);">
            <option value="TRY">TL</option>
            <option value="USD">USD</option>
            <option value="EUR">EUR</option>
          </select>
        </div>
      </div>

      <div style="display:flex; gap:15px; margin-bottom:15px;">
        <div style="flex:1;">
          <label style="display:block; font-size:13px; font-weight:700; margin-bottom:5px;">Vergi Dairesi</label>
          <input type="text" id="ym_vergi_dairesi" class="fi" style="width:100%; border:1px solid var(--border);">
        </div>
        <div style="flex:1;">
          <label style="display:block; font-size:13px; font-weight:700; margin-bottom:5px;">Vergi / TC Kimlik No</label>
          <input type="text" id="ym_vergi_no" class="fi" style="width:100%; border:1px solid var(--border);">
        </div>
      </div>

      <div class="fg-modal" style="margin-bottom: 15px;">
        <label style="display:block; font-size:13px; font-weight:700; margin-bottom:5px;">E-Posta</label>
        <input type="text" id="ym_eposta" class="fi" style="width:100%; border:1px solid var(--border);">
        <small style="font-size:11px; color:var(--muted);">virgül ile ayırarak birden fazla adres girebilirsiniz.</small>
      </div>

      <div class="fg-modal">
        <label style="display:block; font-size:13px; font-weight:700; margin-bottom:5px;">Adres</label>
        <textarea id="ym_adres" class="fi" style="width:100%; border:1px solid var(--border); min-height:60px;"></textarea>
      </div>
    </div>
    <div class="modal-foot" style="background: var(--card-bg); padding: 15px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 10px;">
      <button onclick="closeModals()" style="background: #f0ad4e; color: #fff; border: none; padding: 8px 20px; border-radius: 4px; font-weight: 700; cursor: pointer;">X Vazgeç</button>
      <button onclick="saveAndGo()" style="background: #5cb85c; color: #fff; border: none; padding: 8px 20px; border-radius: 4px; font-weight: 700; cursor: pointer;">✓ Devam Et</button>
    </div>
  </div>
</div>

<script>
const BASE = '<?= BASE_URL ?>';
const currentParams = new URLSearchParams(window.location.search);

function toggleDrop(id) {
  document.querySelectorAll('.period-dropdown').forEach(d => {
    if (d.id !== id) d.classList.remove('open');
  });
  document.getElementById(id).classList.toggle('open');
}
document.addEventListener('click', e => {
  if (!e.target.closest('.period-wrap')) {
    document.querySelectorAll('.period-dropdown').forEach(d => d.classList.remove('open'));
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

// Modal açıldığında tedarikçileri yükle. Bu buton yalnızca Faturalar sekmesinde
// render edilir (bkz. yukarıdaki action-btns bloğu) — İrsaliyeler sekmesinde yoktur,
// bu yüzden null kontrolü şart.
var btnKayitli = document.querySelector('.btn-kayitli');
if (btnKayitli) {
  btnKayitli.addEventListener('click', function() {
    document.getElementById('kmResults').innerHTML = '<div style="padding:12px; color:var(--muted);">Yükleniyor...</div>';
    document.getElementById('kmSearch').value = '';
    loadTedarikci('');
  });
}

function loadTedarikci(q) {
  var url = '<?= BASE_URL ?>/tedarikci/tedarikciBul?q=' + (q === '' ? 'all' : encodeURIComponent(q));
  fetch(url)
    .then(function(r) { return r.json(); })
    .then(function(data) {
      var res = document.getElementById('kmResults');
      if (!data || data.length === 0) {
        res.innerHTML = '<div style="padding:12px; color:var(--muted);">Sonuç bulunamadı.</div>';
        return;
      }
      var html = '';
      for (var i = 0; i < data.length; i++) {
        html += '<div style="padding:10px 15px; border-bottom:1px solid var(--border); cursor:pointer; font-size:14px;" onmouseover="this.style.background=\'#337ab7\';this.style.color=\'#fff\'" onmouseout="this.style.background=\'#fff\';this.style.color=\'#333\'" onclick="window.location.href=\'<?= BASE_URL ?>/alis/ekle?cari_id=' + data[i].id + '\'">' + data[i].unvan + '</div>';
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

/* ── Ödeme Ekle (fatura satırından hızlı ödeme) ── */
function odemeEkleAc(cariId, cariUnvan, kalanTutar, faturaNo) {
  document.getElementById('oeCariId').value = cariId;
  document.getElementById('oeBaslik').textContent = 'Ödeme — ' + cariUnvan + ' (' + faturaNo + ')';
  document.getElementById('oeTutar').value = kalanTutar.toFixed(2).replace('.', ',');
  document.getElementById('oeAciklama').value = 'Fatura ' + faturaNo + ' ödemesi';
  document.getElementById('odemeEkleModal').classList.add('open');
}

function odemeKaydet() {
  const fd = new FormData();
  fd.append('cari_id', document.getElementById('oeCariId').value);
  fd.append('islem_tipi', 'cikis');
  fd.append('kasa_id', document.getElementById('oeKasaId').value);
  fd.append('odeme_yontemi', document.getElementById('oeOdemeYontemi').value);
  fd.append('tutar', document.getElementById('oeTutar').value.replace(',', '.'));
  fd.append('tarih', document.getElementById('oeTarih').value);
  fd.append('saat', document.getElementById('oeSaat').value);
  fd.append('aciklama', document.getElementById('oeAciklama').value);

  if (!fd.get('kasa_id')) { alert('Kasa/Hesap seçiniz.'); return; }
  if (!fd.get('odeme_yontemi')) { alert('Ödeme yöntemi seçiniz.'); return; }
  if (!fd.get('tutar') || parseFloat(fd.get('tutar')) <= 0) { alert('Geçerli bir tutar giriniz.'); return; }

  fetch('<?= BASE_URL ?>/nakit/kaydet', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (res.status === 'success') {
        location.reload();
      } else {
        alert('Hata: ' + (res.message || 'İşlem kaydedilemedi.'));
      }
    })
    .catch(() => alert('İşlem kaydedilemedi!'));
}
</script>

<!-- Ödeme Ekle Modal (fatura satırından hızlı ödeme) -->
<div id="odemeEkleModal" class="modal-overlay">
  <div class="modal-box" style="width:450px;">
    <div class="modal-head" style="background:#5cb85c;">
      <span id="oeBaslik">Ödeme</span>
      <span onclick="closeModals()" style="cursor:pointer; font-size:24px;">X</span>
    </div>
    <div class="modal-body" style="background:var(--card-bg);">
      <input type="hidden" id="oeCariId">
      <div>
        <label style="display:block; font-size:13px; font-weight:700; margin-bottom:5px;">Kasa / Hesap</label>
        <select id="oeKasaId" class="fi" style="width:100%; border:1px solid var(--border);">
          <option value="">Seçiniz</option>
          <?php foreach (($kasaHesaplar ?? []) as $kh): ?>
            <option value="<?= (int)$kh['id'] ?>"><?= htmlspecialchars($kh['hesap_adi'] . ' (' . $kh['para_birimi'] . ')') ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label style="display:block; font-size:13px; font-weight:700; margin-bottom:5px;">Ödeme Yöntemi</label>
        <select id="oeOdemeYontemi" class="fi" style="width:100%; border:1px solid var(--border);">
          <option value="">Seçiniz</option>
          <?php foreach (['Nakit', 'Havale/EFT', 'Kredi Kartı', 'Çek', 'Senet', 'Virman'] as $oy): ?>
            <option value="<?= $oy ?>"><?= $oy ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="display:flex; gap:15px;">
        <div style="flex:1;">
          <label style="display:block; font-size:13px; font-weight:700; margin-bottom:5px;">Tarih</label>
          <input type="date" id="oeTarih" class="fi" value="<?= date('Y-m-d') ?>" style="width:100%; border:1px solid var(--border);">
        </div>
        <div style="flex:1;">
          <label style="display:block; font-size:13px; font-weight:700; margin-bottom:5px;">Saat</label>
          <input type="time" id="oeSaat" class="fi" value="<?= date('H:i') ?>" style="width:100%; border:1px solid var(--border);">
        </div>
      </div>
      <div>
        <label style="display:block; font-size:13px; font-weight:700; margin-bottom:5px;">Tutar</label>
        <input type="text" id="oeTutar" class="fi" placeholder="0,00" style="width:100%; border:1px solid var(--border);">
        <small style="font-size:11px; color:var(--muted);">Bu faturanın kalan tutarıyla önceden dolduruldu, değiştirebilirsiniz.</small>
      </div>
      <div>
        <label style="display:block; font-size:13px; font-weight:700; margin-bottom:5px;">Açıklama</label>
        <textarea id="oeAciklama" class="fi" style="width:100%; border:1px solid var(--border); min-height:50px;"></textarea>
      </div>
    </div>
    <div class="modal-foot">
      <button onclick="closeModals()" style="background:#f0ad4e; color:#fff; border:none; padding:8px 20px; border-radius:4px; font-weight:700; cursor:pointer;">Vazgeç</button>
      <button onclick="odemeKaydet()" style="background:#5cb85c; color:#fff; border:none; padding:8px 20px; border-radius:4px; font-weight:700; cursor:pointer;">Kaydet</button>
    </div>
  </div>
</div>
