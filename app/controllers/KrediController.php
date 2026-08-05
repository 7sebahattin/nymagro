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
        if ($id > 0) {
            $this->model->krediSil($id);
            $this->setFlash('success', 'Kredi silindi.');
        }
        $this->redirect('kredi');
    }
}
