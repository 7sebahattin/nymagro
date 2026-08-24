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
 *   GET  /satis/irsaliye-getir/{id} → irsaliye_getir($id) [JSON AJAX]
 *   GET  /satis/odemeleri/{id} → odemeleri($id) [JSON AJAX — faturaya uygulanan ödemeler]
 */

require_once MODELS_PATH . '/Fatura.php';
require_once MODELS_PATH . '/Depo.php';
require_once MODELS_PATH . '/KasaHesap.php';
require_once MODELS_PATH . '/Nakit.php';

final class SatisController extends Controller
{
    private Fatura $fatura;

    private $cariModel;
    private Depo $depoModel;
    private KasaHesap $kasaHesapModel;
    private Nakit $nakitModel;
    public function __construct()
    {
        $this->fatura = new Fatura();
        require_once MODELS_PATH . '/Cari.php';
        $this->cariModel = new Cari();
        $this->depoModel = new Depo();
        $this->kasaHesapModel = new KasaHesap();
        $this->nakitModel = new Nakit();
    }

    // ─── index ──────────────────────────────────────────────────────────

    public function index(): void
    {
        $limit     = 50;
        $sayfa     = max(1, (int)($_GET['sayfa']    ?? 1));
        $arama     = trim($_GET['ara']              ?? '');
        $durum     = trim($_GET['durum']            ?? '');
        $donem     = trim($_GET['donem']            ?? 'tumu');
        $iptalleri = !empty($_GET['iptalleri']);
        // "İrsaliye Kaydet"/"Perakende Satış Gir"/"Numune Kaydet" ile oluşturulan
        // belgeler (belge_tipi='irsaliye'/'perakende'/'numune') satış faturalarıyla
        // aynı ekrandan girildiği için, gözlemlenebilmeleri adına aynı listede bir
        // sekme/filtre olarak sunulur (proforma'nın Teklifler sayfasında ayrı
        // listelenmesine benzer şekilde, ama bunlar için ayrı bir modül yok).
        $tipParam = $_GET['tip'] ?? '';
        $belgeTipi = in_array($tipParam, ['irsaliye', 'perakende', 'numune'], true) ? $tipParam : 'satis';

        if (mb_strlen($arama) > 0 && mb_strlen($arama) < 3) {
            $arama = '';
        }

        $offset      = ($sayfa - 1) * $limit;
        $toplam      = $this->fatura->say($belgeTipi, $arama, $durum, $donem, $iptalleri);
        $sayfaSayisi = (int)ceil($toplam / $limit);
        $faturalar   = $this->fatura->listele($belgeTipi, $arama, $durum, $donem, $iptalleri, $limit, $offset);
        $ozetler     = $this->fatura->ozetToplamlar($belgeTipi, $donem);

        $this->view('satislar/index', [
            'faturalar'   => $faturalar,
            'toplam'      => $toplam,
            'ozetler'     => $ozetler,
            'arama'       => $arama,
            'durum'       => $durum,
            'donem'       => $donem,
            'iptalleri'   => $iptalleri,
            'belgeTipi'   => $belgeTipi,
            'depolar'     => $this->depoModel->listele(),
            'kasaHesaplar' => $this->kasaHesapModel->hepsini(),
            'sayfa'       => $sayfa,
            'sayfaSayisi' => $sayfaSayisi,
            'limit'       => $limit,
            'flash'       => $this->getFlash(),
            'topbarTitle' => ['irsaliye' => 'İrsaliyeler', 'perakende' => 'Perakende Satışlar', 'numune' => 'Numuneler'][$belgeTipi] ?? 'Satışlar',
            'topbarIcon'  => ['irsaliye' => 'fa-truck', 'perakende' => 'fa-cash-register', 'numune' => 'fa-flask'][$belgeTipi] ?? 'fa-shopping-cart',
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

        // İrsaliyeler/Teklifler sekmesindeki "Faturalandır"/"Satışa Dönüştür"
        // bağlantısıyla gelindiyse, o belgeyi sayfa yüklenir yüklenmez form JS'i
        // otomatik dolduracak.
        $presetKaynakIrsaliyeId = isset($_GET['kaynak_irsaliye_id']) ? (int)$_GET['kaynak_irsaliye_id'] : null;
        $presetKaynakTeklifId  = isset($_GET['kaynak_teklif_id']) ? (int)$_GET['kaynak_teklif_id'] : null;

        $this->view('satislar/ekle', [
            'faturaNo'    => $faturaNo,
            'bugun'       => date('Y-m-d'),
            'hatalar'     => [],
            'eski'        => [],
            'cari'        => $cari,
            'depolar'     => $this->depoModel->listele(),
            'acikIrsaliyeler' => $this->fatura->faturalandirilmamisIrsaliyeler($cariId),
            'presetKaynakIrsaliyeId' => $presetKaynakIrsaliyeId,
            'acikTeklifler' => $this->fatura->faturalandirilmamisTeklifler($cariId),
            'presetKaynakTeklifId' => $presetKaynakTeklifId,
            'flash'       => $this->getFlash(),
            'topbarTitle' => 'Yeni Satış Faturası',
            'topbarIcon'  => 'fa-file-invoice-dollar',
        ]);
    }

    public function duzenle(int $id): void
    {
        $f = $this->fatura->getir($id);
        if (!$f) {
            $this->setFlash('error', 'Fatura bulunamadı.');
            $this->redirect('satis');
        }
        if ($f['durum'] === 'iptal') {
            $this->setFlash('error', 'İptal edilmiş fatura düzenlenemez.');
            $this->redirect('satis/detay/' . $id);
        }

        $eski = $f;
        $eski['fatura_tarihi'] = $f['fatura_tarihi'] ? substr((string)$f['fatura_tarihi'], 0, 10) : '';
        $eski['vade_tarihi']   = $f['vade_tarihi'] ? substr((string)$f['vade_tarihi'], 0, 10) : '';

        $cari = !empty($f['cari_id']) ? $this->cariModel->getir((int)$f['cari_id']) : null;

        $this->view('satislar/duzenle', [
            'fatura'      => $f,
            'kalemler'    => $this->fatura->kalemleriGetir($id),
            'hatalar'     => [],
            'eski'        => $eski,
            'cari'        => $cari,
            'depolar'     => $this->depoModel->listele(),
            'topbarTitle' => 'Fatura Düzenle — ' . $f['fatura_no'],
            'topbarIcon'  => 'fa-file-invoice-dollar',
        ]);
    }

    public function guncelle(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('satis/duzenle/' . $id);
        }

        $mevcut = $this->fatura->getir($id);
        if (!$mevcut) {
            $this->setFlash('error', 'Fatura bulunamadı.');
            $this->redirect('satis');
        }

        $eski    = $_POST;
        $hatalar = [];

        $faturaNo   = trim($_POST['fatura_no']    ?? '');
        $faturaT    = trim($_POST['fatura_tarihi'] ?? '');
        $belgeTipi  = $_POST['belge_tipi'] ?? $mevcut['belge_tipi'];
        if (!in_array($belgeTipi, ['siparis', 'irsaliye', 'proforma', 'satis', 'numune'])) {
            $belgeTipi = 'satis';
        }
        $durum      = $_POST['durum'] ?? $mevcut['durum'];
        $cariId     = !empty($_POST['cari_id']) ? (int)$_POST['cari_id'] : null;
        $odemeSekli = trim($_POST['odeme_sekli'] ?? '');
        $aciklama   = trim($_POST['aciklama']    ?? '');
        $vadeTarihi = trim($_POST['vade_tarihi'] ?? '') ?: null;

        // ── Döviz ────────────────────────────────────────
        $paraBirimi = trim($_POST['para_birimi'] ?? 'TRY');
        if (!in_array($paraBirimi, ['TRY', 'USD', 'EUR', 'GBP'], true)) {
            $paraBirimi = 'TRY';
        }
        $kur = $paraBirimi === 'TRY' ? 1.0 : (float)str_replace(',', '.', $_POST['kur'] ?? '0');
        if ($paraBirimi !== 'TRY' && $kur <= 0) {
            $hatalar['kur'] = 'Döviz seçildiğinde kur girilmesi zorunludur.';
        }

        if ($faturaNo === '') {
            $hatalar['fatura_no'] = 'Fatura no zorunludur.';
        }
        if ($faturaT === '') {
            $hatalar['fatura_tarihi'] = 'Fatura tarihi zorunludur.';
        } else {
            $faturaT = $this->tarihCevir($faturaT);
        }
        if ($vadeTarihi !== null) {
            $vadeTarihi = $this->tarihCevir($vadeTarihi);
        }

        $kalemAdlari  = $_POST['kalem_urun_adi']      ?? [];
        $kalemUrunId  = $_POST['kalem_urun_id']       ?? [];
        $kalemMiktar  = $_POST['kalem_miktar']        ?? [];
        $kalemFiyat   = $_POST['kalem_birim_fiyat']   ?? [];
        $kalemKdv     = $_POST['kalem_kdv_orani']     ?? [];
        $kalemIskonto = $_POST['kalem_iskonto_orani'] ?? [];
        $kalemBirim   = $_POST['kalem_birim']         ?? [];
        $kalemGirisTipi = $_POST['kalem_giris_tipi']  ?? [];

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
                    'miktar'        => $kalemMiktar[$i] ?? null,
                    'birim_fiyat'   => $kalemFiyat[$i] ?? null,
                    'kdv_orani'     => $kalemKdv[$i] ?? null,
                    'iskonto_orani' => $kalemIskonto[$i] ?? null,
                    'birim'         => $kalemBirim[$i] ?? 'Adet',
                    'giris_tipi'    => $kalemGirisTipi[$i] ?? 'adet',
                ], $koliMap, $kur);
            } catch (InvalidArgumentException $e) {
                $hatalar['kalemler'] = $e->getMessage();
                break;
            }
        }

        if (empty($hatalar['kalemler']) && empty($kalemler)) {
            $hatalar['kalemler'] = 'En az bir ürün/hizmet kalemi ekleyin.';
        }

        if (!empty($hatalar)) {
            $this->view('satislar/duzenle', [
                'fatura'      => $mevcut,
                'kalemler'    => $this->fatura->kalemleriGetir($id),
                'hatalar'     => $hatalar,
                'eski'        => $eski,
                'cari'        => $cariId ? $this->cariModel->getir($cariId) : null,
                'depolar'     => $this->depoModel->listele(),
                'topbarTitle' => 'Fatura Düzenle — ' . $mevcut['fatura_no'],
                'topbarIcon'  => 'fa-file-invoice-dollar',
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
            'fatura_tarihi'       => $faturaT,
            'vade_tarihi'         => $vadeTarihi,
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
            'durum'          => $durum === 'taslak' ? 'taslak' : ($mevcut['durum'] === 'taslak' ? 'onaylandi' : $mevcut['durum']),
            'odeme_sekli'    => $odemeSekli ?: null,
            'aciklama'       => $aciklama   ?: null,
            // Düzenleme ekranında bu alanlar için UI yok — oluşturulduğu haliyle
            // korunur (sevk türü/hedef depo/irsaliye-teklif bağlantısı burada değiştirilemez).
            'sevk_turu'          => $mevcut['sevk_turu'] ?? null,
            'hedef_depo_id'      => $mevcut['hedef_depo_id'] ?? null,
            'kaynak_irsaliye_id' => $mevcut['kaynak_irsaliye_id'] ?? null,
            'kaynak_teklif_id'   => $mevcut['kaynak_teklif_id'] ?? null,
        ];

        $depoId = !empty($_POST['depo_id']) ? (int)$_POST['depo_id'] : (int)($mevcut['depo_id'] ?: 1);

        try {
            $this->fatura->guncelle($id, $faturaVeri, $kalemler, $depoId);
            $this->setFlash('success', "Fatura #{$faturaNo} güncellendi.");
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }
        $this->redirect('satis/detay/' . $id);
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
        if (!in_array($belgeTipi, ['siparis', 'irsaliye', 'proforma', 'satis', 'numune'])) {
            $belgeTipi = 'satis';
        }
        // 'satis' (Fatura Kaydet) gerçek/nihai bir belgedir — proforma/irsaliye/sipariş
        // gibi ön belgelerden farklı olarak varsayılan olarak onaylı kaydedilir
        // (aksi halde durumu değiştirecek hiçbir kontrol olmadığından süresiz "taslak" kalır).
        // 'numune' de nihai bir belgedir: mal fiilen depodan çıkmıştır ve arkasından
        // gelecek bir onay/faturalama adımı yoktur (irsaliyedeki "Faturalandır" gibi).
        $durum       = $_POST['durum'] ?? (in_array($belgeTipi, ['satis', 'numune'], true) ? 'onaylandi' : 'taslak');
        $cariId      = !empty($_POST['cari_id']) ? (int)$_POST['cari_id'] : null;
        $odemeSekli  = trim($_POST['odeme_sekli'] ?? '');
        $aciklama    = trim($_POST['aciklama']    ?? '');
        $vadeTarihi  = trim($_POST['vade_tarihi'] ?? '') ?: null;

        // ── Döviz ────────────────────────────────────────
        $paraBirimi = trim($_POST['para_birimi'] ?? 'TRY');
        if (!in_array($paraBirimi, ['TRY', 'USD', 'EUR', 'GBP'], true)) {
            $paraBirimi = 'TRY';
        }
        $kur = $paraBirimi === 'TRY' ? 1.0 : (float)str_replace(',', '.', $_POST['kur'] ?? '0');
        if ($paraBirimi !== 'TRY' && $kur <= 0) {
            $hatalar['kur'] = 'Döviz seçildiğinde kur girilmesi zorunludur.';
        }

        // Belge numarası HER ZAMAN kaydedilen belge tipinin serisinden çözülür.
        // Form numarayı satış serisinden ön-doldurduğu için, İrsaliye/Numune/Proforma
        // kaydedilirken bu numara satış serisini tüketip mükerrerliğe yol açıyordu
        // (bkz. Fatura::belgeNoCozumle).
        $faturaNo = $this->fatura->belgeNoCozumle($faturaNo, $_POST['fatura_no_oto'] ?? '', $belgeTipi);
        if ($faturaT === '') {
            $hatalar['fatura_tarihi'] = 'Fatura tarihi zorunludur.';
        } else {
            // dd.mm.yyyy → yyyy-mm-dd
            $faturaT = $this->tarihCevir($faturaT);
        }
        if ($vadeTarihi !== null) {
            $vadeTarihi = $this->tarihCevir($vadeTarihi);
        }

        $depoId = !empty($_POST['depo_id']) ? (int)$_POST['depo_id'] : 1;

        // ── Sevk türü (yalnızca İrsaliye Kaydet için anlamlıdır) ─────────
        // Müşteriye sevk: mal fiilen depodan çıkar, cari borcu/gelir faturada oluşur.
        // Depolar arası sevk: müşteri yoktur, kaynak depodan hedef depoya transfer olur.
        $sevkTuru = null;
        $hedefDepoId = null;
        if ($belgeTipi === 'irsaliye') {
            $sevkTuru = ($_POST['sevk_turu'] ?? 'musteri') === 'depolar_arasi' ? 'depolar_arasi' : 'musteri';
            if ($sevkTuru === 'depolar_arasi') {
                $cariId = null;
                $hedefDepoId = !empty($_POST['hedef_depo_id']) ? (int)$_POST['hedef_depo_id'] : null;
                if (!$hedefDepoId) {
                    $hatalar['hedef_depo_id'] = 'Depolar arası sevkte hedef depo seçilmesi zorunludur.';
                } elseif ($hedefDepoId === $depoId) {
                    $hatalar['hedef_depo_id'] = 'Hedef depo, kaynak depodan farklı olmalıdır.';
                }
            }
        }

        // ── İrsaliyeden doldurma (yalnızca Fatura Kaydet için anlamlıdır) ─
        // Mal irsaliye kesilirken zaten depodan düşürüldüğü için, bu irsaliyeden
        // doldurulan faturada stok bir daha hareket ettirilmez (bkz. Fatura::stokHareketPlani()).
        $kaynakIrsaliyeId = null;
        if ($belgeTipi === 'satis' && !empty($_POST['kaynak_irsaliye_id'])) {
            $adayId = (int)$_POST['kaynak_irsaliye_id'];
            $aday = $this->fatura->getir($adayId);
            $gecerli = $aday
                && $aday['belge_tipi'] === 'irsaliye'
                && (int)($aday['irsaliye_kullanildi'] ?? 0) === 0
                && $aday['durum'] !== 'iptal';
            if ($gecerli) {
                $kaynakIrsaliyeId = $adayId;
            } else {
                $hatalar['kaynak_irsaliye_id'] = 'Seçilen irsaliye artık faturalandırılamıyor (bulunamadı, iptal edilmiş ya da zaten faturalandırılmış olabilir).';
            }
        }

        // ── Tekliften doldurma (yalnızca Fatura Kaydet için anlamlıdır) ───
        $kaynakTeklifId = null;
        if ($belgeTipi === 'satis' && !empty($_POST['kaynak_teklif_id'])) {
            $adayId = (int)$_POST['kaynak_teklif_id'];
            $aday = $this->fatura->getir($adayId);
            $gecerli = $aday
                && $aday['belge_tipi'] === 'proforma'
                && (int)($aday['teklif_kullanildi'] ?? 0) === 0
                && $aday['durum'] !== 'iptal';
            if ($gecerli) {
                $kaynakTeklifId = $adayId;
            } else {
                $hatalar['kaynak_teklif_id'] = 'Seçilen teklif artık satışa dönüştürülemiyor (bulunamadı, iptal edilmiş ya da zaten dönüştürülmüş olabilir).';
            }
        }

        // ── Kalemler ───────────────────────────────────
        $kalemAdlari    = $_POST['kalem_urun_adi']      ?? [];
        $kalemUrunId    = $_POST['kalem_urun_id']       ?? [];
        $kalemMiktar    = $_POST['kalem_miktar']        ?? [];
        $kalemFiyat     = $_POST['kalem_birim_fiyat']   ?? [];
        $kalemKdv       = $_POST['kalem_kdv_orani']     ?? [];
        $kalemIskonto   = $_POST['kalem_iskonto_orani'] ?? [];
        $kalemBirim     = $_POST['kalem_birim']         ?? [];
        $kalemGirisTipi = $_POST['kalem_giris_tipi']    ?? [];

        // "Koli" girişini adete çevirmek için sunucu tarafında yetkili kaynak.
        $koliMap = $this->fatura->koliIciAdetMap($kalemUrunId);

        // Kalemlerin doğrulaması TEK kapıdan (Fatura::kalemNormalize) geçer;
        // negatif miktar / %100 üstü iskonto gibi değerler burada reddedilir.
        $kalemler = [];
        foreach ($kalemAdlari as $i => $ad) {
            if (trim((string)$ad) === '') continue;
            try {
                $kalemler[] = Fatura::kalemNormalize([
                    'urun_id'       => $kalemUrunId[$i] ?? null,
                    'urun_adi'      => $ad,
                    'miktar'        => $kalemMiktar[$i] ?? null,
                    'birim_fiyat'   => $kalemFiyat[$i] ?? null,
                    'kdv_orani'     => $kalemKdv[$i] ?? null,
                    'iskonto_orani' => $kalemIskonto[$i] ?? null,
                    'birim'         => $kalemBirim[$i] ?? 'Adet',
                    'giris_tipi'    => $kalemGirisTipi[$i] ?? 'adet',
                ], $koliMap, $kur);
            } catch (InvalidArgumentException $e) {
                $hatalar['kalemler'] = $e->getMessage();
                break;
            }
        }

        if (empty($hatalar['kalemler']) && empty($kalemler)) {
            $hatalar['kalemler'] = 'En az bir ürün/hizmet kalemi ekleyin.';
        }

        if (!empty($hatalar)) {
            $this->view('satislar/ekle', [
                'faturaNo'    => $faturaNo ?: $this->fatura->faturaNoUret('satis'),
                'bugun'       => date('Y-m-d'),
                'hatalar'     => $hatalar,
                'eski'        => $eski,
                'depolar'     => $this->depoModel->listele(),
                'acikIrsaliyeler' => $this->fatura->faturalandirilmamisIrsaliyeler($cariId),
                'presetKaynakIrsaliyeId' => $kaynakIrsaliyeId,
                'acikTeklifler' => $this->fatura->faturalandirilmamisTeklifler($cariId),
                'presetKaynakTeklifId' => $kaynakTeklifId,
                'topbarTitle' => 'Yeni Satış Faturası',
                'topbarIcon'  => 'fa-file-invoice-dollar',
            ]);
            return;
        }

        // ── Toplamları hesapla ─────────────────────────
        // Toplamlar kalemlerden hesaplanır — formül tek yerde durur (bkz. Fatura::kalemToplamlari).
        $toplamlar    = Fatura::kalemToplamlari($kalemler);
        $araToplam    = $toplamlar['ara_toplam'];
        $iskontoTutar = $toplamlar['iskonto_tutari'];
        $kdvTutar     = $toplamlar['kdv_tutari'];
        $genelToplam  = $toplamlar['genel_toplam'];
        $odenenTutar = 0;
        $kalanTutar  = $genelToplam;

        // Döviz seçiliyse orijinal döviz toplamları da (referans/gösterim için)
        // ayrıca saklanır — TL toplamları her zaman ana doğruluk kaynağıdır.
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
            'fatura_tarihi'       => $faturaT,
            'vade_tarihi'         => $vadeTarihi,
            'ara_toplam'          => round($araToplam, 2),
            'iskonto_tutari'      => round($iskontoTutar, 2),
            'kdv_tutari'          => round($kdvTutar, 2),
            'genel_toplam'        => round($genelToplam, 2),
            'odenen_tutar'        => $odenenTutar,
            'kalan_tutar'         => round($kalanTutar, 2),
            'para_birimi'         => $paraBirimi,
            'kur'                 => $kur,
            'ara_toplam_doviz'     => $araToplamDoviz,
            'iskonto_tutari_doviz' => $iskontoTutarDoviz,
            'kdv_tutari_doviz'     => $kdvTutarDoviz,
            'genel_toplam_doviz'   => $genelToplamDoviz,
            'durum'          => $durum === 'taslak' ? 'taslak' : 'onaylandi',
            'odeme_sekli'    => $odemeSekli ?: null,
            'aciklama'       => $aciklama   ?: null,
            'created_by'     => class_exists('TenantContext') ? TenantContext::userId() : null,
            'sevk_turu'          => $sevkTuru,
            'hedef_depo_id'      => $hedefDepoId,
            'kaynak_irsaliye_id' => $kaynakIrsaliyeId,
            'kaynak_teklif_id'   => $kaynakTeklifId,
        ];

        try {
            $this->fatura->ekle($faturaVeri, $kalemler, $depoId);
        } catch (\Throwable $e) {
            $this->setFlash('error', $e->getMessage());
            $this->redirect('satis/ekle');
        }

        $belgeEtiketi = ['irsaliye' => 'İrsaliye', 'proforma' => 'Teklif', 'numune' => 'Numune Fişi'][$belgeTipi] ?? 'Fatura';
        $this->setFlash('success', "{$belgeEtiketi} #{$faturaNo} başarıyla kaydedildi.");
        $hedefUrl = match ($belgeTipi) {
            'irsaliye' => 'satis?tip=irsaliye',
            'proforma' => 'teklif',
            'numune'   => 'satis?tip=numune',
            default    => 'satis',
        };
        $this->redirect($hedefUrl);
    }

        // ─── perakende ──────────────────────────────────────────────────────
    public function perakende(): void
    {
        $this->view('satislar/perakende', [
            'bugun'        => date('Y-m-d'),
            'simdi'        => date('H:i'),
            'topbarTitle'  => 'Perakende Satış Gir',
            'topbarIcon'   => 'fa-cash-register',
            'faturaNo'     => $this->fatura->faturaNoUret('perakende'),
            'kasaHesaplar' => $this->kasaHesapModel->hepsini(),
            'depolar'      => $this->depoModel->listele(),
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

        // Kalemler satış/alış ile aynı kapıdan doğrulanır — negatif miktar veya
        // geçersiz oran buradan geçemez (bkz. Fatura::kalemNormalize).
        $kalemler = [];
        try {
            foreach (($data['sepet'] ?? []) as $item) {
                $kalemler[] = Fatura::kalemNormalize([
                    'urun_id'       => $item['id']    ?? null,
                    'urun_adi'      => $item['ad']    ?? '',
                    'miktar'        => $item['miktar'] ?? null,
                    'birim_fiyat'   => $item['fiyat'] ?? null,
                    'kdv_orani'     => $item['kdv']   ?? null,
                    'iskonto_orani' => 0,
                    'birim'         => $item['birim'] ?? 'Adet',
                ]);
            }
        } catch (InvalidArgumentException $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            return;
        }
        if (empty($kalemler)) {
            echo json_encode(['status' => 'error', 'message' => 'Sepette en az bir ürün olmalıdır.']);
            return;
        }

        // Tutarlar İSTEMCİDEN ALINMAZ — kalemlerden yeniden hesaplanır. Aksi halde
        // tarayıcıya müdahale eden biri, depodan çıkan malla hiç ilgisi olmayan bir
        // ciro/KDV tutarı kaydettirebilir.
        $toplamlar   = Fatura::kalemToplamlari($kalemler);
        $genelToplam = $toplamlar['genel_toplam'];

        $faturaNo = $this->fatura->faturaNoUret('perakende');
        $faturaVeri = [
            'belge_tipi'     => 'perakende',
            'fatura_no'      => $faturaNo,
            'cari_id'        => null, // Perakende satışta müşteri genelde boştur
            'fatura_tarihi'  => $data['tarih'],
            'ara_toplam'     => $toplamlar['ara_toplam'],
            'iskonto_tutari' => $toplamlar['iskonto_tutari'],
            'kdv_tutari'     => $toplamlar['kdv_tutari'],
            'genel_toplam'   => $genelToplam,
            'odenen_tutar'   => $genelToplam,
            'kalan_tutar'    => 0,
            'para_birimi'    => 'TRY',
            'durum'          => 'onaylandi',
            'aciklama'       => $data['aciklama'] ?? 'Perakende Satış',
            'created_by'     => class_exists('TenantContext') ? TenantContext::userId() : null,
        ];

        $depoId = !empty($data['depo_id']) ? (int)$data['depo_id'] : 1;
        $kasaId = !empty($data['kasa_id']) ? (int)$data['kasa_id'] : null;

        try {
            $this->fatura->ekle($faturaVeri, $kalemler, $depoId);

            if ($kasaId && $genelToplam > 0) {
                $this->nakitModel->hareketEkle([
                    'kasa_id'  => $kasaId,
                    'cari_id'  => null,
                    'islem_tipi' => 'giris',
                    'tutar'    => $genelToplam,
                    'tarih'    => trim(($data['tarih'] ?? date('Y-m-d')) . ' ' . ($data['saat'] ?? date('H:i')) . ':00'),
                    'aciklama' => 'Perakende Satış #' . $faturaNo,
                ]);
            }

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

    // ─── durum (onayla) ─────────────────────────────────────────────────

    public function durum(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('satis');
        }
        $f = $this->fatura->getir($id);
        if ($f) {
            $this->fatura->onaylaEt($id);
            $this->setFlash('success', "Fatura #{$f['fatura_no']} onaylandı.");
        } else {
            $this->setFlash('error', 'Fatura bulunamadı.');
        }
        $this->redirect('satis' . (in_array($f['belge_tipi'] ?? '', ['irsaliye', 'numune'], true) ? '?tip=' . $f['belge_tipi'] : ''));
    }

    // ─── iptal ──────────────────────────────────────────────────────────

    public function iptal(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('satis');
        }
        $f = $this->fatura->getir($id);
        if ($f) {
            $this->fatura->iptalEt($id);
            $this->setFlash('success', "Fatura #{$f['fatura_no']} iptal edildi.");
        } else {
            $this->setFlash('error', 'Fatura bulunamadı.');
        }
        $this->redirect('satis' . (in_array($f['belge_tipi'] ?? '', ['irsaliye', 'numune'], true) ? '?tip=' . $f['belge_tipi'] : ''));
    }

    // ─── sil ────────────────────────────────────────────────────────────

    public function sil(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('satis');
        }
        $f = $this->fatura->getir($id);
        if ($f) {
            $this->fatura->sil($id);
            $this->setFlash('success', "Fatura #{$f['fatura_no']} silindi.");
        } else {
            $this->setFlash('error', 'Fatura bulunamadı.');
        }
        $this->redirect('satis' . (in_array($f['belge_tipi'] ?? '', ['irsaliye', 'numune'], true) ? '?tip=' . $f['belge_tipi'] : ''));
    }

    // ─── AJAX: İrsaliye Getir (irsaliyeden fatura doldurmak için) ────────

    /** Faturalandırılmamış bir irsaliyenin cari/kalem bilgisini JSON döner. */
    public function irsaliye_getir(int $id): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $f = $this->fatura->getir($id);
        if (!$f || $f['belge_tipi'] !== 'irsaliye' || (int)($f['irsaliye_kullanildi'] ?? 0) === 1 || $f['durum'] === 'iptal') {
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
                'satis_fiyati'  => (float)$k['birim_fiyat'],
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

    // ─── AJAX: Teklif Getir (tekliften fatura doldurmak için) ────────────

    /** Satışa dönüştürülmemiş bir teklifin cari/kalem bilgisini JSON döner. */
    public function teklif_getir(int $id): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $f = $this->fatura->getir($id);
        if (!$f || $f['belge_tipi'] !== 'proforma' || (int)($f['teklif_kullanildi'] ?? 0) === 1 || $f['durum'] === 'iptal') {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Teklif bulunamadı veya artık satışa dönüştürülemiyor.']);
            exit;
        }
        $kalemler = array_map(function ($k) {
            return [
                'id'            => $k['urun_id'],
                'ad'            => $k['urun_adi'],
                'birim'         => $k['birim'],
                'miktar'        => (float)$k['miktar'],
                'satis_fiyati'  => (float)$k['birim_fiyat'],
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

    // ─── AJAX: Faturaya Uygulanan Ödemeler ────────────────────────────────

    /** Bir faturaya (FIFO ile) uygulanmış ödeme/tahsilat hareketlerini JSON döner. */
    public function odemeleri(int $id): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $f = $this->fatura->getir($id);
        if (!$f) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Fatura bulunamadı.']);
            exit;
        }
        $uygulamalar = array_map(function ($u) {
            return [
                'id'               => (int)$u['id'],
                'kasa_hareket_id'  => (int)$u['kasa_hareket_id'],
                'kasa_id'          => (int)$u['kasa_id'],
                'tutar'            => (float)$u['uygulanan_tutar'],
                'tarih'            => $u['tarih'],
                'odeme_yontemi'    => $u['odeme_yontemi'],
                'aciklama'         => $u['aciklama'],
                'kasa_adi'         => $u['kasa_adi'] ?? '',
            ];
        }, $this->fatura->odemeUygulamalariGetir($id));

        echo json_encode(['status' => 'success', 'uygulamalar' => $uygulamalar]);
        exit;
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

    /** yyyy-mm-dd → dd.mm.yyyy (düzenleme formunda göstermek için) */
    private function tarihGoster(?string $t): string
    {
        if ($t && preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $t, $m)) {
            return "{$m[3]}.{$m[2]}.{$m[1]}";
        }
        return (string)$t;
    }

}
