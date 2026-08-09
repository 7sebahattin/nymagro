<?php
/**
 * Public Website Layout (TR / EN / RU)
 * --------------------------------------------------------
 * Sticky header, dil seçici, mobil alt bar, footer.
 * Sayfa içeriği: app/views/web/{$pageView}.php
 *
 * Tüm public sayfalar bu layout üzerinden render edilir.
 */
$locale     = $locale     ?? 'tr';
$ayarlar    = $ayarlar    ?? SiteIcerik::DEFAULT_SETTINGS;
$currentPage= $currentPage ?? 'home';
$pageView   = $pageView   ?? 'home';
$seo        = $seo        ?? null;

$logoPath = (string)($ayarlar['logo_path'] ?? '');
if ($logoPath === '') {
    // Panelden logo yüklenmemişse depodaki marka logosuna düş
    $logoPath = '/img/nymagro-logo.png';
}
if (!preg_match('~^(?:https?:)?//~i', $logoPath)) {
    if (!str_starts_with($logoPath, '/')) $logoPath = '/' . $logoPath;
    $logoPath = BASE_URL . $logoPath;
}
$brand     = (string)($ayarlar['company_name'] ?? 'Nymagro');
$tagline   = (string)($ayarlar['tagline']      ?? 'Bitki Besleme Ürünleri');
$whatsapp  = (string)($ayarlar['whatsapp']     ?? '');
$waLink    = (string)($ayarlar['whatsapp_link']?? '');
$email     = (string)($ayarlar['email']        ?? '');
$phone     = (string)($ayarlar['phone']        ?? '');
$address   = (string)($ayarlar['address']      ?? '');
$siteText = static function(string $key, string $fallback = '') use ($ayarlar, $locale): string {
    $settingKey = 'i18n_' . $locale . '_' . $key;
    if (isset($ayarlar[$settingKey]) && trim((string)$ayarlar[$settingKey]) !== '') {
        return (string)$ayarlar[$settingKey];
    }
    if ($locale === 'tr' && isset($ayarlar[$key]) && trim((string)$ayarlar[$key]) !== '') {
        return (string)$ayarlar[$key];
    }
    return $fallback;
};

$navItems = [
    ['key'=>'home',          'pageKey'=>'home',          'label'=>I18n::t('common.home')],
    ['key'=>'about',         'pageKey'=>'about',         'label'=>I18n::t('common.about')],
    ['key'=>'products',      'pageKey'=>'products',      'label'=>I18n::t('common.products')],
    ['key'=>'quality',       'pageKey'=>'quality',       'label'=>I18n::t('common.quality')],
    ['key'=>'services',      'pageKey'=>'services',      'label'=>I18n::t('common.services')],
    ['key'=>'gallery',       'pageKey'=>'gallery',       'label'=>I18n::t('common.gallery')],
    ['key'=>'contact',       'pageKey'=>'contact',       'label'=>I18n::t('common.contact')],
];
// flag-icons CSS sınıf kodları (fi-xx)
$languageFlags = [
    'tr' => 'tr',   // Türkiye
    'en' => 'gb',   // Great Britain / English
    'ru' => 'ru',   // Rusya
];
?>
<!doctype html>
<html lang="<?= htmlspecialchars($locale) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="theme-color" content="#0d623a">
<?php if ($seo): ?>
<?= $seo->renderHead() ?>
<?php endif; ?>

<link rel="icon" href="<?= htmlspecialchars(BASE_URL) ?>/favicon.ico?v=20260501" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="<?= htmlspecialchars(BASE_URL) ?>/favicon-32x32.png?v=20260501">
<link rel="icon" type="image/png" sizes="16x16" href="<?= htmlspecialchars(BASE_URL) ?>/favicon-16x16.png?v=20260501">
<link rel="apple-touch-icon" sizes="180x180" href="<?= htmlspecialchars(BASE_URL) ?>/apple-touch-icon.png?v=20260501">
<link rel="manifest" href="<?= htmlspecialchars(BASE_URL) ?>/site.webmanifest?v=20260501">

<!-- iOS'ta "Ana Ekrana Ekle" ile tam ekran uygulama görünümü -->
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Nymagro">
<meta name="mobile-web-app-capable" content="yes">

<!-- Bootstrap & Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/lipis/flag-icons@7.2.3/css/flag-icons.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@500;700;800&display=swap">

<style>
:root{
  --p1:#0d623a; --p2:#1e8c55; --p3:#d97a0c;
  --ink:#06281a; --ink2:#356850;
  --grad:linear-gradient(135deg,#0d623a 0%,#1e8c55 100%);
  --grad-cta:linear-gradient(135deg,#f3911f 0%,#d97a0c 100%);
  --shadow:0 24px 80px -32px rgba(13,98,58,.45);
  --radius:18px;
}
*{box-sizing:border-box}
html{scroll-behavior:smooth;-webkit-tap-highlight-color:transparent}
body{font-family:'Inter','Manrope',system-ui,sans-serif;color:var(--ink);background:#fff;line-height:1.65;overflow-x:hidden;padding-bottom:78px}
@media(min-width:992px){body{padding-bottom:0}}
h1,h2,h3,h4{font-family:'Manrope','Inter',sans-serif;font-weight:800;color:var(--ink);letter-spacing:-.02em;line-height:1.15}
h1{font-size:clamp(2rem,4vw,3.4rem)}
h2{font-size:clamp(1.6rem,3vw,2.4rem)}
h3{font-size:clamp(1.2rem,2vw,1.5rem)}
a{color:var(--p1);text-decoration:none;transition:.2s}
a:hover{color:var(--p3)}
.container-xxl{max-width:1280px;margin:0 auto;padding:0 1.25rem}
.kicker{display:inline-flex;align-items:center;gap:.5rem;font-size:.75rem;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:var(--p3);background:linear-gradient(135deg,rgba(13,98,58,.08),rgba(217,122,12,.08));padding:.45rem 1rem;border-radius:999px;border:1px solid rgba(13,98,58,.14)}
.btn-primary-grad{display:inline-flex;align-items:center;gap:.55rem;padding:.85rem 1.55rem;background:var(--grad-cta);color:#fff;font-weight:700;border-radius:999px;border:0;box-shadow:0 14px 40px -16px rgba(13,98,58,.6);transition:.25s}
.btn-primary-grad:hover{transform:translateY(-2px);box-shadow:0 20px 48px -18px rgba(217,122,12,.7);color:#fff}
.btn-outline-grad{display:inline-flex;align-items:center;gap:.55rem;padding:.85rem 1.55rem;background:#fff;border:2px solid rgba(13,98,58,.16);color:var(--p1);font-weight:700;border-radius:999px;transition:.25s}
.btn-outline-grad:hover{border-color:var(--p1);background:rgba(13,98,58,.05)}

/* HEADER */
.site-header{position:sticky;top:0;z-index:90;background:rgba(255,255,255,.86);backdrop-filter:saturate(180%) blur(14px);border-bottom:1px solid rgba(13,98,58,.08);transition:box-shadow .2s}
.site-header.scrolled{box-shadow:0 14px 30px -22px rgba(13,98,58,.35)}
.header-inner{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.85rem 0}
.brand{display:flex;align-items:center;gap:.75rem;color:var(--ink);font-weight:800}
.brand:hover{color:var(--ink)}
.brand-logo{height:40px;width:auto;display:flex;align-items:center;overflow:hidden}
.brand-logo img{height:100%;width:auto;object-fit:contain;display:block}
.nav-main{display:none;gap:.25rem;align-items:center}
@media(min-width:1100px){.nav-main{display:flex}}
.nav-link-x{display:inline-flex;align-items:center;padding:.55rem .9rem;color:var(--ink2);font-weight:600;font-size:.94rem;border-radius:999px;transition:.18s}
.nav-link-x:hover{color:var(--p1);background:rgba(13,98,58,.06)}
.nav-link-x.active{color:var(--p1);background:rgba(13,98,58,.1)}
.header-cta{display:flex;gap:.5rem;align-items:center}
.lang-switch{position:relative}
.lang-btn{display:inline-flex;align-items:center;gap:.45rem;padding:.48rem .85rem;background:rgba(13,98,58,.06);border:1px solid rgba(13,98,58,.12);border-radius:999px;font-weight:700;color:var(--ink);cursor:pointer;font-size:.88rem}
.lang-btn:hover{background:rgba(13,98,58,.1)}
.lang-menu{position:absolute;top:calc(100% + 8px);right:0;background:#fff;border:1px solid rgba(13,98,58,.12);border-radius:14px;box-shadow:0 24px 50px -20px rgba(13,98,58,.3);padding:.4rem;min-width:180px;display:none;z-index:120}
.lang-menu.open{display:block}
.lang-item{display:flex;align-items:center;gap:.65rem;padding:.6rem .75rem;border-radius:9px;color:var(--ink);font-weight:600;font-size:.9rem}
.lang-item:hover{background:rgba(13,98,58,.07);color:var(--p1)}
.lang-item.is-current{background:linear-gradient(135deg,rgba(13,98,58,.12),rgba(217,122,12,.1));color:var(--p1)}
/* flag-icons sizing */
.fi{vertical-align:middle;border-radius:3px;box-shadow:0 1px 3px rgba(0,0,0,.18)}
.lang-btn .fi{font-size:1.15em}
.lang-item .fi{font-size:1.3em}
.btn-cta-quote{display:none;padding:.55rem 1.1rem;background:var(--grad-cta);color:#fff;font-weight:700;border-radius:999px;font-size:.9rem;border:0}
.btn-cta-login{display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1rem;border:1.5px solid rgba(13,98,58,.2);color:var(--p1);font-weight:700;border-radius:999px;font-size:.9rem;background:#fff}
.btn-cta-login:hover{background:var(--p1);color:#fff;border-color:var(--p1)}
@media(min-width:768px){.btn-cta-quote{display:inline-flex;align-items:center;gap:.4rem}}
.menu-toggle{display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:11px;background:rgba(13,98,58,.08);color:var(--p1);border:0;cursor:pointer}
@media(min-width:1100px){.menu-toggle{display:none}}

/* MOBILE DRAWER */
.mob-drawer{position:fixed;inset:0;z-index:200;background:rgba(6,40,26,.65);backdrop-filter:blur(6px);display:none}
.mob-drawer.open{display:block}
.mob-drawer-panel{position:absolute;top:0;right:0;bottom:0;width:84%;max-width:340px;background:#fff;padding:1.25rem;overflow:auto;box-shadow:-30px 0 80px -20px rgba(6,40,26,.3)}
.mob-drawer-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;padding-bottom:1rem;border-bottom:1px solid rgba(13,98,58,.1)}
.mob-link{display:flex;align-items:center;justify-content:space-between;padding:.85rem 0;color:var(--ink);font-weight:600;border-bottom:1px solid rgba(13,98,58,.06)}
.mob-link.active{color:var(--p1)}

/* MOBILE BOTTOM BAR */
.mob-bottom-bar{position:fixed;bottom:0;left:0;right:0;background:#fff;border-top:1px solid rgba(13,98,58,.1);box-shadow:0 -10px 30px -8px rgba(6,40,26,.18);z-index:80;display:flex;justify-content:space-around;padding:.45rem .25rem env(safe-area-inset-bottom);padding-bottom:max(.45rem, env(safe-area-inset-bottom))}
@media(min-width:992px){.mob-bottom-bar{display:none}}
.mob-bb-item{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2px;padding:.45rem .2rem;color:#47745f;font-size:.65rem;font-weight:600;border-radius:10px;text-align:center;line-height:1.05}
.mob-bb-item i{font-size:1.15rem;color:var(--ink2)}
.mob-bb-item.active{color:var(--p1)}
.mob-bb-item.active i{color:var(--p1)}
.mob-bb-item.cta i{background:var(--grad-cta);color:#fff;width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin-top:-18px;box-shadow:0 14px 30px -10px rgba(13,98,58,.55);font-size:1.1rem}

/* WHATSAPP FAB */
.wa-fab{position:fixed;bottom:84px;right:18px;width:56px;height:56px;border-radius:50%;background:#25d366;color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.6rem;box-shadow:0 18px 40px -10px rgba(37,211,102,.55);z-index:60;animation:pulse 2.4s ease-out infinite}
@media(min-width:992px){.wa-fab{bottom:30px;right:30px}}
@keyframes pulse{0%{box-shadow:0 18px 40px -10px rgba(37,211,102,.55),0 0 0 0 rgba(37,211,102,.5)}70%{box-shadow:0 18px 40px -10px rgba(37,211,102,.55),0 0 0 18px rgba(37,211,102,0)}100%{box-shadow:0 18px 40px -10px rgba(37,211,102,.55),0 0 0 0 rgba(37,211,102,0)}}
.wa-fab:hover{color:#fff;transform:scale(1.05)}

/* SECTIONS */
section.block{padding:5rem 0}
@media(max-width:768px){section.block{padding:3.5rem 0}}
.section-head{text-align:center;max-width:780px;margin:0 auto 3rem}
.section-head h2{margin:.5rem 0 1rem}
.section-head p{color:var(--ink2);font-size:1.05rem;margin:0}

/* CARDS */
.card-clean{background:#fff;border-radius:var(--radius);border:1px solid rgba(13,98,58,.1);padding:1.6rem;height:100%;transition:.25s}
.card-clean:hover{transform:translateY(-4px);box-shadow:var(--shadow);border-color:rgba(13,98,58,.25)}
.card-icon{width:54px;height:54px;border-radius:14px;background:linear-gradient(135deg,rgba(13,98,58,.12),rgba(217,122,12,.1));color:var(--p1);display:flex;align-items:center;justify-content:center;font-size:1.3rem;margin-bottom:1rem}

/* FOOTER */
.site-footer{margin-top:4rem;background:linear-gradient(135deg,#06281a 0%,#073820 60%,#09482a 100%);color:#dcebdb;padding:4rem 0 1.25rem;position:relative;overflow:hidden}
.site-footer::before{content:'';position:absolute;inset:0;background-image:radial-gradient(rgba(255,255,255,.08) 1px,transparent 1px);background-size:32px 32px;opacity:.4}
.footer-inner{position:relative;z-index:2}
.footer-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:2.5rem;margin-bottom:2.5rem}
.footer-brand{display:flex;align-items:center;margin-bottom:1rem}
.footer-brand .brand-logo{height:78px;width:auto;display:flex;align-items:center;overflow:hidden}
.footer-brand .brand-logo img{height:100%;width:auto;object-fit:contain;display:block}
.footer h4{color:#fff;font-size:1.05rem;margin-bottom:1rem}
.footer ul{list-style:none;padding:0;margin:0}
.footer ul li{margin-bottom:.55rem}
.footer ul a{color:#a8cfb8;font-size:.88rem}
.footer ul a:hover{color:#fff}
.social{display:flex;gap:.65rem;margin-top:1rem}
.social a{width:40px;height:40px;border-radius:11px;background:rgba(255,255,255,.07);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.05rem;transition:.2s}
.social a:hover{background:#fff;color:var(--p1)}
.footer-bottom{border-top:1px solid rgba(255,255,255,.1);padding-top:1.25rem;display:flex;justify-content:space-between;flex-wrap:wrap;gap:1rem;color:#6ba98a;font-size:.85rem}

/* UTIL */
.skip-link{position:absolute;top:-40px;left:0;background:var(--p1);color:#fff;padding:8px 16px;z-index:300}
.skip-link:focus{top:0}
img{max-width:100%;height:auto}
.fade-in{opacity:0;transform:translateY(14px);transition:opacity .8s ease,transform .8s ease}
.fade-in.shown{opacity:1;transform:none}
</style>
</head>
<body>

<a href="#main" class="skip-link">Skip to content</a>

<!-- HEADER -->
<header class="site-header" id="siteHeader">
  <div class="container-xxl">
    <div class="header-inner">
      <a href="<?= I18n::url('', $locale) ?>" class="brand">
        <span class="brand-logo">
          <?php if ($logoPath): ?>
            <img src="<?= htmlspecialchars($logoPath) ?>" alt="<?= htmlspecialchars($brand) ?>">
          <?php else: ?><?= htmlspecialchars($brand) ?><?php endif; ?>
        </span>
      </a>

      <nav class="nav-main" aria-label="primary">
        <?php foreach ($navItems as $item): ?>
          <?php if ($item['key'] === 'contact') continue; ?>
          <?php
            $href = $item['pageKey'] === 'home' ? I18n::url('', $locale) : I18n::altUrl($item['pageKey'], $locale);
            $isActive = $currentPage === $item['key'];
          ?>
          <a href="<?= $href ?>" class="nav-link-x<?= $isActive ? ' active' : '' ?>"><?= htmlspecialchars($item['label']) ?></a>
        <?php endforeach; ?>
      </nav>

      <div class="header-cta">
        <div class="lang-switch">
          <button type="button" class="lang-btn" id="langBtn" aria-haspopup="true" aria-expanded="false">
            <?php $fc = $languageFlags[$locale] ?? 'un'; ?>
            <span class="fi fi-<?= $fc ?>" aria-hidden="true"></span>
            <span><?= strtoupper($locale) ?></span>
            <i class="fas fa-chevron-down" style="font-size:.6rem;opacity:.6"></i>
          </button>
          <div class="lang-menu" id="langMenu" role="menu">
            <?php
              // Aktif sayfayı I18n pageMap anahtarına çevir
              $pageKeyForAlt = [
                'home'=>'home','about'=>'about','products'=>'products',
                'quality'=>'quality',
                'services'=>'services','gallery'=>'gallery','contact'=>'contact',
              ][$currentPage] ?? 'home';
            ?>
            <?php foreach (I18n::SUPPORTED as $loc): $altUrl = I18n::altUrl($pageKeyForAlt, $loc); ?>
              <?php $fc2 = $languageFlags[$loc] ?? 'un'; ?>
              <a href="<?= htmlspecialchars($altUrl) ?>" class="lang-item<?= $loc === $locale ? ' is-current' : '' ?>" hreflang="<?= $loc ?>">
                <span class="fi fi-<?= $fc2 ?>" aria-hidden="true"></span>
                <span><?= htmlspecialchars(I18n::languageName($loc)) ?></span>
                <?php if ($loc === $locale): ?>
                  <i class="fas fa-check ms-auto" style="font-size:.7rem;color:var(--p1)"></i>
                <?php endif; ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>

        <button type="button" class="btn-cta-quote" onclick="openQuoteModal()">
          <i class="fas fa-paper-plane"></i> <?= htmlspecialchars(I18n::t('common.cta_quote')) ?>
        </button>
        <button type="button" class="menu-toggle" id="menuToggle" aria-label="Menu" aria-controls="mobDrawer" aria-expanded="false">
          <i class="fas fa-bars"></i>
        </button>
      </div>
    </div>
  </div>
</header>

<!-- MOBILE DRAWER -->
<div class="mob-drawer" id="mobDrawer">
  <div class="mob-drawer-panel" role="dialog" aria-label="Navigation">
    <div class="mob-drawer-head">
      <a href="<?= I18n::url('', $locale) ?>" class="brand">
        <span class="brand-logo">
          <?php if ($logoPath): ?>
            <img src="<?= htmlspecialchars($logoPath) ?>" alt="<?= htmlspecialchars($brand) ?>">
          <?php else: ?><?= htmlspecialchars($brand) ?><?php endif; ?>
        </span>
      </a>
      <button type="button" class="menu-toggle" id="menuClose" aria-label="Close"><i class="fas fa-xmark"></i></button>
    </div>
    <nav>
      <?php foreach ($navItems as $item): ?>
        <?php
          $href = $item['pageKey'] === 'home' ? I18n::url('', $locale) : I18n::altUrl($item['pageKey'], $locale);
          $isActive = $currentPage === $item['key'];
        ?>
        <a href="<?= $href ?>" class="mob-link<?= $isActive ? ' active' : '' ?>">
          <?= htmlspecialchars($item['label']) ?>
          <i class="fas fa-arrow-right" style="font-size:.85rem;opacity:.5"></i>
        </a>
      <?php endforeach; ?>
    </nav>
    <!-- Mobil dil seçici -->
    <div class="mt-3 mb-1" style="border-top:1px solid rgba(13,98,58,.08);padding-top:1rem">
      <p style="font-size:.72rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--ink2);margin-bottom:.6rem"><?= htmlspecialchars(I18n::t('common.language')) ?></p>
      <div style="display:flex;gap:.5rem;flex-wrap:wrap">
        <?php foreach (I18n::SUPPORTED as $loc):
          $altUrl2 = I18n::altUrl($pageKeyForAlt ?? 'home', $loc);
          $fc3 = $languageFlags[$loc] ?? 'un';
          $isCurr = $loc === $locale;
        ?>
        <a href="<?= htmlspecialchars($altUrl2) ?>" hreflang="<?= $loc ?>"
           style="display:inline-flex;align-items:center;gap:.4rem;padding:.4rem .75rem;border-radius:999px;font-weight:700;font-size:.85rem;
                  <?= $isCurr ? 'background:var(--grad);color:#fff;box-shadow:0 6px 16px -8px rgba(13,98,58,.55)' : 'background:rgba(13,98,58,.06);color:var(--ink2);border:1px solid rgba(13,98,58,.12)' ?>">
          <span class="fi fi-<?= $fc3 ?>" style="font-size:1.1em;border-radius:2px"></span>
          <span><?= htmlspecialchars(I18n::languageName($loc)) ?></span>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="mt-3 d-grid gap-2">
      <button type="button" class="btn-primary-grad justify-content-center" onclick="closeMobDrawer(); openQuoteModal();">
        <i class="fas fa-paper-plane"></i> <?= htmlspecialchars(I18n::t('common.cta_quote')) ?>
      </button>
      <a href="<?= BASE_URL ?>/giris" class="btn-outline-grad justify-content-center" rel="nofollow">
        <i class="fas fa-arrow-right-to-bracket"></i> <?= htmlspecialchars(I18n::t('common.login')) ?>
      </a>
    </div>
  </div>
</div>

<!-- MAIN -->
<main id="main">
<?php
$pageFile = VIEWS_PATH . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . $pageView . '.php';
if (is_file($pageFile)) {
    include $pageFile;
} else {
    echo '<div class="container-xxl py-5"><h1>' . htmlspecialchars(I18n::t('errors.404_title')) . '</h1></div>';
}
?>
</main>

<style id="public-visual-repair">
/* Visual repair layer: keeps existing design, normalizes responsive spacing and fixed UI offsets. */
:root{
  --header-h-desktop:88px;
  --header-h-tablet:64px;
  --header-h-mobile:58px;
  --bottom-nav-h:74px;
  --safe-bottom:env(safe-area-inset-bottom, 0px);
}
body{
  min-width:320px;
  padding-bottom:calc(var(--bottom-nav-h) + var(--safe-bottom) + 24px);
}
@media(min-width:992px){
  body{padding-bottom:0}
}
#main{
  overflow:hidden;
}
section[id],
.page-hero,
.region-hero,
.pd-hero{
  scroll-margin-top:calc(var(--header-h-desktop) + 18px);
}
.site-header{
  z-index:1000;
  background:#fff;
}
.site-header .brand-logo{
  height:68px;
  width:auto;
  transition:transform .22s ease;
}
.site-header .brand:hover .brand-logo{
  transform:scale(1.06);
}
.header-inner{
  height:var(--header-h-desktop);
  min-height:0;
  padding:.35rem 0;
}
.brand,
.header-cta,
.nav-main{
  min-width:0;
}
.nav-main{
  gap:.5rem;
}
.nav-link-x{
  position:relative;
  padding:.45rem .18rem;
  border-radius:0;
  background:transparent !important;
  color:#356850;
  font-size:.82rem;
  font-weight:700;
  line-height:1;
  white-space:nowrap;
  letter-spacing:0;
}
.nav-link-x::after{
  content:'';
  position:absolute;
  left:0;
  right:auto;
  bottom:.12rem;
  width:0;
  height:2px;
  border-radius:999px;
  background:var(--grad);
  transition:width .22s ease;
}
.nav-link-x:hover,
.nav-link-x.active{
  color:var(--p1);
}
.nav-link-x:hover::after,
.nav-link-x.active::after{
  width:100%;
}
.lang-flag{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  width:1.2em;
  height:1.2em;
  font-size:1.05rem;
  line-height:1;
}
.btn-cta-quote,
.btn-cta-login,
.lang-btn,
.menu-toggle{
  min-height:40px;
}
.btn-cta-quote{
  padding:.54rem .86rem;
  font-size:.8rem;
  line-height:1;
  white-space:nowrap;
}
.btn-cta-quote,
.btn-cta-quote:hover,
.btn-cta-quote:focus{
  color:#fff;
}
.btn-cta-quote:hover,
.btn-cta-quote:focus{
  filter:brightness(1.04);
  box-shadow:0 14px 34px -18px rgba(13,98,58,.8);
}
.btn-cta-login{
  display:none;
  padding:.48rem .82rem;
  font-size:.82rem;
  line-height:1;
  white-space:nowrap;
}
@media(min-width:1100px){
  .header-cta .btn-cta-login{
    display:inline-flex;
  }
}
.mob-drawer{
  z-index:1200;
}
.lang-menu{
  z-index:1250;
}
.mob-bottom-bar{
  min-height:calc(var(--bottom-nav-h) + var(--safe-bottom));
  padding:.42rem .5rem max(.52rem, var(--safe-bottom));
  gap:.15rem;
  align-items:center;
  z-index:950;
}
.mob-bb-item{
  min-width:0;
  min-height:54px;
  padding:.35rem .15rem;
  overflow:hidden;
}
.mob-bb-item span{
  display:block;
  max-width:100%;
  overflow:hidden;
  text-overflow:ellipsis;
  white-space:nowrap;
}
html[lang="ru"] .mob-bb-item.cta span{
  white-space:normal;
  text-overflow:clip;
  line-height:1.05;
  font-size:.58rem;
  max-width:82px;
}
.mob-bb-item.cta i{
  width:40px;
  height:40px;
  margin-top:-14px;
}
.wa-fab{
  right:18px;
  bottom:calc(var(--bottom-nav-h) + var(--safe-bottom) + 30px);
  z-index:940;
}
.to-top{
  position:fixed;
  right:18px;
  bottom:calc(var(--bottom-nav-h) + var(--safe-bottom) + 92px);
  width:46px;
  height:46px;
  border:0;
  border-radius:50%;
  display:flex;
  align-items:center;
  justify-content:center;
  color:#fff;
  background:var(--grad);
  box-shadow:0 18px 38px -18px rgba(13,98,58,.75);
  opacity:0;
  pointer-events:none;
  transform:translateY(10px);
  transition:opacity .2s ease, transform .2s ease;
  z-index:940;
}
.to-top.is-visible{
  opacity:1;
  pointer-events:auto;
  transform:none;
}
.to-top:hover{
  color:#fff;
  transform:translateY(-2px);
}
@media(min-width:992px){
  .wa-fab{
    right:30px;
    bottom:30px;
    z-index:900;
  }
  .to-top{
    right:30px;
    bottom:96px;
    z-index:900;
  }
}
.site-footer{
  margin-top:0;
  background:linear-gradient(135deg,#f8fcf7 0%,#eef6ed 58%,#e2f0e2 100%);
  color:var(--ink2);
  border-top:2px solid rgba(13,98,58,.15);
}
/* Son section'ın alt padding'ini sıfırla, footer düz çizgiyle başlasın */
#main > section:last-child,
#main > div:last-child{
  padding-bottom:0;
}
.site-footer::before{
  background-image:radial-gradient(rgba(13,98,58,.12) 1px,transparent 1px);
  opacity:.36;
}
.footer-grid{
  align-items:start;
}
.site-footer .footer-brand,
.site-footer h4{
  color:var(--ink);
}
.site-footer p,
.site-footer p[style],
.site-footer li{
  color:#3b6954 !important;
  overflow-wrap:anywhere;
}
.site-footer ul a{
  color:#3b6954;
  font-size:.88rem;
}
.site-footer ul a:hover{
  color:var(--p1);
}
.site-footer .social a{
  background:#fff;
  color:#0d623a;
  border:1px solid rgba(13,98,58,.14);
  box-shadow:0 12px 28px -18px rgba(6,40,26,.35);
}
.site-footer .social a:hover{
  background:var(--grad);
  color:#fff;
  border-color:transparent;
}
.footer-bottom{
  border-top-color:rgba(13,98,58,.16);
  color:#5a806e;
}
.footer-bottom a[style]{
  color:#3b6954 !important;
  font-size:.82rem;
}
.footer-login-link{
  display:inline-flex;
  align-items:center;
  gap:.42rem;
  color:#fff !important;
  background:var(--grad);
  border-radius:999px;
  padding:.5rem .9rem;
  font-weight:800;
  box-shadow:0 12px 28px -18px rgba(13,98,58,.7);
}
.footer-login-link:hover{
  color:#fff !important;
  transform:translateY(-1px);
}
.footer-bottom a:hover{
  color:var(--p1) !important;
}
.footer-bottom .footer-login-link:hover{
  color:#fff !important;
}
.products-grid,
.markets-grid,
.steps,
.why-list,
.gallery-grid,
.region-grid,
.region-features,
.spec-grid,
.related-grid{
  min-width:0;
}
.product-card,
.market-card,
.step,
.region-card,
.region-feature,
.spec-card,
.rel-card,
.svc-card,
.value-card,
.info-card,
.form-card,
.faq-item{
  min-width:0;
}
.product-card,
.market-card,
.region-card,
.rel-card{
  color:inherit;
}
.product-card:hover,
.market-card:hover,
.region-card:hover,
.rel-card:hover,
.card-clean:hover,
.step:hover,
.svc-card:hover{
  will-change:transform;
}

@media(max-width:1199.98px){
  .container-xxl{
    padding-left:1rem;
    padding-right:1rem;
  }
  .btn-cta-quote{
    padding:.52rem .9rem;
  }
}

@media(max-width:991.98px){
  :root{
    --header-h-desktop:var(--header-h-tablet);
  }
  .header-inner{
    height:var(--header-h-tablet);
    min-height:0;
    gap:.75rem;
  }
  /* header-h-tablet (64px) 68px'lik masaüstü logosuna dar geliyor —
     bu ara genişlikte taşmayan bir boyutta tutulur. */
  .site-header .brand-logo{
    height:52px;
  }
  .site-header{
    background:rgba(255,255,255,.94);
  }
  section.block{
    padding:4rem 0;
  }
  .page-hero,
  .region-hero,
  .pd-hero{
    padding-top:3.75rem !important;
    padding-bottom:2.75rem !important;
  }
  .section-head{
    margin-bottom:2.25rem;
  }
  .footer-grid{
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:2rem;
  }
}

@media(max-width:767.98px){
  h1{
    font-size:clamp(2rem, 8.8vw, 2.55rem);
    line-height:1.12;
  }
  h2{
    font-size:clamp(1.55rem, 7vw, 2rem);
    line-height:1.16;
  }
  h3{
    font-size:1.18rem;
  }
  .container-xxl{
    padding-left:.95rem;
    padding-right:.95rem;
  }
  .header-inner{
    height:var(--header-h-mobile);
    min-height:0;
    padding:.4rem 0;
    gap:.55rem;
  }
  .brand{
    gap:.5rem;
    flex:0 0 auto;
  }
  .brand-logo{
    height:42px;
    width:auto;
  }
  .site-header .brand-logo{
    height:42px;
    width:auto;
  }
  .header-cta{
    flex:0 0 auto;
    gap:.35rem;
  }
  .lang-btn{
    padding:.42rem .56rem;
    gap:.28rem;
    font-size:.78rem;
    min-height:36px;
  }
  .btn-cta-login{
    width:36px;
    height:36px;
    min-height:36px;
    padding:0;
    justify-content:center;
    border-radius:11px;
  }
  .menu-toggle{
    width:36px;
    height:36px;
    min-height:36px;
    border-radius:11px;
  }
  .lang-menu{
    right:-42px;
    min-width:156px;
  }
  .mob-drawer-panel{
    width:min(88vw, 340px);
    padding:1rem;
    padding-bottom:calc(1rem + var(--safe-bottom));
  }
  .page-hero,
  .region-hero,
  .pd-hero{
    padding-top:3rem !important;
    padding-bottom:2.25rem !important;
  }
  section.block{
    padding:3rem 0;
  }
  .section-head{
    margin-bottom:1.8rem;
  }
  .section-head p,
  .page-hero p,
  .region-hero p,
  .pd-hero p{
    font-size:1rem !important;
    line-height:1.6;
  }
  .card-clean{
    padding:1.25rem;
  }
  .footer-grid{
    grid-template-columns:1fr;
    gap:1.65rem;
    margin-bottom:1.75rem;
  }
  .site-footer{
    margin-top:2.75rem;
    padding:3rem 0 calc(2.25rem + var(--bottom-nav-h) + var(--safe-bottom));
  }
  .footer-bottom{
    display:block;
  }
  .footer-bottom span{
    display:block;
    margin-bottom:.55rem;
  }
  .hero{
    min-height:calc(100svh - var(--header-h-mobile));
    align-items:flex-start;
  }
  .hero-inner{
    padding:3.2rem .95rem calc(2.4rem + var(--bottom-nav-h) + var(--safe-bottom)) !important;
  }
  .hero h1{
    font-size:clamp(2rem, 9.5vw, 2.65rem) !important;
    max-width:100%;
    margin-bottom:1rem;
  }
  .hero p.lead{
    font-size:1rem !important;
    line-height:1.6;
    margin-bottom:1.35rem;
  }
  .hero-kicker,
  .kicker{
    max-width:100%;
    line-height:1.25;
    letter-spacing:.12em;
  }
  .hero-cta{
    gap:.5rem;
    flex-direction:row;
    flex-wrap:nowrap;
  }
  .hero-cta .btn-primary-grad,
  .hero-cta .btn-outline-grad{
    flex:1 1 0;
    min-width:0;
    padding:.62rem .5rem;
    font-size:.78rem;
    gap:.35rem;
    border-radius:10px;
    justify-content:center;
    white-space:nowrap;
  }
  .hero-stats{
    gap:.55rem;
    margin-top:1.65rem;
  }
  .hero-stat{
    padding:.75rem .55rem;
    border-radius:12px;
  }
  .hero-stat strong{
    font-size:1.25rem;
  }
  .hero-stat span{
    font-size:.62rem;
    letter-spacing:.08em;
  }
  .hero-arrows{
    display:none;
  }
  .hero-controls{
    bottom:calc(var(--bottom-nav-h) + var(--safe-bottom) + .8rem) !important;
  }
  .products-grid,
  .markets-grid,
  .steps,
  .region-grid,
  .related-grid{
    grid-template-columns:1fr !important;
    gap:1rem;
  }
  .why-list{
    grid-template-columns:1fr !important;
    gap:.75rem;
    padding-left:0;
  }
  .why-list li{
    padding:1rem;
    line-height:1.45;
  }
  .product-img,
  .rel-card img{
    height:180px !important;
  }
  .market-card{
    height:auto !important;
    min-height:176px;
    padding:1.25rem;
  }
  .region-card{
    min-height:230px !important;
    padding:1.5rem !important;
    border-radius:18px !important;
  }
  .region-card h2{
    font-size:1.35rem !important;
  }
  .product-cta{
    gap:.5rem;
  }
  .product-cta .detail,
  .product-cta .quote{
    font-size:.72rem;
    padding:.5rem .5rem;
  }
  .about-flex{
    gap:2rem !important;
  }
  .about-img{
    height:auto !important;
    aspect-ratio:4 / 3;
    border-radius:18px !important;
  }
  .about-badge{
    left:1rem !important;
    bottom:1rem !important;
    padding:1rem !important;
    border-radius:14px !important;
  }
  .gallery-grid{
    grid-template-columns:1fr 1fr !important;
    grid-auto-rows:150px !important;
  }
  .gallery-item:nth-child(4n+1){
    grid-row:span 1 !important;
  }
  .cta-band,
  .inquiry-card{
    margin:1rem 0 !important;
    padding:2.25rem 1rem !important;
    border-radius:20px !important;
  }
  .pd-grid{
    gap:1.5rem !important;
  }
  .pd-image{
    border-radius:18px !important;
  }
  .pd-image img{
    height:auto !important;
    aspect-ratio:4 / 3;
  }
  .spec-grid{
    grid-template-columns:1fr !important;
  }
}

@media(max-width:575.98px){
  .container-xxl{
    padding-left:.8rem;
    padding-right:.8rem;
  }
  .btn-primary-grad,
  .btn-outline-grad{
    padding:.78rem 1.05rem;
    justify-content:center;
  }
  .mob-bottom-bar{
    padding-left:.25rem;
    padding-right:.25rem;
  }
  .mob-bb-item{
    font-size:.62rem;
  }
  .mob-bb-item i{
    font-size:1.05rem;
  }
  .wa-fab{
    width:50px;
    height:50px;
    right:14px;
    font-size:1.45rem;
  }
  .gallery-grid{
    grid-template-columns:1fr !important;
  }
}

@media(max-width:374.98px){
  .container-xxl{
    padding-left:.7rem;
    padding-right:.7rem;
  }
  .brand-logo{
    height:36px;
    width:auto;
  }
  .site-header .brand-logo{
    height:36px;
    width:auto;
  }
  .lang-btn{
    padding:.36rem .45rem;
  }
  .btn-cta-login,
  .menu-toggle{
    width:34px;
    height:34px;
    min-height:34px;
  }
  .mob-bb-item{
    font-size:.58rem;
  }
  .hero-cta .btn-primary-grad,
  .hero-cta .btn-outline-grad{
    padding:.55rem .35rem;
    font-size:.68rem;
    gap:.25rem;
  }
  .hero-stats{
    grid-template-columns:1fr;
  }
  .hero-stat{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:.75rem;
  }
  .hero-stat span{
    text-align:right;
  }
}
</style>

<!-- FOOTER -->
<footer class="site-footer" role="contentinfo">
  <div class="container-xxl footer-inner">
    <div class="footer-grid">
      <div>
        <div class="footer-brand">
          <span class="brand-logo">
            <?php if ($logoPath): ?><img src="<?= htmlspecialchars($logoPath) ?>" alt="<?= htmlspecialchars($brand) ?>"><?php else: ?><?= htmlspecialchars($brand) ?><?php endif; ?>
          </span>
        </div>
        <p style="color:#a8cfb8;margin-bottom:1.25rem;font-size:.95rem">
          <?= htmlspecialchars($siteText('about_p1', I18n::t('home.about.p1'))) ?>
        </p>
        <div class="social">
          <?php if (!empty($ayarlar['instagram'])): ?><a href="<?= htmlspecialchars($ayarlar['instagram']) ?>" target="_blank" rel="noopener" aria-label="Instagram"><i class="fab fa-instagram"></i></a><?php endif; ?>
          <?php if (!empty($ayarlar['linkedin'])): ?><a href="<?= htmlspecialchars($ayarlar['linkedin']) ?>" target="_blank" rel="noopener" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a><?php endif; ?>
          <?php if (!empty($ayarlar['facebook'])): ?><a href="<?= htmlspecialchars($ayarlar['facebook']) ?>" target="_blank" rel="noopener" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a><?php endif; ?>
          <?php if (!empty($ayarlar['twitter'])): ?><a href="<?= htmlspecialchars($ayarlar['twitter']) ?>" target="_blank" rel="noopener" aria-label="X / Twitter"><i class="fab fa-x-twitter"></i></a><?php endif; ?>
        </div>
      </div>
      <div>
        <h4><?= htmlspecialchars(I18n::t('common.quick_links')) ?></h4>
        <ul>
          <?php foreach ($navItems as $item): ?>
            <?php $href = $item['pageKey'] === 'home' ? I18n::url('', $locale) : I18n::altUrl($item['pageKey'], $locale); ?>
            <li><a href="<?= $href ?>"><?= htmlspecialchars($item['label']) ?></a></li>
          <?php endforeach; ?>
          <li><a href="<?= BASE_URL ?>/giris" rel="nofollow"><?= htmlspecialchars(I18n::t('common.login')) ?></a></li>
        </ul>
      </div>
      <div>
        <h4><?= htmlspecialchars(I18n::t('common.products')) ?></h4>
        <ul>
          <?php
            try { $footerProducts = (new SiteIcerik())->urunler(true); } catch (Throwable $e) { $footerProducts = []; }
            $footerProducts = array_slice($footerProducts, 0, 8);
            foreach ($footerProducts as $fp):
              $fpAd  = $fp['ad_'.$locale] ?? $fp['ad_tr'] ?? '';
              $fpSlg = $fp['slug_'.$locale] ?? $fp['slug_tr'] ?? '';
          ?>
            <li><a href="<?= I18n::altUrl('products', $locale, $fpSlg) ?>"><?= htmlspecialchars($fpAd) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div>
        <h4><?= htmlspecialchars(I18n::t('common.company_info')) ?></h4>
        <ul>
          <li><i class="fas fa-location-dot me-2"></i><?= htmlspecialchars($address) ?></li>
          <?php if ($phone): ?><li><i class="fas fa-phone me-2"></i><a href="tel:<?= htmlspecialchars(preg_replace('/\D+/', '', $phone)) ?>"><?= htmlspecialchars($phone) ?></a></li><?php endif; ?>
          <?php if ($whatsapp): ?><li><i class="fab fa-whatsapp me-2"></i><a href="<?= htmlspecialchars($waLink ?: '#') ?>" target="_blank" rel="noopener"><?= htmlspecialchars($whatsapp) ?></a></li><?php endif; ?>
          <?php if ($email): ?><li><i class="fas fa-envelope me-2"></i><a href="mailto:<?= htmlspecialchars($email) ?>"><?= htmlspecialchars($email) ?></a></li><?php endif; ?>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>&copy; <?= date('Y') ?> <?= htmlspecialchars($brand) ?>. <?= htmlspecialchars(I18n::t('common.copyright')) ?></span>
    </div>
  </div>
</footer>

<!-- WhatsApp FAB -->
<?php if ($waLink): ?>
<a href="<?= htmlspecialchars($waLink) ?>" class="wa-fab" target="_blank" rel="noopener" aria-label="WhatsApp">
  <i class="fab fa-whatsapp"></i>
</a>
<?php endif; ?>

<button type="button" class="to-top" id="toTop" aria-label="Yukarı git">
  <i class="fas fa-arrow-up"></i>
</button>

<!-- MOBILE BOTTOM BAR -->
<nav class="mob-bottom-bar" aria-label="Mobile bottom navigation">
  <a href="<?= I18n::url('', $locale) ?>" class="mob-bb-item<?= $currentPage === 'home' ? ' active' : '' ?>"><i class="fas fa-house"></i><span><?= htmlspecialchars(I18n::t('mobile_nav.home')) ?></span></a>
  <a href="<?= I18n::altUrl('products', $locale) ?>" class="mob-bb-item<?= $currentPage === 'products' ? ' active' : '' ?>"><i class="fas fa-leaf"></i><span><?= htmlspecialchars(I18n::t('mobile_nav.products')) ?></span></a>
  <button type="button" class="mob-bb-item" onclick="openQuoteModal()"><i class="fas fa-paper-plane"></i><span><?= htmlspecialchars(I18n::t('common.cta_quote')) ?></span></button>
</nav>

<!-- QUOTE MODAL -->
<style>
.modal-overlay{position:fixed;inset:0;background:rgba(6,20,14,.55);backdrop-filter:blur(2px);z-index:1000;display:none;align-items:center;justify-content:center;padding:1rem}
.modal-overlay.open{display:flex}
.modal-box{background:#fff;border-radius:18px;max-width:560px;width:100%;max-height:90vh;overflow-y:auto;padding:1.8rem 1.8rem 2rem;position:relative;box-shadow:0 30px 70px -20px rgba(0,0,0,.4)}
.modal-close{position:absolute;top:1rem;right:1rem;width:36px;height:36px;border-radius:50%;border:0;background:rgba(13,98,58,.08);color:var(--ink2);display:flex;align-items:center;justify-content:center;font-size:1rem;cursor:pointer;transition:.2s}
.modal-close:hover{background:rgba(13,98,58,.15);color:var(--ink)}
.modal-box h3{font-size:1.3rem;margin-bottom:.35rem;padding-right:2rem}
.modal-box .modal-lead{color:var(--ink2);font-size:.92rem;margin-bottom:1.3rem}
.modal-box label{font-size:.84rem;font-weight:700;color:var(--ink2);margin-bottom:.4rem;display:block}
.modal-box input, .modal-box textarea{width:100%;border:1.5px solid rgba(13,98,58,.18);border-radius:12px;padding:.75rem 1rem;font-size:.95rem;background:#fff;transition:.2s}
.modal-box input:focus, .modal-box textarea:focus{border-color:var(--p1);outline:none;box-shadow:0 0 0 4px rgba(13,98,58,.12)}
.modal-box textarea{min-height:110px;resize:vertical}
.modal-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:.9rem}
@media(max-width:560px){.modal-form-grid{grid-template-columns:1fr}}
.modal-box .honey{position:absolute;left:-9999px;top:-9999px;visibility:hidden}
.modal-box .consent{display:flex;gap:.6rem;align-items:flex-start;font-size:.83rem;color:var(--ink2);margin:.9rem 0 1.1rem}
.modal-box .consent input{width:auto;margin-top:.25rem}
.modal-err{padding:.75rem 1rem;border-radius:12px;margin-bottom:1.1rem;font-weight:600;font-size:.88rem;display:none;align-items:center;gap:.55rem;background:linear-gradient(135deg,rgba(239,68,68,.12),rgba(220,38,38,.08));color:#b91c1c;border:1px solid rgba(239,68,68,.2)}
.modal-err.show{display:flex}
.modal-done{display:none;text-align:center;padding:1.2rem 0 .4rem}
.modal-done .di{width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,rgba(34,197,94,.15),rgba(16,185,129,.1));color:#16a34a;display:flex;align-items:center;justify-content:center;font-size:1.7rem;margin:0 auto 1rem}
.modal-done h3{margin-bottom:.6rem}
.modal-done p{color:var(--ink2);font-size:.95rem;max-width:400px;margin:0 auto 1.4rem}
[data-quote-state="done"] .quote-form-wrap{display:none}
[data-quote-state="done"] .modal-done{display:block}
[data-quote-state="form"] .modal-done{display:none}
</style>
<div class="modal-overlay" id="quoteModal" data-quote-state="form" aria-hidden="true">
  <div class="modal-box" role="dialog" aria-modal="true" aria-label="<?= htmlspecialchars(I18n::t('common.cta_quote')) ?>">
    <button type="button" class="modal-close" id="quoteModalClose" aria-label="Close"><i class="fas fa-xmark"></i></button>

    <div class="quote-form-wrap">
      <h3><?= htmlspecialchars(I18n::t('common.cta_quote')) ?></h3>
      <p class="modal-lead"><?= htmlspecialchars(I18n::t('contact.lead')) ?></p>

      <div class="modal-err" id="quoteModalErr"></div>

      <form id="quoteForm" method="POST" action="<?= I18n::url(I18n::pageMap()[$locale]['contact'] . '/gonder', $locale) ?>" novalidate>
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>">
        <!-- honeypot -->
        <input type="text" name="website_url" class="honey" tabindex="-1" autocomplete="off">

        <div class="modal-form-grid">
          <div class="mb-3">
            <label for="q_ad_soyad"><?= htmlspecialchars(I18n::t('contact.form.name')) ?> *</label>
            <input id="q_ad_soyad" name="ad_soyad" type="text" required minlength="2" maxlength="120">
          </div>
          <div class="mb-3">
            <label for="q_firma"><?= htmlspecialchars(I18n::t('contact.form.company')) ?></label>
            <input id="q_firma" name="firma" type="text" maxlength="120">
          </div>
          <div class="mb-3">
            <label for="q_ulke"><?= htmlspecialchars(I18n::t('contact.form.country')) ?></label>
            <input id="q_ulke" name="ulke" type="text" maxlength="80">
          </div>
          <div class="mb-3">
            <label for="q_email"><?= htmlspecialchars(I18n::t('contact.form.email')) ?> *</label>
            <input id="q_email" name="email" type="email" required maxlength="120">
          </div>
          <div class="mb-3">
            <label for="q_telefon"><?= htmlspecialchars(I18n::t('contact.form.phone')) ?></label>
            <input id="q_telefon" name="telefon" type="tel" maxlength="40">
          </div>
          <div class="mb-3">
            <label for="q_urun"><?= htmlspecialchars(I18n::t('contact.form.product')) ?></label>
            <input id="q_urun" name="urun" type="text" maxlength="120">
          </div>
        </div>
        <div class="mb-3">
          <label for="q_mesaj"><?= htmlspecialchars(I18n::t('contact.form.message')) ?> *</label>
          <textarea id="q_mesaj" name="mesaj" required minlength="5" maxlength="3500" placeholder="<?= htmlspecialchars(I18n::t('contact.form.placeholder_message')) ?>"></textarea>
        </div>
        <label class="consent">
          <input type="checkbox" required>
          <span><?= htmlspecialchars(I18n::t('contact.form.consent')) ?></span>
        </label>
        <button type="submit" class="btn-primary-grad" style="border:0" id="quoteSubmitBtn">
          <i class="fas fa-paper-plane"></i> <?= htmlspecialchars(I18n::t('contact.form.submit')) ?>
        </button>
      </form>
    </div>

    <div class="modal-done" id="quoteModalDone">
      <div class="di"><i class="fas fa-check"></i></div>
      <h3><?= htmlspecialchars(I18n::t('common.cta_quote')) ?></h3>
      <p><?= htmlspecialchars(I18n::t('contact.form.success')) ?></p>
      <button type="button" class="btn-primary-grad" id="quoteModalDoneClose"><?= htmlspecialchars(I18n::t('common.close')) ?></button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" defer></script>
<script>
(function(){
  // Header shadow on scroll
  const h = document.getElementById('siteHeader');
  const toTop = document.getElementById('toTop');
  const onScroll = () => {
    h.classList.toggle('scrolled', window.scrollY > 8);
    toTop?.classList.toggle('is-visible', window.scrollY > 360);
  };
  window.addEventListener('scroll', onScroll, {passive:true}); onScroll();
  toTop?.addEventListener('click', () => window.scrollTo({top:0, behavior:'smooth'}));

  // Lang menu
  const lb = document.getElementById('langBtn');
  const lm = document.getElementById('langMenu');
  if (lb && lm) {
    lb.addEventListener('click', e => { e.stopPropagation(); lm.classList.toggle('open'); lb.setAttribute('aria-expanded', lm.classList.contains('open')); });
    document.addEventListener('click', () => { lm.classList.remove('open'); lb.setAttribute('aria-expanded', 'false'); });
  }

  // Mobile drawer
  const drawer = document.getElementById('mobDrawer');
  const open = document.getElementById('menuToggle');
  const close = document.getElementById('menuClose');
  if (open && drawer) {
    open.addEventListener('click', () => { drawer.classList.add('open'); document.body.style.overflow='hidden'; open.setAttribute('aria-expanded','true'); });
  }
  if (close && drawer) close.addEventListener('click', () => { drawer.classList.remove('open'); document.body.style.overflow=''; });
  if (drawer) drawer.addEventListener('click', e => { if (e.target === drawer) { drawer.classList.remove('open'); document.body.style.overflow=''; } });

  // Reveal on scroll
  const io = new IntersectionObserver((entries) => {
    entries.forEach(en => { if (en.isIntersecting) { en.target.classList.add('shown'); io.unobserve(en.target); } });
  }, {threshold:.1});
  document.querySelectorAll('.fade-in').forEach(el => io.observe(el));

  // Quote modal
  const qModal = document.getElementById('quoteModal');
  const qForm = document.getElementById('quoteForm');
  const qErr = document.getElementById('quoteModalErr');
  const qSubmitBtn = document.getElementById('quoteSubmitBtn');
  const qUrun = document.getElementById('q_urun');

  window.openQuoteModal = function(urunAdi) {
    if (!qModal) return;
    qModal.setAttribute('data-quote-state', 'form');
    qModal.setAttribute('aria-hidden', 'false');
    qErr.classList.remove('show');
    qErr.textContent = '';
    if (qForm) qForm.reset();
    if (qUrun) qUrun.value = urunAdi || '';
    qModal.classList.add('open');
    document.body.style.overflow = 'hidden';
  };
  window.closeQuoteModal = function() {
    if (!qModal) return;
    qModal.classList.remove('open');
    document.body.style.overflow = '';
  };
  window.closeMobDrawer = function() {
    if (drawer) { drawer.classList.remove('open'); document.body.style.overflow = ''; }
  };

  if (qModal) {
    document.getElementById('quoteModalClose')?.addEventListener('click', closeQuoteModal);
    document.getElementById('quoteModalDoneClose')?.addEventListener('click', closeQuoteModal);
    qModal.addEventListener('click', e => { if (e.target === qModal) closeQuoteModal(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && qModal.classList.contains('open')) closeQuoteModal(); });
  }

  if (qForm) {
    qForm.addEventListener('submit', function(e) {
      e.preventDefault();
      qErr.classList.remove('show');
      qSubmitBtn.disabled = true;
      const originalHtml = qSubmitBtn.innerHTML;
      qSubmitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ...';

      fetch(qForm.action, {
        method: 'POST',
        body: new FormData(qForm),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
        .then(res => res.json())
        .then(data => {
          if (data && data.ok) {
            qModal.setAttribute('data-quote-state', 'done');
          } else {
            qErr.textContent = (data && data.msg) ? data.msg : 'Bir hata oluştu.';
            qErr.classList.add('show');
          }
        })
        .catch(() => {
          qErr.textContent = 'Bağlantı hatası. Lütfen tekrar deneyin.';
          qErr.classList.add('show');
        })
        .finally(() => {
          qSubmitBtn.disabled = false;
          qSubmitBtn.innerHTML = originalHtml;
        });
    });
  }
})();

// "Ana ekrana ekle / Uygulamayı yükle" — tarayıcının bunu önerebilmesi
// için kayıtlı bir service worker şart (bkz. /sw.js).
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('<?= htmlspecialchars(BASE_URL) ?>/sw.js').catch(() => {});
  });
}
</script>
</body>
</html>
