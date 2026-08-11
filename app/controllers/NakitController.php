<?php
/**
 * Controller: NakitController
 */
require_once MODELS_PATH . '/Nakit.php';
require_once MODELS_PATH . '/GelenEFatura.php';
require_once MODELS_PATH . '/KasaHesap.php';

final class NakitController extends Controller
{
    private Nakit $model;
    private GelenEFatura $gelenEFatura;

    public function __construct()
    {
        $this->model = new Nakit();
        $this->gelenEFatura = new GelenEFatura();
    }

    /**
     * AJAX: Tahsilat veya Ödeme Kaydet
     */
    public function kaydet(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Geçersiz istek.']);
            return;
        }

        try {
            $odemeYontemleri = ['Nakit', 'Havale/EFT', 'Kredi Kartı', 'Çek', 'Senet', 'Virman'];
            $odemeYontemi = trim($_POST['odeme_yontemi'] ?? '');
            if (!in_array($odemeYontemi, $odemeYontemleri, true)) {
                throw new Exception('Geçerli bir ödeme yöntemi seçiniz.');
            }

            $kasaId = (int)($_POST['kasa_id'] ?? 0);
            if ($kasaId <= 0) {
                throw new Exception('Kasa/Hesap seçimi zorunludur.');
            }

            $tarihGiris = trim($_POST['tarih'] ?? '') ?: date('Y-m-d');
            $saatGiris  = trim($_POST['saat'] ?? '') ?: date('H:i');
            $tarihTs = strtotime($tarihGiris . ' ' . $saatGiris);

            $data = [
                'kasa_id'       => $kasaId,
                'cari_id'       => !empty($_POST['cari_id']) ? (int)$_POST['cari_id'] : null,
                'islem_tipi'    => $_POST['islem_tipi'], // 'giris' (tahsilat) | 'cikis' (ödeme)
                'odeme_yontemi' => $odemeYontemi,
                'tutar'         => (float)str_replace(',', '.', $_POST['tutar']),
                'aciklama'      => $_POST['aciklama'] ?? '',
                'tarih'         => date('Y-m-d H:i:s', $tarihTs !== false ? $tarihTs : time()),
            ];

            if ($data['tutar'] <= 0) {
                throw new Exception('Tutar sıfırdan büyük olmalıdır.');
            }

            $id = $this->model->hareketEkle($data);
            echo json_encode(['status' => 'success', 'message' => 'İşlem başarıyla kaydedildi.', 'id' => $id]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Nakit Yönetimi > Gelen E-Faturalar
     *
     * /nakit/gelen-e-faturalar
     * /nakit/gelen-e-faturalar/toplu-yukle
     * /nakit/gelen-e-faturalar/detay/{id}
     */
    /**
     * Bu metot /nakit/gelen-e-faturalar/{action} altındaki TÜM alt işlemleri
     * ($action ile) kendi içinde yönlendirir; Router seviyesindeki merkezi
     * RBAC haritası (Rbac::requiredPermissionFor) bu iç dallanmayı GÖREMEZ —
     * bu yüzden VIEW'i aşan alt işlemler için burada AYRICA yetki kontrolü
     * yapılır (bkz. Rbac::METHOD_OVERRIDES → bu metot router'da sadece
     * NAKIT_VIEW ister; create/delete niteliğindeki dallar burada korunur).
     */
    public function gelen_e_faturalar(string $action = '', ?string $id = null): void
    {
        $mutatingCreate = ['yeni', 'toplu-yukle', 'onizleme', 'kaydet', 'pdf-yukle', 'odeme-ekle', 'not-ekle', 'toplu-odeme', 'toplu-not'];
        $mutatingDelete = ['iptal'];
        if (in_array($action, $mutatingCreate, true)) {
            Rbac::authorizeOrDeny('NakitController', 'gelen_e_faturalar_create');
        } elseif (in_array($action, $mutatingDelete, true)) {
            Rbac::authorizeOrDeny('NakitController', 'gelen_e_faturalar_delete');
        }

        try {
            match ($action) {
                'yeni' => $this->gelenEFaturaYeni(),
                'toplu-yukle' => $this->gelenEFaturaTopluYukle(),
                'onizleme' => $this->gelenEFaturaOnizleme(),
                'kaydet' => $this->gelenEFaturaImportKaydet(),
                'detay' => $this->gelenEFaturaDetay((int)$id),
                'pdf-yukle' => $this->gelenEFaturaPdfYukle((int)$id),
                'odeme-ekle' => $this->gelenEFaturaOdemeEkle((int)$id),
                'not-ekle' => $this->gelenEFaturaNotEkle((int)$id),
                'toplu-odeme' => $this->gelenEFaturaTopluOdeme(),
                'toplu-not' => $this->gelenEFaturaTopluNot(),
                'iptal' => $this->gelenEFaturaIptal((int)$id),
                'export' => $this->gelenEFaturaExport(),
                default => $this->gelenEFaturaIndex(),
            };
        } catch (Throwable $e) {
            error_log('Gelen E-Fatura controller hatası: ' . $e->getMessage());
            $this->setFlash('error', 'İşlem sırasında hata oluştu. Teknik detay log dosyasına yazıldı.');
            $this->redirect('nakit/gelen-e-faturalar');
        }
    }

    private function gelenEFaturaIndex(): void
    {
        $filters = $this->readFilters();
        $sayfa = max(1, (int)($_GET['sayfa'] ?? 1));
        $limit = 50;
        $offset = ($sayfa - 1) * $limit;
        $toplam = $this->gelenEFatura->say($filters);
        $sayfaSay = max(1, (int)ceil($toplam / $limit));

        $this->view('nakit/gelen_efaturalar/index', [
            'flash'     => $this->getFlash(),
            'filters'   => $filters,
            'faturalar' => $this->gelenEFatura->listele($filters, $limit, $offset),
            'ozetler'   => $this->gelenEFatura->ozetler($filters),
            'hesaplar'  => (new KasaHesap())->hepsini(),
            'toplam'    => $toplam,
            'sayfa'     => $sayfa,
            'sayfaSay'  => $sayfaSay,
        ]);
    }

    private function gelenEFaturaYeni(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $this->gelenEFatura->manuelKaydet($_POST, $this->currentUserId());
            if (!empty($_FILES['pdf']['name'])) {
                $this->storePdf($id, $_FILES['pdf']);
            }
            $this->setFlash('success', 'Gelen fatura kaydedildi.');
            $this->redirect('nakit/gelen-e-faturalar/detay/' . $id);
        }

        $this->view('nakit/gelen_efaturalar/yeni', [
            'flash' => $this->getFlash(),
            'tedarikciler' => $this->gelenEFatura->tedarikciler(),
        ]);
    }

    private function gelenEFaturaTopluYukle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (empty($_FILES['excel']['tmp_name']) || !is_uploaded_file($_FILES['excel']['tmp_name'])) {
                $this->setFlash('error', 'Excel dosyası seçilmedi.');
                $this->redirect('nakit/gelen-e-faturalar/toplu-yukle');
            }

            $file = $_FILES['excel'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['xlsx'], true)) {
                $this->setFlash('error', 'Sadece .xlsx dosyası yüklenebilir.');
                $this->redirect('nakit/gelen-e-faturalar/toplu-yukle');
            }

            $preview = $this->gelenEFatura->parseExcel($file['tmp_name'], $file['name']);
            $_SESSION['gelen_efatura_preview'] = $preview;
            $this->redirect('nakit/gelen-e-faturalar/onizleme');
        }

        $this->view('nakit/gelen_efaturalar/toplu_yukle', [
            'flash' => $this->getFlash(),
        ]);
    }

    private function gelenEFaturaOnizleme(): void
    {
        $preview = $_SESSION['gelen_efatura_preview'] ?? null;
        if (!$preview) {
            $this->setFlash('error', 'Önizlenecek import verisi bulunamadı.');
            $this->redirect('nakit/gelen-e-faturalar/toplu-yukle');
        }

        $this->view('nakit/gelen_efaturalar/onizleme', [
            'flash' => $this->getFlash(),
            'preview' => $preview,
            'tedarikciler' => $this->gelenEFatura->tedarikciler(),
        ]);
    }

    private function gelenEFaturaImportKaydet(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('nakit/gelen-e-faturalar/onizleme');
        }

        $preview = $_SESSION['gelen_efatura_preview'] ?? null;
        if (!$preview) {
            $this->setFlash('error', 'Kaydedilecek önizleme verisi bulunamadı.');
            $this->redirect('nakit/gelen-e-faturalar/toplu-yukle');
        }

        $actions = $_POST['islem'] ?? [];
        $editableFields = [
            'belge_turu', 'duzenleme_tarihi', 'vade_tarihi', 'tedarikci_calisan_adi',
            'fatura_ismi', 'kategori', 'doviz_tipi', 'doviz_kuru', 'genel_toplam',
            'genel_toplam_tl', 'toplam_odenen', 'toplam_odenen_tl', 'fis_fatura_no',
            'toplam_kdv', 'notlar'
        ];
        foreach ($preview['invoices'] as $idx => &$invoice) {
            $invoice['islem'] = $actions[$idx] ?? ($invoice['is_duplicate'] ?? false ? 'atla' : 'kaydet');
            foreach ($editableFields as $field) {
                if (isset($_POST[$field][$idx])) {
                    $invoice[$field] = trim((string)$_POST[$field][$idx]);
                }
            }
            if (!empty($_POST['tedarikci_id'][$idx])) {
                $invoice['tedarikci_id'] = (int)$_POST['tedarikci_id'][$idx];
            }
            $invoice = $this->gelenEFatura->recalculatePreviewInvoice($invoice);
        }
        unset($invoice);

        $result = $this->gelenEFatura->kaydetPreview($preview, $this->currentUserId());
        unset($_SESSION['gelen_efatura_preview']);
        $this->setFlash('success', $result['saved'] . ' fatura kaydedildi, ' . $result['skipped'] . ' fatura atlandı.');
        $this->redirect('nakit/gelen-e-faturalar?import_batch_id=' . $result['batch_id']);
    }

    private function gelenEFaturaDetay(int $id): void
    {
        $fatura = $this->gelenEFatura->detay($id);
        if (!$fatura) {
            $this->setFlash('error', 'Fatura bulunamadı.');
            $this->redirect('nakit/gelen-e-faturalar');
        }

        $this->view('nakit/gelen_efaturalar/detay', [
            'flash' => $this->getFlash(),
            'fatura' => $fatura,
            'kalemler' => $this->gelenEFatura->kalemler($id),
            'odemeler' => $this->gelenEFatura->odemeler($id),
            'notlar' => $this->gelenEFatura->notlar($id),
            'hesaplar' => (new KasaHesap())->hepsini(),
        ]);
    }

    private function gelenEFaturaPdfYukle(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['pdf']['name'])) {
            $this->redirect('nakit/gelen-e-faturalar/detay/' . $id);
        }
        $this->storePdf($id, $_FILES['pdf']);
        $this->setFlash('success', 'PDF dosyası yüklendi.');
        $this->redirect('nakit/gelen-e-faturalar/detay/' . $id);
    }

    private function gelenEFaturaOdemeEkle(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('nakit/gelen-e-faturalar/detay/' . $id);
        }
        $this->gelenEFatura->odemeEkle($id, $_POST, $this->currentUserId());
        $this->setFlash('success', 'Ödeme kaydedildi ve kalan tutar güncellendi.');
        $this->redirect('nakit/gelen-e-faturalar/detay/' . $id);
    }

    private function gelenEFaturaNotEkle(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->gelenEFatura->notEkle($id, $_POST['not_metni'] ?? '', $this->currentUserId());
            $this->setFlash('success', 'Not eklendi.');
        }
        $this->redirect('nakit/gelen-e-faturalar/detay/' . $id);
    }

    private function gelenEFaturaTopluOdeme(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $rawIds  = array_map('intval', $_POST['ids'] ?? []);
            $ids     = array_values(array_unique($rawIds)); // çift ID'leri temizle
            $amounts = $_POST['tutarlar'] ?? [];
            $saved   = 0;
            $hatalar = [];

            foreach ($ids as $id) {
                if ($id <= 0) continue;
                $amount = (float)str_replace([',', ' '], ['.', ''], (string)($amounts[$id] ?? 0));
                if ($amount <= 0) continue;

                try {
                    $this->gelenEFatura->odemeEkle($id, [
                        'odeme_tutari_tl'     => $amount,
                        'odeme_tarihi'        => $_POST['odeme_tarihi'] ?? date('Y-m-d'),
                        'kasa_banka_hesap_id' => !empty($_POST['kasa_banka_hesap_id']) ? (int)$_POST['kasa_banka_hesap_id'] : null,
                        'odeme_hesabi'        => $_POST['odeme_hesabi'] ?? '',
                        'aciklama'            => trim($_POST['aciklama'] ?? '') ?: 'Toplu gelen e-fatura ödemesi',
                    ], $this->currentUserId());
                    $saved++;
                } catch (Throwable $e) {
                    $hatalar[] = "Fatura #{$id}: " . $e->getMessage();
                }
            }

            if ($saved > 0 && empty($hatalar)) {
                $this->setFlash('success', $saved . ' fatura için ödeme başarıyla işlendi.');
            } elseif ($saved > 0) {
                $this->setFlash('success', $saved . ' fatura işlendi. Hatalar: ' . implode(' | ', $hatalar));
            } else {
                $this->setFlash('error', 'Hiçbir ödeme işlenemedi. ' . implode(' | ', $hatalar));
            }
        }
        $this->redirect('nakit/gelen-e-faturalar');
    }

    private function gelenEFaturaTopluNot(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ids = array_map('intval', $_POST['ids'] ?? []);
            foreach ($ids as $id) {
                $this->gelenEFatura->notEkle($id, $_POST['not_metni'] ?? '', $this->currentUserId());
            }
            $this->setFlash('success', count($ids) . ' faturaya not eklendi.');
        }
        $this->redirect('nakit/gelen-e-faturalar');
    }

    private function gelenEFaturaIptal(int $id): void
    {
        $this->gelenEFatura->pasifYap($id);
        $this->setFlash('success', 'Fatura iptal/pasif yapıldı.');
        $this->redirect('nakit/gelen-e-faturalar');
    }

    private function gelenEFaturaExport(): void
    {
        $rows = $this->gelenEFatura->exportRows($this->readFilters());
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="gelen-efaturalar-' . date('Ymd-His') . '.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Düzenleme Tarihi', 'Vade Tarihi', 'Belge Türü', 'Fatura No', 'Tedarikçi/Çalışan', 'Kategori', 'Döviz', 'Genel Toplam', 'Genel Toplam TL', 'Ödenen TL', 'Kalan TL', 'Ödeme Durumu', 'Vade Durumu'], ';');
        foreach ($rows as $row) {
            fputcsv($out, [
                $row['duzenleme_tarihi'],
                $row['vade_tarihi'],
                $row['belge_turu'],
                $row['fis_fatura_no'],
                $row['tedarikci_unvan'] ?: $row['tedarikci_calisan_adi'],
                $row['kategori'],
                $row['doviz_tipi'],
                $row['genel_toplam'],
                $row['genel_toplam_tl'],
                $row['toplam_odenen_tl'],
                $row['kalan_tutar_tl'],
                $row['odeme_durumu'],
                $row['vade_durumu'],
            ], ';');
        }
        fclose($out);
        exit;
    }

    private function readFilters(): array
    {
        $allowed = [
            'baslangic', 'bitis', 'vade_baslangic', 'vade_bitis', 'ara', 'belge_turu',
            'kategori', 'doviz_tipi', 'odeme_durumu', 'vade_durumu', 'min_tutar',
            'max_tutar', 'pdf', 'not', 'kaynak', 'import_batch_id', 'vadesi_gecen',
            'odenmeyen', 'kismi_odenen', 'pdf_eksik'
        ];
        $filters = [];
        foreach ($allowed as $key) {
            $filters[$key] = trim((string)($_GET[$key] ?? ''));
        }

        $quick = $_GET['quick'] ?? '';
        if ($quick === 'bugun') {
            $filters['baslangic'] = $filters['bitis'] = date('Y-m-d');
        } elseif ($quick === 'bu_hafta') {
            $filters['baslangic'] = date('Y-m-d', strtotime('monday this week'));
            $filters['bitis'] = date('Y-m-d');
        } elseif ($quick === 'bu_ay') {
            $filters['baslangic'] = date('Y-m-01');
            $filters['bitis'] = date('Y-m-d');
        } elseif ($quick === 'gecen_ay') {
            $filters['baslangic'] = date('Y-m-01', strtotime('first day of previous month'));
            $filters['bitis'] = date('Y-m-t', strtotime('last day of previous month'));
        } elseif ($quick === 'son_30') {
            $filters['baslangic'] = date('Y-m-d', strtotime('-30 days'));
            $filters['bitis'] = date('Y-m-d');
        } elseif ($quick === 'vadesi_gecen') {
            $filters['vadesi_gecen'] = '1';
        } elseif ($quick === 'odenmeyen') {
            $filters['odenmeyen'] = '1';
        } elseif ($quick === 'kismi') {
            $filters['kismi_odenen'] = '1';
        } elseif ($quick === 'pdf_eksik') {
            $filters['pdf_eksik'] = '1';
        } elseif ($quick === 'usd') {
            $filters['doviz_tipi'] = 'USD';
        } elseif ($quick === 'tl') {
            $filters['doviz_tipi'] = 'TRY';
        }

        return $filters;
    }

    private function storePdf(int $invoiceId, array $file): void
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('PDF yüklenemedi.');
        }
        if ((int)$file['size'] > 10 * 1024 * 1024) {
            throw new RuntimeException('PDF dosyası 10 MB sınırını aşamaz.');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mime = mime_content_type($file['tmp_name']);
        if ($ext !== 'pdf' || $mime !== 'application/pdf') {
            throw new RuntimeException('Sadece PDF dosyası yüklenebilir.');
        }

        $dir = 'public/uploads/gelen-efaturalar';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $fileName = 'gef-' . $invoiceId . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.pdf';
        $target = $dir . '/' . $fileName;
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            throw new RuntimeException('PDF güvenli klasöre taşınamadı.');
        }
        $this->gelenEFatura->pdfGuncelle($invoiceId, $target);
    }

    private function currentUserId(): ?int
    {
        return !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }
}
