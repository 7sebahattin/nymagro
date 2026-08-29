/**
 * Mobil uyum testi — gerçek tarayıcı ölçümü.
 * --------------------------------------------------------------------------
 * Kullanım:  node tests/mobile/check.mjs <html-dizini>
 * Çıktı:     tek satır JSON  { bulgular: [...] }
 * Çıkış kodu: her zaman 0 — kararı PHP sarmalayıcı verir (tests/regression).
 *
 * Ölçülenler (hepsi gerçek Chromium'da, gerçek mobil ekran boyutlarında):
 *  1) YATAY TAŞMA — sayfa görünen alandan geniş mi? Mobilde bir numaralı
 *     yerleşim hatasıdır: sayfa yana kayar, sabit konumlu alt navigasyon
 *     ekranın dışında kalır ("alt bar alta kayıyor").
 *  2) SABİT ALT NAVİGASYON — .bottom-nav gerçekten görünür alanın altında,
 *     tam olarak görünüyor mu? Hem sayfa başındayken hem de en alta
 *     kaydırıldıktan sonra ölçülür.
 *  3) İÇERİK GİZLENMESİ — sayfanın son içeriği alt navigasyonun altında
 *     kalıyor mu (yeterli padding-bottom var mı)?
 *  4) TAŞMAYA NEDEN OLAN ÖĞE — taşma varsa suçlu öğe raporlanır ki
 *     düzeltme tahmine dayanmasın.
 */

import { readdirSync, existsSync } from 'node:fs';
import { resolve, basename } from 'node:path';
import { execSync } from 'node:child_process';

/**
 * Playwright'ı yerelde veya global npm dizininde bul.
 * (ESM içe aktarma NODE_PATH'i dikkate almaz; global kurulumda mutlak yol gerekir.)
 */
async function playwrightYukle() {
  try {
    return await import('playwright');
  } catch { /* yerelde yok — global dene */ }
  try {
    const kok = execSync('npm root -g', { encoding: 'utf8', stdio: ['ignore', 'pipe', 'ignore'] }).trim();
    const yol = resolve(kok, 'playwright', 'index.js');
    if (existsSync(yol)) {
      return await import('file://' + yol);
    }
  } catch { /* yok sayılır */ }
  return null;
}

const pw = await playwrightYukle();
// CommonJS paketi ESM'den içe aktarıldığında dışa açılanlar `default` altında
// olabilir; iki durumu da destekle.
const chromium = pw?.chromium ?? pw?.default?.chromium ?? null;
if (!chromium) {
  console.log(JSON.stringify({ atlandi: 'playwright bulunamadı', bulgular: [] }));
  process.exit(0);
}

const dizin = process.argv[2];
if (!dizin) {
  console.log(JSON.stringify({ bulgular: [{ sayfa: '-', tur: 'yapilandirma', mesaj: 'HTML dizini verilmedi' }] }));
  process.exit(0);
}

/** Test edilen ekran boyutları — yaygın Android ve iPhone genişlikleri. */
const EKRANLAR = [
  { ad: 'Android 360x800', width: 360, height: 800 },
  { ad: 'iPhone 390x844', width: 390, height: 844 },
  { ad: 'Dar 320x568', width: 320, height: 568 },
];

/**
 * Sayfa içinde çalışır: form alanlarının metin/arka plan kontrastını ölçer.
 *
 * Koyu tema bu panelde VARSAYILANDIR. Bir yerde sabit (hardcoded) açık arka
 * plan ya da koyu metin rengi kaldıysa kutu "boş/siyah" görünür — kullanıcının
 * "bazı text box'lar okunmuyor" dediği hata sınıfı budur. Gözle aramak yerine
 * gerçek hesaplanmış renkler alınıp WCAG kontrast oranı hesaplanır.
 *
 * NOT: Playwright bu fonksiyonun KAYNAĞINI sayfaya taşır; bu yüzden dış
 * kapsamdan hiçbir şeye başvurmaz (kendi kendine yeter).
 */
function olcKontrast() {
  const ayristirRenk = (s) => {
    const m = /rgba?\(([^)]+)\)/.exec(s || '');
    if (!m) return null;
    const p = m[1].split(',').map((x) => parseFloat(x.trim()));
    return { r: p[0], g: p[1], b: p[2], a: p.length > 3 ? p[3] : 1 };
  };
  /**
   * Kaynak-üstünde (source-over) bindirme.
   *
   * DİKKAT: sonucun alfası SABİT 1 DEĞİLDİR. Koyu temada hem input zemini
   * (--input-bg) hem kart zemini (--card-bg) yarı saydam BEYAZ'dır; alfa
   * yanlış hesaplanırsa üst üste iki saydam beyaz "tam beyaz" sanılır ve
   * gerçekte koyu olan zemin beyaz raporlanır (sahte kontrast hatası).
   */
  const uzerineKoy = (ust, alt) => {
    const aOut = ust.a + alt.a * (1 - ust.a);
    if (aOut === 0) return { r: 0, g: 0, b: 0, a: 0 };
    const karistir = (cU, cA) => (cU * ust.a + cA * alt.a * (1 - ust.a)) / aOut;
    return { r: karistir(ust.r, alt.r), g: karistir(ust.g, alt.g), b: karistir(ust.b, alt.b), a: aOut };
  };
  const bagilParlaklik = (c) => {
    const f = (v) => { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); };
    return 0.2126 * f(c.r) + 0.7152 * f(c.g) + 0.0722 * f(c.b);
  };
  const kontrast = (a, b) => {
    const l1 = bagilParlaklik(a);
    const l2 = bagilParlaklik(b);
    return (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05);
  };
  /** Öğenin gerçekte üzerine oturduğu (saydamlıklar bindirilmiş) arka plan. */
  const etkinArkaPlan = (el) => {
    let birikim = null;
    for (let p = el; p; p = p.parentElement) {
      const ps = getComputedStyle(p);
      // Degrade/resim zeminler tek bir renge indirgenemez (getComputedStyle
      // yalnızca background-COLOR döner; degrade orada rgba(0,0,0,0) görünür).
      // Böyle bir zemin varsa ölçüm güvenilir değildir — sahte bulgu üretmemek
      // için bu öğeyi tamamen atla.
      if (ps.backgroundImage && ps.backgroundImage !== 'none') return null;
      const bg = ayristirRenk(ps.backgroundColor);
      if (!bg || bg.a === 0) continue;
      birikim = birikim ? uzerineKoy(birikim, bg) : bg;
      if (birikim.a >= 0.999) break;
    }
    // Hiçbir ata opak değilse tarayıcı tuvali (beyaz) en altta kalır.
    if (!birikim) return { r: 255, g: 255, b: 255, a: 1 };
    if (birikim.a < 0.999) birikim = uzerineKoy(birikim, { r: 255, g: 255, b: 255, a: 1 });
    return birikim;
  };

  /** Bir öğenin doğrudan (çocuklarına ait olmayan) metni var mı? */
  const kendiMetniVar = (el) => {
    for (const n of el.childNodes) {
      if (n.nodeType === 3 && n.textContent.trim().length > 0) return true;
    }
    return false;
  };

  const okunmayanlar = [];
  const gorulen = new Set();

  // Hem form alanları hem de doğrudan metin taşıyan öğeler ölçülür:
  // "okunmayan kutu" şikâyeti yalnızca input'larda değil, tablo hücresi /
  // rozet / açılır menü satırında da ortaya çıkabiliyor.
  const adaylar = document.querySelectorAll(
    'input, select, textarea, td, th, a, button, label, span, div, p, li, h1, h2, h3, h4, h5, h6, option'
  );

  for (const el of adaylar) {
    const st = getComputedStyle(el);
    if (st.display === 'none' || st.visibility === 'hidden' || parseFloat(st.opacity) < 0.15) continue;

    const formAlani = ['INPUT', 'SELECT', 'TEXTAREA'].includes(el.tagName);
    if (formAlani) {
      const tur = (el.getAttribute('type') || '').toLowerCase();
      if (['hidden', 'checkbox', 'radio', 'submit', 'button', 'image', 'range', 'color', 'file'].includes(tur)) continue;
    } else if (el.tagName !== 'OPTION' && !kendiMetniVar(el)) {
      continue;   // metni yoksa rengi de önemli değil
    }

    const r = el.getBoundingClientRect();
    if (!formAlani && el.tagName !== 'OPTION' && (r.width < 8 || r.height < 8)) continue;
    if (formAlani && (r.width < 8 || r.height < 8)) continue;

    const metin = ayristirRenk(st.color);
    if (!metin || metin.a === 0) continue;

    // <option> açılır listede kendi zeminine sahiptir; kendi background'ı
    // varsa ONU kullan, yoksa select'in zeminini devral. (Aksi halde kendi
    // beyaz zeminini ayarlamış bir option, select'in koyu zemininde
    // "koyu üstüne koyu" gibi yanlış raporlanır.)
    let zeminKaynagi = el;
    if (el.tagName === 'OPTION') {
      const kendiBg = ayristirRenk(st.backgroundColor);
      if (!kendiBg || kendiBg.a === 0) zeminKaynagi = el.closest('select') || el;
    }
    const arka = etkinArkaPlan(zeminKaynagi);
    if (!arka) continue;   // degrade zemin — güvenilir ölçülemez
    const onPlan = metin.a < 1 ? uzerineKoy(metin, arka) : metin;
    const oran = kontrast(onPlan, arka);

    // WCAG: büyük metinde eşik 3.0, normal metinde 4.5
    const boyut = parseFloat(st.fontSize) || 14;
    const kalin = (parseInt(st.fontWeight, 10) || 400) >= 700;
    const buyukMetin = boyut >= 24 || (kalin && boyut >= 18.66);
    const esik = buyukMetin ? 3.0 : 4.5;

    if (oran < esik) {
      const anahtar = `${el.tagName}|${el.getAttribute('name') || el.id || ''}|${(el.className || '').toString().slice(0, 30)}|${st.color}`;
      if (gorulen.has(anahtar)) continue;
      gorulen.add(anahtar);
      okunmayanlar.push({
        etiket: el.tagName.toLowerCase(),
        formAlani,
        ad: el.getAttribute('name') || el.id || '',
        sinif: (el.className || '').toString().slice(0, 40),
        ornekMetin: (el.textContent || '').trim().slice(0, 28),
        metinRengi: st.color,
        arkaPlan: `rgb(${Math.round(arka.r)}, ${Math.round(arka.g)}, ${Math.round(arka.b)})`,
        oran: Math.round(oran * 100) / 100,
        esik,
      });
    }
  }
  return okunmayanlar;
}

const bulgular = [];

const tarayici = await chromium.launch({ args: ['--no-sandbox'] });

try {
  const dosyalar = readdirSync(dizin).filter((d) => d.endsWith('.html'));

  for (const dosya of dosyalar) {
    const sayfaAdi = basename(dosya, '.html');

    for (const ekran of EKRANLAR) {
      const context = await tarayici.newContext({
        viewport: { width: ekran.width, height: ekran.height },
        deviceScaleFactor: 2,
        isMobile: true,
        hasTouch: true,
      });
      const page = await context.newPage();
      await page.goto('file://' + resolve(dizin, dosya), { waitUntil: 'load' });
      // Yerleşimin oturması için bir kare bekle
      await page.evaluate(() => new Promise((r) => requestAnimationFrame(() => requestAnimationFrame(r))));

      const olcum = await page.evaluate(() => {
        const vw = document.documentElement.clientWidth;

        // 1) Yatay taşma + suçlu öğe
        const govdeGenislik = Math.max(
          document.documentElement.scrollWidth,
          document.body ? document.body.scrollWidth : 0
        );
        let sucluOgeler = [];
        if (govdeGenislik > vw + 1) {
          for (const el of document.querySelectorAll('body *')) {
            const st = getComputedStyle(el);
            if (st.display === 'none' || st.visibility === 'hidden') continue;
            // Kendi içinde kaydırılabilen kapsayıcılar taşma sayılmaz
            if (st.overflowX === 'auto' || st.overflowX === 'scroll' || st.overflowX === 'hidden') continue;
            const r = el.getBoundingClientRect();
            if (r.width === 0 && r.height === 0) continue;
            if (r.right > vw + 1) {
              // Kaydırılabilir bir ata varsa o zaten taşmayı yutar
              let atasiKaydirilabilir = false;
              for (let p = el.parentElement; p && p !== document.body; p = p.parentElement) {
                const ps = getComputedStyle(p);
                if (ps.overflowX === 'auto' || ps.overflowX === 'scroll' || ps.overflowX === 'hidden') {
                  atasiKaydirilabilir = true;
                  break;
                }
              }
              if (atasiKaydirilabilir) continue;
              sucluOgeler.push({
                etiket: el.tagName.toLowerCase(),
                sinif: (el.className || '').toString().slice(0, 60),
                id: el.id || '',
                sag: Math.round(r.right),
                genislik: Math.round(r.width),
              });
            }
          }
          // En dıştaki birkaç suçluyu bildir
          sucluOgeler = sucluOgeler.slice(0, 5);
        }

        // 2) Sabit alt navigasyon konumu
        const nav = document.querySelector('.bottom-nav');
        let navBilgi = null;
        if (nav) {
          const st = getComputedStyle(nav);
          const r = nav.getBoundingClientRect();
          navBilgi = {
            goruntuleniyor: st.display !== 'none',
            konum: st.position,
            ust: Math.round(r.top),
            alt: Math.round(r.bottom),
            sol: Math.round(r.left),
            sag: Math.round(r.right),
            yukseklik: Math.round(r.height),
          };
        }

        // 3) Kesilip erişilemez hale gelen etkileşimli öğeler.
        //    Yatay taşma `body { overflow-x:hidden }` ile gizlenebilir; bu
        //    durumda sayfa kaymaz ama ekranın dışında kalan bağlantı/butona
        //    ULAŞILAMAZ (ör. sekme şeridinin son sekmesi). Kaydırılabilir bir
        //    ata varsa öğeye parmakla ulaşılabilir; yoksa gerçekten kayıptır.
        const erisilemezler = [];
        for (const el of document.querySelectorAll('a, button, input, select, textarea')) {
          const st = getComputedStyle(el);
          if (st.display === 'none' || st.visibility === 'hidden') continue;
          const r = el.getBoundingClientRect();
          if (r.width === 0 && r.height === 0) continue;
          if (r.right <= vw + 1 && r.left >= -1) continue;

          let kaydirilabilirAta = false;
          for (let p = el.parentElement; p; p = p.parentElement) {
            const ps = getComputedStyle(p);
            if ((ps.overflowX === 'auto' || ps.overflowX === 'scroll') && p.scrollWidth > p.clientWidth + 1) {
              kaydirilabilirAta = true;
              break;
            }
          }
          if (kaydirilabilirAta) continue;

          erisilemezler.push({
            etiket: el.tagName.toLowerCase(),
            sinif: (el.className || '').toString().slice(0, 50),
            metin: (el.textContent || '').trim().slice(0, 30),
            sol: Math.round(r.left),
            sag: Math.round(r.right),
          });
        }

        // 4) DOKUNMA HEDEFİ BOYUTU
        //    Parmakla kullanılan bir kontrolün kenarı ~44px'in altına inince
        //    ıskalanmaya başlar (iOS HIG 44pt, Material 48dp). Metin içindeki
        //    satır içi bağlantılar hariç tutulur — onlar buton değildir;
        //    yalnızca buton gibi davranan (blok/flex görünümlü) öğeler ölçülür.
        const kucukHedefler = [];
        for (const el of document.querySelectorAll('button, select, a, input[type="checkbox"], input[type="radio"]')) {
          const st = getComputedStyle(el);
          if (st.display === 'none' || st.visibility === 'hidden') continue;
          if (el.tagName === 'A') {
            if (st.display === 'inline') continue;                    // metin içi bağlantı
            // WCAG 2.5.8 metin içindeki bağlantıları asgari hedef boyutundan
            // muaf tutar. "Buton gibi" görünen bağlantıları ayırmak için
            // zemin/kenarlık varlığına bakılır: ikisi de yoksa bu düz bir
            // metin bağlantısıdır (kırıntı menüsü, tablo içi ad bağlantısı).
            const zemin = /rgba?\(([^)]+)\)/.exec(st.backgroundColor || '');
            const zeminSaydam = !zemin || parseFloat((zemin[1].split(',')[3] ?? '1').trim()) === 0;
            const kenarliksiz = parseFloat(st.borderTopWidth || '0') === 0
              && parseFloat(st.borderBottomWidth || '0') === 0;
            if (zeminSaydam && kenarliksiz) continue;
          }
          const r = el.getBoundingClientRect();
          if (r.width === 0 || r.height === 0) continue;
          // Ekran dışındakiler ayrı bir bulgu türünde raporlanıyor
          if (r.right < 0 || r.left > vw) continue;

          // Onay kutusu/radyo görsel olarak küçüktür ama ETİKETİNE tıklamak da
          // onu seçer; gerçek dokunma alanı kutu + etikettir.
          let etkin = { w: r.width, h: r.height };
          if (el.tagName === 'INPUT') {
            const etiketEl = (el.id && document.querySelector(`label[for="${CSS.escape(el.id)}"]`)) || el.closest('label');
            if (etiketEl) {
              const lr = etiketEl.getBoundingClientRect();
              if (lr.width > 0 && lr.height > 0) {
                etkin = {
                  w: Math.max(r.right, lr.right) - Math.min(r.left, lr.left),
                  h: Math.max(r.bottom, lr.bottom) - Math.min(r.top, lr.top),
                };
              }
            }
          }

          const enKucukKenar = Math.min(etkin.w, etkin.h);
          if (enKucukKenar < 44) {
            kucukHedefler.push({
              etiket: el.tagName.toLowerCase(),
              sinif: (el.className || '').toString().slice(0, 40),
              metin: (el.textContent || '').trim().slice(0, 24),
              genislik: Math.round(etkin.w),
              yukseklik: Math.round(etkin.h),
            });
          }
        }

        return {
          vw,
          vh: window.innerHeight,
          govdeGenislik,
          sucluOgeler,
          erisilemezler: erisilemezler.slice(0, 6),
          kucukHedefler,
          navBilgi,
          bodyPaddingBottom: document.querySelector('.main-wrap')
            ? getComputedStyle(document.querySelector('.main-wrap')).paddingBottom
            : null,
        };
      });

      const etiket = `${sayfaAdi} @ ${ekran.ad}`;

      // ── 1) Yatay taşma ──
      if (olcum.govdeGenislik > olcum.vw + 1) {
        bulgular.push({
          sayfa: sayfaAdi,
          ekran: ekran.ad,
          tur: 'yatay-tasma',
          mesaj: `${etiket}: sayfa ${olcum.govdeGenislik}px, ekran ${olcum.vw}px (${olcum.govdeGenislik - olcum.vw}px taşma)`,
          suclular: olcum.sucluOgeler,
        });
      }

      // ── 1b) Ekran dışında kalıp ulaşılamayan etkileşimli öğeler ──
      if (olcum.erisilemezler.length > 0) {
        bulgular.push({
          sayfa: sayfaAdi,
          ekran: ekran.ad,
          tur: 'erisilemez-etkilesimli-oge',
          mesaj: `${etiket}: ${olcum.erisilemezler.length} etkileşimli öğe ekran dışında ve kaydırılamıyor (ör. "${olcum.erisilemezler[0].metin}")`,
          suclular: olcum.erisilemezler,
        });
      }

      // ── 1c) Parmakla ıskalanacak kadar küçük dokunma hedefleri ──
      // İki seviye: < 30px gerçekten zor (testi düşürür), 30–44px arası
      // iyileştirme önerisi (bilgi).
      const cokKucuk = olcum.kucukHedefler.filter((h) => Math.min(h.genislik, h.yukseklik) < 30);
      const sinirda = olcum.kucukHedefler.filter((h) => Math.min(h.genislik, h.yukseklik) >= 30);
      if (cokKucuk.length > 0) {
        const ilk = cokKucuk[0];
        bulgular.push({
          sayfa: sayfaAdi, ekran: ekran.ad, tur: 'kucuk-dokunma-hedefi',
          mesaj: `${etiket}: ${cokKucuk.length} kontrol parmakla ıskalanacak kadar küçük `
            + `(ör. ${ilk.etiket}.${ilk.sinif || '-'} "${ilk.metin}" ${ilk.genislik}x${ilk.yukseklik}px, en az 44px olmalı)`,
          suclular: cokKucuk.slice(0, 8),
        });
      }
      if (sinirda.length > 0) {
        bulgular.push({
          sayfa: sayfaAdi, ekran: ekran.ad, tur: 'bilgi-sinirda-dokunma-hedefi',
          mesaj: `${etiket}: ${sinirda.length} kontrol 30–44px arası (kullanılabilir ama ideal değil)`,
          suclular: sinirda.slice(0, 8),
        });
      }

      // ── 2) Alt navigasyon görünür ve ekranın altında mı? ──
      if (!olcum.navBilgi) {
        bulgular.push({ sayfa: sayfaAdi, ekran: ekran.ad, tur: 'alt-nav-yok', mesaj: `${etiket}: .bottom-nav öğesi sayfada yok` });
      } else if (!olcum.navBilgi.goruntuleniyor) {
        bulgular.push({ sayfa: sayfaAdi, ekran: ekran.ad, tur: 'alt-nav-gizli', mesaj: `${etiket}: .bottom-nav mobilde görünmüyor (display:none)` });
      } else {
        if (olcum.navBilgi.konum !== 'fixed') {
          bulgular.push({
            sayfa: sayfaAdi, ekran: ekran.ad, tur: 'alt-nav-sabit-degil',
            mesaj: `${etiket}: .bottom-nav position=${olcum.navBilgi.konum} (fixed olmalı)`,
          });
        }
        // Alt kenar ekranın altına taşmamalı (birkaç px tolerans)
        if (olcum.navBilgi.alt > olcum.vh + 2) {
          bulgular.push({
            sayfa: sayfaAdi, ekran: ekran.ad, tur: 'alt-nav-ekran-disi',
            mesaj: `${etiket}: .bottom-nav alt kenarı ${olcum.navBilgi.alt}px, ekran yüksekliği ${olcum.vh}px — bar ekranın altına kaymış`,
          });
        }
        // Sağ kenar ekranın dışına taşmamalı (yatay taşmanın yan etkisi)
        if (olcum.navBilgi.sag > olcum.vw + 2 || olcum.navBilgi.sol < -2) {
          bulgular.push({
            sayfa: sayfaAdi, ekran: ekran.ad, tur: 'alt-nav-yatay-kaymis',
            mesaj: `${etiket}: .bottom-nav yatayda ${olcum.navBilgi.sol}–${olcum.navBilgi.sag}px, ekran 0–${olcum.vw}px`,
          });
        }
      }

      // ── 3) En alta kaydırınca son içerik alt barın altında kalıyor mu? ──
      const kaydirmaSonrasi = await page.evaluate(() => {
        // panel-ui.css `html { scroll-behavior: smooth }` tanımlar; animasyonlu
        // kaydırma ölçümü bozar (scrollTo çağrısı anında etkili olmaz).
        // Ölçüm süresince anında kaydırmaya zorla.
        const gecici = document.createElement('style');
        gecici.textContent = 'html{scroll-behavior:auto !important}';
        document.head.appendChild(gecici);
        window.scrollTo(0, document.documentElement.scrollHeight);
        return new Promise((r) =>
          requestAnimationFrame(() =>
            requestAnimationFrame(() => {
              const nav = document.querySelector('.bottom-nav');
              const navUst = nav && getComputedStyle(nav).display !== 'none'
                ? nav.getBoundingClientRect().top
                : window.innerHeight;
              const main = document.querySelector('.page-content');
              let sonAlt = null;
              if (main) {
                // Sayfanın AKIŞTAKİ son görünür içeriğini bul.
                // position:fixed/absolute olanlar (toast kabı, modal, açılır
                // menü) sayfa akışının parçası değildir; alt boşluğun yeterli
                // olup olmadığını onlarla ölçmek yanıltıcıdır.
                for (let el = main.lastElementChild; el; el = el.previousElementSibling) {
                  const st = getComputedStyle(el);
                  if (st.display === 'none' || st.visibility === 'hidden') continue;
                  if (st.position === 'fixed' || st.position === 'absolute') continue;
                  const r = el.getBoundingClientRect();
                  if (r.width < 1 || r.height < 1) continue;
                  sonAlt = r.bottom;
                  break;
                }
              }
              r({
                navUst: Math.round(navUst),
                sonAlt: sonAlt === null ? null : Math.round(sonAlt),
                navKonum: nav ? getComputedStyle(nav).position : null,
                navAlt: nav ? Math.round(nav.getBoundingClientRect().bottom) : null,
                vh: window.innerHeight,
              });
            })
          )
        );
      });

      if (kaydirmaSonrasi.navAlt !== null && kaydirmaSonrasi.navAlt > kaydirmaSonrasi.vh + 2) {
        bulgular.push({
          sayfa: sayfaAdi, ekran: ekran.ad, tur: 'alt-nav-kaydirinca-kayiyor',
          mesaj: `${etiket}: en alta kaydırıldığında .bottom-nav alt kenarı ${kaydirmaSonrasi.navAlt}px (ekran ${kaydirmaSonrasi.vh}px)`,
        });
      }
      if (kaydirmaSonrasi.sonAlt !== null && kaydirmaSonrasi.sonAlt > kaydirmaSonrasi.navUst + 1) {
        bulgular.push({
          sayfa: sayfaAdi, ekran: ekran.ad, tur: 'icerik-alt-barin-altinda',
          mesaj: `${etiket}: son içerik ${kaydirmaSonrasi.sonAlt}px'e kadar iniyor ama alt bar ${kaydirmaSonrasi.navUst}px'de başlıyor — içerik barın altında kalıyor`,
        });
      }

      await context.close();
    }

    // ── 5) Okunabilirlik: HER İKİ TEMADA da form alanları okunur olmalı ──
    // Koyu tema varsayılandır; açık tema body[data-theme="acik"] ile açılır.
    // İkisi ayrı ayrı ölçülür, çünkü sabit kodlanmış bir renk çoğu zaman
    // yalnızca bir temada bozulur.
    for (const tema of ['koyu', 'acik']) {
      const context = await tarayici.newContext({
        viewport: { width: 390, height: 844 },
        deviceScaleFactor: 2,
        isMobile: true,
        hasTouch: true,
      });
      const page = await context.newPage();
      await page.goto('file://' + resolve(dizin, dosya), { waitUntil: 'load' });
      await page.evaluate((t) => {
        // Renk geçişleri (transition) ölçümü bozar: tema değiştikten hemen
        // sonra okunan renk, eski ve yeni rengin ARASINDA bir ara değerdir ve
        // sahte kontrast hatası üretir. Ölçüm boyunca tüm geçiş/animasyonları
        // kapat.
        const durdur = document.createElement('style');
        durdur.textContent = '*,*::before,*::after{transition:none !important;animation:none !important}';
        document.head.appendChild(durdur);

        // panel-ui.css yalnızca body[data-theme="acik"] için açık temayı açar;
        // diğer her değer koyu temadır.
        document.body.setAttribute('data-theme', t === 'acik' ? 'acik' : 'koyu');
      }, tema);
      await page.evaluate(() => new Promise((r) => requestAnimationFrame(() => requestAnimationFrame(r))));

      const okunmayanlar = await page.evaluate(olcKontrast);
      const temaAdi = tema === 'acik' ? 'açık tema' : 'koyu tema';

      // İKİ SEVİYE:
      //  · < 2.0  → metin zeminden neredeyse ayırt edilemiyor (koyu üstüne
      //    koyu, beyaz üstüne beyaz). Gerçek hata; testi DÜŞÜRÜR.
      //  · 2.0–4.5 → çoğunlukla renkli marka butonları (beyaz yazılı yeşil/
      //    mavi düğmeler). WCAG AA'nın altında ama okunabiliyor; bilgi amaçlı
      //    raporlanır, testi düşürmez.
      const okunmayan = okunmayanlar.filter((o) => o.oran < 2.0);
      const dusukKontrast = okunmayanlar.filter((o) => o.oran >= 2.0);

      if (okunmayan.length > 0) {
        const ilk = okunmayan[0];
        bulgular.push({
          sayfa: sayfaAdi,
          ekran: temaAdi,
          tur: 'okunmayan-metin',
          mesaj: `${sayfaAdi} @ ${temaAdi}: ${okunmayan.length} öğenin metni zeminden ayırt edilemiyor `
            + `(ör. ${ilk.etiket}[${ilk.ad || ilk.sinif}] metin ${ilk.metinRengi} / zemin ${ilk.arkaPlan} = ${ilk.oran}:1)`,
          suclular: okunmayan,
        });
      }
      if (dusukKontrast.length > 0) {
        bulgular.push({
          sayfa: sayfaAdi,
          ekran: temaAdi,
          tur: 'bilgi-dusuk-kontrast',
          mesaj: `${sayfaAdi} @ ${temaAdi}: ${dusukKontrast.length} öğe WCAG AA eşiğinin altında (2.0–4.5 arası; okunabiliyor)`,
          suclular: dusukKontrast,
        });
      }

      await context.close();
    }
  }
} finally {
  await tarayici.close();
}

console.log(JSON.stringify({ bulgular }));
