<?php
/**
 * Regresyon testi: satış/alış kalemlerinde ürünün KDV oranı otomatik seçimi.
 * --------------------------------------------------------------------------
 * Çalıştırma:  php tests/regression/urun_kdv_orani_varsayilan_invariants.php
 * Çıkış kodu:  0 = tüm kontroller PASSED, 1 = en az bir FAILED.
 *
 * NEDEN VAR
 * Satış/alış faturasına ürün eklendiğinde KDV% alanı ürünün kendi tanımlı
 * oranıyla değil, çoğu zaman %20 ile geliyordu. İki ayrı kök neden vardı:
 *
 * 1) "0 sahte-boş" hatası — satislar/ekle.php, satislar/duzenle.php,
 *    satislar/perakende.php, alislar/ekle.php, alislar/duzenle.php'nin
 *    hepsinde satır oluşturulurken `parseFloat(urun.kdv_orani) || 20` (veya
 *    `u.kdv_orani || 20`) deseni kullanılıyordu. JavaScript'te 0 falsy
 *    olduğundan, ürünün KDV oranı gerçekten %0 tanımlıysa bile `0 || 20`
 *    ifadesi 20'ye düşüyordu — ürünün kendi tanımı sessizce yok sayılıyordu.
 *
 * 2) Alış tarafında YANLIŞ alan — Fatura::urunAra() (satış VE alış formunun
 *    ortak ürün arama uç noktası, /satis/urunBul) yalnızca `kdv_orani`
 *    (ürünün SATIŞ KDV oranı) döndürüyordu, `alis_kdv_orani` (ALIŞ KDV
 *    oranı) hiç dönmüyordu. Alış formu da aynı `kdv_orani` alanına bakınca,
 *    alış ve satış oranı farklı tanımlanmış bir üründe (örn. alışta %1,
 *    satışta %20) alış kalemine hep ürünün SATIŞ oranı uygulanıyordu.
 *
 * Çözüm: her 5 view'da `kdvOraniCoz(...)` adlı ortak bir yardımcı — ilk
 * tanımlı (undefined/null/boş olmayan) değeri kullanır, hiçbiri yoksa 20'ye
 * düşer — 0'ı GEÇERLİ bir değer olarak korur. Alış view'ları bu yardımcıyı
 * `kdvOraniCoz(u.alis_kdv_orani, u.kdv_orani)` şeklinde çağırır: yeni ürün
 * seçiminde ürünün kendi alış oranı öncelikli, mevcut faturadan yüklenen bir
 * kalemde (bu alan o nesnede hiç yok) kalemin kendi kayıtlı oranına düşülür.
 *
 * Yapısal kontroller kaynağı metin olarak denetler; dinamik kontroller her
 * dosyadan GERÇEK kdvOraniCoz fonksiyon gövdesini ayıklayıp (kopya yeniden
 * yazılmaz) Node üzerinde çalıştırır — node yoksa bu bölüm atlanır (bkz.
 * tests/regression/mobil_uyum_invariants.php'deki aynı desen).
 */

$kok = dirname(__DIR__, 2);

/** @var array<int,array{ad:string,ok:bool,detay:string}> */
$sonuclar = [];
/** @var array<int,string> */
$atlananlar = [];

function kontrol(string $ad, bool $gecti, string $detay = ''): void
{
    global $sonuclar;
    $sonuclar[] = ['ad' => $ad, 'ok' => $gecti, 'detay' => $detay];
}

function oku(string $yol): string
{
    $icerik = @file_get_contents($yol);
    return $icerik === false ? '' : $icerik;
}

/** Balanced-brace ile bir JS fonksiyon gövdesini ayıklar (bkz.
 *  yazdirma_koyu_tema_invariants.php'deki aynı yaklaşım). */
function jsFonksiyonGovdesiAyikla(string $kaynak, string $desen): string
{
    if (!preg_match($desen, $kaynak, $m, PREG_OFFSET_CAPTURE)) {
        return '';
    }
    $bas = $m[0][1] + strlen($m[0][0]);
    $derinlik = 1;
    $uz = strlen($kaynak);
    for ($i = $bas; $i < $uz; $i++) {
        if ($kaynak[$i] === '{') { $derinlik++; }
        elseif ($kaynak[$i] === '}') {
            $derinlik--;
            if ($derinlik === 0) {
                return substr($kaynak, $bas, $i - $bas);
            }
        }
    }
    return '';
}

function komutVar(string $komut): bool
{
    $cikti = [];
    $kod = 0;
    @exec('command -v ' . escapeshellarg($komut) . ' 2>/dev/null', $cikti, $kod);
    return $kod === 0 && !empty($cikti);
}

$dosyalar = [
    'satislar/ekle.php'      => $kok . '/app/views/satislar/ekle.php',
    'satislar/duzenle.php'   => $kok . '/app/views/satislar/duzenle.php',
    'satislar/perakende.php' => $kok . '/app/views/satislar/perakende.php',
    'alislar/ekle.php'       => $kok . '/app/views/alislar/ekle.php',
    'alislar/duzenle.php'    => $kok . '/app/views/alislar/duzenle.php',
];
$modelYolu = $kok . '/app/models/Fatura.php';

$icerikler = array_map('oku', $dosyalar);
$modelIcerik = oku($modelYolu);

kontrol(
    'Kaynak dosyaların hepsi okunabildi',
    $modelIcerik !== '' && !in_array('', $icerikler, true)
);

// ─────────────────────────────────────────────────────────────────────────
// 1) Fatura::urunAra() artık alis_kdv_orani da döndürüyor
// ─────────────────────────────────────────────────────────────────────────
if (preg_match('/public function urunAra\(.*?\{(.*?)\n    \}/s', $modelIcerik, $m)) {
    $govde = $m[1];
    kontrol(
        'urunAra() SELECT listesinde alis_kdv_orani var',
        (bool)preg_match('/SELECT[^"]*\balis_kdv_orani\b/s', $govde),
        'satış+alış ortak ürün arama uç noktası hâlâ yalnızca satış KDV oranını döndürüyor'
    );
    kontrol(
        'urunAra() SELECT listesinde kdv_orani (satış oranı) hâlâ duruyor',
        (bool)preg_match('/SELECT[^"]*\bkdv_orani\b/s', $govde),
        'satış tarafının kullandığı alan kaldırılmış olabilir'
    );
} else {
    kontrol('urunAra() metodu bulunabildi', false, 'metot yapısı değişmiş olabilir — test güncellenmeli');
}

// ─────────────────────────────────────────────────────────────────────────
// 2) Hiçbir view'da eski "0 sahte-boş" deseni kalmamış
// ─────────────────────────────────────────────────────────────────────────
$eskiDesen = '/kdv_orani\)?\s*\|\|\s*20/';
foreach ($dosyalar as $etiket => $yol) {
    kontrol(
        "{$etiket}: eski `kdv_orani || 20` deseni kalmamış",
        !preg_match($eskiDesen, $icerikler[$etiket]),
        '`||` kısayolu geri gelmiş — ürünün %0 KDV tanımı yine 20\'ye düşecek'
    );
}

// ─────────────────────────────────────────────────────────────────────────
// 3) Alış view'ları önce ürünün ALIŞ KDV oranına bakıyor
// ─────────────────────────────────────────────────────────────────────────
foreach (['alislar/ekle.php', 'alislar/duzenle.php'] as $etiket) {
    kontrol(
        "{$etiket}: kdv alanı önce alis_kdv_orani'ye bakıyor",
        (bool)preg_match('/kdvOraniCoz\(\s*u\.alis_kdv_orani\s*,\s*u\.kdv_orani\s*\)/', $icerikler[$etiket]),
        'alış kalemi hâlâ ürünün satış KDV oranını (kdv_orani) kullanıyor olabilir'
    );
}
foreach (['satislar/ekle.php', 'satislar/duzenle.php'] as $etiket) {
    kontrol(
        "{$etiket}: kdv alanı kdv_orani (satış oranı) kullanıyor",
        (bool)preg_match('/kdvOraniCoz\(\s*urun\.kdv_orani\s*\)/', $icerikler[$etiket]),
        'beklenen çağrı deseni bulunamadı — view yeniden yazılmış olabilir'
    );
}

// ─────────────────────────────────────────────────────────────────────────
// 4) Dinamik: her dosyadaki GERÇEK kdvOraniCoz gövdesini Node'da çalıştır
// ─────────────────────────────────────────────────────────────────────────
if (!komutVar('node')) {
    $atlananlar[] = 'kdvOraniCoz() davranış testi atlandı: node bulunamadı.';
} else {
    foreach ($dosyalar as $etiket => $yol) {
        $govde = jsFonksiyonGovdesiAyikla(
            $icerikler[$etiket],
            '/function\s+kdvOraniCoz\s*\([^)]*\)\s*\{/'
        );
        if ($govde === '') {
            kontrol("{$etiket}: kdvOraniCoz() fonksiyonu ayıklanabildi", false, 'fonksiyon bulunamadı');
            continue;
        }

        // Fonksiyonun tam imzasını (rest-parametre mi tekli mi) da ayıklamamız
        // gerekiyor — perakende.php tekli parametre kullanıyor.
        preg_match('/function\s+kdvOraniCoz\s*(\([^)]*\))\s*\{/', $icerikler[$etiket], $imza);
        $parametreler = $imza[1] ?? '(deger)';

        $js = <<<JS
        function kdvOraniCoz{$parametreler} {{$govde}}

        const vakalar = [
          ['0.00 (string, DB) korunmalı',        ['0.00'],          0],
          ['0 (number) korunmalı',               [0],               0],
          ['20.00 normal değer',                 ['20.00'],         20],
          ['tanımsız → varsayılan 20',           [undefined],       20],
        ];

        let hata = 0;
        for (const [ad, girdi, beklenen] of vakalar) {
          const sonuc = kdvOraniCoz(...girdi);
          if (sonuc !== beklenen) {
            console.log('FAIL: ' + ad + ' -> ' + sonuc + ' (beklenen ' + beklenen + ')');
            hata++;
          }
        }
        process.exit(hata ? 1 : 0);
        JS;

        $tmp = tempnam(sys_get_temp_dir(), 'nym-kdv-') . '.js';
        file_put_contents($tmp, $js);
        $cikti = [];
        $kodu = 0;
        @exec('node ' . escapeshellarg($tmp) . ' 2>&1', $cikti, $kodu);
        @unlink($tmp);

        kontrol(
            "{$etiket}: kdvOraniCoz() %0 dahil doğru değeri koruyor",
            $kodu === 0,
            implode(' | ', $cikti)
        );
    }

    // Alış view'larında ayrıca "alış oranı önce" davranışı doğrulanır.
    foreach (['alislar/ekle.php', 'alislar/duzenle.php'] as $etiket) {
        $govde = jsFonksiyonGovdesiAyikla(
            $icerikler[$etiket],
            '/function\s+kdvOraniCoz\s*\([^)]*\)\s*\{/'
        );
        if ($govde === '') {
            continue; // yukarıdaki kontrol zaten başarısız oldu
        }
        $js = <<<JS
        function kdvOraniCoz(...degerler) {{$govde}}

        const vakalar = [
          ['alış oranı %1, satış oranı %20 -> alış öncelikli', ['1.00', '20.00'], 1],
          ['yeni ürün: alış oranı yok, satış oranı %20 -> son çare olarak satış', [undefined, '20.00'], 20],
          ['mevcut kalem: alış oranı alanı hiç yok, kalemin kendi %0 oranı korunmalı', [undefined, '0.00'], 0],
        ];

        let hata = 0;
        for (const [ad, girdi, beklenen] of vakalar) {
          const sonuc = kdvOraniCoz(...girdi);
          if (sonuc !== beklenen) {
            console.log('FAIL: ' + ad + ' -> ' + sonuc + ' (beklenen ' + beklenen + ')');
            hata++;
          }
        }
        process.exit(hata ? 1 : 0);
        JS;

        $tmp = tempnam(sys_get_temp_dir(), 'nym-kdv-alis-') . '.js';
        file_put_contents($tmp, $js);
        $cikti = [];
        $kodu = 0;
        @exec('node ' . escapeshellarg($tmp) . ' 2>&1', $cikti, $kodu);
        @unlink($tmp);

        kontrol(
            "{$etiket}: kdvOraniCoz() alış oranını satış oranına tercih ediyor",
            $kodu === 0,
            implode(' | ', $cikti)
        );
    }
}

// ─────────────────────────────────────────────────────────────────────────
// Rapor
// ─────────────────────────────────────────────────────────────────────────
$gecen = array_values(array_filter($sonuclar, static fn(array $s): bool => $s['ok']));
$kalan = array_values(array_filter($sonuclar, static fn(array $s): bool => !$s['ok']));

echo "=== Ürün KDV oranı varsayılanı regresyon taraması ===\n";
echo 'Kontrol sayısı: ' . count($sonuclar) . "\n\n";

foreach ($sonuclar as $s) {
    echo ($s['ok'] ? '  [OK]   ' : '  [HATA] ') . $s['ad'] . "\n";
    if (!$s['ok'] && $s['detay'] !== '') {
        echo '         → ' . $s['detay'] . "\n";
    }
}
foreach ($atlananlar as $a) {
    echo '  [ATLANDI] ' . $a . "\n";
}
echo "\n";

if (empty($kalan)) {
    echo 'PASSED - ' . count($gecen) . " kontrolün tamamı geçti.\n";
    exit(0);
}

echo 'FAILED - ' . count($kalan) . ' kontrol başarısız (' . count($gecen) . " geçti).\n";
exit(1);
