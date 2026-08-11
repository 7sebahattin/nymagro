<?php

require_once MODELS_PATH . '/Fihrist.php';
require_once MODELS_PATH . '/Tanim.php';

class FihristController extends Controller
{
    private Fihrist $fihrist;

    public function __construct()
    {
        $this->fihrist = new Fihrist();
    }

    public function index()
    {
        $tanim = new Tanim();
        $this->view('fihrist/index', [
            'pageTitle'   => 'Fihrist',
            'activeMenu'  => 'fihrist',
            'topbarTitle' => 'Fihrist',
            'topbarIcon'  => 'fa-solid fa-address-book',
            'kayitlar'    => $this->fihrist->listele(),
            'grouped'     => $tanim->grouped(),
            'flash'       => $this->getFlash(),
        ]);
    }

    public function kaydet(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('fihrist');
        }
        try {
            $this->fihrist->kaydet($_POST);
            $this->setFlash('success', 'Kart kaydedildi.');
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }
        $this->redirect('fihrist');
    }

    public function sil(int $id = 0): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('fihrist');
        }
        if ($id > 0) {
            $this->fihrist->sil($id);
            $this->setFlash('success', 'Kart silindi.');
        }
        $this->redirect('fihrist');
    }
}
