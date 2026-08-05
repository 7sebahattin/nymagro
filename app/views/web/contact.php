<?php /* Contact + form */ ?>
<style>
.page-hero{padding:5rem 0 3rem;background:linear-gradient(135deg,#1f1334 0%,#3b1c5e 60%,#7c3aed 100%);color:#fff;position:relative;overflow:hidden}
.page-hero::before{content:'';position:absolute;inset:0;background-image:radial-gradient(rgba(255,255,255,.06) 1px,transparent 1px);background-size:32px 32px}
.page-hero h1{color:#fff;max-width:760px}
.page-hero p{color:#e6dffa;max-width:760px;font-size:1.1rem}
.bcrumb{display:inline-flex;gap:.5rem;align-items:center;color:#cdb9ee;font-size:.9rem;margin-bottom:1rem}
.bcrumb a{color:#cdb9ee}.bcrumb a:hover{color:#fff}
.contact-grid{display:grid;grid-template-columns:1fr 1.4fr;gap:2.5rem;align-items:start}
@media(max-width:992px){.contact-grid{grid-template-columns:1fr}}
.info-card{background:#fff;border:1px solid rgba(124,58,237,.1);border-radius:18px;padding:1.6rem 1.8rem;margin-bottom:1rem}
.info-card h3{font-size:1.15rem;margin-bottom:1rem;color:var(--ink)}
.info-row{display:flex;align-items:flex-start;gap:.85rem;padding:.7rem 0;border-bottom:1px solid rgba(124,58,237,.08)}
.info-row:last-child{border-bottom:0}
.info-row .ic{width:38px;height:38px;border-radius:11px;background:linear-gradient(135deg,rgba(124,58,237,.12),rgba(192,38,211,.1));color:var(--p1);display:flex;align-items:center;justify-content:center;flex-shrink:0}
.info-row strong{display:block;font-size:.78rem;color:var(--ink2);text-transform:uppercase;letter-spacing:.1em}
.info-row a, .info-row span{color:var(--ink);font-weight:600;display:block;font-size:.95rem;word-break:break-word}
.form-card{background:#fff;border:1px solid rgba(124,58,237,.1);border-radius:18px;padding:2rem 2.2rem;box-shadow:0 22px 50px -22px rgba(31,19,52,.18)}
.form-card label{font-size:.84rem;font-weight:700;color:var(--ink2);margin-bottom:.4rem;display:block}
.form-card input, .form-card textarea, .form-card select{width:100%;border:1.5px solid rgba(124,58,237,.18);border-radius:12px;padding:.8rem 1rem;font-size:.95rem;background:#fff;transition:.2s}
.form-card input:focus, .form-card textarea:focus, .form-card select:focus{border-color:var(--p1);outline:none;box-shadow:0 0 0 4px rgba(124,58,237,.12)}
.form-card textarea{min-height:140px;resize:vertical}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
@media(max-width:768px){.form-grid{grid-template-columns:1fr}}
.flash{padding:.85rem 1.1rem;border-radius:12px;margin-bottom:1.25rem;font-weight:600;font-size:.92rem;display:flex;align-items:center;gap:.6rem}
.flash.ok{background:linear-gradient(135deg,rgba(34,197,94,.12),rgba(16,185,129,.08));color:#15803d;border:1px solid rgba(34,197,94,.2)}
.flash.err{background:linear-gradient(135deg,rgba(239,68,68,.12),rgba(220,38,38,.08));color:#b91c1c;border:1px solid rgba(239,68,68,.2)}
.consent{display:flex;gap:.6rem;align-items:flex-start;font-size:.85rem;color:var(--ink2)}
.consent input{width:auto;margin-top:.25rem}
.honey{position:absolute;left:-9999px;top:-9999px;visibility:hidden}
.map-frame{border-radius:18px;overflow:hidden;border:1px solid rgba(124,58,237,.1);height:300px;margin-top:1rem}
.map-frame iframe{width:100%;height:100%;border:0}
</style>

<section class="page-hero">
  <div class="container-xxl" style="position:relative;z-index:2">
    <span class="bcrumb"><a href="<?= I18n::url('', $locale) ?>"><?= htmlspecialchars(I18n::t('breadcrumb.home')) ?></a> / <span><?= htmlspecialchars(I18n::t('common.contact')) ?></span></span>
    <h1><?= htmlspecialchars(I18n::t('contact.h1')) ?></h1>
    <p><?= htmlspecialchars(I18n::t('contact.lead')) ?></p>
  </div>
</section>

<section class="block">
  <div class="container-xxl">
    <div class="contact-grid">

      <div>
        <div class="info-card">
          <h3><?= htmlspecialchars(I18n::t('common.company_info')) ?></h3>
          <div class="info-row">
            <div class="ic"><i class="fas fa-location-dot"></i></div>
            <div><strong><?= htmlspecialchars(I18n::t('common.address')) ?></strong><span><?= htmlspecialchars((string)($ayarlar['address'] ?? '')) ?></span></div>
          </div>
          <?php if (!empty($ayarlar['phone'])): ?>
          <div class="info-row">
            <div class="ic"><i class="fas fa-phone"></i></div>
            <div><strong><?= htmlspecialchars(I18n::t('common.phone')) ?></strong><a href="tel:<?= htmlspecialchars(preg_replace('/\D+/', '', $ayarlar['phone'])) ?>"><?= htmlspecialchars($ayarlar['phone']) ?></a></div>
          </div>
          <?php endif; ?>
          <?php if (!empty($ayarlar['whatsapp_link'])): ?>
          <div class="info-row">
            <div class="ic" style="background:linear-gradient(135deg,rgba(37,211,102,.15),rgba(37,211,102,.08));color:#25d366"><i class="fab fa-whatsapp"></i></div>
            <div><strong>WhatsApp</strong><a href="<?= htmlspecialchars($ayarlar['whatsapp_link']) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($ayarlar['whatsapp'] ?? '') ?></a></div>
          </div>
          <?php endif; ?>
          <?php if (!empty($ayarlar['email'])): ?>
          <div class="info-row">
            <div class="ic"><i class="fas fa-envelope"></i></div>
            <div><strong><?= htmlspecialchars(I18n::t('common.email')) ?></strong><a href="mailto:<?= htmlspecialchars($ayarlar['email']) ?>"><?= htmlspecialchars($ayarlar['email']) ?></a></div>
          </div>
          <?php endif; ?>
        </div>

        <div class="map-frame">
          <iframe loading="lazy" referrerpolicy="no-referrer-when-downgrade"
            src="https://www.google.com/maps?q=<?= urlencode((string)($ayarlar['address'] ?? 'Antalya, Türkiye')) ?>&output=embed"
            title="Nuverna Trade — Antalya"></iframe>
        </div>
      </div>

      <div>
        <div id="contact-form"></div>
        <div class="form-card">
          <?php if (!empty($flash)): ?>
            <div class="flash <?= !empty($flash['ok']) ? 'ok' : 'err' ?>">
              <i class="fas <?= !empty($flash['ok']) ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
              <?= htmlspecialchars($flash['msg']) ?>
            </div>
          <?php endif; ?>

          <form method="POST" action="<?= I18n::url(I18n::pageMap()[$locale]['contact'] . '/gonder', $locale) ?>" novalidate>
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>">
            <!-- honeypot -->
            <input type="text" name="website_url" class="honey" tabindex="-1" autocomplete="off">

            <div class="form-grid">
              <div class="mb-3">
                <label for="ad_soyad"><?= htmlspecialchars(I18n::t('contact.form.name')) ?> *</label>
                <input id="ad_soyad" name="ad_soyad" type="text" required minlength="2" maxlength="120">
              </div>
              <div class="mb-3">
                <label for="firma"><?= htmlspecialchars(I18n::t('contact.form.company')) ?></label>
                <input id="firma" name="firma" type="text" maxlength="120">
              </div>
              <div class="mb-3">
                <label for="ulke"><?= htmlspecialchars(I18n::t('contact.form.country')) ?></label>
                <input id="ulke" name="ulke" type="text" maxlength="80">
              </div>
              <div class="mb-3">
                <label for="email"><?= htmlspecialchars(I18n::t('contact.form.email')) ?> *</label>
                <input id="email" name="email" type="email" required maxlength="120">
              </div>
              <div class="mb-3">
                <label for="telefon"><?= htmlspecialchars(I18n::t('contact.form.phone')) ?></label>
                <input id="telefon" name="telefon" type="tel" maxlength="40">
              </div>
              <div class="mb-3">
                <label for="urun"><?= htmlspecialchars(I18n::t('contact.form.product')) ?></label>
                <input id="urun" name="urun" type="text" value="<?= htmlspecialchars((string)($_GET['p'] ?? '')) ?>" maxlength="120">
              </div>
            </div>
            <div class="mb-3">
              <label for="mesaj"><?= htmlspecialchars(I18n::t('contact.form.message')) ?> *</label>
              <textarea id="mesaj" name="mesaj" required minlength="5" maxlength="3500" placeholder="<?= htmlspecialchars(I18n::t('contact.form.placeholder_message')) ?>"></textarea>
            </div>
            <label class="consent mb-3">
              <input type="checkbox" required>
              <span><?= htmlspecialchars(I18n::t('contact.form.consent')) ?></span>
            </label>
            <button type="submit" class="btn-primary-grad" style="border:0">
              <i class="fas fa-paper-plane"></i> <?= htmlspecialchars(I18n::t('contact.form.submit')) ?>
            </button>
          </form>
        </div>
      </div>

    </div>
  </div>
</section>
