<?php
$h = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$flash = $flash ?? [];
$rol = $rol ?? null;
$katalog = $katalog ?? [];
$isEdit = $rol !== null;
$checkedCodes = $isEdit ? ($rol['permission_codes'] ?? []) : [];
$action = $isEdit ? (BASE_URL . '/roller/' . (int)$rol['id'] . '/guncelle') : (BASE_URL . '/roller/kaydet');

$byModule = [];
foreach ($katalog as $p) {
    $byModule[$p['module']][$p['action']] = $p;
}
$actionOrder = ['VIEW' => 'Görüntüleme', 'CREATE' => 'Ekleme', 'UPDATE' => 'Düzenleme', 'DELETE' => 'Silme'];
$moduleLabels = Rbac::moduleLabels();
?>
<?php if (!empty($flash)): ?>
  <div class="alert alert-<?= $h($flash['tip'] === 'danger' || $flash['tip'] === 'error' ? 'danger' : 'success') ?>"><?= $h($flash['mesaj'] ?? '') ?></div>
<?php endif; ?>

<form method="post" action="<?= $action ?>">
  <?= Csrf::fieldHtml() ?>
  <div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-4 row g-3">
      <div class="col-md-6">
        <label class="form-label">Rol Adı *</label>
        <input type="text" name="name" class="form-control" required minlength="3" value="<?= $h($rol['name'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Açıklama</label>
        <input type="text" name="description" class="form-control" value="<?= $h($rol['description'] ?? '') ?>">
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent"><strong>Modül İzinleri</strong>
      <span class="text-muted small">— bir modülde "Silme" izni olmayan kullanıcılar o modülde kayıt silemez (backend de bunu zorunlu kılar).</span>
    </div>
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Modül</th>
            <?php foreach ($actionOrder as $act => $lbl): ?><th class="text-center"><?= $lbl ?></th><?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($byModule as $moduleCode => $actions): ?>
          <tr>
            <td class="fw-semibold"><?= $h($moduleLabels[$moduleCode] ?? $moduleCode) ?></td>
            <?php foreach ($actionOrder as $act => $lbl): ?>
              <td class="text-center">
                <?php if (isset($actions[$act])): $code = $actions[$act]['code']; ?>
                  <input type="checkbox" class="form-check-input" name="permissions[<?= $h($code) ?>]" value="1"
                         <?= in_array($code, $checkedCodes, true) ? 'checked' : '' ?>>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-3">
    <button type="submit" class="btn btn-success"><i class="fa-solid fa-check"></i> Kaydet</button>
    <a href="<?= BASE_URL ?>/roller" class="btn btn-outline-secondary">Vazgeç</a>
  </div>
</form>
