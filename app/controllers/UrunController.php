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

final class UrunController extends Controller
{
    private Urun $urun;

    public function __construct()
    {
        $this->urun = new Urun();
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

        $stokKodu = trim($_POST['stok_kodu'] ?? '');
        if ($stokKodu !== '' && $this->urun->kodMevcutMu('stok_kodu', $stokKodu)) {
            $hatalar['stok_kodu'] = 'Bu stok kodu zaten kullanılıyor.';
        }

        $barkod = trim($_POST['barkod'] ?? '');
        if ($barkod !== '' && $this->urun->kodMevcutMu('barkod', $barkod)) {
            $hatalar['barkod'] = 'Bu barkod zaten kullanılıyor.';
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
            ]);
            return;
        }

        // ── Kaydet ─────────────────────────────────────
        $acilisStogu = (float)($_POST['stok_miktari'] ?? 0);
        
        $veri = [
            'tip'          => $tip,
            'ad'           => $ad,
            'stok_kodu'    => $stokKodu ?: null,
            'barkod'       => $barkod   ?: null,
            'birim'        => trim($_POST['birim']    ?? 'Adet'),
            'satis_fiyati' => $satisFiyati,
            'alis_fiyati'  => $alisFiyati,
            'kdv_orani'    => $kdvOrani,
            'para_birimi'  => trim($_POST['para_birimi'] ?? 'TRY'),
            'stok_miktari' => 0, // Başlangıçta 0, hareket ile eklenecek
            'kritik_stok'  => (float)($_POST['kritik_stok']  ?? 0),
            'kategori'     => trim($_POST['kategori'] ?? '') ?: null,
            'marka'        => trim($_POST['marka']    ?? '') ?: null,
            'aciklama'     => trim($_POST['aciklama'] ?? '') ?: null,
        ];

        $id = $this->urun->ekle($veri);

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
            ]);
            return;
        }

        $satisFiyati = is_numeric(str_replace(',', '.', $_POST['satis_fiyati'] ?? '0')) ? (float)str_replace(',', '.', $_POST['satis_fiyati'] ?? '0') : 0.0;
        $alisFiyati = is_numeric(str_replace(',', '.', $_POST['alis_fiyati'] ?? '0')) ? (float)str_replace(',', '.', $_POST['alis_fiyati'] ?? '0') : 0.0;
        $kdvOrani = (float)($_POST['kdv_orani'] ?? 20);

        $veri = [
            'tip'          => $_POST['tip'] ?? 'urun',
            'ad'           => $ad,
            'stok_kodu'    => $stokKodu ?: null,
            'barkod'       => $barkod ?: null,
            'birim'        => trim($_POST['birim'] ?? 'Adet'),
            'satis_fiyati' => $satisFiyati,
            'alis_fiyati'  => $alisFiyati,
            'kdv_orani'    => $kdvOrani,
            'para_birimi'  => trim($_POST['para_birimi'] ?? 'TRY'),
            'stok_miktari' => (float)($_POST['stok_miktari'] ?? 0),
            'kritik_stok'  => (float)($_POST['kritik_stok'] ?? 0),
            'kategori'     => trim($_POST['kategori'] ?? '') ?: null,
            'marka'        => trim($_POST['marka'] ?? '') ?: null,
            'aciklama'     => trim($_POST['aciklama'] ?? '') ?: null,
        ];

        $this->urun->guncelle($id, $veri);

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
            $miktar   = (float)($_POST['miktar'] ?? 0);
            $aciklama = trim($_POST['aciklama'] ?? '');
            $depoId   = (int)($_POST['depo_id'] ?? 0);

            if ($miktar > 0 && $depoId > 0) {
                $this->urun->stokHareketiEkle($id, $miktar, 'giris', $aciklama, $depoId);
                $this->setFlash('success', 'Stok girişi başarıyla yapıldı.');
            } else {
                $this->setFlash('error', 'Geçerli bir miktar ve depo seçiniz.');
            }
        }
        $this->redirect("urun/detay/{$id}");
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
        $kayit = $this->urun->getir($id);
        if ($kayit) {
            $this->urun->sil($id);
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