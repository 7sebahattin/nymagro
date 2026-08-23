<?php
/**
 * Regresyon testi: Tahsilat/ödeme → fatura bazlı kalan_tutar FIFO dağıtımı.
 * --------------------------------------------------------------------------
 * Çalıştırma:  php tests/regression/fatura_odeme_fifo_invariants.php
 * Çıkış kodu:  0 = tüm kontroller PASSED, 1 = en az bir FAILED.
 *
 * Arka plan (bkz. commit mesajı): Nakit::hareketEkle() bugüne kadar sadece
 * kasa_hareketleri'ne yazıp cariler.bakiye'yi (recomputeCariBalance ile)
 * güncelliyordu; faturalar.odenen_tutar/kalan_tutar hiçbir tahsilat/ödeme
 * akışında güncellenmiyordu. Sonuç: Müşteri Detayı'ndaki "Açık Bakiye"
 * doğruyken, Satışlar/Alışlar listesindeki "Kalan" sütunu ve "Bekleyen
 * Tahsilat" özet kartı tahsilat sonrasında da hep ilk tutarda donuk
 * kalıyordu.
 *
 * KORUNAN İLKELER
 *  1) FIFO SIRASI: ödeme, en eski açık faturadan başlayarak dağıtılır.
 *  2) TUTAR KORUNUMU: bir faturaya asla genel_toplam'ı aşan ödeme
 *     uygulanmaz; kalan_tutar hiçbir zaman negatif olmaz.
 *  3) DURUM TUTARLILIĞI: kalan_tutar sıfırlanan fatura 'odendi', kısmen
 *     azalan fatura 'kismi_odendi' durumuna geçer.
 *  4) SESSİZ NO-OP: sıfır/negatif tutar veya açık fatura yokluğunda hiçbir
 *     güncelleme üretilmez.
 *  5) TEK TRANSACTION: fifoOdemeDagit() kendi başına begin()/commit()
 *     ÇAĞIRMAZ — Nakit::hareketEkle()'nin AÇIK transaction'ı içinde
 *     çalışacak şekilde tasarlanmıştır (aksi halde kasa hareketiyle
 *     fatura güncellemesi ayrı transaction'lara düşüp tutarsızlaşabilir).
 *  6) KAYNAĞINDA BAĞLANTI: Nakit::hareketEkle() fifoOdemeDagit()'i
 *     gerçekten çağırıyor — sessizce sökülmüş olamaz.
 *  7) RBAC: yeni "bakiyeGuncelle" ucu, isim tabanlı classifyAction()
 *     kuralına göre otomatik UPDATE olarak sınıflanır (override gerekmez).
 *
 * Veritabanı gerektirmez: fifoDagitimHesapla() saf bir statik fonksiyondur,
 * gerçekten çalıştırılır; geri kalanı kaynak denetimi + Reflection iledir.
 */

$kok = dirname(__DIR__, 2);

define('MODELS_PATH', $kok . '/app/models');
define('CORE_PATH', $kok . '/app/core');
require_once $kok . '/app/models/Fatura.php';
require_once $kok . '/app/core/Rbac.php';

/** @var array<int,array{ad:string,ok:bool,detay:string}> */
$sonuclar = [];

function kontrol(string $ad, bool $gecti, string $detay = ''): void
{
    global $sonuclar;
    $sonuclar[] = ['ad' => $ad, 'ok' => $gecti, 'detay' => $detay];
}

function yaklasik(float $a, float $b, float $tolerans = 0.005): bool
{
    return abs($a - $b) <= $tolerans;
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

function acikFatura(int $id, float $genelToplam, float $odenenTutar = 0.0): array
{
    return [
        'id'           => $id,
        'genel_toplam' => $genelToplam,
        'odenen_tutar' => $odenenTutar,
        'kalan_tutar'  => round($genelToplam - $odenenTutar, 2),
    ];
}

// ═════════════════════════════════════════════════════════════════════
// 1) fifoDagitimHesapla() — saf dağıtım matematiği
// ═════════════════════════════════════════════════════════════════════

// Sıfır/negatif tutar → no-op.
kontrol('Sıfır tutar hiçbir güncelleme üretmez',
    Fatura::fifoDagitimHesapla([acikFatura(1, 100)], 0.0) === []);
kontrol('Negatif tutar hiçbir güncelleme üretmez',
    Fatura::fifoDagitimHesapla([acikFatura(1, 100)], -50.0) === []);
kontrol('Açık fatura yokken hiçbir güncelleme üretmez',
    Fatura::fifoDagitimHesapla([], 500.0) === []);

// Tek fatura, tam ödeme.
$r = Fatura::fifoDagitimHesapla([acikFatura(1, 1000)], 1000.0);
kontrol('Tam ödeme: tek güncelleme üretilir', count($r) === 1);
kontrol('Tam ödeme: kalan_tutar sıfırlanır', isset($r[0]) && yaklasik((float)$r[0]['kalan_tutar'], 0.0));
kontrol('Tam ödeme: odenen_tutar genel_toplam\'a eşitlenir', isset($r[0]) && yaklasik((float)$r[0]['odenen_tutar'], 1000.0));
kontrol('Tam ödeme: durum "odendi" olur', isset($r[0]) && $r[0]['durum'] === 'odendi');

// Tek fatura, kısmi ödeme.
$r = Fatura::fifoDagitimHesapla([acikFatura(1, 1000)], 400.0);
kontrol('Kısmi ödeme: kalan_tutar doğru azalır', isset($r[0]) && yaklasik((float)$r[0]['kalan_tutar'], 600.0));
kontrol('Kısmi ödeme: durum "kismi_odendi" olur', isset($r[0]) && $r[0]['durum'] === 'kismi_odendi');

// İki fatura, ödeme ilkini kapatıp ikinciye taşar (FIFO sırası korunmalı).
$acik = [acikFatura(10, 300), acikFatura(20, 500)];
$r = Fatura::fifoDagitimHesapla($acik, 700.0);
kontrol('FIFO: iki fatura güncellenir', count($r) === 2);
kontrol('FIFO: ilk (en eski) fatura tam kapanır',
    isset($r[0]) && $r[0]['id'] === 10 && yaklasik((float)$r[0]['kalan_tutar'], 0.0) && $r[0]['durum'] === 'odendi');
kontrol('FIFO: taşan tutar ikinci faturaya doğru uygulanır',
    isset($r[1]) && $r[1]['id'] === 20 && yaklasik((float)$r[1]['kalan_tutar'], 100.0) && $r[1]['durum'] === 'kismi_odendi');

// Ödeme, açık tutarların toplamını aşıyor → negatif kalan_tutar OLUŞMAZ,
// fazlası sessizce "kullanılmadan" kalır (kasa/cari tarafında zaten
// recomputeCariBalance() ile doğru yansır — bu fonksiyon sadece faturaları
// yönetir, cari bakiyeyi TEKRAR hesaplamaz).
$acik = [acikFatura(1, 200)];
$r = Fatura::fifoDagitimHesapla($acik, 5000.0);
kontrol('Fazla ödeme: kalan_tutar negatife düşmez',
    isset($r[0]) && (float)$r[0]['kalan_tutar'] >= 0.0 && yaklasik((float)$r[0]['kalan_tutar'], 0.0));

// Önceden kısmen ödenmiş fatura üzerine ikinci bir kısmi ödeme — odenen_tutar
// BİRİKMELİ artmalı (üzerine yazılmamalı).
$r = Fatura::fifoDagitimHesapla([acikFatura(1, 1000, 300.0)], 200.0);
kontrol('Kümülatif ödeme: odenen_tutar önceki tutarın üzerine eklenir',
    isset($r[0]) && yaklasik((float)$r[0]['odenen_tutar'], 500.0));
kontrol('Kümülatif ödeme: kalan_tutar buna göre azalır',
    isset($r[0]) && yaklasik((float)$r[0]['kalan_tutar'], 500.0));

// Girdi listesinde (savunma amaçlı) zaten kapalı bir satır varsa atlanır.
$r = Fatura::fifoDagitimHesapla([acikFatura(1, 100, 100.0), acikFatura(2, 200)], 150.0);
kontrol('Zaten kapalı fatura atlanır, ödeme sıradaki açık faturaya gider',
    count($r) === 1 && $r[0]['id'] === 2 && yaklasik((float)$r[0]['kalan_tutar'], 50.0));

// ═════════════════════════════════════════════════════════════════════
// 2) Kaynak denetimi — çekirdek bağlantı ve transaction disiplini
// ═════════════════════════════════════════════════════════════════════

$faturaGovde = metotGovdesi('Fatura', 'fifoOdemeDagit');
kontrol('fifoOdemeDagit() kendi başına begin() ÇAĞIRMAZ (çağıranın transaction\'ı içinde çalışır)',
    !str_contains($faturaGovde, '->begin('));
kontrol('fifoOdemeDagit() kendi başına commit() ÇAĞIRMAZ',
    !str_contains($faturaGovde, '->commit('));
kontrol('fifoOdemeDagit() satır kilidi alır (FOR UPDATE) — eşzamanlı ödeme yarışını önler',
    str_contains($faturaGovde, 'FOR UPDATE'));
kontrol('fifoOdemeDagit() iptal/taslak faturaları hariç tutar',
    str_contains($faturaGovde, "NOT IN ('iptal', 'taslak')"));

$yenidenHesaplaGovde = metotGovdesi('Fatura', 'fifoBakiyeleriYenidenHesapla');
kontrol('fifoBakiyeleriYenidenHesapla() kendi transaction\'ını açar (bağımsız çağrılabilir)',
    str_contains($yenidenHesaplaGovde, '->begin('));
kontrol('fifoBakiyeleriYenidenHesapla() sonunda cari bakiyeyi de yeniden hesaplar',
    str_contains($yenidenHesaplaGovde, 'recomputeCariBalance('));
kontrol('fifoBakiyeleriYenidenHesapla() hata durumunda rollBack yapar',
    str_contains($yenidenHesaplaGovde, '->rollBack('));

$nakitKaynak = (string)@file_get_contents($kok . '/app/models/Nakit.php');
kontrol('Nakit::hareketEkle() artık fifoOdemeDagit()\'i çağırıyor (kaynağında düzeltme koptu/sökülmedi)',
    str_contains($nakitKaynak, '->fifoOdemeDagit('));
kontrol('Nakit::hareketEkle() FIFO dağıtımından SONRA cari bakiyeyi yeniden hesaplıyor',
    strpos($nakitKaynak, '->fifoOdemeDagit(') < strpos($nakitKaynak, '->recomputeCariBalance('));

// ═════════════════════════════════════════════════════════════════════
// 3) Bir kerelik onarım ucu — controller + view + RBAC
// ═════════════════════════════════════════════════════════════════════

foreach (['MusteriController', 'TedarikciController'] as $ctrl) {
    $dosya = $kok . '/app/controllers/' . $ctrl . '.php';
    $kaynak = (string)@file_get_contents($dosya);
    kontrol("{$ctrl}::bakiyeGuncelle() tanımlı", str_contains($kaynak, 'function bakiyeGuncelle('));
    kontrol("{$ctrl}::bakiyeGuncelle() sadece POST kabul eder", str_contains($kaynak, "REQUEST_METHOD'] !== 'POST'"));
    kontrol("{$ctrl}::bakiyeGuncelle() fifoBakiyeleriYenidenHesapla()'yı çağırır",
        str_contains($kaynak, '->fifoBakiyeleriYenidenHesapla('));
}

kontrol('RBAC: MusteriController::bakiyeGuncelle otomatik olarak MUSTERI_UPDATE ister (isim kuralı, override gerekmez)',
    (function () {
        $rm = new ReflectionMethod('Rbac', 'requiredPermissionFor');
        $rm->setAccessible(true);
        return $rm->invoke(null, 'MusteriController', 'bakiyeGuncelle') === 'MUSTERI_UPDATE';
    })());
kontrol('RBAC: TedarikciController::bakiyeGuncelle otomatik olarak TEDARIKCI_UPDATE ister',
    (function () {
        $rm = new ReflectionMethod('Rbac', 'requiredPermissionFor');
        $rm->setAccessible(true);
        return $rm->invoke(null, 'TedarikciController', 'bakiyeGuncelle') === 'TEDARIKCI_UPDATE';
    })());

$partial = (string)@file_get_contents($kok . '/app/views/partials/cari_detay_modern.php');
kontrol('Arayüz: "Fatura Bakiyelerini Yeniden Hesapla" butonu mevcut',
    str_contains($partial, 'Fatura Bakiyelerini Yeniden Hesapla'));
kontrol('Arayüz: buton CSRF korumalı nymPost() ile POST atıyor (çıplak <a href> değil)',
    str_contains($partial, "nymPost('<?= htmlspecialchars(\$recalcUrl)"));
kontrol('Arayüz: buton MUSTERI/TEDARIKCI _UPDATE yetkisiyle kapılı',
    str_contains($partial, "((\$isMusteri ? 'MUSTERI' : 'TEDARIKCI') . '_UPDATE')"));

// ═════════════════════════════════════════════════════════════════════
// 4) Meta: PHP kapanış etiketi tuzağı bu dosyalarda tekrar yok
// ═════════════════════════════════════════════════════════════════════

foreach ([
    $kok . '/app/models/Fatura.php',
    $kok . '/app/models/Nakit.php',
    $kok . '/app/controllers/MusteriController.php',
    $kok . '/app/controllers/TedarikciController.php',
    __FILE__,
] as $dosya) {
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
echo "=== Tahsilat/ödeme → fatura FIFO dağıtımı regresyon testi ===\n\n";
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
