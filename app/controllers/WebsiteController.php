<?php
/**
 * WebsiteController
 * --------------------------------------------------------
 * Nuverna Trade public web sitesi (TR / EN / RU) için tüm sayfaları yönetir.
 *
 * URL örnekleri:
 *   /tr                       → home()
 *   /en/about                 → about()
 *   /tr/urunler/kiraz         → productDetail('kiraz')
 *   /en/products/cherries     → productDetail('cherries')
 *   /tr/ihracat-bolgeleri     → exportMarkets()
 *   /en/export/dubai-uae      → exportRegion('dubai-uae')
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

    /** İhracat bölgesi tanımları (slug → key) */
    private const REGION_SLUGS = [
        'tr' => [
            'avrupa'           => 'europe',
            'dubai-bae'        => 'dubai-uae',
            'suudi-arabistan'  => 'saudi-arabia',
            'misir'            => 'egypt',
            'rusya'            => 'russia',
        ],
        'en' => [
            'europe'           => 'europe',
            'dubai-uae'        => 'dubai-uae',
            'saudi-arabia'     => 'saudi-arabia',
            'egypt'            => 'egypt',
            'russia'           => 'russia',
        ],
        'ru' => [
            'europe'           => 'europe',
            'dubai-uae'        => 'dubai-uae',
            'saudi-arabia'     => 'saudi-arabia',
            'egypt'            => 'egypt',
            'russia'           => 'russia',
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
                    ? "Fresh {$ad} Exporter from Türkiye | Nuverna Trade"
                    : ($this->locale === 'ru'
                        ? "Экспорт свежей продукции «{$ad}» из Турции | Nuverna Trade"
                        : "{$ad} İhracatı | Nuverna Trade"));

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
            'category'    => 'Fresh produce',
            'brand'       => 'Nuverna Trade',
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
            'tr' => "{$regionName} Pazarına Yaş Sebze Meyve İhracatı | Nuverna Trade",
            'en' => "Fresh Produce Exporter to {$regionName} | Nuverna Trade",
            'ru' => "Экспорт свежих фруктов и овощей: {$regionName} | Nuverna Trade",
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
        $seo->setTitle(I18n::t('errors.404_title') . ' | Nuverna Trade')
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
            ->setSiteName('Nuverna Trade')
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
            'name'    => 'Nuverna Trade',
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

        $seo->addJsonLd(SeoMeta::websiteSchema(I18n::url('', $this->locale), 'Nuverna Trade', $this->locale));

        if ($type === 'contact' || $type === 'home') {
            $seo->addJsonLd(SeoMeta::localBusinessSchema([
                'name'    => 'Nuverna Trade',
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
        if ($path === '') return '';
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
                ['q' => "Do you export {$ad} from Türkiye?", 'a' => "Yes — Nuverna Trade exports fresh {$ad} from Türkiye to Europe, Dubai, Saudi Arabia, Russia and surrounding markets."],
                ['q' => 'What packaging options do you offer?', 'a' => 'We offer market-tailored packaging options including cartons, plastic punnets and customised B2B packaging.'],
                ['q' => 'How can I request a quote?', 'a' => 'You can request a quote via the contact form, e-mail or WhatsApp on our contact page.'],
            ];
        }
        if ($this->locale === 'ru') {
            return [
                ['q' => "Экспортируете ли вы «{$ad}» из Турции?", 'a' => "Да — Nuverna Trade поставляет свежую продукцию «{$ad}» из Турции в Европу, Дубай, Саудовскую Аравию, Россию и соседние рынки."],
                ['q' => 'Какие варианты упаковки вы предлагаете?', 'a' => 'Мы предлагаем варианты упаковки под рынок — картонные коробки, пластиковые упаковки и индивидуальные B2B-решения.'],
                ['q' => 'Как запросить предложение?', 'a' => 'Запросите предложение через форму контактов, e-mail или WhatsApp на нашей странице «Контакты».'],
            ];
        }
        return [
            ['q' => "Türkiye’den {$ad} ihracatı yapıyor musunuz?", 'a' => "Evet — Nuverna Trade, Türkiye’den Avrupa, Dubai, Suudi Arabistan, Rusya ve çevre pazarlara taze {$ad} ihraç eder."],
            ['q' => 'Hangi paketleme seçeneklerini sunuyorsunuz?', 'a' => 'Hedef pazara uygun karton, plastik kaset ve özel B2B paketleme seçenekleri sunuyoruz.'],
            ['q' => 'Nasıl teklif alabilirim?', 'a' => 'İletişim sayfasındaki form, e-posta veya WhatsApp üzerinden teklif talebinde bulunabilirsiniz.'],
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
