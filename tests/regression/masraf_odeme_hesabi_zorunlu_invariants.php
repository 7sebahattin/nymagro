<?php
/**
 * Regresyon testi: Masraf formunda "Ödeme Hesabı" seçimi zorunlu.
 * --------------------------------------------------------------------------
 * Çalıştırma:  php tests/regression/masraf_odeme_hesabi_zorunlu_invariants.php
 * Çıkış kodu:  0 = tüm kontroller PASSED, 1 = en az bir FAILED.
 *
 * NEDEN VAR
 * Masraf girişinde "Ödeme Hesabı" (kasa_id) alanı opsiyoneldi. Hesap
 * seçilmeden kaydedilen bir masraf, ödendi işaretlendiğinde hangi kasadan
 * çıktığı belirsiz kalıyordu ve raporlarda "Kasa/Banka: —" olarak görünüyordu.
 * Alan artık formda zorunlu (required + istemci tarafı kontrol) VE model
 * katmanında zorunlu (asıl güvenlik ağı — istemci kontrolü DevTools'tan
 * atlatılabilir): ekle()/guncelle() kasa_id boşken InvalidArgumentException
 * fırlatır, mevcut kategori_id kontrolüyle aynı desende.
 *
 * Görünüm/JS kontrolleri kaynağı metin olarak denetler. Model kontrolleri
 * Masraf sınıfını GERÇEK dosyasından, veritabanı olmadan (Database/
 * TenantContext/Audit yerine sahte sınıf) yükleyip ekle()/guncelle()'yi
 * doğrudan çağırır — sahte Database çağrıları kaydeder, böylece doğrulamanın
 * gerçekten kayıttan ÖNCE devreye girdiği (insert/update hiç çağrılmadığı)
 * de kanıtlanır.
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

// ─────────────────────────────────────────────────────────────────────────
// 1) Görünüm: alan required + kırmızı yıldız + istemci tarafı kontrol
// ─────────────────────────────────────────────────────────────────────────
$view = oku($kok . '/app/views/masraflar/ekle.php');
kontrol('Görünüm dosyası okunabildi', $view !== '');

kontrol(
    '#fKasaId select\'i required',
    (bool)preg_match('/<select id="fKasaId" class="mf-sel" required>/', $view),
    'select artık zorunlu değil — HTML5 doğrulaması devre dışı kalmış olabilir'
);

kontrol(
    '"Ödeme Hesabı" etiketinde zorunlu alan yıldızı var',
    (bool)preg_match('/Ödeme Hesabı\s*<span style="color:#ef4444;">\*<\/span>/', $view),
    'etiket eski haline (yıldızsız) dönmüş olabilir'
);

kontrol(
    'masrafKaydet() fKasaId boşken kaydetmeyi durduruyor',
    (bool)preg_match('/if\s*\(\s*!document\.getElementById\(\'fKasaId\'\)\.value\s*\)\s*\{\s*showToast\(/', $view),
    'istemci tarafı kontrol kaldırılmış — form artık hesap seçilmeden de gönderilebilir'
);

// ─────────────────────────────────────────────────────────────────────────
// 2) Model: sahte veritabanı ile ekle()/guncelle() davranışı
// ─────────────────────────────────────────────────────────────────────────
class TenantContext
{
    public static function activeCompanyId(): ?int { return 1; }
    public static function activePeriodId(): ?int { return 3; }
}
class Audit
{
    public static function log(...$args): void {}
}
class Database
{
    private static ?Database $instance = null;
    public array $insertCalls = [];
    public array $updateCalls = [];

    public static function getInstance(): Database
    {
        return self::$instance ??= new self();
    }

    /** getir()'in beklediği satırı taklit eder: personel bağlı değil, senkron
     *  fonksiyonlar (syncPersonelHareketi) erken çıkıp ek sorgu yapmasın. */
    public function selectOne(string $sql, array $params = []): ?array
    {
        return [
            'id' => 1, 'kategori_id' => 5, 'kasa_id' => 3,
            'tutar' => 100.0, 'kdv_orani' => 20, 'kdv_tutari' => 16.67,
            'odeme_durumu' => 'bekliyor', 'para_birimi' => 'TRY',
            'personel_id' => null, 'personel_hareket_id' => null,
            'islem_tarihi' => '2026-01-01', 'aciklama' => '',
        ];
    }

    public function select(string $sql, array $params = []): array { return []; }

    public function insert(string $table, array $data): int
    {
        $this->insertCalls[] = ['table' => $table, 'data' => $data];
        return 999;
    }

    public function update(string $table, array $data, array $where): int
    {
        $this->updateCalls[] = ['table' => $table, 'data' => $data, 'where' => $where];
        return 1;
    }
}

require_once $kok . '/app/models/Masraf.php';

function yeniMasrafModeli(Database $db): Masraf
{
    $masraf = (new ReflectionClass('Masraf'))->newInstanceWithoutConstructor();
    $prop = new ReflectionProperty('Masraf', 'db');
    $prop->setAccessible(true);
    $prop->setValue($masraf, $db);
    return $masraf;
}

$temelVeri = [
    'kategori_id'  => 5,
    'islem_tarihi' => '2026-01-01',
    'tutar'        => 100.0,
    'kdv_orani'    => 20,
    'odeme_durumu' => 'bekliyor',
];

// ── ekle(): kasa_id boş/null/0 → istisna, insert HİÇ çağrılmamalı ────────
foreach ([null, 0, ''] as $bosDeger) {
    $db = new Database();
    $masraf = yeniMasrafModeli($db);
    $yakalandi = null;
    try {
        $masraf->ekle($temelVeri + ['kasa_id' => $bosDeger]);
    } catch (Throwable $e) {
        $yakalandi = $e;
    }
    $bosEtiket = var_export($bosDeger, true);
    kontrol(
        "ekle(): kasa_id={$bosEtiket} iken InvalidArgumentException fırlatıyor",
        $yakalandi instanceof InvalidArgumentException
        && str_contains($yakalandi->getMessage(), 'Ödeme hesabı'),
        $yakalandi ? ('fırlatılan: ' . get_class($yakalandi) . ' — ' . $yakalandi->getMessage()) : 'istisna fırlatılmadı'
    );
    kontrol(
        "ekle(): kasa_id={$bosEtiket} iken hiçbir INSERT çalıştırılmadı",
        $db->insertCalls === [],
        'doğrulama insert\'ten SONRA yapılıyor olabilir — kayıt kısmen oluşmuş olabilir'
    );
}

// ── ekle(): kasa_id geçerliyken normal çalışmaya devam ediyor ────────────
$db = new Database();
$masraf = yeniMasrafModeli($db);
$yeniId = null;
try {
    $yeniId = $masraf->ekle($temelVeri + ['kasa_id' => 3]);
} catch (Throwable $e) {
    kontrol('ekle(): kasa_id geçerliyken başarıyla kaydediyor', false, $e->getMessage());
}
if ($yeniId !== null) {
    kontrol(
        'ekle(): kasa_id geçerliyken başarıyla kaydediyor',
        $yeniId === 999 && count($db->insertCalls) === 1,
        'beklenmeyen sonuç: id=' . var_export($yeniId, true) . ', insert sayısı=' . count($db->insertCalls)
    );
}

// ── guncelle(): kasa_id boşaltılırsa istisna, UPDATE hiç çağrılmamalı ────
foreach ([null, 0, ''] as $bosDeger) {
    $db = new Database();
    $masraf = yeniMasrafModeli($db);
    $yakalandi = null;
    try {
        $masraf->guncelle(1, $temelVeri + ['kasa_id' => $bosDeger]);
    } catch (Throwable $e) {
        $yakalandi = $e;
    }
    $bosEtiket = var_export($bosDeger, true);
    kontrol(
        "guncelle(): kasa_id={$bosEtiket} iken InvalidArgumentException fırlatıyor",
        $yakalandi instanceof InvalidArgumentException
        && str_contains($yakalandi->getMessage(), 'Ödeme hesabı'),
        $yakalandi ? ('fırlatılan: ' . get_class($yakalandi) . ' — ' . $yakalandi->getMessage()) : 'istisna fırlatılmadı'
    );
    kontrol(
        "guncelle(): kasa_id={$bosEtiket} iken masraflar tablosuna hiçbir UPDATE çalıştırılmadı",
        array_filter($db->updateCalls, fn($c) => $c['table'] === 'masraflar') === [],
        'doğrulama update\'ten SONRA yapılıyor olabilir — kayıt kısmen güncellenmiş olabilir'
    );
}

// ── guncelle(): kasa_id geçerliyken normal çalışmaya devam ediyor ────────
$db = new Database();
$masraf = yeniMasrafModeli($db);
try {
    $masraf->guncelle(1, $temelVeri + ['kasa_id' => 7]);
    $masraflarUpdate = array_filter($db->updateCalls, fn($c) => $c['table'] === 'masraflar');
    kontrol(
        'guncelle(): kasa_id geçerliyken başarıyla güncelliyor',
        count($masraflarUpdate) === 1,
        'masraflar tablosuna beklenen UPDATE çalışmadı'
    );
} catch (Throwable $e) {
    kontrol('guncelle(): kasa_id geçerliyken başarıyla güncelliyor', false, $e->getMessage());
}

// ─────────────────────────────────────────────────────────────────────────
// Rapor
// ─────────────────────────────────────────────────────────────────────────
$gecen = array_values(array_filter($sonuclar, static fn(array $s): bool => $s['ok']));
$kalan = array_values(array_filter($sonuclar, static fn(array $s): bool => !$s['ok']));

echo "=== Masraf ödeme hesabı zorunluluğu regresyon taraması ===\n";
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
