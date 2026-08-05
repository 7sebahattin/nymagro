# Nuverna Trade → Nymagro Değişim Haritası

**Durum: uygulandı.** Kod tarafındaki tüm maddeler tamamlandı; depoda `nuverna`
geçişi kalmadı. Veritabanı tarafı için `db/nymagro-rebrand.sql` çalıştırılmalıdır
(bkz. `KURULUM.md`).

Sadece marka ve içerik alanları değişti; kod yapısı, fonksiyon/değişken mimarisi
ve veritabanı tablo/kolon isimleri korundu.

---

## A. Metinsel marka referansları

Büyük/küçük harf ve boşluk varyantlarının tamamı taranır:

| Aranan | Yerine |
|---|---|
| `Nuverna Trade` | `Nymagro` |
| `Nuverna` | `Nymagro` |
| `NUVERNA TRADE` | `NYMAGRO` |
| `NUVERNA` | `NYMAGRO` |
| `nuverna trade` | `nymagro` |
| `nuverna` | `nymagro` |
| `NuvernaTrade` | `Nymagro` |
| `nuverna-trade` | `nymagro` |
| `nuverna_trade` | `nymagro` |
| `nuvernaTrade` | `nymagro` |
| Tam ünvan (ör. `Nuverna Trade Ltd. Şti.`) | `NYMAGRO TARIM SANAYİ VE TİCARET LİMİTED ŞİRKETİ` |

## B. Alan adları ve e-posta

| Aranan | Yerine |
|---|---|
| `nuvernatrade.com` | `nymagro.com` |
| `www.nuvernatrade.com` | `www.nymagro.com` |
| `https://nuvernatrade.com` | `https://www.nymagro.com` |
| `info@nuvernatrade.com` | `nymagrotarim@gmail.com` |
| `*@nuvernatrade.com` (tüm kutular) | `nymagrotarim@gmail.com` |

> Kurumsal e-posta (`info@nymagro.com`) devreye girene kadar Gmail adresi
> kullanılır. Devreye girdiğinde sadece `brand/tokens/brand.json` güncellenir.

## C. Meta / SEO alanları

Aşağıdaki alanların tamamı `brand/tokens/brand.json` → `seo` bloğundan beslenir:

- `<title>`, `titleTemplate`
- `<meta name="description">`, `keywords`, `author`
- Open Graph: `og:site_name`, `og:title`, `og:description`, `og:url`, `og:image`, `og:locale`
- Twitter: `twitter:title`, `twitter:description`, `twitter:image`, `twitter:site`
- `<link rel="canonical">`
- `manifest.json` → `name`, `short_name`, `theme_color` (`#0d623a`), `background_color` (`#ffffff`)
- `robots.txt` ve `sitemap.xml` içindeki host satırları
- JSON-LD `Organization` / `LocalBusiness` şeması (ad, logo, adres, telefon, e-posta)

## D. Logo ve görsel yolları

| Aranan | Yerine |
|---|---|
| `logo-nuverna*.{png,svg,webp}` | `nymagro-logo.png` |
| `/assets/img/logo*` | Aynı yol, yeni dosya içeriği |
| `favicon.ico` / `apple-touch-icon.png` | Nymagro logosundan yeniden üretilir |
| `og-image.*` | Nymagro yeşil/turuncu düzende yeniden üretilir |
| E-posta şablonu header görseli | `nymagro-logo-white-bg.png` |

Kaynak dosyalar: `brand/logo/` klasörü.

## E. Statik konfigürasyon alanları

Taranacak yerler:

- `.env.example` — `APP_NAME`, `APP_URL`, `MAIL_FROM_NAME`, `MAIL_FROM_ADDRESS`
- `config/app.php` / `config/*.js` / `settings.py` — uygulama adı, URL, marka anahtarları
- `package.json` — `name`, `description`, `author`, `homepage`
- `composer.json` — `name`, `description`
- `README.md` — proje başlığı ve açıklaması
- `LICENSE` telif satırı
- Fatura / PDF / e-posta şablonlarındaki firma başlığı, adres bloğu, footer
- Yasal sayfalar: KVKK aydınlatma metni, gizlilik politikası, mesafeli satış
  sözleşmesi, iade koşulları — firma ünvanı, adres, MERSİS/vergi bilgileri
- Panel giriş ekranı başlığı, tarayıcı sekme başlığı, PWA adı
- WhatsApp/telefon `tel:` ve `wa.me` linkleri

## F. Renkler

Projedeki mevcut renk değişkenleri `brand/tokens/colors.css` değerleriyle
değiştirilir. Hard-coded hex değerleri taranıp eşlenir:

- Eski birincil renk → `#0d623a`
- Eski vurgu rengi → `#f3911f`
- Tailwind kullanılıyorsa `tailwind.config` içindeki `theme.extend.colors`
  bloğu `brand.json` → `colors` ile senkronlanır.

## G. Dokunulmayacaklar

- Veritabanı tablo ve kolon isimleri
- Migration dosyaları ve seed şemaları
- API endpoint yolları ve route isimleri
- Sınıf / fonksiyon / değişken isimleri
- Composer / npm paket isim alanları (`namespace`, `vendor` klasörü)
- Üçüncü parti kütüphane dosyaları (`vendor/`, `node_modules/`)

> `Nuverna` kelimesi bir namespace, tablo adı veya sınıf adı içinde geçiyorsa
> **değiştirilmez**; bunlar ayrı bir liste hâlinde raporlanır ve karar
> kullanıcıya bırakılır.

## H. Doğrulama

Değişiklik sonrası kalıntı taraması:

```
grep -rniE "nuverna" . --exclude-dir={.git,node_modules,vendor,dist,build}
```

Çıktı boş olmalı (veya sadece G maddesindeki bilinçli istisnaları içermeli).
