<?php
/**
 * Regresyon testi: KasaHesap::hareketEkle()/hareketSil() → Fatura FIFO senkronu.
 * --------------------------------------------------------------------------
 * Çalıştırma:  php tests/regression/kasahesap_fifo_invariants.php
 * Çıkış kodu:  0 = tüm kontroller PASSED, 1 = en az bir FAILED.
 *
 * Arka plan: "Hesaplarım > [Hesap] > Para Girişi/Çıkışı Yap" ekranı, Nakit
 * modelini DEĞİL, ayrı (kopya) bir uygulama olan KasaHesap::hareketEkle()'yi
 * kullanıyordu. Nakit::hareketEkle()'ye eklenen FIFO dağıtım + cari bakiye
 * düzeltmesi bu ikinci yola hiç eklenmemişti — bu yüzden bu ekrandan bir
 * cariye tahsilat/ödeme girildiğinde ne faturalar.kalan_tutar ne de
 * cariler.bakiye güncelleniyordu (gerçek örnek: bir müşteriye "İş Bank
 * Sanal POS (Link)" hesabından tam tutar tahsilat girildi, cariler.bakiye
 * doğru 0'landı ama faturanın "Kalan" sütunu değişmeden kaldı — çünkü o
 * ekran farklı bir modele/koda gidiyordu).
 *
 * KORUNAN İLKELER
 *  1) KasaHesap::hareketEkle() artık — cari_id varsa — Nakit::hareketEkle()
 *     ile AYNI iki adımı (fifoOdemeDagit + recomputeCariBalance) uyguluyor.
 *  2) KasaHesap::hareketSil() artık — silinen hareketin cari_id'si varsa —
 *     carinin faturalarını fifoBakiyeleriYenidenHesapla() ile baştan
 *     yeniden hesaplıyor (silinen hareket artık silindi_mi=1 olduğu için
 *     onun etkisi de otomatik olarak dışarıda kalır).
 *  3) Her iki çağrı da yalnızca cari_id VARSA yapılıyor (cari'siz genel
 *     gelir/gider hareketlerinde gereksiz sorgu çalışmaz).
 *
 * Veritabanı gerektirmez: kaynak denetimi + Reflection iledir.
 */

$kok = dirname(__DIR__, 2);

define('MODELS_PATH', $kok . '/app/models');
require_once $kok . '/app/models/KasaHesap.php';

/** @var array<int,array{ad:string,ok:bool,detay:string}> */
$sonuclar = [];

function kontrol(string $ad, bool $gecti, string $detay = ''): void
{
    global $sonuclar;
    $sonuclar[] = ['ad' => $ad, 'ok' => $gecti, 'detay' => $detay];
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
// 1) hareketEkle() — ekleme sırasında FIFO + bakiye senkronu
// ═════════════════════════════════════════════════════════════════════

$ekleGovde = metotGovdesi('KasaHesap', 'hareketEkle');
kontrol('hareketEkle() Fatura::fifoOdemeDagit()\'i çağırıyor',
    str_contains($ekleGovde, '->fifoOdemeDagit('));
kontrol('hareketEkle() Fatura::recomputeCariBalance()\'ı çağırıyor',
    str_contains($ekleGovde, '->recomputeCariBalance('));
kontrol('hareketEkle() bu çağrıları SADECE cari_id varsa yapıyor (cari\'siz gelir/gider için gereksiz sorgu yok)',
    (bool)preg_match('/cariId\s*!==\s*null\)\s*\{[^}]*fifoOdemeDagit/s', $ekleGovde));
kontrol('hareketEkle() fifoOdemeDagit()\'i recomputeCariBalance()\'tan ÖNCE çağırıyor (Nakit::hareketEkle() ile aynı sıra)',
    strpos($ekleGovde, '->fifoOdemeDagit(') < strpos($ekleGovde, '->recomputeCariBalance('));
kontrol('hareketEkle() işlem tipini (giris/cikis) fifoOdemeDagit()\'e olduğu gibi iletiyor',
    (bool)preg_match('/fifoOdemeDagit\(\$cariId,\s*\$islem,\s*\$tutar\)/', $ekleGovde));

// ═════════════════════════════════════════════════════════════════════
// 2) hareketSil() — silme sırasında FIFO geri alma
// ═════════════════════════════════════════════════════════════════════

$silGovde = metotGovdesi('KasaHesap', 'hareketSil');
kontrol('hareketSil() Fatura::fifoBakiyeleriYenidenHesapla()\'yı çağırıyor',
    str_contains($silGovde, '->fifoBakiyeleriYenidenHesapla('));
kontrol('hareketSil() bu çağrıyı SADECE silinen hareketin cari_id\'si varsa yapıyor',
    (bool)preg_match("/!empty\(\\\$h\['cari_id'\]\)\)\s*\{[^}]*fifoBakiyeleriYenidenHesapla/s", $silGovde));
kontrol('hareketSil() önce kasa_hareketleri satırını silindi_mi=1 yapıyor, SONRA yeniden hesaplıyor (silinen hareket artık hariç tutulmalı)',
    strpos($silGovde, "'silindi_mi' => 1") < strpos($silGovde, 'fifoBakiyeleriYenidenHesapla'));

// ═════════════════════════════════════════════════════════════════════
// 3) Nakit::hareketEkle() ile paralellik bozulmamış (iki kopya senkron kalmalı)
// ═════════════════════════════════════════════════════════════════════

require_once $kok . '/app/models/Fatura.php';
require_once $kok . '/app/models/Nakit.php';
$nakitGovde = metotGovdesi('Nakit', 'hareketEkle');
kontrol('Referans: Nakit::hareketEkle() hâlâ fifoOdemeDagit() + recomputeCariBalance() çağırıyor (iki modelin sürüklenmediğinin kanıtı)',
    str_contains($nakitGovde, '->fifoOdemeDagit(') && str_contains($nakitGovde, '->recomputeCariBalance('));

// ═════════════════════════════════════════════════════════════════════
// 4) Meta: PHP kapanış etiketi tuzağı yok
// ═════════════════════════════════════════════════════════════════════

foreach ([$kok . '/app/models/KasaHesap.php', __FILE__] as $dosya) {
    $kaynak = (string)@file_get_contents($dosya);
    $kapali = false;
    foreach (token_get_all($kaynak) as $token) {
        if (is_array($token) && $token[0] === T_CLOSE_TAG) {
            $kapali = true;
            break;
        }
    }
    kontrol('Meta: ' . basename($dosya) . ' içinde PHP kapanış etiketi (?>) yok', !$kapali);
}

// ═════════════════════════════════════════════════════════════════════
// Rapor
// ═════════════════════════════════════════════════════════════════════

$basarili = 0;
$basarisiz = [];
echo "=== KasaHesap → Fatura FIFO senkronu regresyon testi ===\n\n";
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
