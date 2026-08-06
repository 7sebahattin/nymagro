<?php
require_once MODELS_PATH . '/NakitModul.php';

class PortfoyController extends Controller
{
    private NakitModul $model;

    public function __construct()
    {
        $this->model = new NakitModul();
    }

    public function index(): void
    {
        $tip = $this->currentTip();
        $label = $tip === 'cek' ? 'Çek Portföyü' : 'Senet Portföyü';
        $this->view('portfoy/index', [
            'pageTitle'   => $label,
            'activeMenu'  => $tip === 'cek' ? 'cek_portfoyu' : 'senet_portfoyu',
            'topbarTitle' => $label,
            'topbarIcon'  => $tip === 'cek' ? 'fa-solid fa-money-check' : 'fa-solid fa-file-invoice-dollar',
            'tip'         => $tip,
            'label'       => $label,
            'kayitlar'    => $this->model->portfoy($tip),
            'cariler'     => $this->model->cariler(),
            'flash'       => $this->getFlash(),
        ]);
    }

    public function kaydet(): void
    {
        $tip = $this->currentTip();
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirect($tip);
            }
            $this->model->portfoyEkle($tip, $_POST);
            $this->setFlash('success', ($tip === 'cek' ? 'Çek' : 'Senet') . ' kaydedildi.');
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }
        $this->redirect($tip);
    }

    public function sil(int $id = 0): void
    {
        $tip = $this->currentTip();
        if ($id > 0) {
            $this->model->portfoySil($id);
            $this->setFlash('success', 'Kayıt silindi.');
        }
        $this->redirect($tip);
    }

    public function durum(int $id = 0): void
    {
        $tip = $this->currentTip();
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) {
                $this->model->portfoyDurumGuncelle($id, (string)($_POST['durum'] ?? ''));
                $this->setFlash('success', 'Durum güncellendi.');
            }
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }
        $this->redirect($tip);
    }

    private function currentTip(): string
    {
        $section = strtolower(trim((string)($_GET['url'] ?? ''), '/'));
        return str_starts_with($section, 'senet') ? 'senet' : 'cek';
    }
}
