<?php
/**
 * EBelgeGuvenlik
 * --------------------------------------------------------
 * e-Belge (UBL-TR XML) yüklemelerinin GÜVENLİK KAPISI.
 *
 * Bu sınıf bilinçli olarak SAFTIR: veritabanına, oturuma, dosya sistemine
 * yazmaz. Yalnızca "bu içerik işlenmeye uygun mu?" sorusunu yanıtlar ve
 * uygun değilse RuntimeException fırlatır. Böylece CI'da (veritabanı
 * olmadan) doğrudan test edilebilir — bkz. tests/regression/ebelge_parser_invariants.php
 *
 * KAPATTIĞI SALDIRI YÜZEYLERİ
 *  - XXE (dış varlık): <!DOCTYPE koşulsuz reddedilir + parser LIBXML_NONET ile
 *    çalışır ve LIBXML_NOENT ASLA verilmez (bkz. EBelgeParser).
 *  - XML bomb (billion laughs): entity tanımı DOCTYPE içinde yapılır; DOCTYPE
 *    reddi bu saldırıyı kökünden keser.
 *  - XSLT/stylesheet PI: <?xml-stylesheet reddedilir.
 *  - Zip bomb: entry sayısı, açılmış toplam boyut ve sıkıştırma oranı sınırlı.
 *  - Path traversal: zip entry adlarında '..', mutlak yol, ters bölü, NUL reddedilir.
 *    Ayrıca zip HİÇBİR ZAMAN diske açılmaz (extractTo kullanılmaz), bellekte okunur.
 *  - Çalıştırılabilir dosya yüklenmesi: uzantı beyaz listesi + gövdedeki
 *    tehlikeli uzantı parçaları reddedilir; dosya diske her zaman hash adıyla
 *    ve .xml uzantısıyla yazılır (bkz. EBelge::saklamaYolu).
 *
 * NOT: Meşru bir UBL-TR belgesi ASLA DOCTYPE içermez (UBL şema tabanlıdır,
 * DTD kullanmaz). Bu yüzden DOCTYPE reddi hiçbir gerçek faturayı engellemez.
 */
final class EBelgeGuvenlik
{
    /** Tek bir XML dosyasının azami ham boyutu. */
    public const MAX_XML_BYTES = 15 * 1024 * 1024;

    /** Yüklenen zip arşivinin azami boyutu. */
    public const MAX_ZIP_BYTES = 30 * 1024 * 1024;

    /** Zip içindeki azami girdi (dosya) sayısı. */
    public const MAX_ZIP_ENTRIES = 300;

    /** Zip'ten açılacak toplam veri tavanı (zip bomb koruması). */
    public const MAX_UNCOMPRESSED_TOTAL = 150 * 1024 * 1024;

    /** Tek bir girdi için azami sıkıştırma oranı (zip bomb koruması). */
    public const MAX_COMPRESSION_RATIO = 100;

    /** Yüklenebilecek dosya uzantıları. */
    public const IZINLI_UZANTILAR = ['xml', 'zip'];

    /**
     * Dosya adının HERHANGİ bir parçasında görülürse reddedilen uzantılar
     * ("fatura.php.xml" gibi çift uzantılı denemeler).
     */
    private const TEHLIKELI_PARCALAR = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar',
        'pl', 'py', 'jsp', 'asp', 'aspx', 'sh', 'cgi', 'exe', 'bat', 'cmd', 'com', 'msi', 'htaccess',
    ];

    /**
     * finfo'nun XML/ZIP için üretebileceği makul MIME değerleri.
     * MIME tek başına ZAYIF bir sinyaldir (birçok sunucu XML'e text/plain der);
     * asıl doğrulama kök eleman kontrolüdür — bu yüzden liste geniş tutulur,
     * ama application/x-* gibi çalıştırılabilir türler dışarıda kalır.
     */
    private const IZINLI_MIME = [
        'text/xml', 'application/xml', 'text/plain', 'application/octet-stream',
        'application/zip', 'application/x-zip-compressed', 'multipart/x-zip',
    ];

    /** UBL-TR'de kabul ettiğimiz kök elemanlar (namespace ön eki soyulmuş hâliyle). */
    public const IZINLI_KOK_ELEMANLAR = ['Invoice', 'DespatchAdvice'];

    /**
     * $_FILES girdisini denetler ve dosya türünü ('xml' | 'zip') döner.
     *
     * @throws RuntimeException
     */
    public static function yuklemeKapisi(array $file): string
    {
        $hata = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($hata !== UPLOAD_ERR_OK) {
            throw new RuntimeException(self::yuklemeHataMesaji($hata));
        }

        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new RuntimeException('Dosya sunucuya düzgün ulaşmadı. Lütfen tekrar deneyin.');
        }

        $ad = (string)($file['name'] ?? '');
        $uzanti = strtolower(pathinfo($ad, PATHINFO_EXTENSION));
        if (!in_array($uzanti, self::IZINLI_UZANTILAR, true)) {
            throw new RuntimeException('Yalnızca .xml veya .zip dosyası yüklenebilir.');
        }

        // Çift uzantı denemesi: "fatura.php.xml" gibi adlar reddedilir.
        // (Dosya diske zaten hash adıyla yazılır; bu ek bir savunma katmanıdır.)
        foreach (explode('.', strtolower($ad)) as $parca) {
            if (in_array($parca, self::TEHLIKELI_PARCALAR, true)) {
                throw new RuntimeException('Dosya adı güvenli değil: ' . $ad);
            }
        }

        $boyut = (int)($file['size'] ?? 0);
        $tavan = $uzanti === 'zip' ? self::MAX_ZIP_BYTES : self::MAX_XML_BYTES;
        if ($boyut <= 0) {
            throw new RuntimeException('Dosya boş görünüyor.');
        }
        if ($boyut > $tavan) {
            throw new RuntimeException(sprintf(
                'Dosya boyutu sınırı aşıyor (%s MB azami, gelen %s MB).',
                number_format($tavan / 1048576, 0, ',', '.'),
                number_format($boyut / 1048576, 1, ',', '.')
            ));
        }

        $mime = self::mimeTespit($tmp);
        if ($mime !== null && !in_array($mime, self::IZINLI_MIME, true)) {
            throw new RuntimeException('Dosya türü desteklenmiyor (tespit edilen: ' . $mime . ').');
        }

        return $uzanti;
    }

    /** finfo varsa MIME döner, yoksa null (kontrol atlanır — tek başına belirleyici değildir). */
    public static function mimeTespit(string $yol): ?string
    {
        if (!function_exists('finfo_open')) {
            return null;
        }
        $finfo = @finfo_open(FILEINFO_MIME_TYPE);
        if (!$finfo) {
            return null;
        }
        $mime = @finfo_file($finfo, $yol);
        finfo_close($finfo);
        return is_string($mime) && $mime !== '' ? $mime : null;
    }

    /**
     * Ham XML içeriğinin işlenmeye uygun olup olmadığını denetler.
     * Buradan geçen içerik EBelgeParser'a verilebilir.
     *
     * @throws RuntimeException
     */
    public static function xmlIcerikKapisi(string $ham): void
    {
        $boyut = strlen($ham);
        if ($boyut < 64) {
            throw new RuntimeException('Dosya içeriği bir XML belgesi olamayacak kadar küçük.');
        }
        if ($boyut > self::MAX_XML_BYTES) {
            throw new RuntimeException('XML içeriği boyut sınırını aşıyor.');
        }

        // ── XXE / XML bomb: DOCTYPE ve ENTITY koşulsuz reddedilir ──────────
        // Meşru UBL-TR belgelerinde DTD YOKTUR. Metin içeriğinde geçen bir
        // "<!DOCTYPE" ifadesi XML kurallarınca &lt; olarak kaçırılmak
        // zorundadır; yani ham içerikte bu diziyi görmek = DTD var demektir.
        if (stripos($ham, '<!DOCTYPE') !== false) {
            throw new RuntimeException('Belge DOCTYPE (DTD) içeriyor — güvenlik nedeniyle reddedildi. Geçerli bir e-Belge DTD içermez.');
        }
        if (stripos($ham, '<!ENTITY') !== false) {
            throw new RuntimeException('Belge XML varlık (ENTITY) tanımı içeriyor — güvenlik nedeniyle reddedildi.');
        }
        if (stripos($ham, '<?xml-stylesheet') !== false) {
            throw new RuntimeException('Belge XSLT/stylesheet yönergesi içeriyor — güvenlik nedeniyle reddedildi.');
        }

        $kok = self::kokEleman($ham);
        if ($kok === null) {
            throw new RuntimeException('Dosya geçerli bir XML belgesi gibi görünmüyor (kök eleman bulunamadı).');
        }
        if (!in_array($kok, self::IZINLI_KOK_ELEMANLAR, true)) {
            throw new RuntimeException(
                'Bu dosya bir UBL e-Belgesi değil. Beklenen kök eleman: Invoice veya DespatchAdvice, bulunan: ' . $kok . '.'
            );
        }
    }

    /**
     * Kök elemanın adını (namespace ön eki soyulmuş hâlde) döner; bulunamazsa null.
     * XML bildirimi, yorumlar ve işlem yönergeleri atlanır.
     */
    public static function kokEleman(string $ham): ?string
    {
        $bas = substr($ham, 0, 8192);
        $bas = preg_replace('/<\?.*?\?>/s', '', $bas) ?? $bas;   // XML bildirimi ve diğer işlem yönergeleri
        $bas = preg_replace('/<!--.*?-->/s', '', $bas) ?? $bas;   // yorumlar

        if (!preg_match('/<\s*([A-Za-z_][A-Za-z0-9_.\-]*(?::[A-Za-z_][A-Za-z0-9_.\-]*)?)/', $bas, $m)) {
            return null;
        }
        $ad = $m[1];
        $ikiNokta = strrpos($ad, ':');
        return $ikiNokta === false ? $ad : substr($ad, $ikiNokta + 1);
    }

    /**
     * İçeriği UTF-8'e normalize eder.
     *
     * NEDEN GEREKLİ: Muhasebe programlarının ürettiği bazı XML'ler ISO-8859-9
     * (Türkçe) veya windows-1254 kodlamasındadır. DOMDocument bildirimdeki
     * kodlamaya güvenir; bildirimi düzeltmeden içeriği çevirirsek Türkçe
     * karakterler bozulur (mojibake) veya parse hatası alınır. Bu yüzden hem
     * içerik çevrilir HEM DE bildirimdeki encoding UTF-8 olarak güncellenir.
     */
    public static function utf8eCevir(string $ham): string
    {
        // UTF-8 BOM'u kaldır (DOMDocument bunu "content before root" sayabilir).
        if (str_starts_with($ham, "\xEF\xBB\xBF")) {
            $ham = substr($ham, 3);
        }

        $bildirilen = null;
        if (preg_match('/<\?xml[^>]*encoding\s*=\s*["\']([A-Za-z0-9_\-]+)["\']/i', substr($ham, 0, 512), $m)) {
            $bildirilen = strtoupper($m[1]);
        }

        $utf8Mi = ($bildirilen === null || $bildirilen === 'UTF-8')
            && function_exists('mb_check_encoding')
            && mb_check_encoding($ham, 'UTF-8');

        if ($utf8Mi) {
            return $ham;
        }

        if (!function_exists('mb_convert_encoding')) {
            return $ham; // mbstring yoksa dokunma — parse aşaması hata verirse kullanıcıya bildirilir.
        }

        $kaynak = $bildirilen ?? 'ISO-8859-9';
        if ($kaynak === 'UTF-8') {
            // Bildirim UTF-8 diyor ama içerik değil → en olası aday Türkçe kodlama.
            $kaynak = 'ISO-8859-9';
        }

        $cevrilmis = @mb_convert_encoding($ham, 'UTF-8', $kaynak);
        if (!is_string($cevrilmis) || $cevrilmis === '') {
            return $ham;
        }

        // Bildirimi de UTF-8 yap, aksi hâlde parser içeriği yeniden yorumlar.
        $cevrilmis = preg_replace(
            '/(<\?xml[^>]*encoding\s*=\s*["\'])([A-Za-z0-9_\-]+)(["\'])/i',
            '${1}UTF-8${3}',
            $cevrilmis,
            1
        ) ?? $cevrilmis;

        return $cevrilmis;
    }

    /** Ham içeriğin SHA-256'sı — daima küçük harf hex. Hem idempotency anahtarı hem delil. */
    public static function hash(string $ham): string
    {
        return hash('sha256', $ham);
    }

    /**
     * Zip arşivini GÜVENLE okur ve içindeki .xml girdilerini bellekte döner.
     * Diske HİÇBİR ŞEY açmaz (extractTo kullanılmaz).
     *
     * @return array<int, array{ad:string, icerik:string}>
     * @throws RuntimeException
     */
    public static function zipIcerigiCikar(string $zipYolu): array
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException(
                'Bu sunucuda ZIP desteği (ZipArchive) yok. Lütfen XML dosyalarını tek tek yükleyin.'
            );
        }

        $zip = new ZipArchive();
        if ($zip->open($zipYolu) !== true) {
            throw new RuntimeException('ZIP arşivi açılamadı (dosya bozuk veya şifreli olabilir).');
        }

        try {
            if ($zip->numFiles > self::MAX_ZIP_ENTRIES) {
                throw new RuntimeException(sprintf(
                    'ZIP arşivi çok fazla dosya içeriyor (azami %d, bulunan %d).',
                    self::MAX_ZIP_ENTRIES,
                    $zip->numFiles
                ));
            }

            $cikti = [];
            $toplamAcilmis = 0;

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if ($stat === false) {
                    continue;
                }
                $ad = (string)$stat['name'];

                // Dizin girdisi — atla.
                if ($ad === '' || str_ends_with($ad, '/')) {
                    continue;
                }

                // ── Path traversal savunması ──────────────────────────────
                // Not: içerik diske açılmıyor, ama girdi adı log/gösterimde
                // kullanılıyor ve kötü niyetli bir ad zaten meşru bir arşivde
                // bulunmaz — arşivin tamamını reddetmek doğru davranıştır.
                if (str_contains($ad, '..')
                    || str_starts_with($ad, '/')
                    || str_contains($ad, '\\')
                    || str_contains($ad, "\0")
                    || preg_match('#^[A-Za-z]:#', $ad) === 1
                ) {
                    throw new RuntimeException('ZIP arşivinde güvenli olmayan dosya yolu var: ' . $ad);
                }

                // Yalnızca .xml girdileri işlenir; imza/PDF/README sessizce atlanır.
                if (strtolower(pathinfo($ad, PATHINFO_EXTENSION)) !== 'xml') {
                    continue;
                }

                $acilmis = (int)($stat['size'] ?? 0);
                $sikistirilmis = (int)($stat['comp_size'] ?? 0);

                if ($acilmis > self::MAX_XML_BYTES) {
                    throw new RuntimeException('ZIP içindeki "' . $ad . '" dosyası boyut sınırını aşıyor.');
                }
                if ($sikistirilmis > 0 && ($acilmis / $sikistirilmis) > self::MAX_COMPRESSION_RATIO) {
                    throw new RuntimeException('ZIP arşivi şüpheli sıkıştırma oranı içeriyor (zip bomb koruması): ' . $ad);
                }

                $toplamAcilmis += $acilmis;
                if ($toplamAcilmis > self::MAX_UNCOMPRESSED_TOTAL) {
                    throw new RuntimeException('ZIP arşivinin açılmış toplam boyutu sınırı aşıyor (zip bomb koruması).');
                }

                $icerik = $zip->getFromIndex($i, self::MAX_XML_BYTES);
                if ($icerik === false) {
                    throw new RuntimeException('ZIP içindeki "' . $ad . '" dosyası okunamadı.');
                }

                $cikti[] = ['ad' => basename($ad), 'icerik' => $icerik];
            }

            if (empty($cikti)) {
                throw new RuntimeException('ZIP arşivinde hiç .xml dosyası bulunamadı.');
            }

            return $cikti;
        } finally {
            $zip->close();
        }
    }

    private static function yuklemeHataMesaji(int $kod): string
    {
        return match ($kod) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Dosya sunucunun izin verdiği yükleme boyutunu aşıyor (php.ini: upload_max_filesize / post_max_size).',
            UPLOAD_ERR_PARTIAL    => 'Dosya yalnızca kısmen yüklendi, lütfen tekrar deneyin.',
            UPLOAD_ERR_NO_FILE    => 'Dosya seçilmedi.',
            UPLOAD_ERR_NO_TMP_DIR => 'Sunucuda geçici klasör bulunamadı.',
            UPLOAD_ERR_CANT_WRITE => 'Dosya sunucuya yazılamadı.',
            UPLOAD_ERR_EXTENSION  => 'Bir PHP eklentisi yüklemeyi durdurdu.',
            default               => 'Dosya yüklenemedi (hata kodu: ' . $kod . ').',
        };
    }
}
