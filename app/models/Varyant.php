<?php
/**
 * Model: Varyant
 * --------------------------------------------------------
 * varyantlar ve varyant_degerleri tabloları üzerinde işlemler.
 */

class Varyant
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Tüm varyantları ve bağlı değerlerini getirir.
     * Döndürülen dizi örneği:
     * [
     *   [ 'id' => 1, 'ad' => 'LİTRE', 'degerler' => [ ['id'=>1, 'deger'=>'1 L'], ... ] ],
     *   ...
     * ]
     */
    public function tumunuGetir(): array
    {
        $companyId = TenantContext::activeCompanyId();
        $varyantlar = $this->db->select(
            "SELECT * FROM varyantlar WHERE company_id = :company_id ORDER BY id DESC",
            [':company_id' => $companyId]
        );
        
        $degerler = $this->db->select(
            "SELECT vd.*
             FROM varyant_degerleri vd
             JOIN varyantlar v ON v.id = vd.varyant_id
             WHERE vd.company_id = :company_id AND v.company_id = :company_id
             ORDER BY vd.id ASC",
            [':company_id' => $companyId]
        );
        
        // Varyantlara değerlerini ekleyelim
        foreach ($varyantlar as &$v) {
            $v['degerler'] = [];
            foreach ($degerler as $d) {
                if ($d['varyant_id'] == $v['id']) {
                    $v['degerler'][] = $d;
                }
            }
        }
        
        return $varyantlar;
    }

    /** Yeni varyant (başlık) ekler. */
    public function varyantEkle(string $ad): int
    {
        return $this->db->insert('varyantlar', [
            'ad' => mb_strtoupper($ad),
            'company_id' => TenantContext::activeCompanyId(),
        ]);
    }

    /** Varyanta alt değer ekler. */
    public function degerEkle(int $varyantId, string $deger): int
    {
        $this->assertVariantBelongsToCompany($varyantId);
        return $this->db->insert('varyant_degerleri', [
            'varyant_id' => $varyantId,
            'deger'      => mb_strtoupper($deger),
            'company_id' => TenantContext::activeCompanyId(),
        ]);
    }

    /** Ürüne bağlı varyant değer IDsini getirir */
    public function urunVaryantlariniGetir(int $urunId): array
    {
        $this->assertProductBelongsToCompany($urunId);
        $rows = $this->db->select(
            "SELECT uvd.varyant_deger_id
             FROM urun_varyant_degerleri uvd
             JOIN varyant_degerleri vd ON vd.id = uvd.varyant_deger_id
             JOIN varyantlar v ON v.id = vd.varyant_id
             WHERE uvd.urun_id = :id
               AND uvd.company_id = :company_id
               AND vd.company_id = :company_id
               AND v.company_id = :company_id",
            [':id' => $urunId, ':company_id' => TenantContext::activeCompanyId()]
        );
        return array_column($rows, 'varyant_deger_id');
    }

    /** Ürünün varyantlarını toplu kaydeder */
    public function urunVaryantlariniKaydet(int $urunId, array $degerIds): void
    {
        $this->assertProductBelongsToCompany($urunId);
        $companyId = TenantContext::activeCompanyId();

        $this->db->begin();
        try {
            $this->db->query(
                "DELETE FROM urun_varyant_degerleri WHERE urun_id = :id AND company_id = :company_id",
                [':id' => $urunId, ':company_id' => $companyId]
            );

            foreach ($degerIds as $dId) {
                $this->assertVariantValueBelongsToCompany((int)$dId);
                $this->db->insert('urun_varyant_degerleri', [
                    'urun_id'          => $urunId,
                    'varyant_deger_id' => (int)$dId,
                    'company_id'       => $companyId,
                ]);
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function assertVariantBelongsToCompany(int $varyantId): void
    {
        $row = $this->db->selectOne(
            "SELECT id FROM varyantlar WHERE id = :id AND company_id = :company_id LIMIT 1",
            [':id' => $varyantId, ':company_id' => TenantContext::activeCompanyId()]
        );
        if (!$row) {
            throw new RuntimeException('Varyant bu şirkete ait değil veya bulunamadı.');
        }
    }

    private function assertVariantValueBelongsToCompany(int $valueId): void
    {
        $row = $this->db->selectOne(
            "SELECT vd.id
             FROM varyant_degerleri vd
             JOIN varyantlar v ON v.id = vd.varyant_id
             WHERE vd.id = :id AND vd.company_id = :company_id AND v.company_id = :company_id
             LIMIT 1",
            [':id' => $valueId, ':company_id' => TenantContext::activeCompanyId()]
        );
        if (!$row) {
            throw new RuntimeException('Varyant değeri bu şirkete ait değil veya bulunamadı.');
        }
    }

    private function assertProductBelongsToCompany(int $urunId): void
    {
        $row = $this->db->selectOne(
            "SELECT id FROM urunler_hizmetler WHERE id = :id AND company_id = :company_id AND silindi_mi = 0 LIMIT 1",
            [':id' => $urunId, ':company_id' => TenantContext::activeCompanyId()]
        );
        if (!$row) {
            throw new RuntimeException('Ürün bu şirkete ait değil veya bulunamadı.');
        }
    }
}
