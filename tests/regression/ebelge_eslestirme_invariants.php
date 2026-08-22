<?php
/**
 * Regresyon testi: e-Belge cari/ürün eşleştirme katmanı (FAZ 2).
 * --------------------------------------------------------------------------
 * Çalıştırma:  php tests/regression/ebelge_eslestirme_invariants.php
 * Çıkış kodu:  0 = tüm kontroller PASSED, 1 = en az bir FAILED.
 *
 * NEDEN VAR — KORUNAN İKİ İLKE
 *
 *  1) OTOMATİK EŞLEŞTİRME ASLA İSİM BENZERLİĞİNE DAYANMAZ.
 *     Yanlış cari eşleşmesi yanlış cariye borç yazar ve
 *     Fatura::recomputeCariBalance() bunu kalıcı hâle getirir; yanlış ürün
 *     eşleşmesi stok miktarını bozar. Bu testler, ileride "kolaylık olsun diye"
 *     unvan/ürün adı benzerliğinin otomatik eşleştirmeye sızmasını engeller.
 *
 *  2) ÇEKİRDEK TABLOLARA DOĞRUDAN YAZILMAZ.
 *     Yeni cari/ürün kayıtları yalnızca Cari::ekle() ve Urun::ekle() üzerinden
 *     açılır; böylece $fillable süzgeci, UNIQUE kısıtları ve Audit korunur.
 *
 * Veritabanı gerektirmez: saf yardımcılar doğrudan çağrılır, geri kalanı
 * Reflection + kaynak denetimiyle sınanır.
 */

$kok = dirname(__DIR__, 2);
require_once $kok . '/app/models/EBelgeEslestirme.php';

/** @var array<int,array{ad:string,ok:bool,detay:string}> */
$sonuclar = [];

function kontrol(string $ad, bool $gecti, string $detay = ''): void
{
    global $sonuclar;
    $sonuclar[] = ['ad' => $ad, 'ok' => $gecti, 'detay' => $detay];
}

/** Kaynak koddan yorumları ayıklar (yorumdaki sözcükler ihlal sayılmasın). */
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

/** Bir metodun kaynak gövdesini döner (yorumlar dahil değil). */
function metotGovdesi(string $sinif, string $metot): string
{
    $rm = new ReflectionMethod($sinif, $metot);
    $dosya = $rm->getFileName();
    if ($dosya === false) {
        return '';
    }
    $satirlar = file($dosya);
    $govde = implode('', array_slice($satirlar, $rm->getStartLine() - 1, $rm->getEndLine() - $rm->getStartLine() + 1));
    return kodSadece('<?php ' . $govde);
}

// ═════════════════════════════════════════════════════════════════════
// 1) SAF YARDIMCILAR — birim çözümleme
// ═════════════════════════════════════════════════════════════════════

kontrol('birimCozumle: C62 → Adet', EBelgeEslestirme::birimCozumle('C62') === 'Adet');
kontrol('birimCozumle: KGM → Kg', EBelgeEslestirme::birimCozumle('KGM') === 'Kg');
kontrol('birimCozumle: LTR → Litre', EBelgeEslestirme::birimCozumle('LTR') === 'Litre');
kontrol('birimCozumle: küçük harf girdi de çözülüyor', EBelgeEslestirme::birimCozumle('kgm') === 'Kg');
kontrol('birimCozumle: bilinmeyen kod null döner', EBelgeEslestirme::birimCozumle('ZZZ') === null);
kontrol('birimCozumle: null girdi null döner', EBelgeEslestirme::birimCozumle(null) === null);
kontrol(
    'birimCozumle: ÇEVRİM gerektiren kodlar (GRM/MLT) BİLİNÇLİ OLARAK eşlenmiyor',
    EBelgeEslestirme::birimCozumle('GRM') === null && EBelgeEslestirme::birimCozumle('MLT') === null,
    '1000 gramlık kalem sessizce 1000 Kg olarak stoğa girmemeli; karar kullanıcınındır.'
);

kontrol('birimUyumsuzMu: C62 ↔ Adet uyumlu', EBelgeEslestirme::birimUyumsuzMu('C62', 'Adet') === false);
kontrol('birimUyumsuzMu: KGM ↔ Adet UYUMSUZ', EBelgeEslestirme::birimUyumsuzMu('KGM', 'Adet') === true);
kontrol('birimUyumsuzMu: çözülemeyen kod uyumsuz sayılır', EBelgeEslestirme::birimUyumsuzMu('ZZZ', 'Adet') === true);
kontrol('birimUyumsuzMu: birim kodu yoksa uyumsuz sayılır', EBelgeEslestirme::birimUyumsuzMu(null, 'Adet') === true);
kontrol('birimUyumsuzMu: sistem birimi boşsa uyumsuz sayılır', EBelgeEslestirme::birimUyumsuzMu('C62', '') === true);
kontrol('birimUyumsuzMu: büyük/küçük harf farkı uyumsuzluk sayılmaz', EBelgeEslestirme::birimUyumsuzMu('KGM', 'kg') === false);

// ═════════════════════════════════════════════════════════════════════
// 2) SAF YARDIMCILAR — kimlik ve benzerlik
// ═════════════════════════════════════════════════════════════════════

kontrol('kimlikGecerliMi: 10 haneli VKN geçerli', EBelgeEslestirme::kimlikGecerliMi('1234567890') === true);
kontrol('kimlikGecerliMi: 11 haneli TCKN geçerli', EBelgeEslestirme::kimlikGecerliMi('12345678901') === true);
kontrol('kimlikGecerliMi: 9 hane geçersiz', EBelgeEslestirme::kimlikGecerliMi('123456789') === false);
kontrol('kimlikGecerliMi: harf içeren geçersiz', EBelgeEslestirme::kimlikGecerliMi('123456789A') === false);
kontrol('kimlikGecerliMi: boş geçersiz', EBelgeEslestirme::kimlikGecerliMi('') === false);
kontrol('kimlikGecerliMi: null geçersiz', EBelgeEslestirme::kimlikGecerliMi(null) === false);

kontrol(
    'adNormalize: Türkçe karakter ve şirket ekleri sadeleşiyor',
    EBelgeEslestirme::adNormalize('ÖRNEK TARIM ÜRÜNLERİ SANAYİ VE TİCARET A.Ş.') === 'ornek tarim urunleri',
    'gelen: ' . EBelgeEslestirme::adNormalize('ÖRNEK TARIM ÜRÜNLERİ SANAYİ VE TİCARET A.Ş.')
);
kontrol(
    'adNormalize: farklı yazımlar aynı anahtara iniyor',
    EBelgeEslestirme::adNormalize('Örnek Tarım Ürünleri Ltd. Şti.')
        === EBelgeEslestirme::adNormalize('ÖRNEK TARIM ÜRÜNLERİ LİMİTED ŞİRKETİ'),
    'a: ' . EBelgeEslestirme::adNormalize('Örnek Tarım Ürünleri Ltd. Şti.')
    . ' | b: ' . EBelgeEslestirme::adNormalize('ÖRNEK TARIM ÜRÜNLERİ LİMİTED ŞİRKETİ')
);

kontrol('benzerlikPuani: aynı unvan 100', EBelgeEslestirme::benzerlikPuani('Örnek Tarım A.Ş.', 'ÖRNEK TARIM AŞ') === 100.0);
kontrol(
    'benzerlikPuani: yakın unvanlar yüksek puan alır',
    EBelgeEslestirme::benzerlikPuani('Örnek Tarım Ürünleri Ltd.', 'Örnek Tarım Ürünleri Sanayi A.Ş.') >= 70.0
);
kontrol(
    'benzerlikPuani: alakasız unvanlar düşük puan alır',
    EBelgeEslestirme::benzerlikPuani('Örnek Tarım A.Ş.', 'Zirve Bilişim Teknolojileri') < 45.0,
    'gelen: ' . EBelgeEslestirme::benzerlikPuani('Örnek Tarım A.Ş.', 'Zirve Bilişim Teknolojileri')
);
kontrol('benzerlikPuani: boş girdi 0 döner', EBelgeEslestirme::benzerlikPuani('', 'Örnek') === 0.0);

// ═════════════════════════════════════════════════════════════════════
// 3) OTOMATİK EŞLEŞTİRME İSİM BENZERLİĞİ KULLANMAMALI  ★ EN KRİTİK ★
// ═════════════════════════════════════════════════════════════════════

$otomatikMetotlar = ['cariOtomatikBul', 'urunOtomatikBul', 'otomatikEslestir'];
foreach ($otomatikMetotlar as $metot) {
    $govde = metotGovdesi('EBelgeEslestirme', $metot);
    kontrol("{$metot}(): gövdesi okunabildi", $govde !== '');

    kontrol(
        "{$metot}(): benzerlikPuani() ÇAĞIRMIYOR",
        !str_contains($govde, 'benzerlikPuani'),
        'İsim benzerliği otomatik eşleştirmeye sızmış — yanlış cari/ürün bağlanabilir.'
    );
    kontrol(
        "{$metot}(): unvan/ad üzerinde LIKE araması YOK",
        preg_match('/\b(unvan|ad)\s+LIKE/i', $govde) !== 1,
        'Otomatik eşleştirme yalnızca kimlik alanlarıyla (VKN/TCKN/barkod/stok kodu) yapılmalıdır.'
    );
}

$cariBul = metotGovdesi('EBelgeEslestirme', 'cariOtomatikBul');
kontrol(
    'cariOtomatikBul(): kimlik biçimi doğrulanmadan sorgu yapmıyor',
    str_contains($cariBul, 'kimlikGecerliMi')
);
kontrol(
    'cariOtomatikBul(): yalnızca TEK sonuç eşleşme sayılıyor (LIMIT 2 + count===1)',
    str_contains($cariBul, 'LIMIT 2') && preg_match('/count\(\s*\$adaylar\s*\)\s*===\s*1/', $cariBul) === 1,
    'Aynı VKN\'ye sahip iki cari varsa karar kullanıcıya bırakılmalıdır.'
);

$urunBul = metotGovdesi('EBelgeEslestirme', 'urunOtomatikBul');
kontrol(
    'urunOtomatikBul(): yalnızca barkod ve stok_kodu ile eşleşiyor',
    str_contains($urunBul, 'barkod = :kod') && str_contains($urunBul, 'stok_kodu = :kod')
);
kontrol(
    'urunOtomatikBul(): yalnızca TEK sonuç eşleşme sayılıyor',
    substr_count($urunBul, 'LIMIT 2') >= 2 && substr_count($urunBul, 'count($adaylar) === 1') >= 2
);
kontrol(
    'urunOtomatikBul(): ürün ADI sorguya hiç girmiyor',
    !preg_match('/\bad\s*=\s*:/i', $urunBul) && !str_contains($urunBul, 'urun_adi')
);

// Öğrenilmiş ürün eşleşmesi tedarikçi bazlı olmalı.
$ogrenUrun = metotGovdesi('EBelgeEslestirme', 'urunEslesmesiOgren');
kontrol(
    'urunEslesmesiOgren(): satıcı kodu TEDARİKÇİ bazlı öğreniliyor',
    str_contains($ogrenUrun, "'tip' => 'satici_kodu'") && str_contains($ogrenUrun, "'cari' => \$tedarikciId"),
    'Aynı satıcı kodu farklı tedarikçilerde farklı ürünü gösterebilir.'
);
kontrol(
    'urunEslesmesiOgren(): barkod evrensel (tedarikci_cari_id = 0) saklanıyor',
    str_contains($ogrenUrun, "'tip' => 'barkod'") && str_contains($ogrenUrun, "'cari' => 0")
);
kontrol(
    'urunEslesmesiOgren(): hiç kod yoksa (yalnızca ad varsa) öğrenme YAPMIYOR',
    str_contains($ogrenUrun, 'if (empty($anahtarlar))'),
    'Yalnızca ada dayanan bir eşleşme öğrenilirse sonraki belgelerde otomatik uygulanırdı.'
);

// ═════════════════════════════════════════════════════════════════════
// 4) ÇEKİRDEK TABLOLARA DOĞRUDAN YAZMA YASAĞI
// ═════════════════════════════════════════════════════════════════════

$cekirdek = 'faturalar|fatura_kalemleri|stok_hareketleri|urun_stok_depo|cariler|urunler_hizmetler|kasa_hareketleri|kasa_banka';

foreach ([
    'app/models/EBelgeEslestirme.php',
    'app/models/EBelge.php',
    'app/controllers/EBelgeController.php',
] as $goreli) {
    $ham = @file_get_contents($kok . '/' . $goreli);
    if ($ham === false) {
        kontrol("{$goreli} okunabildi", false);
        continue;
    }
    $kaynak = kodSadece($ham);

    kontrol(
        "{$goreli}: çekirdek tablolara insert/update/softDelete YOK",
        preg_match('/->\s*(insert|update|softDelete)\s*\(\s*[\'"](' . $cekirdek . ')[\'"]/i', $kaynak) !== 1,
        'Yeni cari/ürün yalnızca Cari::ekle() ve Urun::ekle() ile açılmalıdır.'
    );
    kontrol(
        "{$goreli}: çekirdek tablolara ham yazma SQL'i YOK",
        preg_match('/(INSERT\s+INTO|UPDATE|DELETE\s+FROM)\s+`?(' . $cekirdek . ')`?\b/i', $kaynak) !== 1
    );
}

$eslestirmeKaynak = kodSadece((string)@file_get_contents($kok . '/app/models/EBelgeEslestirme.php'));
kontrol(
    'Yeni cari mevcut Cari::ekle() ile açılıyor',
    str_contains($eslestirmeKaynak, 'new Cari()') && str_contains($eslestirmeKaynak, '$cariModel->ekle(')
);
kontrol(
    'Yeni ürün mevcut Urun::ekle() ile açılıyor',
    str_contains($eslestirmeKaynak, 'new Urun()') && str_contains($eslestirmeKaynak, '$urunModel->ekle(')
);
kontrol(
    'Yeni ürün açılırken stok miktarı SIFIR (mal girişi aktarımda yazılır)',
    preg_match("/'stok_miktari'\s*=>\s*0/", $eslestirmeKaynak) === 1
);
kontrol(
    'Yeni ürün açılırken stok kodu/barkod çakışması kontrol ediliyor',
    substr_count($eslestirmeKaynak, 'kodMevcutMu(') >= 2
);

// DDL örtük commit tuzağı: Cari/Urun kurucuları ALTER TABLE çalıştırabilir,
// bu yüzden transaction AÇILMADAN ÖNCE örneklenmelidir.
foreach (['yeniCariOlustur' => 'new Cari()', 'yeniUrunOlustur' => 'new Urun()'] as $metot => $ornekleme) {
    $govde = metotGovdesi('EBelgeEslestirme', $metot);
    $modelPos = strpos($govde, $ornekleme);
    $beginPos = strpos($govde, '$this->db->begin()');
    kontrol(
        "{$metot}(): model transaction AÇILMADAN ÖNCE örnekleniyor (DDL örtük commit koruması)",
        $modelPos !== false && $beginPos !== false && $modelPos < $beginPos,
        'Kurucudaki ALTER TABLE açık transaction\'ı sessizce commit eder ve rollback etkisiz kalır.'
    );
}

// ═════════════════════════════════════════════════════════════════════
// 5) DURUM MAKİNESİ VE ENGELLEYİCİLER
// ═════════════════════════════════════════════════════════════════════

$durum = metotGovdesi('EBelgeEslestirme', 'durumTazele');
kontrol(
    'durumTazele(): aktarılmış/reddedilmiş/izleme belgelerin durumuna DOKUNMUYOR',
    str_contains($durum, 'DURUM_AKTARILDI') && str_contains($durum, 'DURUM_REDDEDILDI')
    && str_contains($durum, 'DURUM_IZLEME') && str_contains($durum, 'DURUM_AKTARILIYOR')
);
kontrol(
    'durumTazele(): engelleyici varken "aktarima_hazir" yapmıyor',
    str_contains($durum, 'engelleyiciler') && str_contains($durum, 'DURUM_AKTARIMA_HAZIR')
);

$ozet = metotGovdesi('EBelgeEslestirme', 'eslestirmeOzeti');
kontrol('eslestirmeOzeti(): cari eksikse engelleyici üretiyor', str_contains($ozet, 'bağlanmamış'));
kontrol('eslestirmeOzeti(): birim kararı verilmemiş kalem engelleyici sayılıyor', str_contains($ozet, "empty(\$kalem['hedef_birim'])"));
kontrol('eslestirmeOzeti(): dövizli belgede eksik kur engelleyici sayılıyor', str_contains($ozet, 'kur'));
kontrol(
    'eslestirmeOzeti(): üründüz kalemler bekleyen sayılmıyor',
    str_contains($ozet, 'ESLESME_URUNSUZ')
);

$kalemEslestir = metotGovdesi('EBelgeEslestirme', 'kalemEslestir');
kontrol(
    'kalemEslestir(): kalem eylemi "kalem_islem" anahtarından okunuyor',
    str_contains($kalemEslestir, "\$form['kalem_islem']"),
    'Controller kendi yönlendirmesi için "islem" anahtarını kullanıyor; aynı ada bakmak çakışma yaratır.'
);
kontrol(
    'kalemEslestir(): üründüz kalemde eslesen_urun_id NULL bırakılıyor (stok hareketi oluşmaz)',
    preg_match("/'eslesen_urun_id'\s*=>\s*null/", $kalemEslestir) === 1
);
kontrol(
    'kalemEslestir(): birim çarpanı pozitif olmak zorunda',
    str_contains($kalemEslestir, 'Birim çarpanı sıfırdan büyük')
);

// ═════════════════════════════════════════════════════════════════════
// 6) CONTROLLER / RBAC
// ═════════════════════════════════════════════════════════════════════

$ctrlHam = (string)@file_get_contents($kok . '/app/controllers/EBelgeController.php');
$ctrl = kodSadece($ctrlHam);
kontrol('EBelgeController::eslestir() var', str_contains($ctrl, 'function eslestir('));
kontrol(
    'eslestir(): yazma yalnızca POST ile yapılıyor (HTTP metod bekçisi)',
    preg_match("/function eslestir\(.*?REQUEST_METHOD.*?===\s*'POST'/s", $ctrl) === 1
);
kontrol(
    'eslestir(): e-İrsaliye eşleştirilemiyor (Faz 1 kararı: yalnızca izleme)',
    str_contains($ctrl, 'AKTARILABILIR_TIPLER')
);
kontrol(
    'Controller cari/ürün modellerini doğrudan çağırmıyor (karar katmanı modelde)',
    !str_contains($ctrl, 'new Cari(') && !str_contains($ctrl, 'new Urun(')
);

$rbac = (string)@file_get_contents($kok . '/app/core/Rbac.php');
kontrol(
    'RBAC: EBelgeController::eslestir → UPDATE override\'ı tanımlı',
    str_contains($rbac, "'EBelgeController::eslestir' => 'UPDATE'"),
    'Override olmadan classifyAction() bu ucu VIEW sanardı; salt görüntüleme yetkisiyle cari/ürün kartı açılabilirdi.'
);
kontrol('RBAC: EBELGE modülü kayıtlı', str_contains($rbac, "'EBELGE'"));
kontrol('RBAC: EBelgeController kataloğa eklenmiş', str_contains($rbac, "'EBelgeController'    => 'EBELGE'"));

// ═════════════════════════════════════════════════════════════════════
// Rapor
// ═════════════════════════════════════════════════════════════════════

$basarili = 0;
$basarisiz = [];
echo "=== e-Belge cari/ürün eşleştirme (Faz 2) regresyon testi ===\n\n";
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
