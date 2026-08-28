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

<?php
// Kullanıcı yalnızca AKTİF bir şirketi seçebilir (bkz. CompanyController::select).
// Seçilebilir hiçbir şirketi yoksa bu ekran çıkışsız bir çıkmazdır: liste ya
// boştur ya da sadece tıklanamayan pasif kartlar vardır. Bu durumda ne
// yapması gerektiğini açıkça söyle.
$secilebilir = array_filter($companies, fn(array $c): bool => ($c['status'] ?? '') === 'active');
?>
<?php if (empty($secilebilir)): ?>
  <div class="alert alert-warning">
    <h2 class="h6 mb-2"><i class="fa-solid fa-triangle-exclamation me-1"></i> Girebileceğiniz aktif bir şirket yok</h2>
    <?php if (empty($companies)): ?>
      <p class="mb-2">Hesabınıza henüz hiçbir şirket atanmamış.</p>
    <?php else: ?>
      <p class="mb-2">Yetkili olduğunuz şirket(ler) pasif durumda olduğu için seçilemiyor.</p>
    <?php endif; ?>
    <p class="mb-0">
      Sistem yöneticinizden <strong>Kullanıcı Yönetimi &rsaquo; kullanıcıyı düzenle &rsaquo; Yetkili Şirketler</strong>
      alanından size aktif bir şirket atamasını isteyin.
      <a href="<?= BASE_URL ?>/cikis" class="alert-link ms-1">Çıkış yap</a>
    </p>
  </div>
<?php endif; ?>

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
            <div class="d-flex flex-column align-items-end gap-1">
              <span class="badge text-bg-<?= $company['status'] === 'active' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($company['status']) ?></span>
              <?php if (!empty($company['is_default'])): ?>
                <span class="badge text-bg-warning"><i class="fa-solid fa-star me-1"></i>Varsayılan</span>
              <?php endif; ?>
            </div>
          </div>
          <div class="small mb-1"><span class="text-muted">Aktif dönem:</span> <?= htmlspecialchars($company['active_period_name'] ?? 'Seçilmedi') ?></div>
          <div class="small mb-1"><span class="text-muted">Dönem durumu:</span> <?= htmlspecialchars($company['active_period_status'] ?? '-') ?></div>
          <div class="small mb-3"><span class="text-muted">Rolüm:</span> <?= htmlspecialchars($company['role']) ?></div>
          <a href="<?= BASE_URL ?>/companies/select/<?= (int)$company['id'] ?>" class="btn btn-success w-100 <?= $company['status'] !== 'active' ? 'disabled' : '' ?>">
            <i class="fa-solid fa-check me-1"></i> Seç
          </a>
          <?php if (empty($company['is_default']) && $company['status'] === 'active'): ?>
            <a href="<?= BASE_URL ?>/companies/setDefault/<?= (int)$company['id'] ?>" class="btn btn-outline-secondary btn-sm w-100 mt-2">
              <i class="fa-regular fa-star me-1"></i> Varsayılan Yap
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
