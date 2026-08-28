<?php
$flash = $flash ?? [];
$companies = $companies ?? [];
// Yetkisi olmayan kullanıcıya ham 403 sayfasına götüren buton gösterme.
$aktifId = TenantContext::activeCompanyId();
$canCreate = AuthGuard::isSuperAdmin() || ($aktifId && TenantContext::canManageCompany($aktifId));
?>
<?php if (!empty($flash)): ?>
  <div class="alert alert-<?= htmlspecialchars($flash['tip'] === 'error' ? 'danger' : $flash['tip']) ?>"><?= htmlspecialchars($flash['mesaj']) ?></div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 mb-1">Şirket Yönetimi</h1>
    <div class="text-muted small">Şirketler, varsayılan ayarlar ve dönem bağlantıları.</div>
  </div>
  <?php if ($canCreate): ?>
    <a href="<?= BASE_URL ?>/companies/create" class="btn btn-success"><i class="fa-solid fa-plus me-1"></i> Yeni Şirket</a>
  <?php endif; ?>
</div>

<?php if (empty($companies)): ?>
  <div class="alert alert-warning">
    Hesabınıza atanmış bir şirket yok. Sistem yöneticinizden
    <strong>Kullanıcı Yönetimi &rsaquo; kullanıcıyı düzenle &rsaquo; Yetkili Şirketler</strong>
    alanından size şirket atamasını isteyin.
  </div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Şirket</th>
          <th>Vergi No</th>
          <th>Şehir</th>
          <th>Para Birimi</th>
          <th>Dönem</th>
          <th>Durum</th>
          <th class="text-end">İşlem</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($companies as $company): ?>
        <tr>
          <td>
            <div class="fw-semibold">
              <?= htmlspecialchars($company['company_name']) ?>
              <?php if (!empty($company['is_default'])): ?>
                <span class="badge text-bg-warning ms-1"><i class="fa-solid fa-star me-1"></i>Varsayılan</span>
              <?php endif; ?>
            </div>
            <div class="text-muted small"><?= htmlspecialchars($company['short_name'] ?? '') ?></div>
          </td>
          <td><?= htmlspecialchars($company['tax_number'] ?? '-') ?></td>
          <td><?= htmlspecialchars($company['city'] ?? '-') ?></td>
          <td><?= htmlspecialchars($company['currency'] ?? 'TRY') ?></td>
          <td><?= (int)($company['period_count'] ?? 0) ?></td>
          <td><span class="badge text-bg-<?= $company['status'] === 'active' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($company['status']) ?></span></td>
          <td class="text-end">
            <?php
              $cid = (int)$company['id'];
              $isActive = ($company['status'] ?? '') === 'active';
              $canManage = TenantContext::canManageCompany($cid);
            ?>
            <?php if ($isActive): ?>
              <a href="<?= BASE_URL ?>/companies/select/<?= $cid ?>" class="btn btn-sm btn-outline-primary">Seç</a>
            <?php else: ?>
              <span class="btn btn-sm btn-outline-secondary disabled" title="Pasif şirket seçilemez.">Seç</span>
            <?php endif; ?>
            <?php if (empty($company['is_default']) && $isActive): ?>
              <a href="<?= BASE_URL ?>/companies/setDefault/<?= $cid ?>" class="btn btn-sm btn-outline-warning">Varsayılan Yap</a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/companies/periods/<?= $cid ?>" class="btn btn-sm btn-outline-secondary">Dönemler</a>
            <?php if ($canManage): ?>
              <a href="<?= BASE_URL ?>/companies/edit/<?= $cid ?>" class="btn btn-sm btn-outline-dark">Düzenle</a>
              <?php if ($isActive): ?>
                <a href="#" class="btn btn-sm btn-outline-danger" onclick="return nymPost('<?= BASE_URL ?>/companies/delete/<?= $cid ?>', 'Şirket pasife alınsın mı?')">Pasife Al</a>
              <?php endif; ?>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
