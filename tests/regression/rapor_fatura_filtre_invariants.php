<?php
/**
 * Regresyon testi: fatura tabanlı raporların filtre koşulları.
 * --------------------------------------------------------------------------
 * Çalıştırma:  php tests/regression/rapor_fatura_filtre_invariants.php
 * Çıkış kodu:  0 = tüm kontroller PASSED, 1 = en az bir FAILED.
 *
 * NEDEN VAR
 * Basit Satış Raporu, veritabanında onaylanmış satış faturaları dururken bile
 * hep boş geliyordu — üstelik hata vermeden, "Bu filtreye uygun kayıt
 * bulunamadı" diyerek.
 *
 * Kök neden Rapor::invoiceWhere() içindeki cari koşuluydu:
 *   RaporController::filters() cari seçilmediğinde customer_id/supplier_id'yi
 *   max(0, (int)...) ile (int) 0'a normalize eder — null veya '' değil.
 *   Koşul ise `$cariId !== null && $cariId !== ''` idi; PHP'de bu ifade 0 için
 *   de DOĞRUdur. Sonuç: kullanıcı hiçbir cari seçmese bile sorguya
 *   "f.cari_id = 0" ekleniyor, hiçbir faturanın cari_id'si 0 olmadığı için
 *   sorgu geçerli ama daima sıfır satır dönüyordu.
 *
 * Etkilenen raporlar invoiceWhere() kullanan HEPSİydi: Basit Satış, Alışlar,
 * Satış Kaybı, İadeler, Teklifler, İrsaliyeler.
 *
 * Ayrıca filtre panelindeki "Bu yıl" seçeneği Rapor::dateWhere() içinde
 * karşılıksızdı (FinansalRapor'daki ikizinde vardı): seçenek sunuluyor ama
 * hiçbir tarih süzgeci uygulanmıyordu — kullanıcı filtrelediğini sanırken tüm
 * kayıtları görüyordu.
 *
 * Test, modeli veritabanı olmadan (Database/TenantContext yerine sahte sınıf)
 * yükleyip private yardımcıları reflection ile çağırır; üretilen WHERE
 * metnini denetler. CI'da veritabanı gerekmez.
 */

$kok = dirname(__DIR__, 2);

/** @var array<int,array{ad:string,ok:bool,detay:string}> */
$sonuclar = [];

function kontrol(string $ad, bool $gecti, string $detay = ''): void
{
    global $sonuclar;
    $sonuclar[] = ['ad' => $ad, 'ok' => $gecti, 'detay' => $detay];
}

// ─── Modeli veritabanısız yükle ──────────────────────────────────────────
class TenantContext
{
    public static function activeCompanyId(): ?int { return 1; }
    public static function activePeriodId(): ?int { return 3; }
}
class Database
{
    public static function getInstance() { return null; }
}
require_once $kok . '/app/models/Rapor.php';

$rapor = (new ReflectionClass('Rapor'))->newInstanceWithoutConstructor();

$invoiceWhere = new ReflectionMethod('Rapor', 'invoiceWhere');
$invoiceWhere->setAccessible(true);
$dateWhere = new ReflectionMethod('Rapor', 'dateWhere');
$dateWhere->setAccessible(true);

/** RaporController::filters()'ın hiçbir GET parametresi yokken ürettiği değerler. */
$bosFiltre = [
    'period' => '', 'start_date' => '', 'end_date' => '',
    'customer_id' => 0, 'supplier_id' => 0, 'product_id' => 0,
    'status' => '', 'payment_status' => '', 'invoice_no' => '',
    'min_amount' => '', 'max_amount' => '',
];

// ─── 1. Testin dayandığı varsayım: filters() gerçekten 0 üretiyor mu? ────
$controller = @file_get_contents($kok . '/app/controllers/RaporController.php') ?: '';
kontrol(
    "filters() cari seçilmediğinde 0 üretiyor (testin dayandığı varsayım)",
    preg_match("/'customer_id'\s*=>\s*max\(0,\s*\(int\)/", $controller) === 1
    && preg_match("/'supplier_id'\s*=>\s*max\(0,\s*\(int\)/", $controller) === 1,
    'normalizasyon değişmiş — bu testin senaryosu güncellenmeli'
);

// ─── 2. Cari seçilmemişken cari_id koşulu EKLENMEMELİ ───────────────────
foreach (['musteri', 'tedarikci', ''] as $cariTipi) {
    [$where, $params] = $invoiceWhere->invoke($rapor, $bosFiltre, "f.belge_tipi = 'satis'", $cariTipi);
    $etiket = $cariTipi === '' ? '(tip yok)' : $cariTipi;
    kontrol(
        "Cari seçilmemişken cari_id koşulu eklenmiyor — {$etiket}",
        !str_contains($where, 'cari_id') && !array_key_exists(':cari_id', $params),
        'üretilen WHERE: ' . $where
    );
}

// ─── 3. Cari SEÇİLİYKEN koşul çalışmaya devam etmeli ─────────────────────
[$where, $params] = $invoiceWhere->invoke(
    $rapor,
    ['customer_id' => 42] + $bosFiltre,
    "f.belge_tipi IN ('satis','perakende')",
    'musteri'
);
kontrol(
    'Müşteri seçildiğinde cari_id koşulu uygulanıyor',
    str_contains($where, 'f.cari_id = :cari_id') && ($params[':cari_id'] ?? null) === 42,
    'üretilen WHERE: ' . $where
);

[$where, $params] = $invoiceWhere->invoke(
    $rapor,
    ['supplier_id' => 7] + $bosFiltre,
    "f.belge_tipi = 'alis'",
    'tedarikci'
);
kontrol(
    'Tedarikçi seçildiğinde cari_id koşulu uygulanıyor',
    str_contains($where, 'f.cari_id = :cari_id') && ($params[':cari_id'] ?? null) === 7,
    'üretilen WHERE: ' . $where
);

// ─── 4. Kiracı (şirket/dönem) süzgeçleri her hâlükârda duruyor ───────────
[$where, $params] = $invoiceWhere->invoke($rapor, $bosFiltre, "f.belge_tipi = 'satis'", 'musteri');
kontrol(
    'Şirket ve dönem süzgeçleri korunuyor',
    str_contains($where, 'f.company_id = :tenant_company_id')
    && str_contains($where, 'f.period_id = :tenant_period_id')
    && ($params[':tenant_company_id'] ?? null) === 1
    && ($params[':tenant_period_id'] ?? null) === 3,
    'üretilen WHERE: ' . $where
);
kontrol(
    'İptal faturalar varsayılan olarak dışarıda',
    str_contains($where, "f.durum <> 'iptal'"),
    'üretilen WHERE: ' . $where
);

// "İptal" durumu özellikle seçildiğinde dışlama uygulanmamalı (eski düzeltme).
[$where] = $invoiceWhere->invoke($rapor, ['status' => 'iptal'] + $bosFiltre, "f.belge_tipi = 'satis'", 'musteri');
kontrol(
    'Durum "iptal" seçildiğinde iptal dışlaması kalkıyor',
    !str_contains($where, "f.durum <> 'iptal'") && str_contains($where, 'f.durum = :status'),
    'üretilen WHERE: ' . $where
);

// ─── 5. Tarih filtresi seçenekleri karşılıksız kalmamalı ────────────────
$view = @file_get_contents($kok . '/app/views/raporlar/report.php') ?: '';
$panelSecenekleri = ['today', 'yesterday', 'last_7', 'last_30', 'this_month', 'last_month', 'last_6', 'this_year'];
$karsiliksiz = [];
foreach ($panelSecenekleri as $secenek) {
    [$sql] = $dateWhere->invoke($rapor, ['period' => $secenek], 'f.fatura_tarihi');
    if (trim((string)$sql) === '') {
        $karsiliksiz[] = $secenek;
    }
}
kontrol(
    'Filtre panelindeki her tarih aralığı gerçekten uygulanıyor',
    $karsiliksiz === [],
    'karşılığı olmayan seçenek(ler): ' . implode(', ', $karsiliksiz)
    . ' — panelde sunulup sessizce "Tümü" gibi davranıyor'
);

// Panelde sunulan seçenek listesi ile test edilen liste ayrışmasın.
foreach ($panelSecenekleri as $secenek) {
    if (!str_contains($view, "'{$secenek}'")) {
        kontrol("Panel seçeneği '{$secenek}' hâlâ görünümde tanımlı", false, 'test listesi güncellenmeli');
    }
}

// ─────────────────────────────────────────────────────────────────────────
// Rapor
// ─────────────────────────────────────────────────────────────────────────
$gecen = array_values(array_filter($sonuclar, static fn(array $s): bool => $s['ok']));
$kalan = array_values(array_filter($sonuclar, static fn(array $s): bool => !$s['ok']));

echo "=== Fatura tabanlı rapor filtreleri regresyon taraması ===\n";
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
