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

    /** Diğer formlardan ("yeni kategori/marka/sınıflandırma ekle" vb.) sayfa yenilenmeden tanım eklemek için. */
    public function kaydetAjax(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $id = $this->model('Tanim')->kaydet($_POST);
            echo json_encode(['success' => true, 'id' => $id, 'ad' => trim((string)($_POST['ad'] ?? ''))]);
        } catch (Throwable $e) {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
}
