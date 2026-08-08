<?php
/**
 * DashboardController — Canlı Veri
 * --------------------------------------------------------
 * Tüm özet kartları gerçek veritabanı sorgularından beslenir.
 * Sorgular COALESCE ile 0'a fallback yapar; boş DB'de hata vermez.
 */

final class DashboardController extends Controller
{
    public function index(): void
    {
        $db = Database::getInstance();
        $companyId = TenantContext::activeCompanyId();
        $periodId = TenantContext::activePeriodId();

        // ─── 1. Bugünkü satış & tahsilat ─────────────────────
        $bugunkuSatis = (float)($db->selectOne(
            "SELECT COALESCE(SUM(genel_toplam),0) AS t
             FROM faturalar
             WHERE belge_tipi = 'satis' AND durum <> 'iptal'
               AND silindi_mi = 0 AND fatura_tarihi = CURDATE()"
            . " AND company_id = :cid AND period_id = :pid",
            [':cid' => $companyId, ':pid' => $periodId]
        )['t'] ?? 0);

        $bugunkuTahsilat = (float)($db->selectOne(
            "SELECT COALESCE(SUM(tutar),0) AS t
             FROM kasa_hareketleri
             WHERE hareket_tipi = 'tahsilat' AND silindi_mi = 0
               AND DATE(tarih) = CURDATE()
               AND company_id = :cid AND period_id = :pid",
            [':cid' => $companyId, ':pid' => $periodId]
        )['t'] ?? 0);

        // ─── 2. Bu ay ciro & alış masrafı ─────────────────────
        $buAyCiro = (float)($db->selectOne(
            "SELECT COALESCE(SUM(genel_toplam),0) AS t
             FROM faturalar
             WHERE belge_tipi = 'satis' AND durum <> 'iptal'
               AND silindi_mi = 0
               AND MONTH(fatura_tarihi) = MONTH(CURDATE())
               AND YEAR(fatura_tarihi)  = YEAR(CURDATE())
               AND company_id = :cid AND period_id = :pid",
            [':cid' => $companyId, ':pid' => $periodId]
        )['t'] ?? 0);

        $buAyAlis = (float)($db->selectOne(
            "SELECT COALESCE(SUM(genel_toplam),0) AS t
             FROM faturalar
             WHERE belge_tipi = 'alis' AND durum <> 'iptal'
               AND silindi_mi = 0
               AND MONTH(fatura_tarihi) = MONTH(CURDATE())
               AND YEAR(fatura_tarihi)  = YEAR(CURDATE())
               AND company_id = :cid AND period_id = :pid",
            [':cid' => $companyId, ':pid' => $periodId]
        )['t'] ?? 0);

        // ─── 3. Stok değeri ───────────────────────────────────
        $stokDegeri = (float)($db->selectOne(
            "SELECT COALESCE(SUM(stok_miktari * alis_fiyati),0) AS t
             FROM urunler_hizmetler
             WHERE silindi_mi = 0 AND tip = 'urun' AND company_id = :cid",
            [':cid' => $companyId]
        )['t'] ?? 0);

        // ─── 4. Kasa / Banka ──────────────────────────────────
        $kasaBakiye = (float)($db->selectOne(
            "SELECT COALESCE(SUM(guncel_bakiye),0) AS t
             FROM kasa_banka
             WHERE silindi_mi = 0 AND company_id = :cid",
            [':cid' => $companyId]
        )['t'] ?? 0);

        $bankaBakiye = (float)($db->selectOne(
            "SELECT COALESCE(SUM(guncel_bakiye),0) AS t
             FROM kasa_banka
             WHERE tip = 'banka' AND silindi_mi = 0 AND company_id = :cid",
            [':cid' => $companyId]
        )['t'] ?? 0);

        // ─── 5. Müşteri alacak (açık hesap) ──────────────────
        // Pozitif bakiyeli müşteriler = bize borçlu
        $musteriAlacak = (float)($db->selectOne(
            "SELECT COALESCE(SUM(GREATEST(bakiye, 0)),0) AS t
             FROM cariler
             WHERE silindi_mi = 0 AND company_id = :cid AND tip IN ('musteri','her_ikisi')",
            [':cid' => $companyId]
        )['t'] ?? 0);

        // ─── 6. Tedarikçi borç (açık hesap) ──────────────────
        // Tedarikçi bakiyesi: pozitif = bize borçlu (alacak), negatif = biz borçluyuz
        // Borçlar: bizim tedarikçiye ödeyeceğimiz
        $tedarikciBorc = (float)($db->selectOne(
            "SELECT COALESCE(SUM(ABS(LEAST(bakiye, 0))),0) AS t
             FROM cariler
             WHERE silindi_mi = 0 AND company_id = :cid AND tip IN ('tedarikci','her_ikisi')",
            [':cid' => $companyId]
        )['t'] ?? 0);

        // ─── 7. Vadesi geçen alacaklar ───────────────────────
        $vadesiGecenler = $db->select(
            "SELECT c.unvan AS ad,
                    DATEDIFF(CURDATE(), f.vade_tarihi) AS gun,
                    f.kalan_tutar AS tutar,
                    f.id, f.fatura_no
             FROM faturalar f
             LEFT JOIN cariler c ON c.id = f.cari_id
             WHERE f.belge_tipi = 'satis'
               AND f.silindi_mi = 0
               AND f.durum NOT IN ('iptal','odendi')
               AND f.kalan_tutar > 0
               AND f.vade_tarihi IS NOT NULL
               AND f.vade_tarihi < CURDATE()
               AND f.company_id = :cid AND f.period_id = :pid
             ORDER BY gun DESC
             LIMIT 8",
            [':cid' => $companyId, ':pid' => $periodId]
        );

        // ─── 8. Son satışlar ─────────────────────────────────
        $sonSatislar = $db->select(
            "SELECT f.id, f.fatura_no, f.fatura_tarihi, f.genel_toplam, f.durum,
                    c.unvan AS cari_unvan
             FROM faturalar f
             LEFT JOIN cariler c ON c.id = f.cari_id
             WHERE f.belge_tipi = 'satis' AND f.silindi_mi = 0
               AND f.company_id = :cid AND f.period_id = :pid
             ORDER BY f.fatura_tarihi DESC, f.id DESC
             LIMIT 5",
            [':cid' => $companyId, ':pid' => $periodId]
        );

        // ─── 9. Son alışlar ──────────────────────────────────
        $sonAlislar = $db->select(
            "SELECT f.id, f.fatura_no, f.fatura_tarihi, f.genel_toplam, f.durum,
                    c.unvan AS cari_unvan
             FROM faturalar f
             LEFT JOIN cariler c ON c.id = f.cari_id
             WHERE f.belge_tipi = 'alis' AND f.silindi_mi = 0
               AND f.company_id = :cid AND f.period_id = :pid
             ORDER BY f.fatura_tarihi DESC, f.id DESC
             LIMIT 5",
            [':cid' => $companyId, ':pid' => $periodId]
        );

        // ─── 10. Sayaçlar ────────────────────────────────────
        $musteriSay    = (int)($db->selectOne("SELECT COUNT(*) AS n FROM cariler WHERE silindi_mi=0 AND company_id=:cid AND tip IN ('musteri','her_ikisi')", [':cid' => $companyId])['n'] ?? 0);
        $tedarikciSay  = (int)($db->selectOne("SELECT COUNT(*) AS n FROM cariler WHERE silindi_mi=0 AND company_id=:cid AND tip IN ('tedarikci','her_ikisi')", [':cid' => $companyId])['n'] ?? 0);
        $urunSay       = (int)($db->selectOne("SELECT COUNT(*) AS n FROM urunler_hizmetler WHERE silindi_mi=0 AND company_id=:cid", [':cid' => $companyId])['n'] ?? 0);
        $bekleyenFatur = (int)($db->selectOne("SELECT COUNT(*) AS n FROM faturalar WHERE silindi_mi=0 AND durum='taslak' AND company_id=:cid AND period_id=:pid", [':cid' => $companyId, ':pid' => $periodId])['n'] ?? 0);

        // ─── 11. Varlıklar & Borçlar toplamları ──────────────
        $varliklarToplam = $kasaBakiye + $stokDegeri + $musteriAlacak;
        $borclarToplam   = $tedarikciBorc;

        // ─── 12. Ürün stok listesi (dashboard vitrin) ────────
        $urunStokListesi = $db->select(
            "SELECT id, ad, stok_kodu, birim, satis_fiyati, stok_miktari, kritik_stok, kategori
             FROM urunler_hizmetler
             WHERE silindi_mi = 0 AND tip = 'urun' AND company_id = :cid
             ORDER BY ad ASC
             LIMIT 16",
            [':cid' => $companyId]
        );
        $urunStokToplam = (int)($db->selectOne(
            "SELECT COUNT(*) AS n FROM urunler_hizmetler
             WHERE silindi_mi = 0 AND tip = 'urun' AND company_id = :cid",
            [':cid' => $companyId]
        )['n'] ?? 0);

        // ─── 13. Aylık trend (son 6 ay satış) ─────────────────
        $aylikTrend = $db->select(
            "SELECT DATE_FORMAT(fatura_tarihi,'%Y-%m') AS ay,
                    COALESCE(SUM(genel_toplam),0)       AS toplam
             FROM faturalar
             WHERE belge_tipi='satis' AND durum <> 'iptal' AND silindi_mi=0
               AND fatura_tarihi >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
               AND company_id = :cid AND period_id = :pid
             GROUP BY ay
             ORDER BY ay ASC",
            [':cid' => $companyId, ':pid' => $periodId]
        );

        $this->view('dashboard/index', [
            'pageTitle'        => 'Ana Sayfa',
            'activeMenu'       => 'dashboard',
            'bugun'            => $this->formatTrDate(date('Y-m-d')),
            'buAy'             => $this->ayAdi((int)date('n')),
            // Kartlar
            'bugunkuSatis'     => $bugunkuSatis,
            'bugunkuTahsilat'  => $bugunkuTahsilat,
            'buAyCiro'         => $buAyCiro,
            'buAyAlis'         => $buAyAlis,
            'stokDegeri'       => $stokDegeri,
            // Varlıklar
            'kasaBakiye'       => $kasaBakiye,
            'bankaBakiye'      => $bankaBakiye,
            'stok'             => $stokDegeri,
            'acikHesap'        => $musteriAlacak,
            'varliklarToplam'  => $varliklarToplam,
            // Borçlar
            'borclarToplam'    => $borclarToplam,
            'tedarikciBorc'    => $tedarikciBorc,
            // Listeler
            'vadesiGecenler'   => $vadesiGecenler,
            'sonSatislar'      => $sonSatislar,
            'sonAlislar'       => $sonAlislar,
            'urunStokListesi'  => $urunStokListesi,
            'urunStokToplam'   => $urunStokToplam,
            // Sayaçlar
            'musteriSay'       => $musteriSay,
            'tedarikciSay'     => $tedarikciSay,
            'urunSay'          => $urunSay,
            'bekleyenFatur'    => $bekleyenFatur,
            // Grafik verisi
            'aylikTrend'       => $aylikTrend,
        ]);
    }

    private function formatTrDate(string $date): string
    {
        $aylar = [
            1=>'Ocak','Şubat','Mart','Nisan','Mayıs','Haziran',
            'Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'
        ];
        $gunler = [
            'Sunday'=>'Pazar','Monday'=>'Pazartesi','Tuesday'=>'Salı',
            'Wednesday'=>'Çarşamba','Thursday'=>'Perşembe',
            'Friday'=>'Cuma','Saturday'=>'Cumartesi'
        ];
        $ts = strtotime($date);
        return sprintf('%d %s %d %s',
            (int)date('j', $ts),
            $aylar[(int)date('n', $ts)],
            (int)date('Y', $ts),
            $gunler[date('l', $ts)]
        );
    }

    private function ayAdi(int $ay): string
    {
        $aylar = [
            1=>'Ocak',2=>'Şubat',3=>'Mart',4=>'Nisan',5=>'Mayıs',6=>'Haziran',
            7=>'Temmuz',8=>'Ağustos',9=>'Eylül',10=>'Ekim',11=>'Kasım',12=>'Aralık'
        ];
        return $aylar[$ay] ?? '';
    }
}
