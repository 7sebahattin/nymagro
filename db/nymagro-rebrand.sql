-- =====================================================================
--  NYMAGRO — Veritabanı marka uyarlaması
--  Nuverna Trade altyapısından devralınan canlı içeriği Nymagro'ya çevirir.
--
--  ÖNEMLİ
--  1) Çalıştırmadan ÖNCE mutlaka yedek alın:
--         mysqldump -u KULLANICI -p VERITABANI > yedek_$(date +%F).sql
--  2) Bu betik yalnızca İÇERİK günceller. Tablo, kolon ve indeks
--     yapısına dokunmaz; hiçbir DDL komutu içermez.
--  3) Betik tekrar tekrar çalıştırılabilir (idempotent).
--
--  Kod tarafındaki varsayılanlar zaten güncellendi; bu dosya yalnızca
--  veritabanına daha önce KAYDEDİLMİŞ eski değerleri düzeltmek içindir.
-- =====================================================================

SET NAMES utf8mb4;
START TRANSACTION;

-- ---------------------------------------------------------------------
-- 1) Firma kimliği ve iletişim bilgileri (site_settings)
--    Kayıt yoksa ekler, varsa günceller.
-- ---------------------------------------------------------------------
INSERT INTO site_settings (setting_key, setting_value) VALUES
    ('company_name',  'Nymagro'),
    ('tagline',       'Bitki Besleme · Şelatlı Mikro Element'),
    ('email',         'nymagrotarim@gmail.com'),
    ('phone',         '+90 242 464 12 44'),
    ('whatsapp',      '+90 543 961 73 03'),
    ('whatsapp_link', 'https://wa.me/905439617303'),
    ('address',       'Çamköy Mah. Atatürk Blv. No: 394, Aksu / Antalya, Türkiye'),
    ('website',       'www.nymagro.com'),
    ('site_url',      'https://www.nymagro.com'),
    ('lat',           '36.9459'),
    ('lng',           '30.8437'),
    ('og_image',      '/img/og-image.jpg')
ON DUPLICATE KEY UPDATE
    setting_value = VALUES(setting_value),
    updated_at    = NOW();

-- ---------------------------------------------------------------------
-- 2) Anasayfa hero metinleri
-- ---------------------------------------------------------------------
INSERT INTO site_settings (setting_key, setting_value) VALUES
    ('hero_title_1', 'Toprağa değer,'),
    ('hero_title_2', 'bitkiye güç'),
    ('hero_desc',    'Nymagro, EC Fertilizer standardında üretilen şelatlı mikro element ve sıvı bitki besin ürünlerini İspanya''dan ithal ederek Türk üreticisinin hizmetine sunar.')
ON DUPLICATE KEY UPDATE
    setting_value = VALUES(setting_value),
    updated_at    = NOW();

-- ---------------------------------------------------------------------
-- 3) Çok dilli içerik override'ları (i18n_{dil}_{anahtar})
--    Panelden girilmiş eski ihracat temalı metinleri temizler; boş kalan
--    alanlar app/lang/{tr,en,ru}.php içindeki yeni metinlere düşer.
-- ---------------------------------------------------------------------
DELETE FROM site_settings
WHERE setting_key REGEXP '^i18n_(tr|en|ru)_'
  AND (
        setting_value LIKE '%Nuverna%'
     OR setting_value LIKE '%ihracat%'
     OR setting_value LIKE '%İhracat%'
     OR setting_value LIKE '%yaş sebze%'
     OR setting_value LIKE '%sebze ve meyve%'
     OR setting_value LIKE '%export%'
     OR setting_value LIKE '%Export%'
     OR setting_value LIKE '%экспорт%'
     OR setting_value LIKE '%Экспорт%'
  );

-- ---------------------------------------------------------------------
-- 4) Kalan serbest metinlerde marka adı düzeltmesi
--    (silinmeyen, ama içinde eski marka geçen alanlar)
-- ---------------------------------------------------------------------
UPDATE site_settings
SET setting_value = REPLACE(REPLACE(REPLACE(
        setting_value,
        'Nuverna Trade', 'Nymagro'),
        'nuvernatrade.com', 'nymagro.com'),
        'Nuverna', 'Nymagro'),
    updated_at = NOW()
WHERE setting_value LIKE '%Nuverna%'
   OR setting_value LIKE '%nuvernatrade%';

-- ---------------------------------------------------------------------
-- 5) Ürün kayıtları — eski etiket ve marka referansları
--    Ürünlerin kendisi silinmez; yalnızca metinler düzeltilir.
-- ---------------------------------------------------------------------
UPDATE site_urunler
SET etiket = 'YENİ'
WHERE etiket IN ('FRESH', 'TAZE');

UPDATE site_urunler
SET ad_tr       = REPLACE(ad_tr,       'Nuverna Trade', 'Nymagro'),
    ad_en       = REPLACE(ad_en,       'Nuverna Trade', 'Nymagro'),
    ad_ru       = REPLACE(ad_ru,       'Nuverna Trade', 'Nymagro'),
    aciklama_tr = REPLACE(aciklama_tr, 'Nuverna Trade', 'Nymagro'),
    aciklama_en = REPLACE(aciklama_en, 'Nuverna Trade', 'Nymagro'),
    aciklama_ru = REPLACE(aciklama_ru, 'Nuverna Trade', 'Nymagro'),
    updated_at  = NOW()
WHERE ad_tr       LIKE '%Nuverna%' OR ad_en       LIKE '%Nuverna%' OR ad_ru       LIKE '%Nuverna%'
   OR aciklama_tr LIKE '%Nuverna%' OR aciklama_en LIKE '%Nuverna%' OR aciklama_ru LIKE '%Nuverna%';

-- ---------------------------------------------------------------------
-- 6) Galeri etiketleri
-- ---------------------------------------------------------------------
UPDATE site_galeri
SET etiket_tr = REPLACE(etiket_tr, 'Nuverna Trade', 'Nymagro'),
    etiket_en = REPLACE(etiket_en, 'Nuverna Trade', 'Nymagro'),
    etiket_ru = REPLACE(etiket_ru, 'Nuverna Trade', 'Nymagro')
WHERE etiket_tr LIKE '%Nuverna%' OR etiket_en LIKE '%Nuverna%' OR etiket_ru LIKE '%Nuverna%';

-- ---------------------------------------------------------------------
-- 7) Yönetici hesabı e-posta adresi
-- ---------------------------------------------------------------------
UPDATE users
SET email = 'admin@nymagro.com'
WHERE email = 'admin@nuvernatrade.com';

COMMIT;

-- =====================================================================
--  DOĞRULAMA — betikten sonra çalıştırın, üçü de 0 dönmelidir
-- =====================================================================
-- SELECT COUNT(*) AS kalan_ayar   FROM site_settings WHERE setting_value LIKE '%Nuverna%';
-- SELECT COUNT(*) AS kalan_urun   FROM site_urunler  WHERE aciklama_tr LIKE '%Nuverna%';
-- SELECT COUNT(*) AS kalan_galeri FROM site_galeri   WHERE etiket_tr   LIKE '%Nuverna%';

-- =====================================================================
--  ELLE YAPILACAKLAR (bu betiğin kapsamı dışında)
-- =====================================================================
--  * Ürün kayıtları: site_urunler tablosundaki yaş sebze-meyve ürünleri
--    (kiraz, domates vb.) hâlâ duruyor. Bunları panelden silip yerine
--    SILATRIX, SILECKO MoZ, NYMATEX, SLASTYK, FORTIVIUM, BRILIXA,
--    NUTRIDYN KALI CALZ, CUPPERA ürünlerini eklemeniz gerekir.
--  * Galeri görselleri: eski sevkiyat/paketleme fotoğraflarını panelden
--    kaldırıp ürün ve saha uygulama görselleriyle değiştirin.
--  * Logo: Site Yönetimi ekranından brand/logo/nymagro-logo.png dosyasını
--    yükleyin. Yüklenmezse kod otomatik olarak /img/nymagro-logo.png
--    dosyasını kullanır.
--  * slide1_img … slide4_img ve about_img alanları hâlâ Unsplash'teki yaş
--    sebze-meyve fotoğraflarını gösteriyor. Panelden kendi görsellerinizle
--    değiştirin.
