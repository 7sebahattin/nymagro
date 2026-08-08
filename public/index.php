<?php
/**
 * Front Controller
 * --------------------------------------------------------
 * Tüm istekler (public/.htaccess üzerinden) buraya düşer.
 * Config yükler, Database ve Controller sınıflarını çağırır,
 * Router ile URL'yi doğru yere iletir.
 */

// 0) Session (flash mesajlar için)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1) Yapılandırma
require_once dirname(__DIR__) . '/app/config/config.php';

// 2) Core sınıfları
require_once CORE_PATH . '/Varlik.php';
require_once CORE_PATH . '/Database.php';
require_once CORE_PATH . '/TenantContext.php';
require_once CORE_PATH . '/AuthGuard.php';
require_once CORE_PATH . '/Controller.php';
require_once CORE_PATH . '/Router.php';

// 3) Yönlendir
(new Router())->dispatch();
