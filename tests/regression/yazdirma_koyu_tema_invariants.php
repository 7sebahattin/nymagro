<?php
/**
 * Regresyon testi: koyu temada yazdırma çıktısı okunabilir kalsın.
 * --------------------------------------------------------------------------
 * Çalıştırma:  php tests/regression/yazdirma_koyu_tema_invariants.php
 * Çıkış kodu:  0 = tüm kontroller PASSED, 1 = en az bir FAILED.
 *
 * NEDEN VAR
 * Koyu tema varsayılan (panel-ui.css, :root). Birçok sayfanın kendi
 * @media print bloğu ekran teması için tanımlı var(--text)/var(--card-bg)
 * gibi CSS özel özelliklerini kullanıyordu; bu değişkenler koyu temada
 * beyaza yakın olduğundan yazdırma çıktısı beyaz kağıtta beyaza yakın
 * metin üretiyor, pratikte okunamıyordu.
 *
 * Çözüm iki katman: (1) panel-ui.css'in @media print bloğunda tema
 * değişkenlerinin kendisi print-safe sabit değerlere zorlanıyor —
 * var(--...) kullanan HER sayfa otomatik düzelir; (2) panel-ui.js'teki
 * window.nymYazdir() yazdırmadan önce temayı geçici olarak açığa çevirip
 * sonra geri alıyor, tarayıcı/print sürücüsü tutarsız davranırsa diye
 * ikinci güvenlik katmanı.
 *
 * Bu testler YAPISALdır (veritabanı/tarayıcı gerektirmez, CI'da çalışır).
 * Amaç şu geri dönüşleri yakalamak:
 *   - panel-ui.css'teki print değişken override bloğunun silinmesi/daralması,
 *   - nymYazdir()'in kaldırılması veya tema geri alma mantığının bozulması,
 *   - düzeltilmiş sayfalarda in-page yazdırma butonlarının tekrar
 *     window.print() çağırmaya dönmesi,
 *   - masraf makbuzu gibi izole popup şablonlarında var(--...) kullanımının
 *     geri gelmesi (CSS özel özellikleri pencereler arası miras alınmaz).
 *
 * Görsel/fiili kontrast doğrulaması (gerçek tarayıcıda print media
 * emulation + getComputedStyle) bu depoda Node/Playwright bağımlılığı
 * olmadığı için buraya konmadı; bu test yalnızca kaynağın "doğru
 * çözümü kullanmaya devam ettiğini" denetler.
 */

$kok = dirname(__DIR__, 2);

/** @var array<int,array{ad:string,ok:bool,detay:string}> */
$sonuclar = [];

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

/** Bir JS fonksiyon atamasının (window.x = function () { ... }) gövdesini
 *  süslü parantez sayarak ayıklar. metodGovdesi() ile aynı yaklaşım. */
function jsFonksiyonGovdesi(string $kaynak, string $atamaDeseni): string
{
    if (!preg_match($atamaDeseni, $kaynak, $m, PREG_OFFSET_CAPTURE)) {
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
    return substr($kaynak, $bas);
}

$cssYolu = $kok . '/public/css/panel-ui.css';
$jsYolu  = $kok . '/public/js/panel-ui.js';
$css     = oku($cssYolu);
$js      = oku($jsYolu);

kontrol('Kaynak dosyalar okunabildi (panel-ui.css, panel-ui.js)', $css !== '' && $js !== '');

// ─────────────────────────────────────────────────────────────────────────
// 1) Kök çözüm: panel-ui.css @media print bloğu tema değişkenlerini
//    print-safe sabit değerlere zorluyor mu?
// ─────────────────────────────────────────────────────────────────────────
if (preg_match('/@media\s+print\s*\{(.*)/s', $css, $m)) {
    $printBlok = $m[1];

    // İlk kural :root, body { ... } olmalı (aksi halde miras/kademe farklı
    // davranabilir — bkz. dosya başındaki açıklama).
    $kokKurali = '';
    if (preg_match('/:root\s*,\s*body\s*\{([^}]*)\}/s', $printBlok, $km)) {
        $kokKurali = $km[1];
    }
    kontrol(
        'panel-ui.css @media print içinde :root, body { ... } override kuralı var',
        $kokKurali !== '',
        ':root, body bloğu bulunamadı — print-safe değişken override kaldırılmış olabilir'
    );

    $zorunluDegiskenler = ['--text', '--text2', '--card-bg', '--border', '--muted', '--surface'];
    foreach ($zorunluDegiskenler as $degisken) {
        $desen = '/' . preg_quote($degisken, '/') . '\s*:\s*#[0-9a-fA-F]{3,8}\s*!important\s*;/';
        kontrol(
            "panel-ui.css print bloğu {$degisken} değişkenini print-safe renge (!important) sabitliyor",
            $kokKurali !== '' && preg_match($desen, $kokKurali) === 1,
            "{$degisken} için !important hex override bulunamadı"
        );
    }
} else {
    kontrol('panel-ui.css içinde @media print bloğu var', false, '@media print bulunamadı');
}

// ─────────────────────────────────────────────────────────────────────────
// 2) İkinci katman: window.nymYazdir() var ve temayı geçici değiştirip
//    geri alıyor mu?
// ─────────────────────────────────────────────────────────────────────────
$govdeYaklasik = jsFonksiyonGovdesi($js, '/window\.nymYazdir\s*=\s*function\s*\([^)]*\)\s*\{/');
if ($govdeYaklasik !== '') {
    kontrol(
        "nymYazdir() body[data-theme]'i geçici olarak 'acik' yapıyor",
        (bool) preg_match('/setAttribute\(\s*[\'"]data-theme[\'"]\s*,\s*[\'"]acik[\'"]\s*\)/', $govdeYaklasik)
    );
    kontrol(
        'nymYazdir() afterprint olayında temayı geri alıyor',
        str_contains($govdeYaklasik, "addEventListener('afterprint'") || str_contains($govdeYaklasik, '"afterprint"'),
        "afterprint dinleyicisi bulunamadı — yazdırma sonrası kullanıcının gerçek teması geri yüklenmeyebilir"
    );
    kontrol(
        'nymYazdir() gerçekten window.print() çağırıyor',
        str_contains($govdeYaklasik, 'window.print()')
    );
    kontrol(
        'nymYazdir() localStorage/cookie tema tercihine dokunmuyor (yalnızca body attribute)',
        !str_contains($govdeYaklasik, 'localStorage') && !str_contains($govdeYaklasik, 'document.cookie'),
        'localStorage veya cookie erişimi bulundu — kullanıcının kalıcı tema tercihi bozuluyor olabilir'
    );
} else {
    kontrol('panel-ui.js içinde window.nymYazdir fonksiyonu tanımlı', false, 'window.nymYazdir = function tanımı bulunamadı');
}

// ─────────────────────────────────────────────────────────────────────────
// 3) Daha önce window.print()'i doğrudan çağırdığı için düzeltilen
//    in-page yazdırma noktaları: window.print()'e geri dönmemeli,
//    nymYazdir() kullanmalı.
// ─────────────────────────────────────────────────────────────────────────
$duzeltilenDosyalar = [
    'app/views/alislar/detay.php',
    'app/views/ekstre/cari.php',
    'app/views/raporlar/musteri.php',
    'app/views/raporlar/report.php',
    'app/views/takvim/index.php',
];
foreach ($duzeltilenDosyalar as $goreliYol) {
    $icerik = oku($kok . '/' . $goreliYol);
    kontrol("{$goreliYol} okunabildi", $icerik !== '');

    $hamPrint = preg_match('/onclick\s*=\s*"window\.print\(\)"/', $icerik) === 1
        || preg_match('/\bwindow\.addEventListener\([^)]*function\s*\([^)]*\)\s*\{\s*window\.print\(\)/', $icerik) === 1;
    kontrol(
        "{$goreliYol} artık doğrudan window.print() çağırmıyor",
        !$hamPrint,
        'ham window.print() çağrısı bulundu — nymYazdir() ile değiştirilen bir çağrı geri gelmiş olabilir'
    );
    kontrol(
        "{$goreliYol} nymYazdir() kullanıyor",
        str_contains($icerik, 'nymYazdir()'),
        'nymYazdir() çağrısı bulunamadı'
    );
}

// ─────────────────────────────────────────────────────────────────────────
// 4) İzole popup + document.write mimarisi (Tediye Makbuzu): CSS özel
//    özellikleri pencereler arası miras alınmaz, bu yüzden var(--...)
//    kullanımı burada asla güvenli olmaz — sabit hex renk kalmalı.
// ─────────────────────────────────────────────────────────────────────────
$masrafYolu = $kok . '/app/views/masraflar/index.php';
$masraf     = oku($masrafYolu);
kontrol('app/views/masraflar/index.php okunabildi', $masraf !== '');

if ($masraf !== '' && preg_match('/const\s+html\s*=\s*`(.*?)`;\s*\n\s*const\s+win\s*=\s*window\.open/s', $masraf, $m)) {
    $makbuzSablonu = $m[1];
    kontrol(
        'Tediye Makbuzu şablonunda var(--...) tema değişkeni kullanılmıyor',
        !str_contains($makbuzSablonu, 'var(--'),
        'İzole popup belgesinde var(--...) bulundu — bu değişkenler pencereler arası miras alınmadığı için tanımsız kalır (B-print geri gelmiş olabilir)'
    );
} else {
    kontrol(
        'Tediye Makbuzu HTML şablonu (const html = `...`) bulunabildi',
        false,
        'şablon değişkeni bulunamadı — dosya yapısı değişmiş olabilir, test güncellenmeli'
    );
}

// ─────────────────────────────────────────────────────────────────────────
// Rapor
// ─────────────────────────────────────────────────────────────────────────
$gecen = array_values(array_filter($sonuclar, static fn(array $s): bool => $s['ok']));
$kalan = array_values(array_filter($sonuclar, static fn(array $s): bool => !$s['ok']));

echo "=== Koyu temada yazdırma okunabilirliği regresyon taraması ===\n";
echo 'Kontrol sayısı: ' . count($sonuclar) . "\n\n";

foreach ($sonuclar as $s) {
    echo ($s['ok'] ? '  [OK]   ' : '  [HATA] ') . $s['ad'] . "\n";
    if (!$s['ok'] && $s['detay'] !== '') {
        echo '         → ' . $s['detay'] . "\n";
    }
}
echo "\n";

if (empty($kalan)) {
    echo 'PASSED - ' . count($gecen) . " kontrolün tamamı geçti.\n";
    exit(0);
}

echo 'FAILED - ' . count($kalan) . ' kontrol başarısız (' . count($gecen) . " geçti).\n";
exit(1);
