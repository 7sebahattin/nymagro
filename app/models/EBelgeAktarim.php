<?php
/**
 * EBelgeAktarim — Çekirdek Sisteme Kontrollü Aktarım (FAZ 3)
 * --------------------------------------------------------
 * Eşleştirmesi tamamlanmış bir e-Belgeyi gerçek ALIŞ FATURASINA dönüştürür.
 *
 * ══ TEK DOKUNUŞ NOKTASI ═══════════════════════════════════════════════
 * Bu sınıf faturalar / fatura_kalemleri / stok_hareketleri / cariler
 * tablolarına TEK BİR SQL BİLE yazmaz. Aktarım YALNIZCA
 *     Fatura::ekle($faturaVeri, $kalemler, $depoId)
 * çağrılarak yapılır. Böylece stok planı, negatif stok ayarı, cari bakiye
 * yeniden hesabı, belge numarası tekilliği, dönem kilidi, tenant guard'ları
 * ve Audit kaydı — hepsi bugünkü üretim kodunun sorumluluğunda kalır.
 * Paralel bir INSERT yolu açmak, geçmişte kapatılmış hata sınıflarının
 * (negatif KDV, istemciden gelen toplam, mükerrer belge no) geri dönmesi demektir.
 *
 * ══ İDEMPOTENCY ══════════════════════════════════════════════════════
 * Aktarım guarded UPDATE ile başlar:
 *     UPDATE e_belgeler SET durum='aktariliyor'
 *      WHERE id=:id AND durum='aktarima_hazir' AND aktarilan_fatura_id IS NULL
 * Etkilenen satır 0 ise işlem durur. Bu, mevcut Fatura::kaynakBelgeIsaretle()
 * desenidir ve çift tıklama / iki sekme yarışını kapatır: ikinci istek satır
 * kilidinde bekler, birincisi commit edince 0 satır görür ve reddedilir.
 *
 * ══ NUMARALANDIRMA ═══════════════════════════════════════════════════
 * Tedarikçinin belge numarası fatura_no OLARAK KULLANILMAZ (uq_faturalar_no_aktif
 * çakışması riski). Numara her zaman şirketin kendi alış serisinden
 * Fatura::faturaNoUret('alis') ile üretilir; tedarikçinin numarası ve ETTN
 * aciklama alanına ve (kolon varsa) kaynak_ebelge_id'ye yazılır.
 */
final class EBelgeAktarim
{
    /** Belge toplamı ile çekirdeğin hesaplayacağı toplam arasında sessizce kabul edilen fark. */
    public const TUTAR_TOLERANSI = 0.05;

    /** Bu farkın üzerindeki sapmalar kullanıcı onayı olmadan aktarılmaz. */
    public const TUTAR_ONAY_ESIGI = 0.05;

    private Database $db;

    /** faturalar.kaynak_ebelge_id kolonu istek başına bir kez kontrol edilir. */
    private static ?bool $kaynakKolonuVar = null;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ═════════════════════════════════════════════════════════════════
    // SAF DÖNÜŞÜM — veritabanı gerektirmez, CI'da doğrudan test edilir
    // ═════════════════════════════════════════════════════════════════

    /**
     * Staging kalemlerini Fatura::ekle()'nin beklediği kalem dizisine çevirir.
     *
     * TASARIM NOTLARI
     *  - Çekirdek kalem tablosu iskontoyu ORAN olarak tutar, tutar olarak değil.
     *    Bu yüzden birim fiyat İSKONTO ÖNCESİ tabandan türetilir:
     *        taban        = satir_tutari + iskonto_tutari
     *        birim_fiyat  = (taban / sistem_miktarı) * kur
     *    Böylece Fatura::ekle()'nin kendi hesabı (miktar × birim_fiyat, sonra
     *    iskonto, sonra KDV) belgedeki net satır tutarını yeniden üretir.
     *  - Miktar sistem birimine çevrilir (birim_carpani); birim fiyat da aynı
     *    bölmeden geçtiği için satır toplamı DEĞİŞMEZ.
     *  - Tüm tutarlar TL'ye çevrilir: çekirdekte ara_toplam/kdv/genel_toplam
     *    her zaman TL'dir (bkz. Fatura::ensureDovizColumns açıklaması).
     *  - urun_id NULL ise (üründüz gider kalemi) Fatura::ekle() o kalem için
     *    stok hareketi YAZMAZ — gider faturalarının doğru yolu budur.
     *
     * @return array{kalemler:array<int,array>, uyarilar:string[]}
     */
    public static function kalemleriDonustur(array $stagingKalemler, float $kur): array
    {
        if (!is_finite($kur) || $kur <= 0) {
            $kur = 1.0;
        }

        $kalemler = [];
        $uyarilar = [];

        foreach ($stagingKalemler as $k) {
            $sira = (int)($k['sira_no'] ?? (count($kalemler) + 1));

            $carpan = (float)($k['birim_carpani'] ?? 1);
            if (!is_finite($carpan) || $carpan <= 0) {
                $carpan = 1.0;
            }

            $miktarXml = (float)($k['miktar'] ?? 0);
            $miktar = round($miktarXml * $carpan, 6);

            $satirTutari   = (float)($k['satir_tutari'] ?? 0);
            $iskontoTutari = (float)($k['iskonto_tutari'] ?? 0);
            $taban = $satirTutari + $iskontoTutari;

            // Bazı üreticiler LineExtensionAmount yazmıyor; o zaman miktar × birim fiyattan türet.
            if ($taban <= 0) {
                $taban = $miktarXml * (float)($k['birim_fiyat'] ?? 0);
            }

            // Çekirdek miktar > 0 şartı koyar (Fatura::assertKalemGecerli).
            // Sıfır miktarlı hizmet satırlarını düşürmek yerine 1 birim kabul
            // edip tutarı koruyoruz; kullanıcı uyarıyı önizlemede görür.
            if ($miktar <= 0) {
                $uyarilar[] = sprintf('%d. kalem: miktar sıfır/eksik — 1 birim kabul edildi, satır tutarı korundu.', $sira);
                $miktar = 1.0;
            }

            $birimFiyat = ($taban / $miktar) * $kur;
            if (!is_finite($birimFiyat) || $birimFiyat < 0) {
                $uyarilar[] = sprintf('%d. kalem: birim fiyat hesaplanamadı, 0 kabul edildi.', $sira);
                $birimFiyat = 0.0;
            }

            $iskontoOrani = (float)($k['iskonto_orani'] ?? 0);
            if ($iskontoOrani <= 0 && $iskontoTutari > 0 && $taban > 0) {
                $iskontoOrani = ($iskontoTutari / $taban) * 100;
            }
            if ($iskontoOrani < 0 || !is_finite($iskontoOrani)) {
                $iskontoOrani = 0.0;
            }
            if ($iskontoOrani > 100) {
                $uyarilar[] = sprintf('%d. kalem: iskonto oranı %%100 üzerinde geldi, %%100 ile sınırlandı.', $sira);
                $iskontoOrani = 100.0;
            }

            $kdvOrani = (float)($k['kdv_orani'] ?? 0);
            if (!is_finite($kdvOrani) || $kdvOrani < 0) {
                $kdvOrani = 0.0;
            }
            if ($kdvOrani > 100) {
                $uyarilar[] = sprintf('%d. kalem: KDV oranı %%100 üzerinde geldi, %%100 ile sınırlandı.', $sira);
                $kdvOrani = 100.0;
            }

            $ad = trim((string)($k['urun_adi'] ?? ''));
            if ($ad === '') {
                $ad = 'Kalem #' . $sira;
            }

            $kalemler[] = [
                'urun_id'       => !empty($k['eslesen_urun_id']) ? (int)$k['eslesen_urun_id'] : null,
                'urun_adi'      => mb_substr($ad, 0, 255),
                'aciklama'      => self::kalemAciklamasi($k),
                'miktar'        => $miktar,
                'birim'         => trim((string)($k['hedef_birim'] ?? '')) ?: 'Adet',
                'birim_fiyat'   => round($birimFiyat, 6),
                'kdv_orani'     => round($kdvOrani, 4),
                'iskonto_orani' => round($iskontoOrani, 4),
            ];
        }

        return ['kalemler' => $kalemler, 'uyarilar' => $uyarilar];
    }

    /** Kaleme iliştirilecek izlenebilirlik notu (satıcı kodu, barkod, tevkifat). */
    private static function kalemAciklamasi(array $k): ?string
    {
        $parcalar = [];
        if (!empty($k['satici_urun_kodu'])) {
            $parcalar[] = 'Satıcı kodu: ' . $k['satici_urun_kodu'];
        }
        if (!empty($k['barkod'])) {
            $parcalar[] = 'Barkod: ' . $k['barkod'];
        }
        if ((float)($k['tevkifat_tutari'] ?? 0) > 0) {
            $parcalar[] = 'Tevkifat: ' . number_format((float)$k['tevkifat_tutari'], 2, ',', '.');
        }
        if ((float)($k['otv_tutari'] ?? 0) > 0) {
            $parcalar[] = 'ÖTV: ' . number_format((float)$k['otv_tutari'], 2, ',', '.');
        }
        if (!empty($k['istisna_kodu'])) {
            $parcalar[] = 'İstisna: ' . $k['istisna_kodu'];
        }
        return $parcalar ? mb_substr(implode(' · ', $parcalar), 0, 500) : null;
    }

    /**
     * Çekirdeğin üreteceği toplam ile belgedeki toplamı karşılaştırır.
     *
     * NEDEN GEREKLİ: Fatura::ekle() başlık toplamlarını KALEMLERDEN hesaplar
     * (bkz. Fatura::kalemToplamlari). Kalem bazlı yuvarlama, belgedeki toplamla
     * birkaç kuruş sapabilir. Sapmayı gizlemek yerine ölçüp gösteriyoruz.
     *
     * @return array{hesaplanan:array, fark:float, tolerans_disi:bool}
     */
    public static function tutarKarsilastir(array $kalemler, float $belgeGenelToplamTl): array
    {
        require_once MODELS_PATH . '/Fatura.php';
        $hesaplanan = Fatura::kalemToplamlari($kalemler);
        $fark = round((float)$hesaplanan['genel_toplam'] - $belgeGenelToplamTl, 2);

        return [
            'hesaplanan'    => $hesaplanan,
            'fark'          => $fark,
            'tolerans_disi' => abs($fark) > self::TUTAR_ONAY_ESIGI,
        ];
    }

    // ═════════════════════════════════════════════════════════════════
    // ÖNİZLEME — hiçbir şey yazmaz
    // ═════════════════════════════════════════════════════════════════

    /**
     * Aktarım sonucunda ne oluşacağını, hangi engellerin bulunduğunu döner.
     * Bu metot VERİTABANINA YAZMAZ.
     */
    public function onizleme(int $belgeId): array
    {
        $belge = $this->belgeGetir($belgeId);
        if (!$belge) {
            throw new RuntimeException('e-Belge bulunamadı.');
        }

        $stagingKalemler = $this->db->select(
            "SELECT * FROM e_belge_kalemleri
              WHERE belge_id = :id AND company_id = :cid ORDER BY sira_no",
            [':id' => $belgeId, ':cid' => TenantContext::activeCompanyId()]
        );

        $kur = ($belge['para_birimi'] ?? 'TRY') === 'TRY' ? 1.0 : (float)($belge['kur'] ?? 0);
        $donusum = self::kalemleriDonustur($stagingKalemler, $kur);
        $belgeToplamTl = round((float)$belge['genel_toplam'] * ($kur > 0 ? $kur : 1), 2);
        $karsilastirma = self::tutarKarsilastir($donusum['kalemler'], $belgeToplamTl);

        $engeller = $this->engelleriTopla($belge, $stagingKalemler, $kur);
        $onaylar  = $this->onayGerektirenler($belge, $karsilastirma);

        return [
            'belge'          => $belge,
            'kalemler'       => $donusum['kalemler'],
            'stagingKalemler' => $stagingKalemler,
            'uyarilar'       => $donusum['uyarilar'],
            'kur'            => $kur,
            'belge_toplam_tl' => $belgeToplamTl,
            'karsilastirma'  => $karsilastirma,
            'engeller'       => $engeller,
            'onaylar'        => $onaylar,
            'belge_tipi'     => $this->hedefBelgeTipi($belge),
            'aktarilabilir'  => empty($engeller),
        ];
    }

    /** Aktarımı KESİNLİKLE engelleyen durumlar. */
    private function engelleriTopla(array $belge, array $stagingKalemler, float $kur): array
    {
        $engeller = [];

        if (!in_array((string)$belge['belge_tipi'], EBelge::AKTARILABILIR_TIPLER, true)) {
            $engeller[] = 'Bu belge tipi (e-İrsaliye) ilk fazda aktarılamaz; yalnızca izleme amaçlıdır.';
        }
        // Giden/belirsiz yönlü belge ALIŞ faturasına dönüştürülemez: kendi
        // kestiğimiz satış faturası alış olarak kaydedilirse cari bakiye ters
        // yönde bozulur ve depoya olmayan mal girişi yazılır.
        if (($belge['yon'] ?? 'gelen') !== 'gelen') {
            $engeller[] = EBelge::yonUyarisi((string)($belge['yon'] ?? 'belirsiz'))
                ?? 'Belgenin yönü gelen olarak doğrulanamadı; aktarım kapalı.';
        }
        if (!empty($belge['aktarilan_fatura_id'])) {
            $engeller[] = 'Bu belge zaten aktarılmış (fatura #' . (int)$belge['aktarilan_fatura_id'] . ').';
        }
        if ((string)$belge['durum'] !== EBelge::DURUM_AKTARIMA_HAZIR) {
            $engeller[] = 'Belge "aktarıma hazır" durumunda değil. Önce cari ve kalem eşleştirmelerini tamamlayın.';
        }
        if (empty($belge['eslesen_cari_id'])) {
            $engeller[] = 'Belge bir cariye bağlanmamış.';
        }
        if (empty($stagingKalemler)) {
            $engeller[] = 'Belgede aktarılacak kalem yok.';
        }
        if (($belge['para_birimi'] ?? 'TRY') !== 'TRY' && $kur <= 0) {
            $engeller[] = 'Dövizli belgede kur bilgisi yok; TL karşılığı hesaplanamaz.';
        }

        // Eşleştirme katmanının kendi engelleri (birim kararı vb.)
        require_once MODELS_PATH . '/EBelgeEslestirme.php';
        $ozet = (new EBelgeEslestirme())->eslestirmeOzeti((int)$belge['id']);
        foreach ($ozet['engelleyiciler'] as $e) {
            $engeller[] = $e;
        }

        // Dönem kilidi — Fatura::ekle() zaten reddeder; burada anlaşılır mesaj veriyoruz.
        if (!TenantContext::isActivePeriodWritable()) {
            $engeller[] = 'Aktif dönem kapalı/kilitli olduğu için fatura oluşturulamaz.';
        }

        return array_values(array_unique($engeller));
    }

    /** Aktarımı engellemeyen ama KULLANICI ONAYI isteyen durumlar. */
    private function onayGerektirenler(array $belge, array $karsilastirma): array
    {
        $onaylar = [];

        if ($karsilastirma['tolerans_disi']) {
            $onaylar['tutar_onay'] = sprintf(
                'Çekirdek sistemin hesaplayacağı genel toplam (%s TL) belgedeki tutardan %s TL farklı. '
                . 'Fark kalem bazlı yuvarlamadan kaynaklanır; fatura kalemlerden hesaplanır.',
                number_format((float)$karsilastirma['hesaplanan']['genel_toplam'], 2, ',', '.'),
                number_format(abs((float)$karsilastirma['fark']), 2, ',', '.')
            );
        }

        $donem = TenantContext::activePeriod();
        if ($donem && !empty($belge['belge_tarihi'])) {
            $tarih = (string)$belge['belge_tarihi'];
            if ($tarih < (string)$donem['start_date'] || $tarih > (string)$donem['end_date']) {
                $onaylar['tarih_onay'] = sprintf(
                    'Belge tarihi (%s) aktif dönemin (%s – %s) dışında. Fatura yine de AKTİF DÖNEME kaydedilir.',
                    date('d.m.Y', strtotime($tarih)),
                    date('d.m.Y', strtotime((string)$donem['start_date'])),
                    date('d.m.Y', strtotime((string)$donem['end_date']))
                );
            }
        }

        if ((float)($belge['tevkifat_toplami'] ?? 0) > 0) {
            $onaylar['tevkifat_onay'] = sprintf(
                'Belgede %s TL tevkifat var. Çekirdek fatura sisteminde tevkifat alanı bulunmadığı için '
                . 'fatura matrah + KDV üzerinden oluşturulur; tevkifat detayı e-Belge kaydında saklanır.',
                number_format((float)$belge['tevkifat_toplami'], 2, ',', '.')
            );
        }

        return $onaylar;
    }

    /** Gelen IADE faturası bir alış iadesidir; diğerleri normal alıştır. */
    private function hedefBelgeTipi(array $belge): string
    {
        $kod = mb_strtoupper(trim((string)($belge['fatura_tipi_kodu'] ?? '')), 'UTF-8');
        return $kod === 'IADE' ? 'iade_alis' : 'alis';
    }

    // ═════════════════════════════════════════════════════════════════
    // AKTARIM
    // ═════════════════════════════════════════════════════════════════

    /**
     * Belgeyi çekirdek sisteme aktarır ve oluşan fatura id'sini döner.
     *
     * @param array $onaylar Kullanıcının işaretlediği onaylar (tutar_onay, tarih_onay, tevkifat_onay)
     * @throws RuntimeException
     */
    public function aktar(int $belgeId, int $depoId, array $onaylar, ?int $userId = null): int
    {
        $onizleme = $this->onizleme($belgeId);

        if (!empty($onizleme['engeller'])) {
            throw new RuntimeException('Aktarım yapılamaz: ' . implode(' ', $onizleme['engeller']));
        }
        foreach ($onizleme['onaylar'] as $anahtar => $mesaj) {
            if (empty($onaylar[$anahtar])) {
                throw new RuntimeException('Onay gerekli: ' . $mesaj);
            }
        }

        $belge = $onizleme['belge'];
        $depo = $this->depoDogrula($depoId);

        $kalemler = $onizleme['kalemler'];
        if (empty($kalemler)) {
            throw new RuntimeException('Aktarılacak kalem bulunamadı.');
        }

        // Fatura modeli transaction AÇILMADAN ÖNCE örneklenir: kurucusu
        // idempotent ALTER TABLE çalıştırır ve DDL MySQL'de örtük commit yapar.
        require_once MODELS_PATH . '/Fatura.php';
        $faturaModel = new Fatura();

        $toplamlar = Fatura::kalemToplamlari($kalemler);
        $kur = (float)$onizleme['kur'];
        $paraBirimi = (string)($belge['para_birimi'] ?? 'TRY');

        $faturaVeri = [
            'belge_tipi'     => $onizleme['belge_tipi'],
            'fatura_no'      => $faturaModel->faturaNoUret('alis'),
            'cari_id'        => (int)$belge['eslesen_cari_id'],
            'fatura_tarihi'  => $belge['belge_tarihi'],
            'vade_tarihi'    => $belge['vade_tarihi'] ?: null,
            'ara_toplam'     => $toplamlar['ara_toplam'],
            'iskonto_tutari' => $toplamlar['iskonto_tutari'],
            'kdv_tutari'     => $toplamlar['kdv_tutari'],
            'genel_toplam'   => $toplamlar['genel_toplam'],
            'odenen_tutar'   => 0,
            'kalan_tutar'    => $toplamlar['genel_toplam'],
            'para_birimi'    => $paraBirimi,
            'kur'            => $kur,
            'durum'          => 'onaylandi',
            'aciklama'       => $this->aciklamaUret($belge),
            'created_by'     => $userId ?? TenantContext::userId(),
        ];

        // Dövizli belgede orijinal tutarlar da referans olarak saklanır.
        if ($paraBirimi !== 'TRY' && $kur > 0) {
            $faturaVeri['ara_toplam_doviz']     = round($toplamlar['ara_toplam'] / $kur, 2);
            $faturaVeri['iskonto_tutari_doviz'] = round($toplamlar['iskonto_tutari'] / $kur, 2);
            $faturaVeri['kdv_tutari_doviz']     = round($toplamlar['kdv_tutari'] / $kur, 2);
            $faturaVeri['genel_toplam_doviz']   = round($toplamlar['genel_toplam'] / $kur, 2);
        }

        // Kolon varsa çekirdek fatura da kaynağı işaret eder. Kolon yoksa
        // (ALTER çalışmadıysa) aktarım YİNE DE yapılır — bağ e_belgeler
        // tarafında zaten aktarilan_fatura_id ile kuruluyor.
        if ($this->kaynakKolonuVarMi()) {
            $faturaVeri['kaynak_ebelge_id'] = (int)$belge['id'];
        }

        $this->db->begin();
        try {
            // ── Guarded UPDATE: aynı belgeden ikinci bir fatura üretilemez ──
            $stmt = $this->db->query(
                "UPDATE e_belgeler
                    SET durum = :yeni
                  WHERE id = :id AND company_id = :cid
                    AND durum = :beklenen
                    AND aktarilan_fatura_id IS NULL
                    AND silindi_mi = 0",
                [
                    ':yeni'     => EBelge::DURUM_AKTARILIYOR,
                    ':id'       => $belgeId,
                    ':cid'      => TenantContext::activeCompanyId(),
                    ':beklenen' => EBelge::DURUM_AKTARIMA_HAZIR,
                ]
            );
            if ($stmt->rowCount() === 0) {
                throw new RuntimeException(
                    'Belge aktarıma alınamadı: durumu değişmiş ya da başka bir işlem tarafından aktarılıyor olabilir.'
                );
            }

            // ── ÇEKİRDEĞE TEK DOKUNUŞ ────────────────────────────────
            $faturaId = $faturaModel->ekle($faturaVeri, $kalemler, (int)$depo['id']);

            // SAVUNMA: Urun::stokHareketiEkle() bir hata yakaladığında
            // rollBack() çağırıp SESSİZCE false döner (istisna fırlatmaz) ve
            // Fatura::ekle() bu dönüşü kontrol etmez. Böyle bir durumda dıştaki
            // transaction çoktan geri alınmış olur; devam edersek kullanıcıya
            // "aktarıldı" der ama ortada fatura olmazdı. Derinlik sayacı sıfıra
            // düştüyse işlem düşmüş demektir — sessiz başarı yerine açık hata.
            if (!$this->db->inTransaction()) {
                throw new RuntimeException(
                    'Aktarım sırasında işlem beklenmedik şekilde sonlandı (büyük olasılıkla bir stok '
                    . 'hareketi yazılamadı). Hiçbir kayıt oluşturulmadı; belge aktarıma hazır durumda kaldı.'
                );
            }

            $this->db->update('e_belgeler', [
                'durum'               => EBelge::DURUM_AKTARILDI,
                'aktarilan_fatura_id' => $faturaId,
                'aktarim_period_id'   => TenantContext::activePeriodId(),
                'aktarim_tarihi'      => date('Y-m-d H:i:s'),
                'aktaran_user_id'     => $userId,
                'aktarim_hatasi'      => null,
            ], ['id' => $belgeId]);

            Audit::log('CREATE', 'EBELGE', $belgeId, null, [
                'fatura_id'   => $faturaId,
                'fatura_no'   => $faturaVeri['fatura_no'],
                'belge_no'    => $belge['belge_no'],
                'belge_uuid'  => $belge['belge_uuid'],
                'genel_toplam' => $toplamlar['genel_toplam'],
                'depo_id'     => (int)$depo['id'],
            ], 'e-Belge çekirdek sisteme aktarıldı: ' . $faturaVeri['fatura_no'], true, $userId);

            $this->db->commit();
            return $faturaId;
        } catch (Throwable $e) {
            $this->db->rollBack();

            // Hata kaydı AYRI bir işlemde yazılır: rollback zaten her şeyi geri
            // aldı, belge "aktarima_hazir" olarak kaldı ve tekrar denenebilir.
            try {
                $this->db->update('e_belgeler', [
                    'aktarim_hatasi' => mb_substr($e->getMessage(), 0, 2000),
                ], ['id' => $belgeId]);
            } catch (Throwable $ignored) {
                error_log('[NYMAGRO] e-Belge aktarım hatası kaydedilemedi: ' . $ignored->getMessage());
            }

            error_log('[NYMAGRO] e-Belge aktarım hatası (belge #' . $belgeId . '): ' . $e->getMessage());
            Audit::log('CREATE', 'EBELGE', $belgeId, null, ['hata' => $e->getMessage()],
                'e-Belge aktarımı başarısız — işlem geri alındı.', false, $userId);

            throw $e;
        }
    }

    /** faturalar.aciklama alanına yazılacak izlenebilirlik metni. */
    private function aciklamaUret(array $belge): string
    {
        $parcalar = [
            'Orijinal e-Fatura No: ' . $belge['belge_no'],
            'ETTN: ' . $belge['belge_uuid'],
        ];
        if (!empty($belge['profil_id'])) {
            $parcalar[] = 'Senaryo: ' . $belge['profil_id'];
        }
        if (!empty($belge['fatura_tipi_kodu'])) {
            $parcalar[] = 'Tip: ' . $belge['fatura_tipi_kodu'];
        }
        if (!empty($belge['gonderen_vkn_tckn'])) {
            $parcalar[] = 'VKN/TCKN: ' . $belge['gonderen_vkn_tckn'];
        }
        if ((float)($belge['tevkifat_toplami'] ?? 0) > 0) {
            $parcalar[] = 'Tevkifat: ' . number_format((float)$belge['tevkifat_toplami'], 2, ',', '.');
        }
        return mb_substr(implode(' | ', $parcalar), 0, 1000);
    }

    private function depoDogrula(int $depoId): array
    {
        $depo = $this->db->selectOne(
            "SELECT id, ad FROM depolar
              WHERE id = :id AND company_id = :cid AND silindi_mi = 0",
            [':id' => $depoId, ':cid' => TenantContext::activeCompanyId()]
        );
        if (!$depo) {
            throw new RuntimeException('Seçilen depo bulunamadı veya aktif şirkete ait değil.');
        }
        return $depo;
    }

    /**
     * faturalar.kaynak_ebelge_id kolonunu (yoksa) idempotent olarak ekler.
     *
     * Kolon çekirdek tabloya AİT olsa da ALTER bilinçli olarak bu modülde
     * tutulur: başarısız olursa etkisi burada kalır ve aktarım yine çalışır.
     * Fatura::$fillable içindeki karşılığı olmadan bu kolon yazılamaz —
     * ikisi birlikte anlam ifade eder.
     */
    public function ensureKaynakKolonu(): void
    {
        if (self::$kaynakKolonuVar !== null) {
            return;
        }
        // DDL örtük commit yapar — açık transaction içindeyken çalıştırma.
        if ($this->db->inTransaction()) {
            return;
        }

        try {
            if (TenantContext::hasColumn('faturalar', 'kaynak_ebelge_id')) {
                self::$kaynakKolonuVar = true;
                return;
            }
            $this->db->query("ALTER TABLE faturalar ADD COLUMN kaynak_ebelge_id INT UNSIGNED NULL");
            $this->db->query("ALTER TABLE faturalar ADD INDEX idx_faturalar_ebelge (kaynak_ebelge_id)");
            self::$kaynakKolonuVar = true;
        } catch (Throwable $e) {
            // Yetki yoksa veya tablo kilitliyse: aktarım engellenmez, yalnızca
            // çekirdek taraftaki geri-referans kurulmaz.
            error_log('[NYMAGRO] faturalar.kaynak_ebelge_id eklenemedi: ' . $e->getMessage());
            self::$kaynakKolonuVar = false;
        }
    }

    private function kaynakKolonuVarMi(): bool
    {
        if (self::$kaynakKolonuVar === null) {
            self::$kaynakKolonuVar = TenantContext::hasColumn('faturalar', 'kaynak_ebelge_id');
        }
        return self::$kaynakKolonuVar === true;
    }

    private function belgeGetir(int $belgeId): ?array
    {
        return $this->db->selectOne(
            "SELECT b.*, t.unvan AS gonderen_unvan
               FROM e_belgeler b
               LEFT JOIN e_belge_taraflar t
                      ON t.belge_id = b.id AND t.company_id = b.company_id AND t.rol = 'gonderen'
              WHERE b.id = :id AND b.company_id = :cid AND b.silindi_mi = 0",
            [':id' => $belgeId, ':cid' => TenantContext::activeCompanyId()]
        );
    }

    /** Aktarılmış belgenin oluşturduğu faturanın özeti (detay ekranında gösterilir). */
    public function aktarilanFatura(int $belgeId): ?array
    {
        return $this->db->selectOne(
            "SELECT f.id, f.fatura_no, f.belge_tipi, f.fatura_tarihi, f.genel_toplam, f.durum
               FROM e_belgeler b
               JOIN faturalar f ON f.id = b.aktarilan_fatura_id AND f.company_id = b.company_id
              WHERE b.id = :id AND b.company_id = :cid",
            [':id' => $belgeId, ':cid' => TenantContext::activeCompanyId()]
        );
    }
}
