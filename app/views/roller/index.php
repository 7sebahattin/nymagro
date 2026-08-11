<?php
$h = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$flash = $flash ?? [];
$kayitlar = $kayitlar ?? [];
$canCreate = Rbac::currentUserCan('ROLE_CREATE');
$canUpdate = Rbac::currentUserCan('ROLE_UPDATE');
$canDelete = Rbac::currentUserCan('ROLE_DELETE');
?>
<?php if (!empty($flash)): ?>
  <div class="alert alert-<?= $h($flash['tip'] === 'danger' || $flash['tip'] === 'error' ? 'danger' : 'success') ?>"><?= $h($flash['mesaj'] ?? '') ?></div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <p class="text-muted mb-0">Roller kullanıcılara atanan izin gruplarıdır. "Süper Yönetici" sistem rolüdür, değiştirilemez/silinemez.</p>
  <?php if ($canCreate): ?>
    <a href="<?= BASE_URL ?>/roller/ekle" class="btn btn-sm btn-success"><i class="fa-solid fa-plus"></i> Yeni Rol</a>
  <?php endif; ?>
</div>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light"><tr><th>Rol Adı</th><th>Açıklama</th><th>Kullanıcı Sayısı</th><th>Tip</th><th class="text-end">İşlemler</th></tr></thead>
      <tbody>
      <?php foreach ($kayitlar as $r): ?>
        <tr>
          <td class="fw-semibold"><?= $h($r['name']) ?></td>
          <td class="text-muted"><?= $h($r['description'] ?: '-') ?></td>
          <td><?= (int)$r['user_count'] ?></td>
          <td><?= (int)$r['is_system'] ? '<span class="badge bg-primary">Sistem</span>' : '<span class="badge bg-light text-dark border">Özel</span>' ?></td>
          <td class="text-end">
            <?php if (!(int)$r['is_system']): ?>
              <?php if ($canUpdate): ?>
                <a href="<?= BASE_URL ?>/roller/<?= (int)$r['id'] ?>/duzenle" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
              <?php endif; ?>
              <?php if ($canDelete): ?>
                <form method="post" action="<?= BASE_URL ?>/roller/<?= (int)$r['id'] ?>/sil" class="d-inline" onsubmit="return confirm('<?= $h($r['name']) ?> rolü silinsin mi?')">
                  <?= Csrf::fieldHtml() ?>
                  <button class="btn btn-sm btn-outline-danger" <?= (int)$r['user_count'] > 0 ? 'disabled title="Bu rolde kullanıcı var"' : '' ?>><i class="fa-solid fa-trash"></i></button>
                </form>
              <?php endif; ?>
            <?php else: ?>
              <span class="text-muted small">—</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
