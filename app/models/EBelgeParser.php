<?php
/**
 * EBelgeParser
 * --------------------------------------------------------
 * UBL-TR e-Belge (e-Fatura / e-Arşiv Fatura / e-İrsaliye) XML ayrıştırıcısı.
 *
 * TASARIM KURALI — BU SINIF SAFTIR:
 *   Veritabanına, oturuma, dosya sistemine, TenantContext'e DOKUNMAZ.
 *   Girdi: ham XML metni.  Çıktı: normalize edilmiş PHP dizisi.
 *   Böylece aynı XML'i 10 kez ayrıştırmak HİÇBİR yan etki üretmez ve sınıf
 *   CI'da veritabanı olmadan test edilebilir
 *   (bkz. tests/regression/ebelge_parser_invariants.php).
 *
 * GÜVENLİK:
 *   Ham içerik buraya gelmeden ÖNCE EBelgeGuvenlik::xmlIcerikKapisi()'ndan
 *   geçmelidir (DOCTYPE/ENTITY reddi). Burada ayrıca:
 *     - LIBXML_NONET  → parser ağa çıkamaz
 *     - LIBXML_NOENT  ASLA verilmez → varlık ikamesi (XXE) yapılmaz
 *     - LIBXML_DTDLOAD / LIBXML_DTDATTR / LIBXML_HUGE ASLA verilmez
 *   PHP 8'de dış varlık yükleme zaten varsayılan kapalıdır; bunlar açık
 *   (belgelenmiş) savunma katmanıdır.
 *
 * NEDEN SimpleXML DEĞİL DOMXPath:
 *   UBL dört ayrı namespace kullanır. SimpleXML ile children($ns) zincirlemek
 *   hem okunmaz hem hataya açıktır. XPath ile alan adresleri doğrudan ve
 *   denetlenebilir yazılır.
 */
final class EBelgeParser
{
    public const NS_INVOICE  = 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2';
    public const NS_DESPATCH = 'urn:oasis:names:specification:ubl:schema:xsd:DespatchAdvice-2';
    public const NS_CAC      = 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2';
    public const NS_CBC      = 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2';
    public const NS_EXT      = 'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2';

    /** Tek belgede işlenecek azami kalem sayısı (bellek/DoS koruması). */
    public const MAX_KALEM = 1000;

    /** Tutar karşılaştırmalarında kabul edilen yuvarlama toleransı. */
    public const TOLERANS = 0.01;

    /**
     * GİB vergi türü kodları.
     * 0015 (KDV) ve 9015 (KDV Tevkifatı) yaygın ve doğrulanmış kodlardır.
     * ÖTV/ÖİV kodları üreticiye göre değişebildiği için KOD DEĞİL AD üzerinden
     * ikincil bir eşleme yapılır; her hâlükârda ham kod ve ad e_belge_vergiler
     * tablosuna eksiksiz yazılır (hiçbir vergi bilgisi kaybolmaz).
     * DİKKAT: ÖTV/ÖİV eşlemesi gerçek belgelerle DOĞRULANMALIDIR.
     */
    public const VERGI_KDV      = '0015';
    public const VERGI_TEVKIFAT = '9015';

    /**
     * Ham XML'i normalize diziye çevirir.
     *
     * @return array{
     *   kok:string, belge_tipi:string, baslik:array, taraflar:array,
     *   kalemler:array, belge_vergileri:array, uyarilar:array
     * }
     * @throws RuntimeException Ayrıştırma başarısızsa (mesaj kullanıcıya gösterilebilir).
     */
    public function parse(string $hamXml): array
    {
        $xp    = $this->dom($hamXml);
        $kok   = $xp->document->documentElement;
        $kokAd = $kok->localName;

        if (!in_array($kokAd, EBelgeGuvenlik::IZINLI_KOK_ELEMANLAR, true)) {
            throw new RuntimeException('Desteklenmeyen kök eleman: ' . $kokAd);
        }

        $uyarilar  = [];
        $belgeTipi = $this->belgeTipiBelirle($xp, $kok, $kokAd);

        $baslik         = $this->basligiOku($xp, $kok, $kokAd, $uyarilar);
        $taraflar       = $this->taraflariOku($xp, $kok, $kokAd);
        $kalemler       = $this->kalemleriOku($xp, $kok, $kokAd);
        $belgeVergileri = $this->vergiSatirlariniOku($xp, $kok);

        $baslik['gonderen_vkn_tckn'] = (string)($taraflar['gonderen']['vkn_tckn'] ?? '');
        $baslik['alici_vkn_tckn']    = (string)($taraflar['alici']['vkn_tckn'] ?? '');

        // e-İrsaliyede tutar yoktur; toplamlar 0 kalır ve bu bir hata değildir.
        if ($belgeTipi !== 'eirsaliye') {
            $uyarilar = array_merge($uyarilar, $this->tutarlariDogrula($baslik, $kalemler));
        }

        if (empty($kalemler)) {
            $uyarilar[] = 'Belgede hiç kalem satırı bulunamadı.';
        }
        if (($baslik['gonderen_vkn_tckn'] ?? '') === '') {
            $uyarilar[] = 'Gönderen VKN/TCKN okunamadı — cari eşleştirmesi elle yapılmalıdır.';
        }

        // Gömülü görüntü (PDF/HTML) bilinçli olarak OKUNMAZ: dosyanın büyük
        // kısmını oluşturur ve ham XML zaten diskte saklanır.
        $gomulu = $xp->query('.//cac:Attachment/cbc:EmbeddedDocumentBinaryObject', $kok);
        if ($gomulu !== false && $gomulu->length > 0) {
            $uyarilar[] = 'Belgede gömülü görüntü (PDF/HTML) var; içeriğe alınmadı, ham XML saklandı.';
        }

        return [
            'kok'             => $kokAd,
            'belge_tipi'      => $belgeTipi,
            'baslik'          => $baslik,
            'taraflar'        => $taraflar,
            'kalemler'        => $kalemler,
            'belge_vergileri' => $belgeVergileri,
            'uyarilar'        => $uyarilar,
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // Yükleme
    // ─────────────────────────────────────────────────────────────────

    /** @throws RuntimeException */
    private function dom(string $hamXml): DOMXPath
    {
        $onceki = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;

        // LIBXML_NOENT / LIBXML_DTDLOAD / LIBXML_HUGE BİLİNÇLİ OLARAK YOK.
        $ok = $dom->loadXML($hamXml, LIBXML_NONET | LIBXML_NOCDATA | LIBXML_COMPACT);

        $hatalar = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($onceki);

        if (!$ok || $dom->documentElement === null) {
            $ilk = $hatalar[0] ?? null;
            $detay = $ilk instanceof LibXMLError
                ? sprintf(' (satır %d: %s)', $ilk->line, trim($ilk->message))
                : '';
            throw new RuntimeException('XML ayrıştırılamadı' . $detay);
        }

        $xp = new DOMXPath($dom);
        $xp->registerNamespace('inv', self::NS_INVOICE);
        $xp->registerNamespace('dsp', self::NS_DESPATCH);
        $xp->registerNamespace('cac', self::NS_CAC);
        $xp->registerNamespace('cbc', self::NS_CBC);
        $xp->registerNamespace('ext', self::NS_EXT);

        return $xp;
    }

    private function belgeTipiBelirle(DOMXPath $xp, DOMNode $kok, string $kokAd): string
    {
        if ($kokAd === 'DespatchAdvice') {
            return 'eirsaliye';
        }
        $profil = mb_strtoupper((string)self::metin($xp, 'cbc:ProfileID', $kok), 'UTF-8');
        return $profil === 'EARSIVFATURA' ? 'earsiv' : 'efatura';
    }

    // ─────────────────────────────────────────────────────────────────
    // Başlık
    // ─────────────────────────────────────────────────────────────────

    private function basligiOku(DOMXPath $xp, DOMNode $kok, string $kokAd, array &$uyarilar): array
    {
        $uuid = trim((string)self::metin($xp, 'cbc:UUID', $kok));
        if ($uuid === '') {
            // ETTN birincil idempotency anahtarıdır; onsuz aynı belge iki kez
            // içeri alınabilir. Bu yüzden belge KAYDEDİLMEZ, dosya "hatalı"
            // olarak saklanır ve kullanıcıya bildirilir.
            throw new RuntimeException('Belgede ETTN (cbc:UUID) yok — mükerrer kayıt koruması kurulamayacağı için reddedildi.');
        }

        $no = trim((string)self::metin($xp, 'cbc:ID', $kok));
        if ($no === '') {
            throw new RuntimeException('Belgede belge numarası (cbc:ID) yok.');
        }

        $tarih = self::tarih($xp, 'cbc:IssueDate', $kok);
        if ($tarih === null) {
            throw new RuntimeException('Belgede geçerli bir düzenleme tarihi (cbc:IssueDate) yok.');
        }

        $paraBirimi = mb_strtoupper(trim((string)self::metin($xp, 'cbc:DocumentCurrencyCode', $kok)), 'UTF-8');
        if ($paraBirimi === '') {
            $paraBirimi = 'TRY';
        }
        if (in_array($paraBirimi, ['TL', 'TRL'], true)) {
            $paraBirimi = 'TRY';
        }

        $kur = self::tutar($xp, 'cac:PricingExchangeRate/cbc:CalculationRate', $kok);
        if ($kur === null) {
            $kur = self::tutar($xp, 'cac:PaymentExchangeRate/cbc:CalculationRate', $kok);
        }
        if ($paraBirimi !== 'TRY' && ($kur === null || $kur <= 0)) {
            $uyarilar[] = 'Belge dövizli (' . $paraBirimi . ') ancak XML içinde kur bilgisi yok — aktarımdan önce kur girilmelidir.';
        }

        $notlar = [];
        $notDugumleri = $xp->query('cbc:Note', $kok);
        if ($notDugumleri !== false) {
            foreach ($notDugumleri as $n) {
                $metin = trim((string)$n->nodeValue);
                if ($metin !== '') {
                    $notlar[] = $metin;
                }
            }
        }

        $vade = self::tarih($xp, 'cac:PaymentTerms/cbc:PaymentDueDate', $kok)
            ?? self::tarih($xp, 'cac:PaymentMeans/cbc:PaymentDueDate', $kok);

        $baslik = [
            'belge_uuid'       => mb_substr($uuid, 0, 64),
            'belge_no'         => mb_substr($no, 0, 64),
            'belge_tarihi'     => $tarih,
            'belge_saati'      => self::saat($xp, 'cbc:IssueTime', $kok),
            'profil_id'        => self::kirp(self::metin($xp, 'cbc:ProfileID', $kok), 40),
            'fatura_tipi_kodu' => $kokAd === 'Invoice'
                ? self::kirp(self::metin($xp, 'cbc:InvoiceTypeCode', $kok), 30)
                : self::kirp(self::metin($xp, 'cbc:DespatchAdviceTypeCode', $kok), 30),
            'vade_tarihi'      => $vade,
            'para_birimi'      => mb_substr($paraBirimi, 0, 5),
            'kur'              => $kur,
            'not_metni'        => $notlar ? implode("\n", $notlar) : null,
        ];

        // Tutarlar yalnızca fatura belgelerinde vardır; e-İrsaliyede 0 kalır.
        $baslik['satir_toplami']    = self::tutar($xp, 'cac:LegalMonetaryTotal/cbc:LineExtensionAmount', $kok) ?? 0.0;
        $baslik['iskonto_toplami']  = self::tutar($xp, 'cac:LegalMonetaryTotal/cbc:AllowanceTotalAmount', $kok) ?? 0.0;
        $baslik['matrah_toplami']   = self::tutar($xp, 'cac:LegalMonetaryTotal/cbc:TaxExclusiveAmount', $kok) ?? 0.0;
        $baslik['genel_toplam']     = self::tutar($xp, 'cac:LegalMonetaryTotal/cbc:TaxInclusiveAmount', $kok) ?? 0.0;
        $baslik['odenecek_tutar']   = self::tutar($xp, 'cac:LegalMonetaryTotal/cbc:PayableAmount', $kok) ?? $baslik['genel_toplam'];
        $baslik['vergi_toplami']    = self::tutar($xp, 'cac:TaxTotal/cbc:TaxAmount', $kok) ?? 0.0;
        $baslik['tevkifat_toplami'] = self::tutar($xp, 'cac:WithholdingTaxTotal/cbc:TaxAmount', $kok) ?? 0.0;

        return $baslik;
    }

    // ─────────────────────────────────────────────────────────────────
    // Taraflar
    // ─────────────────────────────────────────────────────────────────

    private function taraflariOku(DOMXPath $xp, DOMNode $kok, string $kokAd): array
    {
        if ($kokAd === 'DespatchAdvice') {
            $gonderenYol = 'cac:DespatchSupplierParty/cac:Party';
            $aliciYol    = 'cac:DeliveryCustomerParty/cac:Party';
        } else {
            $gonderenYol = 'cac:AccountingSupplierParty/cac:Party';
            $aliciYol    = 'cac:AccountingCustomerParty/cac:Party';
        }

        return [
            'gonderen' => $this->tarafOku($xp, $kok, $gonderenYol),
            'alici'    => $this->tarafOku($xp, $kok, $aliciYol),
        ];
    }

    private function tarafOku(DOMXPath $xp, DOMNode $kok, string $yol): array
    {
        $dugumler = $xp->query($yol, $kok);
        $party = ($dugumler !== false && $dugumler->length > 0) ? $dugumler->item(0) : null;
        if ($party === null) {
            return ['vkn_tckn' => '', 'kimlik_semasi' => null, 'unvan' => null];
        }

        // VKN / TCKN: schemeID niteliğine göre okunur. Bazı üreticiler schemeID
        // yazmaz; o durumda 10 hane = VKN, 11 hane = TCKN kabul edilir.
        $vkn  = trim((string)self::metin($xp, 'cac:PartyIdentification/cbc:ID[@schemeID="VKN"]', $party));
        $sema = $vkn !== '' ? 'VKN' : null;

        if ($vkn === '') {
            $vkn  = trim((string)self::metin($xp, 'cac:PartyIdentification/cbc:ID[@schemeID="TCKN"]', $party));
            $sema = $vkn !== '' ? 'TCKN' : null;
        }
        if ($vkn === '') {
            $aday = trim((string)self::metin($xp, 'cac:PartyIdentification/cbc:ID', $party));
            if (preg_match('/^\d{10}$/', $aday) === 1) {
                $vkn  = $aday;
                $sema = 'VKN';
            } elseif (preg_match('/^\d{11}$/', $aday) === 1) {
                $vkn  = $aday;
                $sema = 'TCKN';
            }
        }

        $unvan = self::metin($xp, 'cac:PartyName/cbc:Name', $party);
        $ad    = self::metin($xp, 'cac:Person/cbc:FirstName', $party);
        $soyad = self::metin($xp, 'cac:Person/cbc:FamilyName', $party);
        if ($unvan === null && ($ad !== null || $soyad !== null)) {
            $unvan = trim(((string)$ad) . ' ' . ((string)$soyad));
        }

        $adresParcalari = array_filter([
            self::metin($xp, 'cac:PostalAddress/cbc:StreetName', $party),
            self::metin($xp, 'cac:PostalAddress/cbc:BuildingNumber', $party),
            self::metin($xp, 'cac:PostalAddress/cbc:BuildingName', $party),
        ]);

        return [
            'vkn_tckn'      => mb_substr($vkn, 0, 11),
            'kimlik_semasi' => $sema,
            'unvan'         => self::kirp($unvan, 255),
            'ad'            => self::kirp($ad, 120),
            'soyad'         => self::kirp($soyad, 120),
            'vergi_dairesi' => self::kirp(self::metin($xp, 'cac:PartyTaxScheme/cac:TaxScheme/cbc:Name', $party), 150),
            'mersis_no'     => self::kirp(self::metin($xp, 'cac:PartyIdentification/cbc:ID[@schemeID="MERSISNO"]', $party), 30),
            'ticaret_sicil' => self::kirp(self::metin($xp, 'cac:PartyIdentification/cbc:ID[@schemeID="TICARETSICILNO"]', $party), 30),
            'adres'         => $adresParcalari ? implode(' ', $adresParcalari) : null,
            'ilce'          => self::kirp(self::metin($xp, 'cac:PostalAddress/cbc:CitySubdivisionName', $party), 100),
            'il'            => self::kirp(self::metin($xp, 'cac:PostalAddress/cbc:CityName', $party), 100),
            'ulke'          => self::kirp(self::metin($xp, 'cac:PostalAddress/cac:Country/cbc:Name', $party), 80),
            'posta_kodu'    => self::kirp(self::metin($xp, 'cac:PostalAddress/cbc:PostalZone', $party), 20),
            'telefon'       => self::kirp(self::metin($xp, 'cac:Contact/cbc:Telephone', $party), 50),
            'eposta'        => self::kirp(self::metin($xp, 'cac:Contact/cbc:ElectronicMail', $party), 150),
            'web'           => self::kirp(self::metin($xp, 'cbc:WebsiteURI', $party), 150),
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // Kalemler
    // ─────────────────────────────────────────────────────────────────

    private function kalemleriOku(DOMXPath $xp, DOMNode $kok, string $kokAd): array
    {
        $irsaliyeMi = $kokAd === 'DespatchAdvice';
        $satirYolu  = $irsaliyeMi ? 'cac:DespatchLine' : 'cac:InvoiceLine';
        $miktarYolu = $irsaliyeMi ? 'cbc:DeliveredQuantity' : 'cbc:InvoicedQuantity';

        $satirlar = $xp->query($satirYolu, $kok);
        if ($satirlar === false || $satirlar->length === 0) {
            return [];
        }
        if ($satirlar->length > self::MAX_KALEM) {
            throw new RuntimeException(sprintf(
                'Belge çok fazla kalem içeriyor (azami %d, bulunan %d).',
                self::MAX_KALEM,
                $satirlar->length
            ));
        }

        $kalemler = [];
        $kullanilanSiralar = [];
        $sira = 0;
        foreach ($satirlar as $satir) {
            $sira++;
            $siraNo = (int)(self::metin($xp, 'cbc:ID', $satir) ?? $sira);
            // Bazı üreticiler kalem numarasını tekrar edebiliyor ya da boş
            // bırakabiliyor; staging tablosunda (belge_id, sira_no) TEKİL olduğu
            // için çakışma hâlinde sıralı sayaca düşülür — aksi hâlde tek bir
            // bozuk numara yüzünden belgenin tamamı kaydedilemezdi.
            if ($siraNo <= 0 || isset($kullanilanSiralar[$siraNo])) {
                $siraNo = $sira;
            }
            $kullanilanSiralar[$siraNo] = true;

            $kalem = [
                'sira_no'            => $siraNo,
                'urun_adi'           => self::kirp(self::metin($xp, 'cac:Item/cbc:Name', $satir), 255) ?? '',
                'aciklama'           => self::metin($xp, 'cac:Item/cbc:Description', $satir),
                'satici_urun_kodu'   => self::kirp(self::metin($xp, 'cac:Item/cac:SellersItemIdentification/cbc:ID', $satir), 100),
                'alici_urun_kodu'    => self::kirp(self::metin($xp, 'cac:Item/cac:BuyersItemIdentification/cbc:ID', $satir), 100),
                'barkod'             => self::kirp(self::metin($xp, 'cac:Item/cac:StandardItemIdentification/cbc:ID', $satir), 100),
                'gtip'               => self::kirp(self::metin($xp, 'cac:Item/cac:CommodityClassification/cbc:ItemClassificationCode', $satir), 30),
                'miktar'             => self::tutar($xp, $miktarYolu, $satir) ?? 0.0,
                'birim_kodu'         => self::kirp(self::nitelik($xp, $miktarYolu, 'unitCode', $satir), 10),
                'birim_fiyat'        => self::tutar($xp, 'cac:Price/cbc:PriceAmount', $satir) ?? 0.0,
                'satir_tutari'       => self::tutar($xp, 'cbc:LineExtensionAmount', $satir) ?? 0.0,
                'iskonto_tutari'     => 0.0,
                'iskonto_orani'      => 0.0,
                'kdv_orani'          => 0.0,
                'kdv_tutari'         => 0.0,
                'tevkifat_orani'     => 0.0,
                'tevkifat_tutari'    => 0.0,
                'otv_tutari'         => 0.0,
                'oiv_tutari'         => 0.0,
                'diger_vergi_tutari' => 0.0,
                'istisna_kodu'       => null,
                'vergiler'           => [],
            ];

            // ── İskonto (AllowanceCharge, ChargeIndicator=false) ──────────
            $indirimler = $xp->query('cac:AllowanceCharge', $satir);
            if ($indirimler !== false) {
                foreach ($indirimler as $ind) {
                    $isaret = mb_strtolower(trim((string)self::metin($xp, 'cbc:ChargeIndicator', $ind)), 'UTF-8');
                    if ($isaret !== 'false' && $isaret !== '0') {
                        continue; // masraf (charge), iskonto değil
                    }
                    $kalem['iskonto_tutari'] += self::tutar($xp, 'cbc:Amount', $ind) ?? 0.0;
                    $carpan = self::tutar($xp, 'cbc:MultiplierFactorNumeric', $ind);
                    if ($carpan !== null && $carpan > 0) {
                        $kalem['iskonto_orani'] = round($carpan * 100, 4);
                    }
                }
            }
            // Oran verilmemişse taban tutardan hesapla: çekirdek fatura sistemi
            // (fatura_kalemleri) iskontoyu ORAN olarak tutar, tutar olarak değil.
            if ($kalem['iskonto_orani'] <= 0 && $kalem['iskonto_tutari'] > 0) {
                $taban = $kalem['satir_tutari'] + $kalem['iskonto_tutari'];
                if ($taban > 0) {
                    $kalem['iskonto_orani'] = round(($kalem['iskonto_tutari'] / $taban) * 100, 4);
                }
            }

            // ── Vergiler ──────────────────────────────────────────────────
            foreach ($this->vergiSatirlariniOku($xp, $satir) as $vergi) {
                $kalem['vergiler'][] = $vergi;
                $kod = (string)($vergi['vergi_kodu'] ?? '');
                $ad  = mb_strtoupper((string)($vergi['vergi_adi'] ?? ''), 'UTF-8');

                if ($kod === self::VERGI_TEVKIFAT || str_contains($ad, 'TEVKIFAT') || str_contains($ad, 'TEVKİFAT')) {
                    $kalem['tevkifat_orani']   = (float)$vergi['oran'];
                    $kalem['tevkifat_tutari'] += (float)$vergi['tutar'];
                } elseif ($kod === self::VERGI_KDV || str_contains($ad, 'KDV') || str_contains($ad, 'KATMA')) {
                    $kalem['kdv_orani']   = (float)$vergi['oran'];
                    $kalem['kdv_tutari'] += (float)$vergi['tutar'];
                    if (!empty($vergi['istisna_kodu'])) {
                        $kalem['istisna_kodu'] = $vergi['istisna_kodu'];
                    }
                } elseif (str_contains($ad, 'ÖTV') || str_contains($ad, 'OTV')) {
                    $kalem['otv_tutari'] += (float)$vergi['tutar'];
                } elseif (str_contains($ad, 'ÖİV') || str_contains($ad, 'OIV')) {
                    $kalem['oiv_tutari'] += (float)$vergi['tutar'];
                } else {
                    $kalem['diger_vergi_tutari'] += (float)$vergi['tutar'];
                }
            }

            $kalemler[] = $kalem;
        }

        return $kalemler;
    }

    /**
     * Bir bağlam düğümünün (belge veya kalem) altındaki TaxSubtotal satırlarını okur.
     * Vergi kodu/adı HER ZAMAN ham hâliyle döner — hiçbir vergi bilgisi kaybolmaz.
     */
    private function vergiSatirlariniOku(DOMXPath $xp, DOMNode $ctx): array
    {
        $sonuc = [];
        $altToplamlar = $xp->query('cac:TaxTotal/cac:TaxSubtotal | cac:WithholdingTaxTotal/cac:TaxSubtotal', $ctx);
        if ($altToplamlar === false) {
            return $sonuc;
        }

        foreach ($altToplamlar as $alt) {
            $sonuc[] = [
                'vergi_kodu'       => self::kirp(self::metin($xp, 'cac:TaxCategory/cac:TaxScheme/cbc:TaxTypeCode', $alt), 20),
                'vergi_adi'        => self::kirp(self::metin($xp, 'cac:TaxCategory/cac:TaxScheme/cbc:Name', $alt), 100),
                'matrah'           => self::tutar($xp, 'cbc:TaxableAmount', $alt) ?? 0.0,
                'oran'             => self::tutar($xp, 'cbc:Percent', $alt) ?? 0.0,
                'tutar'            => self::tutar($xp, 'cbc:TaxAmount', $alt) ?? 0.0,
                'istisna_kodu'     => self::kirp(self::metin($xp, 'cac:TaxCategory/cbc:TaxExemptionReasonCode', $alt), 20),
                'istisna_aciklama' => self::kirp(self::metin($xp, 'cac:TaxCategory/cbc:TaxExemptionReason', $alt), 255),
            ];
        }

        return $sonuc;
    }

    // ─────────────────────────────────────────────────────────────────
    // Doğrulama
    // ─────────────────────────────────────────────────────────────────

    /** @return string[] kullanıcıya gösterilecek uyarılar (aktarımı bloke edebilir) */
    private function tutarlariDogrula(array $baslik, array $kalemler): array
    {
        $uyarilar = [];

        $kalemToplami = 0.0;
        foreach ($kalemler as $k) {
            $kalemToplami += (float)$k['satir_tutari'];
        }
        $satirToplami = (float)$baslik['satir_toplami'];
        if ($satirToplami > 0 && abs($kalemToplami - $satirToplami) > self::TOLERANS) {
            $uyarilar[] = sprintf(
                'Kalem tutarları toplamı (%s) belge satır toplamıyla (%s) uyuşmuyor.',
                self::bicimle($kalemToplami),
                self::bicimle($satirToplami)
            );
        }

        $matrah = (float)$baslik['matrah_toplami'];
        $vergi  = (float)$baslik['vergi_toplami'];
        $genel  = (float)$baslik['genel_toplam'];
        if ($genel > 0 && abs(($matrah + $vergi) - $genel) > self::TOLERANS) {
            $uyarilar[] = sprintf(
                'Matrah + vergi (%s) genel toplamla (%s) uyuşmuyor.',
                self::bicimle($matrah + $vergi),
                self::bicimle($genel)
            );
        }

        $odenecek = (float)$baslik['odenecek_tutar'];
        $tevkifat = (float)$baslik['tevkifat_toplami'];
        if ($genel > 0 && $tevkifat <= 0 && abs($odenecek - $genel) > self::TOLERANS) {
            $uyarilar[] = sprintf(
                'Ödenecek tutar (%s) genel toplamdan (%s) farklı.',
                self::bicimle($odenecek),
                self::bicimle($genel)
            );
        }

        if ($tevkifat > 0) {
            $uyarilar[] = sprintf(
                'Bu belgede %s tutarında tevkifat var. Çekirdek fatura sisteminde tevkifat alanı '
                . 'bulunmadığı için aktarımda matrah ve KDV tutarı olarak yansıtılacaktır; '
                . 'tevkifat detayı e-Belge kaydında eksiksiz saklanır.',
                self::bicimle($tevkifat)
            );
        }

        return $uyarilar;
    }

    // ─────────────────────────────────────────────────────────────────
    // Düşük seviye okuyucular (hepsi null-safe)
    // ─────────────────────────────────────────────────────────────────

    private static function metin(DOMXPath $xp, string $yol, ?DOMNode $ctx = null): ?string
    {
        $dugumler = $ctx !== null ? $xp->query($yol, $ctx) : $xp->query($yol);
        if ($dugumler === false || $dugumler->length === 0) {
            return null;
        }
        $deger = trim((string)$dugumler->item(0)->nodeValue);
        return $deger === '' ? null : $deger;
    }

    private static function nitelik(DOMXPath $xp, string $yol, string $ad, ?DOMNode $ctx = null): ?string
    {
        $dugumler = $ctx !== null ? $xp->query($yol, $ctx) : $xp->query($yol);
        if ($dugumler === false || $dugumler->length === 0) {
            return null;
        }
        $dugum = $dugumler->item(0);
        if (!$dugum instanceof DOMElement || !$dugum->hasAttribute($ad)) {
            return null;
        }
        $deger = trim($dugum->getAttribute($ad));
        return $deger === '' ? null : $deger;
    }

    private static function tutar(DOMXPath $xp, string $yol, ?DOMNode $ctx = null): ?float
    {
        $ham = self::metin($xp, $yol, $ctx);
        return $ham === null ? null : self::sayiyaCevir($ham);
    }

    /**
     * UBL tutarları nokta ondalıklıdır; yine de virgül ondalık kullanan
     * üreticilere karşı toleranslı davranılır. Sayı olmayan değer null döner
     * (sessizce 0 kabul edilmez — 0 ile "bilinmiyor" farklı şeylerdir).
     */
    public static function sayiyaCevir(string $ham): ?float
    {
        $temiz = str_replace(["\xc2\xa0", ' ', "\t"], '', trim($ham));
        if ($temiz === '') {
            return null;
        }
        if (str_contains($temiz, ',') && !str_contains($temiz, '.')) {
            $temiz = str_replace(',', '.', $temiz);
        } elseif (str_contains($temiz, ',') && str_contains($temiz, '.')) {
            $temiz = str_replace(',', '', $temiz);
        }
        if (!is_numeric($temiz)) {
            return null;
        }
        $sayi = (float)$temiz;
        return is_finite($sayi) ? $sayi : null;
    }

    private static function tarih(DOMXPath $xp, string $yol, ?DOMNode $ctx = null): ?string
    {
        $ham = self::metin($xp, $yol, $ctx);
        if ($ham === null) {
            return null;
        }
        $ham = substr($ham, 0, 10);
        $dt = DateTime::createFromFormat('Y-m-d', $ham);
        if (!$dt instanceof DateTime || $dt->format('Y-m-d') !== $ham) {
            return null;
        }
        return $ham;
    }

    private static function saat(DOMXPath $xp, string $yol, ?DOMNode $ctx = null): ?string
    {
        $ham = self::metin($xp, $yol, $ctx);
        if ($ham === null) {
            return null;
        }
        if (preg_match('/^(\d{2}):(\d{2})(?::(\d{2}))?/', $ham, $m) !== 1) {
            return null;
        }
        return sprintf('%02d:%02d:%02d', (int)$m[1], (int)$m[2], (int)($m[3] ?? 0));
    }

    private static function kirp(?string $deger, int $uzunluk): ?string
    {
        if ($deger === null) {
            return null;
        }
        $deger = trim($deger);
        return $deger === '' ? null : mb_substr($deger, 0, $uzunluk);
    }

    private static function bicimle(float $tutar): string
    {
        return number_format($tutar, 2, ',', '.');
    }
}
