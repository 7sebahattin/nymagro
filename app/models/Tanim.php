<?php

class Tanim
{
    private Database $db;

    private array $types = [
        'urun_marka' => 'Ürün Markası',
        'urun_kategori' => 'Ürün Kategorisi',
        'musteri_sinif_1' => 'Müşteri Sınıflandırma 1',
        'musteri_sinif_2' => 'Müşteri Sınıflandırma 2',
        'tedarikci_sinif_1' => 'Tedarikçi Sınıflandırma 1',
        'tedarikci_sinif_2' => 'Tedarikçi Sınıflandırma 2',
        'fihrist_grup_1' => 'Fihrist Sınıflandırması 1',
        'fihrist_grup_2' => 'Fihrist Sınıflandırması 2',
        'raf_yeri' => 'Raf Yeri',
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->ensureTable();
        $this->seedFromExistingData();
    }

    public function types(): array
    {
        return $this->types;
    }

    public function grouped(): array
    {
        $rows = $this->db->select("SELECT * FROM tanimlar WHERE silindi_mi = 0 AND company_id = :cid ORDER BY tur, sira, ad", [':cid' => TenantContext::activeCompanyId()]);
        $grouped = array_fill_keys(array_keys($this->types), []);
        foreach ($rows as $row) {
            if (isset($grouped[$row['tur']])) {
                $grouped[$row['tur']][] = $row;
            }
        }
        return $grouped;
    }

    public function kaydet(array $data): int
    {
        $id = (int)($data['id'] ?? 0);
        $type = (string)($data['tur'] ?? '');
        $name = trim((string)($data['ad'] ?? ''));
        $color = (string)($data['renk'] ?? 'green');

        if (!isset($this->types[$type])) {
            throw new InvalidArgumentException('Geçersiz tanım türü.');
        }
        if ($name === '') {
            throw new InvalidArgumentException('Tanım adı boş olamaz.');
        }
        if (!in_array($color, ['green', 'cyan', 'red', 'grey'], true)) {
            $color = 'green';
        }

        if ($id > 0) {
            $this->db->update('tanimlar', ['tur' => $type, 'ad' => $name, 'renk' => $color], ['id' => $id]);
            return $id;
        }

        $exists = $this->db->selectOne(
            "SELECT id FROM tanimlar WHERE tur = :tur AND ad = :ad AND silindi_mi = 0 AND company_id = :cid LIMIT 1",
            [':tur' => $type, ':ad' => $name, ':cid' => TenantContext::activeCompanyId()]
        );
        if ($exists) {
            throw new RuntimeException('Bu tanım zaten mevcut.');
        }

        return $this->db->insert('tanimlar', ['tur' => $type, 'ad' => $name, 'renk' => $color, 'sira' => 0]);
    }

    public function sil(int $id): int
    {
        return $this->db->update('tanimlar', ['silindi_mi' => 1], ['id' => $id]);
    }

    private function ensureTable(): void
    {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS tanimlar (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                company_id INT UNSIGNED NULL,
                tur VARCHAR(60) NOT NULL,
                ad VARCHAR(180) NOT NULL,
                renk ENUM('green','cyan','red','grey') NOT NULL DEFAULT 'green',
                sira INT NOT NULL DEFAULT 0,
                olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                guncellenme_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                silindi_mi TINYINT(1) NOT NULL DEFAULT 0,
                PRIMARY KEY (id),
                KEY idx_tanimlar_tur (tur),
                KEY idx_tanimlar_silindi (silindi_mi),
                KEY idx_tanimlar_company (company_id),
                UNIQUE KEY uq_tanimlar_company_tur_ad (company_id, tur, ad, silindi_mi)
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci"
        );
    }

    private function seedFromExistingData(): void
    {
        $count = $this->db->selectOne("SELECT COUNT(*) AS n FROM tanimlar WHERE silindi_mi = 0 AND company_id = :cid", [':cid' => TenantContext::activeCompanyId()]);
        if ((int)($count['n'] ?? 0) > 0) {
            return;
        }

        if (defined('MUHASEBE_LEGACY_TANIM_SEED') && MUHASEBE_LEGACY_TANIM_SEED) {
        foreach ([
            ['urun_marka', 'CONEX', 'red'],
            ['urun_marka', 'IDEAPLANTECH', 'green'],
            ['urun_marka', 'Örnek marka', 'cyan'],
            ['urun_kategori', 'ETİKET', 'grey'],
            ['urun_kategori', 'Örnek kategori', 'green'],
            ['urun_kategori', 'TİCARİ MAL', 'cyan'],
        ] as [$type, $name, $color]) {
            $this->db->insert('tanimlar', ['tur' => $type, 'ad' => $name, 'renk' => $color]);
        }
        }

        foreach (['marka' => 'urun_marka', 'kategori' => 'urun_kategori'] as $column => $type) {
            $rows = $this->db->select(
                "SELECT DISTINCT {$column} AS ad
                 FROM urunler_hizmetler
                 WHERE silindi_mi = 0 AND company_id = :cid AND {$column} IS NOT NULL AND {$column} <> ''
                 ORDER BY {$column}"
                , [':cid' => TenantContext::activeCompanyId()]
            );
            foreach ($rows as $row) {
                $exists = $this->db->selectOne(
                    "SELECT id FROM tanimlar WHERE tur = :tur AND ad = :ad AND silindi_mi = 0 AND company_id = :cid LIMIT 1",
                    [':tur' => $type, ':ad' => $row['ad'], ':cid' => TenantContext::activeCompanyId()]
                );
                if (!$exists) {
                    $this->db->insert('tanimlar', ['tur' => $type, 'ad' => $row['ad'], 'renk' => 'green']);
                }
            }
        }
    }
}
