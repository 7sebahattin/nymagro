-- =====================================================================
--  NYMAGRO — Ürün kataloğu (T.C. Tarım ve Orman Bakanlığı tescilli 8 ürün)
--
--  nymagro-rebrand.sql çalıştırıldıktan SONRA uygulanır.
--  Eski yaş sebze-meyve ürünlerini pasife alır ve gerçek Nymagro ürünlerini
--  tescil belgelerinden doğrulanmış verilerle ekler.
--
--  Doğrulanmış: tescil no, garanti edilen içerik (%), şelat pH kararlılığı,
--  ambalaj seçenekleri (T.C. Tarım ve Orman Bakanlığı tescil belgelerinden).
--
--  ⚠️ Bitki bazlı uygulama dozu (doz_tr/en/ru) tabloları bu betikte YOK —
--  panelden ürün başına, tescil belgesindeki doz tablosuna bakılarak
--  "Bitki|Yapraktan|Damlama" formatında elle girilmelidir (bkz. KURULUM.md).
--
--  Çalıştırmadan önce yedek alın.
-- =====================================================================

SET NAMES utf8mb4;
START TRANSACTION;

-- ---------------------------------------------------------------------
-- 1) Devralınan yaş sebze-meyve ürünlerini pasife al
--    (silmez — geri almak isterseniz aktif_mi = 1 yapmanız yeterli)
-- ---------------------------------------------------------------------
UPDATE site_urunler
SET aktif_mi = 0, updated_at = NOW()
WHERE aktif_mi = 1
  AND silindi_mi = 0;

-- ---------------------------------------------------------------------
-- 2) Nymagro ürünleri (8 adet, hepsi Bakanlık tescilli)
--    gorsel alanı boş bırakıldı; panelden her ürüne görsel yükleyin
--    (yüklenene kadar public/img/urun-placeholder.jpg kullanılır).
-- ---------------------------------------------------------------------
INSERT INTO site_urunler
    (sira, kategori, ad_tr, ad_en, ad_ru,
     slug_tr, slug_en, slug_ru,
     aciklama_tr, aciklama_en, aciklama_ru,
     sezon_tr, sezon_en, sezon_ru,
     paketleme_tr, paketleme_en, paketleme_ru,
     tescil_no, ph_araligi,
     icerik_tr, icerik_en, icerik_ru,
     gorsel, etiket, aktif_mi)
VALUES
-- ── SILATRIX — Tescil No 2026TK13785 (Mn-EDTA + Zn-EDTA)
(1, 'micro', 'SILATRIX', 'SILATRIX', 'SILATRIX',
 'silatrix', 'silatrix', 'silatrix',
 'Mangan (Mn-EDTA) ve çinko (Zn-EDTA) içeren, tamamı EDTA ile şelatlı sıvı mikro bitki besin karışımı. Suda çözünür mangan %1, suda çözünür çinko %1. EDTA şelatı 2–10 pH aralığında kararlıdır; toprakta bağlanmaz, bitki tarafından hızla alınır. Yapraktan ve damlama sulama ile uygulanır.',
 'A liquid micronutrient blend of manganese (Mn-EDTA) and zinc (Zn-EDTA), fully chelated with EDTA. Water soluble manganese 1%, water soluble zinc 1%. The EDTA chelate is stable across a pH range of 2–10; it resists fixation in the soil and is rapidly taken up by the plant. Applied both foliar and via drip irrigation.',
 'Жидкая смесь микроэлементов — марганца (Mn-EDTA) и цинка (Zn-EDTA), полностью хелатированных ЭДТА. Водорастворимый марганец 1%, водорастворимый цинк 1%. Хелат ЭДТА стабилен в диапазоне pH 2–10, не связывается в почве и быстро усваивается растением. Применяется по листу и через капельное орошение.',
 'Vejetasyon dönemi boyunca, eksiklik belirtisi görüldüğünde', 'Throughout the growing season, when deficiency symptoms appear', 'В течение вегетации, при признаках дефицита',
 '1 L (1,1 kg) · 5 L (5,5 kg) · 10 L (11 kg)', '1 L (1.1 kg) · 5 L (5.5 kg) · 10 L (11 kg)', '1 л (1,1 кг) · 5 л (5,5 кг) · 10 л (11 кг)',
 '2026TK13785', '2–10',
 'Suda Çözünür Mangan (Mn) (Tamamı EDTA ile şelatlıdır.)|%1\nSuda Çözünür Çinko (Zn) (Tamamı EDTA ile şelatlıdır.)|%1',
 'Water Soluble Manganese (Mn) (Fully chelated with EDTA.)|1%\nWater Soluble Zinc (Zn) (Fully chelated with EDTA.)|1%',
 'Водорастворимый марганец (Mn) (Полностью хелатирован ЭДТА.)|1%\nВодорастворимый цинк (Zn) (Полностью хелатирован ЭДТА.)|1%',
 '', 'TESCİLLİ', 1),

-- ── SILECKO MoZ — Tescil No 2026TK13783 (Mo + Zn sülfat, şelatsız)
(2, 'micro', 'SILECKO MoZ', 'SILECKO MoZ', 'SILECKO MoZ',
 'silecko-moz', 'silecko-moz', 'silecko-moz',
 'Molibden (Mo) ve çinko (Zn) içeren sıvı mikro bitki besin ürünü. Suda çözünür molibden %0,1, suda çözünür çinko %1,9. Baklagillerde ve sebzelerde azot metabolizmasını destekler. Uygulama suyunun pH değerinin 6,0–6,7 aralığında olması önerilir.',
 'A liquid micronutrient product containing molybdenum (Mo) and zinc (Zn). Water soluble molybdenum 0.1%, water soluble zinc 1.9%. Supports nitrogen metabolism in legumes and vegetables. An application water pH of 6.0–6.7 is recommended.',
 'Жидкий микроэлементный продукт с молибденом (Mo) и цинком (Zn). Водорастворимый молибден 0,1%, водорастворимый цинк 1,9%. Поддерживает азотный обмен у бобовых и овощных культур. Рекомендуемый pH рабочего раствора — 6,0–6,7.',
 'Vejetasyon dönemi boyunca', 'Throughout the growing season', 'В течение вегетации',
 '1 L (1,15 kg) · 5 L (5,75 kg) · 10 L (11,5 kg)', '1 L (1.15 kg) · 5 L (5.75 kg) · 10 L (11.5 kg)', '1 л (1,15 кг) · 5 л (5,75 кг) · 10 л (11,5 кг)',
 '2026TK13783', '',
 'Suda Çözünür Molibden (Mo)|%0,1\nSuda Çözünür Çinko (Zn)|%1,9',
 'Water Soluble Molybdenum (Mo)|0.1%\nWater Soluble Zinc (Zn)|1.9%',
 'Водорастворимый молибден (Mo)|0,1%\nВодорастворимый цинк (Zn)|1,9%',
 '', 'TESCİLLİ', 1),

-- ── CUPPERA — Tescil No 2026TK13784 (Cu sülfat, şelatsız)
(3, 'micro', 'CUPPERA', 'CUPPERA', 'CUPPERA',
 'cuppera', 'cuppera', 'cuppera',
 'Bakır (Cu) içeren sıvı mikro bitki besin ürünü. Suda çözünür bakır %6,5. Bakır eksikliğinin görüldüğü koşullarda bitkinin besin dengesini destekler ve mantari hastalıklara karşı doğal dirence katkı sağlar.',
 'A liquid micronutrient product containing copper (Cu). Water soluble copper 6.5%. Supports the plant''s nutrient balance where copper deficiency occurs and contributes to natural resistance against fungal disease.',
 'Жидкий микроэлементный продукт с медью (Cu). Водорастворимая медь 6,5%. Поддерживает баланс питания при дефиците меди и способствует естественной устойчивости к грибковым заболеваниям.',
 'Vejetasyon dönemi boyunca', 'Throughout the growing season', 'В течение вегетации',
 '1 L (1,25 kg) · 5 L (6,25 kg) · 10 L (12,5 kg)', '1 L (1.25 kg) · 5 L (6.25 kg) · 10 L (12.5 kg)', '1 л (1,25 кг) · 5 л (6,25 кг) · 10 л (12,5 кг)',
 '2026TK13784', '',
 'Suda Çözünür Bakır (Cu)|%6,5',
 'Water Soluble Copper (Cu)|6.5%',
 'Водорастворимая медь (Cu)|6,5%',
 '', 'TESCİLLİ', 1),

-- ── FORTIVIUM — Tescil No 2026TK13786 (Cu-EDTA + Mn-EDTA + Zn-EDTA)
(4, 'micro', 'FORTIVIUM', 'FORTIVIUM', 'FORTIVIUM',
 'fortivium', 'fortivium', 'fortivium',
 'Bakır (Cu-EDTA), mangan (Mn-EDTA) ve çinko (Zn-EDTA) içeren, tamamı EDTA ile şelatlı sıvı mikro bitki besin karışımı. Suda çözünür bakır %1,1, mangan %0,4, çinko %0,5. EDTA şelatı 2–9 pH aralığında kararlıdır.',
 'A liquid micronutrient blend of copper (Cu-EDTA), manganese (Mn-EDTA) and zinc (Zn-EDTA), fully chelated with EDTA. Water soluble copper 1.1%, manganese 0.4%, zinc 0.5%. The EDTA chelate is stable across a pH range of 2–9.',
 'Жидкая смесь микроэлементов — меди (Cu-EDTA), марганца (Mn-EDTA) и цинка (Zn-EDTA), полностью хелатированных ЭДТА. Водорастворимая медь 1,1%, марганец 0,4%, цинк 0,5%. Хелат стабилен в диапазоне pH 2–9.',
 'Vejetasyon dönemi boyunca, eksiklik belirtisi görüldüğünde', 'Throughout the growing season, when deficiency symptoms appear', 'В течение вегетации, при признаках дефицита',
 '1 L (1,1 kg) · 5 L (5,5 kg) · 10 L (11 kg)', '1 L (1.1 kg) · 5 L (5.5 kg) · 10 L (11 kg)', '1 л (1,1 кг) · 5 л (5,5 кг) · 10 л (11 кг)',
 '2026TK13786', '2–9',
 'Suda Çözünür Bakır (Cu) (Tamamı EDTA ile şelatlıdır.)|%1,1\nSuda Çözünür Mangan (Mn) (Tamamı EDTA ile şelatlıdır.)|%0,4\nSuda Çözünür Çinko (Zn) (Tamamı EDTA ile şelatlıdır.)|%0,5',
 'Water Soluble Copper (Cu) (Fully chelated with EDTA.)|1.1%\nWater Soluble Manganese (Mn) (Fully chelated with EDTA.)|0.4%\nWater Soluble Zinc (Zn) (Fully chelated with EDTA.)|0.5%',
 'Водорастворимая медь (Cu) (Полностью хелатирована ЭДТА.)|1,1%\nВодорастворимый марганец (Mn) (Полностью хелатирован ЭДТА.)|0,4%\nВодорастворимый цинк (Zn) (Полностью хелатирован ЭДТА.)|0,5%',
 '', 'TESCİLLİ', 1),

-- ── NYMATEX — Tescil No 2026TK13855 (Cu-EDTA + Zn-EDTA)
(5, 'micro', 'NYMATEX', 'NYMATEX', 'NYMATEX',
 'nymatex', 'nymatex', 'nymatex',
 'Bakır (Cu-EDTA) ve çinko (Zn-EDTA) içeren, tamamı EDTA ile şelatlı sıvı mikro bitki besin karışımı. Suda çözünür bakır %0,5, çinko %1,5. EDTA şelatı 2–10 pH aralığında kararlıdır; yapraktan ve damlama sulama ile uygulanır.',
 'A liquid micronutrient blend of copper (Cu-EDTA) and zinc (Zn-EDTA), fully chelated with EDTA. Water soluble copper 0.5%, zinc 1.5%. The EDTA chelate is stable across a pH range of 2–10; applied both foliar and via drip irrigation.',
 'Жидкая смесь микроэлементов — меди (Cu-EDTA) и цинка (Zn-EDTA), полностью хелатированных ЭДТА. Водорастворимая медь 0,5%, цинк 1,5%. Хелат стабилен в диапазоне pH 2–10, применяется по листу и через капельное орошение.',
 'Vejetasyon dönemi boyunca, eksiklik belirtisi görüldüğünde', 'Throughout the growing season, when deficiency symptoms appear', 'В течение вегетации, при признаках дефицита',
 '1 L (1,1 kg) · 5 L (5,5 kg) · 10 L (11 kg)', '1 L (1.1 kg) · 5 L (5.5 kg) · 10 L (11 kg)', '1 л (1,1 кг) · 5 л (5,5 кг) · 10 л (11 кг)',
 '2026TK13855', '2–10',
 'Suda Çözünür Bakır (Cu) (Tamamı EDTA ile şelatlıdır.)|%0,5\nSuda Çözünür Çinko (Zn) (Tamamı EDTA ile şelatlıdır.)|%1,5',
 'Water Soluble Copper (Cu) (Fully chelated with EDTA.)|0.5%\nWater Soluble Zinc (Zn) (Fully chelated with EDTA.)|1.5%',
 'Водорастворимая медь (Cu) (Полностью хелатирована ЭДТА.)|0,5%\nВодорастворимый цинк (Zn) (Полностью хелатирован ЭДТА.)|1,5%',
 '', 'TESCİLLİ', 1),

-- ── BRILIXA 0-23-36+TE — Tescil No 2026TK13932 (toz, P-K + iz element)
(6, 'macro', 'BRILIXA', 'BRILIXA', 'BRILIXA',
 'brilixa', 'brilixa', 'brilixa',
 'Fosfor (P2O5 %23) ve potasyum (K2O %36) ağırlıklı, iz elementlerle (B, Fe-EDTA, Mn-EDTA, Mo, Zn-EDTA) desteklenmiş toz makro besin ürünü. Meyve tutumu, kalibre ve dayanıklılık üzerinde doğrudan etkilidir. Suda tamamen çözünür; yapraktan ve damlama sulama ile uygulanır.',
 'A powder macronutrient product built around phosphorus (P2O5 23%) and potassium (K2O 36%), supported by trace elements (B, Fe-EDTA, Mn-EDTA, Mo, Zn-EDTA). Has a direct effect on fruit set, size and firmness. Fully water soluble; applied both foliar and via drip irrigation.',
 'Порошковый макроэлементный продукт на основе фосфора (P2O5 23%) и калия (K2O 36%), дополненный микроэлементами (B, Fe-EDTA, Mn-EDTA, Mo, Zn-EDTA). Напрямую влияет на завязь, калибр и прочность плодов. Полностью водорастворим, применяется по листу и через капельное орошение.',
 'Çiçeklenmeden meyve tutumuna', 'From flowering to fruit set', 'От цветения до завязи плодов',
 '0,5 kg · 1 kg · 5 kg · 10 kg', '0.5 kg · 1 kg · 5 kg · 10 kg', '0,5 кг · 1 кг · 5 кг · 10 кг',
 '2026TK13932', '2–9',
 'Suda Çözünür Fosfor Pentaoksit (P2O5)|%23\nSuda Çözünür Potasyum Oksit (K2O)|%36\nSuda Çözünür Bor (B)|%0,05\nSuda Çözünür Demir (Fe) (EDTA ile şelatlı)|%0,2\nSuda Çözünür Mangan (Mn) (EDTA ile şelatlı)|%0,1\nSuda Çözünür Molibden (Mo)|%0,05\nSuda Çözünür Çinko (Zn) (EDTA ile şelatlı)|%0,1',
 'Water Soluble Phosphorus Pentoxide (P2O5)|23%\nWater Soluble Potassium Oxide (K2O)|36%\nWater Soluble Boron (B)|0.05%\nWater Soluble Iron (Fe) (EDTA chelated)|0.2%\nWater Soluble Manganese (Mn) (EDTA chelated)|0.1%\nWater Soluble Molybdenum (Mo)|0.05%\nWater Soluble Zinc (Zn) (EDTA chelated)|0.1%',
 'Водорастворимый пентоксид фосфора (P2O5)|23%\nВодорастворимый оксид калия (K2O)|36%\nВодорастворимый бор (B)|0,05%\nВодорастворимое железо (Fe) (хелат ЭДТА)|0,2%\nВодорастворимый марганец (Mn) (хелат ЭДТА)|0,1%\nВодорастворимый молибден (Mo)|0,05%\nВодорастворимый цинк (Zn) (хелат ЭДТА)|0,1%',
 '', 'TESCİLLİ', 1),

-- ── SLASTYK 0-10-12+TE — Tescil No 2026TK13933 (sıvı, P-K + B)
(7, 'macro', 'SLASTYK', 'SLASTYK', 'SLASTYK',
 'slastyk', 'slastyk', 'slastyk',
 'Fosfor (P2O5 %10) ve potasyum (K2O %12) içeren, bor (B %0,3) ile desteklenmiş sıvı makro besin ürünü. Çiçeklenme ve meyve tutumu döneminde kullanılır; kalibre ve şeker birikimini destekler.',
 'A liquid macronutrient product containing phosphorus (P2O5 10%) and potassium (K2O 12%), supported by boron (B 0.3%). Used during flowering and fruit set; supports size and sugar accumulation.',
 'Жидкий макроэлементный продукт с фосфором (P2O5 10%) и калием (K2O 12%), дополненный бором (B 0,3%). Применяется в период цветения и завязи плодов, способствует калибру и накоплению сахаров.',
 'Çiçeklenme ve meyve tutumu döneminde', 'During flowering and fruit set', 'В период цветения и завязи плодов',
 '1 L (1,3 kg) · 5 L (6,5 kg)', '1 L (1.3 kg) · 5 L (6.5 kg)', '1 л (1,3 кг) · 5 л (6,5 кг)',
 '2026TK13933', '',
 'Suda Çözünür Fosfor Pentaoksit (P2O5)|%10\nSuda Çözünür Potasyum Oksit (K2O)|%12\nSuda Çözünür Bor (B)|%0,3',
 'Water Soluble Phosphorus Pentoxide (P2O5)|10%\nWater Soluble Potassium Oxide (K2O)|12%\nWater Soluble Boron (B)|0.3%',
 'Водорастворимый пентоксид фосфора (P2O5)|10%\nВодорастворимый оксид калия (K2O)|12%\nВодорастворимый бор (B)|0,3%',
 '', 'TESCİLLİ', 1),

-- ── NutriDyn KALICALZ 5-0-17+(14CaO)+(2MgO)+TE — Tescil No 2026TK13914 (toz, N-K-Ca-Mg)
(8, 'macro', 'NutriDyn KALICALZ', 'NutriDyn KALICALZ', 'NutriDyn KALICALZ',
 'nutridyn-kalicalz', 'nutridyn-kalicalz', 'nutridyn-kalicalz',
 'Azot (N %5), potasyum (K2O %17), kalsiyum (CaO %14) ve magnezyum (MgO %2) içeren, demir (Fe-EDTA) ile desteklenmiş toz makro besin ürünü. Meyve sertliği, raf ömrü ve hücre duvarı dayanıklılığını destekler.',
 'A powder macronutrient product containing nitrogen (N 5%), potassium (K2O 17%), calcium (CaO 14%) and magnesium (MgO 2%), supported by iron (Fe-EDTA). Supports fruit firmness, shelf life and cell wall strength.',
 'Порошковый макроэлементный продукт с азотом (N 5%), калием (K2O 17%), кальцием (CaO 14%) и магнием (MgO 2%), дополненный железом (Fe-EDTA). Поддерживает плотность плодов, лёжкость и прочность клеточной стенки.',
 'Meyve gelişimi ve olgunlaşma döneminde', 'During fruit development and ripening', 'В период развития и созревания плодов',
 '1 kg · 5 kg · 10 kg', '1 kg · 5 kg · 10 kg', '1 кг · 5 кг · 10 кг',
 '2026TK13914', '2–10',
 'Toplam Azot (N)|%5\nNitrat Azotu (N)|%5\nSuda Çözünür Potasyum Oksit (K2O)|%17\nSuda Çözünür Kalsiyum Oksit (CaO)|%14\nSuda Çözünür Magnezyum Oksit (MgO)|%2\nSuda Çözünür Demir (Fe) (EDTA ile şelatlı)|%0,1',
 'Total Nitrogen (N)|5%\nNitrate Nitrogen (N)|5%\nWater Soluble Potassium Oxide (K2O)|17%\nWater Soluble Calcium Oxide (CaO)|14%\nWater Soluble Magnesium Oxide (MgO)|2%\nWater Soluble Iron (Fe) (EDTA chelated)|0.1%',
 'Общий азот (N)|5%\nНитратный азот (N)|5%\nВодорастворимый оксид калия (K2O)|17%\nВодорастворимый оксид кальция (CaO)|14%\nВодорастворимый оксид магния (MgO)|2%\nВодорастворимое железо (Fe) (хелат ЭДТА)|0,1%',
 '', 'TESCİLLİ', 1)

ON DUPLICATE KEY UPDATE
    ad_tr        = VALUES(ad_tr),
    kategori     = VALUES(kategori),
    aciklama_tr  = VALUES(aciklama_tr),
    aciklama_en  = VALUES(aciklama_en),
    aciklama_ru  = VALUES(aciklama_ru),
    sezon_tr     = VALUES(sezon_tr),
    sezon_en     = VALUES(sezon_en),
    sezon_ru     = VALUES(sezon_ru),
    paketleme_tr = VALUES(paketleme_tr),
    paketleme_en = VALUES(paketleme_en),
    paketleme_ru = VALUES(paketleme_ru),
    tescil_no    = VALUES(tescil_no),
    ph_araligi   = VALUES(ph_araligi),
    icerik_tr    = VALUES(icerik_tr),
    icerik_en    = VALUES(icerik_en),
    icerik_ru    = VALUES(icerik_ru),
    aktif_mi     = 1,
    silindi_mi   = 0,
    updated_at   = NOW();

COMMIT;

-- =====================================================================
--  SONRAKİ ADIMLAR
-- =====================================================================
--  1) Panel → Site Yönetimi → Ürünler ekranından her ürüne gerçek ürün
--     görseli yükleyin (yüklenene kadar public/img/urun-placeholder.jpg
--     otomatik olarak gösterilir).
--  2) Panel → ürün düzenle → "Uygulama Dozu" alanına, ürünün tescil
--     belgesindeki bitki bazlı doz tablosunu "Bitki|Yapraktan|Damlama"
--     formatında satır satır girin (bu betikte doz tabloları YOKTUR).
--  3) 'kategori' alanındaki değerler ürün grubu slug'larıyla eşleşir:
--     micro (şelatlı mikro element) · macro (makro besin) · biostimulant
