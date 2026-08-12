<?php
/**
 * Controller: AlisController
 * --------------------------------------------------------
 */

require_once MODELS_PATH . '/Fatura.php';
require_once MODELS_PATH . '/Depo.php';

final class AlisController extends Controller
{
    private Fatura $fatura;
    private $cariModel;
    private Depo $depoModel;

    public function __construct()
    {
        $this->fatura = new Fatura();
        require_once MODELS_PATH . '/Cari.php';
        $this->cariModel = new Cari();
        $this->depoModel = new Depo();
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
            'bugun'       => date('Y-m-d'),
            'hatalar'     => [],
            'eski'        => [],
            'cari'        => $cari,
            'tedarikciAdi' => $tedarikciAdi,
            'depolar'     => $this->depoModel->listele(),
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

        // ── Döviz ────────────────────────────────────────
        $paraBirimi = trim($_POST['para_birimi'] ?? 'TRY');
        if (!in_array($paraBirimi, ['TRY', 'USD', 'EUR', 'GBP'], true)) {
            $paraBirimi = 'TRY';
        }
        $kur = $paraBirimi === 'TRY' ? 1.0 : (float)str_replace(',', '.', $_POST['kur'] ?? '0');
        if ($paraBirimi !== 'TRY' && $kur <= 0) {
            $hatalar['kur'] = 'Döviz seçildiğinde kur girilmesi zorunludur.';
        }

        $kalemAdlari = $_POST['kalem_urun_adi'] ?? [];
        $kalemUrunId = $_POST['kalem_urun_id'] ?? [];
        $kalemGirisTipi = $_POST['kalem_giris_tipi'] ?? [];

        // "Koli" girişini adete çevirmek için sunucu tarafında yetkili kaynak.
        $koliMap = $this->fatura->koliIciAdetMap($kalemUrunId);

        $kalemler = [];
        foreach ($kalemAdlari as $i => $ad) {
            if (trim($ad) === '') continue;
            $urunId = !empty($kalemUrunId[$i]) ? (int)$kalemUrunId[$i] : null;
            $girisTipi = ($kalemGirisTipi[$i] ?? 'adet') === 'koli' ? 'koli' : 'adet';
            $miktarGirilen = (float)str_replace(',', '.', $_POST['kalem_miktar'][$i] ?? '1');
            $birimFiyatGirilen = (float)str_replace(',', '.', $_POST['kalem_birim_fiyat'][$i] ?? '0');
            $kalemler[] = [
                'urun_id'       => $urunId,
                'urun_adi'      => trim($ad),
                'miktar'        => Fatura::kalemMiktarCevir($urunId, $miktarGirilen, $girisTipi, $koliMap),
                'birim_fiyat'   => $birimFiyatGirilen * $kur,
                'kdv_orani'     => (float)($_POST['kalem_kdv_orani'][$i] ?? 20),
                'iskonto_orani' => (float)($_POST['kalem_iskonto_orani'][$i] ?? 0),
                'birim'         => $_POST['kalem_birim'][$i] ?? 'Adet',
            ];
        }

        if (empty($kalemler)) $hatalar['kalemler'] = 'En az bir kalem ekleyin.';

        if (!empty($hatalar)) {
            $this->view('alislar/ekle', [
                'faturaNo' => $faturaNo,
                'bugun'    => date('Y-m-d'),
                'hatalar'  => $hatalar,
                'eski'     => $_POST,
                'depolar'  => $this->depoModel->listele(),
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

        $araToplamDoviz = $iskontoTutarDoviz = $kdvTutarDoviz = $genelToplamDoviz = null;
        if ($paraBirimi !== 'TRY' && $kur > 0) {
            $araToplamDoviz    = round($araToplam / $kur, 2);
            $iskontoTutarDoviz = round($iskontoTutar / $kur, 2);
            $kdvTutarDoviz     = round($kdvTutar / $kur, 2);
            $genelToplamDoviz  = round($genelToplam / $kur, 2);
        }

        $faturaVeri = [
            'belge_tipi'          => 'alis',
            'fatura_no'           => $faturaNo,
            'cari_id'             => $cariId,
            'fatura_tarihi'       => $this->tarihCevir($faturaT),
            'ara_toplam'          => round($araToplam, 2),
            'iskonto_tutari'      => round($iskontoTutar, 2),
            'kdv_tutari'          => round($kdvTutar, 2),
            'genel_toplam'        => round($genelToplam, 2),
            'para_birimi'         => $paraBirimi,
            'kur'                 => $kur,
            'ara_toplam_doviz'     => $araToplamDoviz,
            'iskonto_tutari_doviz' => $iskontoTutarDoviz,
            'kdv_tutari_doviz'     => $kdvTutarDoviz,
            'genel_toplam_doviz'   => $genelToplamDoviz,
            'durum'          => 'onaylandi',
            'created_by'     => class_exists('TenantContext') ? TenantContext::userId() : null,
        ];

        $this->fatura->ekle($faturaVeri, $kalemler, $depoId);

        $this->setFlash('success', "Alış faturası #{$faturaNo} kaydedildi.");
        $this->redirect('alis');
    }

    public function duzenle(int $id): void
    {
        $f = $this->fatura->getir($id);
        if (!$f) {
            $this->setFlash('error', 'Fatura bulunamadı.');
            $this->redirect('alis');
        }
        if ($f['durum'] === 'iptal') {
            $this->setFlash('error', 'İptal edilmiş fatura düzenlenemez.');
            $this->redirect('alis/detay/' . $id);
        }

        $eski = $f;
        $eski['fatura_tarihi'] = $f['fatura_tarihi'] ? substr((string)$f['fatura_tarihi'], 0, 10) : '';

        $cari = !empty($f['cari_id']) ? $this->cariModel->getir((int)$f['cari_id']) : null;

        $this->view('alislar/duzenle', [
            'fatura'      => $f,
            'kalemler'    => $this->fatura->kalemleriGetir($id),
            'faturaNo'    => $f['fatura_no'],
            'bugun'       => $eski['fatura_tarihi'],
            'hatalar'     => [],
            'eski'        => $eski,
            'cari'        => $cari,
            'depolar'     => $this->depoModel->listele(),
            'topbarTitle' => 'Alış Faturası Düzenle — ' . $f['fatura_no'],
            'topbarIcon'  => 'fa-solid fa-truck',
            'activeMenu'  => 'alislar'
        ]);
    }

    public function guncelle(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('alis/duzenle/' . $id);
        }

        $mevcut = $this->fatura->getir($id);
        if (!$mevcut) {
            $this->setFlash('error', 'Fatura bulunamadı.');
            $this->redirect('alis');
        }

        $hatalar  = [];
        $faturaNo = trim($_POST['fatura_no'] ?? '');
        $faturaT  = trim($_POST['fatura_tarihi'] ?? '');
        $cariId   = !empty($_POST['cari_id']) ? (int)$_POST['cari_id'] : null;
        $depoId   = !empty($_POST['depo_id']) ? (int)$_POST['depo_id'] : (int)($mevcut['depo_id'] ?: 1);

        if ($faturaNo === '') $hatalar['fatura_no'] = 'Fatura no zorunludur.';
        if ($faturaT === '')  $hatalar['fatura_tarihi'] = 'Tarih zorunludur.';

        // ── Döviz ────────────────────────────────────────
        $paraBirimi = trim($_POST['para_birimi'] ?? 'TRY');
        if (!in_array($paraBirimi, ['TRY', 'USD', 'EUR', 'GBP'], true)) {
            $paraBirimi = 'TRY';
        }
        $kur = $paraBirimi === 'TRY' ? 1.0 : (float)str_replace(',', '.', $_POST['kur'] ?? '0');
        if ($paraBirimi !== 'TRY' && $kur <= 0) {
            $hatalar['kur'] = 'Döviz seçildiğinde kur girilmesi zorunludur.';
        }

        $kalemAdlari = $_POST['kalem_urun_adi'] ?? [];
        $kalemUrunId = $_POST['kalem_urun_id'] ?? [];
        $kalemGirisTipi = $_POST['kalem_giris_tipi'] ?? [];

        // "Koli" girişini adete çevirmek için sunucu tarafında yetkili kaynak.
        $koliMap = $this->fatura->koliIciAdetMap($kalemUrunId);

        $kalemler = [];
        foreach ($kalemAdlari as $i => $ad) {
            if (trim($ad) === '') continue;
            $urunId = !empty($kalemUrunId[$i]) ? (int)$kalemUrunId[$i] : null;
            $girisTipi = ($kalemGirisTipi[$i] ?? 'adet') === 'koli' ? 'koli' : 'adet';
            $miktarGirilen = (float)str_replace(',', '.', $_POST['kalem_miktar'][$i] ?? '1');
            $birimFiyatGirilen = (float)str_replace(',', '.', $_POST['kalem_birim_fiyat'][$i] ?? '0');
            $kalemler[] = [
                'urun_id'       => $urunId,
                'urun_adi'      => trim($ad),
                'miktar'        => Fatura::kalemMiktarCevir($urunId, $miktarGirilen, $girisTipi, $koliMap),
                'birim_fiyat'   => $birimFiyatGirilen * $kur,
                'kdv_orani'     => (float)($_POST['kalem_kdv_orani'][$i] ?? 20),
                'iskonto_orani' => (float)($_POST['kalem_iskonto_orani'][$i] ?? 0),
                'birim'         => $_POST['kalem_birim'][$i] ?? 'Adet',
            ];
        }

        if (empty($kalemler)) $hatalar['kalemler'] = 'En az bir kalem ekleyin.';

        if (!empty($hatalar)) {
            $this->view('alislar/duzenle', [
                'fatura'      => $mevcut,
                'kalemler'    => $this->fatura->kalemleriGetir($id),
                'faturaNo'    => $faturaNo,
                'bugun'       => $mevcut['fatura_tarihi'] ? substr((string)$mevcut['fatura_tarihi'], 0, 10) : '',
                'hatalar'     => $hatalar,
                'eski'        => $_POST,
                'cari'        => $cariId ? $this->cariModel->getir($cariId) : null,
                'depolar'     => $this->depoModel->listele(),
                'topbarTitle' => 'Alış Faturası Düzenle — ' . $mevcut['fatura_no'],
                'topbarIcon'  => 'fa-solid fa-truck',
                'activeMenu'  => 'alislar'
            ]);
            return;
        }

        $araToplam = 0; $iskontoTutar = 0; $kdvTutar = 0;
        foreach ($kalemler as $k) {
            $lineTotal = $k['miktar'] * $k['birim_fiyat'];
            $lineIsk   = $lineTotal * ($k['iskonto_orani'] / 100);
            $lineKdv   = ($lineTotal - $lineIsk) * ($k['kdv_orani'] / 100);
            $araToplam += $lineTotal; $iskontoTutar += $lineIsk; $kdvTutar += $lineKdv;
        }
        $genelToplam = $araToplam - $iskontoTutar + $kdvTutar;

        $araToplamDoviz = $iskontoTutarDoviz = $kdvTutarDoviz = $genelToplamDoviz = null;
        if ($paraBirimi !== 'TRY' && $kur > 0) {
            $araToplamDoviz    = round($araToplam / $kur, 2);
            $iskontoTutarDoviz = round($iskontoTutar / $kur, 2);
            $kdvTutarDoviz     = round($kdvTutar / $kur, 2);
            $genelToplamDoviz  = round($genelToplam / $kur, 2);
        }

        $faturaVeri = [
            'belge_tipi'          => $mevcut['belge_tipi'],
            'fatura_no'           => $faturaNo,
            'cari_id'             => $cariId,
            'fatura_tarihi'       => $this->tarihCevir($faturaT),
            'ara_toplam'          => round($araToplam, 2),
            'iskonto_tutari'      => round($iskontoTutar, 2),
            'kdv_tutari'          => round($kdvTutar, 2),
            'genel_toplam'        => round($genelToplam, 2),
            'kalan_tutar'         => round($genelToplam - (float)($mevcut['odenen_tutar'] ?? 0), 2),
            'para_birimi'         => $paraBirimi,
            'kur'                 => $kur,
            'ara_toplam_doviz'     => $araToplamDoviz,
            'iskonto_tutari_doviz' => $iskontoTutarDoviz,
            'kdv_tutari_doviz'     => $kdvTutarDoviz,
            'genel_toplam_doviz'   => $genelToplamDoviz,
            'durum'          => $mevcut['durum'],
        ];

        try {
            $this->fatura->guncelle($id, $faturaVeri, $kalemler, $depoId);
            $this->setFlash('success', "Alış faturası #{$faturaNo} güncellendi.");
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }
        $this->redirect('alis/detay/' . $id);
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
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('alis');
        }
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
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('alis');
        }
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

    /** yyyy-mm-dd → dd.mm.yyyy (düzenleme formunda göstermek için) */
    private function tarihGoster(?string $t): string
    {
        if ($t && preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $t, $m)) {
            return "{$m[3]}.{$m[2]}.{$m[1]}";
        }
        return (string)$t;
    }
}
