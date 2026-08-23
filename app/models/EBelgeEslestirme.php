<?php
/**
 * EBelgeEslestirme — e-Belge Cari / Ürün Eşleştirme Katmanı (FAZ 2)
 * --------------------------------------------------------
 * Staging'deki bir e-Belgenin gönderenini sistemdeki CARİ kaydına, kalemlerini
 * de ÜRÜN/HİZMET kayıtlarına bağlar. Aktarım (Faz 3) ancak bu bağlar kurulduktan
 * sonra yapılabilir.
 *
 * ── EN ÖNEMLİ KURAL ────────────────────────────────────────────────────
 * OTOMATİK eşleşme YALNIZCA kimlik niteliğindeki alanlarla yapılır:
 *     cari  : VKN / TCKN tam eşleşme (tek sonuç) veya öğrenilmiş eşleşme
 *     ürün  : barkod / alıcı ürün kodu tam eşleşme (tek sonuç) veya öğrenilmiş eşleşme
 * UNVAN veya ÜRÜN ADI BENZERLİĞİ ASLA OTOMATİK EŞLEŞTİRME ÜRETMEZ.
 * Benzerlik yalnızca kullanıcıya ADAY SIRALAMAK için kullanılır; kararı insan verir.
 *
 * Gerekçe: yanlış cari eşleşmesi yanlış cariye borç yazar ve
 * Fatura::recomputeCariBalance() bunu kalıcı hâle getirir; yanlış ürün eşleşmesi
 * stok miktarını bozar ve geçmişe dönük maliyet/kâr raporlarını yanlışlar.
 * Bu hatalar sessizdir ve geri alınması pahalıdır.
 *
 * ── ÇEKİRDEĞE DOKUNMA BİÇİMİ ───────────────────────────────────────────
 * cariler ve urunler_hizmetler tablolarına DOĞRUDAN yazma YOKTUR. Yeni kayıtlar
 * yalnızca mevcut Cari::ekle() ve Urun::ekle() metotlarıyla açılır — böylece
 * $fillable süzgeci, UNIQUE kısıtları ve Audit kaydı devrede kalır.
 *
 * DİKKAT: Cari modelinin kurucusu ALTER TABLE çalıştırır ve (Fatura/Urun'ün
 * aksine) inTransaction() koruması YOKTUR. DDL MySQL'de örtük commit yaptığı
 * için bu modeller HER ZAMAN transaction AÇILMADAN ÖNCE örneklenir.
 */
final class EBelgeEslestirme
{
    /** Panelde kullanılan birim sözlüğü (bkz. app/views/urunler/ekle.php). */
    public const BIRIM_LISTESI = ['Adet', 'Kg', 'Litre', 'Metre', 'Paket', 'Kutu', 'Ton', 'Hizmet'];

    /**
     * UN/ECE Rec.20 birim kodu → panel birimi.
     * BİLİNÇLİ OLARAK DAR TUTULDU: yalnızca birebir karşılığı olan kodlar
     * eşlenir. GRM (gram) → Kg gibi ÇEVRİM GEREKTİREN kodlar KASTEN dışarıda
     * bırakılmıştır; onlarda birim ve çarpan kararını kullanıcı verir, aksi
     * hâlde 1000 gramlık bir kalem 1000 Kg olarak stoğa girer.
     */
    public const UNECE_BIRIM = [
        'C62' => 'Adet', 'NIU' => 'Adet', 'PCE' => 'Adet', 'EA' => 'Adet', 'H87' => 'Adet',
        'KGM' => 'Kg',
        'LTR' => 'Litre',
        'MTR' => 'Metre',
        'TNE' => 'Ton',
        'PA'  => 'Paket', 'XPK' => 'Paket', 'PK' => 'Paket',
        'BX'  => 'Kutu', 'XBX' => 'Kutu', 'CT' => 'Kutu', 'XCT' => 'Kutu',
    ];

    /** Şirket unvanlarında bilgi taşımayan, benzerlik hesabından çıkarılan ekler. */
    private const UNVAN_EKLERI = [
        'ltd', 'sti', 'as', 'anonim', 'sirketi', 'sirket', 'limited', 'ticaret',
        'tic', 'sanayi', 'san', 've', 'ith', 'ihr', 'ithalat', 'ihracat', 'kollektif', 'komandit',
    ];

    public const ESLESME_OTOMATIK_VKN   = 'vkn_otomatik';
    public const ESLESME_OGRENILMIS     = 'ogrenilmis';
    public const ESLESME_MANUEL         = 'manuel';
    public const ESLESME_YENI           = 'yeni_olusturuldu';
    public const ESLESME_BARKOD         = 'barkod';
    public const ESLESME_ALICI_KODU     = 'alici_kodu';
    public const ESLESME_URUNSUZ        = 'urunsuz';

    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ═════════════════════════════════════════════════════════════════
    // SAF YARDIMCILAR — veritabanı gerektirmez, CI'da doğrudan test edilir
    // ═════════════════════════════════════════════════════════════════

    /** UN/ECE birim kodunu panel birimine çevirir; karşılığı yoksa null. */
    public static function birimCozumle(?string $uneceKodu): ?string
    {
        if ($uneceKodu === null) {
            return null;
        }
        $kod = strtoupper(trim($uneceKodu));
        return self::UNECE_BIRIM[$kod] ?? null;
    }

    /**
     * XML'deki birim ile sistemdeki ürünün birimi çelişiyor mu?
     * Çözülemeyen kod da "uyumsuz" sayılır: bilinmeyen bir birimi sessizce
     * kabul etmek miktar hatasının en kolay yoludur.
     */
    public static function birimUyumsuzMu(?string $xmlBirimKodu, ?string $sistemBirimi): bool
    {
        $sistem = trim((string)$sistemBirimi);
        if ($sistem === '') {
            return true;
        }
        $cozulen = self::birimCozumle($xmlBirimKodu);
        if ($cozulen === null) {
            return true;
        }
        return mb_strtolower($cozulen, 'UTF-8') !== mb_strtolower($sistem, 'UTF-8');
    }

    /** VKN (10 hane) veya TCKN (11 hane) biçimsel geçerlilik. */
    public static function kimlikGecerliMi(?string $vknTckn): bool
    {
        $deger = trim((string)$vknTckn);
        return preg_match('/^\d{10}$/', $deger) === 1 || preg_match('/^\d{11}$/', $deger) === 1;
    }

    /**
     * Unvan/ürün adını karşılaştırma için sadeleştirir.
     * SADECE aday sıralamada kullanılır — otomatik eşleştirmede ASLA.
     */
    public static function adNormalize(string $ad): string
    {
        // Türkçe büyük harfler ÖNCE eşlenir: mb_strtolower('İ') birleşik nokta
        // (U+0307) üretir ve sonraki temizlikte kelimeyi ikiye böler
        // ("TİCARET" → "ti caret"). Bu, benzerlik puanını sessizce bozar.
        $deger = str_replace(
            ['İ', 'I', 'Ş', 'Ğ', 'Ü', 'Ö', 'Ç', 'Â', 'Î', 'Û'],
            ['i', 'i', 's', 'g', 'u', 'o', 'c', 'a', 'i', 'u'],
            trim($ad)
        );
        $deger = mb_strtolower($deger, 'UTF-8');
        $deger = str_replace(['ı', 'ğ', 'ü', 'ş', 'ö', 'ç', 'â', 'î', 'û'], ['i', 'g', 'u', 's', 'o', 'c', 'a', 'i', 'u'], $deger);
        $deger = preg_replace('/[^a-z0-9 ]/', ' ', $deger) ?? '';

        // Tek harflik parçalar ("A.Ş." → "a", "s") kimlik taşımaz; şirket ekleri
        // gibi elenir, aksi hâlde aynı firmanın iki yazımı farklı görünür.
        $parcalar = array_filter(
            preg_split('/\s+/', $deger) ?: [],
            fn($p) => mb_strlen($p) > 1 && !in_array($p, self::UNVAN_EKLERI, true)
        );
        return implode(' ', $parcalar);
    }

    /**
     * 0–100 arası benzerlik puanı (aday SIRALAMA amaçlı).
     * Yüksek puan bile otomatik eşleşme ANLAMINA GELMEZ.
     */
    public static function benzerlikPuani(string $a, string $b): float
    {
        $x = self::adNormalize($a);
        $y = self::adNormalize($b);
        if ($x === '' || $y === '') {
            return 0.0;
        }
        if ($x === $y) {
            return 100.0;
        }

        $yuzde = 0.0;
        similar_text($x, $y, $yuzde);

        // Ortak kelime oranı, harf benzerliğini tek başına bırakmamak için katkı verir.
        $xParcalar = array_unique(explode(' ', $x));
        $yParcalar = array_unique(explode(' ', $y));
        $ortak = count(array_intersect($xParcalar, $yParcalar));
        $enAz = max(1, min(count($xParcalar), count($yParcalar)));
        $kelimeOrani = ($ortak / $enAz) * 100;

        return round(($yuzde * 0.6) + ($kelimeOrani * 0.4), 2);
    }

    // ═════════════════════════════════════════════════════════════════
    // OTOMATİK EŞLEŞTİRME
    // ═════════════════════════════════════════════════════════════════

    /**
     * Belgenin cari ve kalem eşleşmelerini KİMLİK ALANLARIYLA kurmayı dener.
     * İsim benzerliği KULLANILMAZ.
     *
     * @return array{cari:string, kalem_eslesen:int, kalem_toplam:int}
     */
    public function otomatikEslestir(int $belgeId, ?int $userId = null): array
    {
        $belge = $this->belgeGetir($belgeId);
        if (!$belge) {
            throw new RuntimeException('e-Belge bulunamadı.');
        }

        $sonuc = ['cari' => 'degismedi', 'kalem_eslesen' => 0, 'kalem_toplam' => 0];

        // ── 1) CARİ ──────────────────────────────────────────────────
        if (empty($belge['eslesen_cari_id'])) {
            $bulunan = $this->cariOtomatikBul((string)$belge['gonderen_vkn_tckn']);
            if ($bulunan !== null) {
                $this->db->update('e_belgeler', [
                    'eslesen_cari_id'   => $bulunan['cari_id'],
                    'cari_eslesme_tipi' => $bulunan['tip'],
                ], ['id' => $belgeId]);
                $belge['eslesen_cari_id'] = $bulunan['cari_id'];
                $sonuc['cari'] = $bulunan['tip'];
            }
        }

        // ── 2) KALEMLER ──────────────────────────────────────────────
        $kalemler = $this->kalemleriGetir($belgeId);
        $sonuc['kalem_toplam'] = count($kalemler);
        $tedarikciId = (int)($belge['eslesen_cari_id'] ?? 0);

        foreach ($kalemler as $kalem) {
            if (!empty($kalem['eslesen_urun_id']) || ($kalem['urun_eslesme_tipi'] ?? '') === self::ESLESME_URUNSUZ) {
                continue;
            }
            $bulunan = $this->urunOtomatikBul($kalem, $tedarikciId);
            if ($bulunan === null) {
                continue;
            }

            $urun = $this->urunGetir((int)$bulunan['urun_id']);
            if (!$urun) {
                continue;
            }

            // Birim yalnızca ÇELİŞMİYORSA otomatik onaylanır; çelişiyorsa
            // hedef_birim NULL bırakılır ve kullanıcı kararı beklenir.
            $uyumsuz = self::birimUyumsuzMu($kalem['birim_kodu'] ?? null, $urun['birim'] ?? null);

            $this->db->update('e_belge_kalemleri', [
                'eslesen_urun_id'   => (int)$bulunan['urun_id'],
                'urun_eslesme_tipi' => $bulunan['tip'],
                'hedef_birim'       => $uyumsuz ? null : $urun['birim'],
                'birim_carpani'     => !empty($bulunan['birim_carpani']) ? (float)$bulunan['birim_carpani'] : 1,
            ], ['id' => (int)$kalem['id']]);

            $sonuc['kalem_eslesen']++;
        }

        $this->durumTazele($belgeId);

        Audit::log('UPDATE', 'EBELGE', $belgeId, null, $sonuc,
            'e-Belge otomatik eşleştirme çalıştırıldı.', true, $userId);

        return $sonuc;
    }

    /**
     * VKN/TCKN ile cari bulur. YALNIZCA TEK sonuç dönerse eşleşme kabul edilir;
     * aynı vergi numarasına sahip birden çok cari varsa karar kullanıcıya bırakılır.
     *
     * @return array{cari_id:int, tip:string}|null
     */
    private function cariOtomatikBul(string $vknTckn): ?array
    {
        if (!self::kimlikGecerliMi($vknTckn)) {
            return null;
        }
        $companyId = TenantContext::activeCompanyId();

        // a) Daha önce kullanıcının onayladığı eşleşme (öğrenilmiş)
        $ogrenilmis = $this->db->selectOne(
            "SELECT e.cari_id
               FROM e_belge_cari_eslesme e
               JOIN cariler c ON c.id = e.cari_id AND c.company_id = e.company_id AND c.silindi_mi = 0
              WHERE e.company_id = :cid AND e.vkn_tckn = :vkn AND e.silindi_mi = 0
              LIMIT 1",
            [':cid' => $companyId, ':vkn' => $vknTckn]
        );
        if ($ogrenilmis) {
            $this->ogrenmeKullanimiIsle('e_belge_cari_eslesme', ['company_id' => $companyId, 'vkn_tckn' => $vknTckn]);
            return ['cari_id' => (int)$ogrenilmis['cari_id'], 'tip' => self::ESLESME_OGRENILMIS];
        }

        // b) VKN/TCKN tam eşleşme — SADECE tek sonuç kabul edilir.
        $kolon = strlen($vknTckn) === 11 ? 'tc_kimlik_no' : 'vergi_no';
        $adaylar = $this->db->select(
            "SELECT id FROM cariler
              WHERE company_id = :cid AND silindi_mi = 0
                AND {$kolon} = :vkn
                AND tip IN ('tedarikci', 'her_ikisi')
              LIMIT 2",
            [':cid' => $companyId, ':vkn' => $vknTckn]
        );
        if (count($adaylar) === 1) {
            return ['cari_id' => (int)$adaylar[0]['id'], 'tip' => self::ESLESME_OTOMATIK_VKN];
        }

        return null;
    }

    /**
     * Kalemi ürün kartıyla eşler. Sıra: öğrenilmiş → barkod → alıcı ürün kodu.
     * ÜRÜN ADI KULLANILMAZ. Her adımda yalnızca TEK sonuç kabul edilir.
     *
     * @return array{urun_id:int, tip:string, birim_carpani:float}|null
     */
    private function urunOtomatikBul(array $kalem, int $tedarikciId): ?array
    {
        $companyId = TenantContext::activeCompanyId();

        // a) Öğrenilmiş eşleşmeler
        $anahtarlar = [];
        if (!empty($kalem['satici_urun_kodu']) && $tedarikciId > 0) {
            $anahtarlar[] = ['tip' => 'satici_kodu', 'kod' => (string)$kalem['satici_urun_kodu'], 'cari' => $tedarikciId];
        }
        if (!empty($kalem['barkod'])) {
            $anahtarlar[] = ['tip' => 'barkod', 'kod' => (string)$kalem['barkod'], 'cari' => 0];
        }
        if (!empty($kalem['alici_urun_kodu'])) {
            $anahtarlar[] = ['tip' => 'alici_kodu', 'kod' => (string)$kalem['alici_urun_kodu'], 'cari' => 0];
        }

        foreach ($anahtarlar as $anahtar) {
            $satir = $this->db->selectOne(
                "SELECT urun_id, birim_carpani FROM e_belge_urun_eslesme
                  WHERE company_id = :cid AND tedarikci_cari_id = :tid
                    AND kaynak_kod_tipi = :tip AND kaynak_kod = :kod AND silindi_mi = 0
                  LIMIT 1",
                [':cid' => $companyId, ':tid' => $anahtar['cari'], ':tip' => $anahtar['tip'], ':kod' => $anahtar['kod']]
            );
            if ($satir && !empty($satir['urun_id'])) {
                return [
                    'urun_id'       => (int)$satir['urun_id'],
                    'tip'           => self::ESLESME_OGRENILMIS,
                    'birim_carpani' => (float)$satir['birim_carpani'],
                ];
            }
        }

        // b) Barkod tam eşleşme
        if (!empty($kalem['barkod'])) {
            $adaylar = $this->db->select(
                "SELECT id FROM urunler_hizmetler
                  WHERE company_id = :cid AND silindi_mi = 0 AND barkod = :kod LIMIT 2",
                [':cid' => $companyId, ':kod' => $kalem['barkod']]
            );
            if (count($adaylar) === 1) {
                return ['urun_id' => (int)$adaylar[0]['id'], 'tip' => self::ESLESME_BARKOD, 'birim_carpani' => 1.0];
            }
        }

        // c) Alıcı ürün kodu (tedarikçinin bizim koda yazdığı değer) = stok kodu
        if (!empty($kalem['alici_urun_kodu'])) {
            $adaylar = $this->db->select(
                "SELECT id FROM urunler_hizmetler
                  WHERE company_id = :cid AND silindi_mi = 0 AND stok_kodu = :kod LIMIT 2",
                [':cid' => $companyId, ':kod' => $kalem['alici_urun_kodu']]
            );
            if (count($adaylar) === 1) {
                return ['urun_id' => (int)$adaylar[0]['id'], 'tip' => self::ESLESME_ALICI_KODU, 'birim_carpani' => 1.0];
            }
        }

        return null;
    }

    // ═════════════════════════════════════════════════════════════════
    // ADAY ÖNERİLERİ (yalnızca gösterim — otomatik eşleşme DEĞİL)
    // ═════════════════════════════════════════════════════════════════

    /**
     * Kullanıcıya gösterilecek cari adayları, benzerliğe göre sıralı.
     * DİKKAT: buradaki puan hiçbir zaman otomatik eşleştirme için kullanılmaz.
     */
    public function cariAdaylari(array $belge, string $arama = '', int $limit = 10): array
    {
        $companyId = TenantContext::activeCompanyId();
        $unvan = (string)($belge['gonderen_unvan'] ?? '');

        if (trim($arama) !== '') {
            $like = '%' . trim($arama) . '%';
            $satirlar = $this->db->select(
                "SELECT id, unvan, vergi_no, tc_kimlik_no, vergi_dairesi, tip
                   FROM cariler
                  WHERE company_id = :cid AND silindi_mi = 0
                    AND (unvan LIKE :q1 OR vergi_no LIKE :q2 OR cari_kodu LIKE :q3 OR tc_kimlik_no LIKE :q4)
                  ORDER BY unvan LIMIT :limit",
                [':cid' => $companyId, ':q1' => $like, ':q2' => $like, ':q3' => $like, ':q4' => $like, ':limit' => $limit]
            );
        } else {
            // Arama yoksa: tedarikçi kayıtları arasından unvan benzerliğine göre öner.
            $satirlar = $this->db->select(
                "SELECT id, unvan, vergi_no, tc_kimlik_no, vergi_dairesi, tip
                   FROM cariler
                  WHERE company_id = :cid AND silindi_mi = 0
                    AND tip IN ('tedarikci', 'her_ikisi')
                  LIMIT 500",
                [':cid' => $companyId]
            );
            foreach ($satirlar as &$satir) {
                $satir['benzerlik'] = self::benzerlikPuani($unvan, (string)$satir['unvan']);
            }
            unset($satir);
            usort($satirlar, fn($a, $b) => $b['benzerlik'] <=> $a['benzerlik']);
            $satirlar = array_slice($satirlar, 0, $limit);
        }

        return $satirlar;
    }

    /** Kalem için ürün adayları (arama varsa ona göre, yoksa ad benzerliğine göre). */
    public function urunAdaylari(array $kalem, string $arama = '', int $limit = 10): array
    {
        $companyId = TenantContext::activeCompanyId();

        if (trim($arama) !== '') {
            $like = '%' . trim($arama) . '%';
            return $this->db->select(
                "SELECT id, ad, stok_kodu, barkod, birim, tip, alis_fiyati, kdv_orani
                   FROM urunler_hizmetler
                  WHERE company_id = :cid AND silindi_mi = 0
                    AND (ad LIKE :q1 OR stok_kodu LIKE :q2 OR barkod LIKE :q3)
                  ORDER BY ad LIMIT :limit",
                [':cid' => $companyId, ':q1' => $like, ':q2' => $like, ':q3' => $like, ':limit' => $limit]
            );
        }

        $satirlar = $this->db->select(
            "SELECT id, ad, stok_kodu, barkod, birim, tip, alis_fiyati, kdv_orani
               FROM urunler_hizmetler
              WHERE company_id = :cid AND silindi_mi = 0
              LIMIT 500",
            [':cid' => $companyId]
        );
        $ad = (string)($kalem['urun_adi'] ?? '');
        foreach ($satirlar as &$satir) {
            $satir['benzerlik'] = self::benzerlikPuani($ad, (string)$satir['ad']);
        }
        unset($satir);
        usort($satirlar, fn($a, $b) => $b['benzerlik'] <=> $a['benzerlik']);

        return array_slice($satirlar, 0, $limit);
    }

    // ═════════════════════════════════════════════════════════════════
    // MANUEL EŞLEŞTİRME (kullanıcı kararı)
    // ═════════════════════════════════════════════════════════════════

    /** Belgeyi mevcut bir cariye bağlar; istenirse eşleşmeyi öğrenir. */
    public function cariAta(int $belgeId, int $cariId, bool $ogren, ?int $userId = null): array
    {
        $belge = $this->belgeGetir($belgeId);
        if (!$belge) {
            throw new RuntimeException('e-Belge bulunamadı.');
        }

        $cari = $this->db->selectOne(
            "SELECT id, unvan, tip, vergi_no, tc_kimlik_no FROM cariler
              WHERE id = :id AND company_id = :cid AND silindi_mi = 0",
            [':id' => $cariId, ':cid' => TenantContext::activeCompanyId()]
        );
        if (!$cari) {
            throw new RuntimeException('Seçilen cari bulunamadı.');
        }

        $uyarilar = [];
        if (($cari['tip'] ?? '') === 'musteri') {
            $uyarilar[] = 'Seçilen cari yalnızca "müşteri" tipinde. Gelen fatura için tipini '
                . '"tedarikçi" veya "her ikisi" yapmanız önerilir.';
        }
        $belgeVkn = (string)$belge['gonderen_vkn_tckn'];
        $cariVkn  = (string)($cari['vergi_no'] ?: $cari['tc_kimlik_no']);
        if ($belgeVkn !== '' && $cariVkn !== '' && $belgeVkn !== $cariVkn) {
            $uyarilar[] = 'DİKKAT: belgedeki VKN/TCKN (' . $belgeVkn . ') seçilen carininkinden ('
                . $cariVkn . ') farklı.';
        }

        $this->db->begin();
        try {
            $this->db->update('e_belgeler', [
                'eslesen_cari_id'   => $cariId,
                'cari_eslesme_tipi' => self::ESLESME_MANUEL,
            ], ['id' => $belgeId]);

            if ($ogren && self::kimlikGecerliMi($belgeVkn)) {
                $this->cariEslesmesiOgren($belgeVkn, $cariId, (string)($belge['gonderen_unvan'] ?? ''), self::ESLESME_MANUEL, $userId);
            }
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        $this->durumTazele($belgeId);
        Audit::log('UPDATE', 'EBELGE', $belgeId, ['eslesen_cari_id' => $belge['eslesen_cari_id']],
            ['eslesen_cari_id' => $cariId], 'e-Belge cari eşleştirildi: ' . ($cari['unvan'] ?? ''), true, $userId);

        return ['uyarilar' => $uyarilar];
    }

    /**
     * Belgedeki gönderen bilgilerinden YENİ cari kartı açar.
     * Kayıt DOĞRUDAN SQL ile değil, mevcut Cari::ekle() ile oluşturulur.
     */
    public function yeniCariOlustur(int $belgeId, array $form, ?int $userId = null): int
    {
        $belge = $this->belgeGetir($belgeId);
        if (!$belge) {
            throw new RuntimeException('e-Belge bulunamadı.');
        }
        $taraf = $this->db->selectOne(
            "SELECT * FROM e_belge_taraflar
              WHERE belge_id = :id AND company_id = :cid AND rol = 'gonderen'",
            [':id' => $belgeId, ':cid' => TenantContext::activeCompanyId()]
        ) ?? [];

        $unvan = trim((string)($form['unvan'] ?? ($taraf['unvan'] ?? '')));
        if ($unvan === '') {
            throw new RuntimeException('Cari unvanı boş olamaz.');
        }

        $vkn = (string)$belge['gonderen_vkn_tckn'];
        if (self::kimlikGecerliMi($vkn)) {
            $mevcut = $this->db->selectOne(
                "SELECT id, unvan FROM cariler
                  WHERE company_id = :cid AND silindi_mi = 0
                    AND (vergi_no = :vkn OR tc_kimlik_no = :vkn2) LIMIT 1",
                [':cid' => TenantContext::activeCompanyId(), ':vkn' => $vkn, ':vkn2' => $vkn]
            );
            if ($mevcut) {
                throw new RuntimeException(
                    'Bu VKN/TCKN ile kayıtlı bir cari zaten var: ' . $mevcut['unvan']
                    . '. Yeni kart açmak yerine mevcut cariyle eşleştirin.'
                );
            }
        }

        $tip = in_array($form['tip'] ?? '', ['tedarikci', 'her_ikisi'], true) ? $form['tip'] : 'tedarikci';
        $veri = [
            'tip'           => $tip,
            'unvan'         => $unvan,
            'vergi_dairesi' => $taraf['vergi_dairesi'] ?? null,
            'telefon'       => $taraf['telefon'] ?? null,
            'eposta'        => $taraf['eposta'] ?? null,
            'adres'         => $taraf['adres'] ?? null,
            'il'            => $taraf['il'] ?? null,
            'ilce'          => $taraf['ilce'] ?? null,
            'ulke'          => $taraf['ulke'] ?? 'Türkiye',
        ];
        if (strlen($vkn) === 11) {
            $veri['tc_kimlik_no'] = $vkn;
        } elseif ($vkn !== '') {
            $veri['vergi_no'] = $vkn;
        }

        // Cari kurucusu ALTER TABLE çalıştırabilir (inTransaction koruması YOK) —
        // bu yüzden transaction AÇILMADAN ÖNCE örneklenir.
        require_once MODELS_PATH . '/Cari.php';
        $cariModel = new Cari();

        $this->db->begin();
        try {
            $cariId = $cariModel->ekle($veri);
            $this->db->update('e_belgeler', [
                'eslesen_cari_id'   => $cariId,
                'cari_eslesme_tipi' => self::ESLESME_YENI,
            ], ['id' => $belgeId]);

            if (self::kimlikGecerliMi($vkn)) {
                $this->cariEslesmesiOgren($vkn, $cariId, $unvan, self::ESLESME_YENI, $userId);
            }
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        $this->durumTazele($belgeId);
        return $cariId;
    }

    /**
     * Tek bir kalemi ürüne bağlar veya "üründüz gider/hizmet kalemi" olarak işaretler.
     *
     * $form: kalem_islem = urun|urunsuz, urun_id, hedef_birim, birim_carpani, ogren
     *
     * NOT: anahtar bilinçli olarak 'kalem_islem' — 'islem' adı controller'ın
     * kendi eylem yönlendirmesinde kullanılıyor, aynı $_POST dizisinde
     * çakışmaması gerekir.
     */
    public function kalemEslestir(int $belgeId, int $kalemId, array $form, ?int $userId = null): void
    {
        $kalem = $this->kalemGetir($belgeId, $kalemId);
        if (!$kalem) {
            throw new RuntimeException('Belge kalemi bulunamadı.');
        }
        $islem = ($form['kalem_islem'] ?? 'urun') === 'urunsuz' ? 'urunsuz' : 'urun';

        if ($islem === 'urunsuz') {
            // Üründüz kalem: aktarımda fatura_kalemleri.urun_id NULL kalır ve
            // Fatura::ekle() bu kalem için STOK HAREKETİ YAZMAZ (gider faturaları).
            $this->db->update('e_belge_kalemleri', [
                'eslesen_urun_id'   => null,
                'urun_eslesme_tipi' => self::ESLESME_URUNSUZ,
                'hedef_birim'       => null,
                'birim_carpani'     => 1,
            ], ['id' => $kalemId]);
            $this->durumTazele($belgeId);
            Audit::log('UPDATE', 'EBELGE', $belgeId, null, ['kalem_id' => $kalemId, 'islem' => 'urunsuz'],
                'e-Belge kalemi üründüz gider kalemi olarak işaretlendi.', true, $userId);
            return;
        }

        $urunId = (int)($form['urun_id'] ?? 0);
        $urun = $urunId > 0 ? $this->urunGetir($urunId) : null;
        if (!$urun) {
            throw new RuntimeException('Seçilen ürün/hizmet bulunamadı.');
        }

        $hedefBirim = trim((string)($form['hedef_birim'] ?? ''));
        if ($hedefBirim === '') {
            $hedefBirim = (string)($urun['birim'] ?? 'Adet');
        }
        $carpan = (float)str_replace(',', '.', (string)($form['birim_carpani'] ?? '1'));
        if (!is_finite($carpan) || $carpan <= 0) {
            throw new RuntimeException('Birim çarpanı sıfırdan büyük olmalıdır.');
        }

        $ogren = !empty($form['ogren']);

        $this->db->begin();
        try {
            $this->db->update('e_belge_kalemleri', [
                'eslesen_urun_id'   => $urunId,
                'urun_eslesme_tipi' => self::ESLESME_MANUEL,
                'hedef_birim'       => $hedefBirim,
                'birim_carpani'     => $carpan,
            ], ['id' => $kalemId]);

            if ($ogren) {
                $this->urunEslesmesiOgren($belgeId, $kalem, $urunId, $hedefBirim, $carpan, $userId);
            }
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        $this->durumTazele($belgeId);
        Audit::log('UPDATE', 'EBELGE', $belgeId, null,
            ['kalem_id' => $kalemId, 'urun_id' => $urunId, 'hedef_birim' => $hedefBirim, 'birim_carpani' => $carpan],
            'e-Belge kalemi ürünle eşleştirildi.', true, $userId);
    }

    /**
     * Kalemden YENİ ürün/hizmet kartı açar (Urun::ekle üzerinden) ve kalemi ona bağlar.
     * Stok miktarı SIFIR açılır — mal girişi aktarım (Faz 3) sırasında
     * Fatura::ekle() tarafından yazılacaktır.
     */
    public function yeniUrunOlustur(int $belgeId, int $kalemId, array $form, ?int $userId = null): int
    {
        $kalem = $this->kalemGetir($belgeId, $kalemId);
        if (!$kalem) {
            throw new RuntimeException('Belge kalemi bulunamadı.');
        }

        $ad = trim((string)($form['ad'] ?? $kalem['urun_adi']));
        if ($ad === '') {
            throw new RuntimeException('Ürün adı boş olamaz.');
        }
        $tip = ($form['tip'] ?? 'urun') === 'hizmet' ? 'hizmet' : 'urun';
        $birim = trim((string)($form['birim'] ?? ''));
        if (!in_array($birim, self::BIRIM_LISTESI, true)) {
            $birim = self::birimCozumle($kalem['birim_kodu'] ?? null) ?? 'Adet';
        }

        require_once MODELS_PATH . '/Urun.php';
        $urunModel = new Urun();

        $stokKodu = trim((string)($form['stok_kodu'] ?? ''));
        if ($stokKodu === '') {
            $stokKodu = trim((string)($kalem['alici_urun_kodu'] ?? ''));
        }
        if ($stokKodu !== '' && $urunModel->kodMevcutMu('stok_kodu', $stokKodu)) {
            throw new RuntimeException('Bu stok kodu zaten kullanılıyor: ' . $stokKodu
                . '. Farklı bir kod girin ya da mevcut ürünle eşleştirin.');
        }

        $barkod = trim((string)($form['barkod'] ?? ($kalem['barkod'] ?? '')));
        if ($barkod !== '' && $urunModel->kodMevcutMu('barkod', $barkod)) {
            // Barkod başka bir ürüne aitse yeni kartı barkodsuz açmak, mevcut
            // ürünün barkodunu ele geçirmekten daha güvenlidir.
            $barkod = '';
        }

        $veri = [
            'tip'            => $tip,
            'ad'             => mb_substr($ad, 0, 255),
            'stok_kodu'      => $stokKodu !== '' ? $stokKodu : null,
            'barkod'         => $barkod !== '' ? $barkod : null,
            'birim'          => $birim,
            'alis_fiyati'    => (float)($kalem['birim_fiyat'] ?? 0),
            'alis_kdv_orani' => (float)($kalem['kdv_orani'] ?? 0),
            'kdv_orani'      => (float)($kalem['kdv_orani'] ?? 0),
            'stok_miktari'   => 0,
            'gtip'           => $kalem['gtip'] ?? null,
            'aciklama'       => $kalem['aciklama'] ?? null,
        ];

        $this->db->begin();
        try {
            $urunId = $urunModel->ekle($veri);
            $this->db->update('e_belge_kalemleri', [
                'eslesen_urun_id'   => $urunId,
                'urun_eslesme_tipi' => self::ESLESME_YENI,
                'hedef_birim'       => $birim,
                'birim_carpani'     => 1,
            ], ['id' => $kalemId]);

            $this->urunEslesmesiOgren($belgeId, $kalem, $urunId, $birim, 1.0, $userId);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        $this->durumTazele($belgeId);
        return $urunId;
    }

    /** Eşleşmemiş tüm kalemleri üründüz gider kalemi yapar (elektrik/kargo gibi faturalar için). */
    public function topluUrunsuzIsaretle(int $belgeId, ?int $userId = null): int
    {
        $sayac = 0;
        foreach ($this->kalemleriGetir($belgeId) as $kalem) {
            if (!empty($kalem['eslesen_urun_id']) || ($kalem['urun_eslesme_tipi'] ?? '') === self::ESLESME_URUNSUZ) {
                continue;
            }
            $this->db->update('e_belge_kalemleri', [
                'eslesen_urun_id'   => null,
                'urun_eslesme_tipi' => self::ESLESME_URUNSUZ,
                'hedef_birim'       => null,
                'birim_carpani'     => 1,
            ], ['id' => (int)$kalem['id']]);
            $sayac++;
        }

        $this->durumTazele($belgeId);
        Audit::log('UPDATE', 'EBELGE', $belgeId, null, ['kalem' => $sayac],
            'e-Belgede eşleşmemiş kalemler üründüz gider kalemi yapıldı.', true, $userId);
        return $sayac;
    }

    /** Kalemin eşleşmesini kaldırır. */
    public function kalemEslesmesiniKaldir(int $belgeId, int $kalemId, ?int $userId = null): void
    {
        if (!$this->kalemGetir($belgeId, $kalemId)) {
            throw new RuntimeException('Belge kalemi bulunamadı.');
        }
        $this->db->update('e_belge_kalemleri', [
            'eslesen_urun_id'   => null,
            'urun_eslesme_tipi' => null,
            'hedef_birim'       => null,
            'birim_carpani'     => 1,
        ], ['id' => $kalemId]);
        $this->durumTazele($belgeId);
        Audit::log('UPDATE', 'EBELGE', $belgeId, null, ['kalem_id' => $kalemId],
            'e-Belge kalem eşleşmesi kaldırıldı.', true, $userId);
    }

    // ═════════════════════════════════════════════════════════════════
    // DURUM
    // ═════════════════════════════════════════════════════════════════

    /**
     * Belgenin eşleşme durumunu yeniden hesaplar ve yazar.
     *
     * aktarima_hazir  : cari bağlı + tüm kalemler çözülmüş + engelleyici sorun yok
     * dogrulandi      : eşleşme tamam ama engelleyici sorun var (eksik kur, birim kararı)
     * eslesme_bekliyor: cari veya kalem eksik
     * izleme/aktarildi/reddedildi durumlarına DOKUNULMAZ.
     */
    public function durumTazele(int $belgeId): string
    {
        $belge = $this->belgeGetir($belgeId);
        if (!$belge) {
            throw new RuntimeException('e-Belge bulunamadı.');
        }

        $dokunulmaz = [
            EBelge::DURUM_IZLEME,
            EBelge::DURUM_REDDEDILDI,
            EBelge::DURUM_AKTARILDI,
            EBelge::DURUM_AKTARILIYOR,
        ];
        if (in_array((string)$belge['durum'], $dokunulmaz, true)) {
            return (string)$belge['durum'];
        }

        $ozet = $this->eslestirmeOzeti($belgeId);
        if (!$ozet['cari_tamam'] || $ozet['bekleyen_kalem'] > 0) {
            $yeni = EBelge::DURUM_ESLESME_BEKLIYOR;
        } elseif (!empty($ozet['engelleyiciler'])) {
            $yeni = EBelge::DURUM_DOGRULANDI;
        } else {
            $yeni = EBelge::DURUM_AKTARIMA_HAZIR;
        }

        if ($yeni !== (string)$belge['durum']) {
            $this->db->update('e_belgeler', ['durum' => $yeni], ['id' => $belgeId]);
        }
        return $yeni;
    }

    /**
     * Ekranda ve durum hesabında kullanılan eşleşme özeti.
     *
     * @return array{cari_tamam:bool, kalem_toplam:int, bekleyen_kalem:int,
     *               eslesen_kalem:int, urunsuz_kalem:int, engelleyiciler:string[]}
     */
    public function eslestirmeOzeti(int $belgeId): array
    {
        $belge = $this->belgeGetir($belgeId);
        if (!$belge) {
            throw new RuntimeException('e-Belge bulunamadı.');
        }
        $kalemler = $this->kalemleriGetir($belgeId);

        $ozet = [
            'cari_tamam'     => !empty($belge['eslesen_cari_id']),
            'kalem_toplam'   => count($kalemler),
            'bekleyen_kalem' => 0,
            'eslesen_kalem'  => 0,
            'urunsuz_kalem'  => 0,
            'engelleyiciler' => [],
        ];

        foreach ($kalemler as $kalem) {
            $tip = (string)($kalem['urun_eslesme_tipi'] ?? '');
            if ($tip === self::ESLESME_URUNSUZ) {
                $ozet['urunsuz_kalem']++;
                continue;
            }
            if (empty($kalem['eslesen_urun_id'])) {
                $ozet['bekleyen_kalem']++;
                continue;
            }
            $ozet['eslesen_kalem']++;

            // Birim kararı verilmemişse aktarım güvenli değildir: XML'deki KGM
            // ile sistemdeki "Adet" arasında karar verilmeden stok yazılamaz.
            if (empty($kalem['hedef_birim'])) {
                $ozet['engelleyiciler'][] = sprintf(
                    '%d. kalem: XML birimi (%s) ile ürün birimi çelişiyor ya da çözülemedi — birim ve çarpan onayı gerekli.',
                    (int)$kalem['sira_no'],
                    (string)($kalem['birim_kodu'] ?: 'belirtilmemiş')
                );
            }
        }

        if (empty($belge['eslesen_cari_id'])) {
            $ozet['engelleyiciler'][] = 'Belge bir cari kaydına bağlanmamış.';
        }
        if ($ozet['bekleyen_kalem'] > 0) {
            $ozet['engelleyiciler'][] = $ozet['bekleyen_kalem'] . ' kalem henüz eşleştirilmedi.';
        }
        if (($belge['para_birimi'] ?? 'TRY') !== 'TRY' && ($belge['kur'] === null || (float)$belge['kur'] <= 0)) {
            $ozet['engelleyiciler'][] = 'Dövizli belgede kur bilgisi yok; aktarımdan önce girilmelidir.';
        }

        return $ozet;
    }

    // ═════════════════════════════════════════════════════════════════
    // ÖĞRENME TABLOLARI
    // ═════════════════════════════════════════════════════════════════

    private function cariEslesmesiOgren(string $vkn, int $cariId, string $unvan, string $tip, ?int $userId): void
    {
        $companyId = TenantContext::activeCompanyId();
        $mevcut = $this->db->selectOne(
            "SELECT id FROM e_belge_cari_eslesme WHERE company_id = :cid AND vkn_tckn = :vkn LIMIT 1",
            [':cid' => $companyId, ':vkn' => $vkn]
        );

        if ($mevcut) {
            $this->db->update('e_belge_cari_eslesme', [
                'cari_id'           => $cariId,
                'xml_unvan'         => mb_substr($unvan, 0, 255),
                'eslesme_tipi'      => $tip,
                'onaylayan_user_id' => $userId,
                'silindi_mi'        => 0,
                'son_kullanim'      => date('Y-m-d H:i:s'),
            ], ['id' => (int)$mevcut['id']]);
            return;
        }

        $this->db->insert('e_belge_cari_eslesme', [
            'company_id'        => $companyId,
            'vkn_tckn'          => $vkn,
            'cari_id'           => $cariId,
            'xml_unvan'         => mb_substr($unvan, 0, 255),
            'eslesme_tipi'      => $tip,
            'onaylayan_user_id' => $userId,
            'kullanim_sayisi'   => 1,
            'son_kullanim'      => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Ürün eşleşmesini öğrenir. Anahtar TEDARİKÇİ BAZLIDIR: aynı satıcı kodu
     * farklı tedarikçilerde farklı ürünü gösterebilir. Barkod evrenseldir,
     * o yüzden tedarikci_cari_id = 0 ile saklanır.
     */
    private function urunEslesmesiOgren(int $belgeId, array $kalem, int $urunId, string $hedefBirim, float $carpan, ?int $userId): void
    {
        $belge = $this->belgeGetir($belgeId);
        $tedarikciId = (int)($belge['eslesen_cari_id'] ?? 0);

        $anahtarlar = [];
        if (!empty($kalem['satici_urun_kodu']) && $tedarikciId > 0) {
            $anahtarlar[] = ['tip' => 'satici_kodu', 'kod' => (string)$kalem['satici_urun_kodu'], 'cari' => $tedarikciId];
        }
        if (!empty($kalem['barkod'])) {
            $anahtarlar[] = ['tip' => 'barkod', 'kod' => (string)$kalem['barkod'], 'cari' => 0];
        }
        if (!empty($kalem['alici_urun_kodu'])) {
            $anahtarlar[] = ['tip' => 'alici_kodu', 'kod' => (string)$kalem['alici_urun_kodu'], 'cari' => 0];
        }
        if (empty($anahtarlar)) {
            return; // Öğrenilecek bir kod yok (yalnızca ad var) — bilinçli olarak öğrenilmez.
        }

        $companyId = TenantContext::activeCompanyId();
        foreach ($anahtarlar as $anahtar) {
            $mevcut = $this->db->selectOne(
                "SELECT id FROM e_belge_urun_eslesme
                  WHERE company_id = :cid AND tedarikci_cari_id = :tid
                    AND kaynak_kod_tipi = :tip AND kaynak_kod = :kod LIMIT 1",
                [':cid' => $companyId, ':tid' => $anahtar['cari'], ':tip' => $anahtar['tip'], ':kod' => $anahtar['kod']]
            );

            $veri = [
                'urun_id'           => $urunId,
                'hedef_birim'       => $hedefBirim,
                'birim_carpani'     => $carpan,
                'onaylayan_user_id' => $userId,
                'silindi_mi'        => 0,
                'son_kullanim'      => date('Y-m-d H:i:s'),
            ];

            if ($mevcut) {
                $this->db->update('e_belge_urun_eslesme', $veri, ['id' => (int)$mevcut['id']]);
                continue;
            }
            $this->db->insert('e_belge_urun_eslesme', array_merge($veri, [
                'company_id'        => $companyId,
                'tedarikci_cari_id' => $anahtar['cari'],
                'kaynak_kod_tipi'   => $anahtar['tip'],
                'kaynak_kod'        => $anahtar['kod'],
                'kullanim_sayisi'   => 1,
            ]));
        }
    }

    /** Öğrenilmiş eşleşmenin kullanım sayacını artırır (hangi kuralın işe yaradığını görmek için). */
    private function ogrenmeKullanimiIsle(string $tablo, array $anahtar): void
    {
        if ($tablo !== 'e_belge_cari_eslesme') {
            return;
        }
        $this->db->query(
            "UPDATE e_belge_cari_eslesme
                SET kullanim_sayisi = kullanim_sayisi + 1, son_kullanim = NOW()
              WHERE company_id = :cid AND vkn_tckn = :vkn",
            [':cid' => $anahtar['company_id'], ':vkn' => $anahtar['vkn_tckn']]
        );
    }

    // ═════════════════════════════════════════════════════════════════
    // Okuma yardımcıları (hepsi şirket kapsamlı)
    // ═════════════════════════════════════════════════════════════════

    private function belgeGetir(int $belgeId): ?array
    {
        return $this->db->selectOne(
            "SELECT b.*, t.unvan AS gonderen_unvan
               FROM e_belgeler b
               LEFT JOIN e_belge_taraflar t ON t.belge_id = b.id AND t.company_id = b.company_id AND t.rol = 'gonderen'
              WHERE b.id = :id AND b.company_id = :cid AND b.silindi_mi = 0",
            [':id' => $belgeId, ':cid' => TenantContext::activeCompanyId()]
        );
    }

    private function kalemleriGetir(int $belgeId): array
    {
        return $this->db->select(
            "SELECT * FROM e_belge_kalemleri
              WHERE belge_id = :id AND company_id = :cid
              ORDER BY sira_no",
            [':id' => $belgeId, ':cid' => TenantContext::activeCompanyId()]
        );
    }

    private function kalemGetir(int $belgeId, int $kalemId): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM e_belge_kalemleri
              WHERE id = :kid AND belge_id = :bid AND company_id = :cid",
            [':kid' => $kalemId, ':bid' => $belgeId, ':cid' => TenantContext::activeCompanyId()]
        );
    }

    private function urunGetir(int $urunId): ?array
    {
        return $this->db->selectOne(
            "SELECT id, ad, birim, tip, stok_kodu, barkod, kdv_orani
               FROM urunler_hizmetler
              WHERE id = :id AND company_id = :cid AND silindi_mi = 0",
            [':id' => $urunId, ':cid' => TenantContext::activeCompanyId()]
        );
    }

    /** Ekran için: kalemlere eşleşen ürün bilgisini ekler. */
    public function kalemleriEslesmeIle(int $belgeId): array
    {
        return $this->db->select(
            "SELECT k.*, u.ad AS urun_ad, u.stok_kodu AS urun_stok_kodu,
                    u.birim AS urun_birim, u.tip AS urun_tip
               FROM e_belge_kalemleri k
               LEFT JOIN urunler_hizmetler u
                      ON u.id = k.eslesen_urun_id AND u.company_id = k.company_id AND u.silindi_mi = 0
              WHERE k.belge_id = :id AND k.company_id = :cid
              ORDER BY k.sira_no",
            [':id' => $belgeId, ':cid' => TenantContext::activeCompanyId()]
        );
    }

    /** Ekran için: belgeye bağlı cari kaydı. */
    public function eslesenCari(int $belgeId): ?array
    {
        return $this->db->selectOne(
            "SELECT c.id, c.unvan, c.tip, c.vergi_no, c.tc_kimlik_no, c.vergi_dairesi, c.bakiye
               FROM e_belgeler b
               JOIN cariler c ON c.id = b.eslesen_cari_id AND c.company_id = b.company_id AND c.silindi_mi = 0
              WHERE b.id = :id AND b.company_id = :cid",
            [':id' => $belgeId, ':cid' => TenantContext::activeCompanyId()]
        );
    }
}
