<?php
require_once MODELS_PATH . '/NakitModul.php';

class DemirbasController extends Controller
{
    private NakitModul $model;

    public function __construct()
    {
        $this->model = new NakitModul();
    }

    public function index(): void
    {
        $this->view('demirbaslar/index', [
            'pageTitle'   => 'Demirbaşlar',
            'activeMenu'  => 'demirbaslar',
            'topbarTitle' => 'Demirbaşlar',
            'topbarIcon'  => 'fa-solid fa-chair',
            'kayitlar'    => $this->model->demirbaslar(),
            'flash'       => $this->getFlash(),
        ]);
    }

    public function kaydet(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirect('demirbas');
            }
            $this->model->demirbasEkle($_POST);
            $this->setFlash('success', 'Demirbaş kaydedildi.');
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }
        $this->redirect('demirbas');
    }

    public function sil(int $id = 0): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('demirbas');
        }
        if ($id > 0) {
            $this->model->demirbasSil($id);
            $this->setFlash('success', 'Demirbaş silindi.');
        }
        $this->redirect('demirbas');
    }
}
