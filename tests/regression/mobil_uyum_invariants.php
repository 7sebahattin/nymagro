<?php
/**
 * Regresyon testi: Mobil yerleşim uyumu.
 * --------------------------------------------------------------------------
 * Çalıştırma:  php tests/regression/mobil_uyum_invariants.php
 * Çıkış kodu:  0 = tüm kontroller PASSED, 1 = en az bir FAILED.
 *
 * Arka plan (kullanıcının bildirdiği hata):
 *   "Mobilde satış sayfasında alt bar alta kayıyor."
 *
 * Kök neden:
 *   Satışlar sayfasındaki belge tipi sekmeleri (Faturalar / İrsaliyeler /
 *   Perakende / Numuneler) tek satırda ~412px yer kaplıyordu. 360px'lik bir
 *   telefonda bu YATAY TAŞMA yaratır. Yatay taşma olduğunda tarayıcının
 *   yerleşim görünümü (layout viewport) ekrandan geniş olur; `position:fixed;
 *   left:0; right:0` olan .bottom-nav de bu geniş alana yayılıp görünür
 *   alanın dışına taşar. Yani "alt bar kayması" bir alt-bar hatası DEĞİL,
 *   sayfanın yatay taşmasının SONUCUYDU.
 *
 * Çözüm:
 *   Sekme şeridi .tab-scroll ile kendi içinde yatay kaydırılır; sayfa gövdesi
 *   artık ekrandan geniş olmaz.
 *
 * Bu test iki katmanlıdır:
 *   1) STATİK kontroller — her ortamda (CI dahil) çalışır.
 *   2) GERÇEK TARAYICI ölçümü — Node + Playwright + Chromium varsa çalışır;
 *      yoksa atlanır (CI'da PHP-only olduğu için sessizce atlanması normaldir,
 *      çıktıda açıkça belirtilir).
 */

declare(strict_types=1);

$kok = dirname(__DIR__, 2);

/** @var array<int,array{ad:string,ok:bool,detay:string}> */
$sonuclar = [];
$atlananlar = [];

function kontrol(string $ad, bool $gecti, string $detay = ''): void
{
    global $sonuclar;
    $sonuclar[] = ['ad' => $ad, 'ok' => $gecti, 'detay' => $detay];
}

function oku(string $goreliYol): string
{
    global $kok;
    return (string)@file_get_contents($kok . '/' . $goreliYol);
}

// ═════════════════════════════════════════════════════════════════════
// 1) Statik: .tab-scroll bileşeni ve kullanımı
// ═════════════════════════════════════════════════════════════════════

$css = oku('public/css/panel-ui.css');

kontrol('panel-ui.css: .tab-scroll bileşeni tanımlı',
    (bool)preg_match('/\.tab-scroll\s*\{/', $css));
kontrol('panel-ui.css: .tab-scroll kendi içinde yatay kaydırılıyor (overflow-x:auto)',
    (bool)preg_match('/\.tab-scroll\s*\{[^}]*overflow-x:\s*auto/s', $css));
kontrol('panel-ui.css: .tab-scroll içindeki sekmeler daralmıyor ve satır kırmıyor (flex:0 0 auto + nowrap)',
    (bool)preg_match('/\.tab-scroll\s*>\s*\*\s*\{[^}]*flex:\s*0 0 auto[^}]*white-space:\s*nowrap/s', $css));

// Sekme şeritleri artık kaydırılabilir kapsayıcı kullanmalı.
foreach (['app/views/satislar/index.php', 'app/views/alislar/index.php'] as $yol) {
    $kaynak = oku($yol);
    $ad = basename(dirname($yol)) . '/' . basename($yol);
    kontrol("{$ad}: belge tipi sekme şeridi .tab-scroll kullanıyor",
        (bool)preg_match('/<div class="tab-scroll"[^>]*border-bottom:1px solid var\(--border\)/', $kaynak));
    kontrol("{$ad}: eski (kaydırılamayan) satır içi flex sekme şeridi geri gelmemiş",
        !(bool)preg_match('/<div style="display:flex;\s*gap:\d+px;[^"]*border-bottom:1px solid var\(--border\)/', $kaynak));
}

// ═════════════════════════════════════════════════════════════════════
// 2) Statik: sabit alt navigasyonun temel kuralları
// ═════════════════════════════════════════════════════════════════════

kontrol('panel-ui.css: .bottom-nav ekranın altına sabitlenmiş (position:fixed; bottom:0)',
    (bool)preg_match('/\.bottom-nav\s*\{[^}]*position:\s*fixed[^}]*bottom:\s*0/s', $css));
kontrol('panel-ui.css: .bottom-nav mobilde görünür kılınıyor (<768px)',
    (bool)preg_match('/@media\s*\(max-width:\s*767\.98px\)\s*\{.*\.bottom-nav\s*\{\s*display:\s*block/s', $css));
kontrol('panel-ui.css: mobilde içerik alt barın altında kalmasın diye .main-wrap alt boşluğu bar yüksekliğini kapsıyor',
    (bool)preg_match('/@media\s*\(max-width:\s*767\.98px\)\s*\{.*\.main-wrap\s*\{[^}]*padding-bottom:\s*calc\([^)]*--bnav-h/s', $css));
kontrol('panel-ui.css: gövde yatay taşmayı gizliyor (ek güvenlik ağı)',
    (bool)preg_match('/\bbody\s*\{[^}]*overflow-x:\s*hidden/s', $css));

// ═════════════════════════════════════════════════════════════════════
// 3) Statik: mobil görünüm meta etiketi
// ═════════════════════════════════════════════════════════════════════

$layout = oku('app/views/layout/main.php');
kontrol('main.php: mobil viewport meta etiketi var (width=device-width)',
    str_contains($layout, 'name="viewport"') && str_contains($layout, 'width=device-width'));
kontrol('main.php: kullanıcı yakınlaştırması engellenmemiş (erişilebilirlik: user-scalable=no / maximum-scale=1 yok)',
    !str_contains($layout, 'user-scalable=no') && !preg_match('/maximum-scale=1(?![\d.])/', $layout));

// ═════════════════════════════════════════════════════════════════════
// 4) Gerçek tarayıcı ölçümü (Node + Playwright varsa)
// ═════════════════════════════════════════════════════════════════════

/** Komut çalıştırılabiliyor mu? */
function komutVar(string $komut): bool
{
    $cikti = [];
    $kod = 0;
    @exec('command -v ' . escapeshellarg($komut) . ' 2>/dev/null', $cikti, $kod);
    return $kod === 0 && !empty($cikti);
}

$tarayiciTesti = false;
if (!komutVar('node')) {
    $atlananlar[] = 'Gerçek tarayıcı ölçümü atlandı: node bulunamadı.';
} else {
    $ciktiDizini = sys_get_temp_dir() . '/nymagro-mobil-' . getmypid();
    @mkdir($ciktiDizini, 0777, true);

    $renderCikti = [];
    $renderKod = 0;
    @exec(
        escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($kok . '/tests/mobile/render.php')
        . ' ' . escapeshellarg($ciktiDizini) . ' 2>&1',
        $renderCikti,
        $renderKod
    );
    kontrol('Mobil test sayfaları gerçek layout + gerçek view\'lerle render edilebiliyor',
        $renderKod === 0, implode("\n      ", array_slice($renderCikti, 0, 6)));

    if ($renderKod === 0) {
        $nodeCikti = [];
        $nodeKod = 0;
        @exec(
            'node ' . escapeshellarg($kok . '/tests/mobile/check.mjs')
            . ' ' . escapeshellarg($ciktiDizini) . ' 2>/dev/null',
            $nodeCikti,
            $nodeKod
        );
        $ham = trim(implode("\n", $nodeCikti));
        $veri = $ham !== '' ? json_decode($ham, true) : null;

        if (!is_array($veri)) {
            $atlananlar[] = 'Gerçek tarayıcı ölçümü atlandı: check.mjs çıktısı okunamadı (Playwright/Chromium yok olabilir).';
        } elseif (isset($veri['atlandi'])) {
            $atlananlar[] = 'Gerçek tarayıcı ölçümü atlandı: ' . $veri['atlandi'];
        } else {
            $tarayiciTesti = true;
            $bulgular = $veri['bulgular'] ?? [];

            // Bulgu türlerine göre ayrı ayrı raporla — hangi sınıf hatanın
            // geri geldiği tek bakışta görünsün.
            $turler = [
                'yatay-tasma' => 'Hiçbir sayfa mobil ekrandan geniş değil (yatay taşma yok)',
                'erisilemez-etkilesimli-oge' => 'Ekran dışında kalıp ulaşılamayan bağlantı/buton yok',
                'alt-nav-yok' => 'Her sayfada mobil alt navigasyon var',
                'alt-nav-gizli' => 'Mobil alt navigasyon hiçbir sayfada gizlenmiyor',
                'alt-nav-sabit-degil' => 'Mobil alt navigasyon her sayfada sabit (fixed) konumlu',
                'alt-nav-ekran-disi' => 'Mobil alt navigasyon hiçbir sayfada ekranın altına kaymıyor',
                'alt-nav-yatay-kaymis' => 'Mobil alt navigasyon hiçbir sayfada yatayda ekran dışına taşmıyor',
                'alt-nav-kaydirinca-kayiyor' => 'En alta kaydırınca da alt navigasyon yerinde kalıyor',
                'icerik-alt-barin-altinda' => 'Hiçbir sayfada içerik alt navigasyonun altında kalmıyor',
                'okunmayan-metin' => 'Her iki temada da metin zeminden ayırt edilebiliyor (kontrast ≥ 2.0)',
                'kucuk-dokunma-hedefi' => 'Parmakla ıskalanacak kadar küçük (<30px) kontrol yok',
            ];
            foreach ($turler as $tur => $baslik) {
                $eslesen = array_values(array_filter($bulgular, fn(array $b): bool => ($b['tur'] ?? '') === $tur));
                $detay = implode("\n      ", array_map(fn(array $b): string => (string)($b['mesaj'] ?? ''), array_slice($eslesen, 0, 6)));
                kontrol('Tarayıcı ölçümü: ' . $baslik, empty($eslesen), $detay);
            }

            // "bilgi-" ile başlayan türler bilinçli olarak testi DÜŞÜRMEZ:
            // bunlar WCAG AA eşiğinin altındaki marka butonları (2.0–4.5
            // kontrast) ve 30–44px arası dokunma hedefleridir — okunur ve
            // kullanılabilir durumdalar, ama iyileştirilebilirler. Sayıları
            // raporda görünür ki zamanla azaltılabilsin.
            $bilgiler = array_values(array_filter($bulgular, fn(array $b): bool => str_starts_with($b['tur'] ?? '', 'bilgi-')));
            if ($bilgiler) {
                $atlananlar[] = 'Bilgi (testi düşürmez): ' . count($bilgiler)
                    . ' iyileştirme önerisi — düşük kontrastlı marka butonları ve 30–44px arası dokunma hedefleri.';
            }

            // Beklenmeyen bir bulgu türü çıkarsa da testi düşür (sessiz geçmesin).
            $bilinen = array_keys($turler);
            $digerleri = array_values(array_filter(
                $bulgular,
                fn(array $b): bool => !in_array($b['tur'] ?? '', $bilinen, true) && !str_starts_with($b['tur'] ?? '', 'bilgi-')
            ));
            kontrol('Tarayıcı ölçümü: sınıflandırılmamış mobil bulgusu yok',
                empty($digerleri),
                implode("\n      ", array_map(fn(array $b): string => (string)($b['mesaj'] ?? ''), $digerleri)));
        }
    }

    // Geçici dosyaları temizle
    foreach (glob($ciktiDizini . '/*.html') ?: [] as $f) {
        @unlink($f);
    }
    @rmdir($ciktiDizini);
}

// ═════════════════════════════════════════════════════════════════════
// 5) Meta
// ═════════════════════════════════════════════════════════════════════

$kaynak = (string)@file_get_contents(__FILE__);
$kapali = false;
foreach (token_get_all($kaynak) as $token) {
    if (is_array($token) && $token[0] === T_CLOSE_TAG) {
        $kapali = true;
        break;
    }
}
kontrol('Meta: bu test dosyasında PHP kapanış etiketi yok', !$kapali);

// ═════════════════════════════════════════════════════════════════════
// Rapor
// ═════════════════════════════════════════════════════════════════════

$basarili = 0;
$basarisiz = [];
echo "=== Mobil yerleşim uyumu regresyon testi ===\n\n";
foreach ($sonuclar as $s) {
    if ($s['ok']) {
        $basarili++;
        continue;
    }
    $basarisiz[] = $s;
}

echo "Toplam kontrol: " . count($sonuclar) . "\n";
echo "Başarılı:       {$basarili}\n";
echo "Başarısız:      " . count($basarisiz) . "\n";
echo "Tarayıcı ölçümü: " . ($tarayiciTesti ? 'ÇALIŞTI (Chromium)' : 'ATLANDI') . "\n";
foreach ($atlananlar as $a) {
    echo "  · {$a}\n";
}
echo "\n";

if (empty($basarisiz)) {
    echo "PASSED - Tüm kontroller geçti.\n";
    exit(0);
}

echo "FAILED - Aşağıdaki kontroller başarısız:\n\n";
foreach ($basarisiz as $s) {
    echo "  - {$s['ad']}\n";
    if ($s['detay'] !== '') {
        echo "      {$s['detay']}\n";
    }
}
exit(1);
