<?php
/**
 * Regresyon testi: Bu kod tabanında EN SIK tekrar eden hata sınıfları.
 * --------------------------------------------------------------------------
 * Çalıştırma:  php tests/regression/kod_sagligi_invariants.php
 * Çıkış kodu:  0 = tüm kontroller PASSED, 1 = en az bir FAILED.
 *
 * Bu dosya tek bir özelliği değil, TEKRARLAYAN HATA DESENLERİNİ korur.
 * Her kural, gerçekten yaşanmış bir olaydan türetilmiştir.
 *
 * ── SINIF 1: Türkçe yorum içine kaçan PHP kapanış etiketi ────────────────
 * Bir yorumda "?" ve ">" karakterleri yan yana gelirse PHP o noktada kod
 * modundan ÇIKAR. Dosyanın geri kalanı düz metin olarak ekrana basılır;
 * `php -l` bunu SÖZ DİZİMİ HATASI SAYMAZ, sessizce geçer. Türkçe açıklama
 * yazarken ("<?= ... ?>" gibi bir örnek vermek istendiğinde) çok kolay
 * oluşuyor: bu oturumda DÖRT kez tekrarlandı.
 * Kural: saf PHP dosyalarında (model/controller/core/test) kapanış etiketi
 * HİÇ bulunmamalı. Görünümler (view) HTML ile iç içe olduğu için muaftır.
 *
 * ── SINIF 2: RBAC kapsamı dışındaki controller'da yetki kontrolü unutmak ──
 * Rbac::UNMANAGED_CONTROLLERS listesindeki controller'lara Router izin
 * KONTROLÜ UYGULAMAZ; yetkiyi controller kendisi aramak zorundadır. Şirket
 * yönetimi bu listede olduğu için "yeni şirket oluştur" ucu bir dönem giriş
 * yapmış HERKESE açıktı (oluşturan otomatik 'owner' oluyordu).
 * Kural: şirket/dönem yöneten uçlar kendi yetki kontrolünü içermeli.
 *
 * ── SINIF 3: Aynı işin iki kopyası birbirinden sürüklenmesi ──────────────
 * Kasa hareketi ekleme iki ayrı modelde (Nakit ve KasaHesap) elle
 * kopyalanmış. Birine eklenen FIFO düzeltmesi diğerine eklenmeyince,
 * "Hesaplarım" ekranından girilen tahsilat faturanın Kalan tutarını
 * güncellemiyordu.
 * Kural: iki kopya da aynı senkron adımlarını içermeli.
 *
 * Veritabanı gerektirmez.
 */

declare(strict_types=1);

$kok = dirname(__DIR__, 2);

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

/** @return string[] Dizin altındaki tüm .php dosyalarının yolları */
function phpDosyalari(string $mutlakDizin): array
{
    if (!is_dir($mutlakDizin)) {
        return [];
    }
    $liste = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($mutlakDizin));
    foreach ($it as $d) {
        if ($d->isFile() && $d->getExtension() === 'php') {
            $liste[] = $d->getPathname();
        }
    }
    sort($liste);
    return $liste;
}

// ═════════════════════════════════════════════════════════════════════
// SINIF 1 — Saf PHP dosyalarında kapanış etiketi olmamalı
// ═════════════════════════════════════════════════════════════════════

$safDizinler = ['app/models', 'app/controllers', 'app/core', 'tests'];
$kapanisliDosyalar = [];

foreach ($safDizinler as $dizin) {
    foreach (phpDosyalari($kok . '/' . $dizin) as $yol) {
        $kaynak = (string)@file_get_contents($yol);
        foreach (token_get_all($kaynak) as $token) {
            if (is_array($token) && $token[0] === T_CLOSE_TAG) {
                $kapanisliDosyalar[] = str_replace($kok . '/', '', $yol) . ':' . $token[2];
                break;
            }
        }
    }
}

kontrol('Saf PHP dosyalarında (model/controller/core/test) PHP kapanış etiketi yok — '
    . 'yorum içine kaçan bir kapanış etiketi dosyayı ortasından keser ve php -l bunu yakalamaz',
    empty($kapanisliDosyalar),
    implode("\n      ", array_slice($kapanisliDosyalar, 0, 10)));

// Kuralın gerçekten dosyaları taradığını doğrula (boş küme üzerinde
// "geçti" demek anlamsız olurdu).
$taranan = 0;
foreach ($safDizinler as $dizin) {
    $taranan += count(phpDosyalari($kok . '/' . $dizin));
}
kontrol('Kapanış etiketi kuralı anlamlı sayıda dosya tarıyor (en az 50)',
    $taranan >= 50, "taranan dosya: {$taranan}");

// ═════════════════════════════════════════════════════════════════════
// SINIF 2 — RBAC dışı controller'lar kendi yetkisini aramalı
// ═════════════════════════════════════════════════════════════════════

require_once $kok . '/app/core/Rbac.php';
$rc = new ReflectionClass('Rbac');
$unmanaged = $rc->getConstant('UNMANAGED_CONTROLLERS');
kontrol('Rbac::UNMANAGED_CONTROLLERS listesi okunabiliyor', is_array($unmanaged) && $unmanaged !== []);

kontrol('Şirket ve dönem yönetimi hâlâ RBAC kapsamı DIŞINDA (bu yüzden kendi yetki kontrolü şart)',
    is_array($unmanaged)
    && in_array('CompanyController', $unmanaged, true)
    && in_array('PeriodController', $unmanaged, true));

/**
 * Bu controller'lardaki durum değiştiren uçlar kendi yetkisini aramalı.
 * (Yetki çağrısı: TenantContext::canManage*, AuthGuard::isSuperAdmin,
 * ya da bunları saran özel bir assert metodu.)
 */
$yetkiDeseni = '/(canManageCompany|canManagePeriod|isSuperAdmin|assertCanCreateCompany|userCanAccessCompany)/';
$mutasyonUclari = [
    'app/controllers/CompanyController.php' => ['create', 'store', 'edit', 'update', 'delete'],
    'app/controllers/PeriodController.php'  => ['create', 'store', 'edit', 'update', 'close_summary'],
];

foreach ($mutasyonUclari as $dosya => $metotlar) {
    $kaynak = oku($dosya);
    foreach ($metotlar as $metot) {
        // Metot gövdesini kaba ama güvenilir biçimde çıkar (parantez sayımı)
        if (!preg_match('/function\s+' . preg_quote($metot, '/') . '\s*\([^)]*\)\s*:\s*void\s*\{/', $kaynak, $m, PREG_OFFSET_CAPTURE)) {
            kontrol(basename($dosya) . "::{$metot}() bulundu", false, 'metot kaynakta bulunamadı');
            continue;
        }
        $bas = $m[0][1] + strlen($m[0][0]);
        $derinlik = 1;
        $i = $bas;
        $len = strlen($kaynak);
        while ($i < $len && $derinlik > 0) {
            if ($kaynak[$i] === '{') {
                $derinlik++;
            } elseif ($kaynak[$i] === '}') {
                $derinlik--;
            }
            $i++;
        }
        $govde = substr($kaynak, $bas, $i - $bas - 1);

        kontrol(basename($dosya) . "::{$metot}() kendi yetki kontrolünü yapıyor (Router bu controller'a izin uygulamıyor)",
            (bool)preg_match($yetkiDeseni, $govde));
    }
}

// ═════════════════════════════════════════════════════════════════════
// SINIF 3 — Kopyalanmış kasa hareketi yolları senkron kalmalı
// ═════════════════════════════════════════════════════════════════════

define('MODELS_PATH', $kok . '/app/models');
require_once $kok . '/app/models/Nakit.php';
require_once $kok . '/app/models/KasaHesap.php';

function metotGovdesiRefl(string $sinif, string $metot): string
{
    $rm = new ReflectionMethod($sinif, $metot);
    $dosya = $rm->getFileName();
    if ($dosya === false) {
        return '';
    }
    $satirlar = file($dosya);
    return implode('', array_slice($satirlar, $rm->getStartLine() - 1, $rm->getEndLine() - $rm->getStartLine() + 1));
}

$nakitEkle = metotGovdesiRefl('Nakit', 'hareketEkle');
$kasaEkle  = metotGovdesiRefl('KasaHesap', 'hareketEkle');

foreach ([
    'fifoOdemeDagit'       => 'ödemeyi faturalara dağıtma',
    'recomputeCariBalance' => 'cari bakiyesini yeniden hesaplama',
] as $cagri => $aciklama) {
    kontrol("Kasa hareketi eklemenin İKİ kopyası da {$aciklama} adımını içeriyor ({$cagri})",
        str_contains($nakitEkle, $cagri) && str_contains($kasaEkle, $cagri),
        'Nakit: ' . (str_contains($nakitEkle, $cagri) ? 'var' : 'YOK')
        . ' | KasaHesap: ' . (str_contains($kasaEkle, $cagri) ? 'var' : 'YOK'));
}

// ═════════════════════════════════════════════════════════════════════
// Rapor
// ═════════════════════════════════════════════════════════════════════

$basarili = 0;
$basarisiz = [];
echo "=== Kod sağlığı: tekrarlayan hata sınıfları regresyon testi ===\n\n";
foreach ($sonuclar as $s) {
    if ($s['ok']) {
        $basarili++;
        continue;
    }
    $basarisiz[] = $s;
}

echo 'Toplam kontrol: ' . count($sonuclar) . "\n";
echo "Başarılı:       {$basarili}\n";
echo 'Başarısız:      ' . count($basarisiz) . "\n\n";

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
