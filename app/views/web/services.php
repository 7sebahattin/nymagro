<?php
$LL   = I18n::all();
$list = $LL['services']['list'] ?? [];
?>
<style>
.page-hero{padding:5rem 0 3rem;background:linear-gradient(135deg,#06281a 0%,#073820 60%,#0d623a 100%);color:#fff;position:relative;overflow:hidden}
.page-hero::before{content:'';position:absolute;inset:0;background-image:radial-gradient(rgba(255,255,255,.06) 1px,transparent 1px);background-size:32px 32px}
.page-hero h1{color:#fff;max-width:760px}
.page-hero p{color:#dcebdb;max-width:760px;font-size:1.1rem}
.bcrumb{display:inline-flex;gap:.5rem;align-items:center;color:#a8cfb8;font-size:.9rem;margin-bottom:1rem}
.bcrumb a{color:#a8cfb8}.bcrumb a:hover{color:#fff}
.svc-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1.4rem}
.svc-card{background:#fff;border-radius:18px;border:1px solid rgba(13,98,58,.1);padding:1.8rem;height:100%;transition:.25s;position:relative;overflow:hidden}
.svc-card::before{content:'';position:absolute;top:0;right:0;width:120px;height:120px;background:radial-gradient(circle,rgba(13,98,58,.08),transparent 70%);border-radius:50%;transform:translate(40%,-40%)}
.svc-card:hover{transform:translateY(-6px);box-shadow:0 22px 50px -22px rgba(13,98,58,.4);border-color:rgba(13,98,58,.25)}
.svc-icon{width:60px;height:60px;border-radius:16px;background:var(--grad);color:#fff;font-size:1.4rem;display:flex;align-items:center;justify-content:center;margin-bottom:1.2rem;box-shadow:0 14px 30px -12px rgba(13,98,58,.55)}
.svc-card h3{margin:0 0 .6rem;font-size:1.2rem}
.svc-card p{margin:0;color:var(--ink2);font-size:.95rem}
</style>

<section class="page-hero">
  <div class="container-xxl" style="position:relative;z-index:2">
    <span class="bcrumb"><a href="<?= I18n::url('', $locale) ?>"><?= htmlspecialchars(I18n::t('breadcrumb.home')) ?></a> / <span><?= htmlspecialchars(I18n::t('common.services')) ?></span></span>
    <h1><?= htmlspecialchars(I18n::t('services.h1')) ?></h1>
    <p><?= htmlspecialchars(I18n::t('services.lead')) ?></p>
  </div>
</section>

<section class="block">
  <div class="container-xxl">
    <div class="svc-grid">
      <?php
      $svcIcons = ['fa-leaf','fa-shield-halved','fa-box-open','fa-ship','fa-truck-fast','fa-list-check'];
      foreach ($list as $i => $s): ?>
        <div class="svc-card fade-in">
          <div class="svc-icon"><i class="fas <?= htmlspecialchars($svcIcons[$i % count($svcIcons)]) ?>"></i></div>
          <h3><?= htmlspecialchars($s['t']) ?></h3>
          <p><?= htmlspecialchars($s['d']) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
