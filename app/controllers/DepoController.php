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

        $this->view('depolar/index', [
            'depolar'     => $depolar,
            'topbarTitle' => 'Depo Yönetimi',
            'topbarIcon'  => 'fa-solid fa-warehouse',
            'activeMenu'  => 'depolar',
            'flash'       => $this->getFlash()
        ]);
    }

    public function kaydet()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
