<?php
/**
 * Regresyon testi: Müşteri/Tedarikçi düzenleme → tam sayfa "Yeni Ekle"
 * formuna yönlendirme.
 * --------------------------------------------------------------------------
 * Çalıştırma:  php tests/regression/cari_duzenleme_yonlendirme_invariants.php
 * Çıkış kodu:  0 = tüm kontroller PASSED, 1 = en az bir FAILED.
 *
 * Arka plan: Müşteri/Tedarikçi Detayı'ndaki "Bilgilerini Güncelle" küçük
 * modalı (a) uzun tedarikçi formunda aşağı kaymıyordu, (b) "Yeni Ekle"
 * formundaki birçok alanı (fotoğraf, sınıflandırma, vergi muafiyeti, banka
 * bilgileri...) içermiyordu, (c) müşteri ve tedarikçi için neredeyse aynı
 * jenerik alan setini gösteriyordu. Kullanıcının açık tercihi: modalı
 * kaldırıp düzenlemeyi "Yeni Ekle" ekranının (sekmeli, tam sayfa) yapısına
 * yönlendirmek — mevcut kaydın verileriyle önceden doldurulmuş olarak.
 *
 * KORUNAN İLKELER
 *  1) MODAL KALDIRILDI: cari_detay_modern.php artık #cdUpdateModal
 *     içermiyor; "Bilgilerini Güncelle" bir düzenleme SAYFASINA bağlanıyor.
 *  2) YÖNLENDİRME: her iki controller de "Yeni Ekle" ile AYNI view'i
 *     (musteriler/ekle.php, tedarikciler/ekle.php) düzenleme modunda
 *     render eden bir duzenle($id) ucu tanımlar.
 *  3) ÖN DOLUM: her iki view de düzenleme modunda mevcut kaydın alanlarını
 *     ($eski / $val()) forma önceden dolduruyor.
 *  4) ALAN PARİTESİ: guncelle() işleyicileri artık "Yeni Ekle" formunun
 *     gönderdiği TÜM alanları kabul ediyor (sınıflandırma, fotoğraf vb.),
 *     sadece eski modalın dar alt kümesini değil.
 *  5) RBAC: yeni "duzenle" ucu isim tabanlı classifyAction() kuralına göre
 *     otomatik UPDATE olarak sınıflanır (override gerekmez) — modaldaki
 *     "Bilgilerini Güncelle" butonunun gerektirdiği yetkiyle birebir aynı.
 *  6) CSRF: duzenle() salt-GÖRÜNTÜLEME ucu, CSRF gerektirmez (mutasyon
 *     değil); guncelle() POST olduğu için gerektirir.
 *
 * Veritabanı gerektirmez: tamamı kaynak denetimi + Reflection iledir.
 */

$kok = dirname(__DIR__, 2);

define('MODELS_PATH', $kok . '/app/models');
define('CORE_PATH', $kok . '/app/core');
require_once $kok . '/app/core/Rbac.php';
require_once $kok . '/app/core/Csrf.php';

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

// ═════════════════════════════════════════════════════════════════════
// 1) Modal kaldırıldı, yönlendirme bağlantısı var
// ═════════════════════════════════════════════════════════════════════

$partial = oku('app/views/partials/cari_detay_modern.php');
kontrol('cari_detay_modern.php: eski #cdUpdateModal artık YOK', !str_contains($partial, 'cdUpdateModal'));
kontrol('cari_detay_modern.php: "Bilgilerini Güncelle" artık bir sayfaya (href) bağlanıyor',
    (bool)preg_match('/href="<\?= htmlspecialchars\(\$editUrl\)[^"]*"[^>]*>\s*<i class="fa-solid fa-pen">/s', $partial));
kontrol('cari_detay_modern.php: $editUrl .../duzenle/ hedefine gidiyor',
    str_contains($partial, "\$editUrl = BASE_URL . '/' . \$basePath . '/duzenle/'"));

// ═════════════════════════════════════════════════════════════════════
// 2) Controller: duzenle($id) tanımlı, "Yeni Ekle" ile AYNI view'i kullanıyor
// ═════════════════════════════════════════════════════════════════════

foreach ([
    ['MusteriController', 'musteriler/ekle'],
    ['TedarikciController', 'tedarikciler/ekle'],
] as [$ctrl, $beklenenView]) {
    $kaynak = oku('app/controllers/' . $ctrl . '.php');
    kontrol("{$ctrl}::duzenle() tanımlı", str_contains($kaynak, 'function duzenle('));
    kontrol("{$ctrl}::duzenle() \"Yeni Ekle\" formuyla AYNI view'i render ediyor ('{$beklenenView}')",
        (bool)preg_match('/function duzenle.*?view\(\s*\'' . preg_quote($beklenenView, '/') . '\'/s', $kaynak));
    kontrol("{$ctrl}::duzenle() var olmayan/erişilemez kayıt için 'musteri bulunamadı' tarzı korumaya sahip",
        (bool)preg_match('/function duzenle.*?!\$\w+/s', $kaynak));
}

// ═════════════════════════════════════════════════════════════════════
// 3) View: düzenleme modunda ön-dolum ($eski / $val()) çalışıyor
// ═════════════════════════════════════════════════════════════════════

$musteriEkle = oku('app/views/musteriler/ekle.php');
kontrol('musteriler/ekle.php: $duzenleMod ayrımı var', str_contains($musteriEkle, '$duzenleMod'));
kontrol('musteriler/ekle.php: form action düzenleme modunda /musteri/guncelle/{id}\'e gidiyor',
    str_contains($musteriEkle, "BASE_URL . '/musteri/guncelle/' . (int)\$duzenleId"));
kontrol('musteriler/ekle.php: unvan alanı $val() ile önceden doldurulur (hem ekleme hem düzenleme hata-sonrası için ortak)',
    str_contains($musteriEkle, "value=\"<?= \$val('unvan') ?>\""));
kontrol('musteriler/ekle.php: düzenleme modunda açık bakiye salt-okunur gösterilir (fatura/tahsilat kaynaklı, elle değiştirilemez)',
    str_contains($musteriEkle, 'buradan düzenlenemez'));

$tedarikciEkle = oku('app/views/tedarikciler/ekle.php');
kontrol('tedarikciler/ekle.php: $duzenleMod ayrımı var', str_contains($tedarikciEkle, '$duzenleMod'));
kontrol('tedarikciler/ekle.php: form action düzenleme modunda /tedarikci/guncelle/{id}\'e gidiyor',
    str_contains($tedarikciEkle, "BASE_URL . '/tedarikci/guncelle/' . (int)\$duzenleId"));
kontrol('tedarikciler/ekle.php: unvan alanı düzenleme modunda önceden doldurulur',
    str_contains($tedarikciEkle, "id=\"tedarikci_adi\" placeholder=\"Tedarikçi adı veya firma unvanı\" value=\"<?= \$val('unvan') ?>\""));
kontrol('tedarikciler/ekle.php: düzenleme modunda AJAX kaydet akışı DEVRE DIŞI (native POST\'a bırakılır)',
    str_contains($tedarikciEkle, 'EDIT_MODE') && str_contains($tedarikciEkle, '!EDIT_MODE'));
kontrol('tedarikciler/ekle.php: sınıflandırma seçimleri düzenleme modunda önceden işaretlenir',
    str_contains($tedarikciEkle, "\$eski['sinif_1'] ?? '') === \$s['ad'] ? 'selected'"));

// ═════════════════════════════════════════════════════════════════════
// 4) guncelle(): alan paritesi — artık "Yeni Ekle" formunun gönderdiği
//    TÜM alanları kabul ediyor
// ═════════════════════════════════════════════════════════════════════

$musteriCtrl = oku('app/controllers/MusteriController.php');
kontrol('MusteriController::guncelle() artık fotoğraf yüklemesini de işliyor (handleResimUpload)',
    (bool)preg_match('/function guncelle.*?handleResimUpload\(\)/s', $musteriCtrl));
kontrol('MusteriController::guncelle() hata sonrası düzenleme SAYFASINA döner (artık kaybolan bir modala değil)',
    (bool)preg_match("/function guncelle.*?redirect\('musteri\\/duzenle\\/' \\. \\\$id\\)/s", $musteriCtrl));

$tedarikciCtrl = oku('app/controllers/TedarikciController.php');
kontrol('TedarikciController::guncelle() artık sinif_1/sinif_2 alanlarını da kabul ediyor (eski modalda yoktu)',
    (bool)preg_match("/function guncelle.*?'sinif_1'/s", $tedarikciCtrl));
kontrol('TedarikciController::guncelle() hata sonrası düzenleme SAYFASINA döner',
    (bool)preg_match("/function guncelle.*?redirect\('tedarikci\\/duzenle\\/' \\. \\\$id\\)/s", $tedarikciCtrl));

// ═════════════════════════════════════════════════════════════════════
// 5) RBAC + CSRF
// ═════════════════════════════════════════════════════════════════════

$rbacMetot = new ReflectionMethod('Rbac', 'requiredPermissionFor');
$rbacMetot->setAccessible(true);
kontrol('RBAC: MusteriController::duzenle → MUSTERI_UPDATE (modaldaki butonla aynı yetki seviyesi)',
    $rbacMetot->invoke(null, 'MusteriController', 'duzenle') === 'MUSTERI_UPDATE');
kontrol('RBAC: TedarikciController::duzenle → TEDARIKCI_UPDATE',
    $rbacMetot->invoke(null, 'TedarikciController', 'duzenle') === 'TEDARIKCI_UPDATE');

$_SERVER['REQUEST_METHOD'] = 'GET';
kontrol('CSRF: duzenle() bir GET/görüntüleme ucu — CSRF ZORUNLU DEĞİL',
    Csrf::isRequired('MusteriController', 'duzenle') === false);
$_SERVER['REQUEST_METHOD'] = 'POST';
kontrol('CSRF: guncelle() POST olduğu için CSRF ZORUNLU',
    Csrf::isRequired('MusteriController', 'guncelle') === true);

// ═════════════════════════════════════════════════════════════════════
// 6) Meta: PHP kapanış etiketi tuzağı — sadece SAF PHP dosyalarında (controller)
//    anlamlı; view'lar HTML ile iç içe olduğu için kapanış etiketi kullanımı
//    normaldir, o dosyalar bu kontrole dahil edilmez.
// ═════════════════════════════════════════════════════════════════════

foreach ([
    'app/controllers/MusteriController.php',
    'app/controllers/TedarikciController.php',
] as $goreliYol) {
    $kaynak = oku($goreliYol);
    $kapali = false;
    foreach (token_get_all($kaynak) as $token) {
        if (is_array($token) && $token[0] === T_CLOSE_TAG) {
            $kapali = true;
            break;
        }
    }
    kontrol('Meta: ' . basename($goreliYol) . ' içinde PHP kapanış etiketi (?>) yok', !$kapali);
}

// Bu test dosyasının KENDİSİ de saf PHP'dir — bir yorum içine kaçan tek bir
// kapanış etiketi, dosyanın geri kalanını PHP modundan sessizce çıkarıp
// düz metin olarak basar (çıkış kodu yine 0 kalabilir). Bu tam olarak bu
// dosyanın ilk taslağında yaşanan hataydı — kalıcı olarak engellenir.
$oz = false;
foreach (token_get_all((string)@file_get_contents(__FILE__)) as $token) {
    if (is_array($token) && $token[0] === T_CLOSE_TAG) {
        $oz = true;
        break;
    }
}
kontrol('Meta: bu test dosyasının kendisinde PHP kapanış etiketi (?>) yok', !$oz);

// ═════════════════════════════════════════════════════════════════════
// Rapor
// ═════════════════════════════════════════════════════════════════════

$basarili = 0;
$basarisiz = [];
echo "=== Müşteri/Tedarikçi düzenleme → yönlendirme regresyon testi ===\n\n";
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
