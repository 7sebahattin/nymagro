<?php /* Gallery */ ?>
<style>
.page-hero{padding:5rem 0 3rem;background:linear-gradient(135deg,#06281a 0%,#073820 60%,#0d623a 100%);color:#fff;position:relative;overflow:hidden}
.page-hero::before{content:'';position:absolute;inset:0;background-image:radial-gradient(rgba(255,255,255,.06) 1px,transparent 1px);background-size:32px 32px}
.page-hero h1{color:#fff;max-width:760px}
.page-hero p{color:#dcebdb;max-width:760px;font-size:1.1rem}
.bcrumb{display:inline-flex;gap:.5rem;align-items:center;color:#a8cfb8;font-size:.9rem;margin-bottom:1rem}
.bcrumb a{color:#a8cfb8}.bcrumb a:hover{color:#fff}
.gallery-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));grid-auto-rows:220px;gap:1rem}
.gallery-item{position:relative;border-radius:14px;overflow:hidden;background:#06281a}
.gallery-item img{width:100%;height:100%;object-fit:cover;transition:transform .5s,filter .3s}
.gallery-item:hover img{transform:scale(1.06);filter:brightness(.7)}
.gallery-item .tag{position:absolute;left:.8rem;bottom:.8rem;background:rgba(0,0,0,.5);color:#fff;font-size:.78rem;font-weight:700;padding:.35rem .8rem;border-radius:999px;backdrop-filter:blur(4px);opacity:0;transition:opacity .3s}
.gallery-item:hover .tag{opacity:1}
.gallery-item:nth-child(5n+1){grid-row:span 2}
</style>

<section class="page-hero">
  <div class="container-xxl" style="position:relative;z-index:2">
    <span class="bcrumb"><a href="<?= I18n::url('', $locale) ?>"><?= htmlspecialchars(I18n::t('breadcrumb.home')) ?></a> / <span><?= htmlspecialchars(I18n::t('common.gallery')) ?></span></span>
    <h1><?= htmlspecialchars(I18n::t('gallery.h1')) ?></h1>
    <p><?= htmlspecialchars(I18n::t('gallery.lead')) ?></p>
  </div>
</section>

<section class="block">
  <div class="container-xxl">
    <?php if (!empty($galeri)): ?>
      <div class="gallery-grid">
        <?php foreach ($galeri as $g):
          $img = $g['gorsel'] ?? '';
          $tag = $g['etiket_'.$locale] ?? $g['etiket_tr'] ?? '';
        ?>
          <div class="gallery-item fade-in">
            <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($tag ?: 'Nymagro — ürün ve saha uygulama galerisi') ?>" loading="lazy">
            <?php if ($tag): ?><span class="tag"><?= htmlspecialchars($tag) ?></span><?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="text-center py-5" style="color:var(--ink2)">
        <i class="far fa-images" style="font-size:3rem;color:var(--p1);opacity:.4"></i>
        <p class="mt-3"><?= htmlspecialchars(I18n::t('gallery.lead')) ?></p>
      </div>
    <?php endif; ?>
  </div>
</section>
