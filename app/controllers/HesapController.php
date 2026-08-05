<?php
/**
 * HesapController
 * --------------------------------------------------------
 * Nakit Yönetimi → Hesaplarım modülü.
 * kasa_banka (hesap tanımları) + kasa_hareketleri yönetimi.
 */
require_once MODELS_PATH . '/KasaHesap.php';

class HesapController extends Controller
{
    private KasaHesap $model;

    public function __construct()
    {
        $this->model = new KasaHesap();
    }

    // ──────────────────────────────────────────────────────
    // HESAPLAR LİSTESİ
    // ──────────────────────────────────────────────────────

    public function index(): void
    {
        $grouped = $this->model->grupla();

        $this->view('hesaplar/index', [
            'pageTitle'   => 'Hesaplarım',
            'activeMenu'  => 'hesaplar',
            'topbarTitle' => 'Hesaplarım',
            'topbarIcon'  => 'fa-solid fa-lira-sign',
            'grouped'     => $grouped,
            'flash'       => $this->getFlash(),
        ]);
    }

    // ──────────────────────────────────────────────────────
    // HESAP DETAY
    // ──────────────────────────────────────────────────────

    public function detay(int $id = 0): void
    {
        $hesap = $this->model->getir($id);
        if (!$hesap) {
            $this->setFlash('error', 'Hesap bulunamadı.');
            $this->redirect('hesap');
        }

        $limit      = 100;
        $hareketler = $this->model->hareketler($id, $limit);
        $tumHesaplar = $this->model->hepsini(); // Transfer dropdown için

        $this->view('hesaplar/detay', [
            'pageTitle'    => $hesap['hesap_adi'],
            'activeMenu'   => 'hesaplar',
            'topbarTitle'  => $hesap['hesap_adi'],
            'topbarIcon'   => 'fa-solid fa-lira-sign',
            'hesap'        => $hesap,
            'hareketler'   => $hareketler,
            'tumHesaplar'  => $tumHesaplar,
        ]);
    }

    // ──────────────────────────────────────────────────────
    // AJAX — YENİ HESAP KAYDET
    // ──────────────────────────────────────────────────────

    public function hesapKaydet(): void
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
            return;
        }
        try {
            $id = $this->model->ekle([
                'tip'             => trim($_POST['tip']              ?? 'kasa'),
                'hesap_adi'       => trim($_POST['hesap_adi']        ?? ''),
                'banka_adi'       => trim($_POST['banka_adi']        ?? ''),
                'sube'            => trim($_POST['sube']             ?? ''),
                'hesap_no'        => trim($_POST['hesap_no']         ?? ''),
                'iban'            => trim($_POST['iban']             ?? ''),
                'para_birimi'     => trim($_POST['para_birimi']      ?? 'TRY'),
                'acilis_bakiyesi' => (float)str_replace(',', '.', $_POST['acilis_bakiyesi'] ?? 0),
                'aciklama'        => trim($_POST['aciklama']         ?? ''),
            ]);
            echo json_encode(['success' => true, 'id' => $id, 'message' => 'Hesap başarıyla oluşturuldu.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // ──────────────────────────────────────────────────────
    // AJAX — HESAP GÜNCELLE
    // ──────────────────────────────────────────────────────

    public function hesapGuncelle(): void
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
            return;
        }
        try {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new Exception('Geçersiz hesap ID.');

            $this->model->guncelle($id, [
                'hesap_adi'   => trim($_POST['hesap_adi']   ?? ''),
                'banka_adi'   => trim($_POST['banka_adi']   ?? ''),
                'sube'        => trim($_POST['sube']        ?? ''),
                'hesap_no'    => trim($_POST['hesap_no']    ?? ''),
                'iban'        => trim($_POST['iban']        ?? ''),
                'para_birimi' => trim($_POST['para_birimi'] ?? 'TRY'),
                'aciklama'    => trim($_POST['aciklama']    ?? ''),
            ]);
            echo json_encode(['success' => true, 'message' => 'Hesap güncellendi.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // ──────────────────────────────────────────────────────
    // HESAP SİL
    // ──────────────────────────────────────────────────────

    public function hesapSil(int $id = 0): void
    {
        if ($id > 0) {
            $this->model->sil($id);
            $this->setFlash('success', 'Hesap silindi.');
        }
        $this->redirect('hesap');
    }

    // ──────────────────────────────────────────────────────
    // AJAX — HAREKET KAYDET (Para Giriş / Çıkış)
    // ──────────────────────────────────────────────────────

    public function hareketKaydet(): void
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
            return;
        }
        try {
            $kasaId = (int)($_POST['kasa_id'] ?? 0);
            $tutar  = (float)str_replace(',', '.', $_POST['tutar'] ?? 0);

            if ($kasaId <= 0) throw new Exception('Geçersiz hesap.');
            if ($tutar <= 0)  throw new Exception('Tutar sıfırdan büyük olmalıdır.');

            $id = $this->model->hareketEkle([
                'kasa_id'      => $kasaId,
                'cari_id'      => !empty($_POST['cari_id']) ? (int)$_POST['cari_id'] : null,
                'islem_tipi'   => $_POST['islem_tipi']   ?? 'giris',
                'hareket_tipi' => $_POST['hareket_tipi'] ?? null,
                'tutar'        => $tutar,
                'aciklama'     => trim($_POST['aciklama'] ?? ''),
                'tarih'        => !empty($_POST['tarih']) ? $_POST['tarih'] . ' ' . (date('H:i:s')) : date('Y-m-d H:i:s'),
                'para_birimi'  => $_POST['para_birimi']  ?? 'TRY',
            ]);

            $hesap = $this->model->getir($kasaId);
            echo json_encode([
                'success'      => true,
                'id'           => $id,
                'message'      => 'İşlem kaydedildi.',
                'yeni_bakiye'  => (float)($hesap['guncel_bakiye'] ?? 0),
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // ──────────────────────────────────────────────────────
    // AJAX — TRANSFER
    // ──────────────────────────────────────────────────────

    public function transferKaydet(): void
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
            return;
        }
        try {
            $fromId   = (int)($_POST['from_id']  ?? 0);
            $toId     = (int)($_POST['to_id']    ?? 0);
            $tutar    = (float)str_replace(',', '.', $_POST['tutar'] ?? 0);
            $aciklama = trim($_POST['aciklama']   ?? '');
            $tarih    = trim($_POST['tarih']      ?? '');

            $this->model->transfer($fromId, $toId, $tutar, $aciklama, $tarih);
            echo json_encode(['success' => true, 'message' => 'Transfer gerçekleştirildi.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // ──────────────────────────────────────────────────────
    // AJAX — HAREKET SİL
    // ──────────────────────────────────────────────────────

    public function hareketSil(int $id = 0): void
    {
        header('Content-Type: application/json');
        try {
            $result = $this->model->hareketSil($id);
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Hareket silindi.' : 'Hareket bulunamadı.',
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
