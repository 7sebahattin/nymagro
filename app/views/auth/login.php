<?php /** @var string|null $error */ /** @var string $username */ /** @var string $next */ ?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="robots" content="noindex, nofollow">
  <title>Giriş — <?= htmlspecialchars(APP_NAME) ?></title>

  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="theme-color" content="#0d0d1a">
  <link rel="icon" href="<?= varlik('/favicon.ico') ?>" sizes="any">
  <link rel="icon" type="image/png" sizes="32x32" href="<?= varlik('/favicon-32x32.png') ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= varlik('/apple-touch-icon.png') ?>">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="<?= varlik('/css/panel-ui.css') ?>">

  <style>
    body.auth-body {
      display: flex; align-items: center; justify-content: center;
      min-height: 100dvh; padding: 20px;
      position: relative; overflow: hidden;
      background:
        radial-gradient(ellipse at 20% 20%, rgba(233,69,96,.14) 0%, transparent 55%),
        radial-gradient(ellipse at 80% 80%, rgba(15,155,142,.09) 0%, transparent 55%),
        var(--surface);
    }

    /* Bulanık, yavaşça nabız atan dekoratif ışık topları */
    .auth-orb {
      position: absolute; border-radius: 50%; filter: blur(55px);
      animation: pulse 4s ease-in-out infinite alternate;
      pointer-events: none; z-index: 0;
    }
    .auth-orb-0 { width: 320px; height: 320px; background: rgba(233,69,96,.30); top: -80px; left: -60px;  animation-delay: 0s; }
    .auth-orb-1 { width: 260px; height: 260px; background: rgba(15,155,142,.26); bottom: -70px; right: -50px; animation-delay: .8s; }
    .auth-orb-2 { width: 190px; height: 190px; background: rgba(245,166,35,.18); top: 22%; right: 12%;    animation-delay: 1.6s; }
    .auth-orb-3 { width: 150px; height: 150px; background: rgba(233,69,96,.20); bottom: 18%; left: 10%;   animation-delay: 2.4s; }

    .auth-card {
      position: relative; z-index: 1;
      width: 100%; max-width: 400px;
      background: rgba(255,255,255,.055);
      backdrop-filter: blur(24px) saturate(180%);
      -webkit-backdrop-filter: blur(24px) saturate(180%);
      border: 1px solid rgba(255,255,255,.11);
      border-radius: 22px; padding: 36px 28px;
      box-shadow: 0 32px 64px rgba(0,0,0,.45);
      animation: fadeUp .45s cubic-bezier(.22,1,.36,1) both;
    }
    body[data-theme="acik"] .auth-card {
      background: rgba(255,255,255,.86);
      border-color: rgba(0,0,0,.08);
      box-shadow: 0 32px 64px rgba(0,0,0,.14);
    }

    .auth-logo {
      width: 54px; height: 54px; border-radius: 15px; margin: 0 auto 18px;
      background: linear-gradient(160deg, var(--brand), var(--brand2));
      display: flex; align-items: center; justify-content: center;
      color: #fff; font-size: 22px;
      box-shadow: 0 10px 26px rgba(13,98,58,.42);
    }

    .auth-title {
      font-family: 'Playfair Display', Georgia, serif;
      font-size: 28px; font-weight: 900; text-align: center; line-height: 1.2;
      background: linear-gradient(135deg, var(--text), var(--accent));
      -webkit-background-clip: text; background-clip: text;
      -webkit-text-fill-color: transparent;
      margin-bottom: 6px;
    }
    .auth-sub { text-align: center; font-size: 13px; color: var(--muted); margin-bottom: 26px; }

    .auth-row { margin-bottom: 15px; }
    .auth-row label {
      display: block; margin-bottom: 6px;
      font-size: 11px; font-weight: 700; color: var(--muted);
      text-transform: uppercase; letter-spacing: .5px;
    }
    .auth-input-wrap { position: relative; }
    .auth-input-wrap > i.lead {
      position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
      color: var(--muted); font-size: 13px; pointer-events: none;
    }
    .auth-input {
      width: 100%; padding: 13px 14px 13px 40px;
      background: var(--input-bg); border: 1px solid var(--border);
      border-radius: var(--rsm); color: var(--text);
      font-family: inherit; font-size: 16px; outline: none;
      transition: border-color .18s, background .18s;
    }
    .auth-input:focus { border-color: var(--accent); background: var(--accent-soft); }
    .auth-input::placeholder { color: var(--muted); }

    .toggle-pass {
      position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
      background: none; border: none; cursor: pointer;
      color: var(--muted); font-size: 13px;
      width: 36px; height: 36px; border-radius: 8px;
      display: flex; align-items: center; justify-content: center;
    }
    .toggle-pass:hover { color: var(--text); }

    .auth-extra {
      display: flex; justify-content: space-between; align-items: center;
      gap: 10px; margin: 6px 0 20px; flex-wrap: wrap;
    }
    .auth-remember {
      display: inline-flex; align-items: center; gap: 7px;
      font-size: 12.5px; color: var(--text2); cursor: pointer;
    }
    .auth-remember input { width: 15px; height: 15px; accent-color: var(--accent); cursor: pointer; }
    .auth-forgot { font-size: 12.5px; color: var(--accent); font-weight: 600; }
    .auth-forgot:hover { text-decoration: underline; }

    .auth-submit { width: 100%; }

    .auth-foot {
      margin-top: 22px; padding-top: 18px;
      border-top: 1px solid var(--border);
      font-size: 11.5px; color: var(--muted); text-align: center;
    }
    .auth-back {
      display: inline-flex; align-items: center; gap: 6px;
      font-size: 12px; color: var(--muted); margin-bottom: 18px;
    }
    .auth-back:hover { color: var(--text); }

    @media (max-width: 389.98px) {
      .auth-card { padding: 28px 20px; }
      .auth-title { font-size: 24px; }
    }
  </style>
</head>
<body class="auth-body" data-theme="koyu">

<div class="auth-orb auth-orb-0"></div>
<div class="auth-orb auth-orb-1"></div>
<div class="auth-orb auth-orb-2"></div>
<div class="auth-orb auth-orb-3"></div>

<div class="auth-card">
  <a class="auth-back" href="<?= BASE_URL ?>/">
    <i class="fa-solid fa-arrow-left"></i> Ana Sayfaya Dön
  </a>

  <div class="auth-logo"><i class="fa-solid fa-chart-pie"></i></div>
  <h1 class="auth-title">Hoş Geldiniz</h1>
  <p class="auth-sub">Devam etmek için hesabınıza giriş yapın.</p>

  <?php if (!empty($error)): ?>
    <div class="flash flash-error">
      <i class="fa-solid fa-circle-exclamation"></i>
      <span><?= htmlspecialchars($error) ?></span>
    </div>
  <?php endif; ?>

  <form method="post" action="<?= BASE_URL ?>/giris" autocomplete="off">
    <input type="hidden" name="next" value="<?= htmlspecialchars((string)$next) ?>">

    <div class="auth-row">
      <label for="username">Kullanıcı Adı veya E-posta</label>
      <div class="auth-input-wrap">
        <i class="fa-solid fa-user lead"></i>
        <input id="username" name="username" type="text" class="auth-input"
               value="<?= htmlspecialchars((string)($username ?? '')) ?>"
               autocomplete="username" required autofocus>
      </div>
    </div>

    <div class="auth-row">
      <label for="password">Şifre</label>
      <div class="auth-input-wrap">
        <i class="fa-solid fa-lock lead"></i>
        <input id="password" name="password" type="password" class="auth-input"
               placeholder="••••••••" autocomplete="current-password" required>
        <button type="button" class="toggle-pass" id="passToggle" title="Şifreyi göster/gizle" aria-label="Şifreyi göster/gizle">
          <i class="fa-solid fa-eye" id="passEye"></i>
        </button>
      </div>
    </div>

    <div class="auth-extra">
      <label class="auth-remember"><input type="checkbox" name="remember" value="1"> Beni hatırla</label>
      <a class="auth-forgot" href="#" onclick="return false;">Şifremi unuttum</a>
    </div>

    <button type="submit" class="btn btn-primary auth-submit">
      <i class="fa-solid fa-arrow-right-to-bracket"></i> Giriş Yap
    </button>
  </form>

  <div class="auth-foot">
    <i class="fa-solid fa-lock"></i> Bağlantınız güvenli ve şifrelidir.
  </div>
</div>

<script>
  document.getElementById('passToggle').addEventListener('click', function () {
    var i = document.getElementById('password');
    var e = document.getElementById('passEye');
    if (i.type === 'password') { i.type = 'text'; e.classList.replace('fa-eye', 'fa-eye-slash'); }
    else { i.type = 'password'; e.classList.replace('fa-eye-slash', 'fa-eye'); }
  });
</script>

</body>
</html>
