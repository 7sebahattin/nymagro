<?php
/**
 * Tema güvenliği tarayıcısı — bu kod tabanında EN SIK tekrar eden iki hata
 * sınıfını tüm görünüm dosyalarında arar.
 * --------------------------------------------------------------------------
 * Kullanım:  php tests/mobile/tema_tarayici.php [--json]
 *
 * SINIF 1 — "yarım renk tanımı" (renksiz buton)
 *   Bir CSS kuralı `background` verip `color` vermiyorsa, <button> tarayıcı
 *   varsayılanı olan SİYAH metni alır. Koyu temada koyu zemin üzerinde siyah
 *   yazı görünmez olur. (Gerçek örnek: .btn-islem — hesaplar/detay ve
 *   masraflar/index; kullanıcı "bazı kutular okunmuyor" diye bildirdi.)
 *
 * SINIF 2 — "sabit zemin + temalı metin"
 *   Kural zemini SABİT bir hex ile veriyor ama metin rengini tema
 *   değişkenine (var(--text…)) bırakıyorsa, tema değiştiğinde metin
 *   zeminle aynı tarafa kayar ve okunmaz olur.
 *   (Gerçek örnek: .btn-donem { background:#334155; color:var(--text) } —
 *   açık temada koyu zemin üzerinde koyu yazı.)
 *
 * Çıktı: bulgular listesi. Karar (testi düşürme) çağıran tarafa aittir.
 */

declare(strict_types=1);

$kok = dirname(__DIR__, 2);
$jsonMu = in_array('--json', $argv, true);

/** Hex rengi 0–1 arası bağıl parlaklığa çevirir (WCAG). */
function parlaklik(string $hex): ?float
{
    $hex = ltrim(trim($hex), '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
        return null;
    }
    $kanal = static function (int $v): float {
        $s = $v / 255;
        return $s <= 0.03928 ? $s / 12.92 : pow(($s + 0.055) / 1.055, 2.4);
    };
    return 0.2126 * $kanal((int)hexdec(substr($hex, 0, 2)))
         + 0.7152 * $kanal((int)hexdec(substr($hex, 2, 2)))
         + 0.0722 * $kanal((int)hexdec(substr($hex, 4, 2)));
}

/**
 * Bir dosyadaki <style> blokları + inline style="" içeriğinden
 * "selector { bildirimler }" kurallarını çıkarır.
 *
 * @return array<int,array{secici:string,govde:string,satir:int}>
 */
function kurallariCikar(string $kaynak): array
{
    $kurallar = [];

    // <style> blokları
    if (preg_match_all('#<style\b[^>]*>(.*?)</style>#is', $kaynak, $bloklar, PREG_OFFSET_CAPTURE)) {
        foreach ($bloklar[1] as $blok) {
            [$icerik, $ofset] = $blok;
            // Yorumları at
            $icerik = preg_replace('#/\*.*?\*/#s', '', $icerik) ?? $icerik;
            if (preg_match_all('/([^{}@]+)\{([^{}]*)\}/s', $icerik, $m, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
                foreach ($m as $eslesme) {
                    $kurallar[] = [
                        'secici' => trim($eslesme[1][0]),
                        'govde'  => trim($eslesme[2][0]),
                        'satir'  => substr_count(substr($kaynak, 0, $ofset + $eslesme[0][1]), "\n") + 1,
                    ];
                }
            }
        }
    }

    return $kurallar;
}

/** Bildirim gövdesinden bir özelliğin değerini alır. */
function ozellik(string $govde, string $ad): ?string
{
    if (preg_match('/(?:^|;)\s*' . preg_quote($ad, '/') . '\s*:\s*([^;]+)/i', $govde, $m)) {
        return trim($m[1]);
    }
    return null;
}

/**
 * Bir dosyadaki class="a b c" bildirimlerinden BİRLİKTE KULLANIM haritası
 * çıkarır: sınıf => aynı öğede geçen diğer sınıflar.
 *
 * Neden gerekli: yaygın desen bir TABAN sınıfın rengi, bir DEĞİŞTİRİCİ
 * sınıfın yalnızca zemini vermesidir:
 *     .btn-action { color:#fff; }      .btn-yeni { background:#27ae60; }
 *     <button class="btn-action btn-yeni">
 * Değiştiriciyi tek başına "renksiz" saymak yanlış alarm üretir. Bu harita
 * sayesinde "aynı öğede bulunan başka bir sınıf rengi veriyor mu?" sorusu
 * cevaplanabilir.
 *
 * @return array<string, array<string,true>>
 */
function birlikteKullanim(string $kaynak): array
{
    $harita = [];
    if (preg_match_all('/class\s*=\s*"([^"]*)"/i', $kaynak, $m)) {
        foreach ($m[1] as $liste) {
            // Gömülü PHP ifadeleri sınıf adı değildir; ayıkla
            $liste = preg_replace('/<\?.*?\?' . '>/s', ' ', $liste) ?? $liste;
            $siniflar = preg_split('/\s+/', trim($liste)) ?: [];
            $siniflar = array_values(array_filter($siniflar, fn(string $s): bool => $s !== '' && preg_match('/^[A-Za-z_][\w-]*$/', $s) === 1));
            foreach ($siniflar as $s) {
                foreach ($siniflar as $d) {
                    if ($s !== $d) {
                        $harita[$s][$d] = true;
                    }
                }
            }
        }
    }
    return $harita;
}

/** Seçicideki son sınıf adını döndürür (.a.b > .c → 'c'). */
function seciciSonSinif(string $secici): ?string
{
    if (preg_match_all('/\.([A-Za-z_][\w-]*)/', $secici, $m)) {
        return end($m[1]);
    }
    return null;
}

$bulgular = [];

$dosyalar = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($kok . '/app/views'));
foreach ($it as $d) {
    if ($d->isFile() && $d->getExtension() === 'php') {
        $dosyalar[] = $d->getPathname();
    }
}
$dosyalar[] = $kok . '/public/css/panel-ui.css';
sort($dosyalar);

foreach ($dosyalar as $yol) {
    $kaynak = (string)@file_get_contents($yol);
    $goreli = str_replace($kok . '/', '', $yol);

    // .css dosyası ise tüm içerik bir "style bloğu" gibi ele alınır
    $kurallar = str_ends_with($yol, '.css')
        ? kurallariCikar('<style>' . $kaynak . '</style>')
        : kurallariCikar($kaynak);

    $eslik = birlikteKullanim($kaynak);

    // Bu dosyada hangi sınıflara renk (color) verilmiş?
    $renkliSiniflar = [];
    foreach ($kurallar as $k) {
        if (ozellik($k['govde'], 'color') !== null && preg_match_all('/\.([A-Za-z_][\w-]*)/', $k['secici'], $mm)) {
            foreach ($mm[1] as $s) {
                $renkliSiniflar[$s] = true;
            }
        }
    }

    foreach ($kurallar as $kural) {
        $secici = $kural['secici'];
        $govde  = $kural['govde'];

        // Yalnızca gerçekten zemin veren kurallar ilgilendirir
        $zemin = ozellik($govde, 'background') ?? ozellik($govde, 'background-color');
        if ($zemin === null) {
            continue;
        }
        $metin = ozellik($govde, 'color');

        // ── SINIF 1: buton benzeri seçicide zemin var ama metin rengi yok ──
        // (:hover/:focus gibi durum kuralları hariç — asıl kural rengi vermiş olabilir.)
        $durumKurali = (bool)preg_match('/:(hover|focus|active|disabled|checked|visited)/i', $secici);
        $butonBenzeri = (bool)preg_match('/(^|[\s,>])(button|\.btn[\w-]*|\.[\w-]*btn[\w-]*)\b/i', $secici);
        if ($butonBenzeri && !$durumKurali && $metin === null && !str_contains($zemin, 'transparent')) {
            // Aynı öğede yer alan BAŞKA bir sınıf rengi veriyorsa sorun yok
            // (taban sınıf + değiştirici deseni).
            $sonSinif = seciciSonSinif($secici);
            $renkVeren = false;
            if ($sonSinif !== null) {
                foreach (array_keys($eslik[$sonSinif] ?? []) as $kardes) {
                    if (isset($renkliSiniflar[$kardes])) {
                        $renkVeren = true;
                        break;
                    }
                }
                // Seçicinin kendi bileşenlerinden biri renk alıyorsa da yeterli
                // (ör. ".btn-det.red" → ".btn-det" rengi tanımlamış olabilir).
                if (!$renkVeren && preg_match_all('/\.([A-Za-z_][\w-]*)/', $secici, $mm)) {
                    foreach ($mm[1] as $parca) {
                        if ($parca !== $sonSinif && isset($renkliSiniflar[$parca])) {
                            $renkVeren = true;
                            break;
                        }
                    }
                }
            }
            if (!$renkVeren) {
                $bulgular[] = [
                    'sinif'   => 'renksiz-buton',
                    'dosya'   => $goreli,
                    'satir'   => $kural['satir'],
                    'secici'  => $secici,
                    'detay'   => "zemin='{$zemin}' ama color yok — <button> siyah varsayılanı alır",
                ];
            }
        }

        // ── SINIF 2: sabit hex zemin + tema değişkenli metin ──
        if ($metin !== null && preg_match('/var\(\s*--(text|text2|muted)\b/', $metin)
            && preg_match('/#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})\b/', $zemin, $hm)) {
            $lum = parlaklik($hm[0]);
            if ($lum !== null) {
                // Zemin çok koyu ya da çok açıksa, metin temayla ters yöne
                // kaydığında okunmaz hale gelir.
                if ($lum < 0.18 || $lum > 0.82) {
                    $bulgular[] = [
                        'sinif'   => 'sabit-zemin-temali-metin',
                        'dosya'   => $goreli,
                        'satir'   => $kural['satir'],
                        'secici'  => $secici,
                        'detay'   => "zemin='{$hm[0]}' (sabit, parlaklık " . round($lum, 3) . ") + color='{$metin}' (temaya bağlı)",
                    ];
                }
            }
        }
    }
}

if ($jsonMu) {
    echo json_encode(['bulgular' => $bulgular], JSON_UNESCAPED_UNICODE), "\n";
    exit(0);
}

$gruplu = [];
foreach ($bulgular as $b) {
    $gruplu[$b['sinif']][] = $b;
}

echo "=== Tema güvenliği taraması ===\n\n";
foreach ($gruplu as $sinif => $liste) {
    echo strtoupper($sinif) . ' — ' . count($liste) . " bulgu\n";
    foreach ($liste as $b) {
        echo "  {$b['dosya']}:{$b['satir']}\n";
        echo "     seçici: " . substr($b['secici'], 0, 70) . "\n";
        echo "     {$b['detay']}\n";
    }
    echo "\n";
}
echo 'TOPLAM: ' . count($bulgular) . "\n";
