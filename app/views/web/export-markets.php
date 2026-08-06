<?php
/* Ürün grupları listesi (kategori bazlı) */
// $siteText controller tarafından inject edilir
$siteText = $siteText ?? static fn(string $k, string $d = '') => $d;
$comingSoonLabel = [
    'tr' => 'Yakında',
    'en' => 'Coming soon',
    'ru' => 'Скоро',
][$locale] ?? 'Yakında';
$productCountLabel = static function (int $count, string $locale): string {
    if ($count === 0) return '';
    $labels = [
        'tr' => "{$count} ürün",
        'en' => $count === 1 ? '1 product' : "{$count} products",
        'ru' => "{$count} товар(ов)",
    ];
    return $labels[$locale] ?? $labels['en'];
};
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
.region-card .count-pill{position:absolute;top:1.4rem;left:1.4rem;background:rgba(255,255,255,.16);color:#fff;font-size:.7rem;font-weight:800;letter-spacing:.06em;padding:.3rem .7rem;border-radius:999px}
.region-card.is-empty{opacity:.72}
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
          'micro'        => 'fa-flask',
          'macro'        => 'fa-seedling',
          'biostimulant' => 'fa-bolt',
        ];
        foreach ($regions as $r):
          $name  = $r['name'];
          $desc  = $r['desc'];
          $count = (int)($r['count'] ?? 0);
          $countText = $count > 0 ? $productCountLabel($count, $locale) : $comingSoonLabel;
      ?>
        <a href="<?= htmlspecialchars($r['url']) ?>" class="region-card fade-in<?= $count === 0 ? ' is-empty' : '' ?>">
          <i class="fas <?= htmlspecialchars($regionIcons[$r['key']] ?? 'fa-globe') ?> icon"></i>
          <span class="count-pill"><?= htmlspecialchars($countText) ?></span>
          <h2><?= htmlspecialchars($name) ?></h2>
          <p><?= htmlspecialchars($desc) ?></p>
          <span class="more"><?= htmlspecialchars(I18n::t('common.cta_more')) ?> <i class="fas fa-arrow-right"></i></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
