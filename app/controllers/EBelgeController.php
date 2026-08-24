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
require_once MODELS_PATH . '/EBelgeEslestirme.php';
require_once MODELS_PATH . '/EBelgeAktarim.php';

final class EBelgeController extends Controller
{
    private EBelge $model;
    private EBelgeEslestirme $eslestirme;
    private EBelgeAktarim $aktarim;

    public function __construct()
    {
        $this->model = new EBelge();
        $this->eslestirme = new EBelgeEslestirme();
        $this->aktarim = new EBelgeAktarim();
    }

    /**
     * Aktarım ucu için ÇİFT İZİN kontrolü.
     *
     * Router zaten EBELGE_UPDATE'i doğruluyor (Rbac::METHOD_OVERRIDES). Ancak
     * aktarım gerçek bir ALIŞ FATURASI oluşturuyor: bu yüzden kullanıcının
     * alış faturası kesme yetkisi de aranır. Aynı desen NakitController'daki
     * iç dallanma korumasında da kullanılıyor.
     */
    private function aktarimYetkisiZorunlu(): void
    {
        Rbac::authorizeOrDeny('EBelgeController', 'aktar');   // EBELGE_UPDATE
        Rbac::authorizeOrDeny('AlisController', 'kaydet');    // ALIS_CREATE
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

        // Mükerrer (daha önce yüklenmiş) dosyalar veri tabanı düzeyinde zaten
        // ikinci kez içeri alınmaz (UNIQUE company_id+dosya_hash) — ama bu tek
        // başına yeterli değil: kullanıcı "yanlışlıkla aynı dosyayı 2 kez
        // eklemek" konusunda uyarılmak istiyor. Bu yüzden mükerrer varsa
        // (hiç yeni belge gelmemiş olsa BİLE) flash rengi asla sessizce
        // "success" (yeşil) olmamalı — kullanıcı bunu gözden kaçırabilir.
        $ozet = $sonuc['ozet'];
        if ($ozet['mukerrer'] > 0 && $ozet['basarili'] === 0 && $ozet['hatali'] === 0) {
            $mesaj = $ozet['mukerrer'] === 1
                ? 'Bu dosya daha önce yüklenmiş — tekrar eklenmedi (mükerrer).'
                : sprintf('Seçtiğiniz %d dosyanın tamamı daha önce yüklenmiş — tekrar eklenmedi (mükerrer).', $ozet['mukerrer']);
            $this->setFlash('warning', $mesaj);
        } else {
            $mesaj = sprintf(
                '%d belge içeri alındı, %d mükerrer atlandı, %d dosya hatalı.',
                $ozet['basarili'],
                $ozet['mukerrer'],
                $ozet['hatali']
            );
            $this->setFlash(($ozet['hatali'] > 0 || $ozet['mukerrer'] > 0) ? 'warning' : 'success', $mesaj);
        }

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
            'aktarilanFatura' => !empty($belge['aktarilan_fatura_id']) ? $this->aktarim->aktarilanFatura($id) : null,
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

    /**
     * Cari / ürün eşleştirme ekranı (FAZ 2).
     *
     * GET  → eşleştirme ekranını gösterir (adaylar + arama + durum özeti).
     * POST → $_POST['islem'] ile tek bir eşleştirme kararını uygular.
     *
     * YETKİ: Rbac::METHOD_OVERRIDES ile EBELGE_UPDATE'e bağlandı — isim tabanlı
     * sınıflandırıcı bu metodu VIEW sanardı, oysa cari/ürün kartı açabiliyor.
     */
    public function eslestir($id = 0): void
    {
        $id = (int)$id;
        $belge = $this->model->detay($id);
        if (!$belge) {
            $this->setFlash('error', 'e-Belge bulunamadı.');
            $this->redirect('ebelge');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->eslestirmeUygula($id, $belge);
            return;
        }

        // Yalnızca GELEN e-Fatura/e-Arşiv eşleştirilir. e-İrsaliye izleme
        // amaçlıdır; giden/belirsiz yönlü belgeler ise alış faturasına
        // dönüştürülemez (bkz. EBelge::yonBelirle). POST tarafında da reddedilir.
        $engel = $this->eslestirmeEngeli($belge);
        if ($engel !== null) {
            $this->setFlash('error', $engel);
            $this->redirect('ebelge/detay/' . $id);
            return;
        }

        $taraflar = $this->model->taraflar($id);
        $kalemler = $this->eslestirme->kalemleriEslesmeIle($id);
        $urunAramasi = trim((string)($_GET['urun_ara'] ?? ''));

        // Her kalem için aday listesi: arama yapıldıysa arama sonuçları,
        // yapılmadıysa ad benzerliğine göre öneri (yalnızca GÖSTERİM amaçlı).
        $urunAdaylari = [];
        foreach ($kalemler as $kalem) {
            if (!empty($kalem['eslesen_urun_id']) || ($kalem['urun_eslesme_tipi'] ?? '') === EBelgeEslestirme::ESLESME_URUNSUZ) {
                continue;
            }
            $urunAdaylari[(int)$kalem['id']] = $this->eslestirme->urunAdaylari($kalem, $urunAramasi);
        }

        $this->view('ebelge/eslestir', [
            'flash'         => $this->getFlash(),
            'belge'         => $belge,
            'taraflar'      => $taraflar,
            'kalemler'      => $kalemler,
            'eslesenCari'   => $this->eslestirme->eslesenCari($id),
            'cariAdaylari'  => $this->eslestirme->cariAdaylari(
                $belge + ['gonderen_unvan' => $taraflar['gonderen']['unvan'] ?? ''],
                trim((string)($_GET['cari_ara'] ?? ''))
            ),
            'urunAdaylari'  => $urunAdaylari,
            'ozet'          => $this->eslestirme->eslestirmeOzeti($id),
            'cariAramasi'   => trim((string)($_GET['cari_ara'] ?? '')),
            'urunAramasi'   => $urunAramasi,
            'birimListesi'  => EBelgeEslestirme::BIRIM_LISTESI,
            'topbarTitle'   => 'e-Belge Eşleştirme',
            'topbarIcon'    => 'fa-link',
        ]);
    }

    /** eslestir() POST dalı — tek bir eşleştirme kararını uygular. */
    private function eslestirmeUygula(int $id, array $belge): void
    {
        $engel = $this->eslestirmeEngeli($belge);
        if ($engel !== null) {
            $this->setFlash('error', $engel);
            $this->redirect('ebelge/detay/' . $id);
            return;
        }

        $kullanici = $this->aktifKullaniciId();
        $islem = (string)($_POST['islem'] ?? '');

        try {
            switch ($islem) {
                case 'otomatik':
                    $sonuc = $this->eslestirme->otomatikEslestir($id, $kullanici);
                    $this->setFlash('success', sprintf(
                        'Otomatik eşleştirme: cari %s, %d/%d kalem eşleşti. (Eşleşme yalnızca VKN/barkod/ürün kodu ile yapılır.)',
                        $sonuc['cari'] === 'degismedi' ? 'değişmedi' : 'bağlandı',
                        $sonuc['kalem_eslesen'],
                        $sonuc['kalem_toplam']
                    ));
                    break;

                case 'cari_ata':
                    $sonuc = $this->eslestirme->cariAta(
                        $id,
                        (int)($_POST['cari_id'] ?? 0),
                        !empty($_POST['ogren']),
                        $kullanici
                    );
                    $this->setFlash(
                        empty($sonuc['uyarilar']) ? 'success' : 'warning',
                        'Cari eşleştirildi.' . (empty($sonuc['uyarilar']) ? '' : ' ' . implode(' ', $sonuc['uyarilar']))
                    );
                    break;

                case 'cari_yeni':
                    $this->eslestirme->yeniCariOlustur($id, $_POST, $kullanici);
                    $this->setFlash('success', 'Yeni cari kartı oluşturuldu ve belgeye bağlandı.');
                    break;

                case 'kalem':
                    $this->eslestirme->kalemEslestir($id, (int)($_POST['kalem_id'] ?? 0), $_POST, $kullanici);
                    $this->setFlash('success', 'Kalem eşleştirildi.');
                    break;

                case 'kalem_kaldir':
                    $this->eslestirme->kalemEslesmesiniKaldir($id, (int)($_POST['kalem_id'] ?? 0), $kullanici);
                    $this->setFlash('success', 'Kalem eşleşmesi kaldırıldı.');
                    break;

                case 'urun_yeni':
                    $this->eslestirme->yeniUrunOlustur($id, (int)($_POST['kalem_id'] ?? 0), $_POST, $kullanici);
                    $this->setFlash('success', 'Yeni ürün/hizmet kartı açıldı ve kaleme bağlandı.');
                    break;

                case 'toplu_urunsuz':
                    $sayac = $this->eslestirme->topluUrunsuzIsaretle($id, $kullanici);
                    $this->setFlash('success', $sayac . ' kalem üründüz gider/hizmet kalemi olarak işaretlendi.');
                    break;

                default:
                    $this->setFlash('error', 'Geçersiz eşleştirme işlemi.');
            }
        } catch (Throwable $e) {
            error_log('[NYMAGRO] e-Belge eşleştirme hatası: ' . $e->getMessage());
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect('ebelge/eslestir/' . $id);
    }

    /**
     * FAZ 3 — Çekirdek sisteme aktarım.
     *
     * GET  → son onay ekranı (ne oluşacağı, depo seçimi, onay kutuları). Yazmaz.
     * POST → aktarımı yürütür: tek transaction, guarded UPDATE ve
     *        yalnızca Fatura::ekle() çağrısı.
     *
     * YETKİ: EBELGE_UPDATE **ve** ALIS_CREATE (bkz. aktarimYetkisiZorunlu).
     */
    public function aktar($id = 0): void
    {
        $id = (int)$id;
        $this->aktarimYetkisiZorunlu();

        $belge = $this->model->detay($id);
        if (!$belge) {
            $this->setFlash('error', 'e-Belge bulunamadı.');
            $this->redirect('ebelge');
            return;
        }

        // faturalar.kaynak_ebelge_id kolonunu (yoksa) burada hazırla:
        // DDL örtük commit yaptığı için transaction AÇILMADAN önce yapılmalı.
        $this->aktarim->ensureKaynakKolonu();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            require_once MODELS_PATH . '/Depo.php';
            try {
                $onizleme = $this->aktarim->onizleme($id);
            } catch (Throwable $e) {
                $this->setFlash('error', $e->getMessage());
                $this->redirect('ebelge/detay/' . $id);
                return;
            }

            $this->view('ebelge/aktar', [
                'flash'       => $this->getFlash(),
                'onizleme'    => $onizleme,
                'taraflar'    => $this->model->taraflar($id),
                'depolar'     => (new Depo())->listele(),
                'topbarTitle' => 'e-Belge → Fatura Aktarımı',
                'topbarIcon'  => 'fa-file-import',
            ]);
            return;
        }

        try {
            $faturaId = $this->aktarim->aktar(
                $id,
                (int)($_POST['depo_id'] ?? 0),
                $_POST,
                $this->aktifKullaniciId()
            );
            $this->setFlash('success', 'e-Belge alış faturasına dönüştürüldü (fatura #' . $faturaId . ').');
            $this->redirect('ebelge/detay/' . $id);
        } catch (Throwable $e) {
            // Aktarım tek transaction içindedir: hata hâlinde hiçbir şey yazılmaz,
            // belge "aktarıma hazır" durumunda kalır ve tekrar denenebilir.
            $this->setFlash('error', 'Aktarım yapılamadı, hiçbir kayıt oluşturulmadı. ' . $e->getMessage());
            $this->redirect('ebelge/aktar/' . $id);
        }
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

    /**
     * Belge eşleştirilebilir mi? Değilse kullanıcıya gösterilecek gerekçeyi döner.
     * Hem GET (ekranı açma) hem POST (karar uygulama) dalında kullanılır.
     */
    private function eslestirmeEngeli(array $belge): ?string
    {
        if (!in_array((string)$belge['belge_tipi'], EBelge::AKTARILABILIR_TIPLER, true)) {
            return 'e-İrsaliye belgeleri ilk fazda yalnızca izleme amaçlıdır; eşleştirme yapılmaz.';
        }
        if (($belge['yon'] ?? 'gelen') !== 'gelen') {
            return EBelge::yonUyarisi((string)($belge['yon'] ?? 'belirsiz'))
                ?? 'Belgenin yönü gelen olarak doğrulanamadı; eşleştirme kapalı.';
        }
        return null;
    }

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
