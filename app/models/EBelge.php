<?php
/**
 * EBelge — e-Belge (XML) Staging Modeli
 * --------------------------------------------------------
 * FAZ 1 KAPSAMI: yükleme → güvenlik kapısı → ayrıştırma → saklama → listeleme.
 *
 * ÇEKİRDEK SİSTEME DOKUNMAZ:
 *   Bu sınıf faturalar / fatura_kalemleri / stok_hareketleri / cariler /
 *   urunler_hizmetler tablolarına TEK BİR SQL BİLE göndermez. Aktarım (Faz 3)
 *   ayrı bir sınıfta yapılacak ve orada da yalnızca Fatura::ekle() çağrılacaktır.
 *
 * MÜKERRER KAYIT KORUMASI (idempotency) — üç katman:
 *   1) Dosya: UNIQUE (company_id, dosya_hash)  → aynı byte'lar ikinci kez girmez.
 *   2) Belge: UNIQUE (company_id, belge_uuid)  → aynı ETTN ikinci kez girmez.
 *      Yedek anahtar: UNIQUE (company_id, belge_tipi, gonderen_vkn_tckn,
 *      belge_no, belge_tarihi).
 *   3) Aktarım (Faz 3): guarded UPDATE ile durum geçişi — aynı belgeden iki
 *      fatura üretilemez (mevcut Fatura::kaynakBelgeIsaretle deseni).
 *
 * ŞEMA KURULUMU:
 *   Depodaki konvansiyon (bkz. KURULUM.md): migration dosyası yoktur, şema
 *   idempotent CREATE TABLE IF NOT EXISTS ile kod tarafında kurulur.
 *   DDL MySQL'de örtük commit yaptığı için açık bir transaction varken
 *   ÇALIŞTIRILMAZ (bkz. Fatura::__construct açıklaması) ve istek başına
 *   yalnızca bir kez denenir.
 */
final class EBelge
{
    // ─── Durum sabitleri ─────────────────────────────────────────────
    public const DURUM_YENI            = 'yeni';
    public const DURUM_DOGRULANDI      = 'dogrulandi';
    public const DURUM_ESLESME_BEKLIYOR = 'eslesme_bekliyor';
    public const DURUM_AKTARIMA_HAZIR  = 'aktarima_hazir';
    public const DURUM_AKTARILIYOR     = 'aktariliyor';  // Faz 3: guarded UPDATE ile geçilir
    public const DURUM_AKTARILDI       = 'aktarildi';
    public const DURUM_IZLEME          = 'izleme';       // e-İrsaliye: Faz 1'de aktarım kapalı
    public const DURUM_REDDEDILDI      = 'reddedildi';

    public const DOSYA_BEKLIYOR    = 'bekliyor';
    public const DOSYA_PARSE_EDILDI = 'parse_edildi';
    public const DOSYA_HATALI      = 'hatali';
    public const DOSYA_MUKERRER    = 'mukerrer';

    /** Faz 1'de aktarıma açık belge tipleri (e-İrsaliye yalnızca izlenir). */
    public const AKTARILABILIR_TIPLER = ['efatura', 'earsiv'];

    public const BELGE_TIPI_ETIKET = [
        'efatura'   => 'e-Fatura',
        'earsiv'    => 'e-Arşiv Fatura',
        'eirsaliye' => 'e-İrsaliye',
    ];

    private Database $db;

    /** Şema istek başına yalnızca bir kez denensin. */
    private static bool $semaHazir = false;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->ensureSchema();
    }

    // ─────────────────────────────────────────────────────────────────
    // Şema
    // ─────────────────────────────────────────────────────────────────

    /**
     * Idempotent şema kurulumu.
     *
     * NOT: Bu tablolar TenantContext::COMPANY_TABLES listesine BİLİNÇLİ OLARAK
     * eklenmedi. company_id enjeksiyonu zaten kolon VARLIĞINA bakılarak yapılıyor
     * (TenantContext::tenantAwareInsert), dolayısıyla listeye eklemek yalnızca
     * her istekte çalışan gereksiz "WHERE company_id IS NULL" güncellemeleri
     * getirirdi. WRITE_TABLES'a da eklenmedi: kapalı dönemde bile bir e-Belge
     * SAKLANABİLMELİDİR; dönem kilidi yalnızca aktarım anında (faturalar
     * üzerinden) devreye girer.
     */
    public function ensureSchema(): void
    {
        if (self::$semaHazir) {
            return;
        }
        // DDL örtük commit yapar — açık transaction içindeyken çalıştırma.
        if ($this->db->inTransaction()) {
            return;
        }

        try {
            $this->db->query("CREATE TABLE IF NOT EXISTS e_belge_paketleri (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                company_id INT UNSIGNED NOT NULL,
                paket_adi VARCHAR(255) NOT NULL,
                paket_turu VARCHAR(10) NOT NULL DEFAULT 'xml',
                paket_hash CHAR(64) NULL,
                paket_boyut BIGINT UNSIGNED NOT NULL DEFAULT 0,
                dosya_sayisi INT UNSIGNED NOT NULL DEFAULT 0,
                basarili_dosya INT UNSIGNED NOT NULL DEFAULT 0,
                hatali_dosya INT UNSIGNED NOT NULL DEFAULT 0,
                mukerrer_dosya INT UNSIGNED NOT NULL DEFAULT 0,
                bulunan_belge INT UNSIGNED NOT NULL DEFAULT 0,
                durum VARCHAR(30) NOT NULL DEFAULT 'yuklendi',
                hata_ozeti TEXT NULL,
                yukleyen_user_id INT UNSIGNED NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_e_belge_paketleri_company (company_id),
                KEY idx_ebp_durum (company_id, durum),
                KEY idx_ebp_tarih (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");

            $this->db->query("CREATE TABLE IF NOT EXISTS e_belge_dosyalari (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                company_id INT UNSIGNED NOT NULL,
                paket_id INT UNSIGNED NULL,
                dosya_hash CHAR(64) NOT NULL,
                orijinal_ad VARCHAR(255) NOT NULL,
                saklama_yolu VARCHAR(255) NOT NULL,
                boyut BIGINT UNSIGNED NOT NULL DEFAULT 0,
                mime_tespit VARCHAR(100) NULL,
                kok_eleman VARCHAR(60) NULL,
                parse_durumu VARCHAR(30) NOT NULL DEFAULT 'bekliyor',
                parse_hatasi TEXT NULL,
                parse_denemesi INT UNSIGNED NOT NULL DEFAULT 0,
                yukleyen_user_id INT UNSIGNED NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_ebd_company_hash (company_id, dosya_hash),
                KEY idx_e_belge_dosyalari_company (company_id),
                KEY idx_ebd_paket (paket_id),
                KEY idx_ebd_durum (company_id, parse_durumu),
                CONSTRAINT fk_ebd_paket FOREIGN KEY (paket_id)
                    REFERENCES e_belge_paketleri(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");

            $this->db->query("CREATE TABLE IF NOT EXISTS e_belgeler (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                company_id INT UNSIGNED NOT NULL,
                dosya_id INT UNSIGNED NOT NULL,
                belge_uuid VARCHAR(64) NOT NULL,
                belge_tipi VARCHAR(20) NOT NULL,
                yon VARCHAR(10) NOT NULL DEFAULT 'gelen',
                profil_id VARCHAR(40) NULL,
                fatura_tipi_kodu VARCHAR(30) NULL,
                belge_no VARCHAR(64) NOT NULL,
                belge_tarihi DATE NOT NULL,
                belge_saati TIME NULL,
                vade_tarihi DATE NULL,
                gonderen_vkn_tckn VARCHAR(11) NOT NULL DEFAULT '',
                alici_vkn_tckn VARCHAR(11) NOT NULL DEFAULT '',
                para_birimi VARCHAR(5) NOT NULL DEFAULT 'TRY',
                kur DECIMAL(18,6) NULL,
                satir_toplami DECIMAL(18,2) NOT NULL DEFAULT 0.00,
                iskonto_toplami DECIMAL(18,2) NOT NULL DEFAULT 0.00,
                matrah_toplami DECIMAL(18,2) NOT NULL DEFAULT 0.00,
                vergi_toplami DECIMAL(18,2) NOT NULL DEFAULT 0.00,
                tevkifat_toplami DECIMAL(18,2) NOT NULL DEFAULT 0.00,
                genel_toplam DECIMAL(18,2) NOT NULL DEFAULT 0.00,
                odenecek_tutar DECIMAL(18,2) NOT NULL DEFAULT 0.00,
                not_metni TEXT NULL,
                durum VARCHAR(30) NOT NULL DEFAULT 'yeni',
                dogrulama_notlari TEXT NULL,
                eslesen_cari_id INT UNSIGNED NULL,
                cari_eslesme_tipi VARCHAR(20) NULL,
                aktarilan_fatura_id INT UNSIGNED NULL,
                aktarim_period_id INT UNSIGNED NULL,
                aktarim_tarihi DATETIME NULL,
                aktaran_user_id INT UNSIGNED NULL,
                aktarim_hatasi TEXT NULL,
                silindi_mi TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_eb_company_uuid (company_id, belge_uuid),
                UNIQUE KEY uq_eb_dogal (company_id, belge_tipi, gonderen_vkn_tckn, belge_no, belge_tarihi),
                KEY idx_e_belgeler_company (company_id),
                KEY idx_eb_durum (company_id, durum),
                KEY idx_eb_tarih (company_id, belge_tarihi),
                KEY idx_eb_cari (eslesen_cari_id),
                KEY idx_eb_fatura (aktarilan_fatura_id),
                KEY idx_eb_dosya (dosya_id),
                CONSTRAINT fk_eb_dosya FOREIGN KEY (dosya_id)
                    REFERENCES e_belge_dosyalari(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");

            $this->db->query("CREATE TABLE IF NOT EXISTS e_belge_taraflar (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                company_id INT UNSIGNED NOT NULL,
                belge_id INT UNSIGNED NOT NULL,
                rol VARCHAR(10) NOT NULL,
                vkn_tckn VARCHAR(11) NOT NULL DEFAULT '',
                kimlik_semasi VARCHAR(20) NULL,
                unvan VARCHAR(255) NULL,
                ad VARCHAR(120) NULL,
                soyad VARCHAR(120) NULL,
                vergi_dairesi VARCHAR(150) NULL,
                mersis_no VARCHAR(30) NULL,
                ticaret_sicil VARCHAR(30) NULL,
                adres TEXT NULL,
                ilce VARCHAR(100) NULL,
                il VARCHAR(100) NULL,
                ulke VARCHAR(80) NULL,
                posta_kodu VARCHAR(20) NULL,
                telefon VARCHAR(50) NULL,
                eposta VARCHAR(150) NULL,
                web VARCHAR(150) NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_ebt_belge_rol (belge_id, rol),
                KEY idx_e_belge_taraflar_company (company_id),
                KEY idx_ebt_vkn (company_id, vkn_tckn),
                CONSTRAINT fk_ebt_belge FOREIGN KEY (belge_id)
                    REFERENCES e_belgeler(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");

            $this->db->query("CREATE TABLE IF NOT EXISTS e_belge_kalemleri (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                company_id INT UNSIGNED NOT NULL,
                belge_id INT UNSIGNED NOT NULL,
                sira_no INT UNSIGNED NOT NULL DEFAULT 1,
                urun_adi VARCHAR(255) NOT NULL DEFAULT '',
                aciklama TEXT NULL,
                satici_urun_kodu VARCHAR(100) NULL,
                alici_urun_kodu VARCHAR(100) NULL,
                barkod VARCHAR(100) NULL,
                gtip VARCHAR(30) NULL,
                miktar DECIMAL(18,6) NOT NULL DEFAULT 0.000000,
                birim_kodu VARCHAR(10) NULL,
                birim_fiyat DECIMAL(18,6) NOT NULL DEFAULT 0.000000,
                satir_tutari DECIMAL(18,2) NOT NULL DEFAULT 0.00,
                iskonto_tutari DECIMAL(18,2) NOT NULL DEFAULT 0.00,
                iskonto_orani DECIMAL(8,4) NOT NULL DEFAULT 0.0000,
                kdv_orani DECIMAL(8,4) NOT NULL DEFAULT 0.0000,
                kdv_tutari DECIMAL(18,2) NOT NULL DEFAULT 0.00,
                tevkifat_orani DECIMAL(8,4) NOT NULL DEFAULT 0.0000,
                tevkifat_tutari DECIMAL(18,2) NOT NULL DEFAULT 0.00,
                otv_tutari DECIMAL(18,2) NOT NULL DEFAULT 0.00,
                oiv_tutari DECIMAL(18,2) NOT NULL DEFAULT 0.00,
                diger_vergi_tutari DECIMAL(18,2) NOT NULL DEFAULT 0.00,
                istisna_kodu VARCHAR(20) NULL,
                eslesen_urun_id INT UNSIGNED NULL,
                urun_eslesme_tipi VARCHAR(20) NULL,
                hedef_birim VARCHAR(20) NULL,
                birim_carpani DECIMAL(18,6) NOT NULL DEFAULT 1.000000,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_ebk_belge_sira (belge_id, sira_no),
                KEY idx_e_belge_kalemleri_company (company_id),
                KEY idx_ebk_urun (eslesen_urun_id),
                KEY idx_ebk_kodlar (company_id, barkod, alici_urun_kodu),
                CONSTRAINT fk_ebk_belge FOREIGN KEY (belge_id)
                    REFERENCES e_belgeler(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");

            $this->db->query("CREATE TABLE IF NOT EXISTS e_belge_vergiler (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                company_id INT UNSIGNED NOT NULL,
                belge_id INT UNSIGNED NOT NULL,
                kalem_id INT UNSIGNED NULL,
                vergi_kodu VARCHAR(20) NULL,
                vergi_adi VARCHAR(100) NULL,
                matrah DECIMAL(18,2) NOT NULL DEFAULT 0.00,
                oran DECIMAL(8,4) NOT NULL DEFAULT 0.0000,
                tutar DECIMAL(18,2) NOT NULL DEFAULT 0.00,
                istisna_kodu VARCHAR(20) NULL,
                istisna_aciklama VARCHAR(255) NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_e_belge_vergiler_company (company_id),
                KEY idx_ebv_belge (belge_id),
                KEY idx_ebv_kalem (kalem_id),
                CONSTRAINT fk_ebv_belge FOREIGN KEY (belge_id)
                    REFERENCES e_belgeler(id) ON DELETE CASCADE,
                CONSTRAINT fk_ebv_kalem FOREIGN KEY (kalem_id)
                    REFERENCES e_belge_kalemleri(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");

            // Faz 2'de doldurulacak öğrenme tabloları — şema şimdiden kurulur ki
            // ilerideki bir sürüm ayrıca DDL çalıştırmak zorunda kalmasın.
            $this->db->query("CREATE TABLE IF NOT EXISTS e_belge_cari_eslesme (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                company_id INT UNSIGNED NOT NULL,
                vkn_tckn VARCHAR(11) NOT NULL,
                cari_id INT UNSIGNED NOT NULL,
                xml_unvan VARCHAR(255) NULL,
                eslesme_tipi VARCHAR(20) NOT NULL DEFAULT 'manuel',
                onaylayan_user_id INT UNSIGNED NULL,
                kullanim_sayisi INT UNSIGNED NOT NULL DEFAULT 0,
                son_kullanim DATETIME NULL,
                silindi_mi TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_ebce_company_vkn (company_id, vkn_tckn),
                KEY idx_e_belge_cari_eslesme_company (company_id),
                KEY idx_ebce_cari (cari_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");

            $this->db->query("CREATE TABLE IF NOT EXISTS e_belge_urun_eslesme (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                company_id INT UNSIGNED NOT NULL,
                tedarikci_cari_id INT UNSIGNED NOT NULL DEFAULT 0,
                kaynak_kod_tipi VARCHAR(20) NOT NULL,
                kaynak_kod VARCHAR(100) NOT NULL,
                urun_id INT UNSIGNED NULL,
                hedef_birim VARCHAR(20) NULL,
                birim_carpani DECIMAL(18,6) NOT NULL DEFAULT 1.000000,
                onaylayan_user_id INT UNSIGNED NULL,
                kullanim_sayisi INT UNSIGNED NOT NULL DEFAULT 0,
                son_kullanim DATETIME NULL,
                silindi_mi TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_ebue_kod (company_id, tedarikci_cari_id, kaynak_kod_tipi, kaynak_kod),
                KEY idx_e_belge_urun_eslesme_company (company_id),
                KEY idx_ebue_urun (urun_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");

            self::$semaHazir = true;
        } catch (Throwable $e) {
            // Şema kurulamazsa panel ÇÖKMEZ; kullanıcı modüle girdiğinde
            // anlaşılır bir hata görür ve sunucu loguna teknik detay yazılır.
            error_log('[NYMAGRO] e-Belge şeması kurulamadı: ' . $e->getMessage());
        }
    }

    /** Şema hazır mı? Controller bunu kontrol edip kullanıcıya anlaşılır mesaj gösterir. */
    public function semaHazirMi(): bool
    {
        return self::$semaHazir;
    }

    // ─────────────────────────────────────────────────────────────────
    // Yükleme akışı
    // ─────────────────────────────────────────────────────────────────

    /**
     * Yüklenen dosyayı (xml veya zip) baştan sona işler.
     *
     * @return array{paket_id:?int, sonuclar:array<int,array>, ozet:array}
     * @throws RuntimeException Güvenlik kapısı reddederse (kullanıcıya gösterilir).
     */
    public function yuklemeIsle(array $file, ?int $userId): array
    {
        $tur = EBelgeGuvenlik::yuklemeKapisi($file);
        $tmp = (string)$file['tmp_name'];
        $ad  = (string)$file['name'];

        if ($tur === 'zip') {
            $girdiler = EBelgeGuvenlik::zipIcerigiCikar($tmp);
            $paketHash = hash_file('sha256', $tmp) ?: null;
        } else {
            $icerik = file_get_contents($tmp);
            if ($icerik === false) {
                throw new RuntimeException('Yüklenen dosya okunamadı.');
            }
            $girdiler = [['ad' => $ad, 'icerik' => $icerik]];
            $paketHash = EBelgeGuvenlik::hash($icerik);
        }

        $companyId = TenantContext::activeCompanyId();
        if (!$companyId) {
            throw new RuntimeException('Aktif şirket seçilmeden e-Belge yüklenemez.');
        }

        $paketId = $this->db->insert('e_belge_paketleri', [
            'company_id'       => $companyId,
            'paket_adi'        => mb_substr($ad, 0, 255),
            'paket_turu'       => $tur,
            'paket_hash'       => $paketHash,
            'paket_boyut'      => (int)($file['size'] ?? 0),
            'dosya_sayisi'     => count($girdiler),
            'durum'            => 'isleniyor',
            'yukleyen_user_id' => $userId,
        ]);

        $sonuclar = [];
        $ozet = ['basarili' => 0, 'hatali' => 0, 'mukerrer' => 0, 'belge' => 0];

        foreach ($girdiler as $girdi) {
            // Her dosya BAĞIMSIZ işlenir: 50 belgelik bir arşivde 3 bozuk dosya
            // varsa diğer 47'si yine de kaydedilir.
            $sonuc = $this->tekDosyaIsle(
                (string)$girdi['icerik'],
                (string)$girdi['ad'],
                $paketId,
                $companyId,
                $userId
            );
            $sonuclar[] = $sonuc;

            switch ($sonuc['durum']) {
                case self::DOSYA_PARSE_EDILDI:
                    $ozet['basarili']++;
                    $ozet['belge']++;
                    break;
                case self::DOSYA_MUKERRER:
                    $ozet['mukerrer']++;
                    break;
                default:
                    $ozet['hatali']++;
            }
        }

        $hatalar = [];
        foreach ($sonuclar as $s) {
            if ($s['durum'] === self::DOSYA_HATALI) {
                $hatalar[] = $s['dosya_adi'] . ': ' . $s['mesaj'];
            }
        }

        $this->db->update('e_belge_paketleri', [
            'basarili_dosya' => $ozet['basarili'],
            'hatali_dosya'   => $ozet['hatali'],
            'mukerrer_dosya' => $ozet['mukerrer'],
            'bulunan_belge'  => $ozet['belge'],
            'durum'          => $ozet['hatali'] > 0 ? 'kismi' : 'tamamlandi',
            'hata_ozeti'     => $hatalar ? mb_substr(implode("\n", $hatalar), 0, 4000) : null,
        ], ['id' => $paketId]);

        return ['paket_id' => $paketId, 'sonuclar' => $sonuclar, 'ozet' => $ozet];
    }

    /**
     * Tek bir XML içeriğini işler. ASLA exception fırlatmaz — her sonuç
     * (başarı/hata/mükerrer) kullanıcıya gösterilebilir bir dizi olarak döner.
     *
     * @return array{durum:string, dosya_adi:string, mesaj:string, belge_id:?int, dosya_id:?int}
     */
    private function tekDosyaIsle(string $hamGelen, string $dosyaAdi, ?int $paketId, int $companyId, ?int $userId): array
    {
        $sonuc = [
            'durum'     => self::DOSYA_HATALI,
            'dosya_adi' => $dosyaAdi,
            'mesaj'     => '',
            'belge_id'  => null,
            'dosya_id'  => null,
        ];

        try {
            $ham  = EBelgeGuvenlik::utf8eCevir($hamGelen);
            EBelgeGuvenlik::xmlIcerikKapisi($ham);
            $hash = EBelgeGuvenlik::hash($ham);

            // ── İDEMPOTENCY KATMAN 1: aynı dosya ikinci kez girmez ────────
            $mevcutDosya = $this->db->selectOne(
                "SELECT d.id, d.created_at, d.parse_durumu, b.id AS belge_id
                   FROM e_belge_dosyalari d
                   LEFT JOIN e_belgeler b ON b.dosya_id = d.id AND b.company_id = d.company_id
                  WHERE d.company_id = :cid AND d.dosya_hash = :hash
                  LIMIT 1",
                [':cid' => $companyId, ':hash' => $hash]
            );
            if ($mevcutDosya) {
                $sonuc['durum']    = self::DOSYA_MUKERRER;
                $sonuc['dosya_id'] = (int)$mevcutDosya['id'];
                $sonuc['belge_id'] = $mevcutDosya['belge_id'] !== null ? (int)$mevcutDosya['belge_id'] : null;
                $sonuc['mesaj']    = 'Bu dosya daha önce yüklenmiş ('
                    . date('d.m.Y H:i', strtotime((string)$mevcutDosya['created_at'])) . ').';
                return $sonuc;
            }

            $yollar = $this->hamDosyayiSakla($ham, $hash, $companyId);

            $this->db->begin();
            try {
                $dosyaId = $this->db->insert('e_belge_dosyalari', [
                    'company_id'       => $companyId,
                    'paket_id'         => $paketId,
                    'dosya_hash'       => $hash,
                    'orijinal_ad'      => mb_substr($dosyaAdi, 0, 255),
                    'saklama_yolu'     => $yollar['goreli'],
                    'boyut'            => strlen($ham),
                    'kok_eleman'       => EBelgeGuvenlik::kokEleman($ham),
                    'parse_durumu'     => self::DOSYA_BEKLIYOR,
                    'parse_denemesi'   => 1,
                    'yukleyen_user_id' => $userId,
                ]);
                $sonuc['dosya_id'] = $dosyaId;

                try {
                    $parsed = (new EBelgeParser())->parse($ham);
                } catch (Throwable $pe) {
                    $this->db->update('e_belge_dosyalari', [
                        'parse_durumu' => self::DOSYA_HATALI,
                        'parse_hatasi' => mb_substr($pe->getMessage(), 0, 2000),
                    ], ['id' => $dosyaId]);
                    $this->db->commit();

                    $sonuc['durum'] = self::DOSYA_HATALI;
                    $sonuc['mesaj'] = $pe->getMessage();
                    return $sonuc;
                }

                // ── İDEMPOTENCY KATMAN 2: aynı ETTN ikinci kez girmez ─────
                $mevcutBelge = $this->db->selectOne(
                    "SELECT id, belge_no FROM e_belgeler
                      WHERE company_id = :cid AND belge_uuid = :uuid LIMIT 1",
                    [':cid' => $companyId, ':uuid' => $parsed['baslik']['belge_uuid']]
                );
                if ($mevcutBelge) {
                    $this->db->update('e_belge_dosyalari', [
                        'parse_durumu' => self::DOSYA_MUKERRER,
                        'parse_hatasi' => 'Aynı ETTN ile kayıtlı belge zaten var (#' . $mevcutBelge['id'] . ').',
                    ], ['id' => $dosyaId]);
                    $this->db->commit();

                    $sonuc['durum']    = self::DOSYA_MUKERRER;
                    $sonuc['belge_id'] = (int)$mevcutBelge['id'];
                    $sonuc['mesaj']    = 'Bu belge (ETTN) sistemde zaten kayıtlı: ' . $mevcutBelge['belge_no'];
                    return $sonuc;
                }

                $belgeId = $this->belgeyiYaz($parsed, $dosyaId, $companyId);

                $this->db->update('e_belge_dosyalari', [
                    'parse_durumu' => self::DOSYA_PARSE_EDILDI,
                ], ['id' => $dosyaId]);

                $this->db->commit();

                Audit::log('CREATE', 'EBELGE', $belgeId, null, [
                    'belge_uuid' => $parsed['baslik']['belge_uuid'],
                    'belge_no'   => $parsed['baslik']['belge_no'],
                    'belge_tipi' => $parsed['belge_tipi'],
                    'tutar'      => $parsed['baslik']['genel_toplam'],
                    'dosya_hash' => $hash,
                ], 'e-Belge içeri alındı: ' . $parsed['baslik']['belge_no'], true, $userId);

                $sonuc['durum']    = self::DOSYA_PARSE_EDILDI;
                $sonuc['belge_id'] = $belgeId;
                $sonuc['mesaj']    = (self::BELGE_TIPI_ETIKET[$parsed['belge_tipi']] ?? $parsed['belge_tipi'])
                    . ' · ' . $parsed['baslik']['belge_no'];
                return $sonuc;
            } catch (Throwable $e) {
                $this->db->rollBack();
                throw $e;
            }
        } catch (Throwable $e) {
            error_log('[NYMAGRO] e-Belge dosya işleme hatası (' . $dosyaAdi . '): ' . $e->getMessage());

            // Yarış durumu: aynı belge iki istekte eşzamanlı yüklendiyse tekillik
            // indeksi devreye girer. Bu bir hata değil, korumanın çalışmasıdır —
            // kullanıcıya ham SQL mesajı yerine anlaşılır bir bildirim gösterilir.
            if ($e instanceof PDOException && (string)$e->getCode() === '23000') {
                $sonuc['durum'] = self::DOSYA_MUKERRER;
                $sonuc['mesaj'] = str_contains($e->getMessage(), 'uq_eb_dogal')
                    ? 'Aynı gönderen/numara/tarih ile kayıtlı bir belge zaten var (farklı ETTN taşıyor olabilir).'
                    : 'Bu belge sistemde zaten kayıtlı.';
                return $sonuc;
            }

            $sonuc['durum'] = self::DOSYA_HATALI;
            $sonuc['mesaj'] = $e->getMessage();
            return $sonuc;
        }
    }

    /** Ayrıştırılmış belgeyi staging tablolarına yazar. Çağıran transaction açmış olmalıdır. */
    private function belgeyiYaz(array $parsed, int $dosyaId, int $companyId): int
    {
        $baslik = $parsed['baslik'];
        $uyarilar = $parsed['uyarilar'];

        // ── YÖN TESPİTİ ─────────────────────────────────────────────
        // Şirket alıcı mı (gelen), gönderen mi (giden)? Giden belgeler gelen
        // fatura olarak aktarılamaz — bkz. yonBelirle() açıklaması.
        $sirket = TenantContext::activeCompany();
        $sirketVkn = trim((string)($sirket['tax_number'] ?? ''));
        $yon = self::yonBelirle($sirketVkn, $baslik['gonderen_vkn_tckn'] ?? '', $baslik['alici_vkn_tckn'] ?? '');

        $yonUyarisi = self::yonUyarisi($yon, $sirketVkn);
        if ($yonUyarisi !== null) {
            $uyarilar[] = $yonUyarisi;
        }

        // Aktarım yalnızca GELEN e-Fatura/e-Arşiv için açıktır.
        $aktarilabilir = in_array($parsed['belge_tipi'], self::AKTARILABILIR_TIPLER, true) && $yon === 'gelen';
        $durum = !$aktarilabilir
            ? self::DURUM_IZLEME
            : ($uyarilar ? self::DURUM_DOGRULANDI : self::DURUM_ESLESME_BEKLIYOR);

        $belgeId = $this->db->insert('e_belgeler', [
            'company_id'        => $companyId,
            'dosya_id'          => $dosyaId,
            'belge_uuid'        => $baslik['belge_uuid'],
            'belge_tipi'        => $parsed['belge_tipi'],
            'yon'               => $yon,
            'profil_id'         => $baslik['profil_id'],
            'fatura_tipi_kodu'  => $baslik['fatura_tipi_kodu'],
            'belge_no'          => $baslik['belge_no'],
            'belge_tarihi'      => $baslik['belge_tarihi'],
            'belge_saati'       => $baslik['belge_saati'],
            'vade_tarihi'       => $baslik['vade_tarihi'],
            'gonderen_vkn_tckn' => $baslik['gonderen_vkn_tckn'],
            'alici_vkn_tckn'    => $baslik['alici_vkn_tckn'],
            'para_birimi'       => $baslik['para_birimi'],
            'kur'               => $baslik['kur'],
            'satir_toplami'     => $baslik['satir_toplami'],
            'iskonto_toplami'   => $baslik['iskonto_toplami'],
            'matrah_toplami'    => $baslik['matrah_toplami'],
            'vergi_toplami'     => $baslik['vergi_toplami'],
            'tevkifat_toplami'  => $baslik['tevkifat_toplami'],
            'genel_toplam'      => $baslik['genel_toplam'],
            'odenecek_tutar'    => $baslik['odenecek_tutar'],
            'not_metni'         => $baslik['not_metni'],
            'durum'             => $durum,
            'dogrulama_notlari' => $uyarilar
                ? json_encode(array_values($uyarilar), JSON_UNESCAPED_UNICODE)
                : null,
        ]);

        foreach (['gonderen', 'alici'] as $rol) {
            $taraf = $parsed['taraflar'][$rol] ?? null;
            if (!is_array($taraf)) {
                continue;
            }
            $taraf['company_id'] = $companyId;
            $taraf['belge_id']   = $belgeId;
            $taraf['rol']        = $rol;
            $this->db->insert('e_belge_taraflar', $taraf);
        }

        foreach ($parsed['kalemler'] as $kalem) {
            $kalemVergileri = $kalem['vergiler'];
            unset($kalem['vergiler']);
            $kalem['company_id'] = $companyId;
            $kalem['belge_id']   = $belgeId;
            $kalemId = $this->db->insert('e_belge_kalemleri', $kalem);

            foreach ($kalemVergileri as $vergi) {
                $vergi['company_id'] = $companyId;
                $vergi['belge_id']   = $belgeId;
                $vergi['kalem_id']   = $kalemId;
                $this->db->insert('e_belge_vergiler', $vergi);
            }
        }

        foreach ($parsed['belge_vergileri'] as $vergi) {
            $vergi['company_id'] = $companyId;
            $vergi['belge_id']   = $belgeId;
            $vergi['kalem_id']   = null;
            $this->db->insert('e_belge_vergiler', $vergi);
        }

        return $belgeId;
    }

    // ─────────────────────────────────────────────────────────────────
    // Dosya saklama
    // ─────────────────────────────────────────────────────────────────

    /**
     * Ham XML'i diske yazar ve [mutlak, goreli] yollarını döner.
     *
     * KONUM: public/uploads/e-belge/... — bilinçli tercih. FTP dağıtımında
     * public/uploads/** exclude listesindedir (bkz. .github/workflows/deploy.yml),
     * yani senkronizasyon bu klasörü SİLMEZ. Buna karşılık dizine runtime'da
     * bir .htaccess yazılır (deploy bu klasöre dosya göndermediği için depoya
     * konan bir .htaccess sunucuya hiç ulaşmazdı).
     *
     * Dosya adı = SHA-256 + .xml: kullanıcı dosya adı yola HİÇ girmez
     * (path traversal ve çalıştırılabilir uzantı riski tamamen ortadan kalkar).
     *
     * @return array{mutlak:string, goreli:string}
     */
    private function hamDosyayiSakla(string $ham, string $hash, int $companyId): array
    {
        $kok = $this->saklamaKoku();
        $this->korumaDosyasiniYaz($kok);

        $goreliDizin = 'uploads/e-belge/' . $companyId . '/' . date('Y') . '/' . date('m');
        $mutlakDizin = ROOT_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $goreliDizin);

        if (!is_dir($mutlakDizin) && !mkdir($mutlakDizin, 0775, true) && !is_dir($mutlakDizin)) {
            throw new RuntimeException('e-Belge saklama klasörü oluşturulamadı.');
        }

        $goreli = $goreliDizin . '/' . $hash . '.xml';
        $mutlak = $mutlakDizin . DIRECTORY_SEPARATOR . $hash . '.xml';

        // Aynı hash aynı içerik demektir; dosya zaten varsa yeniden yazmaya gerek yok.
        if (!is_file($mutlak) && file_put_contents($mutlak, $ham, LOCK_EX) === false) {
            throw new RuntimeException('e-Belge dosyası diske yazılamadı.');
        }

        return ['mutlak' => $mutlak, 'goreli' => $goreli];
    }

    private function saklamaKoku(): string
    {
        return ROOT_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR
            . 'uploads' . DIRECTORY_SEPARATOR . 'e-belge';
    }

    /**
     * Saklama kökünü web'den doğrudan indirilemez yapar (Apache).
     * Nginx'te .htaccess okunmaz — bu yüzden indirme HER ZAMAN yetki kontrollü
     * PHP ucundan (EBelgeController::indir) yapılır; bu dosya ikinci savunmadır.
     */
    private function korumaDosyasiniYaz(string $kok): void
    {
        if (!is_dir($kok) && !mkdir($kok, 0775, true) && !is_dir($kok)) {
            throw new RuntimeException('e-Belge saklama klasörü oluşturulamadı.');
        }
        $htaccess = $kok . DIRECTORY_SEPARATOR . '.htaccess';
        if (is_file($htaccess)) {
            return;
        }
        $icerik = "# e-Belge XML'leri web'den DOĞRUDAN indirilemez.\n"
            . "# Indirme yalnizca yetki kontrollu PHP ucundan yapilir (EBelgeController::indir).\n"
            . "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n"
            . "<IfModule !mod_authz_core.c>\n    Order Deny,Allow\n    Deny from all\n</IfModule>\n";
        @file_put_contents($htaccess, $icerik);
    }

    /** Belgenin ham XML dosyasının MUTLAK yolu (yoksa null). */
    public function hamXmlYolu(int $belgeId): ?array
    {
        $satir = $this->db->selectOne(
            "SELECT d.saklama_yolu, d.orijinal_ad, b.belge_no
               FROM e_belgeler b
               JOIN e_belge_dosyalari d ON d.id = b.dosya_id AND d.company_id = b.company_id
              WHERE b.id = :id AND b.company_id = :cid AND b.silindi_mi = 0",
            [':id' => $belgeId, ':cid' => TenantContext::activeCompanyId()]
        );
        if (!$satir) {
            return null;
        }

        // Path traversal savunması: veritabanındaki yol bozulmuş/oynanmış olsa
        // bile public/uploads kökünün DIŞINA çıkılamaz.
        $publicKok = realpath(ROOT_PATH . DIRECTORY_SEPARATOR . 'public');
        $mutlak = realpath(ROOT_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, ltrim((string)$satir['saklama_yolu'], '/\\')));

        if ($publicKok === false || $mutlak === false || !is_file($mutlak)) {
            return null;
        }
        if (!str_starts_with($mutlak, $publicKok . DIRECTORY_SEPARATOR)) {
            return null;
        }

        return [
            'yol'      => $mutlak,
            'indirme_adi' => 'ebelge-' . preg_replace('/[^A-Za-z0-9_\-]/', '', (string)$satir['belge_no']) . '.xml',
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // Okuma
    // ─────────────────────────────────────────────────────────────────

    public function listele(array $filtreler = [], int $limit = 50, int $offset = 0): array
    {
        [$where, $params] = $this->listeKosulu($filtreler);
        $params[':limit']  = $limit;
        $params[':offset'] = $offset;

        return $this->db->select(
            "SELECT b.*, d.orijinal_ad, d.dosya_hash,
                    t.unvan AS gonderen_unvan,
                    (SELECT COUNT(*) FROM e_belge_kalemleri k
                      WHERE k.belge_id = b.id AND k.company_id = b.company_id) AS kalem_sayisi
               FROM e_belgeler b
               JOIN e_belge_dosyalari d ON d.id = b.dosya_id AND d.company_id = b.company_id
               LEFT JOIN e_belge_taraflar t ON t.belge_id = b.id AND t.company_id = b.company_id AND t.rol = 'gonderen'
              WHERE {$where}
              ORDER BY b.belge_tarihi DESC, b.id DESC
              LIMIT :limit OFFSET :offset",
            $params
        );
    }

    public function say(array $filtreler = []): int
    {
        [$where, $params] = $this->listeKosulu($filtreler);
        $satir = $this->db->selectOne("SELECT COUNT(*) AS n FROM e_belgeler b WHERE {$where}", $params);
        return (int)($satir['n'] ?? 0);
    }

    public function ozetler(array $filtreler = []): array
    {
        [$where, $params] = $this->listeKosulu($filtreler);
        $satir = $this->db->selectOne(
            "SELECT COUNT(*) AS belge_sayisi,
                    COALESCE(SUM(b.genel_toplam), 0) AS toplam_tutar,
                    COALESCE(SUM(CASE WHEN b.belge_tipi = 'eirsaliye' THEN 1 ELSE 0 END), 0) AS irsaliye_sayisi,
                    COALESCE(SUM(CASE WHEN b.durum = 'eslesme_bekliyor' THEN 1 ELSE 0 END), 0) AS eslesme_bekleyen,
                    COALESCE(SUM(CASE WHEN b.dogrulama_notlari IS NOT NULL THEN 1 ELSE 0 END), 0) AS uyarili
               FROM e_belgeler b
              WHERE {$where}",
            $params
        );
        return $satir ?? [];
    }

    public function detay(int $id): ?array
    {
        return $this->db->selectOne(
            "SELECT b.*, d.orijinal_ad, d.dosya_hash, d.boyut, d.saklama_yolu, d.created_at AS yuklenme_tarihi
               FROM e_belgeler b
               JOIN e_belge_dosyalari d ON d.id = b.dosya_id AND d.company_id = b.company_id
              WHERE b.id = :id AND b.company_id = :cid AND b.silindi_mi = 0",
            [':id' => $id, ':cid' => TenantContext::activeCompanyId()]
        );
    }

    public function taraflar(int $belgeId): array
    {
        $satirlar = $this->db->select(
            "SELECT * FROM e_belge_taraflar
              WHERE belge_id = :id AND company_id = :cid",
            [':id' => $belgeId, ':cid' => TenantContext::activeCompanyId()]
        );
        $cikti = [];
        foreach ($satirlar as $satir) {
            $cikti[$satir['rol']] = $satir;
        }
        return $cikti;
    }

    public function kalemler(int $belgeId): array
    {
        return $this->db->select(
            "SELECT * FROM e_belge_kalemleri
              WHERE belge_id = :id AND company_id = :cid
              ORDER BY sira_no",
            [':id' => $belgeId, ':cid' => TenantContext::activeCompanyId()]
        );
    }

    public function vergiler(int $belgeId): array
    {
        return $this->db->select(
            "SELECT v.*, k.sira_no
               FROM e_belge_vergiler v
               LEFT JOIN e_belge_kalemleri k ON k.id = v.kalem_id AND k.company_id = v.company_id
              WHERE v.belge_id = :id AND v.company_id = :cid
              ORDER BY k.sira_no IS NULL DESC, k.sira_no, v.id",
            [':id' => $belgeId, ':cid' => TenantContext::activeCompanyId()]
        );
    }

    public function sonPaketler(int $limit = 10): array
    {
        return $this->db->select(
            "SELECT p.*, u.full_name AS yukleyen_adi
               FROM e_belge_paketleri p
               LEFT JOIN users u ON u.id = p.yukleyen_user_id
              WHERE p.company_id = :cid
              ORDER BY p.id DESC
              LIMIT :limit",
            [':cid' => TenantContext::activeCompanyId(), ':limit' => $limit]
        );
    }

    /** Ayrıştırılamamış (hatalı) dosyalar — kullanıcı neyin neden alınamadığını görebilsin. */
    public function hataliDosyalar(int $limit = 50): array
    {
        return $this->db->select(
            "SELECT * FROM e_belge_dosyalari
              WHERE company_id = :cid AND parse_durumu = :durum
              ORDER BY id DESC
              LIMIT :limit",
            [
                ':cid'   => TenantContext::activeCompanyId(),
                ':durum' => self::DOSYA_HATALI,
                ':limit' => $limit,
            ]
        );
    }

    /**
     * Belgeyi reddeder (listeden düşer). HAM XML DOSYASI SİLİNMEZ — mali belge
     * saklama yükümlülüğü (VUK) gereği dosya diskte ve dosya kaydı veritabanında kalır.
     */
    public function reddet(int $id, ?int $userId): void
    {
        $once = $this->detay($id);
        if (!$once) {
            throw new RuntimeException('e-Belge bulunamadı.');
        }
        if (!empty($once['aktarilan_fatura_id'])) {
            throw new RuntimeException('Bu belge sisteme aktarılmış; önce ilgili fatura iptal edilmelidir.');
        }

        $this->db->update('e_belgeler', [
            'durum'      => self::DURUM_REDDEDILDI,
            'silindi_mi' => 1,
        ], ['id' => $id]);

        Audit::log('DELETE', 'EBELGE', $id, $once, ['durum' => self::DURUM_REDDEDILDI],
            'e-Belge reddedildi/pasife alındı: ' . ($once['belge_no'] ?? ''), true, $userId);
    }

    /**
     * Belgenin YÖNÜNÜ belirler: şirket alıcı mı, gönderen mi?
     *
     * NEDEN HAYATİ: TÜRMOB/Luca'dan indirilen dosyalar arasında şirketin KENDİ
     * KESTİĞİ (giden) belgeler de bulunur. Bunlar gelen fatura sanılıp
     * aktarılırsa, kendi satış faturamız alış faturası olarak kaydedilir:
     * cari bakiye ters yönde bozulur ve depoya olmayan mal girişi yazılır.
     * Gerçek Luca çıktılarıyla yapılan denemede 7 belgenin 5'i giden çıktı.
     *
     * Saf fonksiyon — veritabanı gerektirmez, CI'da doğrudan test edilir.
     *
     * @return 'gelen'|'giden'|'belirsiz'
     */
    public static function yonBelirle(?string $sirketVkn, ?string $gonderenVkn, ?string $aliciVkn): string
    {
        $sirket   = trim((string)$sirketVkn);
        $gonderen = trim((string)$gonderenVkn);
        $alici    = trim((string)$aliciVkn);

        if ($sirket === '') {
            // Şirketin vergi numarası tanımlı değilse yön güvenilir biçimde
            // belirlenemez. Tahmin etmek yerine "belirsiz" deyip aktarımı
            // kapatıyoruz; kullanıcı Şirket Ayarları'ndan VKN girince çözülür.
            return 'belirsiz';
        }
        if ($alici !== '' && $alici === $sirket) {
            return 'gelen';
        }
        if ($gonderen !== '' && $gonderen === $sirket) {
            return 'giden';
        }
        return 'belirsiz';
    }

    /** Yön için kullanıcıya gösterilecek açıklama (null = sorun yok). */
    public static function yonUyarisi(string $yon, string $sirketVkn = ''): ?string
    {
        return match ($yon) {
            'gelen'  => null,
            'giden'  => 'Bu belge şirketinizin KENDİ KESTİĞİ (giden) bir belgedir — gönderen sizsiniz. '
                . 'Gelen fatura olarak aktarılamaz; yalnızca izleme amaçlı saklanır. '
                . 'Aksi hâlde kendi satışınız alış faturası olarak kaydedilir ve cari bakiye ters yönde bozulur.',
            default  => $sirketVkn === ''
                ? 'Şirketinizin vergi numarası tanımlı olmadığı için belgenin yönü (gelen/giden) '
                  . 'belirlenemedi. Şirket kaydına VKN girildikten sonra yeniden yüklenmelidir. Aktarım kapalıdır.'
                : 'Belgenin ne gönderen ne de alıcı tarafı şirketinizin vergi numarasıyla eşleşiyor. '
                  . 'Bu belge büyük olasılıkla başka bir şirkete ait; aktarım kapalıdır.',
        };
    }

    /** Belge kaydındaki doğrulama uyarılarını dizi olarak döner. */
    public static function uyarilariCoz(?string $json): array
    {
        if ($json === null || $json === '') {
            return [];
        }
        $veri = json_decode($json, true);
        return is_array($veri) ? $veri : [];
    }

    // ─────────────────────────────────────────────────────────────────
    // Yardımcılar
    // ─────────────────────────────────────────────────────────────────

    private function listeKosulu(array $filtreler): array
    {
        $where = ['b.silindi_mi = 0', 'b.company_id = :tenant_company_id'];
        $params = [':tenant_company_id' => TenantContext::activeCompanyId()];

        $harita = [
            'belge_tipi' => ['b.belge_tipi = :belge_tipi', ':belge_tipi'],
            'durum'      => ['b.durum = :durum', ':durum'],
            'para_birimi' => ['b.para_birimi = :para_birimi', ':para_birimi'],
            'baslangic'  => ['b.belge_tarihi >= :baslangic', ':baslangic'],
            'bitis'      => ['b.belge_tarihi <= :bitis', ':bitis'],
            'paket_id'   => ['b.dosya_id IN (SELECT id FROM e_belge_dosyalari WHERE paket_id = :paket_id)', ':paket_id'],
        ];
        foreach ($harita as $anahtar => [$sql, $param]) {
            if (($filtreler[$anahtar] ?? '') !== '') {
                $where[] = $sql;
                $params[$param] = $filtreler[$anahtar];
            }
        }

        if (($filtreler['ara'] ?? '') !== '') {
            $where[] = '(b.belge_no LIKE :ara1 OR b.belge_uuid LIKE :ara2 OR b.gonderen_vkn_tckn LIKE :ara3)';
            $like = '%' . $filtreler['ara'] . '%';
            $params[':ara1'] = $like;
            $params[':ara2'] = $like;
            $params[':ara3'] = $like;
        }
        if (!empty($filtreler['uyarili'])) {
            $where[] = 'b.dogrulama_notlari IS NOT NULL';
        }

        return [implode(' AND ', $where), $params];
    }
}
