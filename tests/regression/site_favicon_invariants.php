<?php
/**
 * Regresyon testi: Site Yönetimi → Genel & İletişim → Favicon alanı.
 * --------------------------------------------------------------------------
 * Çalıştırma:  php tests/regression/site_favicon_invariants.php
 * Çıkış kodu:  0 = tüm kontroller PASSED, 1 = en az bir FAILED.
 *
 * Kapsam (kaynak metin denetimi):
 *  - Ayar anahtarı `favicon_path` model varsayılanlarında tanımlı (boş = varsayılan simge).
 *  - Panel formu `favicon` dosya alanını ve faviconSil (POST) kaldırma uç noktasını içerir.
 *  - Controller yüklemeyi dosyaYukle() ile yapar (SVG yasağı/MIME/boyut doğrulaması
 *    aynı güvenli yoldan geçer) ve faviconSil yalnızca POST kabul eder.
 *  - Public layout özel favicon yoksa eski varsayılan <link> setine düşer.
 *  - WebsiteController favicon_path'i public URL'e normalleştirir.
 */

$kok = dirname(__DIR__, 2);
$fail = 0;
$check = static function (string $ad, bool $ok) use (&$fail): void {
    echo ($ok ? 'PASSED' : 'FAILED') . '  ' . $ad . PHP_EOL;
    if (!$ok) $fail++;
};
$oku = static fn(string $p): string => (string)file_get_contents($kok . '/' . $p);

$model = $oku('app/models/SiteIcerik.php');
$ctrl  = $oku('app/controllers/SiteController.php');
$view  = $oku('app/views/site/yonetim.php');
$lay   = $oku('app/views/web/layout.php');
$web   = $oku('app/controllers/WebsiteController.php');

$check('model: favicon_path varsayılan ayarı tanımlı',
    (bool)preg_match("~DEFAULT_SETTINGS.*?'favicon_path'\s*=>\s*''~s", $model));

$check('controller: favicon yüklemesi dosyaYukle() üzerinden',
    (bool)preg_match("~\\\$_FILES\['favicon'\].*?dosyaYukle\(\\\$_FILES\['favicon'\],\s*'favicon'\)~s", $ctrl));

$check('controller: faviconSil metodu var ve POST zorunlu',
    (bool)preg_match("~function faviconSil\(\).*?REQUEST_METHOD'\]\s*!==\s*'POST'.*?ayarKaydet\('favicon_path',\s*''\)~s", $ctrl));

$check('view: favicon dosya alanı (name="favicon") formda',
    str_contains($view, 'name="favicon"') && str_contains($view, 'id="faviconFile"'));

$check('view: kaldır butonu faviconSil\'e nymPost (POST) ile gider',
    str_contains($view, "/site/faviconSil'"));

$check('view: SVG kabul edilmiyor (accept listesinde svg yok)',
    (bool)preg_match('~name="favicon"[^>]*accept="([^"]*)"~', $view, $m) && !str_contains($m[1] ?? 'svg', 'svg'));

$check('layout: özel favicon varsa kullanılır, yoksa varsayılan set',
    str_contains($lay, "\$faviconUrl !== ''")
    && str_contains($lay, '/favicon.ico?v=20260501')
    && str_contains($lay, '/favicon-32x32.png?v=20260501'));

$check('WebsiteController: favicon_path public URL\'e normalleştirilir',
    (bool)preg_match("~\\\$imageKeys\s*=\s*\[[^\]]*'favicon_path'~", $web));

echo PHP_EOL . ($fail === 0 ? 'ALL PASSED' : "$fail FAILED") . PHP_EOL;
exit($fail === 0 ? 0 : 1);
