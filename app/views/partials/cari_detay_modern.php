<?php
/**
 * Shared BizimHesap-style cari detail screen.
 *
 * Expected:
 * - $cari
 * - $cariTip: musteri|tedarikci
 * - $faturaGecmisi
 * - $odemeGecmisi
 */
$cari = $cari ?? [];
$cariTip = $cariTip ?? 'musteri';
$faturaGecmisi = $faturaGecmisi ?? [];
$odemeGecmisi = $odemeGecmisi ?? [];
$flash = $flash ?? [];

$isMusteri = $cariTip === 'musteri';
$basePath = $isMusteri ? 'musteri' : 'tedarikci';
$entityLabel = $isMusteri ? 'müşteri' : 'tedarikçi';
$entityTitle = $isMusteri ? 'Müşteri' : 'Tedarikçi';
$invoiceTitle = $isMusteri ? 'Önceki Satışlar' : 'Önceki Ürün/Hizmet Alışları';
$paymentTitle = $isMusteri ? 'Önceki Ödemeleri' : 'Önceki Ödemeler';
$emptyInvoiceText = $isMusteri ? 'Bu müşterinin daha önceden kayıtlı satışı yok.' : 'Bu tedarikçinin daha önceden kayıtlı alış kaydı yok.';
$emptyReturnText = $isMusteri ? 'Bu müşterinin daha önceden kayıtlı iadesi yok.' : 'Bu tedarikçinin daha önceden kayıtlı iadesi yok.';
$primaryActionUrl = $isMusteri
    ? BASE_URL . '/satis/ekle?cari_id=' . (int)($cari['id'] ?? 0)
    : BASE_URL . '/alis/ekle?cari_id=' . (int)($cari['id'] ?? 0);
$primaryActionText = $isMusteri ? 'Satış Yap' : 'Alış Yap';
$primaryActionIcon = $isMusteri ? 'fa-cart-shopping' : 'fa-truck-ramp-box';
$noteEndpoint = BASE_URL . '/' . $basePath . '/not_kaydet/' . (int)($cari['id'] ?? 0);
$cariId = (int)($cari['id'] ?? 0);
$updateEndpoint = BASE_URL . '/' . $basePath . '/guncelle/' . $cariId;
$documentUrl = BASE_URL . '/dokuman/cari/' . $cariTip . '/' . $cariId;
$deleteUrl = BASE_URL . '/' . $basePath . '/sil/' . $cariId;
$customerPurchaseUrl = BASE_URL . '/alis/ekle?cari_id=' . $cariId;
$supplierSaleUrl = BASE_URL . '/satis/ekle?cari_id=' . $cariId;
$supplierReturnUrl = BASE_URL . '/alis/ekle?cari_id=' . $cariId . '&belge_tipi=iade_alis';
$statementUrl = BASE_URL . '/ekstre/cari/' . $cariTip . '/' . $cariId;
$conciliationUrl = BASE_URL . '/ekstre/mutabakat/' . $cariTip . '/' . $cariId;

$fmtMoney = static function($value): string {
    return number_format((float)$value, 2, ',', '.') . ' TL';
};
$fmtDate = static function($value): string {
    if (empty($value)) return '-';
    $ts = strtotime((string)$value);
    return $ts ? date('d.m.Y', $ts) : '-';
};
$sumInvoices = static function(array $rows): float {
    $sum = 0.0;
    foreach ($rows as $row) $sum += (float)($row['genel_toplam'] ?? 0);
    return $sum;
};
$sumPayments = static function(array $rows, string $tip): float {
    $sum = 0.0;
    foreach ($rows as $row) {
        if (($row['islem_tipi'] ?? '') === $tip) $sum += (float)($row['tutar'] ?? 0);
    }
    return $sum;
};
$statusText = static function(string $status): string {
    return [
        'taslak' => 'Taslak',
        'onaylandi' => 'Onaylandı',
        'odendi' => 'Ödendi',
        'kismi_odendi' => 'Kısmi Ödendi',
        'iptal' => 'İptal',
    ][$status] ?? ($status !== '' ? ucfirst($status) : '-');
};

$bakiye = (float)($cari['bakiye'] ?? 0);
$toplamFatura = $sumInvoices($faturaGecmisi);
$toplamNakit = $sumPayments($odemeGecmisi, $isMusteri ? 'giris' : 'cikis');
$otherNakit = $sumPayments($odemeGecmisi, $isMusteri ? 'cikis' : 'giris');
$initials = mb_strtoupper(mb_substr((string)($cari['unvan'] ?? $entityTitle), 0, 1));
?>

<style>
.cd-page{display:flex;flex-direction:column;gap:18px}
.cd-hero-row{display:grid;grid-template-columns:minmax(320px,1fr) minmax(280px,420px);gap:22px;align-items:center}
.cd-profile{min-height:118px;border-radius:4px;background:
  linear-gradient(90deg,rgba(255,255,255,.36),rgba(255,255,255,.08)),
  repeating-linear-gradient(90deg,rgba(166,111,45,.10) 0 18px,rgba(255,255,255,.12) 18px 36px),
  linear-gradient(135deg,#f5d9a7,#f1c982);
  box-shadow:0 10px 25px rgba(15,23,42,.18);display:flex;align-items:center;gap:24px;padding:20px 28px}
.cd-avatar{width:76px;height:76px;border-radius:50%;background:#d8d8d8;border:6px solid rgba(255,255,255,.72);display:flex;align-items:center;justify-content:center;color:#7b7b7b;font-size:30px;font-weight:900;flex:0 0 auto}
.cd-profile h2{margin:0;color:#6f6f78;font-size:24px;font-weight:900;letter-spacing:.02em;text-transform:uppercase;line-height:1.2}
.cd-profile small{display:block;margin-top:7px;color:#7d7d86;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.12em}
.cd-note{position:relative;background:#ffffd9;border:7px solid #666;border-radius:28px;min-height:96px;padding:18px 22px;color:#43c27f;box-shadow:0 8px 22px rgba(15,23,42,.08)}
.cd-note::before{content:"";position:absolute;left:-37px;top:32px;border-width:18px 37px 18px 0;border-style:solid;border-color:transparent #666 transparent transparent}
.cd-note::after{content:"";position:absolute;left:-24px;top:40px;border-width:10px 24px 10px 0;border-style:solid;border-color:transparent #ffffd9 transparent transparent}
.cd-note textarea{width:100%;min-height:50px;border:0;background:transparent;color:#2aa86a;resize:vertical;outline:0;font-size:13.5px}
.cd-note-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:8px}
.cd-note-actions button{border:0;border-radius:6px;padding:6px 10px;font-size:12px;font-weight:800}
.cd-note-save{background:#22c55e;color:#fff}
.cd-note-clear{background:#e5e7eb;color:#334155}
.cd-summary{display:grid;grid-template-columns:repeat(4,minmax(180px,1fr));gap:18px}
.cd-stat{height:82px;border-radius:3px;color:#fff;box-shadow:0 10px 22px rgba(15,23,42,.16);display:grid;grid-template-columns:76px 1fr;align-items:center;overflow:hidden}
.cd-stat-icon{height:100%;display:flex;align-items:center;justify-content:center;font-size:34px;color:rgba(255,255,255,.9)}
.cd-stat-body{text-align:center;padding-right:18px}
.cd-stat-title{font-size:12px;margin-bottom:8px}
.cd-stat-value{font-size:18px;font-weight:500}
.cd-stat-sub{font-size:11px;margin-top:4px;opacity:.92}
.cd-red{background:#ff7f73}.cd-blue{background:#58b7dd}.cd-teal{background:#61cda1}.cd-green{background:#63cfa4}.cd-indigo{background:#5661d9}
.cd-actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.cd-btn{display:inline-flex;align-items:center;gap:7px;border:0;border-radius:4px;padding:9px 12px;color:#fff;text-decoration:none;font-size:12.5px;font-weight:600;box-shadow:0 5px 11px rgba(15,23,42,.18);line-height:1.15;letter-spacing:0}
.cd-btn:hover{filter:brightness(1.05);color:#fff;transform:translateY(-1px)}
.cd-dark{background:#334155}.cd-orange{background:#f6a633}.cd-success{background:#35ad50}.cd-info{background:#40a9c4}.cd-purple{background:#0b5533}.cd-danger{background:#d84a43}
.cd-more{position:relative}
.cd-more .dropdown-menu{min-width:255px;padding:0;border:1px solid #cbd5e1;border-radius:4px;box-shadow:0 14px 30px rgba(15,23,42,.18);overflow:hidden}
.cd-more .dropdown-item{display:flex;align-items:center;gap:12px;padding:13px 18px;font-size:14px;color:#253044;border-bottom:1px solid #e5e7eb}
.cd-more .dropdown-item:last-child{border-bottom:0}
.cd-more .dropdown-item:hover{background:#f8fafc;color:#0d623a}
.cd-more .dropdown-item i{width:22px;text-align:center;font-size:20px;color:#2f83c7}
.cd-more .dropdown-item.is-green i{color:#248c41}
.cd-more .dropdown-item.is-gold i{color:#9a6b25}
.cd-more .dropdown-item.is-red i{color:#c43d3d}
.cd-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.cd-form-grid .full{grid-column:1 / -1}
.cd-form-grid label{font-size:12px;font-weight:800;color:#475569;margin-bottom:6px}
.cd-panels{display:grid;grid-template-columns:1fr 1fr;gap:28px;align-items:start}
.cd-panel{background:#fff;border-radius:4px;box-shadow:0 14px 30px rgba(15,23,42,.18);overflow:hidden}
.cd-panel-wide{grid-column:span 1}
.cd-panel-head{background:#334155;color:#fff;padding:14px 16px;display:flex;align-items:center;justify-content:space-between}
.cd-panel-head h3{margin:0;font-size:14px;font-weight:900;text-transform:uppercase}
.cd-collapse{width:34px;height:34px;border:0;border-radius:4px;background:#e5e7eb;color:#64748b;font-weight:900}
.cd-panel-body{padding:22px 18px 16px}
.cd-table{width:100%;border-collapse:collapse;font-size:13px}
.cd-table th{color:#707070;font-weight:800;padding:8px 10px;border-bottom:1px solid #222}
.cd-table td{padding:11px 10px;border-bottom:1px solid #d9d9d9;color:#64748b;vertical-align:middle}
.cd-table .money{text-align:right;white-space:nowrap}
.cd-plus{width:20px;height:20px;border-radius:50%;background:#5ec99e;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:11px}
.cd-status{color:#cf3f3f}
.cd-empty{background:#fff9df;color:#9a741d;padding:16px;border-radius:4px;font-size:13px}
.cd-info-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px 18px;margin-top:12px;font-size:12.5px;color:#475569}
.cd-info-grid strong{color:#0f172a}
.cd-mobile-info{display:none}
.cd-alert{border-radius:6px;padding:12px 14px;font-size:13px;font-weight:800}
.cd-alert.success{background:#dcfce7;color:#166534}.cd-alert.error{background:#fee2e2;color:#991b1b}
@media(max-width:1180px){.cd-summary{grid-template-columns:repeat(2,minmax(180px,1fr))}.cd-panels{grid-template-columns:1fr}}
@media(max-width:760px){
  .cd-hero-row{grid-template-columns:1fr}
  .cd-profile{padding:18px;gap:14px}.cd-profile h2{font-size:19px}.cd-avatar{width:60px;height:60px;font-size:24px}
  .cd-note::before,.cd-note::after{display:none}.cd-note{border-width:4px;border-radius:18px}
  .cd-summary{grid-template-columns:1fr}.cd-stat{grid-template-columns:64px 1fr}
  .cd-actions{display:grid;grid-template-columns:1fr 1fr}.cd-btn{justify-content:center}
  .cd-actions .dropdown{width:100%}.cd-actions .dropdown .cd-btn{width:100%}.cd-more .dropdown-menu{min-width:100%}
  .cd-form-grid{grid-template-columns:1fr}
  .cd-info-grid{grid-template-columns:1fr}.cd-table{font-size:12px}.cd-table th:nth-child(3),.cd-table td:nth-child(3){display:none}
}
</style>

<div class="cd-page">
  <?php if (!empty($flash)): ?>
    <div class="cd-alert <?= htmlspecialchars($flash['tip'] ?? 'success') ?>"><?= htmlspecialchars($flash['mesaj'] ?? '') ?></div>
  <?php endif; ?>

  <div class="cd-hero-row">
    <div class="cd-profile">
      <div class="cd-avatar"><?= htmlspecialchars($initials) ?></div>
      <div>
        <h2><?= htmlspecialchars($cari['unvan'] ?? '-') ?></h2>
        <small><?= htmlspecialchars($entityTitle) ?> kartı</small>
        <div class="cd-info-grid">
          <span><strong>Telefon:</strong> <?= htmlspecialchars(($cari['telefon'] ?? '') !== '' ? $cari['telefon'] : ($cari['cep_telefon'] ?? '-')) ?></span>
          <span><strong>E-posta:</strong> <?= htmlspecialchars($cari['eposta'] ?? '-') ?></span>
          <span><strong>Adres:</strong> <?= htmlspecialchars($cari['adres'] ?? '-') ?></span>
          <span><strong>Vergi No:</strong> <?= htmlspecialchars($cari['vergi_no'] ?? '-') ?></span>
        </div>
      </div>
    </div>

    <div class="cd-note">
      <textarea id="cdNote" placeholder="Bu <?= htmlspecialchars($entityLabel) ?> ile ilgili not kaydetmek için tıklayın."><?= htmlspecialchars($cari['notlar'] ?? '') ?></textarea>
      <div class="cd-note-actions">
        <button type="button" class="cd-note-clear" onclick="document.getElementById('cdNote').value=''">Temizle</button>
        <button type="button" class="cd-note-save" onclick="cdSaveNote()">Notu Kaydet</button>
      </div>
    </div>
  </div>

  <div class="cd-summary">
    <div class="cd-stat cd-red">
      <div class="cd-stat-icon"><i class="fa-solid fa-money-bill-1-wave"></i></div>
      <div class="cd-stat-body">
        <div class="cd-stat-title">Açık Bakiyesi</div>
        <div class="cd-stat-value"><?= $fmtMoney(abs($bakiye)) ?></div>
        <div class="cd-stat-sub"><?= $bakiye == 0 ? 'kapalı' : ($bakiye > 0 ? 'borçlu' : 'alacaklı') ?></div>
      </div>
    </div>
    <div class="cd-stat cd-blue">
      <div class="cd-stat-icon"><i class="fa-solid fa-tag"></i></div>
      <div class="cd-stat-body">
        <div class="cd-stat-title">Çek Bakiyesi</div>
        <div class="cd-stat-value">TL 0,00</div>
      </div>
    </div>
    <div class="cd-stat cd-blue">
      <div class="cd-stat-icon"><i class="fa-solid fa-tag"></i></div>
      <div class="cd-stat-body">
        <div class="cd-stat-title">Senet Bakiyesi</div>
        <div class="cd-stat-value">TL 0,00</div>
      </div>
    </div>
    <div class="cd-stat <?= $isMusteri ? 'cd-green' : 'cd-teal' ?>">
      <div class="cd-stat-icon"><i class="fa-solid <?= $isMusteri ? 'fa-gavel' : 'fa-boxes-stacked' ?>"></i></div>
      <div class="cd-stat-body">
        <div class="cd-stat-title"><?= $isMusteri ? 'Cirosu' : 'Toplam Alış' ?></div>
        <div class="cd-stat-value"><?= $fmtMoney($toplamFatura) ?></div>
      </div>
    </div>
  </div>

  <div class="cd-actions">
    <a class="cd-btn cd-dark" href="<?= htmlspecialchars($primaryActionUrl) ?>"><i class="fa-solid <?= $primaryActionIcon ?>"></i> <?= htmlspecialchars($primaryActionText) ?></a>
    <?php if ($isMusteri): ?>
      <a class="cd-btn cd-orange" href="<?= BASE_URL ?>/teklif"><i class="fa-solid fa-list"></i> Teklif Hazırla</a>
      <button class="cd-btn cd-success" type="button" onclick="openPaymentModal('giris')"><i class="fa-solid fa-turkish-lira-sign"></i> Tahsilat/Ödeme</button>
    <?php else: ?>
      <button class="cd-btn cd-success" type="button" onclick="openPaymentModal('cikis')"><i class="fa-solid fa-turkish-lira-sign"></i> Ödeme/Tahsilat</button>
      <a class="cd-btn cd-info" href="<?= htmlspecialchars($documentUrl) ?>"><i class="fa-solid fa-file-lines"></i> Dökümanlar</a>
    <?php endif; ?>
    <div class="dropdown cd-more">
      <button class="cd-btn cd-dark dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fa-solid fa-list"></i> Hesap Ekstresi
      </button>
      <div class="dropdown-menu">
        <a class="dropdown-item" href="<?= htmlspecialchars($statementUrl) ?>"><i class="fa-regular fa-file-lines"></i> Ekstre</a>
        <button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#conciliationModal">
          <i class="fa-regular fa-file-word"></i> Mutabakat Mektubu
        </button>
      </div>
    </div>
    <div class="dropdown cd-more">
      <button class="cd-btn cd-danger dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fa-solid fa-bars"></i> Diğer İşlemler
      </button>
      <div class="dropdown-menu">
        <button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#cdUpdateModal">
          <i class="fa-solid fa-pen"></i> <?= $isMusteri ? 'Müşteri Bilgilerini Güncelle' : 'Tedarikçi Bilgilerini Güncelle' ?>
        </button>
        <?php if ($isMusteri): ?>
          <a class="dropdown-item" href="<?= htmlspecialchars($documentUrl) ?>"><i class="fa-regular fa-file-lines"></i> Döküman Ekle</a>
          <a class="dropdown-item is-gold" href="<?= htmlspecialchars($customerPurchaseUrl) ?>"><i class="fa-solid fa-tags"></i> Alış Yap</a>
          <a class="dropdown-item is-red" href="<?= htmlspecialchars($deleteUrl) ?>" onclick="return confirm('Bu müşteriyi silmek istediğinize emin misiniz?')"><i class="fa-solid fa-xmark"></i> Müşteriyi Sil</a>
        <?php else: ?>
          <a class="dropdown-item is-green" href="<?= htmlspecialchars($supplierSaleUrl) ?>"><i class="fa-solid fa-cart-shopping"></i> Tedarikçiye Satış Yap</a>
          <a class="dropdown-item is-gold" href="<?= htmlspecialchars($supplierReturnUrl) ?>"><i class="fa-solid fa-rotate-left"></i> Tedarikçiye İade Ver</a>
          <a class="dropdown-item is-red" href="<?= htmlspecialchars($deleteUrl) ?>" onclick="return confirm('Bu tedarikçiyi silmek istediğinize emin misiniz?')"><i class="fa-solid fa-xmark"></i> Tedarikçiyi Sil</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="cd-panels">
    <section class="cd-panel">
      <div class="cd-panel-head">
        <h3><?= htmlspecialchars($invoiceTitle) ?></h3>
        <button class="cd-collapse" type="button" onclick="cdTogglePanel(this)"><i class="fa-solid fa-chevron-down"></i></button>
      </div>
      <div class="cd-panel-body">
        <?php if (empty($faturaGecmisi)): ?>
          <div class="cd-empty"><?= htmlspecialchars($emptyInvoiceText) ?> <a href="<?= htmlspecialchars($primaryActionUrl) ?>"><?= htmlspecialchars($primaryActionText) ?> için tıklayın.</a></div>
        <?php else: ?>
          <table class="cd-table">
            <thead><tr><th></th><th>Tarih</th><th>No</th><th>Durum</th><th class="money">Tutar</th></tr></thead>
            <tbody>
            <?php foreach ($faturaGecmisi as $row): ?>
              <tr>
                <td><span class="cd-plus"><i class="fa-solid fa-plus"></i></span></td>
                <td><?= htmlspecialchars($fmtDate($row['fatura_tarihi'] ?? '')) ?></td>
                <td><?= htmlspecialchars($row['fatura_no'] ?? '-') ?></td>
                <td class="cd-status"><?= htmlspecialchars($statusText((string)($row['durum'] ?? ''))) ?></td>
                <td class="money"><?= $fmtMoney($row['genel_toplam'] ?? 0) ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </section>

    <section class="cd-panel">
      <div class="cd-panel-head">
        <h3><?= htmlspecialchars($paymentTitle) ?></h3>
        <button class="cd-collapse" type="button" onclick="cdTogglePanel(this)"><i class="fa-solid fa-chevron-down"></i></button>
      </div>
      <div class="cd-panel-body">
        <?php if (empty($odemeGecmisi)): ?>
          <div class="cd-empty">Bu <?= htmlspecialchars($entityLabel) ?> için kayıtlı nakit hareketi yok.</div>
        <?php else: ?>
          <table class="cd-table">
            <thead><tr><th></th><th>Tarih</th><th class="money">Tutar</th><th>Şekli</th></tr></thead>
            <tbody>
            <?php foreach ($odemeGecmisi as $row): ?>
              <tr>
                <td><span class="cd-plus"><i class="fa-solid fa-plus"></i></span></td>
                <td><?= htmlspecialchars($fmtDate($row['tarih'] ?? '')) ?></td>
                <td class="money"><?= $fmtMoney($row['tutar'] ?? 0) ?></td>
                <td><?= htmlspecialchars($row['kasa_adi'] ?? (($row['islem_tipi'] ?? '') === 'giris' ? 'Tahsilat' : 'Ödeme')) ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </section>

    <section class="cd-panel">
      <div class="cd-panel-head">
        <h3><?= $isMusteri ? 'Alışlar / İadeler' : 'İadeler / Düzeltmeler' ?></h3>
        <button class="cd-collapse" type="button" onclick="cdTogglePanel(this)"><i class="fa-solid fa-chevron-down"></i></button>
      </div>
      <div class="cd-panel-body">
        <div class="cd-empty"><?= htmlspecialchars($emptyReturnText) ?></div>
      </div>
    </section>
  </div>
</div>

<div class="modal fade" id="conciliationModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="background:#61cda1;color:#fff;">
        <h5 class="modal-title">Mutabakat Mektubu</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-2">Müşterinize/tedarikçinize göndermek üzere Microsoft Word formatında Mutabakat Mektubu dosyası hazırlanacaktır.</p>
        <p class="mb-2">Dosyayı indirdikten sonra imzalayıp kaşeleyip gönderebilirsiniz.</p>
        <p class="mb-0">Mutabakat hesabı <strong style="color:#22c55e;"><?= date('d.m.Y') ?></strong> tarihi itibarıyla yapılacaktır.</p>
      </div>
      <div class="modal-footer" style="background:#fff9df;">
        <button type="button" class="btn btn-warning text-white" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Vazgeç</button>
        <a class="btn btn-danger" href="<?= htmlspecialchars($conciliationUrl) ?>"><i class="fa-solid fa-download"></i> İndir</a>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="cdUpdateModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form method="post" action="<?= htmlspecialchars($updateEndpoint) ?>">
        <div class="modal-header">
          <h5 class="modal-title"><?= htmlspecialchars($entityTitle) ?> Bilgilerini Güncelle</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="cd-form-grid">
            <div class="full">
              <label class="form-label">Unvan *</label>
              <input type="text" name="unvan" class="form-control" value="<?= htmlspecialchars($cari['unvan'] ?? '') ?>" required>
            </div>
            <div>
              <label class="form-label">Yetkili Kişi</label>
              <input type="text" name="yetkili_kisi" class="form-control" value="<?= htmlspecialchars($cari['yetkili_kisi'] ?? '') ?>">
            </div>
            <div>
              <label class="form-label">Cari Kodu</label>
              <input type="text" name="cari_kodu" class="form-control" value="<?= htmlspecialchars($cari['cari_kodu'] ?? '') ?>">
            </div>
            <div>
              <label class="form-label">Telefon</label>
              <input type="text" name="telefon" class="form-control" value="<?= htmlspecialchars($cari['telefon'] ?? '') ?>">
            </div>
            <div>
              <label class="form-label">Cep Telefonu</label>
              <input type="text" name="cep_telefon" class="form-control" value="<?= htmlspecialchars($cari['cep_telefon'] ?? '') ?>">
            </div>
            <div>
              <label class="form-label">E-posta</label>
              <input type="email" name="eposta" class="form-control" value="<?= htmlspecialchars($cari['eposta'] ?? '') ?>">
            </div>
            <div>
              <label class="form-label">Para Birimi</label>
              <select name="para_birimi" class="form-select">
                <?php foreach (['TRY' => 'TRY', 'USD' => 'USD', 'EUR' => 'EUR'] as $code => $label): ?>
                  <option value="<?= $code ?>" <?= (($cari['para_birimi'] ?? 'TRY') === $code) ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="form-label">Vergi Dairesi</label>
              <input type="text" name="vergi_dairesi" class="form-control" value="<?= htmlspecialchars($cari['vergi_dairesi'] ?? '') ?>">
            </div>
            <div>
              <label class="form-label">Vergi No</label>
              <input type="text" name="vergi_no" class="form-control" value="<?= htmlspecialchars($cari['vergi_no'] ?? '') ?>">
            </div>
            <div class="full">
              <label class="form-label">Adres</label>
              <textarea name="adres" class="form-control" rows="2"><?= htmlspecialchars($cari['adres'] ?? '') ?></textarea>
            </div>
            <div class="full">
              <label class="form-label">Notlar</label>
              <textarea name="notlar" class="form-control" rows="3"><?= htmlspecialchars($cari['notlar'] ?? '') ?></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
          <button type="submit" class="btn btn-primary">Güncelle</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="paymentModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="pModalTitle">İşlem Yap</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="paymentForm">
          <input type="hidden" name="cari_id" value="<?= (int)($cari['id'] ?? 0) ?>">
          <input type="hidden" name="islem_tipi" id="pIslemTipi">
          <div class="mb-3">
            <label class="form-label">Kasa / Hesap</label>
            <select name="kasa_id" class="form-select">
              <?php foreach (($kasaHesaplar ?? []) as $kh): ?>
                <option value="<?= (int)$kh['id'] ?>"><?= htmlspecialchars($kh['hesap_adi'] . ' (' . $kh['para_birimi'] . ')') ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Tutar</label>
            <div class="input-group">
              <input type="text" name="tutar" class="form-control" placeholder="0,00" required>
              <span class="input-group-text">TL</span>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Açıklama</label>
            <textarea name="aciklama" class="form-control" rows="2"></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
        <button type="button" class="btn btn-primary" onclick="savePayment()">Kaydet</button>
      </div>
    </div>
  </div>
</div>

<script>
function cdTogglePanel(btn) {
  const body = btn.closest('.cd-panel').querySelector('.cd-panel-body');
  body.style.display = body.style.display === 'none' ? '' : 'none';
  btn.querySelector('i')?.classList.toggle('fa-chevron-up');
}
function openPaymentModal(tip) {
  document.getElementById('pIslemTipi').value = tip;
  document.getElementById('pModalTitle').innerText = tip === 'giris' ? 'Tahsilat Girişi' : 'Ödeme Çıkışı';
  new bootstrap.Modal(document.getElementById('paymentModal')).show();
}
async function savePayment() {
  const form = document.getElementById('paymentForm');
  const formData = new FormData(form);
  try {
    const response = await fetch('<?= BASE_URL ?>/nakit/kaydet', { method: 'POST', body: formData });
    const res = await response.json();
    if (res.status === 'success') location.reload();
    else alert('Hata: ' + (res.message || 'İşlem kaydedilemedi.'));
  } catch(err) {
    alert('İşlem kaydedilemedi!');
  }
}
async function cdSaveNote() {
  const fd = new FormData();
  fd.append('notlar', document.getElementById('cdNote').value);
  try {
    const response = await fetch('<?= $noteEndpoint ?>', { method: 'POST', body: fd });
    const res = await response.json();
    if (res.status === 'success') alert('Not kaydedildi.');
    else alert(res.message || 'Not kaydedilemedi.');
  } catch (e) {
    alert('Not kaydedilemedi.');
  }
}
</script>
