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
        ['icon'=>'fa-certificate','t'=>'Bakanlık Tescili','d'=>'T.C. Tarım ve Orman Bakanlığı tescil belgesi'],
        ['icon'=>'fa-flask','t'=>'EC Fertilizer','d'=>'AB gübre mevzuatı kriterlerine uygun üretim'],
        ['icon'=>'fa-file-shield','t'=>'İthalat Lisansı','d'=>'Lisanslı ithalatçı — Lisans No: 6002'],
        ['icon'=>'fa-atom','t'=>'EDTA Şelat','d'=>'2–10 pH aralığında kararlı, çökelme yapmaz'],
    ],
    'en' => [
        ['icon'=>'fa-certificate','t'=>'Ministry Registration','d'=>'Registration certificate from the Turkish Ministry of Agriculture and Forestry'],
        ['icon'=>'fa-flask','t'=>'EC Fertilizer','d'=>'Manufactured to EU fertilizer regulation criteria'],
        ['icon'=>'fa-file-shield','t'=>'Import Licence','d'=>'Licensed importer — licence no. 6002'],
        ['icon'=>'fa-atom','t'=>'EDTA Chelate','d'=>'Stable between pH 2–10, no precipitation'],
    ],
    'ru' => [
        ['icon'=>'fa-certificate','t'=>'Регистрация в министерстве','d'=>'Свидетельство Министерства сельского и лесного хозяйства Турции'],
        ['icon'=>'fa-flask','t'=>'EC Fertilizer','d'=>'Производство по критериям регламента ЕС об удобрениях'],
        ['icon'=>'fa-file-shield','t'=>'Импортная лицензия','d'=>'Лицензированный импортёр — лицензия № 6002'],
        ['icon'=>'fa-atom','t'=>'Хелат ЭДТА','d'=>'Стабилен в диапазоне pH 2–10, без осадка'],
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
.page-hero{padding:5rem 0 3rem;background:linear-gradient(135deg,#06281a 0%,#073820 60%,#0d623a 100%);color:#fff;position:relative;overflow:hidden}
.page-hero::before{content:'';position:absolute;inset:0;background-image:radial-gradient(rgba(255,255,255,.06) 1px,transparent 1px);background-size:32px 32px}
.page-hero h1{color:#fff;max-width:760px}
.page-hero p{color:#dcebdb;max-width:760px;font-size:1.1rem}
.bcrumb{display:inline-flex;gap:.5rem;align-items:center;color:#a8cfb8;font-size:.9rem;margin-bottom:1rem}
.bcrumb a{color:#a8cfb8}.bcrumb a:hover{color:#fff}
.timeline{position:relative;padding:1rem 0;max-width:900px;margin:0 auto}
.timeline::before{content:'';position:absolute;left:25px;top:0;bottom:0;width:3px;background:linear-gradient(180deg,#0d623a,#d97a0c)}
.tl-step{position:relative;padding-left:75px;padding-bottom:2.5rem}
.tl-step:last-child{padding-bottom:0}
.tl-num{position:absolute;left:0;top:0;width:54px;height:54px;border-radius:50%;background:var(--grad);color:#fff;font-family:'Manrope';font-weight:800;font-size:1.2rem;display:flex;align-items:center;justify-content:center;box-shadow:0 14px 30px -10px rgba(13,98,58,.5);z-index:2}
.tl-content{background:#fff;border:1px solid rgba(13,98,58,.1);border-radius:18px;padding:1.6rem 1.8rem;box-shadow:0 10px 30px -16px rgba(6,40,26,.15)}
.tl-content h3{margin:0 0 .5rem}
.tl-content p{margin:0;color:var(--ink2)}
.cert-band{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-top:3rem}
.cert{padding:1.4rem;background:#fff;border:1px solid rgba(13,98,58,.1);border-radius:14px;text-align:center}
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
    </div>
  </div>
</section>
