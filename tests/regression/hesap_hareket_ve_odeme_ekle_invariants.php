<?php
/**
 * Regresyon testi: Hesap hareketi düzenleme/renk/dropdown düzeltmeleri +
 * fatura satırından hızlı "Ödeme Ekle".
 * --------------------------------------------------------------------------
 * Çalıştırma:  php tests/regression/hesap_hareket_ve_odeme_ekle_invariants.php
 * Çıkış kodu:  0 = tüm kontroller PASSED, 1 = en az bir FAILED.
 *
 * Arka plan:
 *  1) Hesap Detayı > HESAP HAREKETLERİ tablosundaki "Bakiye" sütunu koyu
 *     temada okunaksızdı çünkü rengi sabit `#1e293b` (koyu/neredeyse siyah)
 *     olarak kodlanmıştı — tema değişkeni yerine sabit renk kullanmak,
 *     koyu arka plan üzerinde metni görünmez kılıyordu.
 *  2) Aynı sayfadaki "İşlem" açılır menüsü, tabloyu saran
 *     `overflow-x:auto` konteynerinin İÇİNDE Bootstrap/Popper'ın
 *     `position:absolute` ile konumlandırdığı standart bir menüydü — bu,
 *     overflow konteynerinde klasik bir kırpılma/kayma hatasına yol açar.
 *  3) Bir hesap hareketi (tahsilat/ödeme) girildikten sonra sadece
 *     SİLİNEBİLİYORDU, DÜZENLENEMİYORDU — yanlış tutar/tarih girildiğinde
 *     tek çare silip yeniden girmekti.
 *  4) Fatura listesindeki bir faturaya, o faturayı açmadan/müşteri kartına
 *     gitmeden doğrudan satırdan hızlı bir tahsilat girme yolu yoktu.
 *
 * KORUNAN İLKELER
 *  1) hesaplar/detay.php: Bakiye sütunu artık var(--text)/var(--danger)
 *     kullanıyor, sabit hex renk YOK.
 *  2) "İşlem" dropdown'ı artık data-bs-strategy="fixed" ile overflow
 *     konteynerinden kaçıyor.
 *  3) KasaHesap::hareketGuncelle() var; eski tutarın kasa bakiyesindeki
 *     etkisini fark üzerinden doğru yönde geri alıp yeni tutarı uyguluyor;
 *     cari'ye bağlıysa fifoBakiyeleriYenidenHesapla()'yı çağırıyor.
 *  4) HesapController::hareketGuncelle() → HESAP_UPDATE (isim kuralı).
 *  5) Fatura listesindeki "Ödeme Ekle" SADECE belge_tipi='satis', cari_id
 *     dolu, kalan_tutar > 0 ve NAKIT_CREATE izni varken görünüyor —
 *     numune/irsaliye/perakende'de veya kapanmış faturada gösterilmiyor.
 *
 * Veritabanı gerektirmez: kaynak denetimi + Reflection iledir.
 */

$kok = dirname(__DIR__, 2);

define('MODELS_PATH', $kok . '/app/models');
define('CORE_PATH', $kok . '/app/core');
require_once $kok . '/app/models/KasaHesap.php';
require_once $kok . '/app/core/Rbac.php';

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
// 1) Renk sabiti kaldırıldı
// ═════════════════════════════════════════════════════════════════════

$hesapDetay = oku('app/views/hesaplar/detay.php');
kontrol('hesaplar/detay.php: Bakiye sütununda sabit #1e293b hex renk YOK',
    !str_contains($hesapDetay, "'#1e293b'"));
kontrol('hesaplar/detay.php: Bakiye sütunu var(--text) tema değişkenini kullanıyor',
    str_contains($hesapDetay, "'var(--text)'"));
kontrol('hesaplar/detay.php: negatif bakiye var(--danger) kullanıyor',
    str_contains($hesapDetay, "'var(--danger)'"));

// ═════════════════════════════════════════════════════════════════════
// 2) Dropdown overflow-kırpılma düzeltmesi
// ═════════════════════════════════════════════════════════════════════

kontrol('hesaplar/detay.php: "İşlem" dropdown\'ı data-bs-strategy="fixed" kullanıyor (overflow-x:auto konteynerinden kaçmak için)',
    (bool)preg_match('/btn-islem dropdown-toggle[^>]*data-bs-strategy="fixed"/', $hesapDetay));

// ═════════════════════════════════════════════════════════════════════
// 3) KasaHesap::hareketGuncelle() — doğru yönde fark uygulama + FIFO senkronu
// ═════════════════════════════════════════════════════════════════════

$guncelleGovde = metotGovdesi('KasaHesap', 'hareketGuncelle');
kontrol('hareketGuncelle() eski/yeni tutar farkını hesaplıyor',
    str_contains($guncelleGovde, '$yeniTutar - $eskiTutar'));
kontrol('hareketGuncelle() farkı işlem yönüne göre (giriş +, çıkış -) uyguluyor',
    (bool)preg_match("/islem_tipi'\\]\\s*===\\s*'giris'\\)\\s*\\?\\s*'\\+'\\s*:\\s*'-'/", $guncelleGovde));
kontrol('hareketGuncelle() cari\'ye bağlıysa fifoBakiyeleriYenidenHesapla()\'yı çağırıyor',
    str_contains($guncelleGovde, '->fifoBakiyeleriYenidenHesapla('));
// UPDATE'e geçirilen alan kümesi SADECE tutar/tarih/odeme_yontemi/aciklama
// olmalı — kasa_id/cari_id/islem_tipi'yi OKUMAK ($eski['...']) serbest,
// ama db->update('kasa_hareketleri', [...]) çağrısının İÇİNDE bu alanları
// YENİDEN YAZMAMALI.
preg_match("/db->update\\('kasa_hareketleri',\\s*\\[(.*?)\\],\\s*\\['id'/s", $guncelleGovde, $updateEslesme);
$updateAlanlari = $updateEslesme[1] ?? '';
kontrol('hareketGuncelle() UPDATE alan kümesi bulunabildi (regex eşleşti)', $updateAlanlari !== '');
kontrol('hareketGuncelle() UPDATE çağrısı kasa_id/cari_id/islem_tipi\'ni YENİDEN YAZMIYOR (yalnızca tutar/tarih/ödeme yöntemi/açıklama)',
    !str_contains($updateAlanlari, "'kasa_id'") && !str_contains($updateAlanlari, "'cari_id'") && !str_contains($updateAlanlari, "'islem_tipi'"));

$rbacMetot = new ReflectionMethod('Rbac', 'requiredPermissionFor');
$rbacMetot->setAccessible(true);
kontrol('RBAC: HesapController::hareketGuncelle → HESAP_UPDATE (isim kuralı, override gerekmez)',
    $rbacMetot->invoke(null, 'HesapController', 'hareketGuncelle') === 'HESAP_UPDATE');

$hesapCtrl = oku('app/controllers/HesapController.php');
kontrol('HesapController::hareketGuncelle() sadece POST kabul eder',
    (bool)preg_match("/function hareketGuncelle.*?REQUEST_METHOD'\\]\\s*!==\\s*'POST'/s", $hesapCtrl));
kontrol('HesapController::hareketGuncelle() geçersiz ödeme yöntemini reddediyor (Para Girişi/Çıkışı ile aynı liste)',
    (bool)preg_match("/function hareketGuncelle.*?in_array\\(\\\$odemeYontemi, \\\$odemeYontemleri, true\\)/s", $hesapCtrl));

// ═════════════════════════════════════════════════════════════════════
// 4) Fatura listesinden "Ödeme Ekle"
// ═════════════════════════════════════════════════════════════════════

$satisIndex = oku('app/views/satislar/index.php');
kontrol('satislar/index.php: "Ödeme Ekle" butonu tanımlı',
    str_contains($satisIndex, 'Ödeme Ekle'));
kontrol('satislar/index.php: "Ödeme Ekle" SADECE belge_tipi===satis + cari_id dolu + kalan_tutar>0 + NAKIT_CREATE ile görünür',
    (bool)preg_match(
        "/belgeTipi === 'satis' && !empty\\(\\\$f\\['cari_id'\\]\\) && \\(float\\)\\(\\\$f\\['kalan_tutar'\\] \\?\\? 0\\) > 0\\.004 && Rbac::currentUserCan\\('NAKIT_CREATE'\\)/",
        $satisIndex
    ));
kontrol('satislar/index.php: odemeKaydet() /nakit/kaydet\'e POST atıyor (PHP tarafından render edilen BASE_URL ile, tanımsız JS değişkeniyle DEĞİL)',
    str_contains($satisIndex, "fetch('<?= BASE_URL ?>/nakit/kaydet'"));
kontrol('satislar/index.php: odemeKaydet() tanımsız bir JS BASE_URL değişkeni kullanmıyor',
    !preg_match('/fetch\(BASE_URL\s*\+/', $satisIndex));

$satisCtrl = oku('app/controllers/SatisController.php');
kontrol('SatisController::index() kasaHesaplar\'ı view\'e geçiriyor (Ödeme Ekle modalının Kasa/Hesap seçimi için)',
    (bool)preg_match("/function index\\(\\).*?'kasaHesaplar'\\s*=>\\s*\\\$this->kasaHesapModel->hepsini\\(\\)/s", $satisCtrl));

// ═════════════════════════════════════════════════════════════════════
// 5) Meta: PHP kapanış etiketi tuzağı — saf PHP dosyalarında
// ═════════════════════════════════════════════════════════════════════

foreach ([
    $kok . '/app/models/KasaHesap.php',
    $kok . '/app/controllers/HesapController.php',
    $kok . '/app/controllers/SatisController.php',
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
echo "=== Hesap hareketi düzenleme + Ödeme Ekle regresyon testi ===\n\n";
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
