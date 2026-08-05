-- =====================================================================
--  NYMAGRO — Ürün kataloğu şablonu (İSTEĞE BAĞLI)
--
--  nymagro-rebrand.sql çalıştırıldıktan SONRA, istenirse uygulanır.
--  Eski yaş sebze-meyve ürünlerini pasife alır ve Nymagro ürünlerini ekler.
--
--  ⚠️  DİKKAT — İÇERİK DOĞRULAMASI GEREKİYOR
--  Aşağıdaki ürünlerden yalnızca SILATRIX'in teknik verileri elimizdeki
--  tescil belgesinden alınmıştır ve doğrudur. Diğer yedi ürünün açıklamaları
--  ürün adlarından ve gümrük tarife pozisyonlarından çıkarılmış TASLAKTIR.
--  Her ürünü kendi tescil belgesindeki bilgilerle karşılaştırıp düzeltin;
--  garanti edilen içerik oranlarını doğrulamadan yayına almayın.
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
-- 2) Nymagro ürünleri
--    gorsel alanı boş bırakıldı; panelden her ürüne görsel yükleyin.
-- ---------------------------------------------------------------------
INSERT INTO site_urunler
    (sira, kategori, ad_tr, ad_en, ad_ru,
     slug_tr, slug_en, slug_ru,
     aciklama_tr, aciklama_en, aciklama_ru,
     paketleme_tr, paketleme_en, paketleme_ru,
     gorsel, etiket, aktif_mi)
VALUES
-- ── SILATRIX — Tescil No 2026TK13785 (veriler tescil belgesinden, doğrudur)
(1, 'micro', 'SILATRIX', 'SILATRIX', 'SILATRIX',
 'silatrix', 'silatrix', 'silatrix',
 'Mangan (Mn-EDTA) ve Çinko (Zn-EDTA) içeren sıvı mikro bitki besin maddeleri karışımı. Suda çözünür mangan %1, suda çözünür çinko %1; her ikisinin de tamamı EDTA ile şelatlıdır. EDTA şelatı 2–10 pH aralığında kararlıdır. Yapraktan 100 L suya 100–200 ml, damlama sulamada 300–500 ml/da uygulanır. Vejetasyon dönemi boyunca yapraktan 3-4, damlamadan 2-3 uygulama önerilir. EC Fertilizer standardı, Bakanlık Tescil No: 2026TK13785.',
 'A liquid micronutrient mixture containing manganese (Mn-EDTA) and zinc (Zn-EDTA). Water soluble manganese 1%, water soluble zinc 1%; both fully chelated with EDTA. The EDTA chelate is stable between pH 2 and 10. Apply 100–200 ml per 100 L of water foliarly, or 300–500 ml per decare through drip irrigation. EC Fertilizer grade, registration no. 2026TK13785.',
 'Жидкая смесь микроэлементов с марганцем (Mn-EDTA) и цинком (Zn-EDTA). Водорастворимый марганец 1%, водорастворимый цинк 1%; оба полностью хелатированы ЭДТА. Хелат стабилен в диапазоне pH 2–10. Применение: 100–200 мл на 100 л воды по листу или 300–500 мл/да через капельное орошение. Стандарт EC Fertilizer, рег. № 2026TK13785.',
 '1 L (1,1 kg) · 5 L (5,5 kg) · 10 L (11 kg)',
 '1 L (1.1 kg) · 5 L (5.5 kg) · 10 L (11 kg)',
 '1 л (1,1 кг) · 5 л (5,5 кг) · 10 л (11 кг)',
 '', 'TESCİLLİ', 1),

-- ── Aşağıdaki 7 ürünün açıklamaları TASLAKTIR — tescil belgelerinden doğrulayın
(2, 'micro', 'SILECKO MoZ', 'SILECKO MoZ', 'SILECKO MoZ',
 'silecko-moz', 'silecko-moz', 'silecko-moz',
 'Molibden ve çinko içeren sıvı mikro bitki besin ürünü. Baklagiller ve sebzelerde azot metabolizmasını ve sürgün gelişimini destekler. [Teknik içerik tescil belgesinden doğrulanacaktır.]',
 'A liquid micronutrient product containing molybdenum and zinc. Supports nitrogen metabolism and shoot development in legumes and vegetables. [Technical content to be confirmed from the registration certificate.]',
 'Жидкий микроэлементный продукт с молибденом и цинком. Поддерживает азотный обмен и развитие побегов. [Состав уточняется по свидетельству о регистрации.]',
 '5 L', '5 L', '5 л', '', 'YENİ', 1),

(3, 'foliar', 'NYMATEX', 'NYMATEX', 'NYMATEX',
 'nymatex', 'nymatex', 'nymatex',
 'Yapraktan uygulamaya uygun sıvı bitki besin ürünü. Vejetasyon dönemi boyunca bitkinin genel besin dengesini destekler. [Teknik içerik tescil belgesinden doğrulanacaktır.]',
 'A liquid plant nutrition product suited to foliar application. Supports the plant''s overall nutrient balance throughout the growing season. [Technical content to be confirmed from the registration certificate.]',
 'Жидкий продукт питания растений для листовой обработки. Поддерживает общий баланс питания в течение вегетации. [Состав уточняется.]',
 '5 L', '5 L', '5 л', '', 'YENİ', 1),

(4, 'macro', 'SLASTYK', 'SLASTYK', 'SLASTYK',
 'slastyk', 'slastyk', 'slastyk',
 'Meyve kalitesi ve şeker birikimini destekleyen bitki besin ürünü. Olgunlaşma döneminde kalibre ve renklenmeye katkı sağlar. [Teknik içerik tescil belgesinden doğrulanacaktır.]',
 'A plant nutrition product supporting fruit quality and sugar accumulation. Contributes to size and colouring during ripening. [Technical content to be confirmed from the registration certificate.]',
 'Продукт питания растений для качества плодов и накопления сахаров. Способствует калибру и окраске при созревании. [Состав уточняется.]',
 '5 L', '5 L', '5 л', '', 'YENİ', 1),

(5, 'biostimulant', 'FORTIVIUM', 'FORTIVIUM', 'FORTIVIUM',
 'fortivium', 'fortivium', 'fortivium',
 'Bitkinin stres koşullarına dayanımını artırmaya yönelik biyostimülant ürün. Soğuk, sıcak ve tuzluluk stresi sonrası toparlanmayı destekler. [Teknik içerik tescil belgesinden doğrulanacaktır.]',
 'A biostimulant product aimed at increasing the plant''s tolerance to stress. Supports recovery after cold, heat and salinity stress. [Technical content to be confirmed from the registration certificate.]',
 'Биостимулятор для повышения устойчивости растения к стрессу. Поддерживает восстановление после холода, жары и засоления. [Состав уточняется.]',
 '5 L', '5 L', '5 л', '', 'YENİ', 1),

(6, 'micro', 'BRILIXA', 'BRILIXA', 'BRILIXA',
 'brilixa', 'brilixa', 'brilixa',
 'Toz formda bitki besin ürünü. Suda çözünür yapısıyla yapraktan ve damlama sulamayla uygulanabilir. [Teknik içerik tescil belgesinden doğrulanacaktır.]',
 'A powder form plant nutrition product. Its water soluble structure allows both foliar and fertigation application. [Technical content to be confirmed from the registration certificate.]',
 'Порошковый продукт питания растений. Водорастворимая форма позволяет листовое и фертигационное применение. [Состав уточняется.]',
 '1 kg', '1 kg', '1 кг', '', 'YENİ', 1),

(7, 'macro', 'NUTRIDYN KALI CALZ', 'NUTRIDYN KALI CALZ', 'NUTRIDYN KALI CALZ',
 'nutridyn-kali-calz', 'nutridyn-kali-calz', 'nutridyn-kali-calz',
 'Potasyum ve kalsiyum içeren makro besin ürünü. Meyve sertliği, raf ömrü ve hücre duvarı dayanıklılığını destekler. [Teknik içerik tescil belgesinden doğrulanacaktır.]',
 'A macronutrient product containing potassium and calcium. Supports fruit firmness, shelf life and cell wall strength. [Technical content to be confirmed from the registration certificate.]',
 'Макроэлементный продукт с калием и кальцием. Поддерживает плотность плодов, лёжкость и прочность клеточной стенки. [Состав уточняется.]',
 '5 kg', '5 kg', '5 кг', '', 'YENİ', 1),

(8, 'micro', 'CUPPERA', 'CUPPERA', 'CUPPERA',
 'cuppera', 'cuppera', 'cuppera',
 'Bakır içeren sıvı mikro bitki besin ürünü. Bakır eksikliğinin görüldüğü koşullarda bitkinin besin dengesini destekler. [Teknik içerik tescil belgesinden doğrulanacaktır.]',
 'A liquid micronutrient product containing copper. Supports the plant''s nutrient balance where copper deficiency occurs. [Technical content to be confirmed from the registration certificate.]',
 'Жидкий микроэлементный продукт с медью. Поддерживает баланс питания при дефиците меди. [Состав уточняется.]',
 '5 L', '5 L', '5 л', '', 'YENİ', 1)

ON DUPLICATE KEY UPDATE
    ad_tr      = VALUES(ad_tr),
    aktif_mi   = 1,
    silindi_mi = 0,
    updated_at = NOW();

COMMIT;

-- =====================================================================
--  SONRAKİ ADIMLAR
-- =====================================================================
--  1) Panel → Site Yönetimi → Ürünler ekranından her ürüne görsel yükleyin.
--  2) [Teknik içerik ... doğrulanacaktır] ibaresi geçen açıklamaları,
--     ilgili ürünün tescil belgesindeki garanti edilen içerik oranlarıyla
--     değiştirin. Bu ibare kalırken sayfayı yayına almayın.
--  3) 'kategori' alanındaki değerler ürün grubu slug'larıyla eşleşir:
--     micro · foliar · fertigation · macro · biostimulant
