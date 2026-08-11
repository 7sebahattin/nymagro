<?php
$h = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$flash = $flash ?? [];
$kullanici = $kullanici ?? [];
?>
<?php if (!empty($flash)): ?>
  <div class="alert alert-<?= $h($flash['tip'] === 'danger' || $flash['tip'] === 'error' ? 'danger' : 'success') ?>"><?= $h($flash['mesaj'] ?? '') ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm" style="max-width:480px">
  <div class="card-body p-4">
    <p class="text-muted">
      <strong><?= $h($kullanici['full_name']) ?></strong> (<?= $h($kullanici['username']) ?>) için yeni bir şifre belirleyin.
      Mevcut şifreyi göremezsiniz — sadece sıfırlayabilirsiniz. Kullanıcıya yeni şifreyi güvenli bir kanaldan iletin.
    </p>
    <form method="post" action="<?= BASE_URL ?>/kullanicilar/<?= (int)$kullanici['id'] ?>/sifre-sifirla">
      <div class="mb-3">
        <label class="form-label">Yeni Şifre *</label>
        <input type="password" name="new_password" class="form-control" required minlength="6" autocomplete="new-password">
      </div>
      <div class="mb-3">
        <label class="form-label">Yeni Şifre Tekrar *</label>
        <input type="password" name="confirm_password" class="form-control" required minlength="6" autocomplete="new-password">
      </div>
      <button type="submit" class="btn btn-warning"><i class="fa-solid fa-key"></i> Şifreyi Sıfırla</button>
      <a href="<?= BASE_URL ?>/kullanicilar" class="btn btn-outline-secondary">Vazgeç</a>
    </form>
  </div>
</div>
