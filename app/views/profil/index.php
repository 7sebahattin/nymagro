<?php
/** @var array $user */
/** @var array|null $activeCompany */
/** @var array|null $activePeriod */
/** @var array $flash */
$h = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);
$roleLabels = [
    'super_admin' => 'Süper Yönetici',
    'admin' => 'Yönetici',
    'accountant' => 'Muhasebe',
    'user' => 'Kullanıcı',
];
$statusLabels = ['active' => 'Aktif', 'passive' => 'Pasif'];
$avatar = trim((string)($user['avatar_path'] ?? ''));
$initial = strtoupper(substr((string)($user['full_name'] ?: $user['username'] ?: 'K'), 0, 1));
?>
<style>
  .profile-shell{display:grid;grid-template-columns:320px minmax(0,1fr);gap:18px;align-items:start}
  .profile-card{background:var(--card-bg);border:1px solid var(--border);border-radius:16px;box-shadow:0 12px 34px rgba(8,69,38,.08);overflow:hidden}
  .profile-hero{background:linear-gradient(135deg,var(--accent2),var(--accent) 58%,var(--accent2));padding:26px;color:#fff;position:relative}
  .profile-hero::after{content:'';position:absolute;right:-50px;bottom:-70px;width:180px;height:180px;border-radius:50%;background:rgba(255,255,255,.14)}
  .profile-avatar{width:112px;height:112px;border-radius:24px;background:rgba(255,255,255,.18);border:3px solid rgba(255,255,255,.48);display:flex;align-items:center;justify-content:center;font-size:42px;font-weight:900;overflow:hidden;box-shadow:0 18px 38px rgba(0,0,0,.18);position:relative;z-index:1}
  .profile-avatar img{width:100%;height:100%;object-fit:cover}
  .profile-name{font-size:20px;font-weight:900;line-height:1.2;margin-top:16px;position:relative;z-index:1}
  .profile-sub{font-size:12.5px;color:rgba(255,255,255,.78);margin-top:4px;position:relative;z-index:1}
  .profile-meta{padding:18px;display:grid;gap:10px}
  .profile-kv{display:flex;justify-content:space-between;gap:12px;padding:10px 0;border-bottom:1px solid rgba(46,204,113,.18);font-size:13px}
  .profile-kv:last-child{border-bottom:0}
  .profile-kv span{color:var(--muted)}
  .profile-kv b{color:var(--text);text-align:right;overflow-wrap:anywhere}
  .profile-form{padding:22px}
  .profile-head{display:flex;justify-content:space-between;gap:14px;align-items:flex-start;margin-bottom:18px}
  .profile-head h3{font-size:18px;font-weight:900;color:var(--text);margin:0}
  .profile-head p{font-size:12.5px;color:var(--muted);margin:4px 0 0}
  .profile-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
  .profile-field{display:flex;flex-direction:column;gap:6px}
  .profile-field.full{grid-column:1/-1}
  .profile-field label{font-size:12.5px;font-weight:800;color:var(--text2)}
  .profile-input,.profile-textarea{width:100%;border:1.5px solid var(--border);border-radius:10px;padding:11px 12px;font:inherit;font-size:13.5px;color:var(--text);outline:none;background:var(--card-bg);transition:border-color .15s,box-shadow .15s}
  .profile-textarea{min-height:96px;resize:vertical}
  .profile-input:focus,.profile-textarea:focus{border-color:var(--accent);box-shadow:0 0 0 4px rgba(30,140,85,.12)}
  .profile-upload{display:flex;gap:12px;align-items:center;padding:14px;border:1px dashed var(--text2);border-radius:12px;background:rgba(46,204,113,.10)}
  .profile-upload input{font-size:12.5px;max-width:100%}
  .profile-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:18px;flex-wrap:wrap}
  .profile-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:0;border-radius:10px;padding:11px 16px;font-size:13px;font-weight:800;text-decoration:none;cursor:pointer}
  .profile-btn.primary{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;box-shadow:0 8px 20px rgba(13,98,58,.25)}
  .profile-btn.ghost{background:var(--card-bg);color:var(--accent2);border:1.5px solid var(--border)}
  .profile-btn.danger{background:rgba(231,76,60,.15);color:var(--danger)}
  .profile-alert{border-radius:12px;padding:12px 14px;margin-bottom:16px;font-size:13px;font-weight:700}
  .profile-alert.success{background:rgba(46,204,113,.15);color:var(--success);border:1px solid rgba(46,204,113,.28)}
  .profile-alert.danger{background:rgba(231,76,60,.15);color:var(--danger);border:1px solid rgba(231,76,60,.28)}
  @media(max-width:900px){.profile-shell{grid-template-columns:1fr}.profile-hero{display:flex;gap:16px;align-items:center}.profile-name{margin-top:0}.profile-avatar{width:86px;height:86px;border-radius:18px;font-size:32px}}
  @media(max-width:560px){.profile-form{padding:16px}.profile-grid{grid-template-columns:1fr}.profile-head{flex-direction:column}.profile-actions{flex-direction:column}.profile-btn{width:100%}.profile-upload{flex-direction:column;align-items:flex-start}.profile-hero{padding:20px}.profile-kv{flex-direction:column;gap:2px}.profile-kv b{text-align:left}}
</style>

<?php if (!empty($flash)): ?>
  <div class="profile-alert <?= ($flash['tip'] ?? '') === 'success' ? 'success' : 'danger' ?>">
    <?= $h($flash['mesaj'] ?? '') ?>
  </div>
<?php endif; ?>

<div class="profile-shell">
  <aside class="profile-card">
    <div class="profile-hero">
      <div class="profile-avatar">
        <?php if ($avatar !== ''): ?>
          <img src="<?= $h($avatar) ?>" alt="<?= $h($user['full_name']) ?>">
        <?php else: ?>
          <?= $h($initial) ?>
        <?php endif; ?>
      </div>
      <div>
        <div class="profile-name"><?= $h($user['full_name']) ?></div>
        <div class="profile-sub"><?= $h($user['title'] ?: ($roleLabels[$user['role'] ?? ''] ?? 'Kullanıcı')) ?></div>
      </div>
    </div>
    <div class="profile-meta">
      <div class="profile-kv"><span>Kullanıcı adı</span><b><?= $h($user['username']) ?></b></div>
      <div class="profile-kv"><span>E-posta</span><b><?= $h($user['email'] ?: '-') ?></b></div>
      <div class="profile-kv"><span>Telefon</span><b><?= $h($user['phone'] ?: '-') ?></b></div>
      <div class="profile-kv"><span>Rol</span><b><?= $h($roleLabels[$user['role'] ?? ''] ?? $user['role']) ?></b></div>
      <div class="profile-kv"><span>Durum</span><b><?= $h($statusLabels[$user['status'] ?? ''] ?? $user['status']) ?></b></div>
      <div class="profile-kv"><span>Aktif şirket</span><b><?= $h($activeCompany['company_name'] ?? '-') ?></b></div>
      <div class="profile-kv"><span>Aktif dönem</span><b><?= $h($activePeriod['period_name'] ?? '-') ?></b></div>
      <div class="profile-kv"><span>Son giriş</span><b><?= $h($user['last_login_at'] ?? '-') ?></b></div>
      <div class="profile-kv"><span>Kayıt tarihi</span><b><?= $h($user['created_at'] ?? '-') ?></b></div>
    </div>
  </aside>

  <section class="profile-card">
    <form class="profile-form" method="post" action="<?= BASE_URL ?>/profil/guncelle" enctype="multipart/form-data">
      <div class="profile-head">
        <div>
          <h3>Hesap Bilgileri</h3>
          <p>Kişisel bilgilerinizi, iletişim bilgilerinizi ve profil fotoğrafınızı güncelleyin.</p>
        </div>
        <a class="profile-btn ghost" href="<?= BASE_URL ?>/profil/sifre-degistir">
          <i class="fa-solid fa-lock"></i> Şifre Değiştir
        </a>
      </div>

      <div class="profile-field full">
        <label>Profil Fotoğrafı</label>
        <div class="profile-upload">
          <input type="file" name="avatar" accept="image/png,image/jpeg,image/webp,image/gif">
          <span style="font-size:12px;color:var(--muted);">JPG, PNG, WEBP veya GIF. En fazla 3 MB.</span>
          <?php if ($avatar !== ''): ?>
            <a class="profile-btn danger" href="<?= BASE_URL ?>/profil/avatar-sil" onclick="return confirm('Profil fotoğrafı kaldırılsın mı?')">
              <i class="fa-solid fa-trash"></i> Fotoğrafı Kaldır
            </a>
          <?php endif; ?>
        </div>
      </div>

      <div class="profile-grid">
        <div class="profile-field">
          <label for="full_name">Ad Soyad *</label>
          <input class="profile-input" id="full_name" name="full_name" value="<?= $h($user['full_name']) ?>" required>
        </div>
        <div class="profile-field">
          <label for="username">Kullanıcı Adı *</label>
          <input class="profile-input" id="username" name="username" value="<?= $h($user['username']) ?>" required>
        </div>
        <div class="profile-field">
          <label for="email">E-posta</label>
          <input class="profile-input" id="email" name="email" type="email" value="<?= $h($user['email']) ?>">
        </div>
        <div class="profile-field">
          <label for="phone">Telefon</label>
          <input class="profile-input" id="phone" name="phone" value="<?= $h($user['phone']) ?>">
        </div>
        <div class="profile-field full">
          <label for="title">Unvan</label>
          <input class="profile-input" id="title" name="title" value="<?= $h($user['title']) ?>" placeholder="Örn. Finans Yöneticisi">
        </div>
        <div class="profile-field full">
          <label for="bio">Kısa Not</label>
          <textarea class="profile-textarea" id="bio" name="bio" placeholder="Hesabınızla ilgili kısa bir not yazabilirsiniz."><?= $h($user['bio']) ?></textarea>
        </div>
      </div>

      <div class="profile-actions">
        <a class="profile-btn ghost" href="<?= BASE_URL ?>/dashboard">Vazgeç</a>
        <button class="profile-btn primary" type="submit">
          <i class="fa-solid fa-floppy-disk"></i> Güncelle
        </button>
      </div>
    </form>
  </section>
</div>
