/* ==========================================================================
   Nymagro Ticaret Paneli — Arayüz davranışları
   Kütüphane gerektirmez (Bootstrap yalnızca dropdown/modal için ayrıca yüklü).
   ========================================================================== */
(function () {
  'use strict';

  /* ── CSRF: TÜM form/fetch/link'lere otomatik token ekleme ────────────
     Bu dosya main.php layout'unda TÜM panel sayfalarında yüklendiği için,
     buradaki tek bir değişiklik ~20 mevcut modülün view dosyalarının HİÇBİRİ
     tek tek düzenlenmeden CSRF korumasına dahil olmasını sağlar:
       1) fetch(): POST/PUT/PATCH/DELETE gövdesine/başlığına token eklenir.
       2) <form method="post"> submit anında gizli csrf_token alanı eklenir.
       3) Aynı-origin panel linkleri (ör. eski GET tabanlı "/modul/sil/{id}")
          tıklanmadan hemen önce href'e ?csrf=... eklenir.
     Sunucu tarafı (Csrf::verifyOrDeny) TEK doğruluk kaynağıdır; bu JS sadece
     mevcut kullanıcı deneyimini bozmadan token'ı taşımanın bir yoludur —
     JS engellenirse/başarısız olursa sunucu yine de isteği reddeder. */
  (function setupCsrf() {
    var TOKEN = window.NYM_CSRF_TOKEN || '';
    var BASE = window.NYM_BASE_URL || '';
    if (!TOKEN) return;

    function sameOrigin(url) {
      if (!url) return false;
      if (url.indexOf('/') === 0) return true; // köke göreli
      if (BASE && url.indexOf(BASE) === 0) return true;
      return url.indexOf('http') !== 0; // şema yok → göreli kabul et
    }

    function appendCsrfParam(url) {
      if (/[?&]csrf=/.test(url)) return url;
      var sep = url.indexOf('?') === -1 ? '?' : '&';
      return url + sep + 'csrf=' + encodeURIComponent(TOKEN);
    }

    // 1) fetch() gövdesine/başlığına/URL'sine token ekle
    if (window.fetch) {
      var origFetch = window.fetch.bind(window);
      window.fetch = function (input, init) {
        init = init || {};
        var method = (init.method || (input && input.method) || 'GET').toString().toUpperCase();
        var url = typeof input === 'string' ? input : (input && input.url) || '';
        if (!sameOrigin(url)) {
          return origFetch(input, init); // farklı origin — dokunma
        }
        if (method === 'GET' || method === 'HEAD') {
          // Bare fetch(url) ile tetiklenen GET-tabanlı mutasyonlar da olabilir
          // (ör. eski `/masraf/kopyala/{id}`, `/masraf/sil/{id}` gibi form
          // kullanmayan aksiyonlar). Zararsız/kullanılmayan bir parametre
          // olarak SAF görüntüleme isteklerinde de eklenir.
          if (typeof input === 'string') {
            return origFetch(appendCsrfParam(url), init);
          }
          return origFetch(input, init);
        }
        if (['POST', 'PUT', 'PATCH', 'DELETE'].indexOf(method) === -1) {
          return origFetch(input, init);
        }
        if (init.body instanceof FormData) {
          if (!init.body.has('csrf_token')) init.body.append('csrf_token', TOKEN);
        } else if (init.body instanceof URLSearchParams) {
          if (!init.body.has('csrf_token')) init.body.append('csrf_token', TOKEN);
        } else if (typeof init.body === 'string' && init.body.length && init.body.indexOf('csrf_token=') === -1
                   && (!init.headers || !(init.headers['Content-Type'] || init.headers['content-type'] || '').includes('json'))) {
          init.body += (init.body.indexOf('=') !== -1 ? '&' : '') + 'csrf_token=' + encodeURIComponent(TOKEN);
        } else {
          init.headers = init.headers || {};
          if (init.headers instanceof Headers) {
            if (!init.headers.has('X-CSRF-Token')) init.headers.set('X-CSRF-Token', TOKEN);
          } else if (!init.headers['X-CSRF-Token']) {
            init.headers['X-CSRF-Token'] = TOKEN;
          }
        }
        return origFetch(input, init);
      };
    }

    // 2) <form method="post"> submit anında gizli alan ekle
    document.addEventListener('submit', function (e) {
      var form = e.target;
      if (!form || form.tagName !== 'FORM') return;
      var method = (form.getAttribute('method') || 'GET').toUpperCase();
      if (method !== 'POST') return;
      if (form.querySelector('input[name="csrf_token"]')) return;
      var input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'csrf_token';
      input.value = TOKEN;
      form.appendChild(input);
    }, true);

    // 3) Aynı-origin panel linkleri: tıklanmadan önce href'e ?csrf= ekle
    //    (eski GET-tabanlı sil/iptal/durum linkleri için — sunucu VIEW
    //    olmayan GET işlemlerinde bunu zorunlu kılar, VIEW linklerinde
    //    zararsız/kullanılmayan bir parametre olarak kalır).
    document.addEventListener('click', function (e) {
      if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
      var a = e.target.closest('a[href]');
      if (!a || !BASE) return;
      var href = a.getAttribute('href') || '';
      if (href.indexOf(BASE + '/') !== 0) return;
      if (a.target && a.target !== '_self') return;
      if (a.hasAttribute('download')) return;
      if (/[?&]csrf=/.test(href)) return;
      var sep = href.indexOf('?') === -1 ? '?' : '&';
      a.setAttribute('href', href + sep + 'csrf=' + encodeURIComponent(TOKEN));
    }, true);
  })();

  /* ── Büyük harf: metin kutuları yazarken otomatik büyük harfe çevrilir ──
     ────────────────────────────────────────────────────────────────────
     Panelde girilen ünvan/isim/adres gibi serbest metinler resmi belgelerde
     zaten BÜYÜK HARFLE tutuluyor — kullanıcı yazarken kutunun içeriği anlık
     olarak büyük harfe çevrilir (kaydedilen gerçek değer de böyle olur).
     Türkçe'ye özgü büyük/küçük harf kuralı (i↔İ, ı↔I) doğru uygulansın diye
     toLocaleUpperCase('tr') kullanılıyor — düz toUpperCase() "izmir"i
     "IZMIR" yapardı (yanlış), bu ise doğru şekilde "İZMİR" üretir.
     Şifre/e-posta/URL/arama/sayı gibi büyük/küçük harf duyarlı veya bu
     dönüşümden zarar görecek alanlar hariç tutulur. Bu dosya sadece panel
     sayfalarında yüklendiği için giriş (login) ekranını hiç etkilemez. */
  (function setupAutoUppercase() {
    var EXCLUDED_TYPES = {
      password: 1, email: 1, url: 1, search: 1, number: 1, tel: 1,
      date: 1, time: 1, 'datetime-local': 1, month: 1, week: 1, color: 1,
      file: 1, hidden: 1, checkbox: 1, radio: 1, range: 1,
      submit: 1, button: 1, reset: 1, image: 1
    };
    // Kod/URL/e-posta/şifre gibi büyük/küçük harfin anlamlı olduğu alanlar
    // type="text" olsa bile isim/kimliğine göre ayrıca hariç tutulur.
    var EXCLUDED_NAME_RE = /kod|barkod|url|eposta|e_posta|email|sifre|password|username|slug/i;

    function shouldSkip(el) {
      if (!el || (el.tagName !== 'INPUT' && el.tagName !== 'TEXTAREA')) return true;
      if (el.tagName === 'INPUT') {
        var type = (el.getAttribute('type') || 'text').toLowerCase();
        if (EXCLUDED_TYPES[type]) return true;
      }
      if (el.hasAttribute('data-no-uppercase') || (el.className && /\bno-uppercase\b/.test(el.className))) return true;
      var ident = (el.getAttribute('name') || '') + ' ' + (el.id || '');
      if (EXCLUDED_NAME_RE.test(ident)) return true;
      return false;
    }

    document.addEventListener('input', function (e) {
      var el = e.target;
      if (shouldSkip(el)) return;
      var upper = el.value.toLocaleUpperCase('tr');
      if (upper === el.value) return;
      var start = el.selectionStart;
      var end = el.selectionEnd;
      el.value = upper;
      if (start !== null && end !== null && typeof el.setSelectionRange === 'function') {
        try { el.setSelectionRange(start, end); } catch (ignore) {}
      }
    }, true);
  })();

  /* ── nymPost(): GET linkleriyle state-changing işlem yapmayı önlemek için
     ────────────────────────────────────────────────────────────────────
     Eski kod tabanında "Sil"/"İptal Et"/"Taksit Öde" gibi linkler basit
     <a href="..."> idi (GET). Bunları görsel/CSS'i bozmadan POST'a çevirmek
     için: <a href="#" onclick="return nymPost('URL','Emin misiniz?')">
     Görünüşte aynı link/buton kalır, ama tıklanınca gizli bir <form
     method="post"> oluşturup CSRF token'ıyla submit eder. */
  window.nymPost = function (url, confirmMessage) {
    if (confirmMessage && !window.confirm(confirmMessage)) {
      return false;
    }
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    form.style.display = 'none';
    var csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = 'csrf_token';
    csrf.value = window.NYM_CSRF_TOKEN || '';
    form.appendChild(csrf);
    document.body.appendChild(form);
    form.submit();
    return false;
  };

  /* ── Tema geçişi ────────────────────────────────────────────────────
     Tercih hem localStorage'a hem çereze yazılır. Çerez sayesinde PHP
     tarafı <body data-theme> değerini sunucuda basar; böylece sayfa
     açılışında tema titremesi (flash) olmaz.                            */
  function temaUygula(tema) {
    document.body.setAttribute('data-theme', tema);

    var meta = document.querySelector('meta[name="theme-color"]');
    if (meta) meta.setAttribute('content', tema === 'acik' ? '#f0f2f5' : '#0d0d1a');

    document.querySelectorAll('.js-tema i').forEach(function (i) {
      i.className = 'fa-solid ' + (tema === 'acik' ? 'fa-moon' : 'fa-sun');
    });

    try { localStorage.setItem('nym-theme', tema); } catch (e) { /* özel mod */ }
    document.cookie = 'nym_theme=' + tema + ';path=/;max-age=31536000;samesite=Lax';
  }

  document.querySelectorAll('.js-tema').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var simdiki = document.body.getAttribute('data-theme') === 'acik' ? 'acik' : 'koyu';
      temaUygula(simdiki === 'acik' ? 'koyu' : 'acik');
    });
  });

  /* localStorage ile çerez ayrışmışsa (ör. çerez süresi dolmuş) düzelt */
  try {
    var kayitli = localStorage.getItem('nym-theme');
    var aktif = document.body.getAttribute('data-theme') === 'acik' ? 'acik' : 'koyu';
    if (kayitli && kayitli !== aktif) temaUygula(kayitli);
  } catch (e) { /* yoksay */ }

  /* ── Kenar çubuğu açılır menüleri (akordeon) ───────────────────────── */
  document.querySelectorAll('.js-nav-group').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var hedef = document.getElementById(btn.dataset.hedef);
      if (!hedef) return;
      var acilacak = !hedef.classList.contains('open');

      // Aynı anda yalnızca bir grup açık kalsın
      document.querySelectorAll('.nav-group.open').forEach(function (g) {
        if (g !== hedef) {
          g.classList.remove('open');
          var t = document.querySelector('.js-nav-group[data-hedef="' + g.id + '"]');
          if (t) t.setAttribute('aria-expanded', 'false');
        }
      });

      hedef.classList.toggle('open', acilacak);
      btn.setAttribute('aria-expanded', acilacak ? 'true' : 'false');
    });
  });

  /* ── Profil menüsü ─────────────────────────────────────────────────── */
  var profilBtn = document.querySelector('.js-profil');
  var profilMenu = document.getElementById('profilMenu');
  if (profilBtn && profilMenu) {
    profilBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      var acik = profilMenu.classList.toggle('show');
      profilBtn.setAttribute('aria-expanded', acik ? 'true' : 'false');
    });
    document.addEventListener('click', function (e) {
      if (!profilMenu.contains(e.target) && !profilBtn.contains(e.target)) {
        profilMenu.classList.remove('show');
        profilBtn.setAttribute('aria-expanded', 'false');
      }
    });
  }

  /* ── Mobil "Daha" paneli ───────────────────────────────────────────── */
  var dahaBtn = document.querySelector('.js-daha');
  var panel = document.getElementById('bnavMorePanel');
  var overlay = document.getElementById('bnavOverlay');

  function panelAc() {
    if (!panel || !overlay) return;
    panel.classList.add('open');
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
    if (dahaBtn) dahaBtn.setAttribute('aria-expanded', 'true');
  }
  function panelKapat() {
    if (!panel || !overlay) return;
    panel.classList.remove('open');
    overlay.classList.remove('open');
    document.body.style.overflow = '';
    if (dahaBtn) dahaBtn.setAttribute('aria-expanded', 'false');
  }

  if (dahaBtn) {
    dahaBtn.addEventListener('click', function () {
      panel && panel.classList.contains('open') ? panelKapat() : panelAc();
    });
  }
  if (overlay) overlay.addEventListener('click', panelKapat);
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') panelKapat();
  });

  /* ── Bildirim çubukları 3.5 sn sonra kaybolur ──────────────────────── */
  document.querySelectorAll('.flash, .alert-dismissible-auto').forEach(function (el) {
    setTimeout(function () {
      el.style.transition = 'opacity .5s, transform .5s';
      el.style.opacity = '0';
      el.style.transform = 'translateY(-8px)';
      setTimeout(function () { el.remove(); }, 500);
    }, 3500);
  });
})();
