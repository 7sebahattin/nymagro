<?php
/**
 * Controller: EBelgeController
 * --------------------------------------------------------
 * e-Belge (XML) İçe Alma — FAZ 1: yükleme, ayrıştırma, saklama, görüntüleme.
 *
 * Rotalar:
 *   GET  /ebelge                  → index()   listeleme + özet
 *   GET  /ebelge/yukle            → yukle()   yükleme formu
 *   POST /ebelge/yukle            → yukle()   XML/ZIP işle
 *   GET  /ebelge/detay/{id}       → detay()   belge detayı
 *   GET  /ebelge/indir/{id}       → indir()   ham XML indir (yetki kontrollü)
 *   POST /ebelge/iptal/{id}       → iptal()   belgeyi reddet/pasife al
 *
 * YETKİ (Rbac::classifyAction ile otomatik):
 *   index/detay/indir → EBELGE_VIEW · yukle → EBELGE_CREATE · iptal → EBELGE_UPDATE
 *
 * ÇEKİRDEK SİSTEME DOKUNMAZ: bu controller ve kullandığı model faturalar /
 * fatura_kalemleri / stok_hareketleri / cariler tablolarına hiçbir yazma yapmaz.
 * Aktarım (Faz 3) ayrı bir uçta olacak ve yalnızca Fatura::ekle() çağıracaktır.
 */
require_once MODELS_PATH . '/EBelgeGuvenlik.php';
require_once MODELS_PATH . '/EBelgeParser.php';
require_once MODELS_PATH . '/EBelge.php';

final class EBelgeController extends Controller
{
    private EBelge $model;

    public function __construct()
    {
        $this->model = new EBelge();
    }

    public function index(): void
    {
        $filtreler = $this->filtreleriOku();
        $sayfa  = max(1, (int)($_GET['sayfa'] ?? 1));
        $limit  = 50;
        $offset = ($sayfa - 1) * $limit;
        $toplam = $this->model->say($filtreler);

        // Son yükleme sonuçları (dosya bazlı) yalnızca bir kez gösterilir.
        $sonSonuclar = $_SESSION['ebelge_son_sonuc'] ?? [];
        unset($_SESSION['ebelge_son_sonuc']);

        $this->view('ebelge/index', [
            'flash'         => $this->getFlash(),
            'sonSonuclar'   => $sonSonuclar,
            'semaHazir'     => $this->model->semaHazirMi(),
            'filtreler'     => $filtreler,
            'belgeler'      => $this->model->listele($filtreler, $limit, $offset),
            'ozetler'       => $this->model->ozetler($filtreler),
            'paketler'      => $this->model->sonPaketler(5),
            'hataliDosyalar' => $this->model->hataliDosyalar(20),
            'toplam'        => $toplam,
            'sayfa'         => $sayfa,
            'sayfaSay'      => max(1, (int)ceil($toplam / $limit)),
            'topbarTitle'   => 'e-Belge (XML) İçe Alma',
            'topbarIcon'    => 'fa-file-code',
        ]);
    }

    /**
     * GET  → yükleme formunu gösterir.
     * POST → dosyayı işler (gerçek yazma yalnızca burada olur).
     */
    public function yukle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->view('ebelge/yukle', [
                'flash'       => $this->getFlash(),
                'semaHazir'   => $this->model->semaHazirMi(),
                'limitler'    => [
                    'xml'     => EBelgeGuvenlik::MAX_XML_BYTES,
                    'zip'     => EBelgeGuvenlik::MAX_ZIP_BYTES,
                    'entries' => EBelgeGuvenlik::MAX_ZIP_ENTRIES,
                ],
                'topbarTitle' => 'e-Belge Yükle',
                'topbarIcon'  => 'fa-file-import',
            ]);
            return;
        }

        $dosya = $_FILES['ebelge'] ?? null;
        if (!is_array($dosya)) {
            $this->setFlash('error', 'Dosya seçilmedi.');
            $this->redirect('ebelge/yukle');
        }

        try {
            $sonuc = $this->model->yuklemeIsle($dosya, $this->aktifKullaniciId());
        } catch (Throwable $e) {
            // Güvenlik kapısı ve okuma hataları kullanıcıya AYNEN gösterilir:
            // "neden reddedildi" bilgisi kullanıcının işini yapabilmesi için gerekli.
            error_log('[NYMAGRO] e-Belge yükleme reddedildi: ' . $e->getMessage());
            $this->setFlash('error', $e->getMessage());
            $this->redirect('ebelge/yukle');
            return;
        }

        $ozet = $sonuc['ozet'];
        $mesaj = sprintf(
            '%d belge içeri alındı, %d mükerrer atlandı, %d dosya hatalı.',
            $ozet['basarili'],
            $ozet['mukerrer'],
            $ozet['hatali']
        );
        $this->setFlash($ozet['hatali'] > 0 ? 'warning' : 'success', $mesaj);

        $_SESSION['ebelge_son_sonuc'] = array_slice($sonuc['sonuclar'], 0, 200);
        $this->redirect('ebelge?paket_id=' . (int)$sonuc['paket_id']);
    }

    public function detay($id = 0): void
    {
        $id = (int)$id;
        $belge = $this->model->detay($id);
        if (!$belge) {
            $this->setFlash('error', 'e-Belge bulunamadı.');
            $this->redirect('ebelge');
            return;
        }

        $this->view('ebelge/detay', [
            'flash'       => $this->getFlash(),
            'belge'       => $belge,
            'taraflar'    => $this->model->taraflar($id),
            'kalemler'    => $this->model->kalemler($id),
            'vergiler'    => $this->model->vergiler($id),
            'uyarilar'    => EBelge::uyarilariCoz($belge['dogrulama_notlari'] ?? null),
            'topbarTitle' => 'e-Belge Detayı',
            'topbarIcon'  => 'fa-file-code',
        ]);
    }

    /**
     * Ham XML'i indirir.
     *
     * Dosyalar public/uploads altında dursa da doğrudan indirilemez
     * (klasöre runtime'da .htaccess yazılır). Buradaki uç, yetki + şirket
     * kontrolünden GEÇEN tek erişim yoludur; model ayrıca realpath ile
     * public kökünün dışına çıkılmadığını doğrular.
     */
    public function indir($id = 0): void
    {
        $id = (int)$id;
        $dosya = $this->model->hamXmlYolu($id);
        if ($dosya === null) {
            $this->setFlash('error', 'Belgenin ham XML dosyası bulunamadı.');
            $this->redirect('ebelge');
            return;
        }

        Audit::log('VIEW', 'EBELGE', $id, null, null, 'e-Belge ham XML indirildi.', true, $this->aktifKullaniciId());

        header('Content-Type: application/xml; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $dosya['indirme_adi'] . '"');
        header('Content-Length: ' . (string)filesize($dosya['yol']));
        header('X-Content-Type-Options: nosniff');
        readfile($dosya['yol']);
        exit;
    }

    /** Belgeyi reddeder/pasife alır. POST-only; ham XML dosyası SİLİNMEZ (VUK). */
    public function iptal($id = 0): void
    {
        $id = (int)$id;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('ebelge/detay/' . $id);
            return;
        }

        try {
            $this->model->reddet($id, $this->aktifKullaniciId());
            $this->setFlash('success', 'e-Belge reddedildi. Ham XML dosyası saklanmaya devam ediyor.');
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }
        $this->redirect('ebelge');
    }

    // ─────────────────────────────────────────────────────────────────

    private function filtreleriOku(): array
    {
        $izinli = ['belge_tipi', 'durum', 'para_birimi', 'baslangic', 'bitis', 'ara', 'paket_id', 'uyarili'];
        $filtreler = [];
        foreach ($izinli as $anahtar) {
            $filtreler[$anahtar] = trim((string)($_GET[$anahtar] ?? ''));
        }
        return $filtreler;
    }

    private function aktifKullaniciId(): ?int
    {
        return !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }
}
