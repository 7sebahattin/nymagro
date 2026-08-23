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
$invoiceDetailBase = $isMusteri ? 'satis' : 'alis';
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
$editUrl = BASE_URL . '/' . $basePath . '/duzenle/' . $cariId;
$documentUrl = BASE_URL . '/dokuman/cari/' . $cariTip . '/' . $cariId;
$deleteUrl = BASE_URL . '/' . $basePath . '/sil/' . $cariId;
$customerPurchaseUrl = BASE_URL . '/alis/ekle?cari_id=' . $cariId;
$supplierSaleUrl = BASE_URL . '/satis/ekle?cari_id=' . $cariId;
$supplierReturnUrl = BASE_URL . '/alis/ekle?cari_id=' . $cariId . '&belge_tipi=iade_alis';
$statementUrl = BASE_URL . '/ekstre/cari/' . $cariTip . '/' . $cariId;
$conciliationUrl = BASE_URL . '/ekstre/mutabakat/' . $cariTip . '/' . $cariId;
$recalcUrl = BASE_URL . '/' . $basePath . '/bakiyeGuncelle/' . $cariId;

$fmtMoney = static function($value): string {
    return number_format((float)$value, 2, ',', '.') . ' TL';
};
$fmtDate = static function($value): string {
    if (empty($value)) return '-';
    $ts = strtotime((string)$value);
    return $ts ? date('d.m.Y', $ts) : '-';
};
$fmtDateTime = static function($value): string {
    if (empty($value)) return '-';
    $ts = strtotime((string)$value);
    return $ts ? date('d.m.Y H:i', $ts) : '-';
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
.cd-hero-row{display:grid;grid-template-columns:1fr;gap:22px}
.cd-profile{border-radius:4px;background:var(--card-bg);border:1px solid var(--border);border-left:4px solid var(--accent);box-shadow:0 10px 25px rgba(15,23,42,.18);padding:20px 28px}
.cd-profile-main{display:flex;align-items:center;gap:24px}
.cd-avatar{width:76px;height:76px;border-radius:50%;background:var(--accent);border:4px solid var(--border2);display:flex;align-items:center;justify-content:center;color:#fff;font-size:30px;font-weight:900;flex:0 0 auto}
.cd-profile h2{margin:0;color:var(--text);font-size:24px;font-weight:900;letter-spacing:.02em;text-transform:uppercase;line-height:1.2}
.cd-profile small{display:block;margin-top:7px;color:var(--muted);font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.12em}
.cd-note-inline{margin-top:18px;padding-top:16px;border-top:1px solid var(--border)}
.cd-note-inline label{display:block;font-size:11px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px}
.cd-note-inline textarea{width:100%;min-height:44px;border:1px solid var(--border2);border-radius:6px;background:var(--surface-2);color:var(--text);resize:vertical;outline:0;font-size:13.5px;padding:8px 10px}
.cd-note-inline textarea:focus{border-color:var(--accent)}
.cd-note-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:8px}
.cd-note-actions button{border:0;border-radius:6px;padding:6px 10px;font-size:12px;font-weight:800}
.cd-note-save{background:#22c55e;color:#fff}
.cd-note-clear{background:var(--surface-2);color:var(--text2)}
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
.cd-more .dropdown-menu{min-width:255px;padding:0;border:1px solid var(--border2);border-radius:4px;box-shadow:0 14px 30px rgba(15,23,42,.18);overflow:hidden}
.cd-more .dropdown-item{display:flex;align-items:center;gap:12px;padding:13px 18px;font-size:14px;color:var(--text);border-bottom:1px solid var(--border)}
.cd-more .dropdown-item:last-child{border-bottom:0}
.cd-more .dropdown-item:hover{background:var(--surface-2);color:var(--accent)}
.cd-more .dropdown-item i{width:22px;text-align:center;font-size:20px;color:var(--info)}
.cd-more .dropdown-item.is-green i{color:var(--success)}
.cd-more .dropdown-item.is-gold i{color:var(--warning)}
.cd-more .dropdown-item.is-red i{color:var(--danger)}
.cd-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.cd-form-grid .full{grid-column:1 / -1}
.cd-form-grid label{font-size:12px;font-weight:800;color:var(--text2);margin-bottom:6px}
.cd-panels{display:grid;grid-template-columns:1fr 1fr;gap:28px;align-items:start}
.cd-panel{background:var(--card-bg);border-radius:4px;box-shadow:0 14px 30px rgba(15,23,42,.18);overflow:hidden}
.cd-panel-wide{grid-column:span 1}
.cd-panel-head{background:#334155;color:#fff;padding:14px 16px;display:flex;align-items:center;justify-content:space-between}
.cd-panel-head h3{margin:0;font-size:14px;font-weight:900;text-transform:uppercase}
.cd-collapse{width:34px;height:34px;border:0;border-radius:4px;background:var(--surface-2);color:var(--muted);font-weight:900}
.cd-panel-body{padding:22px 18px 16px}
.cd-table{width:100%;border-collapse:collapse;font-size:13px}
.cd-table th{color:var(--muted);font-weight:800;padding:8px 10px;border-bottom:1px solid var(--border)}
.cd-table td{padding:11px 10px;border-bottom:1px solid var(--border);color:var(--muted);vertical-align:middle}
.cd-table .money{text-align:right;white-space:nowrap}
.cd-plus{width:20px;height:20px;border-radius:50%;border:2px solid #5ec99e;background:transparent;color:#5ec99e;display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;line-height:1;padding:0;cursor:pointer;transition:background .12s}
.cd-plus:hover{background:rgba(94,201,158,.12)}
.cd-plus.is-open{border-color:#ef4444;color:#ef4444}
.cd-plus.is-open:hover{background:rgba(239,68,68,.1)}
.cd-detail-row{display:none}
.cd-detail-row.open{display:table-row}
.cd-detail-row td{padding:12px 10px;background:rgba(255,255,255,.03);border-bottom:1px solid var(--border)}
.cd-detail-inner{display:flex;flex-wrap:wrap;gap:8px 16px;align-items:center;font-size:12.5px;color:var(--text2)}
.cd-detail-btns{display:flex;gap:8px;flex-wrap:wrap}
.cd-btn-det{display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border-radius:4px;background:#334155;color:#fff;text-decoration:none;font-size:12px;font-weight:600}
.cd-btn-det:hover{filter:brightness(1.15);color:#fff}
.cd-status{color:var(--danger)}
.cd-empty{background:rgba(243,156,18,.15);color:var(--warning);padding:16px;border-radius:4px;font-size:13px}
.cd-info-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px 18px;margin-top:12px;font-size:12.5px;color:var(--text2)}
.cd-info-grid strong{color:var(--text)}
.cd-mobile-info{display:none}
.cd-alert{border-radius:6px;padding:12px 14px;font-size:13px;font-weight:800}
.cd-alert.success{background:rgba(46,204,113,.15);color:var(--success)}.cd-alert.error{background:rgba(231,76,60,.15);color:var(--danger)}
@media(max-width:1180px){.cd-summary{grid-template-columns:repeat(2,minmax(180px,1fr))}.cd-panels{grid-template-columns:1fr}}
@media(max-width:760px){
  .cd-profile{padding:18px}.cd-profile-main{gap:14px}.cd-profile h2{font-size:19px}.cd-avatar{width:60px;height:60px;font-size:24px}
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
      <div class="cd-profile-main">
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

      <div class="cd-note-inline">
        <label for="cdNote">Not</label>
        <textarea id="cdNote" placeholder="Bu <?= htmlspecialchars($entityLabel) ?> ile ilgili not kaydetmek için tıklayın."><?= htmlspecialchars($cari['notlar'] ?? '') ?></textarea>
        <div class="cd-note-actions">
          <button type="button" class="cd-note-clear" onclick="document.getElementById('cdNote').value=''">Temizle</button>
          <button type="button" class="cd-note-save" onclick="cdSaveNote()">Notu Kaydet</button>
        </div>
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
        <div class="cd-stat-value"><?= $fmtMoney($cekBakiye ?? 0) ?></div>
      </div>
    </div>
    <div class="cd-stat cd-blue">
      <div class="cd-stat-icon"><i class="fa-solid fa-tag"></i></div>
      <div class="cd-stat-body">
        <div class="cd-stat-title">Senet Bakiyesi</div>
        <div class="cd-stat-value"><?= $fmtMoney($senetBakiye ?? 0) ?></div>
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
    <?php if (Rbac::currentUserCan(($isMusteri ? 'SATIS' : 'ALIS') . '_CREATE')): ?>
    <a class="cd-btn cd-dark" href="<?= htmlspecialchars($primaryActionUrl) ?>"><i class="fa-solid <?= $primaryActionIcon ?>"></i> <?= htmlspecialchars($primaryActionText) ?></a>
    <?php endif; ?>
    <?php if ($isMusteri): ?>
      <?php if (Rbac::currentUserCan('SATIS_CREATE')): ?>
      <a class="cd-btn cd-orange" href="<?= BASE_URL ?>/teklif"><i class="fa-solid fa-list"></i> Teklif Hazırla</a>
      <?php endif; ?>
      <?php if (Rbac::currentUserCan('NAKIT_CREATE')): ?>
      <button class="cd-btn cd-success" type="button" onclick="openPaymentModal('giris')"><i class="fa-solid fa-turkish-lira-sign"></i> Tahsilat/Ödeme</button>
      <?php endif; ?>
    <?php else: ?>
      <?php if (Rbac::currentUserCan('NAKIT_CREATE')): ?>
      <button class="cd-btn cd-success" type="button" onclick="openPaymentModal('cikis')"><i class="fa-solid fa-turkish-lira-sign"></i> Ödeme/Tahsilat</button>
      <?php endif; ?>
      <?php if (Rbac::currentUserCan('DOKUMAN_VIEW')): ?>
      <a class="cd-btn cd-info" href="<?= htmlspecialchars($documentUrl) ?>"><i class="fa-solid fa-file-lines"></i> Dökümanlar</a>
      <?php endif; ?>
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
        <?php if (Rbac::currentUserCan(($isMusteri ? 'MUSTERI' : 'TEDARIKCI') . '_UPDATE')): ?>
        <a class="dropdown-item" href="<?= htmlspecialchars($editUrl) ?>">
          <i class="fa-solid fa-pen"></i> <?= $isMusteri ? 'Müşteri Bilgilerini Güncelle' : 'Tedarikçi Bilgilerini Güncelle' ?>
        </a>
        <a class="dropdown-item" href="#" onclick="return nymPost('<?= htmlspecialchars($recalcUrl) ?>', 'Bu <?= htmlspecialchars($entityLabel) ?>nin geçmiş tahsilat/ödemeleri faturalarına yeniden dağıtılacak. Devam edilsin mi?')">
          <i class="fa-solid fa-rotate"></i> Fatura Bakiyelerini Yeniden Hesapla
        </a>
        <?php endif; ?>
        <?php if ($isMusteri): ?>
          <?php if (Rbac::currentUserCan('DOKUMAN_VIEW')): ?>
          <a class="dropdown-item" href="<?= htmlspecialchars($documentUrl) ?>"><i class="fa-regular fa-file-lines"></i> Döküman Ekle</a>
          <?php endif; ?>
          <?php if (Rbac::currentUserCan('ALIS_CREATE')): ?>
          <a class="dropdown-item is-gold" href="<?= htmlspecialchars($customerPurchaseUrl) ?>"><i class="fa-solid fa-tags"></i> Alış Yap</a>
          <?php endif; ?>
          <?php if (Rbac::currentUserCan('MUSTERI_DELETE')): ?>
          <a class="dropdown-item is-red" href="#" onclick="return nymPost('<?= htmlspecialchars($deleteUrl) ?>', 'Bu müşteriyi silmek istediğinize emin misiniz?')"><i class="fa-solid fa-xmark"></i> Müşteriyi Sil</a>
          <?php endif; ?>
        <?php else: ?>
          <?php if (Rbac::currentUserCan('SATIS_CREATE')): ?>
          <a class="dropdown-item is-green" href="<?= htmlspecialchars($supplierSaleUrl) ?>"><i class="fa-solid fa-cart-shopping"></i> Tedarikçiye Satış Yap</a>
          <?php endif; ?>
          <?php if (Rbac::currentUserCan('ALIS_CREATE')): ?>
          <a class="dropdown-item is-gold" href="<?= htmlspecialchars($supplierReturnUrl) ?>"><i class="fa-solid fa-rotate-left"></i> Tedarikçiye İade Ver</a>
          <?php endif; ?>
          <?php if (Rbac::currentUserCan('TEDARIKCI_DELETE')): ?>
          <a class="dropdown-item is-red" href="#" onclick="return nymPost('<?= htmlspecialchars($deleteUrl) ?>', 'Bu tedarikçiyi silmek istediğinize emin misiniz?')"><i class="fa-solid fa-xmark"></i> Tedarikçiyi Sil</a>
          <?php endif; ?>
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
            <?php foreach ($faturaGecmisi as $row): $rid = (int)($row['id'] ?? 0); ?>
              <tr>
                <td><button type="button" class="cd-plus" onclick="cdToggleDetail('sat-<?= $rid ?>', this)">+</button></td>
                <td><?= htmlspecialchars($fmtDate($row['fatura_tarihi'] ?? '')) ?></td>
                <td><?= htmlspecialchars($row['fatura_no'] ?? '-') ?></td>
                <td class="cd-status"><?= htmlspecialchars($statusText((string)($row['durum'] ?? ''))) ?></td>
                <td class="money"><?= $fmtMoney($row['genel_toplam'] ?? 0) ?></td>
              </tr>
              <tr class="cd-detail-row" id="detail-sat-<?= $rid ?>">
                <td colspan="5">
                  <div class="cd-detail-inner">
                    <div class="cd-detail-btns">
                      <a class="cd-btn-det" href="<?= BASE_URL ?>/<?= $invoiceDetailBase ?>/detay/<?= $rid ?>"><i class="fa-solid fa-eye"></i> Görüntüle / Düzenle</a>
                    </div>
                    <span>Vade: <?= htmlspecialchars($fmtDate($row['vade_tarihi'] ?? '')) ?></span>
                    <span>KDV: <?= $fmtMoney($row['kdv_tutari'] ?? 0) ?></span>
                    <span>Kalan: <strong style="color:#f59e0b;"><?= $fmtMoney($row['kalan_tutar'] ?? 0) ?></strong></span>
                    <?php if (!empty($row['aciklama'])): ?><span><?= htmlspecialchars($row['aciklama']) ?></span><?php endif; ?>
                  </div>
                </td>
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
            <?php foreach ($odemeGecmisi as $row): $rid = (int)($row['id'] ?? 0); ?>
              <tr>
                <td><button type="button" class="cd-plus" onclick="cdToggleDetail('ode-<?= $rid ?>', this)">+</button></td>
                <td><?= htmlspecialchars($fmtDateTime($row['tarih'] ?? '')) ?></td>
                <td class="money"><?= $fmtMoney($row['tutar'] ?? 0) ?></td>
                <td><?= htmlspecialchars($row['odeme_yontemi'] ?? (($row['islem_tipi'] ?? '') === 'giris' ? 'Tahsilat' : 'Ödeme')) ?></td>
              </tr>
              <tr class="cd-detail-row" id="detail-ode-<?= $rid ?>">
                <td colspan="4">
                  <div class="cd-detail-inner">
                    <span>Hesap: <strong style="color:var(--text);"><?= htmlspecialchars($row['kasa_adi'] ?? '-') ?></strong></span>
                    <span>İşlem: <?= htmlspecialchars(($row['islem_tipi'] ?? '') === 'giris' ? 'Giriş (Tahsilat)' : 'Çıkış (Ödeme)') ?></span>
                    <?php if (!empty($row['aciklama'])): ?><span>Açıklama: <?= htmlspecialchars($row['aciklama']) ?></span><?php endif; ?>
                  </div>
                </td>
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
      <div class="modal-footer" style="background:rgba(243,156,18,.15);">
        <button type="button" class="btn btn-warning text-white" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Vazgeç</button>
        <a class="btn btn-danger" href="<?= htmlspecialchars($conciliationUrl) ?>"><i class="fa-solid fa-download"></i> İndir</a>
      </div>
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
            <label class="form-label">Ödeme Yöntemi</label>
            <select name="odeme_yontemi" class="form-select" required>
              <option value="">Seçiniz</option>
              <?php foreach (['Nakit', 'Havale/EFT', 'Kredi Kartı', 'Çek', 'Senet', 'Virman'] as $oy): ?>
                <option value="<?= $oy ?>"><?= $oy ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="cd-form-grid mb-3">
            <div>
              <label class="form-label">Tarih</label>
              <input type="date" name="tarih" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div>
              <label class="form-label">Saat</label>
              <input type="time" name="saat" class="form-control" value="<?= date('H:i') ?>" required>
            </div>
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
function cdToggleDetail(key, btn) {
  const row = document.getElementById('detail-' + key);
  if (!row) return;
  const isOpen = row.classList.toggle('open');
  btn.textContent = isOpen ? '−' : '+';
  btn.classList.toggle('is-open', isOpen);
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
