<?php
$LL = I18n::all();
?>
<style>
.page-hero{padding:5rem 0 3rem;background:linear-gradient(135deg,#06281a 0%,#073820 60%,#0d623a 100%);color:#fff;position:relative;overflow:hidden}
.page-hero::before{content:'';position:absolute;inset:0;background-image:radial-gradient(rgba(255,255,255,.06) 1px,transparent 1px);background-size:32px 32px}
.page-hero h1{color:#fff;max-width:760px}
.page-hero p{color:#dcebdb;max-width:760px;font-size:1.1rem}
.bcrumb{display:inline-flex;gap:.5rem;align-items:center;color:#a8cfb8;font-size:.9rem;margin-bottom:1rem}
.bcrumb a{color:#a8cfb8}.bcrumb a:hover{color:#fff}
.about-section .row{align-items:center;gap:0}
.about-section img{border-radius:24px;width:100%;height:100%;object-fit:cover;max-height:500px}
.value-card{padding:1.4rem;border-radius:14px;background:#fff;border:1px solid rgba(13,98,58,.1);height:100%}
.value-card .ic{width:46px;height:46px;border-radius:12px;background:linear-gradient(135deg,rgba(13,98,58,.12),rgba(217,122,12,.1));color:var(--p1);display:flex;align-items:center;justify-content:center;margin-bottom:.85rem}
</style>

<section class="page-hero">
  <div class="container-xxl" style="position:relative;z-index:2">
    <span class="bcrumb"><a href="<?= I18n::url('', $locale) ?>"><?= htmlspecialchars(I18n::t('breadcrumb.home')) ?></a> / <span><?= htmlspecialchars(I18n::t('common.about')) ?></span></span>
    <h1><?= htmlspecialchars(I18n::t('about.h1')) ?></h1>
    <p><?= htmlspecialchars(I18n::t('about.lead')) ?></p>
  </div>
</section>

<section class="block about-section">
  <div class="container-xxl">
    <div class="row g-5">
      <div class="col-lg-6">
        <img src="<?= htmlspecialchars((string)($ayarlar['about_img'] ?? '')) ?>" alt="Nymagro — bitki besleme ürünleri, Antalya" loading="lazy">
      </div>
      <div class="col-lg-6">
        <p style="color:var(--ink2);font-size:1.05rem"><?= htmlspecialchars(I18n::t('about.p1')) ?></p>
        <p style="color:var(--ink2)"><?= htmlspecialchars(I18n::t('about.p2')) ?></p>
        <div class="row g-3 mt-3">
          <div class="col-md-6">
            <div class="value-card">
              <div class="ic"><i class="fas fa-bullseye"></i></div>
              <h4><?= htmlspecialchars(I18n::t('about.mission_title')) ?></h4>
              <p style="color:var(--ink2);margin:0"><?= htmlspecialchars(I18n::t('about.mission_text')) ?></p>
            </div>
          </div>
          <div class="col-md-6">
            <div class="value-card">
              <div class="ic"><i class="fas fa-eye"></i></div>
              <h4><?= htmlspecialchars(I18n::t('about.vision_title')) ?></h4>
              <p style="color:var(--ink2);margin:0"><?= htmlspecialchars(I18n::t('about.vision_text')) ?></p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="block" style="background:linear-gradient(180deg,#fff 0%,#f6fbf5 100%)">
  <div class="container-xxl">
    <div class="section-head">
      <span class="kicker"><i class="fas fa-heart"></i> <?= htmlspecialchars(I18n::t('about.values_title')) ?></span>
      <h2><?= htmlspecialchars(I18n::t('about.values_title')) ?></h2>
    </div>
    <div class="row g-3">
      <?php $vIcons = ['fa-medal','fa-comments','fa-bullseye','fa-leaf','fa-handshake'];
            foreach (($LL['about']['values'] ?? []) as $i => $v): ?>
        <div class="col-md-6 col-lg-4">
          <div class="value-card text-center">
            <div class="ic mx-auto"><i class="fas <?= $vIcons[$i % 5] ?>"></i></div>
            <h4 class="mb-0"><?= htmlspecialchars($v) ?></h4>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
