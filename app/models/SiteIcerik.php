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
        'tagline'         => 'Bitki Besleme · Şelatlı Mikro Element',
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
        'stat_countries_value' => '8+',
        'stat_products_value'  => '8+',
        'stat_quality_value'   => '100%',
        'about_stat_value'     => '5+',
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
            "INSERT INTO site_urunler (sira, kategori, ad_tr, ad_en, ad_ru, slug_tr, slug_en, slug_ru, aciklama_tr, aciklama_en, aciklama_ru, gorsel, etiket, aktif_mi)
             VALUES (:sira, :kategori, :ad_tr, :ad_en, :ad_ru, :slug_tr, :slug_en, :slug_ru, :aciklama_tr, :aciklama_en, :aciklama_ru, :gorsel, :etiket, :aktif_mi)",
            [
                ':sira'        => (int)($veri['sira'] ?? 0),
                ':kategori'    => $this->sanitizeCategory((string)($veri['kategori'] ?? 'meyve')),
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
                ':etiket'      => trim($veri['etiket'] ?? 'FRESH'),
                ':aktif_mi'    => !empty($veri['aktif_mi']) ? 1 : 0,
            ]
        ) ? (int)$this->db->pdo()->lastInsertId() : 0;
    }

    public function urunGuncelle(int $id, array $veri): void
    {
        $set = [];
        $params = [':id' => $id];
        foreach (['sira','kategori','ad_tr','ad_en','ad_ru','aciklama_tr','aciklama_en','aciklama_ru','gorsel','etiket','aktif_mi'] as $k) {
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
                kategori     VARCHAR(20) NOT NULL DEFAULT 'meyve',
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

        // İlk kurulumda varsayılan ürünleri seed et
        $urunCount = $this->db->selectOne("SELECT COUNT(*) AS n FROM site_urunler");
        if ((int)($urunCount['n'] ?? 0) === 0) {
            $defaults = self::defaultProducts();
            $sira = 1;
            foreach ($defaults as $d) {
                $this->db->query(
                    "INSERT IGNORE INTO site_urunler
                       (sira, kategori, ad_tr, ad_en, ad_ru, slug_tr, slug_en, slug_ru,
                        aciklama_tr, aciklama_en, aciklama_ru,
                        sezon_tr, sezon_en, sezon_ru,
                        paketleme_tr, paketleme_en, paketleme_ru,
                        pazarlar, gorsel, etiket, aktif_mi)
                     VALUES
                       (:sira, :kategori, :ad_tr,:ad_en,:ad_ru,:s_tr,:s_en,:s_ru,
                        :a_tr,:a_en,:a_ru,
                        :sz_tr,:sz_en,:sz_ru,
                        :p_tr,:p_en,:p_ru,
                        :pazar,:g,'FRESH',1)",
                    [
                        ':sira'=>$sira++,
                        ':kategori'=>$d['kategori'] ?? self::defaultCategoryForName($d['tr']),
                        ':ad_tr'=>$d['tr'],':ad_en'=>$d['en'],':ad_ru'=>$d['ru'],
                        ':s_tr'=>$this->uniqueSlug($d['slug_tr'], 'slug_tr'),':s_en'=>$this->uniqueSlug($d['slug_en'], 'slug_en'),':s_ru'=>$this->uniqueSlug($d['slug_en'], 'slug_ru'),
                        ':a_tr'=>$d['desc_tr'],':a_en'=>$d['desc_en'],':a_ru'=>$d['desc_ru'],
                        ':sz_tr'=>$d['season_tr']??'',':sz_en'=>$d['season_en']??'',':sz_ru'=>$d['season_ru']??'',
                        ':p_tr'=>$d['pack_tr']??'',':p_en'=>$d['pack_en']??'',':p_ru'=>$d['pack_ru']??'',
                        ':pazar'=>$d['markets']??'Avrupa, BAE, Suudi Arabistan, Mısır, Rusya',
                        ':g'=>$d['img'],
                    ]
                );
            }
        }

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

        $this->backfillProductCategories();
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
            'kategori'     => "ALTER TABLE site_urunler ADD COLUMN kategori VARCHAR(20) NOT NULL DEFAULT 'meyve' AFTER sira",
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
        return in_array($category, ['meyve', 'sebze'], true) ? $category : 'meyve';
    }

    private function backfillProductCategories(): void
    {
        try {
            $done = $this->db->selectOne(
                "SELECT setting_value FROM site_settings WHERE setting_key = 'product_category_backfill_done' LIMIT 1"
            );
            if (($done['setting_value'] ?? '') === '1') {
                return;
            }

            $rows = $this->db->select("SELECT id, ad_tr, kategori FROM site_urunler");
            foreach ($rows as $row) {
                $current = (string)($row['kategori'] ?? '');
                $category = in_array($current, ['meyve', 'sebze'], true)
                    ? $current
                    : self::defaultCategoryForName((string)($row['ad_tr'] ?? ''));

                if (self::defaultCategoryForName((string)($row['ad_tr'] ?? '')) === 'sebze') {
                    $category = 'sebze';
                }

                $this->db->query(
                    "UPDATE site_urunler SET kategori = :kategori WHERE id = :id",
                    [
                        ':kategori' => $category,
                        ':id' => (int)$row['id'],
                    ]
                );
            }

            $this->ayarKaydet('product_category_backfill_done', '1');
        } catch (Throwable $e) {
            // Older installs without the column are handled by ensureUrunlerColumns before this method runs.
        }
    }

    private static function defaultCategoryForName(string $name): string
    {
        $slug = self::slugify($name);
        $vegetables = ['domates', 'salatalik', 'biber', 'patates', 'sogan'];
        return in_array($slug, $vegetables, true) ? 'sebze' : 'meyve';
    }

    /** Genişletilmiş varsayılan ürün kataloğu (16 ürün, çok dilli + slug + sezon + paketleme) */
    private static function defaultProducts(): array
    {
        return [
            ['tr'=>'Kiraz','en'=>'Cherries','ru'=>'Черешня','slug_tr'=>'kiraz','slug_en'=>'cherries',
             'desc_tr'=>'Anadolu’nun yüksek rakım bahçelerinden, sert ve parlak premium kirazlar.',
             'desc_en'=>'Premium high-altitude cherries from Anatolia — firm, glossy, export-ready.',
             'desc_ru'=>'Премиум черешня из горных садов Анатолии — плотная, блестящая, готовая к экспорту.',
             'season_tr'=>'Mayıs – Temmuz','season_en'=>'May – July','season_ru'=>'Май – Июль',
             'pack_tr'=>'2 kg / 5 kg karton, plastik kaset','pack_en'=>'2 kg / 5 kg carton, plastic punnet','pack_ru'=>'2 кг / 5 кг картон, пластик',
             'markets'=>'Avrupa, BAE, Rusya, Suudi Arabistan',
             'img'=>'https://images.unsplash.com/photo-1610116306796-6fea9f4fae38?auto=format&fit=crop&w=900&q=80'],

            ['tr'=>'Kayısı','en'=>'Apricots','ru'=>'Абрикосы','slug_tr'=>'kayisi','slug_en'=>'apricots',
             'desc_tr'=>'Malatya ve çevresinden aromatik kayısılar, ihracat kalibrelerinde.',
             'desc_en'=>'Aromatic apricots from Malatya region — calibrated for export.',
             'desc_ru'=>'Ароматные абрикосы из региона Малатья, откалиброванные на экспорт.',
             'season_tr'=>'Haziran – Ağustos','season_en'=>'June – August','season_ru'=>'Июнь – Август',
             'pack_tr'=>'5 kg karton','pack_en'=>'5 kg carton','pack_ru'=>'5 кг картон',
             'markets'=>'Avrupa, Rusya, Mısır',
             'img'=>'https://images.unsplash.com/photo-1568702846914-96b305d2aaeb?auto=format&fit=crop&w=900&q=80'],

            ['tr'=>'Şeftali','en'=>'Peaches','ru'=>'Персики','slug_tr'=>'seftali','slug_en'=>'peaches',
             'desc_tr'=>'Sulu ve aromatik şeftaliler, sıkı kabuklu kalibrelerle.',
             'desc_en'=>'Juicy, aromatic peaches with firm skin and uniform calibration.',
             'desc_ru'=>'Сочные ароматные персики с плотной кожицей и единой калибровкой.',
             'season_tr'=>'Haziran – Eylül','season_en'=>'June – September','season_ru'=>'Июнь – Сентябрь',
             'pack_tr'=>'5 kg / 7 kg karton','pack_en'=>'5 kg / 7 kg carton','pack_ru'=>'5 кг / 7 кг',
             'markets'=>'Avrupa, BAE, Rusya',
             'img'=>'https://images.unsplash.com/photo-1595142428229-69f3a4ce0500?auto=format&fit=crop&w=900&q=80'],

            ['tr'=>'Nektarin','en'=>'Nectarines','ru'=>'Нектарины','slug_tr'=>'nektarin','slug_en'=>'nectarines',
             'desc_tr'=>'Düz kabuklu, parlak ve sıkı nektarinler — uzun raf ömrü.',
             'desc_en'=>'Smooth-skinned, firm nectarines with long shelf life.',
             'desc_ru'=>'Гладкие плотные нектарины с длительным сроком хранения.',
             'season_tr'=>'Haziran – Eylül','season_en'=>'June – September','season_ru'=>'Июнь – Сентябрь',
             'pack_tr'=>'5 kg karton','pack_en'=>'5 kg carton','pack_ru'=>'5 кг',
             'markets'=>'Avrupa, BAE',
             'img'=>'https://images.unsplash.com/photo-1572635148818-ef6fd45eb394?auto=format&fit=crop&w=900&q=80'],

            ['tr'=>'Armut','en'=>'Pears','ru'=>'Груши','slug_tr'=>'armut','slug_en'=>'pears',
             'desc_tr'=>'Deveci, Santa Maria ve Williams çeşitleri — uzun raf ömrü.',
             'desc_en'=>'Deveci, Santa Maria and Williams varieties with long shelf life.',
             'desc_ru'=>'Сорта Devechi, Santa Maria и Williams с длительным хранением.',
             'season_tr'=>'Ağustos – Şubat','season_en'=>'August – February','season_ru'=>'Август – Февраль',
             'pack_tr'=>'10 kg karton','pack_en'=>'10 kg carton','pack_ru'=>'10 кг',
             'markets'=>'Rusya, Avrupa, BAE',
             'img'=>'https://images.unsplash.com/photo-1518977676601-b53f82aba655?auto=format&fit=crop&w=900&q=80'],

            ['tr'=>'Karpuz','en'=>'Watermelon','ru'=>'Арбуз','slug_tr'=>'karpuz','slug_en'=>'watermelon',
             'desc_tr'=>'Yüksek brix değerli karpuzlar — Adana ve Antalya menşeli.',
             'desc_en'=>'High-brix watermelons from Adana and Antalya regions.',
             'desc_ru'=>'Арбузы с высоким уровнем брикса из Аданы и Анталии.',
             'season_tr'=>'Mayıs – Eylül','season_en'=>'May – September','season_ru'=>'Май – Сентябрь',
             'pack_tr'=>'Dökme / kafes paletli','pack_en'=>'Bulk / cage pallet','pack_ru'=>'Навалом / клеть',
             'markets'=>'BAE, Suudi Arabistan, Mısır',
             'img'=>'https://images.unsplash.com/photo-1587049352846-4a222e784d38?auto=format&fit=crop&w=900&q=80'],

            ['tr'=>'Domates','en'=>'Tomatoes','ru'=>'Помидоры','slug_tr'=>'domates','slug_en'=>'tomatoes',
             'desc_tr'=>'Salkım, beef, çeri ve cluster domates çeşitleri — yıl boyu.',
             'desc_en'=>'Cluster, beef, cherry and vine tomatoes — year-round availability.',
             'desc_ru'=>'Кисти, биф, черри и грозди — круглогодично.',
             'season_tr'=>'Yıl boyu','season_en'=>'Year-round','season_ru'=>'Круглый год',
             'pack_tr'=>'5 kg / 6 kg karton, plastik kaset','pack_en'=>'5 kg / 6 kg carton, plastic punnet','pack_ru'=>'5 кг / 6 кг',
             'markets'=>'Rusya, Avrupa, BAE',
             'img'=>'https://images.unsplash.com/photo-1546470427-0d49b3d6b3a0?auto=format&fit=crop&w=900&q=80'],

            ['tr'=>'Salatalık','en'=>'Cucumber','ru'=>'Огурцы','slug_tr'=>'salatalik','slug_en'=>'cucumber',
             'desc_tr'=>'Sera ve tarla salatalık — Antalya menşeli, uzun ve mini çeşitleri.',
             'desc_en'=>'Greenhouse and field cucumbers — long and mini varieties from Antalya.',
             'desc_ru'=>'Тепличные и полевые огурцы из Анталии — длинные и мини.',
             'season_tr'=>'Yıl boyu','season_en'=>'Year-round','season_ru'=>'Круглый год',
             'pack_tr'=>'5 kg / 6 kg karton','pack_en'=>'5 kg / 6 kg carton','pack_ru'=>'5 кг / 6 кг',
             'markets'=>'Rusya, BAE, Avrupa',
             'img'=>'https://images.unsplash.com/photo-1604977042946-1eecc30f269e?auto=format&fit=crop&w=900&q=80'],

            ['tr'=>'Biber','en'=>'Pepper','ru'=>'Перец','slug_tr'=>'biber','slug_en'=>'pepper',
             'desc_tr'=>'Renkli kapya, dolmalık ve sivri biberler — sera kalitesinde.',
             'desc_en'=>'Coloured kapya, bell and pointed peppers — greenhouse quality.',
             'desc_ru'=>'Цветной капия, болгарский и острый перец — тепличного качества.',
             'season_tr'=>'Yıl boyu','season_en'=>'Year-round','season_ru'=>'Круглый год',
             'pack_tr'=>'5 kg karton','pack_en'=>'5 kg carton','pack_ru'=>'5 кг',
             'markets'=>'Rusya, Avrupa, BAE',
             'img'=>'https://images.unsplash.com/photo-1563565375-f3fdfdbefa83?auto=format&fit=crop&w=900&q=80'],

            ['tr'=>'Üzüm','en'=>'Grapes','ru'=>'Виноград','slug_tr'=>'uzum','slug_en'=>'grapes',
             'desc_tr'=>'Sultani, çekirdeksiz, kırmızı küre ve siyah çeşitleri.',
             'desc_en'=>'Sultana, seedless, crimson and black varieties.',
             'desc_ru'=>'Султана, без косточек, красные и тёмные сорта.',
             'season_tr'=>'Temmuz – Kasım','season_en'=>'July – November','season_ru'=>'Июль – Ноябрь',
             'pack_tr'=>'5 kg karton, plastik kaset','pack_en'=>'5 kg carton, plastic punnet','pack_ru'=>'5 кг',
             'markets'=>'Avrupa, Rusya, BAE',
             'img'=>'https://images.unsplash.com/photo-1599819177626-754a82d80ed8?auto=format&fit=crop&w=900&q=80'],

            ['tr'=>'İncir','en'=>'Figs','ru'=>'Инжир','slug_tr'=>'incir','slug_en'=>'figs',
             'desc_tr'=>'Aydın bölgesi siyah incir — taze ihracat kalibrelerinde.',
             'desc_en'=>'Black figs from Aydın — fresh export calibrations.',
             'desc_ru'=>'Чёрный инжир из Айдына — экспортная калибровка.',
             'season_tr'=>'Ağustos – Ekim','season_en'=>'August – October','season_ru'=>'Август – Октябрь',
             'pack_tr'=>'1 kg / 2 kg karton','pack_en'=>'1 kg / 2 kg carton','pack_ru'=>'1 кг / 2 кг',
             'markets'=>'Avrupa, BAE',
             'img'=>'https://images.unsplash.com/photo-1574477440844-cf5c8b1f3f7d?auto=format&fit=crop&w=900&q=80'],

            ['tr'=>'Kivi','en'=>'Kiwi','ru'=>'Киви','slug_tr'=>'kivi','slug_en'=>'kiwi',
             'desc_tr'=>'Karadeniz menşeli Hayward kivi — uzun raf ömrü.',
             'desc_en'=>'Hayward kiwi from the Black Sea region — long shelf life.',
             'desc_ru'=>'Киви Hayward из черноморского региона — длительное хранение.',
             'season_tr'=>'Ekim – Mart','season_en'=>'October – March','season_ru'=>'Октябрь – Март',
             'pack_tr'=>'3,5 kg / 10 kg karton','pack_en'=>'3.5 kg / 10 kg carton','pack_ru'=>'3,5 / 10 кг',
             'markets'=>'Rusya, BAE',
             'img'=>'https://images.unsplash.com/photo-1585059895524-72359e06133a?auto=format&fit=crop&w=900&q=80'],

            ['tr'=>'Portakal','en'=>'Orange','ru'=>'Апельсины','slug_tr'=>'portakal','slug_en'=>'orange',
             'desc_tr'=>'Washington, Valencia ve Navel portakallar — Akdeniz bölgesi.',
             'desc_en'=>'Washington, Valencia and Navel oranges — Mediterranean region.',
             'desc_ru'=>'Сорта Washington, Valencia и Navel — Средиземноморье.',
             'season_tr'=>'Kasım – Mayıs','season_en'=>'November – May','season_ru'=>'Ноябрь – Май',
             'pack_tr'=>'15 kg ağ / karton','pack_en'=>'15 kg net / carton','pack_ru'=>'15 кг сетка / картон',
             'markets'=>'Rusya, BAE, Suudi Arabistan',
             'img'=>'https://images.unsplash.com/photo-1547514701-42782101795e?auto=format&fit=crop&w=900&q=80'],

            ['tr'=>'Nar','en'=>'Pomegranate','ru'=>'Гранат','slug_tr'=>'nar','slug_en'=>'pomegranate',
             'desc_tr'=>'Hicaz nar — yüksek brix, koyu kırmızı taneli.',
             'desc_en'=>'Hicaz pomegranate — high brix, deep-red kernels.',
             'desc_ru'=>'Гранат Hicaz — высокий брикс, тёмно-красные зёрна.',
             'season_tr'=>'Eylül – Şubat','season_en'=>'September – February','season_ru'=>'Сентябрь – Февраль',
             'pack_tr'=>'10 kg karton','pack_en'=>'10 kg carton','pack_ru'=>'10 кг',
             'markets'=>'Avrupa, Rusya, BAE',
             'img'=>'https://images.unsplash.com/photo-1541344999736-83eca272f6fc?auto=format&fit=crop&w=900&q=80'],

            ['tr'=>'Patates','en'=>'Potatoes','ru'=>'Картофель','slug_tr'=>'patates','slug_en'=>'potatoes',
             'desc_tr'=>'Yemeklik ve sanayi patatesleri — kalibrelenmiş, fırçalanmış.',
             'desc_en'=>'Table and industrial potatoes — calibrated and brushed.',
             'desc_ru'=>'Столовый и промышленный картофель — калиброванный и щёткой.',
             'season_tr'=>'Yıl boyu','season_en'=>'Year-round','season_ru'=>'Круглый год',
             'pack_tr'=>'10 kg / 25 kg çuval','pack_en'=>'10 kg / 25 kg sack','pack_ru'=>'10 / 25 кг мешок',
             'markets'=>'BAE, Suudi Arabistan, Mısır',
             'img'=>'https://images.unsplash.com/photo-1518977676601-b53f82aba655?auto=format&fit=crop&w=900&q=80'],

            ['tr'=>'Soğan','en'=>'Onion','ru'=>'Лук','slug_tr'=>'sogan','slug_en'=>'onion',
             'desc_tr'=>'Sarı, kırmızı ve beyaz soğan — düşük su oranı, ihracata hazır.',
             'desc_en'=>'Yellow, red and white onions — low water content, export-ready.',
             'desc_ru'=>'Жёлтый, красный и белый лук — низкая влажность, на экспорт.',
             'season_tr'=>'Yıl boyu','season_en'=>'Year-round','season_ru'=>'Круглый год',
             'pack_tr'=>'10 kg / 25 kg ağ / çuval','pack_en'=>'10 kg / 25 kg net / sack','pack_ru'=>'10 / 25 кг сетка',
             'markets'=>'BAE, Suudi Arabistan, Mısır',
             'img'=>'https://images.unsplash.com/photo-1518977956812-cd3dbadaaf31?auto=format&fit=crop&w=900&q=80'],
        ];
    }
}
