<?php
/**
 * Regresyon testi: "Yeni Masraf Gir" ekranındaki masraf kalemi listesi.
 * --------------------------------------------------------------------------
 * Çalıştırma:  php tests/regression/masraf_kalem_secimi_invariants.php
 * Çıkış kodu:  0 = tüm kontroller PASSED, 1 = en az bir FAILED.
 *
 * NEDEN VAR
 * İki ayrı hata birlikte raporlandı:
 *
 * 1) EKSİK KALEM — Masraf::altKategorilerFlat() listeyi `parent_id IS NOT NULL`
 *    ile süzüyordu. Kullanıcı "Yeni Ana Masraf Kalemi Ekle" ile bir kalem
 *    açıp altına alt kalem eklemediğinde o kalem formda HİÇ görünmüyordu;
 *    ekranda yalnızca alt kalemi olan tek bir grup kalıyordu. Doğru kural:
 *    seçilebilir olan YAPRAK kalemlerdir (alt kalemler + alt kalemi olmayan
 *    ana kalemler); alt kalemi olan ana kalem yalnızca optgroup başlığıdır.
 *
 * 2) RENK/OKUNABİLİRLİK — panel-ui.css'teki "kutu içi güvenlik ağı" yalnızca
 *    `select option` boyuyordu, `optgroup` boyanmıyordu. Grup başlığı
 *    tarayıcı varsayılanında (açık zemin + soluk gri yazı) kalınca koyu
 *    açılır listenin ortasında okunmaz beyaz bir şerit oluşuyordu.
 *
 * Testler YAPISALdır; yalnızca SQL davranışı gerçek bir motorda (bellek içi
 * SQLite) doğrulanır — sunucu/veritabanı gerektirmez, CI'da çalışır.
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

$model = oku($kok . '/app/models/Masraf.php');
$view  = oku($kok . '/app/views/masraflar/ekle.php');
$css   = oku($kok . '/public/css/panel-ui.css');

kontrol('Kaynak dosyalar okunabildi', $model !== '' && $view !== '' && $css !== '');

// ─────────────────────────────────────────────────────────────────────────
// 1. Model: hangi kalemler seçilebilir?
// ─────────────────────────────────────────────────────────────────────────
$sorgu = '';
if (preg_match('/public function altKategorilerFlat\(\).*?"(SELECT.*?)",/s', $model, $m)) {
    $sorgu = $m[1];
}

kontrol(
    'altKategorilerFlat() sorgusu bulunabildi',
    $sorgu !== '',
    'metot veya sorgu yapısı değişmiş olabilir — test güncellenmeli'
);

// `parent_id IS NOT NULL` tek başına kalırsa hata geri gelmiş demektir; o koşul
// ancak yaprak ana kalemleri kapsayan bir OR dalıyla birlikte kullanılabilir.
kontrol(
    'Liste "sadece alt kalem" filtresine geri dönmedi',
    $sorgu !== ''
    && (stripos($sorgu, 'parent_id IS NOT NULL') === false
        || preg_match('/parent_id IS NOT NULL\s+OR\s+NOT EXISTS/is', $sorgu) === 1),
    'WHERE koşulu yine yalnızca alt kalemleri alıyor — alt kalemi olmayan ana kalemler formdan kaybolur'
);

kontrol(
    'Alt kalemi olmayan ana kalemler de listeye giriyor (NOT EXISTS)',
    $sorgu !== '' && stripos($sorgu, 'NOT EXISTS') !== false,
    'yaprak ana kalemleri kapsayan koşul kaldırılmış'
);

// Sorgunun gerçek davranışı: bellek içi SQLite üzerinde çalıştırılır.
if ($sorgu !== '' && class_exists('PDO') && in_array('sqlite', PDO::getAvailableDrivers(), true)) {
    try {
        $db = new PDO('sqlite::memory:');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec(
            'CREATE TABLE masraf_kategoriler (
                id INTEGER PRIMARY KEY, parent_id INTEGER, ad TEXT, renk TEXT,
                sira INTEGER DEFAULT 0, silindi_mi INTEGER DEFAULT 0, company_id INTEGER)'
        );
        $satirlar = [
            // id, parent_id, ad, renk, sira, silindi_mi, company_id
            [1, null, 'KIRA',            '#2c3e6b', 0, 0, 1], // alt kalemi var → başlık
            [2, 1,    'DEPO',            '#5cb85c', 0, 0, 1],
            [3, 1,    'OFIS',            '#5bc0de', 1, 0, 1],
            [4, null, 'YAKIT',           '#f0ad4e', 1, 0, 1], // yaprak ana kalem
            [5, null, 'SILINMIS',        '#333333', 2, 1, 1], // silinmiş
            [6, null, 'BASKA_SIRKET',    '#333333', 0, 0, 2], // başka tenant
            [7, null, 'ALTI_SILINMIS',   '#111111', 3, 0, 1], // tek alt kalemi silinmiş → yaprak
            [8, 7,    'SILINMIS_ALT',    '#111111', 0, 1, 1],
        ];
        $st = $db->prepare('INSERT INTO masraf_kategoriler VALUES (?,?,?,?,?,?,?)');
        foreach ($satirlar as $s) {
            $st->execute($s);
        }

        $sonuc = $db->query(str_replace(':cid', '1', $sorgu))->fetchAll(PDO::FETCH_ASSOC);
        $adlar = array_column($sonuc, 'ad');
        sort($adlar);
        $beklenen = ['ALTI_SILINMIS', 'DEPO', 'OFIS', 'YAKIT'];

        kontrol(
            'Sorgu doğru kalem kümesini döndürüyor (SQLite üzerinde)',
            $adlar === $beklenen,
            'dönen: ' . implode(', ', $adlar) . ' | beklenen: ' . implode(', ', $beklenen)
        );

        $anaAdlari = [];
        foreach ($sonuc as $r) {
            $anaAdlari[$r['ad']] = $r['ana_adi'];
        }
        kontrol(
            'Alt kalemler ana kalem adıyla gruplanıyor, yaprak ana kalemler kök seviyede',
            ($anaAdlari['DEPO'] ?? null) === 'KIRA'
            && ($anaAdlari['OFIS'] ?? null) === 'KIRA'
            && array_key_exists('YAKIT', $anaAdlari) && $anaAdlari['YAKIT'] === null,
            'ana_adi eşlemesi beklenenden farklı: ' . json_encode($anaAdlari, JSON_UNESCAPED_UNICODE)
        );

        kontrol(
            'Tenant süzgeci korunuyor (başka şirketin kalemi gelmiyor)',
            !in_array('BASKA_SIRKET', $adlar, true),
            'company_id koşulu düşmüş — şirketler arası sızıntı'
        );
    } catch (Throwable $e) {
        kontrol('Sorgu SQLite üzerinde çalıştırılabildi', false, $e->getMessage());
    }
} else {
    echo "  (bilgi) PDO sqlite sürücüsü yok — sorgunun davranış testi atlandı.\n";
}

// ─────────────────────────────────────────────────────────────────────────
// 2. Görünüm: kök kalemler ve grup başlıkları birlikte basılıyor mu?
// ─────────────────────────────────────────────────────────────────────────
kontrol(
    'Form, gruba girmeyen kök kalemleri de basıyor',
    str_contains($view, '$kokKalemler'),
    'yalnızca optgroup döngüsü kalmış — ana kalemler tekrar kaybolur'
);

kontrol(
    'Düzenlemede kayıtlı kalem listede yoksa geri ekleniyor',
    str_contains($view, '$seciliKatId') && preg_match('/\$listede\s*=\s*false/', $view) === 1,
    'seçim koruma bloğu kaldırılmış — düzenlemede masraf sessizce başka kaleme kayabilir'
);

kontrol(
    'Option nitelikleri kaçışlanıyor (data-renk ham basılmıyor)',
    !preg_match('/data-renk="<\?=\s*\$k\[.renk.\]\s*\?>/', $view),
    'kullanıcının girdiği renk değeri niteliğe kaçışlanmadan yazılıyor'
);

// ─────────────────────────────────────────────────────────────────────────
// 3. Renk: açılır listenin grup başlığı okunur mu?
// ─────────────────────────────────────────────────────────────────────────
kontrol(
    'Panel güvenlik ağı optgroup başlığını da boyuyor',
    preg_match('/\.page-content select optgroup\s*\{/', $css) === 1
    || preg_match('/\.page-content select option,\s*\n\s*\.page-content select optgroup\s*\{/', $css) === 1,
    'panel-ui.css yalnızca option boyuyor — koyu listede beyaz/okunmaz grup başlığı şeridi geri gelir'
);

kontrol(
    'Açılır listenin native katmanı tema ile aynı tarafta (color-scheme)',
    preg_match('/\.page-content select\s*\{\s*color-scheme:\s*dark/s', $css) === 1
    && preg_match('/\[data-theme="acik"\][^{]*select\s*\{\s*color-scheme:\s*light/s', $css) === 1,
    'select için color-scheme eşlemesi kaldırılmış — açılır liste zemini temadan kopar'
);

kontrol(
    'Masraf formundaki yerel select stili option ve optgroup için zemin veriyor',
    preg_match('/\.mf-sel\s+optgroup\s*\{[^}]*background:/', $view) === 1
    && preg_match('/\.mf-sel\s+option\s*\{[^}]*background:/', $view) === 1,
    'sayfa içi select stilinde zemin tanımı yok'
);

// ─────────────────────────────────────────────────────────────────────────
// Rapor
// ─────────────────────────────────────────────────────────────────────────
$gecen = array_values(array_filter($sonuclar, static fn(array $s): bool => $s['ok']));
$kalan = array_values(array_filter($sonuclar, static fn(array $s): bool => !$s['ok']));

echo "=== Masraf kalemi seçimi regresyon taraması ===\n";
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
