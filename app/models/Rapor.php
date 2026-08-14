<?php

class Rapor
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getFilterOptions(): array
    {
        return [
            'customers' => $this->db->select("SELECT id, unvan FROM cariler WHERE silindi_mi = 0 AND company_id = :cid AND tip IN ('musteri','her_ikisi') ORDER BY unvan", [':cid' => TenantContext::activeCompanyId()]),
            'suppliers' => $this->db->select("SELECT id, unvan FROM cariler WHERE silindi_mi = 0 AND company_id = :cid AND tip IN ('tedarikci','her_ikisi') ORDER BY unvan", [':cid' => TenantContext::activeCompanyId()]),
            'products' => $this->db->select("SELECT id, ad FROM urunler_hizmetler WHERE silindi_mi = 0 AND company_id = :cid ORDER BY ad", [':cid' => TenantContext::activeCompanyId()]),
            'categories' => $this->db->select("SELECT DISTINCT kategori AS ad FROM urunler_hizmetler WHERE silindi_mi = 0 AND company_id = :cid AND kategori IS NOT NULL AND kategori <> '' ORDER BY kategori", [':cid' => TenantContext::activeCompanyId()]),
            'warehouses' => $this->tableExists('depolar') ? $this->db->select("SELECT id, ad AS depo_adi FROM depolar WHERE silindi_mi = 0 AND company_id = :cid ORDER BY ad", [':cid' => TenantContext::activeCompanyId()]) : [],
        ];
    }

    public function getReportFilterOptions(): array
    {
        $params = [':company_id' => TenantContext::activeCompanyId()];
        return [
            'currencies' => $this->db->select("SELECT DISTINCT para_birimi AS value FROM cariler WHERE company_id = :company_id AND para_birimi IS NOT NULL AND para_birimi <> '' ORDER BY para_birimi", $params),
            'class1' => $this->columnExists('cariler', 'siniflandirma1') ? $this->db->select("SELECT DISTINCT siniflandirma1 AS value FROM cariler WHERE company_id = :company_id AND siniflandirma1 IS NOT NULL AND siniflandirma1 <> '' ORDER BY siniflandirma1", $params) : [],
            'class2' => $this->columnExists('cariler', 'siniflandirma2') ? $this->db->select("SELECT DISTINCT siniflandirma2 AS value FROM cariler WHERE company_id = :company_id AND siniflandirma2 IS NOT NULL AND siniflandirma2 <> '' ORDER BY siniflandirma2", $params) : [],
            'sources' => $this->columnExists('cariler', 'kaynak') ? $this->db->select("SELECT DISTINCT kaynak AS value FROM cariler WHERE company_id = :company_id AND kaynak IS NOT NULL AND kaynak <> '' ORDER BY kaynak", $params) : [],
            'sales_people' => ($this->tableExists('kullanicilar') && $this->columnExists('cariler', 'satis_elemani_id'))
                ? $this->db->select("SELECT id, ad_soyad AS ad FROM kullanicilar ORDER BY ad_soyad")
                : [],
            'missing' => $this->customerReportMissingColumns(),
        ];
    }

    public function getAvailableColumns(): array
    {
        return [
            'cari_kodu' => 'Cari kod',
            'unvan' => 'Ünvan / Ad Soyad',
            'cari_tipi' => 'Cari tipi',
            'telefon' => 'Telefon',
            'eposta' => 'E-posta',
            'vergi_no' => 'Vergi no / TC no',
            'vergi_dairesi' => 'Vergi dairesi',
            'il' => 'Şehir',
            'ilce' => 'İlçe',
            'adres' => 'Adres',
            'para_birimi' => 'Para birimi',
            'durum' => 'Durum',
            'satis_elemani' => 'Satış elemanı',
            'kaynak' => 'Kaynak',
            'siniflandirma1' => 'Sınıflandırma 1',
            'siniflandirma2' => 'Sınıflandırma 2',
            'risk_limiti' => 'Risk limiti',
            'acik_hesap_borcu' => 'Açık hesap borcu',
            'cek_senet_borcu' => 'Çek/senet borcu',
            'toplam_borc' => 'Toplam borç',
            'toplam_alacak' => 'Toplam alacak',
            'net_bakiye' => 'Net bakiye',
            'bakiye_durumu' => 'Bakiye durumu',
            'son_satis_tarihi' => 'Son satış tarihi',
            'son_tahsilat_tarihi' => 'Son tahsilat tarihi',
            'son_islem_tarihi' => 'Son işlem tarihi',
            'notlar' => 'Notlar',
        ];
    }

    public function getCustomerListReport(array $filters): array
    {
        $params = [
            ':tenant_company_id' => TenantContext::activeCompanyId(),
            ':tenant_period_id' => TenantContext::activePeriodId(),
        ];
        $where = ['c.company_id = :tenant_company_id'];
        $tip = $filters['tip'] ?? 'musteriler';
        if ($tip === 'musteriler') {
            $where[] = "c.tip IN ('musteri','her_ikisi')";
        } elseif ($tip === 'tedarikciler') {
            $where[] = "c.tip IN ('tedarikci','her_ikisi')";
        } else {
            $where[] = "c.tip IN ('musteri','tedarikci','her_ikisi')";
        }

        if (($filters['durum'] ?? '') === 'aktif') {
            $where[] = 'c.silindi_mi = 0';
        } elseif (($filters['durum'] ?? '') === 'pasif') {
            $where[] = 'c.silindi_mi = 1';
        }

        if (!empty($filters['para_birimi'])) {
            $where[] = 'c.para_birimi = :para_birimi';
            $params[':para_birimi'] = $filters['para_birimi'];
        }
        if (!empty($filters['cari_search'])) {
            $where[] = '(c.unvan LIKE :cari_search OR c.cari_kodu LIKE :cari_search OR c.telefon LIKE :cari_search OR c.eposta LIKE :cari_search)';
            $params[':cari_search'] = '%' . $filters['cari_search'] . '%';
        }
        if (!empty($filters['siniflandirma1']) && $this->columnExists('cariler', 'siniflandirma1')) {
            $where[] = 'c.siniflandirma1 = :siniflandirma1';
            $params[':siniflandirma1'] = $filters['siniflandirma1'];
        }
        if (!empty($filters['siniflandirma2']) && $this->columnExists('cariler', 'siniflandirma2')) {
            $where[] = 'c.siniflandirma2 = :siniflandirma2';
            $params[':siniflandirma2'] = $filters['siniflandirma2'];
        }
        if (!empty($filters['kaynak']) && $this->columnExists('cariler', 'kaynak')) {
            $where[] = 'c.kaynak = :kaynak';
            $params[':kaynak'] = $filters['kaynak'];
        }
        if (!empty($filters['satis_elemani_id']) && $this->columnExists('cariler', 'satis_elemani_id')) {
            $where[] = 'c.satis_elemani_id = :satis_elemani_id';
            $params[':satis_elemani_id'] = (int)$filters['satis_elemani_id'];
        }

        $balanceDate = $filters['bakiye_tarihi'] ?? '';
        $invoiceDateSql = '';
        $cashDateSql = '';
        if ($balanceDate !== '') {
            $invoiceDateSql = ' AND f.fatura_tarihi <= :balance_date_invoice';
            $cashDateSql = ' AND DATE(kh.tarih) <= :balance_date_cash';
            $params[':balance_date_invoice'] = $balanceDate;
            $params[':balance_date_cash'] = $balanceDate;
        }

        $optionalSelect = $this->customerOptionalSelect();
        $optionalJoin = $this->customerOptionalJoin();
        $chequeSql = $this->chequeSenetSql($balanceDate !== '');
        if ($balanceDate !== '' && $chequeSql['uses_date']) {
            $params[':balance_date_cheque'] = $balanceDate;
        }

        $rows = $this->db->select(
            "SELECT c.id, c.cari_kodu, c.unvan,
                    CASE c.tip WHEN 'musteri' THEN 'Müşteri' WHEN 'tedarikci' THEN 'Tedarikçi' ELSE 'Müşteri + Tedarikçi' END AS cari_tipi,
                    c.tip AS ham_tip, c.telefon, c.eposta, COALESCE(NULLIF(c.vergi_no, ''), c.tc_kimlik_no) AS vergi_no,
                    c.vergi_dairesi, c.il, c.ilce, c.adres, COALESCE(c.para_birimi, 'TL') AS para_birimi,
                    CASE WHEN c.silindi_mi = 0 THEN 'Aktif' ELSE 'Pasif' END AS durum,
                    {$optionalSelect}
                    COALESCE(inv.satis_toplami, 0) AS satis_toplami,
                    COALESCE(inv.alis_toplami, 0) AS alis_toplami,
                    COALESCE(inv.satis_iade_toplami, 0) AS satis_iade_toplami,
                    COALESCE(inv.alis_iade_toplami, 0) AS alis_iade_toplami,
                    COALESCE(kh.tahsilat_toplami, 0) AS tahsilat_toplami,
                    COALESCE(kh.odeme_toplami, 0) AS odeme_toplami,
                    COALESCE(cs.cek_senet_borcu, 0) AS cek_senet_borcu,
                    GREATEST(
                        (COALESCE(inv.satis_toplami, 0) + COALESCE(inv.alis_toplami, 0))
                        - (COALESCE(inv.satis_iade_toplami, 0) + COALESCE(inv.alis_iade_toplami, 0) + COALESCE(kh.tahsilat_toplami, 0) + COALESCE(kh.odeme_toplami, 0)),
                        0
                    ) AS acik_hesap_borcu,
                    (COALESCE(inv.satis_toplami, 0) + COALESCE(inv.alis_toplami, 0)) AS toplam_borc,
                    (COALESCE(inv.satis_iade_toplami, 0) + COALESCE(inv.alis_iade_toplami, 0) + COALESCE(kh.tahsilat_toplami, 0) + COALESCE(kh.odeme_toplami, 0)) AS toplam_alacak,
                    (COALESCE(inv.satis_toplami, 0) + COALESCE(inv.alis_toplami, 0))
                    - (COALESCE(inv.satis_iade_toplami, 0) + COALESCE(inv.alis_iade_toplami, 0) + COALESCE(kh.tahsilat_toplami, 0) + COALESCE(kh.odeme_toplami, 0)) AS net_bakiye,
                    CASE
                        WHEN ABS((COALESCE(inv.satis_toplami, 0) + COALESCE(inv.alis_toplami, 0))
                            - (COALESCE(inv.satis_iade_toplami, 0) + COALESCE(inv.alis_iade_toplami, 0) + COALESCE(kh.tahsilat_toplami, 0) + COALESCE(kh.odeme_toplami, 0))) < 0.005 THEN 'Sıfır'
                        WHEN ((COALESCE(inv.satis_toplami, 0) + COALESCE(inv.alis_toplami, 0))
                            - (COALESCE(inv.satis_iade_toplami, 0) + COALESCE(inv.alis_iade_toplami, 0) + COALESCE(kh.tahsilat_toplami, 0) + COALESCE(kh.odeme_toplami, 0))) > 0 THEN 'Borçlu'
                        ELSE 'Alacaklı'
                    END AS bakiye_durumu,
                    inv.son_satis_tarihi, kh.son_tahsilat_tarihi,
                    GREATEST(COALESCE(inv.son_islem_tarihi, '1000-01-01'), COALESCE(kh.son_islem_tarihi, '1000-01-01')) AS son_islem_tarihi,
                    c.notlar
             FROM cariler c
             LEFT JOIN (
                SELECT f.cari_id,
                       SUM(CASE WHEN f.belge_tipi IN ('satis','perakende') THEN f.genel_toplam ELSE 0 END) AS satis_toplami,
                       SUM(CASE WHEN f.belge_tipi = 'alis' THEN f.genel_toplam ELSE 0 END) AS alis_toplami,
                       SUM(CASE WHEN f.belge_tipi = 'iade_satis' THEN f.genel_toplam ELSE 0 END) AS satis_iade_toplami,
                       SUM(CASE WHEN f.belge_tipi = 'iade_alis' THEN f.genel_toplam ELSE 0 END) AS alis_iade_toplami,
                       MAX(CASE WHEN f.belge_tipi IN ('satis','perakende') THEN f.fatura_tarihi END) AS son_satis_tarihi,
                       MAX(f.fatura_tarihi) AS son_islem_tarihi
                FROM faturalar f
                WHERE f.silindi_mi = 0 AND f.durum NOT IN ('iptal','taslak')
                  AND f.company_id = :tenant_company_id AND f.period_id = :tenant_period_id {$invoiceDateSql}
                GROUP BY f.cari_id
             ) inv ON inv.cari_id = c.id
             LEFT JOIN (
                SELECT kh.cari_id,
                       SUM(CASE WHEN kh.islem_tipi = 'giris' THEN kh.tutar ELSE 0 END) AS tahsilat_toplami,
                       SUM(CASE WHEN kh.islem_tipi = 'cikis' THEN kh.tutar ELSE 0 END) AS odeme_toplami,
                       MAX(CASE WHEN kh.islem_tipi = 'giris' THEN DATE(kh.tarih) END) AS son_tahsilat_tarihi,
                       MAX(DATE(kh.tarih)) AS son_islem_tarihi
                FROM kasa_hareketleri kh
                WHERE kh.silindi_mi = 0 AND kh.cari_id IS NOT NULL
                  AND kh.company_id = :tenant_company_id AND kh.period_id = :tenant_period_id {$cashDateSql}
                GROUP BY kh.cari_id
             ) kh ON kh.cari_id = c.id
             LEFT JOIN ({$chequeSql['sql']}) cs ON cs.cari_id = c.id
             {$optionalJoin}
             WHERE " . implode(' AND ', $where) . "
             ORDER BY c.unvan",
            $params
        );

        $rows = array_values(array_filter($rows, function ($row) use ($filters): bool {
            $net = (float)$row['net_bakiye'];
            if (!empty($filters['acik_borclular']) && $net <= 0.005) {
                return false;
            }
            if (!empty($filters['alacaklilar']) && $net >= -0.005) {
                return false;
            }
            if (!empty($filters['cek_senet_borclular']) && (float)$row['cek_senet_borcu'] <= 0.005) {
                return false;
            }
            if (!empty($filters['risk_asimi'])) {
                if (!isset($row['risk_limiti']) || (float)$row['risk_limiti'] <= 0 || $net <= (float)$row['risk_limiti']) {
                    return false;
                }
            }
            return true;
        }));

        foreach ($rows as &$row) {
            if ($row['son_islem_tarihi'] === '1000-01-01') {
                $row['son_islem_tarihi'] = null;
            }
        }
        unset($row);

        $summary = [
            'Toplam cari sayısı' => count($rows),
            'Aktif cari sayısı' => count(array_filter($rows, fn($r) => $r['durum'] === 'Aktif')),
            'Pasif cari sayısı' => count(array_filter($rows, fn($r) => $r['durum'] === 'Pasif')),
            'Toplam borç' => $this->sum($rows, 'toplam_borc', true),
            'Toplam alacak' => $this->sum($rows, 'toplam_alacak', true),
            'Net bakiye' => $this->money($this->sum($rows, 'net_bakiye')),
            'Borçlu cari sayısı' => count(array_filter($rows, fn($r) => (float)$r['net_bakiye'] > 0.005)),
            'Alacaklı cari sayısı' => count(array_filter($rows, fn($r) => (float)$r['net_bakiye'] < -0.005)),
            'Risk limitini aşan cari' => count(array_filter($rows, fn($r) => isset($r['risk_limiti']) && (float)$r['risk_limiti'] > 0 && (float)$r['net_bakiye'] > (float)$r['risk_limiti'])),
            'Satışı olmayan müşteri' => count(array_filter($rows, fn($r) => in_array($r['ham_tip'], ['musteri', 'her_ikisi'], true) && (float)$r['satis_toplami'] <= 0.005)),
        ];

        $missing = $this->customerReportMissingColumns();
        $note = $missing
            ? 'Teknik not: ' . implode(', ', $missing) . ' alan/tablo yapısı bulunamadığı için ilgili filtreler bilgilendirme amaçlı gösterilir.'
            : 'Cari bakiyeler satış/alış faturaları, iadeler ve kasa hareketleri üzerinden hesaplanır; iptal ve taslak faturalar toplam dışıdır.';

        return $this->withSummary($rows, $summary, $note);
    }

    public function getCustomerBalance($customerId, $balanceDate = null): array
    {
        $report = $this->getCustomerListReport([
            'tip' => 'musteriler',
            'cari_search' => '',
            'bakiye_tarihi' => $balanceDate ?: '',
        ]);
        foreach ($report['rows'] as $row) {
            if ((int)$row['id'] === (int)$customerId) {
                return $row;
            }
        }
        return ['toplam_borc' => 0, 'toplam_alacak' => 0, 'net_bakiye' => 0, 'bakiye_durumu' => 'Sıfır'];
    }

    public function getSupplierBalance($supplierId, $balanceDate = null): array
    {
        $report = $this->getCustomerListReport([
            'tip' => 'tedarikciler',
            'cari_search' => '',
            'bakiye_tarihi' => $balanceDate ?: '',
        ]);
        foreach ($report['rows'] as $row) {
            if ((int)$row['id'] === (int)$supplierId) {
                return $row;
            }
        }
        return ['toplam_borc' => 0, 'toplam_alacak' => 0, 'net_bakiye' => 0, 'bakiye_durumu' => 'Sıfır'];
    }

    public function getCustomerSalesSummary($customerId, $dateRange = null): array
    {
        $params = [':customer_id' => (int)$customerId];
        $params[':tenant_company_id'] = TenantContext::activeCompanyId();
        $params[':tenant_period_id'] = TenantContext::activePeriodId();
        $dateSql = '';
        if (is_array($dateRange) && !empty($dateRange['start_date'])) {
            $dateSql .= ' AND f.fatura_tarihi >= :start_date';
            $params[':start_date'] = $dateRange['start_date'];
        }
        if (is_array($dateRange) && !empty($dateRange['end_date'])) {
            $dateSql .= ' AND f.fatura_tarihi <= :end_date';
            $params[':end_date'] = $dateRange['end_date'];
        }
        return $this->db->selectOne(
            "SELECT COUNT(*) AS fatura_sayisi, COALESCE(SUM(genel_toplam), 0) AS toplam_satis, MAX(fatura_tarihi) AS son_satis_tarihi
             FROM faturalar f
             WHERE f.silindi_mi = 0 AND f.company_id = :tenant_company_id AND f.period_id = :tenant_period_id
               AND f.durum NOT IN ('iptal','taslak') AND f.belge_tipi IN ('satis','perakende') AND f.cari_id = :customer_id {$dateSql}",
            $params
        ) ?: ['fatura_sayisi' => 0, 'toplam_satis' => 0, 'son_satis_tarihi' => null];
    }

    public function getCustomerCollectionSummary($customerId, $dateRange = null): array
    {
        $params = [':customer_id' => (int)$customerId];
        $params[':tenant_company_id'] = TenantContext::activeCompanyId();
        $params[':tenant_period_id'] = TenantContext::activePeriodId();
        $dateSql = '';
        if (is_array($dateRange) && !empty($dateRange['start_date'])) {
            $dateSql .= ' AND DATE(kh.tarih) >= :start_date';
            $params[':start_date'] = $dateRange['start_date'];
        }
        if (is_array($dateRange) && !empty($dateRange['end_date'])) {
            $dateSql .= ' AND DATE(kh.tarih) <= :end_date';
            $params[':end_date'] = $dateRange['end_date'];
        }
        return $this->db->selectOne(
            "SELECT COUNT(*) AS tahsilat_sayisi, COALESCE(SUM(tutar), 0) AS toplam_tahsilat, MAX(DATE(tarih)) AS son_tahsilat_tarihi
             FROM kasa_hareketleri kh
             WHERE kh.silindi_mi = 0 AND kh.company_id = :tenant_company_id AND kh.period_id = :tenant_period_id
               AND kh.islem_tipi = 'giris' AND kh.cari_id = :customer_id {$dateSql}",
            $params
        ) ?: ['tahsilat_sayisi' => 0, 'toplam_tahsilat' => 0, 'son_tahsilat_tarihi' => null];
    }

    public function getChequeSenetBalance($customerId, $type = null): float
    {
        if (!$this->tableExists('cek_senet_portfoyu')) {
            return 0.0;
        }
        $params = [':cari_id' => (int)$customerId];
        $typeSql = '';
        if ($type !== null) {
            $typeSql = ' AND tur = :tur';
            $params[':tur'] = $type;
        }
        $params[':tenant_company_id'] = TenantContext::activeCompanyId();
        $params[':tenant_period_id'] = TenantContext::activePeriodId();
        $row = $this->db->selectOne("SELECT COALESCE(SUM(tutar), 0) AS bakiye FROM cek_senet_portfoyu WHERE company_id = :tenant_company_id AND period_id = :tenant_period_id AND cari_id = :cari_id {$typeSql}", $params);
        return (float)($row['bakiye'] ?? 0);
    }

    public function getRiskLimitStatus($customerId): array
    {
        if (!$this->columnExists('cariler', 'risk_limiti')) {
            return ['available' => false, 'exceeded' => false, 'risk_limiti' => 0];
        }
        $row = $this->db->selectOne("SELECT risk_limiti FROM cariler WHERE id = :id AND company_id = :company_id", [':id' => (int)$customerId, ':company_id' => TenantContext::activeCompanyId()]);
        $balance = $this->getCustomerBalance((int)$customerId);
        $limit = (float)($row['risk_limiti'] ?? 0);
        return ['available' => true, 'exceeded' => $limit > 0 && (float)$balance['net_bakiye'] > $limit, 'risk_limiti' => $limit];
    }

    public function getProductStockReport(array $filters): array
    {
        $params = [
            ':tenant_company_id' => TenantContext::activeCompanyId(),
            ':tenant_period_id' => TenantContext::activePeriodId(),
        ];
        $where = ["u.silindi_mi = 0", "u.tip = 'urun'", "u.company_id = :tenant_company_id"];
        if (!empty($filters['product_id'])) {
            $where[] = 'u.id = :product_id';
            $params[':product_id'] = (int)$filters['product_id'];
        }
        if (!empty($filters['product_search'])) {
            $where[] = '(u.ad LIKE :product_search OR u.stok_kodu LIKE :product_search OR u.barkod LIKE :product_search)';
            $params[':product_search'] = '%' . $filters['product_search'] . '%';
        }
        if (!empty($filters['category'])) {
            $where[] = 'u.kategori = :category';
            $params[':category'] = $filters['category'];
        }
        if (($filters['stock_filter'] ?? '') === 'in_stock') {
            $where[] = 'COALESCE(st.hareket_stok, u.stok_miktari, 0) > 0';
        } elseif (($filters['stock_filter'] ?? '') === 'out_of_stock') {
            $where[] = 'COALESCE(st.hareket_stok, u.stok_miktari, 0) <= 0';
        } elseif (($filters['stock_filter'] ?? '') === 'critical') {
            $where[] = 'COALESCE(st.hareket_stok, u.stok_miktari, 0) <= COALESCE(u.kritik_stok, 0)';
        }
        if (($filters['min_qty'] ?? '') !== '') {
            $where[] = 'COALESCE(st.hareket_stok, u.stok_miktari, 0) >= :min_qty';
            $params[':min_qty'] = (float)$filters['min_qty'];
        }
        if (($filters['max_qty'] ?? '') !== '') {
            $where[] = 'COALESCE(st.hareket_stok, u.stok_miktari, 0) <= :max_qty';
            $params[':max_qty'] = (float)$filters['max_qty'];
        }
        if (($filters['min_price'] ?? '') !== '') {
            $where[] = 'u.satis_fiyati >= :min_price';
            $params[':min_price'] = (float)$filters['min_price'];
        }
        if (($filters['max_price'] ?? '') !== '') {
            $where[] = 'u.satis_fiyati <= :max_price';
            $params[':max_price'] = (float)$filters['max_price'];
        }

        $rows = $this->db->select(
            "SELECT u.id AS urun_id, u.ad AS urun_adi, u.stok_kodu, u.barkod,
                    COALESCE(u.kategori, '-') AS kategori, u.birim, u.alis_fiyati, u.satis_fiyati,
                    COALESCE(st.hareket_stok, u.stok_miktari, 0) AS mevcut_stok,
                    u.stok_miktari AS urun_tablosu_stok,
                    COALESCE(st.hareket_stok, 0) AS hareketlerden_stok,
                    COALESCE(st.hareket_stok, u.stok_miktari, 0) - u.stok_miktari AS stok_farki,
                    u.kritik_stok AS minimum_stok,
                    COALESCE(st.hareket_stok, u.stok_miktari, 0) * COALESCE(NULLIF(p.avg_cost, 0), u.alis_fiyati, 0) AS stok_degeri,
                    p.son_alis_tarihi, s.son_satis_tarihi,
                    CASE WHEN u.silindi_mi = 0 THEN 'Aktif' ELSE 'Pasif' END AS durum,
                    CASE
                        WHEN COALESCE(st.hareket_stok, u.stok_miktari, 0) < 0 THEN 'Negatif stok'
                        WHEN COALESCE(st.hareket_stok, u.stok_miktari, 0) <= 0 THEN 'Stok yok'
                        WHEN COALESCE(st.hareket_stok, u.stok_miktari, 0) <= COALESCE(u.kritik_stok, 0) THEN 'Kritik stok'
                        ELSE 'Normal'
                    END AS kritik_stok_uyarisi
             FROM urunler_hizmetler u
             LEFT JOIN (" . $this->stockBalanceSql() . ") st ON st.urun_id = u.id
             LEFT JOIN (
                SELECT fk.urun_id, MAX(f.fatura_tarihi) AS son_alis_tarihi,
                       CASE WHEN SUM(fk.miktar) > 0 THEN SUM(fk.toplam) / SUM(fk.miktar) ELSE 0 END AS avg_cost
                FROM fatura_kalemleri fk
                JOIN faturalar f ON f.id = fk.fatura_id
                WHERE fk.silindi_mi = 0 AND f.silindi_mi = 0
                  AND f.company_id = :tenant_company_id AND f.period_id = :tenant_period_id
                  AND f.durum NOT IN ('iptal','taslak') AND f.belge_tipi = 'alis'
                GROUP BY fk.urun_id
             ) p ON p.urun_id = u.id
             LEFT JOIN (
                SELECT fk.urun_id, MAX(f.fatura_tarihi) AS son_satis_tarihi
                FROM fatura_kalemleri fk
                JOIN faturalar f ON f.id = fk.fatura_id
                WHERE fk.silindi_mi = 0 AND f.silindi_mi = 0
                  AND f.company_id = :tenant_company_id AND f.period_id = :tenant_period_id
                  AND f.durum NOT IN ('iptal','taslak') AND f.belge_tipi IN ('satis','perakende')
                GROUP BY fk.urun_id
             ) s ON s.urun_id = u.id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY u.ad",
            $params
        );

        $mismatches = count(array_filter($rows, fn($r) => abs((float)$r['stok_farki']) > 0.001));
        return $this->withSummary($rows, [
            'Toplam ürün sayısı' => count($rows),
            'Aktif ürün sayısı' => count($rows),
            'Stokta olan ürün' => count(array_filter($rows, fn($r) => (float)$r['mevcut_stok'] > 0)),
            'Stokta olmayan ürün' => count(array_filter($rows, fn($r) => (float)$r['mevcut_stok'] <= 0)),
            'Kritik stoktaki ürün' => count(array_filter($rows, fn($r) => in_array($r['kritik_stok_uyarisi'], ['Kritik stok', 'Stok yok', 'Negatif stok'], true))),
            'Toplam stok değeri' => $this->sum($rows, 'stok_degeri', true),
            'Stok tutarsızlığı' => $mismatches,
        ], $mismatches > 0 ? 'Ürün tablosundaki stok ile fatura/manuel hareketlerden hesaplanan stok arasında fark olan ürünler var; stok_farki kolonu kontrol edilmelidir.' : 'Stok hesaplaması alış/satış/iade fatura kalemleri ve manuel stok hareketlerinden hesaplanır.');
    }

    public function getStockMovementsReport(array $filters): array
    {
        [$dateSql, $params] = $this->dateWhere($filters, 'hareket_tarihi');
        $where = ['1=1'];
        if ($dateSql) $where[] = preg_replace('/^\s*AND\s+/i', '', $dateSql);
        if (!empty($filters['product_id'])) {
            $where[] = 'urun_id = :product_id';
            $params[':product_id'] = (int)$filters['product_id'];
        }
        if (!empty($filters['category'])) {
            $where[] = 'kategori = :category';
            $params[':category'] = $filters['category'];
        }
        if (!empty($filters['warehouse_id'])) {
            $where[] = 'depo_id = :warehouse_id';
            $params[':warehouse_id'] = (int)$filters['warehouse_id'];
        }
        if (!empty($filters['movement_type'])) {
            $where[] = 'hareket_tipi = :movement_type';
            $params[':movement_type'] = $filters['movement_type'];
        }
        if (!empty($filters['source_type'])) {
            $where[] = 'kaynak_turu = :source_type';
            $params[':source_type'] = $filters['source_type'];
        }
        if (!empty($filters['invoice_no'])) {
            $where[] = 'kaynak_belge_no LIKE :invoice_no';
            $params[':invoice_no'] = '%' . $filters['invoice_no'] . '%';
        }
        if (($filters['transaction_type'] ?? '') === 'giris') {
            $where[] = 'giris_miktari > 0';
        } elseif (($filters['transaction_type'] ?? '') === 'cikis') {
            $where[] = 'cikis_miktari > 0';
        }

        $rows = $this->db->select(
            "SELECT * FROM (" . $this->stockMovementsSql() . ") sm
             WHERE " . implode(' AND ', $where) . "
             ORDER BY hareket_tarihi DESC, kaynak_id DESC, urun_adi",
            $params
        );
        $topMove = $this->topBy($rows, 'urun_adi', fn($r) => (float)$r['giris_miktari'] + (float)$r['cikis_miktari']);
        $topOut = $this->topBy($rows, 'urun_adi', fn($r) => (float)$r['cikis_miktari']);
        return $this->withSummary($rows, [
            'Toplam giriş miktarı' => number_format($this->sum($rows, 'giris_miktari'), 3, ',', '.'),
            'Toplam çıkış miktarı' => number_format($this->sum($rows, 'cikis_miktari'), 3, ',', '.'),
            'Net stok hareketi' => number_format($this->sum($rows, 'giris_miktari') - $this->sum($rows, 'cikis_miktari'), 3, ',', '.'),
            'Toplam hareket sayısı' => count($rows),
            'En çok hareket gören ürün' => $topMove ?: '-',
            'En çok çıkış yapılan ürün' => $topOut ?: '-',
        ], $this->tableExists('stok_hareketleri') ? 'Stok hareketleri ana kaynak olarak listelenir; fatura kalemleri yalnızca karşılık gelen stok hareketi yoksa fallback hareket üretir.' : 'Kalıcı stok_hareketleri tablosu bulunmadığında rapor alış/satış/iade fatura kalemlerinden sanal hareket üretir.');
    }

    public function getWarehouseStatusReport(array $filters): array
    {
        $params = [':tenant_company_id' => TenantContext::activeCompanyId()];
        $where = ["u.silindi_mi = 0", "u.tip = 'urun'", "u.company_id = :tenant_company_id"];
        if (!empty($filters['product_id'])) {
            $where[] = 'u.id = :product_id';
            $params[':product_id'] = (int)$filters['product_id'];
        }
        if (!empty($filters['category'])) {
            $where[] = 'u.kategori = :category';
            $params[':category'] = $filters['category'];
        }
        if (!empty($filters['warehouse_id']) && $this->tableExists('depolar')) {
            $where[] = 'd.id = :warehouse_id';
            $params[':warehouse_id'] = (int)$filters['warehouse_id'];
        }
        if (($filters['stock_filter'] ?? '') === 'in_stock') {
            $where[] = 'COALESCE(ws.mevcut_stok, u.stok_miktari, 0) > 0';
        } elseif (($filters['stock_filter'] ?? '') === 'out_of_stock') {
            $where[] = 'COALESCE(ws.mevcut_stok, u.stok_miktari, 0) <= 0';
        } elseif (($filters['stock_filter'] ?? '') === 'critical') {
            $where[] = 'COALESCE(ws.mevcut_stok, u.stok_miktari, 0) <= COALESCE(u.kritik_stok, 0)';
        }

        if ($this->tableExists('depolar') && $this->tableExists('urun_stok_depo')) {
            $rows = $this->db->select(
                "SELECT d.ad AS depo_adi, u.ad AS urun_adi, COALESCE(u.kategori, '-') AS kategori,
                        COALESCE(ws.mevcut_stok, usd.miktar, 0) AS mevcut_stok, u.kritik_stok AS minimum_stok,
                        0 AS rezerve_stok, COALESCE(ws.mevcut_stok, usd.miktar, 0) AS kullanilabilir_stok,
                        COALESCE(ws.mevcut_stok, usd.miktar, 0) * COALESCE(u.alis_fiyati, 0) AS stok_degeri,
                        ws.son_hareket_tarihi,
                        CASE WHEN COALESCE(ws.mevcut_stok, usd.miktar, 0) <= 0 THEN 'Stok yok' WHEN COALESCE(ws.mevcut_stok, usd.miktar, 0) <= COALESCE(u.kritik_stok, 0) THEN 'Kritik' ELSE 'Normal' END AS durum
                 FROM urunler_hizmetler u
                 LEFT JOIN urun_stok_depo usd ON usd.urun_id = u.id AND usd.company_id = u.company_id
                 LEFT JOIN depolar d ON d.id = COALESCE(usd.depo_id, u.depo_id) AND d.company_id = u.company_id
                 LEFT JOIN (" . $this->warehouseStockSql() . ") ws ON ws.urun_id = u.id AND ws.depo_id = d.id
                 WHERE " . implode(' AND ', $where) . "
                 ORDER BY d.ad, u.ad",
                $params
            );
            $note = 'Depo bazlı stok varsa urun_stok_depo ve manuel depo hareketlerinden gösterilir; fatura kalemlerinde depo_id olmadığı için fatura stokları genel stok olarak değerlendirilir.';
        } else {
            $rows = $this->getProductStockReport($filters)['rows'];
            foreach ($rows as &$row) {
                $row = [
                    'depo_adi' => 'Genel Stok',
                    'urun_adi' => $row['urun_adi'],
                    'kategori' => $row['kategori'],
                    'mevcut_stok' => $row['mevcut_stok'],
                    'minimum_stok' => $row['minimum_stok'],
                    'rezerve_stok' => 0,
                    'kullanilabilir_stok' => $row['mevcut_stok'],
                    'stok_degeri' => $row['stok_degeri'],
                    'son_hareket_tarihi' => max((string)$row['son_alis_tarihi'], (string)$row['son_satis_tarihi']),
                    'durum' => $row['kritik_stok_uyarisi'],
                ];
            }
            unset($row);
            $note = 'Depo bazlı takip için sistemde tam depo yapısı bulunamadı. Mevcut stoklar genel stok olarak gösteriliyor.';
        }

        $topWarehouse = $this->topBy($rows, 'depo_adi', fn($r) => (float)$r['mevcut_stok']);
        return $this->withSummary($rows, [
            'Toplam depo sayısı' => count(array_unique(array_map(fn($r) => $r['depo_adi'], $rows))),
            'Toplam stok değeri' => $this->sum($rows, 'stok_degeri', true),
            'En yüksek stoklu depo' => $topWarehouse ?: '-',
            'Kritik stoktaki ürün' => count(array_filter($rows, fn($r) => in_array($r['durum'], ['Kritik', 'Kritik stok', 'Stok yok', 'Negatif stok'], true))),
            'Stok olmayan ürün' => count(array_filter($rows, fn($r) => (float)$r['mevcut_stok'] <= 0)),
        ], $note);
    }

    public function getSalesReport(array $filters): array
    {
        [$where, $params] = $this->invoiceWhere($filters, "f.belge_tipi IN ('satis','perakende')", 'musteri');
        $payExpr = 'COALESCE(f.odenen_tutar, 0)';
        $rows = $this->db->select(
            "SELECT f.fatura_tarihi, f.fatura_no, COALESCE(c.unvan, 'Cari yok') AS cari_unvan, f.durum,
                    f.ara_toplam, f.kdv_tutari, f.iskonto_tutari, f.genel_toplam,
                    {$payExpr} AS odenen_tutar,
                    GREATEST(f.genel_toplam - {$payExpr}, 0) AS kalan_tutar,
                    CASE
                        WHEN f.durum = 'iptal' THEN 'İptal'
                        WHEN {$payExpr} <= 0 THEN 'Ödenmedi'
                        WHEN {$payExpr} + 0.005 >= f.genel_toplam THEN 'Ödendi'
                        ELSE 'Kısmi Ödendi'
                    END AS odeme_durumu
             FROM faturalar f
             LEFT JOIN cariler c ON c.id = f.cari_id
             WHERE {$where}
             ORDER BY f.fatura_tarihi DESC, f.id DESC",
            $params
        );

        return $this->withSummary($rows, [
            'Toplam satış tutarı' => $this->sum($rows, 'genel_toplam', true),
            'Toplam KDV' => $this->sum($rows, 'kdv_tutari', true),
            'Toplam tahsilat' => $this->sum($rows, 'odenen_tutar', true),
            'Bekleyen tahsilat' => $this->sum($rows, 'kalan_tutar', true),
            'Fatura sayısı' => count($rows),
            'Ortalama fatura tutarı' => count($rows) ? $this->money($this->sum($rows, 'genel_toplam') / count($rows)) : $this->money(0),
        ]);
    }

    public function getPurchaseReport(array $filters): array
    {
        [$where, $params] = $this->invoiceWhere($filters, "f.belge_tipi = 'alis'", 'tedarikci');
        $payExpr = 'COALESCE(f.odenen_tutar, 0)';
        $rows = $this->db->select(
            "SELECT f.fatura_tarihi, f.fatura_no, COALESCE(c.unvan, 'Cari yok') AS tedarikci,
                    f.ara_toplam, f.kdv_tutari, f.genel_toplam,
                    {$payExpr} AS odenen_tutar,
                    GREATEST(f.genel_toplam - {$payExpr}, 0) AS kalan_tutar,
                    f.durum
             FROM faturalar f
             LEFT JOIN cariler c ON c.id = f.cari_id
             WHERE {$where}
             ORDER BY f.fatura_tarihi DESC, f.id DESC",
            $params
        );

        return $this->withSummary($rows, [
            'Toplam alış tutarı' => $this->sum($rows, 'genel_toplam', true),
            'Toplam KDV' => $this->sum($rows, 'kdv_tutari', true),
            'Toplam ödeme' => $this->sum($rows, 'odenen_tutar', true),
            'Kalan tedarikçi borcu' => $this->sum($rows, 'kalan_tutar', true),
            'Alış faturası sayısı' => count($rows),
        ]);
    }

    public function getProductPurchaseSalesReport(array $filters): array
    {
        [$purchaseDateSql, $purchaseDateParams] = $this->dateWhereForPrefix($filters, 'f.fatura_tarihi', 'p');
        [$salesDateSql, $salesDateParams] = $this->dateWhereForPrefix($filters, 'f.fatura_tarihi', 's');
        $params = $purchaseDateParams + $salesDateParams;
        $params[':tenant_company_id'] = TenantContext::activeCompanyId();
        $params[':tenant_period_id'] = TenantContext::activePeriodId();
        $extra = ['u.company_id = :tenant_company_id', 'u.silindi_mi = 0'];
        if (!empty($filters['product_id'])) {
            $extra[] = 'u.id = :product_id';
            $params[':product_id'] = (int)$filters['product_id'];
        }
        if (!empty($filters['category'])) {
            $extra[] = 'u.kategori = :category';
            $params[':category'] = $filters['category'];
        }
        if (($filters['stock_filter'] ?? '') === 'in_stock') {
            $extra[] = 'u.stok_miktari > 0';
        } elseif (($filters['stock_filter'] ?? '') === 'out_of_stock') {
            $extra[] = 'u.stok_miktari <= 0';
        }
        if (($filters['min_qty'] ?? '') !== '') {
            $extra[] = 'COALESCE(s.sales_qty, 0) >= :min_qty';
            $params[':min_qty'] = (float)$filters['min_qty'];
        }
        $where = $extra ? 'WHERE ' . implode(' AND ', $extra) : '';

        $rows = $this->db->select(
            "SELECT u.ad AS urun_adi, COALESCE(u.kategori, '-') AS kategori,
                    COALESCE(p.purchase_qty, 0) AS alis_miktari,
                    COALESCE(p.purchase_total, 0) AS alis_tutari,
                    CASE WHEN COALESCE(p.purchase_qty, 0) > 0 THEN p.purchase_total / p.purchase_qty ELSE 0 END AS ort_alis_fiyati,
                    COALESCE(s.sales_qty, 0) AS satis_miktari,
                    COALESCE(s.sales_total, 0) AS satis_tutari,
                    CASE WHEN COALESCE(s.sales_qty, 0) > 0 THEN s.sales_total / s.sales_qty ELSE 0 END AS ort_satis_fiyati,
                    COALESCE(s.sales_total, 0) - COALESCE(s.sales_qty, 0) * CASE WHEN COALESCE(p.purchase_qty, 0) > 0 THEN p.purchase_total / p.purchase_qty ELSE u.alis_fiyati END AS brut_kar,
                    CASE WHEN COALESCE(s.sales_total, 0) > 0 THEN ((COALESCE(s.sales_total, 0) - COALESCE(s.sales_qty, 0) * CASE WHEN COALESCE(p.purchase_qty, 0) > 0 THEN p.purchase_total / p.purchase_qty ELSE u.alis_fiyati END) / s.sales_total) * 100 ELSE 0 END AS kar_marji,
                    u.stok_miktari AS mevcut_stok
             FROM urunler_hizmetler u
             LEFT JOIN (
                SELECT fk.urun_id, SUM(fk.miktar) AS purchase_qty, SUM(fk.toplam) AS purchase_total
                FROM fatura_kalemleri fk
                JOIN faturalar f ON f.id = fk.fatura_id
                WHERE fk.silindi_mi = 0 AND f.silindi_mi = 0
                  AND f.company_id = :tenant_company_id AND f.period_id = :tenant_period_id
                  AND f.durum <> 'iptal' AND f.belge_tipi = 'alis' {$purchaseDateSql}
                GROUP BY fk.urun_id
             ) p ON p.urun_id = u.id
             LEFT JOIN (
                SELECT fk.urun_id, SUM(fk.miktar) AS sales_qty, SUM(fk.toplam) AS sales_total
                FROM fatura_kalemleri fk
                JOIN faturalar f ON f.id = fk.fatura_id
                WHERE fk.silindi_mi = 0 AND f.silindi_mi = 0
                  AND f.company_id = :tenant_company_id AND f.period_id = :tenant_period_id
                  AND f.durum <> 'iptal' AND f.belge_tipi IN ('satis','perakende') {$salesDateSql}
                GROUP BY fk.urun_id
             ) s ON s.urun_id = u.id
             {$where}
             ORDER BY satis_tutari DESC, u.ad",
            $params
        );

        return $this->withSummary($rows, [
            'Toplam satış' => $this->sum($rows, 'satis_tutari', true),
            'Toplam alış' => $this->sum($rows, 'alis_tutari', true),
            'Tahmini brüt kâr' => $this->sum($rows, 'brut_kar', true),
            'Ürün sayısı' => count($rows),
        ]);
    }

    public function getCustomerSalesReport(array $filters): array
    {
        [$dateSql, $params] = $this->dateWhere($filters, 'f.fatura_tarihi');
        $params[':tenant_company_id'] = TenantContext::activeCompanyId();
        $params[':tenant_period_id'] = TenantContext::activePeriodId();
        $having = [];
        if (($filters['customer_search'] ?? '') !== '') {
            $params[':customer_search'] = '%' . $filters['customer_search'] . '%';
        }
        if (($filters['balance_filter'] ?? '') === 'debtors') {
            $having[] = 'kalan_bakiye > 0';
        } elseif (($filters['balance_filter'] ?? '') === 'paid') {
            $having[] = 'kalan_bakiye <= 0';
        }
        if (($filters['min_amount'] ?? '') !== '') {
            $having[] = 'toplam_satis >= :min_amount';
            $params[':min_amount'] = (float)$filters['min_amount'];
        }
        if (($filters['max_amount'] ?? '') !== '') {
            $having[] = 'toplam_satis <= :max_amount';
            $params[':max_amount'] = (float)$filters['max_amount'];
        }
        $nameSql = ($filters['customer_search'] ?? '') !== '' ? 'AND c.unvan LIKE :customer_search' : '';
        $havingSql = $having ? 'HAVING ' . implode(' AND ', $having) : '';

        $rows = $this->db->select(
            "SELECT c.unvan AS musteri_adi, COUNT(f.id) AS fatura_sayisi,
                    COALESCE(SUM(f.genel_toplam), 0) AS toplam_satis,
                    COALESCE(SUM(COALESCE(f.odenen_tutar, 0)), 0) AS toplam_tahsilat,
                    COALESCE(SUM(GREATEST(f.genel_toplam - COALESCE(f.odenen_tutar, 0), 0)), 0) AS kalan_bakiye,
                    MAX(f.fatura_tarihi) AS son_satis_tarihi,
                    MAX(pay.son_tahsilat_tarihi) AS son_tahsilat_tarihi,
                    CASE WHEN COUNT(f.id) > 0 THEN SUM(f.genel_toplam) / COUNT(f.id) ELSE 0 END AS ort_fatura_tutari
             FROM cariler c
             JOIN faturalar f ON f.cari_id = c.id AND f.silindi_mi = 0 AND f.durum <> 'iptal' AND f.belge_tipi IN ('satis','perakende')
                  AND f.company_id = :tenant_company_id AND f.period_id = :tenant_period_id {$dateSql}
             LEFT JOIN (
                SELECT cari_id, MAX(tarih) AS son_tahsilat_tarihi
                FROM kasa_hareketleri
                WHERE silindi_mi = 0 AND (hareket_tipi IN ('tahsilat','gelir') OR islem_tipi = 'giris') AND cari_id IS NOT NULL
                  AND company_id = :tenant_company_id AND period_id = :tenant_period_id
                GROUP BY cari_id
             ) pay ON pay.cari_id = c.id
             WHERE c.silindi_mi = 0 AND c.company_id = :tenant_company_id {$nameSql}
             GROUP BY c.id, c.unvan
             {$havingSql}
             ORDER BY toplam_satis DESC",
            $params
        );

        return $this->withSummary($rows, [
            'Toplam satış' => $this->sum($rows, 'toplam_satis', true),
            'Toplam tahsilat' => $this->sum($rows, 'toplam_tahsilat', true),
            'Kalan bakiye' => $this->sum($rows, 'kalan_bakiye', true),
            'Müşteri sayısı' => count($rows),
        ]);
    }

    public function getSalesLossReport(array $filters): array
    {
        [$where, $params] = $this->invoiceWhere($filters, "f.belge_tipi IN ('satis','perakende')", 'musteri', true);
        $where .= " AND f.durum IN ('iptal','taslak')";
        $rows = $this->db->select(
            "SELECT f.fatura_tarihi AS tarih, COALESCE(c.unvan, 'Cari yok') AS cari,
                    f.fatura_no AS belge_no,
                    CASE WHEN f.durum = 'iptal' THEN 'İptal edilen satış' ELSE 'Taslak/tamamlanmamış satış' END AS kayip_turu,
                    f.genel_toplam AS tutar, f.aciklama, f.durum
             FROM faturalar f
             LEFT JOIN cariler c ON c.id = f.cari_id
             WHERE {$where}
             ORDER BY f.fatura_tarihi DESC, f.id DESC",
            $params
        );

        $note = 'Satış kaybı için özel teklif kaybı, stok yetersizliği veya silinen satış takip tablosu bulunmadı; bu rapor mevcut faturalar içindeki iptal ve taslak satışlardan üretilir.';
        return $this->withSummary($rows, [
            'Kayıp tutarı' => $this->sum($rows, 'tutar', true),
            'Kayıp kayıt sayısı' => count($rows),
        ], $note);
    }

    public function getReturnsReport(array $filters): array
    {
        [$where, $params] = $this->invoiceWhere($filters, "f.belge_tipi IN ('iade_satis','iade_alis')", '');
        if (!empty($filters['return_type'])) {
            $where .= ' AND f.belge_tipi = :return_type';
            $params[':return_type'] = $filters['return_type'];
        }
        if (!empty($filters['product_id'])) {
            $where .= ' AND fk.urun_id = :product_id';
            $params[':product_id'] = (int)$filters['product_id'];
        }
        $rows = $this->db->select(
            "SELECT f.fatura_tarihi AS tarih,
                    CASE WHEN f.belge_tipi = 'iade_satis' THEN 'Satış iadesi' ELSE 'Alış iadesi' END AS iade_turu,
                    COALESCE(c.unvan, 'Cari yok') AS cari,
                    fk.urun_adi AS urun,
                    fk.miktar, fk.toplam AS tutar, fk.kdv_tutari, COALESCE(f.aciklama, fk.aciklama) AS aciklama, f.durum
             FROM faturalar f
             JOIN fatura_kalemleri fk ON fk.fatura_id = f.id AND fk.silindi_mi = 0
             LEFT JOIN cariler c ON c.id = f.cari_id
             WHERE {$where}
             ORDER BY f.fatura_tarihi DESC, f.id DESC",
            $params
        );

        return $this->withSummary($rows, [
            'Toplam iade tutarı' => $this->sum($rows, 'tutar', true),
            'Toplam KDV' => $this->sum($rows, 'kdv_tutari', true),
            'İade satırı' => count($rows),
        ], 'İade modülü ayrı tabloyla değil, faturalar.belge_tipi alanındaki iade_satis / iade_alis kayıtlarıyla raporlanır.');
    }

    public function getOffersReport(array $filters): array
    {
        // Ayrı bir "teklifler" tablosu yok — teklif (proforma) kayıtları da diğer
        // belgeler gibi faturalar tablosunda belge_tipi='proforma' olarak tutulur
        // (bkz. TeklifController). Bu yüzden aynı kaynaktan okunur.
        [$where, $params] = $this->invoiceWhere($filters, "f.belge_tipi = 'proforma'", 'musteri');
        $rows = $this->db->select(
            "SELECT f.fatura_tarihi AS tarih, f.fatura_no AS teklif_no,
                    COALESCE(c.unvan, 'Cari yok') AS musteri,
                    f.genel_toplam AS toplam_tutar, f.durum,
                    f.vade_tarihi AS gecerlilik_tarihi, f.aciklama
             FROM faturalar f
             LEFT JOIN cariler c ON c.id = f.cari_id
             WHERE {$where}
             ORDER BY f.fatura_tarihi DESC, f.id DESC",
            $params
        );
        foreach ($rows as &$row) {
            // Bir proformanın kesin satışa dönüşüp dönüşmediğini izleyen bir bağlantı
            // (fatura_id/kaynak_teklif_id vb.) şu anda şemada yok; yanıltıcı bir tahmin
            // yapmak yerine bunu açıkça belirtiyoruz.
            $row['satisa_donustu'] = 'İzlenmiyor';
        }
        unset($row);

        return $this->withSummary($rows, [
            'Toplam teklif tutarı' => $this->sum($rows, 'toplam_tutar', true),
            'Teklif sayısı' => count($rows),
        ], 'Teklif (proforma) kayıtları faturalar tablosundaki belge_tipi=\'proforma\' satırlarından üretilir. Bir teklifin nihai satışa dönüşüp dönüşmediğine dair bağlantı şemada tutulmadığı için "Satışa dönüştü mü?" bilgisi izlenmemektedir.');
    }

    public function getWaybillReport(array $filters): array
    {
        // "İrsaliye Kaydet" ile oluşturulan belgeler de ayrı bir tabloda değil,
        // faturalar tablosunda belge_tipi='irsaliye' olarak tutulur (sevk_turu:
        // 'musteri' → müşteriye sevk, stok o an çıkar; 'depolar_arasi' → kaynak
        // depodan hedef depoya transfer). Bkz. Fatura::stokHareketPlani().
        [$where, $params] = $this->invoiceWhere($filters, "f.belge_tipi = 'irsaliye'", 'musteri');
        $rows = $this->db->select(
            "SELECT f.fatura_tarihi AS tarih, f.fatura_no AS irsaliye_no,
                    CASE WHEN f.sevk_turu = 'depolar_arasi' THEN 'Depolar Arası' ELSE 'Müşteriye Sevk' END AS sevk_turu,
                    CASE WHEN f.sevk_turu = 'depolar_arasi'
                         THEN CONCAT(COALESCE(dk.ad, '?'), ' → ', COALESCE(dh.ad, '?'))
                         ELSE COALESCE(c.unvan, 'Cari yok') END AS musteri,
                    f.genel_toplam AS toplam_tutar, f.durum,
                    CASE WHEN f.sevk_turu = 'depolar_arasi' THEN '—'
                         WHEN f.irsaliye_kullanildi = 1 THEN 'Evet' ELSE 'Hayır' END AS faturalandi_mi,
                    f.aciklama
             FROM faturalar f
             LEFT JOIN cariler c ON c.id = f.cari_id
             LEFT JOIN depolar dk ON dk.id = f.depo_id
             LEFT JOIN depolar dh ON dh.id = f.hedef_depo_id
             WHERE {$where}
             ORDER BY f.fatura_tarihi DESC, f.id DESC",
            $params
        );

        return $this->withSummary($rows, [
            'Toplam irsaliye tutarı' => $this->sum($rows, 'toplam_tutar', true),
            'İrsaliye sayısı' => count($rows),
        ], 'Müşteriye sevk irsaliyesi onaylandığı an malı depodan düşürür; cari borcu/geliri henüz oluşturmaz — bu, "Faturalandır" ile o irsaliyeden doldurulan satış faturası kesildiğinde oluşur (o faturada stok ikinci kez düşürülmez). Depolar arası sevk irsaliyesi ise yalnızca kaynak/hedef depo stoğunu değiştirir; müşteri veya fatura bağlantısı yoktur.');
    }

    public function getSixMonthSalesReport(array $filters): array
    {
        $from = !empty($filters['start_date']) ? $filters['start_date'] : date('Y-m-01', strtotime('-5 months'));
        $to = !empty($filters['end_date']) ? $filters['end_date'] : date('Y-m-t');
        $rows = $this->db->select(
            "SELECT DATE_FORMAT(f.fatura_tarihi, '%Y-%m') AS ay,
                    COUNT(*) AS fatura_sayisi,
                    COALESCE(SUM(f.genel_toplam), 0) AS toplam_satis,
                    COALESCE(SUM(COALESCE(f.odenen_tutar, 0)), 0) AS toplam_tahsilat,
                    COALESCE(SUM(GREATEST(f.genel_toplam - COALESCE(f.odenen_tutar, 0), 0)), 0) AS bekleyen_tahsilat,
                    CASE WHEN COUNT(*) > 0 THEN SUM(f.genel_toplam) / COUNT(*) ELSE 0 END AS ort_fatura_tutari
             FROM faturalar f
             WHERE f.silindi_mi = 0 AND f.durum <> 'iptal' AND f.belge_tipi IN ('satis','perakende')
               AND f.company_id = :tenant_company_id AND f.period_id = :tenant_period_id
               AND f.fatura_tarihi BETWEEN :from AND :to
             GROUP BY DATE_FORMAT(f.fatura_tarihi, '%Y-%m')
             ORDER BY ay",
            [
                ':from' => $from,
                ':to' => $to,
                ':tenant_company_id' => TenantContext::activeCompanyId(),
                ':tenant_period_id' => TenantContext::activePeriodId(),
            ]
        );

        return $this->withSummary($rows, [
            'Toplam satış' => $this->sum($rows, 'toplam_satis', true),
            'Toplam tahsilat' => $this->sum($rows, 'toplam_tahsilat', true),
            'Bekleyen tahsilat' => $this->sum($rows, 'bekleyen_tahsilat', true),
            'Ay sayısı' => count($rows),
        ]);
    }

    public function getStockSalesCoverageReport(array $filters): array
    {
        [$dateSql, $params] = $this->dateWhere($filters, 'f.fatura_tarihi');
        if ($dateSql === '') {
            $dateSql = " AND f.fatura_tarihi >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)";
        }
        $params[':tenant_company_id'] = TenantContext::activeCompanyId();
        $params[':tenant_period_id'] = TenantContext::activePeriodId();
        $extra = ['u.company_id = :tenant_company_id', 'u.silindi_mi = 0'];
        if (!empty($filters['product_id'])) {
            $extra[] = 'u.id = :product_id';
            $params[':product_id'] = (int)$filters['product_id'];
        }
        if (!empty($filters['category'])) {
            $extra[] = 'u.kategori = :category';
            $params[':category'] = $filters['category'];
        }
        if (($filters['stock_filter'] ?? '') === 'critical') {
            $extra[] = 'u.stok_miktari <= u.kritik_stok';
        } elseif (($filters['stock_filter'] ?? '') === 'out_of_stock') {
            $extra[] = 'u.stok_miktari <= 0';
        }
        $where = $extra ? 'WHERE ' . implode(' AND ', $extra) : '';
        $rows = $this->db->select(
            "SELECT u.ad AS urun_adi, u.stok_miktari AS mevcut_stok,
                    COALESCE(s30.qty, 0) AS son_30_gun_satis,
                    COALESCE(s90.qty, 0) AS son_90_gun_satis,
                    COALESCE(s90.qty, 0) / 3 AS ort_aylik_satis,
                    CASE
                        WHEN u.stok_miktari <= 0 THEN 'Stok Yok'
                        WHEN COALESCE(s90.qty, 0) <= 0 THEN 'Satış verisi yok'
                        ELSE CAST(ROUND(u.stok_miktari / (COALESCE(s90.qty, 0) / 3), 2) AS CHAR)
                    END AS stok_kac_ay_yeter,
                    CASE
                        WHEN u.stok_miktari <= 0 THEN 'Stok Yok'
                        WHEN u.stok_miktari <= u.kritik_stok THEN 'Kritik stok'
                        WHEN COALESCE(s90.qty, 0) > 0 AND u.stok_miktari / (COALESCE(s90.qty, 0) / 3) < 1 THEN '1 aydan az'
                        ELSE 'Normal'
                    END AS kritik_stok_uyarisi
             FROM urunler_hizmetler u
             LEFT JOIN (
                SELECT fk.urun_id, SUM(fk.miktar) AS qty
                FROM fatura_kalemleri fk JOIN faturalar f ON f.id = fk.fatura_id
                WHERE fk.silindi_mi = 0 AND f.silindi_mi = 0
                  AND f.company_id = :tenant_company_id AND f.period_id = :tenant_period_id
                  AND f.durum <> 'iptal' AND f.belge_tipi IN ('satis','perakende') AND f.fatura_tarihi >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY fk.urun_id
             ) s30 ON s30.urun_id = u.id
             LEFT JOIN (
                SELECT fk.urun_id, SUM(fk.miktar) AS qty
                FROM fatura_kalemleri fk JOIN faturalar f ON f.id = fk.fatura_id
                WHERE fk.silindi_mi = 0 AND f.silindi_mi = 0
                  AND f.company_id = :tenant_company_id AND f.period_id = :tenant_period_id
                  AND f.durum <> 'iptal' AND f.belge_tipi IN ('satis','perakende') {$dateSql}
                GROUP BY fk.urun_id
             ) s90 ON s90.urun_id = u.id
             {$where}
             ORDER BY son_90_gun_satis DESC, u.ad",
            $params
        );

        return $this->withSummary($rows, [
            'Ürün sayısı' => count($rows),
            'Stok yok' => count(array_filter($rows, fn($r) => (float)$r['mevcut_stok'] <= 0)),
            'Kritik stok' => count(array_filter($rows, fn($r) => $r['kritik_stok_uyarisi'] === 'Kritik stok')),
        ]);
    }

    public function getStockSalesCoverageReportFull(array $filters): array
    {
        $days = 180;
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $days = max(1, (int)ceil((strtotime($filters['end_date']) - strtotime($filters['start_date'])) / 86400) + 1);
        }
        [$dateSql, $params] = $this->dateWhere($filters, 'f.fatura_tarihi');
        if ($dateSql === '') {
            $dateSql = " AND f.fatura_tarihi >= DATE_SUB(CURDATE(), INTERVAL {$days} DAY)";
        }
        $params[':tenant_company_id'] = TenantContext::activeCompanyId();
        $extra = ['u.company_id = :tenant_company_id', 'u.silindi_mi = 0'];
        if (!empty($filters['product_id'])) {
            $extra[] = 'u.id = :product_id';
            $params[':product_id'] = (int)$filters['product_id'];
        }
        if (!empty($filters['category'])) {
            $extra[] = 'u.kategori = :category';
            $params[':category'] = $filters['category'];
        }
        if (($filters['stock_filter'] ?? '') === 'critical') {
            $extra[] = 'COALESCE(st.hareket_stok, u.stok_miktari, 0) <= COALESCE(u.kritik_stok, 0)';
        } elseif (($filters['stock_filter'] ?? '') === 'out_of_stock') {
            $extra[] = 'COALESCE(st.hareket_stok, u.stok_miktari, 0) <= 0';
        } elseif (($filters['stock_filter'] ?? '') === 'in_stock') {
            $extra[] = 'COALESCE(st.hareket_stok, u.stok_miktari, 0) > 0';
        }
        $where = $extra ? 'WHERE ' . implode(' AND ', $extra) : '';

        $rows = $this->db->select(
            "SELECT u.ad AS urun_adi, COALESCE(u.kategori, '-') AS kategori,
                    COALESCE(st.hareket_stok, u.stok_miktari, 0) AS mevcut_stok,
                    COALESCE(s30.qty, 0) AS son_30_gun_satis,
                    COALESCE(s90.qty, 0) AS son_90_gun_satis,
                    COALESCE(s180.qty, 0) AS son_180_gun_satis,
                    COALESCE(sx.qty, 0) / GREATEST({$days} / 30, 1) AS ort_aylik_satis,
                    COALESCE(sx.qty, 0) / {$days} AS gunluk_ortalama_satis,
                    CASE
                        WHEN COALESCE(st.hareket_stok, u.stok_miktari, 0) <= 0 THEN 'Stok yok'
                        WHEN COALESCE(sx.qty, 0) <= 0 THEN 'Satış verisi yok'
                        ELSE CAST(ROUND(COALESCE(st.hareket_stok, u.stok_miktari, 0) / (COALESCE(sx.qty, 0) / {$days}), 1) AS CHAR)
                    END AS stok_kac_gun_yeter,
                    CASE
                        WHEN COALESCE(st.hareket_stok, u.stok_miktari, 0) <= 0 THEN 'Stok yok'
                        WHEN COALESCE(sx.qty, 0) <= 0 THEN 'Satış verisi yok'
                        ELSE CAST(ROUND(COALESCE(st.hareket_stok, u.stok_miktari, 0) / (COALESCE(sx.qty, 0) / GREATEST({$days} / 30, 1)), 2) AS CHAR)
                    END AS stok_kac_ay_yeter,
                    CASE
                        WHEN COALESCE(st.hareket_stok, u.stok_miktari, 0) <= 0 THEN 'Stok yok'
                        WHEN COALESCE(sx.qty, 0) <= 0 THEN 'Satış verisi yok'
                        WHEN COALESCE(st.hareket_stok, u.stok_miktari, 0) / (COALESCE(sx.qty, 0) / {$days}) < 7 THEN 'Kritik'
                        WHEN COALESCE(st.hareket_stok, u.stok_miktari, 0) / (COALESCE(sx.qty, 0) / {$days}) < 30 THEN 'Azalıyor'
                        WHEN COALESCE(st.hareket_stok, u.stok_miktari, 0) / (COALESCE(sx.qty, 0) / {$days}) > 180 THEN 'Fazla stok'
                        ELSE 'Normal'
                    END AS risk_durumu
             FROM urunler_hizmetler u
             LEFT JOIN (" . $this->stockBalanceSql() . ") st ON st.urun_id = u.id
             LEFT JOIN (" . $this->salesQtySql("AND f.fatura_tarihi >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)") . ") s30 ON s30.urun_id = u.id
             LEFT JOIN (" . $this->salesQtySql("AND f.fatura_tarihi >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)") . ") s90 ON s90.urun_id = u.id
             LEFT JOIN (" . $this->salesQtySql("AND f.fatura_tarihi >= DATE_SUB(CURDATE(), INTERVAL 180 DAY)") . ") s180 ON s180.urun_id = u.id
             LEFT JOIN (" . $this->salesQtySql($dateSql) . ") sx ON sx.urun_id = u.id
             {$where}
             ORDER BY son_90_gun_satis DESC, u.ad",
            $params
        );

        return $this->withSummary($rows, [
            'Ürün sayısı' => count($rows),
            'Stok yok' => count(array_filter($rows, fn($r) => (float)$r['mevcut_stok'] <= 0)),
            'Kritik stok' => count(array_filter($rows, fn($r) => $r['risk_durumu'] === 'Kritik')),
            'Fazla stok' => count(array_filter($rows, fn($r) => $r['risk_durumu'] === 'Fazla stok')),
        ], 'Satış hızı, satış/perakende fatura kalemlerinden; mevcut stok alış/satış/iade kalemleri ve manuel hareketlerden hesaplanır.');
    }

    public function getInactiveProductsReport(array $filters): array
    {
        $days = (int)($filters['inactive_days'] ?? 90);
        if ($days <= 0) $days = 90;
        $params = [
            ':days' => $days,
            ':tenant_company_id' => TenantContext::activeCompanyId(),
            ':tenant_period_id' => TenantContext::activePeriodId(),
        ];
        $where = ["u.silindi_mi = 0", "u.tip = 'urun'", "u.company_id = :tenant_company_id"];
        if (!empty($filters['product_id'])) {
            $where[] = 'u.id = :product_id';
            $params[':product_id'] = (int)$filters['product_id'];
        }
        if (!empty($filters['category'])) {
            $where[] = 'u.kategori = :category';
            $params[':category'] = $filters['category'];
        }
        if (($filters['stock_filter'] ?? '') === 'in_stock') {
            $where[] = 'COALESCE(st.hareket_stok, u.stok_miktari, 0) > 0';
        } elseif (($filters['stock_filter'] ?? '') === 'out_of_stock') {
            $where[] = 'COALESCE(st.hareket_stok, u.stok_miktari, 0) <= 0';
        }

        $manualJoin = $this->tableExists('stok_hareketleri')
            ? "LEFT JOIN (SELECT urun_id, MAX(DATE(olusturulma_tarihi)) AS son_stok_hareket_tarihi FROM stok_hareketleri WHERE company_id = :tenant_company_id AND period_id = :tenant_period_id GROUP BY urun_id) sh ON sh.urun_id = u.id"
            : "LEFT JOIN (SELECT NULL AS urun_id, NULL AS son_stok_hareket_tarihi) sh ON 1=0";

        $rows = $this->db->select(
            "SELECT * FROM (
                SELECT u.ad AS urun_adi, u.stok_kodu, COALESCE(u.kategori, '-') AS kategori,
                       COALESCE(st.hareket_stok, u.stok_miktari, 0) AS mevcut_stok,
                       COALESCE(st.hareket_stok, u.stok_miktari, 0) * COALESCE(u.alis_fiyati, 0) AS stok_degeri,
                       p.son_alis_tarihi, s.son_satis_tarihi, sh.son_stok_hareket_tarihi,
                       GREATEST(COALESCE(p.son_alis_tarihi, '1000-01-01'), COALESCE(s.son_satis_tarihi, '1000-01-01'), COALESCE(sh.son_stok_hareket_tarihi, '1000-01-01')) AS son_hareket_tarihi,
                       CASE WHEN GREATEST(COALESCE(p.son_alis_tarihi, '1000-01-01'), COALESCE(s.son_satis_tarihi, '1000-01-01'), COALESCE(sh.son_stok_hareket_tarihi, '1000-01-01')) = '1000-01-01'
                            THEN NULL
                            ELSE DATEDIFF(CURDATE(), GREATEST(COALESCE(p.son_alis_tarihi, '1000-01-01'), COALESCE(s.son_satis_tarihi, '1000-01-01'), COALESCE(sh.son_stok_hareket_tarihi, '1000-01-01')))
                       END AS hareketsiz_gun_sayisi,
                       CASE WHEN s.son_satis_tarihi IS NULL THEN 'Hiç satılmamış' WHEN p.son_alis_tarihi IS NULL THEN 'Hiç alış görmemiş' ELSE 'Hareketsiz' END AS durum
                FROM urunler_hizmetler u
                LEFT JOIN (" . $this->stockBalanceSql() . ") st ON st.urun_id = u.id
                LEFT JOIN (SELECT fk.urun_id, MAX(f.fatura_tarihi) AS son_alis_tarihi FROM fatura_kalemleri fk JOIN faturalar f ON f.id = fk.fatura_id WHERE fk.silindi_mi = 0 AND f.silindi_mi = 0 AND f.company_id = :tenant_company_id AND f.period_id = :tenant_period_id AND f.durum NOT IN ('iptal','taslak') AND f.belge_tipi = 'alis' GROUP BY fk.urun_id) p ON p.urun_id = u.id
                LEFT JOIN (SELECT fk.urun_id, MAX(f.fatura_tarihi) AS son_satis_tarihi FROM fatura_kalemleri fk JOIN faturalar f ON f.id = fk.fatura_id WHERE fk.silindi_mi = 0 AND f.silindi_mi = 0 AND f.company_id = :tenant_company_id AND f.period_id = :tenant_period_id AND f.durum NOT IN ('iptal','taslak') AND f.belge_tipi IN ('satis','perakende') GROUP BY fk.urun_id) s ON s.urun_id = u.id
                {$manualJoin}
                WHERE " . implode(' AND ', $where) . "
             ) x
             WHERE son_hareket_tarihi = '1000-01-01' OR hareketsiz_gun_sayisi >= :days
             ORDER BY hareketsiz_gun_sayisi DESC, stok_degeri DESC",
            $params
        );
        foreach ($rows as &$row) {
            if ($row['son_hareket_tarihi'] === '1000-01-01') {
                $row['son_hareket_tarihi'] = 'Hiç hareket yok';
                $row['hareketsiz_gun_sayisi'] = 'Hiç hareket yok';
            }
        }
        unset($row);
        return $this->withSummary($rows, [
            'Hareketsiz ürün sayısı' => count($rows),
            'Hareketsiz stok değeri' => $this->sum($rows, 'stok_degeri', true),
            'Hiç satılmamış ürün' => count(array_filter($rows, fn($r) => $r['son_satis_tarihi'] === null)),
            'Hiç alış görmemiş ürün' => count(array_filter($rows, fn($r) => $r['son_alis_tarihi'] === null)),
            '180+ gün hareketsiz' => count(array_filter($rows, fn($r) => is_numeric($r['hareketsiz_gun_sayisi']) && (int)$r['hareketsiz_gun_sayisi'] >= 180)),
        ]);
    }

    public function getCategorySalesReport(array $filters): array
    {
        [$dateSql, $params] = $this->dateWhere($filters, 'f.fatura_tarihi');
        $params[':tenant_company_id'] = TenantContext::activeCompanyId();
        $params[':tenant_period_id'] = TenantContext::activePeriodId();
        $extra = [];
        if (!empty($filters['category'])) {
            $extra[] = "COALESCE(u.kategori, 'Kategorisiz') = :category";
            $params[':category'] = $filters['category'];
        }
        if (!empty($filters['product_id'])) {
            $extra[] = 'u.id = :product_id';
            $params[':product_id'] = (int)$filters['product_id'];
        }
        $whereExtra = $extra ? ' AND ' . implode(' AND ', $extra) : '';
        $rows = $this->db->select(
            "SELECT COALESCE(u.kategori, 'Kategorisiz') AS kategori_adi,
                    COUNT(DISTINCT fk.urun_id) AS satilan_urun_cesidi,
                    SUM(fk.miktar) AS toplam_satis_miktari,
                    SUM(fk.toplam) AS toplam_satis_tutari,
                    SUM(fk.kdv_tutari) AS toplam_kdv,
                    CASE WHEN SUM(fk.miktar) > 0 THEN SUM(fk.toplam) / SUM(fk.miktar) ELSE 0 END AS ort_satis_fiyati,
                    SUM(fk.toplam - (fk.miktar * COALESCE(u.alis_fiyati, 0))) AS toplam_brut_kar,
                    0 AS toplam_satis_payi
             FROM fatura_kalemleri fk
             JOIN faturalar f ON f.id = fk.fatura_id
             LEFT JOIN urunler_hizmetler u ON u.id = fk.urun_id
             WHERE fk.silindi_mi = 0 AND f.silindi_mi = 0
               AND f.company_id = :tenant_company_id AND f.period_id = :tenant_period_id
               AND f.durum <> 'iptal' AND f.belge_tipi IN ('satis','perakende') {$dateSql} {$whereExtra}
             GROUP BY COALESCE(u.kategori, 'Kategorisiz')
             ORDER BY toplam_satis_tutari DESC",
            $params
        );
        $total = $this->sum($rows, 'toplam_satis_tutari');
        foreach ($rows as &$row) {
            $row['toplam_satis_payi'] = $total > 0 ? round(((float)$row['toplam_satis_tutari'] / $total) * 100, 2) : 0;
        }

        return $this->withSummary($rows, [
            'En çok satış yapılan kategori' => $rows[0]['kategori_adi'] ?? '-',
            'En yüksek ciro yapan kategori' => $rows[0]['kategori_adi'] ?? '-',
            'En düşük performanslı kategori' => $rows ? end($rows)['kategori_adi'] : '-',
            'Toplam satış' => $this->money($total),
        ]);
    }

    public function calculateCurrentStock(int $productId, ?int $warehouseId = null): float
    {
        $sql = "SELECT COALESCE(SUM(net_miktar), 0) AS stok FROM (" . $this->stockMovementsSql() . ") sm WHERE urun_id = :product_id";
        $params = [':product_id' => $productId];
        if ($warehouseId !== null) {
            $sql .= " AND depo_id = :warehouse_id";
            $params[':warehouse_id'] = $warehouseId;
        }
        $row = $this->db->selectOne($sql, $params);
        return (float)($row['stok'] ?? 0);
    }

    public function calculateProductSalesVelocity(int $productId, array $dateRange = []): array
    {
        $filters = [
            'product_id' => $productId,
            'start_date' => $dateRange['start_date'] ?? '',
            'end_date' => $dateRange['end_date'] ?? '',
        ];
        [$dateSql, $params] = $this->dateWhere($filters, 'f.fatura_tarihi');
        $params[':product_id'] = $productId;
        $params[':tenant_company_id'] = TenantContext::activeCompanyId();
        $params[':tenant_period_id'] = TenantContext::activePeriodId();
        $row = $this->db->selectOne(
            "SELECT COALESCE(SUM(fk.miktar), 0) AS satis_miktari
             FROM fatura_kalemleri fk
             JOIN faturalar f ON f.id = fk.fatura_id
             WHERE fk.silindi_mi = 0 AND f.silindi_mi = 0
               AND f.company_id = :tenant_company_id AND f.period_id = :tenant_period_id
               AND f.durum NOT IN ('iptal','taslak')
               AND f.belge_tipi IN ('satis','perakende') AND fk.urun_id = :product_id {$dateSql}",
            $params
        );
        return ['satis_miktari' => (float)($row['satis_miktari'] ?? 0)];
    }

    public function getProductPurchaseSalesSummary(array $filters): array
    {
        return $this->getProductPurchaseSalesReport($filters);
    }

    private function stockBalanceSql(): string
    {
        return "SELECT urun_id, SUM(net_miktar) AS hareket_stok
                FROM (" . $this->stockMovementsSql() . ") hareketler
                GROUP BY urun_id";
    }

    private function stockMovementsSql(): string
    {
        $companyId = (int)TenantContext::activeCompanyId();
        $periodId = (int)TenantContext::activePeriodId();
        $invoiceFallbackSql = "SELECT fk.urun_id, NULL AS depo_id, f.fatura_tarihi AS hareket_tarihi,
                    CASE WHEN f.belge_tipi IN ('alis','iade_satis') THEN 'giris' ELSE 'cikis' END AS hareket_tipi,
                    CASE
                        WHEN f.belge_tipi = 'alis' THEN 'alis_faturasi'
                        WHEN f.belge_tipi IN ('satis','perakende') THEN 'satis_faturasi'
                        WHEN f.belge_tipi = 'iade_satis' THEN 'satis_iadesi'
                        WHEN f.belge_tipi = 'iade_alis' THEN 'alis_iadesi'
                        ELSE f.belge_tipi
                    END AS kaynak_turu,
                    f.id AS kaynak_id,
                    f.fatura_no AS kaynak_belge_no,
                    u.ad AS urun_adi, u.stok_kodu, COALESCE(u.kategori, '-') AS kategori,
                    '-' AS depo_adi,
                    CASE WHEN f.belge_tipi IN ('alis','iade_satis') THEN fk.miktar ELSE 0 END AS giris_miktari,
                    CASE WHEN f.belge_tipi IN ('satis','perakende','iade_alis') THEN fk.miktar ELSE 0 END AS cikis_miktari,
                    CASE WHEN f.belge_tipi IN ('alis','iade_satis') THEN fk.miktar ELSE -fk.miktar END AS net_miktar,
                    fk.birim, fk.birim_fiyat AS birim_maliyet, fk.toplam AS toplam_tutar,
                    '-' AS kullanici, COALESCE(f.aciklama, fk.aciklama, '') AS aciklama
             FROM fatura_kalemleri fk
             JOIN faturalar f ON f.id = fk.fatura_id
             LEFT JOIN urunler_hizmetler u ON u.id = fk.urun_id
              WHERE fk.silindi_mi = 0 AND f.silindi_mi = 0 AND f.durum NOT IN ('iptal','taslak')
                AND f.company_id = {$companyId} AND f.period_id = {$periodId}
                AND f.belge_tipi IN ('alis','satis','perakende','iade_satis','iade_alis')
                AND fk.urun_id IS NOT NULL";

        $parts = [];

        if ($this->tableExists('stok_hareketleri')) {
            $parts[] = "SELECT sh.urun_id, sh.depo_id, DATE(sh.olusturulma_tarihi) AS hareket_tarihi,
                    sh.islem_tipi AS hareket_tipi, COALESCE(sh.hareket_tipi, 'manuel') AS kaynak_turu,
                    sh.id AS kaynak_id, CONCAT('SH-', sh.id) AS kaynak_belge_no,
                    u.ad AS urun_adi, u.stok_kodu, COALESCE(u.kategori, '-') AS kategori,
                    COALESCE(d.ad, '-') AS depo_adi,
                    CASE WHEN sh.islem_tipi = 'giris' THEN sh.miktar ELSE 0 END AS giris_miktari,
                    CASE WHEN sh.islem_tipi = 'cikis' THEN sh.miktar ELSE 0 END AS cikis_miktari,
                    CASE WHEN sh.islem_tipi = 'giris' THEN sh.miktar ELSE -sh.miktar END AS net_miktar,
                    u.birim, u.alis_fiyati AS birim_maliyet, sh.miktar * COALESCE(u.alis_fiyati, 0) AS toplam_tutar,
                    '-' AS kullanici, COALESCE(sh.aciklama, '') AS aciklama
             FROM stok_hareketleri sh
             LEFT JOIN urunler_hizmetler u ON u.id = sh.urun_id
             LEFT JOIN depolar d ON d.id = sh.depo_id
             WHERE sh.company_id = {$companyId} AND sh.period_id = {$periodId}";
            $parts[] = $invoiceFallbackSql . "
               AND NOT EXISTS (
                    SELECT 1
                    FROM stok_hareketleri shx
                    WHERE shx.company_id = f.company_id
                      AND shx.period_id = f.period_id
                      AND shx.urun_id = fk.urun_id
                      AND shx.islem_tipi = CASE WHEN f.belge_tipi IN ('alis','iade_satis') THEN 'giris' ELSE 'cikis' END
                      AND ABS(shx.miktar - fk.miktar) < 0.001
                      AND shx.aciklama LIKE CONCAT('%', f.fatura_no, '%')
               )";
        } else {
            $parts[] = $invoiceFallbackSql;
        }

        return implode(' UNION ALL ', $parts);
    }

    private function warehouseStockSql(): string
    {
        if (!$this->tableExists('stok_hareketleri')) {
            return "SELECT NULL AS urun_id, NULL AS depo_id, 0 AS mevcut_stok, NULL AS son_hareket_tarihi";
        }
        $companyId = (int)TenantContext::activeCompanyId();
        $periodId = (int)TenantContext::activePeriodId();
        return "SELECT urun_id, depo_id,
                       SUM(CASE WHEN islem_tipi = 'giris' THEN miktar ELSE -miktar END) AS mevcut_stok,
                       MAX(DATE(olusturulma_tarihi)) AS son_hareket_tarihi
                FROM stok_hareketleri
                WHERE depo_id IS NOT NULL AND company_id = {$companyId} AND period_id = {$periodId}
                GROUP BY urun_id, depo_id";
    }

    private function salesQtySql(string $dateSql): string
    {
        $companyId = (int)TenantContext::activeCompanyId();
        $periodId = (int)TenantContext::activePeriodId();
        return "SELECT fk.urun_id, SUM(fk.miktar) AS qty
                FROM fatura_kalemleri fk
                JOIN faturalar f ON f.id = fk.fatura_id
                WHERE fk.silindi_mi = 0 AND f.silindi_mi = 0 AND f.durum NOT IN ('iptal','taslak')
                  AND f.company_id = {$companyId} AND f.period_id = {$periodId}
                  AND f.belge_tipi IN ('satis','perakende') {$dateSql}
                GROUP BY fk.urun_id";
    }

    private function topBy(array $rows, string $labelKey, callable $valueFn): string
    {
        $totals = [];
        foreach ($rows as $row) {
            $label = (string)($row[$labelKey] ?? '-');
            $totals[$label] = ($totals[$label] ?? 0) + (float)$valueFn($row);
        }
        arsort($totals);
        return array_key_first($totals) ?: '';
    }

    private function invoiceWhere(array $filters, string $typeSql, string $cariType = '', bool $includeCanceled = false): array
    {
        $conds = ['f.silindi_mi = 0', $typeSql];
        $conds[] = 'f.company_id = :tenant_company_id';
        $conds[] = 'f.period_id = :tenant_period_id';
        $params = [
            ':tenant_company_id' => TenantContext::activeCompanyId(),
            ':tenant_period_id' => TenantContext::activePeriodId(),
        ];
        if (!$includeCanceled) {
            $conds[] = "f.durum <> 'iptal'";
        }
        [$dateSql, $dateParams] = $this->dateWhere($filters, 'f.fatura_tarihi');
        if ($dateSql) {
            $conds[] = ltrim($dateSql, ' AND');
            $params += $dateParams;
        }
        $customerId = $filters['customer_id'] ?? null;
        $supplierId = $filters['supplier_id'] ?? null;
        $cariId = match ($cariType) {
            'musteri' => $customerId,
            'tedarikci' => $supplierId,
            default => $customerId ?: $supplierId,
        };

        if ($cariId !== null && $cariId !== '') {
            $conds[] = 'f.cari_id = :cari_id';
            $params[':cari_id'] = (int)$cariId;
        }
        if (!empty($filters['status'])) {
            $conds[] = 'f.durum = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['invoice_no'])) {
            $conds[] = 'f.fatura_no LIKE :invoice_no';
            $params[':invoice_no'] = '%' . $filters['invoice_no'] . '%';
        }
        if (($filters['min_amount'] ?? '') !== '') {
            $conds[] = 'f.genel_toplam >= :min_amount';
            $params[':min_amount'] = (float)$filters['min_amount'];
        }
        if (($filters['max_amount'] ?? '') !== '') {
            $conds[] = 'f.genel_toplam <= :max_amount';
            $params[':max_amount'] = (float)$filters['max_amount'];
        }
        if ($cariType) {
            $conds[] = "(c.tip = :cari_tip OR c.tip = 'her_ikisi' OR c.id IS NULL)";
            $params[':cari_tip'] = $cariType;
        }
        if (!empty($filters['payment_status'])) {
            if ($filters['payment_status'] === 'paid') {
                $conds[] = "f.genel_toplam <= COALESCE(f.odenen_tutar, 0) + 0.005";
            } elseif ($filters['payment_status'] === 'unpaid') {
                $conds[] = "COALESCE(f.odenen_tutar, 0) <= 0";
            } elseif ($filters['payment_status'] === 'partial') {
                $conds[] = "COALESCE(f.odenen_tutar, 0) > 0 AND COALESCE(f.odenen_tutar, 0) + 0.005 < f.genel_toplam";
            }
        }

        return [implode(' AND ', $conds), $params];
    }

    private function dateWhere(array $filters, string $field): array
    {
        $period = $filters['period'] ?? '';
        $params = [];
        if ($period === 'today') {
            return [" AND {$field} = CURDATE()", []];
        }
        if ($period === 'yesterday') {
            return [" AND {$field} = DATE_SUB(CURDATE(), INTERVAL 1 DAY)", []];
        }
        if ($period === 'last_7') {
            return [" AND {$field} >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)", []];
        }
        if ($period === 'last_30') {
            return [" AND {$field} >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)", []];
        }
        if ($period === 'this_month') {
            return [" AND YEAR({$field}) = YEAR(CURDATE()) AND MONTH({$field}) = MONTH(CURDATE())", []];
        }
        if ($period === 'last_month') {
            return [" AND {$field} >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01') AND {$field} < DATE_FORMAT(CURDATE(), '%Y-%m-01')", []];
        }
        if ($period === 'last_6') {
            return [" AND {$field} >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)", []];
        }
        $sql = '';
        if (!empty($filters['start_date'])) {
            $sql .= " AND {$field} >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sql .= " AND {$field} <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }
        return [$sql, $params];
    }

    private function dateWhereForPrefix(array $filters, string $field, string $prefix): array
    {
        [$sql, $params] = $this->dateWhere($filters, $field);
        $mapped = [];
        foreach ($params as $key => $value) {
            $newKey = ':' . $prefix . '_' . ltrim($key, ':');
            $sql = str_replace($key, $newKey, $sql);
            $mapped[$newKey] = $value;
        }
        return [$sql, $mapped];
    }

    private function customerOptionalSelect(): string
    {
        $salesPerson = "'-'";
        if ($this->columnExists('cariler', 'satis_elemani_id') && $this->tableExists('kullanicilar') && $this->columnExists('kullanicilar', 'ad_soyad')) {
            $salesPerson = "COALESCE(ku.ad_soyad, '-')";
        }

        return $salesPerson . " AS satis_elemani, "
            . ($this->columnExists('cariler', 'kaynak') ? "COALESCE(c.kaynak, '-')" : "'-'") . " AS kaynak, "
            . ($this->columnExists('cariler', 'siniflandirma1') ? "COALESCE(c.siniflandirma1, '-')" : "'-'") . " AS siniflandirma1, "
            . ($this->columnExists('cariler', 'siniflandirma2') ? "COALESCE(c.siniflandirma2, '-')" : "'-'") . " AS siniflandirma2, "
            . ($this->columnExists('cariler', 'risk_limiti') ? "COALESCE(c.risk_limiti, 0)" : "0") . " AS risk_limiti, ";
    }

    private function customerOptionalJoin(): string
    {
        if ($this->columnExists('cariler', 'satis_elemani_id') && $this->tableExists('kullanicilar') && $this->columnExists('kullanicilar', 'ad_soyad')) {
            return 'LEFT JOIN kullanicilar ku ON ku.id = c.satis_elemani_id';
        }
        return '';
    }

    private function chequeSenetSql(bool $useBalanceDate): array
    {
        if (!$this->tableExists('cek_senet_portfoyu')) {
            return [
                'sql' => 'SELECT NULL AS cari_id, 0 AS cek_senet_borcu',
                'uses_date' => false,
            ];
        }

        $dateSql = '';
        $usesDate = false;
        if ($useBalanceDate && $this->columnExists('cek_senet_portfoyu', 'vade_tarihi')) {
            $dateSql = ' AND vade_tarihi <= :balance_date_cheque';
            $usesDate = true;
        }

        return [
            'sql' => "SELECT cari_id, COALESCE(SUM(tutar), 0) AS cek_senet_borcu
                      FROM cek_senet_portfoyu
                      WHERE company_id = :tenant_company_id AND period_id = :tenant_period_id {$dateSql}
                      GROUP BY cari_id",
            'uses_date' => $usesDate,
        ];
    }

    private function customerReportMissingColumns(): array
    {
        $missing = [];
        foreach ([
            'siniflandirma1' => 'Sınıflandırma 1 kolonu',
            'siniflandirma2' => 'Sınıflandırma 2 kolonu',
            'satis_elemani_id' => 'Satış elemanı bağlantısı',
            'kaynak' => 'Kaynak kolonu',
            'risk_limiti' => 'Risk limiti kolonu',
        ] as $column => $label) {
            if (!$this->columnExists('cariler', $column)) {
                $missing[] = $label;
            }
        }
        if (!$this->tableExists('cek_senet_portfoyu')) {
            $missing[] = 'Çek/Senet tablosu';
        }
        if (!$this->tableExists('borc_alacak_fisleri')) {
            $missing[] = 'Borç/Alacak fişleri tablosu';
        }
        return $missing;
    }

    private function tableExists(string $table): bool
    {
        $row = $this->db->selectOne(
            "SELECT COUNT(*) AS n FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table",
            [':table' => $table]
        );
        return (int)($row['n'] ?? 0) > 0;
    }

    private function columnExists(string $table, string $column): bool
    {
        $row = $this->db->selectOne(
            "SELECT COUNT(*) AS n
             FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = :table AND column_name = :column",
            [':table' => $table, ':column' => $column]
        );
        return (int)($row['n'] ?? 0) > 0;
    }

    private function withSummary(array $rows, array $summary, string $note = ''): array
    {
        return ['rows' => $rows, 'summary' => $summary, 'note' => $note];
    }

    private function sum(array $rows, string $key, bool $format = false)
    {
        $total = array_sum(array_map(fn($row) => (float)($row[$key] ?? 0), $rows));
        return $format ? $this->money($total) : $total;
    }

    private function money(float $value): string
    {
        return number_format($value, 2, ',', '.') . ' TL';
    }
}
