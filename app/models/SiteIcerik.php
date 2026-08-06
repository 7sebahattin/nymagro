<?php
/**
 * SiteIcerik Model
 * --------------------------------------------------------
 * Public landing page (Nymagro) içeriklerini yönetir.
 *   - site_settings   : key-value (logo, whatsapp, email, vb.)
 *   - site_urunler    : ürün kartları (görsel, ad, açıklama)
 *   - site_galeri     : galeri görselleri
 *
 * Şirketten bağımsız (tek site) — TenantContext devre dışı bırakılır.
 */
class SiteIcerik
{
    private Database $db;

    /** Varsayılan ayar anahtarları */
    public const DEFAULT_SETTINGS = [
        'logo_path'       => '',
        'company_name'    => 'Nymagro',
        'tagline'         => 'Bitki Besleme Ürünleri',
        'whatsapp'        => '+90 543 961 73 03',
        'whatsapp_link'   => 'https://wa.me/905439617303',
        'email'           => 'nymagrotarim@gmail.com',
        'phone'           => '+90 242 464 12 44',
        'address'         => 'Çamköy Mah. Atatürk Blv. No: 394, Aksu / Antalya, Türkiye',
        'lat'             => '36.9459',
        'lng'             => '30.8437',
        'instagram'       => '',
        'linkedin'        => '',
        'facebook'        => '',
        'twitter'         => '',
        'website'         => 'www.nymagro.com',
        'site_url'        => 'https://www.nymagro.com',
        'hero_title_1'    => 'Toprağa değer,',
        'hero_title_2'    => 'bitkiye güç',
        'hero_desc'       => 'Nymagro, EC Fertilizer standardında üretilen şelatlı mikro element ve sıvı bitki besin ürünlerini İspanya\'dan ithal ederek Türk üreticisinin hizmetine sunar.',
        'slide_count'     => '4',
        'slide1_img'      => 'https://images.unsplash.com/photo-1610348725531-843dff563e2c?auto=format&fit=crop&w=1400&q=80',
        'slide2_img'      => 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=1400&q=80',
        'slide3_img'      => 'https://images.unsplash.com/photo-1473973266408-ed4e27abdd47?auto=format&fit=crop&w=1400&q=80',
        'slide4_img'      => 'https://images.unsplash.com/photo-1488459716781-31db52582fe9?auto=format&fit=crop&w=1400&q=80',
        'about_img'       => 'https://images.unsplash.com/photo-1488459716781-31db52582fe9?auto=format&fit=crop&w=1200&q=80',
        'og_image'        => '/img/og-image.jpg',
        'stat_countries_value' => '5',
        'stat_products_value'  => '8+',
        'stat_quality_value'   => '100%',
        'about_stat_value'     => '5',
    ];

    /** Slug bazlı arama için */
    public function urunBySlug(string $slug, string $locale = 'tr'): ?array
    {
        $col = 'slug_' . (in_array($locale, ['tr','en','ru'], true) ? $locale : 'tr');
        $row = $this->db->selectOne(
            "SELECT * FROM site_urunler WHERE {$col} = :s AND silindi_mi = 0 LIMIT 1",
            [':s' => $slug]
        );
        return $row ?: null;
    }

    /** Ürün adı çoklu dil destekli */
    public function urunAd(array $u, string $locale = 'tr'): string
    {
        $key = 'ad_' . $locale;
        return (string)($u[$key] ?? $u['ad_tr'] ?? '');
    }

    public function urunAciklama(array $u, string $locale = 'tr'): string
    {
        $key = 'aciklama_' . $locale;
        return (string)($u[$key] ?? $u['aciklama_tr'] ?? '');
    }

    public function urunSlug(array $u, string $locale = 'tr'): string
    {
        $key = 'slug_' . $locale;
        return (string)($u[$key] ?? self::slugify($u['ad_' . $locale] ?? $u['ad_tr'] ?? ''));
    }

    /**
     * "Ad|Değer" satırlarından oluşan içerik metnini diziye çevirir.
     * Örn: "Suda Çözünür Mangan (Mn)|%1" → ['ad' => '...', 'deger' => '%1']
     */
    public static function parseIcerik(?string $metin): array
    {
        $satirlar = [];
        foreach (preg_split('/\r\n|\r|\n/', trim((string)$metin)) as $satir) {
            $satir = trim($satir);
            if ($satir === '') continue;
            $parcalar = explode('|', $satir, 2);
            $satirlar[] = [
                'ad'    => trim($parcalar[0] ?? ''),
                'deger' => trim($parcalar[1] ?? ''),
            ];
        }
        return $satirlar;
    }

    /**
     * "Bitki|Yapraktan|Damlama" satırlarından oluşan doz metnini diziye çevirir.
     */
    public static function parseDoz(?string $metin): array
    {
        $satirlar = [];
        foreach (preg_split('/\r\n|\r|\n/', trim((string)$metin)) as $satir) {
            $satir = trim($satir);
            if ($satir === '') continue;
            $parcalar = explode('|', $satir, 3);
            $satirlar[] = [
                'bitki'     => trim($parcalar[0] ?? ''),
                'yapraktan' => trim($parcalar[1] ?? ''),
                'damlama'   => trim($parcalar[2] ?? ''),
            ];
        }
        return $satirlar;
    }

    /** doz_* metnindeki bitki adlarını virgülle ayrılmış tek satıra çevirir ("Uygun Bitkiler" kartı için) */
    public static function bitkiListesi(?string $dozMetni): string
    {
        $bitkiler = array_map(fn($s) => $s['bitki'], self::parseDoz($dozMetni));
        return implode(', ', array_filter($bitkiler));
    }

    /** TR/EN/RU duyarlı slug üreteci */
    public static function slugify(string $s): string
    {
        $s = trim($s);
        if ($s === '') return '';
        // Türkçe karakter dönüşümü
        $tr = ['ç','Ç','ğ','Ğ','ı','İ','ö','Ö','ş','Ş','ü','Ü'];
        $en = ['c','c','g','g','i','i','o','o','s','s','u','u'];
        $s = str_replace($tr, $en, $s);
        // Rusça transliterasyon (kaba)
        $rus = [
            'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'zh','з'=>'z','и'=>'i','й'=>'y',
            'к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f',
            'х'=>'h','ц'=>'ts','ч'=>'ch','ш'=>'sh','щ'=>'sch','ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya',
            'А'=>'a','Б'=>'b','В'=>'v','Г'=>'g','Д'=>'d','Е'=>'e','Ё'=>'e','Ж'=>'zh','З'=>'z','И'=>'i','Й'=>'y',
            'К'=>'k','Л'=>'l','М'=>'m','Н'=>'n','О'=>'o','П'=>'p','Р'=>'r','С'=>'s','Т'=>'t','У'=>'u','Ф'=>'f',
            'Х'=>'h','Ц'=>'ts','Ч'=>'ch','Ш'=>'sh','Щ'=>'sch','Ъ'=>'','Ы'=>'y','Ь'=>'','Э'=>'e','Ю'=>'yu','Я'=>'ya',
        ];
        $s = strtr($s, $rus);
        $s = strtolower($s);
        $s = preg_replace('/[^a-z0-9\s\-]/', '', $s);
        $s = preg_replace('/[\s\-]+/', '-', $s);
        $s = trim($s, '-');
        return $s ?: 'urun';
    }

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->ensureTables();
    }

    // ──────────────────────────────────────────────────────
    // SETTINGS (key-value)
    // ──────────────────────────────────────────────────────

    public function tumAyarlar(): array
    {
        $rows = $this->db->select("SELECT setting_key, setting_value FROM site_settings");
        $out = self::DEFAULT_SETTINGS;
        foreach ($rows as $r) {
            $out[$r['setting_key']] = $r['setting_value'];
        }
        return $out;
    }

    public function ayar(string $key, string $default = ''): string
    {
        $row = $this->db->selectOne(
            "SELECT setting_value FROM site_settings WHERE setting_key = :k LIMIT 1",
            [':k' => $key]
        );
        return $row['setting_value'] ?? ($default !== '' ? $default : (self::DEFAULT_SETTINGS[$key] ?? ''));
    }

    public function ayarKaydet(string $key, string $value): void
    {
        $existing = $this->db->selectOne(
            "SELECT id FROM site_settings WHERE setting_key = :k LIMIT 1",
            [':k' => $key]
        );
        if ($existing) {
            $this->db->query(
                "UPDATE site_settings SET setting_value = :v, updated_at = NOW() WHERE setting_key = :k",
                [':v' => $value, ':k' => $key]
            );
        } else {
            $this->db->query(
                "INSERT INTO site_settings (setting_key, setting_value) VALUES (:k, :v)",
                [':k' => $key, ':v' => $value]
            );
        }
    }

    public function topluAyarKaydet(array $ayarlar): void
    {
        foreach ($ayarlar as $k => $v) {
            $this->ayarKaydet((string)$k, (string)$v);
        }
    }

    // ──────────────────────────────────────────────────────
    // ÜRÜNLER
    // ──────────────────────────────────────────────────────

    public function urunler(bool $aktifMi = false): array
    {
        $where = $aktifMi ? "WHERE silindi_mi = 0 AND aktif_mi = 1" : "WHERE silindi_mi = 0";
        return $this->db->select("SELECT * FROM site_urunler {$where} ORDER BY sira ASC, id ASC");
    }

    /** Belirli bir ürün grubuna (kategori) ait ürünler */
    public function urunlerByKategori(string $kategori, bool $aktifMi = true): array
    {
        $where = $aktifMi
            ? "WHERE kategori = :k AND silindi_mi = 0 AND aktif_mi = 1"
            : "WHERE kategori = :k AND silindi_mi = 0";
        return $this->db->select(
            "SELECT * FROM site_urunler {$where} ORDER BY sira ASC, id ASC",
            [':k' => $this->sanitizeCategory($kategori)]
        );
    }

    public function urunGetir(int $id): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM site_urunler WHERE id = :id AND silindi_mi = 0",
            [':id' => $id]
        );
    }

    public function urunEkle(array $veri): int
    {
        $adTr = trim($veri['ad_tr'] ?? '');
        $adEn = trim($veri['ad_en'] ?? '');
        $adRu = trim($veri['ad_ru'] ?? '');

        return $this->db->query(
            "INSERT INTO site_urunler
                (sira, kategori, ad_tr, ad_en, ad_ru, slug_tr, slug_en, slug_ru,
                 aciklama_tr, aciklama_en, aciklama_ru, gorsel, etiket, aktif_mi,
                 tescil_no, ph_araligi, icerik_tr, icerik_en, icerik_ru, doz_tr, doz_en, doz_ru)
             VALUES
                (:sira, :kategori, :ad_tr, :ad_en, :ad_ru, :slug_tr, :slug_en, :slug_ru,
                 :aciklama_tr, :aciklama_en, :aciklama_ru, :gorsel, :etiket, :aktif_mi,
                 :tescil_no, :ph_araligi, :icerik_tr, :icerik_en, :icerik_ru, :doz_tr, :doz_en, :doz_ru)",
            [
                ':sira'        => (int)($veri['sira'] ?? 0),
                ':kategori'    => $this->sanitizeCategory((string)($veri['kategori'] ?? 'micro')),
                ':ad_tr'       => $adTr,
                ':ad_en'       => $adEn,
                ':ad_ru'       => $adRu,
                ':slug_tr'     => $this->uniqueSlug((string)($veri['slug_tr'] ?? $adTr), 'slug_tr'),
                ':slug_en'     => $this->uniqueSlug((string)($veri['slug_en'] ?? ($adEn ?: $adTr)), 'slug_en'),
                ':slug_ru'     => $this->uniqueSlug((string)($veri['slug_ru'] ?? ($adEn ?: $adTr)), 'slug_ru'),
                ':aciklama_tr' => trim($veri['aciklama_tr'] ?? ''),
                ':aciklama_en' => trim($veri['aciklama_en'] ?? ''),
                ':aciklama_ru' => trim($veri['aciklama_ru'] ?? ''),
                ':gorsel'      => trim($veri['gorsel'] ?? ''),
                ':etiket'      => trim($veri['etiket'] ?? 'YENİ'),
                ':aktif_mi'    => !empty($veri['aktif_mi']) ? 1 : 0,
                ':tescil_no'   => trim($veri['tescil_no'] ?? ''),
                ':ph_araligi'  => trim($veri['ph_araligi'] ?? ''),
                ':icerik_tr'   => trim($veri['icerik_tr'] ?? ''),
                ':icerik_en'   => trim($veri['icerik_en'] ?? ''),
                ':icerik_ru'   => trim($veri['icerik_ru'] ?? ''),
                ':doz_tr'      => trim($veri['doz_tr'] ?? ''),
                ':doz_en'      => trim($veri['doz_en'] ?? ''),
                ':doz_ru'      => trim($veri['doz_ru'] ?? ''),
            ]
        ) ? (int)$this->db->pdo()->lastInsertId() : 0;
    }

    public function urunGuncelle(int $id, array $veri): void
    {
        $set = [];
        $params = [':id' => $id];
        $alanlar = [
            'sira','kategori','ad_tr','ad_en','ad_ru','aciklama_tr','aciklama_en','aciklama_ru',
            'gorsel','etiket','aktif_mi',
            'tescil_no','ph_araligi','icerik_tr','icerik_en','icerik_ru','doz_tr','doz_en','doz_ru',
        ];
        foreach ($alanlar as $k) {
            if (array_key_exists($k, $veri)) {
                $set[] = "{$k} = :{$k}";
                $val = $veri[$k];
                if ($k === 'aktif_mi') $val = !empty($val) ? 1 : 0;
                if ($k === 'sira')     $val = (int)$val;
                if ($k === 'kategori') $val = $this->sanitizeCategory((string)$val);
                $params[":{$k}"] = $val;
            }
        }
        $mevcut = $this->urunGetir($id);
        if ($mevcut) {
            $adTr = trim((string)($veri['ad_tr'] ?? $mevcut['ad_tr'] ?? ''));
            $adEn = trim((string)($veri['ad_en'] ?? $mevcut['ad_en'] ?? ''));
            $adRu = trim((string)($veri['ad_ru'] ?? $mevcut['ad_ru'] ?? ''));
            if (empty($mevcut['slug_tr']) && $adTr !== '') {
                $set[] = "slug_tr = :slug_tr";
                $params[':slug_tr'] = $this->uniqueSlug($adTr, 'slug_tr', $id);
            }
            if (empty($mevcut['slug_en']) && ($adEn !== '' || $adTr !== '')) {
                $set[] = "slug_en = :slug_en";
                $params[':slug_en'] = $this->uniqueSlug($adEn ?: $adTr, 'slug_en', $id);
            }
            if (empty($mevcut['slug_ru']) && ($adRu !== '' || $adEn !== '' || $adTr !== '')) {
                $set[] = "slug_ru = :slug_ru";
                $params[':slug_ru'] = $this->uniqueSlug($adRu ?: ($adEn ?: $adTr), 'slug_ru', $id);
            }
        }
        if (empty($set)) return;
        $this->db->query("UPDATE site_urunler SET " . implode(', ', $set) . ", updated_at = NOW() WHERE id = :id", $params);
    }

    public function urunSil(int $id): void
    {
        $this->db->query("UPDATE site_urunler SET silindi_mi = 1, updated_at = NOW() WHERE id = :id", [':id' => $id]);
    }

    // ──────────────────────────────────────────────────────
    // GALERİ
    // ──────────────────────────────────────────────────────

    public function galeri(bool $aktifMi = false): array
    {
        $where = $aktifMi ? "WHERE silindi_mi = 0 AND aktif_mi = 1" : "WHERE silindi_mi = 0";
        return $this->db->select("SELECT * FROM site_galeri {$where} ORDER BY sira ASC, id ASC");
    }

    public function galeriEkle(array $veri): int
    {
        $this->db->query(
            "INSERT INTO site_galeri (sira, gorsel, etiket_tr, etiket_en, etiket_ru, aktif_mi)
             VALUES (:sira, :gorsel, :etiket_tr, :etiket_en, :etiket_ru, :aktif_mi)",
            [
                ':sira'      => (int)($veri['sira'] ?? 0),
                ':gorsel'    => trim($veri['gorsel'] ?? ''),
                ':etiket_tr' => trim($veri['etiket_tr'] ?? ''),
                ':etiket_en' => trim($veri['etiket_en'] ?? ''),
                ':etiket_ru' => trim($veri['etiket_ru'] ?? ''),
                ':aktif_mi'  => !empty($veri['aktif_mi']) ? 1 : 0,
            ]
        );
        return (int)$this->db->pdo()->lastInsertId();
    }

    public function galeriSil(int $id): void
    {
        $this->db->query("UPDATE site_galeri SET silindi_mi = 1 WHERE id = :id", [':id' => $id]);
    }

    public function galeriOgesi(int $id): ?array
    {
        return $this->db->selectOne("SELECT * FROM site_galeri WHERE id = :id", [':id' => $id]);
    }

    // ──────────────────────────────────────────────────────
    // ŞEMA
    // ──────────────────────────────────────────────────────

    private function ensureTables(): void
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS site_settings (
                id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
                setting_key   VARCHAR(80) NOT NULL,
                setting_value TEXT NULL,
                created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_settings_key (setting_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS site_urunler (
                id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
                sira         INT NOT NULL DEFAULT 0,
                kategori     VARCHAR(20) NOT NULL DEFAULT 'micro',
                ad_tr        VARCHAR(120) NOT NULL,
                ad_en        VARCHAR(120) NULL,
                ad_ru        VARCHAR(120) NULL,
                slug_tr      VARCHAR(140) NULL,
                slug_en      VARCHAR(140) NULL,
                slug_ru      VARCHAR(140) NULL,
                aciklama_tr  VARCHAR(500) NULL,
                aciklama_en  VARCHAR(500) NULL,
                aciklama_ru  VARCHAR(500) NULL,
                sezon_tr     VARCHAR(120) NULL,
                sezon_en     VARCHAR(120) NULL,
                sezon_ru     VARCHAR(120) NULL,
                paketleme_tr VARCHAR(255) NULL,
                paketleme_en VARCHAR(255) NULL,
                paketleme_ru VARCHAR(255) NULL,
                pazarlar     VARCHAR(255) NULL,
                gorsel       VARCHAR(255) NOT NULL,
                etiket       VARCHAR(40)  NOT NULL DEFAULT 'FRESH',
                aktif_mi     TINYINT(1)   NOT NULL DEFAULT 1,
                silindi_mi   TINYINT(1)   NOT NULL DEFAULT 0,
                created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_slug_tr (slug_tr),
                UNIQUE KEY uq_slug_en (slug_en),
                KEY idx_aktif (aktif_mi, silindi_mi)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci
        ");

        // Geriye dönük: slug ve ek kolonlar yoksa ekle
        $this->ensureUrunlerColumns();

        $this->db->query("
            CREATE TABLE IF NOT EXISTS site_galeri (
                id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
                sira        INT NOT NULL DEFAULT 0,
                gorsel      VARCHAR(255) NOT NULL,
                etiket_tr   VARCHAR(120) NULL,
                etiket_en   VARCHAR(120) NULL,
                etiket_ru   VARCHAR(120) NULL,
                aktif_mi    TINYINT(1) NOT NULL DEFAULT 1,
                silindi_mi  TINYINT(1) NOT NULL DEFAULT 0,
                created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci
        ");

        // İlk kurulumda varsayılan ayarları seed et
        $count = $this->db->selectOne("SELECT COUNT(*) AS n FROM site_settings");
        if ((int)($count['n'] ?? 0) === 0) {
            foreach (self::DEFAULT_SETTINGS as $k => $v) {
                $this->db->query(
                    "INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES (:k, :v)",
                    [':k' => $k, ':v' => $v]
                );
            }
        }

        // Not: Ürün kataloğu artık db/nymagro-urunler.sql ile elle yönetiliyor.
        // Otomatik varsayılan ürün seed'i (eski meyve-sebze kataloğu) kaldırıldı.

        // Backfill: slug_tr/en eksikse otomatik üret
        $missing = $this->db->select("SELECT id, ad_tr, ad_en, ad_ru, slug_tr, slug_en, slug_ru FROM site_urunler WHERE silindi_mi = 0");
        foreach ($missing as $m) {
            $upd = [];
            $params = [':id' => (int)$m['id']];
            if (empty($m['slug_tr'])) { $upd[] = "slug_tr = :st"; $params[':st'] = $this->uniqueSlug($m['ad_tr'], 'slug_tr', (int)$m['id']); }
            if (empty($m['slug_en'])) { $upd[] = "slug_en = :se"; $params[':se'] = $this->uniqueSlug($m['ad_en'] ?: $m['ad_tr'], 'slug_en', (int)$m['id']); }
            if (empty($m['slug_ru'])) { $upd[] = "slug_ru = :sr"; $params[':sr'] = $this->uniqueSlug($m['ad_ru'] ?: ($m['ad_en'] ?: $m['ad_tr']), 'slug_ru', (int)$m['id']); }
            if ($upd) {
                $this->db->query("UPDATE site_urunler SET " . implode(', ', $upd) . " WHERE id = :id", $params);
            }
        }
    }

    private function uniqueSlug(string $source, string $column, ?int $ignoreId = null): string
    {
        $allowed = ['slug_tr', 'slug_en', 'slug_ru'];
        if (!in_array($column, $allowed, true)) {
            $column = 'slug_tr';
        }

        $base = self::slugify($source);
        $candidate = $base;
        $i = 2;

        while ($this->slugExists($candidate, $column, $ignoreId)) {
            $candidate = $base . '-' . $i;
            $i++;
        }

        return $candidate;
    }

    private function slugExists(string $slug, string $column, ?int $ignoreId = null): bool
    {
        $params = [':slug' => $slug];
        $sql = "SELECT id FROM site_urunler WHERE {$column} = :slug";
        if ($ignoreId !== null) {
            $sql .= " AND id <> :id";
            $params[':id'] = $ignoreId;
        }
        $sql .= " LIMIT 1";

        return (bool)$this->db->selectOne($sql, $params);
    }

    /**
     * Yeni kolonlar (slug_tr/en/ru, sezon, paketleme, pazarlar) yoksa ekle.
     * Mevcut kurulumlar için geriye dönük güvenli.
     */
    private function ensureUrunlerColumns(): void
    {
        $columns = [
            'kategori'     => "ALTER TABLE site_urunler ADD COLUMN kategori VARCHAR(20) NOT NULL DEFAULT 'micro' AFTER sira",
            'slug_tr'      => "ALTER TABLE site_urunler ADD COLUMN slug_tr VARCHAR(140) NULL AFTER ad_ru",
            'slug_en'      => "ALTER TABLE site_urunler ADD COLUMN slug_en VARCHAR(140) NULL AFTER slug_tr",
            'slug_ru'      => "ALTER TABLE site_urunler ADD COLUMN slug_ru VARCHAR(140) NULL AFTER slug_en",
            'sezon_tr'     => "ALTER TABLE site_urunler ADD COLUMN sezon_tr VARCHAR(120) NULL AFTER aciklama_ru",
            'sezon_en'     => "ALTER TABLE site_urunler ADD COLUMN sezon_en VARCHAR(120) NULL AFTER sezon_tr",
            'sezon_ru'     => "ALTER TABLE site_urunler ADD COLUMN sezon_ru VARCHAR(120) NULL AFTER sezon_en",
            'paketleme_tr' => "ALTER TABLE site_urunler ADD COLUMN paketleme_tr VARCHAR(255) NULL AFTER sezon_ru",
            'paketleme_en' => "ALTER TABLE site_urunler ADD COLUMN paketleme_en VARCHAR(255) NULL AFTER paketleme_tr",
            'paketleme_ru' => "ALTER TABLE site_urunler ADD COLUMN paketleme_ru VARCHAR(255) NULL AFTER paketleme_en",
            'pazarlar'     => "ALTER TABLE site_urunler ADD COLUMN pazarlar VARCHAR(255) NULL AFTER paketleme_ru",
            'tescil_no'    => "ALTER TABLE site_urunler ADD COLUMN tescil_no VARCHAR(30) NULL AFTER pazarlar",
            'ph_araligi'   => "ALTER TABLE site_urunler ADD COLUMN ph_araligi VARCHAR(20) NULL AFTER tescil_no",
            'icerik_tr'    => "ALTER TABLE site_urunler ADD COLUMN icerik_tr TEXT NULL AFTER ph_araligi",
            'icerik_en'    => "ALTER TABLE site_urunler ADD COLUMN icerik_en TEXT NULL AFTER icerik_tr",
            'icerik_ru'    => "ALTER TABLE site_urunler ADD COLUMN icerik_ru TEXT NULL AFTER icerik_en",
            'doz_tr'       => "ALTER TABLE site_urunler ADD COLUMN doz_tr TEXT NULL AFTER icerik_ru",
            'doz_en'       => "ALTER TABLE site_urunler ADD COLUMN doz_en TEXT NULL AFTER doz_tr",
            'doz_ru'       => "ALTER TABLE site_urunler ADD COLUMN doz_ru TEXT NULL AFTER doz_en",
        ];
        foreach ($columns as $col => $sql) {
            $exists = $this->db->selectOne(
                "SELECT COUNT(*) AS n FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'site_urunler' AND COLUMN_NAME = :c",
                [':c' => $col]
            );
            if ((int)($exists['n'] ?? 0) === 0) {
                try { $this->db->query($sql); } catch (Throwable $e) { /* yoksay */ }
            }
        }
        // aciklama_* kolonları kısa ise genişlet
        try { $this->db->query("ALTER TABLE site_urunler MODIFY COLUMN aciklama_tr VARCHAR(500) NULL"); } catch (Throwable $e) {}
        try { $this->db->query("ALTER TABLE site_urunler MODIFY COLUMN aciklama_en VARCHAR(500) NULL"); } catch (Throwable $e) {}
        try { $this->db->query("ALTER TABLE site_urunler MODIFY COLUMN aciklama_ru VARCHAR(500) NULL"); } catch (Throwable $e) {}
    }

    private function sanitizeCategory(string $category): string
    {
        return in_array($category, ['micro', 'macro', 'biostimulant'], true) ? $category : 'micro';
    }

}
