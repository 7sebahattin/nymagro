<?php
require_once MODELS_PATH . '/NakitModul.php';

class ProjeController extends Controller
{
    private NakitModul $model;

    public function __construct()
    {
        $this->model = new NakitModul();
    }

    public function index(): void
    {
        $this->view('projeler/index', [
            'pageTitle'   => 'Projeler',
            'activeMenu'  => 'projeler',
            'topbarTitle' => 'Projeler',
            'topbarIcon'  => 'fa-solid fa-diagram-project',
            'kayitlar'    => $this->model->projeler(),
            'flash'       => $this->getFlash(),
        ]);
    }

    public function kaydet(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirect('proje');
            }
            $this->model->projeEkle($_POST);
            $this->setFlash('success', 'Proje kaydedildi.');
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }
        $this->redirect('proje');
    }

    public function sil(int $id = 0): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('proje');
        }
        if ($id > 0) {
            $this->model->projeSil($id);
            $this->setFlash('success', 'Proje silindi.');
        }
        $this->redirect('proje');
    }
}
