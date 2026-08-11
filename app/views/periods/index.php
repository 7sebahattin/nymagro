<?php $flash = $flash ?? []; ?>
<?php if (!empty($flash)): ?>
  <div class="alert alert-<?= htmlspecialchars($flash['tip'] === 'error' ? 'danger' : $flash['tip']) ?>"><?= htmlspecialchars($flash['mesaj']) ?></div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 mb-1"><?= htmlspecialchars($company['company_name'] ?? '') ?> - Dönemler</h1>
    <div class="text-muted small">Açık dönemlerde işlem yapılır; kilitli/kapalı dönemler okuma modundadır.</div>
  </div>
  <a href="<?= BASE_URL ?>/periods/create?company_id=<?= (int)$company['id'] ?>" class="btn btn-success"><i class="fa-solid fa-plus me-1"></i> Yeni Dönem</a>
</div>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Dönem</th>
          <th>Yıl</th>
          <th>Başlangıç</th>
          <th>Bitiş</th>
          <th>Durum</th>
          <th>Aktif</th>
          <th class="text-end">İşlem</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($periods as $period): ?>
        <tr>
          <td class="fw-semibold"><?= htmlspecialchars($period['period_name']) ?></td>
          <td><?= (int)$period['fiscal_year'] ?></td>
          <td><?= htmlspecialchars($period['start_date']) ?></td>
          <td><?= htmlspecialchars($period['end_date']) ?></td>
          <td><span class="badge text-bg-<?= $period['status'] === 'open' ? 'success' : ($period['status'] === 'locked' ? 'warning' : 'secondary') ?>"><?= htmlspecialchars($period['status']) ?></span></td>
          <td><?= (int)$period['is_active'] === 1 ? 'Evet' : 'Hayır' ?></td>
          <td class="text-end">
            <a href="<?= BASE_URL ?>/companies/select_period/<?= (int)$period['id'] ?>" class="btn btn-sm btn-outline-primary">Seç</a>
            <a href="<?= BASE_URL ?>/periods/edit/<?= (int)$period['id'] ?>" class="btn btn-sm btn-outline-dark">Düzenle</a>
            <a href="<?= BASE_URL ?>/periods/close_summary/<?= (int)$period['id'] ?>" class="btn btn-sm btn-outline-secondary">Kapama Özeti</a>
            <?php if ($period['status'] === 'open'): ?>
              <a href="#" class="btn btn-sm btn-outline-warning" onclick="return nymPost('<?= BASE_URL ?>/periods/lock/<?= (int)$period['id'] ?>', 'Dönem kilitlensin mi?')">Kilitle</a>
              <a href="#" class="btn btn-sm btn-outline-danger" onclick="return nymPost('<?= BASE_URL ?>/periods/close/<?= (int)$period['id'] ?>', 'Dönem kapatılsın mı?')">Kapat</a>
            <?php elseif ($period['status'] !== 'archived'): ?>
              <a href="#" class="btn btn-sm btn-outline-success" onclick="return nymPost('<?= BASE_URL ?>/periods/reopen/<?= (int)$period['id'] ?>', 'Dönem yeniden açılsın mı?')">Yeniden Aç</a>
            <?php endif; ?>
            <?php if ($period['status'] !== 'archived'): ?>
              <a href="#" class="btn btn-sm btn-outline-secondary" onclick="return nymPost('<?= BASE_URL ?>/periods/archive/<?= (int)$period['id'] ?>', 'Dönem arşivlensin mi?')">Arşivle</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
