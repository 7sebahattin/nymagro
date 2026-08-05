<?php
$LL       = I18n::all();
$lang     = I18n::current();
// $siteText controller tarafından inject edilir; inject edilmediyse basit fallback
$siteText = $siteText ?? static fn(string $k, string $d = '') => $d;
$defaultSteps = $LL['quality']['steps'] ?? [];
$steps = [];
foreach ($defaultSteps as $i => $step) {
    $n = $i + 1;
    $steps[] = [
        't' => $siteText("quality_step{$n}_t", $step['t'] ?? ''),
        'd' => $siteText("quality_step{$n}_d", $step['d'] ?? ''),
    ];
}
$certDefaultsByLang = [
    'tr' => [
        ['icon'=>'fa-award','t'=>'GlobalGAP','d'=>'Sürdürülebilir tarım uygulamaları'],
        ['icon'=>'fa-shield-halved','t'=>'ISO 22000','d'=>'Gıda güvenliği yönetimi'],
        ['icon'=>'fa-temperature-low','t'=>'HACCP','d'=>'Soğuk zincir ve hijyen'],
        ['icon'=>'fa-leaf','t'=>'BRC','d'=>'Gıda güvenliği global standardı'],
    ],
    'en' => [
        ['icon'=>'fa-award','t'=>'GlobalGAP','d'=>'Sustainable agriculture practices'],
        ['icon'=>'fa-shield-halved','t'=>'ISO 22000','d'=>'Food safety management'],
        ['icon'=>'fa-temperature-low','t'=>'HACCP','d'=>'Cold chain and hygiene'],
        ['icon'=>'fa-leaf','t'=>'BRC','d'=>'Global Standard for Food Safety'],
    ],
    'ru' => [
        ['icon'=>'fa-award','t'=>'GlobalGAP','d'=>'Устойчивые сельскохозяйственные практики'],
        ['icon'=>'fa-shield-halved','t'=>'ISO 22000','d'=>'Управление безопасностью пищевой продукции'],
        ['icon'=>'fa-temperature-low','t'=>'HACCP','d'=>'Холодовая цепь и гигиена'],
        ['icon'=>'fa-leaf','t'=>'BRC','d'=>'Глобальный стандарт пищевой безопасности'],
    ],
];
$defaultCerts = $LL['quality']['certs'] ?? ($certDefaultsByLang[$lang] ?? $certDefaultsByLang['en']);
$certs = [];
foreach ($defaultCerts as $i => $cert) {
    $n = $i + 1;
    $certs[] = [
        'icon' => $cert['icon'] ?? 'fa-award',
        't' => $siteText("quality_cert{$n}_t", $cert['t'] ?? ''),
        'd' => $siteText("quality_cert{$n}_d", $cert['d'] ?? ''),
    ];
}
?>
<style>
.page-hero{padding:5rem 0 3rem;background:linear-gradient(135deg,#1f1334 0%,#3b1c5e 60%,#7c3aed 100%);color:#fff;position:relative;overflow:hidden}
.page-hero::before{content:'';position:absolute;inset:0;background-image:radial-gradient(rgba(255,255,255,.06) 1px,transparent 1px);background-size:32px 32px}
.page-hero h1{color:#fff;max-width:760px}
.page-hero p{color:#e6dffa;max-width:760px;font-size:1.1rem}
.bcrumb{display:inline-flex;gap:.5rem;align-items:center;color:#cdb9ee;font-size:.9rem;margin-bottom:1rem}
.bcrumb a{color:#cdb9ee}.bcrumb a:hover{color:#fff}
.timeline{position:relative;padding:1rem 0;max-width:900px;margin:0 auto}
.timeline::before{content:'';position:absolute;left:25px;top:0;bottom:0;width:3px;background:linear-gradient(180deg,#7c3aed,#c026d3)}
.tl-step{position:relative;padding-left:75px;padding-bottom:2.5rem}
.tl-step:last-child{padding-bottom:0}
.tl-num{position:absolute;left:0;top:0;width:54px;height:54px;border-radius:50%;background:var(--grad);color:#fff;font-family:'Manrope';font-weight:800;font-size:1.2rem;display:flex;align-items:center;justify-content:center;box-shadow:0 14px 30px -10px rgba(124,58,237,.5);z-index:2}
.tl-content{background:#fff;border:1px solid rgba(124,58,237,.1);border-radius:18px;padding:1.6rem 1.8rem;box-shadow:0 10px 30px -16px rgba(31,19,52,.15)}
.tl-content h3{margin:0 0 .5rem}
.tl-content p{margin:0;color:var(--ink2)}
.cert-band{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-top:3rem}
.cert{padding:1.4rem;background:#fff;border:1px solid rgba(124,58,237,.1);border-radius:14px;text-align:center}
.cert i{color:var(--p1);font-size:1.6rem;margin-bottom:.5rem}
</style>

<section class="page-hero">
  <div class="container-xxl" style="position:relative;z-index:2">
    <span class="bcrumb"><a href="<?= I18n::url('', $locale) ?>"><?= htmlspecialchars(I18n::t('breadcrumb.home')) ?></a> / <span><?= htmlspecialchars(I18n::t('common.quality')) ?></span></span>
    <h1><?= htmlspecialchars($siteText('quality_h1', I18n::t('quality.h1'))) ?></h1>
    <p><?= htmlspecialchars($siteText('quality_lead', I18n::t('quality.lead'))) ?></p>
  </div>
</section>

<section class="block">
  <div class="container-xxl">
    <div class="timeline">
      <?php foreach ($steps as $i => $st): ?>
        <div class="tl-step fade-in">
          <div class="tl-num"><?= str_pad((string)($i+1), 2, '0', STR_PAD_LEFT) ?></div>
          <div class="tl-content">
            <h3><?= htmlspecialchars($st['t']) ?></h3>
            <p><?= htmlspecialchars($st['d']) ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="cert-band fade-in">
      <?php foreach ($certs as $cert): ?>
        <div class="cert">
          <i class="fas <?= htmlspecialchars($cert['icon']) ?>"></i>
          <h4><?= htmlspecialchars($cert['t']) ?></h4>
          <p style="margin:0;color:var(--ink2);font-size:.85rem"><?= htmlspecialchars($cert['d']) ?></p>
        </div>
      <?php endforeach; ?>
      <?php if (false): ?>
      <div class="cert"><i class="fas fa-award"></i><h4>GlobalGAP</h4><p style="margin:0;color:var(--ink2);font-size:.85rem">Sürdürülebilir tarım uygulamaları</p></div>
      <div class="cert"><i class="fas fa-shield-halved"></i><h4>ISO 22000</h4><p style="margin:0;color:var(--ink2);font-size:.85rem">Gıda güvenliği yönetimi</p></div>
      <div class="cert"><i class="fas fa-temperature-low"></i><h4>HACCP</h4><p style="margin:0;color:var(--ink2);font-size:.85rem">Soğuk zincir & hijyen</p></div>
      <div class="cert"><i class="fas fa-leaf"></i><h4>BRC</h4><p style="margin:0;color:var(--ink2);font-size:.85rem">Global Standart for Food Safety</p></div>
      <?php endif; ?>
    </div>
  </div>
</section>
