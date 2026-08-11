<?php
/**
 * Ana Layout (main.php)
 * --------------------------------------------------------
 * Mobile-first tasarım sistemi. Üç navigasyon bileşeni:
 *   .sidebar        → ≥768px
 *   .mobile-topbar  → <768px
 *   .bottom-nav     → <768px  (+ "Daha" bottom-sheet paneli)
 * Görünürlükleri yalnızca CSS media query ile kontrol edilir (JS ile değil).
 *
 * Menü TEK merkezî diziden ($menu) üretilir; böylece alt navigasyondaki
 * dört ana öğenin sırası her sayfada aynı kalır (parmak hafızası).
 *
 * Beklenen değişkenler:
 *   $pageTitle   (string) — sayfa başlığı
 *   $activeMenu  (string) — aktif menü anahtarı
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
        'takvim' => 'takvim',
        'companies' => 'companies',
        'periods' => 'companies',
        'dashboard' => 'dashboard',
    ][$currentSection] ?? $currentSection;
}

/* ── Merkezî menü tanımı ──────────────────────────────────────────────
   tip: 'link'    → tek satır
        'group'   → açılır alt menü
        'divider' → ayırıcı çizgi
   bnav: true → mobil alt navigasyonun sabit dört öğesinden biri        */
$menu = [
    ['tip' => 'link', 'key' => 'dashboard', 'ad' => 'Ana Sayfa', 'ikon' => 'fa-house', 'url' => '/dashboard', 'bnav' => true, 'bnavAd' => 'Ana Sayfa'],
    ['tip' => 'divider'],
    ['tip' => 'link', 'key' => 'musteriler', 'ad' => 'Müşteriler', 'ikon' => 'fa-users', 'url' => '/musteri', 'bnav' => true, 'bnavAd' => 'Müşteri'],
    ['tip' => 'link', 'key' => 'tedarikciler', 'ad' => 'Tedarikçiler', 'ikon' => 'fa-industry', 'url' => '/tedarikci'],
    ['tip' => 'link', 'key' => 'personel', 'ad' => 'Personel', 'ikon' => 'fa-user-tie', 'url' => '/personel'],
    ['tip' => 'divider'],
    ['tip' => 'group', 'key' => 'urunler_grup', 'ad' => 'Ürünler', 'ikon' => 'fa-tags', 'id' => 'grpUrunler', 'alt' => [
        ['key' => 'urunler', 'ad' => 'Ürün / Hizmet Tanımları', 'url' => '/urun', 'ikon' => 'fa-tags'],
        ['key' => 'depolar', 'ad' => 'Depolar', 'url' => '/depo', 'ikon' => 'fa-warehouse'],
        ['key' => 'urun_varyantlari', 'ad' => 'Ürün Varyantları', 'url' => '/urun/varyantlar', 'ikon' => 'fa-layer-group'],
    ]],
    ['tip' => 'link', 'key' => 'satislar', 'ad' => 'Satışlar', 'ikon' => 'fa-cart-shopping', 'url' => '/satis', 'bnav' => true, 'bnavAd' => 'Satış'],
    ['tip' => 'link', 'key' => 'alislar', 'ad' => 'Alışlar', 'ikon' => 'fa-truck', 'url' => '/alis', 'bnav' => true, 'bnavAd' => 'Alış'],
    ['tip' => 'link', 'key' => 'teklifler', 'ad' => 'Teklifler', 'ikon' => 'fa-folder-open', 'url' => '/teklif'],
    ['tip' => 'divider'],
    ['tip' => 'group', 'key' => 'nakit_grup', 'ad' => 'Nakit Yönetimi', 'ikon' => 'fa-lira-sign', 'id' => 'grpNakit', 'alt' => [
        ['key' => 'hesaplar', 'ad' => 'Hesaplarım', 'url' => '/hesap', 'ikon' => 'fa-wallet'],
        ['key' => 'masraflar', 'ad' => 'Masraflar', 'url' => '/masraf', 'ikon' => 'fa-receipt'],
        ['key' => 'gelen_efaturalar', 'ad' => 'Gelen E-Faturalar', 'url' => '/nakit/gelen-e-faturalar', 'ikon' => 'fa-file-invoice'],
        ['key' => 'krediler', 'ad' => 'Krediler', 'url' => '/kredi', 'ikon' => 'fa-building-columns'],
        ['key' => 'demirbaslar', 'ad' => 'Demirbaşlar', 'url' => '/demirbas', 'ikon' => 'fa-couch'],
        ['key' => 'projeler', 'ad' => 'Projeler', 'url' => '/proje', 'ikon' => 'fa-diagram-project'],
        ['key' => 'cek_portfoyu', 'ad' => 'Çek Portföyü', 'url' => '/cek', 'ikon' => 'fa-money-check'],
        ['key' => 'senet_portfoyu', 'ad' => 'Senet Portföyü', 'url' => '/senet', 'ikon' => 'fa-file-invoice-dollar'],
    ]],
    ['tip' => 'divider'],
    ['tip' => 'group', 'key' => 'raporlar', 'ad' => 'Raporlar', 'ikon' => 'fa-chart-column', 'id' => 'grpRaporlar', 'alt' => [
        ['key' => 'rapor_satis_alis', 'ad' => 'Satışlar - Alışlar', 'url' => '/rapor/satis_alis', 'ikon' => 'fa-chart-line'],
        ['key' => 'rapor_finansal', 'ad' => 'Finansal Raporlar', 'url' => '/rapor/finansal', 'ikon' => 'fa-chart-pie'],
        ['key' => 'rapor_stok', 'ad' => 'Stok Raporları', 'url' => '/rapor/stok', 'ikon' => 'fa-boxes-stacked'],
        ['key' => 'rapor_musteri', 'ad' => 'Müşteri Listesi', 'url' => '/rapor/musteri', 'ikon' => 'fa-address-card'],
    ]],
    ['tip' => 'link', 'key' => 'takvim', 'ad' => 'Takvim', 'ikon' => 'fa-calendar-days', 'url' => '/takvim'],
    ['tip' => 'link', 'key' => 'fihrist', 'ad' => 'Fihrist', 'ikon' => 'fa-address-book', 'url' => '/fihrist'],
    ['tip' => 'divider'],
    ['tip' => 'group', 'key' => 'ayarlar', 'ad' => 'Ayarlar', 'ikon' => 'fa-gear', 'id' => 'grpAyarlar', 'alt' => [
        ['key' => 'tanimlar', 'ad' => 'Tanımlar', 'url' => '/tanim', 'ikon' => 'fa-sliders'],
        ['key' => 'site', 'ad' => 'Site Yönetimi', 'url' => '/site', 'ikon' => 'fa-globe', 'vurgu' => true],
    ]],
];

/* ── RBAC: yalnızca VIEW izni olan modüller menüde görünür ────────────
   Frontend'de gizlemek TEK BAŞINA güvenlik değildir — backend (Router +
   Rbac::authorizeOrDeny) her endpoint'i AYRICA reddeder. Bu sadece UX. */
$menuModulePermission = [
    'musteriler' => 'MUSTERI_VIEW', 'tedarikciler' => 'TEDARIKCI_VIEW', 'personel' => 'PERSONEL_VIEW',
    'urunler' => 'URUN_VIEW', 'depolar' => 'DEPO_VIEW', 'urun_varyantlari' => 'URUN_VIEW',
    'satislar' => 'SATIS_VIEW', 'alislar' => 'ALIS_VIEW', 'teklifler' => 'TEKLIF_VIEW',
    'hesaplar' => 'HESAP_VIEW', 'masraflar' => 'MASRAF_VIEW', 'gelen_efaturalar' => 'NAKIT_VIEW',
    'krediler' => 'KREDI_VIEW', 'demirbaslar' => 'DEMIRBAS_VIEW', 'projeler' => 'PROJE_VIEW',
    'cek_portfoyu' => 'PORTFOY_VIEW', 'senet_portfoyu' => 'PORTFOY_VIEW',
    'rapor_satis_alis' => 'RAPOR_VIEW', 'rapor_finansal' => 'RAPOR_VIEW',
    'rapor_stok' => 'RAPOR_VIEW', 'rapor_musteri' => 'RAPOR_VIEW',
    'takvim' => 'TAKVIM_VIEW', 'fihrist' => 'FIHRIST_VIEW',
    'tanimlar' => 'TANIM_VIEW', 'site' => 'SITE_VIEW',
];
$menuCanSee = function (string $menuKey) use ($menuModulePermission): bool {
    if (!isset($menuModulePermission[$menuKey])) return true;
    if (!class_exists('Rbac') || !class_exists('AuthGuard') || !AuthGuard::isLoggedIn()) return true;
    return Rbac::currentUserCan($menuModulePermission[$menuKey]);
};
foreach ($menu as $mi => $mItem) {
    if ($mItem['tip'] === 'link') {
        if (!$menuCanSee($mItem['key'])) unset($menu[$mi]);
    } elseif ($mItem['tip'] === 'group') {
        $menu[$mi]['alt'] = array_values(array_filter($mItem['alt'], fn($alt) => $menuCanSee($alt['key'])));
        if (empty($menu[$mi]['alt'])) unset($menu[$mi]);
    }
}
$menu = array_values($menu);

/* ── Süper Yönetici özel menüsü (Kullanıcılar / Roller / Audit Log) ── */
if (class_exists('AuthGuard') && AuthGuard::isSuperAdmin()) {
    $menu[] = ['tip' => 'divider'];
    $menu[] = ['tip' => 'group', 'key' => 'yonetim_grup', 'ad' => 'Yönetim', 'ikon' => 'fa-user-shield', 'id' => 'grpYonetim', 'alt' => [
        ['key' => 'kullanicilar', 'ad' => 'Kullanıcılar', 'url' => '/kullanicilar', 'ikon' => 'fa-users-gear'],
        ['key' => 'roller', 'ad' => 'Roller', 'url' => '/roller', 'ikon' => 'fa-user-shield'],
        ['key' => 'audit', 'ad' => 'Audit Log', 'url' => '/audit', 'ikon' => 'fa-shield-halved'],
    ]];
}

/** Alt menü anahtarı → gerçek rota bölümü eşlemesi (aktiflik tespiti için) */
$altAktifMi = function (array $alt) use ($activeMenu, $currentSection, $currentAction): bool {
    if ($activeMenu === $alt['key']) return true;
    return match ($alt['key']) {
        'urunler'          => $currentSection === 'urun' && $currentAction !== 'varyantlar',
        'depolar'          => $currentSection === 'depo',
        'urun_varyantlari' => $currentSection === 'urun' && $currentAction === 'varyantlar',
        'hesaplar'         => $currentSection === 'hesap',
        'masraflar'        => $currentSection === 'masraf',
        'gelen_efaturalar' => $currentSection === 'nakit' && $currentAction === 'gelen_e_faturalar',
        'krediler'         => $currentSection === 'kredi',
        'demirbaslar'      => $currentSection === 'demirbas',
        'projeler'         => $currentSection === 'proje',
        'cek_portfoyu'     => $currentSection === 'cek',
        'senet_portfoyu'   => $currentSection === 'senet',
        'tanimlar'         => $currentSection === 'tanim',
        'site'             => $currentSection === 'site',
        'rapor_satis_alis' => $currentSection === 'rapor' && $currentAction === 'satis_alis',
        'rapor_finansal'   => $currentSection === 'rapor' && in_array($currentAction, ['finansal', 'financial'], true),
        'rapor_stok'       => $currentSection === 'rapor' && $currentAction === 'stok',
        'rapor_musteri'    => $currentSection === 'rapor' && in_array($currentAction, ['musteri', 'musteri_listesi'], true),
        default            => false,
    };
};

/** Bir grubun herhangi bir alt öğesi aktif mi? */
$grupAktifMi = function (array $grup) use ($altAktifMi): bool {
    foreach ($grup['alt'] as $alt) {
        if ($altAktifMi($alt)) return true;
    }
    return false;
};

/* ── Mobil alt navigasyon: sabit dört öğe + "Daha" ────────────────────── */
$bnavSabit = array_values(array_filter($menu, fn($m) => !empty($m['bnav'])));

/* "Daha" panelindeki her şey: alt navigasyonda yer bulamayan tüm sayfalar */
$bnavDaha = [];
foreach ($menu as $m) {
    if ($m['tip'] === 'divider' || !empty($m['bnav'])) continue;
    if ($m['tip'] === 'link') {
        $bnavDaha[] = ['ad' => $m['ad'], 'url' => $m['url'], 'ikon' => $m['ikon'], 'aktif' => $activeMenu === $m['key']];
    } else {
        foreach ($m['alt'] as $alt) {
            $bnavDaha[] = ['ad' => $alt['ad'], 'url' => $alt['url'], 'ikon' => $alt['ikon'], 'aktif' => $altAktifMi($alt)];
        }
    }
}

/* ── Tenant / kullanıcı bilgileri ─────────────────────────────────────── */
$activeCompany = class_exists('TenantContext') ? TenantContext::activeCompany() : null;
$activePeriod  = class_exists('TenantContext') ? TenantContext::activePeriod() : null;
$periodStatusLabels = ['open' => 'Açık', 'locked' => 'Kilitli', 'closed' => 'Kapalı', 'archived' => 'Arşiv'];
$periodStatusClass  = ['open' => 'badge-success', 'locked' => 'badge-warning', 'closed' => 'badge-muted', 'archived' => 'badge-muted'];

$currentUserName   = class_exists('AuthGuard') && AuthGuard::isLoggedIn() ? AuthGuard::userName() : 'Misafir';
$currentUserRole   = class_exists('AuthGuard') && AuthGuard::isLoggedIn() ? AuthGuard::userRole() : 'guest';
$currentUserAvatar = (string)($_SESSION['user_avatar_path'] ?? '');
$roleLabels = [
    'super_admin' => 'Süper Yönetici',
    'admin' => 'Yönetici',
    'accountant' => 'Muhasebe',
    'user' => 'Kullanıcı',
    'guest' => 'Misafir',
];
$currentUserRoleLabel = $roleLabels[$currentUserRole] ?? 'Kullanıcı';

/* Tema: çerezden okunur → sunucu tarafında basılır, böylece sayfa
   açılışında açık/koyu tema arasında titreme (flash) olmaz. */
$tema = (($_COOKIE['nym_theme'] ?? '') === 'acik') ? 'acik' : 'koyu';

/* Şirket rengi (Şirket Ayarları → "Şirket rengi") sistemin vurgu rengini
   ezer. Seçim yapılmamışsa tasarım sisteminin varsayılanı kullanılır. */
$sirketRenkleri = [
    'emerald' => ['#22c55e', '#16a34a', '34,197,94'],
    'blue'    => ['#3b82f6', '#2563eb', '59,130,246'],
    'violet'  => ['#8b5cf6', '#7c3aed', '139,92,246'],
    'amber'   => ['#f59e0b', '#d97706', '245,158,11'],
    'rose'    => ['#f43f5e', '#e11d48', '244,63,94'],
    'cyan'    => ['#06b6d4', '#0891b2', '6,182,212'],
    'slate'   => ['#94a3b8', '#64748b', '148,163,184'],
];
$sirketAyarlari = class_exists('TenantContext') ? TenantContext::activeCompanySettings() : [];
$sirketRengi = $sirketRenkleri[$sirketAyarlari['theme_color'] ?? ''] ?? null;

$sayfaBasligi = $topbarTitle ?? $pageTitle;
$sayfaIkonu   = $topbarIcon ?? '';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
  <!-- Panel sayfaları arama motorlarında indekslenmemeli -->
  <meta name="robots" content="noindex, nofollow, noarchive" />
  <meta name="googlebot" content="noindex, nofollow" />
  <title><?= htmlspecialchars($pageTitle) ?> — <?= htmlspecialchars(APP_NAME) ?></title>

  <!-- PWA -->
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="<?= htmlspecialchars(APP_NAME) ?>">
  <meta name="theme-color" content="<?= $tema === 'acik' ? '#f0f2f5' : '#0d0d1a' ?>">
  <link rel="manifest" href="<?= varlik('/panel.webmanifest') ?>">
  <link rel="icon" href="<?= varlik('/favicon.ico') ?>" sizes="any">
  <link rel="icon" type="image/png" sizes="32x32" href="<?= varlik('/favicon-32x32.png') ?>">
  <link rel="icon" type="image/png" sizes="16x16" href="<?= varlik('/favicon-16x16.png') ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= varlik('/apple-touch-icon.png') ?>">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
  <link rel="stylesheet" href="<?= varlik('/css/panel-ui.css') ?>" />
  <?php if ($sirketRengi): ?>
  <style>
    :root {
      --accent: <?= $sirketRengi[0] ?>;
      --accent2: <?= $sirketRengi[1] ?>;
      --glow: rgba(<?= $sirketRengi[2] ?>, .40);
      --accent-soft: rgba(<?= $sirketRengi[2] ?>, .14);
    }
  </style>
  <?php endif; ?>
</head>
<body data-theme="<?= $tema === 'acik' ? 'acik' : 'koyu' ?>">

<!-- ═══ MASAÜSTÜ KENAR ÇUBUĞU ═══ -->
<aside class="sidebar">
  <div class="tenant-box tenant-box-top">
    <div class="tenant-mark"></div>
    <div class="tenant-info">
      <div class="tenant-company" title="<?= htmlspecialchars($activeCompany['company_name'] ?? 'Şirket seçilmedi') ?>">
        <?= htmlspecialchars($activeCompany['company_name'] ?? 'Şirket seçilmedi') ?>
      </div>
      <div class="tenant-period">
        <span><?= htmlspecialchars(($activePeriod['period_name'] ?? 'Dönem seçilmedi') . (!empty($activePeriod['period_name']) ? ' Dönemi' : '')) ?></span>
        <?php if (!empty($activePeriod['status'])): ?>
          <span class="badge <?= $periodStatusClass[$activePeriod['status']] ?? 'badge-muted' ?>">
            <?= htmlspecialchars($periodStatusLabels[$activePeriod['status']] ?? $activePeriod['status']) ?>
          </span>
        <?php endif; ?>
      </div>
    </div>
    <div class="dropdown">
      <button class="tenant-menu-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Şirket işlemleri">
        <i class="fa-solid fa-ellipsis-vertical"></i>
      </button>
      <div class="dropdown-menu dropdown-menu-end">
        <a href="<?= BASE_URL ?>/companies/switch" class="dropdown-item"><i class="fa-solid fa-right-left"></i> Değiştir</a>
        <a href="<?= BASE_URL ?>/companies" class="dropdown-item"><i class="fa-solid fa-building"></i> Yönet</a>
        <?php if (!empty($activeCompany['id'])): ?>
          <a href="<?= BASE_URL ?>/companies/edit/<?= (int)$activeCompany['id'] ?>" class="dropdown-item"><i class="fa-solid fa-palette"></i> Ayarlar</a>
          <a href="<?= BASE_URL ?>/companies/periods/<?= (int)$activeCompany['id'] ?>" class="dropdown-item"><i class="fa-solid fa-calendar-days"></i> Dönemler</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <ul>
      <?php foreach ($menu as $m): ?>
        <?php if ($m['tip'] === 'divider'): ?>
          <li class="nav-divider"></li>

        <?php elseif ($m['tip'] === 'link'): ?>
          <li>
            <a href="<?= BASE_URL . $m['url'] ?>" class="nav-item<?= $activeMenu === $m['key'] ? ' active' : '' ?>">
              <i class="fa-solid <?= $m['ikon'] ?> nav-ico"></i>
              <span class="nav-txt"><?= htmlspecialchars($m['ad']) ?></span>
            </a>
          </li>

        <?php else: $acik = $grupAktifMi($m); ?>
          <li>
            <button type="button" class="nav-item js-nav-group<?= $acik ? ' active' : '' ?>"
                    data-hedef="<?= $m['id'] ?>" aria-expanded="<?= $acik ? 'true' : 'false' ?>">
              <i class="fa-solid <?= $m['ikon'] ?> nav-ico"></i>
              <span class="nav-txt"><?= htmlspecialchars($m['ad']) ?></span>
              <i class="fa-solid fa-chevron-down nav-caret"></i>
            </button>
            <ul class="nav-group<?= $acik ? ' open' : '' ?>" id="<?= $m['id'] ?>">
              <?php foreach ($m['alt'] as $alt): ?>
                <li>
                  <a href="<?= BASE_URL . $alt['url'] ?>"
                     class="nav-sub<?= $altAktifMi($alt) ? ' active' : '' ?><?= !empty($alt['vurgu']) ? ' is-highlight' : '' ?>">
                    <?= htmlspecialchars($alt['ad']) ?>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          </li>
        <?php endif; ?>
      <?php endforeach; ?>
    </ul>
  </nav>

</aside>

<!-- ═══ İÇERİK ═══ -->
<div class="main-wrap">

  <!-- Masaüstü üst bar -->
  <header class="top-bar">
    <div class="top-title">
      <?php if ($sayfaIkonu !== ''): ?><i class="<?= htmlspecialchars($sayfaIkonu) ?>"></i><?php endif; ?>
      <span><?= htmlspecialchars($sayfaBasligi) ?></span>
    </div>
    <div class="top-actions">
      <button type="button" class="icon-btn js-tema" title="Temayı değiştir" aria-label="Temayı değiştir">
        <i class="fa-solid <?= $tema === 'acik' ? 'fa-moon' : 'fa-sun' ?>"></i>
      </button>
      <a href="<?= BASE_URL ?>/takvim" class="icon-btn" title="Takvim">
        <i class="fa-regular fa-calendar-days"></i>
        <span class="dot-badge"><?= date('j') ?></span>
      </a>

      <div class="profile-wrap">
        <button class="profile-btn js-profil" aria-expanded="false">
          <div class="profile-avatar">
            <?php if ($currentUserAvatar !== ''): ?>
              <img src="<?= htmlspecialchars($currentUserAvatar) ?>" alt="<?= htmlspecialchars($currentUserName) ?>">
            <?php else: ?>
              <i class="fa-solid fa-user"></i>
            <?php endif; ?>
          </div>
          <div class="profile-meta">
            <div class="profile-name" title="<?= htmlspecialchars($currentUserName) ?>"><?= htmlspecialchars($currentUserName) ?></div>
            <div class="profile-role"><?= htmlspecialchars($currentUserRoleLabel) ?></div>
          </div>
          <i class="fa-solid fa-chevron-down profile-caret"></i>
        </button>

        <div class="profile-menu" id="profilMenu">
          <div class="pm-head">
            <div class="profile-avatar">
              <?php if ($currentUserAvatar !== ''): ?>
                <img src="<?= htmlspecialchars($currentUserAvatar) ?>" alt="<?= htmlspecialchars($currentUserName) ?>">
              <?php else: ?>
                <i class="fa-solid fa-user"></i>
              <?php endif; ?>
            </div>
            <div class="pm-head-meta">
              <div class="pm-head-name" title="<?= htmlspecialchars($currentUserName) ?>"><?= htmlspecialchars($currentUserName) ?></div>
              <div class="pm-head-role"><?= htmlspecialchars($currentUserRoleLabel) ?></div>
            </div>
          </div>
          <a href="<?= BASE_URL ?>/profil" class="pm-item"><i class="fa-solid fa-circle-user"></i> Hesabım</a>
          <a href="<?= BASE_URL ?>/profil/sifre-degistir" class="pm-item"><i class="fa-solid fa-lock"></i> Şifre Değiştir</a>
          <a href="<?= BASE_URL ?>/cikis" class="pm-item is-danger"><i class="fa-solid fa-arrow-right-from-bracket"></i> Çıkış Yap</a>
        </div>
      </div>
    </div>
  </header>

  <!-- Mobil üst bar -->
  <header class="mobile-topbar">
    <div class="mt-brand">
      <div class="brand-icon"><i class="fa-solid fa-chart-pie"></i></div>
      <div class="mt-titles">
        <div class="mt-title"><?= htmlspecialchars($sayfaBasligi) ?></div>
        <div class="mt-sub">
          <?= htmlspecialchars($activeCompany['short_name'] ?? $activeCompany['company_name'] ?? 'Şirket seçilmedi') ?>
          <?php if (!empty($activePeriod['period_name'])): ?>
            · <?= htmlspecialchars($activePeriod['period_name']) ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="mt-actions">
      <button type="button" class="icon-btn js-tema" title="Temayı değiştir" aria-label="Temayı değiştir">
        <i class="fa-solid <?= $tema === 'acik' ? 'fa-moon' : 'fa-sun' ?>"></i>
      </button>
      <a href="<?= BASE_URL ?>/profil" class="icon-btn" title="Hesabım"><i class="fa-solid fa-user"></i></a>
    </div>
  </header>

  <main class="page-content">
    <?= $content ?>
  </main>
</div>

<!-- ═══ MOBİL ALT NAVİGASYON ═══ -->
<nav class="bottom-nav">
  <div class="bottom-nav-inner">
    <?php foreach ($bnavSabit as $b): $aktif = $activeMenu === $b['key']; ?>
      <a href="<?= BASE_URL . $b['url'] ?>" class="bnav-item<?= $aktif ? ' active' : '' ?>">
        <span class="bnav-icon-wrap<?= $aktif ? ' active' : '' ?>">
          <i class="fa-solid <?= $b['ikon'] ?> bnav-icon"></i>
        </span>
        <span class="bnav-label"><?= htmlspecialchars($b['bnavAd']) ?></span>
      </a>
    <?php endforeach; ?>
    <button type="button" class="bnav-item js-daha" aria-expanded="false">
      <span class="bnav-icon-wrap"><i class="fa-solid fa-ellipsis bnav-icon"></i></span>
      <span class="bnav-label">Daha</span>
    </button>
  </div>
</nav>

<div class="bnav-overlay" id="bnavOverlay"></div>

<div class="bnav-more-panel" id="bnavMorePanel">
  <div class="bnav-more-title">Tüm Sayfalar</div>
  <div class="bnav-more-grid">
    <?php foreach ($bnavDaha as $d): ?>
      <a href="<?= BASE_URL . $d['url'] ?>" class="bnav-more-item<?= $d['aktif'] ? ' active' : '' ?>">
        <i class="fa-solid <?= $d['ikon'] ?>"></i> <?= htmlspecialchars($d['ad']) ?>
      </a>
    <?php endforeach; ?>
    <a href="<?= BASE_URL ?>/companies/switch" class="bnav-more-item"><i class="fa-solid fa-right-left"></i> Şirket Değiştir</a>
    <a href="<?= BASE_URL ?>/cikis" class="bnav-more-item"><i class="fa-solid fa-arrow-right-from-bracket"></i> Çıkış Yap</a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= varlik('/js/panel-ui.js') ?>"></script>
</body>
</html>
