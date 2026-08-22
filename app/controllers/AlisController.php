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
        // "İrsaliye Kaydet" ile oluşturulan belgeler (belge_tipi='irsaliye', sevk_turu='tedarikci')
        // aynı ekrandan girildiği için, gözlemlenebilmeleri adına aynı listede bir sekme olarak sunulur.
        $belgeTipi = ($_GET['tip'] ?? '') === 'irsaliye' ? 'irsaliye' : 'alis';

        $offset      = ($sayfa - 1) * $limit;
        $toplam      = $this->fatura->say($belgeTipi, $arama, $durum, $donem);
        $sayfaSayisi = (int)ceil($toplam / $limit);
        $faturalar   = $this->fatura->listele($belgeTipi, $arama, $durum, $donem, false, $limit, $offset);
        $ozetler     = $this->fatura->ozetToplamlar($belgeTipi, $donem);

        $this->view('alislar/index', [
            'faturalar'   => $faturalar,
            'toplam'      => $toplam,
            'ozetler'     => $ozetler,
            'arama'       => $arama,
            'durum'       => $durum,
            'donem'       => $donem,
            'belgeTipi'   => $belgeTipi,
            'sayfa'       => $sayfa,
            'sayfaSayisi' => $sayfaSayisi,
            'limit'       => $limit,
            'flash'       => $this->getFlash(),
            'pageTitle'   => $belgeTipi === 'irsaliye' ? 'İrsaliyeler' : 'Alışlar',
            'topbarTitle' => $belgeTipi === 'irsaliye' ? 'İrsaliyeler' : 'Alışlar',
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

        // İrsaliyeler sekmesindeki "Faturalandır" bağlantısıyla gelindiyse, o
        // irsaliyeyi sayfa yüklenir yüklenmez form JS'i otomatik dolduracak.
        $presetKaynakIrsaliyeId = isset($_GET['kaynak_irsaliye_id']) ? (int)$_GET['kaynak_irsaliye_id'] : null;

        $this->view('alislar/ekle', [
            'faturaNo'    => $faturaNo,
            'bugun'       => date('Y-m-d'),
            'hatalar'     => [],
            'eski'        => [],
            'cari'        => $cari,
            'tedarikciAdi' => $tedarikciAdi,
            'depolar'     => $this->depoModel->listele(),
            'acikIrsaliyeler' => $this->fatura->faturalandirilmamisIrsaliyeler($cariId, 100, 'tedarikci'),
            'presetKaynakIrsaliyeId' => $presetKaynakIrsaliyeId,
            'flash'       => $this->getFlash(),
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

        // ── Belge tipi ────────────────────────────────────
        // Önceden bu satır her zaman 'alis' idi — hangi butona (Proforma/Sipariş,
        // İrsaliye, Fatura) basılırsa basılsın "İrsaliye Kaydet" fiilen çalışmıyordu.
        $belgeTipi = $_POST['belge_tipi'] ?? 'alis';
        if (!in_array($belgeTipi, ['siparis', 'irsaliye', 'alis'], true)) {
            $belgeTipi = 'alis';
        }
        $durum = $belgeTipi === 'alis' ? 'onaylandi' : 'taslak';

        // Belge numarası HER ZAMAN kaydedilen belge tipinin serisinden çözülür
        // (bkz. Fatura::belgeNoCozumle) — ön ek sabit değil, şirket ayarından gelir.
        $faturaNo = $this->fatura->belgeNoCozumle($faturaNo, $_POST['fatura_no_oto'] ?? '', $belgeTipi);
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

        // İrsaliye Kaydet her zaman "tedarikçiden alım" yönündedir — mal fiilen
        // depoya girer, cari borcu/gider henüz oluşmaz (o, alış faturasında oluşur).
        // Kendi depolarınız arası transfer bu ekrandan değil Satışlar ekranındaki
        // "Depolar Arası Sevk" seçeneğinden yapılır.
        $sevkTuru = $belgeTipi === 'irsaliye' ? 'tedarikci' : null;

        // ── İrsaliyeden doldurma (yalnızca Fatura Kaydet için anlamlıdır) ─
        // Mal irsaliye ile teslim alınırken zaten depoya girdiği için, bu
        // irsaliyeden doldurulan faturada stok bir daha hareket ettirilmez.
        $kaynakIrsaliyeId = null;
        if ($belgeTipi === 'alis' && !empty($_POST['kaynak_irsaliye_id'])) {
            $adayId = (int)$_POST['kaynak_irsaliye_id'];
            $aday = $this->fatura->getir($adayId);
            $gecerli = $aday
                && $aday['belge_tipi'] === 'irsaliye'
                && ($aday['sevk_turu'] ?? '') === 'tedarikci'
                && (int)($aday['irsaliye_kullanildi'] ?? 0) === 0
                && $aday['durum'] !== 'iptal';
            if ($gecerli) {
                $kaynakIrsaliyeId = $adayId;
            } else {
                $hatalar['kaynak_irsaliye_id'] = 'Seçilen irsaliye artık faturalandırılamıyor (bulunamadı, iptal edilmiş ya da zaten faturalandırılmış olabilir).';
            }
        }

        $kalemAdlari = $_POST['kalem_urun_adi'] ?? [];
        $kalemUrunId = $_POST['kalem_urun_id'] ?? [];
        $kalemGirisTipi = $_POST['kalem_giris_tipi'] ?? [];

        // "Koli" girişini adete çevirmek için sunucu tarafında yetkili kaynak.
        $koliMap = $this->fatura->koliIciAdetMap($kalemUrunId);

        // Kalem doğrulaması satış tarafıyla aynı kapıdan geçer (bkz. Fatura::kalemNormalize).
        $kalemler = [];
        foreach ($kalemAdlari as $i => $ad) {
            if (trim((string)$ad) === '') continue;
            try {
                $kalemler[] = Fatura::kalemNormalize([
                    'urun_id'       => $kalemUrunId[$i] ?? null,
                    'urun_adi'      => $ad,
                    'miktar'        => $_POST['kalem_miktar'][$i] ?? null,
                    'birim_fiyat'   => $_POST['kalem_birim_fiyat'][$i] ?? null,
                    'kdv_orani'     => $_POST['kalem_kdv_orani'][$i] ?? null,
                    'iskonto_orani' => $_POST['kalem_iskonto_orani'][$i] ?? null,
                    'birim'         => $_POST['kalem_birim'][$i] ?? 'Adet',
                    'giris_tipi'    => $kalemGirisTipi[$i] ?? 'adet',
                ], $koliMap, $kur);
            } catch (InvalidArgumentException $e) {
                $hatalar['kalemler'] = $e->getMessage();
                break;
            }
        }

        if (empty($hatalar['kalemler']) && empty($kalemler)) $hatalar['kalemler'] = 'En az bir kalem ekleyin.';

        if (!empty($hatalar)) {
            $this->view('alislar/ekle', [
                'faturaNo' => $faturaNo,
                'bugun'    => date('Y-m-d'),
                'hatalar'  => $hatalar,
                'eski'     => $_POST,
                'depolar'  => $this->depoModel->listele(),
                'acikIrsaliyeler' => $this->fatura->faturalandirilmamisIrsaliyeler($cariId, 100, 'tedarikci'),
                'presetKaynakIrsaliyeId' => $kaynakIrsaliyeId,
                'activeMenu' => 'alislar'
            ]);
            return;
        }

        // Toplamlar kalemlerden hesaplanır — formül tek yerde durur (bkz. Fatura::kalemToplamlari).
        $toplamlar    = Fatura::kalemToplamlari($kalemler);
        $araToplam    = $toplamlar['ara_toplam'];
        $iskontoTutar = $toplamlar['iskonto_tutari'];
        $kdvTutar     = $toplamlar['kdv_tutari'];
        $genelToplam  = $toplamlar['genel_toplam'];

        $araToplamDoviz = $iskontoTutarDoviz = $kdvTutarDoviz = $genelToplamDoviz = null;
        if ($paraBirimi !== 'TRY' && $kur > 0) {
            $araToplamDoviz    = round($araToplam / $kur, 2);
            $iskontoTutarDoviz = round($iskontoTutar / $kur, 2);
            $kdvTutarDoviz     = round($kdvTutar / $kur, 2);
            $genelToplamDoviz  = round($genelToplam / $kur, 2);
        }

        $faturaVeri = [
            'belge_tipi'          => $belgeTipi,
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
            'durum'          => $durum,
            'created_by'     => class_exists('TenantContext') ? TenantContext::userId() : null,
            'sevk_turu'          => $sevkTuru,
            'kaynak_irsaliye_id' => $kaynakIrsaliyeId,
        ];

        try {
            $this->fatura->ekle($faturaVeri, $kalemler, $depoId);
        } catch (\Throwable $e) {
            $this->setFlash('error', $e->getMessage());
            $this->redirect('alis/ekle');
        }

        $belgeEtiketi = ['irsaliye' => 'İrsaliye', 'siparis' => 'Sipariş'][$belgeTipi] ?? 'Alış faturası';
        $this->setFlash('success', "{$belgeEtiketi} #{$faturaNo} kaydedildi.");
        $this->redirect('alis' . ($belgeTipi === 'irsaliye' ? '?tip=irsaliye' : ''));
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

        // Kalem doğrulaması kaydet() ile aynı kapıdan geçer (bkz. Fatura::kalemNormalize).
        $kalemler = [];
        foreach ($kalemAdlari as $i => $ad) {
            if (trim((string)$ad) === '') continue;
            try {
                $kalemler[] = Fatura::kalemNormalize([
                    'urun_id'       => $kalemUrunId[$i] ?? null,
                    'urun_adi'      => $ad,
                    'miktar'        => $_POST['kalem_miktar'][$i] ?? null,
                    'birim_fiyat'   => $_POST['kalem_birim_fiyat'][$i] ?? null,
                    'kdv_orani'     => $_POST['kalem_kdv_orani'][$i] ?? null,
                    'iskonto_orani' => $_POST['kalem_iskonto_orani'][$i] ?? null,
                    'birim'         => $_POST['kalem_birim'][$i] ?? 'Adet',
                    'giris_tipi'    => $kalemGirisTipi[$i] ?? 'adet',
                ], $koliMap, $kur);
            } catch (InvalidArgumentException $e) {
                $hatalar['kalemler'] = $e->getMessage();
                break;
            }
        }

        if (empty($hatalar['kalemler']) && empty($kalemler)) $hatalar['kalemler'] = 'En az bir kalem ekleyin.';

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

        // Toplamlar kalemlerden hesaplanır — formül tek yerde durur (bkz. Fatura::kalemToplamlari).
        $toplamlar    = Fatura::kalemToplamlari($kalemler);
        $araToplam    = $toplamlar['ara_toplam'];
        $iskontoTutar = $toplamlar['iskonto_tutari'];
        $kdvTutar     = $toplamlar['kdv_tutari'];
        $genelToplam  = $toplamlar['genel_toplam'];

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
            // Düzenleme ekranında bu alanlar için UI yok — oluşturulduğu haliyle korunur.
            'sevk_turu'          => $mevcut['sevk_turu'] ?? null,
            'hedef_depo_id'      => $mevcut['hedef_depo_id'] ?? null,
            'kaynak_irsaliye_id' => $mevcut['kaynak_irsaliye_id'] ?? null,
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
        $this->redirect('alis' . (($f['belge_tipi'] ?? '') === 'irsaliye' ? '?tip=irsaliye' : ''));
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
        $this->redirect('alis' . (($f['belge_tipi'] ?? '') === 'irsaliye' ? '?tip=irsaliye' : ''));
    }

    // ─── AJAX: İrsaliye Getir (irsaliyeden fatura doldurmak için) ────────

    /** Faturalandırılmamış bir tedarikçi irsaliyesinin cari/kalem bilgisini JSON döner. */
    public function irsaliye_getir(int $id): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $f = $this->fatura->getir($id);
        if (!$f || $f['belge_tipi'] !== 'irsaliye' || ($f['sevk_turu'] ?? '') !== 'tedarikci'
            || (int)($f['irsaliye_kullanildi'] ?? 0) === 1 || $f['durum'] === 'iptal') {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'İrsaliye bulunamadı veya artık faturalandırılamıyor.']);
            exit;
        }
        $kalemler = array_map(function ($k) {
            return [
                'id'            => $k['urun_id'],
                'ad'            => $k['urun_adi'],
                'birim'         => $k['birim'],
                'miktar'        => (float)$k['miktar'],
                'alis_fiyati'   => (float)$k['birim_fiyat'],
                'kdv_orani'     => (float)$k['kdv_orani'],
                'iskonto_orani' => (float)$k['iskonto_orani'],
            ];
        }, $this->fatura->kalemleriGetir($id));

        echo json_encode([
            'status'    => 'success',
            'id'        => $f['id'],
            'fatura_no' => $f['fatura_no'],
            'cari_id'   => $f['cari_id'],
            'cari_unvan'=> $f['cari_unvan'] ?? '',
            'kalemler'  => $kalemler,
        ]);
        exit;
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
