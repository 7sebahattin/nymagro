<?php
/**
 * Regresyon testi: Satışlar ekranından Perakende girişi ve Serbest Meslek
 * Makbuzları kaldırıldı.
 * --------------------------------------------------------------------------
 * Çalıştırma:  php tests/regression/satis_perakende_girisi_kaldirildi_invariants.php
 * Çıkış kodu:  0 = tüm kontroller PASSED, 1 = en az bir FAILED.
 *
 * NEDEN VAR
 * Kullanıcı isteğiyle Satışlar ekranından üç görünür öğe kaldırıldı:
 *   - Üstteki sekme çubuğundaki "Perakende" sekmesi,
 *   - "Perakende Satış Gir" butonu (hem Faturalar sekmesinde hem de eski
 *     Perakende sekmesinde iki ayrı yerde vardı),
 *   - "Serbest Meslek Makbuzları" butonu (zaten yalnızca "yakında eklenecek"
 *     uyarısı gösteren bir taslaktı, gerçek bir ekranı yoktu).
 * Ayrıca Raporlar altında ayrı bir "Perakende Satış Raporu" olup olmadığı
 * kontrol edildi — böyle bir rapor hiç yoktu, bu yüzden kaldırılacak bir şey
 * yok; bu test o durumun GERİ GELMEDİĞİNİ (yanlışlıkla eklenmediğini) izler.
 *
 * BİLİNÇLİ OLARAK DOKUNULMAYANLAR (bu testin konusu DEĞİL):
 *   - /satis/perakende ekranı ve SatisController::perakende()/perakendeKaydet()
 *     — yalnızca oraya giden görünür giriş noktaları kaldırıldı, ekranın
 *     kendisi ve geçmiş perakende faturaları (belge_tipi='perakende') duruyor.
 *   - Basit Satış Raporu ve diğer finansal raporlardaki
 *     belge_tipi IN ('satis','perakende') süzgeçleri — perakende faturalar
 *     gerçek satış olarak sayılmaya devam ediyor, bu doğru davranış.
 *
 * Yapısal bir test: kaynağı metin olarak denetler, veritabanı gerektirmez.
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

$viewHam = oku($kok . '/app/views/satislar/index.php');
$raporController = oku($kok . '/app/controllers/RaporController.php');

kontrol('Kaynak dosyalar okunabildi', $viewHam !== '' && $raporController !== '');

// HTML yorumları (bu testin ve view'daki açıklama yorumunun kendisi "Perakende
// Satış Gir" / "Serbest Meslek Makbuzları" ifadelerini METİN olarak barındırır
// — kaldırıldıklarını AÇIKLAMAK için) çıkarılır; aksi halde testler kendi
// açıklama yorumlarını "buton hâlâ var" sanıp yanlış pozitif üretir.
$view = (string)preg_replace('/<!--.*?-->/s', '', $viewHam);

// ─────────────────────────────────────────────────────────────────────────
// 1) Üst sekme çubuğunda "Perakende" sekmesi yok
// ─────────────────────────────────────────────────────────────────────────
kontrol(
    'Sekme çubuğunda ?tip=perakende bağlantısı yok',
    !str_contains($view, "/satis?tip=perakende"),
    'Perakende sekmesi geri gelmiş'
);
kontrol(
    'Sekme çubuğunda "Perakende" etiketi yok',
    !preg_match('/fa-cash-register.*?Perakende\s*</s', $view),
    'Perakende sekme ikonu/etiketi geri gelmiş'
);

// ─────────────────────────────────────────────────────────────────────────
// 2) "Perakende Satış Gir" ve "Serbest Meslek Makbuzları" butonları yok
// ─────────────────────────────────────────────────────────────────────────
kontrol(
    '"Perakende Satış Gir" butonu (hiçbir sekmede) yok',
    !str_contains($view, 'Perakende Satış Gir'),
    'buton metni hâlâ view içinde bulunuyor'
);
kontrol(
    'satis/perakende\'ye giden action-btn bağlantısı yok',
    !preg_match('/href="<\?=\s*BASE_URL\s*\?>\/satis\/perakende"\s*class="btn-action"/', $view),
    'action-btns içinde perakende ekranına giden buton geri gelmiş'
);
kontrol(
    '"Serbest Meslek Makbuzları" butonu yok',
    !str_contains($view, 'Serbest Meslek Makbuzları'),
    'buton metni hâlâ view içinde bulunuyor'
);

// ─────────────────────────────────────────────────────────────────────────
// 3) belgeTipi==='perakende' dalı artık bir "ekle" butonu üretmiyor
//    (tab kaldırıldığı için normal gezinmeyle erişilemez, ama action-btns
//    bloğunda orphan bir buton bırakılmadığını da doğruluyoruz)
// ─────────────────────────────────────────────────────────────────────────
if (preg_match('/<div class="action-btns">(.*?)<\?php\s+endif;\s*\?>\s*\n<\/div>/s', $view, $m)) {
    $blok = $m[1];
    kontrol(
        "action-btns bloğunda belgeTipi === 'perakende' dalı yok",
        !preg_match('/\bbelgeTipi\s*===\s*[\'"]perakende[\'"]/', $blok),
        'eski dal geri gelmiş — tab olmadan da bu buton /satis?tip=perakende ile erişilebilir kalırdı'
    );
} else {
    kontrol('action-btns bloğu bulunabildi', false, 'blok yapısı değişmiş olabilir — test güncellenmeli');
}

// ─────────────────────────────────────────────────────────────────────────
// 4) Bilinçli olarak dokunulmayanlar hâlâ yerinde
//    (aşırı-geniş bir "perakende" temizliğinin yanlışlıkla asıl ekranı veya
//    finansal raporlardaki gerçek satış sayımını silmediğini kanıtlar)
// ─────────────────────────────────────────────────────────────────────────
$satisController = oku($kok . '/app/controllers/SatisController.php');
$perakendeView = oku($kok . '/app/views/satislar/perakende.php');
$raporModel = oku($kok . '/app/models/Rapor.php');

kontrol(
    'SatisController::perakende() hâlâ mevcut (ekran silinmedi, yalnızca girişi gizlendi)',
    (bool)preg_match('/function\s+perakende\s*\(/', $satisController),
    'perakende() metodu kaldırılmış — bu istenenden fazlası'
);
kontrol(
    'satislar/perakende.php görünümü hâlâ mevcut',
    $perakendeView !== '',
    'view dosyası silinmiş — bu istenenden fazlası'
);
kontrol(
    "Basit Satış Raporu hâlâ belge_tipi IN ('satis','perakende') sayıyor",
    (bool)preg_match("/belge_tipi\s+IN\s*\(\s*'satis'\s*,\s*'perakende'\s*\)/", $raporModel),
    'perakende faturalar artık satış raporuna dahil edilmiyor olabilir — bu istenenden fazlası'
);

// ─────────────────────────────────────────────────────────────────────────
// 5) Raporlar altında ayrı bir "Perakende Satış Raporu" yok
// ─────────────────────────────────────────────────────────────────────────
kontrol(
    'RaporController\'da perakende\'ye özel bir rapor tanımlı değil',
    !preg_match('/[\'"][^\'"]*[Pp]erakende[^\'"]*[Rr]apor[^\'"]*[\'"]/', $raporController),
    'perakende adını taşıyan bir rapor başlığı eklenmiş — kullanıcı "varsa kaldır" demişti, "yoksa ekleme" de geçerli kalmalı'
);

// ─────────────────────────────────────────────────────────────────────────
// Rapor
// ─────────────────────────────────────────────────────────────────────────
$gecen = array_values(array_filter($sonuclar, static fn(array $s): bool => $s['ok']));
$kalan = array_values(array_filter($sonuclar, static fn(array $s): bool => !$s['ok']));

echo "=== Perakende girişi kaldırma regresyon taraması ===\n";
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
