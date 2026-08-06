<?php
/**
 * Controller: AlisController
 * --------------------------------------------------------
 */

require_once MODELS_PATH . '/Fatura.php';

final class AlisController extends Controller
{
    private Fatura $fatura;
    private $cariModel;

    public function __construct()
    {
        $this->fatura = new Fatura();
        require_once MODELS_PATH . '/Cari.php';
        $this->cariModel = new Cari();
    }

    public function index(): void
    {
        $limit     = 50;
        $sayfa     = max(1, (int)($_GET['sayfa']    ?? 1));
        $arama     = trim($_GET['ara']              ?? '');
        $durum     = trim($_GET['durum']            ?? '');
        $donem     = trim($_GET['donem']            ?? '1ay');

        $offset      = ($sayfa - 1) * $limit;
        $toplam      = $this->fatura->say('alis', $arama, $durum, $donem);
        $sayfaSayisi = (int)ceil($toplam / $limit);
        $faturalar   = $this->fatura->listele('alis', $arama, $durum, $donem, false, $limit, $offset);
        $ozetler     = $this->fatura->ozetToplamlar('alis', $donem);

        $this->view('alislar/index', [
            'faturalar'   => $faturalar,
            'toplam'      => $toplam,
            'ozetler'     => $ozetler,
            'arama'       => $arama,
            'durum'       => $durum,
            'donem'       => $donem,
            'sayfa'       => $sayfa,
            'sayfaSayisi' => $sayfaSayisi,
            'limit'       => $limit,
            'flash'       => $this->getFlash(),
            'pageTitle'   => 'Alışlar',
            'topbarTitle' => 'Alışlar',
            'topbarIcon'  => 'fa-solid fa-truck',
            'activeMenu'  => 'alislar'
        ]);
    }

    public function ekle(): void
    {
        $faturaNo = $this->fatura->faturaNoUret('alis');
        $cariId = isset($_GET['cari_id']) ? (int)$_GET['cari_id'] : null;
        $cari = $cariId ? $this->cariModel->getir($cariId) : null;
        $tedarikciAdi = $_GET['tedarikci'] ?? '';

        $this->view('alislar/ekle', [
            'faturaNo'    => $faturaNo,
            'bugun'       => date('d.m.Y'),
            'hatalar'     => [],
            'eski'        => [],
            'cari'        => $cari,
            'tedarikciAdi' => $tedarikciAdi,
            'topbarTitle' => 'Yeni Alış Faturası',
            'topbarIcon'  => 'fa-solid fa-truck',
            'activeMenu'  => 'alislar'
        ]);
    }

    public function kaydet(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('alis/ekle');
        }

        $hatalar = [];
        $faturaNo = trim($_POST['fatura_no'] ?? '');
        $faturaT  = trim($_POST['fatura_tarihi'] ?? '');
        $cariId   = !empty($_POST['cari_id']) ? (int)$_POST['cari_id'] : null;
        $depoId   = !empty($_POST['depo_id']) ? (int)$_POST['depo_id'] : 1;

        if ($faturaNo === '') $hatalar['fatura_no'] = 'Fatura no zorunludur.';
        if ($faturaT === '')  $hatalar['fatura_tarihi'] = 'Tarih zorunludur.';

        $kalemAdlari = $_POST['kalem_urun_adi'] ?? [];
        $kalemler = [];
        foreach ($kalemAdlari as $i => $ad) {
            if (trim($ad) === '') continue;
            $kalemler[] = [
                'urun_id'       => !empty($_POST['kalem_urun_id'][$i]) ? (int)$_POST['kalem_urun_id'][$i] : null,
                'urun_adi'      => trim($ad),
                'miktar'        => (float)str_replace(',', '.', $_POST['kalem_miktar'][$i] ?? '1'),
                'birim_fiyat'   => (float)str_replace(',', '.', $_POST['kalem_birim_fiyat'][$i] ?? '0'),
                'kdv_orani'     => (float)($_POST['kalem_kdv_orani'][$i] ?? 20),
                'iskonto_orani' => (float)($_POST['kalem_iskonto_orani'][$i] ?? 0),
                'birim'         => $_POST['kalem_birim'][$i] ?? 'Adet',
            ];
        }

        if (empty($kalemler)) $hatalar['kalemler'] = 'En az bir kalem ekleyin.';

        if (!empty($hatalar)) {
            $this->view('alislar/ekle', [
                'faturaNo' => $faturaNo,
                'bugun'    => date('d.m.Y'),
                'hatalar'  => $hatalar,
                'eski'     => $_POST,
                'activeMenu' => 'alislar'
            ]);
            return;
        }

        // Toplamları hesapla
        $araToplam = 0; $iskontoTutar = 0; $kdvTutar = 0;
        foreach ($kalemler as $k) {
            $lineTotal = $k['miktar'] * $k['birim_fiyat'];
            $lineIsk   = $lineTotal * ($k['iskonto_orani'] / 100);
            $lineKdv   = ($lineTotal - $lineIsk) * ($k['kdv_orani'] / 100);
            $araToplam += $lineTotal; $iskontoTutar += $lineIsk; $kdvTutar += $lineKdv;
        }
        $genelToplam = $araToplam - $iskontoTutar + $kdvTutar;

        $faturaVeri = [
            'belge_tipi'     => 'alis',
            'fatura_no'      => $faturaNo,
            'cari_id'        => $cariId,
            'fatura_tarihi'  => $this->tarihCevir($faturaT),
            'ara_toplam'     => round($araToplam, 2),
            'iskonto_tutari' => round($iskontoTutar, 2),
            'kdv_tutari'     => round($kdvTutar, 2),
            'genel_toplam'   => round($genelToplam, 2),
            'para_birimi'    => $_POST['para_birimi'] ?? 'TRY',
            'durum'          => 'onaylandi'
        ];

        $this->fatura->ekle($faturaVeri, $kalemler, $depoId);

        $this->setFlash('success', "Alış faturası #{$faturaNo} kaydedildi.");
        $this->redirect('alis');
    }

    public function detay(int $id): void
    {
        $f = $this->fatura->getir($id);
        if (!$f) $this->redirect('alis');
        
        $this->view('alislar/detay', [
            'fatura'      => $f,
            'kalemler'    => $this->fatura->kalemleriGetir($id),
            'company'     => class_exists('TenantContext') ? TenantContext::activeCompany() : null,
            'settings'    => class_exists('TenantContext') ? TenantContext::activeCompanySettings() : [],
            'topbarTitle' => 'Alış Detayı — ' . $f['fatura_no'],
            'topbarIcon'  => 'fa-solid fa-truck',
            'activeMenu'  => 'alislar'
        ]);
    }

    public function iptal(int $id): void
    {
        $f = $this->fatura->getir($id);
        if ($f) {
            $this->fatura->iptalEt($id);
            $this->setFlash('success', "Fatura #{$f['fatura_no']} iptal edildi.");
        } else {
            $this->setFlash('error', 'Fatura bulunamadı.');
        }
        $this->redirect('alis');
    }

    public function sil(int $id): void
    {
        $f = $this->fatura->getir($id);
        if ($f) {
            $this->fatura->sil($id);
            $this->setFlash('success', "Fatura #{$f['fatura_no']} silindi.");
        } else {
            $this->setFlash('error', 'Fatura bulunamadı.');
        }
        $this->redirect('alis');
    }

    private function tarihCevir(string $t): string
    {
        if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $t)) {
            [$g, $a, $y] = explode('.', $t);
            return "{$y}-{$a}-{$g}";
        }
        return $t;
    }
}
