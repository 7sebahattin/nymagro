<?php
/**
 * Csrf
 * --------------------------------------------------------
 * Senkronizör token deseni: oturum başına TEK, uzun ömürlü bir token.
 * Router bunu POST/PUT/PATCH/DELETE isteklerinde ve "mutasyon" (VIEW
 * olmayan) GET işlemlerinde (ör. eski `/modul/sil/{id}` linkleri) zorunlu
 * kılar (bkz. Router::dispatch() + Rbac::requiredPermissionFor()).
 *
 * Token nereden gelir:
 *  - POST: $_POST['csrf_token']  veya  X-CSRF-Token header (fetch/AJAX)
 *  - GET (mutasyon): $_GET['csrf']
 *
 * Sunucu tarafı zorunlu kılma tek başına yeterlidir; public/js/panel-ui.js
 * içindeki JS ise TÜM mevcut form/fetch/link'lere token'ı OTOMATİK ekler —
 * böylece ~20 mevcut modülün view dosyalarının HİÇBİRİ değiştirilmeden
 * korumaya dahil olur (bkz. panel-ui.js üstündeki yorum).
 */
final class Csrf
{
    private const SESSION_KEY = 'csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY]) || !is_string($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::SESSION_KEY];
    }

    /** Login/logout gibi kimlik durumu değişen anlarda tazelenir. */
    public static function rotate(): string
    {
        $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        return $_SESSION[self::SESSION_KEY];
    }

    public static function fieldHtml(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    private static function submittedToken(): ?string
    {
        if (!empty($_POST['csrf_token']) && is_string($_POST['csrf_token'])) {
            return $_POST['csrf_token'];
        }
        $header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (is_string($header) && $header !== '') {
            return $header;
        }
        if (!empty($_GET['csrf']) && is_string($_GET['csrf'])) {
            return $_GET['csrf'];
        }
        return null;
    }

    public static function isValid(): bool
    {
        $submitted = self::submittedToken();
        if ($submitted === null || $submitted === '') {
            return false;
        }
        return hash_equals(self::token(), $submitted);
    }

    /**
     * GET ile tetiklenen, FORM/SAYFA GÖSTERMEYEN, gerçekten veri değiştiren
     * bilinen uç noktalar (mevcut kod tabanında doğrulanmış — bkz. grep
     * denetimi). DİKKAT: Rbac::requiredPermissionFor()'un CREATE/UPDATE
     * sınıflandırması burada KULLANILMAZ — çünkü o sınıflandırmada "ekle"
     * (form göster, GET) ile "iptal" (gerçek mutasyon, GET) ikisi de aynı
     * kovaya (CREATE/UPDATE) düşüyor. CSRF'in GET'te de gerekmesi için
     * yöntemin GERÇEKTEN yazma yaptığından emin olmamız lazım — bu yüzden
     * açık bir liste kullanılıyor (yanlışlıkla bir form sayfasını CSRF
     * hatasıyla kilitlememek için).
     */
    /**
     * Faz 1.5 kapsamında bu listedeki TÜM uç noktalar POST-only'e çevrildi
     * (artık GET ile hiçbiri mutasyon yapmıyor) — bu yüzden liste boş.
     * Yeni bir GET-mutasyonu kesinlikle EKLENMEMELİ; bunun yerine ilgili
     * controller metodu POST-only yapılmalı (bkz. nymPost() deseni).
     */
    private const EXPLICIT_GET_MUTATIONS = [];

    /**
     * Bu isteğin CSRF doğrulaması gerektirip gerektirmediğini belirler.
     * - Tüm POST/PUT/PATCH/DELETE istekleri (istisna: herkese açık public site).
     * - GET istekleri: SADECE "sil" ile biten yöntemler (bu kod tabanında hep
     *   silme tetikleyicisidir, asla form göstermez) + yukarıdaki açık liste
     *   + logout.
     */
    public static function isRequired(string $controllerName, string $methodName): bool
    {
        // Herkese açık, kimlik durumu taşımayan public site — kapsam dışı.
        if (in_array($controllerName, ['WebsiteController', 'SitemapController'], true)) {
            return false;
        }

        $httpMethod = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if (in_array($httpMethod, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return true;
        }

        // GET: sadece bilinen mutasyon noktaları.
        if ($controllerName === 'AuthController' && $methodName === 'logout') {
            return true;
        }
        if (str_ends_with(strtolower($methodName), 'sil')) {
            return true;
        }
        return in_array($controllerName . '::' . $methodName, self::EXPLICIT_GET_MUTATIONS, true);
    }

    public static function verifyOrDeny(string $controllerName, string $methodName): void
    {
        if (!self::isRequired($controllerName, $methodName)) {
            return;
        }
        if (self::isValid()) {
            return;
        }

        if (class_exists('Audit') && class_exists('AuthGuard') && AuthGuard::isLoggedIn()) {
            Audit::log('UNAUTHORIZED_ACCESS_ATTEMPT', null, null, null, null,
                "{$controllerName}::{$methodName} → geçersiz/eksik CSRF token.", false);
        }

        self::renderRejected();
    }

    private static function renderRejected(): void
    {
        http_response_code(419); // yaygın "Page Expired / CSRF" konvansiyonu
        echo '<!doctype html><html lang="tr"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>Oturum Güvenlik Anahtarı Geçersiz</title>'
            . '<style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#f5f6f8;'
            . 'display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}'
            . '.box{background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,.08);padding:40px;'
            . 'max-width:440px;text-align:center}h1{font-size:20px;color:#c0392b;margin:0 0 12px}'
            . 'p{color:#444;line-height:1.5}a{display:inline-block;margin-top:16px;color:#0D623A;'
            . 'text-decoration:none;font-weight:600}</style></head><body>'
            . '<div class="box"><h1>Oturum Güvenlik Anahtarı Geçersiz</h1>'
            . '<p>Sayfa uzun süre açık kaldı ya da güvenlik anahtarınız süresi doldu. '
            . 'Lütfen sayfayı yenileyip işlemi tekrar deneyin.</p>'
            . '<a href="javascript:history.back()">Geri dön</a></div>'
            . '</body></html>';
        exit;
    }
}
