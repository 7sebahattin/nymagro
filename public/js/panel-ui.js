/* ==========================================================================
   Nymagro Ticaret Paneli — Arayüz davranışları
   Kütüphane gerektirmez (Bootstrap yalnızca dropdown/modal için ayrıca yüklü).
   ========================================================================== */
(function () {
  'use strict';

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
