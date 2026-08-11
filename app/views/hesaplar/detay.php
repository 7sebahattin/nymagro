<?php
/**
 * Hesap Detayı
 * $hesap       : kasa_banka kaydı
 * $hareketler  : kasa_hareketleri (DESC) — ham liste
 * $tumHesaplar : tüm hesaplar (transfer dropdown için)
 */

$hesapId   = (int)$hesap['id'];
$bakiye    = (float)$hesap['guncel_bakiye'];
$pb        = $hesap['para_birimi'] ?? 'TRY';
$tip       = $hesap['tip'] ?? 'kasa';

$fmt = fn(float $n, string $para = null) =>
    ($para ?? $pb) . ' ' . number_format(abs($n), 2, ',', '.');

// ── Kümülatif bakiye hesapla (ASC → DEF hesapla → tekrar reverse) ──
$hareketlerAsc = array_reverse($hareketler);
$runBal = (float)($hesap['acilis_bakiyesi'] ?? 0);
foreach ($hareketlerAsc as &$h) {
    if ($h['islem_tipi'] === 'giris') {
        $runBal += (float)$h['tutar'];
    } else {
        $runBal -= (float)$h['tutar'];
    }
    $h['_bakiye'] = $runBal;
}
unset($h);
$hareketlerDisplay = array_reverse($hareketlerAsc);

$tipLabel = [
    'kasa'        => 'Kasa',
    'banka'       => 'Banka',
    'pos'         => 'POS',
    'kredi_karti' => 'Kredi Kartı',
    'ortak'       => 'Ortak Hesabı',
    'veresiye'    => 'Veresiye',
];
?>
<style>
  :root { --navy:#2c3e6b; --teal:#17a2b8; }

  /* ── HERO ── */
  .blue-hero {
    background: linear-gradient(135deg, #337ab7, #2c5282);
    color: #fff; padding: 22px 26px; border-radius: 8px;
    margin-bottom: 20px; display: flex; align-items: center; gap: 20px;
    box-shadow: 0 3px 12px rgba(44,82,130,.3);
  }
  .tl-icon {
    font-size: 26px; font-weight: 700; width: 52px; height: 52px;
    background: rgba(255,255,255,.2); border-radius: 50%;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
  }
  .hero-info { flex: 1; }
  .hero-badge { font-size: 11px; background: rgba(255,255,255,.2); padding: 2px 8px; border-radius: 10px; margin-bottom: 6px; display: inline-block; }
  .hero-title { display: block; font-size: 18px; font-weight: 700; margin-bottom: 3px; }
  .hero-bal   { display: block; font-size: 15px; opacity: .9; }
  .hero-bal.negatif { color: #fca5a5; }

  /* ── ACTION ROW ── */
  .h-action-row { display: flex; gap: 8px; margin-bottom: 18px; flex-wrap: wrap; }
  .h-action-row button, .h-action-row .dropdown > button {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 15px; border: none; border-radius: 5px;
    font-size: 12.5px; font-weight: 600; cursor: pointer; transition: filter .15s;
  }
  .bt-guncelle  { background: #5cb85c; color: #fff; }
  .bt-paragir   { background: #337ab7; color: #fff; }
  .bt-paracik   { background: #d9534f; color: #fff; }
  .bt-transfer  { background: #f0ad4e; color: #fff; }
  .bt-sil       { background: #6c757d; color: #fff; }
  .h-action-row button:hover { filter: brightness(1.1); }

  /* ── TABLE CARD ── */
  .table-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 8px; overflow: hidden; }
  .table-header {
    background: var(--navy); color: #fff; padding: 11px 16px;
    font-size: 12.5px; font-weight: 700; letter-spacing: .4px;
    display: flex; align-items: center; justify-content: space-between;
  }
  .table-controls { padding: 8px 14px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 8px; }
  .table-controls label { font-size: 12.5px; color: var(--muted); margin: 0; }
  .table-controls input {
    padding: 5px 10px; border: 1px solid var(--border2); border-radius: 5px;
    font-size: 13px; outline: none; width: 220px;
  }
  .table-controls input:focus { border-color: var(--teal); }

  .hrk-table { width: 100%; border-collapse: collapse; }
  .hrk-table thead tr { background: var(--surface-2); }
  .hrk-table thead th {
    padding: 9px 12px; font-size: 12px; font-weight: 700; color: var(--text2);
    border-bottom: 2px solid var(--border); white-space: nowrap; text-align: left;
  }
  .hrk-table tbody tr { border-bottom: 1px solid var(--border); transition: background .1s; }
  .hrk-table tbody tr:hover { background: var(--surface-2); }
  .hrk-table tbody td { padding: 9px 12px; font-size: 13px; color: var(--text2); vertical-align: middle; }
  .txt-right { text-align: right !important; }
  .txt-green { color: #16a34a; font-weight: 600; }
  .txt-red   { color: var(--danger); font-weight: 600; }
  .txt-gray  { color: var(--muted); }

  .badge-tip {
    display: inline-block; padding: 2px 8px; border-radius: 4px;
    font-size: 11.5px; font-weight: 600; white-space: nowrap;
  }
  .badge-giris   { background: rgba(46,204,113,.15); color: #16a34a; }
  .badge-cikis   { background: rgba(231,76,60,.15); color: var(--danger); }
  .badge-transfer { background: rgba(243,156,18,.15); color: var(--warning); }

  .btn-islem {
    font-size: 12px; padding: 4px 10px; background: var(--surface-2);
    border: 1px solid var(--border); border-radius: 4px; cursor: pointer;
  }
  .btn-islem:hover { background: var(--surface-2); }

  .empty-state { text-align: center; padding: 48px 16px; color: var(--muted); font-size: 14px; }
  .empty-state i { font-size: 32px; display: block; margin-bottom: 10px; }

  /* ── MODALLER ── */
  .hmodal-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,.55);
    z-index: 900; display: none; align-items: center; justify-content: center;
  }
  .hmodal-overlay.open { display: flex; }
  .hmodal {
    background: var(--ink); border: 1px solid var(--border2); border-radius: 8px; width: 500px; max-width: 96vw;
    box-shadow: 0 8px 40px rgba(0,0,0,.25); overflow: hidden;
    display: flex; flex-direction: column;
  }
  .hmodal-header {
    background: var(--navy); color: #fff; padding: 13px 18px;
    font-size: 14px; font-weight: 700;
    display: flex; align-items: center; justify-content: space-between;
  }
  .hmodal-close { background: none; border: none; color: #fff; font-size: 20px; cursor: pointer; opacity: .8; }
  .hmodal-close:hover { opacity: 1; }
  .hmodal-body { padding: 18px 20px; display: flex; flex-direction: column; gap: 12px; }
  .hupd-row { display: grid; grid-template-columns: 130px 1fr; align-items: center; gap: 10px; }
  .hupd-row label { font-size: 12.5px; font-weight: 600; color: var(--text2); }
  .hupd-inp, .hupd-select {
    padding: 7px 10px; border: 1px solid var(--border2); border-radius: 5px;
    font-size: 13px; color: var(--text); outline: none; width: 100%; box-sizing: border-box;
  }
  .hupd-inp:focus, .hupd-select:focus { border-color: var(--teal); }
  .hmodal-footer {
    padding: 12px 20px; border-top: 1px solid var(--border);
    display: flex; justify-content: space-between; align-items: center; gap: 8px;
  }
  .hmodal-footer-r { display: flex; gap: 8px; }
  .btn-mdl {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 7px 16px; border: none; border-radius: 5px;
    font-size: 13px; font-weight: 600; cursor: pointer;
  }
  .btn-mdl:hover { filter: brightness(1.1); }
  .btn-mdl.danger  { background: #ef4444; color: #fff; }
  .btn-mdl.warning { background: #f59e0b; color: #fff; }
  .btn-mdl.success { background: #5cb85c; color: #fff; }
  .btn-mdl.blue    { background: #337ab7; color: #fff; }
  .btn-mdl.gray    { background: #64748b; color: #fff; }
  .btn-mdl.red     { background: #d9534f; color: #fff; }

  /* Transfer modal — hesap seç */
  .hupd-row.full { grid-template-columns: 1fr; }

  /* Toast */
  .toast-container { position: fixed; bottom: 22px; right: 22px; z-index: 9999; display: flex; flex-direction: column; gap: 7px; pointer-events: none; }
  .toast-msg { display: flex; align-items: center; gap: 9px; padding: 11px 16px; border-radius: 8px; font-size: 13px; font-weight: 500; box-shadow: 0 3px 14px rgba(0,0,0,.15); background: var(--ink); border: 1px solid var(--border2); pointer-events: auto; }
  .toast-msg.success { border-left: 4px solid #22c55e; }
  .toast-msg.error   { border-left: 4px solid #ef4444; }
  .toast-msg.info    { border-left: 4px solid #3b82f6; }

  /* sidebar */
  .profile-dropdown.show { display: flex !important; }
  .nav-link:focus { color: var(--muted); outline: none; }
  .nav-link.active:focus { color: #4ade80; outline: none; }
</style>

<!-- HERO -->
<div class="blue-hero">
  <div class="tl-icon"><?= $pb === 'TRY' ? '₺' : ($pb === 'USD' ? '$' : ($pb === 'EUR' ? '€' : $pb[0])) ?></div>
  <div class="hero-info">
    <span class="hero-badge"><?= htmlspecialchars($tipLabel[$tip] ?? strtoupper($tip)) ?></span>
    <span class="hero-title"><?= htmlspecialchars($hesap['hesap_adi']) ?></span>
    <span class="hero-bal <?= $bakiye < 0 ? 'negatif' : '' ?>">
      Bakiye: <?= $bakiye < 0 ? '-' : '' ?><?= $fmt($bakiye) ?>
    </span>
  </div>
  <a href="<?= BASE_URL ?>/hesap" style="color:rgba(255,255,255,.7); font-size:12px; text-decoration:none; margin-left:auto; align-self:flex-start;">
    <i class="fa-solid fa-arrow-left"></i> Geri
  </a>
</div>

<!-- AKSİYON BUTONLARI -->
<div class="h-action-row">
  <?php if (Rbac::currentUserCan('HESAP_UPDATE')): ?>
  <button class="bt-guncelle" onclick="openModal('mUpdate')">
    <i class="fa-solid fa-pen"></i> Güncelle
  </button>
  <?php endif; ?>
  <?php if (Rbac::currentUserCan('HESAP_CREATE')): ?>
  <button class="bt-paragir" onclick="openModal('mGiris')">
    <i class="fa-solid fa-arrow-down"></i> Para Girişi Yap
  </button>
  <button class="bt-paracik" onclick="openModal('mCikis')">
    <i class="fa-solid fa-arrow-up"></i> Para Çıkışı Yap
  </button>
  <div class="dropdown">
    <button class="bt-transfer dropdown-toggle" data-bs-toggle="dropdown">
      <i class="fa-solid fa-repeat"></i> Hesaplar Arası Transfer
    </button>
    <ul class="dropdown-menu shadow">
      <li><a class="dropdown-item" href="#" onclick="openModal('mTransfer');return false;">
        <i class="fa-solid fa-arrow-right-arrow-left"></i> Transfer Yap
      </a></li>
    </ul>
  </div>
  <?php endif; ?>
  <?php if (Rbac::currentUserCan('HESAP_DELETE')): ?>
  <button class="bt-sil" onclick="hesapSilOnayla()">
    <i class="fa-solid fa-trash"></i> Hesabı Sil
  </button>
  <?php endif; ?>
</div>

<!-- HAREKET TABLOSU -->
<div class="table-card">
  <div class="table-header">
    <span>HESAP HAREKETLERİ</span>
    <span style="font-size:11px; opacity:.7;"><?= count($hareketlerDisplay) ?> kayıt</span>
  </div>
  <div class="table-controls">
    <label>Ara:</label>
    <input type="text" id="araInput" placeholder="İşlem, açıklama..." oninput="filterTable(this.value)">
  </div>

  <div style="overflow-x:auto;">
    <table class="hrk-table" id="hrkTable">
      <thead>
        <tr>
          <th>Tarih</th>
          <th>İşlem Tipi</th>
          <th>Cari / Açıklama</th>
          <th class="txt-right">Giriş</th>
          <th class="txt-right">Çıkış</th>
          <th class="txt-right">Bakiye</th>
          <th style="width:80px;"></th>
        </tr>
      </thead>
      <tbody id="hrkBody">
        <?php if (empty($hareketlerDisplay)): ?>
        <tr>
          <td colspan="7">
            <div class="empty-state">
              <i class="fa-solid fa-inbox"></i>
              Henüz hareket kaydı bulunmuyor.
            </div>
          </td>
        </tr>
        <?php else: foreach ($hareketlerDisplay as $h):
          $isGiris  = $h['islem_tipi'] === 'giris';
          $tarih    = date('d.m.Y', strtotime($h['tarih']));
          $saat     = date('H:i', strtotime($h['tarih']));
          $tutar    = (float)$h['tutar'];
          $runBakiye = (float)$h['_bakiye'];
          $htip     = $h['hareket_tipi'] ?? $h['islem_tipi'];
          $badgeCls = match(true) {
            $htip === 'transfer'          => 'badge-transfer',
            $h['islem_tipi'] === 'giris'  => 'badge-giris',
            default                       => 'badge-cikis',
          };
          $htipLabel = match($htip) {
            'para_girisi' => 'Para Girişi',
            'para_cikisi' => 'Para Çıkışı',
            'transfer'    => 'Transfer',
            'tahsilat'    => 'Tahsilat',
            'odeme'       => 'Ödeme',
            'giris'       => 'Giriş',
            'cikis'       => 'Çıkış',
            default       => ucfirst($htip ?? $h['islem_tipi']),
          };
        ?>
        <tr data-id="<?= $h['id'] ?>">
          <td>
            <div><?= $tarih ?></div>
            <div style="font-size:11px; color:var(--muted);"><?= $saat ?></div>
          </td>
          <td><span class="badge-tip <?= $badgeCls ?>"><?= $htipLabel ?></span></td>
          <td>
            <?php if (!empty($h['cari_adi'])): ?>
              <div style="font-weight:500;"><?= htmlspecialchars($h['cari_adi']) ?></div>
            <?php endif; ?>
            <?php if (!empty($h['aciklama'])): ?>
              <div style="font-size:12px; color:var(--muted);"><?= htmlspecialchars($h['aciklama']) ?></div>
            <?php endif; ?>
          </td>
          <td class="txt-right txt-green">
            <?= $isGiris ? number_format($tutar, 2, ',', '.') : '' ?>
          </td>
          <td class="txt-right txt-red">
            <?= !$isGiris ? number_format($tutar, 2, ',', '.') : '' ?>
          </td>
          <td class="txt-right" style="font-weight:600; color:<?= $runBakiye < 0 ? '#dc2626' : '#1e293b' ?>;">
            <?= ($runBakiye < 0 ? '-' : '') . number_format(abs($runBakiye), 2, ',', '.') ?>
          </td>
          <td>
            <?php if (Rbac::currentUserCan('HESAP_DELETE')): ?>
            <div class="dropdown">
              <button class="btn-islem dropdown-toggle" data-bs-toggle="dropdown">İşlem</button>
              <ul class="dropdown-menu shadow">
                <li><a class="dropdown-item text-danger" href="#"
                       onclick="hareketSil(<?= $h['id'] ?>, this);return false;">
                  <i class="fa-solid fa-xmark"></i> Sil
                </a></li>
              </ul>
            </div>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ══ MODAL: GÜNCELLE ══════════════════════════════════ -->
<div class="hmodal-overlay" id="mUpdate">
  <div class="hmodal">
    <div class="hmodal-header">
      <span>Hesap Güncelleme</span>
      <button class="hmodal-close" onclick="closeModal('mUpdate')">&times;</button>
    </div>
    <div class="hmodal-body">
      <div class="hupd-row">
        <label>Hesap Adı</label>
        <input type="text" id="updHesapAdi" class="hupd-inp" value="<?= htmlspecialchars($hesap['hesap_adi']) ?>">
      </div>
      <?php if (!empty($hesap['banka_adi'])): ?>
      <div class="hupd-row">
        <label>Banka Adı</label>
        <input type="text" id="updBankaAdi" class="hupd-inp" value="<?= htmlspecialchars($hesap['banka_adi'] ?? '') ?>">
      </div>
      <?php endif; ?>
      <div class="hupd-row">
        <label>Para Birimi</label>
        <select id="updParaBirimi" class="hupd-select">
          <?php foreach (['TRY','USD','EUR','GBP'] as $p): ?>
          <option value="<?= $p ?>" <?= $hesap['para_birimi'] === $p ? 'selected' : '' ?>><?= $p ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if (!empty($hesap['hesap_no'])): ?>
      <div class="hupd-row">
        <label>Hesap No</label>
        <input type="text" id="updHesapNo" class="hupd-inp" value="<?= htmlspecialchars($hesap['hesap_no'] ?? '') ?>">
      </div>
      <?php endif; ?>
      <?php if (!empty($hesap['iban'])): ?>
      <div class="hupd-row">
        <label>IBAN</label>
        <input type="text" id="updIban" class="hupd-inp" value="<?= htmlspecialchars($hesap['iban'] ?? '') ?>">
      </div>
      <?php endif; ?>
      <div class="hupd-row">
        <label>Açıklama</label>
        <input type="text" id="updAciklama" class="hupd-inp" value="<?= htmlspecialchars($hesap['aciklama'] ?? '') ?>">
      </div>
    </div>
    <div class="hmodal-footer">
      <button class="btn-mdl gray" onclick="closeModal('mUpdate')">
        <i class="fa-solid fa-xmark"></i> Vazgeç
      </button>
      <div class="hmodal-footer-r">
        <button class="btn-mdl success" id="btnGuncelle" onclick="hesapGuncelle()">
          <i class="fa-solid fa-check"></i> Kaydet
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ══ MODAL: PARA GİRİŞİ ═══════════════════════════════ -->
<div class="hmodal-overlay" id="mGiris">
  <div class="hmodal">
    <div class="hmodal-header" style="background:#337ab7;">
      <span><i class="fa-solid fa-arrow-down"></i> Hesaba Para Girişi</span>
      <button class="hmodal-close" onclick="closeModal('mGiris')">&times;</button>
    </div>
    <div class="hmodal-body">
      <div class="hupd-row">
        <label>Tutar <span style="color:#ef4444;">*</span></label>
        <input type="text" id="giTutar" class="hupd-inp" placeholder="0,00" oninput="sayiFormat(this)">
      </div>
      <div class="hupd-row">
        <label>Tarih</label>
        <input type="date" id="giTarih" class="hupd-inp" value="<?= date('Y-m-d') ?>">
      </div>
      <div class="hupd-row">
        <label>Ödeme Yöntemi <span style="color:#ef4444;">*</span></label>
        <select id="giOdemeYontemi" class="hupd-inp">
          <option value="">Seçiniz</option>
          <?php foreach (['Nakit', 'Havale/EFT', 'Kredi Kartı', 'Çek', 'Senet', 'Virman'] as $oy): ?>
            <option value="<?= $oy ?>"><?= $oy ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="hupd-row">
        <label>Açıklama</label>
        <input type="text" id="giAciklama" class="hupd-inp" placeholder="Opsiyonel">
      </div>
    </div>
    <div class="hmodal-footer">
      <button class="btn-mdl gray" onclick="closeModal('mGiris')">Vazgeç</button>
      <button class="btn-mdl blue" id="btnGirisKaydet" onclick="hareketKaydet('giris')">
        <i class="fa-solid fa-check"></i> Kaydet
      </button>
    </div>
  </div>
</div>

<!-- ══ MODAL: PARA ÇIKIŞI ════════════════════════════════ -->
<div class="hmodal-overlay" id="mCikis">
  <div class="hmodal">
    <div class="hmodal-header" style="background:#d9534f;">
      <span><i class="fa-solid fa-arrow-up"></i> Hesaptan Para Çıkışı</span>
      <button class="hmodal-close" onclick="closeModal('mCikis')">&times;</button>
    </div>
    <div class="hmodal-body">
      <div class="hupd-row">
        <label>Tutar <span style="color:#ef4444;">*</span></label>
        <input type="text" id="ciTutar" class="hupd-inp" placeholder="0,00" oninput="sayiFormat(this)">
      </div>
      <div class="hupd-row">
        <label>Tarih</label>
        <input type="date" id="ciTarih" class="hupd-inp" value="<?= date('Y-m-d') ?>">
      </div>
      <div class="hupd-row">
        <label>Ödeme Yöntemi <span style="color:#ef4444;">*</span></label>
        <select id="ciOdemeYontemi" class="hupd-inp">
          <option value="">Seçiniz</option>
          <?php foreach (['Nakit', 'Havale/EFT', 'Kredi Kartı', 'Çek', 'Senet', 'Virman'] as $oy): ?>
            <option value="<?= $oy ?>"><?= $oy ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="hupd-row">
        <label>Açıklama</label>
        <input type="text" id="ciAciklama" class="hupd-inp" placeholder="Opsiyonel">
      </div>
    </div>
    <div class="hmodal-footer">
      <button class="btn-mdl gray" onclick="closeModal('mCikis')">Vazgeç</button>
      <button class="btn-mdl red" id="btnCikisKaydet" onclick="hareketKaydet('cikis')">
        <i class="fa-solid fa-check"></i> Kaydet
      </button>
    </div>
  </div>
</div>

<!-- ══ MODAL: TRANSFER ═══════════════════════════════════ -->
<div class="hmodal-overlay" id="mTransfer">
  <div class="hmodal">
    <div class="hmodal-header" style="background:#e67e22;">
      <span><i class="fa-solid fa-repeat"></i> Hesaplar Arası Transfer</span>
      <button class="hmodal-close" onclick="closeModal('mTransfer')">&times;</button>
    </div>
    <div class="hmodal-body">
      <div class="hupd-row">
        <label>Kaynak Hesap</label>
        <div style="padding:6px 10px; background:var(--surface-2); border-radius:5px; font-size:13px; font-weight:600;">
          <?= htmlspecialchars($hesap['hesap_adi']) ?> (mevcut)
        </div>
      </div>
      <div class="hupd-row">
        <label>Hedef Hesap <span style="color:#ef4444;">*</span></label>
        <select id="trToId" class="hupd-select">
          <option value="">— Seçiniz —</option>
          <?php foreach ($tumHesaplar as $h): ?>
            <?php if ($h['id'] == $hesapId) continue; ?>
            <option value="<?= $h['id'] ?>">
              <?= htmlspecialchars($h['hesap_adi']) ?> (<?= number_format($h['guncel_bakiye'], 2, ',', '.') ?> <?= $h['para_birimi'] ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="hupd-row">
        <label>Tutar <span style="color:#ef4444;">*</span></label>
        <input type="text" id="trTutar" class="hupd-inp" placeholder="0,00" oninput="sayiFormat(this)">
      </div>
      <div class="hupd-row">
        <label>Tarih</label>
        <input type="date" id="trTarih" class="hupd-inp" value="<?= date('Y-m-d') ?>">
      </div>
      <div class="hupd-row">
        <label>Açıklama</label>
        <input type="text" id="trAciklama" class="hupd-inp" placeholder="Opsiyonel">
      </div>
    </div>
    <div class="hmodal-footer">
      <button class="btn-mdl gray" onclick="closeModal('mTransfer')">Vazgeç</button>
      <button class="btn-mdl warning" id="btnTransfer" onclick="transferKaydet()">
        <i class="fa-solid fa-check"></i> Transfer Yap
      </button>
    </div>
  </div>
</div>

<!-- Toast -->
<div class="toast-container" id="toastContainer"></div>

<script>
const BASE     = '<?= BASE_URL ?>';
const HESAP_ID = <?= $hesapId ?>;

/* ── MODAL YARDIMCILAR ──────────────────────── */
function openModal(id)  { document.getElementById(id).classList.add('open');    }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.hmodal-overlay.open, .ym-overlay.open').forEach(el => el.classList.remove('open'));
  }
});

/* ── PARA FORMAT ────────────────────────────── */
function sayiFormat(inp) {
  let v = inp.value.replace(/[^\d,\.]/g, '').replace(',', '.');
  inp.value = v;
}

/* ── TABLO FİLTRE ───────────────────────────── */
function filterTable(q) {
  q = q.toLowerCase();
  document.querySelectorAll('#hrkBody tr').forEach(row => {
    const txt = row.textContent.toLowerCase();
    row.style.display = txt.includes(q) ? '' : 'none';
  });
}

/* ── HESAP GÜNCELLE ─────────────────────────── */
function hesapGuncelle() {
  const btn = document.getElementById('btnGuncelle');
  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

  const fd = new FormData();
  fd.append('id',          HESAP_ID);
  fd.append('hesap_adi',   document.getElementById('updHesapAdi')?.value.trim()    || '');
  fd.append('banka_adi',   document.getElementById('updBankaAdi')?.value.trim()    || '');
  fd.append('hesap_no',    document.getElementById('updHesapNo')?.value.trim()     || '');
  fd.append('iban',        document.getElementById('updIban')?.value.trim()        || '');
  fd.append('para_birimi', document.getElementById('updParaBirimi')?.value         || 'TRY');
  fd.append('aciklama',    document.getElementById('updAciklama')?.value.trim()    || '');

  fetch(BASE + '/hesap/hesapGuncelle', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        showToast(res.message, 'success');
        closeModal('mUpdate');
        setTimeout(() => location.reload(), 900);
      } else {
        showToast(res.message || 'Hata oluştu.', 'error');
      }
    })
    .catch(() => showToast('Bağlantı hatası.', 'error'))
    .finally(() => {
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-check"></i> Kaydet';
    });
}

/* ── HAREKET KAYDET (Giriş/Çıkış) ──────────── */
function hareketKaydet(tip) {
  const prefix = tip === 'giris' ? 'gi' : 'ci';
  const tutar  = document.getElementById(prefix + 'Tutar').value.replace(',', '.');
  const tarih  = document.getElementById(prefix + 'Tarih').value;
  const odemeYontemi = document.getElementById(prefix + 'OdemeYontemi').value;
  const acikl  = document.getElementById(prefix + 'Aciklama').value.trim();

  if (!tutar || parseFloat(tutar) <= 0) {
    showToast('Geçerli bir tutar giriniz.', 'error');
    return;
  }
  if (!odemeYontemi) {
    showToast('Ödeme yöntemi seçiniz.', 'error');
    return;
  }

  const btnId = tip === 'giris' ? 'btnGirisKaydet' : 'btnCikisKaydet';
  const btn   = document.getElementById(btnId);
  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

  const fd = new FormData();
  fd.append('kasa_id',    HESAP_ID);
  fd.append('islem_tipi', tip);
  fd.append('tutar',      tutar);
  fd.append('tarih',      tarih);
  fd.append('odeme_yontemi', odemeYontemi);
  fd.append('aciklama',   acikl);

  fetch(BASE + '/hesap/hareketKaydet', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        showToast(res.message, 'success');
        closeModal(tip === 'giris' ? 'mGiris' : 'mCikis');
        setTimeout(() => location.reload(), 900);
      } else {
        showToast(res.message || 'Hata oluştu.', 'error');
      }
    })
    .catch(() => showToast('Bağlantı hatası.', 'error'))
    .finally(() => {
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-check"></i> Kaydet';
    });
}

/* ── TRANSFER ───────────────────────────────── */
function transferKaydet() {
  const toId   = document.getElementById('trToId').value;
  const tutar  = document.getElementById('trTutar').value.replace(',', '.');
  const tarih  = document.getElementById('trTarih').value;
  const acikl  = document.getElementById('trAciklama').value.trim();

  if (!toId)   { showToast('Hedef hesap seçiniz.', 'error'); return; }
  if (!tutar || parseFloat(tutar) <= 0) { showToast('Geçerli bir tutar giriniz.', 'error'); return; }

  const btn = document.getElementById('btnTransfer');
  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

  const fd = new FormData();
  fd.append('from_id',  HESAP_ID);
  fd.append('to_id',    toId);
  fd.append('tutar',    tutar);
  fd.append('tarih',    tarih);
  fd.append('aciklama', acikl || 'Hesaplar arası transfer');

  fetch(BASE + '/hesap/transferKaydet', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        showToast(res.message, 'success');
        closeModal('mTransfer');
        setTimeout(() => location.reload(), 900);
      } else {
        showToast(res.message || 'Hata oluştu.', 'error');
      }
    })
    .catch(() => showToast('Bağlantı hatası.', 'error'))
    .finally(() => {
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-check"></i> Transfer Yap';
    });
}

/* ── HAREKET SİL ────────────────────────────── */
function hareketSil(id, el) {
  if (!confirm('Bu hareketi silmek istediğinizden emin misiniz?\nBakiye otomatik güncellenir.')) return;

  fetch(BASE + '/hesap/hareketSil/' + id, { method: 'POST' })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        showToast('Hareket silindi.', 'success');
        const row = el.closest('tr');
        if (row) row.remove();
        setTimeout(() => location.reload(), 1200);
      } else {
        showToast(res.message || 'Silinemedi.', 'error');
      }
    })
    .catch(() => showToast('Bağlantı hatası.', 'error'));
}

/* ── HESAP SİL ──────────────────────────────── */
function hesapSilOnayla() {
  const hesapAdi = '<?= addslashes($hesap['hesap_adi']) ?>';
  if (!confirm(`"${hesapAdi}" hesabını silmek istediğinizden emin misiniz?\nBu işlem geri alınamaz.`)) return;
  nymPost(BASE + '/hesap/hesapSil/' + HESAP_ID);
}

/* ── TOAST ──────────────────────────────────── */
function showToast(msg, type = 'info') {
  const icons = { success: 'check-circle', error: 'circle-xmark', info: 'circle-info' };
  const c  = document.getElementById('toastContainer');
  const el = document.createElement('div');
  el.className = 'toast-msg ' + type;
  el.innerHTML = `<i class="fa-solid fa-${icons[type] || 'circle-info'}"></i> ${msg}`;
  c.appendChild(el);
  setTimeout(() => el.remove(), 3500);
}

/* ── SİDEBAR ACCORDION ───────────────────────── */
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
</script>
