<?php
/**
 * Regresyon testi: faturalar.belge_tipi/durum ENUM → VARCHAR geçişi + onarımı.
 * --------------------------------------------------------------------------
 * Çalıştırma:  php tests/regression/faturalar_enum_esnek_invariants.php
 * Çıkış kodu:  0 = tüm kontroller PASSED, 1 = en az bir FAILED.
 *
 * Arka plan (bkz. commit mesajı): faturalar.belge_tipi ENUM olarak
 * tanımlanmıştı ve 'irsaliye'/'numune'/'proforma'/'siparis' bu ENUM'a HİÇ
 * eklenmemişti. MySQL, strict olmayan modda ENUM'da tanımsız bir değeri
 * INSERT ederken hata fırlatmak yerine sessizce boş string'e çevirip
 * uyarı verir — satır "başarıyla" yazılır (stok/tutar gibi diğer sütunlar
 * doğru kalıcı olur) ama belge_tipi boş kaldığı için hiçbir zaman ilgili
 * filtreye uymaz. Belgeler her yerde "yokmuş" gibi görünürken satır
 * gerçekte veritabanındaydı.
 *
 * KORUNAN İLKELER
 *  1) Fatura::__construct() artık ENUM→VARCHAR dönüşümünü DENER (idempotent,
 *     yalnızca hâlâ ENUM ise ALTER atar).
 *  2) Onarım eşlemesi (fatura_no önekinden doğru belge_tipi'ni geri
 *     çıkarma) GERÇEK Fatura::belgeOnEki() önekleriyle TAM eşleşmeli —
 *     biri değişip diğeri unutulursa bu test kırılır.
 *  3) Onarım SADECE belge_tipi = '' olan satırları hedefler; asla var olan
 *     geçerli bir değerin (ör. 'satis') üzerine yazmaz.
 *  4) DDL yalnızca açık transaction YOKKEN denenir (bu codebase'in genel
 *     kuralı — MySQL DDL örtük commit yapar).
 *
 * Veritabanı gerektirmez: kaynak denetimi + Reflection iledir.
 */

$kok = dirname(__DIR__, 2);

define('MODELS_PATH', $kok . '/app/models');
require_once $kok . '/app/models/Fatura.php';

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
// 1) Constructor bağlantısı
// ═════════════════════════════════════════════════════════════════════

$ctorGovde = metotGovdesi('Fatura', '__construct');
kontrol('__construct() ensureBelgeTipiVeDurumEsnek()\'i çağırıyor',
    str_contains($ctorGovde, 'ensureBelgeTipiVeDurumEsnek()'));
kontrol('__construct() bu çağrıyı "!inTransaction()" koruması İÇİNDE yapıyor (DDL örtük commit tuzağı)',
    (bool)preg_match('/inTransaction\(\)\s*\)\s*\{[^}]*ensureBelgeTipiVeDurumEsnek/s', $ctorGovde));

// ═════════════════════════════════════════════════════════════════════
// 2) ensureBelgeTipiVeDurumEsnek() — idempotent ENUM→VARCHAR + onarım
// ═════════════════════════════════════════════════════════════════════

$govde = metotGovdesi('Fatura', 'ensureBelgeTipiVeDurumEsnek');
kontrol('Yalnızca hâlâ ENUM ise ALTER atar (COLUMN_TYPE kontrolü var)',
    str_contains($govde, "INFORMATION_SCHEMA.COLUMNS") && str_contains($govde, "'enum('"));
kontrol('belge_tipi VARCHAR\'a çevriliyor',
    str_contains($govde, 'MODIFY COLUMN belge_tipi VARCHAR'));
kontrol('durum da VARCHAR\'a çevriliyor',
    str_contains($govde, 'MODIFY COLUMN durum VARCHAR'));
kontrol('Onarım UPDATE\'i SADECE belge_tipi = \'\' satırlarını hedefliyor (var olan değerlerin üzerine yazmıyor)',
    str_contains($govde, "belge_tipi = ''"));
kontrol('DDL/onarım hataları try/catch ile yutuluyor (uygulamayı kilitlemiyor)',
    substr_count($govde, 'catch (\Throwable') >= 2);

// ═════════════════════════════════════════════════════════════════════
// 3) Onarım eşlemesi ile belgeOnEki() GERÇEK önekleri TAM eşleşmeli
// ═════════════════════════════════════════════════════════════════════

// Onarım kodundan eşleme çıkar: 'ONEK-' => 'tip',
preg_match_all("/'([A-Z]+-)'\s*=>\s*'([a-z_]+)'/", $govde, $onarimEslesme, PREG_SET_ORDER);
$onarimHaritasi = [];
foreach ($onarimEslesme as $m) {
    $onarimHaritasi[$m[2]] = rtrim($m[1], '-');
}
kontrol('Onarım eşlemesi en az numune/irsaliye/proforma/siparis içeriyor',
    isset($onarimHaritasi['numune'], $onarimHaritasi['irsaliye'], $onarimHaritasi['proforma'], $onarimHaritasi['siparis']));

$onEkiGovde = metotGovdesi('Fatura', 'belgeOnEki');
foreach (['numune' => 'NUM', 'irsaliye' => 'IRS', 'proforma' => 'PRO', 'siparis' => 'SIP'] as $tip => $beklenenOnek) {
    kontrol("belgeOnEki('{$tip}') gerçekten '{$beklenenOnek}' döner (sabit, para birimi ayarına bağlı değil)",
        (bool)preg_match("/'{$tip}'\\s*=>.*'{$beklenenOnek}'/", $onEkiGovde));
    kontrol("Onarım haritasındaki '{$tip}' → '{$beklenenOnek}-' eşlemesi belgeOnEki() ile TUTARLI",
        ($onarimHaritasi[$tip] ?? null) === $beklenenOnek);
}

// ═════════════════════════════════════════════════════════════════════
// 4) Meta: PHP kapanış etiketi tuzağı yok
// ═════════════════════════════════════════════════════════════════════

foreach ([$kok . '/app/models/Fatura.php', __FILE__] as $dosya) {
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
echo "=== faturalar ENUM→VARCHAR geçişi + onarımı regresyon testi ===\n\n";
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
