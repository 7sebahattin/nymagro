<?php
/**
 * @var bool  $isLoggedIn
 * @var array $ayarlar  site_settings DB'den
 * @var array $urunler  site_urunler — aktif & sıralı
 * @var array $galeri   site_galeri — aktif & sıralı
 */
$ayarlar = $ayarlar ?? [];
$urunler = $urunler ?? [];
$galeri  = $galeri  ?? [];
$s = fn(string $k, string $def = '') => htmlspecialchars($ayarlar[$k] ?? $def, ENT_QUOTES);

$i18nOverrides = [];
foreach ($ayarlar as $key => $value) {
    if (preg_match('/^i18n_(tr|en|ru)_(.+)$/', (string)$key, $m) && trim((string)$value) !== '') {
        $i18nOverrides[$m[1]][$m[2]] = (string)$value;
    }
}
foreach (['hero_title_1','hero_title_2','hero_desc'] as $legacyKey) {
    if (!isset($i18nOverrides['tr'][$legacyKey]) && trim((string)($ayarlar[$legacyKey] ?? '')) !== '') {
        $i18nOverrides['tr'][$legacyKey] = (string)$ayarlar[$legacyKey];
    }
}

// WhatsApp link normalize
$waLink = $ayarlar['whatsapp_link'] ?? '';
if ($waLink === '' && !empty($ayarlar['whatsapp'])) {
    // Numaradan otomatik wa.me linki üret
    $clean = preg_replace('/[^0-9]/', '', (string)$ayarlar['whatsapp']);
    if ($clean !== '') $waLink = 'https://wa.me/' . ltrim($clean, '0');
}
$logo = $ayarlar['logo_path'] ?? '';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title data-i18n="meta_title">Nuverna Trade | Fresh Fruits &amp; Vegetables Export from Türkiye</title>
  <meta name="description" data-i18n-attr="content" data-i18n="meta_desc"
        content="Nuverna Trade — Türkiye'den Avrupa, Körfez, Mısır ve Rusya pazarlarına premium kalite taze meyve-sebze ihracatı. Özenli paketleme, güvenilir sevkiyat.">
  <meta property="og:title" content="Nuverna Trade — Fresh Produce Export from Türkiye">
  <meta property="og:description" content="Premium fresh fruits and vegetables export from Anatolia to Europe, GCC, Egypt and Russia.">
  <meta property="og:type" content="website">
  <link rel="icon" href="<?= htmlspecialchars(BASE_URL) ?>/favicon.ico?v=20260501" sizes="any">
  <link rel="icon" type="image/png" sizes="32x32" href="<?= htmlspecialchars(BASE_URL) ?>/favicon-32x32.png?v=20260501">
  <link rel="icon" type="image/png" sizes="16x16" href="<?= htmlspecialchars(BASE_URL) ?>/favicon-16x16.png?v=20260501">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= htmlspecialchars(BASE_URL) ?>/apple-touch-icon.png?v=20260501">
  <link rel="manifest" href="<?= htmlspecialchars(BASE_URL) ?>/site.webmanifest?v=20260501">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

  <style>
    :root {
      --p1: #7c3aed;        /* purple primary */
      --p2: #a855f7;        /* purple light */
      --p3: #c026d3;        /* magenta */
      --p4: #d946ef;        /* fuchsia */
      --p-deep: #4c1d95;    /* deep purple */
      --p-darker: #2e1065;  /* darkest purple */
      --grad: linear-gradient(135deg, #7c3aed 0%, #a855f7 35%, #c026d3 70%, #d946ef 100%);
      --grad-soft: linear-gradient(135deg, #faf5ff 0%, #fdf4ff 100%);
      --ink: #1e1b4b;
      --ink-2: #4a4458;
      --muted: #71717a;
      --line: #ede9fe;
    }
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    html{scroll-behavior:smooth}
    body{font-family:'Inter','Segoe UI',system-ui,sans-serif;color:var(--ink);background:#fff;line-height:1.65;-webkit-font-smoothing:antialiased;overflow-x:hidden}
    a{color:inherit;text-decoration:none}
    img{max-width:100%;display:block}
    button{font-family:inherit;cursor:pointer}
    .container{max-width:1280px;margin:0 auto;padding:0 28px}
    @media(max-width:600px){.container{padding:0 18px}}

    /* ── HEADER ── */
    .nv-header{position:fixed;top:0;left:0;right:0;z-index:300;backdrop-filter:blur(18px);background:rgba(255,255,255,.86);border-bottom:1px solid rgba(124,58,237,.08);transition:all .25s}
    .nv-header.scrolled{background:rgba(255,255,255,.95);box-shadow:0 4px 24px rgba(76,29,149,.08)}
    .nv-header-inner{display:flex;align-items:center;justify-content:space-between;height:92px;gap:14px}
    .nv-brand{display:flex;align-items:center;gap:11px;flex-shrink:0}
    .nv-logo{
      width:82px;height:82px;border-radius:50%;
      background:#fff;
      display:flex;align-items:center;justify-content:center;
      box-shadow:0 10px 26px rgba(76,29,149,.24);
      position:relative;
      padding:5px;
    }
    .nv-logo img{width:100%;height:100%;object-fit:contain;border-radius:50%}
    .nv-logo svg{width:56px;height:56px;color:var(--p1)}
    .nv-brand-text{font-size:19px;font-weight:800;color:var(--p-deep);letter-spacing:.2px;line-height:1.05}
    .nv-brand-text small{display:block;font-size:9px;color:var(--p3);text-transform:uppercase;letter-spacing:1.5px;font-weight:700;margin-top:5px;white-space:nowrap}

    .nv-nav{display:flex;gap:20px;align-items:center}
    @media(max-width:1080px){.nv-nav{display:none}}
    .nv-nav a{font-size:12.5px;font-weight:600;color:var(--ink-2);transition:color .15s;position:relative;padding:8px 0;white-space:nowrap}
    .nv-nav a:hover{color:var(--p1)}
    .nv-nav a::after{content:'';position:absolute;left:0;bottom:0;height:2px;width:0;background:var(--grad);transition:width .25s}
    .nv-nav a:hover::after{width:100%}

    .nv-actions{display:flex;align-items:center;gap:10px;flex-shrink:0}

    /* Lang switcher */
    .nv-lang{position:relative}
    .nv-lang-btn{display:inline-flex;align-items:center;gap:6px;padding:7px 11px;border:1.5px solid #ede9fe;border-radius:8px;background:#fff;font-size:12.5px;font-weight:600;color:var(--ink-2);transition:all .15s}
    .nv-lang-btn:hover{border-color:var(--p2);color:var(--p1)}
    .nv-lang-btn img{width:18px;height:13px;object-fit:cover;border-radius:2px}
    .nv-lang-menu{position:absolute;top:calc(100% + 6px);right:0;background:#fff;border:1px solid #ede9fe;border-radius:10px;box-shadow:0 12px 32px rgba(76,29,149,.14);min-width:140px;padding:6px;display:none;z-index:200}
    .nv-lang-menu.open{display:block}
    .nv-lang-menu button{display:flex;align-items:center;gap:9px;width:100%;padding:9px 12px;border:none;background:transparent;font-size:13px;color:var(--ink-2);font-weight:500;border-radius:6px;text-align:left;transition:background .12s}
    .nv-lang-menu button:hover{background:#faf5ff;color:var(--p-deep)}
    .nv-lang-menu button.active{background:var(--grad);color:#fff;font-weight:700}
    .nv-lang-menu img{width:20px;height:14px;object-fit:cover;border-radius:2px;flex-shrink:0}

    /* CTA */
    .nv-btn{display:inline-flex;align-items:center;gap:8px;padding:10px 22px;border-radius:10px;font-size:13.5px;font-weight:700;transition:all .18s;border:none;line-height:1}
    .nv-btn-primary{background:var(--grad);color:#fff;box-shadow:0 6px 20px rgba(124,58,237,.36)}
    .nv-btn-primary:hover{transform:translateY(-2px);box-shadow:0 10px 28px rgba(124,58,237,.5);filter:brightness(1.06)}
    .nv-btn-ghost{color:var(--p-deep);border:1.5px solid var(--p2);background:#fff}
    .nv-btn-ghost:hover{background:#faf5ff;color:var(--p1)}
    .nv-btn-light{background:rgba(255,255,255,.16);color:#fff;border:1.5px solid rgba(255,255,255,.28);backdrop-filter:blur(8px)}
    .nv-btn-light:hover{background:rgba(255,255,255,.26)}

    .nv-mobile-toggle{display:none;width:42px;height:42px;border:1.5px solid #ede9fe;border-radius:10px;background:#fff;color:var(--p-deep);font-size:18px}
    @media(max-width:1080px){.nv-mobile-toggle{display:inline-flex;align-items:center;justify-content:center}}
    @media(max-width:540px){.nv-actions .nv-btn{display:none}}
    @media(max-width:680px){
      .nv-header-inner{height:82px}
      .nv-logo{width:68px;height:68px}
      .nv-brand{gap:10px}
      .nv-brand-text{font-size:17px}
      .nv-brand-text small{font-size:8.5px;letter-spacing:1.2px}
    }

    /* Mobile menu */
    .nv-mobile-menu{position:fixed;top:0;right:-100%;width:min(320px,90vw);height:100vh;background:#fff;z-index:400;padding:80px 24px 28px;transition:right .3s;box-shadow:-12px 0 40px rgba(76,29,149,.18);overflow-y:auto}
    .nv-mobile-menu.open{right:0}
    .nv-mobile-menu .close{position:absolute;top:18px;right:18px;width:38px;height:38px;border:1.5px solid #ede9fe;border-radius:10px;background:#fff;color:var(--p-deep);font-size:18px}
    .nv-mobile-menu a{display:block;padding:13px 0;border-bottom:1px solid #f4f0fa;font-size:14px;font-weight:600;color:var(--ink-2)}
    .nv-mobile-menu a:hover{color:var(--p1)}
    .nv-mobile-menu .mm-cta{margin-top:18px;display:flex;flex-direction:column;gap:10px}
    .nv-overlay{position:fixed;inset:0;background:rgba(46,16,101,.5);z-index:350;opacity:0;pointer-events:none;transition:opacity .25s}
    .nv-overlay.open{opacity:1;pointer-events:auto}

    /* ─────────── HERO ─────────── */
    .nv-hero{position:relative;min-height:100vh;padding:150px 0 80px;background:radial-gradient(ellipse at 80% 10%,rgba(217,70,239,.12),transparent 60%),radial-gradient(ellipse at 10% 90%,rgba(124,58,237,.16),transparent 55%),linear-gradient(180deg,#faf5ff 0%,#fff 100%);overflow:hidden;display:flex;align-items:center}
    .nv-hero::before{content:'';position:absolute;inset:0;background-image:radial-gradient(circle at 1px 1px,rgba(124,58,237,.08) 1px,transparent 1px);background-size:32px 32px;opacity:.5;pointer-events:none}
    .nv-hero-grid{display:grid;grid-template-columns:1.1fr 1fr;gap:60px;align-items:center;position:relative;z-index:1}
    @media(max-width:980px){.nv-hero-grid{grid-template-columns:1fr;gap:40px}}

    .nv-eyebrow{display:inline-flex;align-items:center;gap:7px;padding:7px 14px;border-radius:30px;background:rgba(124,58,237,.1);border:1px solid rgba(124,58,237,.18);font-size:11.5px;font-weight:700;color:var(--p1);text-transform:uppercase;letter-spacing:1.2px;margin-bottom:22px}
    .nv-eyebrow .dot{width:7px;height:7px;border-radius:50%;background:var(--p4);box-shadow:0 0 0 4px rgba(217,70,239,.18);animation:pulse 2s infinite}
    @keyframes pulse{0%,100%{box-shadow:0 0 0 4px rgba(217,70,239,.18)}50%{box-shadow:0 0 0 8px rgba(217,70,239,.05)}}

    .nv-hero h1{font-size:54px;font-weight:800;line-height:1.08;letter-spacing:-1.2px;margin-bottom:22px;color:var(--p-darker)}
    .nv-hero h1 span{background:var(--grad);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}
    @media(max-width:600px){.nv-hero h1{font-size:36px}}

    .nv-hero p.lead{font-size:17px;color:var(--ink-2);max-width:560px;margin-bottom:34px;line-height:1.75}
    .nv-hero-actions{display:flex;flex-wrap:wrap;gap:12px;margin-bottom:42px}
    .nv-hero-actions .nv-btn{padding:14px 26px;font-size:14px}

    .nv-stats{display:flex;flex-wrap:wrap;gap:38px;padding-top:32px;border-top:1px solid #ede9fe}
    .nv-stat .v{font-size:30px;font-weight:800;background:var(--grad);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;line-height:1}
    .nv-stat .l{font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.6px;margin-top:5px;font-weight:600}

    /* Hero slider visual */
    .nv-hero-visual{position:relative;height:520px;border-radius:24px;overflow:hidden;box-shadow:0 32px 80px rgba(76,29,149,.28)}
    @media(max-width:980px){.nv-hero-visual{height:380px}}
    .nv-slide{position:absolute;inset:0;opacity:0;transition:opacity .8s ease;background-size:cover;background-position:center}
    .nv-slide.active{opacity:1}
    .nv-slide::before{content:'';position:absolute;inset:0;background:linear-gradient(180deg,rgba(46,16,101,.05) 0%,rgba(46,16,101,.7) 100%)}
    .nv-slide-content{position:absolute;left:30px;right:30px;bottom:30px;color:#fff;z-index:2}
    .nv-slide-content .tag{display:inline-block;padding:5px 11px;background:var(--grad);border-radius:6px;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;margin-bottom:12px}
    .nv-slide-content h3{font-size:24px;font-weight:800;line-height:1.2;margin-bottom:6px}
    .nv-slide-content p{font-size:13.5px;color:rgba(255,255,255,.92);max-width:380px}
    .nv-slide-dots{position:absolute;bottom:18px;right:24px;display:flex;gap:7px;z-index:3}
    .nv-slide-dots button{width:24px;height:6px;border-radius:3px;border:none;background:rgba(255,255,255,.4);transition:all .25s;cursor:pointer}
    .nv-slide-dots button.active{background:#fff;width:38px}

    /* Floating accent (badge) */
    .nv-floating-badge{position:absolute;top:24px;right:24px;background:#fff;padding:14px 18px;border-radius:14px;box-shadow:0 12px 30px rgba(76,29,149,.18);display:flex;gap:10px;align-items:center;z-index:3}
    .nv-floating-badge i{width:38px;height:38px;border-radius:10px;background:var(--grad);color:#fff;display:flex;align-items:center;justify-content:center;font-size:17px}
    .nv-floating-badge .v{font-size:13px;font-weight:800;color:var(--p-deep);line-height:1.1}
    .nv-floating-badge .l{font-size:10.5px;color:var(--muted);text-transform:uppercase;letter-spacing:.6px;font-weight:600}

    /* ─────────── SECTION COMMON ─────────── */
    section.nv-section{padding:96px 0;position:relative}
    section.nv-section.alt{background:var(--grad-soft)}
    .nv-section-head{text-align:center;max-width:720px;margin:0 auto 56px}
    .nv-section-tag{display:inline-block;font-size:12px;font-weight:700;color:var(--p1);letter-spacing:2px;text-transform:uppercase;margin-bottom:14px}
    .nv-section-head h2{font-size:38px;font-weight:800;color:var(--p-darker);letter-spacing:-.6px;line-height:1.2;margin-bottom:14px}
    @media(max-width:600px){.nv-section-head h2{font-size:28px}}
    .nv-section-head p{font-size:15.5px;color:var(--ink-2);line-height:1.75}

    /* ─── ABOUT ─── */
    .nv-about-grid{display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center}
    @media(max-width:880px){.nv-about-grid{grid-template-columns:1fr;gap:40px}}
    .nv-about h2{font-size:38px;font-weight:800;color:var(--p-darker);line-height:1.2;letter-spacing:-.5px;margin-bottom:18px}
    .nv-about p{font-size:15px;color:var(--ink-2);margin-bottom:14px;line-height:1.8}
    .nv-about ul{list-style:none;margin-top:22px}
    .nv-about ul li{display:flex;align-items:flex-start;gap:13px;font-size:14.5px;color:var(--ink);margin-bottom:13px;font-weight:500}
    .nv-about ul li i{width:26px;height:26px;border-radius:8px;background:var(--grad);color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;flex-shrink:0;margin-top:2px}

    .nv-about-img-wrap{position:relative;border-radius:24px;overflow:hidden;height:480px;box-shadow:0 30px 70px rgba(76,29,149,.22);background-size:cover;background-position:center}
    .nv-about-img-wrap::after{content:'';position:absolute;inset:0;background:linear-gradient(45deg,rgba(124,58,237,.4),rgba(217,70,239,.15))}
    .nv-about-stat{position:absolute;left:28px;bottom:28px;background:rgba(255,255,255,.96);backdrop-filter:blur(12px);padding:22px 26px;border-radius:16px;box-shadow:0 14px 40px rgba(76,29,149,.18);z-index:2}
    .nv-about-stat .num{font-size:38px;font-weight:800;background:var(--grad);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;line-height:1}
    .nv-about-stat .lab{font-size:12px;color:var(--ink-2);font-weight:600;margin-top:4px}

    /* ─── PRODUCTS ─── */
    .nv-products{display:grid;grid-template-columns:repeat(4,1fr);gap:22px}
    @media(max-width:1000px){.nv-products{grid-template-columns:repeat(3,1fr)}}
    @media(max-width:760px){.nv-products{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:460px){.nv-products{grid-template-columns:1fr}}
    .nv-product{position:relative;border-radius:18px;overflow:hidden;cursor:pointer;background:#fff;border:1px solid #ede9fe;transition:all .25s;box-shadow:0 4px 16px rgba(76,29,149,.06)}
    .nv-product:hover{transform:translateY(-6px);box-shadow:0 18px 40px rgba(124,58,237,.18);border-color:var(--p2)}
    .nv-product-img{width:100%;height:200px;background-size:cover;background-position:center;position:relative;overflow:hidden}
    .nv-product-img::after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,transparent 50%,rgba(46,16,101,.4) 100%);transition:opacity .25s}
    .nv-product:hover .nv-product-img::after{opacity:.7}
    .nv-product-tag{position:absolute;top:12px;left:12px;padding:5px 11px;background:var(--grad);color:#fff;border-radius:6px;font-size:10.5px;font-weight:800;letter-spacing:1px;text-transform:uppercase;z-index:2}
    .nv-product-body{padding:18px}
    .nv-product-body h4{font-size:16px;font-weight:800;color:var(--p-darker);margin-bottom:5px}
    .nv-product-body p{font-size:12.5px;color:var(--muted);line-height:1.5}
    .nv-product-arrow{position:absolute;top:14px;right:14px;width:34px;height:34px;border-radius:50%;background:#fff;color:var(--p1);display:flex;align-items:center;justify-content:center;font-size:13px;opacity:0;transform:translateX(-8px);transition:all .25s;z-index:2;box-shadow:0 6px 18px rgba(0,0,0,.18)}
    .nv-product:hover .nv-product-arrow{opacity:1;transform:translateX(0)}

    .nv-product-modal{position:fixed;inset:0;background:rgba(46,16,101,.58);backdrop-filter:blur(8px);z-index:600;display:none;align-items:center;justify-content:center;padding:22px}
    .nv-product-modal.open{display:flex}
    .nv-product-dialog{width:min(860px,100%);max-height:calc(100vh - 44px);background:#fff;border-radius:22px;overflow:hidden;box-shadow:0 30px 90px rgba(46,16,101,.35);display:grid;grid-template-columns:1fr 1fr}
    @media(max-width:760px){.nv-product-dialog{grid-template-columns:1fr;overflow-y:auto}}
    .nv-product-modal-img{min-height:360px;background-size:cover;background-position:center;position:relative}
    @media(max-width:760px){.nv-product-modal-img{min-height:240px}}
    .nv-product-modal-img::after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,transparent 45%,rgba(46,16,101,.45))}
    .nv-product-modal-tag{position:absolute;left:20px;bottom:20px;z-index:2;padding:7px 13px;background:var(--grad);color:#fff;border-radius:7px;font-size:11px;font-weight:800;letter-spacing:1px;text-transform:uppercase}
    .nv-product-modal-body{padding:34px;display:flex;flex-direction:column;justify-content:center;position:relative}
    .nv-product-modal-close{position:absolute;top:16px;right:16px;width:36px;height:36px;border:none;border-radius:10px;background:#faf5ff;color:var(--p-deep);font-size:17px}
    .nv-product-modal-close:hover{background:#ede9fe}
    .nv-product-modal-body h3{font-size:30px;line-height:1.15;color:var(--p-darker);font-weight:800;margin-bottom:12px;padding-right:42px}
    .nv-product-modal-body p{font-size:15px;color:var(--ink-2);line-height:1.8;margin-bottom:24px}
    .nv-product-modal-actions{display:flex;gap:10px;flex-wrap:wrap}

    /* ─── EXPORT MARKETS ─── */
    .nv-markets{display:grid;grid-template-columns:repeat(5,1fr);gap:18px}
    @media(max-width:980px){.nv-markets{grid-template-columns:repeat(3,1fr)}}
    @media(max-width:560px){.nv-markets{grid-template-columns:repeat(2,1fr)}}
    .nv-market{padding:24px 18px;border-radius:16px;background:#fff;border:1px solid #ede9fe;text-align:center;transition:all .25s}
    .nv-market:hover{transform:translateY(-4px);background:var(--grad);border-color:transparent;box-shadow:0 18px 40px rgba(124,58,237,.28)}
    .nv-market:hover .nv-market-flag,.nv-market:hover h4,.nv-market:hover p{color:#fff!important}
    .nv-market-flag{font-size:42px;margin-bottom:12px}
    .nv-market h4{font-size:16px;font-weight:800;color:var(--p-darker);margin-bottom:4px;transition:color .2s}
    .nv-market p{font-size:11.5px;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;font-weight:600;transition:color .2s}

    /* ─── SERVICES ─── */
    .nv-services{display:grid;grid-template-columns:repeat(3,1fr);gap:24px}
    @media(max-width:880px){.nv-services{grid-template-columns:1fr 1fr}}
    @media(max-width:560px){.nv-services{grid-template-columns:1fr}}
    .nv-service{padding:32px 26px;border-radius:18px;background:#fff;border:1px solid #ede9fe;transition:all .22s;position:relative;overflow:hidden}
    .nv-service::before{content:'';position:absolute;top:0;left:0;width:100%;height:4px;background:var(--grad);transform:scaleX(0);transform-origin:left;transition:transform .3s}
    .nv-service:hover::before{transform:scaleX(1)}
    .nv-service:hover{transform:translateY(-5px);box-shadow:0 22px 50px rgba(124,58,237,.16);border-color:var(--p2)}
    .nv-service-icon{width:56px;height:56px;border-radius:14px;background:linear-gradient(135deg,rgba(124,58,237,.12),rgba(217,70,239,.12));color:var(--p1);display:flex;align-items:center;justify-content:center;font-size:22px;margin-bottom:18px;transition:all .25s}
    .nv-service:hover .nv-service-icon{background:var(--grad);color:#fff;transform:scale(1.05)}
    .nv-service h3{font-size:18px;font-weight:800;color:var(--p-darker);margin-bottom:10px}
    .nv-service p{font-size:13.5px;color:var(--ink-2);line-height:1.7}

    /* ─── WHY US (4 features) ─── */
    .nv-why-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:22px}
    @media(max-width:980px){.nv-why-grid{grid-template-columns:1fr 1fr}}
    @media(max-width:520px){.nv-why-grid{grid-template-columns:1fr}}
    .nv-why-card{padding:28px 22px;border-radius:16px;background:#fff;border:1px solid #ede9fe;transition:all .22s;text-align:center}
    .nv-why-card:hover{transform:translateY(-4px);box-shadow:0 18px 38px rgba(124,58,237,.14)}
    .nv-why-icon{width:72px;height:72px;border-radius:50%;background:var(--grad);color:#fff;display:flex;align-items:center;justify-content:center;font-size:26px;margin:0 auto 16px;box-shadow:0 12px 26px rgba(124,58,237,.32)}
    .nv-why-card h4{font-size:16px;font-weight:800;color:var(--p-darker);margin-bottom:8px}
    .nv-why-card p{font-size:13px;color:var(--ink-2);line-height:1.65}

    /* ─── PROCESS / TIMELINE ─── */
    .nv-process{display:grid;grid-template-columns:repeat(4,1fr);gap:28px;position:relative}
    .nv-process::before{content:'';position:absolute;top:48px;left:8%;right:8%;height:2px;background:linear-gradient(90deg,var(--p2),var(--p4));z-index:0}
    @media(max-width:880px){.nv-process{grid-template-columns:1fr 1fr}.nv-process::before{display:none}}
    @media(max-width:480px){.nv-process{grid-template-columns:1fr}}
    .nv-process-step{text-align:center;position:relative;z-index:1}
    .nv-process-num{width:96px;height:96px;border-radius:50%;background:#fff;border:2.5px solid var(--p2);color:var(--p1);display:flex;align-items:center;justify-content:center;font-size:30px;font-weight:800;margin:0 auto 18px;box-shadow:0 10px 28px rgba(124,58,237,.16);transition:all .25s}
    .nv-process-step:hover .nv-process-num{background:var(--grad);color:#fff;border-color:transparent;transform:scale(1.05)}
    .nv-process-step h4{font-size:17px;font-weight:800;color:var(--p-darker);margin-bottom:8px}
    .nv-process-step p{font-size:13px;color:var(--ink-2);line-height:1.65;max-width:240px;margin:0 auto}

    /* ─── GALLERY ─── */
    .nv-gallery{display:grid;grid-template-columns:repeat(4,1fr);grid-auto-rows:200px;gap:14px}
    @media(max-width:880px){.nv-gallery{grid-template-columns:repeat(3,1fr);grid-auto-rows:180px}}
    @media(max-width:560px){.nv-gallery{grid-template-columns:repeat(2,1fr);grid-auto-rows:150px}}
    .nv-gallery-item{border-radius:14px;overflow:hidden;background-size:cover;background-position:center;transition:all .25s;position:relative;cursor:pointer}
    .nv-gallery-item::after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,transparent 60%,rgba(76,29,149,.65) 100%);opacity:0;transition:opacity .25s}
    .nv-gallery-item:hover{transform:scale(1.02)}
    .nv-gallery-item:hover::after{opacity:1}
    .nv-gallery-item.tall{grid-row:span 2}
    @media(max-width:560px){.nv-gallery-item.tall{grid-row:span 1}}
    .nv-gallery-tag{position:absolute;left:12px;bottom:12px;padding:5px 10px;background:rgba(255,255,255,.9);color:var(--p-deep);font-size:11px;font-weight:700;border-radius:5px;opacity:0;transform:translateY(8px);transition:all .25s;z-index:2}
    .nv-gallery-item:hover .nv-gallery-tag{opacity:1;transform:translateY(0)}

    /* ─── CONTACT ─── */
    .nv-contact-grid{display:grid;grid-template-columns:1fr 1.1fr;gap:50px;align-items:start}
    @media(max-width:880px){.nv-contact-grid{grid-template-columns:1fr}}
    .nv-contact-info{padding:32px;border-radius:20px;background:var(--grad);color:#fff;position:relative;overflow:hidden}
    .nv-contact-info::after{content:'';position:absolute;right:-60px;bottom:-60px;width:240px;height:240px;background:radial-gradient(circle,rgba(255,255,255,.15) 0%,transparent 70%);pointer-events:none}
    .nv-contact-info h3{font-size:24px;font-weight:800;margin-bottom:8px}
    .nv-contact-info p.intro{font-size:14px;color:rgba(255,255,255,.88);margin-bottom:26px;line-height:1.7}
    .nv-contact-row{display:flex;align-items:flex-start;gap:14px;margin-bottom:18px;font-size:14px;position:relative;z-index:1}
    .nv-contact-row i{width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0}
    .nv-contact-row b{display:block;font-size:11px;text-transform:uppercase;letter-spacing:1px;color:rgba(255,255,255,.7);font-weight:700;margin-bottom:3px}
    .nv-contact-row span{color:#fff;line-height:1.55}
    .nv-contact-social{display:flex;gap:8px;margin-top:24px;position:relative;z-index:1}
    .nv-contact-social a{width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,.16);display:flex;align-items:center;justify-content:center;color:#fff;transition:all .15s}
    .nv-contact-social a:hover{background:#fff;color:var(--p1);transform:translateY(-2px)}

    .nv-contact-form{padding:32px;border-radius:20px;background:#fff;border:1px solid #ede9fe;box-shadow:0 12px 36px rgba(76,29,149,.08)}
    .nv-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
    @media(max-width:520px){.nv-form-grid{grid-template-columns:1fr}}
    .nv-form-group{margin-bottom:16px}
    .nv-form-group label{display:block;font-size:12.5px;font-weight:700;color:var(--ink-2);margin-bottom:6px}
    .nv-form-group input,.nv-form-group textarea{width:100%;padding:11px 14px;border:1.5px solid #ede9fe;border-radius:10px;font-family:inherit;font-size:13.5px;color:var(--ink);outline:none;transition:border-color .15s,box-shadow .15s;background:#faf5ff}
    .nv-form-group input:focus,.nv-form-group textarea:focus{border-color:var(--p2);background:#fff;box-shadow:0 0 0 4px rgba(124,58,237,.08)}
    .nv-form-group textarea{resize:vertical;min-height:110px}

    /* ─── FOOTER ─── */
    .nv-footer{background:#1e1b4b;color:#c4b5fd;padding:64px 0 24px;font-size:14px;position:relative;overflow:hidden}
    .nv-footer::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:var(--grad)}
    .nv-footer-grid{display:grid;grid-template-columns:1.6fr 1fr 1fr 1fr;gap:40px;margin-bottom:42px}
    @media(max-width:880px){.nv-footer-grid{grid-template-columns:1fr 1fr;gap:30px}}
    @media(max-width:480px){.nv-footer-grid{grid-template-columns:1fr}}
    .nv-footer h4{color:#fff;font-size:13px;font-weight:800;text-transform:uppercase;letter-spacing:1.4px;margin-bottom:18px}
    .nv-footer ul{list-style:none}
    .nv-footer ul li{margin-bottom:10px;font-size:12.5px}
    .nv-footer ul a{color:#a5a3d8;transition:color .15s}
    .nv-footer ul a:hover{color:var(--p1)}
    .nv-footer p{color:#a5a3d8;line-height:1.75;font-size:13.5px;margin-top:12px}
    .nv-footer-brand{display:flex;align-items:center;gap:11px;margin-bottom:6px}
    .nv-footer-brand .nv-logo{width:64px;height:64px}
    .nv-footer-brand .nv-logo svg{width:40px;height:40px}
    .nv-footer-brand .nv-brand-text{color:#fff}
    .nv-footer-bottom{border-top:1px solid rgba(196,181,253,.16);padding-top:22px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:14px;font-size:12.5px;color:#a5a3d8}

    /* ─── FLOATING ─── */
    .nv-fab-whatsapp{position:fixed;bottom:96px;right:22px;width:56px;height:56px;border-radius:50%;background:#25d366;color:#fff;display:flex;align-items:center;justify-content:center;font-size:24px;box-shadow:0 12px 30px rgba(37,211,102,.4);z-index:200;transition:all .2s}
    .nv-fab-whatsapp:hover{transform:scale(1.08);color:#fff}
    @media(max-width:680px){.nv-fab-whatsapp{right:16px;bottom:82px;width:48px;height:48px;font-size:20px}}
    .nv-scroll-top{position:fixed;bottom:22px;right:22px;width:46px;height:46px;border-radius:50%;background:var(--grad);color:#fff;border:none;display:flex;align-items:center;justify-content:center;font-size:14px;box-shadow:0 10px 26px rgba(124,58,237,.36);z-index:200;cursor:pointer;opacity:0;pointer-events:none;transition:all .25s}
    .nv-scroll-top.show{opacity:1;pointer-events:auto}
    @media(max-width:680px){.nv-scroll-top{right:19px;bottom:138px;width:42px;height:42px}}

    /* ─── MOBILE BOTTOM BAR ─── */
    .nv-mobile-bar{display:none;position:fixed;bottom:0;left:0;right:0;background:#fff;border-top:1px solid #ede9fe;padding:8px 4px;z-index:250;box-shadow:0 -8px 24px rgba(76,29,149,.1)}
    @media(max-width:880px){.nv-mobile-bar{display:grid;grid-template-columns:repeat(4,1fr);gap:2px}}
    .nv-mobile-bar a{display:flex;flex-direction:column;align-items:center;gap:3px;padding:6px 4px;font-size:10.5px;color:var(--ink-2);font-weight:600;min-width:0;border-radius:8px;transition:background .15s}
    .nv-mobile-bar a:hover,.nv-mobile-bar a.active{background:#faf5ff;color:var(--p1)}
    .nv-mobile-bar a i{font-size:18px;margin-bottom:2px}
    @media(max-width:880px){body{padding-bottom:64px}}

    /* hide elements when smaller breakpoints */
    @media(max-width:480px){.nv-floating-badge{display:none}}
  </style>
</head>
<body>

<!-- ═══════════════ HEADER ═══════════════ -->
<header class="nv-header" id="nvHeader">
  <div class="container nv-header-inner">
    <a href="#" class="nv-brand">
      <div class="nv-logo">
        <?php if (!empty($logo)): ?>
          <img src="<?= htmlspecialchars($logo) ?>" alt="<?= $s('company_name','Nuverna') ?>">
        <?php else: ?>
          <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M22 78 V22 L78 78 V22" stroke="white" stroke-width="11" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
          </svg>
        <?php endif; ?>
      </div>
      <div class="nv-brand-text">
        <?= $s('company_name','Nuverna') ?>
        <small data-i18n="brand_tag"><?= $s('tagline','Trade · Fresh Export') ?></small>
      </div>
    </a>

    <nav class="nv-nav" id="nvNav">
      <a href="#hero"     data-i18n="nav_home">Ana Sayfa</a>
      <a href="#about"    data-i18n="nav_about">Hakkımızda</a>
      <a href="#products" data-i18n="nav_products">Ürünler</a>
      <a href="#markets"  data-i18n="nav_markets">İhracat Bölgeleri</a>
      <a href="#services" data-i18n="nav_services">Hizmetler</a>
      <a href="#why"      data-i18n="nav_why">Neden Nuverna?</a>
      <a href="#gallery"  data-i18n="nav_gallery">Galeri</a>
      <a href="#contact"  data-i18n="nav_contact">İletişim</a>
    </nav>

    <div class="nv-actions">
      <!-- Language switcher -->
      <div class="nv-lang">
        <button class="nv-lang-btn" id="nvLangBtn" type="button" aria-haspopup="true">
          <img id="nvLangFlag" src="https://flagcdn.com/w40/tr.png" alt="TR">
          <span id="nvLangCode">TR</span>
          <i class="fa-solid fa-chevron-down" style="font-size:9px;"></i>
        </button>
        <div class="nv-lang-menu" id="nvLangMenu">
          <button data-lang="tr" class="active">
            <img src="https://flagcdn.com/w40/tr.png" alt="TR"> Türkçe
          </button>
          <button data-lang="en">
            <img src="https://flagcdn.com/w40/gb.png" alt="EN"> English
          </button>
          <button data-lang="ru">
            <img src="https://flagcdn.com/w40/ru.png" alt="RU"> Русский
          </button>
        </div>
      </div>

      <button class="nv-mobile-toggle" id="nvMobileToggle" aria-label="Menu">
        <i class="fa-solid fa-bars"></i>
      </button>
    </div>
  </div>
</header>

<!-- Mobile menu -->
<div class="nv-overlay" id="nvOverlay"></div>
<aside class="nv-mobile-menu" id="nvMobileMenu">
  <button class="close" id="nvMobileClose"><i class="fa-solid fa-xmark"></i></button>
  <a href="#hero" data-i18n="nav_home">Ana Sayfa</a>
  <a href="#about" data-i18n="nav_about">Hakkımızda</a>
  <a href="#products" data-i18n="nav_products">Ürünler</a>
  <a href="#markets" data-i18n="nav_markets">İhracat Bölgeleri</a>
  <a href="#services" data-i18n="nav_services">Hizmetler</a>
  <a href="#why" data-i18n="nav_why">Neden Nuverna?</a>
  <a href="#gallery" data-i18n="nav_gallery">Galeri</a>
  <a href="#contact" data-i18n="nav_contact">İletişim</a>
  <div class="mm-cta">
    <?php if ($isLoggedIn): ?>
      <a class="nv-btn nv-btn-primary" href="<?= BASE_URL ?>/dashboard"><i class="fa-solid fa-gauge-high"></i> <span data-i18n="nav_panel">Panele Git</span></a>
    <?php else: ?>
      <a class="nv-btn nv-btn-primary" href="<?= BASE_URL ?>/giris"><i class="fa-solid fa-arrow-right-to-bracket"></i> <span data-i18n="nav_login">Giriş Yap</span></a>
    <?php endif; ?>
  </div>
</aside>

<!-- ═══════════════ HERO ═══════════════ -->
<section class="nv-hero" id="hero">
  <div class="container nv-hero-grid">
    <div>
      <div class="nv-eyebrow">
        <span class="dot"></span>
        <span data-i18n="hero_eyebrow">Türkiye'den Dünyaya İhracat</span>
      </div>
      <h1>
        <span data-i18n="hero_title_1"><?= $s('hero_title_1', "Anadolu'dan dünyaya") ?></span><br>
        <span data-i18n="hero_title_2"><?= $s('hero_title_2', 'taze ihracat') ?></span>
      </h1>
      <p class="lead" data-i18n="hero_desc"><?= $s('hero_desc', 'Nuverna Trade, kaliteli yaş sebze ve meyveleri dünya pazarlarına ulaştırır.') ?></p>
      <div class="nv-hero-actions">
        <a class="nv-btn nv-btn-primary" href="#products"><i class="fa-solid fa-leaf"></i> <span data-i18n="hero_btn_products">Ürünleri İncele</span></a>
        <a class="nv-btn nv-btn-ghost" href="#contact"><i class="fa-solid fa-envelope-open-text"></i> <span data-i18n="hero_btn_contact">İletişime Geç</span></a>
      </div>
      <div class="nv-stats">
        <div class="nv-stat"><div class="v"><?= $s('stat_countries_value', '15+') ?></div><div class="l" data-i18n="stat_countries">Ülkeye İhracat</div></div>
        <div class="nv-stat"><div class="v"><?= $s('stat_products_value', '30+') ?></div><div class="l" data-i18n="stat_products">Ürün Çeşidi</div></div>
        <div class="nv-stat"><div class="v"><?= $s('stat_quality_value', '100%') ?></div><div class="l" data-i18n="stat_quality">Kalite Kontrol</div></div>
      </div>
    </div>

    <div class="nv-hero-visual">
      <div class="nv-floating-badge">
        <i class="fa-solid fa-award"></i>
        <div>
          <div class="v" data-i18n="badge_v">Premium</div>
          <div class="l" data-i18n="badge_l">Quality</div>
        </div>
      </div>

      <div class="nv-slide active" style="background-image:url('<?= $s('slide1_img', 'https://images.unsplash.com/photo-1610348725531-843dff563e2c?auto=format&fit=crop&w=1400&q=80') ?>')">
        <div class="nv-slide-content">
          <span class="tag" data-i18n="slide1_tag">Yeni Hasat</span>
          <h3 data-i18n="slide1_title">Taze Meyve İhracatı</h3>
          <p data-i18n="slide1_desc">Anadolu'nun verimli topraklarından özenle seçilen taze meyveler.</p>
        </div>
      </div>
      <div class="nv-slide" style="background-image:url('<?= $s('slide2_img', 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=1400&q=80') ?>')">
        <div class="nv-slide-content">
          <span class="tag" data-i18n="slide2_tag">Premium Paketleme</span>
          <h3 data-i18n="slide2_title">Kalite Kontrol & Paketleme</h3>
          <p data-i18n="slide2_desc">Her ürün, hedef pazara uygun standartta kontrol edilir ve paketlenir.</p>
        </div>
      </div>
      <div class="nv-slide" style="background-image:url('<?= $s('slide3_img', 'https://images.unsplash.com/photo-1473973266408-ed4e27abdd47?auto=format&fit=crop&w=1400&q=80') ?>')">
        <div class="nv-slide-content">
          <span class="tag" data-i18n="slide3_tag">Global Ulaşım</span>
          <h3 data-i18n="slide3_title">Avrupa, Körfez, Rusya Pazarlarına</h3>
          <p data-i18n="slide3_desc">15+ ülkeye düzenli ve güvenilir sevkiyat ağı.</p>
        </div>
      </div>

      <div class="nv-slide-dots">
        <button class="active" data-slide="0"></button>
        <button data-slide="1"></button>
        <button data-slide="2"></button>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════ ABOUT ═══════════════ -->
<section class="nv-section" id="about">
  <div class="container nv-about-grid">
    <div class="nv-about">
      <div class="nv-section-tag" data-i18n="about_tag">Hakkımızda</div>
      <h2 data-i18n="about_title">Kalite, güven ve süreklilik üzerine kurulu bir ihracat firması.</h2>
      <p data-i18n="about_p1">
        Nuverna Trade, Türkiye'nin verimli topraklarında yetişen kaliteli yaş sebze ve meyveleri uluslararası pazarlara ulaştıran profesyonel bir ihracat firmasıdır. Ürünlerin seçimi, kalite kontrolü, paketlenmesi ve sevkiyat sürecinde titizlikle hareket ederiz.
      </p>
      <p data-i18n="about_p2">
        Amacımız; müşterilerimize süreklilik, kalite ve güven sunmaktır. Her bir kasanın arkasında titizlikle çalışan bir ekip vardır.
      </p>
      <ul>
        <li><i class="fa-solid fa-check"></i> <span data-i18n="about_li1">Kalite odaklı yaklaşım ve tedarik ağı</span></li>
        <li><i class="fa-solid fa-check"></i> <span data-i18n="about_li2">Hedef pazara uygun paketleme standartları</span></li>
        <li><i class="fa-solid fa-check"></i> <span data-i18n="about_li3">Yıllık deneyim ve güvenilir operasyon</span></li>
        <li><i class="fa-solid fa-check"></i> <span data-i18n="about_li4">Zamanında teslimat ve müşteri memnuniyeti</span></li>
      </ul>
    </div>
    <div class="nv-about-img-wrap" style="background-image:url('<?= $s('about_img', 'https://images.unsplash.com/photo-1488459716781-31db52582fe9?auto=format&fit=crop&w=1200&q=80') ?>')">
      <div class="nv-about-stat">
        <div class="num"><?= $s('about_stat_value', '15+') ?></div>
        <div class="lab" data-i18n="about_stat">Ülkeye Düzenli İhracat</div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════ PRODUCTS ═══════════════ -->
<section class="nv-section alt" id="products">
  <div class="container">
    <div class="nv-section-head">
      <div class="nv-section-tag" data-i18n="products_tag">Ürünlerimiz</div>
      <h2 data-i18n="products_title">Premium kalite, taze ürün yelpazesi</h2>
      <p data-i18n="products_desc">Anadolu'nun bereketli topraklarından dünya soframıza uzanan taze ürünler.</p>
    </div>

    <div class="nv-products">
      <?php if (empty($urunler)): ?>
        <div style="grid-column:1/-1; text-align:center; color:#9ca3af; padding:50px;">
          Henüz ürün eklenmedi. Yönetim panelinden ekleyin.
        </div>
      <?php else: foreach ($urunler as $u): ?>
      <div class="nv-product"
           role="button"
           tabindex="0"
           aria-label="<?= htmlspecialchars($u['ad_tr'] ?? '') ?>"
           data-name-tr="<?= htmlspecialchars($u['ad_tr'] ?? '') ?>"
           data-name-en="<?= htmlspecialchars($u['ad_en'] ?? '') ?>"
           data-name-ru="<?= htmlspecialchars($u['ad_ru'] ?? '') ?>"
           data-desc-tr="<?= htmlspecialchars($u['aciklama_tr'] ?? '') ?>"
           data-desc-en="<?= htmlspecialchars($u['aciklama_en'] ?? '') ?>"
           data-desc-ru="<?= htmlspecialchars($u['aciklama_ru'] ?? '') ?>"
           data-image="<?= htmlspecialchars($u['gorsel'] ?? '') ?>"
           data-tag="<?= htmlspecialchars($u['etiket'] ?: 'FRESH') ?>">
        <div class="nv-product-img" style="background-image:url('<?= htmlspecialchars($u['gorsel']) ?>')">
          <span class="nv-product-tag"><?= htmlspecialchars($u['etiket'] ?: 'FRESH') ?></span>
        </div>
        <div class="nv-product-arrow"><i class="fa-solid fa-arrow-up-right-from-square"></i></div>
        <div class="nv-product-body">
          <h4 class="nv-product-name"><?= htmlspecialchars($u['ad_tr']) ?></h4>
          <p class="nv-product-desc"><?= htmlspecialchars($u['aciklama_tr'] ?? '') ?></p>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</section>

<!-- ═══════════════ EXPORT MARKETS ═══════════════ -->
<section class="nv-section" id="markets">
  <div class="container">
    <div class="nv-section-head">
      <div class="nv-section-tag" data-i18n="markets_tag">İhracat Bölgeleri</div>
      <h2 data-i18n="markets_title">Türkiye'den dünyaya uzanan tedarik ağı</h2>
      <p data-i18n="markets_desc">Farklı pazarların kalite ve paketleme beklentilerine uygun çözümler sunarız.</p>
    </div>
    <div class="nv-markets">
      <div class="nv-market">
        <div class="nv-market-flag">🇪🇺</div>
        <h4 data-i18n="market_eu">Avrupa</h4>
        <p data-i18n="market_eu_d">Almanya · Hollanda · Polonya</p>
      </div>
      <div class="nv-market">
        <div class="nv-market-flag">🇦🇪</div>
        <h4 data-i18n="market_uae">BAE / Dubai</h4>
        <p data-i18n="market_uae_d">Premium Pazar</p>
      </div>
      <div class="nv-market">
        <div class="nv-market-flag">🇸🇦</div>
        <h4 data-i18n="market_sa">Suudi Arabistan</h4>
        <p data-i18n="market_sa_d">Körfez Ülkeleri</p>
      </div>
      <div class="nv-market">
        <div class="nv-market-flag">🇪🇬</div>
        <h4 data-i18n="market_eg">Mısır</h4>
        <p data-i18n="market_eg_d">Kuzey Afrika</p>
      </div>
      <div class="nv-market">
        <div class="nv-market-flag">🇷🇺</div>
        <h4 data-i18n="market_ru">Rusya</h4>
        <p data-i18n="market_ru_d">BDT Bölgesi</p>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════ SERVICES ═══════════════ -->
<section class="nv-section alt" id="services">
  <div class="container">
    <div class="nv-section-head">
      <div class="nv-section-tag" data-i18n="services_tag">Hizmetlerimiz</div>
      <h2 data-i18n="services_title">Uçtan uca ihracat çözümleri</h2>
      <p data-i18n="services_desc">Tedarik, kontrol, paketleme, sevkiyat — her aşama tek elden.</p>
    </div>
    <div class="nv-services">
      <div class="nv-service">
        <div class="nv-service-icon"><i class="fa-solid fa-seedling"></i></div>
        <h3 data-i18n="srv1_t">Ürün Tedariki</h3>
        <p data-i18n="srv1_d">Türkiye'nin en verimli üretim bölgelerinden doğrudan tedarik.</p>
      </div>
      <div class="nv-service">
        <div class="nv-service-icon"><i class="fa-solid fa-magnifying-glass-chart"></i></div>
        <h3 data-i18n="srv2_t">Kalite Kontrol</h3>
        <p data-i18n="srv2_d">Hedef pazar standartlarına uygun titiz inceleme ve ayrım.</p>
      </div>
      <div class="nv-service">
        <div class="nv-service-icon"><i class="fa-solid fa-box"></i></div>
        <h3 data-i18n="srv3_t">Paketleme Çözümleri</h3>
        <p data-i18n="srv3_d">Müşteri ve ülke spesifik ambalajlama, etiketleme.</p>
      </div>
      <div class="nv-service">
        <div class="nv-service-icon"><i class="fa-solid fa-ship"></i></div>
        <h3 data-i18n="srv4_t">İhracat Operasyonu</h3>
        <p data-i18n="srv4_d">Gümrük, evrak ve uluslararası sevk süreç yönetimi.</p>
      </div>
      <div class="nv-service">
        <div class="nv-service-icon"><i class="fa-solid fa-truck-fast"></i></div>
        <h3 data-i18n="srv5_t">Lojistik Koordinasyon</h3>
        <p data-i18n="srv5_d">Soğuk zincir, kara/deniz/hava tercih bazlı planlama.</p>
      </div>
      <div class="nv-service">
        <div class="nv-service-icon"><i class="fa-solid fa-handshake-angle"></i></div>
        <h3 data-i18n="srv6_t">Müşteri Odaklı Tedarik</h3>
        <p data-i18n="srv6_d">Talebe özel kalibre, paket ve teslim çözümleri.</p>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════ WHY NUVERNA ═══════════════ -->
<section class="nv-section" id="why">
  <div class="container">
    <div class="nv-section-head">
      <div class="nv-section-tag" data-i18n="why_tag">Neden Nuverna?</div>
      <h2 data-i18n="why_title">Güveniniz, kalitemizden başlar.</h2>
      <p data-i18n="why_desc">Her aşamada müşteri memnuniyetine, ürün kalitesine ve operasyon güvenliğine odaklanırız.</p>
    </div>
    <div class="nv-why-grid">
      <div class="nv-why-card">
        <div class="nv-why-icon"><i class="fa-solid fa-shield-halved"></i></div>
        <h4 data-i18n="why1_t">Güvenilir Tedarik</h4>
        <p data-i18n="why1_d">Doğrulanmış üretim noktaları ve sürekli kalite takibi.</p>
      </div>
      <div class="nv-why-card">
        <div class="nv-why-icon"><i class="fa-solid fa-leaf"></i></div>
        <h4 data-i18n="why2_t">Taze ve Premium</h4>
        <p data-i18n="why2_d">Hasat, paketleme ve sevkiyat arasındaki süre minimumda.</p>
      </div>
      <div class="nv-why-card">
        <div class="nv-why-icon"><i class="fa-solid fa-globe"></i></div>
        <h4 data-i18n="why3_t">Global Erişim</h4>
        <p data-i18n="why3_d">Avrupa, Körfez, Rusya'ya düzenli ihracat hatları.</p>
      </div>
      <div class="nv-why-card">
        <div class="nv-why-icon"><i class="fa-solid fa-headset"></i></div>
        <h4 data-i18n="why4_t">Esnek İletişim</h4>
        <p data-i18n="why4_d">Türkçe, İngilizce ve Rusça hızlı yanıt veren ekip.</p>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════ PROCESS ═══════════════ -->
<section class="nv-section alt" id="process">
  <div class="container">
    <div class="nv-section-head">
      <div class="nv-section-tag" data-i18n="proc_tag">Süreç</div>
      <h2 data-i18n="proc_title">Tarladan dünyaya 4 adımda</h2>
      <p data-i18n="proc_desc">Her ürün, müşteri beklentisine uygun şekilde seçilir, kontrol edilir, paketlenir ve sevkiyata hazırlanır.</p>
    </div>
    <div class="nv-process">
      <div class="nv-process-step">
        <div class="nv-process-num">01</div>
        <h4 data-i18n="proc1_t">Ürün Seçimi</h4>
        <p data-i18n="proc1_d">Verim alanlarından özenle seçilen kaliteli ürünler.</p>
      </div>
      <div class="nv-process-step">
        <div class="nv-process-num">02</div>
        <h4 data-i18n="proc2_t">Kalite Kontrol</h4>
        <p data-i18n="proc2_d">Boyut, renk, sağlamlık ve hijyen kontrolü.</p>
      </div>
      <div class="nv-process-step">
        <div class="nv-process-num">03</div>
        <h4 data-i18n="proc3_t">Paketleme</h4>
        <p data-i18n="proc3_d">Hedef pazara uygun ambalaj ve etiketleme.</p>
      </div>
      <div class="nv-process-step">
        <div class="nv-process-num">04</div>
        <h4 data-i18n="proc4_t">Sevkiyat</h4>
        <p data-i18n="proc4_d">Soğuk zincir ile zamanında teslim.</p>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════ GALLERY ═══════════════ -->
<section class="nv-section" id="gallery">
  <div class="container">
    <div class="nv-section-head">
      <div class="nv-section-tag" data-i18n="gallery_tag">Galeri</div>
      <h2 data-i18n="gallery_title">Sahadan kareler</h2>
      <p data-i18n="gallery_desc">Üretim, paketleme ve sevkiyat süreçlerinden seçilmiş anlar.</p>
    </div>
    <div class="nv-gallery">
      <?php
      // DB'de galeri yoksa varsayılan demo görsellerle doldur
      $galeriList = !empty($galeri) ? $galeri : [
        ['gorsel'=>'https://images.unsplash.com/photo-1506976785307-8732e854ad03?auto=format&fit=crop&w=800&q=80','etiket_tr'=>'Üretim Alanı','etiket_en'=>'Field','etiket_ru'=>'Поле','tall'=>1],
        ['gorsel'=>'https://images.unsplash.com/photo-1601000938259-9e92002320b2?auto=format&fit=crop&w=800&q=80','etiket_tr'=>'Paketleme','etiket_en'=>'Packing','etiket_ru'=>'Упаковка'],
        ['gorsel'=>'https://images.unsplash.com/photo-1542838686-37da4a9fd1b3?auto=format&fit=crop&w=800&q=80','etiket_tr'=>'Kalite Kontrol','etiket_en'=>'QC','etiket_ru'=>'КК'],
        ['gorsel'=>'https://images.unsplash.com/photo-1488459716781-31db52582fe9?auto=format&fit=crop&w=800&q=80','etiket_tr'=>'Hal / Pazar','etiket_en'=>'Market','etiket_ru'=>'Рынок','tall'=>1],
        ['gorsel'=>'https://images.unsplash.com/photo-1572635148818-ef6fd45eb394?auto=format&fit=crop&w=800&q=80','etiket_tr'=>'Taze Meyve','etiket_en'=>'Fresh Fruit','etiket_ru'=>'Свежие фрукты'],
        ['gorsel'=>'https://images.unsplash.com/photo-1513125370-3460ebe3401b?auto=format&fit=crop&w=800&q=80','etiket_tr'=>'Sevkiyat','etiket_en'=>'Shipping','etiket_ru'=>'Отгрузка'],
        ['gorsel'=>'https://images.unsplash.com/photo-1518977676601-b53f82aba655?auto=format&fit=crop&w=800&q=80','etiket_tr'=>'Armut Hasadı','etiket_en'=>'Pear Harvest','etiket_ru'=>'Сбор груш'],
        ['gorsel'=>'https://images.unsplash.com/photo-1546470427-0d49b3d6b3a0?auto=format&fit=crop&w=800&q=80','etiket_tr'=>'Domates','etiket_en'=>'Tomato','etiket_ru'=>'Помидоры'],
      ];
      $idx = 0;
      foreach ($galeriList as $g):
          $isTall = !empty($g['tall']) || ($idx % 4 === 0);
          $idx++;
      ?>
      <div class="nv-gallery-item <?= $isTall ? 'tall' : '' ?>"
           style="background-image:url('<?= htmlspecialchars($g['gorsel']) ?>')"
           data-tag-tr="<?= htmlspecialchars($g['etiket_tr'] ?? '') ?>"
           data-tag-en="<?= htmlspecialchars($g['etiket_en'] ?? '') ?>"
           data-tag-ru="<?= htmlspecialchars($g['etiket_ru'] ?? '') ?>">
        <?php if (!empty($g['etiket_tr'])): ?>
          <span class="nv-gallery-tag nv-gallery-tag-text"><?= htmlspecialchars($g['etiket_tr']) ?></span>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══════════════ CONTACT ═══════════════ -->
<section class="nv-section alt" id="contact">
  <div class="container">
    <div class="nv-section-head">
      <div class="nv-section-tag" data-i18n="contact_tag">İletişim</div>
      <h2 data-i18n="contact_title">Sizinle iş birliği yapmaya hazırız</h2>
      <p data-i18n="contact_desc">Teklif, ürün listesi ve sevkiyat seçenekleri için bize ulaşın.</p>
    </div>

    <div class="nv-contact-grid">
      <div class="nv-contact-info">
        <h3><?= $s('company_name','Nuverna Trade') ?></h3>
        <p class="intro" data-i18n="contact_intro">Türkiye merkezli ihracat operasyonumuzla 7/24 ulaşılabiliriz. Mesajınızı bekliyoruz.</p>

        <?php if (!empty($ayarlar['address'])): ?>
        <div class="nv-contact-row">
          <i class="fa-solid fa-location-dot"></i>
          <div>
            <b data-i18n="c_addr">Adres</b>
            <span><?= $s('address') ?></span>
          </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($ayarlar['email'])): ?>
        <div class="nv-contact-row">
          <i class="fa-solid fa-envelope"></i>
          <div>
            <b data-i18n="c_mail">E-Posta</b>
            <span><a href="mailto:<?= $s('email') ?>" style="color:#fff;text-decoration:none"><?= $s('email') ?></a></span>
          </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($ayarlar['phone'])): ?>
        <div class="nv-contact-row">
          <i class="fa-solid fa-phone"></i>
          <div>
            <b data-i18n="c_phone">Telefon</b>
            <span><?= $s('phone') ?></span>
          </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($ayarlar['whatsapp'])): ?>
        <div class="nv-contact-row">
          <i class="fa-brands fa-whatsapp"></i>
          <div>
            <b data-i18n="c_wa">WhatsApp</b>
            <span><?php if ($waLink): ?><a href="<?= htmlspecialchars($waLink) ?>" target="_blank" style="color:#fff;text-decoration:none"><?= $s('whatsapp') ?></a><?php else: ?><?= $s('whatsapp') ?><?php endif; ?></span>
          </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($ayarlar['website'])): ?>
        <div class="nv-contact-row">
          <i class="fa-solid fa-globe"></i>
          <div>
            <b data-i18n="c_web">Web</b>
            <span><?= $s('website') ?></span>
          </div>
        </div>
        <?php endif; ?>

        <div class="nv-contact-social">
          <?php if (!empty($ayarlar['instagram'])): ?><a href="<?= $s('instagram') ?>" target="_blank" rel="noopener" title="Instagram"><i class="fa-brands fa-instagram"></i></a><?php endif; ?>
          <?php if (!empty($ayarlar['linkedin'])): ?><a href="<?= $s('linkedin') ?>" target="_blank" rel="noopener" title="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a><?php endif; ?>
          <?php if (!empty($ayarlar['facebook'])): ?><a href="<?= $s('facebook') ?>" target="_blank" rel="noopener" title="Facebook"><i class="fa-brands fa-facebook-f"></i></a><?php endif; ?>
          <?php if (!empty($ayarlar['twitter'])): ?><a href="<?= $s('twitter') ?>" target="_blank" rel="noopener" title="X / Twitter"><i class="fa-brands fa-x-twitter"></i></a><?php endif; ?>
          <?php if ($waLink): ?><a href="<?= htmlspecialchars($waLink) ?>" target="_blank" rel="noopener" title="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a><?php endif; ?>
        </div>
      </div>

      <form class="nv-contact-form" onsubmit="return false">
        <div class="nv-form-grid">
          <div class="nv-form-group">
            <label data-i18n="f_name">Ad Soyad</label>
            <input type="text" required>
          </div>
          <div class="nv-form-group">
            <label data-i18n="f_company">Firma</label>
            <input type="text">
          </div>
        </div>
        <div class="nv-form-grid">
          <div class="nv-form-group">
            <label data-i18n="f_mail">E-Posta</label>
            <input type="email" required>
          </div>
          <div class="nv-form-group">
            <label data-i18n="f_phone">Telefon / WhatsApp</label>
            <input type="tel">
          </div>
        </div>
        <div class="nv-form-group">
          <label data-i18n="f_msg">Mesajınız</label>
          <textarea required></textarea>
        </div>
        <button type="submit" class="nv-btn nv-btn-primary" style="padding:13px 28px;font-size:14px;width:100%;justify-content:center">
          <i class="fa-solid fa-paper-plane"></i> <span data-i18n="f_send">Mesajı Gönder</span>
        </button>
      </form>
    </div>
  </div>
</section>

<!-- ═══════════════ FOOTER ═══════════════ -->
<footer class="nv-footer">
  <div class="container">
    <div class="nv-footer-grid">
      <div>
        <div class="nv-footer-brand">
          <div class="nv-logo">
            <?php if (!empty($logo)): ?>
              <img src="<?= htmlspecialchars($logo) ?>" alt="<?= $s('company_name','Nuverna') ?>">
            <?php else: ?>
              <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M22 78 V22 L78 78 V22" stroke="white" stroke-width="11" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
              </svg>
            <?php endif; ?>
          </div>
          <div class="nv-brand-text">
            <?= $s('company_name','Nuverna') ?>
            <small data-i18n="brand_tag"><?= $s('tagline','Trade · Fresh Export') ?></small>
          </div>
        </div>
        <p data-i18n="footer_desc">Türkiye merkezli premium kalite yaş sebze ve meyve ihracatı. Avrupa, Körfez, Mısır ve Rusya'ya güvenilir tedarik.</p>
      </div>
      <div>
        <h4 data-i18n="f_links">Hızlı Bağlantılar</h4>
        <ul>
          <li><a href="#about" data-i18n="nav_about">Hakkımızda</a></li>
          <li><a href="#products" data-i18n="nav_products">Ürünler</a></li>
          <li><a href="#markets" data-i18n="nav_markets">İhracat Bölgeleri</a></li>
          <li><a href="#gallery" data-i18n="nav_gallery">Galeri</a></li>
          <li><a href="<?= BASE_URL ?>/giris" data-i18n="nav_login">Giriş Yap</a></li>
        </ul>
      </div>
      <div>
        <h4 data-i18n="f_products">Ürünler</h4>
        <ul>
          <li><a href="#products" data-i18n="prod_cherries">Kiraz</a></li>
          <li><a href="#products" data-i18n="prod_apricots">Kayısı</a></li>
          <li><a href="#products" data-i18n="prod_peaches">Şeftali</a></li>
          <li><a href="#products" data-i18n="prod_watermelon">Karpuz</a></li>
          <li><a href="#products" data-i18n="prod_tomatoes">Domates</a></li>
        </ul>
      </div>
      <div>
        <h4 data-i18n="f_contact">İletişim</h4>
        <p style="margin-top:0">
          <?php if (!empty($ayarlar['address'])): ?><i class="fa-solid fa-location-dot"></i> <?= $s('address') ?><br><?php endif; ?>
          <?php if (!empty($ayarlar['email'])): ?><i class="fa-solid fa-envelope"></i> <?= $s('email') ?><br><?php endif; ?>
          <?php if (!empty($ayarlar['phone'])): ?><i class="fa-solid fa-phone"></i> <?= $s('phone') ?><br><?php endif; ?>
          <?php if (!empty($ayarlar['whatsapp'])): ?><i class="fa-brands fa-whatsapp"></i> <?= $s('whatsapp') ?><?php endif; ?>
        </p>
      </div>
    </div>
    <div class="nv-footer-bottom">
      <span>© <?= date('Y') ?> Nuverna Trade. <span data-i18n="rights">Tüm hakları saklıdır.</span></span>
      <span data-i18n="tagline">Fresh Fruits &amp; Vegetables Export from Türkiye</span>
    </div>
  </div>
</footer>

<!-- ═══════════════ FLOATING / MOBILE BAR ═══════════════ -->
<div class="nv-product-modal" id="nvProductModal" aria-hidden="true">
  <div class="nv-product-dialog" role="dialog" aria-modal="true" aria-labelledby="nvProductModalTitle">
    <div class="nv-product-modal-img" id="nvProductModalImg">
      <span class="nv-product-modal-tag" id="nvProductModalTag">FRESH</span>
    </div>
    <div class="nv-product-modal-body">
      <button class="nv-product-modal-close" type="button" id="nvProductModalClose" aria-label="Kapat">
        <i class="fa-solid fa-xmark"></i>
      </button>
      <h3 id="nvProductModalTitle"></h3>
      <p id="nvProductModalDesc"></p>
      <div class="nv-product-modal-actions">
        <a class="nv-btn nv-btn-primary" href="#contact" id="nvProductModalContact">
          <i class="fa-solid fa-envelope-open-text"></i> <span data-i18n="hero_btn_contact">İletişime Geç</span>
        </a>
        <?php if ($waLink): ?>
        <a class="nv-btn nv-btn-ghost" href="<?= htmlspecialchars($waLink) ?>" target="_blank" rel="noopener">
          <i class="fa-brands fa-whatsapp"></i> WhatsApp
        </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php if ($waLink): ?>
<a href="<?= htmlspecialchars($waLink) ?>" target="_blank" rel="noopener" class="nv-fab-whatsapp" title="WhatsApp">
  <i class="fa-brands fa-whatsapp"></i>
</a>
<?php endif; ?>
<button class="nv-scroll-top" id="nvScrollTop" aria-label="Yukarı"><i class="fa-solid fa-arrow-up"></i></button>

<nav class="nv-mobile-bar">
  <a href="#hero"><i class="fa-solid fa-house"></i><span data-i18n="m_home">Anasayfa</span></a>
  <a href="#products"><i class="fa-solid fa-leaf"></i><span data-i18n="m_prods">Ürünler</span></a>
  <a href="#gallery"><i class="fa-solid fa-images"></i><span data-i18n="nav_gallery">Galeri</span></a>
  <a href="#contact"><i class="fa-solid fa-envelope"></i><span data-i18n="m_contact">İletişim</span></a>
</nav>

<!-- ═══════════════ I18N + JS ═══════════════ -->
<script>
const i18n = {
  tr: {
    meta_title:"Nuverna Trade | Türkiye'den Taze Meyve & Sebze İhracatı",
    meta_desc:"Nuverna Trade — Türkiye'den Avrupa, Körfez, Mısır ve Rusya'ya premium kalite taze meyve-sebze ihracatı. Özenli paketleme, güvenilir sevkiyat.",
    brand_tag:"Trade · Fresh Export",
    nav_home:"Ana Sayfa", nav_about:"Hakkımızda", nav_products:"Ürünler", nav_markets:"İhracat Bölgeleri",
    nav_services:"Hizmetler", nav_why:"Neden Nuverna?", nav_gallery:"Galeri", nav_contact:"İletişim",
    nav_login:"Giriş Yap", nav_panel:"Panele Git",
    hero_eyebrow:"Türkiye'den Dünyaya İhracat",
    hero_title_1:"Anadolu'dan dünyaya", hero_title_2:"taze ihracat",
    hero_desc:"Nuverna Trade, Anadolu'nun en kaliteli yaş sebze ve meyvelerini özenle seçer, paketler ve dünya pazarlarına güvenle ulaştırır. Avrupa, Dubai, Suudi Arabistan, Mısır ve Rusya'ya premium kalite ihracat çözümleri.",
    hero_btn_products:"Ürünleri İncele", hero_btn_contact:"İletişime Geç",
    stat_countries:"Ülkeye İhracat", stat_products:"Ürün Çeşidi", stat_quality:"Kalite Kontrol",
    badge_v:"Premium", badge_l:"Quality",
    slide1_tag:"Yeni Hasat", slide1_title:"Taze Meyve İhracatı", slide1_desc:"Anadolu'nun verimli topraklarından özenle seçilen taze meyveler.",
    slide2_tag:"Premium Paketleme", slide2_title:"Kalite Kontrol & Paketleme", slide2_desc:"Her ürün, hedef pazara uygun standartta kontrol edilir ve paketlenir.",
    slide3_tag:"Global Ulaşım", slide3_title:"Avrupa, Körfez, Rusya Pazarlarına", slide3_desc:"15+ ülkeye düzenli ve güvenilir sevkiyat ağı.",
    about_tag:"Hakkımızda", about_title:"Kalite, güven ve süreklilik üzerine kurulu bir ihracat firması.",
    about_p1:"Nuverna Trade, Türkiye'nin verimli topraklarında yetişen kaliteli yaş sebze ve meyveleri uluslararası pazarlara ulaştıran profesyonel bir ihracat firmasıdır. Ürünlerin seçimi, kalite kontrolü, paketlenmesi ve sevkiyat sürecinde titizlikle hareket ederiz.",
    about_p2:"Amacımız; müşterilerimize süreklilik, kalite ve güven sunmaktır. Her bir kasanın arkasında titizlikle çalışan bir ekip vardır.",
    about_li1:"Kalite odaklı yaklaşım ve tedarik ağı", about_li2:"Hedef pazara uygun paketleme standartları",
    about_li3:"Yıllık deneyim ve güvenilir operasyon", about_li4:"Zamanında teslimat ve müşteri memnuniyeti",
    about_stat:"Ülkeye Düzenli İhracat",
    products_tag:"Ürünlerimiz", products_title:"Premium kalite, taze ürün yelpazesi",
    products_desc:"Anadolu'nun bereketli topraklarından dünya soframıza uzanan taze ürünler.",
    prod_cherries:"Kiraz", prod_cherries_d:"Premium taze kiraz, ihracata uygun paketleme.",
    prod_apricots:"Kayısı", prod_apricots_d:"Aromatik, doğal şekerlik tanımlanmış kayısı.",
    prod_peaches:"Şeftali", prod_peaches_d:"Sulu, parlak yüzeyli birinci sınıf şeftali.",
    prod_nectarines:"Nektarin", prod_nectarines_d:"Kabuğu sıkı, içi sulu, sevkiyata dayanıklı.",
    prod_pears:"Armut", prod_pears_d:"Sert dokulu, uzun raf ömürlü armut çeşitleri.",
    prod_watermelon:"Karpuz", prod_watermelon_d:"Yüksek brix değeri, mevsiminde hasat.",
    prod_tomatoes:"Domates", prod_tomatoes_d:"Salkım, çeri ve standart sera domatesleri.",
    prod_cucumber:"Salatalık", prod_cucumber_d:"Sera ve tarla, çeşitli gramaj seçenekleri.",
    markets_tag:"İhracat Bölgeleri", markets_title:"Türkiye'den dünyaya uzanan tedarik ağı",
    markets_desc:"Farklı pazarların kalite ve paketleme beklentilerine uygun çözümler sunarız.",
    market_eu:"Avrupa", market_eu_d:"Almanya · Hollanda · Polonya",
    market_uae:"BAE / Dubai", market_uae_d:"Premium Pazar",
    market_sa:"Suudi Arabistan", market_sa_d:"Körfez Ülkeleri",
    market_eg:"Mısır", market_eg_d:"Kuzey Afrika",
    market_ru:"Rusya", market_ru_d:"BDT Bölgesi",
    services_tag:"Hizmetlerimiz", services_title:"Uçtan uca ihracat çözümleri",
    services_desc:"Tedarik, kontrol, paketleme, sevkiyat — her aşama tek elden.",
    srv1_t:"Ürün Tedariki", srv1_d:"Türkiye'nin en verimli üretim bölgelerinden doğrudan tedarik.",
    srv2_t:"Kalite Kontrol", srv2_d:"Hedef pazar standartlarına uygun titiz inceleme ve ayrım.",
    srv3_t:"Paketleme Çözümleri", srv3_d:"Müşteri ve ülke spesifik ambalajlama, etiketleme.",
    srv4_t:"İhracat Operasyonu", srv4_d:"Gümrük, evrak ve uluslararası sevk süreç yönetimi.",
    srv5_t:"Lojistik Koordinasyon", srv5_d:"Soğuk zincir, kara/deniz/hava tercih bazlı planlama.",
    srv6_t:"Müşteri Odaklı Tedarik", srv6_d:"Talebe özel kalibre, paket ve teslim çözümleri.",
    why_tag:"Neden Nuverna?", why_title:"Güveniniz, kalitemizden başlar.",
    why_desc:"Her aşamada müşteri memnuniyetine, ürün kalitesine ve operasyon güvenliğine odaklanırız.",
    why1_t:"Güvenilir Tedarik", why1_d:"Doğrulanmış üretim noktaları ve sürekli kalite takibi.",
    why2_t:"Taze ve Premium", why2_d:"Hasat, paketleme ve sevkiyat arasındaki süre minimumda.",
    why3_t:"Global Erişim", why3_d:"Avrupa, Körfez, Rusya'ya düzenli ihracat hatları.",
    why4_t:"Esnek İletişim", why4_d:"Türkçe, İngilizce ve Rusça hızlı yanıt veren ekip.",
    proc_tag:"Süreç", proc_title:"Tarladan dünyaya 4 adımda",
    proc_desc:"Her ürün, müşteri beklentisine uygun şekilde seçilir, kontrol edilir, paketlenir ve sevkiyata hazırlanır.",
    proc1_t:"Ürün Seçimi", proc1_d:"Verim alanlarından özenle seçilen kaliteli ürünler.",
    proc2_t:"Kalite Kontrol", proc2_d:"Boyut, renk, sağlamlık ve hijyen kontrolü.",
    proc3_t:"Paketleme", proc3_d:"Hedef pazara uygun ambalaj ve etiketleme.",
    proc4_t:"Sevkiyat", proc4_d:"Soğuk zincir ile zamanında teslim.",
    gallery_tag:"Galeri", gallery_title:"Sahadan kareler", gallery_desc:"Üretim, paketleme ve sevkiyat süreçlerinden seçilmiş anlar.",
    g_field:"Üretim Alanı", g_pack:"Paketleme", g_quality:"Kalite Kontrol", g_market:"Hal / Pazar",
    g_fruit:"Taze Meyve", g_export:"Sevkiyat", g_pear:"Armut Hasadı", g_tomato:"Domates",
    contact_tag:"İletişim", contact_title:"Sizinle iş birliği yapmaya hazırız",
    contact_desc:"Teklif, ürün listesi ve sevkiyat seçenekleri için bize ulaşın.",
    contact_intro:"Türkiye merkezli ihracat operasyonumuzla 7/24 ulaşılabiliriz. Mesajınızı bekliyoruz.",
    c_addr:"Adres", c_mail:"E-Posta", c_wa:"WhatsApp", c_web:"Web",
    f_name:"Ad Soyad", f_company:"Firma", f_mail:"E-Posta", f_phone:"Telefon / WhatsApp",
    f_msg:"Mesajınız", f_send:"Mesajı Gönder",
    f_links:"Hızlı Bağlantılar", f_products:"Ürünler", f_contact:"İletişim",
    footer_desc:"Türkiye merkezli premium kalite yaş sebze ve meyve ihracatı. Avrupa, Körfez, Mısır ve Rusya'ya güvenilir tedarik.",
    rights:"Tüm hakları saklıdır.", tagline:"Fresh Fruits & Vegetables Export from Türkiye",
    m_home:"Anasayfa", m_prods:"Ürünler", m_market:"Bölgeler", m_contact:"İletişim", m_login:"Giriş",
  },

  en: {
    meta_title:"Nuverna Trade | Fresh Fruits & Vegetables Export from Türkiye",
    meta_desc:"Nuverna Trade — premium fresh produce export from Türkiye to Europe, GCC, Egypt and Russia. Quality packaging and reliable logistics.",
    brand_tag:"Trade · Fresh Export",
    nav_home:"Home", nav_about:"About", nav_products:"Products", nav_markets:"Export Markets",
    nav_services:"Services", nav_why:"Why Nuverna?", nav_gallery:"Gallery", nav_contact:"Contact",
    nav_login:"Sign In", nav_panel:"Open Panel",
    hero_eyebrow:"From Türkiye to the World",
    hero_title_1:"Fresh export from", hero_title_2:"Anatolia to the world",
    hero_desc:"Nuverna Trade carefully sources, packages and ships premium fresh fruits & vegetables from Türkiye to Europe, Dubai, Saudi Arabia, Egypt and Russia.",
    hero_btn_products:"Explore Products", hero_btn_contact:"Get in Touch",
    stat_countries:"Export Countries", stat_products:"Product Range", stat_quality:"Quality Controlled",
    badge_v:"Premium", badge_l:"Quality",
    slide1_tag:"Fresh Harvest", slide1_title:"Fresh Fruit Export", slide1_desc:"Hand-picked from Anatolia's fertile soils.",
    slide2_tag:"Premium Packing", slide2_title:"Quality Control & Packaging", slide2_desc:"Every product is checked and packed to target market standards.",
    slide3_tag:"Global Reach", slide3_title:"Europe, Gulf and Russia", slide3_desc:"Reliable shipping network to 15+ countries.",
    about_tag:"About Us", about_title:"An export firm built on quality, trust and continuity.",
    about_p1:"Nuverna Trade is a professional export company delivering premium fresh fruits and vegetables grown in Türkiye to international markets, with rigorous quality control at every step.",
    about_p2:"Our purpose is to provide our clients with continuity, quality and trust. There's a dedicated team behind every shipment.",
    about_li1:"Quality-focused sourcing and supply network", about_li2:"Packaging tailored to each market",
    about_li3:"Years of export experience", about_li4:"On-time delivery and customer satisfaction",
    about_stat:"Countries with regular export",
    products_tag:"Our Products", products_title:"Premium quality, fresh range",
    products_desc:"From Anatolia's bountiful soils to global tables.",
    prod_cherries:"Cherries", prod_cherries_d:"Premium fresh cherries, export-grade packaging.",
    prod_apricots:"Apricots", prod_apricots_d:"Aromatic apricots with ideal sugar profile.",
    prod_peaches:"Peaches", prod_peaches_d:"Juicy, glossy first-class peaches.",
    prod_nectarines:"Nectarines", prod_nectarines_d:"Firm-skinned, juicy and shipping-friendly.",
    prod_pears:"Pears", prod_pears_d:"Firm texture, long shelf-life varieties.",
    prod_watermelon:"Watermelon", prod_watermelon_d:"High brix value, harvested in season.",
    prod_tomatoes:"Tomatoes", prod_tomatoes_d:"Cluster, cherry and standard greenhouse types.",
    prod_cucumber:"Cucumber", prod_cucumber_d:"Greenhouse and field, multiple sizes.",
    markets_tag:"Export Markets", markets_title:"Supply network from Türkiye to the world",
    markets_desc:"Solutions tailored to the quality and packaging expectations of each market.",
    market_eu:"Europe", market_eu_d:"Germany · NL · Poland",
    market_uae:"UAE / Dubai", market_uae_d:"Premium Market",
    market_sa:"Saudi Arabia", market_sa_d:"Gulf Region",
    market_eg:"Egypt", market_eg_d:"North Africa",
    market_ru:"Russia", market_ru_d:"CIS Region",
    services_tag:"Our Services", services_title:"End-to-end export solutions",
    services_desc:"Sourcing, control, packing, shipping — all under one roof.",
    srv1_t:"Sourcing", srv1_d:"Direct sourcing from Türkiye's most productive regions.",
    srv2_t:"Quality Control", srv2_d:"Rigorous inspection aligned with destination standards.",
    srv3_t:"Packaging", srv3_d:"Customer- and country-specific packing & labelling.",
    srv4_t:"Export Operations", srv4_d:"Customs, paperwork and international shipment management.",
    srv5_t:"Logistics", srv5_d:"Cold chain planning across road, sea and air.",
    srv6_t:"Customer-Focused Supply", srv6_d:"On-demand calibre, pack and delivery solutions.",
    why_tag:"Why Nuverna?", why_title:"Your trust starts with our quality.",
    why_desc:"We focus on customer satisfaction, product quality and operational safety at every step.",
    why1_t:"Reliable Sourcing", why1_d:"Verified production points and continuous QC.",
    why2_t:"Fresh & Premium", why2_d:"Minimum time between harvest, pack and ship.",
    why3_t:"Global Reach", why3_d:"Regular export lanes to Europe, Gulf, Russia.",
    why4_t:"Flexible Communication", why4_d:"A team responsive in Turkish, English and Russian.",
    proc_tag:"Process", proc_title:"From farm to global, in 4 steps",
    proc_desc:"Each product is selected, controlled, packaged and shipped according to client expectations.",
    proc1_t:"Selection", proc1_d:"Hand-picked from productive growing zones.",
    proc2_t:"Quality Control", proc2_d:"Size, color, firmness and hygiene inspection.",
    proc3_t:"Packaging", proc3_d:"Market-specific packaging and labelling.",
    proc4_t:"Shipment", proc4_d:"On-time delivery via cold chain.",
    gallery_tag:"Gallery", gallery_title:"Frames from the field", gallery_desc:"Selected moments from production, packaging and shipping.",
    g_field:"Field", g_pack:"Packing", g_quality:"QC", g_market:"Market",
    g_fruit:"Fresh Fruit", g_export:"Shipping", g_pear:"Pear Harvest", g_tomato:"Tomato",
    contact_tag:"Contact", contact_title:"Ready to partner with you",
    contact_desc:"Reach out for quotations, product lists and shipping options.",
    contact_intro:"Our Türkiye-based export team is reachable around the clock. We're waiting for your message.",
    c_addr:"Address", c_mail:"E-Mail", c_wa:"WhatsApp", c_web:"Web",
    f_name:"Full Name", f_company:"Company", f_mail:"E-Mail", f_phone:"Phone / WhatsApp",
    f_msg:"Your Message", f_send:"Send Message",
    f_links:"Quick Links", f_products:"Products", f_contact:"Contact",
    footer_desc:"Türkiye-based premium fresh fruits and vegetables export. Reliable supply to Europe, Gulf, Egypt and Russia.",
    rights:"All rights reserved.", tagline:"Fresh Fruits & Vegetables Export from Türkiye",
    m_home:"Home", m_prods:"Products", m_market:"Markets", m_contact:"Contact", m_login:"Sign In",
  },

  ru: {
    meta_title:"Nuverna Trade | Экспорт свежих фруктов и овощей из Турции",
    meta_desc:"Nuverna Trade — премиум-экспорт свежих фруктов и овощей из Турции в Европу, Залив, Египет и Россию. Качественная упаковка, надёжная логистика.",
    brand_tag:"Trade · Свежий экспорт",
    nav_home:"Главная", nav_about:"О нас", nav_products:"Продукция", nav_markets:"Рынки экспорта",
    nav_services:"Услуги", nav_why:"Почему Nuverna?", nav_gallery:"Галерея", nav_contact:"Контакты",
    nav_login:"Войти", nav_panel:"Перейти в панель",
    hero_eyebrow:"Из Турции в мир",
    hero_title_1:"Из Анатолии в мир —", hero_title_2:"свежий экспорт",
    hero_desc:"Nuverna Trade тщательно отбирает, упаковывает и поставляет свежие фрукты и овощи премиум-качества из Турции в Европу, Дубай, Саудовскую Аравию, Египет и Россию.",
    hero_btn_products:"Смотреть продукцию", hero_btn_contact:"Связаться",
    stat_countries:"Страны экспорта", stat_products:"Виды продукции", stat_quality:"Контроль качества",
    badge_v:"Премиум", badge_l:"Качество",
    slide1_tag:"Свежий урожай", slide1_title:"Экспорт свежих фруктов", slide1_desc:"Тщательно отобраны на плодородных полях Анатолии.",
    slide2_tag:"Премиум упаковка", slide2_title:"Контроль и упаковка", slide2_desc:"Каждый продукт проверяется и упаковывается под стандарты целевого рынка.",
    slide3_tag:"Глобальная доставка", slide3_title:"Европа, Залив, Россия", slide3_desc:"Надёжная сеть отгрузок в 15+ стран.",
    about_tag:"О нас", about_title:"Экспортная компания, построенная на качестве и доверии.",
    about_p1:"Nuverna Trade — профессиональная экспортная компания, поставляющая премиум-фрукты и овощи из Турции на международные рынки с тщательным контролем на каждом этапе.",
    about_p2:"Наша цель — стабильность, качество и доверие. За каждой поставкой стоит преданная команда.",
    about_li1:"Сеть надёжных поставщиков", about_li2:"Упаковка под каждый рынок",
    about_li3:"Многолетний опыт экспорта", about_li4:"Своевременные поставки",
    about_stat:"Страны регулярного экспорта",
    products_tag:"Наша продукция", products_title:"Премиум-качество, свежий ассортимент",
    products_desc:"С плодородных земель Анатолии — на стол всему миру.",
    prod_cherries:"Черешня", prod_cherries_d:"Свежая премиум-черешня в экспортной упаковке.",
    prod_apricots:"Абрикосы", prod_apricots_d:"Ароматные абрикосы с идеальным балансом сладости.",
    prod_peaches:"Персики", prod_peaches_d:"Сочные, глянцевые персики первого сорта.",
    prod_nectarines:"Нектарины", prod_nectarines_d:"Плотная кожица, сочные, удобны при транспортировке.",
    prod_pears:"Груши", prod_pears_d:"Плотная текстура, долгий срок хранения.",
    prod_watermelon:"Арбуз", prod_watermelon_d:"Высокий брикс, сезонный сбор.",
    prod_tomatoes:"Помидоры", prod_tomatoes_d:"Кисти, черри и стандартные тепличные сорта.",
    prod_cucumber:"Огурцы", prod_cucumber_d:"Тепличные и грунтовые, разные калибры.",
    markets_tag:"Рынки экспорта", markets_title:"Снабжающая сеть из Турции в мир",
    markets_desc:"Решения под качество и упаковку каждого целевого рынка.",
    market_eu:"Европа", market_eu_d:"Германия · NL · Польша",
    market_uae:"ОАЭ / Дубай", market_uae_d:"Премиум-рынок",
    market_sa:"Саудовская Аравия", market_sa_d:"Регион Залива",
    market_eg:"Египет", market_eg_d:"Северная Африка",
    market_ru:"Россия", market_ru_d:"СНГ",
    services_tag:"Услуги", services_title:"Полный цикл экспорта",
    services_desc:"Закупка, контроль, упаковка, отгрузка — всё в одном месте.",
    srv1_t:"Поставка", srv1_d:"Прямая закупка в самых продуктивных регионах Турции.",
    srv2_t:"Контроль качества", srv2_d:"Тщательная проверка под стандарты пункта назначения.",
    srv3_t:"Упаковка", srv3_d:"Упаковка и маркировка под клиента и страну.",
    srv4_t:"Экспортные операции", srv4_d:"Таможня, документы и международная отгрузка.",
    srv5_t:"Логистика", srv5_d:"Холодовая цепь — авто, море, авиа.",
    srv6_t:"Под клиента", srv6_d:"Калибровка, упаковка и доставка по запросу.",
    why_tag:"Почему Nuverna?", why_title:"Ваше доверие начинается с нашего качества.",
    why_desc:"Мы сфокусированы на качестве, удовлетворённости клиентов и безопасности операций.",
    why1_t:"Надёжная поставка", why1_d:"Проверенные пункты производства и постоянный КК.",
    why2_t:"Свежесть и премиум", why2_d:"Минимум времени между сбором, упаковкой и отгрузкой.",
    why3_t:"Глобальный охват", why3_d:"Регулярные линии в Европу, Залив, Россию.",
    why4_t:"Гибкое общение", why4_d:"Команда отвечает на турецком, английском и русском.",
    proc_tag:"Процесс", proc_title:"От поля до мира — 4 шага",
    proc_desc:"Каждый продукт отбирается, контролируется, упаковывается и отправляется под ожидания клиента.",
    proc1_t:"Отбор", proc1_d:"Ручной отбор в продуктивных зонах.",
    proc2_t:"Контроль", proc2_d:"Размер, цвет, плотность и гигиена.",
    proc3_t:"Упаковка", proc3_d:"Упаковка и маркировка под рынок.",
    proc4_t:"Отгрузка", proc4_d:"Своевременная доставка по холодовой цепи.",
    gallery_tag:"Галерея", gallery_title:"Кадры с поля", gallery_desc:"Избранные моменты с производства, упаковки и отгрузки.",
    g_field:"Поле", g_pack:"Упаковка", g_quality:"КК", g_market:"Рынок",
    g_fruit:"Свежие фрукты", g_export:"Отгрузка", g_pear:"Сбор груш", g_tomato:"Помидоры",
    contact_tag:"Контакты", contact_title:"Готовы к сотрудничеству",
    contact_desc:"Свяжитесь для коммерческого предложения, прайса и условий отгрузки.",
    contact_intro:"Наша экспортная команда в Турции на связи 24/7. Ждём вашего сообщения.",
    c_addr:"Адрес", c_mail:"E-mail", c_wa:"WhatsApp", c_web:"Сайт",
    f_name:"Имя и фамилия", f_company:"Компания", f_mail:"E-mail", f_phone:"Телефон / WhatsApp",
    f_msg:"Сообщение", f_send:"Отправить",
    f_links:"Быстрые ссылки", f_products:"Продукция", f_contact:"Контакты",
    footer_desc:"Премиум-экспорт свежих фруктов и овощей из Турции. Надёжные поставки в Европу, Залив, Египет и Россию.",
    rights:"Все права защищены.", tagline:"Fresh Fruits & Vegetables Export from Türkiye",
    m_home:"Главная", m_prods:"Продукция", m_market:"Рынки", m_contact:"Контакты", m_login:"Войти",
  }
};

const i18nOverrides = <?= json_encode($i18nOverrides, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}' ?>;
Object.entries(i18nOverrides).forEach(([lang, texts]) => {
  if (!i18n[lang]) i18n[lang] = {};
  Object.assign(i18n[lang], texts);
});

const langFlags = { tr: 'tr', en: 'gb', ru: 'ru' };
let currentLang = localStorage.getItem('nv_lang') || 'tr';

function setLang(lang) {
  if (!i18n[lang]) lang = 'tr';
  currentLang = lang;
  document.documentElement.lang = lang;
  localStorage.setItem('nv_lang', lang);

  document.querySelectorAll('[data-i18n]').forEach(el => {
    const key = el.getAttribute('data-i18n');
    const attr = el.getAttribute('data-i18n-attr');
    const txt = i18n[lang][key];
    if (txt === undefined) return;
    if (attr) el.setAttribute(attr, txt);
    else el.textContent = txt;
  });

  // Update flag/code in switcher
  document.getElementById('nvLangFlag').src = `https://flagcdn.com/w40/${langFlags[lang]}.png`;
  document.getElementById('nvLangCode').textContent = lang.toUpperCase();
  document.querySelectorAll('#nvLangMenu button').forEach(b => b.classList.toggle('active', b.dataset.lang === lang));

  document.querySelectorAll('.nv-product').forEach(card => {
    const suffix = lang.charAt(0).toUpperCase() + lang.slice(1);
    const name = card.dataset[`name${suffix}`] || card.dataset.nameTr || '';
    const desc = card.dataset[`desc${suffix}`] || card.dataset.descTr || '';
    card.querySelector('.nv-product-name').textContent = name;
    card.querySelector('.nv-product-desc').textContent = desc;
  });

  document.querySelectorAll('.nv-gallery-item').forEach(item => {
    const suffix = lang.charAt(0).toUpperCase() + lang.slice(1);
    const tag = item.dataset[`tag${suffix}`] || item.dataset.tagTr || '';
    const label = item.querySelector('.nv-gallery-tag-text');
    if (label) label.textContent = tag;
  });
}

// Init lang
setLang(localStorage.getItem('nv_lang') || 'tr');

// Lang dropdown
const langBtn = document.getElementById('nvLangBtn');
const langMenu = document.getElementById('nvLangMenu');
langBtn.addEventListener('click', e => { e.stopPropagation(); langMenu.classList.toggle('open'); });
document.addEventListener('click', () => langMenu.classList.remove('open'));
document.querySelectorAll('#nvLangMenu button').forEach(b => {
  b.addEventListener('click', () => { setLang(b.dataset.lang); langMenu.classList.remove('open'); });
});

const productModal = document.getElementById('nvProductModal');
const productModalImg = document.getElementById('nvProductModalImg');
const productModalTag = document.getElementById('nvProductModalTag');
const productModalTitle = document.getElementById('nvProductModalTitle');
const productModalDesc = document.getElementById('nvProductModalDesc');

function productText(card, field) {
  const suffix = currentLang.charAt(0).toUpperCase() + currentLang.slice(1);
  return card.dataset[`${field}${suffix}`] || card.dataset[`${field}Tr`] || '';
}

function openProductModal(card) {
  productModalImg.style.backgroundImage = `url("${(card.dataset.image || '').replace(/"/g, '%22')}")`;
  productModalTag.textContent = card.dataset.tag || 'FRESH';
  productModalTitle.textContent = productText(card, 'name');
  productModalDesc.textContent = productText(card, 'desc');
  productModal.classList.add('open');
  productModal.setAttribute('aria-hidden', 'false');
  document.body.style.overflow = 'hidden';
}

function closeProductModal() {
  productModal.classList.remove('open');
  productModal.setAttribute('aria-hidden', 'true');
  document.body.style.overflow = '';
}

document.querySelectorAll('.nv-product').forEach(card => {
  card.addEventListener('click', () => openProductModal(card));
  card.addEventListener('keydown', e => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      openProductModal(card);
    }
  });
});
document.getElementById('nvProductModalClose').addEventListener('click', closeProductModal);
productModal.addEventListener('click', e => {
  if (e.target === productModal) closeProductModal();
});
document.getElementById('nvProductModalContact').addEventListener('click', closeProductModal);
document.addEventListener('keydown', e => {
  if (e.key === 'Escape' && productModal.classList.contains('open')) closeProductModal();
});

// Header scroll effect
const header = document.getElementById('nvHeader');
window.addEventListener('scroll', () => header.classList.toggle('scrolled', window.scrollY > 30));

// Mobile menu
const mTog = document.getElementById('nvMobileToggle');
const mMenu = document.getElementById('nvMobileMenu');
const mClose = document.getElementById('nvMobileClose');
const overlay = document.getElementById('nvOverlay');
const openMobile = () => { mMenu.classList.add('open'); overlay.classList.add('open'); };
const closeMobile = () => { mMenu.classList.remove('open'); overlay.classList.remove('open'); };
mTog.addEventListener('click', openMobile);
mClose.addEventListener('click', closeMobile);
overlay.addEventListener('click', closeMobile);
mMenu.querySelectorAll('a').forEach(a => a.addEventListener('click', closeMobile));

// Hero slider
const slides = document.querySelectorAll('.nv-slide');
const dots   = document.querySelectorAll('.nv-slide-dots button');
let curSlide = 0;
function showSlide(idx) {
  slides.forEach((s,i)=>s.classList.toggle('active', i===idx));
  dots.forEach((d,i)=>d.classList.toggle('active', i===idx));
  curSlide = idx;
}
dots.forEach((d,i)=>d.addEventListener('click', ()=>showSlide(i)));
setInterval(()=>showSlide((curSlide+1)%slides.length), 5500);

// Scroll to top
const scrollBtn = document.getElementById('nvScrollTop');
window.addEventListener('scroll', () => scrollBtn.classList.toggle('show', window.scrollY > 600));
scrollBtn.addEventListener('click', () => window.scrollTo({top:0, behavior:'smooth'}));
</script>

</body>
</html>
