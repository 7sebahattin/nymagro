<?php
/**
 * Model: Depo
 * --------------------------------------------------------
 * depolar ve urun_stok_depo tabloları üzerinde işlemler.
 */

class Depo
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function listele(): array
    {
        return $this->db->select("SELECT * FROM depolar WHERE silindi_mi = 0 AND company_id = :cid ORDER BY ad ASC", [':cid' => TenantContext::activeCompanyId()]);
    }

    public function getir(int $id): ?array
    {
        return $this->db->selectOne("SELECT * FROM depolar WHERE id = :id AND silindi_mi = 0 AND company_id = :cid", [':id' => $id, ':cid' => TenantContext::activeCompanyId()]);
    }

    public function ekle(array $veri): int
    {
        return $this->db->insert('depolar', $veri);
    }

    public function guncelle(int $id, array $veri): int
    {
        return $this->db->update('depolar', $veri, ['id' => $id]);
    }

    /** Depoyu siler — içinde stok değeri olan bir depo silinemez. */
    public function sil(int $id): int
    {
        if ($this->depoDegeri($id) > 0) {
            throw new RuntimeException('İçinde stok olan depo silinemez.');
        }
        return $this->db->softDelete('depolar', $id);
    }

    /** Depodaki ürün stok durumunu ve değerlerini getirir */
    public function stokDurumu(int $depoId): array
    {
        $sql = "SELECT u.id as urun_id, u.ad as urun_adi, u.stok_kodu, u.marka, u.birim, u.alis_fiyati, usd.miktar
                FROM urun_stok_depo usd
                JOIN urunler_hizmetler u ON usd.urun_id = u.id
                WHERE usd.depo_id = :depo_id AND u.silindi_mi = 0 AND u.company_id = :cid
                ORDER BY u.ad ASC";
        return $this->db->select($sql, [':depo_id' => $depoId, ':cid' => TenantContext::activeCompanyId()]);
    }

    /** Depodaki toplam stok değerini hesaplar */
    public function depoDegeri(int $depoId): float
    {
        $sql = "SELECT SUM(usd.miktar * u.alis_fiyati) as toplam_deger
                FROM urun_stok_depo usd
                JOIN urunler_hizmetler u ON usd.urun_id = u.id
                WHERE usd.depo_id = :depo_id AND u.silindi_mi = 0 AND u.company_id = :cid";
        $row = $this->db->selectOne($sql, [':depo_id' => $depoId, ':cid' => TenantContext::activeCompanyId()]);
        return (float)($row['toplam_deger'] ?? 0);
    }

    /** Bir ürünün bu depodaki mevcut stok miktarı (stok sayımı farkı hesaplamak için). */
    public function urunStokMiktari(int $urunId, int $depoId): float
    {
        $row = $this->db->selectOne(
            "SELECT usd.miktar FROM urun_stok_depo usd
             JOIN urunler_hizmetler u ON u.id = usd.urun_id
             WHERE usd.urun_id = :uid AND usd.depo_id = :did AND u.company_id = :cid",
            [':uid' => $urunId, ':did' => $depoId, ':cid' => TenantContext::activeCompanyId()]
        );
        return (float)($row['miktar'] ?? 0);
    }
}
