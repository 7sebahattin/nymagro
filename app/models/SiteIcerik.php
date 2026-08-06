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

    /**
     * Bakanlık tescilli 8 gerçek Nymagro ürünü — db/nymagro-urunler.sql ile
     * birebir aynı veri. İlk kurulumda seedNymagroUrunleriIfMissing() ile
     * otomatik yüklenir; elle SQL çalıştırmaya gerek yoktur.
     */
    private const NYMAGRO_URUNLERI = [
        [
            'sira' => 1,
            'kategori' => "micro",
            'ad_tr' => "SILATRIX",
            'ad_en' => "SILATRIX",
            'ad_ru' => "SILATRIX",
            'slug_tr' => "silatrix",
            'slug_en' => "silatrix",
            'slug_ru' => "silatrix",
            'aciklama_tr' => "Mangan (Mn-EDTA) ve çinko (Zn-EDTA) içeren, tamamı EDTA ile şelatlı sıvı mikro bitki besin karışımı. Suda çözünür mangan %1, suda çözünür çinko %1. EDTA şelatı 2–10 pH aralığında kararlıdır; toprakta bağlanmaz, bitki tarafından hızla alınır. Yapraktan ve damlama sulama ile uygulanır.",
            'aciklama_en' => "A liquid micronutrient blend of manganese (Mn-EDTA) and zinc (Zn-EDTA), fully chelated with EDTA. Water soluble manganese 1%, water soluble zinc 1%. The EDTA chelate is stable across a pH range of 2–10; it resists fixation in the soil and is rapidly taken up by the plant. Applied both foliar and via drip irrigation.",
            'aciklama_ru' => "Жидкая смесь микроэлементов — марганца (Mn-EDTA) и цинка (Zn-EDTA), полностью хелатированных ЭДТА. Водорастворимый марганец 1%, водорастворимый цинк 1%. Хелат ЭДТА стабилен в диапазоне pH 2–10, не связывается в почве и быстро усваивается растением. Применяется по листу и через капельное орошение.",
            'sezon_tr' => "Vejetasyon dönemi boyunca, eksiklik belirtisi görüldüğünde",
            'sezon_en' => "Throughout the growing season, when deficiency symptoms appear",
            'sezon_ru' => "В течение вегетации, при признаках дефицита",
            'paketleme_tr' => "1 L (1,1 kg) · 5 L (5,5 kg) · 10 L (11 kg)",
            'paketleme_en' => "1 L (1.1 kg) · 5 L (5.5 kg) · 10 L (11 kg)",
            'paketleme_ru' => "1 л (1,1 кг) · 5 л (5,5 кг) · 10 л (11 кг)",
            'tescil_no' => "2026TK13785",
            'ph_araligi' => "2–10",
            'icerik_tr' => "Suda Çözünür Mangan (Mn) (Tamamı EDTA ile şelatlıdır.)|%1\nSuda Çözünür Çinko (Zn) (Tamamı EDTA ile şelatlıdır.)|%1",
            'icerik_en' => "Water Soluble Manganese (Mn) (Fully chelated with EDTA.)|1%\nWater Soluble Zinc (Zn) (Fully chelated with EDTA.)|1%",
            'icerik_ru' => "Водорастворимый марганец (Mn) (Полностью хелатирован ЭДТА.)|1%\nВодорастворимый цинк (Zn) (Полностью хелатирован ЭДТА.)|1%",
            'gorsel' => "",
            'etiket' => "TESCİLLİ",
            'aktif_mi' => 1,
        ],
        [
            'sira' => 2,
            'kategori' => "micro",
            'ad_tr' => "SILECKO MoZ",
            'ad_en' => "SILECKO MoZ",
            'ad_ru' => "SILECKO MoZ",
            'slug_tr' => "silecko-moz",
            'slug_en' => "silecko-moz",
            'slug_ru' => "silecko-moz",
            'aciklama_tr' => "Molibden (Mo) ve çinko (Zn) içeren sıvı mikro bitki besin ürünü. Suda çözünür molibden %0,1, suda çözünür çinko %1,9. Baklagillerde ve sebzelerde azot metabolizmasını destekler. Uygulama suyunun pH değerinin 6,0–6,7 aralığında olması önerilir.",
            'aciklama_en' => "A liquid micronutrient product containing molybdenum (Mo) and zinc (Zn). Water soluble molybdenum 0.1%, water soluble zinc 1.9%. Supports nitrogen metabolism in legumes and vegetables. An application water pH of 6.0–6.7 is recommended.",
            'aciklama_ru' => "Жидкий микроэлементный продукт с молибденом (Mo) и цинком (Zn). Водорастворимый молибден 0,1%, водорастворимый цинк 1,9%. Поддерживает азотный обмен у бобовых и овощных культур. Рекомендуемый pH рабочего раствора — 6,0–6,7.",
            'sezon_tr' => "Vejetasyon dönemi boyunca",
            'sezon_en' => "Throughout the growing season",
            'sezon_ru' => "В течение вегетации",
            'paketleme_tr' => "1 L (1,15 kg) · 5 L (5,75 kg) · 10 L (11,5 kg)",
            'paketleme_en' => "1 L (1.15 kg) · 5 L (5.75 kg) · 10 L (11.5 kg)",
            'paketleme_ru' => "1 л (1,15 кг) · 5 л (5,75 кг) · 10 л (11,5 кг)",
            'tescil_no' => "2026TK13783",
            'ph_araligi' => "",
            'icerik_tr' => "Suda Çözünür Molibden (Mo)|%0,1\nSuda Çözünür Çinko (Zn)|%1,9",
            'icerik_en' => "Water Soluble Molybdenum (Mo)|0.1%\nWater Soluble Zinc (Zn)|1.9%",
            'icerik_ru' => "Водорастворимый молибден (Mo)|0,1%\nВодорастворимый цинк (Zn)|1,9%",
            'gorsel' => "",
            'etiket' => "TESCİLLİ",
            'aktif_mi' => 1,
        ],
        [
            'sira' => 3,
            'kategori' => "micro",
            'ad_tr' => "CUPPERA",
            'ad_en' => "CUPPERA",
            'ad_ru' => "CUPPERA",
            'slug_tr' => "cuppera",
            'slug_en' => "cuppera",
            'slug_ru' => "cuppera",
            'aciklama_tr' => "Bakır (Cu) içeren sıvı mikro bitki besin ürünü. Suda çözünür bakır %6,5. Bakır eksikliğinin görüldüğü koşullarda bitkinin besin dengesini destekler ve mantari hastalıklara karşı doğal dirence katkı sağlar.",
            'aciklama_en' => "A liquid micronutrient product containing copper (Cu). Water soluble copper 6.5%. Supports the plant's nutrient balance where copper deficiency occurs and contributes to natural resistance against fungal disease.",
            'aciklama_ru' => "Жидкий микроэлементный продукт с медью (Cu). Водорастворимая медь 6,5%. Поддерживает баланс питания при дефиците меди и способствует естественной устойчивости к грибковым заболеваниям.",
            'sezon_tr' => "Vejetasyon dönemi boyunca",
            'sezon_en' => "Throughout the growing season",
            'sezon_ru' => "В течение вегетации",
            'paketleme_tr' => "1 L (1,25 kg) · 5 L (6,25 kg) · 10 L (12,5 kg)",
            'paketleme_en' => "1 L (1.25 kg) · 5 L (6.25 kg) · 10 L (12.5 kg)",
            'paketleme_ru' => "1 л (1,25 кг) · 5 л (6,25 кг) · 10 л (12,5 кг)",
            'tescil_no' => "2026TK13784",
            'ph_araligi' => "",
            'icerik_tr' => "Suda Çözünür Bakır (Cu)|%6,5",
            'icerik_en' => "Water Soluble Copper (Cu)|6.5%",
            'icerik_ru' => "Водорастворимая медь (Cu)|6,5%",
            'gorsel' => "",
            'etiket' => "TESCİLLİ",
            'aktif_mi' => 1,
        ],
        [
            'sira' => 4,
            'kategori' => "micro",
            'ad_tr' => "FORTIVIUM",
            'ad_en' => "FORTIVIUM",
            'ad_ru' => "FORTIVIUM",
            'slug_tr' => "fortivium",
            'slug_en' => "fortivium",
            'slug_ru' => "fortivium",
            'aciklama_tr' => "Bakır (Cu-EDTA), mangan (Mn-EDTA) ve çinko (Zn-EDTA) içeren, tamamı EDTA ile şelatlı sıvı mikro bitki besin karışımı. Suda çözünür bakır %1,1, mangan %0,4, çinko %0,5. EDTA şelatı 2–9 pH aralığında kararlıdır.",
            'aciklama_en' => "A liquid micronutrient blend of copper (Cu-EDTA), manganese (Mn-EDTA) and zinc (Zn-EDTA), fully chelated with EDTA. Water soluble copper 1.1%, manganese 0.4%, zinc 0.5%. The EDTA chelate is stable across a pH range of 2–9.",
            'aciklama_ru' => "Жидкая смесь микроэлементов — меди (Cu-EDTA), марганца (Mn-EDTA) и цинка (Zn-EDTA), полностью хелатированных ЭДТА. Водорастворимая медь 1,1%, марганец 0,4%, цинк 0,5%. Хелат стабилен в диапазоне pH 2–9.",
            'sezon_tr' => "Vejetasyon dönemi boyunca, eksiklik belirtisi görüldüğünde",
            'sezon_en' => "Throughout the growing season, when deficiency symptoms appear",
            'sezon_ru' => "В течение вегетации, при признаках дефицита",
            'paketleme_tr' => "1 L (1,1 kg) · 5 L (5,5 kg) · 10 L (11 kg)",
            'paketleme_en' => "1 L (1.1 kg) · 5 L (5.5 kg) · 10 L (11 kg)",
            'paketleme_ru' => "1 л (1,1 кг) · 5 л (5,5 кг) · 10 л (11 кг)",
            'tescil_no' => "2026TK13786",
            'ph_araligi' => "2–9",
            'icerik_tr' => "Suda Çözünür Bakır (Cu) (Tamamı EDTA ile şelatlıdır.)|%1,1\nSuda Çözünür Mangan (Mn) (Tamamı EDTA ile şelatlıdır.)|%0,4\nSuda Çözünür Çinko (Zn) (Tamamı EDTA ile şelatlıdır.)|%0,5",
            'icerik_en' => "Water Soluble Copper (Cu) (Fully chelated with EDTA.)|1.1%\nWater Soluble Manganese (Mn) (Fully chelated with EDTA.)|0.4%\nWater Soluble Zinc (Zn) (Fully chelated with EDTA.)|0.5%",
            'icerik_ru' => "Водорастворимая медь (Cu) (Полностью хелатирована ЭДТА.)|1,1%\nВодорастворимый марганец (Mn) (Полностью хелатирован ЭДТА.)|0,4%\nВодорастворимый цинк (Zn) (Полностью хелатирован ЭДТА.)|0,5%",
            'gorsel' => "",
            'etiket' => "TESCİLLİ",
            'aktif_mi' => 1,
        ],
        [
            'sira' => 5,
            'kategori' => "micro",
            'ad_tr' => "NYMATEX",
            'ad_en' => "NYMATEX",
            'ad_ru' => "NYMATEX",
            'slug_tr' => "nymatex",
            'slug_en' => "nymatex",
            'slug_ru' => "nymatex",
            'aciklama_tr' => "Bakır (Cu-EDTA) ve çinko (Zn-EDTA) içeren, tamamı EDTA ile şelatlı sıvı mikro bitki besin karışımı. Suda çözünür bakır %0,5, çinko %1,5. EDTA şelatı 2–10 pH aralığında kararlıdır; yapraktan ve damlama sulama ile uygulanır.",
            'aciklama_en' => "A liquid micronutrient blend of copper (Cu-EDTA) and zinc (Zn-EDTA), fully chelated with EDTA. Water soluble copper 0.5%, zinc 1.5%. The EDTA chelate is stable across a pH range of 2–10; applied both foliar and via drip irrigation.",
            'aciklama_ru' => "Жидкая смесь микроэлементов — меди (Cu-EDTA) и цинка (Zn-EDTA), полностью хелатированных ЭДТА. Водорастворимая медь 0,5%, цинк 1,5%. Хелат стабилен в диапазоне pH 2–10, применяется по листу и через капельное орошение.",
            'sezon_tr' => "Vejetasyon dönemi boyunca, eksiklik belirtisi görüldüğünde",
            'sezon_en' => "Throughout the growing season, when deficiency symptoms appear",
            'sezon_ru' => "В течение вегетации, при признаках дефицита",
            'paketleme_tr' => "1 L (1,1 kg) · 5 L (5,5 kg) · 10 L (11 kg)",
            'paketleme_en' => "1 L (1.1 kg) · 5 L (5.5 kg) · 10 L (11 kg)",
            'paketleme_ru' => "1 л (1,1 кг) · 5 л (5,5 кг) · 10 л (11 кг)",
            'tescil_no' => "2026TK13855",
            'ph_araligi' => "2–10",
            'icerik_tr' => "Suda Çözünür Bakır (Cu) (Tamamı EDTA ile şelatlıdır.)|%0,5\nSuda Çözünür Çinko (Zn) (Tamamı EDTA ile şelatlıdır.)|%1,5",
            'icerik_en' => "Water Soluble Copper (Cu) (Fully chelated with EDTA.)|0.5%\nWater Soluble Zinc (Zn) (Fully chelated with EDTA.)|1.5%",
            'icerik_ru' => "Водорастворимая медь (Cu) (Полностью хелатирована ЭДТА.)|0,5%\nВодорастворимый цинк (Zn) (Полностью хелатирован ЭДТА.)|1,5%",
            'gorsel' => "",
            'etiket' => "TESCİLLİ",
            'aktif_mi' => 1,
        ],
        [
            'sira' => 6,
            'kategori' => "macro",
            'ad_tr' => "BRILIXA",
            'ad_en' => "BRILIXA",
            'ad_ru' => "BRILIXA",
            'slug_tr' => "brilixa",
            'slug_en' => "brilixa",
            'slug_ru' => "brilixa",
            'aciklama_tr' => "Fosfor (P2O5 %23) ve potasyum (K2O %36) ağırlıklı, iz elementlerle (B, Fe-EDTA, Mn-EDTA, Mo, Zn-EDTA) desteklenmiş toz makro besin ürünü. Meyve tutumu, kalibre ve dayanıklılık üzerinde doğrudan etkilidir. Suda tamamen çözünür; yapraktan ve damlama sulama ile uygulanır.",
            'aciklama_en' => "A powder macronutrient product built around phosphorus (P2O5 23%) and potassium (K2O 36%), supported by trace elements (B, Fe-EDTA, Mn-EDTA, Mo, Zn-EDTA). Has a direct effect on fruit set, size and firmness. Fully water soluble; applied both foliar and via drip irrigation.",
            'aciklama_ru' => "Порошковый макроэлементный продукт на основе фосфора (P2O5 23%) и калия (K2O 36%), дополненный микроэлементами (B, Fe-EDTA, Mn-EDTA, Mo, Zn-EDTA). Напрямую влияет на завязь, калибр и прочность плодов. Полностью водорастворим, применяется по листу и через капельное орошение.",
            'sezon_tr' => "Çiçeklenmeden meyve tutumuna",
            'sezon_en' => "From flowering to fruit set",
            'sezon_ru' => "От цветения до завязи плодов",
            'paketleme_tr' => "0,5 kg · 1 kg · 5 kg · 10 kg",
            'paketleme_en' => "0.5 kg · 1 kg · 5 kg · 10 kg",
            'paketleme_ru' => "0,5 кг · 1 кг · 5 кг · 10 кг",
            'tescil_no' => "2026TK13932",
            'ph_araligi' => "2–9",
            'icerik_tr' => "Suda Çözünür Fosfor Pentaoksit (P2O5)|%23\nSuda Çözünür Potasyum Oksit (K2O)|%36\nSuda Çözünür Bor (B)|%0,05\nSuda Çözünür Demir (Fe) (EDTA ile şelatlı)|%0,2\nSuda Çözünür Mangan (Mn) (EDTA ile şelatlı)|%0,1\nSuda Çözünür Molibden (Mo)|%0,05\nSuda Çözünür Çinko (Zn) (EDTA ile şelatlı)|%0,1",
            'icerik_en' => "Water Soluble Phosphorus Pentoxide (P2O5)|23%\nWater Soluble Potassium Oxide (K2O)|36%\nWater Soluble Boron (B)|0.05%\nWater Soluble Iron (Fe) (EDTA chelated)|0.2%\nWater Soluble Manganese (Mn) (EDTA chelated)|0.1%\nWater Soluble Molybdenum (Mo)|0.05%\nWater Soluble Zinc (Zn) (EDTA chelated)|0.1%",
            'icerik_ru' => "Водорастворимый пентоксид фосфора (P2O5)|23%\nВодорастворимый оксид калия (K2O)|36%\nВодорастворимый бор (B)|0,05%\nВодорастворимое железо (Fe) (хелат ЭДТА)|0,2%\nВодорастворимый марганец (Mn) (хелат ЭДТА)|0,1%\nВодорастворимый молибден (Mo)|0,05%\nВодорастворимый цинк (Zn) (хелат ЭДТА)|0,1%",
            'gorsel' => "",
            'etiket' => "TESCİLLİ",
            'aktif_mi' => 1,
        ],
        [
            'sira' => 7,
            'kategori' => "macro",
            'ad_tr' => "SLASTYK",
            'ad_en' => "SLASTYK",
            'ad_ru' => "SLASTYK",
            'slug_tr' => "slastyk",
            'slug_en' => "slastyk",
            'slug_ru' => "slastyk",
            'aciklama_tr' => "Fosfor (P2O5 %10) ve potasyum (K2O %12) içeren, bor (B %0,3) ile desteklenmiş sıvı makro besin ürünü. Çiçeklenme ve meyve tutumu döneminde kullanılır; kalibre ve şeker birikimini destekler.",
            'aciklama_en' => "A liquid macronutrient product containing phosphorus (P2O5 10%) and potassium (K2O 12%), supported by boron (B 0.3%). Used during flowering and fruit set; supports size and sugar accumulation.",
            'aciklama_ru' => "Жидкий макроэлементный продукт с фосфором (P2O5 10%) и калием (K2O 12%), дополненный бором (B 0,3%). Применяется в период цветения и завязи плодов, способствует калибру и накоплению сахаров.",
            'sezon_tr' => "Çiçeklenme ve meyve tutumu döneminde",
            'sezon_en' => "During flowering and fruit set",
            'sezon_ru' => "В период цветения и завязи плодов",
            'paketleme_tr' => "1 L (1,3 kg) · 5 L (6,5 kg)",
            'paketleme_en' => "1 L (1.3 kg) · 5 L (6.5 kg)",
            'paketleme_ru' => "1 л (1,3 кг) · 5 л (6,5 кг)",
            'tescil_no' => "2026TK13933",
            'ph_araligi' => "",
            'icerik_tr' => "Suda Çözünür Fosfor Pentaoksit (P2O5)|%10\nSuda Çözünür Potasyum Oksit (K2O)|%12\nSuda Çözünür Bor (B)|%0,3",
            'icerik_en' => "Water Soluble Phosphorus Pentoxide (P2O5)|10%\nWater Soluble Potassium Oxide (K2O)|12%\nWater Soluble Boron (B)|0.3%",
            'icerik_ru' => "Водорастворимый пентоксид фосфора (P2O5)|10%\nВодорастворимый оксид калия (K2O)|12%\nВодорастворимый бор (B)|0,3%",
            'gorsel' => "",
            'etiket' => "TESCİLLİ",
            'aktif_mi' => 1,
        ],
        [
            'sira' => 8,
            'kategori' => "macro",
            'ad_tr' => "NutriDyn KALICALZ",
            'ad_en' => "NutriDyn KALICALZ",
            'ad_ru' => "NutriDyn KALICALZ",
            'slug_tr' => "nutridyn-kalicalz",
            'slug_en' => "nutridyn-kalicalz",
            'slug_ru' => "nutridyn-kalicalz",
            'aciklama_tr' => "Azot (N %5), potasyum (K2O %17), kalsiyum (CaO %14) ve magnezyum (MgO %2) içeren, demir (Fe-EDTA) ile desteklenmiş toz makro besin ürünü. Meyve sertliği, raf ömrü ve hücre duvarı dayanıklılığını destekler.",
            'aciklama_en' => "A powder macronutrient product containing nitrogen (N 5%), potassium (K2O 17%), calcium (CaO 14%) and magnesium (MgO 2%), supported by iron (Fe-EDTA). Supports fruit firmness, shelf life and cell wall strength.",
            'aciklama_ru' => "Порошковый макроэлементный продукт с азотом (N 5%), калием (K2O 17%), кальцием (CaO 14%) и магнием (MgO 2%), дополненный железом (Fe-EDTA). Поддерживает плотность плодов, лёжкость и прочность клеточной стенки.",
            'sezon_tr' => "Meyve gelişimi ve olgunlaşma döneminde",
            'sezon_en' => "During fruit development and ripening",
            'sezon_ru' => "В период развития и созревания плодов",
            'paketleme_tr' => "1 kg · 5 kg · 10 kg",
            'paketleme_en' => "1 kg · 5 kg · 10 kg",
            'paketleme_ru' => "1 кг · 5 кг · 10 кг",
            'tescil_no' => "2026TK13914",
            'ph_araligi' => "2–10",
            'icerik_tr' => "Toplam Azot (N)|%5\nNitrat Azotu (N)|%5\nSuda Çözünür Potasyum Oksit (K2O)|%17\nSuda Çözünür Kalsiyum Oksit (CaO)|%14\nSuda Çözünür Magnezyum Oksit (MgO)|%2\nSuda Çözünür Demir (Fe) (EDTA ile şelatlı)|%0,1",
            'icerik_en' => "Total Nitrogen (N)|5%\nNitrate Nitrogen (N)|5%\nWater Soluble Potassium Oxide (K2O)|17%\nWater Soluble Calcium Oxide (CaO)|14%\nWater Soluble Magnesium Oxide (MgO)|2%\nWater Soluble Iron (Fe) (EDTA chelated)|0.1%",
            'icerik_ru' => "Общий азот (N)|5%\nНитратный азот (N)|5%\nВодорастворимый оксид калия (K2O)|17%\nВодорастворимый оксид кальция (CaO)|14%\nВодорастворимый оксид магния (MgO)|2%\nВодорастворимое железо (Fe) (хелат ЭДТА)|0,1%",
            'gorsel' => "",
            'etiket' => "TESCİLLİ",
            'aktif_mi' => 1,
        ],
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

        // İlk kurulumda 8 gerçek Nymagro ürününü otomatik yükle (elle SQL
        // çalıştırmaya gerek kalmaz). Tek seferlik ve tamamen zararsızdır —
        // devralınan eski ürünleri yalnızca ilk seferde pasife alır, kendi
        // ürünlerini pasife alanları veya sildiklerini asla geri açmaz.
        $this->seedNymagroUrunleriIfMissing();

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

    /**
     * Bakanlık tescilli 8 ürünü ilk kurulumda otomatik yükler.
     * site_settings.nymagro_urunler_seeded bayrağı ile tek seferlik çalışır;
     * tekrar çağrılırsa hiçbir şey yapmaz (site sahibinin panelden yaptığı
     * değişiklikleri asla ezmez).
     */
    private function seedNymagroUrunleriIfMissing(): void
    {
        $bayrak = $this->db->selectOne(
            "SELECT setting_value FROM site_settings WHERE setting_key = 'nymagro_urunler_seeded'"
        );
        if (!empty($bayrak['setting_value'])) {
            return;
        }

        // Devralınan eski ürünleri (varsa) pasife al — yalnızca bu ilk seferde
        $this->db->query("UPDATE site_urunler SET aktif_mi = 0, updated_at = NOW() WHERE aktif_mi = 1 AND silindi_mi = 0");

        foreach (self::NYMAGRO_URUNLERI as $u) {
            $var = $this->db->selectOne("SELECT id FROM site_urunler WHERE slug_tr = :s", [':s' => $u['slug_tr']]);
            if ($var) {
                continue;
            }
            $kolonlar = array_keys($u);
            $yerTutucular = array_map(fn($k) => ':' . $k, $kolonlar);
            $params = [];
            foreach ($u as $k => $v) {
                $params[':' . $k] = $v;
            }
            $this->db->query(
                "INSERT INTO site_urunler (" . implode(', ', $kolonlar) . ") VALUES (" . implode(', ', $yerTutucular) . ")",
                $params
            );
        }

        $this->db->query(
            "INSERT INTO site_settings (setting_key, setting_value) VALUES ('nymagro_urunler_seeded', '1')
             ON DUPLICATE KEY UPDATE setting_value = '1'"
        );
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
