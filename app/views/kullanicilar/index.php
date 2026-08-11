<?php
$h = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$flash = $flash ?? [];
$kayitlar = $kayitlar ?? [];
$roller = $roller ?? [];
$filters = $filters ?? [];
$statusBadge = ['active' => ['Aktif', 'success'], 'passive' => ['Pasif', 'secondary'], 'locked' => ['Kilitli', 'danger']];
$canCreate = Rbac::currentUserCan('USER_CREATE');
$canUpdate = Rbac::currentUserCan('USER_UPDATE');
$canDisable = Rbac::currentUserCan('USER_DISABLE');
$canResetPw = Rbac::currentUserCan('USER_PASSWORD_RESET');
$canDelete = Rbac::currentUserCan('USER_DELETE');
$meId = AuthGuard::userId();
?>
<?php if (!empty($flash)): ?>
  <div class="alert alert-<?= $h($flash['tip'] === 'danger' || $flash['tip'] === 'error' ? 'danger' : 'success') ?>"><?= $h($flash['mesaj'] ?? '') ?></div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <form class="d-flex gap-2 flex-wrap" method="get" action="<?= BASE_URL ?>/kullanicilar">
    <input type="text" name="q" class="form-control form-control-sm" style="min-width:200px" placeholder="Ad, kullanıcı adı veya e-posta ara" value="<?= $h($filters['q'] ?? '') ?>">
    <select name="role_id" class="form-select form-select-sm" style="min-width:160px">
      <option value="">Tüm roller</option>
      <?php foreach ($roller as $r): ?>
        <option value="<?= (int)$r['id'] ?>" <?= (int)($filters['role_id'] ?? 0) === (int)$r['id'] ? 'selected' : '' ?>><?= $h($r['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="status" class="form-select form-select-sm" style="min-width:140px">
      <option value="">Tüm durumlar</option>
      <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Aktif</option>
      <option value="passive" <?= ($filters['status'] ?? '') === 'passive' ? 'selected' : '' ?>>Pasif</option>
      <option value="locked" <?= ($filters['status'] ?? '') === 'locked' ? 'selected' : '' ?>>Kilitli</option>
    </select>
    <button class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-filter"></i> Filtrele</button>
  </form>
  <?php if ($canCreate): ?>
    <a href="<?= BASE_URL ?>/kullanicilar/ekle" class="btn btn-sm btn-success"><i class="fa-solid fa-user-plus"></i> Yeni Kullanıcı</a>
  <?php endif; ?>
</div>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Ad Soyad</th><th>Kullanıcı Adı</th><th>E-posta</th><th>Rol</th><th>Durum</th>
          <th>Son Giriş</th><th>Oluşturulma</th><th class="text-end">İşlemler</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($kayitlar)): ?>
        <tr><td colspan="8" class="text-center text-muted py-4">Kayıt bulunamadı.</td></tr>
      <?php endif; ?>
      <?php foreach ($kayitlar as $u): [$label, $cls] = $statusBadge[$u['status']] ?? ['?', 'secondary']; ?>
        <tr>
          <td class="fw-semibold"><?= $h($u['full_name']) ?><?= (int)$u['id'] === $meId ? ' <span class="badge bg-info-subtle text-info-emphasis">Siz</span>' : '' ?></td>
          <td><?= $h($u['username']) ?></td>
          <td><?= $h($u['email'] ?: '-') ?></td>
          <td><?= $h($u['role_name'] ?: $u['role_code']) ?></td>
          <td><span class="badge bg-<?= $cls ?>"><?= $label ?></span></td>
          <td><?= $u['last_login_at'] ? $h(date('d.m.Y H:i', strtotime($u['last_login_at']))) : '-' ?></td>
          <td><?= $h(date('d.m.Y', strtotime($u['created_at']))) ?></td>
          <td class="text-end text-nowrap">
            <div class="btn-group btn-group-sm">
              <a href="<?= BASE_URL ?>/kullanicilar/<?= (int)$u['id'] ?>/aktivite" class="btn btn-outline-secondary" title="Aktivite Geçmişi"><i class="fa-solid fa-clock-rotate-left"></i></a>
              <a href="<?= BASE_URL ?>/kullanicilar/<?= (int)$u['id'] ?>/giris-gecmisi" class="btn btn-outline-secondary" title="Giriş Geçmişi"><i class="fa-solid fa-right-to-bracket"></i></a>
              <?php if ($canUpdate): ?>
                <a href="<?= BASE_URL ?>/kullanicilar/<?= (int)$u['id'] ?>/duzenle" class="btn btn-outline-primary" title="Düzenle"><i class="fa-solid fa-pen"></i></a>
              <?php endif; ?>
              <?php if ($canResetPw): ?>
                <a href="<?= BASE_URL ?>/kullanicilar/<?= (int)$u['id'] ?>/sifre-sifirla" class="btn btn-outline-warning" title="Şifre Sıfırla"><i class="fa-solid fa-key"></i></a>
              <?php endif; ?>
              <?php if ($canDisable && (int)$u['id'] !== $meId): ?>
                <?php if ($u['status'] === 'active'): ?>
                  <form method="post" action="<?= BASE_URL ?>/kullanicilar/<?= (int)$u['id'] ?>/durum" class="d-inline" onsubmit="return confirm('<?= $h($u['username']) ?> pasifleştirilsin mi?')">
                    <input type="hidden" name="status" value="passive">
                    <button class="btn btn-outline-secondary" title="Pasifleştir"><i class="fa-solid fa-user-slash"></i></button>
                  </form>
                <?php else: ?>
                  <form method="post" action="<?= BASE_URL ?>/kullanicilar/<?= (int)$u['id'] ?>/durum" class="d-inline">
                    <input type="hidden" name="status" value="active">
                    <button class="btn btn-outline-success" title="Aktifleştir"><i class="fa-solid fa-user-check"></i></button>
                  </form>
                <?php endif; ?>
              <?php endif; ?>
              <?php if ($canDelete && $u['status'] === 'passive' && (int)$u['id'] !== $meId): ?>
                <button type="button" class="btn btn-outline-danger" title="Kalıcı Sil" data-bs-toggle="modal" data-bs-target="#silModal<?= (int)$u['id'] ?>"><i class="fa-solid fa-trash"></i></button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php if ($canDelete && $u['status'] === 'passive' && (int)$u['id'] !== $meId): ?>
        <div class="modal fade" id="silModal<?= (int)$u['id'] ?>" tabindex="-1">
          <div class="modal-dialog">
            <div class="modal-content">
              <form method="post" action="<?= BASE_URL ?>/kullanicilar/<?= (int)$u['id'] ?>/sil">
                <div class="modal-header"><h5 class="modal-title">Kullanıcıyı Kalıcı Sil</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                  <p><strong><?= $h($u['full_name']) ?></strong> (<?= $h($u['username']) ?>, #<?= (int)$u['id'] ?>) kalıcı olarak silinecek. Bu işlem geri alınamaz; kullanıcının audit geçmişi korunur ama hesabı tamamen kaldırılır.</p>
                  <label class="form-label">Onaylamak için kullanıcı adını yazın: <code><?= $h($u['username']) ?></code></label>
                  <input type="text" name="confirm_username" class="form-control" required autocomplete="off">
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Vazgeç</button>
                  <button type="submit" class="btn btn-danger">Kalıcı Sil</button>
                </div>
              </form>
            </div>
          </div>
        </div>
        <?php endif; ?>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if (($sayfaSayisi ?? 1) > 1): ?>
<nav class="mt-3">
  <ul class="pagination pagination-sm justify-content-center">
    <?php for ($p = 1; $p <= $sayfaSayisi; $p++): $qs = array_merge($filters, ['sayfa' => $p]); ?>
      <li class="page-item <?= $p === $sayfa ? 'active' : '' ?>">
        <a class="page-link" href="?<?= http_build_query($qs) ?>"><?= $p ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>
