<?php
$period = $period ?? [];
$flash = $flash ?? [];
$v = fn(string $key, string $default = '') => htmlspecialchars((string)($period[$key] ?? $default));
?>
<?php if (!empty($flash)): ?>
  <div class="alert alert-<?= htmlspecialchars($flash['tip'] === 'error' ? 'danger' : $flash['tip']) ?>"><?= htmlspecialchars($flash['mesaj']) ?></div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars($action) ?>" class="card border-0 shadow-sm">
  <div class="card-body row g-3">
    <input type="hidden" name="company_id" value="<?= (int)($period['company_id'] ?? $company['id']) ?>">
    <div class="col-12 d-flex justify-content-between align-items-center">
      <div>
        <h1 class="h4 mb-1"><?= htmlspecialchars($company['company_name'] ?? '') ?></h1>
        <div class="text-muted small"><?= !empty($period['id']) ? 'Dönem düzenle' : 'Yeni dönem oluştur' ?></div>
      </div>
      <button class="btn btn-success"><i class="fa-solid fa-floppy-disk me-1"></i> Kaydet</button>
    </div>
    <div class="col-md-3">
      <label class="form-label">Dönem adı</label>
      <input name="period_name" class="form-control" required value="<?= $v('period_name') ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Mali yıl</label>
      <input name="fiscal_year" type="number" class="form-control" required value="<?= $v('fiscal_year', date('Y')) ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Başlangıç</label>
      <input name="start_date" type="date" class="form-control" required value="<?= $v('start_date') ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Bitiş</label>
      <input name="end_date" type="date" class="form-control" required value="<?= $v('end_date') ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Durum</label>
      <select name="status" class="form-select">
        <?php foreach (['open' => 'Açık', 'locked' => 'Kilitli', 'closed' => 'Kapalı', 'archived' => 'Arşiv'] as $key => $label): ?>
          <option value="<?= $key ?>" <?= ($period['status'] ?? 'open') === $key ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3 d-flex align-items-end">
      <div class="form-check mb-2">
        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" <?= (int)($period['is_active'] ?? 1) === 1 ? 'checked' : '' ?>>
        <label class="form-check-label" for="is_active">Aktif dönem</label>
      </div>
    </div>
    <div class="col-12">
      <label class="form-label">Notlar</label>
      <textarea name="notes" class="form-control" rows="3"><?= $v('notes') ?></textarea>
    </div>
  </div>
</form>
