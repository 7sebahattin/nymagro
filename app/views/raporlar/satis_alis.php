<style>
/* YENI EKLENEN HEAD STYLLERI */
/* ── Dashboard Grid ── */
    .report-grid {
      display: grid;
      grid-template-columns: repeat(10, 1fr);
      gap: 12px;
      max-width: 1200px;
      margin: 0 auto;
    }

    .report-btn {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-decoration: none;
      color: #fff;
      border-radius: 12px;
      padding: 24px;
      text-align: center;
      transition: transform 0.2s, box-shadow 0.2s, filter 0.2s;
      min-height: 160px;
      position: relative;
      overflow: hidden;
    }

    .report-btn:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 24px rgba(0,0,0,0.15);
      color: #fff;
      filter: brightness(1.1);
    }

    .report-btn:active {
      transform: translateY(0);
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .report-btn i {
      font-size: 48px;
      margin-bottom: 16px;
      opacity: 0.95;
    }

    .report-btn span {
      font-size: 16px;
      font-weight: 700;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      line-height: 1.3;
      text-shadow: 0 1px 2px rgba(0,0,0,0.2);
    }

    /* Column Spans */
    .col-span-4 { grid-column: span 4; }
    .col-span-3 { grid-column: span 3; }
    .col-span-2 { grid-column: span 2; }

    /* Custom Colors */
    .bg-blue     { background-color: var(--info); }
    .bg-brown    { background-color: var(--danger); }
    .bg-green1   { background-color: #00ba00; }
    .bg-red      { background-color: #e62e5c; }
    .bg-orange1  { background-color: #c96200; }
    .bg-gray     { background-color: var(--text); }
    .bg-green2   { background-color: #688f4b; }
    .bg-orange2  { background-color: #ff8c1a; }
    .bg-green3   { background-color: #558c46; }
    .bg-brown2   { background-color: var(--danger); }

    /* Specific Font Sizes for some blocks if needed */
    .text-lg { font-size: 18px !important; }
    .text-md { font-size: 14px !important; }

    

    
    

    
  
  
  .nav-link.active:focus { color: #4ade80; outline: none; }
</style>

<style>
/* ── Dashboard Grid ── */
    .report-grid {
      display: grid;
      grid-template-columns: repeat(10, 1fr);
      gap: 12px;
      max-width: 1200px;
      margin: 0 auto;

    .report-btn {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-decoration: none;
      color: #fff;
      border-radius: 12px;
      padding: 24px;
      text-align: center;
      transition: transform 0.2s, box-shadow 0.2s, filter 0.2s;
      min-height: 160px;
      position: relative;
      overflow: hidden;

    .report-btn:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 24px rgba(0,0,0,0.15);
      color: #fff;
      filter: brightness(1.1);

    .report-btn:active {
      transform: translateY(0);
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);

    .report-btn i {
      font-size: 48px;
      margin-bottom: 16px;
      opacity: 0.95;

    .report-btn span {
      font-size: 16px;
      font-weight: 700;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      line-height: 1.3;
      text-shadow: 0 1px 2px rgba(0,0,0,0.2);

    /* Column Spans */
    .col-span-4 { grid-column: span 4; }
    .col-span-3 { grid-column: span 3; }
    .col-span-2 { grid-column: span 2; }

    /* Custom Colors */
    .bg-blue     { background-color: var(--info); }
    .bg-brown    { background-color: var(--danger); }
    .bg-green1   { background-color: #00ba00; }
    .bg-red      { background-color: #e62e5c; }
    .bg-orange1  { background-color: #c96200; }
    .bg-gray     { background-color: var(--text); }
    .bg-green2   { background-color: #688f4b; }
    .bg-orange2  { background-color: #ff8c1a; }
    .bg-green3   { background-color: #558c46; }
    .bg-brown2   { background-color: var(--danger); }

    /* Specific Font Sizes for some blocks if needed */
    .text-lg { font-size: 18px !important; }
    .text-md { font-size: 14px !important; }

    @media (max-width: 992px) {
      .report-grid {
        grid-template-columns: repeat(6, 1fr);

      .col-span-4, .col-span-3, .col-span-2 { grid-column: span 3; }
      .bg-blue { grid-column: span 6; }
      .bg-orange2 { grid-column: span 6; }

      .report-grid { grid-template-columns: repeat(2, 1fr); }
      .col-span-4, .col-span-3, .col-span-2 { grid-column: span 2; }

      .report-grid { grid-template-columns: 1fr; }
      .col-span-4, .col-span-3, .col-span-2 { grid-column: span 1; }

  .nav-link.active:focus { color: #4ade80; outline: none; }
</style>

<style>
  .report-grid {
    display: grid;
    grid-template-columns: repeat(10, minmax(0, 1fr));
    gap: 14px;
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
  }
  .report-btn {
    min-width: 0;
    min-height: 154px;
    padding: 20px 10px;
    border-radius: 14px;
    overflow: hidden;
  }
  .report-btn i {
    font-size: clamp(30px, 4vw, 48px);
    margin-bottom: 10px;
  }
  .report-btn span {
    display: block;
    max-width: 100%;
    font-size: clamp(11px, 1.8vw, 16px);
    line-height: 1.25;
    letter-spacing: .2px;
    overflow-wrap: anywhere;
    word-break: normal;
  }
  .nav-link.active:focus { color:var(--text2); outline:none; }
  @media (max-width: 992px) {
    .report-grid { grid-template-columns: repeat(6, minmax(0, 1fr)); }
    .col-span-4 { grid-column: span 6; }
    .col-span-3,
    .col-span-2 { grid-column: span 3; }
  }
  @media (max-width: 576px) {
    .report-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
    .col-span-4,
    .col-span-3,
    .col-span-2 { grid-column: span 1; }
    .report-btn { min-height: 126px; padding: 14px 8px; border-radius: 12px; }
    .report-btn i { font-size: 28px; margin-bottom: 8px; }
    .report-btn span,
    .report-btn .text-lg,
    .report-btn .text-md { font-size: 11.5px !important; line-height: 1.22; letter-spacing: 0; }
  }
  @media (max-width: 360px) {
    .report-grid { grid-template-columns: 1fr; }
  }
</style>

<div class="report-grid">
      <!-- Satır 1 -->
      <a href="<?= BASE_URL ?>/raporlar/basit-satis" class="report-btn col-span-4 bg-blue">
        <i class="fa-solid fa-cart-shopping"></i>
        <span class="text-lg">BASİT SATIŞ RAPORU</span>
      </a>
      <a href="<?= BASE_URL ?>/raporlar/urun-alis-satis" class="report-btn col-span-2 bg-brown">
        <i class="fa-solid fa-box-archive"></i>
        <span>ÜRÜN ALIŞ-SATIŞ<br>RAPORU</span>
      </a>
      <a href="<?= BASE_URL ?>/raporlar/musteri-satis" class="report-btn col-span-2 bg-green1">
        <i class="fa-solid fa-users"></i>
        <span>MÜŞTERİ SATIŞ<br>RAPORU</span>
      </a>
      <a href="<?= BASE_URL ?>/raporlar/satis-kaybi" class="report-btn col-span-2 bg-red">
        <i class="fa-solid fa-thumbs-down"></i>
        <span>SATIŞ KAYBI</span>
      </a>

      <!-- Satır 2 -->
      <a href="<?= BASE_URL ?>/raporlar/alislar" class="report-btn col-span-2 bg-orange1">
        <i class="fa-solid fa-arrow-down-short-wide"></i>
        <span>ALIŞLAR</span>
      </a>
      <a href="<?= BASE_URL ?>/raporlar/iadeler" class="report-btn col-span-2 bg-gray">
        <i class="fa-solid fa-reply"></i>
        <span>İADELER</span>
      </a>
      <a href="<?= BASE_URL ?>/raporlar/teklifler" class="report-btn col-span-2 bg-green2">
        <i class="fa-solid fa-newspaper"></i>
        <span>TEKLİFLER</span>
      </a>
      <a href="<?= BASE_URL ?>/raporlar/6-aylik-satislar" class="report-btn col-span-4 bg-orange2">
        <i class="fa-solid fa-chart-simple"></i>
        <span class="text-lg">6 AYLIK SATIŞLAR</span>
      </a>

      <!-- Satır 3 -->
      <a href="<?= BASE_URL ?>/raporlar/stok-satis-karsilama" class="report-btn col-span-3 bg-green3">
        <span class="text-lg" style="margin-top:auto;margin-bottom:auto;">STOK-SATIŞ<br>KARŞILAMA</span>
      </a>
      <a href="<?= BASE_URL ?>/raporlar/kategori-bazli-satis" class="report-btn col-span-3 bg-brown2">
        <span class="text-lg" style="margin-top:auto;margin-bottom:auto;text-transform:none;">Kategori Bazlı Satış<br>Raporu</span>
      </a>
    </div>

<script>
(function () {
  'use strict';
  /* Sidebar accordion logic */
  document.querySelectorAll('.submenu').forEach(sub => {
    const link = document.querySelector(`[data-bs-target="#${sub.id}"]`);
    if (!link) return;
    sub.addEventListener('show.bs.collapse', () => {
      link.setAttribute('aria-expanded', 'true');
      document.querySelectorAll('.submenu.show').forEach(o => {
        if (o.id !== sub.id) bootstrap.Collapse.getInstance(o)?.hide();
      });
    });
    sub.addEventListener('hide.bs.collapse', () => link.setAttribute('aria-expanded', 'false'));
  });

  // Close profile dropdown when clicking outside
  document.addEventListener('click', function(e) {
    const profileDropdown = document.getElementById('profileDropdown');
    const userDropdownBtn = document.getElementById('userDropdownBtn');
    if (profileDropdown && userDropdownBtn && !profileDropdown.contains(e.target) && !userDropdownBtn.contains(e.target)) {
      profileDropdown.classList.remove('show');
    }
  });

})();
</script>
