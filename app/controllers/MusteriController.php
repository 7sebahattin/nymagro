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

        $this->view('musteriler/detay', [
            'pageTitle'   => 'Müşteri Detayları',
            'activeMenu'  => 'musteriler',
            'topbarIcon'  => 'fa-solid fa-user',
            'topbarTitle' => 'Müşteri Detayları',
            'musteri'     => $musteri,
            'satisGecmisi'=> $satisGecmisi,
            'odemeGecmisi'=> $odemeGecmisi,
            'kasaHesaplar'=> $kasaHesaplar,
            'flash'       => $this->getFlash(),
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

        if ($veri['unvan'] === '') {
            $this->setFlash('error', 'Müşteri adı / unvanı zorunludur.');
            $this->redirect('musteri/detay/' . $id);
        }

        if ($veri['eposta'] !== '' && !filter_var($veri['eposta'], FILTER_VALIDATE_EMAIL)) {
            $this->setFlash('error', 'Geçerli bir e-posta adresi girin.');
            $this->redirect('musteri/detay/' . $id);
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

    // ──────────────────────────────────────────────────────
    // SOFT DELETE
    // ──────────────────────────────────────────────────────
    public function sil(int $id): void
    {
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

}
