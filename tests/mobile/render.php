<?php
/**
 * Mobil uyum testi — sayfa render edici (headless, veritabanısız).
 * --------------------------------------------------------------------------
 * Kullanım:  php tests/mobile/render.php <cikti-dizini>
 *
 * GERÇEK layout (app/views/layout/main.php) ve GERÇEK modül view'leri, temsili
 * verilerle render edilip statik .html dosyalarına yazılır. Böylece
 * tests/mobile/check.mjs bu sayfaları gerçek bir tarayıcıda (Chromium) mobil
 * ekran boyutunda açıp ölçebilir.
 *
 * Neden bu yol: uygulama MySQL'e bağlı ve bu ortamda MySQL yok. Ancak mobil
 * yerleşim hataları (yatay taşma, sabit alt barın ekran dışına kayması) tamamen
 * HTML+CSS kaynaklıdır; veri KATMANI değil, İŞARETLEME + STİL test edilir.
 * Bu yüzden veritabanı bağımlılıkları vekil (stub) sınıflarla karşılanır ama
 * layout ve view dosyalarının KENDİSİ gerçektir — dosyalar değiştiğinde test
 * de değişimi görür.
 */

declare(strict_types=1);

$kok = dirname(__DIR__, 2);
$ciktiDizini = $argv[1] ?? sys_get_temp_dir() . '/nymagro-mobil';

if (!is_dir($ciktiDizini) && !mkdir($ciktiDizini, 0777, true) && !is_dir($ciktiDizini)) {
    fwrite(STDERR, "Çıktı dizini oluşturulamadı: {$ciktiDizini}\n");
    exit(1);
}

// ── Uygulama sabitleri ────────────────────────────────────────────────
define('ROOT_PATH', $kok);
define('APP_PATH', $kok . '/app');
define('CORE_PATH', APP_PATH . '/core');
define('MODELS_PATH', APP_PATH . '/models');
define('VIEWS_PATH', APP_PATH . '/views');
define('CONTROLLERS_PATH', APP_PATH . '/controllers');
define('BASE_URL', '');            // dosya:// altında kök-göreli bağlantılar
define('APP_NAME', 'Nymagro');
define('APP_ENV', 'testing');

$_SESSION = ['user_id' => 1, 'user_logged_in' => true];
$_GET = [];
$_SERVER['REQUEST_METHOD'] = 'GET';

// ── Vekil (stub) bağımlılıklar ────────────────────────────────────────
// Not: bunlar YALNIZCA testte tanımlanır; üretim kodu değiştirilmez.
final class Csrf
{
    public static function token(): string { return 'test-token'; }
    public static function fieldHtml(): string { return '<input type="hidden" name="csrf_token" value="test-token">'; }
}

final class Rbac
{
    /** Testte tüm izinler açık — en yoğun (en geniş) arayüz render edilsin. */
    public static function currentUserCan(string $kod): bool { return true; }
    public static function authorizeOrDeny(string $c, string $m): void {}
    public static function isSuperAdmin(int $userId): bool { return true; }
    public static function moduleLabels(): array { return []; }
}

final class AuthGuard
{
    public static function isLoggedIn(): bool { return true; }
    public static function isSuperAdmin(): bool { return true; }
    public static function userId(): int { return 1; }
    public static function userName(): string { return 'Test Kullanıcı'; }
    public static function userRole(): string { return 'super_admin'; }
}

final class TenantContext
{
    public static function activeCompany(): ?array
    {
        return ['id' => 1, 'company_name' => 'NYMAGRO TARIM SANAYİ VE TİCARET LİMİTED ŞİRKETİ', 'short_name' => 'nym', 'status' => 'active'];
    }
    public static function activePeriod(): ?array
    {
        return ['id' => 1, 'company_id' => 1, 'period_name' => '2026', 'status' => 'open', 'fiscal_year' => 2026];
    }
    public static function activeCompanySettings(): array { return ['theme_color' => 'emerald']; }
    public static function activeCompanyId(): ?int { return 1; }
    public static function activePeriodId(): ?int { return 1; }
    public static function userId(): int { return 1; }
    public static function canManageCompany(int $id): bool { return true; }
    public static function canManagePeriod(int $id): bool { return true; }
    public static function userCanAccessCompany(int $id): bool { return true; }
}

/** main.php'nin kullandığı varlık (asset) yardımcısı. */
function varlik(string $yol): string { return $yol; }

// View'lerin doğrudan kullandığı gerçek modeller (yalnızca sınıf tanımı için;
// veritabanına yapıcı çağrılmadan dokunulmaz).
require_once MODELS_PATH . '/Urun.php';

// ── Temsili veri üreteçleri ───────────────────────────────────────────
// Gerçek hayatta karşılaşılan UZUN metinler bilinçli olarak kullanılır:
// mobil yatay taşma çoğu zaman uzun cari unvanı / belge no yüzünden oluşur.

function sahteFatura(int $i, string $belgeTipi = 'satis'): array
{
    $unvanlar = [
        'ATILGAN GÜMRÜK MÜŞAVİRLİĞİ LTD.ŞTİ.',
        'MELSABERRY',
        'ANTALYAM ANTREPO LOJİSTİK A.Ş.',
        'NYMAGRO TARIM SANAYİ VE TİCARET LİMİTED ŞİRKETİ',
    ];
    return [
        'id' => $i,
        'fatura_no' => sprintf('NYM-2026-%06d', $i),
        'fatura_tarihi' => '2026-08-0' . (($i % 9) + 1),
        'vade_tarihi' => '2026-09-0' . (($i % 9) + 1),
        'cari_id' => $i,
        'cari_unvan' => $unvanlar[$i % count($unvanlar)],
        'genel_toplam' => 123456.78,
        'ara_toplam' => 100000.00,
        'kdv_tutari' => 23456.78,
        'odenen_tutar' => 50000.00,
        'kalan_tutar' => 73456.78,
        'durum' => ['onaylandi', 'taslak', 'kismi_odendi', 'odendi'][$i % 4],
        'belge_tipi' => $belgeTipi,
        'aciklama' => 'Örnek açıklama metni',
        'olusturan_adi' => 'Sebahattin',
        'irsaliye_kullanildi' => 0,
        'sevk_turu' => 'musteri',
        'depo_id' => 1,
        'hedef_depo_id' => null,
        'para_birimi' => 'TRY',
    ];
}

function sahteUrun(int $i): array
{
    $adlar = ['SILATRIX 1 L', 'SILECKO MOZ 5 L', 'ARDİYE HİZMET BEDELİ', 'BRİLİXA 0-23-36+TE 1 KG'];
    return [
        'id' => $i,
        'ad' => $adlar[$i % count($adlar)],
        'tip' => $i % 3 === 2 ? 'hizmet' : 'urun',
        'stok_kodu' => 'STK-' . $i,
        'barkod' => '869000000000' . $i,
        'kategori' => 'Şelatlı Mikro Element',
        'marka' => 'Nymagro',
        'satis_fiyati' => 2829.72,
        'alis_fiyati' => 1500.00,
        'stok_miktari' => 744,
        'kritik_stok' => 10,
        'birim' => 'Adet',
        'kdv_orani' => 20,
        'eticaret' => 1,
        'aktif_mi' => 1,
        'koli_ici_adet' => 12,
    ];
}

// ── Render edilecek sayfalar ──────────────────────────────────────────
/** @return array<string, array{view:string, vars:array}> */
function sayfalar(): array
{
    $faturalar = array_map(fn(int $i): array => sahteFatura($i), range(1, 6));
    $alislar   = array_map(fn(int $i): array => sahteFatura($i, 'alis'), range(1, 6));
    $urunler   = array_map('sahteUrun', range(1, 8));

    $ortakFatura = [
        'toplam' => 6, 'sayfa' => 1, 'sayfaSayisi' => 1, 'limit' => 50,
        'arama' => '', 'durum' => '', 'donem' => 'tumu', 'iptalleri' => false,
        'flash' => [], 'depolar' => [['id' => 1, 'ad' => 'Finike Depo']],
        'kasaHesaplar' => [['id' => 1, 'hesap_adi' => 'İş Bankası Sanal POS (Link)', 'tur' => 'banka', 'para_birimi' => 'TRY']],
        'ozetler' => ['adet' => 6, 'toplam_tutar' => 1238968.00, 'bekleyen_tutar' => 1063968.00, 'iptal_adet' => 0],
    ];

    return [
        'satislar' => [
            'view' => 'satislar/index',
            'vars' => $ortakFatura + ['faturalar' => $faturalar, 'belgeTipi' => 'satis'],
        ],
        'alislar' => [
            'view' => 'alislar/index',
            'vars' => $ortakFatura + ['faturalar' => $alislar, 'belgeTipi' => 'alis'],
        ],
        'urunler' => [
            'view' => 'urunler/index',
            'vars' => [
                'urunler' => $urunler, 'toplam' => 8, 'arama' => '', 'tipFlt' => '',
                'katFlt' => '', 'markaFlt' => '', 'sayfa' => 1, 'sayfaSayisi' => 1, 'limit' => 50,
                'kategoriler' => [['kategori' => 'Şelatlı Mikro Element'], ['kategori' => 'Makro Besin']],
                'markalar' => [['marka' => 'Nymagro']],
                'flash' => [],
            ],
        ],
        'musteriler' => [
            'view' => 'musteriler/index',
            'vars' => [
                'musteriler' => array_map('sahteCari', range(1, 6)),
                'toplam' => 6, 'sayfa' => 1, 'sayfaSayisi' => 1, 'limit' => 50,
                'arama' => '', 'aktifMi' => '', 'eticaretMi' => '', 'sadeceBakiyeli' => false,
                'ozetler' => ['adet' => 6, 'toplam_bakiye' => 1063968.00, 'borclu' => 3, 'alacakli' => 1],
                'flash' => [],
            ],
        ],
        'teklifler' => [
            'view' => 'teklifler/index',
            'vars' => [
                'teklifler' => array_map(fn(int $i): array => sahteFatura($i, 'proforma'), range(1, 5)),
                'toplam' => 5, 'sayfa' => 1, 'sayfaSayisi' => 1, 'limit' => 50,
                'arama' => '', 'flash' => [],
            ],
        ],
        'kullanicilar' => [
            'view' => 'kullanicilar/index',
            'vars' => [
                'kayitlar' => array_map('sahteKullanici', range(1, 4)),
                'roller' => [['id' => 1, 'code' => 'super_admin', 'name' => 'Süper Yönetici', 'is_system' => 1]],
                'filters' => ['q' => '', 'role_id' => 0, 'status' => ''],
                'sirketAdlari' => [1 => ['NYMAGRO TARIM SANAYİ VE TİCARET LİMİTED ŞİRKETİ'], 2 => []],
                'toplam' => 4, 'sayfa' => 1, 'sayfaSayisi' => 1, 'flash' => [],
            ],
        ],
        'kullanici_formu' => [
            'view' => 'kullanicilar/form',
            'vars' => [
                'kullanici' => sahteKullanici(1),
                'roller' => [['id' => 1, 'code' => 'super_admin', 'name' => 'Süper Yönetici', 'is_system' => 1]],
                'sirketler' => [
                    ['id' => 1, 'company_name' => 'NYMAGRO TARIM SANAYİ VE TİCARET LİMİTED ŞİRKETİ', 'short_name' => 'nym', 'status' => 'active'],
                    ['id' => 2, 'company_name' => 'Nuverna Trade', 'short_name' => 'Nuverna', 'status' => 'passive'],
                ],
                'atananlar' => [1 => ['company_id' => 1, 'role' => 'viewer', 'is_default' => 1, 'company_name' => 'NYMAGRO', 'status' => 'active']],
                'sirketRolleri' => ['owner' => 'Sahip', 'viewer' => 'Standart'],
                'flash' => [],
            ],
        ],
        'sirket_secim' => [
            'view' => 'companies/switch',
            'vars' => [
                'companies' => [
                    ['id' => 1, 'company_name' => 'NYMAGRO TARIM SANAYİ VE TİCARET LİMİTED ŞİRKETİ', 'short_name' => 'nym', 'status' => 'active', 'role' => 'owner', 'is_default' => 0, 'active_period_name' => '2026', 'active_period_status' => 'open'],
                    ['id' => 2, 'company_name' => 'Nuverna Trade', 'short_name' => 'Nuverna', 'status' => 'passive', 'role' => 'owner', 'is_default' => 1, 'active_period_name' => '2026', 'active_period_status' => 'open'],
                ],
                'flash' => [],
            ],
        ],
        'rapor' => [
            'view' => 'raporlar/report',
            'vars' => [
                'title' => 'Alışlar Raporu',
                'columns' => [
                    'fatura_tarihi' => 'Tarih', 'fatura_no' => 'Fatura No', 'tedarikci' => 'Tedarikçi',
                    'ara_toplam' => 'Ara Toplam', 'kdv_tutari' => 'KDV', 'genel_toplam' => 'Genel Toplam',
                    'odenen_tutar' => 'Ödenen', 'kalan_tutar' => 'Kalan Borç', 'durum' => 'Durum',
                ],
                'filtersEnabled' => ['date', 'supplier', 'status', 'payment', 'amount'],
                'filters' => [],
                'options' => ['customers' => [], 'suppliers' => [], 'products' => [], 'categories' => [], 'warehouses' => []],
                'rows' => array_map(fn(int $i): array => [
                    'fatura_tarihi' => '2026-07-27', 'fatura_no' => 'NYMA-2026-000004',
                    'tedarikci' => 'ATILGAN GÜMRÜK MÜŞAVİRLİĞİ LTD.ŞTİ.',
                    'ara_toplam' => 29543.00, 'kdv_tutari' => 5908.60, 'genel_toplam' => 35451.60,
                    'odenen_tutar' => 0.00, 'kalan_tutar' => 35451.60, 'durum' => 'onaylandi',
                ], range(1, 4)),
                'summary' => ['Toplam alış tutarı' => '35.451,60 TL', 'Alış faturası sayısı' => 4],
                'note' => '',
                'flash' => [],
            ],
        ],
    ];
}

function sahteCari(int $i): array
{
    return [
        'id' => $i,
        'unvan' => ['MELSABERRY', 'ATILGAN GÜMRÜK MÜŞAVİRLİĞİ LTD.ŞTİ.', 'ANTALYAM ANTREPO LOJİSTİK A.Ş.'][$i % 3],
        'tip' => 'musteri', 'telefon' => '0555 555 55 55', 'cep_telefon' => '0555 555 55 55',
        'eposta' => 'ornek@nuvernatrade.com', 'adres' => 'Finike / Antalya',
        'vergi_dairesi' => 'Finike', 'vergi_no' => '1234567890',
        'bakiye' => 123456.78, 'acik_bakiye' => 123456.78, 'cek_senet_bakiye' => 0.0,
        'para_birimi' => 'TRY', 'aktif_mi' => 1, 'eticaret_mi' => 0,
        'cari_kodu' => 'CR-' . $i, 'sinif_1' => null, 'sinif_2' => null,
        'olusturulma_tarihi' => '2026-01-01 10:00:00',
    ];
}

function sahteKullanici(int $i): array
{
    return [
        'id' => $i,
        'username' => 'kullanici' . $i,
        'email' => 'kullanici' . $i . '@nuvernatrade.com',
        'full_name' => 'Test Kullanıcı ' . $i,
        'phone' => '0555 555 55 55', 'title' => 'Uzman',
        'status' => 'active', 'role_id' => 1, 'role_code' => 'super_admin',
        'role_name' => 'Süper Yönetici', 'last_login_at' => '2026-08-28 22:00:00',
        'created_at' => '2026-01-01 10:00:00', 'locked_until' => null,
    ];
}

// ── Render ────────────────────────────────────────────────────────────
function renderEt(string $viewYolu, array $degiskenler, string $pageTitle, string $activeMenu): string
{
    // 1) Modül view'i
    extract($degiskenler, EXTR_SKIP);
    ob_start();
    include VIEWS_PATH . '/' . $viewYolu . '.php';
    $content = (string)ob_get_clean();

    // 2) Gerçek layout
    ob_start();
    include VIEWS_PATH . '/layout/main.php';
    return (string)ob_get_clean();
}

$yazilan = [];
foreach (sayfalar() as $ad => $tanim) {
    $_GET['url'] = $ad;
    try {
        $html = renderEt($tanim['view'], $tanim['vars'], $ad, $ad);
    } catch (Throwable $e) {
        fwrite(STDERR, "RENDER HATASI [{$ad}]: " . $e->getMessage() . "\n  " . $e->getFile() . ':' . $e->getLine() . "\n");
        exit(1);
    }

    // Uzak kaynakları (font/CDN) dosya:// altında beklememek için kaldır;
    // yerel CSS mutlak yola çevrilir ki tarayıcı GERÇEK stilleri yüklesin.
    $html = preg_replace('#<link[^>]+href="https?://[^"]*"[^>]*>#i', '', $html) ?? $html;
    $html = str_replace('href="/css/', 'href="' . ROOT_PATH . '/public/css/', $html);

    // TÜM script'ler çıkarılır. Test edilen şey YERLEŞİM (CSS) olduğu için
    // script'lere gerek yoktur; ayrıca sayfa içi otomatik form gönderimleri
    // (ör. arama kutusundaki oninput) ölçüm sırasında gezinme tetikleyip
    // testi kararsız hale getirir. Tema (data-theme) zaten sunucu tarafında
    // <body> üzerine basıldığı için CSS değişkenleri script'siz de doğrudur.
    $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html) ?? $html;
    $html = preg_replace('#\son[a-z]+="[^"]*"#i', '', $html) ?? $html;

    // Bootstrap CSS bir CDN'den geliyor ve bu testte yüklenmiyor. Yerleşim
    // ölçümünü bozan TEK yapısal kuralı asgari biçimde yerine koy: Bootstrap
    // modalları varsayılan olarak gizlidir. Bu shim olmadan kapalı modallar
    // "görünür ve dev" sayılıp sahte bulgu üretir. (Sayfaların kendi düzeni
    // Bootstrap'a değil, panel-ui.css + sayfa içi stillere dayanır.)
    $shim = '<style>/* test-shim */ .modal:not(.show){display:none!important}</style>';
    $html = str_replace('</head>', $shim . '</head>', $html);

    $dosya = rtrim($GLOBALS['argv'][1] ?? sys_get_temp_dir() . '/nymagro-mobil', '/') . '/' . $ad . '.html';
    file_put_contents($dosya, $html);
    $yazilan[] = $dosya;
}

echo implode("\n", $yazilan) . "\n";
