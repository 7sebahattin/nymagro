<?php

class TedarikciController extends Controller
{
    private $cariModel;

    public function __construct()
    {
        $this->cariModel = $this->model('Cari');
    }

    public function index()
    {
        $arama          = trim($_GET['ara'] ?? '');
        $sayfa          = max(1, (int)($_GET['sayfa'] ?? 1));
        $aktifMi        = $_GET['aktif'] ?? '';
        $eticaretMi     = $_GET['eticaret'] ?? '';
        $sadeceBakiyeli = !empty($_GET['bakiyeli']);
        $limit          = 25;
        $offset         = ($sayfa - 1) * $limit;

        $toplam = $this->cariModel->say('tedarikci', $arama, $aktifMi, $eticaretMi, $sadeceBakiyeli);
        $tedarikciler = $this->cariModel->listele('tedarikci', $arama, $limit, $offset, $aktifMi, $eticaretMi, $sadeceBakiyeli);
        $ozetler = $this->cariModel->ozetler('tedarikci');
        $sayfaSayisi = (int)ceil($toplam / $limit);

        $this->view('tedarikciler/index', [
            'pageTitle'    => 'Tedarikçiler',
            'activeMenu'   => 'tedarikciler',
            'topbarTitle'  => 'Tedarikçiler',
            'topbarIcon'   => 'fa-solid fa-industry',
            'tedarikciler' => $tedarikciler,
            'toplam'       => $toplam,
            'ozetler'      => $ozetler,
            'arama'        => $arama,
            'aktifMi'      => $aktifMi,
            'eticaretMi'   => $eticaretMi,
            'sadeceBakiyeli' => $sadeceBakiyeli,
            'sayfa'        => $sayfa,
            'sayfaSayisi'  => $sayfaSayisi,
            'limit'        => $limit,
            'flash'        => $this->getFlash(),
        ]);
    }

    public function ekle()
    {
        $tanimGrouped = $this->model('Tanim')->grouped();
        $this->view('tedarikciler/ekle', [
            'pageTitle'   => 'Yeni Tedarikçi Ekle',
            'activeMenu'  => 'tedarikciler',
            'topbarTitle' => 'Yeni Tedarikçi Ekle',
            'topbarIcon'  => 'fa-solid fa-industry',
            'sinif1ler'   => $tanimGrouped['tedarikci_sinif_1'] ?? [],
            'sinif2ler'   => $tanimGrouped['tedarikci_sinif_2'] ?? [],
        ]);
    }

    public function kaydet()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            header('Content-Type: application/json');

            $data = [
                'tip' => 'tedarikci',
                'unvan' => trim($_POST['unvan'] ?? ''),
                'vergi_dairesi' => trim($_POST['vergi_dairesi'] ?? ''),
                'vergi_no' => trim($_POST['vergi_no'] ?? ''),
                'tc_kimlik_no' => trim($_POST['tc_kimlik_no'] ?? ''),
                'telefon' => trim($_POST['telefon'] ?? ''),
                'eposta' => trim($_POST['eposta'] ?? ''),
                'adres' => trim($_POST['adres'] ?? ''),
                'para_birimi' => trim($_POST['para_birimi'] ?? 'TRY'),
                'bakiye' => trim($_POST['bakiye'] ?? '') !== '' ? (float)str_replace(',', '.', $_POST['bakiye']) : 0,
                'yetkili_kisi' => trim($_POST['yetkili_kisi'] ?? ''),
                'notlar' => trim($_POST['notlar'] ?? ''),
                'sinif_1' => trim($_POST['sinif_1'] ?? '') ?: null,
                'sinif_2' => trim($_POST['sinif_2'] ?? '') ?: null,
                'cari_kodu' => trim($_POST['cari_kodu'] ?? '') ?: null,
                'vergi_muaf' => !empty($_POST['vergi_muaf']) ? 1 : 0,
                'banka_bilgileri' => trim($_POST['banka_bilgileri'] ?? ''),
                'vade_gun' => !empty($_POST['vade_gun']) ? (int)$_POST['vade_gun'] : null,
                'diger_erisim_bilgileri' => trim($_POST['diger_erisim_bilgileri'] ?? ''),
            ];

            if (empty($data['unvan'])) {
                echo json_encode(['success' => false, 'message' => 'Tedarikçi adı/unvanı boş olamaz']);
                return;
            }

            try {
                if ($this->cariModel->ekle($data)) {
                    echo json_encode(['success' => true, 'message' => 'Tedarikçi başarıyla kaydedildi']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Kayıt sırasında bir hata oluştu']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            $this->redirect('tedarikci');
        }
    }

    public function hizli_kaydet()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            header('Content-Type: application/json');

            $data = [
                'tip'           => 'tedarikci',
                'unvan'         => trim($_POST['unvan'] ?? ''),
                'vergi_dairesi' => trim($_POST['vergi_dairesi'] ?? ''),
                'vergi_no'      => trim($_POST['vergi_no'] ?? ''),
                'telefon'       => trim($_POST['telefon'] ?? ''),
                'eposta'        => trim($_POST['eposta'] ?? ''),
                'adres'         => trim($_POST['adres'] ?? ''),
                'para_birimi'   => trim($_POST['para_birimi'] ?? 'TRY'),
            ];

            if (empty($data['unvan'])) {
                echo json_encode(['success' => false, 'message' => 'Unvan boş olamaz']);
                exit;
            }

            try {
                $newId = $this->cariModel->ekle($data);
                if ($newId) {
                    echo json_encode(['success' => true, 'id' => $newId]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Hata oluştu']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
        }
    }

    public function tedarikciBul(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $q = trim($_GET['q'] ?? '');
        
        $faturaModel = $this->model('Fatura');
        
        // Eğer q=all ise tüm tedarikçileri getir (opsiyonel)
        if ($q === 'all') {
            $sonuclar = $faturaModel->cariAra('', 'tedarikci', 50);
            echo json_encode($sonuclar);
            exit;
        }
        
        if (mb_strlen($q) < 2) {
            echo json_encode([]);
            exit;
        }
        
        $sonuclar = $faturaModel->cariAra($q, 'tedarikci');
        echo json_encode($sonuclar);
        exit;
    }

    public function detay($id = 0)
    {
        $tedarikci = $this->cariModel->getir((int)$id);
        if (!$tedarikci) {
            $this->redirect('tedarikci');
        }

        $alisGecmisi = $this->cariModel->alisGecmisi((int)$id);
        $odemeGecmisi = $this->cariModel->odemeGecmisi((int)$id);
        $kasaHesaplar = $this->model('KasaHesap')->hepsini();
        $cekSenetBakiye = $this->cariModel->cekSenetBakiyeAyrik((int)$id);

        $this->view('tedarikciler/detay', [
            'pageTitle'    => 'Tedarikçi Detayı',
            'activeMenu'   => 'tedarikciler',
            'topbarTitle'  => 'Tedarikçi Detayı',
            'topbarIcon'   => 'fa-solid fa-industry',
            'tedarikci'    => $tedarikci,
            'alisGecmisi'  => $alisGecmisi,
            'odemeGecmisi' => $odemeGecmisi,
            'kasaHesaplar' => $kasaHesaplar,
            'cekBakiye'    => $cekSenetBakiye['cek'],
            'senetBakiye'  => $cekSenetBakiye['senet'],
            'flash'        => $this->getFlash(),
        ]);
    }

    public function guncelle($id = 0): void
    {
        $id = (int)$id;
        $tedarikci = $this->cariModel->getir($id);
        if (!$tedarikci || !in_array((string)($tedarikci['tip'] ?? ''), ['tedarikci', 'her_ikisi'], true)) {
            $this->setFlash('error', 'Tedarikçi bulunamadı.');
            $this->redirect('tedarikci');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('tedarikci/detay/' . $id);
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
            'vergi_muaf'             => !empty($_POST['vergi_muaf']) ? 1 : 0,
            'banka_bilgileri'        => trim($_POST['banka_bilgileri'] ?? ''),
            'vade_gun'               => !empty($_POST['vade_gun']) ? (int)$_POST['vade_gun'] : null,
            'diger_erisim_bilgileri' => trim($_POST['diger_erisim_bilgileri'] ?? ''),
        ];

        if ($veri['unvan'] === '') {
            $this->setFlash('error', 'Tedarikçi adı / unvanı zorunludur.');
            $this->redirect('tedarikci/detay/' . $id);
        }

        if ($veri['eposta'] !== '' && !filter_var($veri['eposta'], FILTER_VALIDATE_EMAIL)) {
            $this->setFlash('error', 'Geçerli bir e-posta adresi girin.');
            $this->redirect('tedarikci/detay/' . $id);
        }

        if ($veri['cari_kodu'] === '') {
            $veri['cari_kodu'] = null;
        }

        try {
            $this->cariModel->guncelle($id, $veri);
            $this->setFlash('success', 'Tedarikçi bilgileri güncellendi.');
        } catch (Exception $e) {
            $this->setFlash('error', 'Güncelleme sırasında hata: ' . $e->getMessage());
        }

        $this->redirect('tedarikci/detay/' . $id);
    }

    public function sil(int $id): void
    {
        if ($this->cariModel->sil($id) > 0) {
            $this->setFlash('success', 'Tedarikçi silindi.');
        } else {
            $this->setFlash('error', 'Tedarikçi bulunamadı.');
        }
        $this->redirect('tedarikci');
    }

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
}
