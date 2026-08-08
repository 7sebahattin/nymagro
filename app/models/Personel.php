<?php

class Personel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->ensureTables();
    }

    public function listele(): array
    {
        return $this->db->select(
            "SELECT p.*,
                    COALESCE(SUM(CASE WHEN ph.tip IN ('maas','prim','ek_odeme','odeme') AND ph.odeme_durumu = 'odendi' AND ph.silindi_mi = 0 THEN ph.tutar ELSE 0 END), 0) AS toplam_odeme,
                    COALESCE(SUM(CASE WHEN ph.tip IN ('avans','kesinti') AND ph.odeme_durumu <> 'iptal' AND ph.silindi_mi = 0 THEN ph.tutar ELSE 0 END), 0) AS toplam_kesinti_avans,
                    MAX(CASE WHEN ph.odeme_durumu = 'odendi' AND ph.silindi_mi = 0 THEN ph.tarih ELSE NULL END) AS son_odeme_tarihi
             FROM personeller p
             LEFT JOIN personel_hareketleri ph ON ph.personel_id = p.id AND ph.company_id = :cid AND ph.period_id = :pid
             WHERE p.silindi_mi = 0 AND p.company_id = :cid
             GROUP BY p.id
             ORDER BY p.ad",
            [':cid' => TenantContext::activeCompanyId(), ':pid' => TenantContext::activePeriodId()]
        );
    }

    public function ekle(array $data): int
    {
        $ad = trim((string)($data['ad'] ?? ''));
        if ($ad === '') {
            throw new InvalidArgumentException('Personel adı boş olamaz.');
        }

        return $this->db->insert('personeller', [
            'ad' => $ad,
            'gorev' => trim((string)($data['gorev'] ?? '')) ?: null,
            'departman' => trim((string)($data['departman'] ?? '')) ?: null,
            'maas' => (float)($data['maas'] ?? 0),
            'aktif_mi' => !empty($data['aktif_mi']) ? 1 : 0,
            'notlar' => trim((string)($data['notlar'] ?? '')) ?: null,
        ]);
    }

    public function sil(int $id): int
    {
        return $this->db->update('personeller', ['silindi_mi' => 1], ['id' => $id]);
    }

    public function hareketEkle(array $data): int
    {
        $personelId = (int)($data['personel_id'] ?? 0);
        $tip = (string)($data['tip'] ?? '');
        $allowed = ['maas', 'sgk', 'sigorta', 'yemek', 'yol', 'prim', 'ek_odeme', 'avans', 'kesinti', 'odeme'];

        if ($personelId <= 0) {
            throw new InvalidArgumentException('Personel seçilmedi.');
        }
        if (!in_array($tip, $allowed, true)) {
            throw new InvalidArgumentException('Geçersiz hareket tipi.');
        }

        $tutar = (float)($data['tutar'] ?? 0);
        if ($tutar <= 0) {
            throw new InvalidArgumentException('Tutar sıfırdan büyük olmalı.');
        }

        $tarih = $this->validDate($data['tarih'] ?? '') ?: date('Y-m-d');
        $durum = in_array(($data['odeme_durumu'] ?? ''), ['bekliyor', 'odendi', 'iptal'], true) ? $data['odeme_durumu'] : 'bekliyor';
        $kasaId = !empty($data['kasa_id']) ? (int)$data['kasa_id'] : null;
        $aciklama = trim((string)($data['aciklama'] ?? '')) ?: $this->tipLabel($tip);
        $belgeNo = trim((string)($data['belge_no'] ?? '')) ?: null;

        $this->db->begin();
        try {
            $masrafId = $this->db->insert('masraflar', [
                'kategori_id' => $this->personelKategoriId($tip),
                'kasa_id' => $kasaId,
                'personel_id' => $personelId,
                'islem_tarihi' => $tarih,
                'vade_tarihi' => $durum === 'bekliyor' ? $tarih : null,
                'belge_no' => $belgeNo,
                'aciklama' => $aciklama,
                'tutar' => $tutar,
                'kdv_orani' => 0,
                'kdv_tutari' => 0,
                'para_birimi' => 'TRY',
                'odeme_durumu' => $durum === 'odendi' ? 'odendi' : 'bekliyor',
                'odeme_tarihi' => $durum === 'odendi' ? $tarih : null,
                'notlar' => 'Personel modülünden oluşturuldu.',
            ]);

            $id = $this->db->insert('personel_hareketleri', [
                'personel_id' => $personelId,
                'masraf_id' => $masrafId,
                'kasa_id' => $kasaId,
                'tarih' => $tarih,
                'tip' => $tip,
                'tutar' => $tutar,
                'aciklama' => $aciklama . ' [masraf#' . $masrafId . ']',
                'odeme_durumu' => $durum,
                'belge_no' => $belgeNo,
                'para_birimi' => 'TRY',
            ]);

            $this->db->update('masraflar', ['personel_hareket_id' => $id], ['id' => $masrafId]);

            if ($durum === 'odendi' && $kasaId) {
                $this->db->insert('kasa_hareketleri', [
                    'kasa_id' => $kasaId,
                    'kasa_banka_id' => $kasaId,
                    'islem_tipi' => 'cikis',
                    'hareket_tipi' => 'gider',
                    'tutar' => $tutar,
                    'tarih' => $tarih . ' 00:00:00',
                    'aciklama' => $aciklama . ' [masraf#' . $masrafId . ']',
                    'para_birimi' => 'TRY',
                ]);
                $this->db->query(
                    "UPDATE kasa_banka SET guncel_bakiye = guncel_bakiye - :tutar WHERE id = :id AND company_id = :cid",
                    [':tutar' => $tutar, ':id' => $kasaId, ':cid' => TenantContext::activeCompanyId()]
                );
            }

            $this->db->commit();
            return $id;
        } catch (Throwable $e) {
            if ($this->db->pdo()->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function hareketSil(int $id): int
    {
        $row = $this->db->selectOne("SELECT masraf_id FROM personel_hareketleri WHERE id = :id AND company_id = :cid", [':id' => $id, ':cid' => TenantContext::activeCompanyId()]);
        $count = $this->db->update('personel_hareketleri', ['silindi_mi' => 1], ['id' => $id]);
        if (!empty($row['masraf_id'])) {
            $this->db->update('masraflar', ['silindi_mi' => 1], ['id' => (int)$row['masraf_id']]);
        }
        return $count;
    }

    public function hesaplar(): array
    {
        return $this->db->select("SELECT id, tip, hesap_adi FROM kasa_banka WHERE silindi_mi = 0 AND company_id = :cid ORDER BY tip, hesap_adi", [':cid' => TenantContext::activeCompanyId()]);
    }

    public function sonHareketler(int $limit = 20): array
    {
        return $this->db->select(
            "SELECT ph.*, p.ad AS personel_adi, kb.hesap_adi
             FROM personel_hareketleri ph
             JOIN personeller p ON p.id = ph.personel_id
             LEFT JOIN kasa_banka kb ON kb.id = ph.kasa_id
             WHERE ph.silindi_mi = 0 AND ph.company_id = :cid AND ph.period_id = :pid
             ORDER BY ph.tarih DESC, ph.id DESC
             LIMIT :limit",
            [':limit' => $limit, ':cid' => TenantContext::activeCompanyId(), ':pid' => TenantContext::activePeriodId()]
        );
    }

    private function ensureTables(): void
    {
        $exists = $this->db->selectOne(
            "SELECT COUNT(*) AS n
             FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name IN ('personeller','personel_hareketleri')"
        );

        if ((int)($exists['n'] ?? 0) < 2) {
            $sqlFile = ROOT_PATH . DIRECTORY_SEPARATOR . 'database_personel_finans.sql';
            if (is_file($sqlFile)) {
                $this->db->pdo()->exec(file_get_contents($sqlFile));
            }
        }

        $this->ensureMovementEnum();
        $this->ensureLinks();
    }

    private function ensureMovementEnum(): void
    {
        $column = $this->db->selectOne("SHOW COLUMNS FROM personel_hareketleri LIKE 'tip'");
        $type = (string)($column['Type'] ?? '');
        if ($type !== '' && !str_contains($type, "'sgk'")) {
            $this->db->query(
                "ALTER TABLE personel_hareketleri
                 MODIFY tip ENUM('maas','sgk','sigorta','yemek','yol','prim','ek_odeme','avans','kesinti','odeme') NOT NULL"
            );
        }
    }

    private function validDate(string $date): string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : '';
    }

    private function ensureLinks(): void
    {
        $this->addColumnIfMissing('personel_hareketleri', 'masraf_id', 'INT NULL AFTER personel_id');
        $this->addColumnIfMissing('personel_hareketleri', 'belge_no', 'VARCHAR(80) NULL AFTER odeme_durumu');
        $this->addColumnIfMissing('personel_hareketleri', 'para_birimi', "VARCHAR(5) NOT NULL DEFAULT 'TRY' AFTER belge_no");
        $this->addColumnIfMissing('masraflar', 'personel_id', 'INT UNSIGNED NULL AFTER kasa_id');
        $this->addColumnIfMissing('masraflar', 'personel_hareket_id', 'INT UNSIGNED NULL AFTER personel_id');
        $this->addColumnIfMissing('masraflar', 'notlar', 'VARCHAR(255) NULL AFTER tekrar_periyot');
    }

    private function addColumnIfMissing(string $table, string $column, string $definition): void
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $table) || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $column)) {
            throw new InvalidArgumentException('Geçersiz tablo veya kolon adı.');
        }
        $row = $this->db->selectOne("SHOW COLUMNS FROM {$table} LIKE " . $this->db->pdo()->quote($column));
        if (!$row) {
            $this->db->query("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
        }
    }

    private function personelKategoriId(string $tip): int
    {
        $label = $this->tipLabel($tip);
        $companyId = TenantContext::activeCompanyId();
        $parent = $this->db->selectOne(
            "SELECT id FROM masraf_kategoriler WHERE silindi_mi = 0 AND company_id = :cid AND UPPER(ad) LIKE '%PERSONEL%' AND parent_id IS NULL ORDER BY id LIMIT 1",
            [':cid' => $companyId]
        );
        if (!$parent) {
            $parentId = $this->db->insert('masraf_kategoriler', ['parent_id' => null, 'ad' => 'PERSONEL GIDERLERI', 'renk' => '#5bc0de', 'sira' => 0]);
        } else {
            $parentId = (int)$parent['id'];
        }

        $category = $this->db->selectOne(
            "SELECT id FROM masraf_kategoriler WHERE silindi_mi = 0 AND company_id = :cid AND parent_id = :pid AND ad = :ad LIMIT 1",
            [':cid' => $companyId, ':pid' => $parentId, ':ad' => $label]
        );
        if ($category) {
            return (int)$category['id'];
        }

        return $this->db->insert('masraf_kategoriler', [
            'parent_id' => $parentId,
            'ad' => $label,
            'renk' => '#5bc0de',
            'sira' => 0,
        ]);
    }

    private function tipLabel(string $tip): string
    {
        return [
            'maas' => 'Maaş',
            'sgk' => 'SGK',
            'sigorta' => 'Sigorta',
            'yemek' => 'Yemek',
            'yol' => 'Yol',
            'prim' => 'Prim',
            'ek_odeme' => 'Ek ödeme',
            'avans' => 'Avans',
            'kesinti' => 'Kesinti',
            'odeme' => 'Diğer personel ödemesi',
        ][$tip] ?? 'Diğer personel ödemesi';
    }
}
