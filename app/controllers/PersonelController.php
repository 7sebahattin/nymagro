<?php

class PersonelController extends Controller
{
    public function index(): void
    {
        $model = $this->model('Personel');

        $this->view('personel/index', [
            'pageTitle' => 'Personel',
            'activeMenu' => 'personel',
            'topbarTitle' => 'Personel',
            'topbarIcon' => 'fa-solid fa-user-tie',
            'personeller' => $model->listele(),
            'hareketler' => $model->sonHareketler(),
            'hesaplar' => $model->hesaplar(),
            'flash' => $this->getFlash(),
        ]);
    }

    public function ekle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('personel');
        }
        try {
            $this->model('Personel')->ekle($_POST);
            $this->setFlash('success', 'Personel kaydı eklendi.');
        } catch (Throwable $e) {
            error_log('Personel ekleme hatası: ' . $e->getMessage());
            $this->setFlash('error', $e->getMessage());
        }
        $this->redirect('personel');
    }

    public function sil(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('personel');
        }
        if ($this->model('Personel')->sil($id) > 0) {
            $this->setFlash('success', 'Personel kaydı silindi.');
        } else {
            $this->setFlash('error', 'Personel bulunamadı.');
        }
        $this->redirect('personel');
    }

    public function hareket_ekle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('personel');
        }
        try {
            $this->model('Personel')->hareketEkle($_POST);
            $this->setFlash('success', 'Personel hareketi eklendi.');
        } catch (Throwable $e) {
            error_log('Personel hareket ekleme hatası: ' . $e->getMessage());
            $this->setFlash('error', $e->getMessage());
        }
        $this->redirect('personel');
    }

    public function hareket_sil(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('personel');
        }
        $this->model('Personel')->hareketSil($id);
        $this->setFlash('success', 'Personel hareketi silindi.');
        $this->redirect('personel');
    }
}
