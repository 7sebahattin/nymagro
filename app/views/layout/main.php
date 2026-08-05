<?php
/**
 * Ana Layout (main.php)
 * --------------------------------------------------------
 * Header + Sidebar + Topbar + $content + Footer
 * Tüm sayfalar bu layout üzerinden render edilir.
 *
 * Beklenen değişkenler:
 *   $pageTitle   (string) — sayfa başlığı
 *   $activeMenu  (string) — aktif menü anahtarı: dashboard|musteriler|tedarikciler|personel|urunler|satislar|alislar|teklifler|nakit|ayarlar|raporlar|fihrist
 *   $content     (string) — Controller::view() tarafından doldurulur
 */

$activeMenu = $activeMenu ?? '';
$pageTitle  = $pageTitle  ?? APP_NAME;
$currentUrl = trim((string)($_GET['url'] ?? ''), '/');
$currentSegments = $currentUrl !== '' ? explode('/', $currentUrl) : [];
$currentSection = strtolower($currentSegments[0] ?? 'dashboard');
$currentAction = strtolower(str_replace('-', '_', $currentSegments[1] ?? ''));

if ($activeMenu === '') {
    $activeMenu = [
        'musteri' => 'musteriler',
        'tedarikci' => 'tedarikciler',
        'personel' => 'personel',
        'urun' => 'urunler',
        'depo' => 'depolar',
        'satis' => 'satislar',
        'alis' => 'alislar',
        'teklif' => 'teklifler',
        'hesap' => 'hesaplar',
        'masraf' => 'masraflar',
        'nakit' => 'nakit',
        'kredi' => 'krediler',
        'demirbas' => 'demirbaslar',
        'proje' => 'projeler',
        'cek' => 'cek_portfoyu',
        'senet' => 'senet_portfoyu',
        'tanim' => 'tanimlar',
        'rapor' => 'raporlar',
        'raporlar' => 'raporlar',
        'fihrist' => 'fihrist',
        'companies' => 'companies',
        'periods' => 'companies',
        'dashboard' => 'dashboard',
    ][$currentSection] ?? $currentSection;
}

/** Aktif menü helper */
$isActive = function(string $key) use ($activeMenu): string {
    return $activeMenu === $key ? ' active' : '';
};

$isSubActive = function(string $menuKey, string $section = '', string $action = '') use ($activeMenu, $currentSection, $currentAction): string {
    if ($activeMenu === $menuKey) {
        return ' active';
    }
    if ($section !== '' && $currentSection === $section && ($action === '' || $currentAction === $action)) {
        return ' active';
    }
    return '';
};

$isUrunlerOpen = in_array($activeMenu, ['urunler', 'depolar', 'urun_varyantlari'], true) || in_array($currentSection, ['urun', 'depo'], true);
$isNakitOpen = in_array($activeMenu, ['nakit', 'hesaplar', 'masraflar', 'krediler', 'demirbaslar', 'projeler', 'gelen_efaturalar', 'cek_portfoyu', 'senet_portfoyu'], true)
    || in_array($currentSection, ['nakit', 'hesap', 'masraf', 'kredi', 'demirbas', 'proje', 'cek', 'senet'], true);
$isAyarlarOpen = in_array($activeMenu, ['ayarlar', 'tanimlar', 'site'], true) || in_array($currentSection, ['tanim', 'ayarlar', 'site'], true);
$isRaporlarOpen = $activeMenu === 'raporlar' || in_array($currentSection, ['rapor', 'raporlar'], true);
$activeCompany = class_exists('TenantContext') ? TenantContext::activeCompany() : null;
$activePeriod = class_exists('TenantContext') ? TenantContext::activePeriod() : null;
$activeCompanySettings = class_exists('TenantContext') ? TenantContext::activeCompanySettings() : [];
$periodStatusLabels = ['open' => 'Açık', 'locked' => 'Kilitli', 'closed' => 'Kapalı', 'archived' => 'Arşiv'];
$periodStatusClass = ['open' => 'success', 'locked' => 'warning', 'closed' => 'secondary', 'archived' => 'dark'];
$tenantTheme = $activeCompanySettings['theme_color'] ?? 'violet';
$tenantPalettes = [
    'emerald' => ['accent' => '#1e8c55', 'soft' => 'rgba(30,140,85,.16)', 'text' => '#a9d4be'],
    'blue' => ['accent' => '#3b82f6', 'soft' => 'rgba(59,130,246,.16)', 'text' => '#93c5fd'],
    'violet' => ['accent' => '#1e8c55', 'soft' => 'rgba(30,140,85,.16)', 'text' => '#a9d4be'],
    'amber' => ['accent' => '#f59e0b', 'soft' => 'rgba(245,158,11,.16)', 'text' => '#fcd34d'],
    'rose' => ['accent' => '#f43f5e', 'soft' => 'rgba(244,63,94,.16)', 'text' => '#fda4af'],
    'cyan' => ['accent' => '#06b6d4', 'soft' => 'rgba(6,182,212,.16)', 'text' => '#67e8f9'],
    'slate' => ['accent' => '#94a3b8', 'soft' => 'rgba(148,163,184,.16)', 'text' => '#cbd5e1'],
];
$tenantPalette = $tenantPalettes[$tenantTheme] ?? $tenantPalettes['violet'];
$currentUserName = class_exists('AuthGuard') && AuthGuard::isLoggedIn() ? AuthGuard::userName() : 'Misafir';
$currentUserRole = class_exists('AuthGuard') && AuthGuard::isLoggedIn() ? AuthGuard::userRole() : 'guest';
$currentUserAvatar = (string)($_SESSION['user_avatar_path'] ?? '');
$roleLabels = [
    'super_admin' => 'Süper Yönetici',
    'admin' => 'Yönetici',
    'accountant' => 'Muhasebe',
    'user' => 'Kullanıcı',
    'guest' => 'Misafir',
];
$currentUserRoleLabel = $roleLabels[$currentUserRole] ?? 'Kullanıcı';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <!-- Panel sayfaları arama motorlarında indekslenmemeli -->
  <meta name="robots" content="noindex, nofollow, noarchive" />
  <meta name="googlebot" content="noindex, nofollow" />
  <title><?= htmlspecialchars($pageTitle) ?> — <?= htmlspecialchars(APP_NAME) ?></title>
  <link rel="icon" href="<?= htmlspecialchars(BASE_URL) ?>/favicon.ico?v=20260501" sizes="any">
  <link rel="icon" type="image/png" sizes="32x32" href="<?= htmlspecialchars(BASE_URL) ?>/favicon-32x32.png?v=20260501">
  <link rel="icon" type="image/png" sizes="16x16" href="<?= htmlspecialchars(BASE_URL) ?>/favicon-16x16.png?v=20260501">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= htmlspecialchars(BASE_URL) ?>/apple-touch-icon.png?v=20260501">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', system-ui, sans-serif; background: #f1f5f9; display: flex; min-height: 100vh; }

    /* â”€â”€ Sidebar â”€â”€ */
    .sidebar { width: 250px; min-height: 100vh; background: #1e293b; display: flex; flex-direction: column; flex-shrink: 0; box-shadow: 4px 0 20px rgba(0,0,0,.35); position: fixed; top: 0; left: 0; z-index: 200; }
    .sidebar__brand { display: flex; align-items: center; gap: 10px; padding: 22px 20px 18px; border-bottom: 1px solid rgba(255,255,255,.06); }
    .sidebar__brand-icon { width: 36px; height: 36px; background: linear-gradient(135deg,#0d623a,#d97a0c); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 16px; color: #fff; flex-shrink: 0; box-shadow:0 8px 20px rgba(13,98,58,.32); }
    .sidebar__brand-text { font-size: 15px; font-weight: 700; color: #f1f5f9; }
    .sidebar__brand-sub  { font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: .8px; }
    .sidebar__nav { flex: 1; overflow-y: auto; padding: 10px 0; scrollbar-width: thin; scrollbar-color: #334155 transparent; }
    .sidebar__nav::-webkit-scrollbar { width: 4px; }
    .sidebar__nav::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
    .nav-item { list-style: none; }
    .nav-link { display: flex; align-items: center; gap: 12px; padding: 11px 20px; color: #94a3b8; text-decoration: none; font-size: 13.5px; font-weight: 500; transition: background .18s, color .18s; cursor: pointer; }
    .nav-link:hover { background: #334155; color: #e2e8f0; }
    .nav-link.active { background: rgba(13,98,58,.18); color: #a9d4be; border-right: 3px solid #1e8c55; }
    .nav-link.active .nav-icon { color: #a9d4be; }
    .nav-icon { width: 18px; text-align: center; font-size: 14px; color: #64748b; flex-shrink: 0; transition: color .18s; }
    .nav-link:hover .nav-icon { color: #94a3b8; }
    .nav-label { flex: 1; line-height: 1; text-transform: uppercase; font-size: 11px; font-weight: 700; letter-spacing: 0.5px; }

    .badge-new { background: #1e8c55; color: #fff; font-size: 9.5px; font-weight: 700; padding: 2px 7px; border-radius: 20px; }
    .nav-toggle { font-size: 11px; color: #475569; transition: transform .28s ease, color .18s; }
    .nav-link[aria-expanded="true"] .nav-toggle { transform: rotate(45deg); color: #94a3b8; }
    .submenu { background: #162032; list-style: none; }
    .submenu-link { display: flex; align-items: center; gap: 10px; padding: 9px 20px 9px 46px; color: #64748b; text-decoration: none; font-size: 12.5px; transition: background .15s, color .15s; }
    .submenu-link::before { content: ''; width: 5px; height: 5px; border-radius: 50%; background: #334155; flex-shrink: 0; transition: background .15s; }
    .submenu-link:hover { background: #1e293b; color: #cbd5e1; }
    .submenu-link:hover::before { background: #1e8c55; }
    .submenu.show { border-left: 3px solid rgba(30,140,85,.38); }
    .submenu-link.active { color: #a9d4be; font-weight: 700; background: rgba(13,98,58,.20); }
    .submenu-link.active::before { background: #1e8c55; }
    .submenu-link.site-management-btn {
      margin: 6px 12px 8px 36px;
      padding: 9px 12px;
      border-radius: 8px;
      background: linear-gradient(135deg,#0d623a,#d97a0c);
      color: #fff;
      font-weight: 800;
      box-shadow: 0 8px 18px rgba(13,98,58,.22);
    }
    .submenu-link.site-management-btn::before { background: #fff; }
    .submenu-link.site-management-btn:hover,
    .submenu-link.site-management-btn.active {
      color: #fff;
      background: linear-gradient(135deg,#0d623a,#a85c07);
    }
    .submenu-link.site-management-btn.active::before { background:#fff; }
    .nav-divider { height: 1px; background: rgba(255,255,255,.05); margin: 6px 16px; }
    .sidebar__footer { padding: 14px 20px; border-top: 1px solid rgba(255,255,255,.06); display: flex; align-items: center; gap: 10px; }
    .sidebar__avatar { width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg,#6366f1,#1e8c55); display: flex; align-items: center; justify-content: center; font-size: 13px; color: #fff; font-weight: 600; flex-shrink: 0; }
    .sidebar__user-name { font-size: 12.5px; font-weight: 600; color: #e2e8f0; }
    .sidebar__user-role { font-size: 10.5px; color: #475569; }
    .tenant-panel { padding: 9px 12px; border-top: 1px solid rgba(255,255,255,.08); background: rgba(15,23,42,.58); }
    .tenant-panel__compact { display:flex; align-items:center; gap:10px; min-width:0; }
    .tenant-panel__mark { width:4px; align-self:stretch; border-radius:999px; background: var(--tenant-accent); box-shadow:0 0 14px var(--tenant-accent-soft); }
    .tenant-panel__text { min-width:0; flex:1; }
    .tenant-panel__company { font-size: 13px; line-height: 1.2; color: #f8fafc; font-weight: 800; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .tenant-panel__period { display: flex; align-items: center; gap: 7px; color: #cbd5e1; font-size: 11.5px; line-height:1.2; margin-top:3px; min-width:0; }
    .tenant-panel__period-name { white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .tenant-panel__status { font-size: 10px; border-radius: 999px; padding: 2px 7px; font-weight: 800; }
    .tenant-panel__status.success { background: rgba(13,98,58,.20); color: #a9d4be; }
    .tenant-panel__status.warning { background: rgba(245,158,11,.18); color: #fcd34d; }
    .tenant-panel__status.secondary { background: rgba(148,163,184,.18); color: #cbd5e1; }
    .tenant-panel__status.dark { background: rgba(15,23,42,.8); color: #94a3b8; }
    .tenant-panel__menu-btn { width:32px; height:32px; display:flex; align-items:center; justify-content:center; border:1px solid rgba(148,163,184,.28); background:var(--tenant-accent-soft); color:var(--tenant-text); border-radius:8px; flex-shrink:0; }
    .tenant-panel__menu-btn:hover, .tenant-panel__menu-btn[aria-expanded="true"] { background:var(--tenant-accent); border-color:var(--tenant-accent); color:#0f172a; }
    .tenant-panel__dropdown { min-width:190px; border-radius:8px; border:1px solid #e2e8f0; box-shadow:0 10px 28px rgba(0,0,0,.18); padding:6px; }
    .tenant-panel__dropdown .dropdown-item { border-radius:6px; font-size:12.5px; font-weight:600; padding:8px 10px; display:flex; align-items:center; gap:8px; }
    .tenant-panel__dropdown .dropdown-item i { width:14px; color:#64748b; }

    /* â”€â”€ Layout â”€â”€ */
    .page-wrapper { margin-left: 250px; flex: 1; display: flex; flex-direction: column; min-height: 100vh; }

    /* â”€â”€ Topbar â”€â”€ */
    .topbar { background: #fff; border-bottom: 1px solid #e2e8f0; padding: 0 24px; height: 56px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; box-shadow: 0 1px 6px rgba(0,0,0,.06); }
    .topbar__left { display: flex; align-items: center; }
    .topbar__right { display: flex; align-items: center; gap: 14px; }
    .topbar__avatar-btn { display: flex; align-items: center; gap: 8px; background: none; border: none; cursor: pointer; padding: 4px 8px; border-radius: 8px; transition: background .15s; }
    .topbar__avatar-btn:hover { background: #f1f5f9; }
    .topbar__avatar { width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg,#6366f1,#1e8c55); display: flex; align-items: center; justify-content: center; font-size: 13px; color: #fff; font-weight: 600; }
    .topbar__uname { font-size: 13px; font-weight: 600; color: #1e293b; }
    .notif-btn { position: relative; background: none; border: none; cursor: pointer; color: #64748b; font-size: 16px; padding: 6px; border-radius: 8px; transition: background .15s, color .15s; }
    .notif-btn:hover { background: #f1f5f9; color: #1e293b; }
    .notif-badge { position: absolute; top: 2px; right: 2px; width: 16px; height: 16px; background: #ef4444; border-radius: 50%; font-size: 9px; font-weight: 700; color: #fff; display: flex; align-items: center; justify-content: center; }

    /* â”€â”€ Main â”€â”€ */
    .main-content { flex: 1; padding: 24px; }

    /* â”€â”€ Profil Butonu â”€â”€ */
    .topbar__profile-btn {
      display: flex; align-items: center; gap: 9px;
      background: #1e293b; border: none; cursor: pointer;
      padding: 5px 12px 5px 5px; border-radius: 8px;
      transition: background .15s;
    }
    .topbar__profile-btn:hover { background: #334155; }
    .topbar__profile-avatar {
      width: 32px; height: 32px; border-radius: 7px;
      background: linear-gradient(135deg,#6366f1,#1e8c55);
      display: flex; align-items: center; justify-content: center;
      font-size: 14px; color: #fff; flex-shrink: 0;
      overflow: hidden;
    }
    .topbar__profile-avatar img,
    .pd-header-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .topbar__profile-name { font-size: 13px; font-weight: 600; color: #e2e8f0; }
    .topbar__profile-role { font-size: 10.5px; color: #64748b; }
    .topbar__profile-caret { font-size: 10px; color: #64748b; margin-left: 2px; transition: transform .2s; }
    .topbar__profile-btn[aria-expanded="true"] .topbar__profile-caret { transform: rotate(180deg); }

    /* â”€â”€ Profil Dropdown â”€â”€ */
    .profile-dropdown {
      position: absolute; top: calc(100% + 8px); right: 0;
      width: 210px; background: #fff; border-radius: 10px;
      box-shadow: 0 8px 30px rgba(0,0,0,.14), 0 2px 8px rgba(0,0,0,.08);
      display: none; flex-direction: column; z-index: 999;
      overflow: hidden; border: 1px solid #e2e8f0;
    }
    .profile-dropdown.show { display: flex !important; }
    .pd-header {
      padding: 14px 16px 10px;
      background: linear-gradient(135deg,#1e293b,#2c3e6b);
      display: flex; align-items: center; gap: 10px;
    }
    .pd-header-avatar {
      width: 38px; height: 38px; border-radius: 8px;
      background: linear-gradient(135deg,#6366f1,#1e8c55);
      display: flex; align-items: center; justify-content: center;
      font-size: 16px; color: #fff; flex-shrink: 0;
      overflow: hidden;
    }
    .pd-header-name { font-size: 13px; font-weight: 700; color: #f1f5f9; }
    .pd-header-role { font-size: 10.5px; color: #94a3b8; }
    .pd-divider { height: 1px; background: #f1f5f9; margin: 0; }
    .pd-item {
      display: flex; align-items: center; gap: 11px;
      padding: 11px 16px; text-decoration: none;
      font-size: 13px; font-weight: 500; color: #374151;
      transition: background .12s, color .12s;
      border-left: 3px solid transparent;
    }
    .pd-item:hover { background: #f8fafc; }
    .pd-icon {
      width: 30px; height: 30px; border-radius: 7px;
      display: flex; align-items: center; justify-content: center;
      font-size: 12px; flex-shrink: 0;
    }
    .pd-item.pd-hesabim .pd-icon  { background: #e6f2e5; color: #0d623a; }
    .pd-item.pd-hesabim:hover      { border-left-color: #0d623a; color: #0d623a; }
    .pd-item.pd-sifre .pd-icon     { background: #dbeafe; color: #2563eb; }
    .pd-item.pd-sifre:hover        { border-left-color: #2563eb; color: #2563eb; }
    .pd-item.pd-cikis .pd-icon     { background: #fee2e2; color: #dc2626; }
    .pd-item.pd-cikis:hover        { border-left-color: #dc2626; color: #dc2626; }
    .pd-item.pd-cikis              { border-top: 1px solid #f1f5f9; }

    .nav-link:focus { color: #94a3b8; outline: none; }
    .nav-link.active:focus { color: #a9d4be; outline: none; }

    @media (max-width: 768px) {
      .sidebar { width: 200px; } .page-wrapper { margin-left: 200px; }
    }
    @media (max-width: 576px) {
      .sidebar { display: none; } .page-wrapper { margin-left: 0; }
    }
  </style>
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/mobile.css" />
</head>
<body>

<!-- â•â•â• SIDEBAR â•â•â• -->
<aside class="sidebar">
  <div class="sidebar__brand">
    <div class="sidebar__brand-icon"><i class="fa-solid fa-chart-pie"></i></div>
    <div>
      <div class="sidebar__brand-text"><?= htmlspecialchars(APP_NAME) ?></div>
      <div class="sidebar__brand-sub">Ticaret Paneli</div>
    </div>
  </div>

  <nav class="sidebar__nav">
    <ul class="list-unstyled mb-0" id="mainMenu">

      <li class="nav-item">
        <a href="<?= BASE_URL ?>/dashboard" class="nav-link<?= $isActive('dashboard') ?>">
          <i class="fa-solid fa-home nav-icon"></i>
          <span class="nav-label">Ana Sayfa</span>
        </a>
      </li>
      <li class="nav-divider"></li>

      <li class="nav-item">
        <a href="<?= BASE_URL ?>/musteri" class="nav-link<?= $isActive('musteriler') ?>">
          <i class="fa-solid fa-users nav-icon"></i>
          <span class="nav-label">Müşteriler</span>
        </a>
      </li>

      <li class="nav-item">
        <a href="<?= BASE_URL ?>/tedarikci" class="nav-link<?= $isActive('tedarikciler') ?>">
          <i class="fa-solid fa-industry nav-icon"></i>
          <span class="nav-label">Tedarikçiler</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="<?= BASE_URL ?>/personel" class="nav-link<?= $isActive('personel') ?>">
          <i class="fa-solid fa-user-tie nav-icon"></i>
          <span class="nav-label">Personel</span>
        </a>
      </li>
      <li class="nav-divider"></li>
      
      <li class="nav-item">
        <a href="#" class="nav-link <?= $isUrunlerOpen ? 'active' : '' ?>" data-bs-toggle="collapse" data-bs-target="#subUrunler" aria-expanded="<?= $isUrunlerOpen ? 'true' : 'false' ?>">
          <i class="fa-solid fa-tags nav-icon"></i>
          <span class="nav-label">Ürünler</span>
          <i class="fa-solid fa-plus nav-toggle"></i>
        </a>
        <ul class="submenu collapse <?= $isUrunlerOpen ? 'show' : '' ?>" id="subUrunler">
          <li><a href="<?= BASE_URL ?>/urun" class="submenu-link <?= $activeMenu === 'urunler' ? 'active' : '' ?>">Ürün / Hizmet Tanımları</a></li>
          <li><a href="<?= BASE_URL ?>/depo" class="submenu-link <?= $activeMenu === 'depolar' ? 'active' : '' ?>">Depolar</a></li>
          <li><a href="<?= BASE_URL ?>/urun/varyantlar" class="submenu-link <?= $activeMenu === 'urun_varyantlari' ? 'active' : '' ?>">Ürün Varyantları</a></li>
        </ul>
      </li>

      <li class="nav-item">
        <a href="<?= BASE_URL ?>/satis" class="nav-link<?= $isActive('satislar') ?>">
          <i class="fa-solid fa-shopping-cart nav-icon"></i>
          <span class="nav-label">Satışlar</span>
        </a>
      </li>

      <li class="nav-item">
        <a href="<?= BASE_URL ?>/alis" class="nav-link<?= $isActive('alislar') ?>">
          <i class="fa-solid fa-truck nav-icon"></i>
          <span class="nav-label">Alışlar</span>
        </a>
      </li>

      <li class="nav-item">
        <a href="<?= BASE_URL ?>/teklif" class="nav-link<?= $isActive('teklifler') ?>">
          <i class="fa-solid fa-folder-open nav-icon"></i>
          <span class="nav-label">Teklifler</span>
        </a>
      </li>
      <li class="nav-divider"></li>

      <li class="nav-item">
        <a href="#" class="nav-link <?= $isNakitOpen ? 'active' : '' ?>" data-bs-toggle="collapse" data-bs-target="#subNakit" aria-expanded="<?= $isNakitOpen ? 'true' : 'false' ?>">
          <i class="fa-solid fa-lira-sign nav-icon"></i>
          <span class="nav-label">Nakit Yönetimi</span>
          <i class="fa-solid fa-plus nav-toggle"></i>
        </a>
        <ul class="submenu collapse <?= $isNakitOpen ? 'show' : '' ?>" id="subNakit">
          <li><a href="<?= BASE_URL ?>/hesap" class="submenu-link<?= $isSubActive('hesaplar', 'hesap') ?>">Hesaplarım</a></li>
          <li><a href="<?= BASE_URL ?>/masraf" class="submenu-link<?= $isSubActive('masraflar', 'masraf') ?>">Masraflar</a></li>
          <li><a href="<?= BASE_URL ?>/nakit/gelen-e-faturalar" class="submenu-link<?= $currentSection === 'nakit' && $currentAction === 'gelen_e_faturalar' ? ' active' : '' ?>">Gelen E-Faturalar</a></li>
          <li><a href="<?= BASE_URL ?>/kredi" class="submenu-link<?= $isSubActive('krediler', 'kredi') ?>">Krediler</a></li>
          <li><a href="<?= BASE_URL ?>/demirbas" class="submenu-link<?= $isSubActive('demirbaslar', 'demirbas') ?>">Demirbaşlar</a></li>
          <li><a href="<?= BASE_URL ?>/proje" class="submenu-link<?= $isSubActive('projeler', 'proje') ?>">Projeler</a></li>
          <li><a href="<?= BASE_URL ?>/cek" class="submenu-link<?= $isSubActive('cek_portfoyu', 'cek') ?>">Çek Portföyü</a></li>
          <li><a href="<?= BASE_URL ?>/senet" class="submenu-link<?= $isSubActive('senet_portfoyu', 'senet') ?>">Senet Portföyü</a></li>
        </ul>
      </li>
      <li class="nav-divider"></li>

      <li class="nav-item">
        <a href="#" class="nav-link <?= $isAyarlarOpen ? 'active' : '' ?>" data-bs-toggle="collapse" data-bs-target="#subAyarlar" aria-expanded="<?= $isAyarlarOpen ? 'true' : 'false' ?>">
          <i class="fa-solid fa-gear nav-icon"></i>
          <span class="nav-label">Ayarlar</span>
          <i class="fa-solid fa-plus nav-toggle"></i>
        </a>
        <ul class="submenu collapse <?= $isAyarlarOpen ? 'show' : '' ?>" id="subAyarlar">
          <li><a href="<?= BASE_URL ?>/tanim" class="submenu-link<?= $isSubActive('tanimlar', 'tanim') ?>">Tanımlar</a></li>
          <li><a href="<?= BASE_URL ?>/site" class="submenu-link site-management-btn<?= $currentSection === 'site' ? ' active' : '' ?>">Site Yönetimi</a></li>
        </ul>
      </li>

      <li class="nav-item">
        <a href="#" class="nav-link <?= $isRaporlarOpen ? 'active' : '' ?>" data-bs-toggle="collapse" data-bs-target="#subRaporlar" aria-expanded="<?= $isRaporlarOpen ? 'true' : 'false' ?>">
          <i class="fa-solid fa-chart-column nav-icon"></i>
          <span class="nav-label">Raporlar</span>
          <i class="fa-solid fa-plus nav-toggle"></i>
        </a>
        <ul class="submenu collapse <?= $isRaporlarOpen ? 'show' : '' ?>" id="subRaporlar">
          <li><a href="<?= BASE_URL ?>/rapor/satis_alis" class="submenu-link<?= $currentSection === 'rapor' && $currentAction === 'satis_alis' ? ' active' : '' ?>">Satışlar - Alışlar</a></li>
          <li><a href="<?= BASE_URL ?>/rapor/finansal" class="submenu-link<?= $currentSection === 'rapor' && ($currentAction === 'finansal' || $currentAction === 'financial') ? ' active' : '' ?>">Finansal Raporlar</a></li>
          <li><a href="<?= BASE_URL ?>/rapor/stok" class="submenu-link<?= $currentSection === 'rapor' && $currentAction === 'stok' ? ' active' : '' ?>">Stok Raporları</a></li>
          <li><a href="<?= BASE_URL ?>/rapor/musteri" class="submenu-link<?= $currentSection === 'rapor' && in_array($currentAction, ['musteri', 'musteri_listesi'], true) ? ' active' : '' ?>">Müşteri Listesi</a></li>
        </ul>
      </li>

      <li class="nav-item">
        <a href="<?= BASE_URL ?>/fihrist" class="nav-link<?= $isActive('fihrist') ?>">
          <i class="fa-solid fa-address-book nav-icon"></i>
          <span class="nav-label">Fihrist</span>
        </a>
      </li>

    </ul>
  </nav>

  <div class="tenant-panel" style="--tenant-accent: <?= htmlspecialchars($tenantPalette['accent']) ?>; --tenant-accent-soft: <?= htmlspecialchars($tenantPalette['soft']) ?>; --tenant-text: <?= htmlspecialchars($tenantPalette['text']) ?>;">
    <div class="tenant-panel__compact">
      <div class="tenant-panel__mark"></div>
      <div class="tenant-panel__text">
        <div class="tenant-panel__company" title="<?= htmlspecialchars($activeCompany['company_name'] ?? 'Şirket seçilmedi') ?>">
          <?= htmlspecialchars($activeCompany['company_name'] ?? 'Şirket seçilmedi') ?>
        </div>
        <div class="tenant-panel__period">
          <span class="tenant-panel__period-name"><?= htmlspecialchars(($activePeriod['period_name'] ?? 'Dönem seçilmedi') . (!empty($activePeriod['period_name']) ? ' Dönemi' : '')) ?></span>
          <?php if (!empty($activePeriod['status'])): ?>
            <span class="tenant-panel__status <?= htmlspecialchars($periodStatusClass[$activePeriod['status']] ?? 'secondary') ?>">
              <?= htmlspecialchars($periodStatusLabels[$activePeriod['status']] ?? $activePeriod['status']) ?>
            </span>
          <?php endif; ?>
        </div>
      </div>
      <div class="dropdown">
        <button class="tenant-panel__menu-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Şirket işlemleri">
          <i class="fa-solid fa-ellipsis-vertical"></i>
        </button>
        <div class="dropdown-menu dropdown-menu-end tenant-panel__dropdown">
          <a href="<?= BASE_URL ?>/companies/switch" class="dropdown-item"><i class="fa-solid fa-right-left"></i> Değiştir</a>
          <a href="<?= BASE_URL ?>/companies" class="dropdown-item"><i class="fa-solid fa-building"></i> Yönet</a>
          <?php if (!empty($activeCompany['id'])): ?>
            <a href="<?= BASE_URL ?>/companies/edit/<?= (int)$activeCompany['id'] ?>" class="dropdown-item"><i class="fa-solid fa-palette"></i> Ayarlar</a>
            <a href="<?= BASE_URL ?>/companies/periods/<?= (int)$activeCompany['id'] ?>" class="dropdown-item"><i class="fa-solid fa-calendar-days"></i> Dönemler</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</aside>

<!-- â•â•â• PAGE â•â•â• -->
<div class="page-wrapper">

  <header class="topbar">
    <div class="topbar__left">
      <?php if (!empty($topbarTitle)): ?>
        <div class="topbar__title" style="font-size:15px; font-weight:700; color:#1e293b; display:flex; align-items:center; gap:8px;">
          <?php if (!empty($topbarIcon)): ?>
            <i class="<?= htmlspecialchars($topbarIcon) ?>" style="color:#0d623a;"></i>
          <?php endif; ?>
          <?= htmlspecialchars($topbarTitle) ?>
        </div>
      <?php else: ?>
        <i class="fa-solid fa-bars" style="font-size:20px; color:#555; cursor:pointer;"></i>
      <?php endif; ?>
    </div>
    <div class="topbar__right" style="position:relative;">
      <a href="<?= BASE_URL ?>/takvim" class="notif-btn" style="text-decoration:none; color:#555; font-size:22px; padding:4px; display:inline-flex; align-items:center;">
        <i class="fa-regular fa-calendar-days"></i>
        <span class="notif-badge" style="position:absolute; top:-2px; right:-4px; padding:2px 5px; background:#ff5b5b; border-radius:4px; font-size:10px; font-weight:700; color:#fff; line-height:1;"><?= date('j') ?></span>
      </a>

      <div style="position:relative; display:inline-block; margin-left:8px;">
        <button class="topbar__profile-btn" id="userDropdownBtn" aria-expanded="false"
                onclick="event.stopPropagation(); const dd=document.getElementById('profileDropdown'); const isOpen=dd.classList.toggle('show'); this.setAttribute('aria-expanded', isOpen);">
          <div class="topbar__profile-avatar">
            <?php if ($currentUserAvatar !== ''): ?>
              <img src="<?= htmlspecialchars($currentUserAvatar) ?>" alt="<?= htmlspecialchars($currentUserName) ?>">
            <?php else: ?>
              <i class="fa-solid fa-user"></i>
            <?php endif; ?>
          </div>
          <div style="text-align:left;">
            <div class="topbar__profile-name"><?= htmlspecialchars($currentUserName) ?></div>
            <div class="topbar__profile-role"><?= htmlspecialchars($currentUserRoleLabel) ?></div>
          </div>
          <i class="fa-solid fa-chevron-down topbar__profile-caret"></i>
        </button>

        <div class="profile-dropdown" id="profileDropdown">
          <!-- Başlık -->
          <div class="pd-header">
            <div class="pd-header-avatar">
              <?php if ($currentUserAvatar !== ''): ?>
                <img src="<?= htmlspecialchars($currentUserAvatar) ?>" alt="<?= htmlspecialchars($currentUserName) ?>">
              <?php else: ?>
                <i class="fa-solid fa-user"></i>
              <?php endif; ?>
            </div>
            <div>
              <div class="pd-header-name"><?= htmlspecialchars($currentUserName) ?></div>
              <div class="pd-header-role"><?= htmlspecialchars($currentUserRoleLabel) ?></div>
            </div>
          </div>

          <!-- Menü öğeleri -->
          <a href="<?= BASE_URL ?>/profil" class="pd-item pd-hesabim">
            <div class="pd-icon"><i class="fa-solid fa-circle-user"></i></div>
            <span>Hesabım</span>
          </a>

          <a href="<?= BASE_URL ?>/profil/sifre-degistir" class="pd-item pd-sifre">
            <div class="pd-icon"><i class="fa-solid fa-lock"></i></div>
            <span>Şifre Değiştir</span>
          </a>

          <a href="<?= BASE_URL ?>/cikis" class="pd-item pd-cikis">
            <div class="pd-icon"><i class="fa-solid fa-arrow-right-from-bracket"></i></div>
            <span>Çıkış Yap</span>
          </a>
        </div>
      </div>
    </div>
  </header>

  <main class="main-content">
    <?= $content ?>
  </main>

</div>

<!-- Mobile Bottom Navigation Bar -->
<div class="mobile-bottom-bar">
  <button class="m-nav-btn" id="mNavMenu"><i class="fa-solid fa-bars"></i><span>Menü</span></button>
  <button class="m-nav-btn" id="mNavNakit"><i class="fa-solid fa-lira-sign"></i><span>Nakit</span></button>
  <button class="m-nav-btn" id="mNavRaporlar"><i class="fa-solid fa-chart-column"></i><span>Raporlar</span></button>
  <a href="<?= BASE_URL ?>/dashboard" class="m-nav-btn" id="mNavHome"><i class="fa-solid fa-home"></i><span>Ana Sayfa</span></a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
  'use strict';

  // Sidebar accordion — tek accordion açık kalır
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

  // Profile dropdown — dışarı tıklanınca kapat
  document.addEventListener('click', function(e) {
    const dd  = document.getElementById('profileDropdown');
    const btn = document.getElementById('userDropdownBtn');
    if (dd && btn && !dd.contains(e.target) && !btn.contains(e.target)) {
      dd.classList.remove('show');
      btn.setAttribute('aria-expanded', 'false');
    }
  });
})();
</script>
<script src="<?= BASE_URL ?>/js/mobile.js"></script>
</body>
</html>
