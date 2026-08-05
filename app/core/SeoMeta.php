<?php
/**
 * SeoMeta
 * --------------------------------------------------------
 * Public website için meta tag, hreflang, canonical, Open Graph,
 * Twitter Card ve Schema.org (JSON-LD) çıktısını üretir.
 *
 * Kullanım:
 *   $seo = new SeoMeta();
 *   $seo->setTitle('...')->setDescription('...')->setCanonical($url);
 *   $seo->setHreflang(['tr'=>$trUrl,'en'=>$enUrl,'ru'=>$ruUrl]);
 *   $seo->addJsonLd($organizationSchema);
 *   echo $seo->renderHead();
 */
final class SeoMeta
{
    private string $title = '';
    private string $description = '';
    private string $canonical = '';
    private array  $hreflang  = [];
    private string $ogImage   = '';
    private string $ogType    = 'website';
    private string $siteName  = 'Nuverna Trade';
    private string $locale    = 'tr';
    private bool   $noindex   = false;
    private array  $jsonLd    = [];
    private array  $extra     = [];

    public function setTitle(string $t): self        { $this->title = $t; return $this; }
    public function setDescription(string $d): self  { $this->description = $d; return $this; }
    public function setCanonical(string $url): self  { $this->canonical = $url; return $this; }
    public function setHreflang(array $map): self    { $this->hreflang = $map; return $this; }
    public function setOgImage(string $url): self    { $this->ogImage = $url; return $this; }
    public function setOgType(string $type): self    { $this->ogType = $type; return $this; }
    public function setSiteName(string $name): self  { $this->siteName = $name; return $this; }
    public function setLocale(string $loc): self     { $this->locale = $loc; return $this; }
    public function setNoindex(bool $v = true): self { $this->noindex = $v; return $this; }
    public function addJsonLd(array $schema): self   { $this->jsonLd[] = $schema; return $this; }
    public function addExtra(string $tag): self      { $this->extra[] = $tag; return $this; }

    public function getTitle(): string       { return $this->title; }
    public function getDescription(): string { return $this->description; }

    public function renderHead(): string
    {
        $h = '';
        $h .= '<title>' . self::esc($this->title) . '</title>' . "\n";
        $h .= '<meta name="description" content="' . self::esc($this->description) . '">' . "\n";

        if ($this->noindex) {
            $h .= '<meta name="robots" content="noindex, nofollow">' . "\n";
        } else {
            $h .= '<meta name="robots" content="index, follow, max-image-preview:large">' . "\n";
        }

        if ($this->canonical) {
            $h .= '<link rel="canonical" href="' . self::esc($this->canonical) . '">' . "\n";
        }

        // hreflang
        foreach ($this->hreflang as $loc => $url) {
            if (!$url) continue;
            $h .= '<link rel="alternate" hreflang="' . self::esc($loc) . '" href="' . self::esc($url) . '">' . "\n";
        }
        if (!empty($this->hreflang['tr'])) {
            $h .= '<link rel="alternate" hreflang="x-default" href="' . self::esc($this->hreflang['tr']) . '">' . "\n";
        }

        // Open Graph
        $h .= '<meta property="og:type" content="' . self::esc($this->ogType) . '">' . "\n";
        $h .= '<meta property="og:site_name" content="' . self::esc($this->siteName) . '">' . "\n";
        $h .= '<meta property="og:title" content="' . self::esc($this->title) . '">' . "\n";
        $h .= '<meta property="og:description" content="' . self::esc($this->description) . '">' . "\n";
        if ($this->canonical) {
            $h .= '<meta property="og:url" content="' . self::esc($this->canonical) . '">' . "\n";
        }
        if ($this->ogImage) {
            $h .= '<meta property="og:image" content="' . self::esc($this->ogImage) . '">' . "\n";
            $h .= '<meta property="og:image:width" content="1200">' . "\n";
            $h .= '<meta property="og:image:height" content="630">' . "\n";
        }
        $localeMap = ['tr'=>'tr_TR','en'=>'en_US','ru'=>'ru_RU'];
        $h .= '<meta property="og:locale" content="' . self::esc($localeMap[$this->locale] ?? 'tr_TR') . '">' . "\n";
        foreach ($this->hreflang as $loc => $url) {
            if ($loc === $this->locale) continue;
            $alt = $localeMap[$loc] ?? null;
            if ($alt) {
                $h .= '<meta property="og:locale:alternate" content="' . self::esc($alt) . '">' . "\n";
            }
        }

        // Twitter Card
        $h .= '<meta name="twitter:card" content="summary_large_image">' . "\n";
        $h .= '<meta name="twitter:title" content="' . self::esc($this->title) . '">' . "\n";
        $h .= '<meta name="twitter:description" content="' . self::esc($this->description) . '">' . "\n";
        if ($this->ogImage) {
            $h .= '<meta name="twitter:image" content="' . self::esc($this->ogImage) . '">' . "\n";
        }

        foreach ($this->extra as $tag) {
            $h .= $tag . "\n";
        }

        // JSON-LD
        foreach ($this->jsonLd as $schema) {
            $h .= '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
        }

        return $h;
    }

    // ─── Hazır Şema Üreticileri ───────────────────────────

    public static function organizationSchema(array $opts): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type'    => 'Organization',
            'name'     => $opts['name']  ?? 'Nuverna Trade',
            'url'      => $opts['url']   ?? '',
            'logo'     => $opts['logo']  ?? '',
            'email'    => $opts['email'] ?? '',
            'telephone'=> $opts['phone'] ?? '',
            'sameAs'   => array_values(array_filter($opts['sameAs'] ?? [])),
            'address'  => [
                '@type'           => 'PostalAddress',
                'streetAddress'   => $opts['street']  ?? '',
                'addressLocality' => $opts['city']    ?? 'Antalya',
                'addressRegion'   => $opts['region']  ?? 'Antalya',
                'postalCode'      => $opts['postal']  ?? '',
                'addressCountry'  => $opts['country'] ?? 'TR',
            ],
        ];
    }

    public static function localBusinessSchema(array $opts): array
    {
        $org = self::organizationSchema($opts);
        $org['@type']    = 'LocalBusiness';
        $org['priceRange'] = $opts['priceRange'] ?? '$$';
        if (!empty($opts['lat']) && !empty($opts['lng'])) {
            $org['geo'] = [
                '@type'     => 'GeoCoordinates',
                'latitude'  => (float)$opts['lat'],
                'longitude' => (float)$opts['lng'],
            ];
        }
        $org['openingHoursSpecification'] = [[
            '@type'     => 'OpeningHoursSpecification',
            'dayOfWeek' => ['Monday','Tuesday','Wednesday','Thursday','Friday'],
            'opens'     => '09:00',
            'closes'    => '18:00',
        ]];
        return $org;
    }

    public static function websiteSchema(string $url, string $name, string $locale = 'tr'): array
    {
        return [
            '@context'      => 'https://schema.org',
            '@type'         => 'WebSite',
            'name'          => $name,
            'url'           => $url,
            'inLanguage'    => $locale,
        ];
    }

    public static function breadcrumbSchema(array $items, string $baseUrl): array
    {
        $list = [];
        foreach ($items as $i => $it) {
            $list[] = [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => (string)($it['name'] ?? ''),
                'item'     => (string)($it['url']  ?? ''),
            ];
        }
        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $list,
        ];
    }

    public static function itemListSchema(array $items): array
    {
        $list = [];
        foreach ($items as $i => $it) {
            $list[] = [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'url'      => (string)($it['url']  ?? ''),
                'name'     => (string)($it['name'] ?? ''),
            ];
        }
        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'ItemList',
            'itemListElement' => $list,
        ];
    }

    public static function productSchema(array $opts): array
    {
        $s = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Product',
            'name'        => (string)($opts['name'] ?? ''),
            'description' => (string)($opts['description'] ?? ''),
            'image'       => (string)($opts['image'] ?? ''),
            'category'    => (string)($opts['category'] ?? 'Fresh produce'),
            'brand'       => [
                '@type' => 'Brand',
                'name'  => (string)($opts['brand'] ?? 'Nuverna Trade'),
            ],
        ];
        return $s;
    }

    public static function faqSchema(array $faqs): array
    {
        $list = [];
        foreach ($faqs as $f) {
            $list[] = [
                '@type'          => 'Question',
                'name'           => (string)($f['q'] ?? ''),
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => (string)($f['a'] ?? ''),
                ],
            ];
        }
        return [
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => $list,
        ];
    }

    private static function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
