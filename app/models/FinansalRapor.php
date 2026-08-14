<?php

class FinansalRapor
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getFilterOptions(): array
    {
        return [
            'accounts' => $this->db->select("SELECT id, tip, hesap_adi FROM kasa_banka WHERE silindi_mi = 0 AND company_id = :cid ORDER BY tip, hesap_adi", [':cid' => TenantContext::activeCompanyId()]),
            'cariler' => $this->db->select("SELECT id, tip, unvan FROM cariler WHERE silindi_mi = 0 AND company_id = :cid ORDER BY unvan", [':cid' => TenantContext::activeCompanyId()]),
            'customers' => $this->db->select("SELECT id, unvan FROM cariler WHERE silindi_mi = 0 AND company_id = :cid AND tip IN ('musteri','her_ikisi') ORDER BY unvan", [':cid' => TenantContext::activeCompanyId()]),
            'expense_categories' => $this->tableExists('masraf_kategoriler') ? $this->db->select("SELECT id, ad FROM masraf_kategoriler WHERE silindi_mi = 0 AND company_id = :cid ORDER BY ad", [':cid' => TenantContext::activeCompanyId()]) : [],
            'employees' => $this->tableExists('personeller') ? $this->db->select("SELECT id, ad FROM personeller WHERE silindi_mi = 0 AND company_id = :cid ORDER BY ad", [':cid' => TenantContext::activeCompanyId()]) : [],
            'users' => $this->userOptions(),
        ];
    }

    public function getCashBankMovements(array $filters): array
    {
        [$dateSql, $params] = $this->dateWhere($filters, 'kh.tarih');
        $where = ['kh.silindi_mi = 0', 'kh.company_id = :tenant_company_id', 'kh.period_id = :tenant_period_id'];
        $params[':tenant_company_id'] = TenantContext::activeCompanyId();
        $params[':tenant_period_id']  = TenantContext::activePeriodId();
        if ($dateSql) $where[] = $this->stripLeadingAnd($dateSql);
        if (!empty($filters['account_id'])) {
            $where[] = 'kh.kasa_id = :account_id';
            $params[':account_id'] = (int)$filters['account_id'];
        }
        if (!empty($filters['transaction_type'])) {
            $where[] = 'kh.islem_tipi = :transaction_type';
            $params[':transaction_type'] = $filters['transaction_type'];
        }
        if (!empty($filters['cari_search'])) {
            $where[] = 'c.unvan LIKE :cari_search';
            $params[':cari_search'] = '%' . $filters['cari_search'] . '%';
        }
        if (($filters['min_amount'] ?? '') !== '') {
            $where[] = 'kh.tutar >= :min_amount';
            $params[':min_amount'] = (float)$filters['min_amount'];
        }
        if (($filters['max_amount'] ?? '') !== '') {
            $where[] = 'kh.tutar <= :max_amount';
            $params[':max_amount'] = (float)$filters['max_amount'];
        }
        if (!empty($filters['description'])) {
            $where[] = 'kh.aciklama LIKE :description';
            $params[':description'] = '%' . $filters['description'] . '%';
        }

        $rows = $this->db->select(
            "SELECT kh.tarih, kh.islem_tipi,
                    CASE WHEN kb.tip = 'banka' THEN 'Banka' ELSE 'Kasa' END AS hesap_turu,
                    kb.hesap_adi,
                    CASE WHEN c.tip = 'musteri' THEN 'Müşteri' WHEN c.tip = 'tedarikci' THEN 'Tedarikçi' WHEN c.tip = 'her_ikisi' THEN 'Müşteri/Tedarikçi' ELSE 'Diğer' END AS cari_turu,
                    COALESCE(c.unvan, '-') AS cari_adi,
                    kh.aciklama,
                    CASE WHEN kh.islem_tipi = 'giris' THEN kh.tutar ELSE 0 END AS gelir_tutari,
                    CASE WHEN kh.islem_tipi = 'cikis' THEN kh.tutar ELSE 0 END AS gider_tutari,
                    0 AS bakiye,
                    kb.tip AS raw_hesap_tipi
             FROM kasa_hareketleri kh
             LEFT JOIN kasa_banka kb ON kb.id = kh.kasa_id
             LEFT JOIN cariler c ON c.id = kh.cari_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY kh.tarih ASC, kh.id ASC",
            $params
        );

        $balance = 0.0;
        foreach ($rows as &$row) {
            $balance += ((float)$row['gelir_tutari'] - (float)$row['gider_tutari']);
            $row['bakiye'] = $balance;
        }
        $rows = array_reverse($rows);

        return $this->withSummary($rows, [
            'Toplam kasa girişi' => $this->money($this->sumWhere($rows, 'gelir_tutari', 'raw_hesap_tipi', 'kasa')),
            'Toplam kasa çıkışı' => $this->money($this->sumWhere($rows, 'gider_tutari', 'raw_hesap_tipi', 'kasa')),
            'Toplam banka girişi' => $this->money($this->sumWhere($rows, 'gelir_tutari', 'raw_hesap_tipi', 'banka')),
            'Toplam banka çıkışı' => $this->money($this->sumWhere($rows, 'gider_tutari', 'raw_hesap_tipi', 'banka')),
            'Net bakiye' => $this->money($this->sum($rows, 'gelir_tutari') - $this->sum($rows, 'gider_tutari')),
            'İşlem sayısı' => count($rows),
        ]);
    }

    public function getEmployeeFinanceReport(array $filters): array
    {
        if (!$this->tableExists('personeller') || !$this->tableExists('personel_hareketleri')) {
            return $this->missing('Çalışan raporu için personel/veri yapısı bulunamadı.', 'database_personel_finans.sql dosyasındaki personeller ve personel_hareketleri tabloları uygulanmalı.');
        }

        [$dateSql, $params] = $this->dateWhere($filters, 'ph.tarih');
        $joinFilter = ($dateSql ? $dateSql : '') . ' AND ph.company_id = :tenant_company_id AND ph.period_id = :tenant_period_id';
        // Paylaşılan "Ödeme durumu" filtresi paid/partial/unpaid (İngilizce, fatura
        // ödeme durumu için) gönderir; personel_hareketleri.odeme_durumu ise
        // bekliyor/odendi/iptal (Türkçe, "kısmi" durumu yok) kullanır — birebir
        // eşleştirmek her zaman 0 satır dönerdi.
        $personelDurum = ['paid' => 'odendi', 'unpaid' => 'bekliyor'][$filters['payment_status'] ?? ''] ?? null;
        if ($personelDurum !== null) {
            $joinFilter .= ' AND ph.odeme_durumu = :payment_status';
            $params[':payment_status'] = $personelDurum;
        }

        $where = ['p.silindi_mi = 0', 'p.company_id = :tenant_company_id'];
        $params[':tenant_company_id'] = TenantContext::activeCompanyId();
        $params[':tenant_period_id']  = TenantContext::activePeriodId();
        if (!empty($filters['employee_id'])) {
            $where[] = 'p.id = :employee_id';
            $params[':employee_id'] = (int)$filters['employee_id'];
        }
        if (!empty($filters['employee_search'])) {
            $where[] = '(p.ad LIKE :employee_search OR p.gorev LIKE :employee_search OR p.departman LIKE :employee_search)';
            $params[':employee_search'] = '%' . $filters['employee_search'] . '%';
        }

        $rows = $this->db->select(
            "SELECT p.ad AS calisan_adi,
                    COALESCE(p.gorev, '-') AS gorev,
                    p.maas,
                    COALESCE(SUM(CASE WHEN ph.tip IN ('prim','ek_odeme') AND ph.odeme_durumu <> 'iptal' THEN ph.tutar ELSE 0 END), 0) AS prim_ek_odeme,
                    COALESCE(SUM(CASE WHEN ph.tip IN ('sgk','sigorta','yemek','yol') AND ph.odeme_durumu <> 'iptal' THEN ph.tutar ELSE 0 END), 0) AS yan_giderler,
                    COALESCE(SUM(CASE WHEN ph.tip = 'avans' AND ph.odeme_durumu <> 'iptal' THEN ph.tutar ELSE 0 END), 0) AS avans,
                    COALESCE(SUM(CASE WHEN ph.tip = 'kesinti' AND ph.odeme_durumu <> 'iptal' THEN ph.tutar ELSE 0 END), 0) AS kesinti,
                    COALESCE(SUM(CASE WHEN ph.tip IN ('maas','sgk','sigorta','yemek','yol','prim','ek_odeme','odeme') AND ph.odeme_durumu = 'odendi' THEN ph.tutar ELSE 0 END), 0) AS toplam_odeme,
                    (p.maas
                        + COALESCE(SUM(CASE WHEN ph.tip IN ('sgk','sigorta','yemek','yol','prim','ek_odeme') AND ph.odeme_durumu <> 'iptal' THEN ph.tutar ELSE 0 END), 0)
                        - COALESCE(SUM(CASE WHEN ph.tip IN ('avans','kesinti') AND ph.odeme_durumu <> 'iptal' THEN ph.tutar ELSE 0 END), 0)
                        - COALESCE(SUM(CASE WHEN ph.tip IN ('maas','sgk','sigorta','yemek','yol','prim','ek_odeme','odeme') AND ph.odeme_durumu = 'odendi' THEN ph.tutar ELSE 0 END), 0)
                    ) AS kalan_borc_alacak,
                    MAX(CASE WHEN ph.odeme_durumu = 'odendi' THEN ph.tarih ELSE NULL END) AS son_odeme_tarihi
             FROM personeller p
             LEFT JOIN personel_hareketleri ph ON ph.personel_id = p.id AND ph.silindi_mi = 0 {$joinFilter}
             WHERE " . implode(' AND ', $where) . "
             GROUP BY p.id, p.ad, p.gorev, p.maas
             ORDER BY p.ad",
            $params
        );

        return $this->withSummary($rows, [
            'Çalışan sayısı' => count($rows),
            'Toplam maaş' => $this->money($this->sum($rows, 'maas')),
            'Toplam ödeme' => $this->money($this->sum($rows, 'toplam_odeme')),
            'Kalan borç/alacak' => $this->money($this->sum($rows, 'kalan_borc_alacak')),
        ], 'Çalışan raporu personeller ve personel_hareketleri tablolarından hesaplanır; iptal hareketler toplam dışıdır.');
    }

    public function getOverdueReceivables(array $filters): array
    {
        $where = [
            "f.silindi_mi = 0",
            "f.durum <> 'iptal'",
            "f.belge_tipi IN ('satis','perakende')",
            "GREATEST(f.genel_toplam - COALESCE(f.odenen_tutar,0), 0) > 0",
            "f.company_id = :tenant_company_id",
            "f.period_id = :tenant_period_id",
        ];
        $params = [
            ':tenant_company_id' => TenantContext::activeCompanyId(),
            ':tenant_period_id'  => TenantContext::activePeriodId(),
        ];
        if (!empty($filters['customer_id'])) {
            $where[] = 'f.cari_id = :customer_id';
            $params[':customer_id'] = (int)$filters['customer_id'];
        }
        // "Bugün / Son 7 gün / Bu ay …" gibi dönem ön ayarları burada uygulanmıyordu —
        // yalnızca elle girilen başlangıç/bitiş tarihi çalışıyordu. dateWhere() her
        // ikisini de (ön ayar + elle giriş) tutarlı biçimde ele alır.
        [$dateSql, $dateParams] = $this->dateWhere($filters, 'f.vade_tarihi');
        if ($dateSql) {
            $where[] = $this->stripLeadingAnd($dateSql);
            $params += $dateParams;
        }
        if (($filters['min_days'] ?? '') !== '') {
            $where[] = 'DATEDIFF(CURDATE(), f.vade_tarihi) >= :min_days';
            $params[':min_days'] = (int)$filters['min_days'];
        }
        if (($filters['min_amount'] ?? '') !== '') {
            $where[] = 'GREATEST(f.genel_toplam - COALESCE(f.odenen_tutar,0), 0) >= :min_amount';
            $params[':min_amount'] = (float)$filters['min_amount'];
        }
        if (($filters['max_amount'] ?? '') !== '') {
            $where[] = 'GREATEST(f.genel_toplam - COALESCE(f.odenen_tutar,0), 0) <= :max_amount';
            $params[':max_amount'] = (float)$filters['max_amount'];
        }
        if (!empty($filters['only_overdue'])) {
            $where[] = 'f.vade_tarihi < CURDATE()';
        }
        if (!empty($filters['high_risk'])) {
            $where[] = 'DATEDIFF(CURDATE(), f.vade_tarihi) >= 30';
        }

        $rows = $this->db->select(
            "SELECT COALESCE(c.unvan, 'Cari yok') AS musteri_adi, f.fatura_no, f.fatura_tarihi, f.vade_tarihi,
                    f.genel_toplam AS fatura_toplami, COALESCE(f.odenen_tutar,0) AS tahsil_edilen,
                    GREATEST(f.genel_toplam - COALESCE(f.odenen_tutar,0), 0) AS kalan_alacak,
                    GREATEST(DATEDIFF(CURDATE(), f.vade_tarihi), 0) AS gecikme_gunu,
                    CASE WHEN DATEDIFF(CURDATE(), f.vade_tarihi) >= 60 THEN 'Yüksek' WHEN DATEDIFF(CURDATE(), f.vade_tarihi) >= 30 THEN 'Orta' WHEN f.vade_tarihi < CURDATE() THEN 'Düşük' ELSE 'Vadesi gelmedi' END AS risk_durumu
             FROM faturalar f
             LEFT JOIN cariler c ON c.id = f.cari_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY f.vade_tarihi ASC, kalan_alacak DESC",
            $params
        );

        return $this->withSummary($rows, [
            'Vadesi geçmiş alacak' => $this->money($this->sum($rows, 'kalan_alacak')),
            'Fatura sayısı' => count($rows),
            'Yüksek riskli kayıt' => count(array_filter($rows, fn($r) => $r['risk_durumu'] === 'Yüksek')),
        ]);
    }

    public function getVatReport(array $filters): array
    {
        [$dateSql, $params] = $this->dateWhere($filters, 'f.fatura_tarihi');
        $where = [
            "f.silindi_mi = 0",
            "f.durum NOT IN ('iptal','taslak')",
            "f.company_id = :tenant_company_id",
            "f.period_id = :tenant_period_id",
        ];
        $params[':tenant_company_id'] = TenantContext::activeCompanyId();
        $params[':tenant_period_id']  = TenantContext::activePeriodId();
        if ($dateSql) $where[] = $this->stripLeadingAnd($dateSql);
        if (!empty($filters['month'])) {
            $where[] = 'MONTH(f.fatura_tarihi) = :month';
            $params[':month'] = (int)$filters['month'];
        }
        if (!empty($filters['year'])) {
            $where[] = 'YEAR(f.fatura_tarihi) = :year';
            $params[':year'] = (int)$filters['year'];
        }
        if (!empty($filters['invoice_type'])) {
            $where[] = $filters['invoice_type'] === 'satis' ? "f.belge_tipi IN ('satis','perakende')" : "f.belge_tipi = 'alis'";
        } else {
            $where[] = "f.belge_tipi IN ('satis','perakende','alis')";
        }
        if (($filters['vat_rate'] ?? '') !== '') {
            $where[] = 'EXISTS (SELECT 1 FROM fatura_kalemleri fk WHERE fk.fatura_id = f.id AND fk.silindi_mi = 0 AND fk.kdv_orani = :vat_rate)';
            $params[':vat_rate'] = (float)$filters['vat_rate'];
        }

        $queries = [
            "SELECT DATE_FORMAT(f.fatura_tarihi, '%Y-%m') AS donem,
                    SUM(CASE WHEN f.belge_tipi IN ('satis','perakende') THEN f.ara_toplam - f.iskonto_tutari ELSE 0 END) AS satis_matrahi,
                    SUM(CASE WHEN f.belge_tipi IN ('satis','perakende') THEN f.kdv_tutari ELSE 0 END) AS satis_kdv,
                    SUM(CASE WHEN f.belge_tipi = 'alis' THEN f.ara_toplam - f.iskonto_tutari ELSE 0 END) AS alis_matrahi,
                    SUM(CASE WHEN f.belge_tipi = 'alis' THEN f.kdv_tutari ELSE 0 END) AS alis_kdv,
                    SUM(CASE WHEN f.belge_tipi IN ('satis','perakende') THEN f.kdv_tutari WHEN f.belge_tipi = 'alis' THEN -f.kdv_tutari ELSE 0 END) AS net_kdv
             FROM faturalar f
             WHERE " . implode(' AND ', $where) . "
             GROUP BY DATE_FORMAT(f.fatura_tarihi, '%Y-%m')"
        ];

        if ($this->tableExists('gelen_efaturalar') && ($filters['invoice_type'] ?? '') !== 'satis') {
            [$geDateSql, $geParams] = $this->dateWhere($filters, 'ge.duzenleme_tarihi');
            foreach ($geParams as $key => $value) {
                $newKey = ':ge_' . ltrim($key, ':');
                $params[$newKey] = $value;
            }

            $geWhere = [
                "ge.silindi_mi = 0",
                "ge.fatura_durumu <> 'iptal'",
                "ge.company_id = :tenant_company_id",
                "ge.period_id = :tenant_period_id",
            ];
            if ($geDateSql) {
                $geWhere[] = str_replace([':start_date', ':end_date'], [':ge_start_date', ':ge_end_date'], $this->stripLeadingAnd($geDateSql));
            }
            if (!empty($filters['month'])) {
                $geWhere[] = 'MONTH(ge.duzenleme_tarihi) = :month';
            }
            if (!empty($filters['year'])) {
                $geWhere[] = 'YEAR(ge.duzenleme_tarihi) = :year';
            }
            if (($filters['vat_rate'] ?? '') !== '') {
                $geWhere[] = 'EXISTS (SELECT 1 FROM gelen_efatura_kalemleri gek WHERE gek.gelen_efatura_id = ge.id AND gek.kdv_orani = :vat_rate AND gek.company_id = :tenant_company_id)';
            }

            $queries[] = "SELECT DATE_FORMAT(ge.duzenleme_tarihi, '%Y-%m') AS donem,
                    0 AS satis_matrahi,
                    0 AS satis_kdv,
                    SUM(GREATEST(ge.genel_toplam_tl - ge.toplam_kdv, 0)) AS alis_matrahi,
                    SUM(ge.toplam_kdv) AS alis_kdv,
                    -SUM(ge.toplam_kdv) AS net_kdv
             FROM gelen_efaturalar ge
             WHERE " . implode(' AND ', $geWhere) . "
             GROUP BY DATE_FORMAT(ge.duzenleme_tarihi, '%Y-%m')";
        }

        $rows = $this->db->select(
            "SELECT donem,
                    SUM(satis_matrahi) AS satis_matrahi,
                    SUM(satis_kdv) AS satis_kdv,
                    SUM(alis_matrahi) AS alis_matrahi,
                    SUM(alis_kdv) AS alis_kdv,
                    SUM(net_kdv) AS odenecek_kdv,
                    0 AS devreden_kdv,
                    '' AS net_durum
             FROM (" . implode(' UNION ALL ', $queries) . ") k
             GROUP BY donem
             ORDER BY donem DESC",
            $params
        );

        foreach ($rows as &$row) {
            $net = (float)$row['odenecek_kdv'];
            $row['devreden_kdv'] = $net < 0 ? abs($net) : 0;
            $row['odenecek_kdv'] = max($net, 0);
            $row['net_durum'] = $net >= 0 ? 'Ödenecek KDV' : 'Devreden KDV';
        }

        return $this->withSummary($rows, [
            'Satış KDV' => $this->money($this->sum($rows, 'satis_kdv')),
            'Alış KDV' => $this->money($this->sum($rows, 'alis_kdv')),
            'Ödenecek KDV' => $this->money($this->sum($rows, 'odenecek_kdv')),
            'Devreden KDV' => $this->money($this->sum($rows, 'devreden_kdv')),
        ], $this->tableExists('gelen_efaturalar') ? 'KDV raporu aktif gelen e-faturaları alış/gider KDV olarak dahil eder.' : '');
    }

    public function getExpenseReport(array $filters): array
    {
        if (!$this->tableExists('masraflar')) {
            return $this->missing('Masraf raporu için masraflar tablosu bulunamadı.', 'Öneri: masraflar ve masraf_kategoriler tabloları oluşturulmalı.');
        }
        [$dateSql, $params] = $this->dateWhere($filters, 'm.islem_tarihi');
        $where = ['m.silindi_mi = 0', 'm.company_id = :tenant_company_id', 'm.period_id = :tenant_period_id'];
        $params[':tenant_company_id'] = TenantContext::activeCompanyId();
        $params[':tenant_period_id']  = TenantContext::activePeriodId();
        if ($dateSql) $where[] = $this->stripLeadingAnd($dateSql);
        if (!empty($filters['expense_category_id'])) {
            $where[] = 'm.kategori_id = :expense_category_id';
            $params[':expense_category_id'] = (int)$filters['expense_category_id'];
        }
        // Paylaşılan "Ödeme durumu" filtresi paid/partial/unpaid (İngilizce) gönderir;
        // masraflar.odeme_durumu ise bekliyor/odendi/gecikti (Türkçe, "kısmi" yok)
        // kullanır — birebir eşleştirmek her zaman 0 satır dönerdi.
        $masrafDurum = ['paid' => 'odendi', 'unpaid' => 'bekliyor'][$filters['payment_status'] ?? ''] ?? null;
        if ($masrafDurum !== null) {
            $where[] = 'm.odeme_durumu = :payment_status';
            $params[':payment_status'] = $masrafDurum;
        }
        if (!empty($filters['account_id'])) {
            $where[] = 'm.kasa_id = :account_id';
            $params[':account_id'] = (int)$filters['account_id'];
        }
        if (($filters['min_amount'] ?? '') !== '') {
            $where[] = '(m.tutar + m.kdv_tutari) >= :min_amount';
            $params[':min_amount'] = (float)$filters['min_amount'];
        }
        if (($filters['max_amount'] ?? '') !== '') {
            $where[] = '(m.tutar + m.kdv_tutari) <= :max_amount';
            $params[':max_amount'] = (float)$filters['max_amount'];
        }

        $queries = [
            "SELECT m.islem_tarihi AS tarih, COALESCE(mk.ad, '-') AS masraf_kategorisi, m.aciklama,
                    m.tutar, m.kdv_tutari AS kdv, (m.tutar + m.kdv_tutari) AS toplam,
                    m.odeme_durumu AS odeme_yontemi, COALESCE(kb.hesap_adi, '-') AS kasa_banka,
                    m.belge_no, '-' AS kullanici
             FROM masraflar m
             LEFT JOIN masraf_kategoriler mk ON mk.id = m.kategori_id
             LEFT JOIN kasa_banka kb ON kb.id = m.kasa_id
             WHERE " . implode(' AND ', $where)
        ];

        if ($this->tableExists('gelen_efaturalar')) {
            [$geDateSql, $geParams] = $this->dateWhere($filters, 'ge.duzenleme_tarihi');
            foreach ($geParams as $key => $value) {
                $params[':ge_' . ltrim($key, ':')] = $value;
            }
            $geWhere = [
                "ge.silindi_mi = 0",
                "ge.fatura_durumu <> 'iptal'",
                "ge.company_id = :tenant_company_id",
                "ge.period_id = :tenant_period_id",
            ];
            if ($geDateSql) {
                $geWhere[] = str_replace([':start_date', ':end_date'], [':ge_start_date', ':ge_end_date'], $this->stripLeadingAnd($geDateSql));
            }
            if (!empty($filters['payment_status'])) {
                $geWhere[] = 'ge.odeme_durumu = :payment_status';
            }
            if (($filters['min_amount'] ?? '') !== '') {
                $geWhere[] = 'ge.genel_toplam_tl >= :min_amount';
            }
            if (($filters['max_amount'] ?? '') !== '') {
                $geWhere[] = 'ge.genel_toplam_tl <= :max_amount';
            }

            $queries[] = "SELECT ge.duzenleme_tarihi AS tarih,
                    COALESCE(NULLIF(ge.kategori,''), 'Gelen E-Fatura') AS masraf_kategorisi,
                    CONCAT(COALESCE(ge.fatura_ismi,''), ' ', COALESCE(ge.tedarikci_calisan_adi,'')) AS aciklama,
                    GREATEST(ge.genel_toplam_tl - ge.toplam_kdv, 0) AS tutar,
                    ge.toplam_kdv AS kdv,
                    ge.genel_toplam_tl AS toplam,
                    ge.odeme_durumu AS odeme_yontemi,
                    COALESCE(ge.son_odeme_hesabi, '-') AS kasa_banka,
                    ge.fis_fatura_no AS belge_no,
                    '-' AS kullanici
             FROM gelen_efaturalar ge
             WHERE " . implode(' AND ', $geWhere);
        }

        $rows = $this->db->select(
            "SELECT * FROM (" . implode(' UNION ALL ', $queries) . ") r
             ORDER BY tarih DESC, belge_no DESC",
            $params
        );
        $topCategory = $this->topExpenseCategory($where, $params);

        return $this->withSummary($rows, [
            'Toplam masraf' => $this->money($this->sum($rows, 'toplam')),
            'Toplam masraf KDV' => $this->money($this->sum($rows, 'kdv')),
            'En yüksek masraf kategorisi' => $topCategory,
            'Masraf sayısı' => count($rows),
            'Ortalama masraf tutarı' => count($rows) ? $this->money($this->sum($rows, 'toplam') / count($rows)) : $this->money(0),
        ], 'Kullanıcı bilgisi masraflar tablosunda tutulmadığı için kullanıcı kolonu teknik olarak eşleştirilemiyor.');
    }

    public function getPromissoryNotesReport(array $filters): array
    {
        if (!$this->tableExists('cek_senet_portfoyu')) {
            return $this->missing('Senet raporu için senet/veri yapısı bulunamadı.', 'Öneri: cek_senet_portfoyu tablosu tip=senet kayıtlarını, belge_no, cari_id, tutar, kesim_tarihi, vade_tarihi, durum alanlarıyla tutmalı.');
        }
        return $this->getPortfolioReport('senet', $filters);
    }

    public function getBaBsReport(array $filters): array
    {
        $month = (int)(($filters['month'] ?? 0) ?: date('n'));
        $year = (int)(($filters['year'] ?? 0) ?: date('Y'));
        $params = [
            ':month' => $month,
            ':year'  => $year,
            ':tenant_company_id' => TenantContext::activeCompanyId(),
            ':tenant_period_id'  => TenantContext::activePeriodId(),
        ];
        $where = [
            "f.silindi_mi = 0",
            "f.durum <> 'iptal'",
            "MONTH(f.fatura_tarihi) = :month",
            "YEAR(f.fatura_tarihi) = :year",
            "f.company_id = :tenant_company_id",
            "f.period_id = :tenant_period_id",
        ];
        if (($filters['transaction_group'] ?? '') === 'BA') {
            $where[] = "f.belge_tipi = 'alis'";
        } elseif (($filters['transaction_group'] ?? '') === 'BS') {
            $where[] = "f.belge_tipi IN ('satis','perakende')";
        } else {
            $where[] = "f.belge_tipi IN ('alis','satis','perakende')";
        }
        $havingParts = [];
        if (($filters['min_amount'] ?? '') !== '') {
            $havingParts[] = 'genel_toplam >= :min_amount';
            $params[':min_amount'] = (float)$filters['min_amount'];
        }
        if (($filters['max_amount'] ?? '') !== '') {
            $havingParts[] = 'genel_toplam <= :max_amount';
            $params[':max_amount'] = (float)$filters['max_amount'];
        }
        if (!empty($filters['cari_search'])) {
            $where[] = 'c.unvan LIKE :cari_search';
            $params[':cari_search'] = '%' . $filters['cari_search'] . '%';
        }
        if (!empty($filters['tax_no'])) {
            $where[] = '(c.vergi_no LIKE :tax_no OR c.tc_kimlik_no LIKE :tax_no)';
            $params[':tax_no'] = '%' . $filters['tax_no'] . '%';
        }
        $having = $havingParts ? 'HAVING ' . implode(' AND ', $havingParts) : '';
        $rows = $this->db->select(
            "SELECT COALESCE(c.unvan, 'Cari yok') AS cari_unvan, COALESCE(NULLIF(c.vergi_no,''), c.tc_kimlik_no, '-') AS vergi_no,
                    COUNT(f.id) AS belge_sayisi, SUM(f.ara_toplam - f.iskonto_tutari) AS toplam_matrah,
                    SUM(f.kdv_tutari) AS toplam_kdv, SUM(f.genel_toplam) AS genel_toplam,
                    CASE WHEN f.belge_tipi = 'alis' THEN 'BA' ELSE 'BS' END AS islem_turu
             FROM faturalar f
             LEFT JOIN cariler c ON c.id = f.cari_id
             WHERE " . implode(' AND ', $where) . "
             GROUP BY c.id, c.unvan, vergi_no, islem_turu
             {$having}
             ORDER BY genel_toplam DESC",
            $params
        );
        return $this->withSummary($rows, [
            'BA/BS toplamı' => $this->money($this->sum($rows, 'genel_toplam')),
            'Belge sayısı' => (int)$this->sum($rows, 'belge_sayisi'),
            'Cari sayısı' => count($rows),
        ]);
    }

    public function getIncomeExpenseReport(array $filters): array
    {
        $group = ($filters['group_by'] ?? '') ?: 'monthly';
        $expr = $group === 'daily' ? '%Y-%m-%d' : ($group === 'yearly' ? '%Y' : '%Y-%m');
        [$dateSql, $params] = $this->dateWhere($filters, 'tarih');
        $where = $dateSql ? 'WHERE ' . $this->stripLeadingAnd($dateSql) : '';
        $params[':tenant_company_id'] = TenantContext::activeCompanyId();
        $params[':tenant_period_id']  = TenantContext::activePeriodId();
        $gelenEFaturaUnion = '';
        if ($this->tableExists('gelen_efaturalar')) {
            $gelenEFaturaUnion = "
                UNION ALL
                SELECT duzenleme_tarihi AS tarih, 0, 0, 0, genel_toplam_tl
                FROM gelen_efaturalar
                WHERE silindi_mi = 0 AND fatura_durumu <> 'iptal'
                  AND company_id = :tenant_company_id AND period_id = :tenant_period_id
            ";
        }
        $rows = $this->db->select(
            "SELECT DATE_FORMAT(tarih, '{$expr}') AS donem,
                    SUM(satis_geliri) AS toplam_satis_geliri,
                    SUM(diger_gelir) AS diger_gelirler,
                    SUM(alis_maliyeti) AS toplam_alis_maliyeti,
                    SUM(masraf) AS masraflar,
                    0 AS personel_giderleri,
                    SUM(satis_geliri + diger_gelir - alis_maliyeti - masraf) AS net_gelir_gider_farki,
                    CASE WHEN SUM(satis_geliri + diger_gelir - alis_maliyeti - masraf) >= 0 THEN 'Kâr' ELSE 'Zarar' END AS kar_zarar_durumu
             FROM (
                SELECT fatura_tarihi AS tarih,
                       CASE WHEN belge_tipi IN ('satis','perakende') THEN genel_toplam ELSE 0 END AS satis_geliri,
                       0 AS diger_gelir,
                       CASE WHEN belge_tipi = 'alis' THEN genel_toplam ELSE 0 END AS alis_maliyeti,
                       0 AS masraf
                FROM faturalar
                WHERE silindi_mi = 0 AND durum <> 'iptal' AND belge_tipi IN ('satis','perakende','alis')
                  AND company_id = :tenant_company_id AND period_id = :tenant_period_id
                UNION ALL
                SELECT islem_tarihi AS tarih, 0, 0, 0, tutar + kdv_tutari
                FROM masraflar
                WHERE silindi_mi = 0
                  AND company_id = :tenant_company_id AND period_id = :tenant_period_id
                {$gelenEFaturaUnion}
                UNION ALL
                SELECT DATE(tarih) AS tarih, 0, CASE WHEN islem_tipi = 'giris' AND hareket_tipi NOT IN ('tahsilat','transfer') THEN tutar ELSE 0 END, 0, CASE WHEN islem_tipi = 'cikis' AND hareket_tipi NOT IN ('odeme','transfer','masraf') THEN tutar ELSE 0 END
                FROM kasa_hareketleri
                WHERE silindi_mi = 0
                  AND company_id = :tenant_company_id AND period_id = :tenant_period_id
             ) x
             {$where}
             GROUP BY DATE_FORMAT(tarih, '{$expr}')
             ORDER BY donem DESC",
            $params
        );
        $income = $this->sum($rows, 'toplam_satis_geliri') + $this->sum($rows, 'diger_gelirler');
        $expense = $this->sum($rows, 'toplam_alis_maliyeti') + $this->sum($rows, 'masraflar') + $this->sum($rows, 'personel_giderleri');
        return $this->withSummary($rows, [
            'Toplam gelir' => $this->money($income),
            'Toplam gider' => $this->money($expense),
            'Net kâr/zarar' => $this->money($income - $expense),
            'Gelir/gider oranı' => $expense > 0 ? number_format($income / $expense, 2, ',', '.') : '-',
            'En yüksek gider kalemi' => $this->sum($rows, 'toplam_alis_maliyeti') >= $this->sum($rows, 'masraflar') ? 'Alış maliyeti' : 'Masraflar',
        ], 'Personel giderleri için personel bordro tablosu bulunmadığından 0 gösterilir.');
    }

    public function getAccountBalancesReport(array $filters): array
    {
        $params = [
            ':tenant_company_id' => TenantContext::activeCompanyId(),
            ':tenant_period_id'  => TenantContext::activePeriodId(),
        ];
        $rows = $this->db->select(
            "SELECT hesap_turu, hesap_adi, toplam_borc, toplam_alacak,
                    toplam_borc - toplam_alacak AS net_bakiye,
                    CASE WHEN toplam_borc - toplam_alacak > 0 THEN 'Borçlu' WHEN toplam_borc - toplam_alacak < 0 THEN 'Alacaklı' ELSE 'Sıfır' END AS bakiye_durumu,
                    son_islem_tarihi
             FROM (
                SELECT CASE WHEN c.tip = 'tedarikci' THEN 'Tedarikçi' ELSE 'Müşteri' END AS hesap_turu,
                       c.unvan AS hesap_adi,
                       COALESCE(SUM(CASE WHEN f.belge_tipi IN ('satis','perakende') THEN f.genel_toplam ELSE 0 END),0) AS toplam_borc,
                       COALESCE(SUM(CASE WHEN f.belge_tipi = 'alis' THEN f.genel_toplam ELSE 0 END),0) + COALESCE(kh.alacak,0) + COALESCE(gef.gelen_borc,0) AS toplam_alacak,
                       GREATEST(MAX(f.fatura_tarihi), DATE(MAX(kh.son_tarih)), DATE(MAX(gef.son_tarih))) AS son_islem_tarihi
                FROM cariler c
                LEFT JOIN faturalar f ON f.cari_id = c.id AND f.silindi_mi = 0 AND f.durum <> 'iptal'
                                       AND f.company_id = :tenant_company_id AND f.period_id = :tenant_period_id
                LEFT JOIN (
                    SELECT cari_id, SUM(CASE WHEN islem_tipi = 'giris' THEN tutar ELSE 0 END) AS alacak, MAX(tarih) AS son_tarih
                    FROM kasa_hareketleri
                    WHERE silindi_mi = 0
                      AND company_id = :tenant_company_id AND period_id = :tenant_period_id
                    GROUP BY cari_id
                ) kh ON kh.cari_id = c.id
                LEFT JOIN (
                    SELECT tedarikci_id, SUM(genel_toplam_tl) AS gelen_borc, MAX(duzenleme_tarihi) AS son_tarih
                    FROM gelen_efaturalar
                    WHERE silindi_mi = 0 AND fatura_durumu <> 'iptal' AND tedarikci_id IS NOT NULL
                      AND company_id = :tenant_company_id AND period_id = :tenant_period_id
                    GROUP BY tedarikci_id
                ) gef ON gef.tedarikci_id = c.id
                WHERE c.silindi_mi = 0
                  AND c.company_id = :tenant_company_id
                GROUP BY c.id, c.tip, c.unvan, kh.alacak, gef.gelen_borc
                UNION ALL
                SELECT 'Tedarikçi' AS hesap_turu,
                       CONCAT(COALESCE(NULLIF(tedarikci_calisan_adi,''), 'Eşleşmemiş Gelen E-Fatura'), ' (eşleşmemiş)') AS hesap_adi,
                       0 AS toplam_borc,
                       SUM(genel_toplam_tl) AS toplam_alacak,
                       MAX(duzenleme_tarihi) AS son_islem_tarihi
                FROM gelen_efaturalar
                WHERE silindi_mi = 0 AND fatura_durumu <> 'iptal' AND tedarikci_id IS NULL
                  AND company_id = :tenant_company_id AND period_id = :tenant_period_id
                GROUP BY tedarikci_calisan_adi
                UNION ALL
                SELECT CASE WHEN kb.tip = 'banka' THEN 'Banka' ELSE 'Kasa' END, kb.hesap_adi,
                       COALESCE(SUM(CASE WHEN kh.islem_tipi = 'cikis' THEN kh.tutar ELSE 0 END),0),
                       COALESCE(SUM(CASE WHEN kh.islem_tipi = 'giris' THEN kh.tutar ELSE 0 END),0),
                       DATE(MAX(kh.tarih))
                FROM kasa_banka kb
                LEFT JOIN kasa_hareketleri kh ON kh.kasa_id = kb.id AND kh.silindi_mi = 0
                                              AND kh.company_id = :tenant_company_id AND kh.period_id = :tenant_period_id
                WHERE kb.silindi_mi = 0
                  AND kb.company_id = :tenant_company_id
                GROUP BY kb.id, kb.tip, kb.hesap_adi
             ) b
             ORDER BY hesap_turu, hesap_adi",
            $params
        );
        if (!empty($filters['account_type'])) {
            $rows = array_values(array_filter($rows, fn($r) => ($r['hesap_turu'] ?? '') === $filters['account_type']));
        }
        if (!empty($filters['cari_search'])) {
            $q = mb_strtolower($filters['cari_search'], 'UTF-8');
            $rows = array_values(array_filter($rows, fn($r) => str_contains(mb_strtolower((string)($r['hesap_adi'] ?? ''), 'UTF-8'), $q)));
        }
        if (!empty($filters['balance_state'])) {
            $rows = array_values(array_filter($rows, fn($r) => ($r['bakiye_durumu'] ?? '') === $filters['balance_state']));
        }
        if (($filters['min_amount'] ?? '') !== '') {
            $min = (float)$filters['min_amount'];
            $rows = array_values(array_filter($rows, fn($r) => abs((float)($r['net_bakiye'] ?? 0)) >= $min));
        }
        if (($filters['max_amount'] ?? '') !== '') {
            $max = (float)$filters['max_amount'];
            $rows = array_values(array_filter($rows, fn($r) => abs((float)($r['net_bakiye'] ?? 0)) <= $max));
        }
        return $this->withSummary($rows, [
            'Toplam borç' => $this->money($this->sum($rows, 'toplam_borc')),
            'Toplam alacak' => $this->money($this->sum($rows, 'toplam_alacak')),
            'Net bakiye' => $this->money($this->sum($rows, 'net_bakiye')),
            'Hesap sayısı' => count($rows),
        ], 'Cari bakiye kasa_hareketleri.fatura_id olmadan cari bazlı hareketlerden ve fatura toplamlarından hesaplanır.');
    }

    public function getChequeReport(array $filters): array
    {
        if (!$this->tableExists('cek_senet_portfoyu')) {
            return $this->missing('Çek raporu için çek/veri yapısı bulunamadı.', 'Öneri: cek_senet_portfoyu tablosu tip=cek kayıtlarını, belge_no, banka, cari_id, tutar, vade_tarihi, durum alanlarıyla tutmalı.');
        }
        return $this->getPortfolioReport('cek', $filters);
    }

    public function getCreditReport(array $filters): array
    {
        if (!$this->tableExists('krediler') || !$this->tableExists('kredi_odeme_plani')) {
            return $this->missing('Kredi raporu için kredi/ödeme veri yapısı bulunamadı.', 'Öneri: krediler(id, kredi_adi, banka, ana_para, faiz_orani, taksit_sayisi, durum) ve kredi_odemeleri(id, kredi_id, tarih, tutar) tabloları eklenmeli.');
        }

        $params = [
            ':tenant_company_id' => TenantContext::activeCompanyId(),
            ':tenant_period_id'  => TenantContext::activePeriodId(),
        ];
        $where = ['k.silindi_mi = 0', 'k.company_id = :tenant_company_id', 'k.period_id = :tenant_period_id'];
        // Tarih filtresi taksit ödeme tarihine uygulanır — WHERE yerine JOIN'in ON
        // koşuluna eklenir, aksi halde bu dönemde ödemesi olmayan (ama halen borcu
        // devam eden) krediler LEFT JOIN'e rağmen listeden tamamen düşerdi.
        [$dateSql, $dateParams] = $this->dateWhere($filters, 'p.odeme_tarihi');
        $paymentJoinExtra = $dateSql ? ' AND ' . $this->stripLeadingAnd($dateSql) : '';
        if ($dateSql) {
            $params += $dateParams;
        }
        if (!empty($filters['bank'])) {
            $where[] = 'hb.hesap_adi LIKE :bank';
            $params[':bank'] = '%' . $filters['bank'] . '%';
        }
        $havingParts = [];
        if (!empty($filters['payment_status'])) {
            if ($filters['payment_status'] === 'paid') {
                $havingParts[] = 'k.kalan_borc <= 0';
            } elseif ($filters['payment_status'] === 'unpaid') {
                $havingParts[] = 'odenen_tutar <= 0';
            } elseif ($filters['payment_status'] === 'partial') {
                $havingParts[] = 'odenen_tutar > 0 AND k.kalan_borc > 0';
            }
        }
        $having = $havingParts ? 'HAVING ' . implode(' AND ', $havingParts) : '';

        $rows = $this->db->select(
            "SELECT k.ad AS kredi_adi,
                    COALESCE(hb.hesap_adi, '-') AS banka,
                    COALESCE(SUM(p.tutar), k.kalan_borc) AS ana_para,
                    '-' AS faiz_orani,
                    COUNT(p.id) AS taksit_sayisi,
                    COALESCE(SUM(CASE WHEN p.odendi = 1 THEN p.tutar ELSE 0 END), 0) AS odenen_tutar,
                    k.kalan_borc,
                    MAX(p.odeme_tarihi) AS son_odeme_tarihi,
                    CASE WHEN k.kalan_borc <= 0 OR k.kalan_taksit_sayisi <= 0 THEN 'Kapandı' ELSE 'Aktif' END AS durum
             FROM krediler k
             LEFT JOIN kredi_odeme_plani p ON p.kredi_id = k.id AND p.silindi_mi = 0 AND p.company_id = k.company_id AND p.period_id = k.period_id{$paymentJoinExtra}
             LEFT JOIN kasa_banka hb ON hb.id = k.hesap_id AND hb.company_id = k.company_id
             WHERE " . implode(' AND ', $where) . "
             GROUP BY k.id
             {$having}
             ORDER BY k.kalan_borc DESC",
            $params
        );

        return $this->withSummary($rows, [
            'Toplam kalan borç' => $this->money($this->sum($rows, 'kalan_borc')),
            'Ödenen taksit toplamı' => $this->money($this->sum($rows, 'odenen_tutar')),
            'Kredi sayısı' => count($rows),
        ], 'Faiz oranı ve orijinal ana para krediler tablosunda ayrı saklanmadığından; ana para taksit planından hesaplanır, faiz oranı "-" olarak gösterilir.');
    }

    public function getDailyAccountMovements(array $filters): array
    {
        $filters['start_date'] = ($filters['start_date'] ?? '') ?: date('Y-m-d');
        $filters['end_date'] = ($filters['end_date'] ?? '') ?: $filters['start_date'];
        $result = $this->getCashBankMovements($filters);
        foreach ($result['rows'] as &$row) {
            $row['saat'] = substr((string)$row['tarih'], 11, 5) ?: '-';
            $row['islem_turu'] = $row['islem_tipi'];
            $row['hesap'] = $row['hesap_adi'];
            $row['cari'] = $row['cari_adi'];
            $row['giris'] = $row['gelir_tutari'];
            $row['cikis'] = $row['gider_tutari'];
            $row['gun_sonu_bakiye'] = $row['bakiye'];
        }
        return $result;
    }

    public function getDebitCreditVoucherReport(array $filters): array
    {
        return $this->missing('Borç/alacak fişleri raporu için fiş veri yapısı bulunamadı.', 'Öneri: borc_alacak_fisleri(id, fis_no, tarih, cari_id, fis_turu, tutar, aciklama, kullanici_id, durum) tablosu eklenmeli.');
    }

    public function getUserSalesCollectionReport(array $filters): array
    {
        // faturalar.created_by kullanıcı bazında satış faturalarını izlemek için
        // yeterli (bkz. Fatura::listele() — aynı kolonla users'a JOIN edilir).
        // kasa_hareketleri ise HANGİ KULLANICI tarafından tahsilat yapıldığını
        // tutmuyor (kolon yok) — bu yüzden "tahsilat sayısı" bu raporda
        // izlenemez; "toplam/bekleyen tahsilat" ise ilgili faturaların
        // odenen_tutar alanından (fatura bazlı, kullanıcı bazlı değil) hesaplanır.
        $conds = [
            "f.silindi_mi = 0", "f.belge_tipi IN ('satis','perakende')", "f.durum <> 'iptal'",
            'f.company_id = :tenant_company_id', 'f.period_id = :tenant_period_id',
        ];
        $params = [
            ':tenant_company_id' => TenantContext::activeCompanyId(),
            ':tenant_period_id' => TenantContext::activePeriodId(),
        ];
        [$dateSql, $dateParams] = $this->dateWhere($filters, 'f.fatura_tarihi');
        if ($dateSql) {
            $conds[] = $this->stripLeadingAnd($dateSql);
            $params += $dateParams;
        }
        if (!empty($filters['user_id'])) {
            $conds[] = 'f.created_by = :user_id';
            $params[':user_id'] = (int)$filters['user_id'];
        }
        if (!empty($filters['customer_id'])) {
            $conds[] = 'f.cari_id = :customer_id';
            $params[':customer_id'] = (int)$filters['customer_id'];
        }
        if (!empty($filters['payment_status'])) {
            if ($filters['payment_status'] === 'paid') {
                $conds[] = 'f.genel_toplam <= COALESCE(f.odenen_tutar, 0) + 0.005';
            } elseif ($filters['payment_status'] === 'unpaid') {
                $conds[] = 'COALESCE(f.odenen_tutar, 0) <= 0';
            } elseif ($filters['payment_status'] === 'partial') {
                $conds[] = 'COALESCE(f.odenen_tutar, 0) > 0 AND COALESCE(f.odenen_tutar, 0) + 0.005 < f.genel_toplam';
            }
        }

        $rows = $this->db->select(
            "SELECT COALESCE(u.full_name, 'Kullanıcı atanmamış') AS kullanici_adi,
                    COUNT(*) AS satis_faturasi_sayisi,
                    COALESCE(SUM(f.genel_toplam), 0) AS toplam_satis_tutari,
                    COALESCE(SUM(COALESCE(f.odenen_tutar, 0)), 0) AS toplam_tahsilat,
                    COALESCE(SUM(GREATEST(f.genel_toplam - COALESCE(f.odenen_tutar, 0), 0)), 0) AS bekleyen_tahsilat,
                    CASE WHEN COUNT(*) > 0 THEN SUM(f.genel_toplam) / COUNT(*) ELSE 0 END AS ortalama_satis_tutari,
                    MAX(f.fatura_tarihi) AS son_islem_tarihi
             FROM faturalar f
             LEFT JOIN users u ON u.id = f.created_by
             WHERE " . implode(' AND ', $conds) . "
             GROUP BY f.created_by, u.full_name
             ORDER BY toplam_satis_tutari DESC",
            $params
        );
        foreach ($rows as &$row) {
            $row['tahsilat_sayisi'] = '—';
        }
        unset($row);

        return $this->withSummary($rows, [
            'Toplam satış tutarı' => $this->money($this->sum($rows, 'toplam_satis_tutari')),
            'Kullanıcı sayısı' => count($rows),
        ], 'Bu rapor faturalar.created_by üzerinden kullanıcı bazında satış faturalarını gösterir. Tahsilat işlemlerinin hangi kullanıcı tarafından yapıldığı kasa_hareketleri tablosunda tutulmadığı için "Tahsilat sayısı" izlenmemektedir; toplam/bekleyen tahsilat ise ilgili faturaların ödenen tutarından (fatura bazlı) hesaplanır.');
    }

    private function getPortfolioReport(string $type, array $filters = []): array
    {
        $where = [
            'p.silindi_mi = 0', 'p.tip = :type',
            'p.company_id = :tenant_company_id', 'p.period_id = :tenant_period_id',
        ];
        $params = [
            ':type' => $type,
            ':tenant_company_id' => TenantContext::activeCompanyId(),
            ':tenant_period_id'  => TenantContext::activePeriodId(),
        ];
        [$dateSql, $dateParams] = $this->dateWhere($filters, 'p.vade_tarihi');
        if ($dateSql) {
            $where[] = $this->stripLeadingAnd($dateSql);
            $params += $dateParams;
        }
        if (!empty($filters['cari_search'])) {
            $where[] = 'c.unvan LIKE :cari_search';
            $params[':cari_search'] = '%' . $filters['cari_search'] . '%';
        }
        if (!empty($filters['status'])) {
            $where[] = 'p.durum = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['only_overdue'])) {
            $where[] = "p.vade_tarihi < CURDATE() AND p.durum NOT IN ('tahsil','odendi','kapandi')";
        }

        $rows = $this->db->select(
            "SELECT belge_no AS cek_senet_no, COALESCE(c.unvan, '-') AS cari_adi,
                    CASE WHEN tip = 'cek' THEN 'Çek' ELSE 'Senet' END AS tur,
                    kesim_tarihi AS duzenleme_tarihi, vade_tarihi, tutar, durum,
                    GREATEST(DATEDIFF(CURDATE(), vade_tarihi), 0) AS gecikme_gunu, aciklama, banka
             FROM cek_senet_portfoyu p
             LEFT JOIN cariler c ON c.id = p.cari_id AND c.company_id = p.company_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY p.vade_tarihi ASC",
            $params
        );
        return $this->withSummary($rows, ['Toplam tutar' => $this->money($this->sum($rows, 'tutar')), 'Kayıt sayısı' => count($rows)]);
    }

    private function topExpenseCategory(array $where, array $params): string
    {
        $row = $this->db->selectOne(
            "SELECT COALESCE(mk.ad, '-') AS ad, SUM(m.tutar + m.kdv_tutari) AS toplam
             FROM masraflar m
             LEFT JOIN masraf_kategoriler mk ON mk.id = m.kategori_id AND mk.company_id = m.company_id
             WHERE " . implode(' AND ', $where) . "
             GROUP BY mk.id, mk.ad
             ORDER BY toplam DESC
             LIMIT 1",
            $params
        );
        return $row['ad'] ?? '-';
    }

    private function userOptions(): array
    {
        foreach (['kullanicilar', 'users'] as $table) {
            if (!$this->tableExists($table)) continue;

            // Hangi ad kolonları var? (full_name, ad, name vb. — kurulumdan kuruluma değişebilir)
            $candidates = ['full_name', 'ad', 'name', 'username', 'email'];
            $cols = [];
            foreach ($candidates as $c) {
                if ($this->columnExists($table, $c)) $cols[] = $c;
            }
            if (empty($cols)) {
                return $this->db->select("SELECT id, CAST(id AS CHAR) AS ad FROM {$table} ORDER BY id");
            }
            // ORDER BY için ilk varolan kolonu kullan
            $orderCol = $cols[0];
            $coalesce = 'COALESCE(`' . implode('`, `', $cols) . '`, CAST(id AS CHAR))';
            return $this->db->select("SELECT id, {$coalesce} AS ad FROM {$table} ORDER BY `{$orderCol}`");
        }
        return [];
    }

    private function dateWhere(array $filters, string $field): array
    {
        $period = $filters['period'] ?? '';
        if ($period === 'today') return [" AND DATE({$field}) = CURDATE()", []];
        if ($period === 'yesterday') return [" AND DATE({$field}) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)", []];
        if ($period === 'last_7') return [" AND DATE({$field}) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)", []];
        if ($period === 'last_30') return [" AND DATE({$field}) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)", []];
        if ($period === 'this_month') return [" AND YEAR({$field}) = YEAR(CURDATE()) AND MONTH({$field}) = MONTH(CURDATE())", []];
        if ($period === 'last_month') return [" AND DATE({$field}) >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01') AND DATE({$field}) < DATE_FORMAT(CURDATE(), '%Y-%m-01')", []];
        if ($period === 'last_6') return [" AND DATE({$field}) >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)", []];
        if ($period === 'this_year') return [" AND YEAR({$field}) = YEAR(CURDATE())", []];
        $sql = '';
        $params = [];
        if (!empty($filters['start_date'])) {
            $sql .= " AND DATE({$field}) >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sql .= " AND DATE({$field}) <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }
        return [$sql, $params];
    }

    private function tableExists(string $table): bool
    {
        $row = $this->db->selectOne("SELECT COUNT(*) AS n FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table", [':table' => $table]);
        return (int)($row['n'] ?? 0) > 0;
    }

    private function columnExists(string $table, string $column): bool
    {
        $row = $this->db->selectOne(
            "SELECT COUNT(*) AS n FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c",
            [':t' => $table, ':c' => $column]
        );
        return (int)($row['n'] ?? 0) > 0;
    }

    private function stripLeadingAnd(string $sql): string
    {
        return preg_replace('/^\s+AND\s+/i', '', $sql) ?? $sql;
    }

    private function missing(string $userMessage, string $technical): array
    {
        return $this->withSummary([], [], $userMessage . ' ' . $technical);
    }

    private function withSummary(array $rows, array $summary, string $note = ''): array
    {
        return ['rows' => $rows, 'summary' => $summary, 'note' => $note];
    }

    private function sum(array $rows, string $key): float
    {
        return array_sum(array_map(fn($row) => (float)($row[$key] ?? 0), $rows));
    }

    private function sumWhere(array $rows, string $key, string $filterKey, string $filterValue): float
    {
        return array_sum(array_map(fn($row) => ($row[$filterKey] ?? '') === $filterValue ? (float)($row[$key] ?? 0) : 0, $rows));
    }

    private function money(float $value): string
    {
        return number_format($value, 2, ',', '.') . ' TL';
    }
}
