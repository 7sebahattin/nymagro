# Nymagro — Canlıya Alma Rehberi

Bu depo, `nuvernatrade.com` altyapısından devralınan PHP uygulamasının
Nymagro markasına uyarlanmış hâlidir. Aşağıdaki adımları **sırayla** uygulayın.

---

## ⚠️ 0. Önce güvenlik

### a) Yönetici şifresini değiştirin

`app/core/AuthGuard.php` içinde ilk kurulum için sabit kodlanmış varsayılan
hesap var: **kullanıcı `admin`, şifre `admin123`**. Bu depo bir dönem herkese
açıktı; şifre canlı sistemde hâlâ geçerliyse **hemen** panelden değiştirin.

### b) `config.php` depoda tutulmaz

`app/config/config.php` `.gitignore` içindedir; veritabanı bilgileri depoya
girmez. Yeni bir sunucuya kurulum yapıyorsanız:

```bash
cp app/config/config.example.php app/config/config.php
# sonra DB_HOST / DB_NAME / DB_USER / DB_PASS değerlerini girin
```

Canlıya alırken `APP_ENV` değerini `production` yapın — hata mesajları gizlenir.

### c) Depo görünürlüğü

Depo şu an **public**. Özel tutmak isterseniz:
GitHub → Settings → General → en altta *Change repository visibility*.

---

## 1. Veritabanını yedekleyin

```bash
mysqldump -u KULLANICI -p VERITABANI > yedek_$(date +%F).sql
```

Bu adımı atlamayın. Sonraki adımlar canlı içeriği değiştirir.

---

## 2. Marka SQL betiğini çalıştırın

Kod tarafındaki varsayılanlar güncellendi, ancak site içeriğinin bir kısmı
veritabanında (`site_settings`) tutuluyor. Bu betik oradaki eski değerleri
düzeltir:

```bash
mysql -u KULLANICI -p VERITABANI < db/nymagro-rebrand.sql
```

Betik idempotenttir; birden fazla kez çalıştırılabilir. Tablo veya kolon
yapısına dokunmaz, yalnızca içerik günceller.

**Doğrulama** — üçü de `0` dönmelidir:

```sql
SELECT COUNT(*) FROM site_settings WHERE setting_value LIKE '%Nuverna%';
SELECT COUNT(*) FROM site_urunler  WHERE aciklama_tr  LIKE '%Nuverna%';
SELECT COUNT(*) FROM site_galeri   WHERE etiket_tr    LIKE '%Nuverna%';
```

---

## 3. Ürün kataloğunu değiştirin

Sitede hâlâ devralınan yaş sebze-meyve ürünleri (kiraz, domates vb.) var.
İki seçeneğiniz var:

**a) Hazır şablonu kullanın** (önerilen)

```bash
mysql -u KULLANICI -p VERITABANI < db/nymagro-urunler.sql
```

Eski ürünleri pasife alır, sekiz Nymagro ürününü (SILATRIX, SILECKO MoZ,
CUPPERA, FORTIVIUM, NYMATEX, BRILIXA, SLASTYK, NutriDyn KALICALZ) ekler.
**Sekizi de T.C. Tarım ve Orman Bakanlığı tescil belgelerinden doğrulanmış**
tescil no, garanti edilen içerik (%) ve şelat pH kararlılığı verileriyle
doğrudan çalıştırılabilir.

> **Eksik olan tek şey:** Bitki bazlı uygulama dozu tabloları (`doz_tr` /
> `doz_en` / `doz_ru`) bu şablonda YOK. Panel → Site Yönetimi → Ürünler →
> ürünü düzenle → "Uygulama Dozu" alanına, ürünün tescil belgesindeki doz
> tablosunu `Bitki|Yapraktan|Damlama` formatında satır satır girin.
> Girilmezse ürün sayfasında "Uygulama Şekli ve Dozu" tablosu görünmez.
>
> Ürün görselleri de boş — yüklenene kadar `public/img/urun-placeholder.jpg`
> (Nymagro renkleriyle üretilmiş jenerik görsel) otomatik gösterilir. Gerçek
> ürün fotoğrafı geldikçe panelden ürün başına tek tek değiştirin.

**b) Panelden elle girin**

Panel → Site Yönetimi → Ürünler. Yeni alanlar: Tescil No, pH Aralığı,
Garanti Edilen İçerik (`Ad|Yüzde` formatında, satır satır), Uygulama Dozu
(`Bitki|Yapraktan|Damlama` formatında, satır satır).

---

## 4. Dosyaları sunucuya yükleyin

`app/` ve `public/` klasörlerini sunucuya kopyalayın.

Sunucuda **korunması gerekenler** (üzerine yazmayın):
- `app/config/config.php` — veritabanı bilgileri
- `public/uploads/` — yüklenmiş görseller

---

## 5. Logo ve görselleri yükleyin

Panel → Site Yönetimi → Genel:

| Alan | Yüklenecek |
|---|---|
| Logo | `brand/logo/nymagro-logo.png` |
| Slayt 1-4 görselleri | Kendi ürün/tarla fotoğraflarınız |
| Hakkımızda görseli | Kendi fotoğrafınız |
| OG görseli | Boş bırakılırsa `/img/og-image.jpg` kullanılır |

> Slayt ve hakkımızda görselleri şu an hâlâ Unsplash'teki **yaş sebze-meyve**
> fotoğraflarını gösteriyor. Bunlar mutlaka değiştirilmeli.

Logo yüklemezseniz kod otomatik olarak depodaki `/img/nymagro-logo.png`
dosyasına düşer — site logosuz kalmaz.

---

## 6. Arama motoru geçişi

Sayfa adresleri değişti:

| Eski | Yeni |
|---|---|
| `/tr/ihracat-bolgeleri` | `/tr/urun-gruplari` |
| `/tr/ihracat/{bölge}` | `/tr/urun-grubu/{grup}` |
| `/tr/kalite-paketleme` | `/tr/kalite-tescil` |
| `/en/export-markets` | `/en/product-groups` |
| `/en/export/{region}` | `/en/product-group/{group}` |
| `/en/quality-packaging` | `/en/quality-registration` |

Eski alan adı (`nuvernatrade.com`) hâlâ sizdeyse ve trafiği taşımak
istiyorsanız `.htaccess` üzerinden `nymagro.com`'a 301 yönlendirmesi kurun.
Google Search Console'da yeni siteyi doğrulayıp `sitemap.xml` adresini
gönderin.

---

## 7. Kontrol listesi

Yayına almadan önce tarayıcıdan doğrulayın:

- [ ] `/tr`, `/en`, `/ru` — üç dil de açılıyor
- [ ] `/tr/urun-gruplari` ve alt sayfaları çalışıyor
- [ ] `/tr/kalite-tescil` açılıyor
- [ ] Header ve footer'da Nymagro logosu görünüyor
- [ ] Renkler yeşil/turuncu; hiçbir yerde mor kalmamış
- [ ] Tarayıcı sekmesinde yeşil yaprak favicon'u var
- [ ] İletişim bilgileri doğru: `nymagrotarim@gmail.com`, `(0242) 464 12 44`
- [ ] WhatsApp butonu `0543 961 73 03` numarasına gidiyor
- [ ] İletişim formu mesaj gönderiyor ve panele düşüyor
- [ ] Panele giriş yapılıyor, şifre değiştirilmiş
- [ ] Ürün listesinde eski meyve-sebze kayıtları görünmüyor
- [ ] 3 ürün grubu sayfası (mikro element / makro besin / biyostimülant) açılıyor
- [ ] 8 ürün detay sayfasında Tescil No, garanti edilen içerik tablosu görünüyor
- [ ] Uygulama dozu tabloları panelden girildi (bkz. bölüm 3)

---

## RBAC, Kullanıcı Yönetimi ve Audit Log

Panele kapalı sistem mantığında bir kullanıcı yönetimi, rol tabanlı yetkilendirme
(RBAC) ve gizli audit log sistemi eklendi. Herhangi bir manuel migration
komutu YOKTUR — yeni tablolar (`roles`, `permissions`, `role_permissions`,
`audit_logs`, `login_history`) ve `users` tablosundaki yeni sütunlar
(`role_id`, `failed_login_count`, `locked_until`, `status_changed_at`),
uygulamanın kendi idempotent şema-bootstrap konvansiyonuyla (bu depodaki
diğer tüm tablolar gibi) **ilk istekte otomatik** oluşturulur/eklenir.

### Yapmanız gerekenler

1. **Backup alın** (her zamanki gibi, bu depodaki genel kural):
   ```bash
   mysqldump -u KULLANICI -p VERITABANI > yedek_$(date +%F).sql
   ```
2. Dosyaları sunucuya kopyalayın (`app/` ve `public/`), her zamanki gibi.
3. Panele giriş yapın (mevcut Super Admin hesabınızla — şifresi değişmedi).
   İlk istekte şema otomatik kurulur; mevcut kullanıcılarınızın hepsi otomatik
   olarak birer role eşlenir (aşağıya bakın), **hiçbir mevcut yetkiniz
   otomatik olarak kısıtlanmaz.**
4. Süper Yönetici menüsünde yeni "Yönetim" grubu görünür: **Kullanıcılar**,
   **Roller**, **Audit Log**. Normal kullanıcılar bu grubu göremez.

### Mevcut kullanıcılarınıza ne olur?

Eski `users.role` değeriniz (`admin` / `accountant` / `user` / `super_admin`)
otomatik olarak aynı isimli bir role eşlenir. Bu "miras" roller, migrasyon
öncesi zaten hiçbir yetki kontrolü olmadığı için, **tüm iş modüllerinde tam
CRUD (silme dahil) yetkisi** ile oluşturulur — sadece Kullanıcı/Rol/Audit
yönetimi Süper Yönetici'ye özeldir. Yani mevcut personeliniz migrasyondan
hemen sonra hiçbir şeyin kısıtlandığını FARK ETMEZ.

Yeni personel için daha kısıtlı roller de hazır gelir: **Veri Giriş**
(görüntüle/ekle/düzenle, silme yok), **Görüntüleme** (sadece görüntüleme),
**Muhasebe** (finans modüllerinde görüntüle/ekle/düzenle). Süper Yönetici
isterse mevcut personelini de zamanla bu daha dar rollere geçirebilir —
bunu otomatik yapmadık, bilinçli bir yönetici kararı olması gerekiyor.

### Güvenlik notları

- Başarısız girişlerde 5 denemeden sonra hesap 15 dakika otomatik kilitlenir.
- Bir kullanıcı pasifleştirildiğinde/kilitlendiğinde veya rolü değiştiğinde,
  o kullanıcının **açık oturumu bir sonraki isteğinde anında sonlanır**
  (eski yetkilerle devam edemez).
- Audit Log ve Giriş Geçmişi sadece Süper Yönetici'ye açıktır; şifreler asla
  loglanmaz.
- Bilinen ve KURULUM.md'de yukarıda zaten belirtilen `admin`/`admin123`
  varsayılan şifresini henüz değiştirmediyseniz **şimdi değiştirin**
  (Kullanıcılar → admin → Şifre Sıfırla).

### Bilinen sınırlamalar (bu sürümde YOK, bilinçli olarak ertelendi)

- 2FA/TOTP, CSRF token'ları (mevcut formlarda zaten yoktu, bu değişiklikle
  de eklenmedi — ayrı bir iş olarak planlanmalı), her modülde optimistic
  locking (version numarası), audit log için PDF/gelişmiş export.
- Modül görünümlerinde (ör. Müşteriler listesi) Ekle/Düzenle/Sil butonları
  şu an SADECE backend'de zorunlu kılınıyor; buton bazlı frontend gizleme
  yalnız yeni eklenen Kullanıcılar/Roller/Audit ekranlarında var, ~20 mevcut
  modülün kendi view'lerine henüz yayılmadı (backend zaten reddettiği için
  güvenlik açığı değil, sadece "yetkisiz" hata sayfasına düşme UX'i).

## Marka varlıkları

`brand/` klasöründe:

| Dosya | İçerik |
|---|---|
| `BRAND.md` | Firma bilgileri, renk paleti, logo kuralları, ürün ailesi, mevzuat notu |
| `REBRAND-MAP.md` | Uygulanan değişim haritası |
| `tokens/colors.css` | CSS renk değişkenleri |
| `tokens/brand.json` | Firma, iletişim, renk ve SEO alanları — tek doğruluk kaynağı |
| `logo/nymagro-logo.png` | Şeffaf zeminli ana logo |
| `logo/nymagro-wordmark.png` | Slogansız wordmark |
| `logo/nymagro-leaf-mark.png` | Yaprak işareti (favicon kaynağı) |
| `logo/silatrix-etiket.png` | SILATRIX ambalaj etiketi referansı |

**Renkler:** Yeşil `#0D623A` (birincil) · Turuncu `#F3911F` (vurgu) ·
Açık yeşil `#CFE1CD` · Metin `#231F20` · Gri `#6D6E71`

---

## Mevzuat notu

Ürünler **kimyevi gübre / bitki besleme ürünüdür**, bitki koruma ürünü
(zirai ilaç / pestisit) değildir. Site metinlerinde "zirai ilaç", "pestisit",
"ilaçlama" ifadeleri **kullanılmamalıdır**; bunun yerine "bitki besleme
ürünleri", "sıvı gübre", "mikro besin elementleri" kullanılır.

Kalite sayfasındaki sertifika rozetleri, devralınan altyapıdaki gıda güvenliği
belgeleri (GlobalGAP, ISO 22000, HACCP, BRC) yerine Nymagro'nun gerçekten
sahip olduğu belgelerle değiştirildi: Bakanlık tescili, EC Fertilizer standardı,
İthalat Lisansı No 6002 ve EDTA şelat kararlılığı. Sahip olmadığınız bir
sertifikayı bu bölüme eklemeyin.
