# NYMAGRO — Marka Kılavuzu

Bu dosya, `nuvernatrade.com` altyapısından kopyalanan projenin NYMAGRO markasına
uyarlanmasında kullanılacak tek doğruluk kaynağıdır. Tüm renk, metin, logo ve
iletişim bilgileri buradan alınmalıdır.

Kaynak: T.C. Tarım ve Orman Bakanlığı Kimyevi Gübre Tescil Belgesi
(Tescil No: 2026TK13785, 11.02.2026) ve SILATRIX ambalaj etiketi.

---

## 1. Firma Bilgileri

| Alan | Değer |
|---|---|
| Ticari ünvan (tam) | NYMAGRO TARIM SANAYİ VE TİCARET LİMİTED ŞİRKETİ |
| Ticari ünvan (kısa) | NYMAGRO TARIM SAN. ve TİC. LTD. ŞTİ. |
| Marka adı | Nymagro |
| Adres | Çamköy Mah. Atatürk Blv. No: 394, Aksu / ANTALYA |
| Telefon | (0242) 464 12 44 |
| GSM | 0543 961 73 03 |
| Web | https://www.nymagro.com |
| E-posta | nymagrotarim@gmail.com |
| Lisans türü | İthalatçı |
| Lisans No | 6002 |
| Faaliyet | Bitki besleme ürünleri / kimyevi gübre ithalatı ve satışı |

### Tedarikçi (üretici firma)

| Alan | Değer |
|---|---|
| Firma | IDEAPLANTECH — Innovaciones y Desarrollos Agronomicos Idea Plantech S.L. |
| Adres | C/ Dr. Pujades, 53 — 08700 Igualada, Barcelona (İSPANYA) |
| E-posta | ideaplantechinnova@gmail.com |

---

## 2. Renk Paleti

Renkler doğrudan orijinal logo ve ambalaj etiketinden örneklenmiştir.

| Rol | Ad | HEX | RGB |
|---|---|---|---|
| Birincil | Nymagro Green | `#0D623A` | 13, 98, 58 |
| Vurgu | Nymagro Orange | `#F3911F` | 243, 145, 31 |
| Yumuşak zemin | Leaf Mist | `#CFE1CD` | 207, 225, 205 |
| Metin (koyu) | Ink | `#231F20` | 35, 31, 32 |
| Metin (ikincil) | Slate Gray | `#6D6E71` | 109, 110, 113 |
| Zemin | White | `#FFFFFF` | 255, 255, 255 |

### Türetilmiş tonlar (UI için)

| Token | HEX | Kullanım |
|---|---|---|
| `green-900` | `#06381F` | Koyu başlık, footer zemini |
| `green-700` | `#0D623A` | **Birincil marka rengi** |
| `green-500` | `#1E8C55` | Hover, ikincil buton |
| `green-100` | `#CFE1CD` | Kart zemini, rozet |
| `green-50`  | `#F1F7F0` | Sayfa zemini |
| `orange-600`| `#D97A0C` | Turuncu hover / basılı hâl |
| `orange-500`| `#F3911F` | **Vurgu / CTA butonu** |
| `orange-100`| `#FDECD6` | Uyarı rozeti zemini |

### Kullanım kuralları

- Birincil aksiyon butonları **turuncu** (`#F3911F`), beyaz metinle.
- Başlıklar, navigasyon ve footer **koyu yeşil** (`#0D623A`).
- Turuncu üzerine yeşil metin **kullanılmaz** (kontrast yetersiz).
- `#F3911F` üzerine beyaz metin sadece 18px+ / kalın kullanılmalı; küçük
  metinlerde `#231F20` tercih edilir (WCAG AA).

---

## 3. Logo

| Dosya | Kullanım |
|---|---|
| `brand/logo/nymagro-logo.png` | Şeffaf zeminli ana logo (web header, favicon kaynağı) |
| `brand/logo/nymagro-logo-white-bg.png` | Beyaz zeminli sürüm (baskı, e-posta imzası) |
| `brand/logo/silatrix-etiket.png` | SILATRIX ambalaj etiketi referansı |

Logo yapısı: küçük harf `nym` (yeşil) + `agro` (turuncu), `a` harfinin üzerinde
iki yapraklı yeşil filiz. Alt satırda gri "Tarım Sanayi ve Ticaret Ltd. Şti."

**Kurallar**
- Logonun etrafında en az `n` harfi yüksekliği kadar boşluk bırakılır.
- Minimum genişlik: web 140px, baskı 30mm.
- Renkleri değiştirilmez; koyu zeminde tamamen beyaz tek renk sürüm kullanılır.
- Logo döndürülmez, gölge/kontur eklenmez, orantısı bozulmaz.

---

## 4. Ürün Ailesi

İthal edilen IDEAPLANTECH ürünleri (2025-12-29 tarihli sevkiyat faturasına göre):

| Ürün | Ambalaj | Not |
|---|---|---|
| SILATRIX | 1 L | Mangan (Mn-EDTA) + Çinko (Zn-EDTA), sıvı mikro besin karışımı — **tescilli** |
| SILECKO MoZ | 5 L | |
| NYMATEX | 5 L | |
| SLASTYK | 5 L | |
| FORTIVIUM | 5 L | |
| BRILIXA | 1 kg | |
| NUTRIDYN KALI CALZ | 5 kg | |
| CUPPERA | 5 L | |

### SILATRIX — tescil verileri

| Alan | Değer |
|---|---|
| Tip ismi | Mangan (Mn-EDTA) ve Çinko (Zn-EDTA) Sıvı Mikro Bitki Besin Maddeleri Karışımı |
| Kriter | EC FERTILIZER |
| Tescil No | 2026TK13785 |
| Lisans No | 6002 |
| Suda çözünür Mn | %1 (tamamı EDTA ile şelatlı) |
| Suda çözünür Zn | %1 (tamamı EDTA ile şelatlı) |
| Stabil pH aralığı | 2 – 10 |
| Ambalaj | 1 L (1,1 kg) / 5 L (5,5 kg) / 10 L (11 kg) |
| Depolama | +2 °C / +35 °C, serin ve kuru, güneş ışığından uzak |
| Raf ömrü | Normal şartlarda 5 yıl |
| Menşe | İthalat (İspanya) |
| Belge geçerliliği | 11.02.2026 tarihinden itibaren 5 yıl |

**Uygulama dozları**

| Bitki | Yapraktan (100 L suya) | Damlama sulama |
|---|---|---|
| Sebzeler | 100-150 ml | 300-500 ml/da |
| Meyvecilik / Bağ | 100-150 ml | 300-500 ml/da |
| Tarla bitkileri (tahıllar hariç) | 150-200 ml | 300-500 ml/da |
| Tropikal meyveler / Muz / Çilek | 150-200 ml | 300-500 ml/da |
| Kesme çiçekçilik | 100-150 ml | 300-500 ml/da |

Vejetasyon dönemi boyunca yapraktan 3-4, damlamadan 2-3 uygulama önerilir.

---

## 5. İçerik ve Mevzuat Notu

Ürünler **kimyevi gübre / bitki besleme ürünüdür**, bitki koruma ürünü
(zirai ilaç / pestisit) değildir. Site metinlerinde "zirai ilaç", "pestisit",
"ilaçlama" ifadeleri kullanılmamalı; bunun yerine **"bitki besleme ürünleri"**,
**"mikro besin elementleri"**, **"sıvı gübre"** terimleri tercih edilmelidir.
Bu hem tescil belgesiyle uyumludur hem de yanıltıcı beyan riskini önler.

Ürün sayfalarında zorunlu olarak yer alması gerekenler: garanti edilen içerik,
uygulama şekli ve dozu, uyarılar, depolama şartları, firma beyanı, Bakanlık
Tescil No ve Lisans No.
