<style>
/* YENI EKLENEN HEAD STYLLERI */
/* ── Dashboard Grid ── */
    .report-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 16px;
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
      padding: 32px 16px;
      text-align: center;
      transition: transform 0.2s, box-shadow 0.2s, filter 0.2s;
      min-height: 200px;
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
      font-size: 56px;
      margin-bottom: 16px;
      opacity: 0.95;
    }

    .report-btn span {
      font-size: 20px;
      font-weight: 700;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      line-height: 1.3;
      text-shadow: 0 1px 2px rgba(0,0,0,0.2);
    }

    /* Column Spans */
    .col-span-2 { grid-column: span 2; }
    .col-span-1 { grid-column: span 1; }

    /* Custom Colors */
    .bg-urunler { background-color: #ba3805; }
    .bg-stokhareketleri { background-color: #0c9191; }
    .bg-depodurumu { background-color: #7a3d05; }
    .bg-stoksatiskarsilama { background-color: #fd8e2a; }
    .bg-hareketgormeyen { background-color: #2fb510; }

    /* Specific Font Sizes */
    .text-md { font-size: 16px !important; }

    

    
    

    
  
  
  .nav-link.active:focus { color: #4ade80; outline: none; }
</style>

<style>
/* ── Dashboard Grid ── */
    .report-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 16px;
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
      padding: 32px 16px;
      text-align: center;
      transition: transform 0.2s, box-shadow 0.2s, filter 0.2s;
      min-height: 200px;
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
      font-size: 56px;
      margin-bottom: 16px;
      opacity: 0.95;

    .report-btn span {
      font-size: 20px;
      font-weight: 700;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      line-height: 1.3;
      text-shadow: 0 1px 2px rgba(0,0,0,0.2);

    /* Column Spans */
    .col-span-2 { grid-column: span 2; }
    .col-span-1 { grid-column: span 1; }

    /* Custom Colors */
    .bg-urunler { background-color: #ba3805; }
    .bg-stokhareketleri { background-color: #0c9191; }
    .bg-depodurumu { background-color: #7a3d05; }
    .bg-stoksatiskarsilama { background-color: #fd8e2a; }
    .bg-hareketgormeyen { background-color: #2fb510; }

    /* Specific Font Sizes */
    .text-md { font-size: 16px !important; }

    @media (max-width: 992px) {
      .report-grid {
        grid-template-columns: repeat(2, 1fr);

      .col-span-2 { grid-column: span 2; }
      .col-span-1 { grid-column: span 1; }

      .report-btn { min-height: 160px; padding: 20px; }
      .report-btn i { font-size: 40px; }
      .report-btn span { font-size: 16px; }

      .report-grid { grid-template-columns: 1fr; }
      .col-span-2, .col-span-1 { grid-column: span 1; }

  .nav-link.active:focus { color: #4ade80; outline: none; }
</style>

<style>
  .report-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
  }
  .report-btn {
    min-width: 0;
    min-height: 178px;
    padding: 24px 12px;
    border-radius: 14px;
    overflow: hidden;
  }
  .report-btn i {
    font-size: clamp(34px, 5vw, 56px);
    margin-bottom: 12px;
  }
  .report-btn span {
    display: block;
    max-width: 100%;
    font-size: clamp(12px, 2vw, 20px);
    line-height: 1.25;
    letter-spacing: .2px;
    overflow-wrap: anywhere;
    word-break: normal;
  }
  .nav-link.active:focus { color:#a9d4be; outline:none; }
  @media (max-width: 768px) {
    .report-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
    .col-span-2,
    .col-span-1 { grid-column: span 1; }
    .report-btn { min-height: 132px; padding: 16px 8px; border-radius: 12px; }
    .report-btn i { font-size: 30px; margin-bottom: 8px; }
    .report-btn span,
    .report-btn .text-md { font-size: 12px !important; line-height: 1.22; letter-spacing: 0; }
  }
  @media (max-width: 360px) {
    .report-grid { grid-template-columns: 1fr; }
  }
</style>

<div class="report-grid">
      <!-- Satır 1: 2 Kutu (span 2 - span 2) -->
      <a href="<?= BASE_URL ?>/rapor/stok/urunler" class="report-btn col-span-2 bg-urunler">
        <i class="fa-solid fa-tags"></i>
        <span>ÜRÜNLER</span>
      </a>
      <a href="<?= BASE_URL ?>/rapor/stok/hareketler" class="report-btn col-span-2 bg-stokhareketleri">
        <i class="fa-solid fa-truck"></i>
        <span>STOK HAREKETLERİ</span>
      </a>

      <!-- Satır 2: 3 Kutu (span 2 - span 1 - span 1) -->
      <a href="<?= BASE_URL ?>/rapor/stok/depo-durumu" class="report-btn col-span-2 bg-depodurumu">
        <i class="fa-solid fa-chart-column"></i>
        <span>DEPO DURUMU</span>
      </a>
      <a href="<?= BASE_URL ?>/rapor/stok/stok-satis-karsilama" class="report-btn col-span-1 bg-stoksatiskarsilama">
        <span class="text-md" style="margin-top:auto;margin-bottom:auto;">STOK-SATIŞ<br>KARŞILAMA</span>
      </a>
      <a href="<?= BASE_URL ?>/rapor/stok/hareket-gormeyen-urunler" class="report-btn col-span-1 bg-hareketgormeyen">
        <i class="fa-regular fa-face-frown"></i>
        <span class="text-md">HAREKET<br>GÖRMEYEN<br>ÜRÜNLER</span>
      </a>
    </div>

<script>
(function () {
  'use strict';
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
