<?php
/**
 * Regresyon testi: 2026-08-24 rapor bulguları — Rapor modeli düzeltmeleri.
 * --------------------------------------------------------------------------
 * Çalıştırma:  php tests/regression/rapor_cari_tip_ve_hizmet_invariants.php
 * Çıkış kodu:  0 = tüm kontroller PASSED, 1 = en az bir FAILED.
 *
 * Kapsanan 2 kök neden düzeltmesi:
 *
 *  1) Alış Raporu / Basit Satış Raporu / Satış Kaybı Raporu, gerçek (iptal
 *     olmayan) faturalar varken sessizce BOŞ dönebiliyordu. Kök neden:
 *     Rapor::invoiceWhere() faturanın kendi belge_tipi'i (zaten $typeSql ile
 *     doğru filtrelenmiş) yetmiyormuş gibi, AYRICA bağlı carinin "tip"
 *     alanının da (musteri/tedarikci) uyuşmasını istiyordu. Uygulama zaten
 *     "Tedarikçiye Satış Yap" / "Müşteriye Alış Gir" gibi çapraz işlemleri
 *     desteklediği ve carinin tip alanı zamanla faturanın belge_tipi'inden
 *     bağımsız kalabildiği için (elle düzenleme, e-Fatura'yı mevcut bir
 *     cariyle eşleştirme, vb.) bu fazladan koşul gerçek faturaları raporlardan
 *     sessizce düşürüyordu. Artık kaldırıldı — faturanın kendi belge_tipi'i
 *     tek ve yeterli kaynak.
 *
 *  2) Ürün Alış-Satış Raporu ve Stok-Satış Karşılama Raporu, urunler_hizmetler
 *     tablosundaki HİZMET kalemlerini (ör. "Ardiye Hizmet Bedeli") de gerçek
 *     ürünlermiş gibi listeliyordu — "Mevcut stok" gibi hizmet için anlamsız
 *     alanlarla. getProductStockReport() (Ürünler Stok Raporu) zaten
 *     "u.tip = 'urun'" filtresi kullanıyordu; aynı ilke burada da uygulandı.
 *
 * Veritabanı gerektirmez: kaynak denetimi + Reflection iledir.
 */

$kok = dirname(__DIR__, 2);

require_once $kok . '/app/models/Rapor.php';

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
// 1) invoiceWhere() artık carinin "tip" alanına göre fatura hariç tutmuyor
// ═════════════════════════════════════════════════════════════════════

$invoiceWhereGovde = metotGovdesi('Rapor', 'invoiceWhere');
kontrol('invoiceWhere() içinde carinin tip alanını zorunlu kılan eski koşul ("c.tip = :cari_tip") KALDIRILDI',
    !str_contains($invoiceWhereGovde, "c.tip = :cari_tip"));
kontrol('invoiceWhere() içinde ":cari_tip" parametresi artık kullanılmıyor',
    !str_contains($invoiceWhereGovde, ':cari_tip'));
kontrol('invoiceWhere() hâlâ tenant (company_id/period_id) filtresini uyguluyor (fazladan koşulu kaldırırken tenant izolasyonu bozulmadı)',
    str_contains($invoiceWhereGovde, 'f.company_id = :tenant_company_id') && str_contains($invoiceWhereGovde, 'f.period_id = :tenant_period_id'));
kontrol('invoiceWhere() hâlâ iptal faturaları varsayılan olarak dışlıyor (bu düzeltme iptal-hariç-tutma mantığını bozmadı)',
    str_contains($invoiceWhereGovde, "f.durum <> 'iptal'"));
kontrol('invoiceWhere() $cariType parametresi hâlâ customer_id/supplier_id seçiminde kullanılıyor (yalnızca fazladan c.tip WHERE\'i kaldırıldı, cariId seçim mantığı korundu)',
    (bool)preg_match("/match\\s*\\(\\s*\\\$cariType\\s*\\)/", $invoiceWhereGovde));

// ═════════════════════════════════════════════════════════════════════
// 2) Ürün raporları artık yalnızca gerçek ürünleri (tip='urun') listeliyor
// ═════════════════════════════════════════════════════════════════════

$urunAlisSatisGovde = metotGovdesi('Rapor', 'getProductPurchaseSalesReport');
kontrol("getProductPurchaseSalesReport() u.tip = 'urun' filtresi uyguluyor (hizmet kalemleri artık listelenmiyor)",
    str_contains($urunAlisSatisGovde, "u.tip = 'urun'"));

// NOT (sonraki tur): STOK raporlarındaki bu filtre daha sonra
// Urun::stokTakipKosuluSql() ile değiştirildi — aynı kuralın DAHA GENİŞİ
// (tip = 'urun' VE stok takibi kapalı değil). Yani hizmet kalemleri hâlâ
// dışarıda; üstüne stok takibi kapatılmış kartlar da dışarıda.
// Ayrıntı: tests/regression/stok_takibi_invariants.php
$stokKarsilamaGovde = metotGovdesi('Rapor', 'getStockSalesCoverageReportFull');
kontrol('getStockSalesCoverageReportFull() hizmet kalemlerini dışarıda bırakıyor',
    str_contains($stokKarsilamaGovde, "u.tip = 'urun'")
    || str_contains($stokKarsilamaGovde, 'Urun::stokTakipKosuluSql'));

// Referans: getProductStockReport() (Ürünler Stok Raporu) bu ilkeyi zaten
// uyguluyordu — yeni eklenen filtrelerin bu mevcut ilkeyle aynı olduğunu
// doğrular (kod tabanında sürüklenme olmadığının kanıtı).
$urunlerStokGovde = metotGovdesi('Rapor', 'getProductStockReport');
kontrol('Referans: getProductStockReport() aynı ilkeyi kullanıyor (kod tabanında sürüklenme yok)',
    str_contains($urunlerStokGovde, "u.tip = 'urun'")
    || str_contains($urunlerStokGovde, 'Urun::stokTakipKosuluSql'));

// ═════════════════════════════════════════════════════════════════════
// Meta: PHP kapanış etiketi tuzağı yok
// ═════════════════════════════════════════════════════════════════════

foreach ([$kok . '/app/models/Rapor.php', __FILE__] as $dosya) {
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
echo "=== Rapor: cari tip filtresi + hizmet kalemi hariç tutma regresyon testi ===\n\n";
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
