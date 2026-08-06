<?php
/**
 * Masraf Model
 * ────────────────────────────────────────────────────────
 * masraflar          — gider kayıtları
 * masraf_kategoriler — ana/alt kategori ağacı
 *
 * Sütunlar (masraflar):
 *   id, kategori_id, kasa_id, islem_tarihi, vade_tarihi,
 *   belge_no, aciklama, tutar, kdv_orani, kdv_tutari,
 *   para_birimi, odeme_durumu(bekliyor|odendi|gecikti),
 *   odeme_tarihi, tekrarlayan, tekrar_periyot,
 *   notlar, silindi_mi, olusturulma_tarihi, guncelleme_tarihi
 *
 * Sütunlar (masraf_kategoriler):
 *   id, parent_id, ad, renk, sira, silindi_mi, olusturulma_tarihi
 */
class Masraf
{
    private Database $db;

    private array $fillable = [
        'kategori_id', 'kasa_id', 'personel_id', 'personel_hareket_id', 'islem_tarihi', 'vade_tarihi',
        'belge_no', 'aciklama', 'tutar', 'kdv_orani', 'kdv_tutari',
        'para_birimi', 'odeme_durumu', 'odeme_tarihi',
        'tekrarlayan', 'tekrar_periyot', 'notlar',
    ];

    private array $katFillable = ['parent_id', 'ad', 'renk', 'sira'];

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->ensurePersonelLinks();
    }

    // ──────────────────────────────────────────────────────
    // MASRAF LİSTELEME
    // ──────────────────────────────────────────────────────

    /**
     * Sayfalı, filtrelenmiş masraf listesi.
     *
     * @param string $durum   '' | 'bekliyor' | 'odendi' | 'gecikti'
     * @param string $arama   açıklama / belge no araması
     * @param string $period  '1ay' | '3ay' | '6ay' | '1yil' | '' (tümü)
     * @param int    $limit
     * @param int    $offset
     */
    public function listele(
        string $durum  = '',
        string $arama  = '',
        string $period = '6ay',
        int    $limit  = 50,
        int    $offset = 0
    ): array {
        [$where, $params] = $this->buildWhere($durum, $arama, $period);

        $params[':lim'] = $limit;
        $params[':off'] = $offset;

        return $this->db->select(
            "SELECT
                m.*,
                mk.ad        AS kategori_adi,
                mk.renk      AS kategori_renk,
                pa.ad        AS ana_kategori_adi,
                kb.hesap_adi AS hesap_adi
             FROM masraflar m
             LEFT JOIN masraf_kategoriler mk ON mk.id = m.kategori_id
             LEFT JOIN masraf_kategoriler pa ON pa.id = mk.parent_id
             LEFT JOIN kasa_banka         kb ON kb.id = m.kasa_id
             WHERE {$where}
             ORDER BY m.islem_tarihi DESC, m.id DESC
             LIMIT :lim OFFSET :off",
            $params
        );
    }

    public function say(
        string $durum  = '',
        string $arama  = '',
        string $period = '6ay'
    ): int {
        [$where, $params] = $this->buildWhere($durum, $arama, $period);
        $row = $this->db->selectOne(
            "SELECT COUNT(*) AS c FROM masraflar m
             LEFT JOIN masraf_kategoriler mk ON mk.id = m.kategori_id
             WHERE {$where}",
            $params
        );
        return (int)($row['c'] ?? 0);
    }

    /** Özet kutular: toplam tutar + durum bazlı */
    public function ozetler(string $period = '6ay'): array
    {
        [$where, $params] = $this->buildWhere('', '', $period);

        return $this->db->selectOne(
            "SELECT
                COUNT(*) AS toplam_adet,
                COALESCE(SUM(tutar + kdv_tutari), 0) AS toplam_tutar,
                COALESCE(SUM(CASE WHEN odeme_durumu = 'odendi'   THEN tutar + kdv_tutari ELSE 0 END), 0) AS odendi_tutar,
                COALESCE(SUM(CASE WHEN odeme_durumu = 'bekliyor' THEN tutar + kdv_tutari ELSE 0 END), 0) AS bekliyor_tutar,
                COALESCE(SUM(CASE WHEN odeme_durumu = 'gecikti'  THEN tutar + kdv_tutari ELSE 0 END), 0) AS gecikti_tutar,
                COUNT(CASE WHEN odeme_durumu = 'bekliyor' THEN 1 END) AS bekliyor_adet,
                COUNT(CASE WHEN odeme_durumu = 'gecikti'  THEN 1 END) AS gecikti_adet
             FROM masraflar m
             LEFT JOIN masraf_kategoriler mk ON mk.id = m.kategori_id
             WHERE {$where}",
            $params
        ) ?? [];
    }

    // ──────────────────────────────────────────────────────
    // TEKİL MASRAF
    // ──────────────────────────────────────────────────────

    public function getir(int $id): ?array
    {
        return $this->db->selectOne(
            "SELECT m.*, mk.ad AS kategori_adi, mk.renk AS kategori_renk,
                    pa.ad AS ana_kategori_adi, kb.hesap_adi
             FROM masraflar m
             LEFT JOIN masraf_kategoriler mk ON mk.id = m.kategori_id AND mk.company_id = m.company_id
             LEFT JOIN masraf_kategoriler pa ON pa.id = mk.parent_id   AND pa.company_id = m.company_id
             LEFT JOIN kasa_banka         kb ON kb.id = m.kasa_id      AND kb.company_id = m.company_id
             WHERE m.id = :id AND m.silindi_mi = 0
               AND m.company_id = :cid AND m.period_id = :pid",
            [':id' => $id, ':cid' => TenantContext::activeCompanyId(), ':pid' => TenantContext::activePeriodId()]
        );
    }

    // ──────────────────────────────────────────────────────
    // MASRAF CRUD
    // ──────────────────────────────────────────────────────

    public function ekle(array $veri): int
    {
        $personelTip = $this->normalizePersonelTip($veri['personel_hareket_tipi'] ?? '');
        $temiz = array_intersect_key($veri, array_flip($this->fillable));

        if (empty($temiz['islem_tarihi'])) {
            $temiz['islem_tarihi'] = date('Y-m-d');
        }
        if (empty($temiz['kategori_id'])) {
            throw new InvalidArgumentException('Masraf kalemi seçilmedi.');
        }

        // KDV tutarını hesapla
        $tutar    = (float)($temiz['tutar']     ?? 0);
        $kdvOrani = (float)($temiz['kdv_orani'] ?? 0);
        $temiz['kdv_tutari'] = round($tutar * $kdvOrani / (100 + $kdvOrani), 2);

        // Boş opsiyonelleri NULL yap
        foreach (['kasa_id','vade_tarihi','belge_no','odeme_tarihi','tekrar_periyot'] as $k) {
            if (isset($temiz[$k]) && (string)$temiz[$k] === '') {
                $temiz[$k] = null;
            }
        }

        $temiz['para_birimi']   = $temiz['para_birimi']   ?? 'TRY';
        $temiz['odeme_durumu']  = $temiz['odeme_durumu']  ?? 'bekliyor';

        $id = $this->db->insert('masraflar', $temiz);

        if (!empty($temiz['personel_id'])) {
            $hareketId = $this->personelHareketiEkle($id, $temiz, $personelTip);
            $this->db->update('masraflar', ['personel_hareket_id' => $hareketId], ['id' => $id]);
        }

        // Eğer ödendi ise ve kasa_id varsa → kasa_hareketleri'ne ekle
        if (!empty($temiz['kasa_id']) && $temiz['odeme_durumu'] === 'odendi') {
            $this->kasaHareketiEkle($id, $temiz);
        }

        return $id;
    }

    public function guncelle(int $id, array $veri): int
    {
        $personelTip = $this->normalizePersonelTip($veri['personel_hareket_tipi'] ?? '');
        $temiz = array_intersect_key($veri, array_flip($this->fillable));
        if (empty($temiz)) return 0;

        $mevcut = $this->getir($id);

        // KDV yeniden hesapla
        if (isset($temiz['tutar']) || isset($temiz['kdv_orani'])) {
            $tutar    = (float)($temiz['tutar']     ?? $mevcut['tutar']     ?? 0);
            $kdvOrani = (float)($temiz['kdv_orani'] ?? $mevcut['kdv_orani'] ?? 0);
            $temiz['kdv_tutari'] = round($tutar * $kdvOrani / (100 + $kdvOrani), 2);
        }

        foreach (['kasa_id','vade_tarihi','belge_no','odeme_tarihi','tekrar_periyot'] as $k) {
            if (isset($temiz[$k]) && (string)$temiz[$k] === '') {
                $temiz[$k] = null;
            }
        }

        $rows = $this->db->update('masraflar', $temiz, ['id' => $id]);
        $this->syncPersonelHareketi($id, $personelTip);

        // Düzenleme formundan "Ödendi" durumuna geçildiyse, ekle()/odemeYap()
        // ile aynı şekilde kasa hareketi oluştur. kasaHareketiEkle() zaten
        // tekrar kayıt oluşturmayı engelleyen bir güvenlik kontrolü içeriyor.
        $guncel = array_merge($mevcut ?: [], $temiz);
        if (($guncel['odeme_durumu'] ?? '') === 'odendi' && !empty($guncel['kasa_id'])) {
            $this->kasaHareketiEkle($id, $guncel);
        }

        return $rows;
    }

    public function sil(int $id): int
    {
        $masraf = $this->getir($id);
        $rows = $this->db->update('masraflar', ['silindi_mi' => 1], ['id' => $id]);
        if (!empty($masraf['personel_hareket_id'])) {
            $this->db->update('personel_hareketleri', ['silindi_mi' => 1], ['id' => (int)$masraf['personel_hareket_id']]);
        }
        return $rows;
    }

    /** Masrafı ödendi olarak işaretle */
    public function odemeYap(int $id, int $kasaId = 0, string $tarih = ''): int
    {
        $masraf = $this->getir($id);
        if (!$masraf) return 0;

        $tarih = $tarih ?: date('Y-m-d');
        $data  = ['odeme_durumu' => 'odendi', 'odeme_tarihi' => $tarih];
        if ($kasaId > 0) {
            $data['kasa_id'] = $kasaId;
        }

        $rows = $this->db->update('masraflar', $data, ['id' => $id]);
        if (!empty($masraf['personel_hareket_id'])) {
            $this->db->update('personel_hareketleri', [
                'odeme_durumu' => 'odendi',
                'tarih' => $tarih,
                'kasa_id' => $kasaId > 0 ? $kasaId : ($masraf['kasa_id'] ?: null),
            ], ['id' => (int)$masraf['personel_hareket_id']]);
        }

        // Kasa hareketi oluştur
        if ($kasaId > 0 || !empty($masraf['kasa_id'])) {
            $hedefKasa = $kasaId ?: (int)$masraf['kasa_id'];
            $this->kasaHareketiEkle($id, array_merge($masraf, $data, ['kasa_id' => $hedefKasa]));
        }

        return $rows;
    }

    // ──────────────────────────────────────────────────────
    // KATEGORİ İŞLEMLERİ
    // ──────────────────────────────────────────────────────

    /** Tüm kategoriler (ağaç yapısı: ana → alt) */
    public function kategoriler(): array
    {
        $tum = $this->db->select(
            "SELECT * FROM masraf_kategoriler WHERE silindi_mi = 0 AND company_id = :cid ORDER BY sira ASC, ad ASC",
            [':cid' => TenantContext::activeCompanyId()]
        );

        // Ağaç yap
        $analar = [];
        $altlar = [];
        foreach ($tum as $k) {
            if ($k['parent_id'] === null) {
                $analar[$k['id']] = $k;
            } else {
                $altlar[(int)$k['parent_id']][] = $k;
            }
        }
        foreach ($analar as &$a) {
            $a['altlar'] = $altlar[$a['id']] ?? [];
        }
        return array_values($analar);
    }

    /** Sadece alt kategoriler (form select için, parent_id ile gruplu) */
    public function altKategorilerFlat(): array
    {
        return $this->db->select(
            "SELECT mk.id, mk.ad, mk.renk, pa.ad AS ana_adi
             FROM masraf_kategoriler mk
             LEFT JOIN masraf_kategoriler pa ON pa.id = mk.parent_id
             WHERE mk.silindi_mi = 0 AND mk.parent_id IS NOT NULL AND mk.company_id = :cid
             ORDER BY pa.sira ASC, mk.sira ASC, mk.ad ASC",
            [':cid' => TenantContext::activeCompanyId()]
        );
    }

    public function kategoriEkle(array $veri): int
    {
        $temiz = array_intersect_key($veri, array_flip($this->katFillable));
        if (empty($temiz['ad'])) throw new InvalidArgumentException('Kategori adı boş olamaz.');
        $temiz['renk'] = $temiz['renk'] ?? '#5bc0de';
        // parent_id boşsa NULL yap
        if (isset($temiz['parent_id']) && (string)$temiz['parent_id'] === '') {
            $temiz['parent_id'] = null;
        }
        return $this->db->insert('masraf_kategoriler', $temiz);
    }

    public function kategoriGuncelle(int $id, array $veri): int
    {
        $temiz = array_intersect_key($veri, array_flip($this->katFillable));
        if (empty($temiz)) return 0;
        return $this->db->update('masraf_kategoriler', $temiz, ['id' => $id]);
    }

    public function kategoriSil(int $id): bool
    {
        // Alt kategorileri olan bir ana kategoriyi silme
        $altSay = $this->db->selectOne(
            "SELECT COUNT(*) AS c FROM masraf_kategoriler WHERE parent_id = :id AND silindi_mi = 0 AND company_id = :cid",
            [':id' => $id, ':cid' => TenantContext::activeCompanyId()]
        );
        if ((int)($altSay['c'] ?? 0) > 0) {
            throw new RuntimeException('Bu kategorinin alt kalemleri mevcut. Önce alt kalemleri silin.');
        }
        // Bu kategoriye bağlı masraf var mı?
        $masrafSay = $this->db->selectOne(
            "SELECT COUNT(*) AS c FROM masraflar WHERE kategori_id = :id AND silindi_mi = 0 AND company_id = :cid",
            [':id' => $id, ':cid' => TenantContext::activeCompanyId()]
        );
        if ((int)($masrafSay['c'] ?? 0) > 0) {
            throw new RuntimeException('Bu kategoriye kayıtlı masraflar var, silinemez.');
        }
        $this->db->update('masraf_kategoriler', ['silindi_mi' => 1], ['id' => $id]);
        return true;
    }

    // ──────────────────────────────────────────────────────
    // PRIVATE YARDIMCILAR
    // ──────────────────────────────────────────────────────

    private function buildWhere(string $durum, string $arama, string $period): array
    {
        $where  = ['m.silindi_mi = 0', 'm.company_id = :company_id', 'm.period_id = :period_id'];
        $params = [':company_id' => TenantContext::activeCompanyId(), ':period_id' => TenantContext::activePeriodId()];

        if ($durum !== '') {
            // 'gecikti': kendi durumu gecikti VEYA bekliyor + vadesi geçmiş
            if ($durum === 'gecikti') {
                $where[] = "(m.odeme_durumu = 'gecikti' OR (m.odeme_durumu = 'bekliyor' AND m.vade_tarihi < CURDATE()))";
            } else {
                $where[]         = "m.odeme_durumu = :durum";
                $params[':durum'] = $durum;
            }
        }

        if ($arama !== '') {
            $like               = '%' . $arama . '%';
            $where[]            = "(m.aciklama LIKE :ara1 OR m.belge_no LIKE :ara2 OR mk.ad LIKE :ara3)";
            $params[':ara1']    = $like;
            $params[':ara2']    = $like;
            $params[':ara3']    = $like;
        }

        if ($period !== '' && $period !== 'tum') {
            $map = ['1ay' => '-1 MONTH', '3ay' => '-3 MONTH', '6ay' => '-6 MONTH', '1yil' => '-12 MONTH'];
            if (isset($map[$period])) {
                $where[] = "m.islem_tarihi >= DATE_ADD(CURDATE(), INTERVAL {$map[$period]})";
            }
        }

        return [implode(' AND ', $where), $params];
    }

    // ──────────────────────────────────────────────────────
    // BELGE İŞLEMLERİ
    // ──────────────────────────────────────────────────────

    public function belgeEkle(array $veri): int
    {
        $this->ensureBelgelerTable();
        // Önce belgenin bağlandığı masrafın aktif şirkette olduğunu doğrula
        $masraf = $this->getir((int)$veri['masraf_id']);
        if (!$masraf) {
            throw new RuntimeException('Belge eklenemez: Masraf bulunamadı veya bu şirkete ait değil.');
        }
        return $this->db->insert('masraf_belgeler', [
            'masraf_id'    => (int)$veri['masraf_id'],
            'company_id'   => TenantContext::activeCompanyId(),
            'dosya_adi'    => $veri['dosya_adi'],
            'orijinal_adi' => $veri['orijinal_adi'],
            'boyut'        => (int)$veri['boyut'],
            'uzanti'       => $veri['uzanti'],
        ]);
    }

    public function belgeler(int $masrafId): array
    {
        $this->ensureBelgelerTable();
        return $this->db->select(
            "SELECT b.* FROM masraf_belgeler b
             INNER JOIN masraflar m ON m.id = b.masraf_id AND m.company_id = b.company_id
             WHERE b.masraf_id = :id AND b.silindi_mi = 0
               AND b.company_id = :cid
               AND m.silindi_mi = 0
             ORDER BY b.yuklenme_tarihi DESC",
            [':id' => $masrafId, ':cid' => TenantContext::activeCompanyId()]
        );
    }

    public function belgeGetir(int $belgeId): ?array
    {
        $this->ensureBelgelerTable();
        return $this->db->selectOne(
            "SELECT b.* FROM masraf_belgeler b
             INNER JOIN masraflar m ON m.id = b.masraf_id AND m.company_id = b.company_id
             WHERE b.id = :bid AND b.silindi_mi = 0
               AND b.company_id = :cid
               AND m.silindi_mi = 0
             LIMIT 1",
            [':bid' => $belgeId, ':cid' => TenantContext::activeCompanyId()]
        );
    }

    private function ensureBelgelerTable(): void
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS masraf_belgeler (
                id              INT(11) NOT NULL AUTO_INCREMENT,
                masraf_id       INT(11) NOT NULL,
                company_id      INT UNSIGNED NULL,
                dosya_adi       VARCHAR(200) NOT NULL,
                orijinal_adi    VARCHAR(200) NOT NULL,
                boyut           INT(11) NOT NULL DEFAULT 0,
                uzanti          VARCHAR(10) NOT NULL DEFAULT '',
                silindi_mi      TINYINT(1) NOT NULL DEFAULT 0,
                yuklenme_tarihi TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_masraf (masraf_id),
                KEY idx_company (company_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci
        ");
        // Eski kayıtlar için kolon yoksa ekle
        $col = $this->db->selectOne("SHOW COLUMNS FROM masraf_belgeler LIKE 'company_id'");
        if (!$col) {
            $this->db->query("ALTER TABLE masraf_belgeler ADD COLUMN company_id INT UNSIGNED NULL AFTER masraf_id, ADD KEY idx_company (company_id)");
            // Mevcut kayıtların company_id'sini masraflar tablosundan doldur
            $this->db->query("UPDATE masraf_belgeler b
                              INNER JOIN masraflar m ON m.id = b.masraf_id
                              SET b.company_id = m.company_id
                              WHERE b.company_id IS NULL");
        }
    }

    private function ensurePersonelLinks(): void
    {
        $this->addColumnIfMissing('masraflar', 'personel_id', 'INT UNSIGNED NULL AFTER kasa_id');
        $this->addColumnIfMissing('masraflar', 'personel_hareket_id', 'INT UNSIGNED NULL AFTER personel_id');
        $this->addColumnIfMissing('personel_hareketleri', 'masraf_id', 'INT NULL AFTER personel_id');

        $column = $this->db->selectOne("SHOW COLUMNS FROM personel_hareketleri LIKE 'tip'");
        $type = (string)($column['Type'] ?? '');
        if ($type !== '' && !str_contains($type, "'sgk'")) {
            $this->db->query("ALTER TABLE personel_hareketleri MODIFY tip ENUM('maas','sgk','sigorta','yemek','yol','prim','ek_odeme','avans','kesinti','odeme') NOT NULL");
        }
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

    private function personelHareketiEkle(int $masrafId, array $masraf, string $tip): int
    {
        $existing = $this->db->selectOne(
            "SELECT id FROM personel_hareketleri
             WHERE masraf_id = :mid AND silindi_mi = 0
               AND company_id = :cid AND period_id = :pid
             LIMIT 1",
            [
                ':mid' => $masrafId,
                ':cid' => TenantContext::activeCompanyId(),
                ':pid' => TenantContext::activePeriodId(),
            ]
        );
        if ($existing) {
            return (int)$existing['id'];
        }

        return $this->db->insert('personel_hareketleri', [
            'personel_id' => (int)$masraf['personel_id'],
            'masraf_id' => $masrafId,
            'kasa_id' => !empty($masraf['kasa_id']) ? (int)$masraf['kasa_id'] : null,
            'tarih' => $masraf['odeme_tarihi'] ?: ($masraf['islem_tarihi'] ?? date('Y-m-d')),
            'tip' => $tip,
            'tutar' => (float)$masraf['tutar'] + (float)($masraf['kdv_tutari'] ?? 0),
            'aciklama' => trim(($masraf['aciklama'] ?? '') . ' [masraf#' . $masrafId . ']'),
            'odeme_durumu' => ($masraf['odeme_durumu'] ?? '') === 'odendi' ? 'odendi' : 'bekliyor',
            'belge_no' => $masraf['belge_no'] ?? null,
            'para_birimi' => $masraf['para_birimi'] ?? 'TRY',
        ]);
    }

    private function syncPersonelHareketi(int $masrafId, string $tip): void
    {
        $masraf = $this->getir($masrafId);
        if (!$masraf) {
            return;
        }

        if (empty($masraf['personel_id'])) {
            if (!empty($masraf['personel_hareket_id'])) {
                $this->db->update('personel_hareketleri', ['silindi_mi' => 1], ['id' => (int)$masraf['personel_hareket_id']]);
                $this->db->update('masraflar', ['personel_hareket_id' => null], ['id' => $masrafId]);
            }
            return;
        }

        if (empty($masraf['personel_hareket_id'])) {
            $hareketId = $this->personelHareketiEkle($masrafId, $masraf, $tip);
            $this->db->update('masraflar', ['personel_hareket_id' => $hareketId], ['id' => $masrafId]);
            return;
        }

        $this->db->update('personel_hareketleri', [
            'personel_id' => (int)$masraf['personel_id'],
            'kasa_id' => !empty($masraf['kasa_id']) ? (int)$masraf['kasa_id'] : null,
            'tarih' => $masraf['odeme_tarihi'] ?: ($masraf['islem_tarihi'] ?? date('Y-m-d')),
            'tip' => $tip,
            'tutar' => (float)$masraf['tutar'] + (float)($masraf['kdv_tutari'] ?? 0),
            'aciklama' => trim(($masraf['aciklama'] ?? '') . ' [masraf#' . $masrafId . ']'),
            'odeme_durumu' => ($masraf['odeme_durumu'] ?? '') === 'odendi' ? 'odendi' : 'bekliyor',
            'belge_no' => $masraf['belge_no'] ?? null,
        ], ['id' => (int)$masraf['personel_hareket_id']]);
    }

    private function normalizePersonelTip(string $tip): string
    {
        $allowed = ['maas', 'sgk', 'sigorta', 'yemek', 'yol', 'prim', 'ek_odeme', 'avans', 'kesinti', 'odeme'];
        return in_array($tip, $allowed, true) ? $tip : 'odeme';
    }

    /** Masraf ödendiğinde kasa_hareketleri'ne çıkış hareketi yaz */
    private function kasaHareketiEkle(int $masrafId, array $masraf): void
    {
        $kasaId = (int)($masraf['kasa_id'] ?? 0);
        if ($kasaId <= 0) return;

        $tutar = (float)($masraf['tutar'] ?? 0) + (float)($masraf['kdv_tutari'] ?? 0);
        if ($tutar <= 0) return;

        // Daha önce bu masraf için hareket var mı? (tenant-scoped)
        $mevcut = $this->db->selectOne(
            "SELECT id FROM kasa_hareketleri
             WHERE aciklama LIKE :pat AND kasa_id = :kid AND silindi_mi = 0
               AND company_id = :cid AND period_id = :pid
             LIMIT 1",
            [
                ':pat' => '%[masraf#' . $masrafId . ']%',
                ':kid' => $kasaId,
                ':cid' => TenantContext::activeCompanyId(),
                ':pid' => TenantContext::activePeriodId(),
            ]
        );
        if ($mevcut) return; // çift hareket önleme

        $this->db->insert('kasa_hareketleri', [
            'kasa_id'       => $kasaId,
            'kasa_banka_id' => $kasaId,
            'islem_tipi'    => 'cikis',
            'hareket_tipi'  => 'gider',
            'tutar'         => $tutar,
            'tarih'         => ($masraf['odeme_tarihi'] ?? $masraf['islem_tarihi'] ?? date('Y-m-d')) . ' 00:00:00',
            'aciklama'      => trim(($masraf['aciklama'] ?? '') . ' [masraf#' . $masrafId . ']'),
            'para_birimi'   => $masraf['para_birimi'] ?? 'TRY',
        ]);

        // Kasa bakiyesini güncelle
        $this->db->query(
            "UPDATE kasa_banka SET guncel_bakiye = guncel_bakiye - :t WHERE id = :id AND company_id = :cid",
            [':t' => $tutar, ':id' => $kasaId, ':cid' => TenantContext::activeCompanyId()]
        );
    }
}
