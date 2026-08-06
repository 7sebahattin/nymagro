<?php
/**
 * Model: Fihrist
 * --------------------------------------------------------
 * Müşteri/tedarikçi dışındaki serbest kart kayıtları (muhasebeci,
 * banka şubesi, esnaf vs.) için basit adres defteri.
 */

class Fihrist
{
    private Database $db;

    private array $fillable = [
        'unvan', 'siniflandirma_1', 'siniflandirma_2', 'yetkili',
        'eposta', 'telefon1', 'telefon2', 'adres', 'notlar',
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->ensureSchema();
    }

    public function listele(): array
    {
        return $this->db->select(
            "SELECT * FROM fihrist_kartlari
             WHERE silindi_mi = 0 AND company_id = :cid AND period_id = :pid
             ORDER BY unvan ASC",
            $this->tenantParams()
        );
    }

    public function getir(int $id): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM fihrist_kartlari WHERE id = :id AND silindi_mi = 0 AND company_id = :cid AND period_id = :pid",
            array_merge([':id' => $id], $this->tenantParams())
        );
    }

    public function kaydet(array $data): int
    {
        $unvan = trim((string)($data['unvan'] ?? ''));
        if ($unvan === '') {
            throw new InvalidArgumentException('İsim / Unvan boş olamaz.');
        }

        $temiz = array_intersect_key($data, array_flip($this->fillable));
        $temiz = array_map(fn($v) => is_string($v) ? trim($v) : $v, $temiz);
        $temiz['unvan'] = $unvan;

        $id = (int)($data['id'] ?? 0);
        if ($id > 0) {
            $this->db->update('fihrist_kartlari', $temiz, ['id' => $id]);
            return $id;
        }

        return $this->db->insert('fihrist_kartlari', $temiz);
    }

    public function sil(int $id): int
    {
        return $this->db->update('fihrist_kartlari', ['silindi_mi' => 1], ['id' => $id]);
    }

    private function tenantParams(): array
    {
        return [
            ':cid' => TenantContext::activeCompanyId(),
            ':pid' => TenantContext::activePeriodId(),
        ];
    }

    private function ensureSchema(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS fihrist_kartlari (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id INT UNSIGNED NULL,
            period_id INT UNSIGNED NULL,
            unvan VARCHAR(180) NOT NULL,
            siniflandirma_1 VARCHAR(120) NULL,
            siniflandirma_2 VARCHAR(120) NULL,
            yetkili VARCHAR(120) NULL,
            eposta VARCHAR(150) NULL,
            telefon1 VARCHAR(40) NULL,
            telefon2 VARCHAR(40) NULL,
            adres TEXT NULL,
            notlar TEXT NULL,
            silindi_mi TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_fihrist_tenant (company_id, period_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");
    }
}
