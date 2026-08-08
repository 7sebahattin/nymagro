<?php
/**
 * Site Yönetimi — Nymagro
 * @var array $ayarlar
 * @var array $urunler
 * @var array $galeri
 * @var array $mesajlar
 * @var int   $yeniMesajSay
 * @var array $flash
 */

// ── Yardımcı fonksiyonlar ─────────────────────────────
$v  = fn(string $k, string $d = '') => htmlspecialchars((string)($ayarlar[$k] ?? $d), ENT_QUOTES);
$tv = fn(string $lang, string $key, string $d = '') => htmlspecialchars(
    (string)($ayarlar['i18n_'.$lang.'_'.$key] ?? $ayarlar[$key] ?? $d), ENT_QUOTES
);
$sliderCount = max(4, min(20, (int)($ayarlar['slide_count'] ?? 4)));
$slideDefault = static function(string $lang, int $index, string $field): string {
    static $cache = [];
    if (!isset($cache[$lang])) {
        $file = ROOT_PATH . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $lang . '.php';
        $cache[$lang] = file_exists($file) ? require $file : [];
    }
    return (string)($cache[$lang]['home']['hero']['slides'][$index - 1][$field] ?? '');
};

// ── Sayfa içerik bölümleri (accordion) ────────────────
$sections = [
    [
        'id'    => 'hero',
        'icon'  => 'fa-bullhorn',
        'title' => 'Anasayfa Hero (Slider)',
        'color' => 'var(--accent)',
        'note'  => 'Anasayfadaki büyük başlık, açıklama ve butonlar aşağıdaki slider kartlarından gelir. 1. Slider, sayfa ilk açıldığında görünen içeriktir.',
    ],
    [
        'id'    => 'about',
        'icon'  => 'fa-circle-info',
        'title' => 'Hakkımızda',
        'color' => 'var(--accent)',
        'fields' => [
            'about_tag'   => ['label'=>'Bölüm etiketi',  'type'=>'text', 'd'=>['tr'=>'Hakkımızda','en'=>'About Us','ru'=>'О нас']],
            'about_title' => ['label'=>'Ana başlık',     'type'=>'text', 'd'=>['tr'=>'Bilgi ve kalite üzerine bitki besleme.','en'=>'Plant nutrition built on knowledge and quality.','ru'=>'Питание растений на основе знаний и качества.']],
            'about_p1'    => ['label'=>'1. paragraf',    'type'=>'area', 'd'=>['tr'=>'Nymagro, İspanya\'da EC Fertilizer standardında üretilen şelatlı mikro element ve sıvı bitki besin ürünlerini Türkiye\'ye ithal eden, Antalya merkezli bir tarım firmasıdır.','en'=>'Nymagro is an Antalya based agricultural company importing chelated micronutrients and liquid plant nutrition products manufactured in Spain to EC Fertilizer standards.','ru'=>'Nymagro — компания из Анталии, импортирующая хелатные микроэлементы и жидкие продукты питания растений, произведённые в Испании по стандарту EC Fertilizer.']],
            'about_p2'    => ['label'=>'2. paragraf',    'type'=>'area', 'd'=>['tr'=>'Amacımız; üreticiye belgeli ürün, doğru doz ve sürekli teknik destek sunmaktır.','en'=>'Our purpose is to offer growers documented products, the right dose and continuous technical support.','ru'=>'Наша цель — документированный продукт, правильная доза и постоянная техническая поддержка.']],
            'about_li1'   => ['label'=>'Madde 1',        'type'=>'text', 'd'=>['tr'=>'Bakanlık tescilli ürün portföyü','en'=>'Officially registered product range','ru'=>'Зарегистрированный ассортимент']],
            'about_li2'   => ['label'=>'Madde 2',        'type'=>'text', 'd'=>['tr'=>'EDTA şelatlı, yüksek alınabilirlik','en'=>'EDTA chelated, high availability','ru'=>'Хелат ЭДТА, высокая усвояемость']],
            'about_li3'   => ['label'=>'Madde 3',        'type'=>'text', 'd'=>['tr'=>'Analize dayalı gübreleme programı','en'=>'Analysis based fertilization programme','ru'=>'Программа питания на основе анализа']],
            'about_li4'   => ['label'=>'Madde 4',        'type'=>'text', 'd'=>['tr'=>'Türkiye genelinde bayi ağı','en'=>'Dealer network across Türkiye','ru'=>'Дилерская сеть по всей Турции']],
        ],
    ],
    [
        'id'    => 'products',
        'icon'  => 'fa-leaf',
        'title' => 'Ürünler Bölümü',
        'color' => '#16a34a',
        'fields' => [
            'products_tag'   => ['label'=>'Bölüm etiketi', 'type'=>'text', 'd'=>['tr'=>'Ürünlerimiz','en'=>'Our Products','ru'=>'Продукция']],
            'products_title' => ['label'=>'Ana başlık',    'type'=>'text', 'd'=>['tr'=>'Bitkinin her döneminde doğru besin','en'=>'The right nutrient at every growth stage','ru'=>'Нужное питание на каждом этапе']],
            'products_desc'  => ['label'=>'Açıklama',      'type'=>'area', 'd'=>['tr'=>'Mikro element eksikliğinden meyve kalitesine kadar geniş bir ürün portföyü.','en'=>'A broad portfolio, from micronutrient deficiencies to fruit quality.','ru'=>'Широкий портфель — от дефицита микроэлементов до качества плодов.']],
        ],
    ],
    [
        'id'    => 'markets',
        'icon'  => 'fa-layer-group',
        'title' => 'Ürün Grupları',
        'color' => '#f3911f',
        'fields' => [
            'markets_tag'   => ['label'=>'Bölüm etiketi', 'type'=>'text', 'd'=>['tr'=>'Ürün Gruplarımız','en'=>'Our Product Groups','ru'=>'Группы продукции']],
            'markets_title' => ['label'=>'Ana başlık',    'type'=>'text', 'd'=>['tr'=>'İhtiyacınıza göre üç ana grup','en'=>'Three core groups for every need','ru'=>'Три основные группы под любую задачу']],
            'markets_desc'  => ['label'=>'Açıklama',      'type'=>'area', 'd'=>['tr'=>'Şelatlı mikro element gübreleri, makro besin çözümleri ve yakında eklenecek biyostimülant ürün grubu.','en'=>'Chelated micronutrient fertilizers, macronutrient solutions and a biostimulant range coming soon.','ru'=>'Хелатные микроэлементные удобрения, макроэлементные решения и биостимуляторы — скоро в ассортименте.']],
            'market_micro'   => ['label'=>'Mikro element grubu — adı',    'type'=>'text', 'd'=>['tr'=>'Şelatlı Mikro Element Gübreleri','en'=>'Chelated Micronutrient Fertilizers','ru'=>'Хелатные микроэлементные удобрения']],
            'market_micro_d' => ['label'=>'Mikro element grubu — açık.',  'type'=>'area', 'd'=>['tr'=>'Çinko, bakır, mangan ve molibden içeren, EDTA ile şelatlı sıvı mikro besin ürünleri.','en'=>'Liquid micronutrient products containing zinc, copper, manganese and molybdenum, chelated with EDTA.','ru'=>'Жидкие микроэлементные продукты с цинком, медью, марганцем и молибденом, хелатированные ЭДТА.']],
            'market_macro'   => ['label'=>'Makro besin grubu — adı',      'type'=>'text', 'd'=>['tr'=>'Makro Besin Ürünleri','en'=>'Macronutrient Products','ru'=>'Макроэлементные продукты']],
            'market_macro_d' => ['label'=>'Makro besin grubu — açık.',    'type'=>'area', 'd'=>['tr'=>'Fosfor, potasyum, kalsiyum ve azot içeren, meyve tutumu ve kalibresini destekleyen sıvı ve toz formda makro besin çözümleri.','en'=>'Liquid and powder macronutrient solutions containing phosphorus, potassium, calcium and nitrogen.','ru'=>'Жидкие и порошковые макроэлементные решения с фосфором, калием, кальцием и азотом.']],
            'market_biostimulant'   => ['label'=>'Biyostimülant grubu — adı',   'type'=>'text', 'd'=>['tr'=>'Biyostimülantlar','en'=>'Biostimulants','ru'=>'Биостимуляторы']],
            'market_biostimulant_d' => ['label'=>'Biyostimülant grubu — açık.', 'type'=>'area', 'd'=>['tr'=>'Kök gelişimini hızlandıran ve bitkinin stres koşullarına dayanımını artıran biyostimülant ürün grubumuz yakında portföyümüze eklenecek.','en'=>'Our biostimulant range, aimed at accelerating root development and boosting tolerance to stress, is coming soon.','ru'=>'Линейка биостимуляторов для ускорения развития корней и повышения устойчивости к стрессу — скоро в ассортименте.']],
        ],
    ],
    [
        'id'    => 'quality',
        'icon'  => 'fa-shield-halved',
        'title' => 'Kalite & Tescil',
        'color' => 'var(--accent)',
        'fields' => [
            'quality_h1'     => ['label'=>'Sayfa başlığı',  'type'=>'text', 'd'=>['tr'=>'Kalite ve Tescil','en'=>'Quality & Registration','ru'=>'Качество и регистрация']],
            'quality_lead'   => ['label'=>'Açıklama',        'type'=>'area', 'd'=>['tr'=>'Üretimden tarlaya belgeli ve kontrollü süreç.','en'=>'A documented, controlled process from production to the field.','ru'=>'Документированный и контролируемый процесс.']],
            'quality_step1_t'=> ['label'=>'Adım 1 başlık',  'type'=>'text', 'd'=>['tr'=>'EC Fertilizer Standardı','en'=>'EC Fertilizer Standard','ru'=>'Стандарт EC Fertilizer']],
            'quality_step1_d'=> ['label'=>'Adım 1 açık.',   'type'=>'text', 'd'=>['tr'=>'AB gübre mevzuatına göre formülasyon.','en'=>'Formulated to EU fertilizer regulation.','ru'=>'Формула по регламенту ЕС.']],
            'quality_step2_t'=> ['label'=>'Adım 2 başlık',  'type'=>'text', 'd'=>['tr'=>'Parti Bazlı Analiz','en'=>'Batch Analysis','ru'=>'Анализ партии']],
            'quality_step2_d'=> ['label'=>'Adım 2 açık.',   'type'=>'text', 'd'=>['tr'=>'İçerik, şelat kararlılığı ve pH kontrolü.','en'=>'Content, chelate stability and pH control.','ru'=>'Состав, стабильность хелата и pH.']],
            'quality_step3_t'=> ['label'=>'Adım 3 başlık',  'type'=>'text', 'd'=>['tr'=>'Bakanlık Tescili','en'=>'Ministry Registration','ru'=>'Регистрация в министерстве']],
            'quality_step3_d'=> ['label'=>'Adım 3 açık.',   'type'=>'text', 'd'=>['tr'=>'Tescil belgesi ile piyasaya arz.','en'=>'Placed on the market with a registration certificate.','ru'=>'Вывод на рынок со свидетельством.']],
            'quality_step4_t'=> ['label'=>'Adım 4 başlık',  'type'=>'text', 'd'=>['tr'=>'İzlenebilirlik','en'=>'Traceability','ru'=>'Прослеживаемость']],
            'quality_step4_d'=> ['label'=>'Adım 4 açık.',   'type'=>'text', 'd'=>['tr'=>'Parti no, üretim ve son kullanma tarihi.','en'=>'Batch number, production and expiry date.','ru'=>'Номер партии, даты производства и годности.']],
            'quality_cert1_t'=> ['label'=>'Rozet 1 başlık',  'type'=>'text', 'd'=>['tr'=>'Bakanlık Tescili','en'=>'Ministry Registration','ru'=>'Регистрация в министерстве']],
            'quality_cert1_d'=> ['label'=>'Rozet 1 açık.',   'type'=>'text', 'd'=>['tr'=>'T.C. Tarım ve Orman Bakanlığı tescil belgesi','en'=>'Registration certificate from the Turkish Ministry of Agriculture and Forestry','ru'=>'Свидетельство Министерства сельского и лесного хозяйства Турции']],
            'quality_cert2_t'=> ['label'=>'Rozet 2 başlık',  'type'=>'text', 'd'=>['tr'=>'EC Fertilizer','en'=>'EC Fertilizer','ru'=>'EC Fertilizer']],
            'quality_cert2_d'=> ['label'=>'Rozet 2 açık.',   'type'=>'text', 'd'=>['tr'=>'AB gübre mevzuatı kriterlerine uygun üretim','en'=>'Manufactured to EU fertilizer regulation criteria','ru'=>'Производство по критериям регламента ЕС об удобрениях']],
            'quality_cert3_t'=> ['label'=>'Rozet 3 başlık',  'type'=>'text', 'd'=>['tr'=>'İthalat Lisansı','en'=>'Import Licence','ru'=>'Импортная лицензия']],
            'quality_cert3_d'=> ['label'=>'Rozet 3 açık.',   'type'=>'text', 'd'=>['tr'=>'Lisanslı ithalatçı — Lisans No: 6002','en'=>'Licensed importer — licence no. 6002','ru'=>'Лицензированный импортёр — лицензия № 6002']],
            'quality_cert4_t'=> ['label'=>'Rozet 4 başlık',  'type'=>'text', 'd'=>['tr'=>'EDTA Şelat','en'=>'EDTA Chelate','ru'=>'Хелат ЭДТА']],
            'quality_cert4_d'=> ['label'=>'Rozet 4 açık.',   'type'=>'text', 'd'=>['tr'=>'2–10 pH aralığında kararlı, çökelme yapmaz','en'=>'Stable between pH 2–10, no precipitation','ru'=>'Стабилен в диапазоне pH 2–10, без осадка']],
        ],
    ],
    [
        'id'    => 'services',
        'icon'  => 'fa-handshake',
        'title' => 'Hizmetlerimiz',
        'color' => 'var(--accent)',
        'fields' => [
            'services_tag'   => ['label'=>'Bölüm etiketi', 'type'=>'text', 'd'=>['tr'=>'Hizmetlerimiz','en'=>'Our Services','ru'=>'Услуги']],
            'services_title' => ['label'=>'Ana başlık',    'type'=>'text', 'd'=>['tr'=>'Üründen teknik desteğe uçtan uca çözüm','en'=>'End-to-end solutions, from product to technical support','ru'=>'Комплексные решения — от продукта до поддержки']],
            'srv1_t'  => ['label'=>'Hizmet 1 başlık', 'type'=>'text', 'd'=>['tr'=>'Ürün Tedariki','en'=>'Product Supply','ru'=>'Поставка продукции']],
            'srv1_d'  => ['label'=>'Hizmet 1 açık.',  'type'=>'text', 'd'=>['tr'=>'Avrupa üretim tesisinden doğrudan tedarik.','en'=>'Direct supply from the European facility.','ru'=>'Прямые поставки с завода в Европе.']],
            'srv2_t'  => ['label'=>'Hizmet 2 başlık', 'type'=>'text', 'd'=>['tr'=>'Teknik Tarla Desteği','en'=>'Technical Field Support','ru'=>'Поддержка в поле']],
            'srv2_d'  => ['label'=>'Hizmet 2 açık.',  'type'=>'text', 'd'=>['tr'=>'Saha ziyareti ve eksiklik teşhisi.','en'=>'Field visits and deficiency diagnosis.','ru'=>'Выезды и диагностика дефицитов.']],
            'srv3_t'  => ['label'=>'Hizmet 3 başlık', 'type'=>'text', 'd'=>['tr'=>'Gübreleme Programı','en'=>'Fertilization Programme','ru'=>'Программа питания']],
            'srv3_d'  => ['label'=>'Hizmet 3 açık.',  'type'=>'text', 'd'=>['tr'=>'Toprak ve yaprak analizine göre program.','en'=>'Programme based on soil and leaf analysis.','ru'=>'Программа по анализу почвы и листа.']],
            'srv4_t'  => ['label'=>'Hizmet 4 başlık', 'type'=>'text', 'd'=>['tr'=>'Bayilik','en'=>'Dealership','ru'=>'Дилерство']],
            'srv4_d'  => ['label'=>'Hizmet 4 açık.',  'type'=>'text', 'd'=>['tr'=>'Bölge temsilciliği ve stok desteği.','en'=>'Regional representation and stock support.','ru'=>'Региональное представительство и склад.']],
            'srv5_t'  => ['label'=>'Hizmet 5 başlık', 'type'=>'text', 'd'=>['tr'=>'Lojistik','en'=>'Logistics','ru'=>'Логистика']],
            'srv5_d'  => ['label'=>'Hizmet 5 açık.',  'type'=>'text', 'd'=>['tr'=>'Düzenli sevkiyat ve hızlı teslimat.','en'=>'Regular shipments and fast delivery.','ru'=>'Регулярные отгрузки и быстрая доставка.']],
            'srv6_t'  => ['label'=>'Hizmet 6 başlık', 'type'=>'text', 'd'=>['tr'=>'Eğitim ve Tanıtım','en'=>'Training and Promotion','ru'=>'Обучение и продвижение']],
            'srv6_d'  => ['label'=>'Hizmet 6 açık.',  'type'=>'text', 'd'=>['tr'=>'Üretici toplantıları ve demo parseller.','en'=>'Grower meetings and demo plots.','ru'=>'Встречи и демоучастки.']],
        ],
    ],
    [
        'id'    => 'gallery',
        'icon'  => 'fa-images',
        'title' => 'Galeri',
        'color' => 'var(--accent2)',
        'fields' => [
            'gallery_tag'   => ['label'=>'Bölüm etiketi', 'type'=>'text', 'd'=>['tr'=>'Galeri','en'=>'Gallery','ru'=>'Галерея']],
            'gallery_title' => ['label'=>'Ana başlık',    'type'=>'text', 'd'=>['tr'=>'Üründen tarlaya','en'=>'From product to field','ru'=>'От продукта до поля']],
            'gallery_desc'  => ['label'=>'Açıklama',      'type'=>'area', 'd'=>['tr'=>'Ürünlerimiz ve saha uygulamalarımızdan anlar.','en'=>'Moments from our products and field applications.','ru'=>'Моменты продукции и полевых применений.']],
        ],
    ],
];
$statusLabels = ['new'=>'Yeni','read'=>'Okundu','replied'=>'Yanıtlandı','spam'=>'Spam'];
$localFlags   = ['tr'=>'🇹🇷','en'=>'🇬🇧','ru'=>'🇷🇺'];
?>

<style>
/* ── Değişkenler ──────────────────────────────────────── */
/* NOT: --ink/--muted/--border tanımlanmıyor — bunlar panel-ui.css'in
   genel tema tokenları; burada yeniden tanımlamak (:root üzerinden)
   TÜM sayfayı (sidebar, topbar, profil menüsü dahil) etkileyip koyu
   temada okunmaz metinlere yol açıyordu. Yalnızca bu sayfaya özgü,
   çakışmayan değişkenler burada kalır. */
:root {
  --p: var(--accent); --ps: rgba(13,98,58,.1);
  --rad: 12px; --card-shadow: 0 1px 4px rgba(0,0,0,.06), 0 4px 16px rgba(13,98,58,.06);
}

/* ── Layout ───────────────────────────────────────────── */
.sm-wrap { max-width: 1100px; }

/* ── Header row ───────────────────────────────────────── */
.sm-header { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:20px; }
.sm-header h2 { font-size:19px; font-weight:800; color:var(--text); display:flex; align-items:center; gap:8px; }
.sm-header p  { font-size:12.5px; color:var(--muted); margin-top:3px; }
.sm-preview-btn { display:inline-flex; align-items:center; gap:6px; padding:9px 16px; background:var(--card-bg); border:1.5px solid var(--border); border-radius:var(--rad); font-size:12.5px; font-weight:700; color:var(--p); text-decoration:none; transition:.15s; }
.sm-preview-btn:hover { background:var(--ps); border-color:var(--p); }

/* ── Tabs ─────────────────────────────────────────────── */
.sm-tabs { display:flex; gap:2px; border-bottom:2px solid var(--border); margin-bottom:20px; overflow-x:auto; -webkit-overflow-scrolling:touch; scrollbar-width:none; }
.sm-tabs::-webkit-scrollbar { display:none; }
.sm-tab { flex:0 0 auto; display:inline-flex; align-items:center; gap:7px; padding:11px 18px; background:none; border:none; border-bottom:3px solid transparent; font-size:13px; font-weight:700; color:var(--muted); cursor:pointer; transition:.15s; white-space:nowrap; margin-bottom:-2px; }
.sm-tab:hover { color:var(--p); }
.sm-tab.active { color:var(--p); border-bottom-color:var(--p); }
.sm-tab-badge { background:var(--p); color:#fff; font-size:10px; font-weight:800; padding:2px 7px; border-radius:99px; line-height:1.2; }
.sm-tab-badge.red { background:#dc2626; }

/* ── Panes ────────────────────────────────────────────── */
.sm-pane { display:none; }
.sm-pane.active { display:block; animation:smFade .2s ease; }
@keyframes smFade { from{opacity:0;transform:translateY(6px)} to{opacity:1;transform:none} }

/* ── Card ─────────────────────────────────────────────── */
.sm-card { background:var(--card-bg); border:1px solid var(--border); border-radius:var(--rad); margin-bottom:14px; box-shadow:var(--card-shadow); overflow:hidden; }
.sm-card-head { padding:16px 20px; display:flex; align-items:center; gap:10px; border-bottom:1px solid var(--border); background:rgba(46,204,113,.10); }
.sm-card-head .ico { width:32px; height:32px; border-radius:8px; background:var(--ps); color:var(--p); display:flex; align-items:center; justify-content:center; font-size:13px; flex-shrink:0; }
.sm-card-head h3 { font-size:14px; font-weight:800; color:var(--text); margin:0; }
.sm-card-head p  { font-size:11.5px; color:var(--muted); margin:2px 0 0; }
.sm-card-body { padding:20px; }

/* ── Form grid ────────────────────────────────────────── */
.sm-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px 20px; }
.sm-grid .full { grid-column:1/-1; }
@media(max-width:700px){ .sm-grid{ grid-template-columns:1fr; } }

.sm-field { display:flex; flex-direction:column; gap:5px; }
.sm-field label { font-size:12px; font-weight:700; color:var(--text2); }
.sm-field .hint  { font-size:11px; color:var(--muted); }
.sm-inp, .sm-area, .sm-sel {
  width:100%; padding:9px 12px; border:1.5px solid var(--border); border-radius:9px;
  font-size:13px; color:var(--text); background:var(--card-bg); outline:none; transition:.15s; font-family:inherit;
}
.sm-inp:focus, .sm-area:focus { border-color:var(--p); box-shadow:0 0 0 3px rgba(13,98,58,.1); }
.sm-area { min-height:78px; resize:vertical; }

/* ── Logo box ─────────────────────────────────────────── */
.sm-logo-box { display:flex; align-items:center; gap:16px; padding:16px; background:rgba(46,204,113,.10); border-radius:10px; border:1px dashed var(--text2); }
.sm-logo-prev { width:80px; height:80px; border-radius:12px; background:linear-gradient(135deg,var(--accent),var(--accent2)); display:flex; align-items:center; justify-content:center; overflow:hidden; flex-shrink:0; }
.sm-logo-prev img { width:100%; height:100%; object-fit:cover; }
.sm-logo-prev svg { width:40px; height:40px; color:#fff; }

/* ── Buttons ──────────────────────────────────────────── */
.sm-btn { display:inline-flex; align-items:center; gap:6px; padding:9px 18px; border:none; border-radius:9px; font-size:13px; font-weight:700; cursor:pointer; transition:.15s; line-height:1; white-space:nowrap; }
.sm-btn-primary { background:linear-gradient(135deg,var(--accent),var(--accent2)); color:#fff; box-shadow:0 4px 14px rgba(13,98,58,.3); }
.sm-btn-primary:hover { filter:brightness(1.07); transform:translateY(-1px); }
.sm-btn-ghost   { background:var(--card-bg); border:1.5px solid var(--border); color:var(--p); }
.sm-btn-ghost:hover { background:rgba(46,204,113,.10); }
.sm-btn-danger  { background:rgba(231,76,60,.15); color:var(--danger); border:none; }
.sm-btn-danger:hover { background:rgba(231,76,60,.28); }
.sm-btn-sm { padding:6px 12px; font-size:12px; }

.sm-save-bar { display:flex; align-items:center; gap:10px; padding:16px 0 24px; flex-wrap:wrap; }

/* ── Image preview tiles ───────────────────────────────── */
.sm-img-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:12px; }
.sm-img-tile { border:1px solid var(--border); border-radius:10px; overflow:hidden; background:rgba(46,204,113,.10); }
.sm-img-prev { height:130px; background:rgba(46,204,113,.12) center/cover no-repeat; }
.sm-img-tile-body { padding:10px 12px; }
.sm-img-tile-body label { font-size:11.5px; font-weight:700; color:var(--text); display:block; margin-bottom:6px; }
.sm-img-tile-body .sm-inp { font-size:12px; padding:7px 10px; }
.sm-img-tile-body input[type=file] { font-size:11.5px; width:100%; }
.sm-section-media { margin-bottom:16px; }
.sm-section-media .sm-img-grid { grid-template-columns:repeat(auto-fit,minmax(240px,360px)); }
.sm-slider-list { display:flex; flex-direction:column; gap:14px; margin-bottom:16px; }
.sm-slider-card { border:1px solid var(--border); border-radius:12px; background:rgba(46,204,113,.10); overflow:hidden; }
.sm-slider-head { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:12px 14px; border-bottom:1px solid var(--border); background:var(--card-bg); }
.sm-slider-head h4 { margin:0; font-size:13px; font-weight:800; color:var(--text); display:flex; align-items:center; gap:7px; }
.sm-slider-body { padding:14px; }
.sm-slider-media { margin-bottom:14px; }

/* ── Accordion ────────────────────────────────────────── */
.accord-item { border:1px solid var(--border); border-radius:var(--rad); margin-bottom:8px; background:var(--card-bg); overflow:hidden; transition:box-shadow .15s; }
.accord-item.open { box-shadow:0 4px 20px rgba(13,98,58,.1); border-color:var(--text2); }
.accord-head { width:100%; display:flex; align-items:center; gap:10px; padding:14px 18px; background:none; border:none; cursor:pointer; text-align:left; transition:background .15s; }
.accord-head:hover { background:rgba(46,204,113,.10); }
.accord-item.open .accord-head { background:rgba(46,204,113,.10); border-bottom:1px solid var(--border); }
.accord-ico { width:30px; height:30px; border-radius:7px; display:flex; align-items:center; justify-content:center; font-size:12px; flex-shrink:0; }
.accord-title { flex:1; font-size:13.5px; font-weight:800; color:var(--text); }
.accord-arrow { color:var(--muted); font-size:11px; transition:transform .2s; }
.accord-item.open .accord-arrow { transform:rotate(180deg); }
.accord-body { display:none; padding:18px 20px 20px; }
.accord-item.open .accord-body { display:block; }

/* ── Language switcher (accordion içi) ────────────────── */
.lang-pills { display:flex; gap:6px; margin-bottom:16px; }
.lang-pill { padding:6px 14px; border:1.5px solid var(--border); border-radius:99px; background:var(--card-bg); color:var(--muted); font-size:12px; font-weight:700; cursor:pointer; transition:.12s; display:inline-flex; align-items:center; gap:5px; }
.lang-pill.active { background:var(--p); color:#fff; border-color:var(--p); box-shadow:0 3px 10px rgba(13,98,58,.25); }
.lang-panel { display:none; }
.lang-panel.active { display:block; }

/* ── Sosyal medya grid ──────────────────────────────────── */
.social-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
@media(max-width:600px){ .social-grid{ grid-template-columns:1fr; } }
.social-item { display:flex; align-items:center; gap:10px; padding:10px 12px; border:1.5px solid var(--border); border-radius:9px; background:var(--card-bg); }
.social-item .sico { width:30px; height:30px; border-radius:7px; display:flex; align-items:center; justify-content:center; font-size:14px; flex-shrink:0; }
.social-item .sm-inp { border:none; padding:0; font-size:13px; flex:1; outline:none; }
.social-item:focus-within { border-color:var(--p); box-shadow:0 0 0 3px rgba(13,98,58,.1); }

/* ── Product grid ─────────────────────────────────────── */
.pr-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(210px,1fr)); gap:12px; }
.pr-card { background:var(--card-bg); border:1px solid var(--border); border-radius:var(--rad); overflow:hidden; transition:.15s; }
.pr-card:hover { transform:translateY(-2px); box-shadow:0 8px 24px rgba(13,98,58,.12); border-color:var(--text2); }
.pr-card-img { height:150px; background:rgba(46,204,113,.12) center/cover no-repeat; position:relative; }
.pr-card-tag { position:absolute; top:8px; left:8px; padding:3px 8px; background:linear-gradient(135deg,var(--accent),var(--accent2)); color:#fff; font-size:10px; font-weight:800; border-radius:4px; letter-spacing:.5px; }
.pr-card-passive { position:absolute; top:8px; right:8px; padding:3px 8px; background:#71717a; color:#fff; font-size:10px; border-radius:4px; }
.pr-card-body { padding:12px 14px 8px; }
.pr-card-body h4 { font-size:13.5px; font-weight:800; color:var(--text); margin-bottom:3px; }
.pr-card-body p  { font-size:11.5px; color:var(--muted); line-height:1.45; height:30px; overflow:hidden; }
.pr-card-foot { padding:8px 12px 12px; display:flex; gap:6px; }
.pr-card-foot button { flex:1; padding:7px; font-size:11.5px; font-weight:700; border:none; border-radius:7px; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; gap:4px; }
.pr-edit { background:rgba(46,204,113,.15); color:var(--accent2); }
.pr-edit:hover { background:var(--border); }
.pr-del  { background:rgba(231,76,60,.15); color:var(--danger); }
.pr-del:hover  { background:rgba(231,76,60,.28); }
.pr-empty { text-align:center; padding:40px; color:var(--muted); background:rgba(46,204,113,.10); border:1px dashed var(--text2); border-radius:var(--rad); }
.pr-empty i { font-size:28px; color:var(--text2); display:block; margin-bottom:10px; }

/* ── Gallery grid ─────────────────────────────────────── */
.gal-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(170px,1fr)); gap:10px; }
.gal-card { border:1px solid var(--border); border-radius:10px; overflow:hidden; background:var(--card-bg); }
.gal-card-img { height:120px; background:rgba(46,204,113,.12) center/cover no-repeat; position:relative; }
.gal-card-passive { position:absolute; top:6px; right:6px; padding:2px 7px; background:#6b7280; color:#fff; font-size:10px; border-radius:4px; }
.gal-card-body { padding:8px 10px; display:flex; align-items:center; justify-content:space-between; }
.gal-card-body span { font-size:11.5px; color:var(--muted); font-weight:600; }
.gal-del { padding:5px 10px; background:rgba(231,76,60,.15); color:var(--danger); border:none; border-radius:6px; font-size:11px; font-weight:700; cursor:pointer; }
.gal-del:hover { background:rgba(231,76,60,.28); }

/* ── Messages table ───────────────────────────────────── */
.msg-wrap { border:1px solid var(--border); border-radius:var(--rad); overflow-x:auto; background:var(--card-bg); }
.msg-table { width:100%; border-collapse:collapse; font-size:12.5px; }
.msg-table th { background:rgba(46,204,113,.10); color:var(--text); font-weight:800; font-size:11.5px; padding:10px 12px; text-align:left; border-bottom:2px solid var(--border); white-space:nowrap; }
.msg-table td { padding:10px 12px; border-bottom:1px solid rgba(46,204,113,.18); vertical-align:top; }
.msg-table tr:last-child td { border-bottom:none; }
.msg-table tr:hover td { background:rgba(46,204,113,.10); }
.msg-badge { display:inline-flex; align-items:center; padding:3px 9px; border-radius:99px; font-size:10.5px; font-weight:800; }
.msg-badge.new     { background:rgba(59,130,246,.15); color:var(--info); }
.msg-badge.read    { background:rgba(46,204,113,.15); color:var(--success); }
.msg-badge.replied { background:rgba(46,204,113,.15); color:var(--accent); }
.msg-badge.spam    { background:rgba(231,76,60,.15); color:var(--danger); }
.msg-locale-badge  { font-size:11px; font-weight:700; display:inline-flex; align-items:center; gap:3px; }
.msg-acts { display:flex; gap:4px; flex-wrap:wrap; }
.msg-acts button { padding:4px 9px; border:none; border-radius:6px; font-size:11px; font-weight:700; cursor:pointer; }
.msg-read-btn    { background:rgba(46,204,113,.15); color:var(--accent2); }
.msg-replied-btn { background:rgba(46,204,113,.15); color:var(--success); }
.msg-del-btn     { background:rgba(231,76,60,.15); color:var(--danger); }
.msg-text-toggle { color:var(--p); font-size:11.5px; font-weight:600; cursor:pointer; display:block; margin-top:3px; }
.msg-full-text   { display:none; background:var(--surface-2); border-radius:6px; padding:8px; margin-top:5px; font-size:12px; white-space:pre-wrap; line-height:1.5; border:1px solid rgba(46,204,113,.24); }
.msg-filter-bar  { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:12px; }
.msg-filter { padding:6px 13px; border:1.5px solid var(--border); background:var(--card-bg); border-radius:99px; font-size:12px; font-weight:700; cursor:pointer; color:var(--muted); }
.msg-filter.active { background:var(--p); color:#fff; border-color:var(--p); }

/* ── Modal ────────────────────────────────────────────── */
.sm-overlay { position:fixed; inset:0; background:rgba(15,60,39,.55); z-index:900; display:none; align-items:center; justify-content:center; padding:16px; backdrop-filter:blur(4px); }
.sm-overlay.open { display:flex; }
.sm-modal { background:var(--ink); border:1px solid var(--border2); border-radius:14px; width:min(680px,100%); max-height:calc(100dvh - 32px); box-shadow:0 24px 60px rgba(0,0,0,.22); display:flex; flex-direction:column; overflow:hidden; }
.sm-modal form { display:flex; flex-direction:column; flex:1; min-height:0; }
.sm-mhdr { background:linear-gradient(135deg,var(--accent),var(--accent2)); color:#fff; padding:16px 20px; display:flex; justify-content:space-between; align-items:center; flex-shrink:0; }
.sm-mhdr h4 { font-size:15px; font-weight:800; }
.sm-mclose { background:rgba(255,255,255,.2); border:none; color:#fff; width:30px; height:30px; border-radius:7px; font-size:16px; cursor:pointer; display:flex; align-items:center; justify-content:center; }
.sm-mclose:hover { background:rgba(255,255,255,.3); }
.sm-mbody { padding:20px; overflow-y:auto; flex:1; min-height:0; overscroll-behavior:contain; }
.sm-mftr  { padding:12px 20px; border-top:1px solid var(--border); display:flex; justify-content:flex-end; gap:8px; flex-shrink:0; background:rgba(46,204,113,.10); }
.sm-trans-box { background:rgba(46,204,113,.10); border:1px solid var(--border); border-radius:9px; padding:14px; margin-top:12px; }
.sm-trans-box summary { cursor:pointer; font-size:13px; font-weight:700; color:var(--p); list-style:none; display:flex; align-items:center; gap:6px; }
.sm-trans-box summary::-webkit-details-marker { display:none; }
.sm-trans-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:12px; }
@media(max-width:520px){ .sm-trans-grid{ grid-template-columns:1fr; } }

/* ── Toast ────────────────────────────────────────────── */
.sm-toast-wrap { position:fixed; bottom:20px; right:20px; z-index:9999; display:flex; flex-direction:column; gap:6px; }
.sm-toast { padding:10px 16px; border-radius:9px; background:var(--ink); border:1px solid var(--border2); box-shadow:0 6px 22px rgba(0,0,0,.13); font-size:13px; font-weight:600; display:flex; align-items:center; gap:8px; min-width:220px; animation:slideUp .2s ease; }
@keyframes slideUp { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:none} }
.sm-toast.ok  { border-left:4px solid #16a34a; color:var(--success); }
.sm-toast.err { border-left:4px solid #dc2626; color:var(--danger); }

@media(max-width:640px){
  .sm-mhdr, .sm-mbody, .sm-mftr { padding:14px; }
  .sm-overlay { padding:0; align-items:flex-end; }
  .sm-modal { border-radius:14px 14px 0 0; max-height:90dvh; width:100%; }
  .sm-toast-wrap { left:12px; right:12px; bottom:84px; }
  .sm-toast { min-width:0; width:100%; }
}
</style>

<?php if (!empty($flash)): ?>
<div class="alert alert-<?= ($flash['tip'] ?? '') === 'success' ? 'success' : 'danger' ?> mb-3">
  <?= htmlspecialchars($flash['mesaj'] ?? '') ?>
</div>
<?php endif; ?>

<!-- HEADER -->
<div class="sm-header">
  <div>
    <h2><i class="fa-solid fa-globe"></i> Site Yönetimi</h2>
    <p>Nymagro web sitesinin tüm içeriklerini buradan yönetin.</p>
  </div>
  <a href="<?= BASE_URL ?>/tr" target="_blank" class="sm-preview-btn">
    <i class="fa-solid fa-arrow-up-right-from-square"></i> Siteyi Görüntüle
  </a>
</div>

<!-- TABS -->
<div class="sm-tabs">
  <button class="sm-tab active" data-pane="tab-genel"><i class="fa-solid fa-sliders"></i> Genel & İletişim</button>
  <button class="sm-tab" data-pane="tab-icerik"><i class="fa-solid fa-pen-nib"></i> Sayfa İçerikleri</button>
  <button class="sm-tab" data-pane="tab-urunler">
    <i class="fa-solid fa-leaf"></i> Ürünler
    <span class="sm-tab-badge"><?= count(array_filter($urunler, fn($u) => empty($u['silindi_mi']))) ?></span>
  </button>
  <button class="sm-tab" data-pane="tab-galeri">
    <i class="fa-solid fa-images"></i> Galeri
    <span class="sm-tab-badge"><?= count(array_filter($galeri, fn($g) => empty($g['silindi_mi']))) ?></span>
  </button>
  <button class="sm-tab" data-pane="tab-mesajlar">
    <i class="fa-solid fa-envelope"></i> Mesajlar
    <?php if (!empty($yeniMesajSay)): ?>
      <span class="sm-tab-badge red"><?= (int)$yeniMesajSay ?></span>
    <?php endif; ?>
  </button>
</div>

<!-- ═══ TAB: GENEL & İLETİŞİM ═══════════════════════════ -->
<div class="sm-pane active" id="tab-genel">
  <form method="post" action="<?= BASE_URL ?>/site/ayarlarKaydet" enctype="multipart/form-data">
    <input type="hidden" name="return_tab" value="tab-genel">

    <!-- LOGO + MARKA -->
    <div class="sm-card">
      <div class="sm-card-head">
        <div class="ico"><i class="fa-solid fa-image"></i></div>
        <div><h3>Logo &amp; Marka Kimliği</h3><p>Sitenin başlığında görünen logo ve marka bilgileri</p></div>
      </div>
      <div class="sm-card-body">
        <!-- Logo -->
        <div class="sm-logo-box" style="margin-bottom:16px;">
          <div class="sm-logo-prev" id="logoPrev">
            <?php if (!empty($ayarlar['logo_path'])): ?>
              <img src="<?= htmlspecialchars($ayarlar['logo_path']) ?>" alt="Logo">
            <?php else: ?>
              <svg viewBox="0 0 100 100" fill="none"><path d="M22 78V22L78 78V22" stroke="white" stroke-width="11" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <?php endif; ?>
          </div>
          <div style="flex:1;">
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px;">
              <input type="file" name="logo" id="logoFile" accept="image/png,image/jpeg,image/svg+xml,image/webp" style="display:none" onchange="smLogoPreview(this)">
              <button type="button" class="sm-btn sm-btn-ghost sm-btn-sm" onclick="document.getElementById('logoFile').click()"><i class="fa-solid fa-upload"></i> Logo Seç</button>
              <?php if (!empty($ayarlar['logo_path'])): ?>
                <a href="<?= BASE_URL ?>/site/logoSil" class="sm-btn sm-btn-danger sm-btn-sm" onclick="return confirm('Logo kaldırılsın mı?')"><i class="fa-solid fa-trash"></i> Kaldır</a>
              <?php endif; ?>
            </div>
            <p style="font-size:11.5px;color:var(--muted);">PNG, JPG, SVG veya WebP — max 5 MB. Kare format önerilir (256×256 px).</p>
          </div>
        </div>

        <!-- Kimlik grid -->
        <div class="sm-grid">
          <div class="sm-field">
            <label>Şirket Adı *</label>
            <input type="text" name="company_name" class="sm-inp" value="<?= $v('company_name', 'Nymagro') ?>" required>
          </div>
          <div class="sm-field">
            <label>Slogan</label>
            <input type="text" name="tagline" class="sm-inp" value="<?= $v('tagline', 'Bitki Besleme Ürünleri') ?>" placeholder="Bitki Besleme Ürünleri">
          </div>
        </div>
      </div>
    </div>

    <!-- İLETİŞİM -->
    <div class="sm-card">
      <div class="sm-card-head">
        <div class="ico"><i class="fa-solid fa-address-book"></i></div>
        <div><h3>İletişim Bilgileri</h3><p>Sitede görünecek iletişim adresi, telefon ve WhatsApp bilgileri</p></div>
      </div>
      <div class="sm-card-body">
        <div class="sm-grid">
          <div class="sm-field">
            <label>E-Posta</label>
            <input type="email" name="email" class="sm-inp" value="<?= $v('email') ?>" placeholder="nymagrotarim@gmail.com">
          </div>
          <div class="sm-field">
            <label>Sabit Telefon</label>
            <input type="text" name="phone" class="sm-inp" value="<?= $v('phone') ?>" placeholder="+90 (242) 000 00 00">
          </div>
          <div class="sm-field">
            <label>WhatsApp Numarası <span style="color:var(--muted);font-weight:400">(görüntüleme)</span></label>
            <input type="text" name="whatsapp" class="sm-inp" value="<?= $v('whatsapp') ?>" placeholder="+90 (532) 000 00 00">
          </div>
          <div class="sm-field">
            <label>WhatsApp Linki</label>
            <input type="url" name="whatsapp_link" class="sm-inp" value="<?= $v('whatsapp_link') ?>" placeholder="https://wa.me/905320000000">
            <span class="hint" style="font-size:11px;color:var(--muted)">Numara 0 ve + olmadan: <code>wa.me/905320000000</code></span>
          </div>
          <div class="sm-field full">
            <label>Adres</label>
            <textarea name="address" class="sm-area" rows="2" placeholder="Antalya, Türkiye"><?= $v('address') ?></textarea>
          </div>
        </div>
      </div>
    </div>

    <!-- SOSYAL MEDYA -->
    <div class="sm-card">
      <div class="sm-card-head">
        <div class="ico"><i class="fa-solid fa-share-nodes"></i></div>
        <div><h3>Sosyal Medya</h3><p>Footer'da gösterilecek sosyal medya linkleri</p></div>
      </div>
      <div class="sm-card-body">
        <div class="social-grid">
          <div class="social-item">
            <div class="sico" style="background:rgba(243,156,18,.17)"><i class="fa-brands fa-instagram" style="color:#e4405f"></i></div>
            <input type="url" name="instagram" class="sm-inp" value="<?= $v('instagram') ?>" placeholder="https://instagram.com/nymagro">
          </div>
          <div class="social-item">
            <div class="sico" style="background:rgba(59,130,246,.13)"><i class="fa-brands fa-linkedin-in" style="color:#0077b5"></i></div>
            <input type="url" name="linkedin" class="sm-inp" value="<?= $v('linkedin') ?>" placeholder="https://linkedin.com/company/...">
          </div>
          <div class="social-item">
            <div class="sico" style="background:rgba(59,130,246,.13)"><i class="fa-brands fa-facebook-f" style="color:#1877f2"></i></div>
            <input type="url" name="facebook" class="sm-inp" value="<?= $v('facebook') ?>" placeholder="https://facebook.com/nymagro">
          </div>
          <div class="social-item">
            <div class="sico" style="background:var(--surface-2)"><i class="fa-brands fa-x-twitter" style="color:var(--text)"></i></div>
            <input type="url" name="twitter" class="sm-inp" value="<?= $v('twitter') ?>" placeholder="https://x.com/nymagro">
          </div>
        </div>
      </div>
    </div>

    <div class="sm-card">
      <div class="sm-card-head">
        <div class="ico"><i class="fa-solid fa-share-from-square"></i></div>
        <div><h3>Sosyal Paylaşım Görseli</h3><p>Link paylaşımlarında kullanılacak Open Graph görseli</p></div>
      </div>
      <div class="sm-card-body">
        <div class="sm-img-grid">
          <div class="sm-img-tile">
            <div class="sm-img-prev" id="ogImgPrev" style="background-image:url('<?= $v('og_image') ?>')"></div>
            <div class="sm-img-tile-body">
              <label>OG / Sosyal Medya Görseli</label>
              <input type="file" name="og_image_file" accept="image/*" onchange="smPrevImg(this,'ogImgPrev')">
              <input type="hidden" name="og_image" value="<?= $v('og_image') ?>">
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="sm-save-bar">
      <button type="submit" class="sm-btn sm-btn-primary"><i class="fa-solid fa-floppy-disk"></i> Değişiklikleri Kaydet</button>
      <a href="<?= BASE_URL ?>/tr" target="_blank" class="sm-btn sm-btn-ghost"><i class="fa-solid fa-eye"></i> Önizle</a>
    </div>
  </form>
</div>

<!-- ═══ TAB: SAYFA İÇERİKLERİ ══════════════════════════ -->
<div class="sm-pane" id="tab-icerik">
  <form method="post" action="<?= BASE_URL ?>/site/ayarlarKaydet" enctype="multipart/form-data">
    <input type="hidden" name="return_tab" value="tab-icerik">

    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:14px;">
      <p style="font-size:12.5px;color:var(--muted);">Her bölüme tıklayarak açın, dili seçin ve metinleri düzenleyin.</p>
      <div style="display:flex;gap:6px;font-size:12px;color:var(--muted);align-items:center;">
        <i class="fa-solid fa-circle-info"></i> Boş bırakırsanız sistem varsayılanı kullanılır.
      </div>
    </div>

    <?php foreach ($sections as $sec): ?>
    <div class="accord-item" id="accord-<?= $sec['id'] ?>">
      <button type="button" class="accord-head" onclick="smToggleAccord('<?= $sec['id'] ?>')">
        <div class="accord-ico" style="background:<?= $sec['color'] ?>1a;color:<?= $sec['color'] ?>">
          <i class="fa-solid <?= $sec['icon'] ?>"></i>
        </div>
        <span class="accord-title"><?= htmlspecialchars($sec['title']) ?></span>
        <i class="fa-solid fa-chevron-down accord-arrow"></i>
      </button>
      <div class="accord-body">
        <?php if ($sec['id'] === 'hero'): ?>
          <?php if (!empty($sec['note'])): ?>
            <p style="font-size:12px;color:var(--muted);background:rgba(46,204,113,.10);border:1px solid var(--border);border-radius:9px;padding:10px 12px;margin-bottom:14px;"><i class="fa-solid fa-circle-info"></i> <?= htmlspecialchars($sec['note']) ?></p>
          <?php endif; ?>
          <input type="hidden" name="slide_count" id="siteSliderCount" value="<?= (int)$sliderCount ?>">
          <div class="sm-slider-list" id="siteSliderList">
            <?php for ($n = 1; $n <= $sliderCount; $n++):
              $imgKey = "slide{$n}_img";
              $fileKey = "slide{$n}_img_file";
            ?>
            <div class="sm-slider-card" data-slider="<?= $n ?>">
              <div class="sm-slider-head">
                <h4><i class="fa-solid fa-film"></i> <?= $n ?>. Slider</h4>
                <?php if ($n === 4): ?>
                  <button type="button" class="sm-btn sm-btn-ghost sm-btn-sm" onclick="smAddSlider()"><i class="fa-solid fa-plus"></i> Slider Ekle</button>
                <?php endif; ?>
              </div>
              <div class="sm-slider-body">
                <div class="sm-img-grid sm-slider-media">
                  <div class="sm-img-tile">
                    <div class="sm-img-prev" id="slide<?= $n ?>Prev" style="background-image:url('<?= $v($imgKey) ?>')"></div>
                    <div class="sm-img-tile-body">
                      <label>Slider Görseli</label>
                      <input type="file" name="<?= $fileKey ?>" accept="image/*" onchange="smPrevImg(this,'slide<?= $n ?>Prev')">
                      <input type="hidden" name="<?= $imgKey ?>" value="<?= $v($imgKey) ?>">
                    </div>
                  </div>
                </div>

                <div class="lang-pills" data-accord="slide<?= $n ?>">
                  <button type="button" class="lang-pill active" onclick="smSwitchLang(this,'slide<?= $n ?>','tr')">🇹🇷 Türkçe</button>
                  <button type="button" class="lang-pill" onclick="smSwitchLang(this,'slide<?= $n ?>','en')">🇬🇧 English</button>
                  <button type="button" class="lang-pill" onclick="smSwitchLang(this,'slide<?= $n ?>','ru')">🇷🇺 Русский</button>
                </div>
                <?php foreach (['tr','en','ru'] as $lc): ?>
                <div class="lang-panel <?= $lc === 'tr' ? 'active' : '' ?>" id="lang-slide<?= $n ?>-<?= $lc ?>">
                  <div class="sm-grid">
                    <?php foreach (['kicker'=>'Üst etiket', 'title'=>'Başlık', 'desc'=>'Açıklama', 'cta1'=>'1. Buton', 'cta2'=>'2. Buton'] as $field => $label):
                      $key = "slide{$n}_{$field}";
                      $isArea = $field === 'desc';
                      $val = $tv($lc, $key, $slideDefault($lc, $n, $field));
                    ?>
                    <div class="sm-field <?= $isArea ? 'full' : '' ?>">
                      <label><?= htmlspecialchars($label) ?></label>
                      <?php if ($isArea): ?>
                        <textarea name="i18n[<?= $lc ?>][<?= $key ?>]" class="sm-area" rows="2"><?= $val ?></textarea>
                      <?php else: ?>
                        <input type="text" name="i18n[<?= $lc ?>][<?= $key ?>]" class="sm-inp" value="<?= $val ?>">
                      <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endfor; ?>
          </div>
        <?php else: ?>
          <?php if ($sec['id'] === 'about'): ?>
          <div class="sm-section-media">
            <div class="sm-img-grid">
              <div class="sm-img-tile">
                <div class="sm-img-prev" id="aboutImgPrev" style="background-image:url('<?= $v('about_img') ?>')"></div>
                <div class="sm-img-tile-body">
                  <label>Hakkımızda Görseli</label>
                  <input type="file" name="about_img_file" accept="image/*" onchange="smPrevImg(this,'aboutImgPrev')">
                  <input type="hidden" name="about_img" value="<?= $v('about_img') ?>">
                </div>
              </div>
            </div>
          </div>
          <?php endif; ?>

          <!-- Dil seçici -->
          <div class="lang-pills" data-accord="<?= $sec['id'] ?>">
            <button type="button" class="lang-pill active" onclick="smSwitchLang(this,'<?= $sec['id'] ?>','tr')">🇹🇷 Türkçe</button>
            <button type="button" class="lang-pill" onclick="smSwitchLang(this,'<?= $sec['id'] ?>','en')">🇬🇧 English</button>
            <button type="button" class="lang-pill" onclick="smSwitchLang(this,'<?= $sec['id'] ?>','ru')">🇷🇺 Русский</button>
          </div>

          <?php foreach (['tr','en','ru'] as $lc): ?>
          <div class="lang-panel <?= $lc === 'tr' ? 'active' : '' ?>" id="lang-<?= $sec['id'] ?>-<?= $lc ?>">
            <div class="sm-grid">
              <?php foreach ($sec['fields'] as $key => $meta):
                $isArea = ($meta['type'] ?? 'text') === 'area';
                $val    = $tv($lc, $key, $meta['d'][$lc] ?? '');
              ?>
              <div class="sm-field <?= $isArea ? 'full' : '' ?>">
                <label><?= htmlspecialchars($meta['label']) ?></label>
                <?php if ($isArea): ?>
                  <textarea name="i18n[<?= $lc ?>][<?= $key ?>]" class="sm-area" rows="2"><?= $val ?></textarea>
                <?php else: ?>
                  <input type="text" name="i18n[<?= $lc ?>][<?= $key ?>]" class="sm-inp" value="<?= $val ?>">
                <?php endif; ?>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>

    <div class="sm-save-bar">
      <button type="submit" class="sm-btn sm-btn-primary"><i class="fa-solid fa-floppy-disk"></i> İçerikleri Kaydet</button>
      <a href="<?= BASE_URL ?>/tr" target="_blank" class="sm-btn sm-btn-ghost"><i class="fa-solid fa-eye"></i> Önizle</a>
    </div>
  </form>
</div>

<!-- ═══ TAB: ÜRÜNLER ════════════════════════════════════ -->
<div class="sm-pane" id="tab-urunler">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:14px;">
    <div>
      <h3 style="font-size:14px;font-weight:800;color:var(--text);">Ürün Listesi</h3>
      <p style="font-size:12px;color:var(--muted);">Siteye eklenen ürünler — sıra numarası küçükten büyüğe gösterilir.</p>
    </div>
    <button class="sm-btn sm-btn-primary" onclick="smModalAc('modalUrun');smFormSifirla()"><i class="fa-solid fa-plus"></i> Yeni Ürün</button>
  </div>

  <?php $aktifUrunler = array_filter($urunler, fn($u) => empty($u['silindi_mi'])); ?>
  <?php if (empty($aktifUrunler)): ?>
    <div class="pr-empty"><i class="fa-solid fa-leaf"></i>Henüz ürün eklenmedi. "Yeni Ürün" butonuna tıklayın.</div>
  <?php else: ?>
    <div class="pr-grid" id="urunGrid">
      <?php foreach ($aktifUrunler as $u): ?>
      <div class="pr-card" id="urun-<?= (int)$u['id'] ?>" data-urun='<?= htmlspecialchars(json_encode($u, JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>'>
        <div class="pr-card-img" style="background-image:url('<?= htmlspecialchars($u['gorsel'] ?? '') ?>')">
          <span class="pr-card-tag"><?= htmlspecialchars($u['etiket'] ?? 'YENİ') ?></span>
          <span class="pr-card-tag" style="right:8px;left:auto;background:rgba(255,255,255,.92);color:var(--accent);border:1px solid rgba(13,98,58,.18)">
            <?= ['micro'=>'MİKRO ELEMENT','macro'=>'MAKRO BESİN','biostimulant'=>'BİYOSTİMÜLANT'][$u['kategori'] ?? 'micro'] ?? 'MİKRO ELEMENT' ?>
          </span>
          <?php if (empty($u['aktif_mi'])): ?><span class="pr-card-passive">PASİF</span><?php endif; ?>
        </div>
        <div class="pr-card-body">
          <h4><?= htmlspecialchars($u['ad_tr'] ?? '') ?></h4>
          <p><?= htmlspecialchars($u['aciklama_tr'] ?? '') ?></p>
        </div>
        <div class="pr-card-foot">
          <button class="pr-edit" onclick="smUrunDuzenle(<?= (int)$u['id'] ?>)"><i class="fa-solid fa-pen"></i> Düzenle</button>
          <button class="pr-del"  onclick="smUrunSil(<?= (int)$u['id'] ?>, '<?= addslashes((string)($u['ad_tr'] ?? '')) ?>')"><i class="fa-solid fa-trash"></i></button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- ═══ TAB: GALERİ ═════════════════════════════════════ -->
<div class="sm-pane" id="tab-galeri">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:14px;">
    <div>
      <h3 style="font-size:14px;font-weight:800;color:var(--text);">Galeri Görselleri</h3>
      <p style="font-size:12px;color:var(--muted);">Üretim, paketleme ve sevkiyat fotoğrafları.</p>
    </div>
    <button class="sm-btn sm-btn-primary" onclick="smModalAc('modalGaleri')"><i class="fa-solid fa-plus"></i> Görsel Ekle</button>
  </div>

  <?php $aktifGaleri = array_filter($galeri, fn($g) => empty($g['silindi_mi'])); ?>
  <?php if (empty($aktifGaleri)): ?>
    <div class="pr-empty"><i class="fa-regular fa-images"></i>Henüz galeri görseli eklenmedi.</div>
  <?php else: ?>
    <div class="gal-grid">
      <?php foreach ($aktifGaleri as $g): ?>
      <div class="gal-card" id="galeri-<?= (int)$g['id'] ?>">
        <div class="gal-card-img" style="background-image:url('<?= htmlspecialchars($g['gorsel'] ?? '') ?>')">
          <?php if (empty($g['aktif_mi'])): ?><span class="gal-card-passive">PASİF</span><?php endif; ?>
        </div>
        <div class="gal-card-body">
          <span><?= htmlspecialchars($g['etiket_tr'] ?: '—') ?></span>
          <button class="gal-del" onclick="smGaleriSil(<?= (int)$g['id'] ?>)"><i class="fa-solid fa-trash"></i></button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- ═══ TAB: MESAJLAR ═══════════════════════════════════ -->
<div class="sm-pane" id="tab-mesajlar">
  <div class="sm-card">
    <div class="sm-card-head">
      <div class="ico"><i class="fa-solid fa-envelope-open-text"></i></div>
      <div>
        <h3>Website İletişim Mesajları</h3>
        <p>Sitedeki iletişim formundan gelen teklif ve sorgu mesajları</p>
      </div>
    </div>
    <div class="sm-card-body">
      <?php $mesajlar = $mesajlar ?? []; ?>
      <?php if (empty($mesajlar)): ?>
        <div class="pr-empty"><i class="fa-solid fa-inbox"></i>Henüz mesaj yok.</div>
      <?php else: ?>
        <div class="msg-filter-bar">
          <button class="msg-filter active" data-mf="all">Tümü <small>(<?= count($mesajlar) ?>)</small></button>
          <?php
            $sc = array_count_values(array_column($mesajlar, 'status'));
            foreach (['new'=>'Yeni','read'=>'Okundu','replied'=>'Yanıtlandı','spam'=>'Spam'] as $sk=>$sl):
              if (($sc[$sk] ?? 0) === 0) continue;
          ?>
            <button class="msg-filter" data-mf="<?= $sk ?>"><?= $sl ?> <small>(<?= $sc[$sk] ?>)</small></button>
          <?php endforeach; ?>
        </div>

        <div class="msg-wrap">
          <table class="msg-table">
            <thead>
              <tr>
                <th>Dil</th><th>Ad Soyad</th><th>Firma / Ülke</th>
                <th>E-posta</th><th>Tel</th><th>Ürün</th>
                <th>Durum</th><th>Tarih</th><th>İşlem</th>
              </tr>
            </thead>
            <tbody id="msgBody">
              <?php foreach ($mesajlar as $m): ?>
              <tr id="msg-<?= (int)$m['id'] ?>" data-mf="<?= htmlspecialchars($m['status'] ?? 'new') ?>">
                <td><span class="msg-locale-badge"><?= $localFlags[$m['locale'] ?? 'tr'] ?? '🌐' ?> <?= strtoupper(htmlspecialchars($m['locale'] ?? '')) ?></span></td>
                <td>
                  <strong><?= htmlspecialchars($m['ad_soyad'] ?? '') ?></strong>
                  <?php if (!empty($m['mesaj'])): ?>
                    <span class="msg-text-toggle" onclick="smToggleMsgText(<?= (int)$m['id'] ?>)">▼ Mesaj</span>
                    <div class="msg-full-text" id="mft-<?= (int)$m['id'] ?>"><?= htmlspecialchars($m['mesaj'] ?? '') ?></div>
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($m['firma'] ?? '') ?><br><small style="color:var(--muted)"><?= htmlspecialchars($m['ulke'] ?? '') ?></small></td>
                <td><?php if (!empty($m['email'])): ?><a href="mailto:<?= htmlspecialchars($m['email']) ?>" style="color:var(--p)"><?= htmlspecialchars($m['email']) ?></a><?php endif; ?></td>
                <td style="white-space:nowrap"><?php if (!empty($m['telefon'])): ?><a href="tel:<?= htmlspecialchars(preg_replace('/\D+/','',$m['telefon'])) ?>" style="color:var(--p)"><?= htmlspecialchars($m['telefon']) ?></a><?php endif; ?></td>
                <td><?= htmlspecialchars($m['urun'] ?? '') ?></td>
                <td><span class="msg-badge <?= htmlspecialchars($m['status'] ?? 'new') ?>"><?= $statusLabels[$m['status'] ?? 'new'] ?? '' ?></span></td>
                <td style="white-space:nowrap;font-size:11.5px"><?= date('d.m.y H:i', strtotime($m['created_at'])) ?></td>
                <td>
                  <div class="msg-acts">
                    <?php if (($m['status'] ?? '') !== 'read'): ?><button class="msg-read-btn" onclick="smMesajDurum(<?= (int)$m['id'] ?>,'read')">Okundu</button><?php endif; ?>
                    <?php if (($m['status'] ?? '') !== 'replied'): ?><button class="msg-replied-btn" onclick="smMesajDurum(<?= (int)$m['id'] ?>,'replied')">Yanıtlandı</button><?php endif; ?>
                    <button class="msg-del-btn" onclick="smMesajSil(<?= (int)$m['id'] ?>)"><i class="fa-solid fa-trash"></i></button>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ═══ MODAL: ÜRÜN ═════════════════════════════════════ -->
<div class="sm-overlay" id="modalUrun">
  <div class="sm-modal">
    <div class="sm-mhdr">
      <h4 id="urunModalBaslik"><i class="fa-solid fa-leaf"></i> Yeni Ürün</h4>
      <button class="sm-mclose" onclick="smModalKapat('modalUrun')">&times;</button>
    </div>
    <form id="formUrun" enctype="multipart/form-data" onsubmit="return smUrunKaydet(event)">
      <div class="sm-mbody">
        <input type="hidden" name="id" id="urunId" value="0">

        <div class="sm-grid">
          <div class="sm-field full">
            <label>Görsel</label>
            <div id="urunGorselPrev" style="height:130px;background:rgba(46,204,113,.12) center/cover no-repeat;border-radius:8px;margin-bottom:8px;display:none;border:1px solid var(--border)"></div>
            <input type="file" name="gorsel" accept="image/*" class="sm-inp" onchange="smPrevImg(this,'urunGorselPrev',true)">
            <span style="font-size:11px;color:var(--muted)">Düzenleme yapıyorsanız boş bırakırsanız mevcut görsel korunur.</span>
          </div>

          <div class="sm-field">
            <label>Ad (TR) *</label>
            <input type="text" name="ad_tr" id="urunAdTr" class="sm-inp" required>
          </div>
          <div class="sm-field">
            <label>Etiket</label>
            <input type="text" name="etiket" id="urunEtiket" class="sm-inp" value="YENİ" maxlength="20">
          </div>
          <div class="sm-field full">
            <label>Açıklama (TR)</label>
            <textarea name="aciklama_tr" id="urunAciklamaTr" class="sm-area" rows="2"></textarea>
          </div>
          <div class="sm-field">
            <label>Tescil No</label>
            <input type="text" name="tescil_no" id="urunTescilNo" class="sm-inp" placeholder="Örn: 2026TK13785">
          </div>
          <div class="sm-field">
            <label>pH Aralığı</label>
            <input type="text" name="ph_araligi" id="urunPhAraligi" class="sm-inp" placeholder="Örn: 2–10 (opsiyonel)">
          </div>
          <div class="sm-field full">
            <label>Garanti Edilen İçerik (TR)</label>
            <textarea name="icerik_tr" id="urunIcerikTr" class="sm-area" rows="4" placeholder="Her satıra bir madde: Ad|Yüzde&#10;Örn: Suda Çözünür Çinko (Zn) (EDTA ile şelatlı)|%1"></textarea>
            <span style="font-size:11px;color:var(--muted)">Her satır: <code>Ad|Yüzde</code> formatında.</span>
          </div>
          <div class="sm-field full">
            <label>Uygulama Dozu (TR)</label>
            <textarea name="doz_tr" id="urunDozTr" class="sm-area" rows="4" placeholder="Her satıra bir bitki grubu: Bitki|Yapraktan|Damlama&#10;Örn: Sebzeler|100-150 ml/100L|300-500 ml/da"></textarea>
            <span style="font-size:11px;color:var(--muted)">Her satır: <code>Bitki|Yapraktan|Damlama</code> formatında.</span>
          </div>
        </div>

        <details class="sm-trans-box">
          <summary><i class="fa-solid fa-language"></i> İngilizce &amp; Rusça çeviriler (opsiyonel)</summary>
          <div class="sm-trans-grid">
            <div class="sm-field"><label>Ad (EN)</label><input type="text" name="ad_en" id="urunAdEn" class="sm-inp"></div>
            <div class="sm-field"><label>Ad (RU)</label><input type="text" name="ad_ru" id="urunAdRu" class="sm-inp"></div>
            <div class="sm-field"><label>Açıklama (EN)</label><textarea name="aciklama_en" id="urunAciklamaEn" class="sm-area" rows="2"></textarea></div>
            <div class="sm-field"><label>Açıklama (RU)</label><textarea name="aciklama_ru" id="urunAciklamaRu" class="sm-area" rows="2"></textarea></div>
            <div class="sm-field full"><label>Garanti Edilen İçerik (EN)</label><textarea name="icerik_en" id="urunIcerikEn" class="sm-area" rows="3" placeholder="Ad|Yüzde"></textarea></div>
            <div class="sm-field full"><label>Garanti Edilen İçerik (RU)</label><textarea name="icerik_ru" id="urunIcerikRu" class="sm-area" rows="3" placeholder="Ad|Yüzde"></textarea></div>
            <div class="sm-field full"><label>Uygulama Dozu (EN)</label><textarea name="doz_en" id="urunDozEn" class="sm-area" rows="3" placeholder="Bitki|Yapraktan|Damlama"></textarea></div>
            <div class="sm-field full"><label>Uygulama Dozu (RU)</label><textarea name="doz_ru" id="urunDozRu" class="sm-area" rows="3" placeholder="Bitki|Yapraktan|Damlama"></textarea></div>
          </div>
        </details>

        <div class="sm-grid" style="margin-top:12px;">
          <div class="sm-field">
            <label>Sıra No</label>
            <input type="number" name="sira" id="urunSira" class="sm-inp" value="0" min="0">
          </div>
          <div class="sm-field">
            <label>Kategori</label>
            <select name="kategori" id="urunKategori" class="sm-inp">
              <option value="micro">Şelatlı Mikro Element</option>
              <option value="macro">Makro Besin</option>
              <option value="biostimulant">Biyostimülant</option>
            </select>
          </div>
          <div class="sm-field">
            <label>Durum</label>
            <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;padding:10px 0;">
              <input type="checkbox" name="aktif_mi" id="urunAktif" value="1" checked>
              <span style="font-size:13px">Siteye aktif olarak göster</span>
            </label>
          </div>
        </div>
      </div>
      <div class="sm-mftr">
        <button type="button" class="sm-btn sm-btn-ghost" onclick="smModalKapat('modalUrun')">Vazgeç</button>
        <button type="submit" class="sm-btn sm-btn-primary"><i class="fa-solid fa-floppy-disk"></i> Kaydet</button>
      </div>
    </form>
  </div>
</div>

<!-- ═══ MODAL: GALERİ ═══════════════════════════════════ -->
<div class="sm-overlay" id="modalGaleri">
  <div class="sm-modal">
    <div class="sm-mhdr">
      <h4><i class="fa-solid fa-images"></i> Galeri Görseli Ekle</h4>
      <button class="sm-mclose" onclick="smModalKapat('modalGaleri')">&times;</button>
    </div>
    <form id="formGaleri" enctype="multipart/form-data" onsubmit="return smGaleriKaydet(event)">
      <div class="sm-mbody">
        <div class="sm-field" style="margin-bottom:12px;">
          <label>Görsel</label>
          <div id="galPrev" style="height:150px;background:rgba(46,204,113,.12) center/cover no-repeat;border-radius:8px;margin-bottom:8px;display:none;border:1px solid var(--border)"></div>
          <input type="file" name="gorsel" accept="image/*" class="sm-inp" onchange="smPrevImg(this,'galPrev',true)">
        </div>
        <div class="sm-grid">
          <div class="sm-field">
            <label>Etiket (TR)</label>
            <input type="text" name="etiket_tr" class="sm-inp" placeholder="Örn: Üretim Alanı">
          </div>
          <div class="sm-field">
            <label>Etiket (EN)</label>
            <input type="text" name="etiket_en" class="sm-inp">
          </div>
          <div class="sm-field">
            <label>Etiket (RU)</label>
            <input type="text" name="etiket_ru" class="sm-inp">
          </div>
          <div class="sm-field">
            <label>Sıra No</label>
            <input type="number" name="sira" class="sm-inp" value="0" min="0">
          </div>
          <div class="sm-field">
            <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;margin-top:20px;">
              <input type="checkbox" name="aktif_mi" value="1" checked>
              <span>Aktif olarak göster</span>
            </label>
          </div>
        </div>
      </div>
      <div class="sm-mftr">
        <button type="button" class="sm-btn sm-btn-ghost" onclick="smModalKapat('modalGaleri')">Vazgeç</button>
        <button type="submit" class="sm-btn sm-btn-primary"><i class="fa-solid fa-upload"></i> Yükle</button>
      </div>
    </form>
  </div>
</div>

<!-- Toast -->
<div class="sm-toast-wrap" id="smToast"></div>

<script>
const SM_BASE = '<?= BASE_URL ?>';

/* ── Tab switch ────────────────────────────────────────── */
document.querySelectorAll('.sm-tab').forEach(t => t.addEventListener('click', () => {
  document.querySelectorAll('.sm-tab').forEach(x => x.classList.remove('active'));
  document.querySelectorAll('.sm-pane').forEach(x => x.classList.remove('active'));
  t.classList.add('active');
  document.getElementById(t.dataset.pane)?.classList.add('active');
  history.replaceState(null, '', '#' + t.dataset.pane);
}));
if (location.hash) {
  const t = document.querySelector(`.sm-tab[data-pane="${location.hash.slice(1)}"]`);
  if (t) t.click();
}

/* ── Accordion ─────────────────────────────────────────── */
function smToggleAccord(id) {
  const item = document.getElementById('accord-' + id);
  if (!item) return;
  item.classList.toggle('open');
}

/* ── Language switch (accordion içi) ───────────────────── */
function smSwitchLang(btn, accordId, lang) {
  const pills = btn.closest('.lang-pills').querySelectorAll('.lang-pill');
  pills.forEach(p => p.classList.remove('active'));
  btn.classList.add('active');
  ['tr','en','ru'].forEach(lc => {
    const panel = document.getElementById('lang-' + accordId + '-' + lc);
    if (panel) panel.classList.toggle('active', lc === lang);
  });
}

/* ── Toast ─────────────────────────────────────────────── */
function smToast(msg, type = 'ok') {
  const wrap = document.getElementById('smToast');
  const div  = document.createElement('div');
  div.className = 'sm-toast ' + type;
  div.innerHTML  = (type === 'ok' ? '<i class="fa-solid fa-check-circle"></i>' : '<i class="fa-solid fa-exclamation-circle"></i>') + ' ' + msg;
  wrap.appendChild(div);
  setTimeout(() => div.remove(), 3200);
}

/* ── Modal ─────────────────────────────────────────────── */
function smModalAc(id)    { document.getElementById(id)?.classList.add('open'); }
function smModalKapat(id) { document.getElementById(id)?.classList.remove('open'); }
document.querySelectorAll('.sm-overlay').forEach(o => o.addEventListener('click', function(e){ if(e.target===this) smModalKapat(this.id); }));

/* ── Görsel önizleme ───────────────────────────────────── */
function smPrevImg(input, targetId, show = false) {
  const el = document.getElementById(targetId);
  if (!el) return;
  const file = input.files?.[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    el.style.backgroundImage = `url(${e.target.result})`;
    if (show) el.style.display = 'block';
  };
  reader.readAsDataURL(file);
}
function smAddSlider() {
  const list = document.getElementById('siteSliderList');
  const countInput = document.getElementById('siteSliderCount');
  if (!list || !countInput) return;
  const next = Math.min(20, (parseInt(countInput.value || '4', 10) || 4) + 1);
  if (next > 20) return;
  countInput.value = String(next);
  const html = `
    <div class="sm-slider-card" data-slider="${next}">
      <div class="sm-slider-head">
        <h4><i class="fa-solid fa-film"></i> ${next}. Slider</h4>
      </div>
      <div class="sm-slider-body">
        <div class="sm-img-grid sm-slider-media">
          <div class="sm-img-tile">
            <div class="sm-img-prev" id="slide${next}Prev"></div>
            <div class="sm-img-tile-body">
              <label>Slider Görseli</label>
              <input type="file" name="slide${next}_img_file" accept="image/*" onchange="smPrevImg(this,'slide${next}Prev')">
              <input type="hidden" name="slide${next}_img" value="">
            </div>
          </div>
        </div>
        <div class="lang-pills" data-accord="slide${next}">
          <button type="button" class="lang-pill active" onclick="smSwitchLang(this,'slide${next}','tr')">🇹🇷 Türkçe</button>
          <button type="button" class="lang-pill" onclick="smSwitchLang(this,'slide${next}','en')">🇬🇧 English</button>
          <button type="button" class="lang-pill" onclick="smSwitchLang(this,'slide${next}','ru')">🇷🇺 Русский</button>
        </div>
        ${['tr','en','ru'].map((lc, idx) => `
          <div class="lang-panel ${idx === 0 ? 'active' : ''}" id="lang-slide${next}-${lc}">
            <div class="sm-grid">
              ${[
                ['kicker','Üst etiket',false],
                ['title','Başlık',false],
                ['desc','Açıklama',true],
                ['cta1','1. Buton',false],
                ['cta2','2. Buton',false]
              ].map(([field, label, area]) => `
                <div class="sm-field ${area ? 'full' : ''}">
                  <label>${label}</label>
                  ${area
                    ? `<textarea name="i18n[${lc}][slide${next}_${field}]" class="sm-area" rows="2"></textarea>`
                    : `<input type="text" name="i18n[${lc}][slide${next}_${field}]" class="sm-inp">`
                  }
                </div>
              `).join('')}
            </div>
          </div>
        `).join('')}
      </div>
    </div>`;
  list.insertAdjacentHTML('beforeend', html);
}
function smLogoPreview(input) {
  const el = document.getElementById('logoPrev');
  if (!el || !input.files?.[0]) return;
  const reader = new FileReader();
  reader.onload = e => { el.style.backgroundImage = `url(${e.target.result})`; el.style.backgroundSize = 'cover'; };
  reader.readAsDataURL(input.files[0]);
}

/* ── Ürün modal ────────────────────────────────────────── */
function smFormSifirla() {
  document.getElementById('formUrun')?.reset();
  document.getElementById('urunId').value = '0';
  document.getElementById('urunEtiket').value = 'YENİ';
  document.getElementById('urunKategori').value = 'micro';
  document.getElementById('urunAktif').checked = true;
  document.getElementById('urunGorselPrev').style.display = 'none';
  document.getElementById('urunModalBaslik').innerHTML = '<i class="fa-solid fa-leaf"></i> Yeni Ürün';
}
function smUrunDuzenle(id) {
  const card = document.getElementById('urun-' + id);
  if (!card) return;
  const u = JSON.parse(card.dataset.urun);
  document.getElementById('formUrun').reset();
  document.getElementById('urunId').value          = u.id;
  document.getElementById('urunSira').value        = u.sira || 0;
  document.getElementById('urunKategori').value    = u.kategori || 'micro';
  document.getElementById('urunEtiket').value      = u.etiket || 'YENİ';
  document.getElementById('urunAdTr').value        = u.ad_tr || '';
  document.getElementById('urunAdEn').value        = u.ad_en || '';
  document.getElementById('urunAdRu').value        = u.ad_ru || '';
  document.getElementById('urunAciklamaTr').value  = u.aciklama_tr || '';
  document.getElementById('urunAciklamaEn').value  = u.aciklama_en || '';
  document.getElementById('urunAciklamaRu').value  = u.aciklama_ru || '';
  document.getElementById('urunTescilNo').value    = u.tescil_no || '';
  document.getElementById('urunPhAraligi').value   = u.ph_araligi || '';
  document.getElementById('urunIcerikTr').value    = u.icerik_tr || '';
  document.getElementById('urunIcerikEn').value    = u.icerik_en || '';
  document.getElementById('urunIcerikRu').value    = u.icerik_ru || '';
  document.getElementById('urunDozTr').value       = u.doz_tr || '';
  document.getElementById('urunDozEn').value       = u.doz_en || '';
  document.getElementById('urunDozRu').value       = u.doz_ru || '';
  document.getElementById('urunAktif').checked     = !!parseInt(u.aktif_mi);
  const prev = document.getElementById('urunGorselPrev');
  if (u.gorsel) { prev.style.backgroundImage = `url(${u.gorsel})`; prev.style.display = 'block'; }
  document.getElementById('urunModalBaslik').innerHTML = '<i class="fa-solid fa-pen"></i> Ürün Düzenle';
  smModalAc('modalUrun');
}
function smUrunKaydet(e) {
  e.preventDefault();
  const fd = new FormData(document.getElementById('formUrun'));
  fetch(SM_BASE + '/site/urunKaydet', { method:'POST', body:fd })
    .then(r => r.json())
    .then(res => {
      if (res.ok) { smToast(res.msg); smModalKapat('modalUrun'); setTimeout(() => location.reload(), 700); }
      else smToast(res.msg, 'err');
    })
    .catch(() => smToast('Sunucu hatası', 'err'));
  return false;
}
function smUrunSil(id, ad) {
  if (!confirm('"' + ad + '" silinsin mi?')) return;
  fetch(SM_BASE + '/site/urunSil/' + id)
    .then(r => r.json())
    .then(res => {
      if (res.ok) { smToast(res.msg); document.getElementById('urun-' + id)?.remove(); }
      else smToast(res.msg, 'err');
    });
}

/* ── Galeri ────────────────────────────────────────────── */
function smGaleriKaydet(e) {
  e.preventDefault();
  const fd = new FormData(document.getElementById('formGaleri'));
  fetch(SM_BASE + '/site/galeriKaydet', { method:'POST', body:fd })
    .then(r => r.json())
    .then(res => {
      if (res.ok) { smToast(res.msg); smModalKapat('modalGaleri'); setTimeout(() => location.reload(), 700); }
      else smToast(res.msg, 'err');
    });
  return false;
}
function smGaleriSil(id) {
  if (!confirm('Bu görsel silinsin mi?')) return;
  fetch(SM_BASE + '/site/galeriSil/' + id)
    .then(r => r.json())
    .then(res => {
      if (res.ok) { smToast('Görsel silindi.'); document.getElementById('galeri-' + id)?.remove(); }
      else smToast(res.msg || 'Hata', 'err');
    });
}

/* ── Mesajlar ──────────────────────────────────────────── */
function smToggleMsgText(id) {
  const el  = document.getElementById('mft-' + id);
  const btn = el?.previousElementSibling;
  if (!el) return;
  const open = el.style.display === 'block';
  el.style.display = open ? 'none' : 'block';
  if (btn) btn.textContent = open ? '▼ Mesaj' : '▲ Gizle';
}
function smMesajDurum(id, status) {
  const labels = { new:'Yeni', read:'Okundu', replied:'Yanıtlandı', spam:'Spam' };
  const fd = new FormData(); fd.append('status', status);
  fetch(SM_BASE + '/site/mesajDurum/' + id, { method:'POST', body:fd })
    .then(r => r.json())
    .then(res => {
      if (!res.ok) { smToast(res.msg || 'Hata', 'err'); return; }
      const row = document.getElementById('msg-' + id);
      if (row) {
        row.dataset.mf = status;
        const b = row.querySelector('.msg-badge');
        if (b) { b.className = 'msg-badge ' + status; b.textContent = labels[status] || status; }
      }
      smToast('Durum güncellendi.');
    });
}
function smMesajSil(id) {
  if (!confirm('Bu mesaj silinsin mi?')) return;
  fetch(SM_BASE + '/site/mesajSil/' + id)
    .then(r => r.json())
    .then(res => {
      if (res.ok) { document.getElementById('msg-' + id)?.remove(); smToast('Mesaj silindi.'); }
      else smToast(res.msg || 'Hata', 'err');
    });
}
document.querySelectorAll('.msg-filter').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.msg-filter').forEach(x => x.classList.remove('active'));
    btn.classList.add('active');
    const f = btn.dataset.mf;
    document.querySelectorAll('#msgBody tr').forEach(row => {
      row.style.display = (f === 'all' || row.dataset.mf === f) ? '' : 'none';
    });
  });
});
</script>
