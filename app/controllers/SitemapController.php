<?php
/**
 * SitemapController
 * --------------------------------------------------------
 * /sitemap.xml  → public sayfaların XML sitemap'i (TR/EN/RU + ürünler + bölgeler)
 * /robots.txt   → arama motoru direktifi (panel sayfaları disallow)
 *
 * Panel ve özel rotalar (giris, dashboard, satis, vb.) sitemap'e EKLENMEZ.
 */
require_once CORE_PATH   . '/I18n.php';
require_once MODELS_PATH . '/SiteIcerik.php';

class SitemapController extends Controller
{
    public function index(): void
    {
        $urls = $this->buildUrls();

        header('Content-Type: application/xml; charset=utf-8');
        header('X-Robots-Tag: noindex'); // sitemap dosyasının kendisi indekslenmesin
        header('Cache-Control: public, max-age=3600');

        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

        foreach ($urls as $u) {
            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($u['loc'], ENT_QUOTES) . "</loc>\n";
            if (!empty($u['lastmod']))    echo "    <lastmod>" . htmlspecialchars($u['lastmod']) . "</lastmod>\n";
            if (!empty($u['changefreq'])) echo "    <changefreq>" . htmlspecialchars($u['changefreq']) . "</changefreq>\n";
            if (!empty($u['priority']))   echo "    <priority>" . htmlspecialchars((string)$u['priority']) . "</priority>\n";

            // hreflang alternatif dil bağlantıları
            if (!empty($u['alternates']) && is_array($u['alternates'])) {
                foreach ($u['alternates'] as $loc => $alt) {
                    echo '    <xhtml:link rel="alternate" hreflang="' . htmlspecialchars($loc) . '" href="' . htmlspecialchars($alt, ENT_QUOTES) . '"/>' . "\n";
                }
                if (isset($u['alternates']['tr'])) {
                    echo '    <xhtml:link rel="alternate" hreflang="x-default" href="' . htmlspecialchars($u['alternates']['tr'], ENT_QUOTES) . '"/>' . "\n";
                }
            }
            echo "  </url>\n";
        }

        echo '</urlset>';
        exit;
    }

    public function robots(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: public, max-age=3600');

        // BASE_URL'in path kısmını al (ör: /Muhasebe)
        $basePath = (string)(parse_url(BASE_URL, PHP_URL_PATH) ?? '');
        $basePath = rtrim($basePath, '/');

        $disallowList = [
            '/dashboard', '/satis', '/satisl', '/satislar', '/alis', '/alislar',
            '/musteri', '/musteriler', '/tedarikci', '/tedarikciler',
            '/personel', '/urun', '/urunler', '/nakit', '/hesap', '/hesaplar',
            '/masraf', '/masraflar', '/rapor', '/raporlar',
            '/companies', '/periods', '/site', '/profil', '/takvim',
            '/auth', '/giris', '/cikis', '/cek', '/senet',
            '/teklifler', '/krediler', '/depolar', '/demirbaslar', '/projeler', '/fihrist', '/tanimlar',
            '/uploads/masraflar/',
        ];

        echo "# Nymagro — robots.txt\n";
        echo "# Public site (TR/EN/RU) is indexable. Admin panel and private routes are disallowed.\n\n";
        echo "User-agent: *\n";
        echo "Allow: /\n";
        foreach ($disallowList as $path) {
            echo 'Disallow: ' . $basePath . $path . "\n";
        }
        echo "\n";

        // Yaygın arama motoru için kibar gecikme
        echo "User-agent: AhrefsBot\nCrawl-delay: 10\n\n";
        echo "User-agent: SemrushBot\nCrawl-delay: 10\n\n";

        // Sitemap referansı
        echo "Sitemap: " . BASE_URL . "/sitemap.xml\n";
        exit;
    }

    /** Public sitemap'e dahil edilecek tüm URL'leri çıkar */
    private function buildUrls(): array
    {
        $urls = [];
        $today = date('Y-m-d');

        $pageKeys = ['home','about','products','quality','services','gallery','contact'];

        $priorities = [
            'home' => '1.0',
            'about' => '0.8',
            'products' => '0.9',
            'quality' => '0.7',
            'services' => '0.7',
            'gallery' => '0.5',
            'contact' => '0.7',
        ];
        $changefreq = [
            'home' => 'weekly',
            'about' => 'monthly',
            'products' => 'weekly',
            'quality' => 'monthly',
            'services' => 'monthly',
            'gallery' => 'weekly',
            'contact' => 'monthly',
        ];

        // Statik public sayfalar (TR/EN/RU)
        foreach ($pageKeys as $key) {
            foreach (['tr','en','ru'] as $loc) {
                $alts = [];
                foreach (['tr','en','ru'] as $altLoc) {
                    $alts[$altLoc] = I18n::altUrl($key, $altLoc);
                }
                $urls[] = [
                    'loc'        => I18n::altUrl($key, $loc),
                    'lastmod'    => $today,
                    'changefreq' => $changefreq[$key] ?? 'monthly',
                    'priority'   => $priorities[$key]  ?? '0.7',
                    'alternates' => $alts,
                ];
            }
        }

        // Ürün detay sayfaları
        try {
            $site    = new SiteIcerik();
            $urunler = $site->urunler(true);
        } catch (Throwable $e) {
            $urunler = [];
        }
        foreach ($urunler as $u) {
            $alts = [];
            foreach (['tr','en','ru'] as $loc) {
                $slug = $u['slug_'.$loc] ?? $u['slug_tr'] ?? '';
                if ($slug) {
                    $alts[$loc] = I18n::altUrl('products', $loc, $slug);
                }
            }
            foreach ($alts as $loc => $url) {
                $urls[] = [
                    'loc'        => $url,
                    'lastmod'    => isset($u['updated_at']) ? substr((string)$u['updated_at'], 0, 10) : $today,
                    'changefreq' => 'weekly',
                    'priority'   => '0.85',
                    'alternates' => $alts,
                ];
            }
        }

        return $urls;
    }
}
