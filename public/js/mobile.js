document.addEventListener('DOMContentLoaded', function() {
  const sidebar = document.querySelector('.sidebar');
  const body = document.body;
  
  if (!sidebar) return; // Prevent errors on pages without sidebar

  // Create backdrop
  const backdrop = document.createElement('div');
  backdrop.className = 'sidebar-backdrop';
  body.appendChild(backdrop);
  
  // Functions to open/close mobile sidebar
  function openMobileSidebar() {
    sidebar.classList.add('mobile-open');
    backdrop.classList.add('show');
    body.style.overflow = 'hidden'; // Prevent background scrolling
  }
  
  function closeMobileSidebar() {
    sidebar.classList.remove('mobile-open');
    backdrop.classList.remove('show');
    body.style.overflow = '';
  }
  
  backdrop.addEventListener('click', closeMobileSidebar);
  
  // Bind Bottom Bar Buttons
  const btnMenu = document.getElementById('mNavMenu');
  if (btnMenu) {
    btnMenu.addEventListener('click', function(e) {
      e.preventDefault();
      openMobileSidebar();
    });
  }
  
  const btnNakit = document.getElementById('mNavNakit');
  if (btnNakit) {
    btnNakit.addEventListener('click', function(e) {
      e.preventDefault();
      openMobileSidebar();
      // Expand Nakit Yönetimi submenu
      const nakitToggle = document.querySelector('[data-bs-target="#subNakit"]');
      const nakitSub = document.getElementById('subNakit');
      if (nakitToggle && nakitSub && !nakitSub.classList.contains('show')) {
        nakitToggle.click();
      }
      // Scroll sidebar to Nakit after slight delay for animation
      setTimeout(() => { if (nakitToggle) nakitToggle.scrollIntoView({ behavior: 'smooth', block: 'center' }); }, 350);
    });
  }

  const btnRaporlar = document.getElementById('mNavRaporlar');
  if (btnRaporlar) {
    btnRaporlar.addEventListener('click', function(e) {
      e.preventDefault();
      openMobileSidebar();
      // Expand Raporlar submenu
      const raporlarToggle = document.querySelector('[data-bs-target="#subRaporlar"]');
      const raporlarSub = document.getElementById('subRaporlar');
      if (raporlarToggle && raporlarSub && !raporlarSub.classList.contains('show')) {
        raporlarToggle.click();
      }
      // Scroll sidebar to Raporlar after slight delay for animation
      setTimeout(() => { if (raporlarToggle) raporlarToggle.scrollIntoView({ behavior: 'smooth', block: 'center' }); }, 350);
    });
  }
  
  // Active state for bottom bar (optional enhancement)
  // Highlights the bottom bar icon if the current page belongs to that section
  const currentUrl = window.location.href;
  if (currentUrl.includes('/hesap') || currentUrl.includes('/nakit') || currentUrl.includes('/masraf') || currentUrl.includes('/kredi') || currentUrl.includes('/demirbas') || currentUrl.includes('/proje')) {
    if(btnNakit) btnNakit.classList.add('active');
  } else if (currentUrl.includes('rapor')) {
    if(btnRaporlar) btnRaporlar.classList.add('active');
  } else if (currentUrl.includes('index') || currentUrl.endsWith('/Muhasebe/')) {
    const btnHome = document.getElementById('mNavHome');
    if(btnHome) btnHome.classList.add('active');
  }
});
