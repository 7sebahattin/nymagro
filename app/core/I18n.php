<?php
/**
 * I18n
 * --------------------------------------------------------
 * Public website (TR / EN / RU) için basit, bağımlılıksız çeviri katmanı.
 *
 * Kullanım:
 *   I18n::set('en');              // mevcut dili belirle
 *   I18n::t('home.hero.title')   // çeviri string'i
 *   I18n::all()                  // mevcut dilin tam dizi (view'larda foreach için)
 *   I18n::current()              // 'tr' | 'en' | 'ru'
 *   I18n::url('about', 'en')     // /Muhasebe/en/about
 *
 * DÜZELTME: Her locale ayrı dizide saklanır. TR fallback diğer dillerin
 * çevirilerini ezmez.
 */
final class I18n
{
    public const SUPPORTED      = ['tr', 'en', 'ru'];
    public const DEFAULT_LOCALE = 'tr';

    private static string $current  = self::DEFAULT_LOCALE;
    /** @var array<string,array<string,mixed>> locale => messages */
    private static array  $messages = [];
    /** @var array<string,bool> */
    private static array  $loaded   = [];

    // ── Temel API ────────────────────────────────────────────

    public static function set(string $locale): void
    {
        $locale = strtolower(trim($locale));
        if (!in_array($locale, self::SUPPORTED, true)) {
            $locale = self::DEFAULT_LOCALE;
        }
        self::$current = $locale;
        self::loadLocale($locale);
    }

    public static function current(): string
    {
        return self::$current;
    }

    /**
     * Mevcut dilin tüm çeviri dizisini döner.
     * View'larda foreach / array erişimi için kullanın.
     *
     * @return array<string,mixed>
     */
    public static function all(): array
    {
        self::loadLocale(self::$current); // yüklü değilse yükle
        return self::$messages[self::$current] ?? [];
    }

    /**
     * Nokta-ayrımlı anahtar ile çeviri döner.
     *   I18n::t('home.hero.title')
     *   I18n::t('contact.form.name')
     *
     * Anahtar mevcut dilde bulunamazsa TR'ye döner;
     * hâlâ bulunamazsa anahtarın kendisini döner.
     */
    public static function t(string $key, array $params = []): string
    {
        self::loadLocale(self::$current);
        $msg = self::dig(self::$messages[self::$current] ?? [], $key);

        if ($msg === null && self::$current !== self::DEFAULT_LOCALE) {
            // TR fallback — sadece TR messages'a bakılır, mevcut locale'e dokunulmaz
            self::loadLocale(self::DEFAULT_LOCALE);
            $msg = self::dig(self::$messages[self::DEFAULT_LOCALE] ?? [], $key);
        }

        if ($msg === null) {
            return $key; // son çare: anahtarı göster
        }

        if (!empty($params)) {
            foreach ($params as $k => $v) {
                $msg = str_replace('%' . strtoupper((string)$k) . '%', (string)$v, $msg);
            }
        }

        return $msg;
    }

    // ── URL yardımcıları ─────────────────────────────────────

    /** /Muhasebe/{locale}/{path} formunda public site URL üretir */
    public static function url(string $path = '', ?string $locale = null): string
    {
        $locale = $locale ?: self::$current;
        if (!in_array($locale, self::SUPPORTED, true)) {
            $locale = self::DEFAULT_LOCALE;
        }
        $path = ltrim((string)$path, '/');
        $url  = BASE_URL . '/' . $locale;
        if ($path !== '') {
            $url .= '/' . $path;
        }
        return $url;
    }

    /** Dil seçimi için aynı sayfanın diğer dildeki URL'sini üretir (slug-aware) */
    public static function altUrl(string $page, string $targetLocale, ?string $slug = null): string
    {
        $map     = self::pageMap();
        $segment = $map[$targetLocale][$page] ?? '';
        $url     = BASE_URL . '/' . $targetLocale . ($segment !== '' ? '/' . $segment : '');
        if ($slug !== null && $slug !== '') {
            $url .= '/' . $slug;
        }
        return $url;
    }

    // ── Sayfa haritası ───────────────────────────────────────

    /**
     * Sayfa anahtarı -> URL segment haritası.
     * 'home' anahtarı kök sayfa (segment yok).
     */
    public static function pageMap(): array
    {
        return [
            'tr' => [
                'home'           => '',
                'about'          => 'hakkimizda',
                'products'       => 'urunler',
                'export_markets' => 'urun-gruplari',
                'export_region'  => 'urun-grubu',
                'quality'        => 'kalite-tescil',
                'services'       => 'hizmetler',
                'gallery'        => 'galeri',
                'contact'        => 'iletisim',
            ],
            'en' => [
                'home'           => '',
                'about'          => 'about',
                'products'       => 'products',
                'export_markets' => 'product-groups',
                'export_region'  => 'product-group',
                'quality'        => 'quality-registration',
                'services'       => 'services',
                'gallery'        => 'gallery',
                'contact'        => 'contact',
            ],
            'ru' => [
                'home'           => '',
                'about'          => 'about',
                'products'       => 'products',
                'export_markets' => 'product-groups',
                'export_region'  => 'product-group',
                'quality'        => 'quality-registration',
                'services'       => 'services',
                'gallery'        => 'gallery',
                'contact'        => 'contact',
            ],
        ];
    }

    /** URL segmentinden mantıksal sayfa anahtarına dönüştürme (router'da kullanılır) */
    public static function pageKeyFromSegment(string $locale, string $segment): ?string
    {
        $map     = self::pageMap()[$locale] ?? [];
        $segment = strtolower($segment);
        if ($segment === '') return 'home';
        foreach ($map as $key => $seg) {
            if ($seg === $segment) return $key;
        }
        return null;
    }

    // ── Dil meta ─────────────────────────────────────────────

    public static function languageName(string $locale): string
    {
        return [
            'tr' => 'Türkçe',
            'en' => 'English',
            'ru' => 'Русский',
        ][$locale] ?? $locale;
    }

    public static function languageFlag(string $locale): string
    {
        return [
            'tr' => '🇹🇷',
            'en' => '🇬🇧',
            'ru' => '🇷🇺',
        ][$locale] ?? '🌐';
    }

    public static function isRtl(): bool
    {
        return false; // tr/en/ru hepsi LTR
    }

    public static function htmlLang(): string
    {
        return self::$current;
    }

    // ── İç yardımcılar ───────────────────────────────────────

    private static function loadLocale(string $locale): void
    {
        if (!empty(self::$loaded[$locale])) return;

        $file = defined('APP_PATH')
            ? APP_PATH . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $locale . '.php'
            : __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $locale . '.php';

        if (is_file($file)) {
            $data = require $file;
            // Her locale kendi dizisinde saklanır — çakışma yok
            self::$messages[$locale] = is_array($data) ? $data : [];
            self::$loaded[$locale]   = true;
        }
    }

    /** Nokta-ayrımlı path ile dizi içinde string değer arar */
    private static function dig(array $arr, string $path): ?string
    {
        $parts = explode('.', $path);
        $cur   = $arr;
        foreach ($parts as $p) {
            if (!is_array($cur) || !array_key_exists($p, $cur)) return null;
            $cur = $cur[$p];
        }
        return is_string($cur) ? $cur : null;
    }
}
