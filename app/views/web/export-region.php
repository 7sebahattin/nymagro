<?php
/* Export region landing — SEO landing per region */
$LL    = I18n::all();
$cards = $LL['home']['trust']['cards'] ?? [];
$regionCardIcons = ['fa-leaf','fa-box-open','fa-truck-fast','fa-handshake'];
?>
<style>
.region-hero{padding:5rem 0 3rem;background:linear-gradient(135deg,#06281a 0%,#073820 60%,#0d623a 100%);color:#fff;position:relative;overflow:hidden}
.region-hero::before{content:'';position:absolute;inset:0;background-image:radial-gradient(rgba(255,255,255,.06) 1px,transparent 1px);background-size:32px 32px}
.region-hero h1{color:#fff;max-width:760px}
.region-hero p{color:#dcebdb;max-width:760px;font-size:1.1rem}
.bcrumb{display:inline-flex;gap:.5rem;align-items:center;color:#a8cfb8;font-size:.9rem;margin-bottom:1rem;flex-wrap:wrap}
.bcrumb a{color:#a8cfb8}.bcrumb a:hover{color:#fff}
.region-features{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;margin-top:2rem}
.region-feature{background:#fff;border-radius:14px;border:1px solid rgba(13,98,58,.1);padding:1.4rem}
.region-feature .ic{width:46px;height:46px;border-radius:11px;background:linear-gradient(135deg,rgba(13,98,58,.12),rgba(217,122,12,.1));color:var(--p1);display:flex;align-items:center;justify-content:center;margin-bottom:.85rem}
.products-mini{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:1rem}
.products-mini a{background:#fff;border:1px solid rgba(13,98,58,.1);border-radius:14px;overflow:hidden;display:block;transition:.2s}
.products-mini a:hover{transform:translateY(-3px);box-shadow:0 16px 36px -16px rgba(13,98,58,.4)}
.products-mini img{width:100%;height:120px;object-fit:cover}
.products-mini h4{padding:.7rem 1rem;margin:0;font-size:.95rem;color:var(--ink)}
</style>

<section class="region-hero">
  <div class="container-xxl" style="position:relative;z-index:2">
    <span class="bcrumb">
      <a href="<?= I18n::url('', $locale) ?>"><?= htmlspecialchars(I18n::t('breadcrumb.home')) ?></a> /
      <a href="<?= I18n::altUrl('export_markets', $locale) ?>"><?= htmlspecialchars(I18n::t('common.markets')) ?></a> /
      <span><?= htmlspecialchars($regionName) ?></span>
    </span>
    <span class="kicker" style="background:rgba(255,255,255,.18);color:#fff;border-color:rgba(255,255,255,.3)"><i class="fas fa-globe"></i> <?= htmlspecialchars(I18n::t('common.markets')) ?></span>
    <h1 class="mt-3"><?= htmlspecialchars($regionName) ?></h1>
    <p><?= htmlspecialchars($regionDesc) ?></p>
  </div>
</section>

<section class="block">
  <div class="container-xxl">
    <div class="region-features">
      <?php foreach (array_slice($cards, 0, 4) as $ci => $card): ?>
      <div class="region-feature">
        <div class="ic"><i class="fas <?= $regionCardIcons[$ci] ?? 'fa-star' ?>"></i></div>
        <h4><?= htmlspecialchars($card['t'] ?? '') ?></h4>
        <p style="color:var(--ink2);margin:0;font-size:.9rem"><?= htmlspecialchars($card['d'] ?? '') ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php if (!empty($urunler)): ?>
<section class="block" style="background:linear-gradient(180deg,#fff 0%,#f6fbf5 100%)">
  <div class="container-xxl">
    <div class="section-head">
      <span class="kicker"><i class="fas fa-leaf"></i> <?= htmlspecialchars(I18n::t('common.products')) ?></span>
      <h2><?= htmlspecialchars(I18n::t('home.products.title')) ?></h2>
    </div>
    <div class="products-mini">
      <?php foreach ($urunler as $u):
        $ad = $u['ad_'.$locale] ?? $u['ad_tr'] ?? '';
        $slug = $u['slug_'.$locale] ?? $u['slug_tr'] ?? '';
        $img = $u['gorsel'] ?? '';
      ?>
        <a href="<?= I18n::altUrl('products', $locale, $slug) ?>">
          <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($ad) ?>" loading="lazy">
          <h4><?= htmlspecialchars($ad) ?></h4>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="container-xxl">
  <div class="cta-band fade-in" style="background:linear-gradient(135deg,#0d623a,#d97a0c);color:#fff;border-radius:32px;padding:3.5rem 2rem;text-align:center;margin:2rem 0">
    <h2 style="color:#fff"><?= htmlspecialchars(I18n::t('home.cta_band.title')) ?></h2>
    <p style="color:#e9f4e8;max-width:600px;margin:0 auto 2rem"><?= htmlspecialchars(I18n::t('home.cta_band.desc')) ?></p>
    <a href="<?= I18n::altUrl('contact', $locale) ?>?market=<?= urlencode($regionName) ?>" style="background:#fff;color:var(--p1);font-weight:800;padding:1rem 2rem;border-radius:999px;display:inline-flex;align-items:center;gap:.6rem">
      <i class="fas fa-paper-plane"></i> <?= htmlspecialchars(I18n::t('home.cta_band.btn')) ?>
    </a>
  </div>
</section>
