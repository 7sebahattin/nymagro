<?php $flash = $flash ?? []; ?>
<?php if (!empty($flash)): ?>
  <div class="alert alert-<?= htmlspecialchars($flash['tip'] === 'error' ? 'danger' : $flash['tip']) ?>"><?= htmlspecialchars($flash['mesaj']) ?></div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 mb-1">Şirket Yönetimi</h1>
    <div class="text-muted small">Şirketler, varsayılan ayarlar ve dönem bağlantıları.</div>
  </div>
  <a href="<?= BASE_URL ?>/companies/create" class="btn btn-success"><i class="fa-solid fa-plus me-1"></i> Yeni Şirket</a>
</div>

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
            <div class="fw-semibold"><?= htmlspecialchars($company['company_name']) ?></div>
            <div class="text-muted small"><?= htmlspecialchars($company['short_name'] ?? '') ?></div>
          </td>
          <td><?= htmlspecialchars($company['tax_number'] ?? '-') ?></td>
          <td><?= htmlspecialchars($company['city'] ?? '-') ?></td>
          <td><?= htmlspecialchars($company['currency'] ?? 'TRY') ?></td>
          <td><?= (int)($company['period_count'] ?? 0) ?></td>
          <td><span class="badge text-bg-<?= $company['status'] === 'active' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($company['status']) ?></span></td>
          <td class="text-end">
            <a href="<?= BASE_URL ?>/companies/select/<?= (int)$company['id'] ?>" class="btn btn-sm btn-outline-primary">Seç</a>
            <a href="<?= BASE_URL ?>/companies/periods/<?= (int)$company['id'] ?>" class="btn btn-sm btn-outline-secondary">Dönemler</a>
            <a href="<?= BASE_URL ?>/companies/edit/<?= (int)$company['id'] ?>" class="btn btn-sm btn-outline-dark">Düzenle</a>
            <a href="<?= BASE_URL ?>/companies/delete/<?= (int)$company['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Şirket pasife alınsın mı?')">Pasife Al</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
