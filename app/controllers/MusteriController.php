<?php
/**
 * MusteriController
 * --------------------------------------------------------
 * Müşteri listeleme, ekleme, güncelleme, silme işlemleri.
 * URL şeması:
 *   GET  /musteri           → index()   listeleme
 *   GET  /musteri/ekle      → ekle()    form göster
 *   POST /musteri/kaydet    → kaydet()  forma POST geldi
 *   GET  /musteri/sil/{id}  → sil($id)  soft-delete
 */
final class MusteriController extends Controller
{
    private Cari $cariModel;

    public function __construct()
    {
        $this->cariModel = $this->model('Cari');
    }

    // ──────────────────────────────────────────────────────
    // LİSTELEME
    // ──────────────────────────────────────────────────────
    public function index(): void
    {
        // ── GET parametrelerini yakala ─────────────────────
        $arama          = trim($_GET['ara']        ?? '');
        $sayfa          = max(1, (int)($_GET['sayfa']    ?? 1));
        $aktifMi        = $_GET['aktif']     ?? '';   // '' | '1' | '0'
        $eticaretMi     = $_GET['eticaret']  ?? '';   // '' | '1'
        $sadeceBakiyeli = !empty($_GET['bakiyeli']);   // bool
        $limit          = 25;
        $offset         = ($sayfa - 1) * $limit;

        // ── Model çağrıları ────────────────────────────────
        $toplam      = $this->cariModel->say(
            'musteri', $arama, $aktifMi, $eticaretMi, $sadeceBakiyeli
        );
        $musteriler  = $this->cariModel->listele(
            'musteri', $arama, $limit, $offset, $aktifMi, $eticaretMi, $sadeceBakiyeli
        );
        $ozetler     = $this->cariModel->ozetler('musteri');
        $sayfaSayisi = (int)ceil($toplam / $limit);

        $this->view('musteriler/index', [
            'pageTitle'       => 'Müşteriler',
            'activeMenu'      => 'musteriler',
            'topbarIcon'      => 'fa-solid fa-users',
            'topbarTitle'     => 'Müşteriler',
            'musteriler'      => $musteriler,
            'toplam'          => $toplam,
            'ozetler'         => $ozetler,
            'arama'           => $arama,
            'aktifMi'         => $aktifMi,
            'eticaretMi'      => $eticaretMi,
            'sadeceBakiyeli'  => $sadeceBakiyeli,
            'sayfa'           => $sayfa,
            'sayfaSayisi'     => $sayfaSayisi,
            'limit'           => $limit,
            'flash'           => $this->getFlash(),
        ]);
    }

    // ──────────────────────────────────────────────────────
    // EKLEME FORMU
    // ──────────────────────────────────────────────────────
    public function ekle(): void
    {
        $this->view('musteriler/ekle', [
            'pageTitle'   => 'Yeni Müşteri Ekle',
            'activeMenu'  => 'musteriler',
            'topbarIcon'  => 'fa-solid fa-user-plus',
            'topbarTitle' => 'Yeni Müşteri Ekle',
            'hatalar'     => [],
            'eski'        => [],
        ]);
    }

    // ──────────────────────────────────────────────────────
    // DETAY
    // ──────────────────────────────────────────────────────
    public function detay($id = 0): void
    {
        $musteri = $this->cariModel->getir((int)$id);
        if (!$musteri) {
            $this->redirect('musteri');
        }

        $satisGecmisi = $this->cariModel->satisGecmisi((int)$id);
        $odemeGecmisi = $this->cariModel->odemeGecmisi((int)$id);
        $kasaHesaplar = $this->model('KasaHesap')->hepsini();
        $cekSenetBakiye = $this->cariModel->cekSenetBakiyeAyrik((int)$id);

        $this->view('musteriler/detay', [
            'pageTitle'   => 'Müşteri Detayları',
            'activeMenu'  => 'musteriler',
            'topbarIcon'  => 'fa-solid fa-user',
            'topbarTitle' => 'Müşteri Detayları',
            'musteri'     => $musteri,
            'satisGecmisi'=> $satisGecmisi,
            'odemeGecmisi'=> $odemeGecmisi,
            'kasaHesaplar'=> $kasaHesaplar,
            'cekBakiye'   => $cekSenetBakiye['cek'],
            'senetBakiye' => $cekSenetBakiye['senet'],
            'flash'       => $this->getFlash(),
        ]);
    }

    // ──────────────────────────────────────────────────────
    // DÜZENLE (tam sayfa — "Yeni Ekle" formuyla aynı yapı, önceden dolu)
    // ──────────────────────────────────────────────────────
    public function duzenle($id = 0): void
    {
        $id = (int)$id;
        $musteri = $this->cariModel->getir($id);
        if (!$musteri || !in_array((string)($musteri['tip'] ?? ''), ['musteri', 'her_ikisi'], true)) {
            $this->setFlash('error', 'Müşteri bulunamadı.');
            $this->redirect('musteri');
        }

        $this->view('musteriler/ekle', [
            'pageTitle'   => 'Müşteri Bilgilerini Düzenle',
            'activeMenu'  => 'musteriler',
            'topbarIcon'  => 'fa-solid fa-user-pen',
            'topbarTitle' => 'Müşteri Bilgilerini Düzenle',
            'hatalar'     => [],
            'eski'        => $musteri,
            'duzenleId'   => $id,
        ]);
    }

    // ──────────────────────────────────────────────────────
    // KAYDET (POST)
    // ──────────────────────────────────────────────────────
    public function kaydet(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('musteri/ekle');
        }

        // ── Girdileri temizle ──────────────────────────────
        $veri = [
            'tip'            => 'musteri',
            'unvan'          => trim($_POST['unvan']          ?? ''),
            'yetkili_kisi'   => trim($_POST['yetkili_kisi']   ?? ''),
            'eposta'         => trim($_POST['eposta']         ?? ''),
            'cep_telefon'    => trim($_POST['cep_telefon']    ?? ''),
            'telefon'        => trim($_POST['telefon']        ?? ''),
            'adres'          => trim($_POST['adres']          ?? ''),
            'vergi_dairesi'  => trim($_POST['vergi_dairesi']  ?? ''),
            'vergi_no'       => trim($_POST['vergi_no']       ?? ''),
            'tc_kimlik_no'   => trim($_POST['tc_kimlik_no']   ?? ''),
            'para_birimi'    => trim($_POST['para_birimi']    ?? 'TRY'),
            'bakiye'         => (float)str_replace(',', '.', $_POST['bakiye'] ?? '0'),
            'cari_kodu'      => trim($_POST['cari_kodu']      ?? ''),
            'notlar'         => trim($_POST['notlar']         ?? ''),
        ];

        try {
            $resimYolu = $this->handleResimUpload();
            if ($resimYolu !== null) {
                $veri['resim_yolu'] = $resimYolu;
            }
        } catch (RuntimeException $e) {
            $this->view('musteriler/ekle', [
                'pageTitle'   => 'Yeni Müşteri Ekle',
                'activeMenu'  => 'musteriler',
                'topbarIcon'  => 'fa-solid fa-user-plus',
                'topbarTitle' => 'Yeni Müşteri Ekle',
                'hatalar'     => ['resim' => $e->getMessage()],
                'eski'        => $_POST,
            ]);
            return;
        }

        // ── Doğrulama ─────────────────────────────────────
        $hatalar = [];

        if ($veri['unvan'] === '') {
            $hatalar['unvan'] = 'Müşteri adı / unvanı zorunludur.';
        }
        if ($veri['eposta'] !== '' && !filter_var($veri['eposta'], FILTER_VALIDATE_EMAIL)) {
            $hatalar['eposta'] = 'Geçerli bir e-posta adresi girin.';
        }
        if ($veri['vergi_no'] !== '' && !preg_match('/^\d{10,11}$/', $veri['vergi_no'])) {
            $hatalar['vergi_no'] = 'Vergi / TC kimlik no 10 veya 11 haneli olmalıdır.';
        }

        if (!empty($hatalar)) {
            $this->view('musteriler/ekle', [
                'pageTitle'   => 'Yeni Müşteri Ekle',
                'activeMenu'  => 'musteriler',
                'topbarIcon'  => 'fa-solid fa-user-plus',
                'topbarTitle' => 'Yeni Müşteri Ekle',
                'hatalar'     => $hatalar,
                'eski'        => $_POST,
            ]);
            return;
        }

        // ── Kaydet ────────────────────────────────────────
        try {
            $yeniId = $this->cariModel->ekle($veri);
            $this->setFlash('success', '"' . htmlspecialchars($veri['unvan']) . '" başarıyla eklendi.');
            $this->redirect('musteri');
        } catch (Exception $e) {
            $this->view('musteriler/ekle', [
                'pageTitle'   => 'Yeni Müşteri Ekle',
                'activeMenu'  => 'musteriler',
                'topbarIcon'  => 'fa-solid fa-user-plus',
                'topbarTitle' => 'Yeni Müşteri Ekle',
                'hatalar'     => ['genel' => 'Kayıt sırasında hata: ' . $e->getMessage()],
                'eski'        => $_POST,
            ]);
        }
    }

    public function guncelle($id = 0): void
    {
        $id = (int)$id;
        $musteri = $this->cariModel->getir($id);
        if (!$musteri || !in_array((string)($musteri['tip'] ?? ''), ['musteri', 'her_ikisi'], true)) {
            $this->setFlash('error', 'Müşteri bulunamadı.');
            $this->redirect('musteri');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('musteri/detay/' . $id);
        }

        $veri = [
            'unvan'         => trim($_POST['unvan'] ?? ''),
            'yetkili_kisi'  => trim($_POST['yetkili_kisi'] ?? ''),
            'eposta'        => trim($_POST['eposta'] ?? ''),
            'cep_telefon'   => trim($_POST['cep_telefon'] ?? ''),
            'telefon'       => trim($_POST['telefon'] ?? ''),
            'adres'         => trim($_POST['adres'] ?? ''),
            'vergi_dairesi' => trim($_POST['vergi_dairesi'] ?? ''),
            'vergi_no'      => trim($_POST['vergi_no'] ?? ''),
            'tc_kimlik_no'  => trim($_POST['tc_kimlik_no'] ?? ''),
            'para_birimi'   => trim($_POST['para_birimi'] ?? 'TRY'),
            'cari_kodu'     => trim($_POST['cari_kodu'] ?? ''),
            'notlar'        => trim($_POST['notlar'] ?? ''),
        ];

        try {
            $resimYolu = $this->handleResimUpload();
            if ($resimYolu !== null) {
                $veri['resim_yolu'] = $resimYolu;
            }
        } catch (RuntimeException $e) {
            $this->setFlash('error', $e->getMessage());
            $this->redirect('musteri/duzenle/' . $id);
        }

        if ($veri['unvan'] === '') {
            $this->setFlash('error', 'Müşteri adı / unvanı zorunludur.');
            $this->redirect('musteri/duzenle/' . $id);
        }

        if ($veri['eposta'] !== '' && !filter_var($veri['eposta'], FILTER_VALIDATE_EMAIL)) {
            $this->setFlash('error', 'Geçerli bir e-posta adresi girin.');
            $this->redirect('musteri/duzenle/' . $id);
        }

        if ($veri['cari_kodu'] === '') {
            $veri['cari_kodu'] = null;
        }

        try {
            $this->cariModel->guncelle($id, $veri);
            $this->setFlash('success', 'Müşteri bilgileri güncellendi.');
        } catch (Exception $e) {
            $this->setFlash('error', 'Güncelleme sırasında hata: ' . $e->getMessage());
        }

        $this->redirect('musteri/detay/' . $id);
    }

    /**
     * Bu müşterinin geçmiş tahsilat kayıtlarını faturalarına yeniden (FIFO)
     * dağıtır. Bkz. Fatura::fifoBakiyeleriYenidenHesapla() — özellikle bu
     * düzeltme canlıya alınmadan önce girilmiş tahsilatların, ilgili
     * faturaların "Kalan" tutarına hiç yansımamış olmasını onarmak için.
     */
    public function bakiyeGuncelle($id = 0): void
    {
        $id = (int)$id;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('musteri/detay/' . $id);
        }
        $musteri = $this->cariModel->getir($id);
        if (!$musteri) {
            $this->setFlash('error', 'Müşteri bulunamadı.');
            $this->redirect('musteri');
        }

        try {
            require_once MODELS_PATH . '/Fatura.php';
            (new Fatura())->fifoBakiyeleriYenidenHesapla($id);
            $this->setFlash('success', 'Fatura bakiyeleri, güncel tahsilat kayıtlarına göre yeniden hesaplandı.');
        } catch (Exception $e) {
            $this->setFlash('error', 'Yeniden hesaplama sırasında hata: ' . $e->getMessage());
        }
        $this->redirect('musteri/detay/' . $id);
    }

    // ──────────────────────────────────────────────────────
    // SOFT DELETE
    // ──────────────────────────────────────────────────────
    public function sil(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('musteri');
        }
        $cari = $this->cariModel->getir($id);
        if ($cari) {
            $this->cariModel->sil($id);
            $this->setFlash('success', '"' . htmlspecialchars($cari['unvan']) . '" silindi.');
        } else {
            $this->setFlash('error', 'Kayıt bulunamadı.');
        }
        $this->redirect('musteri');
    }

    // ──────────────────────────────────────────────────────
    // FLASH MESAJ YARDIMCILARI
    // ──────────────────────────────────────────────────────
    
    // ──────────────────────────────────────────────────────
    // NOT KAYDET (AJAX)
    // ──────────────────────────────────────────────────────
    public function not_kaydet($id = 0): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $notlar = trim($_POST['notlar'] ?? '');
            if ($id > 0) {
                $this->cariModel->guncelle((int)$id, ['notlar' => $notlar]);
                echo json_encode(['status' => 'success', 'notlar' => $notlar]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Geçersiz ID']);
            }
        }
        exit;
    }

    // ──────────────────────────────────────────────────────
    // AJAX: HIZLI MÜŞTERİ EKLE
    // ──────────────────────────────────────────────────────
    public function hizli_ekle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json; charset=utf-8');
            $unvan = trim($_POST['unvan'] ?? '');
            if ($unvan === '') {
                echo json_encode(['status' => 'error', 'message' => 'Unvan zorunludur']);
                exit;
            }
            
            $veri = [
                'tip' => 'musteri',
                'unvan' => $unvan,
                'telefon' => trim($_POST['telefon'] ?? ''),
                'para_birimi' => trim($_POST['para_birimi'] ?? 'TRY'),
                'vergi_dairesi' => trim($_POST['vergi_dairesi'] ?? ''),
                'vergi_no' => trim($_POST['vergi_no'] ?? ''),
                'eposta' => trim($_POST['eposta'] ?? ''),
                'adres' => trim($_POST['adres'] ?? '')
            ];
            
            try {
                $id = $this->cariModel->ekle($veri);
                echo json_encode(['status' => 'success', 'id' => $id]);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
        }
    }

    /** Müşteri fotoğrafı yükleme (opsiyonel). Yüklenen yoksa null döner. */
    private function handleResimUpload(): ?string
    {
        $file = $_FILES['resim'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Fotoğraf yüklenemedi. Hata kodu: ' . (int)$file['error']);
        }

        if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
            throw new RuntimeException('Fotoğraf dosyası 2MB boyutunu geçemez.');
        }

        $tmpPath = (string)($file['tmp_name'] ?? '');
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            throw new RuntimeException('Fotoğraf dosyası doğrulanamadı.');
        }

        $mime = null;
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = $finfo ? finfo_file($finfo, $tmpPath) : null;
            if ($finfo) {
                finfo_close($finfo);
            }
        } elseif (function_exists('mime_content_type')) {
            $mime = mime_content_type($tmpPath);
        }

        $allowed = [
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];
        if (!isset($allowed[$mime])) {
            throw new RuntimeException('Fotoğraf için sadece PNG, JPG, WEBP veya GIF yüklenebilir.');
        }

        $uploadDir = ROOT_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'cari-fotograflar';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
            throw new RuntimeException('Fotoğraf klasörü oluşturulamadı.');
        }

        $fileName = 'musteri-' . date('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . $allowed[$mime];
        $target = $uploadDir . DIRECTORY_SEPARATOR . $fileName;
        if (!move_uploaded_file($tmpPath, $target)) {
            throw new RuntimeException('Fotoğraf dosyası kaydedilemedi.');
        }

        return 'uploads/cari-fotograflar/' . $fileName;
    }
}
