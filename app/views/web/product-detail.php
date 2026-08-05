<?php /* Product detail page — SEO landing per product */ ?>
<style>
.pd-hero{background:linear-gradient(135deg,#1f1334 0%,#3b1c5e 60%,#7c3aed 100%);color:#fff;padding:3rem 0 5rem;position:relative;overflow:hidden}
.pd-hero::before{content:'';position:absolute;inset:0;background-image:radial-gradient(rgba(255,255,255,.06) 1px,transparent 1px);background-size:32px 32px}
.pd-hero h1{color:#fff;margin:.5rem 0 1rem}
.pd-hero p{color:#e6dffa;font-size:1.05rem;max-width:600px}
.bcrumb{display:inline-flex;gap:.5rem;align-items:center;color:#cdb9ee;font-size:.9rem;margin-bottom:1rem;flex-wrap:wrap}
.bcrumb a{color:#cdb9ee}
.bcrumb a:hover{color:#fff}
.pd-grid{display:grid;grid-template-columns:1.1fr 1fr;gap:3rem;align-items:center}
@media(max-width:992px){.pd-grid{grid-template-columns:1fr}}
.pd-image{border-radius:24px;overflow:hidden;box-shadow:0 30px 80px -30px rgba(0,0,0,.5);max-height:520px}
.pd-image img{width:100%;height:520px;object-fit:cover}
.spec-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;margin-top:2rem}
.spec-card{background:#fff;border:1px solid rgba(124,58,237,.1);border-radius:14px;padding:1.2rem}
.spec-card h4{font-size:.85rem;color:var(--p1);text-transform:uppercase;letter-spacing:.12em;margin-bottom:.5rem}
.spec-card p{margin:0;color:var(--ink);font-weight:600}
.faq-item{background:#fff;border:1px solid rgba(124,58,237,.1);border-radius:14px;margin-bottom:.85rem;overflow:hidden}
.faq-item summary{cursor:pointer;padding:1.1rem 1.3rem;font-weight:700;list-style:none;display:flex;justify-content:space-between;align-items:center}
.faq-item summary::-webkit-details-marker{display:none}
.faq-item summary::after{content:'\f078';font-family:'Font Awesome 6 Free';font-weight:900;color:var(--p1);transition:transform .2s}
.faq-item[open] summary::after{transform:rotate(180deg)}
.faq-item .body{padding:0 1.3rem 1.2rem;color:var(--ink2)}
.inquiry-card{background:linear-gradient(135deg,#7c3aed,#c026d3);color:#fff;padding:2.5rem;border-radius:24px;text-align:center;margin-top:2rem}
.inquiry-card h3{color:#fff;margin-bottom:.7rem}
.inquiry-card p{color:#f0e9fc;margin-bottom:1.6rem}
.related-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1.2rem}
.rel-card{display:flex;flex-direction:column;background:#fff;border-radius:16px;border:1px solid rgba(124,58,237,.08);overflow:hidden;transition:.25s}
.rel-card:hover{transform:translateY(-4px);box-shadow:0 18px 40px -18px rgba(124,58,237,.4)}
.rel-card img{height:160px;width:100%;object-fit:cover}
.rel-card .body{padding:.85rem 1rem 1rem}
.rel-card h4{margin:0 0 .3rem;font-size:1rem;color:var(--ink)}
.rel-card span{font-size:.8rem;color:var(--p1);font-weight:700}
</style>

<section class="pd-hero">
  <div class="container-xxl" style="position:relative;z-index:2">
    <span class="bcrumb">
      <a href="<?= I18n::url('', $locale) ?>"><?= htmlspecialchars(I18n::t('breadcrumb.home')) ?></a> /
      <a href="<?= I18n::altUrl('products', $locale) ?>"><?= htmlspecialchars(I18n::t('common.products')) ?></a> /
      <span><?= htmlspecialchars($ad) ?></span>
    </span>
    <h1><?= htmlspecialchars($ad) ?></h1>
    <p><?= htmlspecialchars($aciklama) ?></p>
  </div>
</section>

<section class="container-xxl" style="margin-top:-4rem;position:relative;z-index:5">
  <div class="pd-grid">
    <div class="pd-image">
      <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($ad) ?> — Nuverna Trade Türkiye fresh produce export" width="900" height="520">
    </div>
    <div>
      <span class="kicker"><i class="fas fa-leaf"></i> <?= htmlspecialchars(I18n::t('common.fresh')) ?></span>
      <h2 class="mt-3"><?= htmlspecialchars(I18n::t('products.detail.description')) ?></h2>
      <p style="color:var(--ink2);font-size:1.05rem"><?= htmlspecialchars($aciklama) ?></p>

      <div class="spec-grid">
        <?php if (!empty($urun['sezon_'.$locale]) || !empty($urun['sezon_tr'])): ?>
          <div class="spec-card">
            <h4><?= htmlspecialchars(I18n::t('products.detail.season')) ?></h4>
            <p><?= htmlspecialchars($urun['sezon_'.$locale] ?? $urun['sezon_tr']) ?></p>
          </div>
        <?php endif; ?>
        <?php if (!empty($urun['paketleme_'.$locale]) || !empty($urun['paketleme_tr'])): ?>
          <div class="spec-card">
            <h4><?= htmlspecialchars(I18n::t('products.detail.packaging')) ?></h4>
            <p><?= htmlspecialchars($urun['paketleme_'.$locale] ?? $urun['paketleme_tr']) ?></p>
          </div>
        <?php endif; ?>
        <?php if (!empty($urun['pazarlar'])): ?>
          <div class="spec-card">
            <h4><?= htmlspecialchars(I18n::t('products.detail.markets')) ?></h4>
            <p><?= htmlspecialchars($urun['pazarlar']) ?></p>
          </div>
        <?php endif; ?>
        <div class="spec-card">
          <h4><?= htmlspecialchars(I18n::t('products.detail.quality')) ?></h4>
          <p><?= htmlspecialchars(I18n::t('quality.steps.1.d') ?: 'GlobalGAP, ISO 22000') ?></p>
        </div>
      </div>

      <div class="mt-4 d-flex gap-2 flex-wrap">
        <a href="<?= I18n::altUrl('contact', $locale) ?>?p=<?= urlencode($ad) ?>" class="btn-primary-grad"><i class="fas fa-paper-plane"></i> <?= htmlspecialchars(I18n::t('products.detail.inquiry')) ?></a>
        <?php if (!empty($ayarlar['whatsapp_link'])): ?>
          <a href="<?= htmlspecialchars($ayarlar['whatsapp_link']) ?>" target="_blank" rel="noopener" class="btn-outline-grad"><i class="fab fa-whatsapp"></i> WhatsApp</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<!-- FAQ -->
<section class="block">
  <div class="container-xxl">
    <div class="section-head">
      <span class="kicker"><i class="fas fa-circle-question"></i> FAQ</span>
      <h2><?= htmlspecialchars(I18n::t('products.detail.faq')) ?></h2>
    </div>
    <div style="max-width:780px;margin:0 auto">
      <?php foreach ($faqs as $f): ?>
        <details class="faq-item">
          <summary><?= htmlspecialchars($f['q']) ?></summary>
          <div class="body"><?= htmlspecialchars($f['a']) ?></div>
        </details>
      <?php endforeach; ?>
    </div>

    <div class="inquiry-card fade-in">
      <h3><?= htmlspecialchars(I18n::t('products.detail.inquiry')) ?></h3>
      <p><?= htmlspecialchars(I18n::t('home.cta_band.desc')) ?></p>
      <a href="<?= I18n::altUrl('contact', $locale) ?>?p=<?= urlencode($ad) ?>" class="btn-w" style="background:#fff;color:var(--p1);font-weight:800;padding:1rem 2rem;border-radius:999px"><i class="fas fa-paper-plane"></i> <?= htmlspecialchars(I18n::t('home.cta_band.btn')) ?></a>
    </div>
  </div>
</section>

<!-- RELATED -->
<?php if (!empty($ilgili)): ?>
<section class="block" style="background:linear-gradient(180deg,#fff 0%,#faf6ff 100%)">
  <div class="container-xxl">
    <div class="section-head">
      <h2><?= htmlspecialchars(I18n::t('products.detail.related')) ?></h2>
    </div>
    <div class="related-grid">
      <?php foreach ($ilgili as $r):
        $rAd   = $r['ad_'.$locale]   ?? $r['ad_tr']   ?? '';
        $rSlug = $r['slug_'.$locale] ?? $r['slug_tr'] ?? SiteIcerik::slugify($rAd);
        $rImg  = $r['gorsel'] ?? '';
      ?>
        <a href="<?= I18n::altUrl('products', $locale, $rSlug) ?>" class="rel-card">
          <img src="<?= htmlspecialchars($rImg) ?>" alt="<?= htmlspecialchars($rAd) ?>" loading="lazy">
          <div class="body">
            <h4><?= htmlspecialchars($rAd) ?></h4>
            <span><?= htmlspecialchars(I18n::t('common.cta_more')) ?> <i class="fas fa-arrow-right"></i></span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
