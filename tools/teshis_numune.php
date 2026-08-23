<?php
/**
 * Teşhis aracı: Numune/İrsaliye/Perakende belgelerinin neden Numuneler/
 * İrsaliyeler/Perakende listelerinde görünmediğini bulmak için salt-okunur
 * bir döküm çıkarır.
 *
 * ÇALIŞTIRMA (yalnızca sunucu terminalinden / SSH ile — tarayıcıdan DEĞİL):
 *   php tools/teshis_numune.php
 *
 * Bu dosya PUBLIC (public/) klasörünün dışındadır, bu yüzden tarayıcıdan
 * URL ile erişilemez. Hiçbir veri değiştirmez — sadece SELECT çalıştırır.
 * Çıktıyı buraya (sohbete) yapıştırın, kök nedeni buradan tespit edip
 * kalıcı düzeltmeyi uygulayacağım.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Bu araç yalnızca komut satırından (php tools/teshis_numune.php) çalıştırılabilir.\n");
}

require_once __DIR__ . '/../app/config/config.php';

$dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
$pdo = new PDO($dsn, DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

function baslik(string $t): void
{
    echo "\n=== {$t} ===\n";
}

function tablo(array $rows): void
{
    if (empty($rows)) {
        echo "(kayıt yok)\n";
        return;
    }
    $cols = array_keys($rows[0]);
    echo implode(" | ", $cols) . "\n";
    echo str_repeat('-', 100) . "\n";
    foreach ($rows as $r) {
        $vals = array_map(static function ($v) {
            if ($v === null) return 'NULL';
            $s = (string)$v;
            return mb_strlen($s) > 40 ? mb_substr($s, 0, 37) . '...' : $s;
        }, $r);
        echo implode(" | ", $vals) . "\n";
    }
}

baslik('1) Şirketler (companies)');
tablo($pdo->query("SELECT id, company_name, status, deleted_at FROM companies ORDER BY id")->fetchAll());

baslik('2) Muhasebe Dönemleri (accounting_periods)');
tablo($pdo->query("SELECT id, company_id, period_name, fiscal_year, status, is_active FROM accounting_periods ORDER BY company_id, fiscal_year")->fetchAll());

baslik('3) faturalar tablosunda belge_tipi dağılımı (şirket+dönem+tip+silindi_mi bazında sayım)');
tablo($pdo->query(
    "SELECT company_id, period_id, belge_tipi, durum, silindi_mi, COUNT(*) AS adet,
            MIN(fatura_tarihi) AS en_eski, MAX(fatura_tarihi) AS en_yeni
     FROM faturalar
     WHERE belge_tipi IN ('numune','irsaliye','perakende')
     GROUP BY company_id, period_id, belge_tipi, durum, silindi_mi
     ORDER BY company_id, period_id, belge_tipi"
)->fetchAll());

baslik('4) Son 20 numune/irsaliye/perakende kaydı (tüm şirket/dönemler, filtre YOK)');
tablo($pdo->query(
    "SELECT f.id, f.company_id, f.period_id, f.belge_tipi, f.fatura_no, f.fatura_tarihi,
            f.durum, f.silindi_mi, f.cari_id, c.unvan AS cari_unvan, f.genel_toplam, f.created_by
     FROM faturalar f
     LEFT JOIN cariler c ON c.id = f.cari_id
     WHERE f.belge_tipi IN ('numune','irsaliye','perakende')
     ORDER BY f.id DESC
     LIMIT 20"
)->fetchAll());

baslik('5) MELSABERRY adlı cari(ler) ve bunlara bağlı TÜM faturalar (belge_tipi ayrımı olmadan)');
$melsaberry = $pdo->query("SELECT id, unvan, tip, company_id, silindi_mi FROM cariler WHERE unvan LIKE '%MELSABERRY%'")->fetchAll();
tablo($melsaberry);
foreach ($melsaberry as $c) {
    echo "\n  -- Cari #{$c['id']} ({$c['unvan']}) faturaları:\n";
    $stmt = $pdo->prepare(
        "SELECT id, company_id, period_id, belge_tipi, fatura_no, fatura_tarihi, durum, silindi_mi, genel_toplam
         FROM faturalar WHERE cari_id = :cid ORDER BY id DESC"
    );
    $stmt->execute([':cid' => $c['id']]);
    tablo($stmt->fetchAll());
}

baslik('6) users tablosunda oturum açan kullanıcı(lar) ve varsayılan/son aktif şirket bilgisi (varsa)');
try {
    tablo($pdo->query("SELECT id, username, full_name FROM users ORDER BY id")->fetchAll());
} catch (\Throwable $e) {
    echo "(users tablosu okunamadı: {$e->getMessage()})\n";
}

echo "\nBitti. Bu çıktının TAMAMINI kopyalayıp sohbete yapıştırın.\n";
