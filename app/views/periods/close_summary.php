<?php $money = fn($v) => number_format((float)$v, 2, ',', '.'); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 mb-1"><?= htmlspecialchars($period['company_name']) ?> - <?= htmlspecialchars($period['period_name']) ?></h1>
    <div class="text-muted small">Dönem kapama öncesi kontrol özeti.</div>
  </div>
  <a href="<?= BASE_URL ?>/periods/close/<?= (int)$period['id'] ?>" class="btn btn-danger" onclick="return confirm('Bu dönem kapatılsın mı?')">
    <i class="fa-solid fa-lock me-1"></i> Dönemi Kapat
  </a>
</div>

<div class="row g-3">
  <?php foreach ([
    'Toplam Satış' => $summary['toplam_satis'] ?? 0,
    'Toplam Alış' => $summary['toplam_alis'] ?? 0,
    'Toplam Tahsilat' => $summary['toplam_tahsilat'] ?? 0,
    'Toplam Ödeme' => $summary['toplam_odeme'] ?? 0,
  ] as $label => $value): ?>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm"><div class="card-body">
        <div class="text-muted small"><?= htmlspecialchars($label) ?></div>
        <div class="h5 mb-0"><?= $money($value) ?> TL</div>
      </div></div>
    </div>
  <?php endforeach; ?>
</div>

<div class="card border-0 shadow-sm mt-3">
  <div class="card-body">
    <h2 class="h6">Kontroller</h2>
    <div class="table-responsive">
      <table class="table mb-0">
        <tbody>
          <tr><td>Taslak faturalar</td><td class="text-end"><?= (int)($summary['taslak_fatura'] ?? 0) ?></td></tr>
          <tr><td>Ödenmemiş faturalar</td><td class="text-end"><?= (int)($summary['odenmemis_fatura'] ?? 0) ?></td></tr>
          <tr><td>Negatif stoklu ürün</td><td class="text-end"><?= (int)($summary['negatif_stok'] ?? 0) ?></td></tr>
          <tr><td>Devir notu</td><td class="text-end"><?= htmlspecialchars($summary['not']) ?></td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>
