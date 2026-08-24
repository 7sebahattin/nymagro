<?php
/**
 * Regresyon testi: 2026-08-24 talep turu — 4 ayrı düzeltme.
 * --------------------------------------------------------------------------
 * Çalıştırma:  php tests/regression/koli_numune_donem_ebelge_invariants.php
 * Çıkış kodu:  0 = tüm kontroller PASSED, 1 = en az bir FAILED.
 *
 * Kapsanan 4 düzeltme:
 *
 *  1) KOLİ/BİRİM ALANI TEKİLLEŞTİRME: Satış/Alış fatura satırlarında hem
 *     "Adet/Koli" giriş tipi seçici hem de genel "Birim" dropdown'ı aynı anda
 *     görünüyor, ikisi de "Adet" gösterdiği için kafa karıştırıyordu. Artık
 *     ayrı seçici YOK — Birim alanından "Koli" seçilince (ürünün koli içi
 *     adedi tanımlıysa) miktar otomatik koli bazlı sayılıyor.
 *  2) NUMUNE/İRSALİYE/PERAKENDE GÖRÜNÜRLÜĞÜ: Müşteri kartındaki "Önceki
 *     Satışlar" listesi yalnızca belge_tipi='satis' faturaları gösteriyordu;
 *     numune/irsaliye/perakende çıkışları o müşteride hiç görünmüyordu
 *     (stok düşmüş olsa bile). Artık bu tipler de listelenip "Tip" sütununda
 *     etiketleniyor (faturalandırılmış irsaliyeler mükerrer sayılmasın diye
 *     hariç tutuluyor).
 *  3) VARSAYILAN DÖNEM FİLTRESİ: Satışlar/Alışlar listeleri varsayılan olarak
 *     "Son 1 Ay" ile filtreliyordu, kullanıcı her seferinde "Tümü"ne
 *     tıklamak zorunda kalıyordu. Varsayılan artık 'tumu'.
 *  4) e-BELGE MÜKERRER UYARISI: XML/ZIP yükleme sonucunda TÜM dosyalar
 *     mükerrer (daha önce yüklenmiş) çıksa bile flash mesajı sessizce yeşil
 *     "success" gösteriyordu — kullanıcı yanlışlıkla ikinci kez eklediğini
 *     fark edemeyebiliyordu. Artık mükerrer varsa flash HER ZAMAN 'warning'.
 *
 * Veritabanı gerektirmez: kaynak denetimi + Reflection iledir.
 */

$kok = dirname(__DIR__, 2);

define('MODELS_PATH', $kok . '/app/models');
require_once $kok . '/app/core/TenantContext.php';
require_once $kok . '/app/models/Fatura.php';
require_once $kok . '/app/models/Cari.php';

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

/**
 * Controller sınıflarını (Controller/Depo/Cari/EBelge* gibi zincirleme
 * bağımlılıkları require etmeden) HAM KAYNAK metninden metot gövdesi çıkarır.
 * SatisController/AlisController/EBelgeController bu test dosyasında hiç
 * require edilmiyor — bu yüzden Reflection değil, basit parantez sayımı
 * kullanılır (yalnızca "...): void {" imzalı metotlar için).
 */
function metotGovdesiHamKaynaktan(string $kaynak, string $metot): string
{
    if (!preg_match('/function\s+' . preg_quote($metot, '/') . '\s*\([^)]*\)\s*:\s*void\s*\{/', $kaynak, $m, PREG_OFFSET_CAPTURE)) {
        return '';
    }
    $baslangic = $m[0][1] + strlen($m[0][0]);
    $derinlik = 1;
    $i = $baslangic;
    $len = strlen($kaynak);
    while ($i < $len && $derinlik > 0) {
        if ($kaynak[$i] === '{') {
            $derinlik++;
        } elseif ($kaynak[$i] === '}') {
            $derinlik--;
        }
        $i++;
    }
    return kodSadece('<?php ' . substr($kaynak, $baslangic, $i - $baslangic - 1));
}

// ═════════════════════════════════════════════════════════════════════
// 1) Koli/Birim alanı tekilleştirme
// ═════════════════════════════════════════════════════════════════════

foreach ([
    'app/views/satislar/ekle.php'    => 'kalem-input',
    'app/views/satislar/duzenle.php' => 'kalem-input',
    'app/views/alislar/ekle.php'     => 'fi',
    'app/views/alislar/duzenle.php'  => 'fi',
] as $yol => $_sinif) {
    $kaynak = oku($yol);
    $ad = basename(dirname($yol)) . '/' . basename($yol);

    kontrol("{$ad}: ayrı bir \"Adet/Koli\" <select name=\"kalem_giris_tipi[]\"> seçici artık YOK (kaldırıldı)",
        !(bool)preg_match('/<select\s+name="kalem_giris_tipi\[\]"/', $kaynak));

    kontrol("{$ad}: kalem_giris_tipi[] artık DAİMA gizli (hidden) bir alan olarak render ediliyor",
        (bool)preg_match('/<input type="hidden" name="kalem_giris_tipi\[\]"/', $kaynak));

    kontrol("{$ad}: Birim <select name=\"kalem_birim[]\"> alanı birimDegisti() ile bağlı (Koli seçilince koli bazlı sayım tetiklenir)",
        (bool)preg_match('/name="kalem_birim\[\]"[^>]*onchange="birimDegisti\(/', $kaynak));

    kontrol("{$ad}: birimDegisti() fonksiyonu tanımlı",
        (bool)preg_match('/function\s*\(?\s*birimDegisti|birimDegisti\s*=\s*function/', $kaynak) || str_contains($kaynak, 'window.birimDegisti = function'));

    kontrol("{$ad}: birimDegisti() Birim === 'Koli' VE koliIci > 0 ise gizli alanı 'koli' yapıyor",
        (bool)preg_match("/birimSel(?:\.value| &&[^}]*value)[^\n]*===\s*'Koli'[^\n]*koliIci\s*>\s*0[^\n]*\?\s*'koli'\s*:\s*'adet'/s", $kaynak)
        || (bool)preg_match("/=== 'Koli' && koliIci > 0\\) \\? 'koli' : 'adet'/", $kaynak));

    kontrol("{$ad}: BİRİM_LİSTESİ (birimSecenekleriHtml) hâlâ 'Koli' seçeneğini içeriyor",
        str_contains($kaynak, "'Koli'"));
}

// ekle.php'de: yeni satır, ürünün varsayılan birimi zaten 'Koli' ise ilk
// andan itibaren koli bazlı sayılsın (kullanıcı ekstra tıklama yapmasın) —
// duzenle.php'de ise GÜVENLİK GEREĞİ bu otomatik senkron YAPILMAZ (aksi halde
// veritabanında zaten adet cinsinden saklanmış bir miktar yeniden koliIci ile
// çarpılıp veri bozulurdu).
foreach (['app/views/satislar/ekle.php', 'app/views/alislar/ekle.php'] as $yol) {
    $kaynak = oku($yol);
    kontrol(basename(dirname($yol)) . '/' . basename($yol) . ": yeni kalem eklenirken ürünün birimi 'Koli' ise gizli giriş tipi başlangıçta 'koli' oluyor",
        (bool)preg_match("/birim === 'Koli' && koliIci > 0 \\? 'koli' : 'adet'/", $kaynak));
}
foreach (['app/views/satislar/duzenle.php', 'app/views/alislar/duzenle.php'] as $yol) {
    $kaynak = oku($yol);
    kontrol(basename(dirname($yol)) . '/' . basename($yol) . ": düzenleme ekranında gizli giriş tipi HER ZAMAN 'adet' ile başlıyor (zaten adet cinsinden saklı miktarın yanlışlıkla tekrar koliIci ile çarpılmasını önler)",
        (bool)preg_match('/<input type="hidden" name="kalem_giris_tipi\[\]" value="adet">/', $kaynak));
}

// ═════════════════════════════════════════════════════════════════════
// 2) Numune/İrsaliye/Perakende görünürlüğü (müşteri kartı)
// ═════════════════════════════════════════════════════════════════════

$satisGecmisiGovde = metotGovdesi('Cari', 'satisGecmisi');
kontrol("Cari::satisGecmisi() artık sadece 'satis' değil, 'perakende'/'irsaliye'/'numune' belge tiplerini de getiriyor",
    (bool)preg_match("/belge_tipi IN \\('satis', 'perakende', 'irsaliye', 'numune'\\)/", $satisGecmisiGovde));
kontrol("Cari::satisGecmisi() faturalandırılmış (irsaliye_kullanildi=1) irsaliyeleri HARİÇ tutuyor (mükerrer sayım/ciro şişmesin diye — aynı belge hem irsaliye hem oluşturduğu satış faturası olarak 2 kez görünmesin)",
    str_contains($satisGecmisiGovde, "irsaliye_kullanildi = 1"));

$partial = oku('app/views/partials/cari_detay_modern.php');
kontrol('cari_detay_modern.php: $belgeTipAdi yardımcı fonksiyonu tanımlı (numune/irsaliye/perakende için Türkçe etiket)',
    str_contains($partial, '$belgeTipAdi = static function'));
kontrol('cari_detay_modern.php: fatura tablosuna "Tip" sütunu eklendi',
    (bool)preg_match('/<th>Tip<\/th>/', $partial));
kontrol('cari_detay_modern.php: her satırda belge_tipi, $belgeTipAdi ile gösteriliyor',
    str_contains($partial, "\$belgeTipAdi((string)(\$row['belge_tipi']"));
kontrol('cari_detay_modern.php: detay satırı colspan yeni sütun sayısına (6) güncellendi',
    str_contains($partial, 'colspan="6"'));

// ═════════════════════════════════════════════════════════════════════
// 3) Varsayılan dönem filtresi: '1ay' değil 'tumu'
// ═════════════════════════════════════════════════════════════════════

$satisIndexGovde = metotGovdesiHamKaynaktan(oku('app/controllers/SatisController.php'), 'index');
kontrol('SatisController::index() metodu bulundu (kaynakta tanımlı)', $satisIndexGovde !== '');
kontrol("SatisController::index() \$donem varsayılanı artık 'tumu' ('1ay' DEĞİL)",
    (bool)preg_match("/\\\$_GET\\['donem'\\]\\s*\\?\\?\\s*'tumu'/", $satisIndexGovde));
kontrol("SatisController::index() içinde eski '1ay' varsayılanı kalmamış",
    !(bool)preg_match("/\\\$_GET\\['donem'\\]\\s*\\?\\?\\s*'1ay'/", $satisIndexGovde));

$alisIndexGovde = metotGovdesiHamKaynaktan(oku('app/controllers/AlisController.php'), 'index');
kontrol('AlisController::index() metodu bulundu (kaynakta tanımlı)', $alisIndexGovde !== '');
kontrol("AlisController::index() \$donem varsayılanı artık 'tumu' ('1ay' DEĞİL)",
    (bool)preg_match("/\\\$_GET\\['donem'\\]\\s*\\?\\?\\s*'tumu'/", $alisIndexGovde));
kontrol("AlisController::index() içinde eski '1ay' varsayılanı kalmamış",
    !(bool)preg_match("/\\\$_GET\\['donem'\\]\\s*\\?\\?\\s*'1ay'/", $alisIndexGovde));

// Fatura::donemFiltresi (veya benzeri) içinde 'tumu' hâlâ "filtre yok" anlamına
// gelmeli — aksi halde varsayılanı değiştirmek listeyi kırar (her satış/alış
// tek seferde tüm dönemi çekmeye çalışır ama filtre hâlâ '1ay' davranırsa
// kullanıcı yine sadece son ayı görür).
$rf = new ReflectionClass('Fatura');
$filtreMetotAdi = null;
foreach ($rf->getMethods() as $m) {
    if ($m->getDeclaringClass()->getName() !== 'Fatura') {
        continue;
    }
    $govde = metotGovdesi('Fatura', $m->getName());
    if (str_contains($govde, "case '1ay'") && str_contains($govde, 'switch')) {
        $filtreMetotAdi = $m->getName();
        break;
    }
}
kontrol("Fatura'nın dönem filtre switch'i bulunabildi (donem/filtre isimli bir metotta 'case \\'1ay\\'' var)",
    $filtreMetotAdi !== null, $filtreMetotAdi ?? '(bulunamadı)');
if ($filtreMetotAdi !== null) {
    $filtreGovde = metotGovdesi('Fatura', $filtreMetotAdi);
    kontrol("Fatura::{$filtreMetotAdi}() switch'inde 'tumu' için özel bir case YOK (yani hiçbir tarih filtresi eklenmiyor — tüm kayıtlar gelir)",
        !(bool)preg_match("/case 'tumu':/", $filtreGovde));
}

// ═════════════════════════════════════════════════════════════════════
// 4) e-Belge mükerrer uyarısı
// ═════════════════════════════════════════════════════════════════════

$ebelgeYukleGovde = metotGovdesiHamKaynaktan(oku('app/controllers/EBelgeController.php'), 'yukle');
kontrol('EBelgeController::yukle() metodu bulundu (kaynakta tanımlı)', $ebelgeYukleGovde !== '');
kontrol("EBelgeController::yukle() TÜM dosyalar mükerrer çıktığında (0 başarılı, 0 hatalı) ayrı ve net bir 'warning' mesajı veriyor",
    (bool)preg_match("/mukerrer'\\]\\s*>\\s*0\\s*&&\\s*\\\$ozet\\['basarili'\\]\\s*===\\s*0\\s*&&\\s*\\\$ozet\\['hatali'\\]\\s*===\\s*0/", $ebelgeYukleGovde));
kontrol("EBelgeController::yukle() mükerrer varsa (kısmi de olsa) flash rengi ARTIK asla sessizce 'success' olmuyor",
    (bool)preg_match("/\\(\\\$ozet\\['hatali'\\]\\s*>\\s*0\\s*\\|\\|\\s*\\\$ozet\\['mukerrer'\\]\\s*>\\s*0\\)\\s*\\?\\s*'warning'\\s*:\\s*'success'/", $ebelgeYukleGovde));
kontrol("EBelgeController::yukle() 'warning' verirken setFlash çağırıyor (kullanıcıya mutlaka gösteriliyor)",
    substr_count($ebelgeYukleGovde, "setFlash('warning'") >= 1);

// ═════════════════════════════════════════════════════════════════════
// Meta: PHP kapanış etiketi tuzağı yok
// ═════════════════════════════════════════════════════════════════════

foreach ([
    $kok . '/app/views/satislar/ekle.php',
    $kok . '/app/views/satislar/duzenle.php',
    $kok . '/app/views/alislar/ekle.php',
    $kok . '/app/views/alislar/duzenle.php',
    $kok . '/app/views/partials/cari_detay_modern.php',
    $kok . '/app/models/Cari.php',
    $kok . '/app/controllers/SatisController.php',
    $kok . '/app/controllers/AlisController.php',
    $kok . '/app/controllers/EBelgeController.php',
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
    // View dosyaları (HTML ile iç içe) PHP kapanış etiketini meşru olarak
    // kullanabilir; burada asıl aranan, test dosyasının KENDİSİNDE (ki HTML
    // içermez) böyle bir kapanış etiketi kesinlikle olmamasıdır.
    if ($dosya === __FILE__) {
        kontrol('Meta: ' . basename($dosya) . ' içinde PHP kapanış etiketi (?>) yok', !$kapali);
    }
}

// ═════════════════════════════════════════════════════════════════════
// Rapor
// ═════════════════════════════════════════════════════════════════════

$basarili = 0;
$basarisiz = [];
echo "=== Koli/Birim + Numune Görünürlüğü + Varsayılan Dönem + e-Belge Mükerrer regresyon testi ===\n\n";
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
