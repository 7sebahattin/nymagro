<?php
/**
 * Regresyon testi: Şirket/dönem seçimi + kullanıcı-şirket ataması.
 * --------------------------------------------------------------------------
 * Çalıştırma:  php tests/regression/tenant_secim_invariants.php
 * Çıkış kodu:  0 = tüm kontroller PASSED, 1 = en az bir FAILED.
 *
 * Arka plan (kullanıcının bildirdiği hata):
 *   Yeni açılan bir kullanıcı ("nuray") giriş yapabiliyor ama hiçbir ekranı
 *   açamıyordu; /dashboard "Bu şirkete erişim yetkiniz yok." beyaz sayfasında
 *   takılıyor, "Şirket Değiştir" ekranında ise seçemeyeceği tek bir PASİF
 *   şirket görüyordu. Kullanıcı yönetiminde şirket atama alanı hiç yoktu.
 *
 * Tespit edilen kök nedenler:
 *   A) UserAdmin::create() users tablosuna yazıyor ama user_companies'e HİÇ
 *      satır eklemiyordu; formda da şirket seçme alanı yoktu.
 *   B) TenantContext::bootstrap() giriş yapılmadan ÖNCE de çalışıyor ve
 *      userId() varsayılan olarak 1 döndürdüğü için oturuma "1 numaralı
 *      kullanıcının" şirketi yazılıyordu. Girişte bu seçim temizlenmediği
 *      için yeni kullanıcı erişemediği bir şirkete kilitleniyordu.
 *   C) ensureActiveSelection() erişilemeyen seçimi yalnızca YERİNE BİR ŞEY
 *      BULABİLDİĞİNDE değiştiriyordu; hiç uygun şirket yoksa erişilemeyen id
 *      oturumda kalıyor ve her istekte 403 üretiyordu.
 *   D) ensureDefaultTenant() giriş yapan HER kullanıcıya, id'si en küçük
 *      şirketin 'owner' rolünü sessizce veriyordu (yetki yükselmesi).
 *
 * Bu test, TenantContext'in SAF karar fonksiyonlarını (resolveCompanySelection
 * / resolvePeriodSelection) tüm varyasyonlarıyla GERÇEKTEN ÇALIŞTIRARAK
 * doğrular; geri kalan invariantlar kaynak denetimiyle korunur.
 * Veritabanı gerektirmez.
 */

declare(strict_types=1);

$kok = dirname(__DIR__, 2);

require_once $kok . '/app/core/TenantContext.php';

/** @var array<int,array{ad:string,ok:bool,detay:string}> */
$sonuclar = [];

function kontrol(string $ad, bool $gecti, string $detay = ''): void
{
    global $sonuclar;
    $sonuclar[] = ['ad' => $ad, 'ok' => $gecti, 'detay' => $detay];
}

function kodSadece(string $kaynak): string
{
    $cikti = '';
    foreach (token_get_all($kaynak) as $token) {
        if (is_array($token)) {
            if ($token[0] === T_COMMENT || $token[0] === T_DOC_COMMENT) {
                continue;
            }
            $cikti .= $token[1];
            continue;
        }
        $cikti .= $token;
    }
    return $cikti;
}

function metotGovdesi(string $sinif, string $metot): string
{
    $rm = new ReflectionMethod($sinif, $metot);
    $dosya = $rm->getFileName();
    if ($dosya === false) {
        return '';
    }
    $satirlar = file($dosya);
    return kodSadece('<?php ' . implode('', array_slice($satirlar, $rm->getStartLine() - 1, $rm->getEndLine() - $rm->getStartLine() + 1)));
}

function oku(string $goreliYol): string
{
    global $kok;
    return (string)@file_get_contents($kok . '/' . $goreliYol);
}

/** Şirket satırı kısayolu. */
function sirket(int $id, string $status = 'active', int $isDefault = 0): array
{
    return ['id' => $id, 'status' => $status, 'is_default' => $isDefault];
}

/** Dönem satırı kısayolu. */
function donem(int $id, string $status = 'open', int $yil = 2026): array
{
    return ['id' => $id, 'status' => $status, 'fiscal_year' => $yil];
}

// ═════════════════════════════════════════════════════════════════════
// 1) resolveCompanySelection() — TÜM VARYASYONLAR (gerçek çalıştırma)
// ═════════════════════════════════════════════════════════════════════

$R = fn(int $uid, ?int $sess, int $owner, array $liste) =>
    TenantContext::resolveCompanySelection($uid, $sess, $owner, $liste);

// --- Giriş yapılmamış kullanıcı ---
kontrol('Şirket seçimi: giriş yapılmamışsa (userId=0) hiçbir şirket seçilmez',
    $R(0, null, 0, [sirket(1)]) === null);
kontrol('Şirket seçimi: giriş yapılmamışsa oturumda şirket olsa BİLE seçilmez (anonim istek fail-closed)',
    $R(0, 1, 0, [sirket(1)]) === null);
kontrol('Şirket seçimi: negatif/geçersiz userId de reddedilir',
    $R(-5, 1, -5, [sirket(1)]) === null);

// --- Hiç şirket ataması olmayan kullanıcı (bildirilen hatanın çekirdeği) ---
kontrol('Şirket seçimi: hiç şirketi olmayan kullanıcı için null döner (oturum temizlenmeli)',
    $R(7, null, 0, []) === null);
kontrol('Şirket seçimi: hiç şirketi olmayan kullanıcının oturumundaki YABANCI şirket devralınmaz (asıl hata: 403 döngüsü)',
    $R(7, 42, 1, []) === null);

// --- Yalnızca pasif şirketi olan kullanıcı (ekran görüntüsündeki durum) ---
kontrol('Şirket seçimi: yalnızca PASİF şirketi olan kullanıcı için null döner (pasif şirkete girilemez)',
    $R(7, null, 0, [sirket(1, 'passive')]) === null);
kontrol('Şirket seçimi: oturumdaki şirket pasifleştiyse artık korunmaz (arşivlenen şirkette çalışmaya devam edilmez)',
    $R(7, 1, 7, [sirket(1, 'passive')]) === null);
kontrol('Şirket seçimi: arşivlenmiş (archived) şirket de seçilmez',
    $R(7, null, 0, [sirket(1, 'archived')]) === null);

// --- Normal seçim ---
kontrol('Şirket seçimi: tek aktif şirketi olan kullanıcı o şirkete düşer',
    $R(7, null, 0, [sirket(5)]) === 5);
kontrol('Şirket seçimi: pasif + aktif karışıkken AKTİF olan seçilir',
    $R(7, null, 0, [sirket(1, 'passive'), sirket(9, 'active')]) === 9);
kontrol('Şirket seçimi: birden çok aktif şirkette varsayılan (is_default) olan öncelikli',
    $R(7, null, 0, [sirket(3), sirket(8, 'active', 1), sirket(11)]) === 8);
kontrol('Şirket seçimi: varsayılan yoksa en küçük id seçilir (deterministik)',
    $R(7, null, 0, [sirket(11), sirket(3), sirket(8)]) === 3);
kontrol('Şirket seçimi: pasif şirket varsayılan işaretli olsa bile seçilmez, aktif olan kazanır',
    $R(7, null, 0, [sirket(2, 'passive', 1), sirket(6, 'active', 0)]) === 6);

// --- Oturumdaki seçimin korunması ---
kontrol('Şirket seçimi: kullanıcının kendi seçtiği aktif şirket korunur (varsayılana geri düşmez)',
    $R(7, 11, 7, [sirket(3, 'active', 1), sirket(11)]) === 11);
kontrol('Şirket seçimi: oturumdaki şirket kullanıcıya ait DEĞİLSE yok sayılır, erişilebilir olana düşülür',
    $R(7, 99, 7, [sirket(3)]) === 3);

// --- Kullanıcı değişimi (oturum devri) — B şıkkının testi ---
kontrol('Şirket seçimi: oturumdaki seçim BAŞKA kullanıcıya aitse devralınmaz (owner=1, giren=7)',
    $R(7, 11, 1, [sirket(3), sirket(11)]) === 3);
kontrol('Şirket seçimi: sahipsiz (owner=0) seçim de devralınmaz — giriş öncesi bootstrap kalıntısı',
    $R(7, 11, 0, [sirket(3), sirket(11)]) === 3);
kontrol('Şirket seçimi: seçim sahibi ile giren kullanıcı AYNI ise seçim korunur',
    $R(7, 11, 7, [sirket(3), sirket(11)]) === 11);

// --- Sınır değerler ---
kontrol('Şirket seçimi: oturumda 0 yazıyorsa geçersiz sayılır',
    $R(7, 0, 7, [sirket(4)]) === 4);
kontrol('Şirket seçimi: id/is_default string gelse bile (PDO string döndürebilir) doğru çalışır',
    $R(7, null, 0, [['id' => '12', 'status' => 'active', 'is_default' => '1'], ['id' => '4', 'status' => 'active', 'is_default' => '0']]) === 12);

// ═════════════════════════════════════════════════════════════════════
// 2) resolvePeriodSelection() — TÜM VARYASYONLAR
// ═════════════════════════════════════════════════════════════════════

$P = fn(?int $sess, array $liste) => TenantContext::resolvePeriodSelection($sess, $liste);

kontrol('Dönem seçimi: hiç dönem yoksa null döner',
    $P(null, []) === null);
kontrol('Dönem seçimi: oturumdaki dönem BU şirkete aitse korunur',
    $P(50, [donem(50), donem(51)]) === 50);
kontrol('Dönem seçimi: oturumdaki dönem başka şirkete aitse yok sayılır (şirket değişince dönem de değişir)',
    $P(999, [donem(50)]) === 50);
kontrol('Dönem seçimi: açık dönem varken kapalı/kilitli dönem seçilmez',
    $P(null, [donem(50, 'closed', 2025), donem(51, 'open', 2024)]) === 51);
kontrol('Dönem seçimi: birden çok açık dönemde en yeni mali yıl seçilir',
    $P(null, [donem(50, 'open', 2024), donem(51, 'open', 2026), donem(52, 'open', 2025)]) === 51);
kontrol('Dönem seçimi: hiç açık dönem yoksa en yeni dönem (kapalı olsa da) seçilir — kullanıcı en azından görüntüleyebilsin',
    $P(null, [donem(50, 'closed', 2024), donem(51, 'locked', 2026)]) === 51);
kontrol('Dönem seçimi: oturumdaki KAPALI dönem bu şirkete aitse korunur (kullanıcı bilerek seçmiş olabilir)',
    $P(50, [donem(50, 'closed', 2024), donem(51, 'open', 2026)]) === 50);
kontrol('Dönem seçimi: aynı mali yılda birden çok dönem varsa deterministik (en büyük id)',
    $P(null, [donem(50, 'open', 2026), donem(77, 'open', 2026)]) === 77);

// ═════════════════════════════════════════════════════════════════════
// 3) Kök neden A: kullanıcı oluşturma artık şirket ataması yapıyor
// ═════════════════════════════════════════════════════════════════════

require_once $kok . '/app/models/UserAdmin.php';

$createGovde = metotGovdesi('UserAdmin', 'create');
kontrol('UserAdmin::create() şirket ataması olmadan kullanıcı oluşturmayı REDDEDİYOR (şirketsiz kullanıcı hiçbir ekranı açamaz)',
    (bool)preg_match('/if\s*\(\s*!\s*\$companyIds\s*\)/', $createGovde));
kontrol('UserAdmin::create() user_companies satırlarını AYNI transaction içinde yazıyor (yarım kullanıcı kalmaz)',
    (bool)preg_match('/db->begin\(\).*syncCompanies\(.*db->commit\(\)/s', $createGovde));
kontrol('UserAdmin::create() şirket rolünü doğrulayarak alıyor (ENUM dışı değer yazılamaz)',
    str_contains($createGovde, 'normalizeCompanyRole('));

$updateGovde = metotGovdesi('UserAdmin', 'update');
kontrol('UserAdmin::update() şirket atamalarını da güncelliyor',
    str_contains($updateGovde, 'syncCompanies('));
kontrol('UserAdmin::update() yöneticinin KENDİ şirket erişimini kaldırmasını engelliyor (kendini kilitleyemez)',
    (bool)preg_match('/\$id\s*===\s*\$actorId.*array_diff\(\$companyIdsBefore,\s*\$companyIds\)/s', $updateGovde));

// normalizeCompanyRole gerçek çağrılarla doğrulanır
kontrol('normalizeCompanyRole(): geçerli rol aynen döner',
    UserAdmin::normalizeCompanyRole('accountant') === 'accountant');
kontrol('normalizeCompanyRole(): uydurma rol güvenli varsayılana düşer (ENUM sessiz bozulması olmaz)',
    UserAdmin::normalizeCompanyRole('superuser') === UserAdmin::DEFAULT_COMPANY_ROLE);
kontrol('normalizeCompanyRole(): boş/null değer güvenli varsayılana düşer',
    UserAdmin::normalizeCompanyRole(null) === UserAdmin::DEFAULT_COMPANY_ROLE
    && UserAdmin::normalizeCompanyRole('') === UserAdmin::DEFAULT_COMPANY_ROLE);
kontrol('Şirket rolü varsayılanı en az yetkili seçenek (yeni kullanıcı kazara şirket yöneticisi olmaz)',
    !in_array(UserAdmin::DEFAULT_COMPANY_ROLE, ['owner', 'admin'], true));
kontrol('COMPANY_ROLES listesi user_companies.role ENUM değerleriyle birebir aynı',
    array_keys(UserAdmin::COMPANY_ROLES) === ['owner', 'admin', 'accountant', 'sales', 'viewer']);

$syncGovde = metotGovdesi('UserAdmin', 'syncCompanies');
kontrol('syncCompanies() can_manage_company yetkisini SADECE owner/admin rollerine veriyor',
    (bool)preg_match("/in_array\(\\\$role,\s*\['owner',\s*'admin'\],\s*true\)/", $syncGovde));
kontrol('syncCompanies() işaretlenmeyen şirketlerin atamasını siliyor (kutu kaldırınca erişim gerçekten kalkar)',
    (bool)preg_match('/array_diff\(\$current,\s*\$target\).*DELETE FROM user_companies/s', $syncGovde));
kontrol('syncCompanies() atama kalmışsa mutlaka bir varsayılan şirket bırakıyor',
    str_contains($syncGovde, 'is_default = 1'));

// Kullanıcı formu artık şirket seçme alanı içeriyor
$form = oku('app/views/kullanicilar/form.php');
kontrol('Kullanıcı formunda "Yetkili Şirketler" alanı var (kullanıcının bildirdiği eksik alan)',
    str_contains($form, 'Yetkili Şirketler'));
kontrol('Kullanıcı formu company_ids[] onay kutularını gönderiyor',
    str_contains($form, 'name="company_ids[]"'));
kontrol('Kullanıcı formu şirket yetki düzeyini (company_role) gönderiyor',
    str_contains($form, 'name="company_role"'));

$ctrl = oku('app/controllers/UserController.php');
kontrol('UserController::ekle() forma şirket listesini iletiyor',
    (bool)preg_match("/'sirketler'\s*=>\s*\\\$this->users->assignableCompanies\(\)/", $ctrl));
kontrol('UserController::duzenle() forma mevcut atamaları iletiyor',
    (bool)preg_match("/'atananlar'\s*=>\s*\\\$this->users->companyAssignments\(/", $ctrl));

$liste = oku('app/views/kullanicilar/index.php');
kontrol('Kullanıcı listesinde şirketi olmayan kullanıcı açıkça uyarıyla gösteriliyor',
    str_contains($liste, 'Şirket atanmamış'));

// ═════════════════════════════════════════════════════════════════════
// 4) Kök neden B: giriş öncesi/oturum devri sızıntısı
// ═════════════════════════════════════════════════════════════════════

$userIdGovde = metotGovdesi('TenantContext', 'userId');
kontrol('TenantContext::userId() giriş yapılmamışken artık 1 DEĞİL 0 döndürüyor (anonim istek 1 numaralı kullanıcı sanılmıyor)',
    str_contains($userIdGovde, "?? 0") && !str_contains($userIdGovde, "?? 1"));

$bootstrapGovde = metotGovdesi('TenantContext', 'bootstrap');
kontrol('TenantContext::bootstrap() şirket seçimini SADECE giriş yapılmışken çalıştırıyor',
    (bool)preg_match('/if\s*\(\s*!self::isLoggedIn\(\)\s*\)\s*\{\s*return;/', $bootstrapGovde));
kontrol('TenantContext::bootstrap() şemayı yine de her istekte hazırlıyor (giriş ekranı açılabilsin)',
    strpos($bootstrapGovde, 'ensureSchema()') < strpos($bootstrapGovde, 'isLoggedIn()'));

$authGuard = oku('app/core/AuthGuard.php');
kontrol('AuthGuard::attempt() başarılı girişte önceki oturumun şirket/dönem seçimini temizliyor',
    (bool)preg_match("/unset\(\\\$_SESSION\['active_company_id'\],\s*\\\$_SESSION\['active_period_id'\]/", $authGuard));

$erisimGovde = metotGovdesi('TenantContext', 'userCanAccessCompany');
kontrol('userCanAccessCompany() giriş yapılmamışsa sorgu bile atmadan false dönüyor (fail-closed)',
    (bool)preg_match('/userId\(\)\s*<=\s*0/', $erisimGovde));

// ═════════════════════════════════════════════════════════════════════
// 5) Kök neden C: erişilemeyen seçim her durumda temizleniyor
// ═════════════════════════════════════════════════════════════════════

$secimGovde = metotGovdesi('TenantContext', 'ensureActiveSelection');
kontrol('ensureActiveSelection() seçilebilir şirket yoksa oturumdaki seçimi TEMİZLİYOR (403 döngüsü kırıldı)',
    (bool)preg_match('/companyId\s*===\s*null\)\s*\{\s*self::clearActiveSelection\(\)/s', $secimGovde));
kontrol('ensureActiveSelection() kararı saf fonksiyona devrediyor (test edilebilirlik)',
    str_contains($secimGovde, 'resolveCompanySelection(') && str_contains($secimGovde, 'resolvePeriodSelection('));

$rotaGovde = metotGovdesi('TenantContext', 'requireActiveForRoute');
kontrol('requireActiveForRoute() artık ham 403 ile ölmüyor, seçimi temizleyip şirket seçim ekranına yönlendiriyor',
    !str_contains($rotaGovde, "die('Bu şirkete erişim yetkiniz yok.')")
    && str_contains($rotaGovde, 'clearActiveSelection()'));

$switchView = oku('app/views/companies/switch.php');
kontrol('Şirket Değiştir ekranı, seçilebilir aktif şirket yokken kullanıcıya ne yapacağını söylüyor (çıkmaz kaldırıldı)',
    str_contains($switchView, 'Girebileceğiniz aktif bir şirket yok'));
kontrol('Şirket Değiştir ekranında çıkış bağlantısı var (kullanıcı en azından çıkabilsin)',
    str_contains($switchView, '/cikis'));

// Seçim yazan tüm yollar setActiveSelection() kullanmalı — aksi halde seçim
// "sahipsiz" kalır ve bir sonraki istekte yok sayılır.
foreach (['app/controllers/CompanyController.php'] as $dosya) {
    $kaynak = oku($dosya);
    kontrol(basename($dosya) . ": oturuma doğrudan active_company_id yazmıyor (TenantContext::setActiveSelection kullanıyor)",
        !str_contains($kaynak, "\$_SESSION['active_company_id']")
        && str_contains($kaynak, 'TenantContext::setActiveSelection('));
}

// ═════════════════════════════════════════════════════════════════════
// 6) Kök neden D: sessiz 'owner' ataması kaldırıldı
// ═════════════════════════════════════════════════════════════════════

$defaultTenantGovde = metotGovdesi('TenantContext', 'ensureDefaultTenant');
kontrol('ensureDefaultTenant() artık her kullanıcıya sessizce owner vermiyor; sadece sistemde HİÇ atama yokken çalışıyor',
    (bool)preg_match('/SELECT id FROM user_companies LIMIT 1/', $defaultTenantGovde));
kontrol('ensureDefaultTenant() ilk kurulum atamasını sadece gerçek bir kullanıcı için yapıyor (userId > 0)',
    (bool)preg_match('/!\$anyAssignmentExists\s*&&\s*self::userId\(\)\s*>\s*0/', $defaultTenantGovde));

// ═════════════════════════════════════════════════════════════════════
// 7) Firmalar (şirket) ekranı bulguları
// ═════════════════════════════════════════════════════════════════════

$companyCtrl = oku('app/controllers/CompanyController.php');
kontrol('CompanyController::create()/store() artık yetki arıyor (Rbac kapsamı dışında olduğu için burada kontrol şart)',
    substr_count($companyCtrl, 'assertCanCreateCompany()') >= 3);
kontrol('CompanyController::delete() son aktif şirketin pasife alınmasını engelliyor (kimse giriş yapamaz duruma düşmez)',
    str_contains($companyCtrl, 'activeCompanyCount()'));
kontrol('CompanyController::delete() pasife alınan şirket seçiliyse oturum seçimini temizliyor',
    (bool)preg_match('/activeCompanyId\(\)\s*===\s*\$id\)\s*\{\s*TenantContext::clearActiveSelection/s', $companyCtrl));
kontrol('CompanyController::select_period() pasif şirketin dönemiyle "pasif şirket seçilemez" kuralının atlatılmasını engelliyor',
    (bool)preg_match("/select_period.*status'\]\s*!==\s*'active'/s", $companyCtrl));

require_once $kok . '/app/models/Company.php';
$updateCompanyGovde = metotGovdesi('Company', 'updateCompany');
kontrol('Company::updateCompany() status değerini doğruluyor (ENUM dışı değer sessizce yazılıp şirketi erişilmez yapamaz)',
    str_contains($updateCompanyGovde, 'normalizeStatus('));
kontrol('Company::updateCompany() son aktif şirketin düzenleme formundan pasifleştirilmesini de engelliyor',
    str_contains($updateCompanyGovde, 'activeCompanyCount()'));

$createCompanyGovde = metotGovdesi('Company', 'create');
kontrol('Company::create() status değerini doğruluyor',
    str_contains($createCompanyGovde, 'normalizeStatus('));

$companiesIndex = oku('app/views/companies/index.php');
kontrol('Firmalar listesinde yetkisi olmayan kullanıcıya "Düzenle/Pasife Al" butonları gösterilmiyor (ham 403 yerine gizleme)',
    str_contains($companiesIndex, 'TenantContext::canManageCompany('));
kontrol('Firmalar listesinde pasif şirketin "Seç" butonu tıklanamaz durumda',
    str_contains($companiesIndex, 'Pasif şirket seçilemez'));

// Rbac::isSuperAdmin() ZORUNLU bir $userId parametresi alır; argümansız çağrı
// ArgumentCountError ile sayfayı çökertir. Argümansız kullanım için
// AuthGuard::isSuperAdmin() sarmalayıcısı vardır. Bu tuzağı kalıcı olarak kapat.
require_once $kok . '/app/core/Rbac.php';
$rbacSuper = new ReflectionMethod('Rbac', 'isSuperAdmin');
kontrol('Ön koşul: Rbac::isSuperAdmin() zorunlu parametre alıyor (argümansız çağrı hatalıdır)',
    $rbacSuper->getNumberOfRequiredParameters() === 1);
foreach (['app/controllers', 'app/views', 'app/models', 'app/core'] as $dizin) {
    $bulunan = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($kok . '/' . $dizin));
    foreach ($it as $dosya) {
        if ($dosya->isFile() && $dosya->getExtension() === 'php') {
            $icerik = (string)@file_get_contents($dosya->getPathname());
            if (preg_match('/Rbac::isSuperAdmin\(\s*\)/', $icerik)) {
                $bulunan[] = $dosya->getFilename();
            }
        }
    }
    kontrol("{$dizin}: argümansız Rbac::isSuperAdmin() çağrısı yok (AuthGuard::isSuperAdmin() kullanılmalı)",
        empty($bulunan), implode(', ', $bulunan));
}

// ═════════════════════════════════════════════════════════════════════
// 8) Meta
// ═════════════════════════════════════════════════════════════════════

$kaynak = (string)@file_get_contents(__FILE__);
$kapali = false;
foreach (token_get_all($kaynak) as $token) {
    if (is_array($token) && $token[0] === T_CLOSE_TAG) {
        $kapali = true;
        break;
    }
}
kontrol('Meta: bu test dosyasında PHP kapanış etiketi yok', !$kapali);

// ═════════════════════════════════════════════════════════════════════
// Rapor
// ═════════════════════════════════════════════════════════════════════

$basarili = 0;
$basarisiz = [];
echo "=== Şirket/dönem seçimi + kullanıcı-şirket ataması regresyon testi ===\n\n";
foreach ($sonuclar as $s) {
    if ($s['ok']) {
        $basarili++;
        continue;
    }
    $basarisiz[] = $s;
}

echo "Toplam kontrol: " . count($sonuclar) . "\n";
echo "Başarılı:       {$basarili}\n";
echo "Başarısız:      " . count($basarisiz) . "\n\n";

if (empty($basarisiz)) {
    echo "PASSED - Tüm kontroller geçti.\n";
    exit(0);
}

echo "FAILED - Aşağıdaki kontroller başarısız:\n\n";
foreach ($basarisiz as $s) {
    echo "  - {$s['ad']}\n";
    if ($s['detay'] !== '') {
        echo "      {$s['detay']}\n";
    }
}
exit(1);
