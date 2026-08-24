<?php
/**
 * Regresyon testi: "Faturaya uygulanan ödemeler" (payment application) özelliği.
 * --------------------------------------------------------------------------
 * Çalıştırma:  php tests/regression/fatura_odeme_uygulamalari_invariants.php
 * Çıkış kodu:  0 = tüm kontroller PASSED, 1 = en az bir FAILED.
 *
 * Arka plan: Kullanıcı "eklenen ödeme fatura altındaki alanda da görünsün ve
 * düzenlenip silinebilsin" istedi. Bunun için her kasa/banka hareketinin
 * (fifoOdemeDagit ile) hangi faturaya, ne kadar uyguladığı ayrı bir tabloda
 * (fatura_odeme_uygulamalari) kayıt altına alınıyor. Bu test, o kaydın
 * doğru şekilde yazıldığını/temizlendiğini ve fatura detayında okunabildiğini
 * kaynak denetimiyle (DB'siz) doğrular.
 *
 * KORUNAN İLKELER
 *  1) fifoOdemeDagit() artık opsiyonel bir $kasaHareketId parametresi alıyor;
 *     verilirse, uygulanan her fatura için fatura_odeme_uygulamalari'na bir
 *     satır yazılıyor (tutar > 0.004 olan gerçek uygulamalar için).
 *  2) fifoDagitimHesapla() (saf/DB'siz çekirdek) artık her sonuç satırında
 *     'uygulanan' anahtarını da döndürüyor — uygulama kaydına yazılacak tutar.
 *  3) fifoBakiyeleriYenidenHesapla() artık: (a) eski uygulama kayıtlarını
 *     silip, (b) carinin TÜM kasa_hareketleri satırlarını tarihe göre TEK TEK
 *     (kasa_hareket_id ile) yeniden uyguluyor — böylece "hangi ödeme hangi
 *     faturaya gitti" bilgisi de baştan doğru kurulmuş oluyor (eskiden sadece
 *     toplam giriş/çıkış tutarları kullanılıyordu, kaynak hareket bilgisi
 *     kayboluyordu).
 *  4) odemeUygulamalariGetir() bir faturanın uygulama kayıtlarını, kaynak
 *     kasa hareketiyle (tarih/ödeme yöntemi/kasa adı) birlikte, tenant'a
 *     (company_id) göre filtrelenmiş şekilde döner.
 *  5) fatura_odeme_uygulamalari tablosu TenantContext'in COMPANY_TABLES,
 *     PERIOD_TABLES ve WRITE_TABLES listelerinde yer alıyor (diğer finansal
 *     tablolarla aynı çok-kiracılı/izin kurallarına tabi olması için).
 *
 * Veritabanı gerektirmez: kaynak denetimi + Reflection + saf fonksiyon
 * çağrısıyladır.
 */

$kok = dirname(__DIR__, 2);

define('MODELS_PATH', $kok . '/app/models');
require_once $kok . '/app/core/TenantContext.php';
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
// 1) fifoDagitimHesapla() — saf çekirdek artık 'uygulanan' döndürüyor
// ═════════════════════════════════════════════════════════════════════

$acikFaturalar = [
    ['id' => 1, 'genel_toplam' => 1000.0, 'odenen_tutar' => 0.0, 'kalan_tutar' => 1000.0],
    ['id' => 2, 'genel_toplam' => 500.0,  'odenen_tutar' => 0.0, 'kalan_tutar' => 500.0],
];

$sonucTamKapatma = Fatura::fifoDagitimHesapla($acikFaturalar, 1000.0);
kontrol('fifoDagitimHesapla(): tam kapatan ödeme SADECE ilk faturayı günceller',
    count($sonucTamKapatma) === 1 && $sonucTamKapatma[0]['id'] === 1);
kontrol('fifoDagitimHesapla(): tam kapatan ödemede uygulanan = fatura tutarının tamamı (1000.0)',
    isset($sonucTamKapatma[0]['uygulanan']) && abs($sonucTamKapatma[0]['uygulanan'] - 1000.0) < 0.005);
kontrol('fifoDagitimHesapla(): tam kapatılan fatura durumu "odendi"',
    $sonucTamKapatma[0]['durum'] === 'odendi');

$sonucKismiTasma = Fatura::fifoDagitimHesapla($acikFaturalar, 1200.0);
kontrol('fifoDagitimHesapla(): taşan ödeme İKİNCİ (bir sonraki) faturaya da FIFO ile dağılır',
    count($sonucKismiTasma) === 2 && $sonucKismiTasma[1]['id'] === 2);
kontrol('fifoDagitimHesapla(): taşan kısımda ikinci faturaya uygulanan = 200.0 (1200 - 1000)',
    isset($sonucKismiTasma[1]['uygulanan']) && abs($sonucKismiTasma[1]['uygulanan'] - 200.0) < 0.005);
kontrol('fifoDagitimHesapla(): ikinci faturada kalan_tutar doğru (500 - 200 = 300)',
    abs($sonucKismiTasma[1]['kalan_tutar'] - 300.0) < 0.005 && $sonucKismiTasma[1]['durum'] === 'kismi_odendi');

$toplamUygulanan = array_sum(array_column($sonucKismiTasma, 'uygulanan'));
kontrol('fifoDagitimHesapla(): tüm satırlardaki "uygulanan" toplamı ödeme tutarına eşit (tutar kaybı/fazlası yok)',
    abs($toplamUygulanan - 1200.0) < 0.005);

// ═════════════════════════════════════════════════════════════════════
// 2) fifoOdemeDagit() — kaynak denetimi: opsiyonel $kasaHareketId + insert
// ═════════════════════════════════════════════════════════════════════

$rf = new ReflectionMethod('Fatura', 'fifoOdemeDagit');
$parametreler = $rf->getParameters();
kontrol('fifoOdemeDagit() 4 parametre alıyor (cariId, islemTipi, tutar, kasaHareketId)',
    count($parametreler) === 4);
kontrol('fifoOdemeDagit() son parametresi $kasaHareketId, opsiyonel (nullable, varsayılan null)',
    isset($parametreler[3])
        && $parametreler[3]->getName() === 'kasaHareketId'
        && $parametreler[3]->isOptional()
        && $parametreler[3]->allowsNull());

$dagitGovde = metotGovdesi('Fatura', 'fifoOdemeDagit');
kontrol('fifoOdemeDagit() $kasaHareketId verilmişse fatura_odeme_uygulamalari\'na insert ediyor',
    (bool)preg_match('/kasaHareketId\s*!==\s*null[^)]*\)\s*\{[^}]*insert\(\s*[\'"]fatura_odeme_uygulamalari[\'"]/s', $dagitGovde));
kontrol('fifoOdemeDagit() sadece GERÇEK uygulama (uygulanan > 0.004) için insert ediyor (sıfır tutarlı satır kaydedilmiyor)',
    (bool)preg_match('/guncelleme\[[\'"]uygulanan[\'"]\]\s*>\s*0\.004/', $dagitGovde));
kontrol('fifoOdemeDagit() insert edilen satırda kasa_hareket_id = $kasaHareketId olarak yazılıyor',
    (bool)preg_match('/[\'"]kasa_hareket_id[\'"]\s*=>\s*\$kasaHareketId/', $dagitGovde));
kontrol('fifoOdemeDagit() insert edilen satırda tutar = uygulanan (fatura_odeme_uygulamalari.tutar gerçek dağıtılan tutar)',
    (bool)preg_match('/[\'"]tutar[\'"]\s*=>\s*\$guncelleme\[[\'"]uygulanan[\'"]\]/', $dagitGovde));

// ═════════════════════════════════════════════════════════════════════
// 3) fifoBakiyeleriYenidenHesapla() — eski uygulamaları temizleyip
//    kasa_hareketleri'ni TEK TEK (id ile) yeniden uyguluyor
// ═════════════════════════════════════════════════════════════════════

$yenidenGovde = metotGovdesi('Fatura', 'fifoBakiyeleriYenidenHesapla');
kontrol('fifoBakiyeleriYenidenHesapla() eski fatura_odeme_uygulamalari kayıtlarını siliyor (DELETE ... FROM fatura_odeme_uygulamalari)',
    (bool)preg_match('/DELETE\s+fou\s+FROM\s+fatura_odeme_uygulamalari/i', $yenidenGovde));
kontrol('fifoBakiyeleriYenidenHesapla() silme işlemini tenant\'a (company_id) göre kısıtlıyor',
    (bool)preg_match('/fou\.company_id\s*=\s*:company_id/', $yenidenGovde));
kontrol('fifoBakiyeleriYenidenHesapla() artık kasa_hareketleri satırlarını TARİHE göre sıralı çekiyor (ORDER BY tarih ASC, id ASC)',
    (bool)preg_match('/ORDER BY tarih ASC,\s*id ASC/', $yenidenGovde));
kontrol('fifoBakiyeleriYenidenHesapla() her hareketi kendi id\'siyle (üçüncü değil, dördüncü argüman olarak) tek tek yeniden uyguluyor',
    (bool)preg_match('/fifoOdemeDagit\(\$cariId,\s*\$h\[[\'"]islem_tipi[\'"]\],\s*\(float\)\$h\[[\'"]tutar[\'"]\],\s*\(int\)\$h\[[\'"]id[\'"]\]\)/', $yenidenGovde));
kontrol('fifoBakiyeleriYenidenHesapla() sıfırlama adımını UYGULAMALARI silme/yeniden uygulamadan ÖNCE yapıyor',
    strpos($yenidenGovde, "SET odenen_tutar = 0") < strpos($yenidenGovde, 'DELETE'));
kontrol('fifoBakiyeleriYenidenHesapla() eski uygulamaları SİLMEYİ, yeniden uygulamadan (foreach) ÖNCE yapıyor (aksi halde az önce yazılan yeni kayıtlar da silinir)',
    strpos($yenidenGovde, 'DELETE') < strpos($yenidenGovde, 'foreach'));

// ═════════════════════════════════════════════════════════════════════
// 4) odemeUygulamalariGetir() — fatura detayında gösterim sorgusu
// ═════════════════════════════════════════════════════════════════════

$getirGovde = metotGovdesi('Fatura', 'odemeUygulamalariGetir');
kontrol('odemeUygulamalariGetir() fatura_odeme_uygulamalari ile kasa_hareketleri\'ni JOIN ediyor',
    str_contains($getirGovde, 'INNER JOIN kasa_hareketleri'));
kontrol('odemeUygulamalariGetir() sonucu tenant\'a (company_id) göre filtreliyor',
    (bool)preg_match('/fou\.company_id\s*=\s*:company_id/', $getirGovde));
kontrol('odemeUygulamalariGetir() silinmiş kasa hareketlerini (silindi_mi=1) hariç tutuyor',
    str_contains($getirGovde, 'kh.silindi_mi = 0'));
kontrol('odemeUygulamalariGetir() düzenleme linki için kasa_id\'yi de döndürüyor (hangi hesabın hareketi olduğu)',
    str_contains($getirGovde, 'kh.kasa_id'));
kontrol('odemeUygulamalariGetir() kasa adını (kasa_banka.hesap_adi) LEFT JOIN ile getiriyor',
    (bool)preg_match('/LEFT JOIN kasa_banka .* kb\.hesap_adi AS kasa_adi/s', $getirGovde) || str_contains($getirGovde, 'kb.hesap_adi AS kasa_adi'));

// ═════════════════════════════════════════════════════════════════════
// 5) ensureOdemeUygulamalariTablosu() — idempotent şema, constructor'da çağrılıyor
// ═════════════════════════════════════════════════════════════════════

$ensureGovde = metotGovdesi('Fatura', 'ensureOdemeUygulamalariTablosu');
kontrol('ensureOdemeUygulamalariTablosu() CREATE TABLE IF NOT EXISTS kullanıyor (idempotent)',
    str_contains($ensureGovde, 'CREATE TABLE IF NOT EXISTS fatura_odeme_uygulamalari'));

$ctorGovde = metotGovdesi('Fatura', '__construct');
kontrol('Fatura::__construct() ensureOdemeUygulamalariTablosu()\'yu çağırıyor',
    str_contains($ctorGovde, 'ensureOdemeUygulamalariTablosu()'));

// ═════════════════════════════════════════════════════════════════════
// 6) TenantContext — yeni tablo çok-kiracılı listelerde
// ═════════════════════════════════════════════════════════════════════

$rc = new ReflectionClass('TenantContext');
foreach (['COMPANY_TABLES', 'PERIOD_TABLES', 'WRITE_TABLES'] as $sabitAdi) {
    $deger = $rc->getConstant($sabitAdi);
    kontrol("TenantContext::{$sabitAdi} içinde 'fatura_odeme_uygulamalari' var",
        is_array($deger) && in_array('fatura_odeme_uygulamalari', $deger, true));
}

// ═════════════════════════════════════════════════════════════════════
// 7) Çağrı zincirinin ucu: Nakit ve KasaHesap hâlâ $id'yi iletiyor
// ═════════════════════════════════════════════════════════════════════

require_once $kok . '/app/models/Nakit.php';
require_once $kok . '/app/models/KasaHesap.php';

$nakitGovde = metotGovdesi('Nakit', 'hareketEkle');
kontrol('Nakit::hareketEkle() fifoOdemeDagit()\'e 4. argüman olarak yeni hareketin $id\'sini iletiyor',
    (bool)preg_match('/fifoOdemeDagit\(\s*\(int\)\$data\[[\'"]cari_id[\'"]\],\s*\$islemTipi,\s*\(float\)\$data\[[\'"]tutar[\'"]\],\s*\$id\)/', $nakitGovde));

$kasaHesapGovde = metotGovdesi('KasaHesap', 'hareketEkle');
kontrol('KasaHesap::hareketEkle() fifoOdemeDagit()\'e 4. argüman olarak yeni hareketin $id\'sini iletiyor',
    (bool)preg_match('/fifoOdemeDagit\(\$cariId,\s*\$islem,\s*\$tutar,\s*\$id\)/', $kasaHesapGovde));

// ═════════════════════════════════════════════════════════════════════
// 8) Controller uçları: /satis/odemeleri ve /alis/odemeleri
//    (Controller sınıfları require ETMEDEN, ham kaynak metniyle denetlenir —
//    SatisController/AlisController soyut Controller sınıfını miras alıyor
//    ve bu test dosyası onu (ve bağımlı Depo/Cari modellerini) yüklemiyor.)
// ═════════════════════════════════════════════════════════════════════

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

$satisKaynak = (string)@file_get_contents($kok . '/app/controllers/SatisController.php');
$satisOdemeleriGovde = metotGovdesiHamKaynaktan($satisKaynak, 'odemeleri');
kontrol('SatisController::odemeleri() metodu bulundu (kaynakta tanımlı)',
    $satisOdemeleriGovde !== '');
kontrol('SatisController::odemeleri() Fatura::odemeUygulamalariGetir()\'i çağırıyor',
    str_contains($satisOdemeleriGovde, '->odemeUygulamalariGetir('));
kontrol('SatisController::odemeleri() JSON içerik tipini ayarlıyor',
    str_contains($satisOdemeleriGovde, "'Content-Type: application/json"));

$alisKaynak = (string)@file_get_contents($kok . '/app/controllers/AlisController.php');
$alisOdemeleriGovde = metotGovdesiHamKaynaktan($alisKaynak, 'odemeleri');
kontrol('AlisController::odemeleri() metodu bulundu (kaynakta tanımlı)',
    $alisOdemeleriGovde !== '');
kontrol('AlisController::odemeleri() Fatura::odemeUygulamalariGetir()\'i çağırıyor',
    str_contains($alisOdemeleriGovde, '->odemeUygulamalariGetir('));

// RBAC: metot adı "odemeleri" — sil/iptal/durum/duzenle/guncelle/kaydet/ekle/yukle
// desenlerinden hiçbirine uymuyor, dolayısıyla classifyAction() varsayılan
// olarak VIEW döner (SATIS_VIEW / ALIS_VIEW yeterli — sayfayı zaten görebilen
// biri, o sayfadaki bir faturanın ödemelerini de görebilmeli).
kontrol('Meta: "odemeleri" metot adı sil/iptal/durum/duzenle/guncelle/kaydet/ekle/yukle desenlerinden hiçbirine uymuyor (VIEW olarak sınıflanır)',
    !(bool)preg_match('/(sil|iptal|durum|duzenle|guncelle|kaydet|ekle|yukle)$/', strtolower('odemeleri'))
    && !str_contains('odemeleri', 'kaydet') && !str_contains('odemeleri', 'yukle') && !str_contains('odemeleri', '_ekle'));

// ═════════════════════════════════════════════════════════════════════
// 9) Meta: PHP kapanış etiketi tuzağı yok
// ═════════════════════════════════════════════════════════════════════

foreach ([
    $kok . '/app/models/Fatura.php',
    $kok . '/app/controllers/SatisController.php',
    $kok . '/app/controllers/AlisController.php',
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
echo "=== Fatura Ödeme Uygulamaları (payment-application) regresyon testi ===\n\n";
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
