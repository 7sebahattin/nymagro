<?php
/**
 * Regresyon testi: Ürün listesi tip (ticari mal / hizmet / tümü) filtresi.
 * --------------------------------------------------------------------------
 * Çalıştırma:  php tests/regression/urun_liste_tip_filtresi_invariants.php
 * Çıkış kodu:  0 = tüm kontroller PASSED, 1 = en az bir FAILED.
 *
 * Arka plan (kullanıcı talebi):
 *   "Ürünler sayfası listesinde varyantı sadece ticari mallar listelensin,
 *    eğer tümü dersek öyle diğerleri de listelensin."
 *
 * urunler_hizmetler tablosu hem ticari malları (tip='urun') hem de
 * hizmet/masraf kalemlerini (tip='hizmet') tutar.
 *
 * Bulunan İKİ hata:
 *   1) Varsayılan sekme ("Aktif Ürünler", tip='') hiç filtre uygulamıyordu;
 *      ticari mallarla hizmetler ("ARDİYE HİZMET BEDELİ" gibi) karışık
 *      listeleniyordu — kullanıcının şikâyet ettiği durum.
 *   2) "Tüm Ürünler" sekmesi tip='all' gönderiyor, model ise bunu doğrudan
 *      "WHERE tip = 'all'" olarak sorguya koyuyordu. 'all' geçerli bir tip
 *      DEĞİLDİR; bu sekme HER ZAMAN boş liste döndürüyordu. Yani sekmelerin
 *      biri fazlasını, diğeri hiçbir şeyi gösteriyordu.
 *
 * Düzeltme: sekme değeri ile SQL koşulu arasındaki eşleme tek bir saf
 * fonksiyonda (Urun::listeTipKosulu) toplandı ve sekmeler oradan üretiliyor.
 *
 * Veritabanı gerektirmez: saf fonksiyon çağrıları + kaynak denetimi.
 */

declare(strict_types=1);

$kok = dirname(__DIR__, 2);
require_once $kok . '/app/models/Urun.php';

/** @var array<int,array{ad:string,ok:bool,detay:string}> */
$sonuclar = [];

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

function kodSadece(string $kaynak): string
{
    $cikti = '';
    foreach (token_get_all($kaynak) as $token) {
        if (is_array($token)) {
            if ($token[0] === T_COMMENT || $token[0] === T_DOC_COMMENT) {
                continue;
            }
            $cikti .= $token[1];
            continue;
        }
        $cikti .= $token;
    }
    return $cikti;
}

function metotGovdesi(string $sinif, string $metot): string
{
    $rm = new ReflectionMethod($sinif, $metot);
    $dosya = $rm->getFileName();
    if ($dosya === false) {
        return '';
    }
    $satirlar = file($dosya);
    return kodSadece('<?php ' . implode('', array_slice($satirlar, $rm->getStartLine() - 1, $rm->getEndLine() - $rm->getStartLine() + 1)));
}

// ═════════════════════════════════════════════════════════════════════
// 1) listeTipKosulu() — TÜM VARYASYONLAR (gerçek çalıştırma)
// ═════════════════════════════════════════════════════════════════════

kontrol("Varsayılan sekme ('') YALNIZCA ticari malları getiriyor (hizmetler karışmıyor)",
    Urun::listeTipKosulu('') === 'urun');
kontrol("'hizmet' sekmesi yalnızca hizmetleri getiriyor",
    Urun::listeTipKosulu('hizmet') === 'hizmet');
kontrol("'all' sekmesi hiçbir tip koşulu uygulamıyor — GERÇEKTEN hepsi listeleniyor",
    Urun::listeTipKosulu('all') === null);
kontrol("'all' artık 'WHERE tip = all' üretmiyor (eski hata: sekme her zaman boş dönüyordu)",
    Urun::listeTipKosulu('all') !== 'all');
kontrol("Tanınmayan bir sekme değeri güvenli varsayılana (ticari mal) düşüyor, sessizce filtresiz kalmıyor",
    Urun::listeTipKosulu('sacmalik') === 'urun' && Urun::listeTipKosulu('urun') === 'urun');

// Dönüş tipi ya null (filtre yok) ya da gerçek bir ENUM değeri olmalı.
foreach (['', 'hizmet', 'all', 'urun', 'zzz'] as $deger) {
    $sonuc = Urun::listeTipKosulu($deger);
    kontrol("listeTipKosulu('{$deger}') ya null ya da geçerli bir tip döndürüyor ('urun'/'hizmet')",
        $sonuc === null || in_array($sonuc, ['urun', 'hizmet'], true));
}

// ═════════════════════════════════════════════════════════════════════
// 2) Sekme tanımları
// ═════════════════════════════════════════════════════════════════════

kontrol('Sekme listesi üç seçenek içeriyor: ticari mal (varsayılan), hizmet, tümü',
    array_keys(Urun::LISTE_TIP_SEKMELERI) === ['', 'hizmet', 'all']);
kontrol('Varsayılan sekme ilk sırada (kullanıcı sayfayı açtığında ticari malları görür)',
    array_key_first(Urun::LISTE_TIP_SEKMELERI) === '');
kontrol('Her sekmenin bir etiketi var',
    count(array_filter(Urun::LISTE_TIP_SEKMELERI, fn($v): bool => is_string($v) && $v !== '')) === 3);
kontrol('Sekme değerlerinin tamamı listeTipKosulu() tarafından tanınıyor (ölü sekme yok)',
    Urun::listeTipKosulu('') === 'urun'
    && Urun::listeTipKosulu('hizmet') === 'hizmet'
    && Urun::listeTipKosulu('all') === null);

// ═════════════════════════════════════════════════════════════════════
// 3) Model sorgusu bu eşlemeyi kullanıyor
// ═════════════════════════════════════════════════════════════════════

$buildWhere = metotGovdesi('Urun', 'buildWhere');
kontrol('Urun::buildWhere() sekme değerini doğrudan SQL\'e koymuyor, listeTipKosulu() üzerinden çeviriyor',
    str_contains($buildWhere, 'listeTipKosulu(') && !preg_match('/\$tip\s*!==\s*\'\'/', $buildWhere));
kontrol('Urun::buildWhere() yalnızca koşul null DEĞİLSE tip filtresi ekliyor',
    (bool)preg_match('/tipKosulu\s*!==\s*null/', $buildWhere));
kontrol('Urun::buildWhere() tip değerini hazırlanmış parametreyle bağlıyor (SQL enjeksiyonu yok)',
    str_contains($buildWhere, "params[':tip']"));

// ═════════════════════════════════════════════════════════════════════
// 4) Görünüm ve controller
// ═════════════════════════════════════════════════════════════════════

$view = oku('app/views/urunler/index.php');
kontrol('Ürün listesi sekmeleri tek kaynaktan (Urun::LISTE_TIP_SEKMELERI) üretiliyor',
    str_contains($view, 'Urun::LISTE_TIP_SEKMELERI'));
kontrol('Görünümde elle yazılmış eski "Aktif Ürünler / Tüm Ürünler" sekmeleri kalmamış',
    !str_contains($view, '>Aktif Ürünler<') && !str_contains($view, '>Tüm Ürünler<'));
kontrol('Sekme etiketleri HTML kaçışından geçiriliyor',
    (bool)preg_match('/htmlspecialchars\(\$sekmeAd\)/', $view));

$ctrl = oku('app/controllers/UrunController.php');
kontrol('UrunController::index() tanınmayan sekme değerini varsayılana düşürüyor',
    (bool)preg_match('/array_key_exists\(\$tipFlt,\s*Urun::LISTE_TIP_SEKMELERI\)/', $ctrl));

// ═════════════════════════════════════════════════════════════════════
// 5) Meta
// ═════════════════════════════════════════════════════════════════════

foreach ([$kok . '/app/models/Urun.php', __FILE__] as $dosya) {
    $kaynak = (string)@file_get_contents($dosya);
    $kapali = false;
    foreach (token_get_all($kaynak) as $token) {
        if (is_array($token) && $token[0] === T_CLOSE_TAG) {
            $kapali = true;
            break;
        }
    }
    kontrol('Meta: ' . basename($dosya) . ' içinde PHP kapanış etiketi yok', !$kapali);
}

// ═════════════════════════════════════════════════════════════════════
// Rapor
// ═════════════════════════════════════════════════════════════════════

$basarili = 0;
$basarisiz = [];
echo "=== Ürün listesi tip filtresi regresyon testi ===\n\n";
foreach ($sonuclar as $s) {
    if ($s['ok']) {
        $basarili++;
        continue;
    }
    $basarisiz[] = $s;
}

echo "Toplam kontrol: " . count($sonuclar) . "\n";
echo "Başarılı:       {$basarili}\n";
echo "Başarısız:      " . count($basarisiz) . "\n\n";

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
