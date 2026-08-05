<?php /* 404 page */ ?>
<style>
.notfound{padding:6rem 0 4rem;text-align:center}
.notfound .code{font-family:'Manrope';font-size:8rem;line-height:.9;font-weight:800;background:var(--grad);-webkit-background-clip:text;background-clip:text;color:transparent;letter-spacing:-.04em}
.notfound p{color:var(--ink2);font-size:1.1rem;margin:1rem auto 2rem;max-width:520px}
</style>

<section class="notfound">
  <div class="container-xxl">
    <div class="code">404</div>
    <h2 class="mt-3"><?= htmlspecialchars(I18n::t('errors.404_title')) ?></h2>
    <p><?= htmlspecialchars(I18n::t('errors.404_desc')) ?></p>
    <a href="<?= I18n::url('', $locale) ?>" class="btn-primary-grad">
      <i class="fas fa-house"></i> <?= htmlspecialchars(I18n::t('errors.go_home')) ?>
    </a>
  </div>
</section>
