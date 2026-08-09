<?php /* Products list page */ ?>
<style>
.page-hero{padding:4.5rem 0 3rem;background:linear-gradient(135deg,#06281a 0%,#073820 60%,#0d623a 100%);color:#fff;position:relative;overflow:hidden}
.page-hero::before{content:'';position:absolute;inset:0;background-image:radial-gradient(rgba(255,255,255,.06) 1px,transparent 1px);background-size:32px 32px}
.page-hero h1{color:#fff;max-width:760px}
.page-hero p{color:#dcebdb;max-width:760px;font-size:1.1rem}
.bcrumb{display:inline-flex;gap:.5rem;align-items:center;color:#a8cfb8;font-size:.9rem;margin-bottom:1rem}
.bcrumb a{color:#a8cfb8}.bcrumb a:hover{color:#fff}
.products-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:1.4rem}
.product-card{background:#fff;border-radius:18px;overflow:hidden;box-shadow:0 6px 22px -10px rgba(6,40,26,.18);border:1px solid rgba(13,98,58,.08);transition:.25s;display:flex;flex-direction:column}
.product-card:hover{transform:translateY(-6px);box-shadow:0 22px 50px -22px rgba(13,98,58,.4);border-color:rgba(13,98,58,.2)}
.product-img{position:relative;height:200px;overflow:hidden;background:#eef6ed}
.product-img img{width:100%;height:100%;object-fit:cover;transition:transform .5s}
.product-card:hover .product-img img{transform:scale(1.05)}
.product-tag{position:absolute;top:.7rem;left:.7rem;background:var(--grad);color:#fff;font-size:.65rem;font-weight:800;letter-spacing:.12em;padding:.3rem .7rem;border-radius:999px}
.product-body{padding:1.1rem 1.2rem 1.3rem;flex:1;display:flex;flex-direction:column;gap:.5rem}
.product-body h3{font-size:1.15rem;margin:0}
.product-body p{color:#47745f;font-size:.86rem;flex:1;margin:0}
.product-meta{display:flex;gap:.5rem;flex-wrap:wrap;margin:.4rem 0}
.meta-pill{font-size:.7rem;color:var(--p1);background:rgba(13,98,58,.08);padding:.25rem .6rem;border-radius:999px;font-weight:600}
.product-cta{display:flex;gap:.55rem;align-items:center;margin-top:.5rem}
.product-cta .detail,
.product-cta .quote{flex:1;display:inline-flex;align-items:center;justify-content:center;gap:.3rem;text-align:center;font-weight:700;font-size:.78rem;font-family:inherit;line-height:1.2;box-sizing:border-box;padding:.5rem .7rem;border-radius:999px;border:0;cursor:pointer;white-space:nowrap}
.product-cta .detail{background:var(--grad-cta);color:#fff}
.product-cta .quote{background:var(--grad);color:#fff}
</style>

<section class="page-hero">
  <div class="container-xxl" style="position:relative;z-index:2">
    <span class="bcrumb"><a href="<?= I18n::url('', $locale) ?>"><?= htmlspecialchars(I18n::t('breadcrumb.home')) ?></a> / <span><?= htmlspecialchars(I18n::t('common.products')) ?></span></span>
    <h1><?= htmlspecialchars(I18n::t('products.h1')) ?></h1>
    <p><?= htmlspecialchars(I18n::t('products.lead')) ?></p>
  </div>
</section>

<section class="block">
  <div class="container-xxl">
    <div class="products-grid">
      <?php foreach ($urunler as $u):
        $ad   = $u['ad_'.$locale]   ?? $u['ad_tr']   ?? '';
        $desc = $u['aciklama_'.$locale] ?? $u['aciklama_tr'] ?? '';
        $slug = $u['slug_'.$locale] ?? $u['slug_tr'] ?? SiteIcerik::slugify($ad);
        $img  = $u['gorsel'] ?? '';
        $sezon = $u['sezon_'.$locale] ?? '';
        $etiket = $u['etiket'] ?? 'YENİ';
      ?>
        <article class="product-card fade-in">
          <a href="<?= I18n::altUrl('products', $locale, $slug) ?>" class="product-img" aria-label="<?= htmlspecialchars($ad) ?>">
            <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($ad) ?> — Nymagro bitki besleme ürünü" loading="lazy" width="400" height="300">
            <span class="product-tag"><?= htmlspecialchars($etiket) ?></span>
          </a>
          <div class="product-body">
            <h3><a href="<?= I18n::altUrl('products', $locale, $slug) ?>" style="color:var(--ink)"><?= htmlspecialchars($ad) ?></a></h3>
            <p><?= htmlspecialchars(mb_strimwidth($desc, 0, 120, '…')) ?></p>
            <?php if ($sezon): ?><div class="product-meta"><span class="meta-pill"><i class="far fa-calendar"></i> <?= htmlspecialchars($sezon) ?></span></div><?php endif; ?>
            <div class="product-cta">
              <a href="<?= I18n::altUrl('products', $locale, $slug) ?>" class="detail"><?= htmlspecialchars(I18n::t('common.cta_more')) ?> <i class="fas fa-arrow-right"></i></a>
              <button type="button" class="quote" onclick="openQuoteModal(<?= htmlspecialchars(json_encode($ad, JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>)"><?= htmlspecialchars(I18n::t('common.cta_quote')) ?></button>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
