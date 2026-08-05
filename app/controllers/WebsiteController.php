<?php
/**
 * WebsiteController
 * --------------------------------------------------------
 * Nymagro public web sitesi (TR / EN / RU) için tüm sayfaları yönetir.
 *
 * URL örnekleri:
 *   /tr                       → home()
 *   /en/about                 → about()
 *   /tr/urunler/silatrix      → productDetail('silatrix')
 *   /en/products/silatrix     → productDetail('silatrix')
 *   /tr/urun-gruplari         → exportMarkets()
 *   /en/product-group/foliar-fertilizers → exportRegion('foliar-fertilizers')
 *   /tr/iletisim              → contact()
 *   /tr/iletisim/gonder (POST)→ contactSubmit()
 */
require_once CORE_PATH    . '/I18n.php';
require_once CORE_PATH    . '/SeoMeta.php';
require_once MODELS_PATH  . '/SiteIcerik.php';
require_once MODELS_PATH  . '/ContactMessage.php';

class WebsiteController extends Controller
{
    private string $locale;
    private ?SiteIcerik $site = null;
    private array $ayarlar;

    /** Ürün grubu tanımları (slug → key) */
    private const REGION_SLUGS = [
        'tr' => [
            'mikro-element-gubreleri' => 'micro',
            'yaprak-gubreleri'        => 'foliar',
            'damlama-gubreleri'       => 'fertigation',
            'makro-besin-urunleri'    => 'macro',
            'biyostimulantlar'        => 'biostimulant',
        ],
        'en' => [
            'micronutrient-fertilizers' => 'micro',
            'foliar-fertilizers'        => 'foliar',
            'fertigation-products'      => 'fertigation',
            'macronutrient-products'    => 'macro',
            'biostimulants'             => 'biostimulant',
        ],
        'ru' => [
            'micronutrient-fertilizers' => 'micro',
            'foliar-fertilizers'        => 'foliar',
            'fertigation-products'      => 'fertigation',
            'macronutrient-products'    => 'macro',
            'biostimulants'             => 'biostimulant',
        ],
    ];

    public function __construct(string $locale = 'tr')
    {
        $this->locale = in_array($locale, I18n::SUPPORTED, true) ? $locale : I18n::DEFAULT_LOCALE;
        I18n::set($this->locale);

        try {
            $this->site = new SiteIcerik();
            $this->ayarlar = $this->normalizeSettings($this->site->tumAyarlar());
        } catch (Throwable $e) {
            $this->ayarlar = $this->normalizeSettings(SiteIcerik::DEFAULT_SETTINGS);
        }
    }

    // ───────────────────────── Sayfalar ─────────────────────────

    public function home(): void
    {
        $urunler = $this->safeProducts();
        $galeri  = $this->safeGallery();

        $seo = $this->baseSeo('home');
        $seo->setTitle(I18n::t('meta.home_title'))
            ->setDescription(I18n::t('meta.home_description'))
            ->setCanonical($this->canonical('home'))
            ->setHreflang($this->hreflangFor('home'));

        $this->addCommonSchemas($seo, 'home');
        $seo->addJsonLd(SeoMeta::itemListSchema(array_map(function($u) {
            return [
                'name' => $this->site->urunAd($u, $this->locale),
                'url'  => I18n::altUrl('products', $this->locale, $this->site->urunSlug($u, $this->locale)),
            ];
        }, array_slice($urunler, 0, 12))));

        $this->renderPage('home', [
            'seo'     => $seo,
            'ayarlar' => $this->ayarlar,
            'urunler' => $urunler,
            'galeri'  => $galeri,
            'currentPage' => 'home',
        ]);
    }

    public function about(): void
    {
        $seo = $this->baseSeo('about');
        $seo->setTitle(I18n::t('meta.about_title'))
            ->setDescription(I18n::t('meta.about_description'))
            ->setCanonical($this->canonical('about'))
            ->setHreflang($this->hreflangFor('about'));

        $this->addCommonSchemas($seo, 'about');
        $seo->addJsonLd(SeoMeta::breadcrumbSchema([
            ['name' => I18n::t('breadcrumb.home'), 'url' => I18n::url('', $this->locale)],
            ['name' => I18n::t('common.about'),     'url' => $this->canonical('about')],
        ], BASE_URL));

        $this->renderPage('about', [
            'seo'     => $seo,
            'ayarlar' => $this->ayarlar,
            'currentPage' => 'about',
        ]);
    }

    public function products(): void
    {
        $urunler = $this->safeProducts();

        $seo = $this->baseSeo('products');
        $seo->setTitle(I18n::t('meta.products_title'))
            ->setDescription(I18n::t('meta.products_description'))
            ->setCanonical($this->canonical('products'))
            ->setHreflang($this->hreflangFor('products'));

        $this->addCommonSchemas($seo, 'products');
        $seo->addJsonLd(SeoMeta::breadcrumbSchema([
            ['name' => I18n::t('breadcrumb.home'), 'url' => I18n::url('', $this->locale)],
            ['name' => I18n::t('common.products'), 'url' => $this->canonical('products')],
        ], BASE_URL));
        $seo->addJsonLd(SeoMeta::itemListSchema(array_map(function($u) {
            return [
                'name' => $this->site->urunAd($u, $this->locale),
                'url'  => I18n::altUrl('products', $this->locale, $this->site->urunSlug($u, $this->locale)),
            ];
        }, $urunler)));

        $this->renderPage('products', [
            'seo'     => $seo,
            'ayarlar' => $this->ayarlar,
            'urunler' => $urunler,
            'currentPage' => 'products',
        ]);
    }

    public function productDetail(string $slug): void
    {
        if (!$this->site) {
            $this->notFound();
            return;
        }

        $slug    = self::sanitizeSlug($slug);
        $urun    = $this->site->urunBySlug($slug, $this->locale);
        if (!$urun) {
            // Diğer dillerden de kontrol et (cross-locale fallback)
            foreach (['tr','en','ru'] as $loc) {
                $u = $this->site->urunBySlug($slug, $loc);
                if ($u) { $urun = $u; break; }
            }
        }
        if (!$urun) {
            $this->notFound();
            return;
        }

        $ad      = $this->site->urunAd($urun, $this->locale);
        $aciklama= $this->site->urunAciklama($urun, $this->locale);
        $urun    = $this->normalizeProduct($urun);
        $img     = (string)$urun['gorsel'];
        $current = $this->site->urunSlug($urun, $this->locale);

        // hreflang slug-aware
        $hreflang = [];
        foreach (['tr','en','ru'] as $loc) {
            $hreflang[$loc] = I18n::altUrl('products', $loc, $this->site->urunSlug($urun, $loc));
        }

        $title = ($this->locale === 'en'
                    ? "{$ad} | Plant Nutrition Product — Nymagro"
                    : ($this->locale === 'ru'
                        ? "{$ad} | Продукт питания растений — Nymagro"
                        : "{$ad} | Bitki Besleme Ürünü — Nymagro"));

        $seo = $this->baseSeo('products');
        $seo->setTitle($title)
            ->setDescription(mb_substr($aciklama, 0, 160) ?: I18n::t('meta.products_description'))
            ->setCanonical(I18n::altUrl('products', $this->locale, $current))
            ->setHreflang($hreflang)
            ->setOgImage($img ?: $seo->getTitle())
            ->setOgType('article');

        $this->addCommonSchemas($seo, 'product');

        $seo->addJsonLd(SeoMeta::productSchema([
            'name'        => $ad,
            'description' => $aciklama,
            'image'       => $img,
            'category'    => 'Plant nutrition',
            'brand'       => 'Nymagro',
        ]));

        $seo->addJsonLd(SeoMeta::breadcrumbSchema([
            ['name' => I18n::t('breadcrumb.home'),  'url' => I18n::url('', $this->locale)],
            ['name' => I18n::t('common.products'),  'url' => $this->canonical('products')],
            ['name' => $ad,                         'url' => I18n::altUrl('products', $this->locale, $current)],
        ], BASE_URL));

        // FAQ schema
        $faqs = $this->productFaqs($ad);
        $seo->addJsonLd(SeoMeta::faqSchema($faqs));

        // İlgili ürünler
        $ilgili = array_filter($this->safeProducts(), function($u) use ($urun) {
            return (int)$u['id'] !== (int)$urun['id'];
        });
        $ilgili = array_slice($ilgili, 0, 4);

        $this->renderPage('product-detail', [
            'seo'     => $seo,
            'ayarlar' => $this->ayarlar,
            'urun'    => $urun,
            'ad'      => $ad,
            'aciklama'=> $aciklama,
            'img'     => $img,
            'faqs'    => $faqs,
            'ilgili'  => $ilgili,
            'currentPage' => 'products',
        ]);
    }

    public function exportMarkets(): void
    {
        $seo = $this->baseSeo('export_markets');
        $seo->setTitle(I18n::t('meta.export_markets_title'))
            ->setDescription(I18n::t('meta.export_markets_description'))
            ->setCanonical($this->canonical('export_markets'))
            ->setHreflang($this->hreflangFor('export_markets'));

        $this->addCommonSchemas($seo, 'markets');
        $seo->addJsonLd(SeoMeta::breadcrumbSchema([
            ['name' => I18n::t('breadcrumb.home'), 'url' => I18n::url('', $this->locale)],
            ['name' => I18n::t('common.markets'),  'url' => $this->canonical('export_markets')],
        ], BASE_URL));

        // Bölge listesi (locale slug ile)
        $regions = [];
        $localeMap = self::REGION_SLUGS[$this->locale] ?? self::REGION_SLUGS['en'];
        foreach ($localeMap as $slug => $key) {
            $info = I18n::t("markets.list.{$key}.name");
            $desc = I18n::t("markets.list.{$key}.desc");
            $regions[] = [
                'slug' => $slug,
                'key'  => $key,
                'name' => $info,
                'desc' => $desc,
                'url'  => I18n::altUrl('export_region', $this->locale, $slug),
            ];
        }

        $seo->addJsonLd(SeoMeta::itemListSchema(array_map(function($r) {
            return ['name' => $r['name'], 'url' => $r['url']];
        }, $regions)));

        $this->renderPage('export-markets', [
            'seo'     => $seo,
            'ayarlar' => $this->ayarlar,
            'regions' => $regions,
            'currentPage' => 'markets',
        ]);
    }

    public function exportRegion(string $slug): void
    {
        $slug      = self::sanitizeSlug($slug);
        $localeMap = self::REGION_SLUGS[$this->locale] ?? self::REGION_SLUGS['en'];

        // Slug → key, eğer mevcut dilde bulunamazsa diğer dilleri dene
        $key = $localeMap[$slug] ?? null;
        if (!$key) {
            foreach (self::REGION_SLUGS as $loc => $map) {
                if (isset($map[$slug])) { $key = $map[$slug]; break; }
            }
        }
        if (!$key) {
            $this->notFound();
            return;
        }

        $regionName = I18n::t("markets.list.{$key}.name");
        $regionDesc = I18n::t("markets.list.{$key}.desc");

        // hreflang slug-aware
        $hreflang = [];
        foreach (['tr','en','ru'] as $loc) {
            $localeSlug = array_search($key, self::REGION_SLUGS[$loc] ?? [], true);
            if ($localeSlug !== false) {
                $hreflang[$loc] = I18n::altUrl('export_region', $loc, $localeSlug);
            }
        }

        $titleTpl = [
            'tr' => "{$regionName} | Nymagro Bitki Besleme Ürünleri",
            'en' => "{$regionName} | Nymagro Plant Nutrition Products",
            'ru' => "{$regionName} | Продукты питания растений Nymagro",
        ];

        $seo = $this->baseSeo('export_region');
        $seo->setTitle($titleTpl[$this->locale] ?? $titleTpl['en'])
            ->setDescription(mb_substr($regionDesc, 0, 160))
            ->setCanonical(I18n::altUrl('export_region', $this->locale, $slug))
            ->setHreflang($hreflang);

        $this->addCommonSchemas($seo, 'region');
        $seo->addJsonLd(SeoMeta::breadcrumbSchema([
            ['name' => I18n::t('breadcrumb.home'),    'url' => I18n::url('', $this->locale)],
            ['name' => I18n::t('common.markets'),     'url' => $this->canonical('export_markets')],
            ['name' => $regionName,                   'url' => I18n::altUrl('export_region', $this->locale, $slug)],
        ], BASE_URL));

        $this->renderPage('export-region', [
            'seo'        => $seo,
            'ayarlar'    => $this->ayarlar,
            'regionName' => $regionName,
            'regionDesc' => $regionDesc,
            'regionKey'  => $key,
            'urunler'    => array_slice($this->safeProducts(), 0, 8),
            'currentPage' => 'markets',
        ]);
    }

    public function quality(): void
    {
        $seo = $this->baseSeo('quality');
        $seo->setTitle(I18n::t('meta.quality_title'))
            ->setDescription(I18n::t('meta.quality_description'))
            ->setCanonical($this->canonical('quality'))
            ->setHreflang($this->hreflangFor('quality'));

        $this->addCommonSchemas($seo, 'quality');
        $seo->addJsonLd(SeoMeta::breadcrumbSchema([
            ['name' => I18n::t('breadcrumb.home'),  'url' => I18n::url('', $this->locale)],
            ['name' => I18n::t('common.quality'),   'url' => $this->canonical('quality')],
        ], BASE_URL));

        $this->renderPage('quality', [
            'seo'     => $seo,
            'ayarlar' => $this->ayarlar,
            'currentPage' => 'quality',
        ]);
    }

    public function services(): void
    {
        $seo = $this->baseSeo('services');
        $seo->setTitle(I18n::t('meta.services_title'))
            ->setDescription(I18n::t('meta.services_description'))
            ->setCanonical($this->canonical('services'))
            ->setHreflang($this->hreflangFor('services'));

        $this->addCommonSchemas($seo, 'services');
        $seo->addJsonLd(SeoMeta::breadcrumbSchema([
            ['name' => I18n::t('breadcrumb.home'),  'url' => I18n::url('', $this->locale)],
            ['name' => I18n::t('common.services'),  'url' => $this->canonical('services')],
        ], BASE_URL));

        $this->renderPage('services', [
            'seo'     => $seo,
            'ayarlar' => $this->ayarlar,
            'currentPage' => 'services',
        ]);
    }

    public function gallery(): void
    {
        $galeri = $this->safeGallery();

        $seo = $this->baseSeo('gallery');
        $seo->setTitle(I18n::t('meta.gallery_title'))
            ->setDescription(I18n::t('meta.gallery_description'))
            ->setCanonical($this->canonical('gallery'))
            ->setHreflang($this->hreflangFor('gallery'));

        $this->addCommonSchemas($seo, 'gallery');
        $seo->addJsonLd(SeoMeta::breadcrumbSchema([
            ['name' => I18n::t('breadcrumb.home'),  'url' => I18n::url('', $this->locale)],
            ['name' => I18n::t('common.gallery'),   'url' => $this->canonical('gallery')],
        ], BASE_URL));

        $this->renderPage('gallery', [
            'seo'     => $seo,
            'ayarlar' => $this->ayarlar,
            'galeri'  => $galeri,
            'currentPage' => 'gallery',
        ]);
    }

    public function contact(): void
    {
        $seo = $this->baseSeo('contact');
        $seo->setTitle(I18n::t('meta.contact_title'))
            ->setDescription(I18n::t('meta.contact_description'))
            ->setCanonical($this->canonical('contact'))
            ->setHreflang($this->hreflangFor('contact'));

        $this->addCommonSchemas($seo, 'contact');
        $seo->addJsonLd(SeoMeta::breadcrumbSchema([
            ['name' => I18n::t('breadcrumb.home'),  'url' => I18n::url('', $this->locale)],
            ['name' => I18n::t('common.contact'),   'url' => $this->canonical('contact')],
        ], BASE_URL));

        $flash = $_SESSION['flash_contact'] ?? null;
        unset($_SESSION['flash_contact']);

        $this->renderPage('contact', [
            'seo'     => $seo,
            'ayarlar' => $this->ayarlar,
            'flash'   => $flash,
            'currentPage' => 'contact',
        ]);
    }

    public function contactSubmit(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            header('Location: ' . $this->canonical('contact'));
            exit;
        }
        // CSRF — basit kontrol
        $token = $_POST['_csrf'] ?? '';
        if (empty($_SESSION['_csrf']) || !hash_equals((string)$_SESSION['_csrf'], (string)$token)) {
            $_SESSION['flash_contact'] = ['ok' => false, 'msg' => I18n::t('contact.form.error')];
            header('Location: ' . $this->canonical('contact'));
            exit;
        }
        // Honeypot (bot algılama)
        if (!empty($_POST['website_url'])) {
            $_SESSION['flash_contact'] = ['ok' => false, 'msg' => I18n::t('contact.form.spam')];
            header('Location: ' . $this->canonical('contact'));
            exit;
        }

        $ad    = trim((string)($_POST['ad_soyad'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $msg   = trim((string)($_POST['mesaj'] ?? ''));
        if ($ad === '' || $email === '' || $msg === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_contact'] = ['ok' => false, 'msg' => I18n::t('contact.form.invalid')];
            header('Location: ' . $this->canonical('contact'));
            exit;
        }

        try {
            (new ContactMessage())->kaydet([
                'locale'  => $this->locale,
                'ad_soyad'=> $ad,
                'firma'   => trim((string)($_POST['firma'] ?? '')),
                'ulke'    => trim((string)($_POST['ulke']  ?? '')),
                'email'   => $email,
                'telefon' => trim((string)($_POST['telefon'] ?? '')),
                'urun'    => trim((string)($_POST['urun'] ?? '')),
                'mesaj'   => $msg,
            ]);
            $_SESSION['flash_contact'] = ['ok' => true, 'msg' => I18n::t('contact.form.success')];
        } catch (Throwable $e) {
            $_SESSION['flash_contact'] = ['ok' => false, 'msg' => I18n::t('contact.form.error')];
        }
        header('Location: ' . $this->canonical('contact') . '#contact-form');
        exit;
    }

    public function notFound(): void
    {
        http_response_code(404);
        $seo = $this->baseSeo('home');
        $seo->setTitle(I18n::t('errors.404_title') . ' | Nymagro')
            ->setDescription(I18n::t('errors.404_desc'))
            ->setNoindex(true)
            ->setCanonical($this->canonical('home'));

        $this->renderPage('404', [
            'seo'     => $seo,
            'ayarlar' => $this->ayarlar,
            'currentPage' => 'home',
        ]);
    }

    // ───────────────────── Yardımcılar ─────────────────────

    private function baseSeo(string $page): SeoMeta
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(16));
        }
        $seo = new SeoMeta();
        $seo->setLocale($this->locale)
            ->setSiteName('Nymagro')
            ->setOgImage($this->ogImageUrl());
        return $seo;
    }

    /** Belirli sayfanın canonical URL'i */
    private function canonical(string $pageKey): string
    {
        $segment = I18n::pageMap()[$this->locale][$pageKey] ?? '';
        return I18n::url($segment, $this->locale);
    }

    /** TR/EN/RU varyantları (slug yoksa) */
    private function hreflangFor(string $pageKey): array
    {
        $out = [];
        foreach (['tr','en','ru'] as $loc) {
            $seg = I18n::pageMap()[$loc][$pageKey] ?? '';
            $out[$loc] = I18n::url($seg, $loc);
        }
        return $out;
    }

    private function addCommonSchemas(SeoMeta $seo, string $type): void
    {
        // Organization + WebSite — her sayfada
        $seo->addJsonLd(SeoMeta::organizationSchema([
            'name'    => 'Nymagro',
            'url'     => I18n::url('', $this->locale),
            'logo'    => $this->absoluteLogo(),
            'email'   => (string)($this->ayarlar['email'] ?? ''),
            'phone'   => (string)($this->ayarlar['phone'] ?? ''),
            'street'  => (string)($this->ayarlar['address'] ?? ''),
            'city'    => 'Antalya',
            'region'  => 'Antalya',
            'country' => 'TR',
            'sameAs'  => array_values(array_filter([
                (string)($this->ayarlar['instagram'] ?? ''),
                (string)($this->ayarlar['linkedin']  ?? ''),
                (string)($this->ayarlar['facebook']  ?? ''),
                (string)($this->ayarlar['twitter']   ?? ''),
            ])),
        ]));

        $seo->addJsonLd(SeoMeta::websiteSchema(I18n::url('', $this->locale), 'Nymagro', $this->locale));

        if ($type === 'contact' || $type === 'home') {
            $seo->addJsonLd(SeoMeta::localBusinessSchema([
                'name'    => 'Nymagro',
                'url'     => I18n::url('', $this->locale),
                'logo'    => $this->absoluteLogo(),
                'email'   => (string)($this->ayarlar['email'] ?? ''),
                'phone'   => (string)($this->ayarlar['phone'] ?? ''),
                'street'  => (string)($this->ayarlar['address'] ?? ''),
                'city'    => 'Antalya',
                'region'  => 'Antalya',
                'country' => 'TR',
                'lat'     => (string)($this->ayarlar['lat'] ?? '36.927'),
                'lng'     => (string)($this->ayarlar['lng'] ?? '30.687'),
            ]));
        }
    }

    private function absoluteLogo(): string
    {
        $path = (string)($this->ayarlar['logo_path'] ?? '');
        // Panelden logo yüklenmemişse depodaki marka logosuna düş
        if ($path === '') $path = '/img/nymagro-logo.png';
        return $this->publicAssetUrl($path);
    }

    private function ogImageUrl(): string
    {
        $og = (string)($this->ayarlar['og_image'] ?? '');
        if ($og !== '') {
            return $this->publicAssetUrl($og);
        }
        // Yoksa hero slide #1
        $h = (string)($this->ayarlar['slide1_img'] ?? '');
        return $h !== '' ? $this->publicAssetUrl($h) : $this->absoluteLogo();
    }

    private function safeProducts(): array
    {
        try {
            return array_map([$this, 'normalizeProduct'], $this->site->urunler(true));
        } catch (Throwable $e) {
            return [];
        }
    }

    private function safeGallery(): array
    {
        try {
            return array_map([$this, 'normalizeGalleryItem'], $this->site->galeri(true));
        } catch (Throwable $e) {
            return [];
        }
    }

    private function normalizeSettings(array $settings): array
    {
        $imageKeys = ['logo_path', 'about_img', 'og_image'];
        $slideCount = max(4, min(20, (int)($settings['slide_count'] ?? 4)));
        for ($i = 1; $i <= $slideCount; $i++) {
            $imageKeys[] = 'slide' . $i . '_img';
        }
        foreach ($imageKeys as $key) {
            if (!empty($settings[$key])) {
                $settings[$key] = $this->publicAssetUrl((string)$settings[$key]);
            }
        }
        return $settings;
    }

    private function normalizeProduct(array $product): array
    {
        if (!empty($product['gorsel'])) {
            $product['gorsel'] = $this->publicAssetUrl((string)$product['gorsel']);
        }
        return $product;
    }

    private function normalizeGalleryItem(array $item): array
    {
        if (!empty($item['gorsel'])) {
            $item['gorsel'] = $this->publicAssetUrl((string)$item['gorsel']);
        }
        return $item;
    }

    private function publicAssetUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') return '';

        if (preg_match('~^https?://~i', $url)) {
            $parts = parse_url($url);
            $host = strtolower((string)($parts['host'] ?? ''));
            $path = (string)($parts['path'] ?? '');
            $query = isset($parts['query']) ? '?' . $parts['query'] : '';
            $baseHost = strtolower((string)(parse_url(BASE_URL, PHP_URL_HOST) ?: ''));

            if (preg_match('~/uploads/~', $path) && ($host === $baseHost || in_array($host, ['localhost', '127.0.0.1', '::1'], true))) {
                $uploadsPath = preg_replace('~^.*?/uploads/~', '/uploads/', $path);
                return BASE_URL . $uploadsPath . $query;
            }

            return $url;
        }

        if (str_starts_with($url, '//')) {
            return $url;
        }

        $path = str_starts_with($url, '/') ? $url : '/' . $url;
        if (preg_match('~^/Muhasebe/uploads/~i', $path)) {
            $path = preg_replace('~^/Muhasebe~i', '', $path);
        }
        return BASE_URL . $path;
    }

    private function productFaqs(string $ad): array
    {
        if ($this->locale === 'en') {
            return [
                ['q' => "How is {$ad} applied?", 'a' => "{$ad} can be applied both foliarly and through drip irrigation. Please follow the dose recommendations on the product label and adjust them to your soil and leaf analysis results."],
                ['q' => 'What packaging options are available?', 'a' => 'Depending on the product, packaging is available in 1 L, 5 L and 10 L containers, and in 1 kg and 5 kg for powder products.'],
                ['q' => 'How can I request a quote?', 'a' => 'You can request a quote via the contact form, e-mail or WhatsApp on our contact page. Our technical team will get back to you.'],
            ];
        }
        if ($this->locale === 'ru') {
            return [
                ['q' => "Как применять «{$ad}»?", 'a' => "«{$ad}» можно применять как по листу, так и через капельное орошение. Следуйте рекомендациям по дозировке на этикетке и корректируйте их по результатам анализа почвы и листа."],
                ['q' => 'Какие варианты упаковки доступны?', 'a' => 'В зависимости от продукта доступны упаковки 1 л, 5 л и 10 л, а для порошковых продуктов — 1 кг и 5 кг.'],
                ['q' => 'Как запросить предложение?', 'a' => 'Запросите предложение через форму контактов, e-mail или WhatsApp на странице «Контакты» — с вами свяжется наша техническая команда.'],
            ];
        }
        return [
            ['q' => "{$ad} nasıl uygulanır?", 'a' => "{$ad}, hem yapraktan hem de damlama sulama ile uygulanabilir. Ürün etiketindeki doz tavsiyelerine uyunuz ve toprak/yaprak analizi sonucunuza göre dozu ayarlayınız."],
            ['q' => 'Hangi ambalaj seçenekleri var?', 'a' => 'Ürüne göre 1 L, 5 L ve 10 L ambalaj seçenekleri; toz ürünlerde 1 kg ve 5 kg seçenekleri sunulmaktadır.'],
            ['q' => 'Nasıl teklif alabilirim?', 'a' => 'İletişim sayfasındaki form, e-posta veya WhatsApp üzerinden teklif talebinde bulunabilirsiniz. Teknik ekibimiz size dönüş yapar.'],
        ];
    }

    private static function sanitizeSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
        return (string)$slug;
    }

    /** Shared layout ile sayfayı render et */
    private function renderPage(string $page, array $vars): void
    {
        $vars['locale']   = $this->locale;
        $vars['pageView'] = $page;

        // CSRF token (form submission için)
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(16));
        }
        $vars['csrf'] = $_SESSION['_csrf'];

        /*
         * $siteText(key, fallback)
         * ─────────────────────────
         * Önce DB'de i18n_{locale}_{key} bakılır (panel'den override edilebilir).
         * Sonra locale-siz {key} bakılır.
         * Bulunamazsa verilen $fallback döner (genellikle I18n::t(...)).
         */
        $locale  = $this->locale;
        $ayarlar = $this->ayarlar;
        $vars['siteText'] = static function(string $key, string $fallback = '') use ($locale, $ayarlar): string {
            $locKey = 'i18n_' . $locale . '_' . $key;
            $val = trim((string)($ayarlar[$locKey] ?? ''));
            if ($val !== '') return $val;
            $val = trim((string)($ayarlar[$key] ?? ''));
            return $val !== '' ? $val : $fallback;
        };

        extract($vars, EXTR_SKIP);
        $layout = VIEWS_PATH . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . 'layout.php';
        require $layout;
    }
}
