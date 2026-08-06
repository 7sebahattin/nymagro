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

            // 2. Kasa/banka hesabının güncel bakiyesini güncelle
            $sign = ($islemTipi === 'giris') ? '+' : '-';
            $this->db->query(
                "UPDATE kasa_banka SET guncel_bakiye = guncel_bakiye {$sign} :tutar WHERE id = :kasa_id AND company_id = :company_id6",
                [':tutar' => $data['tutar'], ':kasa_id' => $kasaId, ':company_id6' => TenantContext::activeCompanyId()]
            );

            // 3. Cari bakiyesini güncelle (Eğer cari varsa) — tek doğruluk
            //    kaynağı olan Fatura::recomputeCariBalance() üzerinden.
            if (!empty($data['cari_id'])) {
                require_once MODELS_PATH . '/Fatura.php';
                (new Fatura())->recomputeCariBalance((int)$data['cari_id']);
            }

            $this->db->commit();
            return $id;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
