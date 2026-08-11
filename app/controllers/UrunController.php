<?php
/**
 * Controller: UrunController
 * --------------------------------------------------------
 * URL eşleşmeleri (Router tablosunda 'urun' => 'UrunController'):
 *   GET  /urun              → index()
 *   GET  /urun/ekle         → ekle()
 *   POST /urun/kaydet       → kaydet()
 *   GET  /urun/sil/{id}     → sil($id)
 */

require_once MODELS_PATH . '/Urun.php';
require_once MODELS_PATH . '/Varyant.php';
require_once MODELS_PATH . '/Tanim.php';
require_once MODELS_PATH . '/SiteIcerik.php';
require_once MODELS_PATH . '/Company.php';

final class UrunController extends Controller
{
    private const ALLOWED_IMG = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private const MAX_BYTES   = 5 * 1024 * 1024; // 5 MB

    private Urun $urun;

    public function __construct()
    {
        $this->urun = new Urun();
    }

    /** "Koli İçi Adet Sayısı" formundan gelen değeri doğrular. Boş/geçersiz/0 → null (koli modu kapalı). */
    private function parseKoliIciAdet(?string $raw): ?float
    {
        $raw = str_replace(',', '.', trim((string)$raw));
        if ($raw === '' || !is_numeric($raw)) {
            return null;
        }
        $deger = (float)$raw;
        return $deger > 0 ? $deger : null;
    }

    /** Ürün ana görselini yükler, public path döner (aynı doğrulama SiteController::dosyaYukle ile). */
    private function anaGorselYukle(array $file): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new Exception('Görsel yükleme hatası: ' . ($file['error'] ?? '?'));
        }
        if (($file['size'] ?? 0) > self::MAX_BYTES) {
            throw new Exception('Görsel 5 MB sınırını aşıyor.');
        }
        $uzanti = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($uzanti, self::ALLOWED_IMG, true)) {
            throw new Exception('Geçersiz görsel türü: ' . $uzanti);
        }
        if (function_exists('mime_content_type')) {
            $mime = (string)@mime_content_type($file['tmp_name']);
            $okMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if ($mime !== '' && !in_array($mime, $okMimes, true)) {
                throw new Exception('Dosya içeriği geçerli bir görsel değil.');
            }
        }
        if (function_exists('getimagesize') && @getimagesize($file['tmp_name']) === false) {
            throw new Exception('Dosya içeriği okunabilir bir görsel değil.');
        }

        $hedefKlasor = ROOT_PATH . DIRECTORY_SEPARATOR . 'public'
                     . DIRECTORY_SEPARATOR . 'uploads'
                     . DIRECTORY_SEPARATOR . 'urunler' . DIRECTORY_SEPARATOR;
        if (!is_dir($hedefKlasor)) mkdir($hedefKlasor, 0755, true);

        $yeniAd = 'urun_' . uniqid() . '.' . $uzanti;
        $hedef  = $hedefKlasor . $yeniAd;
        if (!move_uploaded_file($file['tmp_name'], $hedef)) {
            throw new Exception('Görsel kaydedilemedi.');
        }
        return 'uploads/urunler/' . $yeniAd;
    }

    /** Aktif şirket, nymagro.com ile ürün senkronu yapılan "vitrin şirketi" mi? */
    private function isStorefrontCompany(): bool
    {
        $storefrontId = (new Company())->storefrontCompanyId();
        return $storefrontId !== null && $storefrontId === TenantContext::activeCompanyId();
    }

    /**
     * Kaydedilmiş bir panel ürününü, "e-ticaret'te göster" durumuna göre
     * site vitrinine bağlar/çözer. Yalnızca aktif şirket "vitrin şirketi"
     * ise bir şey yapar — diğer şirketlerde nymagro.com'a hiç dokunulmaz.
     */
    private function siteVitriniSenkronla(int $urunId, bool $eticaret): void
    {
        if (!$this->isStorefrontCompany()) {
            return;
        }

        $panelUrun = $this->urun->getir($urunId);
        if (!$panelUrun) {
            return;
        }

        $siteModel = new SiteIcerik();
        if ($eticaret) {
            $siteUrunId = $siteModel->syncFromPanelUrun($panelUrun);
            if ($siteUrunId > 0 && (int)($panelUrun['site_urun_id'] ?? 0) !== $siteUrunId) {
                $this->urun->guncelle($urunId, ['site_urun_id' => $siteUrunId]);
            }
        } elseif (!empty($panelUrun['site_urun_id'])) {
            $siteModel->unsyncPanelUrun($urunId);
        }
    }

    // ─── index ──────────────────────────────────────────────────────────

    public function index(): void
    {
        $limit    = 50;
        $sayfa    = max(1, (int)($_GET['sayfa']   ?? 1));
        $arama    = trim($_GET['ara']             ?? '');
        $tipFlt   = trim($_GET['tip']             ?? '');
        $katFlt   = trim($_GET['kategori']        ?? '');
        $markaFlt = trim($_GET['marka']           ?? '');

        // Arama en az 3 karakter
        if (mb_strlen($arama) > 0 && mb_strlen($arama) < 3) {
            $arama = '';
        }

        $offset     = ($sayfa - 1) * $limit;
        $toplam     = $this->urun->say($arama, $tipFlt, $katFlt, $markaFlt);
        $sayfaSayisi = (int)ceil($toplam / $limit);
        $urunler    = $this->urun->listele($arama, $tipFlt, $katFlt, $markaFlt, $limit, $offset);
        $kategoriler = $this->urun->kategoriler();
        $markalar    = $this->urun->markalar();

        $this->view('urunler/index', [
            'urunler'     => $urunler,
            'toplam'      => $toplam,
            'arama'       => $arama,
            'tipFlt'      => $tipFlt,
            'katFlt'      => $katFlt,
            'markaFlt'    => $markaFlt,
            'kategoriler' => $kategoriler,
            'markalar'    => $markalar,
            'sayfa'       => $sayfa,
            'sayfaSayisi' => $sayfaSayisi,
            'limit'       => $limit,
            'flash'       => $this->getFlash(),
            'topbarTitle' => 'Ürün / Hizmet Tanımları',
            'topbarIcon'  => 'fa-tags',
            'activeMenu'  => 'urunler',
        ]);
    }

    // ─── ekle ───────────────────────────────────────────────────────────

    public function ekle(): void
    {
        $vModel = new Varyant();
        $eski = [];
        
        $copyId = (int)($_GET['copy_id'] ?? 0);
        if ($copyId > 0) {
            $source = $this->urun->getir($copyId);
            if ($source) {
                $eski = $source;
                $eski['ad'] = 'Kopya ' . $source['ad'];
                // Stok kodu ve barkodu temizle (çünkü benzersiz olmalılar)
                $eski['stok_kodu'] = '';
                $eski['barkod'] = '';
                // Mevcut stok miktarını kopyalamıyoruz, yeni ürün 0 stokla başlar
                $eski['stok_miktari'] = 0;
            }
        }

        $tanimGrouped = (new Tanim())->grouped();

        $this->view('urunler/ekle', [
            'eski'            => $eski,
            'hatalar'         => [],
            'varyantlar'      => $vModel->tumunuGetir(),
            'tanimKategoriler' => $tanimGrouped['urun_kategori'] ?? [],
            'tanimMarkalar'    => $tanimGrouped['urun_marka'] ?? [],
            'topbarTitle' => 'Yeni Ürün / Hizmet Ekle',
            'topbarIcon'  => 'fa-box-open',
            'activeMenu'  => 'urunler',
            'isStorefront' => $this->isStorefrontCompany(),
        ]);
    }

    // ─── kaydet (POST) ──────────────────────────────────────────────────

    public function kaydet(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('urun/ekle');
        }

        $eski    = $_POST;
        $hatalar = [];

        // ── Doğrulama ──────────────────────────────────
        $ad = trim($_POST['ad'] ?? '');
        if ($ad === '') {
            $hatalar['ad'] = 'Ürün adı zorunludur.';
        }

        $tip = $_POST['tip'] ?? 'urun';
        if (!in_array($tip, ['urun', 'hizmet'])) {
            $tip = 'urun';
        }

        $satisFiyati = str_replace(',', '.', trim($_POST['satis_fiyati'] ?? '0'));
        $satisFiyati = is_numeric($satisFiyati) ? (float)$satisFiyati : 0.0;
        if ($satisFiyati < 0) {
            $hatalar['satis_fiyati'] = 'Satış fiyatı negatif olamaz.';
        }

        $alisFiyati = str_replace(',', '.', trim($_POST['alis_fiyati'] ?? '0'));
        $alisFiyati = is_numeric($alisFiyati) ? (float)$alisFiyati : 0.0;

        $kdvOrani = (float)($_POST['kdv_orani'] ?? 20);
        if (!in_array((int)$kdvOrani, [0, 1, 8, 10, 18, 20])) {
            $kdvOrani = 20;
        }
        $kdvDahil = !empty($_POST['kdv_dahil']) && $_POST['kdv_dahil'] === '1' ? 1 : 0;

        $alisParaBirimi = trim($_POST['alis_para_birimi'] ?? 'TRY');
        if (!in_array($alisParaBirimi, ['TRY', 'USD', 'EUR', 'GBP'], true)) {
            $alisParaBirimi = 'TRY';
        }
        $alisKdvOrani = (float)($_POST['alis_kdv_orani'] ?? 20);
        if (!in_array((int)$alisKdvOrani, [0, 1, 8, 10, 18, 20])) {
            $alisKdvOrani = 20;
        }
        $alisIskonto = is_numeric(str_replace(',', '.', $_POST['alis_iskonto'] ?? '0'))
            ? (float)str_replace(',', '.', $_POST['alis_iskonto'] ?? '0') : 0.0;
        $alisIskonto = max(0.0, min(100.0, $alisIskonto));

        $stokKodu = trim($_POST['stok_kodu'] ?? '');
        if ($stokKodu !== '' && $this->urun->kodMevcutMu('stok_kodu', $stokKodu)) {
            $hatalar['stok_kodu'] = 'Bu stok kodu zaten kullanılıyor.';
        }

        $barkod = trim($_POST['barkod'] ?? '');
        if ($barkod !== '' && $this->urun->kodMevcutMu('barkod', $barkod)) {
            $hatalar['barkod'] = 'Bu barkod zaten kullanılıyor.';
        }

        $resimYolu = null;
        if (!empty($_FILES['resim_ana']['name'])) {
            try {
                $resimYolu = $this->anaGorselYukle($_FILES['resim_ana']);
            } catch (Exception $e) {
                $hatalar['resim_ana'] = $e->getMessage();
            }
        }

        // ── Hata varsa formu tekrar göster ─────────────
        if (!empty($hatalar)) {
            $vModel = new Varyant();
            $tanimGrouped = (new Tanim())->grouped();
            $this->view('urunler/ekle', [
                'eski'        => $eski,
                'hatalar'     => $hatalar,
                'varyantlar'  => $vModel->tumunuGetir(),
                'tanimKategoriler' => $tanimGrouped['urun_kategori'] ?? [],
                'tanimMarkalar'    => $tanimGrouped['urun_marka'] ?? [],
                'topbarTitle' => 'Yeni Ürün / Hizmet Ekle',
                'topbarIcon'  => 'fa-box-open',
                'activeMenu'  => 'urunler',
                'isStorefront' => $this->isStorefrontCompany(),
            ]);
            return;
        }

        // ── Kaydet ─────────────────────────────────────
        $acilisStogu = (float)($_POST['stok_miktari'] ?? 0);
        $eticaret = !empty($_POST['eticaret']);

        $veri = [
            'tip'          => $tip,
            'ad'           => $ad,
            'stok_kodu'    => $stokKodu ?: null,
            'barkod'       => $barkod   ?: null,
            'birim'        => trim($_POST['birim']    ?? 'Adet'),
            'satis_fiyati' => $satisFiyati,
            'alis_fiyati'  => $alisFiyati,
            'kdv_orani'    => $kdvOrani,
            'kdv_dahil'    => $kdvDahil,
            'para_birimi'  => trim($_POST['para_birimi'] ?? 'TRY'),
            'alis_para_birimi' => $alisParaBirimi,
            'alis_kdv_orani'   => $alisKdvOrani,
            'alis_iskonto'     => $alisIskonto,
            'stok_miktari' => 0, // Başlangıçta 0, hareket ile eklenecek
            'kritik_stok'  => (float)($_POST['kritik_stok']  ?? 0),
            'koli_ici_adet' => $this->parseKoliIciAdet($_POST['koli_ici_adet'] ?? null),
            'kategori'     => trim($_POST['kategori'] ?? '') ?: null,
            'marka'        => trim($_POST['marka']    ?? '') ?: null,
            'aciklama'     => trim($_POST['aciklama'] ?? '') ?: null,
            'resim_yolu'   => $resimYolu,
            'eticaret'     => $eticaret,
            'stok_takibi'    => in_array($_POST['stok_takibi'] ?? '', ['normal', 'seri', 'lot', 'yok'], true) ? $_POST['stok_takibi'] : 'normal',
            'fatura_basligi' => trim($_POST['fatura_basligi'] ?? '') ?: null,
            'gtip'           => trim($_POST['gtip'] ?? '') ?: null,
        ];

        $id = $this->urun->ekle($veri);

        $this->siteVitriniSenkronla($id, $eticaret);

        // Eğer açılış stoğu girilmişse, varsayılan olarak ANA DEPO'ya (ID: 1) giriş yap
        if ($acilisStogu > 0) {
            $this->urun->stokHareketiEkle($id, $acilisStogu, 'giris', 'Açılış Stoğu (Sistem Tarafından Atandı)', 1);
        }

        // Varyantları kaydet
        if (!empty($_POST['varyant_degerleri'])) {
            $vModel = new Varyant();
            $vModel->urunVaryantlariniKaydet($id, $_POST['varyant_degerleri']);
        }

        $this->setFlash('success', '"' . htmlspecialchars($ad) . '" başarıyla eklendi.');
        $this->redirect('urun');
    }

    /** AJAX: Hızlı Ürün Ekle */
    public function hizli_ekle(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Geçersiz istek.']);
            return;
        }

        $ad = trim($_POST['ad'] ?? '');
        if ($ad === '') {
            echo json_encode(['status' => 'error', 'message' => 'Ürün adı zorunludur.']);
            return;
        }

        $satisFiyati = (float)str_replace(',', '.', $_POST['satis_fiyati'] ?? '0');
        $kdvOrani    = (float)($_POST['kdv_orani'] ?? 20);
        $birim       = trim($_POST['birim'] ?? 'Adet');

        $id = $this->urun->ekle([
            'ad'           => $ad,
            'tip'          => trim($_POST['tip'] ?? 'stoklu'),
            'satis_fiyati' => $satisFiyati,
            'kdv_orani'    => $kdvOrani,
            'birim'        => $birim,
            'stok_miktari' => 0,
            'para_birimi'  => 'TRY',
            'marka'        => trim($_POST['marka'] ?? '') ?: null,
            'kategori'     => trim($_POST['kategori'] ?? '') ?: null,
            'stok_kodu'    => trim($_POST['urun_kodu'] ?? '') ?: null,
            'barkod'       => trim($_POST['barkod'] ?? '') ?: null,
        ]);

        if ($id) {
            echo json_encode([
                'status' => 'success',
                'urun' => [
                    'id'           => $id,
                    'ad'           => $ad,
                    'satis_fiyati' => $satisFiyati,
                    'kdv_orani'    => $kdvOrani,
                    'birim'        => $birim
                ]
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Kaydedilemedi.']);
        }
        exit;
    }

    // ─── detay ───────────────────────────────────────────────────────────
    public function detay(int $id): void
    {
        $kayit = $this->urun->getir($id);
        if (!$kayit) {
            $this->setFlash('error', 'Kayıt bulunamadı.');
            $this->redirect('urun');
        }

        $satisGecmisi = $this->urun->satisGecmisi($id);
        $alisGecmisi = $this->urun->alisGecmisi($id);
        $manuelHareketler = $this->urun->stokHareketleri($id);
        $depoStoklari = $this->urun->depoStoklariniGetir($id);

        require_once MODELS_PATH . '/Depo.php';
        $dModel = new Depo();
        $depolar = $dModel->listele();

        $this->view('urunler/detay', [
            'urun'             => $kayit,
            'satisGecmisi'     => $satisGecmisi,
            'alisGecmisi'      => $alisGecmisi,
            'manuelHareketler' => $manuelHareketler,
            'depoStoklari'     => $depoStoklari,
            'depolar'          => $depolar,
            'topbarTitle'      => 'Ürün Detayı: ' . $kayit['ad'],
            'topbarIcon'       => 'fa-box-open',
            'activeMenu'       => 'urunler',
            'flash'            => $this->getFlash()
        ]);
    }

    // ─── duzenle ─────────────────────────────────────────────────────────
    public function duzenle(int $id): void
    {
        $kayit = $this->urun->getir($id);
        if (!$kayit) {
            $this->setFlash('error', 'Kayıt bulunamadı.');
            $this->redirect('urun');
        }

        $vModel = new Varyant();
        $kayit['varyant_degerleri'] = $vModel->urunVaryantlariniGetir($id);
        $tanimGrouped = (new Tanim())->grouped();

        $this->view('urunler/duzenle', [
            'eski'        => $kayit,
            'hatalar'     => [],
            'varyantlar'  => $vModel->tumunuGetir(),
            'tanimKategoriler' => $tanimGrouped['urun_kategori'] ?? [],
            'tanimMarkalar'    => $tanimGrouped['urun_marka'] ?? [],
            'topbarTitle' => 'Ürün / Hizmet Düzenle',
            'topbarIcon'  => 'fa-pen',
            'activeMenu'  => 'urunler',
            'isStorefront' => $this->isStorefrontCompany(),
        ]);
    }

    // ─── guncelle (POST) ─────────────────────────────────────────────────
    public function guncelle(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect("urun/duzenle/{$id}");
        }

        $eski = array_merge(['id' => $id], $_POST);
        $hatalar = [];

        $ad = trim($_POST['ad'] ?? '');
        if ($ad === '') $hatalar['ad'] = 'Ürün adı zorunludur.';

        $stokKodu = trim($_POST['stok_kodu'] ?? '');
        if ($stokKodu !== '' && $this->urun->kodMevcutMu('stok_kodu', $stokKodu, $id)) {
            $hatalar['stok_kodu'] = 'Bu stok kodu zaten kullanılıyor.';
        }

        $barkod = trim($_POST['barkod'] ?? '');
        if ($barkod !== '' && $this->urun->kodMevcutMu('barkod', $barkod, $id)) {
            $hatalar['barkod'] = 'Bu barkod zaten kullanılıyor.';
        }

        $resimYolu = null;
        if (!empty($_FILES['resim_ana']['name'])) {
            try {
                $resimYolu = $this->anaGorselYukle($_FILES['resim_ana']);
            } catch (Exception $e) {
                $hatalar['resim_ana'] = $e->getMessage();
            }
        }

        if (!empty($hatalar)) {
            $vModel = new Varyant();
            $tanimGrouped = (new Tanim())->grouped();
            $this->view('urunler/duzenle', [
                'eski'        => $eski,
                'hatalar'     => $hatalar,
                'varyantlar'  => $vModel->tumunuGetir(),
                'tanimKategoriler' => $tanimGrouped['urun_kategori'] ?? [],
                'tanimMarkalar'    => $tanimGrouped['urun_marka'] ?? [],
                'topbarTitle' => 'Ürün / Hizmet Düzenle',
                'topbarIcon'  => 'fa-pen',
                'activeMenu'  => 'urunler',
                'isStorefront' => $this->isStorefrontCompany(),
            ]);
            return;
        }

        $satisFiyati = is_numeric(str_replace(',', '.', $_POST['satis_fiyati'] ?? '0')) ? (float)str_replace(',', '.', $_POST['satis_fiyati'] ?? '0') : 0.0;
        $alisFiyati = is_numeric(str_replace(',', '.', $_POST['alis_fiyati'] ?? '0')) ? (float)str_replace(',', '.', $_POST['alis_fiyati'] ?? '0') : 0.0;
        $kdvOrani = (float)($_POST['kdv_orani'] ?? 20);
        if (!in_array((int)$kdvOrani, [0, 1, 8, 10, 18, 20])) {
            $kdvOrani = 20;
        }
        $kdvDahil = !empty($_POST['kdv_dahil']) && $_POST['kdv_dahil'] === '1' ? 1 : 0;

        $alisParaBirimi = trim($_POST['alis_para_birimi'] ?? 'TRY');
        if (!in_array($alisParaBirimi, ['TRY', 'USD', 'EUR', 'GBP'], true)) {
            $alisParaBirimi = 'TRY';
        }
        $alisKdvOrani = (float)($_POST['alis_kdv_orani'] ?? 20);
        if (!in_array((int)$alisKdvOrani, [0, 1, 8, 10, 18, 20])) {
            $alisKdvOrani = 20;
        }
        $alisIskonto = is_numeric(str_replace(',', '.', $_POST['alis_iskonto'] ?? '0'))
            ? (float)str_replace(',', '.', $_POST['alis_iskonto'] ?? '0') : 0.0;
        $alisIskonto = max(0.0, min(100.0, $alisIskonto));

        $eticaret = !empty($_POST['eticaret']);

        $veri = [
            'tip'          => $_POST['tip'] ?? 'urun',
            'ad'           => $ad,
            'stok_kodu'    => $stokKodu ?: null,
            'barkod'       => $barkod ?: null,
            'birim'        => trim($_POST['birim'] ?? 'Adet'),
            'satis_fiyati' => $satisFiyati,
            'alis_fiyati'  => $alisFiyati,
            'kdv_orani'    => $kdvOrani,
            'kdv_dahil'    => $kdvDahil,
            'para_birimi'  => trim($_POST['para_birimi'] ?? 'TRY'),
            'alis_para_birimi' => $alisParaBirimi,
            'alis_kdv_orani'   => $alisKdvOrani,
            'alis_iskonto'     => $alisIskonto,
            'kritik_stok'  => (float)($_POST['kritik_stok'] ?? 0),
            'koli_ici_adet' => $this->parseKoliIciAdet($_POST['koli_ici_adet'] ?? null),
            'kategori'     => trim($_POST['kategori'] ?? '') ?: null,
            'marka'        => trim($_POST['marka'] ?? '') ?: null,
            'aciklama'     => trim($_POST['aciklama'] ?? '') ?: null,
            'eticaret'     => $eticaret,
            'stok_takibi'    => in_array($_POST['stok_takibi'] ?? '', ['normal', 'seri', 'lot', 'yok'], true) ? $_POST['stok_takibi'] : 'normal',
            'fatura_basligi' => trim($_POST['fatura_basligi'] ?? '') ?: null,
            'gtip'           => trim($_POST['gtip'] ?? '') ?: null,
        ];
        if ($resimYolu !== null) {
            $veri['resim_yolu'] = $resimYolu;
        }

        $this->urun->guncelle($id, $veri);
        $this->siteVitriniSenkronla($id, $eticaret);

        // Stok miktarı formdan doğrudan ezilmez; farkı stok hareketi olarak
        // (Ana Depo üzerinden) audit-trailed şekilde işleriz.
        $mevcutStok = (float)($this->urun->getir($id)['stok_miktari'] ?? 0);
        $yeniStok = (float)($_POST['stok_miktari'] ?? $mevcutStok);
        $fark = $yeniStok - $mevcutStok;
        if ($fark != 0) {
            $tip = $fark > 0 ? 'giris' : 'cikis';
            $this->urun->stokHareketiEkle($id, abs($fark), $tip, 'Ürün Düzenleme Farkı', 1);
        }

        // Varyantları kaydet
        $vModel = new Varyant();
        $vModel->urunVaryantlariniKaydet($id, $_POST['varyant_degerleri'] ?? []);

        $this->setFlash('success', 'Ürün başarıyla güncellendi.');
        $this->redirect("urun/detay/{$id}");
    }

    // ─── stokGiris (POST) ────────────────────────────────────────────────
    public function stokGiris(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $miktarGirilen = (float)str_replace(',', '.', $_POST['miktar'] ?? '0');
            $girisTipi     = ($_POST['giris_tipi'] ?? 'adet') === 'koli' ? 'koli' : 'adet';
            $aciklama      = trim($_POST['aciklama'] ?? '');
            $depoId        = (int)($_POST['depo_id'] ?? 0);

            // Koli → adet dönüşümü sunucu tarafında yapılır (tek yetkili kaynak);
            // istemci tarafı sadece önizleme içindir.
            $kayit       = $this->urun->getir($id);
            $koliIciAdet = (float)($kayit['koli_ici_adet'] ?? 0);
            $miktarAdet  = $miktarGirilen;

            if ($girisTipi === 'koli' && $koliIciAdet > 0) {
                $miktarAdet = $miktarGirilen * $koliIciAdet;
                $fmtNum = fn(float $n): string => rtrim(rtrim(number_format($n, 3, '.', ''), '0'), '.');
                $not = "{$fmtNum($miktarGirilen)} koli × {$fmtNum($koliIciAdet)} = {$fmtNum($miktarAdet)} adet";
                $aciklama = $aciklama !== '' ? "{$not} — {$aciklama}" : $not;
            }

            if ($miktarAdet > 0 && $depoId > 0) {
                $this->urun->stokHareketiEkle($id, $miktarAdet, 'giris', $aciklama, $depoId);
                $this->setFlash('success', 'Stok girişi başarıyla yapıldı.');
            } else {
                $this->setFlash('error', 'Geçerli bir miktar ve depo seçiniz.');
            }
        }
        $this->redirect("urun/detay/{$id}");
    }

    // ─── stokEkstresi ────────────────────────────────────────────────────
    public function stokEkstresi(int $id): void
    {
        $kayit = $this->urun->getir($id);
        if (!$kayit) {
            $this->setFlash('error', 'Kayıt bulunamadı.');
            $this->redirect('urun');
        }

        $bas = trim($_GET['bas'] ?? '');
        $bit = trim($_GET['bit'] ?? '');

        $hareketler = $this->urun->ekstreHareketleri($id, $bas, $bit);
        $devir      = $this->urun->ekstreDevirBakiyesi($id, $bas);

        // Yürüyen bakiye + toplamlar
        $bakiye = $devir;
        $toplamGiris = 0.0;
        $toplamCikis = 0.0;
        foreach ($hareketler as &$h) {
            $giris = $h['islem_tipi'] === 'giris' ? (float)$h['miktar'] : 0.0;
            $cikis = $h['islem_tipi'] !== 'giris' ? (float)$h['miktar'] : 0.0;

            $bakiye      += $giris - $cikis;
            $toplamGiris += $giris;
            $toplamCikis += $cikis;

            $h['giris']  = $giris;
            $h['cikis']  = $cikis;
            $h['bakiye'] = $bakiye;
        }
        unset($h);

        $this->view('urunler/ekstre', [
            'urun'        => $kayit,
            'hareketler'  => $hareketler,
            'devir'       => $devir,
            'bas'         => $bas,
            'bit'         => $bit,
            'toplamGiris' => $toplamGiris,
            'toplamCikis' => $toplamCikis,
            'sonBakiye'   => $bakiye,
            'topbarTitle' => 'Stok Ekstresi: ' . $kayit['ad'],
            'topbarIcon'  => 'fa-list',
            'activeMenu'  => 'urunler',
            'flash'       => $this->getFlash(),
        ]);
    }

    // ─── VARYANTLAR ──────────────────────────────────────────────────────

    public function varyantlar(): void
    {
        $vModel = new Varyant();
        $varyantlar = $vModel->tumunuGetir();

        $this->view('urunler/varyantlar', [
            'varyantlar'  => $varyantlar,
            'topbarTitle' => 'Ürün Varyantları',
            'topbarIcon'  => 'fa-sliders',
            'activeMenu'  => 'urun_varyantlari',
            'flash'       => $this->getFlash(),
        ]);
    }

    public function varyantEkle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ad = trim($_POST['varyant_ismi'] ?? '');
            if ($ad !== '') {
                $vModel = new Varyant();
                $vModel->varyantEkle($ad);
                $this->setFlash('success', 'Varyant başarıyla eklendi.');
            } else {
                $this->setFlash('error', 'Varyant ismi boş olamaz.');
            }
        }
        $this->redirect('urun/varyantlar');
    }

    public function varyantDegeriEkle(int $varyantId): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $deger = trim($_POST['deger'] ?? '');
            if ($deger !== '') {
                $vModel = new Varyant();
                $vModel->degerEkle($varyantId, $deger);
                $this->setFlash('success', 'Varyant değeri başarıyla eklendi.');
            } else {
                $this->setFlash('error', 'Varyant değeri boş olamaz.');
            }
        }
        $this->redirect('urun/varyantlar');
    }

    // ─── sil ────────────────────────────────────────────────────────────

    public function sil(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('urun');
        }
        $kayit = $this->urun->getir($id);
        if ($kayit) {
            // Önce silmeyi dene (stok kontrolü burada yapılır). Ancak
            // silme reddedilirse siteyle bağlantı koparılmamalı — aksi
            // halde ürün panelde kalmaya devam ederken vitrin senkronundan
            // sessizce kopmuş olurdu.
            try {
                $this->urun->sil($id);
            } catch (RuntimeException $e) {
                $this->setFlash('error', $e->getMessage());
                $this->redirect('urun');
                return;
            }

            // Siteyle bağlıysa bağı kopar; site ürünü yayında kalır ama
            // artık silinmiş panel kaydını referans etmez.
            if (!empty($kayit['site_urun_id'])) {
                try {
                    (new SiteIcerik())->panelBaginiKopar($id);
                    $this->urun->guncelle($id, ['site_urun_id' => null, 'eticaret' => 0]);
                } catch (Throwable $e) {
                    // Site tarafı erişilemezse silme işlemi yine de sürsün.
                }
            }

            $this->setFlash('success', '"' . htmlspecialchars($kayit['ad']) . '" silindi.');
        } else {
            $this->setFlash('error', 'Kayıt bulunamadı.');
        }
        $this->redirect('urun');
    }

    // ──────────────────────────────────────────────────────
    // AJAX: ÜRÜN ARAMA (Kopyalama için)
    // ──────────────────────────────────────────────────────
    public function searchAjax(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $q = trim($_GET['q'] ?? '');
        
        if (mb_strlen($q) < 3) {
            echo json_encode([]);
            exit;
        }

        $results = $this->urun->aramaKopya($q);

        echo json_encode($results);
        exit;
    }
}