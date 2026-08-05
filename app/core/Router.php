<?php
/**
 * Router
 * --------------------------------------------------------
 * URL'yi parse edip doğru Controller::method'u çalıştırır.
 *
 * Public website (TR/EN/RU) rotaları:
 *   /tr                      → WebsiteController::home('tr')
 *   /en/about                → WebsiteController::about('en')
 *   /tr/urunler/kiraz        → WebsiteController::productDetail('tr','kiraz')
 *   /sitemap.xml             → SitemapController::index()
 *   /robots.txt              → SitemapController::robots()
 *
 * Panel rotaları (mevcut yapı korunur):
 *   /dashboard, /satis, /raporlar  vb.
 */

final class Router
{
    private array $routes = [
        'musteri'    => 'MusteriController',
        'tedarikci'  => 'TedarikciController',
        'personel'   => 'PersonelController',
        'urun'       => 'UrunController',
        'satis'      => 'SatisController',
        'alis'       => 'AlisController',
        'nakit'      => 'NakitController',
        'hesap'      => 'HesapController',
        'ekstre'     => 'EkstreController',
        'masraf'     => 'MasrafController',
        'dokuman'    => 'DokumanController',
        'rapor'      => 'RaporController',
        'raporlar'   => 'RaporController',
        'dashboard'  => 'DashboardController',
        'companies'  => 'CompanyController',
        'periods'    => 'PeriodController',
        'home'       => 'HomeController',
        'auth'       => 'AuthController',
        'giris'      => 'AuthController',
        'cikis'      => 'AuthController',
        'site'       => 'SiteController',
        'profil'     => 'ProfilController',
        'takvim'     => 'TakvimController',
        'cek'        => 'PortfoyController',
        'senet'      => 'PortfoyController',
    ];

    private array $segmentMethodMap = [
        'giris' => 'login',
        'cikis' => 'logout',
    ];

    public function dispatch(): void
    {
        $url = $this->parseUrl();

        // 1) Auth ve tenant şemasını ayağa kaldır
        AuthGuard::bootstrap();
        TenantContext::bootstrap();

        // 2) Özel SEO dosyaları
        if (!empty($url[0])) {
            $first = strtolower($url[0]);
            if ($first === 'sitemap.xml' || $first === 'sitemap-index.xml') {
                $this->dispatchPublic('SitemapController', 'index', []);
                return;
            }
            if ($first === 'robots.txt') {
                $this->dispatchPublic('SitemapController', 'robots', []);
                return;
            }
        }

        // 3) Çok dilli public website (/tr, /en, /ru)
        require_once CORE_PATH . '/I18n.php';
        if (!empty($url[0]) && in_array(strtolower($url[0]), I18n::SUPPORTED, true)) {
            $this->dispatchWebsite($url);
            return;
        }

        // 4) Kök URL → varsayılan dile yönlendir (public site)
        if (empty($url[0])) {
            $preferred = $this->detectPreferredLocale();
            header('Location: ' . BASE_URL . '/' . $preferred, true, 302);
            exit;
        }

        // 5) Login zorunluluğu (panel için)
        AuthGuard::requireLogin($url);

        // 6) Sadece login olmuş kullanıcılar için aktif şirket/dönem zorunlu
        if (AuthGuard::isLoggedIn()) {
            TenantContext::requireActiveForRoute($url);
        }

        // ─── Panel Controller ──────────────────────────────
        $segment = strtolower($url[0] ?? '');
        $controllerName = !empty($segment)
            ? ($this->routes[$segment] ?? ucfirst($segment) . 'Controller')
            : DEFAULT_CONTROLLER;

        $controllerFile = CONTROLLERS_PATH . DIRECTORY_SEPARATOR . $controllerName . '.php';

        if (!file_exists($controllerFile)) {
            http_response_code(404);
            die("404 — Controller bulunamadı: {$controllerName}");
        }

        require_once $controllerFile;
        $controller = new $controllerName();
        $firstSegment = $segment;
        array_shift($url);

        // ─── Metot ─────────────────────────────────────────
        $numericResourceId = null;
        if (!empty($url[0]) && ctype_digit($url[0]) && !empty($url[1])) {
            $numericResourceId = array_shift($url);
            $method = str_replace('-', '_', array_shift($url));
        } elseif (!empty($url[0])) {
            $method = str_replace('-', '_', $url[0]);
        } elseif (isset($this->segmentMethodMap[$firstSegment])) {
            $method = $this->segmentMethodMap[$firstSegment];
        } else {
            $method = DEFAULT_METHOD;
        }
        if ($method !== '' && ctype_digit($method[0])) {
            $method = '_' . $method;
        }

        if (!method_exists($controller, $method)) {
            http_response_code(404);
            die("404 — Metot bulunamadı: {$controllerName}::{$method}");
        }
        if ($numericResourceId !== null) {
            array_unshift($url, $numericResourceId);
        } else {
            array_shift($url);
        }

        $params = $url ? array_values($url) : [];
        call_user_func_array([$controller, $method], $params);
    }

    /** Public website yönlendirici (TR / EN / RU) */
    private function dispatchWebsite(array $url): void
    {
        $locale = strtolower(array_shift($url));
        I18n::set($locale);

        $segment = strtolower($url[0] ?? '');
        $pageKey = I18n::pageKeyFromSegment($locale, $segment);

        $actionMap = [
            'home'           => 'home',
            'about'          => 'about',
            'products'       => 'products',
            'export_markets' => 'exportMarkets',
            'export_region'  => 'exportRegion',
            'quality'        => 'quality',
            'services'       => 'services',
            'gallery'        => 'gallery',
            'contact'        => 'contact',
        ];

        $controllerFile = CONTROLLERS_PATH . DIRECTORY_SEPARATOR . 'WebsiteController.php';
        if (!file_exists($controllerFile)) {
            http_response_code(500);
            die('WebsiteController bulunamadı.');
        }
        require_once $controllerFile;
        $controller = new WebsiteController($locale);

        // İletişim formu submit (POST)
        if ($pageKey === 'contact' && !empty($url[1]) && in_array(strtolower($url[1]), ['gonder','submit','send'], true)) {
            $controller->contactSubmit();
            return;
        }

        if ($pageKey === null) {
            $controller->notFound();
            return;
        }

        $action = $actionMap[$pageKey] ?? null;
        if ($action === null || !method_exists($controller, $action)) {
            $controller->notFound();
            return;
        }

        // Slug'lı sayfalar
        if ($pageKey === 'products' && !empty($url[1])) {
            $controller->productDetail((string)$url[1]);
            return;
        }
        if ($pageKey === 'export_region') {
            $slug = (string)($url[1] ?? '');
            if ($slug === '') {
                $controller->exportMarkets();
                return;
            }
            $controller->exportRegion($slug);
            return;
        }

        $controller->{$action}();
    }

    private function dispatchPublic(string $controllerName, string $method, array $params): void
    {
        require_once CORE_PATH . '/I18n.php';
        $file = CONTROLLERS_PATH . DIRECTORY_SEPARATOR . $controllerName . '.php';
        if (!file_exists($file)) {
            http_response_code(404);
            die('404');
        }
        require_once $file;
        $c = new $controllerName();
        if (!method_exists($c, $method)) {
            http_response_code(404);
            die('404');
        }
        call_user_func_array([$c, $method], $params);
    }

    /** Tarayıcı dil tercihinden TR/EN/RU çözümle (varsayılan TR) */
    private function detectPreferredLocale(): string
    {
        $accept = strtolower((string)($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));
        if (str_starts_with($accept, 'tr')) return 'tr';
        if (str_starts_with($accept, 'ru')) return 'ru';
        if (str_starts_with($accept, 'en')) return 'en';
        // Genel arama: ilk eşleşme
        foreach (I18n::SUPPORTED as $loc) {
            if (str_contains($accept, $loc)) return $loc;
        }
        return I18n::DEFAULT_LOCALE;
    }

    private function parseUrl(): array
    {
        if (!isset($_GET['url'])) {
            return [];
        }
        $url = rtrim($_GET['url'], '/');
        $url = filter_var($url, FILTER_SANITIZE_URL);
        return explode('/', $url);
    }
}
