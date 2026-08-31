<?php
/**
 * Regresyon testi: "Hizmet bir ürün değildir, depoda stoklanmaz" kuralı.
 * --------------------------------------------------------------------------
 * Çalıştırma:  php tests/regression/stok_takibi_invariants.php
 * Çıkış kodu:  0 = tüm kontroller PASSED, 1 = en az bir FAILED.
 *
 * Arka plan (kullanıcı raporu — Stok Sayımı ekranı görüntüsü):
 *   "ARDİYE HİZMET BEDELİ" ve "YÜKLEME BOŞALTMA BEDELİ" kalemleri ANA DEPO'da
 *   1 adet stokla listeleniyordu. Hizmet kalemleri satılır/satın alınır ama
 *   ambara girmez (Logo, Mikro, Odoo, SAP dahil tüm stok uygulamalarında böyle).
 *
 * Kök neden:
 *   Kural sistemde vardı ama YALNIZCA Fatura::assertStokYeterli() içinde —
 *   yani "yeterli stok var mı" kontrolünde. Stok HAREKETİ yazan yol
 *   (Fatura.php: "if ($kalemVeri['urun_id']) { ... stokHareketiEkle(...) }")
 *   bu kuralı bilmediği için her fatura satırı, hizmet olsa bile, depoya
 *   giriş yazıyordu.
 *
 * Düzeltme (bu testin koruduğu sözleşme):
 *   1) Kural tek bir kaynaktan gelir: Urun::stokTakipKosuluSql() (SQL) ve
 *      Urun::stokTakipliSatirMi() (PHP).
 *   2) Kontrol, stok yazan YEDİ çağrının ortak geçidi olan
 *      Urun::stokHareketiEkle() içinde, ilk iş olarak yapılır.
 *   3) Depo/rapor/dashboard ekranları stok listelerken aynı koşulu kullanır.
 *   4) Düzeltmeden ÖNCE oluşmuş hatalı kayıtlar için onarım akışı vardır ve
 *      stok defterinden (stok_hareketleri) SİLME yapmaz — ters hareket yazar.
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

/** Yorumları ayıklar; kaynak denetimi yalnızca gerçek kodu görsün diye. */
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

/**
 * Metot gövdesini ham kaynaktan, süslü parantez sayarak çıkarır.
 * (Reflection kullanılmaz: Depo/Rapor/Controller sınıfları soyut Controller ve
 *  Database gibi bağımlılıkları gerektiriyor; bu test hiçbirini yüklemiyor.)
 */
function metotGovdesi(string $kod, string $metot): string
{
    $desen = '/function\s+' . preg_quote($metot, '/') . '\s*\([^)]*\)\s*(?::\s*\??[A-Za-z_\\\\|]+\s*)?\{/';
    if (!preg_match($desen, $kod, $m, PREG_OFFSET_CAPTURE)) {
        return '';
    }
    $baslangic = $m[0][1] + strlen($m[0][0]);
    $derinlik = 1;
    $i = $baslangic;
    $len = strlen($kod);
    while ($i < $len && $derinlik > 0) {
        if ($kod[$i] === '{') {
            $derinlik++;
        } elseif ($kod[$i] === '}') {
            $derinlik--;
        }
        $i++;
    }
    return substr($kod, $baslangic, $i - $baslangic - 1);
}

$urunKod      = kodSadece(oku('app/models/Urun.php'));
$depoKod      = kodSadece(oku('app/models/Depo.php'));
$raporKod     = kodSadece(oku('app/models/Rapor.php'));
$dashKod      = kodSadece(oku('app/controllers/DashboardController.php'));
$depoCtrlKod  = kodSadece(oku('app/controllers/DepoController.php'));
$faturaKod    = kodSadece(oku('app/models/Fatura.php'));
$depoView     = oku('app/views/depolar/index.php');

// ═════════════════════════════════════════════════════════════════════
// 1) Urun::stokTakipliSatirMi() — TÜM VARYASYONLAR (gerçek çalıştırma)
// ═════════════════════════════════════════════════════════════════════

$vakalar = [
    // [girdi, beklenen, açıklama]
    [['tip' => 'urun',   'stok_takibi' => 'normal'], true,  'ticari mal, normal takip'],
    [['tip' => 'urun',   'stok_takibi' => 'seri'],   true,  'ticari mal, seri no takibi'],
    [['tip' => 'urun',   'stok_takibi' => 'lot'],    true,  'ticari mal, lot takibi'],
    [['tip' => 'urun',   'stok_takibi' => 'yok'],    false, 'ticari mal ama takip kapalı'],
    [['tip' => 'hizmet', 'stok_takibi' => 'normal'], false, 'HİZMET (kullanıcının bildirdiği durum)'],
    [['tip' => 'hizmet', 'stok_takibi' => 'yok'],    false, 'hizmet + takip kapalı'],
    [['tip' => 'hizmet', 'stok_takibi' => 'seri'],   false, 'hizmet, takip alanı ne olursa olsun'],
    [['tip' => 'urun'],                              true,  'stok_takibi alanı yok → normal sayılır'],
    [['stok_takibi' => 'normal'],                    true,  'tip alanı yok → ürün sayılır'],
    [['tip' => 'urun',   'stok_takibi' => null],     true,  'stok_takibi NULL → normal (SQL COALESCE ile aynı)'],
    [['tip' => 'urun',   'stok_takibi' => ''],       true,  'stok_takibi boş metin → "yok" değil'],
    [['tip' => 'URUN',   'stok_takibi' => 'normal'], false, 'tip büyük harf → şemadaki değer değil, fail-closed'],
    [[],                                             false, 'boş satır → fail-closed'],
    [null,                                           false, 'ürün bulunamadı (null) → fail-closed'],
];
foreach ($vakalar as [$girdi, $beklenen, $aciklama]) {
    $gercek = Urun::stokTakipliSatirMi($girdi);
    kontrol(
        'stokTakipliSatirMi: ' . $aciklama . ' → ' . ($beklenen ? 'stoklanır' : 'stoklanmaz'),
        $gercek === $beklenen,
        'beklenen=' . var_export($beklenen, true) . ' gerçek=' . var_export($gercek, true)
    );
}

// ═════════════════════════════════════════════════════════════════════
// 2) Urun::stokTakipKosuluSql() — SQL karşılığı aynı kuralı ifade etmeli
// ═════════════════════════════════════════════════════════════════════

$sqlU  = Urun::stokTakipKosuluSql('u');
$sqlUH = Urun::stokTakipKosuluSql('urunler_hizmetler');

kontrol('stokTakipKosuluSql varsayılan takma ad "u"',
    Urun::stokTakipKosuluSql() === $sqlU);
kontrol('stokTakipKosuluSql: tip = urun koşulu var',
    str_contains($sqlU, "u.tip = 'urun'"), $sqlU);
kontrol('stokTakipKosuluSql: stok takibi "yok" olanları dışlar',
    str_contains($sqlU, "<> 'yok'"), $sqlU);
kontrol('stokTakipKosuluSql: NULL stok_takibi COALESCE ile normal sayılır (PHP tarafıyla aynı)',
    str_contains($sqlU, "COALESCE(u.stok_takibi, 'normal')"), $sqlU);
kontrol('stokTakipKosuluSql: takma ad her iki koşula da uygulanır',
    substr_count($sqlUH, 'urunler_hizmetler.') === 2, $sqlUH);
kontrol('stokTakipKosuluSql: sabit "u." takma adı sızmıyor',
    !str_contains($sqlUH, 'u.tip') && !str_contains($sqlUH, 'u.stok_takibi'), $sqlUH);
kontrol('stokTakipKosuluSql: iki koşul AND ile bağlı (OR değil)',
    str_contains($sqlU, ' AND ') && !str_contains($sqlU, ' OR '), $sqlU);
kontrol('stokTakipKosuluSql: parametre yer tutucusu içermez (WHERE parçasına doğrudan gömülüyor)',
    !str_contains($sqlU, ':'), $sqlU);

// ═════════════════════════════════════════════════════════════════════
// 3) stokHareketiEkle() — TÜM stok yollarının ortak geçidi
// ═════════════════════════════════════════════════════════════════════

$hareketGovde = metotGovdesi($urunKod, 'stokHareketiEkle');
kontrol('stokHareketiEkle() gövdesi kaynakta bulundu', $hareketGovde !== '');

$kapiPos   = strpos($hareketGovde, 'stokTakipliMi');
$insertPos = strpos($hareketGovde, "insert('stok_hareketleri'");

kontrol('stokHareketiEkle(): stok takibi kapısı var',
    $kapiPos !== false,
    'stokTakipliMi() çağrısı yok — hizmet kalemleri yine depoya girer');
kontrol('stokHareketiEkle(): kapı, deftere yazmadan ÖNCE çalışır',
    $kapiPos !== false && $insertPos !== false && $kapiPos < $insertPos,
    'kapı=' . var_export($kapiPos, true) . ' insert=' . var_export($insertPos, true));
kontrol('stokHareketiEkle(): kapı olumsuz koşulla (!) kurulmuş',
    (bool)preg_match('/if\s*\(\s*!\s*\$this->stokTakipliMi\s*\(\s*\$urunId\s*\)\s*\)/', $hareketGovde));
kontrol('stokHareketiEkle(): kapıya takılan satır SESSİZCE atlanır (true döner, istisna atmaz)',
    (bool)preg_match('/stokTakipliMi\s*\(\s*\$urunId\s*\)\s*\)\s*\{\s*return\s+true\s*;/', $hareketGovde),
    'Hizmet kalemli bir fatura kaydı bozulmamalı; yalnızca stok hareketi yazılmamalı');
kontrol('stokHareketiEkle(): kapı, transaction açılmadan önce çalışır (boşuna begin/commit yok)',
    $kapiPos !== false && strpos($hareketGovde, 'begin(') !== false
        && $kapiPos < strpos($hareketGovde, 'begin('));
kontrol('stokHareketiEkle(): depo kırılımı (urun_stok_depo) da kapının arkasında',
    $kapiPos !== false && strpos($hareketGovde, 'urun_stok_depo') !== false
        && $kapiPos < strpos($hareketGovde, 'urun_stok_depo'));

// stokTakipliMi(): fail-closed + tenant izolasyonu + önbellek
$takipliMiGovde = metotGovdesi($urunKod, 'stokTakipliMi');
kontrol('stokTakipliMi() gövdesi kaynakta bulundu', $takipliMiGovde !== '');
kontrol('stokTakipliMi(): geçersiz id (<= 0) için false döner',
    (bool)preg_match('/\$urunId\s*<=\s*0\s*\)\s*\{\s*return\s+false\s*;/', $takipliMiGovde));
kontrol('stokTakipliMi(): sorgu company_id ile sınırlı (tenant sızıntısı yok)',
    str_contains($takipliMiGovde, 'company_id = :cid'));
kontrol('stokTakipliMi(): önbellek anahtarı company_id içerir (şirket değişince karışmaz)',
    (bool)preg_match('/\$anahtar\s*=\s*\$urunId\s*\.\s*.:.\s*\.\s*\(int\)TenantContext::activeCompanyId\(\)/', $takipliMiGovde));
kontrol('stokTakipliMi(): kararı ortak saf fonksiyona devreder (kural kopyalanmamış)',
    str_contains($takipliMiGovde, 'stokTakipliSatirMi'));

// Önbellek tazeliği: ürün kaydı/güncellemesi tip veya stok_takibi değiştirebilir
$ekleGovde     = metotGovdesi($urunKod, 'ekle');
$guncelleGovde = metotGovdesi($urunKod, 'guncelle');
kontrol('Urun::ekle() önbelleği temizler (yeni kartın tipi hemen geçerli olsun)',
    str_contains($ekleGovde, 'stokTakipCacheTemizle'));
kontrol('Urun::guncelle() önbelleği temizler (ürün → hizmet dönüşümü aynı istekte geçerli olsun)',
    str_contains($guncelleGovde, 'stokTakipCacheTemizle'));

// ═════════════════════════════════════════════════════════════════════
// 3b) Kart alanları normalizasyonu — hizmet kartında stok parametresi olmaz
//     (gerçek çalıştırma; ürün formu, e-Belge aktarımı ve vitrin senkronu
//      dahil TÜM yazma yolları Urun::ekle()/guncelle() üzerinden geçer)
// ═════════════════════════════════════════════════════════════════════

$normalize = new ReflectionMethod('Urun', 'kartAlanlariniNormalize');
$normalize->setAccessible(true);
$nrm = static fn(array $temiz, ?array $mevcut = null): array => $normalize->invoke(null, $temiz, $mevcut);

$yeniHizmet = $nrm(['tip' => 'hizmet', 'ad' => 'ARDİYE HİZMET BEDELİ', 'stok_takibi' => 'normal', 'kritik_stok' => 5]);
kontrol('Yeni HİZMET kartında stok takibi kapatılır',
    ($yeniHizmet['stok_takibi'] ?? null) === 'yok', var_export($yeniHizmet, true));
kontrol('Yeni HİZMET kartında kritik stok sıfırlanır',
    (float)($yeniHizmet['kritik_stok'] ?? -1) === 0.0);
kontrol('Yeni HİZMET kartı sıfır stokla açılır',
    (float)($yeniHizmet['stok_miktari'] ?? -1) === 0.0);
kontrol('Normalizasyondan geçen hizmet kartı stok koşulunu sağlamaz (uçtan uca tutarlılık)',
    Urun::stokTakipliSatirMi($yeniHizmet) === false);

$yeniUrun = $nrm(['tip' => 'urun', 'ad' => 'Çuval', 'stok_takibi' => 'lot', 'kritik_stok' => 5, 'stok_miktari' => 12]);
kontrol('Ticari mal kartına dokunulmaz (stok_takibi korunur)',
    ($yeniUrun['stok_takibi'] ?? null) === 'lot');
kontrol('Ticari mal kartında kritik stok korunur',
    (float)($yeniUrun['kritik_stok'] ?? 0) === 5.0);
kontrol('Ticari mal kartında açılış stoğu korunur',
    (float)($yeniUrun['stok_miktari'] ?? 0) === 12.0);

kontrol('Tanınmayan tip "urun" kabul edilir (fail-closed davranış ürünü stok dışına düşürmesin)',
    ($nrm(['tip' => 'HİZMET'])['tip'] ?? null) === 'urun');
kontrol('Boş tip "urun" kabul edilir',
    ($nrm(['tip' => ''])['tip'] ?? null) === 'urun');
kontrol('Tanınmayan stok_takibi "normal" kabul edilir',
    ($nrm(['tip' => 'urun', 'stok_takibi' => 'evet'])['stok_takibi'] ?? null) === 'normal');
foreach (['normal', 'seri', 'lot', 'yok'] as $mod) {
    kontrol("Geçerli stok takip modu korunur: {$mod}",
        ($nrm(['tip' => 'urun', 'stok_takibi' => $mod])['stok_takibi'] ?? null) === $mod);
}
kontrol('tip gönderilmemişse alan eklenmez (kısmi güncelleme mevcut tipi ezmez)',
    !array_key_exists('tip', $nrm(['ad' => 'X'], ['tip' => 'hizmet'])));

// Güncelleme: kartın mevcut tipi hizmetse, alan gönderilmese bile kural işler
$mevcutHizmet = $nrm(['ad' => 'ARDİYE HİZMET BEDELİ'], ['tip' => 'hizmet', 'stok_miktari' => 1]);
kontrol('Mevcut hizmet kartı güncellenirken stok takibi yine kapatılır',
    ($mevcutHizmet['stok_takibi'] ?? null) === 'yok');
kontrol('Güncellemede stok_miktari SESSİZCE sıfırlanmaz (defter kaydı olmadan envanter silinmez)',
    !array_key_exists('stok_miktari', $mevcutHizmet),
    'Böyle bir kart Depolar sayfasındaki hatalı stok taramasına düşer ve onarımla düzeltilir');

$urunToHizmet = $nrm(['tip' => 'hizmet'], ['tip' => 'urun', 'stok_takibi' => 'normal', 'stok_miktari' => 3]);
kontrol('Ürün → hizmet dönüşümünde stok takibi kapatılır',
    ($urunToHizmet['stok_takibi'] ?? null) === 'yok');
kontrol('Ürün → hizmet dönüşümünde mevcut stok sessizce silinmez',
    !array_key_exists('stok_miktari', $urunToHizmet));

$ekleGovdeN = metotGovdesi($urunKod, 'ekle');
kontrol('Urun::ekle() normalizasyondan geçer',
    str_contains($ekleGovdeN, 'kartAlanlariniNormalize'));
kontrol('Urun::guncelle() normalizasyondan geçer',
    str_contains($guncelleGovde, 'kartAlanlariniNormalize'));

// ═════════════════════════════════════════════════════════════════════
// 4) Kök neden: Fatura kalem döngüsü artık kendi kuralını taşımıyor
// ═════════════════════════════════════════════════════════════════════

kontrol('Fatura.php stok hareketini Urun::stokHareketiEkle() üzerinden yazar (kapıdan geçer)',
    str_contains($faturaKod, 'stokHareketiEkle'));
kontrol('Fatura.php stok_hareketleri tablosuna doğrudan INSERT yapmaz (kapıyı atlayamaz)',
    !preg_match("/insert\s*\(\s*'stok_hareketleri'/", $faturaKod));

// ═════════════════════════════════════════════════════════════════════
// 5) Depo ekranları: stoklanmayan kalem listelenmez
// ═════════════════════════════════════════════════════════════════════

kontrol('Depo.php, Urun.php dosyasını yükler (statik kural çağrısı için)',
    (bool)preg_match("/require_once\s+__DIR__\s*\.\s*'\/Urun\.php'/", $depoKod));

$stokDurumuGovde = metotGovdesi($depoKod, 'stokDurumu');
$depoDegeriGovde = metotGovdesi($depoKod, 'depoDegeri');
kontrol('Depo::stokDurumu() gövdesi bulundu', $stokDurumuGovde !== '');
kontrol('Depo::stokDurumu() ortak stok koşulunu kullanır (Stok Sayımı ekranı buradan beslenir)',
    str_contains($stokDurumuGovde, 'Urun::stokTakipKosuluSql'),
    'Kullanıcının bildirdiği hata tam olarak buydu: hizmetler ANA DEPO listesinde çıkıyordu');
kontrol('Depo::depoDegeri() ortak stok koşulunu kullanır (depo değeri hizmetle şişmesin)',
    str_contains($depoDegeriGovde, 'Urun::stokTakipKosuluSql'));
kontrol('Depo.php kuralı elle kopyalamamış (tek kaynak ilkesi)',
    !str_contains($depoKod, "stok_takibi, 'normal') <> 'yok'"));

// ═════════════════════════════════════════════════════════════════════
// 6) Raporlar
// ═════════════════════════════════════════════════════════════════════

kontrol('Rapor.php, Urun.php dosyasını yükler',
    (bool)preg_match("/require_once.+Urun\.php/", $raporKod));

$stokRaporlari = [
    'getProductStockReport'          => 'Ürün Stok Raporu',
    'getWarehouseStatusReport'       => 'Depo Durum Raporu',
    'getInactiveProductsReport'      => 'Hareketsiz Ürünler',
    'getStockSalesCoverageReportFull' => 'Stok-Satış Karşılama',
];
foreach ($stokRaporlari as $metot => $baslik) {
    $govde = metotGovdesi($raporKod, $metot);
    kontrol("Rapor::{$metot}() ({$baslik}) stok koşulunu kullanır",
        $govde !== '' && str_contains($govde, 'Urun::stokTakipKosuluSql'));
}

// Bilinçli istisna: Ürün Alış-Satış Raporu bir STOK raporu değil, alım-satım
// raporudur. Stok takibi kapalı bir ÜRÜNÜN gerçek alış/satışları gizlenmemeli;
// bu yüzden burada yalnızca tip = 'urun' filtresi vardır.
$alisSatisGovde = metotGovdesi($raporKod, 'getProductPurchaseSalesReport');
kontrol('Rapor::getProductPurchaseSalesReport() gövdesi bulundu', $alisSatisGovde !== '');
kontrol('Ürün Alış-Satış Raporu hizmetleri dışlar (kullanıcı sorusu: "hizmet bedeli de listeleniyor")',
    str_contains($alisSatisGovde, "u.tip = 'urun'"));
kontrol('Ürün Alış-Satış Raporu BİLİNÇLİ olarak stok takibi koşulunu KULLANMAZ',
    !str_contains($alisSatisGovde, 'stokTakipKosuluSql'),
    'Takibi kapalı bir ürünün gerçek alış/satış rakamları rapordan gizlenmemeli');

// ═════════════════════════════════════════════════════════════════════
// 7) Dashboard: stok değeri ve stok vitrini
// ═════════════════════════════════════════════════════════════════════

kontrol('DashboardController Urun.php dosyasını yükler',
    (bool)preg_match("/require_once.+Urun\.php/", $dashKod));
kontrol('Dashboard stok sorguları ortak koşulu kullanır (3 sorgu)',
    substr_count($dashKod, 'Urun::stokTakipKosuluSql') === 3,
    'bulunan: ' . substr_count($dashKod, 'Urun::stokTakipKosuluSql'));
kontrol('Dashboard artık çıplak "tip = urun" filtresi kullanmıyor',
    !str_contains($dashKod, "tip = 'urun'"));

// ─── Şirket sağlık metriği ────────────────────────────────────────────
$companyKod = kodSadece(oku('app/models/Company.php'));
$negatifGovde = metotGovdesi($companyKod, 'negativeStockCount');
kontrol('Company::negativeStockCount() gövdesi bulundu', $negatifGovde !== '');
kontrol('Negatif stok sayacı yalnızca stoklanan kartları sayar (hizmette negatif stok anlamsız)',
    str_contains($negatifGovde, 'Urun::stokTakipKosuluSql'));

// ═════════════════════════════════════════════════════════════════════
// 8) Tespit (salt-okunur) ve onarım (defter güvenli)
// ═════════════════════════════════════════════════════════════════════

$tespitGovde = metotGovdesi($urunKod, 'stokTakipDisiHataliKayitlar');
kontrol('stokTakipDisiHataliKayitlar() gövdesi bulundu', $tespitGovde !== '');
kontrol('Tespit salt-okunurdur (INSERT/UPDATE/DELETE yok)',
    !preg_match('/\b(INSERT|UPDATE|DELETE)\b/i', $tespitGovde));
kontrol('Tespit, ortak kuralın DEĞİLİNİ arar (NOT (...))',
    str_contains($tespitGovde, 'NOT (') && str_contains($tespitGovde, 'stokTakipKosuluSql'));
kontrol('Tespit yalnızca gerçekten sorunlu kayıtları getirir (stok / depo satırı)',
    str_contains($tespitGovde, 'HAVING') && str_contains($tespitGovde, 'stok_miktari <> 0'));
kontrol('Tespit aktif şirketle sınırlı', str_contains($tespitGovde, 'company_id'));
kontrol('Tespit silinmiş kartları atlar', str_contains($tespitGovde, 'silindi_mi = 0'));

// REGRESYON (kullanıcı raporu): onarım sonrası banner tekrar çıkıyordu.
// Sebep: HAVING koşulu geçmiş hareket SAYISINI (hareket_sayisi > 0) da
// tetikleyici sayıyordu. stok_hareketleri append-only bir defter; onarımın
// kendisi bile deftere düzeltme hareketi yazıyor. Yani bir kez onarılan
// kalem, geçmiş hareketi olduğu için SONSUZA DEK "hatalı" görünüyordu.
kontrol('Tespit, HAVING koşulunda geçmiş hareket SAYISINI tetikleyici olarak kullanmıyor',
    !preg_match('/HAVING[^;]*hareket_sayisi/is', $tespitGovde),
    'Defter append-only; onarımın kendisi bile hareket yazdığı için onarılmış kalem sonsuza dek hatalı görünürdü');

$onarGovde = metotGovdesi($urunKod, 'stokTakipDisiKayitlariOnar');
kontrol('stokTakipDisiKayitlariOnar() gövdesi bulundu', $onarGovde !== '');
kontrol('Onarım, düzeltilecek kayıtları tespit metodundan alır (aynı tanım)',
    str_contains($onarGovde, 'stokTakipDisiHataliKayitlar'));
kontrol('Onarım stok DEFTERİNE ters hareket YAZAR (iz korunur)',
    (bool)preg_match("/insert\s*\(\s*'stok_hareketleri'/", $onarGovde));
kontrol('Onarım stok defterinden SİLMEZ (append-only; stok_hareketleri tablosunda silindi_mi yok)',
    !preg_match('/DELETE\s+FROM\s+stok_hareketleri/i', $onarGovde));
kontrol('Ters hareketin yönü bakiyenin işaretine göre belirlenir',
    (bool)preg_match("/\\\$miktar\s*>\s*0\s*\?\s*'cikis'\s*:\s*'giris'/", $onarGovde));
kontrol('Ters hareket mutlak değerle yazılır (negatif miktar defterine yazılmaz)',
    str_contains($onarGovde, 'abs($miktar)'));
kontrol('Onarım depo kırılımını temizler', (bool)preg_match('/DELETE\s+FROM\s+urun_stok_depo/i', $onarGovde));
kontrol('Onarım kart üzerindeki toplam stoğu sıfırlar',
    (bool)preg_match('/UPDATE\s+urunler_hizmetler\s+SET\s+stok_miktari\s*=\s*0/i', $onarGovde));
kontrol('Onarım tek transaction içinde çalışır (yarım kalmaz)',
    str_contains($onarGovde, 'begin(') && str_contains($onarGovde, 'commit('));
kontrol('Onarım hata durumunda geri alınır',
    (bool)preg_match('/rollback\\s*\\(/i', $onarGovde));
kontrol('Onarım denetim kaydı (Audit) yazar',
    str_contains($onarGovde, 'Audit::log'));
kontrol('Onarım idempotenttir: düzeltilecek kayıt yoksa hiçbir şey yapmaz',
    (bool)preg_match('/if\s*\(\s*!\s*\$hatalilar\s*\)\s*\{\s*return/', $onarGovde));
kontrol('Onarım tüm yazma sorgularını company_id ile sınırlar',
    substr_count($onarGovde, 'company_id') >= 4);

// ═════════════════════════════════════════════════════════════════════
// 9) Onarım ucu: yetki + POST
// ═════════════════════════════════════════════════════════════════════

$stokOnarGovde = metotGovdesi($depoCtrlKod, 'stokOnar');
kontrol('DepoController::stokOnar() tanımlı', $stokOnarGovde !== '');
kontrol('stokOnar yalnızca POST kabul eder (CSRF koruması POST üzerinden işler)',
    str_contains($stokOnarGovde, "REQUEST_METHOD'] !== 'POST'"));
kontrol('stokOnar DEPO_UPDATE yetkisini AÇIKÇA arar (ad bazlı sınıflandırma bunu VIEW sayardı)',
    (bool)preg_match("/Rbac::currentUserCan\s*\(\s*'DEPO_UPDATE'\s*\)/", $stokOnarGovde));
kontrol('stokOnar yetkisiz istekte 403 döner',
    str_contains($stokOnarGovde, 'http_response_code(403)'));
kontrol('stokOnar hatayı kullanıcıya taşır (beyaz ekran yok)',
    str_contains($stokOnarGovde, 'catch') && str_contains($stokOnarGovde, 'setFlash'));

$indexGovde = metotGovdesi($depoCtrlKod, 'index');
kontrol('Depolar sayfası hatalı stok taramasını görünüme aktarır',
    str_contains($indexGovde, 'stokTakipDisiHataliKayitlar') && str_contains($indexGovde, 'hataliStoklar'));
kontrol('Tarama başarısız olursa depolar sayfası yine de açılır (try/catch)',
    str_contains($indexGovde, 'catch'));

kontrol('Depolar görünümü uyarıyı yalnızca hatalı kayıt varsa gösterir',
    str_contains($depoView, 'if (!empty($hataliStoklar))'));
kontrol('Onarım düğmesi DEPO_UPDATE yetkisiyle sınırlı',
    (bool)preg_match("/currentUserCan\('DEPO_UPDATE'\).+?stokOnar/s", $depoView));
kontrol('Onarım düğmesi POST + CSRF ile gönderir (nymPost)',
    (bool)preg_match('/nymPost\\(.[^\\n]*\\/depo\\/stokOnar/', $depoView));
kontrol('Onarım düğmesi onay ister',
    (bool)preg_match('/stokOnar.,\s*.[^\']*Devam edilsin mi\?/', $depoView));
kontrol('Görünümde ürün adları kaçışlanır (XSS)',
    str_contains($depoView, 'htmlspecialchars($h[' . "'ad'" . '])'));

// ═════════════════════════════════════════════════════════════════════
// 9b) Ürün formu: hizmet seçilince stok alanları gösterilmez
//     (yalnızca arayüz kolaylığı — asıl kural sunucuda; JS kapalıyken de
//      Urun::ekle()/guncelle() normalizasyonu veriyi tutarlı tutar)
// ═════════════════════════════════════════════════════════════════════

foreach (['app/views/urunler/ekle.php', 'app/views/urunler/duzenle.php'] as $form) {
    $kaynak = oku($form);
    $ad = basename($form);
    kontrol("{$ad}: üç stok alanı da işaretlenmiş (stok takibi, kritik stok, stok miktarı)",
        substr_count($kaynak, 'class="fg js-stok-alani"') === 3,
        'bulunan: ' . substr_count($kaynak, 'class="fg js-stok-alani"'));
    kontrol("{$ad}: tip seçimi değişince alanlar güncelleniyor",
        str_contains($kaynak, "tip.addEventListener('change', uygula)"));
    kontrol("{$ad}: sayfa açılışında da uygulanıyor (kayıtlı hizmet kartında alanlar gizli açılır)",
        (bool)preg_match('/uygula\(\);\s*\}\)\(\);/', $kaynak));
    kontrol("{$ad}: hizmet seçilince bilgi notu gösteriliyor",
        str_contains($kaynak, 'hizmetStokNotu'));
    kontrol("{$ad}: alanlar DOM'dan silinmiyor, sadece gizleniyor (hidden)",
        str_contains($kaynak, 'el.hidden = hizmet'));
    kontrol("{$ad}: varsayılan tip 'urun' açıkça seçili",
        str_contains($kaynak, "(\$eski['tip'] ?? 'urun') === 'urun'"));
}

kontrol('panel-ui.css: [hidden] kuralı display:flex kapsayıcıları da gizler',
    (bool)preg_match('/\[hidden\]\s*\{\s*display:\s*none\s*!important/', oku('public/css/panel-ui.css')),
    '.fg { display:flex } kuralı tarayıcının varsayılan [hidden] davranışını eziyordu');

// ═════════════════════════════════════════════════════════════════════
// 10) Meta: PHP kapanış etiketi tuzağı
//     (Türkçe yorum içinde kapanış etiketi geçerse dosya sessizce kesilir;
//      php -l bunu YAKALAMAZ. Bu yüzden ayrıca denetlenir.)
// ═════════════════════════════════════════════════════════════════════

foreach ([
    'app/models/Urun.php',
    'app/models/Depo.php',
    'app/models/Rapor.php',
    'app/controllers/DepoController.php',
    'app/controllers/DashboardController.php',
    'app/models/Company.php',
    __FILE__,
] as $dosya) {
    $kaynak = str_starts_with($dosya, '/') ? (string)@file_get_contents($dosya) : oku($dosya);
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
echo "=== Stok takibi (hizmet depoda stoklanmaz) regresyon testi ===\n\n";
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
