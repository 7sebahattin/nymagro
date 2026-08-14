<?php

class RaporController extends Controller
{
    private array $financialReports = [
        'kasa-banka' => ['getCashBankMovements', 'Kasa - Banka Hareketleri Raporu',
            ['tarih' => 'Tarih', 'islem_tipi' => 'İşlem tipi', 'hesap_turu' => 'Hesap türü', 'hesap_adi' => 'Hesap adı', 'cari_turu' => 'Cari türü', 'cari_adi' => 'Cari adı', 'aciklama' => 'Açıklama', 'belge_no' => 'Belge/Fatura no', 'gelir_tutari' => 'Gelir tutarı', 'gider_tutari' => 'Gider tutarı', 'bakiye' => 'Bakiye'],
            ['date', 'account', 'transaction_type', 'cari_search', 'amount', 'description']],
        'calisanlar' => ['getEmployeeFinanceReport', 'Çalışanlar Raporu',
            ['calisan_adi' => 'Çalışan adı', 'gorev' => 'Görev', 'maas' => 'Maaş', 'prim_ek_odeme' => 'Prim/ek ödeme', 'yan_giderler' => 'SGK/Yemek/Yol', 'avans' => 'Avans', 'kesinti' => 'Kesinti', 'toplam_odeme' => 'Toplam ödeme', 'kalan_borc_alacak' => 'Kalan borç/alacak', 'son_odeme_tarihi' => 'Son ödeme tarihi'],
            ['date', 'employee', 'employee_search', 'payment']],
        'vadesi-gecen-alacaklar' => ['getOverdueReceivables', 'Vadesi Geçen Alacaklar Raporu',
            ['musteri_adi' => 'Müşteri adı', 'fatura_no' => 'Fatura no', 'fatura_tarihi' => 'Fatura tarihi', 'vade_tarihi' => 'Vade tarihi', 'fatura_toplami' => 'Fatura toplamı', 'tahsil_edilen' => 'Tahsil edilen', 'kalan_alacak' => 'Kalan alacak', 'gecikme_gunu' => 'Gecikme günü', 'risk_durumu' => 'Risk durumu'],
            ['customer', 'date', 'min_days', 'amount', 'overdue_flags']],
        'kdv' => ['getVatReport', 'KDV Raporu',
            ['donem' => 'Dönem', 'satis_matrahi' => 'Satış matrahı', 'satis_kdv' => 'Satış KDV', 'alis_matrahi' => 'Alış matrahı', 'alis_kdv' => 'Alış KDV', 'odenecek_kdv' => 'Ödenecek KDV', 'devreden_kdv' => 'Devreden KDV', 'net_durum' => 'Net durum'],
            ['date', 'month_year', 'vat_rate', 'invoice_type']],
        'masraflar' => ['getExpenseReport', 'Masraflar Raporu',
            ['tarih' => 'Tarih', 'masraf_kategorisi' => 'Masraf kategorisi', 'aciklama' => 'Açıklama', 'tutar' => 'Tutar', 'kdv' => 'KDV', 'toplam' => 'Toplam', 'odeme_yontemi' => 'Ödeme durumu', 'kasa_banka' => 'Kasa/Banka', 'belge_no' => 'Belge no', 'kullanici' => 'Kullanıcı'],
            ['date', 'expense_category', 'payment', 'account', 'amount', 'user']],
        'senetler' => ['getPromissoryNotesReport', 'Senetler Raporu',
            ['cek_senet_no' => 'Senet no', 'cari_adi' => 'Cari adı', 'tur' => 'Senet türü', 'duzenleme_tarihi' => 'Düzenleme tarihi', 'vade_tarihi' => 'Vade tarihi', 'tutar' => 'Tutar', 'durum' => 'Durum', 'gecikme_gunu' => 'Gecikme günü', 'aciklama' => 'Açıklama'],
            ['date', 'cari_search', 'status', 'overdue_flags']],
        'ba-bs' => ['getBaBsReport', 'BA-BS Listesi Raporu',
            ['cari_unvan' => 'Cari unvan', 'vergi_no' => 'Vergi no / TC no', 'belge_sayisi' => 'Belge sayısı', 'toplam_matrah' => 'Toplam matrah', 'toplam_kdv' => 'Toplam KDV', 'genel_toplam' => 'Genel toplam', 'islem_turu' => 'İşlem türü'],
            ['month_year', 'transaction_group', 'amount', 'cari_search', 'tax_no']],
        'gelir-gider' => ['getIncomeExpenseReport', 'Gelir Gider Durumu Raporu',
            ['donem' => 'Dönem', 'toplam_satis_geliri' => 'Toplam satış geliri', 'diger_gelirler' => 'Diğer gelirler', 'toplam_alis_maliyeti' => 'Toplam alış maliyeti', 'masraflar' => 'Masraflar', 'personel_giderleri' => 'Personel giderleri', 'net_gelir_gider_farki' => 'Net gelir/gider farkı', 'kar_zarar_durumu' => 'Kâr/Zarar durumu'],
            ['date', 'group_by', 'account']],
        'hesap-bakiyeleri' => ['getAccountBalancesReport', 'Hesap Bakiyeleri Raporu',
            ['hesap_turu' => 'Hesap türü', 'hesap_adi' => 'Hesap adı', 'toplam_borc' => 'Toplam borç', 'toplam_alacak' => 'Toplam alacak', 'net_bakiye' => 'Net bakiye', 'bakiye_durumu' => 'Bakiye durumu', 'son_islem_tarihi' => 'Son işlem tarihi'],
            ['account_type', 'cari_search', 'balance_state', 'amount']],
        'cekler' => ['getChequeReport', 'Çekler Raporu',
            ['cek_senet_no' => 'Çek no', 'banka' => 'Banka', 'cari_adi' => 'Cari adı', 'tur' => 'Çek türü', 'vade_tarihi' => 'Vade tarihi', 'tutar' => 'Tutar', 'durum' => 'Durum', 'duzenleme_tarihi' => 'Tahsil/ödeme tarihi', 'aciklama' => 'Açıklama'],
            ['date', 'cari_search', 'status', 'overdue_flags']],
        'krediler' => ['getCreditReport', 'Krediler Raporu',
            ['kredi_adi' => 'Kredi adı', 'banka' => 'Banka/finans kuruluşu', 'ana_para' => 'Ana para', 'faiz_orani' => 'Faiz oranı', 'taksit_sayisi' => 'Taksit sayısı', 'odenen_tutar' => 'Ödenen tutar', 'kalan_borc' => 'Kalan borç', 'son_odeme_tarihi' => 'Son ödeme tarihi', 'durum' => 'Durum'],
            ['date', 'bank', 'payment']],
        'gunluk-hesap-giris-cikislari' => ['getDailyAccountMovements', 'Günlük Hesap Giriş Çıkışları Raporu',
            ['tarih' => 'Tarih', 'saat' => 'Saat', 'islem_turu' => 'İşlem türü', 'hesap' => 'Hesap', 'cari' => 'Cari', 'aciklama' => 'Açıklama', 'giris' => 'Giriş', 'cikis' => 'Çıkış', 'gun_sonu_bakiye' => 'Gün sonu bakiye'],
            ['date', 'account', 'transaction_type', 'user']],
        'borc-alacak-fisleri' => ['getDebitCreditVoucherReport', 'Borç Alacak Fişleri Raporu',
            ['fis_no' => 'Fiş no', 'tarih' => 'Tarih', 'cari_adi' => 'Cari adı', 'fis_turu' => 'Fiş türü', 'tutar' => 'Tutar', 'aciklama' => 'Açıklama', 'kullanici' => 'Kullanıcı', 'durum' => 'Durum'],
            ['date', 'cari_search', 'voucher_type', 'amount', 'user']],
        'kullanici-satis-tahsilat' => ['getUserSalesCollectionReport', 'Kullanıcı Satış-Tahsilat Raporu',
            ['kullanici_adi' => 'Kullanıcı adı', 'satis_faturasi_sayisi' => 'Satış faturası sayısı', 'toplam_satis_tutari' => 'Toplam satış tutarı', 'tahsilat_sayisi' => 'Tahsilat sayısı', 'toplam_tahsilat' => 'Toplam tahsilat', 'bekleyen_tahsilat' => 'Bekleyen tahsilat', 'ortalama_satis_tutari' => 'Ortalama satış tutarı', 'son_islem_tarihi' => 'Son işlem tarihi'],
            ['date', 'user', 'customer', 'payment']],
    ];

    public function index(): void
    {
        $this->satis_alis();
    }

    private function renderReport(string $method, string $title, array $columns, array $filters = []): void
    {
        $model = $this->model('Rapor');
        $cleanFilters = $this->filters();
        try {
            $result = $model->{$method}($cleanFilters);
        } catch (Throwable $e) {
            error_log('Rapor hatası [' . $method . ']: ' . $e->getMessage());
            $result = [
                'rows' => [],
                'summary' => [],
                'note' => 'Rapor hazırlanırken teknik bir sorun oluştu. Detaylar log dosyasına yazıldı.',
            ];
        }

        $this->view('raporlar/report', [
            'pageTitle' => $title,
            'activeMenu' => 'raporlar',
            'topbarTitle' => $title,
            'topbarIcon' => 'fa-solid fa-chart-line',
            'title' => $title,
            'columns' => $columns,
            'filtersEnabled' => $filters,
            'filters' => $cleanFilters,
            'options' => $model->getFilterOptions(),
            'rows' => $result['rows'] ?? [],
            'summary' => $result['summary'] ?? [],
            'note' => $result['note'] ?? '',
        ]);
    }

    private function renderFinancialReport(string $slug): void
    {
        if (!isset($this->financialReports[$slug])) {
            http_response_code(404);
            die('404 — Finansal rapor bulunamadı.');
        }
        [$method, $title, $columns, $filters] = $this->financialReports[$slug];
        $model = $this->model('FinansalRapor');
        $cleanFilters = $this->filters();
        try {
            $result = $model->{$method}($cleanFilters);
        } catch (Throwable $e) {
            error_log('Finansal rapor hatası [' . $method . ']: ' . $e->getMessage());
            $result = [
                'rows' => [],
                'summary' => [],
                'note' => 'Rapor hazırlanırken teknik bir sorun oluştu. Detaylar log dosyasına yazıldı.',
            ];
        }

        $this->view('raporlar/report', [
            'pageTitle' => $title,
            'activeMenu' => 'raporlar',
            'topbarTitle' => $title,
            'topbarIcon' => 'fa-solid fa-chart-pie',
            'title' => $title,
            'description' => 'Bu finansal rapor canlı veritabanı kayıtlarından hazırlanır; iptal kayıtları toplam dışında tutulur.',
            'columns' => $columns,
            'filtersEnabled' => $filters,
            'filters' => $cleanFilters,
            'options' => $model->getFilterOptions(),
            'rows' => $result['rows'] ?? [],
            'summary' => $result['summary'] ?? [],
            'note' => $result['note'] ?? '',
        ]);
    }

    public function satis_alis()
    {
        $this->view('raporlar/satis_alis', [
            'pageTitle'   => 'Satışlar - Alışlar',
            'activeMenu'  => 'raporlar',
            'topbarTitle' => 'Satışlar - Alışlar',
            'topbarIcon'  => 'fa-solid fa-chart-line'
        ]);
    }

    public function basit_satis(): void
    {
        $this->renderReport('getSalesReport', 'Basit Satış Raporu', [
            'fatura_tarihi' => 'Tarih', 'fatura_no' => 'Fatura No', 'cari_unvan' => 'Müşteri Adı',
            'durum' => 'Fatura Durumu', 'ara_toplam' => 'Ara Toplam', 'kdv_tutari' => 'KDV',
            'iskonto_tutari' => 'İskonto', 'genel_toplam' => 'Genel Toplam', 'odenen_tutar' => 'Tahsil Edilen',
            'kalan_tutar' => 'Kalan Bakiye', 'odeme_durumu' => 'Ödeme Durumu',
        ], ['date', 'customer', 'status', 'payment', 'invoice', 'amount']);
    }

    public function urun_alis_satis(): void
    {
        $this->renderReport('getProductPurchaseSalesReport', 'Ürün Alış-Satış Raporu', [
            'urun_adi' => 'Ürün adı', 'kategori' => 'Kategori', 'alis_miktari' => 'Toplam alış miktarı',
            'alis_tutari' => 'Toplam alış tutarı', 'ort_alis_fiyati' => 'Ortalama alış fiyatı',
            'satis_miktari' => 'Toplam satış miktarı', 'satis_tutari' => 'Toplam satış tutarı',
            'ort_satis_fiyati' => 'Ortalama satış fiyatı', 'brut_kar' => 'Tahmini brüt kâr',
            'kar_marji' => 'Kâr marjı %', 'mevcut_stok' => 'Mevcut stok',
        ], ['date', 'product', 'category', 'min_qty', 'stock']);
    }

    public function musteri_satis(): void
    {
        $this->renderReport('getCustomerSalesReport', 'Müşteri Satış Raporu', [
            'musteri_adi' => 'Müşteri adı', 'fatura_sayisi' => 'Fatura sayısı', 'toplam_satis' => 'Toplam satış',
            'toplam_tahsilat' => 'Toplam tahsilat', 'kalan_bakiye' => 'Kalan bakiye',
            'son_satis_tarihi' => 'Son satış tarihi', 'son_tahsilat_tarihi' => 'Son tahsilat tarihi',
            'ort_fatura_tutari' => 'Ortalama fatura tutarı',
        ], ['date', 'customer_search', 'balance', 'amount']);
    }

    public function satis_kaybi(): void
    {
        $this->renderReport('getSalesLossReport', 'Satış Kaybı Raporu', [
            'tarih' => 'Tarih', 'cari' => 'Müşteri', 'belge_no' => 'Belge/Fatura/Teklif no',
            'kayip_turu' => 'Kayıp türü', 'tutar' => 'Tutar', 'aciklama' => 'Açıklama', 'durum' => 'Durum',
        ], ['date', 'customer', 'status', 'amount']);
    }

    public function alislar(): void
    {
        $this->renderReport('getPurchaseReport', 'Alışlar Raporu', [
            'fatura_tarihi' => 'Tarih', 'fatura_no' => 'Fatura No', 'tedarikci' => 'Tedarikçi',
            'ara_toplam' => 'Ara Toplam', 'kdv_tutari' => 'KDV', 'genel_toplam' => 'Genel Toplam',
            'odenen_tutar' => 'Ödenen', 'kalan_tutar' => 'Kalan Borç', 'durum' => 'Durum',
        ], ['date', 'supplier', 'status', 'payment', 'amount']);
    }

    public function iadeler(): void
    {
        $this->renderReport('getReturnsReport', 'İadeler Raporu', [
            'tarih' => 'Tarih', 'iade_turu' => 'İade türü', 'cari' => 'Müşteri/Tedarikçi',
            'urun' => 'Ürün', 'miktar' => 'Miktar', 'tutar' => 'Tutar', 'kdv_tutari' => 'KDV',
            'aciklama' => 'Açıklama', 'durum' => 'Durum',
        ], ['date', 'return_type', 'customer', 'supplier', 'product']);
    }

    public function teklifler(): void
    {
        $this->renderReport('getOffersReport', 'Teklifler Raporu', [
            'tarih' => 'Tarih', 'teklif_no' => 'Teklif No', 'musteri' => 'Müşteri',
            'toplam_tutar' => 'Toplam tutar', 'durum' => 'Durum', 'satisa_donustu' => 'Satışa dönüştü mü?',
            'gecerlilik_tarihi' => 'Geçerlilik tarihi', 'aciklama' => 'Açıklama',
        ], ['date', 'customer', 'status', 'converted']);
    }

    public function irsaliyeler(): void
    {
        $this->renderReport('getWaybillReport', 'İrsaliyeler Raporu', [
            'tarih' => 'Tarih', 'irsaliye_no' => 'İrsaliye No', 'sevk_turu' => 'Sevk türü',
            'musteri' => 'Müşteri / Hedef', 'toplam_tutar' => 'Toplam tutar', 'durum' => 'Durum',
            'faturalandi_mi' => 'Faturalandı mı?', 'aciklama' => 'Açıklama',
        ], ['date', 'customer', 'status']);
    }

    public function _6_aylik_satislar(): void
    {
        $this->renderReport('getSixMonthSalesReport', '6 Aylık Satışlar Raporu', [
            'ay' => 'Ay', 'fatura_sayisi' => 'Fatura sayısı', 'toplam_satis' => 'Toplam satış',
            'toplam_tahsilat' => 'Toplam tahsilat', 'bekleyen_tahsilat' => 'Bekleyen tahsilat',
            'ort_fatura_tutari' => 'Ortalama fatura tutarı',
        ], ['date']);
    }

    public function stok_satis_karsilama(): void
    {
        $this->stokReportKarsilama();
    }

    public function kategori_bazli_satis(): void
    {
        $this->renderReport('getCategorySalesReport', 'Kategori Bazlı Satış Raporu', [
            'kategori_adi' => 'Kategori adı', 'satilan_urun_cesidi' => 'Satılan ürün çeşidi',
            'toplam_satis_miktari' => 'Toplam satış miktarı', 'toplam_satis_tutari' => 'Toplam satış tutarı',
            'toplam_kdv' => 'Toplam KDV', 'ort_satis_fiyati' => 'Ortalama satış fiyatı',
            'toplam_brut_kar' => 'Toplam brüt kâr', 'toplam_satis_payi' => 'Toplam satış içindeki payı %',
        ], ['date', 'category', 'product', 'amount']);
    }

    public function finansal()
    {
        $this->view('raporlar/finansal', [
            'pageTitle'   => 'Finansal Raporlar',
            'activeMenu'  => 'raporlar',
            'topbarTitle' => 'Finansal Raporlar',
            'topbarIcon'  => 'fa-solid fa-chart-pie'
        ]);
    }

    public function financial(string $slug = ''): void
    {
        $this->renderFinancialReport($slug);
    }

    public function stok(string $slug = '')
    {
        if ($slug !== '') {
            $this->renderStockReport($slug);
            return;
        }
        $this->view('raporlar/stok', [
            'pageTitle'   => 'Stok Raporları',
            'activeMenu'  => 'raporlar',
            'topbarTitle' => 'Stok Raporları',
            'topbarIcon'  => 'fa-solid fa-cubes'
        ]);
    }

    private function renderStockReport(string $slug): void
    {
        $reports = [
            'urunler' => ['getProductStockReport', 'Ürünler Stok Raporu', [
                'urun_id' => 'Ürün ID', 'urun_adi' => 'Ürün adı', 'stok_kodu' => 'Stok kodu', 'barkod' => 'Barkod',
                'kategori' => 'Kategori', 'birim' => 'Birim', 'alis_fiyati' => 'Alış fiyatı', 'satis_fiyati' => 'Satış fiyatı',
                'mevcut_stok' => 'Mevcut stok', 'minimum_stok' => 'Minimum stok', 'stok_degeri' => 'Stok değeri',
                'son_alis_tarihi' => 'Son alış tarihi', 'son_satis_tarihi' => 'Son satış tarihi', 'durum' => 'Durum',
                'kritik_stok_uyarisi' => 'Kritik stok uyarısı', 'urun_tablosu_stok' => 'Ürün tablosu stok',
                'hareketlerden_stok' => 'Hareket stoku', 'stok_farki' => 'Fark',
            ], ['product', 'product_search', 'category', 'stock', 'qty_range', 'price_range']],
            'hareketler' => ['getStockMovementsReport', 'Stok Hareketleri Raporu', [
                'hareket_tarihi' => 'Hareket tarihi', 'urun_adi' => 'Ürün adı', 'stok_kodu' => 'Stok kodu',
                'kategori' => 'Kategori', 'depo_adi' => 'Depo', 'hareket_tipi' => 'Hareket tipi',
                'kaynak_turu' => 'Kaynak türü', 'kaynak_belge_no' => 'Kaynak belge/fatura no',
                'giris_miktari' => 'Giriş miktarı', 'cikis_miktari' => 'Çıkış miktarı', 'birim' => 'Birim',
                'birim_maliyet' => 'Birim maliyet', 'toplam_tutar' => 'Toplam tutar', 'kullanici' => 'Kullanıcı',
                'aciklama' => 'Açıklama',
            ], ['date', 'product', 'category', 'warehouse', 'movement_type', 'source_type', 'invoice', 'transaction_type']],
            'depo-durumu' => ['getWarehouseStatusReport', 'Depo Durumu Raporu', [
                'depo_adi' => 'Depo adı', 'urun_adi' => 'Ürün adı', 'kategori' => 'Kategori',
                'mevcut_stok' => 'Mevcut stok', 'minimum_stok' => 'Minimum stok', 'rezerve_stok' => 'Rezerve stok',
                'kullanilabilir_stok' => 'Kullanılabilir stok', 'stok_degeri' => 'Stok değeri',
                'son_hareket_tarihi' => 'Son hareket tarihi', 'durum' => 'Durum',
            ], ['warehouse', 'product', 'category', 'stock']],
            'stok-satis-karsilama' => ['getStockSalesCoverageReportFull', 'Stok-Satış Karşılama Raporu', [
                'urun_adi' => 'Ürün adı', 'kategori' => 'Kategori', 'mevcut_stok' => 'Mevcut stok',
                'son_30_gun_satis' => 'Son 30 gün satış', 'son_90_gun_satis' => 'Son 90 gün satış',
                'son_180_gun_satis' => 'Son 180 gün satış', 'ort_aylik_satis' => 'Ortalama aylık satış',
                'gunluk_ortalama_satis' => 'Günlük ortalama satış', 'stok_kac_gun_yeter' => 'Stok kaç gün yeter?',
                'stok_kac_ay_yeter' => 'Stok kaç ay yeter?', 'risk_durumu' => 'Risk durumu',
            ], ['date', 'product', 'category', 'stock']],
            'hareket-gormeyen-urunler' => ['getInactiveProductsReport', 'Hareket Görmeyen Ürünler Raporu', [
                'urun_adi' => 'Ürün adı', 'stok_kodu' => 'Stok kodu', 'kategori' => 'Kategori',
                'mevcut_stok' => 'Mevcut stok', 'stok_degeri' => 'Stok değeri', 'son_alis_tarihi' => 'Son alış tarihi',
                'son_satis_tarihi' => 'Son satış tarihi', 'son_stok_hareket_tarihi' => 'Son stok hareket tarihi',
                'hareketsiz_gun_sayisi' => 'Hareketsiz gün sayısı', 'durum' => 'Durum',
            ], ['inactive_days', 'product', 'category', 'stock']],
        ];

        if (!isset($reports[$slug])) {
            http_response_code(404);
            die('404 — Stok raporu bulunamadı.');
        }
        [$method, $title, $columns, $filters] = $reports[$slug];
        $this->renderReport($method, $title, $columns, $filters);
    }

    private function stokReportKarsilama(): void
    {
        $this->renderReport('getStockSalesCoverageReportFull', 'Stok-Satış Karşılama Raporu', [
            'urun_adi' => 'Ürün adı', 'kategori' => 'Kategori', 'mevcut_stok' => 'Mevcut stok',
            'son_30_gun_satis' => 'Son 30 gün satış', 'son_90_gun_satis' => 'Son 90 gün satış',
            'son_180_gun_satis' => 'Son 180 gün satış', 'ort_aylik_satis' => 'Ortalama aylık satış',
            'gunluk_ortalama_satis' => 'Günlük ortalama satış', 'stok_kac_gun_yeter' => 'Stok kaç gün yeter?',
            'stok_kac_ay_yeter' => 'Stok kaç ay yeter?', 'risk_durumu' => 'Risk durumu',
        ], ['date', 'product', 'category', 'stock']);
    }

    public function musteri(): void
    {
        $this->customerListForm();
    }

    public function musteri_listesi(string $action = ''): void
    {
        if ($action === 'hazirla') {
            $_GET['hazirla'] = 1;
        }
        if ($action === 'export-excel') {
            $this->exportCustomerListExcel();
            return;
        }
        if ($action === 'export-pdf') {
            $this->exportCustomerListPdf();
            return;
        }
        $this->customerListForm();
    }

    public function customerListForm(): void
    {
        $model = $this->model('Rapor');
        $filters = $this->customerListFilters($model);
        $prepared = !empty($_GET['hazirla']);
        $result = ['rows' => [], 'summary' => [], 'note' => ''];
        $error = '';

        if ($prepared) {
            try {
                $result = $model->getCustomerListReport($filters);
            } catch (Throwable $e) {
                error_log('Müşteri listesi raporu hatası: ' . $e->getMessage());
                $error = 'Rapor hazırlanırken teknik bir sorun oluştu. Detaylar log dosyasına yazıldı.';
            }
        }

        $this->view('raporlar/musteri', [
            'pageTitle'   => 'Müşteri Listesi',
            'activeMenu'  => 'raporlar',
            'topbarTitle' => 'Müşteri Listesi',
            'topbarIcon'  => 'fa-solid fa-users',
            'filters' => $filters,
            'options' => $model->getReportFilterOptions(),
            'availableColumns' => $model->getAvailableColumns(),
            'rows' => $result['rows'] ?? [],
            'summary' => $result['summary'] ?? [],
            'note' => $result['note'] ?? '',
            'error' => $error,
            'prepared' => $prepared,
        ]);
    }

    public function generateCustomerListReport(): void
    {
        $this->customerListForm();
    }

    public function exportCustomerListExcel(): void
    {
        $model = $this->model('Rapor');
        $filters = $this->customerListFilters($model);
        $result = $model->getCustomerListReport($filters);
        $columns = $filters['columns'];
        $available = $model->getAvailableColumns();

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="musteri_listesi_' . date('Ymd') . '.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Müşteri Listesi Raporu', date('d.m.Y H:i')], ';');
        fputcsv($out, ['Kullanılan filtreler', $this->customerFilterSummary($filters)], ';');
        fputcsv($out, [], ';');
        fputcsv($out, array_map(fn($key) => $available[$key] ?? $key, $columns), ';');
        foreach ($result['rows'] as $row) {
            fputcsv($out, array_map(fn($key) => $this->customerExportValue($key, $row[$key] ?? ''), $columns), ';');
        }
        fputcsv($out, [], ';');
        foreach (($result['summary'] ?? []) as $label => $value) {
            fputcsv($out, [$label, $value], ';');
        }
        fclose($out);
        exit;
    }

    public function exportCustomerListPdf(): void
    {
        $model = $this->model('Rapor');
        $filters = $this->customerListFilters($model);
        $result = $model->getCustomerListReport($filters);
        $columns = $filters['columns'];
        $available = $model->getAvailableColumns();
        $activeCompany = class_exists('TenantContext') ? TenantContext::activeCompany() : null;
        $activePeriod = class_exists('TenantContext') ? TenantContext::activePeriod() : null;
        $companyName = $activeCompany['company_name'] ?? APP_NAME;
        $logoPath = trim((string)($activeCompany['logo_path'] ?? ''));
        $logoUrl = $logoPath !== '' && !empty($activeCompany['id']) ? BASE_URL . '/companies/logo/' . (int)$activeCompany['id'] : '';
        $meta = htmlspecialchars(implode(' | ', array_filter([
            $companyName,
            $activePeriod['period_name'] ?? null,
            date('d.m.Y H:i'),
        ])), ENT_QUOTES, 'UTF-8');
        $filterSummary = htmlspecialchars($this->customerFilterSummary($filters), ENT_QUOTES, 'UTF-8');

        header('Content-Type: text/html; charset=UTF-8');
        echo '<!doctype html><html lang="tr"><head><meta charset="utf-8"><title>Müşteri Listesi</title>';
        echo '<style>@page{size:A4 landscape;margin:8mm}*{box-sizing:border-box}body{font-family:Arial,sans-serif;font-size:9px;color:#111827;margin:0;background:#fff;-webkit-print-color-adjust:exact;print-color-adjust:exact}.toolbar{padding:10px 0;margin-bottom:10px}.toolbar button{border:1px solid #cbd5e1;background:#f8fafc;border-radius:4px;padding:7px 12px;font-weight:700;cursor:pointer}.title{display:flex;align-items:center;gap:7px;border-bottom:1px solid #1e293b;padding-bottom:7px;margin-bottom:7px}.logo{width:26px;height:26px;object-fit:contain;border:1px solid #dbe3ef;border-radius:5px;padding:2px;background:#fff}.logo-fallback{width:26px;height:26px;border-radius:5px;background:#1e293b;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:10px}.title h1{margin:0;font-size:15px}.title p{margin:3px 0 0;color:#64748b;font-size:9px}.filters{background:#f8fafc;border:1px solid #dbe3ef;padding:5px 7px;margin-bottom:7px;font-size:8px}table{width:100%;border-collapse:collapse;table-layout:auto}thead{display:table-header-group}tr,td,th{break-inside:avoid;page-break-inside:avoid}th,td{border:1px solid #dbe3ef;padding:3px 4px;text-align:left;vertical-align:top}th{background:#1e293b;color:#fff;font-size:7.5px;white-space:normal}td{font-size:7.5px}@media print{.no-print{display:none!important}}</style>';
        echo '</head><body><div class="toolbar no-print"><button onclick="window.print()">Yazdır / PDF Kaydet</button></div>';
        echo '<div class="title">' . ($logoUrl ? '<img class="logo" src="' . htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') . '" alt="">' : '<div class="logo-fallback">' . htmlspecialchars(mb_substr($companyName, 0, 1, 'UTF-8'), ENT_QUOTES, 'UTF-8') . '</div>') . '<div><h1>Müşteri Listesi Raporu</h1>' . ($meta ? '<p>' . $meta . '</p>' : '') . '</div></div><div class="filters">' . $filterSummary . '</div><table><thead><tr>';
        foreach ($columns as $column) {
            echo '<th>' . htmlspecialchars($available[$column] ?? $column, ENT_QUOTES, 'UTF-8') . '</th>';
        }
        echo '</tr></thead><tbody>';
        foreach ($result['rows'] as $row) {
            echo '<tr>';
            foreach ($columns as $column) {
                echo '<td>' . htmlspecialchars((string)$this->customerExportValue($column, $row[$column] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table><script>window.print();</script></body></html>';
        exit;
    }

    private function customerListFilters(object $model): array
    {
        $available = $model->getAvailableColumns();
        $defaultColumns = ['cari_kodu', 'unvan', 'cari_tipi', 'telefon', 'eposta', 'vergi_no', 'il', 'para_birimi', 'durum', 'toplam_borc', 'toplam_alacak', 'net_bakiye', 'bakiye_durumu', 'son_islem_tarihi'];
        $selected = $_GET['columns'] ?? $defaultColumns;
        if (!is_array($selected)) {
            $selected = $defaultColumns;
        }
        $selected = array_values(array_intersect($selected, array_keys($available)));
        if (!$selected) {
            $selected = $defaultColumns;
        }

        $date = (string)($_GET['bakiye_tarihi'] ?? date('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }

        $tip = $_GET['tip'] ?? 'musteriler';
        $durum = $_GET['durum'] ?? 'hepsi';

        return [
            'tip' => in_array($tip, ['musteriler', 'tedarikciler', 'tum'], true) ? $tip : 'musteriler',
            'siniflandirma1' => trim((string)($_GET['siniflandirma1'] ?? '')),
            'siniflandirma2' => trim((string)($_GET['siniflandirma2'] ?? '')),
            'satis_elemani_id' => max(0, (int)($_GET['satis_elemani_id'] ?? 0)),
            'kaynak' => trim((string)($_GET['kaynak'] ?? '')),
            'columns' => $selected,
            'durum' => in_array($durum, ['hepsi', 'aktif', 'pasif'], true) ? $durum : 'hepsi',
            'para_birimi' => trim((string)($_GET['para_birimi'] ?? '')),
            'bakiye_tarihi' => $date,
            'acik_borclular' => !empty($_GET['acik_borclular']) ? 1 : 0,
            'cek_senet_borclular' => !empty($_GET['cek_senet_borclular']) ? 1 : 0,
            'alacaklilar' => !empty($_GET['alacaklilar']) ? 1 : 0,
            'risk_asimi' => !empty($_GET['risk_asimi']) ? 1 : 0,
            'satisi_olmayanlar' => !empty($_GET['satisi_olmayanlar']) ? 1 : 0,
            'cari_search' => trim((string)($_GET['cari_search'] ?? '')),
        ];
    }

    private function customerFilterSummary(array $filters): string
    {
        $tip = ['musteriler' => 'Müşteriler', 'tedarikciler' => 'Tedarikçiler', 'tum' => 'Tümü'][$filters['tip']] ?? 'Müşteriler';
        $parts = [
            'Tip: ' . $tip,
            'Durum: ' . ucfirst($filters['durum'] ?? 'hepsi'),
            'Bakiye Tarihi: ' . date('d.m.Y', strtotime($filters['bakiye_tarihi'] ?? date('Y-m-d'))),
            'Para Birimi: ' . (($filters['para_birimi'] ?? '') ?: 'Tümü'),
        ];
        if (!empty($filters['cari_search'])) {
            $parts[] = 'Arama: ' . $filters['cari_search'];
        }
        return implode(' | ', $parts);
    }

    private function customerExportValue(string $key, $value)
    {
        if (in_array($key, ['risk_limiti', 'acik_hesap_borcu', 'cek_senet_borcu', 'toplam_borc', 'toplam_alacak', 'net_bakiye'], true) && is_numeric($value)) {
            return number_format((float)$value, 2, ',', '.');
        }
        return $value;
    }

    private function filters(): array
    {
        $allowedStatus = ['taslak', 'onaylandi', 'odendi', 'kismi_odendi', 'iptal', 'bekliyor', 'tahsil', 'ciro', 'protestolu', 'iade', 'portfoy', 'gecikti'];
        $allowedPayment = ['paid', 'unpaid', 'partial', 'bekliyor', 'odendi', 'gecikti'];
        $allowedPeriod = ['today', 'yesterday', 'last_7', 'last_30', 'this_month', 'last_month', 'last_6', 'this_year', 'custom'];
        $date = fn($v) => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$v) ? $v : '';

        return [
            'period' => in_array($_GET['period'] ?? '', $allowedPeriod, true) ? $_GET['period'] : '',
            'start_date' => $date($_GET['start_date'] ?? ''),
            'end_date' => $date($_GET['end_date'] ?? ''),
            'customer_id' => max(0, (int)($_GET['customer_id'] ?? 0)),
            'supplier_id' => max(0, (int)($_GET['supplier_id'] ?? 0)),
            'product_id' => max(0, (int)($_GET['product_id'] ?? 0)),
            'category' => trim((string)($_GET['category'] ?? '')),
            'status' => in_array($_GET['status'] ?? '', $allowedStatus, true) ? $_GET['status'] : '',
            'payment_status' => in_array($_GET['payment_status'] ?? '', $allowedPayment, true) ? $_GET['payment_status'] : '',
            'invoice_no' => trim((string)($_GET['invoice_no'] ?? '')),
            'min_amount' => is_numeric($_GET['min_amount'] ?? null) ? $_GET['min_amount'] : '',
            'max_amount' => is_numeric($_GET['max_amount'] ?? null) ? $_GET['max_amount'] : '',
            'min_qty' => is_numeric($_GET['min_qty'] ?? null) ? $_GET['min_qty'] : '',
            'max_qty' => is_numeric($_GET['max_qty'] ?? null) ? $_GET['max_qty'] : '',
            'min_price' => is_numeric($_GET['min_price'] ?? null) ? $_GET['min_price'] : '',
            'max_price' => is_numeric($_GET['max_price'] ?? null) ? $_GET['max_price'] : '',
            'product_search' => trim((string)($_GET['product_search'] ?? '')),
            'customer_search' => trim((string)($_GET['customer_search'] ?? '')),
            'balance_filter' => in_array($_GET['balance_filter'] ?? '', ['debtors', 'paid'], true) ? $_GET['balance_filter'] : '',
            'stock_filter' => in_array($_GET['stock_filter'] ?? '', ['in_stock', 'out_of_stock', 'critical'], true) ? $_GET['stock_filter'] : '',
            'return_type' => in_array($_GET['return_type'] ?? '', ['iade_satis', 'iade_alis'], true) ? $_GET['return_type'] : '',
            'account_id' => max(0, (int)($_GET['account_id'] ?? 0)),
            'warehouse_id' => max(0, (int)($_GET['warehouse_id'] ?? 0)),
            'transaction_type' => in_array($_GET['transaction_type'] ?? '', ['giris', 'cikis'], true) ? $_GET['transaction_type'] : '',
            'movement_type' => in_array($_GET['movement_type'] ?? '', ['giris', 'cikis'], true) ? $_GET['movement_type'] : '',
            'source_type' => in_array($_GET['source_type'] ?? '', ['alis_faturasi','satis_faturasi','satis_iadesi','alis_iadesi','manuel'], true) ? $_GET['source_type'] : '',
            'cari_search' => trim((string)($_GET['cari_search'] ?? '')),
            'description' => trim((string)($_GET['description'] ?? '')),
            'month' => max(0, min(12, (int)($_GET['month'] ?? 0))),
            'year' => max(0, min(2100, (int)($_GET['year'] ?? 0))),
            'vat_rate' => is_numeric($_GET['vat_rate'] ?? null) ? $_GET['vat_rate'] : '',
            'invoice_type' => in_array($_GET['invoice_type'] ?? '', ['satis', 'alis'], true) ? $_GET['invoice_type'] : '',
            'expense_category_id' => max(0, (int)($_GET['expense_category_id'] ?? 0)),
            'user_id' => max(0, (int)($_GET['user_id'] ?? 0)),
            'min_days' => is_numeric($_GET['min_days'] ?? null) ? $_GET['min_days'] : '',
            'only_overdue' => !empty($_GET['only_overdue']) ? 1 : 0,
            'high_risk' => !empty($_GET['high_risk']) ? 1 : 0,
            'transaction_group' => in_array($_GET['transaction_group'] ?? '', ['BA', 'BS'], true) ? $_GET['transaction_group'] : '',
            'tax_no' => trim((string)($_GET['tax_no'] ?? '')),
            'group_by' => in_array($_GET['group_by'] ?? '', ['daily', 'monthly', 'yearly'], true) ? $_GET['group_by'] : '',
            'account_type' => in_array($_GET['account_type'] ?? '', ['Müşteri', 'Tedarikçi', 'Kasa', 'Banka'], true) ? $_GET['account_type'] : '',
            'balance_state' => in_array($_GET['balance_state'] ?? '', ['Borçlu', 'Alacaklı', 'Sıfır'], true) ? $_GET['balance_state'] : '',
            'employee_search' => trim((string)($_GET['employee_search'] ?? '')),
            'employee_id' => max(0, (int)($_GET['employee_id'] ?? 0)),
            'bank' => trim((string)($_GET['bank'] ?? '')),
            'voucher_type' => in_array($_GET['voucher_type'] ?? '', ['borc', 'alacak'], true) ? $_GET['voucher_type'] : '',
            'inactive_days' => in_array((int)($_GET['inactive_days'] ?? 90), [30,60,90,180,365], true) ? (int)($_GET['inactive_days'] ?? 90) : 90,
        ];
    }
}
