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

        return {
          vw,
          vh: window.innerHeight,
          govdeGenislik,
          sucluOgeler,
          erisilemezler: erisilemezler.slice(0, 6),
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
              const sonCocuk = main && main.lastElementChild ? main.lastElementChild : null;
              let sonAlt = null;
              if (sonCocuk) {
                // Görünür son öğeyi bul
                let el = sonCocuk;
                while (el && getComputedStyle(el).display === 'none') el = el.previousElementSibling;
                if (el) sonAlt = el.getBoundingClientRect().bottom;
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
  }
} finally {
  await tarayici.close();
}

console.log(JSON.stringify({ bulgular }));
