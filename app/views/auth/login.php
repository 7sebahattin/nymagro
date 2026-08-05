<?php /** @var string|null $error */ /** @var string $username */ /** @var string $next */ ?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Giriş — Nymagro</title>
  <link rel="icon" href="<?= htmlspecialchars(BASE_URL) ?>/favicon.ico?v=20260501" sizes="any">
  <link rel="icon" type="image/png" sizes="32x32" href="<?= htmlspecialchars(BASE_URL) ?>/favicon-32x32.png?v=20260501">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= htmlspecialchars(BASE_URL) ?>/apple-touch-icon.png?v=20260501">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <style>
    *,*::before,*::after { box-sizing:border-box; margin:0; padding:0; }
    body {
      font-family:'Segoe UI', system-ui, sans-serif;
      min-height:100vh;
      display:flex; align-items:center; justify-content:center;
      background:
        radial-gradient(ellipse at top right, rgba(13,98,58,.22), transparent 60%),
        radial-gradient(ellipse at bottom left, rgba(217,122,12,.18), transparent 55%),
        #0f172a;
      padding:20px;
    }

    .login-wrap { width:100%; max-width:430px; border-radius:18px; overflow:hidden; box-shadow:0 24px 60px rgba(0,0,0,.35); background:#fff; }

    /* Form tarafı */
    .login-form { padding:48px 44px; display:flex; flex-direction:column; }
    .lf-back { font-size:12px; color:#64748b; text-decoration:none; display:inline-flex; align-items:center; gap:5px; margin-bottom:22px; }
    .lf-back:hover { color:#0f172a; }

    .lf-title { font-size:22px; font-weight:700; color:#0f172a; margin-bottom:6px; }
    .lf-subtitle { font-size:13px; color:#64748b; margin-bottom:26px; }

    .lf-error {
      background:#fee2e2; color:#991b1b; padding:11px 14px; border-radius:8px;
      font-size:13px; font-weight:500; margin-bottom:18px; display:flex; align-items:center; gap:8px;
      border-left:3px solid #dc2626;
    }

    .lf-row { display:flex; flex-direction:column; gap:6px; margin-bottom:16px; }
    .lf-row label { font-size:12.5px; font-weight:600; color:#374151; }
    .lf-input-wrap { position:relative; }
    .lf-input-wrap > i { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:13px; pointer-events:none; }
    .lf-input {
      width:100%; padding:11px 14px 11px 40px;
      border:1.5px solid #e2e8f0; border-radius:9px;
      font-size:14px; color:#1e293b; outline:none;
      transition:border-color .15s, box-shadow .15s;
      background:#fff;
    }
    .lf-input:focus { border-color:#1e8c55; box-shadow:0 0 0 3px rgba(30,140,85,.14); }

    .lf-toggle-pass {
      position:absolute; right:12px; top:50%; transform:translateY(-50%);
      background:none; border:none; cursor:pointer; color:#94a3b8; font-size:13px; padding:5px;
    }
    .lf-toggle-pass:hover { color:#374151; }

    .lf-extra { display:flex; justify-content:space-between; align-items:center; margin:8px 0 22px; }
    .lf-remember { display:inline-flex; align-items:center; gap:6px; font-size:12.5px; color:#475569; }
    .lf-remember input { width:14px; height:14px; cursor:pointer; }
    .lf-forgot { font-size:12.5px; color:#0d623a; text-decoration:none; font-weight:600; }
    .lf-forgot:hover { text-decoration:underline; }

    .lf-submit {
      width:100%; padding:13px;
      background:linear-gradient(135deg,#0d623a,#d97a0c);
      color:#fff; border:none; border-radius:9px;
      font-size:14px; font-weight:700; cursor:pointer;
      display:inline-flex; align-items:center; justify-content:center; gap:8px;
      box-shadow:0 4px 14px rgba(13,98,58,.32);
      transition:transform .12s, box-shadow .15s, filter .15s;
    }
    .lf-submit:hover  { filter:brightness(1.05); box-shadow:0 6px 20px rgba(13,98,58,.42); transform:translateY(-1px); }
    .lf-submit:active { transform:translateY(0); }

    .lf-divider { display:flex; align-items:center; gap:10px; margin:20px 0; color:#94a3b8; font-size:11px; }
    .lf-divider::before, .lf-divider::after { content:''; flex:1; height:1px; background:#e2e8f0; }

    .lf-help { font-size:12px; color:#64748b; text-align:center; line-height:1.6; }
    .lf-help b { color:#0f172a; }

    .lf-foot { margin-top:auto; font-size:11.5px; color:#94a3b8; text-align:center; padding-top:24px; }
  </style>
</head>
<body>

<div class="login-wrap">

  <!-- ── Form ── -->
  <div class="login-form">
    <a class="lf-back" href="<?= BASE_URL ?>/">
      <i class="fa-solid fa-arrow-left"></i> Ana Sayfaya Dön
    </a>

    <div class="lf-title">Hoş Geldiniz</div>
    <div class="lf-subtitle">Devam etmek için hesabınıza giriş yapın.</div>

    <?php if (!empty($error)): ?>
    <div class="lf-error">
      <i class="fa-solid fa-circle-exclamation"></i>
      <span><?= htmlspecialchars($error) ?></span>
    </div>
    <?php endif; ?>

    <form method="post" action="<?= BASE_URL ?>/giris" autocomplete="off">
      <input type="hidden" name="next" value="<?= $next ?>">

      <div class="lf-row">
        <label for="username">Kullanıcı Adı veya E-posta</label>
        <div class="lf-input-wrap">
          <i class="fa-solid fa-user"></i>
          <input id="username" name="username" type="text" class="lf-input"
                 value="" autocomplete="off" required autofocus>
        </div>
      </div>

      <div class="lf-row">
        <label for="password">Şifre</label>
        <div class="lf-input-wrap">
          <i class="fa-solid fa-lock"></i>
          <input id="password" name="password" type="password" class="lf-input" placeholder="••••••••" required>
          <button type="button" class="lf-toggle-pass" onclick="togglePass()" title="Şifreyi göster/gizle">
            <i class="fa-solid fa-eye" id="passEye"></i>
          </button>
        </div>
      </div>

      <div class="lf-extra">
        <label class="lf-remember"><input type="checkbox" name="remember" value="1"> Beni hatırla</label>
        <a class="lf-forgot" href="#" onclick="return false;">Şifremi unuttum</a>
      </div>

      <button type="submit" class="lf-submit">
        <i class="fa-solid fa-arrow-right-to-bracket"></i> Giriş Yap
      </button>
    </form>



    <div class="lf-foot">
      <i class="fa-solid fa-lock"></i> Bağlantınız güvenli ve şifrelidir.
    </div>
  </div>

</div>

<script>
  function togglePass(){
    const i = document.getElementById('password');
    const e = document.getElementById('passEye');
    if (i.type === 'password') { i.type = 'text';     e.classList.replace('fa-eye','fa-eye-slash'); }
    else                       { i.type = 'password'; e.classList.replace('fa-eye-slash','fa-eye'); }
  }
</script>

</body>
</html>
