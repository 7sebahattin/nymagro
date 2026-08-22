<?php
$h = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$flash = $flash ?? [];
$kullanici = $kullanici ?? null;
$roller = $roller ?? [];
$isEdit = $kullanici !== null;
$meId = AuthGuard::userId();
$isSelf = $isEdit && (int)$kullanici['id'] === $meId;
$action = $isEdit ? (BASE_URL . '/kullanicilar/' . (int)$kullanici['id'] . '/guncelle') : (BASE_URL . '/kullanicilar/kaydet');
?>
<?php if (!empty($flash)): ?>
  <div class="alert alert-<?= $h($flash['tip'] === 'danger' || $flash['tip'] === 'error' ? 'danger' : 'success') ?>"><?= $h($flash['mesaj'] ?? '') ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
  <div class="card-body p-4">
    <form method="post" action="<?= $action ?>" class="row g-3">
      <?= Csrf::fieldHtml() ?>
      <div class="col-md-6">
        <label class="form-label">Ad Soyad *</label>
        <input type="text" name="full_name" class="form-control" required value="<?= $h($kullanici['full_name'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Kullanıcı Adı *</label>
        <input type="text" name="username" class="form-control" required pattern="[A-Za-z0-9_.\-]{3,80}" value="<?= $h($kullanici['username'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">E-posta</label>
        <input type="email" name="email" class="form-control" value="<?= $h($kullanici['email'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Telefon</label>
        <input type="text" name="phone" class="form-control" value="<?= $h($kullanici['phone'] ?? '') ?>">
      </div>
      <?php if ($isEdit): ?>
        <div class="col-md-6">
          <label class="form-label">Unvan</label>
          <input type="text" name="title" class="form-control" value="<?= $h($kullanici['title'] ?? '') ?>">
        </div>
      <?php endif; ?>

      <div class="col-md-6">
        <label class="form-label">Rol *</label>
        <select name="role_id" class="form-select" required <?= $isSelf ? 'disabled' : '' ?>>
          <?php foreach ($roller as $r): ?>
            <option value="<?= (int)$r['id'] ?>" <?= $isEdit && (int)$kullanici['role_id'] === (int)$r['id'] ? 'selected' : '' ?>><?= $h($r['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if ($isSelf): ?>
          <input type="hidden" name="role_id" value="<?= (int)$kullanici['role_id'] ?>">
          <div class="form-text text-warning">Kendi rolünüzü değiştiremezsiniz.</div>
        <?php endif; ?>
      </div>

      <?php if (!$isEdit): ?>
        <div class="col-md-6">
          <label class="form-label">Durum</label>
          <select name="status" class="form-select">
            <option value="active">Aktif</option>
            <option value="passive">Pasif</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Şifre *</label>
          <input type="password" name="password" class="form-control" required minlength="10" autocomplete="new-password">
        </div>
        <div class="col-md-6">
          <label class="form-label">Şifre Tekrar *</label>
          <input type="password" name="password_confirm" class="form-control" required minlength="10" autocomplete="new-password">
        </div>
        <div class="col-12">
          <label class="form-label">Açıklama</label>
          <textarea name="note" class="form-control" rows="2"></textarea>
        </div>
      <?php endif; ?>

      <div class="col-12">
        <button type="submit" class="btn btn-success"><i class="fa-solid fa-check"></i> Kaydet</button>
        <a href="<?= BASE_URL ?>/kullanicilar" class="btn btn-outline-secondary">Vazgeç</a>
      </div>
    </form>
  </div>
</div>
