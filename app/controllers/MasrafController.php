<?php
/**
 * MasrafController
 * ────────────────────────────────────────────────────────
 * Nakit Yönetimi → Masraflar modülü.
 * URL: /masraf/...
 */
require_once MODELS_PATH . '/Masraf.php';
require_once MODELS_PATH . '/KasaHesap.php';
require_once MODELS_PATH . '/Personel.php';

class MasrafController extends Controller
{
    private Masraf    $model;
    private KasaHesap $hesap;
    private Personel  $personel;

    public function __construct()
    {
        $this->model = new Masraf();
        $this->hesap = new KasaHesap();
        $this->personel = new Personel();
    }

    // ──────────────────────────────────────────────────────
    // LİSTE
    // ──────────────────────────────────────────────────────

    public function index(): void
    {
        $durum  = $_GET['durum']  ?? '';
        $arama  = trim($_GET['ara'] ?? '');
        $period = $_GET['period'] ?? '6ay';
        $sayfa  = max(1, (int)($_GET['sayfa'] ?? 1));
        $limit  = 30;
        $offset = ($sayfa - 1) * $limit;

        // Geçerli durum değerleri
        if (!in_array($durum, ['', 'bekliyor', 'odendi', 'gecikti'])) $durum = '';
        if (!in_array($period, ['1ay','3ay','6ay','1yil','tum']))      $period = '6ay';

        $toplam   = $this->model->say($durum, $arama, $period);
        $masraflar = $this->model->listele($durum, $arama, $period, $limit, $offset);
        $ozetler  = $this->model->ozetler($period);
        $sayfaSay = (int)ceil($toplam / $limit);

        $this->view('masraflar/index', [
            'pageTitle'   => 'Masraflar',
            'activeMenu'  => 'masraflar',
            'topbarTitle' => 'Masraflar',
            'topbarIcon'  => 'fa-solid fa-receipt',
            'masraflar'   => $masraflar,
            'toplam'      => $toplam,
            'ozetler'     => $ozetler,
            'durum'       => $durum,
            'arama'       => $arama,
            'period'      => $period,
            'sayfa'       => $sayfa,
            'sayfaSay'    => $sayfaSay,
            'limit'       => $limit,
            'hesaplar'    => $this->hesap->hepsini(),
            'flash'       => $this->getFlash(),
        ]);
    }

    // ──────────────────────────────────────────────────────
    // MASRAF EKLE FORMU
    // ──────────────────────────────────────────────────────

    public function ekle(): void
    {
        $kategoriler = $this->model->altKategorilerFlat();
        $hesaplar    = $this->hesap->hepsini();

        $this->view('masraflar/ekle', [
            'pageTitle'   => 'Yeni Masraf Gir',
            'activeMenu'  => 'masraflar',
            'topbarTitle' => 'Yeni Masraf Gir',
            'topbarIcon'  => 'fa-solid fa-receipt',
            'kategoriler' => $kategoriler,
            'hesaplar'    => $hesaplar,
            'personeller' => $this->personel->listele(),
        ]);
    }

    // ──────────────────────────────────────────────────────
    // AJAX — MASRAF KAYDET
    // ──────────────────────────────────────────────────────

    public function kaydet(): void
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
            return;
        }
        try {
            $tutar = (float)str_replace([',', ' '], ['.', ''], $_POST['tutar'] ?? 0);
            if ($tutar <= 0) throw new Exception('Tutar sıfırdan büyük olmalıdır.');

            $id = $this->model->ekle([
                'kategori_id'   => (int)($_POST['kategori_id']   ?? 0),
                'kasa_id'       => !empty($_POST['kasa_id']) ? (int)$_POST['kasa_id'] : null,
                'islem_tarihi'  => $_POST['islem_tarihi']  ?? date('Y-m-d'),
                'vade_tarihi'   => $_POST['vade_tarihi']   ?? '',
                'belge_no'      => trim($_POST['belge_no'] ?? ''),
                'aciklama'      => trim($_POST['aciklama'] ?? ''),
                'tutar'         => $tutar,
                'kdv_orani'     => (float)($_POST['kdv_orani'] ?? 0),
                'para_birimi'   => $_POST['para_birimi']   ?? 'TRY',
                'odeme_durumu'  => $_POST['odeme_durumu']  ?? 'bekliyor',
                'odeme_tarihi'  => $_POST['odeme_tarihi']  ?? '',
                'tekrarlayan'   => !empty($_POST['tekrarlayan']) ? 1 : 0,
                'tekrar_periyot'=> trim($_POST['tekrar_periyot'] ?? ''),
                'notlar'        => trim($_POST['notlar'] ?? ''),
                'personel_id'   => !empty($_POST['personel_id']) ? (int)$_POST['personel_id'] : null,
                'personel_hareket_tipi' => trim($_POST['personel_hareket_tipi'] ?? ''),
            ]);

            echo json_encode(['success' => true, 'id' => $id, 'message' => 'Masraf kaydedildi.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // ──────────────────────────────────────────────────────
    // AJAX — MASRAF GÜNCELLE
    // ──────────────────────────────────────────────────────

    public function guncelle(): void
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
            return;
        }
        try {
            $id    = (int)($_POST['id'] ?? 0);
            $tutar = (float)str_replace([',', ' '], ['.', ''], $_POST['tutar'] ?? 0);
            if ($id <= 0)    throw new Exception('Geçersiz ID.');
            if ($tutar <= 0) throw new Exception('Tutar sıfırdan büyük olmalıdır.');

            $this->model->guncelle($id, [
                'kategori_id'          => (int)($_POST['kategori_id']  ?? 0),
                'kasa_id'              => !empty($_POST['kasa_id']) ? (int)$_POST['kasa_id'] : null,
                'personel_id'          => !empty($_POST['personel_id']) ? (int)$_POST['personel_id'] : null,
                'personel_hareket_tipi'=> trim($_POST['personel_hareket_tipi'] ?? ''),
                'islem_tarihi'         => $_POST['islem_tarihi']  ?? date('Y-m-d'),
                'vade_tarihi'          => $_POST['vade_tarihi']   ?? '',
                'belge_no'             => trim($_POST['belge_no'] ?? ''),
                'aciklama'             => trim($_POST['aciklama'] ?? ''),
                'tutar'                => $tutar,
                'kdv_orani'            => (float)($_POST['kdv_orani'] ?? 0),
                'para_birimi'          => $_POST['para_birimi']   ?? 'TRY',
                'odeme_durumu'         => $_POST['odeme_durumu']  ?? 'bekliyor',
                'odeme_tarihi'         => $_POST['odeme_tarihi']  ?? '',
                'tekrarlayan'          => !empty($_POST['tekrarlayan']) ? 1 : 0,
                'tekrar_periyot'       => trim($_POST['tekrar_periyot'] ?? ''),
                'notlar'               => trim($_POST['notlar']   ?? ''),
            ]);
            echo json_encode(['success' => true, 'message' => 'Masraf güncellendi.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // ──────────────────────────────────────────────────────
    // AJAX — ÖDEME YAP
    // ──────────────────────────────────────────────────────

    public function odemeYap(): void
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
            return;
        }
        try {
            $id     = (int)($_POST['id']      ?? 0);
            $kasaId = (int)($_POST['kasa_id'] ?? 0);
            $tarih  = trim($_POST['tarih']    ?? date('Y-m-d'));
            if ($id <= 0) throw new Exception('Geçersiz ID.');

            $this->model->odemeYap($id, $kasaId, $tarih);
            echo json_encode(['success' => true, 'message' => 'Ödeme işaretlendi.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // ──────────────────────────────────────────────────────
    // MASRAF SİL
    // ──────────────────────────────────────────────────────

    public function sil(int $id = 0): void
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
            return;
        }
        try {
            if ($id <= 0) throw new Exception('Geçersiz ID.');
            $this->model->sil($id);
            echo json_encode(['success' => true, 'message' => 'Masraf silindi.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // ──────────────────────────────────────────────────────
    // MASRAF KOPYALA
    // ──────────────────────────────────────────────────────

    public function kopyala(int $id = 0): void
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
            return;
        }
        try {
            if ($id <= 0) throw new Exception('Geçersiz ID.');
            $kaynak = $this->model->getir($id);
            if (!$kaynak) throw new Exception('Masraf bulunamadı.');

            $yeniId = $this->model->ekle([
                'kategori_id'   => (int)$kaynak['kategori_id'],
                'kasa_id'       => !empty($kaynak['kasa_id']) ? (int)$kaynak['kasa_id'] : null,
                'islem_tarihi'  => date('Y-m-d'),
                'vade_tarihi'   => $kaynak['vade_tarihi'] ?? '',
                'belge_no'      => '',
                'aciklama'      => ($kaynak['aciklama'] ?? '') . ' (Kopya)',
                'tutar'         => (float)$kaynak['tutar'],
                'kdv_orani'     => (float)$kaynak['kdv_orani'],
                'para_birimi'   => $kaynak['para_birimi'] ?? 'TRY',
                'odeme_durumu'  => 'bekliyor',
                'odeme_tarihi'  => '',
                'tekrarlayan'   => (int)($kaynak['tekrarlayan'] ?? 0),
                'tekrar_periyot'=> $kaynak['tekrar_periyot'] ?? '',
                'notlar'        => $kaynak['notlar'] ?? '',
            ]);
            echo json_encode(['success' => true, 'id' => $yeniId, 'message' => 'Masraf kopyalandı. Yeni kayıt bugün tarihiyle oluşturuldu.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // ──────────────────────────────────────────────────────
    // BELGE YÜKLE
    // ──────────────────────────────────────────────────────

    public function belgeYukle(): void
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
            return;
        }
        try {
            $masrafId = (int)($_POST['masraf_id'] ?? 0);
            if ($masrafId <= 0) throw new Exception('Geçersiz masraf ID.');

            if (empty($_FILES['dosya']['name'])) {
                throw new Exception('Dosya seçilmedi.');
            }

            $izinliUzantilar = ['doc','docx','xls','xlsx','pdf','jpg','jpeg','gif','png','txt'];
            $maxBoyut        = 5 * 1024 * 1024; // 5MB

            $dosyaAdi  = $_FILES['dosya']['name'];
            $geciciYol = $_FILES['dosya']['tmp_name'];
            $boyut     = $_FILES['dosya']['size'];
            $hata      = $_FILES['dosya']['error'];

            if ($hata !== UPLOAD_ERR_OK) throw new Exception('Dosya yükleme hatası: ' . $hata);
            if ($boyut > $maxBoyut) throw new Exception('Dosya boyutu 5MB\'ı geçemez.');

            $uzanti = strtolower(pathinfo($dosyaAdi, PATHINFO_EXTENSION));
            if (!in_array($uzanti, $izinliUzantilar)) {
                throw new Exception('İzin verilmeyen dosya türü: ' . $uzanti);
            }

            $hedefKlasor = ROOT_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'masraflar' . DIRECTORY_SEPARATOR . $masrafId . DIRECTORY_SEPARATOR;
            if (!is_dir($hedefKlasor)) {
                mkdir($hedefKlasor, 0755, true);
            }

            $yeniAd  = uniqid('belge_') . '.' . $uzanti;
            $hedefYol = $hedefKlasor . $yeniAd;

            if (!move_uploaded_file($geciciYol, $hedefYol)) {
                throw new Exception('Dosya kaydedilemedi.');
            }

            // DB'ye kaydet
            $this->model->belgeEkle([
                'masraf_id'    => $masrafId,
                'dosya_adi'    => $yeniAd,
                'orijinal_adi' => $dosyaAdi,
                'boyut'        => $boyut,
                'uzanti'       => $uzanti,
            ]);

            echo json_encode(['success' => true, 'message' => 'Belge yüklendi: ' . htmlspecialchars($dosyaAdi)]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // ──────────────────────────────────────────────────────
    // BELGE İNDİR (güvenli proxy)
    // ──────────────────────────────────────────────────────

    public function belgeIndir(int $belgeId = 0): void
    {
        try {
            if ($belgeId <= 0) throw new Exception('Geçersiz belge ID.');
            $belge = $this->model->belgeGetir($belgeId);
            if (!$belge) {
                http_response_code(404);
                die('Belge bulunamadı veya erişim izniniz yok.');
            }

            $yol = ROOT_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads'
                 . DIRECTORY_SEPARATOR . 'masraflar' . DIRECTORY_SEPARATOR . (int)$belge['masraf_id']
                 . DIRECTORY_SEPARATOR . $belge['dosya_adi'];

            // Path traversal koruması
            $real = realpath($yol);
            $base = realpath(ROOT_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'masraflar');
            if ($real === false || $base === false || !str_starts_with($real, $base)) {
                http_response_code(404);
                die('Geçersiz dosya yolu.');
            }
            if (!is_file($real)) {
                http_response_code(404);
                die('Dosya bulunamadı.');
            }

            $mime = function_exists('mime_content_type') ? mime_content_type($real) : 'application/octet-stream';
            header('Content-Type: ' . $mime);
            header('Content-Length: ' . filesize($real));
            header('Content-Disposition: inline; filename="' . rawurlencode($belge['orijinal_adi']) . '"');
            header('X-Content-Type-Options: nosniff');
            readfile($real);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            die('Hata: ' . htmlspecialchars($e->getMessage()));
        }
    }

    // ──────────────────────────────────────────────────────
    // KATEGORİ YÖNETİMİ
    // ──────────────────────────────────────────────────────

    public function kalemler(): void
    {
        $kategoriler = $this->model->kategoriler();

        $this->view('masraflar/kalemler', [
            'pageTitle'   => 'Masraf Kalemleri',
            'activeMenu'  => 'masraflar',
            'topbarTitle' => 'Masraf Kalemleri',
            'topbarIcon'  => 'fa-solid fa-receipt',
            'kategoriler' => $kategoriler,
            'flash'       => $this->getFlash(),
        ]);
    }

    public function kategoriKaydet(): void
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
            return;
        }
        try {
            $id = $this->model->kategoriEkle([
                'parent_id' => !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null,
                'ad'        => trim($_POST['ad']   ?? ''),
                'renk'      => trim($_POST['renk'] ?? '#5bc0de'),
            ]);
            echo json_encode(['success' => true, 'id' => $id, 'message' => 'Kategori eklendi.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function kategoriGuncelle(): void
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
            return;
        }
        try {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new Exception('Geçersiz ID.');
            $this->model->kategoriGuncelle($id, [
                'ad'   => trim($_POST['ad']   ?? ''),
                'renk' => trim($_POST['renk'] ?? '#5bc0de'),
            ]);
            echo json_encode(['success' => true, 'message' => 'Kategori güncellendi.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function kategoriSil(int $id = 0): void
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
            return;
        }
        try {
            if ($id <= 0) throw new Exception('Geçersiz ID.');
            $this->model->kategoriSil($id);
            echo json_encode(['success' => true, 'message' => 'Kategori silindi.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
