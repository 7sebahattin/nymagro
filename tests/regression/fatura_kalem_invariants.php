<?php
/**
 * Regresyon testi: fatura kalemleri ve toplamları güvenli yoldan geçsin.
 * --------------------------------------------------------------------------
 * Çalıştırma:  php tests/regression/fatura_kalem_invariants.php
 * Çıkış kodu:  0 = tüm kontroller PASSED, 1 = en az bir FAILED.
 *
 * NEDEN VAR
 * Denetimde (bkz. denetim raporu B1–B4) doğrulanmış dört açık vardı:
 *
 *   B1  İskonto oranı sunucuda sınırlanmıyordu. %500 iskonto matrahı negatife
 *       çevirip NEGATİF KDV ve NEGATİF fatura toplamı üretiyor, bu tutar cari
 *       bakiyeye ters yönde işliyordu.
 *   B2  Perakende uç noktası ara toplam/KDV/genel toplamı İSTEMCİDEN alıyordu;
 *       depodan çıkan mal ile kaydedilen ciro birbirinden kopabiliyordu
 *       (12.000 TL'lik mal 1 TL olarak kaydedildi).
 *   B3  Perakendede miktar hiç doğrulanmıyordu; negatif miktarlı bir "satış"
 *       stoğu düşürmek yerine ARTIRIYORDU.
 *   B4  Belge numarası ön ekinin sabit 'SAT'/'ALI' olduğu varsayılıyordu; oysa
 *       ön ek şirket ayarından gelir. İrsaliye/proforma/numune satış serisinden
 *       numara götürüyor, aynı numara ikinci kez dağıtılıyordu.
 *
 * Bu testler İŞLEVSEL değil YAPISALdır: veritabanı gerektirmedikleri için CI'da
 * çalışabilirler. Amaç, düzeltmelerin geri alınmasını (ör. yeniden ham $_POST
 * okuyan bir kalem döngüsü eklenmesini) fark etmektir. Davranışın kendisi
 * ayrıca canlı ortamda uçtan uca doğrulanmıştır.
 *
 * NOT: Kalemlerin gerçek matematiğini sınayan işlevsel testler bilinçli olarak
 * buraya konmadı — depoda çekirdek tabloların şeması bulunmadığı için (B8) o
 * testler CI'da ayağa kaldırılamıyor. B8 kapandığında eklenmelidir.
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

/** Bir metodun gövdesini kaba ama yeterli biçimde ayıklar (süslü parantez sayarak). */
function metodGovdesi(string $kaynak, string $metodAdi): string
{
    if (!preg_match('/function\s+' . preg_quote($metodAdi, '/') . '\s*\([^)]*\)[^{]*\{/', $kaynak, $m, PREG_OFFSET_CAPTURE)) {
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

$satisYolu  = $kok . '/app/controllers/SatisController.php';
$alisYolu   = $kok . '/app/controllers/AlisController.php';
$faturaYolu = $kok . '/app/models/Fatura.php';

$satis  = oku($satisYolu);
$alis   = oku($alisYolu);
$fatura = oku($faturaYolu);

kontrol('Kaynak dosyalar okunabildi', $satis !== '' && $alis !== '' && $fatura !== '');

// ─────────────────────────────────────────────────────────────────────────
// 1) Tek kapı: kalem doğrulaması Fatura::kalemNormalize() üzerinden yapılmalı
// ─────────────────────────────────────────────────────────────────────────
$kalemUretenMetotlar = [
    ['SatisController', $satis,  'kaydet'],
    ['SatisController', $satis,  'guncelle'],
    ['SatisController', $satis,  'perakende_kaydet'],
    ['AlisController',  $alis,   'kaydet'],
    ['AlisController',  $alis,   'guncelle'],
];
foreach ($kalemUretenMetotlar as [$sinif, $kaynak, $metot]) {
    $govde = metodGovdesi($kaynak, $metot);
    kontrol(
        "{$sinif}::{$metot}() kalemleri Fatura::kalemNormalize() ile doğruluyor",
        $govde !== '' && str_contains($govde, 'Fatura::kalemNormalize('),
        $govde === '' ? 'metod gövdesi bulunamadı' : 'kalemNormalize çağrısı yok — B1/B3/B9 geri gelmiş olabilir'
    );
}

// Ham $_POST'tan doğrudan kalem kurulmamalı (kalemNormalize atlanmış olur).
foreach ([['SatisController', $satis], ['AlisController', $alis]] as [$sinif, $kaynak]) {
    $hamKalem = preg_match(
        "/'(?:iskonto_orani|kdv_orani|miktar)'\s*=>\s*\(float\)\s*\(?\\\$(?:_POST|kalem)/",
        $kaynak
    ) === 1;
    kontrol(
        "{$sinif} ham \$_POST değerinden kalem kurmuyor",
        !$hamKalem,
        'doğrudan (float)$_POST[...] ile kalem alanı kuruluyor'
    );
}

// ─────────────────────────────────────────────────────────────────────────
// 2) B2: perakende toplamları istemciden alınmamalı, kalemlerden hesaplanmalı
// ─────────────────────────────────────────────────────────────────────────
$perakende = metodGovdesi($satis, 'perakende_kaydet');
kontrol(
    'perakende_kaydet() toplamları kalemlerden hesaplıyor',
    $perakende !== '' && str_contains($perakende, 'Fatura::kalemToplamlari('),
    'Fatura::kalemToplamlari() çağrısı yok'
);
$istemciToplami = preg_match(
    "/'(?:ara_toplam|kdv_tutari|genel_toplam)'\s*=>\s*\(float\)\s*\\\$data\[/",
    $perakende
) === 1;
kontrol(
    'perakende_kaydet() fatura toplamlarını istemciden okumuyor',
    !$istemciToplami,
    "\$data['genelToplam'] / \$data['araToplam'] doğrudan faturaya yazılıyor — B2 geri gelmiş"
);

// ─────────────────────────────────────────────────────────────────────────
// 3) Toplam formülü tek yerde: controller'larda elle hesap kalmamalı
// ─────────────────────────────────────────────────────────────────────────
foreach ([['SatisController', $satis], ['AlisController', $alis]] as [$sinif, $kaynak]) {
    $elleHesap = preg_match("/\['iskonto_orani'\]\s*\/\s*100/", $kaynak) === 1;
    kontrol(
        "{$sinif} toplamları elle hesaplamıyor (Fatura::kalemToplamlari kullanıyor)",
        !$elleHesap,
        'satır içi iskonto/KDV hesabı geri gelmiş — formül yeniden ayrışabilir'
    );
}

// ─────────────────────────────────────────────────────────────────────────
// 4) B4: belge numarası ön eki sabit varsayılmamalı
// ─────────────────────────────────────────────────────────────────────────
foreach ([['SatisController', $satis], ['AlisController', $alis]] as [$sinif, $kaynak]) {
    $sabitOnEk = preg_match("/'(?:SAT|ALI)-'\s*\.\s*date\(/", $kaynak) === 1;
    kontrol(
        "{$sinif} sabit 'SAT-'/'ALI-' ön eki varsaymıyor",
        !$sabitOnEk,
        "ön ek şirket ayarından gelir (company_settings.invoice_prefix) — B4 geri gelmiş"
    );
    kontrol(
        "{$sinif} belge numarasını Fatura::belgeNoCozumle() ile çözüyor",
        str_contains($kaynak, 'belgeNoCozumle('),
        'belgeNoCozumle çağrısı yok'
    );
}

// faturaNoUret() sıradaki numarayı SERİ bazında bulmalı; belge_tipi'ne göre
// filtrelerse başka bir tipin aldığı numarayı görmez ve ikinci kez dağıtır.
$uretGovde = metodGovdesi($fatura, 'faturaNoUret');
kontrol(
    'Fatura::faturaNoUret() sıradaki numarayı belge_tipi ile filtrelemiyor',
    $uretGovde !== '' && !str_contains($uretGovde, 'belge_tipi = :tip'),
    'MAX sorgusu belge_tipi ile daraltılmış — çakışan numara yeniden dağıtılabilir'
);

// ─────────────────────────────────────────────────────────────────────────
// 5) Model katmanı son savunma hattı (B9) ve sınır sabiti
// ─────────────────────────────────────────────────────────────────────────
foreach (['ekle', 'guncelle'] as $metot) {
    $govde = metodGovdesi($fatura, $metot);
    kontrol(
        "Fatura::{$metot}() kalemleri assertKalemGecerli() ile denetliyor",
        $govde !== '' && str_contains($govde, 'assertKalemGecerli('),
        'programatik çağrılar doğrulamasız kalıyor'
    );
}
kontrol(
    'Oran sınırı (KALEM_MAX_ORAN) tanımlı ve 100',
    preg_match('/KALEM_MAX_ORAN\s*=\s*100(?:\.0)?;/', $fatura) === 1,
    'sınır sabiti değişmiş veya kaldırılmış'
);

// ─────────────────────────────────────────────────────────────────────────
// Rapor
// ─────────────────────────────────────────────────────────────────────────
$gecen = array_values(array_filter($sonuclar, static fn(array $s): bool => $s['ok']));
$kalan = array_values(array_filter($sonuclar, static fn(array $s): bool => !$s['ok']));

echo "=== Fatura kalem/toplam invariant regresyon taraması ===\n";
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
