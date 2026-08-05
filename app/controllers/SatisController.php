<?php
/**
 * Controller: SatisController
 * --------------------------------------------------------
 * URL eşleşmeleri (Router: 'satis' => 'SatisController'):
 *   GET  /satis                → index()
 *   GET  /satis/ekle           → ekle()
 *   POST /satis/kaydet         → kaydet()
 *   GET  /satis/detay/{id}     → detay($id)
 *   GET  /satis/iptal/{id}     → iptal($id)
 *   GET  /satis/sil/{id}       → sil($id)
 *   GET  /satis/musteriBul     → musteriBul()  [JSON AJAX]
 *   GET  /satis/urunBul        → urunBul()     [JSON AJAX]
 */

require_once MODELS_PATH . '/Fatura.php';

final class SatisController extends Controller
{
    private Fatura $fatura;

    private $cariModel;
    public function __construct()
    {
        $this->fatura = new Fatura();
        require_once MODELS_PATH . '/Cari.php';
        $this->cariModel = new Cari();
    }

    // ─── index ──────────────────────────────────────────────────────────

    public function index(): void
    {
        $limit     = 50;
        $sayfa     = max(1, (int)($_GET['sayfa']    ?? 1));
        $arama     = trim($_GET['ara']              ?? '');
        $durum     = trim($_GET['durum']            ?? '');
        $donem     = trim($_GET['donem']            ?? '1ay');
        $iptalleri = !empty($_GET['iptalleri']);

        if (mb_strlen($arama) > 0 && mb_strlen($arama) < 3) {
            $arama = '';
        }

        $offset      = ($sayfa - 1) * $limit;
        $toplam      = $this->fatura->say('satis', $arama, $durum, $donem, $iptalleri);
        $sayfaSayisi = (int)ceil($toplam / $limit);
        $faturalar   = $this->fatura->listele('satis', $arama, $durum, $donem, $iptalleri, $limit, $offset);
        $ozetler     = $this->fatura->ozetToplamlar('satis', $donem);

        $this->view('satislar/index', [
            'faturalar'   => $faturalar,
            'toplam'      => $toplam,
            'ozetler'     => $ozetler,
            'arama'       => $arama,
            'durum'       => $durum,
            'donem'       => $donem,
            'iptalleri'   => $iptalleri,
            'sayfa'       => $sayfa,
            'sayfaSayisi' => $sayfaSayisi,
            'limit'       => $limit,
            'flash'       => $this->getFlash(),
            'topbarTitle' => 'Satışlar',
            'topbarIcon'  => 'fa-shopping-cart',
        ]);
    }

    // ─── ekle ───────────────────────────────────────────────────────────

    public function ekle(): void
    {
        $faturaNo = $this->fatura->faturaNoUret('satis');
        $cariId = isset($_GET['cari_id']) ? (int)$_GET['cari_id'] : null;
        $cari = null;
        if ($cariId) {
            $cari = $this->cariModel->getir($cariId);
        }

        $this->view('satislar/ekle', [
            'faturaNo'    => $faturaNo,
            'bugun'       => date('d.m.Y'),
            'hatalar'     => [],
            'eski'        => [],
            'cari'        => $cari,
            'topbarTitle' => 'Yeni Satış Faturası',
            'topbarIcon'  => 'fa-file-invoice-dollar',
        ]);
    }

    public function fatura($id = 0, string $mode = ''): void
    {
        if ($mode !== 'print') {
            $this->detay((int)$id);
            return;
        }

        $f = $this->fatura->getir((int)$id);
        if (!$f) {
            http_response_code(404);
            die('Fatura bulunamadÄ±.');
        }

        $this->view('satislar/print', [
            'fatura'   => $f,
            'kalemler' => $this->fatura->kalemleriGetir((int)$id),
            'company'  => class_exists('TenantContext') ? TenantContext::activeCompany() : null,
            'settings' => class_exists('TenantContext') ? TenantContext::activeCompanySettings() : [],
        ], 'print');
    }

    // ─── kaydet (POST) ──────────────────────────────────────────────────

    public function kaydet(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('satis/ekle');
        }

        $eski    = $_POST;
        $hatalar = [];

        // ── Doğrulama ──────────────────────────────────
        $faturaNo    = trim($_POST['fatura_no']    ?? '');
        $faturaT     = trim($_POST['fatura_tarihi'] ?? '');
        $belgeTipi = $_POST['belge_tipi'] ?? 'satis';
        if (!in_array($belgeTipi, ['siparis', 'irsaliye', 'proforma', 'satis'])) {
            $belgeTipi = 'satis';
        }
        $durum       = $_POST['durum'] ?? 'taslak';
        $cariId      = !empty($_POST['cari_id']) ? (int)$_POST['cari_id'] : null;
        $paraBirimi  = trim($_POST['para_birimi'] ?? 'TRY');
        $odemeSekli  = trim($_POST['odeme_sekli'] ?? '');
        $aciklama    = trim($_POST['aciklama']    ?? '');
        $vadeTarihi  = trim($_POST['vade_tarihi'] ?? '') ?: null;

        // EĞER numara otomatik üretilen formatta gelmişse ve belge tipi farklıysa, 
        // o tip için yeni bir numara üret (çakışmayı önlemek için)
        $defaultPrefix = 'SAT-' . date('Y');
        if (strpos($faturaNo, $defaultPrefix) === 0 && $belgeTipi !== 'satis') {
            $faturaNo = $this->fatura->faturaNoUret($belgeTipi);
        }

        if ($faturaNo === '') {
            $hatalar['fatura_no'] = 'Fatura no zorunludur.';
        }
        if ($faturaT === '') {
            $hatalar['fatura_tarihi'] = 'Fatura tarihi zorunludur.';
        } else {
            // dd.mm.yyyy → yyyy-mm-dd
            $faturaT = $this->tarihCevir($faturaT);
        }
        if ($vadeTarihi !== null) {
            $vadeTarihi = $this->tarihCevir($vadeTarihi);
        }

        // ── Kalemler ───────────────────────────────────
        $kalemAdlari    = $_POST['kalem_urun_adi']      ?? [];
        $kalemUrunId    = $_POST['kalem_urun_id']       ?? [];
        $kalemMiktar    = $_POST['kalem_miktar']        ?? [];
        $kalemFiyat     = $_POST['kalem_birim_fiyat']   ?? [];
        $kalemKdv       = $_POST['kalem_kdv_orani']     ?? [];
        $kalemIskonto   = $_POST['kalem_iskonto_orani'] ?? [];
        $kalemBirim     = $_POST['kalem_birim']         ?? [];

        $kalemler = [];
        foreach ($kalemAdlari as $i => $ad) {
            $ad = trim($ad);
            if ($ad === '') continue;
            $kalemler[] = [
                'urun_id'       => !empty($kalemUrunId[$i]) ? (int)$kalemUrunId[$i] : null,
                'urun_adi'      => $ad,
                'miktar'        => max(0.001, (float)str_replace(',', '.', $kalemMiktar[$i] ?? '1')),
                'birim_fiyat'   => max(0, (float)str_replace(',', '.', $kalemFiyat[$i] ?? '0')),
                'kdv_orani'     => (float)($kalemKdv[$i] ?? 20),
                'iskonto_orani' => (float)($kalemIskonto[$i] ?? 0),
                'birim'         => $kalemBirim[$i] ?? 'Adet',
            ];
        }

        if (empty($kalemler)) {
            $hatalar['kalemler'] = 'En az bir ürün/hizmet kalemi ekleyin.';
        }

        if (!empty($hatalar)) {
            $this->view('satislar/ekle', [
                'faturaNo'    => $faturaNo ?: $this->fatura->faturaNoUret('satis'),
                'bugun'       => date('d.m.Y'),
                'hatalar'     => $hatalar,
                'eski'        => $eski,
                'topbarTitle' => 'Yeni Satış Faturası',
                'topbarIcon'  => 'fa-file-invoice-dollar',
            ]);
            return;
        }

        // ── Toplamları hesapla ─────────────────────────
        $araToplam    = 0;
        $iskontoTutar = 0;
        $kdvTutar     = 0;
        foreach ($kalemler as $k) {
            $at  = $k['miktar'] * $k['birim_fiyat'];
            $it  = $at * ($k['iskonto_orani'] / 100);
            $kdt = ($at - $it) * ($k['kdv_orani'] / 100);
            $araToplam    += $at;
            $iskontoTutar += $it;
            $kdvTutar     += $kdt;
        }
        $genelToplam = $araToplam - $iskontoTutar + $kdvTutar;
        $odenenTutar = 0;
        $kalanTutar  = $genelToplam;

        $faturaVeri = [
            'belge_tipi'     => $belgeTipi,
            'fatura_no'      => $faturaNo,
            'cari_id'        => $cariId,
            'fatura_tarihi'  => $faturaT,
            'vade_tarihi'    => $vadeTarihi,
            'ara_toplam'     => round($araToplam, 2),
            'iskonto_tutari' => round($iskontoTutar, 2),
            'kdv_tutari'     => round($kdvTutar, 2),
            'genel_toplam'   => round($genelToplam, 2),
            'odenen_tutar'   => $odenenTutar,
            'kalan_tutar'    => round($kalanTutar, 2),
            'para_birimi'    => $paraBirimi,
            'kur'            => 1.000000,
            'durum'          => $durum === 'taslak' ? 'taslak' : 'onaylandi',
            'odeme_sekli'    => $odemeSekli ?: null,
            'aciklama'       => $aciklama   ?: null,
        ];

        $depoId = !empty($_POST['depo_id']) ? (int)$_POST['depo_id'] : 1;
        $yeniFaturaId = $this->fatura->ekle($faturaVeri, $kalemler, $depoId);

        $this->setFlash('success', "Fatura #{$faturaNo} başarıyla kaydedildi.");
        $this->redirect('satis');
    }

        // ─── perakende ──────────────────────────────────────────────────────
    public function perakende(): void
    {
        $this->view('satislar/perakende', [
            'bugun'       => date('Y-m-d'),
            'simdi'       => date('H:i'),
            'topbarTitle' => 'Perakende Satış Gir',
            'topbarIcon'  => 'fa-cash-register',
            'faturaNo'    => $this->fatura->faturaNoUret('perakende'),
        ]);
    }

    public function perakende_kaydet(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('satis/perakende');
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            echo json_encode(['status' => 'error', 'message' => 'Geçersiz veri.']);
            return;
        }

        $kalemler = [];
        foreach ($data['sepet'] as $item) {
            $kalemler[] = [
                'urun_id'       => $item['id'],
                'urun_adi'      => $item['ad'],
                'miktar'        => (float)$item['miktar'],
                'birim_fiyat'   => (float)$item['fiyat'],
                'kdv_orani'     => (float)$item['kdv'],
                'iskonto_orani' => 0,
                'birim'         => $item['birim'] ?? 'Adet',
            ];
        }

        $faturaNo = $this->fatura->faturaNoUret('perakende');
        $faturaVeri = [
            'belge_tipi'     => 'perakende',
            'fatura_no'      => $faturaNo,
            'cari_id'        => null, // Perakende satışta müşteri genelde boştur
            'fatura_tarihi'  => $data['tarih'],
            'ara_toplam'     => (float)$data['araToplam'],
            'kdv_tutari'     => (float)$data['kdvToplam'],
            'genel_toplam'   => (float)$data['genelToplam'],
            'para_birimi'    => 'TRY',
            'durum'          => 'onaylandi',
            'aciklama'       => $data['aciklama'] ?? 'Perakende Satış',
        ];

        try {
            $this->fatura->ekle($faturaVeri, $kalemler, 1);
            echo json_encode(['status' => 'success', 'message' => 'Satış kaydedildi.', 'fatura_no' => $faturaNo]);
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ─── detay ──────────────────────────────────────────────────────────

    public function detay(int $id): void
    {
        $f = $this->fatura->getir($id);
        if (!$f) {
            http_response_code(404);
            die('Fatura bulunamadı.');
        }
        $kalemler = $this->fatura->kalemleriGetir($id);
        $company = class_exists('TenantContext') ? TenantContext::activeCompany() : null;
        $settings = class_exists('TenantContext') ? TenantContext::activeCompanySettings() : [];

        $this->view('satislar/detay', [
            'fatura'      => $f,
            'kalemler'    => $kalemler,
            'company'      => $company,
            'settings'     => $settings,
            'flash'       => $this->getFlash(),
            'topbarTitle' => 'Fatura Detayı — ' . htmlspecialchars($f['fatura_no']),
            'topbarIcon'  => 'fa-file-invoice-dollar',
        ]);
    }

    // ─── iptal ──────────────────────────────────────────────────────────

    public function iptal(int $id): void
    {
        $f = $this->fatura->getir($id);
        if ($f) {
            $this->fatura->iptalEt($id);
            $this->setFlash('success', "Fatura #{$f['fatura_no']} iptal edildi.");
        } else {
            $this->setFlash('error', 'Fatura bulunamadı.');
        }
        $this->redirect('satis');
    }

    // ─── sil ────────────────────────────────────────────────────────────

    public function sil(int $id): void
    {
        $f = $this->fatura->getir($id);
        if ($f) {
            $this->fatura->sil($id);
            $this->setFlash('success', "Fatura #{$f['fatura_no']} silindi.");
        } else {
            $this->setFlash('error', 'Fatura bulunamadı.');
        }
        $this->redirect('satis');
    }

    // ─── AJAX: Müşteri Ara ───────────────────────────────────────────────

    public function musteriBul(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $q = trim($_GET['q'] ?? '');
        if ($q === 'all') {
            $sonuclar = $this->fatura->cariAra('', 'musteri');
            echo json_encode($sonuclar);
            exit;
        }
        if (mb_strlen($q) < 2) {
            echo json_encode([]);
            exit;
        }
        $sonuclar = $this->fatura->cariAra($q, 'musteri');
        echo json_encode($sonuclar);
        exit;
    }

    // ─── AJAX: Ürün Ara ─────────────────────────────────────────────────

    public function urunBul(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $q = trim($_GET['q'] ?? '');
        if (mb_strlen($q) < 2) {
            echo json_encode([]);
            exit;
        }
        $sonuclar = $this->fatura->urunAra($q);
        echo json_encode($sonuclar);
        exit;
    }

    // ─── Private Helpers ────────────────────────────────────────────────

    /** dd.mm.yyyy → yyyy-mm-dd, ya da zaten yyyy-mm-dd olan kabul et */
    private function tarihCevir(string $t): string
    {
        if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $t)) {
            [$g, $a, $y] = explode('.', $t);
            return "{$y}-{$a}-{$g}";
        }
        return $t; // zaten yyyy-mm-dd
    }

}
