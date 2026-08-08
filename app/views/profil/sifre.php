<?php
/** @var array $user */
/** @var array $flash */
$h = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);
?>
<style>
  .pass-shell{max-width:620px;margin:0 auto}
  .pass-card{background:var(--card-bg);border:1px solid var(--border);border-radius:16px;box-shadow:0 12px 34px rgba(8,69,38,.08);overflow:hidden}
  .pass-head{padding:24px;background:linear-gradient(135deg,var(--accent2),var(--accent) 58%,var(--accent2));color:#fff}
  .pass-head h3{font-size:20px;font-weight:900;margin:0}
  .pass-head p{font-size:13px;color:rgba(255,255,255,.78);margin:5px 0 0}
  .pass-form{padding:24px;display:grid;gap:14px}
  .pass-field{display:flex;flex-direction:column;gap:6px}
  .pass-field label{font-size:12.5px;font-weight:800;color:var(--text2)}
  .pass-input{width:100%;border:1.5px solid var(--border);border-radius:10px;padding:12px;font:inherit;font-size:13.5px;color:var(--text);outline:none;background:var(--card-bg);transition:border-color .15s,box-shadow .15s}
  .pass-input:focus{border-color:var(--accent);box-shadow:0 0 0 4px rgba(30,140,85,.12)}
  .pass-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:6px;flex-wrap:wrap}
  .pass-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:0;border-radius:10px;padding:11px 16px;font-size:13px;font-weight:800;text-decoration:none;cursor:pointer}
  .pass-btn.primary{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;box-shadow:0 8px 20px rgba(13,98,58,.25)}
  .pass-btn.ghost{background:var(--card-bg);color:var(--accent2);border:1.5px solid var(--border)}
  .pass-alert{border-radius:12px;padding:12px 14px;margin-bottom:16px;font-size:13px;font-weight:700}
  .pass-alert.success{background:rgba(46,204,113,.15);color:var(--success);border:1px solid rgba(46,204,113,.28)}
  .pass-alert.danger{background:rgba(231,76,60,.15);color:var(--danger);border:1px solid rgba(231,76,60,.28)}
  @media(max-width:560px){.pass-form{padding:16px}.pass-actions{flex-direction:column}.pass-btn{width:100%}}
</style>

<div class="pass-shell">
  <?php if (!empty($flash)): ?>
    <div class="pass-alert <?= ($flash['tip'] ?? '') === 'success' ? 'success' : 'danger' ?>">
      <?= $h($flash['mesaj'] ?? '') ?>
    </div>
  <?php endif; ?>

  <section class="pass-card">
    <div class="pass-head">
      <h3>Şifre Değiştir</h3>
      <p><?= $h($user['full_name'] ?? '') ?> hesabı için yeni bir şifre belirleyin.</p>
    </div>
    <form class="pass-form" method="post" action="<?= BASE_URL ?>/profil/sifre-guncelle" autocomplete="off">
      <div class="pass-field">
        <label for="current_password">Mevcut Şifre</label>
        <input class="pass-input" id="current_password" name="current_password" type="password" required>
      </div>
      <div class="pass-field">
        <label for="new_password">Yeni Şifre</label>
        <input class="pass-input" id="new_password" name="new_password" type="password" minlength="6" required>
      </div>
      <div class="pass-field">
        <label for="confirm_password">Yeni Şifre Tekrar</label>
        <input class="pass-input" id="confirm_password" name="confirm_password" type="password" minlength="6" required>
      </div>
      <div class="pass-actions">
        <a class="pass-btn ghost" href="<?= BASE_URL ?>/profil">Hesabıma Dön</a>
        <button class="pass-btn primary" type="submit">
          <i class="fa-solid fa-key"></i> Şifreyi Güncelle
        </button>
      </div>
    </form>
  </section>
</div>
