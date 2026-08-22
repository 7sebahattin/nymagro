<?php
/**
 * Regresyon testi: e-Belge → çekirdek fatura aktarımı (FAZ 3).
 * --------------------------------------------------------------------------
 * Çalıştırma:  php tests/regression/ebelge_aktarim_invariants.php
 * Çıkış kodu:  0 = tüm kontroller PASSED, 1 = en az bir FAILED.
 *
 * KORUNAN İLKELER
 *  1) ÇEKİRDEĞE TEK DOKUNUŞ: aktarım yalnızca Fatura::ekle() ile yapılır.
 *     faturalar/fatura_kalemleri/stok_hareketleri/cariler tablolarına doğrudan
 *     INSERT/UPDATE yazılmaz.
 *  2) TUTAR KORUNUMU: staging kalemleri çekirdek kalem biçimine çevrildiğinde
 *     satır toplamları DEĞİŞMEZ. Bu, gerçek üretim fonksiyonu olan
 *     Fatura::kalemToplamlari() çağrılarak doğrulanır — kopya bir formülle değil.
 *  3) NUMARA POLİTİKASI: tedarikçinin belge numarası fatura_no olarak
 *     kullanılmaz; numara her zaman alış serisinden üretilir.
 *  4) İDEMPOTENCY: aktarım guarded UPDATE ile başlar, aynı belgeden ikinci
 *     fatura üretilemez.
 *  5) ÇİFT İZİN: aktarım ucu hem EBELGE_UPDATE hem ALIS_CREATE ister.
 *
 * Veritabanı gerektirmez: saf dönüşüm fonksiyonları gerçekten çalıştırılır,
 * geri kalanı Reflection + kaynak denetimiyle sınanır.
 */

$kok = dirname(__DIR__, 2);

// Fatura.php yalnızca sınıf tanımı içerir; MODELS_PATH sabiti
// EBelgeAktarim::tutarKarsilastir() içindeki require_once için gereklidir.
define('MODELS_PATH', $kok . '/app/models');
require_once $kok . '/app/models/EBelgeAktarim.php';
require_once $kok . '/app/models/Fatura.php';

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

/** Staging kalemi üretir (varsayılanlar gerçek şemayla aynı adlarda). */
function stagingKalemi(array $ozel = []): array
{
    return array_merge([
        'sira_no'           => 1,
        'urun_adi'          => 'Test Ürünü',
        'aciklama'          => null,
        'satici_urun_kodu'  => null,
        'alici_urun_kodu'   => null,
        'barkod'            => null,
        'gtip'              => null,
        'miktar'            => 1,
        'birim_kodu'        => 'C62',
        'birim_fiyat'       => 100,
        'satir_tutari'      => 100,
        'iskonto_tutari'    => 0,
        'iskonto_orani'     => 0,
        'kdv_orani'         => 20,
        'kdv_tutari'        => 20,
        'tevkifat_tutari'   => 0,
        'otv_tutari'        => 0,
        'istisna_kodu'      => null,
        'eslesen_urun_id'   => null,
        'urun_eslesme_tipi' => 'urunsuz',
        'hedef_birim'       => 'Adet',
        'birim_carpani'     => 1,
    ], $ozel);
}

// ═════════════════════════════════════════════════════════════════════
// 1) TUTAR KORUNUMU — gerçek üretim formülüyle doğrulanır
// ═════════════════════════════════════════════════════════════════════

// Faz 1 fixture'ındaki 1. kalem: 100 adet × 80 TL, 500 TL iskonto, %20 KDV.
// Net satır 7.500 TL, KDV 1.500 TL → satır toplamı 9.000 TL olmalı.
$d = EBelgeAktarim::kalemleriDonustur([stagingKalemi([
    'miktar' => 100, 'birim_fiyat' => 80, 'satir_tutari' => 7500,
    'iskonto_tutari' => 500, 'iskonto_orani' => 6.25, 'kdv_orani' => 20,
    'kdv_tutari' => 1500, 'eslesen_urun_id' => 42, 'urun_eslesme_tipi' => 'manuel',
])], 1.0);
$k = $d['kalemler'][0];

kontrol('Dönüşüm: birim fiyat iskonto ÖNCESİ tabandan türetiliyor (8000/100 = 80)',
    yaklasik((float)$k['birim_fiyat'], 80.0), 'gelen: ' . $k['birim_fiyat']);
kontrol('Dönüşüm: iskonto ORAN olarak taşınıyor (%6,25)', yaklasik((float)$k['iskonto_orani'], 6.25));
kontrol('Dönüşüm: eşleşen ürün id kaleme geçiyor', $k['urun_id'] === 42);
kontrol('Dönüşüm: hedef birim kaleme geçiyor', $k['birim'] === 'Adet');

$toplam = Fatura::kalemToplamlari($d['kalemler']);
kontrol('Tutar korunumu: ara toplam 8.000 (iskonto öncesi)', yaklasik((float)$toplam['ara_toplam'], 8000.0), 'gelen: ' . $toplam['ara_toplam']);
kontrol('Tutar korunumu: iskonto 500', yaklasik((float)$toplam['iskonto_tutari'], 500.0), 'gelen: ' . $toplam['iskonto_tutari']);
kontrol('Tutar korunumu: KDV 1.500 (net 7.500 üzerinden %20)', yaklasik((float)$toplam['kdv_tutari'], 1500.0), 'gelen: ' . $toplam['kdv_tutari']);
kontrol('Tutar korunumu: genel toplam 9.000 — belgedeki değerle birebir',
    yaklasik((float)$toplam['genel_toplam'], 9000.0), 'gelen: ' . $toplam['genel_toplam']);

// Faz 1 fixture'ının tamamı (2 kalem) → belgedeki 11.400 TL ile eşleşmeli.
$d2 = EBelgeAktarim::kalemleriDonustur([
    stagingKalemi(['sira_no' => 1, 'miktar' => 100, 'birim_fiyat' => 80, 'satir_tutari' => 7500,
        'iskonto_tutari' => 500, 'iskonto_orani' => 6.25, 'kdv_orani' => 20]),
    stagingKalemi(['sira_no' => 2, 'miktar' => 50, 'birim_fiyat' => 40, 'satir_tutari' => 2000,
        'kdv_orani' => 20, 'hedef_birim' => 'Kg']),
], 1.0);
$kars = EBelgeAktarim::tutarKarsilastir($d2['kalemler'], 11400.00);
kontrol('Tam belge: çekirdeğin hesaplayacağı toplam belgedekiyle aynı (fark 0)',
    yaklasik((float)$kars['fark'], 0.0) && $kars['tolerans_disi'] === false,
    'hesaplanan: ' . $kars['hesaplanan']['genel_toplam'] . ' · fark: ' . $kars['fark']);

// Birim çarpanı: 10 koli × 12 = 120 adet olurken SATIR TOPLAMI değişmemeli.
$d3 = EBelgeAktarim::kalemleriDonustur([stagingKalemi([
    'miktar' => 10, 'birim_fiyat' => 120, 'satir_tutari' => 1200, 'kdv_orani' => 0,
    'birim_carpani' => 12, 'hedef_birim' => 'Adet',
])], 1.0);
$k3 = $d3['kalemler'][0];
kontrol('Birim çarpanı: miktar sistem birimine çevriliyor (10 × 12 = 120)', yaklasik((float)$k3['miktar'], 120.0));
kontrol('Birim çarpanı: birim fiyat da bölünüyor (1200/120 = 10)', yaklasik((float)$k3['birim_fiyat'], 10.0));
kontrol('Birim çarpanı: SATIR TOPLAMI DEĞİŞMİYOR (1.200 TL)',
    yaklasik((float)Fatura::kalemToplamlari($d3['kalemler'])['genel_toplam'], 1200.0));

// Döviz: tutarlar TL'ye çevrilmeli (çekirdekte ara_toplam/KDV her zaman TL).
$d4 = EBelgeAktarim::kalemleriDonustur([stagingKalemi([
    'miktar' => 2, 'birim_fiyat' => 50, 'satir_tutari' => 100, 'kdv_orani' => 0,
])], 30.0);
kontrol('Döviz: birim fiyat kurla TL\'ye çevriliyor (50 × 30 = 1.500)',
    yaklasik((float)$d4['kalemler'][0]['birim_fiyat'], 1500.0), 'gelen: ' . $d4['kalemler'][0]['birim_fiyat']);
kontrol('Döviz: satır toplamı TL karşılığı (100 × 30 = 3.000)',
    yaklasik((float)Fatura::kalemToplamlari($d4['kalemler'])['genel_toplam'], 3000.0));

// ═════════════════════════════════════════════════════════════════════
// 2) ÇEKİRDEK DOĞRULAMASINDAN GEÇEBİLİRLİK
// ═════════════════════════════════════════════════════════════════════
// Fatura::assertKalemGecerli() miktar>0, fiyat>=0, oranlar 0..100 şartı koyar.
// Dönüşümün ürettiği her kalem bu şartları SAĞLAMALIDIR; aksi hâlde aktarım
// canlıda exception ile düşer.

$zorlu = EBelgeAktarim::kalemleriDonustur([
    stagingKalemi(['sira_no' => 1, 'miktar' => 0, 'satir_tutari' => 500, 'kdv_orani' => 20]),
    stagingKalemi(['sira_no' => 2, 'iskonto_tutari' => 300, 'satir_tutari' => 100, 'iskonto_orani' => 0]),
    stagingKalemi(['sira_no' => 3, 'kdv_orani' => 250, 'urun_adi' => '']),
    stagingKalemi(['sira_no' => 4, 'iskonto_orani' => 180, 'satir_tutari' => 100]),
], 1.0);

$hepsiGecerli = true;
$detay = '';
foreach ($zorlu['kalemler'] as $kk) {
    if ((float)$kk['miktar'] <= 0) { $hepsiGecerli = false; $detay .= 'miktar<=0 '; }
    if ((float)$kk['birim_fiyat'] < 0) { $hepsiGecerli = false; $detay .= 'fiyat<0 '; }
    if ((float)$kk['kdv_orani'] < 0 || (float)$kk['kdv_orani'] > 100) { $hepsiGecerli = false; $detay .= 'kdv aralık dışı '; }
    if ((float)$kk['iskonto_orani'] < 0 || (float)$kk['iskonto_orani'] > 100) { $hepsiGecerli = false; $detay .= 'iskonto aralık dışı '; }
    if (trim((string)$kk['urun_adi']) === '') { $hepsiGecerli = false; $detay .= 'ad boş '; }
}
kontrol('Zorlu girdiler Fatura::assertKalemGecerli() şartlarını ihlal etmiyor', $hepsiGecerli, $detay);
kontrol('Sıfır miktar 1 birime çekiliyor ve uyarı üretiliyor',
    yaklasik((float)$zorlu['kalemler'][0]['miktar'], 1.0) && count($zorlu['uyarilar']) >= 1);
kontrol('Sıfır miktarlı kalemde satır tutarı korunuyor (500 TL)',
    yaklasik((float)$zorlu['kalemler'][0]['birim_fiyat'], 500.0));
kontrol('Oran verilmemiş iskonto tutardan hesaplanıyor (300/400 = %75)',
    yaklasik((float)$zorlu['kalemler'][1]['iskonto_orani'], 75.0), 'gelen: ' . $zorlu['kalemler'][1]['iskonto_orani']);
kontrol('%100 üzeri KDV/iskonto oranları sınırlanıyor',
    yaklasik((float)$zorlu['kalemler'][2]['kdv_orani'], 100.0)
    && yaklasik((float)$zorlu['kalemler'][3]['iskonto_orani'], 100.0));
kontrol('Boş ürün adı yerine "Kalem #n" konuyor', $zorlu['kalemler'][2]['urun_adi'] === 'Kalem #3');

// Üründüz kalem: urun_id NULL kalmalı (Fatura::ekle stok hareketi yazmaz).
$urunsuz = EBelgeAktarim::kalemleriDonustur([stagingKalemi(['eslesen_urun_id' => null])], 1.0);
kontrol('Üründüz gider kalemi: urun_id NULL kalıyor (stok hareketi oluşmaz)',
    $urunsuz['kalemler'][0]['urun_id'] === null);

// Tolerans dışı fark tespiti
$kars2 = EBelgeAktarim::tutarKarsilastir($d2['kalemler'], 11400.00 + 5.00);
kontrol('Tutar farkı tolerans dışındaysa işaretleniyor', $kars2['tolerans_disi'] === true);

// İzlenebilirlik notu
$izlenebilir = EBelgeAktarim::kalemleriDonustur([stagingKalemi([
    'satici_urun_kodu' => 'ORN-4417', 'barkod' => '8690000111222', 'tevkifat_tutari' => 400,
])], 1.0);
kontrol('Kalem açıklamasına satıcı kodu/barkod/tevkifat izi düşüyor',
    str_contains((string)$izlenebilir['kalemler'][0]['aciklama'], 'ORN-4417')
    && str_contains((string)$izlenebilir['kalemler'][0]['aciklama'], '8690000111222')
    && str_contains((string)$izlenebilir['kalemler'][0]['aciklama'], 'Tevkifat'));

// ═════════════════════════════════════════════════════════════════════
// 3) ÇEKİRDEĞE TEK DOKUNUŞ
// ═════════════════════════════════════════════════════════════════════

$cekirdek = 'faturalar|fatura_kalemleri|stok_hareketleri|urun_stok_depo|cariler|urunler_hizmetler|kasa_hareketleri|kasa_banka';
$aktarimHam = (string)@file_get_contents($kok . '/app/models/EBelgeAktarim.php');
$aktarimKod = kodSadece($aktarimHam);

kontrol('EBelgeAktarim: çekirdek tablolara insert/update/softDelete YOK',
    preg_match('/->\s*(insert|update|softDelete)\s*\(\s*[\'"](' . $cekirdek . ')[\'"]/i', $aktarimKod) !== 1);
kontrol('EBelgeAktarim: çekirdek tablolara ham INSERT/UPDATE/DELETE YOK',
    preg_match('/(INSERT\s+INTO|UPDATE|DELETE\s+FROM)\s+`?(' . $cekirdek . ')`?\b/i', $aktarimKod) !== 1);
kontrol('EBelgeAktarim: faturalar üzerinde YIKICI DDL (DROP/MODIFY/CHANGE) YOK',
    preg_match('/ALTER\s+TABLE\s+faturalar\s+(DROP|MODIFY|CHANGE)/i', $aktarimKod) !== 1,
    'faturalar tablosuna yalnızca NULL kabul eden yeni kolon eklenebilir.');
kontrol('EBelgeAktarim: faturalar\'a yalnızca NULLable kolon ekleniyor',
    preg_match('/ALTER TABLE faturalar ADD COLUMN kaynak_ebelge_id INT UNSIGNED NULL/i', $aktarimKod) === 1);

$aktarGovde = metotGovdesi('EBelgeAktarim', 'aktar');
kontrol('aktar(): fatura oluşturma YALNIZCA Fatura::ekle() ile yapılıyor',
    substr_count($aktarGovde, '$faturaModel->ekle(') === 1,
    'Tek dokunuş noktası korunmalı.');
kontrol('aktar(): Fatura modeli transaction AÇILMADAN ÖNCE örnekleniyor (DDL örtük commit)',
    strpos($aktarGovde, 'new Fatura()') !== false
    && strpos($aktarGovde, '$this->db->begin()') !== false
    && strpos($aktarGovde, 'new Fatura()') < strpos($aktarGovde, '$this->db->begin()'));
kontrol('aktar(): Fatura::ekle() sonrası transaction hâlâ ayakta mı kontrol ediliyor',
    str_contains($aktarGovde, '!$this->db->inTransaction()'),
    'Urun::stokHareketiEkle() hata hâlinde sessizce rollBack edip false döner ve Fatura::ekle() '
    . 'bu dönüşü kontrol etmez; bu guard olmadan kullanıcıya yanlışlıkla "aktarıldı" denirdi.');
kontrol('aktar(): tüm iş tek transaction içinde (begin + commit + rollBack)',
    str_contains($aktarGovde, '$this->db->begin()')
    && str_contains($aktarGovde, '$this->db->commit()')
    && str_contains($aktarGovde, '$this->db->rollBack()'));

// ═════════════════════════════════════════════════════════════════════
// 4) İDEMPOTENCY VE NUMARA POLİTİKASI
// ═════════════════════════════════════════════════════════════════════

kontrol('aktar(): guarded UPDATE ile başlıyor (durum + aktarilan_fatura_id şartı)',
    str_contains($aktarGovde, "durum = :beklenen") && str_contains($aktarGovde, 'aktarilan_fatura_id IS NULL'));
kontrol('aktar(): guarded UPDATE 0 satır etkilerse işlem durduruluyor',
    str_contains($aktarGovde, 'rowCount() === 0'));
kontrol('aktar(): başarıda belge "aktarildi" ve fatura id staging\'e yazılıyor',
    str_contains($aktarGovde, 'DURUM_AKTARILDI') && str_contains($aktarGovde, "'aktarilan_fatura_id' => \$faturaId"));

kontrol('Numara politikası: fatura no alış serisinden üretiliyor',
    str_contains($aktarGovde, "faturaNoUret('alis')"));
kontrol('Numara politikası: tedarikçinin belge numarası fatura_no OLARAK KULLANILMIYOR',
    preg_match("/'fatura_no'\s*=>\s*\\\$belge\['belge_no'\]/", $aktarGovde) !== 1,
    'uq_faturalar_no_aktif çakışması ve seri karışması riski.');

$aciklamaGovde = metotGovdesi('EBelgeAktarim', 'aciklamaUret');
kontrol('İzlenebilirlik: orijinal belge no ve ETTN açıklamaya yazılıyor',
    str_contains($aciklamaGovde, 'Orijinal e-Fatura No') && str_contains($aciklamaGovde, 'ETTN'));

kontrol('kaynak_ebelge_id yalnızca kolon VARSA gönderiliyor (kolon yoksa aktarım yine çalışır)',
    str_contains($aktarGovde, 'kaynakKolonuVarMi()'));
$faturaKaynak = (string)@file_get_contents($kok . '/app/models/Fatura.php');
kontrol('Fatura::$fillable içinde kaynak_ebelge_id tanımlı',
    str_contains($faturaKaynak, "'kaynak_ebelge_id'"),
    'Fillable\'da olmazsa array_intersect_key alanı süzer ve bağlantı hiç yazılmaz.');

// ═════════════════════════════════════════════════════════════════════
// 5) ENGELLER, ONAYLAR VE HATA DAVRANIŞI
// ═════════════════════════════════════════════════════════════════════

$engelGovde = metotGovdesi('EBelgeAktarim', 'engelleriTopla');
foreach ([
    'aktarima hazır durum kontrolü' => 'DURUM_AKTARIMA_HAZIR',
    'zaten aktarılmış kontrolü'     => 'aktarilan_fatura_id',
    'cari bağlı mı kontrolü'        => 'eslesen_cari_id',
    'e-İrsaliye engeli'             => 'AKTARILABILIR_TIPLER',
    'dönem kilidi kontrolü'         => 'isActivePeriodWritable',
    'eşleştirme engelleri'          => 'eslestirmeOzeti',
] as $ad => $iz) {
    kontrol("engelleriTopla(): {$ad} var", str_contains($engelGovde, $iz));
}

$onayGovde = metotGovdesi('EBelgeAktarim', 'onayGerektirenler');
kontrol('onayGerektirenler(): tutar farkı için onay isteniyor', str_contains($onayGovde, 'tutar_onay'));
kontrol('onayGerektirenler(): dönem dışı tarih için onay isteniyor', str_contains($onayGovde, 'tarih_onay'));
kontrol('onayGerektirenler(): tevkifat için bilgilendirme onayı isteniyor', str_contains($onayGovde, 'tevkifat_onay'));
kontrol('aktar(): onay kutuları işaretlenmeden aktarım yapılmıyor',
    str_contains($aktarGovde, 'Onay gerekli'));
kontrol('aktar(): engel varsa hiç başlamıyor', str_contains($aktarGovde, "empty(\$onizleme['engeller'])"));

$onizlemeGovde = metotGovdesi('EBelgeAktarim', 'onizleme');
kontrol('onizleme(): veritabanına YAZMIYOR (yalnızca okuma)',
    !preg_match('/->\s*(insert|update|softDelete)\s*\(/', $onizlemeGovde)
    && !preg_match('/(INSERT\s+INTO|UPDATE\s+|DELETE\s+FROM)/i', $onizlemeGovde));

kontrol('aktar(): hata hâlinde rollback sonrası hata mesajı ayrı işlemde saklanıyor',
    str_contains($aktarGovde, "'aktarim_hatasi'"));
kontrol('aktar(): hata hâlinde belge durumu "aktarildi" YAPILMIYOR (tekrar denenebilir)',
    preg_match('/catch.*DURUM_AKTARILDI/s', substr($aktarGovde, (int)strpos($aktarGovde, 'catch'))) !== 1);

$hedefGovde = metotGovdesi('EBelgeAktarim', 'hedefBelgeTipi');
kontrol('IADE tipli belge alış İADESİ olarak aktarılıyor',
    str_contains($hedefGovde, "'iade_alis'") && str_contains($hedefGovde, "'IADE'"));

// ═════════════════════════════════════════════════════════════════════
// 6) YETKİLENDİRME — ÇİFT İZİN
// ═════════════════════════════════════════════════════════════════════

$ctrl = kodSadece((string)@file_get_contents($kok . '/app/controllers/EBelgeController.php'));
kontrol('Controller: aktar() ucu var', str_contains($ctrl, 'function aktar('));
kontrol('Controller: aktarımda ÇİFT İZİN aranıyor (EBELGE_UPDATE + ALIS_CREATE)',
    str_contains($ctrl, "authorizeOrDeny('EBelgeController', 'aktar')")
    && str_contains($ctrl, "authorizeOrDeny('AlisController', 'kaydet')"),
    'Aktarım gerçek alış faturası oluşturur; yalnızca e-Belge yetkisi yeterli olmamalıdır.');
kontrol('Controller: aktar() çift izin kontrolünü İLK İŞ olarak yapıyor',
    preg_match('/function aktar\([^)]*\)[^{]*\{\s*\$id\s*=\s*\(int\)\$id;\s*\$this->aktarimYetkisiZorunlu\(\);/s', $ctrl) === 1);
kontrol('Controller: aktar() yazma işlemini yalnızca POST ile yapıyor',
    preg_match("/function aktar\(.*?REQUEST_METHOD.*?!==\s*'POST'/s", $ctrl) === 1);
kontrol('Controller: kolon hazırlığı (DDL) transaction dışında, aktarım öncesi yapılıyor',
    str_contains($ctrl, 'ensureKaynakKolonu()'));

$rbac = (string)@file_get_contents($kok . '/app/core/Rbac.php');
kontrol('RBAC: EBelgeController::aktar → UPDATE override\'ı tanımlı',
    str_contains($rbac, "'EBelgeController::aktar' => 'UPDATE'"),
    'Override olmadan classifyAction() bu ucu VIEW sanardı.');

$detayGorunum = (string)@file_get_contents($kok . '/app/views/ebelge/detay.php');
kontrol('Arayüz: aktarım butonu çift izin kontrolüyle gösteriliyor',
    str_contains($detayGorunum, "currentUserCan('EBELGE_UPDATE')")
    && str_contains($detayGorunum, "currentUserCan('ALIS_CREATE')"));

// ═════════════════════════════════════════════════════════════════════
// Rapor
// ═════════════════════════════════════════════════════════════════════

$basarili = 0;
$basarisiz = [];
echo "=== e-Belge → çekirdek fatura aktarımı (Faz 3) regresyon testi ===\n\n";
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
