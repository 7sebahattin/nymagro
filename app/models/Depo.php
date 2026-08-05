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

    public function sil(int $id): int
    {
        return $this->db->softDelete('depolar', $id);
    }

    /** Depodaki ürün stok durumunu ve değerlerini getirir */
    public function stokDurumu(int $depoId): array
    {
        $sql = "SELECT u.id as urun_id, u.ad as urun_adi, u.stok_kodu, u.marka, u.birim, u.alis_fiyati, usd.miktar 
                FROM urun_stok_depo usd
                JOIN urunler_hizmetler u ON usd.urun_id = u.id
                WHERE usd.depo_id = :depo_id AND u.silindi_mi = 0
                ORDER BY u.ad ASC";
        return $this->db->select($sql, [':depo_id' => $depoId]);
    }

    /** Depodaki toplam stok değerini hesaplar */
    public function depoDegeri(int $depoId): float
    {
        $sql = "SELECT SUM(usd.miktar * u.alis_fiyati) as toplam_deger
                FROM urun_stok_depo usd
                JOIN urunler_hizmetler u ON usd.urun_id = u.id
                WHERE usd.depo_id = :depo_id AND u.silindi_mi = 0";
        $row = $this->db->selectOne($sql, [':depo_id' => $depoId]);
        return (float)($row['toplam_deger'] ?? 0);
    }
}
