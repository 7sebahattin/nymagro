<?php
/**
 * Front Controller
 * --------------------------------------------------------
 * Tüm istekler (public/.htaccess üzerinden) buraya düşer.
 * Config yükler, Database ve Controller sınıflarını çağırır,
 * Router ile URL'yi doğru yere iletir.
 */

// 0) Oturum çerezi sertleştirme — session_start()'tan ÖNCE ayarlanmalıdır.
//    httponly : çerez JavaScript'e kapalı (XSS durumunda oturum çalınamaz)
//    samesite : Lax — çapraz siteden gelen POST'lara çerez gönderilmez (CSRF'e ek katman)
//    secure   : yalnızca HTTPS üzerinden gönderilir; HTTP'de açılırsa oturum hiç
//               kurulamayacağı için isteğin gerçekten HTTPS olup olmadığına bakılır
//               (ters vekil arkasında X-Forwarded-Proto).
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
    || (($_SERVER['SERVER_PORT'] ?? '') == 443);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// 0b) Güvenlik başlıkları. Uygulama kendi sayfalarını çerçevelemediği için
//     clickjacking'e kapatılır; tarayıcının içerik türü tahmin etmesi engellenir.
//     CSP bilinçli olarak eklenmedi: paneldeki görünümler satır içi script/style
//     kullanıyor, dar bir politika arayüzü bozar (bkz. denetim raporu B6).
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    if ($isHttps) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

// 1) Yapılandırma
require_once dirname(__DIR__) . '/app/config/config.php';

// 2) Core sınıfları
require_once CORE_PATH . '/Varlik.php';
require_once CORE_PATH . '/Database.php';
require_once CORE_PATH . '/TenantContext.php';
require_once CORE_PATH . '/AuthGuard.php';
require_once CORE_PATH . '/Rbac.php';
require_once CORE_PATH . '/Audit.php';
require_once CORE_PATH . '/Csrf.php';
require_once CORE_PATH . '/Controller.php';
require_once CORE_PATH . '/Router.php';

// 3) Yönlendir
(new Router())->dispatch();
