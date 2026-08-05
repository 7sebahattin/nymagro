<?php

require_once MODELS_PATH . '/CariDokuman.php';

final class DokumanController extends Controller
{
    private CariDokuman $dokumanModel;

    public function __construct()
    {
        $this->dokumanModel = new CariDokuman();
    }

    public function cari(string $tip = 'musteri', $id = 0): void
    {
        $tip = $this->normalizeTip($tip);
        if ($tip === null) {
            $this->redirect('dashboard');
        }

        $cariId = (int)$id;
        $cari = $this->dokumanModel->cariGetir($cariId, $tip);
        if (!$cari) {
            $this->setFlash('error', 'Cari kayıt bulunamadı.');
            $this->redirect($tip === 'musteri' ? 'musteri' : 'tedarikci');
        }

        $this->view('dokuman/cari', [
            'pageTitle' => $tip === 'musteri' ? 'Müşteri Dökümanları' : 'Tedarikçi Dökümanları',
            'activeMenu' => $tip === 'musteri' ? 'musteriler' : 'tedarikciler',
            'topbarIcon' => 'fa-regular fa-file-lines',
            'topbarTitle' => $tip === 'musteri' ? 'Müşteri Dökümanları' : 'Tedarikçi Dökümanları',
            'cari' => $cari,
            'cariTip' => $tip,
            'dokumanlar' => $this->dokumanModel->listele($cariId),
            'flash' => $this->getFlash(),
        ]);
    }

    public function yukle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('dashboard');
        }

        $tip = $this->normalizeTip((string)($_POST['cari_tip'] ?? ''));
        $cariId = (int)($_POST['cari_id'] ?? 0);
        if ($tip === null || $cariId <= 0) {
            $this->setFlash('error', 'Geçersiz cari bilgisi.');
            $this->redirect('dashboard');
        }

        $cari = $this->dokumanModel->cariGetir($cariId, $tip);
        if (!$cari) {
            $this->setFlash('error', 'Cari kayıt bulunamadı.');
            $this->redirect($tip === 'musteri' ? 'musteri' : 'tedarikci');
        }

        $file = $_FILES['dosya'] ?? null;
        if (!$file || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->setFlash('error', 'Yüklenecek dosya seçilemedi.');
            $this->redirectToCari($tip, $cariId);
        }

        $maxSize = 15 * 1024 * 1024;
        if ((int)$file['size'] > $maxSize) {
            $this->setFlash('error', 'Dosya boyutu en fazla 15 MB olabilir.');
            $this->redirectToCari($tip, $cariId);
        }

        $originalName = (string)$file['name'];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv'];
        if (!in_array($ext, $allowed, true)) {
            $this->setFlash('error', 'Bu dosya türü desteklenmiyor.');
            $this->redirectToCari($tip, $cariId);
        }

        $companyId = TenantContext::activeCompanyId();
        $uploadDir = ROOT_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'cari-dokumanlar' . DIRECTORY_SEPARATOR . $companyId . DIRECTORY_SEPARATOR . $cariId;

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            $this->setFlash('error', 'Yükleme klasörü oluşturulamadı.');
            $this->redirectToCari($tip, $cariId);
        }

        $storedName = 'dokuman_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $storedName;

        if (!move_uploaded_file((string)$file['tmp_name'], $targetPath)) {
            $this->setFlash('error', 'Dosya yüklenemedi.');
            $this->redirectToCari($tip, $cariId);
        }

        $relativePath = 'uploads/cari-dokumanlar/' . $companyId . '/' . $cariId . '/' . $storedName;
        $mimeType = '';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mimeType = (string)finfo_file($finfo, $targetPath);
                finfo_close($finfo);
            }
        }

        $this->dokumanModel->ekle([
            'cari_id' => $cariId,
            'baslik' => trim($_POST['baslik'] ?? ''),
            'aciklama' => trim($_POST['aciklama'] ?? ''),
            'orijinal_ad' => $originalName,
            'dosya_ad' => $storedName,
            'dosya_yolu' => $relativePath,
            'mime_type' => $mimeType,
            'boyut' => (int)$file['size'],
        ]);

        $this->setFlash('success', 'Döküman yüklendi.');
        $this->redirectToCari($tip, $cariId);
    }

    public function indir($id = 0): void
    {
        $doc = $this->dokumanModel->getir((int)$id);
        if (!$doc) {
            http_response_code(404);
            exit('Dosya bulunamadı.');
        }

        $publicRoot = realpath(ROOT_PATH . DIRECTORY_SEPARATOR . 'public');
        $filePath = realpath(ROOT_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . ltrim((string)$doc['dosya_yolu'], '/\\'));
        if (!$publicRoot || !$filePath || !$this->isInsidePath($filePath, $publicRoot) || !is_file($filePath)) {
            http_response_code(404);
            exit('Dosya bulunamadı.');
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $downloadName = basename((string)$doc['orijinal_ad']);
        header('Content-Type: ' . ((string)($doc['mime_type'] ?? '') ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . addslashes($downloadName) . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }

    public function sil($id = 0): void
    {
        $doc = $this->dokumanModel->getir((int)$id);
        if (!$doc) {
            $this->setFlash('error', 'Döküman bulunamadı.');
            $this->redirect('dashboard');
        }

        $this->dokumanModel->sil((int)$id);
        $this->setFlash('success', 'Döküman listeden kaldırıldı.');
        $redirectTip = $this->normalizeTip((string)($_GET['tip'] ?? '')) ?? (string)$doc['cari_tip'];
        $this->redirectToCari($redirectTip, (int)$doc['cari_id']);
    }

    private function normalizeTip(string $tip): ?string
    {
        $tip = strtolower(trim($tip));
        return in_array($tip, ['musteri', 'tedarikci'], true) ? $tip : null;
    }

    private function redirectToCari(string $tip, int $cariId): void
    {
        $tip = $this->normalizeTip($tip) ?? 'musteri';
        $this->redirect('dokuman/cari/' . $tip . '/' . $cariId);
    }

    private function isInsidePath(string $path, string $root): bool
    {
        $path = str_replace('\\', '/', $path);
        $root = rtrim(str_replace('\\', '/', $root), '/');
        return $path === $root || str_starts_with($path, $root . '/');
    }
}
