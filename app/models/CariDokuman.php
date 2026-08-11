<?php

final class CariDokuman
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->ensureTable();
    }

    public function cariGetir(int $cariId, string $tip): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM cariler
             WHERE id = :id
               AND tip IN (:tip, 'her_ikisi')
               AND silindi_mi = 0
               AND company_id = :cid",
            [':id' => $cariId, ':tip' => $tip, ':cid' => TenantContext::activeCompanyId()]
        );
    }

    public function listele(int $cariId): array
    {
        return $this->db->select(
            "SELECT * FROM cari_dokumanlar
             WHERE cari_id = :cid
               AND silindi_mi = 0
               AND company_id = :company_id
             ORDER BY created_at DESC, id DESC",
            [':cid' => $cariId, ':company_id' => TenantContext::activeCompanyId()]
        );
    }

    public function ekle(array $data): int
    {
        $veri = [
            'cari_id' => (int)$data['cari_id'],
            'baslik' => (string)($data['baslik'] ?? ''),
            'aciklama' => (string)($data['aciklama'] ?? ''),
            'orijinal_ad' => (string)$data['orijinal_ad'],
            'dosya_ad' => (string)$data['dosya_ad'],
            'dosya_yolu' => (string)$data['dosya_yolu'],
            'mime_type' => (string)($data['mime_type'] ?? ''),
            'boyut' => (int)($data['boyut'] ?? 0),
            'created_by' => AuthGuard::userId(),
        ];
        $id = $this->db->insert('cari_dokumanlar', $veri);
        Audit::log('CREATE', 'DOKUMAN', $id, null, $veri, 'Döküman yüklendi: ' . ($veri['orijinal_ad'] ?? ''));
        return $id;
    }

    public function getir(int $id): ?array
    {
        return $this->db->selectOne(
            "SELECT d.*, c.tip AS cari_tip, c.unvan AS cari_unvan
             FROM cari_dokumanlar d
             JOIN cariler c ON c.id = d.cari_id
             WHERE d.id = :id
               AND d.silindi_mi = 0
               AND d.company_id = :cid",
            [':id' => $id, ':cid' => TenantContext::activeCompanyId()]
        );
    }

    public function sil(int $id): int
    {
        $before = $this->getir($id);
        $sonuc = $this->db->softDelete('cari_dokumanlar', $id);
        Audit::log('DELETE', 'DOKUMAN', $id, $before, null, 'Döküman silindi: ' . ($before['orijinal_ad'] ?? ''));
        return $sonuc;
    }

    private function ensureTable(): void
    {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS cari_dokumanlar (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                company_id INT UNSIGNED NULL,
                cari_id INT UNSIGNED NOT NULL,
                baslik VARCHAR(180) NULL,
                aciklama TEXT NULL,
                orijinal_ad VARCHAR(255) NOT NULL,
                dosya_ad VARCHAR(255) NOT NULL,
                dosya_yolu VARCHAR(500) NOT NULL,
                mime_type VARCHAR(120) NULL,
                boyut INT UNSIGNED NOT NULL DEFAULT 0,
                created_by INT UNSIGNED NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                silindi_mi TINYINT(1) NOT NULL DEFAULT 0,
                PRIMARY KEY (id),
                KEY idx_cari_dokumanlar_company (company_id),
                KEY idx_cari_dokumanlar_cari (cari_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci"
        );
    }
}
