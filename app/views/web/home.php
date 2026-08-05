<?php
/** Public Home — Nymagro landing page (locale aware) */
$LL        = I18n::all();
$siteText  = $siteText ?? static fn(string $k, string $d = '') => $d;
$defaultSlides = $LL['home']['hero']['slides']     ?? [];
$slideCount = max(count($defaultSlides), (int)($ayarlar['slide_count'] ?? count($defaultSlides)));
$slidesArr = [];
for ($i = 1; $i <= $slideCount; $i++) {
    $default = $defaultSlides[$i - 1] ?? ['kicker'=>'', 'title'=>'', 'desc'=>'', 'cta1'=>'', 'cta2'=>''];
    foreach (['kicker','title','desc','cta1','cta2'] as $field) {
        $default[$field] = $siteText("slide{$i}_{$field}", (string)($default[$field] ?? ''));
    }
    $slidesArr[] = $default;
}
$cards     = $LL['home']['trust']['cards']     ?? [];
$steps     = $LL['home']['process']['steps']   ?? [];
$whyItems  = $LL['home']['why']['items']       ?? [];
$highlights= $LL['home']['about']['highlights'] ?? [];

$slideImgs = [];
for ($i = 1; $i <= $slideCount; $i++) {
    $slideImgs[] = (string)($ayarlar["slide{$i}_img"] ?? '');
}
?>

<style>
/* HERO */
.hero{position:relative;min-height:88vh;display:flex;align-items:center;color:#fff;overflow:hidden;background:#06281a}
.hero .slide{position:absolute;inset:0;opacity:0;transition:opacity 1s ease;background-position:center;background-size:cover}
.hero .slide::after{content:'';position:absolute;inset:0;background:linear-gradient(110deg,rgba(6,40,26,.85) 0%,rgba(13,98,58,.55) 60%,rgba(217,122,12,.35) 100%)}
.hero .slide.is-active{opacity:1}
.hero-inner{position:relative;z-index:3;padding:5rem 0}
.hero-kicker{display:inline-flex;align-items:center;gap:.5rem;background:rgba(255,255,255,.12);padding:.5rem 1.1rem;border-radius:999px;font-size:.78rem;font-weight:700;letter-spacing:.18em;text-transform:uppercase;margin-bottom:1.4rem;backdrop-filter:blur(6px)}
.hero h1{color:#fff;font-size:clamp(2.2rem,5vw,4rem);max-width:780px;margin:0 0 1.25rem}
.hero p.lead{color:#c6e2d1;font-size:clamp(1.05rem,1.6vw,1.25rem);max-width:680px;margin-bottom:2rem}
.hero-cta{display:flex;flex-wrap:wrap;gap:.7rem}
.hero-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem;margin-top:3rem;max-width:560px}
.hero-stat{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.14);border-radius:14px;padding:1rem 1.1rem;backdrop-filter:blur(8px)}
.hero-stat strong{font-family:'Manrope';font-size:1.6rem;display:block;color:#fff}
.hero-stat span{color:#a8cfb8;font-size:.78rem;text-transform:uppercase;letter-spacing:.12em}
.hero-controls{position:absolute;bottom:2rem;left:50%;transform:translateX(-50%);z-index:4;display:flex;gap:.5rem}
.hero-dot{width:30px;height:5px;border-radius:99px;background:rgba(255,255,255,.3);border:0;transition:.2s;cursor:pointer}
.hero-dot.is-active{background:#fff;width:48px}
.hero-arrows{position:absolute;top:50%;transform:translateY(-50%);z-index:4;display:flex;justify-content:space-between;width:100%;padding:0 1rem;pointer-events:none}
.hero-arrow{pointer-events:auto;width:46px;height:46px;border-radius:50%;background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;cursor:pointer;backdrop-filter:blur(8px)}
.hero-arrow:hover{background:rgba(255,255,255,.22)}

/* PRODUCT GRID */
.products-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1.2rem}
.product-card{background:#fff;border-radius:18px;overflow:hidden;box-shadow:0 6px 22px -10px rgba(6,40,26,.18);border:1px solid rgba(13,98,58,.08);transition:.25s;display:flex;flex-direction:column}
.product-card:hover{transform:translateY(-6px);box-shadow:0 22px 50px -22px rgba(13,98,58,.4);border-color:rgba(13,98,58,.2)}
.product-img{position:relative;height:180px;overflow:hidden;background:#eef6ed}
.product-img img{width:100%;height:100%;object-fit:cover;transition:transform .5s}
.product-card:hover .product-img img{transform:scale(1.05)}
.product-tag{position:absolute;top:.7rem;left:.7rem;background:var(--grad);color:#fff;font-size:.65rem;font-weight:800;letter-spacing:.12em;padding:.3rem .7rem;border-radius:999px}
.product-body{padding:1rem 1.1rem 1.2rem;flex:1;display:flex;flex-direction:column}
.product-body h4{margin:0 0 .35rem;font-size:1.1rem}
.product-body p{color:#47745f;margin:0 0 .9rem;font-size:.86rem;flex:1}
.product-link{font-weight:700;font-size:.85rem;color:var(--p1);display:inline-flex;align-items:center;gap:.4rem}
.product-link:hover{color:var(--p3)}

/* MARKETS */
.markets-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1.2rem}
.market-card{position:relative;border-radius:18px;overflow:hidden;height:200px;background:linear-gradient(135deg,#073820,#0d623a);color:#fff;padding:1.5rem;display:flex;flex-direction:column;justify-content:flex-end;transition:.25s}
.market-card:hover{transform:translateY(-4px);color:#fff}
.market-card::before{content:'';position:absolute;top:-30px;right:-30px;width:140px;height:140px;border-radius:50%;background:radial-gradient(circle,rgba(255,255,255,.25),transparent 70%)}
.market-card h4{color:#fff;margin-bottom:.4rem}
.market-card p{margin:0;color:#dcebdb;font-size:.85rem}

/* PROCESS */
.steps{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1.4rem}
.step{position:relative;padding:1.6rem;background:#fff;border-radius:18px;border:1px solid rgba(13,98,58,.08);text-align:center}
.step-num{width:54px;height:54px;border-radius:50%;background:var(--grad);color:#fff;display:flex;align-items:center;justify-content:center;font-family:'Manrope';font-weight:800;font-size:1.2rem;margin:0 auto 1rem;box-shadow:0 14px 30px -10px rgba(13,98,58,.5)}
.step h4{margin-bottom:.4rem}

/* CTA BAND */
.cta-band{position:relative;background:var(--grad);color:#fff;border-radius:32px;padding:3.5rem 2rem;text-align:center;overflow:hidden;margin:2rem 0}
.cta-band::before,.cta-band::after{content:'';position:absolute;border-radius:50%;background:rgba(255,255,255,.08)}
.cta-band::before{width:280px;height:280px;top:-100px;right:-80px}
.cta-band::after{width:220px;height:220px;bottom:-80px;left:-60px}
.cta-band > *{position:relative;z-index:2}
.cta-band h2{color:#fff;margin-bottom:.7rem}
.cta-band p{color:#e9f4e8;margin-bottom:2rem;max-width:600px;margin-left:auto;margin-right:auto}
.cta-band .btn-w{background:#fff;color:var(--p1);font-weight:800;padding:1rem 2rem;border-radius:999px;display:inline-flex;align-items:center;gap:.6rem}
.cta-band .btn-w:hover{transform:translateY(-2px);background:#eef6ed}

/* WHY LIST */
.why-list{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem}
.why-list li{display:flex;align-items:flex-start;gap:.8rem;padding:1.1rem 1.25rem;background:linear-gradient(135deg,#f4faf3 0%,#fff9f2 100%);border:1px solid rgba(13,98,58,.1);border-radius:14px;color:var(--ink2);font-weight:600;list-style:none}
.why-list li::before{content:'\f00c';font-family:'Font Awesome 6 Free';font-weight:900;color:var(--p1);background:rgba(13,98,58,.1);width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0}

/* GALLERY MASONRY */
.gallery-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));grid-auto-rows:200px;gap:1rem}
.gallery-item{position:relative;border-radius:14px;overflow:hidden;background:#06281a}
.gallery-item img{width:100%;height:100%;object-fit:cover;transition:transform .5s,filter .3s}
.gallery-item:hover img{transform:scale(1.06);filter:brightness(.7)}
.gallery-item .tag{position:absolute;left:.8rem;bottom:.8rem;background:rgba(0,0,0,.5);color:#fff;font-size:.78rem;font-weight:700;padding:.35rem .8rem;border-radius:999px;backdrop-filter:blur(4px);opacity:0;transition:opacity .3s}
.gallery-item:hover .tag{opacity:1}
.gallery-item:nth-child(4n+1){grid-row:span 2}

/* ABOUT */
.about-flex{display:grid;grid-template-columns:1fr 1fr;gap:3rem;align-items:center}
@media(max-width:992px){.about-flex{grid-template-columns:1fr}}
.about-img{border-radius:24px;overflow:hidden;height:480px;position:relative}
.about-img img{width:100%;height:100%;object-fit:cover}
.about-img::after{content:'';position:absolute;inset:0;background:linear-gradient(135deg,transparent 60%,rgba(13,98,58,.25) 100%)}
.about-badge{position:absolute;bottom:1.5rem;left:1.5rem;background:#fff;border-radius:18px;padding:1.2rem 1.5rem;box-shadow:0 22px 50px -16px rgba(6,40,26,.3);max-width:240px;z-index:2}
.about-badge strong{font-family:'Manrope';font-size:2rem;background:var(--grad);-webkit-background-clip:text;background-clip:text;color:transparent;display:block;line-height:1}
.about-badge span{color:var(--ink2);font-weight:600;font-size:.85rem}
</style>

<!-- HERO -->
<section class="hero" aria-label="Hero">
  <?php foreach ($slidesArr as $i => $s): $img = $slideImgs[$i] ?? ''; ?>
    <div class="slide<?= $i === 0 ? ' is-active' : '' ?>" style="background-image:url('<?= htmlspecialchars($img) ?>')" data-idx="<?= $i ?>"></div>
  <?php endforeach; ?>

  <div class="container-xxl hero-inner">
    <?php foreach ($slidesArr as $i => $s): ?>
      <div class="hero-content<?= $i === 0 ? ' is-active' : '' ?>" data-idx="<?= $i ?>" style="display:<?= $i === 0 ? 'block' : 'none' ?>">
        <span class="hero-kicker"><i class="fas fa-leaf"></i> <?= htmlspecialchars($s['kicker']) ?></span>
        <h1><?= htmlspecialchars($s['title']) ?></h1>
        <p class="lead"><?= htmlspecialchars($s['desc']) ?></p>
        <div class="hero-cta">
          <a href="<?= I18n::altUrl('products', $locale) ?>" class="btn-primary-grad"><i class="fas fa-leaf"></i> <?= htmlspecialchars($s['cta1']) ?></a>
          <a href="<?= I18n::altUrl('contact', $locale) ?>" class="btn-outline-grad" style="background:rgba(255,255,255,.12);color:#fff;border-color:rgba(255,255,255,.3)"><i class="fas fa-paper-plane"></i> <?= htmlspecialchars($s['cta2']) ?></a>
        </div>
      </div>
    <?php endforeach; ?>

    <div class="hero-stats">
      <div class="hero-stat"><strong><?= htmlspecialchars((string)($ayarlar['stat_countries_value'] ?? '5')) ?></strong><span><?= htmlspecialchars(I18n::t('common.markets')) ?></span></div>
      <div class="hero-stat"><strong><?= htmlspecialchars((string)($ayarlar['stat_products_value'] ?? '8+')) ?></strong><span><?= htmlspecialchars(I18n::t('common.products')) ?></span></div>
      <div class="hero-stat"><strong><?= htmlspecialchars((string)($ayarlar['stat_quality_value'] ?? '100%')) ?></strong><span><?= htmlspecialchars(I18n::t('common.quality')) ?></span></div>
    </div>
  </div>

  <div class="hero-arrows" aria-hidden="true">
    <button type="button" class="hero-arrow" id="heroPrev"><i class="fas fa-arrow-left"></i></button>
    <button type="button" class="hero-arrow" id="heroNext"><i class="fas fa-arrow-right"></i></button>
  </div>
  <div class="hero-controls" role="tablist">
    <?php foreach ($slidesArr as $i => $s): ?>
      <button type="button" class="hero-dot<?= $i === 0 ? ' is-active' : '' ?>" data-idx="<?= $i ?>" aria-label="Slide <?= $i + 1 ?>"></button>
    <?php endforeach; ?>
  </div>
</section>

<!-- TRUST CARDS -->
<section class="block">
  <div class="container-xxl">
    <div class="row g-3">
      <?php foreach ($cards as $c):
        $icons = ['fa-leaf','fa-box-open','fa-truck-fast','fa-globe'];
        static $iCount = 0;
      ?>
        <div class="col-md-6 col-lg-3">
          <div class="card-clean text-center fade-in">
            <div class="card-icon mx-auto"><i class="fas <?= $icons[$iCount++ % 4] ?>"></i></div>
            <h4 class="mb-2"><?= htmlspecialchars($c['t']) ?></h4>
            <p class="m-0" style="color:var(--ink2);font-size:.94rem"><?= htmlspecialchars($c['d']) ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ABOUT -->
<section class="block" style="background:linear-gradient(180deg,#fff 0%,#f6fbf5 100%)">
  <div class="container-xxl">
    <div class="about-flex fade-in">
      <div class="about-img">
        <img src="<?= htmlspecialchars((string)($ayarlar['about_img'] ?? '')) ?>" alt="Nymagro Antalya — bitki besleme ürünleri" loading="lazy" width="800" height="600">
        <div class="about-badge"><strong><?= htmlspecialchars((string)($ayarlar['about_stat_value'] ?? '5')) ?></strong><span><?= htmlspecialchars(I18n::t('common.markets')) ?></span></div>
      </div>
      <div>
        <span class="kicker"><i class="fas fa-circle-info"></i> <?= htmlspecialchars($siteText('about_tag', I18n::t('home.about.kicker'))) ?></span>
        <h2 class="mt-3 mb-3"><?= htmlspecialchars($siteText('about_title', I18n::t('home.about.title'))) ?></h2>
        <p style="color:var(--ink2);font-size:1.05rem"><?= htmlspecialchars($siteText('about_p1', I18n::t('home.about.p1'))) ?></p>
        <p style="color:var(--ink2)"><?= htmlspecialchars($siteText('about_p2', I18n::t('home.about.p2'))) ?></p>
        <ul class="why-list mt-4">
          <?php foreach ($highlights as $hi): ?><li><?= htmlspecialchars($hi) ?></li><?php endforeach; ?>
        </ul>
        <div class="mt-4">
          <a href="<?= I18n::altUrl('about', $locale) ?>" class="btn-primary-grad"><i class="fas fa-arrow-right"></i> <?= htmlspecialchars(I18n::t('home.about.cta')) ?></a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- PRODUCTS -->
<section class="block" id="urunler">
  <div class="container-xxl">
    <div class="section-head fade-in">
      <span class="kicker"><i class="fas fa-leaf"></i> <?= htmlspecialchars($siteText('products_tag', I18n::t('home.products.kicker'))) ?></span>
      <h2><?= htmlspecialchars($siteText('products_title', I18n::t('home.products.title'))) ?></h2>
      <p><?= htmlspecialchars($siteText('products_desc', I18n::t('home.products.desc'))) ?></p>
    </div>

    <div class="products-grid">
      <?php foreach (array_slice($urunler ?? [], 0, 8) as $u):
        $ad   = $u['ad_'.$locale]   ?? $u['ad_tr']   ?? '';
        $desc = $u['aciklama_'.$locale] ?? $u['aciklama_tr'] ?? '';
        $slug = $u['slug_'.$locale] ?? $u['slug_tr'] ?? SiteIcerik::slugify($ad);
        $img  = $u['gorsel'] ?? '';
        $etiket = $u['etiket'] ?? 'FRESH';
      ?>
        <a href="<?= I18n::altUrl('products', $locale, $slug) ?>" class="product-card fade-in">
          <div class="product-img">
            <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($ad) ?> — Nymagro bitki besleme ürünü" loading="lazy" width="400" height="300">
            <span class="product-tag"><?= htmlspecialchars($etiket) ?></span>
          </div>
          <div class="product-body">
            <h4><?= htmlspecialchars($ad) ?></h4>
            <p><?= htmlspecialchars(mb_strimwidth($desc, 0, 90, '…')) ?></p>
            <span class="product-link"><?= htmlspecialchars(I18n::t('common.cta_more')) ?> <i class="fas fa-arrow-right"></i></span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="text-center mt-5">
      <a href="<?= I18n::altUrl('products', $locale) ?>" class="btn-outline-grad"><?= htmlspecialchars(I18n::t('common.all_products')) ?> <i class="fas fa-arrow-right"></i></a>
    </div>
  </div>
</section>

<!-- MARKETS -->
<section class="block" style="background:linear-gradient(180deg,#f6fbf5 0%,#fff 100%)">
  <div class="container-xxl">
    <div class="section-head fade-in">
      <span class="kicker"><i class="fas fa-globe"></i> <?= htmlspecialchars($siteText('markets_tag', I18n::t('home.markets.kicker'))) ?></span>
      <h2><?= htmlspecialchars($siteText('markets_title', I18n::t('home.markets.title'))) ?></h2>
      <p><?= htmlspecialchars($siteText('markets_desc', I18n::t('home.markets.desc'))) ?></p>
    </div>
    <div class="markets-grid">
      <?php
        $regionMap = [
          'tr' => ['avrupa','dubai-bae','suudi-arabistan','misir','rusya'],
          'en' => ['europe','dubai-uae','saudi-arabia','egypt','russia'],
          'ru' => ['europe','dubai-uae','saudi-arabia','egypt','russia'],
        ];
        $keyMap = ['avrupa'=>'europe','dubai-bae'=>'dubai-uae','suudi-arabistan'=>'saudi-arabia','misir'=>'egypt','rusya'=>'russia',
                   'europe'=>'europe','dubai-uae'=>'dubai-uae','saudi-arabia'=>'saudi-arabia','egypt'=>'egypt','russia'=>'russia'];
        $marketTextKeys = [
          'europe' => ['market_eu', 'market_eu_d'],
          'dubai-uae' => ['market_uae', 'market_uae_d'],
          'saudi-arabia' => ['market_sa', 'market_sa_d'],
          'egypt' => ['market_eg', 'market_eg_d'],
          'russia' => ['market_ru', 'market_ru_d'],
        ];
        foreach ($regionMap[$locale] as $slug):
          $key = $keyMap[$slug] ?? $slug;
          $textKeys = $marketTextKeys[$key] ?? null;
          $name = $textKeys ? $siteText($textKeys[0], $LL['markets']['list'][$key]['name'] ?? '') : ($LL['markets']['list'][$key]['name'] ?? '');
          $desc = $textKeys ? $siteText($textKeys[1], $LL['markets']['list'][$key]['desc'] ?? '') : ($LL['markets']['list'][$key]['desc'] ?? '');
      ?>
        <a href="<?= I18n::altUrl('export_region', $locale, $slug) ?>" class="market-card fade-in">
          <h4><?= htmlspecialchars($name) ?></h4>
          <p><?= htmlspecialchars(mb_strimwidth($desc, 0, 110, '…')) ?></p>
        </a>
      <?php endforeach; ?>
    </div>
    <div class="text-center mt-4">
      <a href="<?= I18n::altUrl('export_markets', $locale) ?>" class="btn-outline-grad"><?= htmlspecialchars(I18n::t('common.all_markets')) ?> <i class="fas fa-arrow-right"></i></a>
    </div>
  </div>
</section>

<!-- PROCESS -->
<section class="block">
  <div class="container-xxl">
    <div class="section-head fade-in">
      <span class="kicker"><i class="fas fa-shield-halved"></i> <?= htmlspecialchars($siteText('quality_h1', I18n::t('home.process.kicker'))) ?></span>
      <h2><?= htmlspecialchars(I18n::t('home.process.title')) ?></h2>
    </div>
    <div class="steps">
      <?php foreach ($steps as $i => $st): ?>
        <div class="step fade-in">
          <div class="step-num"><?= str_pad((string)($i+1), 2, '0', STR_PAD_LEFT) ?></div>
          <?php $n = $i + 1; ?>
          <h4><?= htmlspecialchars($siteText("quality_step{$n}_t", $st['t'])) ?></h4>
          <p style="color:var(--ink2);font-size:.93rem;margin:0"><?= htmlspecialchars($siteText("quality_step{$n}_d", $st['d'])) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- CTA BAND -->
<section class="container-xxl">
  <div class="cta-band fade-in">
    <span class="kicker" style="background:rgba(255,255,255,.18);color:#fff;border-color:rgba(255,255,255,.3)"><i class="fas fa-bolt"></i> <?= htmlspecialchars(I18n::t('common.cta_quote')) ?></span>
    <h2 class="mt-3"><?= htmlspecialchars(I18n::t('home.cta_band.title')) ?></h2>
    <p><?= htmlspecialchars(I18n::t('home.cta_band.desc')) ?></p>
    <a href="<?= I18n::altUrl('contact', $locale) ?>" class="btn-w"><i class="fas fa-paper-plane"></i> <?= htmlspecialchars(I18n::t('home.cta_band.btn')) ?></a>
  </div>
</section>

<!-- WHY -->
<section class="block">
  <div class="container-xxl">
    <div class="section-head fade-in">
      <span class="kicker"><i class="fas fa-award"></i> <?= htmlspecialchars(I18n::t('home.why.kicker')) ?></span>
      <h2><?= htmlspecialchars(I18n::t('home.why.title')) ?></h2>
    </div>
    <ul class="why-list">
      <?php foreach ($whyItems as $w): ?><li><?= htmlspecialchars($w) ?></li><?php endforeach; ?>
    </ul>
  </div>
</section>

<!-- GALLERY -->
<?php if (!empty($galeri)): ?>
<section class="block" style="background:linear-gradient(180deg,#fff 0%,#f6fbf5 100%)">
  <div class="container-xxl">
    <div class="section-head fade-in">
      <span class="kicker"><i class="fas fa-images"></i> <?= htmlspecialchars($siteText('gallery_tag', I18n::t('home.gallery.kicker'))) ?></span>
      <h2><?= htmlspecialchars($siteText('gallery_title', I18n::t('home.gallery.title'))) ?></h2>
      <p><?= htmlspecialchars($siteText('gallery_desc', I18n::t('home.gallery.desc'))) ?></p>
    </div>
    <div class="gallery-grid">
      <?php foreach (array_slice($galeri, 0, 8) as $g):
        $img = $g['gorsel'] ?? '';
        $tag = $g['etiket_'.$locale] ?? $g['etiket_tr'] ?? '';
      ?>
        <div class="gallery-item fade-in">
          <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($tag ?: 'Nymagro galeri') ?>" loading="lazy">
          <?php if ($tag): ?><span class="tag"><?= htmlspecialchars($tag) ?></span><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<script>
(function(){
  // Hero slider
  const slides = document.querySelectorAll('.hero .slide');
  const contents = document.querySelectorAll('.hero-content');
  const dots = document.querySelectorAll('.hero-dot');
  let cur = 0;
  function go(i){
    cur = (i + slides.length) % slides.length;
    slides.forEach((s, x) => s.classList.toggle('is-active', x === cur));
    contents.forEach((c, x) => { c.style.display = x === cur ? 'block' : 'none'; c.classList.toggle('is-active', x === cur); });
    dots.forEach((d, x) => d.classList.toggle('is-active', x === cur));
  }
  dots.forEach(d => d.addEventListener('click', () => go(parseInt(d.dataset.idx))));
  document.getElementById('heroPrev')?.addEventListener('click', () => go(cur - 1));
  document.getElementById('heroNext')?.addEventListener('click', () => go(cur + 1));
  let t = setInterval(() => go(cur + 1), 6500);
  document.querySelector('.hero')?.addEventListener('mouseenter', () => clearInterval(t));
  document.querySelector('.hero')?.addEventListener('mouseleave', () => t = setInterval(() => go(cur + 1), 6500));
})();
</script>
