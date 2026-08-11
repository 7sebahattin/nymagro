<?php
require_once MODELS_PATH . '/NakitModul.php';
require_once MODELS_PATH . '/KasaHesap.php';

class KrediController extends Controller
{
    private NakitModul $model;

    public function __construct()
    {
        $this->model = new NakitModul();
    }

    public function index()
    {
        $this->view('krediler/index', [
            'pageTitle'   => 'Krediler',
            'activeMenu'  => 'krediler',
            'topbarTitle' => 'Krediler',
            'topbarIcon'  => 'fa-solid fa-building-columns',
            'kayitlar'    => $this->model->krediler(),
            'hesaplar'    => (new KasaHesap())->hepsini(),
            'flash'       => $this->getFlash(),
        ]);
    }

    public function kaydet(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirect('kredi');
            }
            $this->model->krediEkle($_POST);
            $this->setFlash('success', 'Kredi ve ödeme planı kaydedildi.');
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }
        $this->redirect('kredi');
    }

    public function sil(int $id = 0): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('kredi');
        }
        if ($id > 0) {
            $this->model->krediSil($id);
            $this->setFlash('success', 'Kredi silindi.');
        }
        $this->redirect('kredi');
    }

    public function detay(int $id): void
    {
        $kredi = $this->model->krediGetir($id);
        if (!$kredi) {
            $this->setFlash('error', 'Kredi bulunamadı.');
            $this->redirect('kredi');
        }

        $this->view('krediler/detay', [
            'pageTitle'   => 'Kredi Detayı',
            'activeMenu'  => 'krediler',
            'topbarTitle' => $kredi['ad'],
            'topbarIcon'  => 'fa-solid fa-building-columns',
            'kredi'       => $kredi,
            'taksitler'   => $this->model->taksitler($id),
            'flash'       => $this->getFlash(),
        ]);
    }

    public function taksitOde(int $id = 0): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('kredi');
        }
        try {
            if ($id > 0) {
                $this->model->taksitOde($id);
                $this->setFlash('success', 'Taksit ödendi olarak işaretlendi.');
            }
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }
        $krediId = (int)($_POST['kredi_id'] ?? $_GET['kredi_id'] ?? 0);
        $this->redirect($krediId > 0 ? 'kredi/detay/' . $krediId : 'kredi');
    }
}
