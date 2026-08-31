<?php
/**
 * Controller: DepoController
 */

require_once MODELS_PATH . '/Depo.php';

class DepoController extends Controller
{
    private Depo $depo;

    public function __construct()
    {
        $this->depo = new Depo();
    }

    public function index()
    {
        $depolar = $this->depo->listele();

        // Hizmet / stok takibi kapalı kalemlerde hatalı stok var mı?
        // (Bu kayıtlar artık oluşmuyor — bkz. Urun::stokHareketiEkle() — ama
        // düzeltmeden ÖNCE oluşmuş olanlar veride kalmış olabilir.)
        require_once MODELS_PATH . '/Urun.php';
        $urunModel = new Urun();
        $hataliStoklar = [];
        try {
            $hataliStoklar = $urunModel->stokTakipDisiHataliKayitlar();
        } catch (Throwable $e) {
            error_log('[NYMAGRO] Hatalı stok taraması başarısız: ' . $e->getMessage());
        }

        $this->view('depolar/index', [
            'depolar'       => $depolar,
            'hataliStoklar' => $hataliStoklar,
            'topbarTitle'   => 'Depo Yönetimi',
            'topbarIcon'    => 'fa-solid fa-warehouse',
            'activeMenu'    => 'depolar',
            'flash'         => $this->getFlash()
        ]);
    }

    /**
     * Hizmet / stok takibi kapalı kalemlerdeki hatalı stok kayıtlarını onarır.
     *
     * POST + CSRF ile korunur (veri değiştiren bir işlem). Yetki: DEPO_UPDATE
     * (Rbac::classifyAction 'stokOnar' adını VIEW sayacağı için burada açıkça
     * aranır — bu uç stok bakiyelerini değiştirir).
     */
    public function stokOnar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('depo');
        }
        if (!Rbac::currentUserCan('DEPO_UPDATE')) {
            http_response_code(403);
            die('Stok onarımı için yetkiniz yok.');
        }

        require_once MODELS_PATH . '/Urun.php';
        $urunModel = new Urun();
        try {
            $sonuc = $urunModel->stokTakipDisiKayitlariOnar();
            if ($sonuc['onarilan'] === 0) {
                $this->setFlash('success', 'Düzeltilecek hatalı stok kaydı bulunamadı.');
            } else {
                $this->setFlash('success', sprintf(
                    '%d kalem onarıldı: %s',
                    $sonuc['onarilan'],
                    implode(' · ', array_slice($sonuc['detay'], 0, 5))
                ));
            }
        } catch (Throwable $e) {
            $this->setFlash('error', 'Onarım sırasında hata: ' . $e->getMessage());
        }
        $this->redirect('depo');
    }

    public function kaydet()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('depo');
        }
        {
            $ad = trim($_POST['ad'] ?? '');
            if ($ad !== '') {
                $this->depo->ekle([
                    'ad'      => $ad,
                    'adres'   => trim($_POST['adres'] ?? ''),
                    'yetkili' => trim($_POST['yetkili'] ?? ''),
                    'telefon' => trim($_POST['telefon'] ?? '')
                ]);
                $this->setFlash('success', 'Depo başarıyla oluşturuldu.');
            } else {
                $this->setFlash('error', 'Depo adı boş olamaz.');
            }
        }
        $this->redirect('depo');
    }

    public function sil(int $id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('depo');
        }
        // Ana Depo (ID: 1) silinemesin
        if ($id === 1) {
            $this->setFlash('error', 'Ana Depo silinemez.');
        } else {
            try {
                $this->depo->sil($id);
                $this->setFlash('success', 'Depo silindi.');
            } catch (RuntimeException $e) {
                $this->setFlash('error', $e->getMessage());
            }
        }
        $this->redirect('depo');
    }

    public function detay(int $id)
    {
        $depo = $this->depo->getir($id);
        if (!$depo) {
            $this->setFlash('error', 'Depo bulunamadı.');
            $this->redirect('depo');
        }

        $stoklar = $this->depo->stokDurumu($id);
        $toplamDeger = $this->depo->depoDegeri($id);

        $this->view('depolar/detay', [
            'depo'         => $depo,
            'stoklar'      => $stoklar,
            'toplamDeger'  => $toplamDeger,
            'topbarTitle'  => $depo['ad'] . ' Detayı',
            'topbarIcon'   => 'fa-solid fa-warehouse',
            'activeMenu'   => 'depolar',
            'flash'        => $this->getFlash()
        ]);
    }

    public function guncelle(int $id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('depo/detay/' . $id);
        }
        {
            $this->depo->guncelle($id, [
                'ad'      => trim($_POST['ad']),
                'adres'   => trim($_POST['adres']),
                'yetkili' => trim($_POST['yetkili']),
                'telefon' => trim($_POST['telefon'])
            ]);
            $this->setFlash('success', 'Depo bilgileri güncellendi.');
        }
        $this->redirect('depo/detay/' . $id);
    }

    /** Stok Sayımı Ekranı */
    public function sayim(int $id)
    {
        $depo = $this->depo->getir($id);
        if (!$depo) {
            $this->setFlash('error', 'Depo bulunamadı.');
            $this->redirect('depo');
        }

        $stoklar = $this->depo->stokDurumu($id);

        $this->view('depolar/sayim', [
            'depo'        => $depo,
            'stoklar'     => $stoklar,
            'topbarTitle' => 'Stok Sayımı: ' . $depo['ad'],
            'topbarIcon'  => 'fa-solid fa-calculator',
            'activeMenu'  => 'depolar'
        ]);
    }

    /** Stok Sayımı Kaydet */
    public function sayimKaydet(int $id)
    {
        $depo = $this->depo->getir($id);
        if (!$depo) {
            $this->setFlash('error', 'Depo bulunamadı.');
            $this->redirect('depo');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('depo/detay/' . $id);
        }
        {
            $sayimlar = $_POST['sayim'] ?? []; // [urun_id => miktar]

            require_once MODELS_PATH . '/Urun.php';
            $uModel = new Urun();

            foreach ($sayimlar as $urunId => $yeniMiktar) {
                $urunId = (int)$urunId;
                $mevcut = $this->depo->urunStokMiktari($urunId, $id);
                $fark = (float)$yeniMiktar - $mevcut;

                if ($fark != 0) {
                    $tip = $fark > 0 ? 'giris' : 'cikis';
                    $uModel->stokHareketiEkle($urunId, abs($fark), $tip, 'Stok Sayımı Farkı', $id);
                }
            }

            $this->setFlash('success', 'Stok sayımı başarıyla kaydedildi.');
        }
        $this->redirect('depo/detay/' . $id);
    }
}
