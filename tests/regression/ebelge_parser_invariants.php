<?php
/**
 * Regresyon testi: e-Belge (UBL-TR XML) güvenlik kapısı ve ayrıştırıcısı.
 * --------------------------------------------------------------------------
 * Çalıştırma:  php tests/regression/ebelge_parser_invariants.php
 * Çıkış kodu:  0 = tüm kontroller PASSED, 1 = en az bir FAILED.
 *
 * NEDEN VERİTABANI GEREKTİRMEZ
 * EBelgeGuvenlik ve EBelgeParser bilinçli olarak SAF sınıflardır: veritabanına,
 * oturuma ve dosya sistemine dokunmazlar. Bu sayede depodaki diğer testlerin
 * aksine (bkz. fatura_kalem_invariants.php içindeki B8 notu) burada gerçek
 * İŞLEVSEL testler yazılabiliyor — yalnızca yapısal kontroller değil.
 *
 * KAPSAM
 *  1) Güvenlik kapısı: DOCTYPE/ENTITY reddi (XXE + XML bomb), kök eleman
 *     beyaz listesi, kodlama normalizasyonu.
 *  2) Ayrıştırma: e-Fatura / e-Arşiv / e-İrsaliye, çoklu KDV, tevkifat,
 *     kalem iskontosu, dövizli belge, ürün kodları.
 *  3) Doğrulama: tutar tutarsızlığı ve eksik kur UYARI üretmeli (hata değil).
 *  4) Mimari sözleşme: staging katmanı ÇEKİRDEK tablolara yazmamalı.
 */

$kok = dirname(__DIR__, 2);

require_once $kok . '/app/models/EBelgeGuvenlik.php';
require_once $kok . '/app/models/EBelgeParser.php';

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

/** Fixture'ı okur; okunamazsa testi FAILED yapar ve '' döner. */
function fixture(string $ad): string
{
    global $kok;
    $yol = $kok . '/tests/fixtures/ebelge/' . $ad;
    $icerik = @file_get_contents($yol);
    if ($icerik === false || $icerik === '') {
        kontrol("Fixture okunabildi: {$ad}", false, 'Dosya bulunamadı: ' . $yol);
        return '';
    }
    return $icerik;
}

/**
 * Kaynak koddan YORUMLARI ayıklar.
 *
 * NEDEN GEREKLİ: bu dosyadaki kaynak-tabanlı kontroller "LIBXML_NOENT
 * kullanılmıyor" gibi şeyleri sınıyor. Üretim kodundaki açıklama yorumları
 * bilinçli olarak bu sabitlerin ADINI geçiriyor ("… ASLA verilmez") — düz metin
 * araması bunu yanlışlıkla ihlal sayardı. Token'lara ayırıp yorumları atınca
 * kontrol yalnızca GERÇEK KODU sınar.
 */
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

/** Verilen kapalı fonksiyonun exception fırlatıp fırlatmadığını sınar. */
function reddediliyorMu(callable $fn, string &$mesaj = null): bool
{
    try {
        $fn();
        return false;
    } catch (Throwable $e) {
        $mesaj = $e->getMessage();
        return true;
    }
}

$parser = new EBelgeParser();

// ─────────────────────────────────────────────────────────────────────
// 1) GÜVENLİK KAPISI
// ─────────────────────────────────────────────────────────────────────

$xxe = fixture('11_doctype_xxe.xml');
if ($xxe !== '') {
    $mesaj = '';
    $reddedildi = reddediliyorMu(fn() => EBelgeGuvenlik::xmlIcerikKapisi($xxe), $mesaj);
    kontrol(
        'XXE denemesi (DOCTYPE + harici ENTITY) reddediliyor',
        $reddedildi && stripos($mesaj, 'DOCTYPE') !== false,
        $reddedildi ? "mesaj: {$mesaj}" : 'Dosya güvenlik kapısından GEÇTİ — kritik açık.'
    );
}

$bomb = fixture('12_xml_bomb.xml');
if ($bomb !== '') {
    $mesaj = '';
    $reddedildi = reddediliyorMu(fn() => EBelgeGuvenlik::xmlIcerikKapisi($bomb), $mesaj);
    kontrol(
        'XML bomb (billion laughs) reddediliyor',
        $reddedildi,
        $reddedildi ? "mesaj: {$mesaj}" : 'Dosya güvenlik kapısından GEÇTİ — kritik açık.'
    );
}

$yanlisKok = fixture('13_yanlis_kok.xml');
if ($yanlisKok !== '') {
    $mesaj = '';
    $reddedildi = reddediliyorMu(fn() => EBelgeGuvenlik::xmlIcerikKapisi($yanlisKok), $mesaj);
    kontrol(
        'UBL olmayan XML (yanlış kök eleman) reddediliyor',
        $reddedildi && stripos($mesaj, 'UBL') !== false,
        $reddedildi ? "mesaj: {$mesaj}" : 'Kök eleman denetimi çalışmıyor.'
    );
    kontrol('kokEleman() ad çözümlemesi doğru', EBelgeGuvenlik::kokEleman($yanlisKok) === 'Rapor');
}

$temel = fixture('01_efatura_temel_tl.xml');
if ($temel !== '') {
    $gecti = !reddediliyorMu(fn() => EBelgeGuvenlik::xmlIcerikKapisi($temel));
    kontrol('Geçerli e-Fatura güvenlik kapısından geçiyor', $gecti);
    kontrol('kokEleman() namespace ön ekini soyuyor', EBelgeGuvenlik::kokEleman($temel) === 'Invoice');
    kontrol(
        'hash() küçük harf 64 karakter SHA-256 üretiyor',
        preg_match('/^[0-9a-f]{64}$/', EBelgeGuvenlik::hash($temel)) === 1
    );
    kontrol(
        'Aynı içerik aynı hash üretiyor (idempotency anahtarı kararlı)',
        EBelgeGuvenlik::hash($temel) === EBelgeGuvenlik::hash($temel)
    );
}

// Kodlama normalizasyonu: ISO-8859-9 (Türkçe) içerik UTF-8'e çevrilmeli.
if (function_exists('mb_convert_encoding')) {
    $utf8Kaynak = '<?xml version="1.0" encoding="ISO-8859-9"?>' . "\n"
        . '<Invoice><Ad>Şeker Çiğdem Ürünleri İĞÜ</Ad></Invoice>';
    $iso = mb_convert_encoding($utf8Kaynak, 'ISO-8859-9', 'UTF-8');
    $cevrilmis = EBelgeGuvenlik::utf8eCevir($iso);
    kontrol(
        'ISO-8859-9 içerik UTF-8\'e çevriliyor (Türkçe karakterler korunuyor)',
        mb_check_encoding($cevrilmis, 'UTF-8') && str_contains($cevrilmis, 'Şeker Çiğdem Ürünleri İĞÜ'),
        'çıktı: ' . mb_substr($cevrilmis, 0, 80)
    );
    kontrol(
        'Kodlama çevriminde XML bildirimi de UTF-8 yapılıyor',
        str_contains($cevrilmis, 'encoding="UTF-8"'),
        'Bildirim güncellenmezse parser içeriği yeniden yorumlar ve Türkçe karakterler bozulur.'
    );
} else {
    kontrol('mbstring yüklü (kodlama çevrimi test edilebiliyor)', false, 'mbstring eklentisi yok — kodlama testi atlandı.');
}

// Gerçek Luca çıktılarında görülen varyant: XML BİLDİRİMİ OLMAYAN belge.
// (Denenen 7 gerçek dosyanın birinde XML bildirim satırı hiç yoktu.)
$bildirimsiz = '<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"'
    . ' xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2">'
    . '<cbc:UUID>11111111-2222-3333-4444-555555555555</cbc:UUID>'
    . '<cbc:ID>TST2026000000001</cbc:ID><cbc:IssueDate>2026-01-01</cbc:IssueDate></Invoice>';
kontrol('XML bildirimi olmayan belge güvenlik kapısından geçiyor (gerçek Luca varyantı)',
    !reddediliyorMu(fn() => EBelgeGuvenlik::xmlIcerikKapisi($bildirimsiz)));
kontrol('XML bildirimi olmayan belgede kök eleman doğru okunuyor',
    EBelgeGuvenlik::kokEleman($bildirimsiz) === 'Invoice');

// BOM temizliği
kontrol(
    'UTF-8 BOM temizleniyor',
    !str_starts_with(EBelgeGuvenlik::utf8eCevir("\xEF\xBB\xBF<Invoice/>"), "\xEF\xBB\xBF")
);

// ─────────────────────────────────────────────────────────────────────
// 2) AYRIŞTIRMA — e-Fatura (mutlu yol)
// ─────────────────────────────────────────────────────────────────────

if ($temel !== '') {
    try {
        $b = $parser->parse($temel);

        kontrol('e-Fatura: belge tipi doğru', $b['belge_tipi'] === 'efatura', 'gelen: ' . $b['belge_tipi']);
        kontrol('e-Fatura: ETTN okundu', $b['baslik']['belge_uuid'] === '9f2c1a44-7b3e-4d55-9c10-2f8ab6e51d07');
        kontrol('e-Fatura: belge no okundu', $b['baslik']['belge_no'] === 'ABC2026000000123');
        kontrol('e-Fatura: tarih normalize edildi', $b['baslik']['belge_tarihi'] === '2026-03-12');
        kontrol('e-Fatura: saat normalize edildi', $b['baslik']['belge_saati'] === '10:45:00');
        kontrol('e-Fatura: vade tarihi PaymentTerms\'ten okundu', $b['baslik']['vade_tarihi'] === '2026-04-11');
        kontrol('e-Fatura: para birimi TRY', $b['baslik']['para_birimi'] === 'TRY');
        kontrol('e-Fatura: genel toplam doğru', yaklasik((float)$b['baslik']['genel_toplam'], 11400.00),
            'gelen: ' . $b['baslik']['genel_toplam']);
        kontrol('e-Fatura: KDV toplamı doğru', yaklasik((float)$b['baslik']['vergi_toplami'], 1900.00));
        kontrol('e-Fatura: iskonto toplamı doğru', yaklasik((float)$b['baslik']['iskonto_toplami'], 500.00));

        kontrol('e-Fatura: gönderen VKN okundu', $b['taraflar']['gonderen']['vkn_tckn'] === '1234567890');
        kontrol('e-Fatura: gönderen şeması VKN', $b['taraflar']['gonderen']['kimlik_semasi'] === 'VKN');
        kontrol('e-Fatura: alıcı VKN okundu', $b['taraflar']['alici']['vkn_tckn'] === '9876543210');
        kontrol('e-Fatura: vergi dairesi okundu', $b['taraflar']['gonderen']['vergi_dairesi'] === 'Kepez');
        kontrol('e-Fatura: başlığa gönderen/alıcı VKN kopyalandı',
            $b['baslik']['gonderen_vkn_tckn'] === '1234567890' && $b['baslik']['alici_vkn_tckn'] === '9876543210');

        kontrol('e-Fatura: 2 kalem okundu', count($b['kalemler']) === 2, 'gelen: ' . count($b['kalemler']));

        $k1 = $b['kalemler'][0] ?? [];
        kontrol('Kalem 1: alıcı ürün kodu okundu (otomatik eşleşme yolu)', ($k1['alici_urun_kodu'] ?? null) === 'NYM-SLX-1L');
        kontrol('Kalem 1: barkod okundu (otomatik eşleşme yolu)', ($k1['barkod'] ?? null) === '8690000111222');
        kontrol('Kalem 1: satıcı ürün kodu okundu', ($k1['satici_urun_kodu'] ?? null) === 'ORN-4417');
        kontrol('Kalem 1: birim kodu okundu', ($k1['birim_kodu'] ?? null) === 'C62');
        kontrol('Kalem 1: miktar doğru', yaklasik((float)($k1['miktar'] ?? 0), 100.0));
        kontrol('Kalem 1: birim fiyat doğru', yaklasik((float)($k1['birim_fiyat'] ?? 0), 80.0));
        kontrol('Kalem 1: iskonto tutarı doğru', yaklasik((float)($k1['iskonto_tutari'] ?? 0), 500.0));
        kontrol('Kalem 1: iskonto ORANA çevrildi (%6,25)', yaklasik((float)($k1['iskonto_orani'] ?? 0), 6.25),
            'gelen: ' . ($k1['iskonto_orani'] ?? 'yok') . ' — çekirdek fatura_kalemleri iskontoyu oran olarak tutar');
        kontrol('Kalem 1: KDV oranı %20', yaklasik((float)($k1['kdv_orani'] ?? 0), 20.0));
        kontrol('Kalem 1: KDV tutarı 1500', yaklasik((float)($k1['kdv_tutari'] ?? 0), 1500.0));
        kontrol('Kalem 1: satır tutarı 7500', yaklasik((float)($k1['satir_tutari'] ?? 0), 7500.0));

        $k2 = $b['kalemler'][1] ?? [];
        kontrol('Kalem 2: yalnızca satıcı kodu var (ürünsüz kalem yolu)',
            ($k2['satici_urun_kodu'] ?? null) === 'HZM-NAK'
            && ($k2['barkod'] ?? null) === null
            && ($k2['alici_urun_kodu'] ?? null) === null);
        kontrol('Kalem 2: birim kodu KGM', ($k2['birim_kodu'] ?? null) === 'KGM');

        kontrol('e-Fatura: tutarlar tutarlı olduğu için UYARI ÜRETİLMEDİ',
            empty($b['uyarilar']),
            'uyarılar: ' . implode(' | ', $b['uyarilar']));
    } catch (Throwable $e) {
        kontrol('e-Fatura ayrıştırılabildi', false, $e->getMessage());
    }
}

// ─────────────────────────────────────────────────────────────────────
// 3) AYRIŞTIRMA — e-Arşiv, e-İrsaliye, çoklu KDV
// ─────────────────────────────────────────────────────────────────────

$earsiv = fixture('02_earsiv_temel.xml');
if ($earsiv !== '') {
    try {
        $b = $parser->parse($earsiv);
        kontrol('e-Arşiv: ProfileID ile ayrıştı', $b['belge_tipi'] === 'earsiv', 'gelen: ' . $b['belge_tipi']);
        kontrol('e-Arşiv: alıcı TCKN okundu',
            $b['taraflar']['alici']['vkn_tckn'] === '12345678901'
            && $b['taraflar']['alici']['kimlik_semasi'] === 'TCKN');
        kontrol('e-Arşiv: unvan yoksa ad+soyaddan üretiliyor',
            ($b['taraflar']['alici']['unvan'] ?? '') === 'Ayşe Örnek',
            'gelen: ' . ($b['taraflar']['alici']['unvan'] ?? 'yok'));
    } catch (Throwable $e) {
        kontrol('e-Arşiv ayrıştırılabildi', false, $e->getMessage());
    }
}

$irsaliye = fixture('03_eirsaliye.xml');
if ($irsaliye !== '') {
    try {
        $b = $parser->parse($irsaliye);
        kontrol('e-İrsaliye: DespatchAdvice kökü tanındı', $b['belge_tipi'] === 'eirsaliye', 'gelen: ' . $b['belge_tipi']);
        kontrol('e-İrsaliye: 2 kalem okundu', count($b['kalemler']) === 2);
        kontrol('e-İrsaliye: DeliveredQuantity okundu',
            yaklasik((float)($b['kalemler'][0]['miktar'] ?? 0), 100.0)
            && yaklasik((float)($b['kalemler'][1]['miktar'] ?? 0), 250.0));
        kontrol('e-İrsaliye: taraflar Despatch/Delivery altından okundu',
            $b['taraflar']['gonderen']['vkn_tckn'] === '1234567890'
            && $b['taraflar']['alici']['vkn_tckn'] === '9876543210');
        kontrol('e-İrsaliye: tutar alanları 0 (tutar taşımaz) ve bu hata sayılmıyor',
            yaklasik((float)$b['baslik']['genel_toplam'], 0.0) && empty($b['uyarilar']),
            'uyarılar: ' . implode(' | ', $b['uyarilar']));
    } catch (Throwable $e) {
        kontrol('e-İrsaliye ayrıştırılabildi', false, $e->getMessage());
    }
}

$cokKdv = fixture('04_cok_kdv_orani.xml');
if ($cokKdv !== '') {
    try {
        $b = $parser->parse($cokKdv);
        kontrol('Çoklu KDV: belge düzeyinde 2 vergi satırı okundu',
            count($b['belge_vergileri']) === 2, 'gelen: ' . count($b['belge_vergileri']));
        kontrol('Çoklu KDV: kalem oranları ayrı ayrı okundu',
            yaklasik((float)($b['kalemler'][0]['kdv_orani'] ?? 0), 1.0)
            && yaklasik((float)($b['kalemler'][1]['kdv_orani'] ?? 0), 20.0));
        kontrol('Çoklu KDV: uyarı üretilmedi (tutarlar tutuyor)', empty($b['uyarilar']),
            'uyarılar: ' . implode(' | ', $b['uyarilar']));
    } catch (Throwable $e) {
        kontrol('Çoklu KDV belgesi ayrıştırılabildi', false, $e->getMessage());
    }
}

// ─────────────────────────────────────────────────────────────────────
// 4) DOĞRULAMA UYARILARI (hata değil, uyarı olmalı)
// ─────────────────────────────────────────────────────────────────────

$tutarsiz = fixture('05_tutar_tutarsiz.xml');
if ($tutarsiz !== '') {
    try {
        $b = $parser->parse($tutarsiz);
        $metin = mb_strtolower(implode(' | ', $b['uyarilar']), 'UTF-8');
        kontrol('Tutar tutarsızlığı UYARI olarak raporlanıyor (belge yine de okunuyor)',
            !empty($b['uyarilar']) && str_contains($metin, 'uyuşmuyor'),
            'uyarılar: ' . implode(' | ', $b['uyarilar']));
    } catch (Throwable $e) {
        kontrol('Tutarsız belge yine de ayrıştırılabiliyor', false, $e->getMessage());
    }
}

$tevkifat = fixture('06_tevkifatli.xml');
if ($tevkifat !== '') {
    try {
        $b = $parser->parse($tevkifat);
        kontrol('Tevkifat: belge toplamı okundu', yaklasik((float)$b['baslik']['tevkifat_toplami'], 400.0),
            'gelen: ' . $b['baslik']['tevkifat_toplami']);
        kontrol('Tevkifat: kalem düzeyinde ayrıştırıldı',
            yaklasik((float)($b['kalemler'][0]['tevkifat_tutari'] ?? 0), 400.0));
        kontrol('Tevkifat: KDV tutarı tevkifatla KARIŞMADI',
            yaklasik((float)($b['kalemler'][0]['kdv_tutari'] ?? 0), 2000.0),
            'gelen KDV: ' . ($b['kalemler'][0]['kdv_tutari'] ?? 'yok'));
        $metin = mb_strtolower(implode(' | ', $b['uyarilar']), 'UTF-8');
        kontrol('Tevkifat: kullanıcıya bilgilendirme uyarısı üretiliyor',
            str_contains($metin, 'tevkifat'),
            'uyarılar: ' . implode(' | ', $b['uyarilar']));
        kontrol('Tevkifat: vergi satırları eksiksiz saklanıyor (kalem düzeyinde 2 satır)',
            count($b['kalemler'][0]['vergiler'] ?? []) === 2,
            'gelen: ' . count($b['kalemler'][0]['vergiler'] ?? []));
    } catch (Throwable $e) {
        kontrol('Tevkifatlı belge ayrıştırılabildi', false, $e->getMessage());
    }
}

$dovizli = fixture('07_dovizli_kursuz.xml');
if ($dovizli !== '') {
    try {
        $b = $parser->parse($dovizli);
        kontrol('Dövizli: para birimi USD okundu', $b['baslik']['para_birimi'] === 'USD');
        kontrol('Dövizli: kur yoksa null kalıyor (0 kabul edilmiyor)', $b['baslik']['kur'] === null);
        $metin = mb_strtolower(implode(' | ', $b['uyarilar']), 'UTF-8');
        kontrol('Dövizli: eksik kur için uyarı üretiliyor', str_contains($metin, 'kur'),
            'uyarılar: ' . implode(' | ', $b['uyarilar']));
        kontrol('İstisna kodu kalem düzeyinde okundu',
            ($b['kalemler'][0]['istisna_kodu'] ?? null) === '301',
            'gelen: ' . ($b['kalemler'][0]['istisna_kodu'] ?? 'yok'));
    } catch (Throwable $e) {
        kontrol('Dövizli belge ayrıştırılabildi', false, $e->getMessage());
    }
}

// ─────────────────────────────────────────────────────────────────────
// 5) HATALI GİRDİLER — kontrollü reddedilmeli
// ─────────────────────────────────────────────────────────────────────

$bozuk = fixture('10_bozuk_kesik.xml');
if ($bozuk !== '') {
    $mesaj = '';
    $reddedildi = reddediliyorMu(fn() => $parser->parse($bozuk), $mesaj);
    kontrol('Bozuk/kesik XML kontrollü hata veriyor (fatal değil)',
        $reddedildi && str_contains($mesaj, 'ayrıştırılamadı'),
        'mesaj: ' . $mesaj);
}

$uuidYok = fixture('14_uuid_yok.xml');
if ($uuidYok !== '') {
    $mesaj = '';
    $reddedildi = reddediliyorMu(fn() => $parser->parse($uuidYok), $mesaj);
    kontrol('ETTN (UUID) olmayan belge REDDEDİLİYOR (idempotency anahtarı kurulamaz)',
        $reddedildi && stripos($mesaj, 'ETTN') !== false,
        'mesaj: ' . $mesaj);
}

// Sayı çevrimi kenar durumları
kontrol('sayiyaCevir: nokta ondalık', yaklasik((float)EBelgeParser::sayiyaCevir('1234.56'), 1234.56));
kontrol('sayiyaCevir: virgül ondalık toleransı', yaklasik((float)EBelgeParser::sayiyaCevir('1234,56'), 1234.56));
kontrol('sayiyaCevir: binlik ayraçlı', yaklasik((float)EBelgeParser::sayiyaCevir('1,234.56'), 1234.56));
kontrol('sayiyaCevir: sayı olmayan değer null döner (sessizce 0 olmaz)', EBelgeParser::sayiyaCevir('abc') === null);
kontrol('sayiyaCevir: boş değer null döner', EBelgeParser::sayiyaCevir('   ') === null);

// ─────────────────────────────────────────────────────────────────────
// 6) MİMARİ SÖZLEŞME — staging katmanı çekirdeğe YAZMAMALI
// ─────────────────────────────────────────────────────────────────────

$cekirdekTablolar = 'faturalar|fatura_kalemleri|stok_hareketleri|urun_stok_depo|cariler|urunler_hizmetler|kasa_hareketleri|kasa_banka';

foreach (['app/models/EBelge.php', 'app/controllers/EBelgeController.php'] as $goreli) {
    $ham = @file_get_contents($kok . '/' . $goreli);
    if ($ham === false) {
        kontrol("{$goreli} okunabildi", false);
        continue;
    }
    $kaynak = kodSadece($ham);

    $yazmaCagrisi = preg_match(
        '/->\s*(insert|update|softDelete)\s*\(\s*[\'"](' . $cekirdekTablolar . ')[\'"]/i',
        $kaynak
    ) === 1;
    kontrol(
        "{$goreli}: çekirdek tablolara insert/update/softDelete YOK",
        !$yazmaCagrisi,
        'Staging katmanı çekirdek tablolara yalnızca Fatura::ekle() üzerinden dokunabilir.'
    );

    $hamSql = preg_match(
        '/(INSERT\s+INTO|UPDATE|DELETE\s+FROM)\s+`?(' . $cekirdekTablolar . ')`?\b/i',
        $kaynak
    ) === 1;
    kontrol(
        "{$goreli}: çekirdek tablolara ham yazma SQL\'i YOK",
        !$hamSql,
        'Ham INSERT/UPDATE/DELETE bulundu — stok ve cari bakiye tutarlılığı bozulabilir.'
    );
}

$parserHam = @file_get_contents($kok . '/app/models/EBelgeParser.php');
$parserKaynak = $parserHam === false ? false : kodSadece($parserHam);
if ($parserKaynak !== false) {
    kontrol('EBelgeParser: LIBXML_NOENT KULLANILMIYOR (XXE kapısı açılmıyor)',
        !str_contains($parserKaynak, 'LIBXML_NOENT'));
    kontrol('EBelgeParser: LIBXML_DTDLOAD KULLANILMIYOR',
        !str_contains($parserKaynak, 'LIBXML_DTDLOAD'));
    kontrol('EBelgeParser: LIBXML_HUGE KULLANILMIYOR (parser sertlik limitleri korunuyor)',
        !str_contains($parserKaynak, 'LIBXML_HUGE'));
    kontrol('EBelgeParser: LIBXML_NONET veriliyor (parser ağa çıkamaz)',
        str_contains($parserKaynak, 'LIBXML_NONET'));
    kontrol('EBelgeParser: veritabanına dokunmuyor (saf sınıf)',
        !str_contains($parserKaynak, 'Database::') && !str_contains($parserKaynak, 'TenantContext::'));
}

$guvenlikHam = @file_get_contents($kok . '/app/models/EBelgeGuvenlik.php');
$guvenlikKaynak = $guvenlikHam === false ? false : kodSadece($guvenlikHam);
if ($guvenlikKaynak !== false) {
    kontrol('EBelgeGuvenlik: zip extractTo() KULLANILMIYOR (arşiv diske açılmaz)',
        !str_contains($guvenlikKaynak, 'extractTo'));
    kontrol('EBelgeGuvenlik: DOCTYPE denetimi mevcut',
        str_contains($guvenlikKaynak, '<!DOCTYPE'));
}

// ─────────────────────────────────────────────────────────────────────
// 7) ŞEMA ↔ KOD UYUMU — "Unknown column" hatasını canlıdan ÖNCE yakala
// ─────────────────────────────────────────────────────────────────────
//
// Bu modülde veritabanına yazılan alanların bir kısmı parser'ın ürettiği
// diziden GELİR (taraflar/kalemler/vergiler doğrudan insert edilir). Parser'a
// eklenen bir alanın DDL'e eklenmemesi canlıda "Unknown column" ile patlar.
// Aşağıdaki kontroller bunu veritabanı olmadan yakalar.

$modelHam = @file_get_contents($kok . '/app/models/EBelge.php');
if ($modelHam === false) {
    kontrol('app/models/EBelge.php okunabildi', false);
} else {
    /** CREATE TABLE gövdelerinden kolon adlarını çıkarır. */
    $ddlKolonlari = function (string $kaynak): array {
        $tablolar = [];
        if (preg_match_all('/CREATE TABLE IF NOT EXISTS\s+(\w+)\s*\((.*?)\)\s*ENGINE=/s', $kaynak, $eslesmeler, PREG_SET_ORDER)) {
            foreach ($eslesmeler as $t) {
                $kolonlar = [];
                foreach (preg_split('/\r?\n/', $t[2]) as $satir) {
                    $satir = trim($satir);
                    if ($satir === '' || preg_match('/^(PRIMARY|UNIQUE|KEY|INDEX|CONSTRAINT|FOREIGN)\b/i', $satir)) {
                        continue;
                    }
                    if (preg_match('/^([a-z_][a-z0-9_]*)\s+/i', $satir, $km)) {
                        $kolonlar[] = $km[1];
                    }
                }
                $tablolar[$t[1]] = $kolonlar;
            }
        }
        return $tablolar;
    };

    /** Literal insert() çağrısındaki dizi anahtarlarını çıkarır. */
    $insertAnahtarlari = function (string $kaynak, string $tablo): array {
        $desen = "/insert\(\s*'" . preg_quote($tablo, '/') . "'\s*,\s*\[(.*?)\]\s*\)/s";
        if (preg_match($desen, $kaynak, $m) !== 1) {
            return [];
        }
        preg_match_all("/'([a-z_][a-z0-9_]*)'\s*=>/i", $m[1], $km);
        return $km[1];
    };

    $semalar = $ddlKolonlari($modelHam);
    $beklenenTablolar = [
        'e_belge_paketleri', 'e_belge_dosyalari', 'e_belgeler', 'e_belge_taraflar',
        'e_belge_kalemleri', 'e_belge_vergiler', 'e_belge_cari_eslesme', 'e_belge_urun_eslesme',
    ];
    kontrol('Şema: 8 staging tablosunun tamamı tanımlı',
        count(array_intersect($beklenenTablolar, array_keys($semalar))) === count($beklenenTablolar),
        'bulunan: ' . implode(', ', array_keys($semalar)));

    // Tekillik (idempotency) anahtarları yerinde mi?
    kontrol('Şema: dosya tekilliği UNIQUE (company_id, dosya_hash)',
        str_contains($modelHam, 'uq_ebd_company_hash (company_id, dosya_hash)'));
    kontrol('Şema: belge tekilliği UNIQUE (company_id, belge_uuid)',
        str_contains($modelHam, 'uq_eb_company_uuid (company_id, belge_uuid)'));
    kontrol('Şema: yedek doğal anahtar UNIQUE (company_id, belge_tipi, gonderen, no, tarih)',
        str_contains($modelHam, 'uq_eb_dogal (company_id, belge_tipi, gonderen_vkn_tckn, belge_no, belge_tarihi)'));
    kontrol('Şema: staging tablolarında period_id kolonu YOK (otomatik dönem damgası istenmiyor)',
        preg_match('/^\s*period_id\s+INT/mi', $modelHam) === 0,
        'period_id kolonu eklenirse TenantContext::tenantAwareInsert() aktif dönemi otomatik damgalar '
        . 've belge yanlış döneme kilitlenir. Aktarım dönemi için aktarim_period_id kullanılır.');

    // Literal insert()'ler ↔ DDL
    foreach (['e_belge_paketleri', 'e_belge_dosyalari', 'e_belgeler'] as $tablo) {
        $anahtarlar = $insertAnahtarlari($modelHam, $tablo);
        $eksik = array_diff($anahtarlar, $semalar[$tablo] ?? []);
        kontrol("Şema uyumu: {$tablo} insert alanlarının tamamı DDL'de var",
            $anahtarlar !== [] && $eksik === [],
            $anahtarlar === [] ? 'insert() çağrısı bulunamadı' : 'DDL\'de olmayan alanlar: ' . implode(', ', $eksik));
    }

    // Parser çıktısı ↔ DDL (dinamik insert edilen diziler)
    if ($temel !== '') {
        try {
            $b = $parser->parse($temel);

            $tarafAnahtarlari = array_keys($b['taraflar']['gonderen']);
            $eksik = array_diff($tarafAnahtarlari, $semalar['e_belge_taraflar'] ?? []);
            kontrol('Şema uyumu: parser taraf alanları e_belge_taraflar ile örtüşüyor',
                $eksik === [], 'DDL\'de olmayan alanlar: ' . implode(', ', $eksik));

            $kalem = $b['kalemler'][0];
            unset($kalem['vergiler']); // model bunu insert etmeden önce çıkarır
            $eksik = array_diff(array_keys($kalem), $semalar['e_belge_kalemleri'] ?? []);
            kontrol('Şema uyumu: parser kalem alanları e_belge_kalemleri ile örtüşüyor',
                $eksik === [], 'DDL\'de olmayan alanlar: ' . implode(', ', $eksik));

            $vergi = $b['belge_vergileri'][0] ?? ($b['kalemler'][0]['vergiler'][0] ?? []);
            $eksik = array_diff(array_keys($vergi), $semalar['e_belge_vergiler'] ?? []);
            kontrol('Şema uyumu: parser vergi alanları e_belge_vergiler ile örtüşüyor',
                $vergi !== [] && $eksik === [], 'DDL\'de olmayan alanlar: ' . implode(', ', $eksik));
        } catch (Throwable $e) {
            kontrol('Şema uyumu kontrolü çalıştırılabildi', false, $e->getMessage());
        }
    }

    // Şema kurulumu depodaki konvansiyona uymalı.
    $modelKod = kodSadece($modelHam);
    kontrol('ensureSchema(): açık transaction varken DDL çalıştırmıyor (örtük commit tuzağı)',
        str_contains($modelKod, 'inTransaction()'));
    kontrol('ensureSchema(): istek başına tek kez çalışıyor',
        str_contains($modelKod, 'self::$semaHazir'));
    kontrol('ensureSchema(): hata panelin tamamını düşürmüyor (try/catch + error_log)',
        preg_match('/catch\s*\(\s*Throwable[^)]*\)\s*\{[^}]*error_log/s', $modelKod) === 1);
    kontrol('Ham XML dosya adı kullanıcı girdisinden DEĞİL hash\'ten üretiliyor',
        str_contains($modelKod, "\$hash . '.xml'"));
    kontrol('İndirme yolu realpath ile public kökünün dışına çıkamıyor',
        str_contains($modelKod, 'realpath') && str_contains($modelKod, 'str_starts_with'));
}

// ─────────────────────────────────────────────────────────────────────
// 8) META: PHP kapanış etiketi tuzağı
// ─────────────────────────────────────────────────────────────────────
//
// Tek satırlık bir yorumda geçen "?" + ">" dizisi PHP blogunu KAPATIR. Bu,
// e-Belge modülünde iki kez gerçekten yaşandı: birinde sınıf parse edilemedi,
// diğerinde bir TEST DOSYASI yarıda PHP modundan çıkıp geri kalanı düz metin
// olarak bastı ve yine de ÇIKIŞ KODU 0 döndürdü — yani CI "PASSED" gördü ama
// kontrollerin yarısı hiç çalışmadı. Bu sessiz başarısızlık, testlerin
// koruduğu her şeyi anlamsız kılar.
//
// Bu modüldeki dosyalar HTML üretmez; hiçbirinde kapanış etiketi bulunmamalıdır.

$kapanisTaranan = array_merge(
    glob($kok . '/app/models/EBelge*.php') ?: [],
    [$kok . '/app/controllers/EBelgeController.php'],
    glob($kok . '/tests/regression/ebelge_*.php') ?: []
);
foreach ($kapanisTaranan as $dosya) {
    $icerik = @file_get_contents($dosya);
    if ($icerik === false) {
        kontrol('Meta: ' . basename($dosya) . ' okunabildi', false);
        continue;
    }
    $kapanisVar = false;
    foreach (token_get_all($icerik) as $token) {
        if (is_array($token) && $token[0] === T_CLOSE_TAG) {
            $kapanisVar = true;
            break;
        }
    }
    kontrol(
        'Meta: ' . basename($dosya) . ' PHP kapanış etiketi içermiyor',
        !$kapanisVar,
        'Yorum içindeki kapanış dizisi dosyayı yarıda PHP modundan çıkarır; test sessizce "başarılı" görünür.'
    );
}

// ─────────────────────────────────────────────────────────────────────
// Rapor
// ─────────────────────────────────────────────────────────────────────

$basarili = 0;
$basarisiz = [];
echo "=== e-Belge (XML) ayrıştırıcı ve güvenlik kapısı regresyon testi ===\n\n";
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
