<?php
/**
 * Ornek yapilandirma dosyasi.
 *
 * Kurulumda bu dosyayi `config.php` olarak kopyalayin ve kendi
 * sunucu bilgilerinizi girin. `config.php` surum kontrolune dahil
 * DEGILDIR (.gitignore) — veritabani bilgileri depoda tutulmaz.
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'veritabani_adi');
define('DB_USER', 'veritabani_kullanicisi');
define('DB_PASS', 'veritabani_sifresi');
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME', 'Nymagro');

// Canliya alirken 'production' yapin — hata mesajlari gizlenir.
define('APP_ENV', 'development');

if (!defined('BASE_URL')) {
    $autoBaseUrl = getenv('APP_BASE_URL');
    if (!$autoBaseUrl) {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
            || (($_SERVER['SERVER_PORT'] ?? '') == 443);

        $scheme = $isHttps ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $base   = rtrim(str_replace('\\', '/', dirname($script)), '/');

        if (str_ends_with(strtolower($base), '/public')) {
            $base = substr($base, 0, -7);
        }

        if ($base === '.' || $base === '/') {
            $base = '';
        }

        $autoBaseUrl = $scheme . '://' . $host . $base;
    }

    define('BASE_URL', rtrim($autoBaseUrl, '/'));
}

define('ROOT_PATH', dirname(__DIR__, 2));
define('APP_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'app');
define('CONTROLLERS_PATH', APP_PATH . DIRECTORY_SEPARATOR . 'controllers');
define('MODELS_PATH', APP_PATH . DIRECTORY_SEPARATOR . 'models');
define('VIEWS_PATH', APP_PATH . DIRECTORY_SEPARATOR . 'views');
define('CORE_PATH', APP_PATH . DIRECTORY_SEPARATOR . 'core');

define('DEFAULT_CONTROLLER', 'HomeController');
define('DEFAULT_METHOD', 'index');

if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

date_default_timezone_set('Europe/Istanbul');
