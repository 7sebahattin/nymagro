<?php $flash = $flash ?? []; ?>
<?php if (!empty($flash)): ?>
  <div class="alert alert-<?= htmlspecialchars($flash['tip'] === 'error' ? 'danger' : $flash['tip']) ?>"><?= htmlspecialchars($flash['mesaj']) ?></div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 mb-1">Şirket Değiştir</h1>
    <div class="text-muted small">Yetkili olduğunuz şirket ve dönemlerden birini seçin.</div>
  </div>
  <a href="<?= BASE_URL ?>/companies" class="btn btn-outline-secondary"><i class="fa-solid fa-building me-1"></i> Şirket Yönetimi</a>
</div>

<div class="row g-3">
  <?php foreach ($companies as $company): ?>
    <div class="col-md-6 col-xl-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between gap-2 mb-2">
            <div>
              <h2 class="h6 mb-1"><?= htmlspecialchars($company['company_name']) ?></h2>
              <div class="text-muted small"><?= htmlspecialchars($company['short_name'] ?? '') ?></div>
            </div>
            <span class="badge text-bg-<?= $company['status'] === 'active' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($company['status']) ?></span>
          </div>
          <div class="small mb-1"><span class="text-muted">Aktif dönem:</span> <?= htmlspecialchars($company['active_period_name'] ?? 'Seçilmedi') ?></div>
          <div class="small mb-1"><span class="text-muted">Dönem durumu:</span> <?= htmlspecialchars($company['active_period_status'] ?? '-') ?></div>
          <div class="small mb-3"><span class="text-muted">Rolüm:</span> <?= htmlspecialchars($company['role']) ?></div>
          <a href="<?= BASE_URL ?>/companies/select/<?= (int)$company['id'] ?>" class="btn btn-success w-100 <?= $company['status'] !== 'active' ? 'disabled' : '' ?>">
            <i class="fa-solid fa-check me-1"></i> Seç
          </a>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
