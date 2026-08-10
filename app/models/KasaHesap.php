<?php
/**
 * KasaHesap Model
 * --------------------------------------------------------
 * kasa_banka tablosunu (hesap tanımları) ve
 * kasa_hareketleri tablosunu (hareketler) yönetir.
 *
 * kasa_banka sütunları:
 *   id, tip, hesap_adi, banka_adi, sube, hesap_no, iban,
 *   para_birimi, acilis_bakiyesi, guncel_bakiye, aciklama,
 *   olusturulma_tarihi, guncellenme_tarihi, silindi_mi
 *
 * kasa_hareketleri sütunları:
 *   id, kasa_id, cari_id, islem_tipi(giris|cikis),
 *   hareket_tipi, tutar, tarih, aciklama, para_birimi, silindi_mi
 */
class KasaHesap
{
    private Database $db;

    private array $fillable = [
        'tip', 'hesap_adi', 'banka_adi', 'sube', 'hesap_no', 'iban',
        'para_birimi', 'acilis_bakiyesi', 'guncel_bakiye', 'aciklama',
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->ensureOdemeYontemiColumn();
    }

    /** kasa_hareketleri.odeme_yontemi kolonu yoksa ekler (Nakit modeliyle aynı kolon/desen — güvenlik için buradan da garanti edilir). */
    private function ensureOdemeYontemiColumn(): void
    {
        try {
            $var = $this->db->selectOne(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'kasa_hareketleri' AND COLUMN_NAME = 'odeme_yontemi'"
            );
            if (!$var) {
                $this->db->query("ALTER TABLE kasa_hareketleri ADD COLUMN odeme_yontemi VARCHAR(30) NULL DEFAULT NULL AFTER hareket_tipi");
            }
        } catch (\Throwable $e) { /* sessizce geç */ }
    }

    private function normalizeHareketTipi(string $islemTipi, ?string $hareketTipi = null, bool $hasCari = false): string
    {
        $legacy = [
            'para_girisi' => 'gelir',
            'para_cikisi' => 'gider',
            'transfer' => $islemTipi === 'giris' ? 'virman_giris' : 'virman_cikis',
        ];
        if ($hareketTipi !== null && isset($legacy[$hareketTipi])) {
            return $legacy[$hareketTipi];
        }

        $allowed = ['tahsilat', 'odeme', 'virman_giris', 'virman_cikis', 'gelir', 'gider'];
        if ($hareketTipi !== null && in_array($hareketTipi, $allowed, true)) {
            return $hareketTipi;
        }

        if ($hasCari) {
            return $islemTipi === 'giris' ? 'tahsilat' : 'odeme';
        }

        return $islemTipi === 'giris' ? 'gelir' : 'gider';
    }

    // ──────────────────────────────────────────────────────
    // HESAP LİSTELEME
    // ──────────────────────────────────────────────────────

    /** Tüm aktif hesaplar (opsiyonel tip filtresi) */
    public function hepsini(string $tip = ''): array
    {
        $where  = 'silindi_mi = 0';
        $where .= ' AND company_id = :company_id';
        $params = [':company_id' => TenantContext::activeCompanyId()];
        if ($tip !== '') {
            $where .= ' AND tip = :tip';
            $params[':tip'] = $tip;
        }
        return $this->db->select(
            "SELECT * FROM kasa_banka WHERE {$where} ORDER BY tip, hesap_adi",
            $params
        );
    }

    /**
     * Hesapları tipe göre grupla.
     * @return array ['kasa' => [...], 'banka' => [...], ...]
     */
    public function grupla(): array
    {
        $all     = $this->hepsini();
        $grouped = [];
        foreach ($all as $item) {
            $grouped[$item['tip']][] = $item;
        }
        return $grouped;
    }

    /** Tekil hesap */
    public function getir(int $id): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM kasa_banka WHERE id = :id AND silindi_mi = 0 AND company_id = :company_id",
            [':id' => $id, ':company_id' => TenantContext::activeCompanyId()]
        );
    }

    // ──────────────────────────────────────────────────────
    // HESAP CRUD
    // ──────────────────────────────────────────────────────

    public function ekle(array $veri): int
    {
        $temiz = array_intersect_key($veri, array_flip($this->fillable));

        if (empty($temiz['hesap_adi'])) {
            throw new InvalidArgumentException('Hesap adı boş olamaz.');
        }

        $temiz['tip']           = $temiz['tip']         ?? 'kasa';
        $temiz['para_birimi']   = $temiz['para_birimi'] ?? 'TRY';
        $acilis                 = (float)($temiz['acilis_bakiyesi'] ?? 0);
        $temiz['acilis_bakiyesi'] = $acilis;
        $temiz['guncel_bakiye'] = $acilis; // Açılışta güncel = açılış

        return $this->db->insert('kasa_banka', $temiz);
    }

    public function guncelle(int $id, array $veri): int
    {
        $temiz = array_intersect_key($veri, array_flip($this->fillable));
        if (empty($temiz)) {
            return 0;
        }
        return $this->db->update('kasa_banka', $temiz, ['id' => $id]);
    }

    public function sil(int $id): int
    {
        return $this->db->update('kasa_banka', ['silindi_mi' => 1], ['id' => $id]);
    }

    // ──────────────────────────────────────────────────────
    // HAREKET LİSTELEME
    // ──────────────────────────────────────────────────────

    public function hareketler(int $kasaId, int $limit = 100, int $offset = 0): array
    {
        return $this->db->select(
            "SELECT kh.*, c.unvan AS cari_adi
             FROM   kasa_hareketleri kh
             LEFT JOIN cariler c ON c.id = kh.cari_id
             WHERE  kh.kasa_id = :kid AND kh.silindi_mi = 0
               AND kh.company_id = :company_id AND kh.period_id = :period_id
             ORDER  BY kh.tarih DESC, kh.id DESC
             LIMIT  :lim OFFSET :off",
            [':kid' => $kasaId, ':lim' => $limit, ':off' => $offset, ':company_id' => TenantContext::activeCompanyId(), ':period_id' => TenantContext::activePeriodId()]
        );
    }

    public function hareketSay(int $kasaId): int
    {
        $row = $this->db->selectOne(
            "SELECT COUNT(*) AS c FROM kasa_hareketleri WHERE kasa_id = :kid AND silindi_mi = 0 AND company_id = :company_id AND period_id = :period_id",
            [':kid' => $kasaId, ':company_id' => TenantContext::activeCompanyId(), ':period_id' => TenantContext::activePeriodId()]
        );
        return (int)($row['c'] ?? 0);
    }

    // ──────────────────────────────────────────────────────
    // HAREKET EKLEME
    // ──────────────────────────────────────────────────────

    /**
     * Para girişi veya çıkışı ekler, hesap bakiyesini günceller.
     */
    public function hareketEkle(array $data): int
    {
        $kasaId = (int)($data['kasa_id'] ?? $data['kasa_banka_id'] ?? 0);
        $tutar  = (float)$data['tutar'];
        $islem  = $data['islem_tipi']; // 'giris' | 'cikis'
        $cariId = !empty($data['cari_id']) ? (int)$data['cari_id'] : null;
        $hareketTipi = $this->normalizeHareketTipi($islem, $data['hareket_tipi'] ?? null, $cariId !== null);

        if ($tutar <= 0) {
            throw new InvalidArgumentException('Tutar sıfırdan büyük olmalıdır.');
        }

        $this->db->begin();
        try {
            $id = $this->db->insert('kasa_hareketleri', [
                'kasa_id'       => $kasaId,
                'kasa_banka_id' => $kasaId,
                'cari_id'       => $cariId,
                'islem_tipi'    => $islem,
                'hareket_tipi'  => $hareketTipi,
                'odeme_yontemi' => $data['odeme_yontemi'] ?? null,
                'tutar'         => $tutar,
                'tarih'         => $data['tarih'] ?? date('Y-m-d H:i:s'),
                'aciklama'      => $data['aciklama'] ?? '',
                'para_birimi'   => $data['para_birimi'] ?? 'TRY',
                'company_id'    => TenantContext::activeCompanyId(),
                'period_id'     => TenantContext::activePeriodId(),
            ]);

            // Bakiyeyi güncelle
            $sign = ($islem === 'giris') ? '+' : '-';
            $this->db->query(
                "UPDATE kasa_banka SET guncel_bakiye = guncel_bakiye {$sign} :tutar WHERE id = :id AND company_id = :company_id",
                [':tutar' => $tutar, ':id' => $kasaId, ':company_id' => TenantContext::activeCompanyId()]
            );

            $this->db->commit();
            return $id;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // ──────────────────────────────────────────────────────
    // TRANSFER
    // ──────────────────────────────────────────────────────

    public function transfer(int $fromId, int $toId, float $tutar, string $aciklama = '', string $tarih = ''): void
    {
        if ($fromId === $toId) {
            throw new InvalidArgumentException('Kaynak ve hedef hesap aynı olamaz.');
        }
        if ($tutar <= 0) {
            throw new InvalidArgumentException('Tutar sıfırdan büyük olmalıdır.');
        }

        $tarih    = $tarih ?: date('Y-m-d H:i:s');
        $aciklama = $aciklama ?: 'Hesaplar arası transfer';

        $this->db->begin();
        try {
            // Kaynaktan çıkış
            $this->db->insert('kasa_hareketleri', [
                'kasa_id'       => $fromId,
                'kasa_banka_id' => $fromId,
                'karsi_kasa_id' => $toId,
                'islem_tipi'    => 'cikis',
                'hareket_tipi'  => 'virman_cikis',
                'tutar'         => $tutar,
                'tarih'         => $tarih,
                'aciklama'      => $aciklama,
                'para_birimi'   => 'TRY',
                'company_id'    => TenantContext::activeCompanyId(),
                'period_id'     => TenantContext::activePeriodId(),
            ]);
            $this->db->query(
                "UPDATE kasa_banka SET guncel_bakiye = guncel_bakiye - :t WHERE id = :id AND company_id = :company_id",
                [':t' => $tutar, ':id' => $fromId, ':company_id' => TenantContext::activeCompanyId()]
            );

            // Hedefe giriş
            $this->db->insert('kasa_hareketleri', [
                'kasa_id'       => $toId,
                'kasa_banka_id' => $toId,
                'karsi_kasa_id' => $fromId,
                'islem_tipi'    => 'giris',
                'hareket_tipi'  => 'virman_giris',
                'tutar'         => $tutar,
                'tarih'         => $tarih,
                'aciklama'      => $aciklama,
                'para_birimi'   => 'TRY',
                'company_id'    => TenantContext::activeCompanyId(),
                'period_id'     => TenantContext::activePeriodId(),
            ]);
            $this->db->query(
                "UPDATE kasa_banka SET guncel_bakiye = guncel_bakiye + :t WHERE id = :id AND company_id = :company_id",
                [':t' => $tutar, ':id' => $toId, ':company_id' => TenantContext::activeCompanyId()]
            );

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // ──────────────────────────────────────────────────────
    // HAREKET SİL (soft + bakiye geri al)
    // ──────────────────────────────────────────────────────

    public function hareketSil(int $id): bool
    {
        $h = $this->db->selectOne(
            "SELECT * FROM kasa_hareketleri WHERE id = :id AND silindi_mi = 0 AND company_id = :company_id",
            [':id' => $id, ':company_id' => TenantContext::activeCompanyId()]
        );
        if (!$h) {
            return false;
        }

        $this->db->begin();
        try {
            $this->db->update('kasa_hareketleri', ['silindi_mi' => 1], ['id' => $id]);

            // Bakiyeyi tersine çevir
            $sign = ($h['islem_tipi'] === 'giris') ? '-' : '+';
            $this->db->query(
                "UPDATE kasa_banka SET guncel_bakiye = guncel_bakiye {$sign} :tutar WHERE id = :id AND company_id = :company_id",
                [':tutar' => $h['tutar'], ':id' => $h['kasa_id'], ':company_id' => TenantContext::activeCompanyId()]
            );

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
