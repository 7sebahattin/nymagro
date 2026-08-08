<style>
/* YENI EKLENEN HEAD STYLLERI */
/* ── Dashboard Grid ── */
    .report-grid {
      display: grid;
      grid-template-columns: repeat(12, 1fr);
      gap: 12px;
      max-width: 1400px;
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
      padding: 24px 12px;
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
      font-size: 40px;
      margin-bottom: 12px;
      opacity: 0.95;
    }

    .report-btn span {
      font-size: 15px;
      font-weight: 600;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      line-height: 1.3;
      text-shadow: 0 1px 2px rgba(0,0,0,0.2);
    }

    /* Column Spans */
    .col-span-3 { grid-column: span 3; }
    .col-span-2 { grid-column: span 2; }

    /* Custom Colors */
    .bg-r1c1 { background-color: #2a7ab9; }
    .bg-r1c2 { background-color: #094a2b; }
    .bg-r1c3 { background-color: var(--text); }
    .bg-r1c4 { background-color: #f65511; }

    .bg-r2c1 { background-color: #009000; }
    .bg-r2c2 { background-color: #ffc400; color: #fff; } /* Text is white on yellow? Wait, let's keep it white, looks fine. Or #fff */
    .bg-r2c3 { background-color: #e64a00; }
    .bg-r2c4 { background-color: #0000a6; }
    .bg-r2c5 { background-color: var(--warning); }

    .bg-r3c1 { background-color: #a60015; }
    .bg-r3c2 { background-color: #000096; }
    .bg-r3c3 { background-color: #0095d3; }
    .bg-r3c4 { background-color: #11d943; }
    .bg-r3c5 { background-color: var(--text2); }

    /* For specific items that need different layout */
    .no-icon { justify-content: center; }

    

    

    
    

    
  
  
  .nav-link.active:focus { color: #4ade80; outline: none; }
</style>

<style>
/* ── Dashboard Grid ── */
    .report-grid {
      display: grid;
      grid-template-columns: repeat(12, 1fr);
      gap: 12px;
      max-width: 1400px;
      margin: 0 auto;

    .report-btn {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-decoration: none;
      color: #fff;
      border-radius: 12px;
      padding: 24px 12px;
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
      font-size: 40px;
      margin-bottom: 12px;
      opacity: 0.95;

    .report-btn span {
      font-size: 15px;
      font-weight: 600;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      line-height: 1.3;
      text-shadow: 0 1px 2px rgba(0,0,0,0.2);

    /* Column Spans */
    .col-span-3 { grid-column: span 3; }
    .col-span-2 { grid-column: span 2; }

    /* Custom Colors */
    .bg-r1c1 { background-color: #2a7ab9; }
    .bg-r1c2 { background-color: #094a2b; }
    .bg-r1c3 { background-color: var(--text); }
    .bg-r1c4 { background-color: #f65511; }

    .bg-r2c1 { background-color: #009000; }
    .bg-r2c2 { background-color: #ffc400; color: #fff; } /* Text is white on yellow? Wait, let's keep it white, looks fine. Or #fff */
    .bg-r2c3 { background-color: #e64a00; }
    .bg-r2c4 { background-color: #0000a6; }
    .bg-r2c5 { background-color: var(--warning); }

    .bg-r3c1 { background-color: #a60015; }
    .bg-r3c2 { background-color: #000096; }
    .bg-r3c3 { background-color: #0095d3; }
    .bg-r3c4 { background-color: #11d943; }
    .bg-r3c5 { background-color: var(--text2); }

    /* For specific items that need different layout */
    .no-icon { justify-content: center; }

    @media (max-width: 1200px) {
      .report-btn span { font-size: 13px; }
      .report-btn i { font-size: 32px; }

    @media (max-width: 992px) {
      .report-grid {
        grid-template-columns: repeat(6, 1fr);

      .col-span-3 { grid-column: span 3; }
      .col-span-2 { grid-column: span 2; }
      /* Adjust spans for medium screens if needed, 
         but 6-col grid will just wrap them. */

      .report-grid { grid-template-columns: repeat(2, 1fr); }
      .col-span-3, .col-span-2 { grid-column: span 1; }

      .report-grid { grid-template-columns: 1fr; }
      .col-span-3, .col-span-2 { grid-column: span 1; }

  .nav-link.active:focus { color: #4ade80; outline: none; }
</style>

<style>
  .report-grid {
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    gap: 14px;
    width: 100%;
    max-width: 1400px;
    margin: 0 auto;
  }
  .report-btn {
    min-width: 0;
    min-height: 156px;
    padding: 20px 10px;
    border-radius: 14px;
    overflow: hidden;
    isolation: isolate;
  }
  .report-btn i {
    font-size: clamp(28px, 4vw, 42px);
    margin-bottom: 10px;
  }
  .report-btn span {
    display: block;
    max-width: 100%;
    font-size: clamp(11px, 1.8vw, 15px);
    line-height: 1.25;
    letter-spacing: .2px;
    overflow-wrap: anywhere;
    word-break: normal;
  }
  .nav-link.active:focus { color:var(--text2); outline:none; }
  @media (max-width: 992px) {
    .report-grid { grid-template-columns: repeat(6, minmax(0, 1fr)); }
    .col-span-3 { grid-column: span 3; }
    .col-span-2 { grid-column: span 2; }
  }
  @media (max-width: 576px) {
    .report-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
    .col-span-3,
    .col-span-2 { grid-column: span 1; }
    .report-btn { min-height: 126px; padding: 14px 8px; border-radius: 12px; }
    .report-btn i { font-size: 28px; margin-bottom: 8px; }
    .report-btn span { font-size: 11.5px; line-height: 1.22; letter-spacing: 0; }
  }
  @media (max-width: 360px) {
    .report-grid { grid-template-columns: 1fr; }
  }
</style>

<div class="report-grid">
      <!-- Satır 1: 4 Kutu (3 - 3 - 3 - 3) -->
      <a href="<?= BASE_URL ?>/rapor/financial/kasa-banka" class="report-btn col-span-3 bg-r1c1 no-icon">
        <span style="text-transform:uppercase;">KASA - BANKA HAREKETLERİ</span>
      </a>
      <a href="<?= BASE_URL ?>/rapor/financial/calisanlar" class="report-btn col-span-3 bg-r1c2 no-icon">
        <span style="text-transform:uppercase;">ÇALIŞANLAR</span>
      </a>
      <a href="<?= BASE_URL ?>/rapor/financial/vadesi-gecen-alacaklar" class="report-btn col-span-3 bg-r1c3">
        <i class="fa-regular fa-calendar-days"></i>
        <span>Vadesi Geçen Alacaklar</span>
      </a>
      <a href="<?= BASE_URL ?>/rapor/financial/kdv" class="report-btn col-span-3 bg-r1c4 no-icon">
        <span style="text-transform:uppercase;">KDV RAPORU</span>
      </a>

      <!-- Satır 2: 5 Kutu (2 - 3 - 2 - 3 - 2) -->
      <a href="<?= BASE_URL ?>/rapor/financial/masraflar" class="report-btn col-span-2 bg-r2c1">
        <i class="fa-solid fa-fire"></i>
        <span>MASRAFLAR</span>
      </a>
      <a href="<?= BASE_URL ?>/rapor/financial/senetler" class="report-btn col-span-3 bg-r2c2">
        <i class="fa-solid fa-table-list"></i>
        <span>SENETLER</span>
      </a>
      <a href="<?= BASE_URL ?>/rapor/financial/ba-bs" class="report-btn col-span-2 bg-r2c3">
        <i class="fa-solid fa-cart-shopping"></i>
        <span>BA-BS LİSTESİ</span>
      </a>
      <a href="<?= BASE_URL ?>/rapor/financial/gelir-gider" class="report-btn col-span-3 bg-r2c4">
        <i class="fa-solid fa-chart-column"></i>
        <span>GELİR GİDER DURUMU</span>
      </a>
      <a href="<?= BASE_URL ?>/rapor/financial/hesap-bakiyeleri" class="report-btn col-span-2 bg-r2c5">
        <i class="fa-solid fa-gauge-high"></i>
        <span>HESAP BAKİYELERİ</span>
      </a>

      <!-- Satır 3: 5 Kutu (2 - 2 - 3 - 2 - 3) -->
      <a href="<?= BASE_URL ?>/rapor/financial/cekler" class="report-btn col-span-2 bg-r3c1">
        <i class="fa-solid fa-money-bill-1"></i>
        <span>ÇEKLER</span>
      </a>
      <a href="<?= BASE_URL ?>/rapor/financial/krediler" class="report-btn col-span-2 bg-r3c2">
        <i class="fa-regular fa-credit-card"></i>
        <span>KREDİLER</span>
      </a>
      <a href="<?= BASE_URL ?>/rapor/financial/gunluk-hesap-giris-cikislari" class="report-btn col-span-3 bg-r3c3">
        <i class="fa-solid fa-right-left"></i>
        <span>GÜNLÜK HESAP<br>GİRİŞ ÇIKIŞLARI</span>
      </a>
      <a href="<?= BASE_URL ?>/rapor/financial/borc-alacak-fisleri" class="report-btn col-span-2 bg-r3c4">
        <i class="fa-solid fa-copy"></i>
        <span>BORÇ ALACAK FİŞLERİ</span>
      </a>
      <a href="<?= BASE_URL ?>/rapor/financial/kullanici-satis-tahsilat" class="report-btn col-span-3 bg-r3c5">
        <i class="fa-solid fa-users"></i>
        <span>KULLANICI SATIŞ-TAHSİLAT RAPORU</span>
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
