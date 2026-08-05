<?php
/**
 * SiteController
 * --------------------------------------------------------
 * Public landing page (Nuverna Trade) içerik yönetimi.
 *
 * /site            → ayarlar (ana sayfa içerik yönetimi — varsayılan)
 * /site/urun-kaydet  POST AJAX
 * /site/urun-sil/{id} GET AJAX
 * /site/galeri-kaydet POST AJAX
 * /site/galeri-sil/{id} GET AJAX
 */
require_once MODELS_PATH . '/SiteIcerik.php';
require_once MODELS_PATH . '/ContactMessage.php';

class SiteController extends Controller
{
    private SiteIcerik $model;

    private const ALLOWED_IMG = ['jpg','jpeg','png','gif','webp','svg'];
    private const MAX_BYTES   = 5 * 1024 * 1024; // 5 MB

    public function __construct()
    {
        $this->model = new SiteIcerik();
    }

    public function index(): void
    {
        try { $mesajlar = (new ContactMessage())->tumMesajlar(200); } catch (Throwable $e) { $mesajlar = []; }
        try { $yeniMesajSay = (new ContactMessage())->yeniSay(); } catch (Throwable $e) { $yeniMesajSay = 0; }

        $this->view('site/yonetim', [
            'pageTitle'     => 'Site Yönetimi',
            'activeMenu'    => 'site',
            'topbarTitle'   => 'Site Yönetimi',
            'topbarIcon'    => 'fa-solid fa-globe',
            'ayarlar'       => $this->model->tumAyarlar(),
            'urunler'       => $this->model->urunler(false),
            'galeri'        => $this->model->galeri(false),
            'mesajlar'      => $mesajlar,
            'yeniMesajSay'  => $yeniMesajSay,
            'flash'         => $this->getFlash(),
        ]);
    }

    // ─── CONTACT MESSAGES ───────────────────────────────────

    public function mesajDurum(int $id = 0): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        try {
            if ($id <= 0) throw new Exception('Geçersiz ID.');
            $status = trim((string)($_POST['status'] ?? $_GET['status'] ?? 'read'));
            (new ContactMessage())->durumGuncelle($id, $status);
            echo json_encode(['ok' => true]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        }
    }

    public function mesajSil(int $id = 0): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        try {
            if ($id <= 0) throw new Exception('Geçersiz ID.');
            (new ContactMessage())->sil($id);
            echo json_encode(['ok' => true]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        }
    }

    // ─── GENEL AYARLAR + LOGO ───────────────────────────

    public function ayarlarKaydet(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->redirect('site'); }

        try {
            $alanlar = [
                'company_name','tagline','whatsapp','whatsapp_link','email','phone','address',
                'instagram','linkedin','facebook','twitter','website',
                'hero_title_1','hero_title_2','hero_desc',
                'slide_count','slide1_img','slide2_img','slide3_img','slide4_img','about_img','og_image',
                'stat_countries_value','stat_products_value','stat_quality_value','about_stat_value',
            ];
            $payload = [];
            foreach ($alanlar as $k) {
                if (isset($_POST[$k])) $payload[$k] = trim((string)$_POST[$k]);
            }
            $slideCount = max(4, min(20, (int)($_POST['slide_count'] ?? 4)));
            $payload['slide_count'] = (string)$slideCount;
            for ($i = 1; $i <= $slideCount; $i++) {
                $key = 'slide' . $i . '_img';
                if (isset($_POST[$key])) {
                    $payload[$key] = trim((string)$_POST[$key]);
                }
            }

            foreach ((array)($_POST['i18n'] ?? []) as $lang => $texts) {
                if (!in_array($lang, ['tr', 'en', 'ru'], true) || !is_array($texts)) {
                    continue;
                }
                foreach ($texts as $key => $value) {
                    if (!preg_match('/^[a-z0-9_]+$/', (string)$key)) {
                        continue;
                    }
                    $payload['i18n_' . $lang . '_' . $key] = trim((string)$value);
                }
            }

            // Logo yüklendiyse işle
            if (!empty($_FILES['logo']['name'])) {
                $logoYol = $this->dosyaYukle($_FILES['logo'], 'logo');
                if ($logoYol) {
                    $payload['logo_path'] = $logoYol;
                }
            }

            $imageUploads = [
                'about_img'  => 'about_img_file',
                'og_image'   => 'og_image_file',
            ];
            for ($i = 1; $i <= $slideCount; $i++) {
                $imageUploads['slide' . $i . '_img'] = 'slide' . $i . '_img_file';
            }
            foreach ($imageUploads as $settingKey => $fileKey) {
                if (!empty($_FILES[$fileKey]['name'])) {
                    $payload[$settingKey] = $this->dosyaYukle($_FILES[$fileKey], 'icerik');
                }
            }

            $this->model->topluAyarKaydet($payload);
            $this->setFlash('success', 'Site ayarları kaydedildi.');
        } catch (Exception $e) {
            $this->setFlash('error', 'Kaydedilemedi: ' . $e->getMessage());
        }
        $returnTab = preg_replace('/[^a-z0-9_-]/', '', (string)($_POST['return_tab'] ?? 'tab-genel'));
        $this->redirect('site#' . ($returnTab ?: 'tab-genel'));
    }

    public function logoSil(): void
    {
        $this->model->ayarKaydet('logo_path', '');
        $this->setFlash('success', 'Logo kaldırıldı. Varsayılan logoya dönüldü.');
        $this->redirect('site#tab-genel');
    }

    // ─── ÜRÜN ───────────────────────────────────────────

    public function urunKaydet(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['ok'=>false,'msg'=>'Geçersiz istek']); return; }

        try {
            $id   = (int)($_POST['id'] ?? 0);
            $veri = [
                'sira'        => (int)($_POST['sira'] ?? 0),
                'kategori'    => in_array(($_POST['kategori'] ?? 'meyve'), ['meyve', 'sebze'], true) ? $_POST['kategori'] : 'meyve',
                'ad_tr'       => trim($_POST['ad_tr'] ?? ''),
                'ad_en'       => trim($_POST['ad_en'] ?? ''),
                'ad_ru'       => trim($_POST['ad_ru'] ?? ''),
                'aciklama_tr' => trim($_POST['aciklama_tr'] ?? ''),
                'aciklama_en' => trim($_POST['aciklama_en'] ?? ''),
                'aciklama_ru' => trim($_POST['aciklama_ru'] ?? ''),
                'etiket'      => trim($_POST['etiket'] ?? 'FRESH'),
                'aktif_mi'    => !empty($_POST['aktif_mi']),
            ];

            if ($veri['ad_tr'] === '') throw new Exception('Türkçe ürün adı zorunludur.');

            // Yeni görsel yüklendi mi?
            if (!empty($_FILES['gorsel']['name'])) {
                $yol = $this->dosyaYukle($_FILES['gorsel'], 'urunler');
                if ($yol) $veri['gorsel'] = $yol;
            } elseif (!empty($_POST['gorsel_url'])) {
                $veri['gorsel'] = trim((string)$_POST['gorsel_url']);
            }

            if ($id > 0) {
                if (!isset($veri['gorsel'])) {
                    $mevcut = $this->model->urunGetir($id);
                    if (!$mevcut) throw new Exception('Ürün bulunamadı.');
                }
                $this->model->urunGuncelle($id, $veri);
                echo json_encode(['ok' => true, 'msg' => 'Ürün güncellendi.', 'id' => $id]);
            } else {
                if (empty($veri['gorsel'])) throw new Exception('Yeni ürün için görsel zorunludur.');
                $newId = $this->model->urunEkle($veri);
                echo json_encode(['ok' => true, 'msg' => 'Ürün eklendi.', 'id' => $newId]);
            }
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        }
    }

    public function urunSil(int $id = 0): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        try {
            if ($id <= 0) throw new Exception('Geçersiz ID.');
            $this->model->urunSil($id);
            echo json_encode(['ok' => true, 'msg' => 'Ürün silindi.']);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        }
    }

    // ─── GALERİ ─────────────────────────────────────────

    public function galeriKaydet(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['ok'=>false,'msg'=>'Geçersiz istek']); return; }

        try {
            if (empty($_FILES['gorsel']['name']) && empty($_POST['gorsel_url'])) {
                throw new Exception('Görsel seçilmedi.');
            }
            $gorsel = !empty($_FILES['gorsel']['name'])
                ? $this->dosyaYukle($_FILES['gorsel'], 'galeri')
                : trim((string)$_POST['gorsel_url']);

            $id = $this->model->galeriEkle([
                'sira'      => (int)($_POST['sira'] ?? 0),
                'gorsel'    => $gorsel,
                'etiket_tr' => trim($_POST['etiket_tr'] ?? ''),
                'etiket_en' => trim($_POST['etiket_en'] ?? ''),
                'etiket_ru' => trim($_POST['etiket_ru'] ?? ''),
                'aktif_mi'  => !empty($_POST['aktif_mi']),
            ]);
            echo json_encode(['ok' => true, 'msg' => 'Galeri öğesi eklendi.', 'id' => $id]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        }
    }

    public function galeriSil(int $id = 0): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        try {
            if ($id <= 0) throw new Exception('Geçersiz ID.');
            $this->model->galeriSil($id);
            echo json_encode(['ok' => true, 'msg' => 'Galeri öğesi silindi.']);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        }
    }

    // ─── DOSYA YÜKLEME ──────────────────────────────────

    private function dosyaYukle(array $file, string $altKlasor): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new Exception('Yükleme hatası: ' . ($file['error'] ?? '?'));
        }
        if (($file['size'] ?? 0) > self::MAX_BYTES) {
            throw new Exception('Dosya 5 MB sınırını aşıyor.');
        }
        $uzanti = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($uzanti, self::ALLOWED_IMG, true)) {
            throw new Exception('Geçersiz görsel türü: ' . $uzanti);
        }

        // MIME doğrulama
        if (function_exists('mime_content_type')) {
            $mime = (string)@mime_content_type($file['tmp_name']);
            $okMimes = ['image/jpeg','image/png','image/gif','image/webp','image/svg+xml'];
            if ($mime !== '' && !in_array($mime, $okMimes, true)) {
                throw new Exception('Dosya içeriği geçerli bir görsel değil.');
            }
        }

        if ($uzanti !== 'svg' && function_exists('getimagesize') && @getimagesize($file['tmp_name']) === false) {
            throw new Exception('Dosya içeriği okunabilir bir görsel değil.');
        }

        $hedefKlasor = ROOT_PATH . DIRECTORY_SEPARATOR . 'public'
                     . DIRECTORY_SEPARATOR . 'uploads'
                     . DIRECTORY_SEPARATOR . 'site'
                     . DIRECTORY_SEPARATOR . $altKlasor . DIRECTORY_SEPARATOR;
        if (!is_dir($hedefKlasor)) mkdir($hedefKlasor, 0755, true);

        $yeniAd  = $altKlasor . '_' . uniqid() . '.' . $uzanti;
        $hedef   = $hedefKlasor . $yeniAd;
        if (!move_uploaded_file($file['tmp_name'], $hedef)) {
            throw new Exception('Dosya kaydedilemedi.');
        }
        // Deployment-friendly public path. The frontend expands it with the current BASE_URL.
        return 'uploads/site/' . $altKlasor . '/' . $yeniAd;
    }
}
