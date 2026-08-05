<?php
/**
 * Nakit Model
 * Kasa ve Banka hareketlerini yönetir.
 */
class Nakit
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Kasa/Banka hareketi ekle (Tahsilat veya Ödeme)
     */
    public function hareketEkle(array $data): int
    {
        TenantContext::assertWritablePeriod('kasa_hareketleri');
        $kasaId = (int)($data['kasa_id'] ?? $data['kasa_banka_id'] ?? 0);
        $islemTipi = $data['islem_tipi'] ?? 'giris';
        $hareketTipi = $data['hareket_tipi'] ?? ($islemTipi === 'giris' ? 'tahsilat' : 'odeme');
        $faturaCompanySql1 = TenantContext::hasColumn('faturalar', 'company_id') ? ' AND company_id = :company_id1' : '';
        $faturaCompanySql2 = TenantContext::hasColumn('faturalar', 'company_id') ? ' AND company_id = :company_id2' : '';
        $this->db->begin();
        try {
            // 1. Hareketi ekle
            $id = $this->db->insert('kasa_hareketleri', [
                'kasa_id'       => $kasaId,
                'kasa_banka_id' => $kasaId,
                'hareket_tipi'  => $hareketTipi,
                'cari_id'       => $data['cari_id'] ?? null,
                'islem_tipi'    => $islemTipi, // 'giris' veya 'cikis'
                'tutar'         => $data['tutar'],
                'tarih'         => $data['tarih'] ?? date('Y-m-d H:i:s'),
                'aciklama'      => $data['aciklama'] ?? '',
                'para_birimi'   => $data['para_birimi'] ?? 'TRY',
                'company_id'    => TenantContext::activeCompanyId(),
                'period_id'     => TenantContext::activePeriodId(),
            ]);

            // 2. Cari bakiyesini güncelle (Eğer cari varsa)
            if (!empty($data['cari_id'])) {
                $cariId = (int)$data['cari_id'];
                $islem = $data['islem_tipi'] === 'giris' ? '-' : '+'; // Tahsilat borcu azaltır (-)
                
                // Cari bakiyeyi anlık olarak faturalar ve nakit hareketleri üzerinden tekrar hesapla en garantisidir
                $this->db->query("UPDATE cariler SET bakiye = (
                    (SELECT COALESCE(SUM(genel_toplam), 0) FROM faturalar WHERE cari_id = :cid1 AND belge_tipi IN ('satis','perakende') AND silindi_mi = 0 AND durum <> 'iptal' {$faturaCompanySql1}) -
                    (SELECT COALESCE(SUM(genel_toplam), 0) FROM faturalar WHERE cari_id = :cid2 AND belge_tipi = 'alis' AND silindi_mi = 0 AND durum <> 'iptal' {$faturaCompanySql2}) -
                    (SELECT COALESCE(SUM(tutar), 0) FROM kasa_hareketleri WHERE cari_id = :cid3 AND islem_tipi = 'giris' AND company_id = :company_id3) +
                    (SELECT COALESCE(SUM(tutar), 0) FROM kasa_hareketleri WHERE cari_id = :cid4 AND islem_tipi = 'cikis' AND company_id = :company_id4)
                ) WHERE id = :cid5 AND company_id = :company_id5", [
                    ':cid1' => $cariId, ':cid2' => $cariId, ':cid3' => $cariId, ':cid4' => $cariId, ':cid5' => $cariId,
                    ':company_id1' => TenantContext::activeCompanyId(),
                    ':company_id2' => TenantContext::activeCompanyId(),
                    ':company_id3' => TenantContext::activeCompanyId(),
                    ':company_id4' => TenantContext::activeCompanyId(),
                    ':company_id5' => TenantContext::activeCompanyId(),
                ]);
            }

            $this->db->commit();
            return $id;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
