<?php

class TanimController extends Controller
{
    public function index(): void
    {
        $model = $this->model('Tanim');

        $this->view('tanimlar/index', [
            'pageTitle' => 'Tanımlar',
            'activeMenu' => 'tanimlar',
            'topbarTitle' => 'Tanımlar',
            'topbarIcon' => 'fa-solid fa-gear',
            'types' => $model->types(),
            'grouped' => $model->grouped(),
            'flash' => $this->getFlash(),
        ]);
    }

    public function kaydet(): void
    {
        try {
            $this->model('Tanim')->kaydet($_POST);
            $this->setFlash('success', 'Tanım kaydedildi.');
        } catch (Throwable $e) {
            error_log('Tanım kaydetme hatası: ' . $e->getMessage());
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect('tanim');
    }

    public function sil(int $id = 0): void
    {
        if ($id > 0) {
            $this->model('Tanim')->sil($id);
            $this->setFlash('success', 'Tanım silindi.');
        }

        $this->redirect('tanim');
    }
}
