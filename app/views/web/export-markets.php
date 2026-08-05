<?php
/* Export markets list page */
// $siteText controller tarafından inject edilir
$siteText = $siteText ?? static fn(string $k, string $d = '') => $d;
$marketTextKeys = [
    'europe' => ['market_eu', 'market_eu_d'],
    'dubai-uae' => ['market_uae', 'market_uae_d'],
    'saudi-arabia' => ['market_sa', 'market_sa_d'],
    'egypt' => ['market_eg', 'market_eg_d'],
    'russia' => ['market_ru', 'market_ru_d'],
];
?>
<style>
.page-hero{padding:5rem 0 3rem;background:linear-gradient(135deg,#06281a 0%,#073820 60%,#0d623a 100%);color:#fff;position:relative;overflow:hidden}
.page-hero::before{content:'';position:absolute;inset:0;background-image:radial-gradient(rgba(255,255,255,.06) 1px,transparent 1px);background-size:32px 32px}
.page-hero h1{color:#fff;max-width:760px}
.page-hero p{color:#dcebdb;max-width:760px;font-size:1.1rem}
.bcrumb{display:inline-flex;gap:.5rem;align-items:center;color:#a8cfb8;font-size:.9rem;margin-bottom:1rem}
.bcrumb a{color:#a8cfb8}.bcrumb a:hover{color:#fff}
.region-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1.4rem}
.region-card{position:relative;border-radius:22px;overflow:hidden;min-height:280px;color:#fff;padding:2rem;display:flex;flex-direction:column;justify-content:flex-end;background:linear-gradient(135deg,#073820,#0d623a);transition:.25s}
.region-card:hover{transform:translateY(-4px);color:#fff;box-shadow:0 30px 60px -25px rgba(13,98,58,.6)}
.region-card::before{content:'';position:absolute;top:-50px;right:-50px;width:200px;height:200px;border-radius:50%;background:radial-gradient(circle,rgba(255,255,255,.18),transparent 70%)}
.region-card .icon{position:absolute;top:1.4rem;right:1.4rem;font-size:1.4rem;opacity:.5}
.region-card h2{color:#fff;margin-bottom:.6rem;font-size:1.6rem}
.region-card p{color:#dcebdb;margin:0 0 1rem;font-size:.94rem}
.region-card .more{display:inline-flex;align-items:center;gap:.4rem;font-weight:700;font-size:.9rem;color:#fff}
</style>

<section class="page-hero">
  <div class="container-xxl" style="position:relative;z-index:2">
    <span class="bcrumb"><a href="<?= I18n::url('', $locale) ?>"><?= htmlspecialchars(I18n::t('breadcrumb.home')) ?></a> / <span><?= htmlspecialchars(I18n::t('common.markets')) ?></span></span>
    <h1><?= htmlspecialchars($siteText('markets_title', I18n::t('markets.h1'))) ?></h1>
    <p><?= htmlspecialchars($siteText('markets_desc', I18n::t('markets.lead'))) ?></p>
  </div>
</section>

<section class="block">
  <div class="container-xxl">
    <div class="region-grid">
      <?php
        $regionIcons = [
          'europe'       => 'fa-earth-europe',
          'dubai-uae'    => 'fa-mosque',
          'saudi-arabia' => 'fa-kaaba',
          'egypt'        => 'fa-monument',
          'russia'       => 'fa-snowflake',
        ];
        foreach ($regions as $r):
          $textKeys = $marketTextKeys[$r['key']] ?? null;
          $name = $textKeys ? $siteText($textKeys[0], $r['name']) : $r['name'];
          $desc = $textKeys ? $siteText($textKeys[1], $r['desc']) : $r['desc'];
      ?>
        <a href="<?= htmlspecialchars($r['url']) ?>" class="region-card fade-in">
          <i class="fas <?= htmlspecialchars($regionIcons[$r['key']] ?? 'fa-globe') ?> icon"></i>
          <h2><?= htmlspecialchars($name) ?></h2>
          <p><?= htmlspecialchars($desc) ?></p>
          <span class="more"><?= htmlspecialchars(I18n::t('common.cta_more')) ?> <i class="fas fa-arrow-right"></i></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
