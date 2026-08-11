<?php
/**
 * Regresyon testi: hiçbir controller action'ı GET ile state değiştiremesin.
 * --------------------------------------------------------------------------
 * Çalıştırma:  php tests/security/csrf_get_mutation_scan.php
 * Çıkış kodu:  0 = tüm kontroller PASSED, 1 = en az bir FAILED.
 *
 * Nasıl çalışır (manuel listeye BAĞLI DEĞİLDİR):
 *   1) app/controllers/*.php altındaki HER controller sınıfı Reflection ile
 *      taranır; sadece o sınıfın KENDİ tanımladığı PUBLIC metodlar (Router
 *      üzerinden fiilen çağrılabilenler) değerlendirilir.
 *   2) Her metod için "mutasyon mu?" sorusu, uygulamanın KENDİ üretim
 *      sınıflandırıcısı olan Rbac::requiredPermissionFor() /
 *      Rbac::classifyAction() çağrılarak cevaplanır — yani yeni bir
 *      controller/metod eklendiğinde bu test otomatik olarak onu da
 *      sınıflandırır; ayrı bakım gerektiren bir "riskli metod listesi" YOKTUR.
 *   3) Rbac'ın isim tabanlı sınıflandırması TEK BAŞINA yanıltıcıdır: "ekle"/
 *      "duzenle" hem "yeni kayıt formu GÖSTER" (GET, zararsız) hem de bazen
 *      gerçek bir yazma anlamına gelebilir — Csrf.php'deki koda dair yorum
 *      da bunu açıkça belirtiyor. Bu yüzden isim-tabanlı sınıflandırma tek
 *      başına yeterli sayılmaz; metodun GÖVDESİ de taranır: içinde çağrılan
 *      her `->metod(` çağrısının adı YİNE AYNI Rbac::classifyAction() ile
 *      sınıflandırılır. Çağrılan alt-metotlardan biri bile VIEW dışı bir
 *      şey döndürüyorsa (ör. ->ekle(, ->kaydet(, ->guncelle(, ->sil(,
 *      ->iptalEt(, ->create(, ->update(, ->delete(, ->resetPassword(,
 *      Audit::log(, ham SQL INSERT/UPDATE/DELETE) o giriş metodu GERÇEK
 *      bir mutasyon adayı sayılır. Sadece $this->view(...) çağıran salt
 *      form-gösterme metotları (ör. ekle()/duzenle() sayfaları) böylece
 *      otomatik olarak elenir — elle tutulan bir "güvenli metod listesi"
 *      YOKTUR, tamamı aynı isimlendirme kuralının gövdeye uygulanmasıdır.
 *   4) Gerçek mutasyon adayı olarak işaretlenen her metod için GERÇEK HTTP
 *      davranışı iki üretim fonksiyonu simüle edilerek doğrulanır:
 *        a) Csrf::isRequired($controller, $method) — $_SERVER['REQUEST_METHOD']
 *           'GET' iken çağrılır (Router'ın GET isteklerinde gerçekten
 *           kullandığı FONKSİYONUN AYNISI). *sil ile biten metodlar için bu
 *           bekçi zaten GET'te de CSRF zorunlu kıldığından tek başına yeterli
 *           sayılır.
 *        b) Metod gövdesinde (ReflectionMethod start/end satırları arasında)
 *           `REQUEST_METHOD` geçen bir HTTP-metod bekçisi var mı — bu,
 *           kod tabanındaki fiili POST-only koruma deseni ile birebir aynı
 *           imzadır (bkz. `if ($_SERVER['REQUEST_METHOD'] !== 'POST') {...}`).
 *      (a) VEYA (b) sağlanmıyorsa test o metod için FAILED verir.
 *
 * Bilinen sınır: statik bir kaynak-taraması olduğu için "REQUEST_METHOD
 * geçiyor ama mantık hatası yüzünden mutasyonu asıl engellemiyor" gibi akış
 * hatalarını YAKALAMAZ — sadece bekçinin YOKLUĞUNU kesin olarak yakalar.
 * Bu, Faz 1.5 kapsamında elle yapılan denetimle aynı imzayı otomatikleştirir.
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
define('ROOT_PATH', $root);
define('APP_PATH', $root . '/app');
define('CONTROLLERS_PATH', APP_PATH . '/controllers');
define('MODELS_PATH', APP_PATH . '/models');
define('VIEWS_PATH', APP_PATH . '/views');
define('CORE_PATH', APP_PATH . '/core');
define('BASE_URL', 'http://localhost');
define('APP_ENV', 'testing');

// Bu betik DB/oturuma dokunmadan sadece sınıf tanımlarını okur; Router.php
// hariç tüm çekirdek sınıflar require edilir (Router burada gerekmiyor).
foreach (['Controller', 'Csrf', 'Rbac'] as $cls) {
    require_once CORE_PATH . "/{$cls}.php";
}

// Rbac::classifyAction() private — Router'ın GERÇEKTEN kullandığı isimlendirme
// mantığını (manuel bir kopya değil, üretim kodunun kendisini) çağırmak için
// Reflection ile erişiyoruz.
$classifyAction = new ReflectionMethod('Rbac', 'classifyAction');
$classifyAction->setAccessible(true);

$mutatingActions = ['CREATE', 'UPDATE', 'DELETE', 'DISABLE', 'PASSWORD_RESET', 'SETTINGS_CHANGE'];

$controllerFiles = glob(CONTROLLERS_PATH . '/*Controller.php');
sort($controllerFiles);

$failures = [];
$checked = 0;
$mutatingCount = 0;

foreach ($controllerFiles as $file) {
    require_once $file;
    $className = basename($file, '.php');
    if (!class_exists($className, false)) {
        continue;
    }
    $rc = new ReflectionClass($className);
    if ($rc->isAbstract()) {
        continue;
    }

    foreach ($rc->getMethods(ReflectionMethod::IS_PUBLIC) as $rm) {
        // Yalnızca bu sınıfın KENDİ tanımladığı metodlar Router üzerinden
        // çağrılabilir; base Controller'dan miras alınanlar (view/redirect/
        // setFlash/model/getFlash) zaten protected, ama garanti olsun diye
        // yine de declaring class kontrolü yapılıyor.
        if ($rm->getDeclaringClass()->getName() !== $className) {
            continue;
        }
        if ($rm->isConstructor() || $rm->isStatic()) {
            continue;
        }
        $methodName = $rm->getName();
        $checked++;

        // 1) Rbac'ın KENDİ sınıflandırması (method-override + force-view +
        //    isim tabanlı classifyAction hepsi dahil).
        $permCode = Rbac::requiredPermissionFor($className, $methodName);
        if ($permCode !== null && $permCode !== '__RBAC_UNCATALOGUED__') {
            $action = substr($permCode, strrpos($permCode, '_') + 1);
        } else {
            // UNMANAGED_CONTROLLERS (Auth/Home/Website/Sitemap/Profil/Company/
            // Period/Dashboard) Rbac izin kodu üretmez — ama GET-mutasyon
            // riskinden MUAF DEĞİLDİR, bu yüzden isim tabanlı sınıflandırıcı
            // doğrudan çağrılıyor.
            $action = $classifyAction->invoke(null, $methodName);
        }

        if (!in_array($action, $mutatingActions, true)) {
            continue; // VIEW / read-only olarak sınıflandırıldı.
        }

        // Kaynak kodunu bir kere oku (hem gövde-mutasyon kontrolü hem de
        // HTTP-metod bekçisi kontrolü aynı satır aralığını kullanır).
        $start = $rm->getStartLine();
        $end   = $rm->getEndLine();
        $lines = file($file);
        $body  = implode('', array_slice($lines, $start - 1, $end - $start + 1));

        // "ekle"/"duzenle" gibi isimler hem form-gösterme hem gerçek yazma
        // anlamına gelebildiğinden (bkz. dosya başındaki açıklama), giriş
        // metodunun gövdesinde çağrılan her ->metod( ve statik Sinif::metod(
        // adı da AYNI classifyAction() ile sınıflandırılır; ayrıca bilinen
        // İngilizce CRUD fiilleri (UserAdmin/RoleAdmin gibi modellerde
         // kullanılıyor) ve ham SQL/Audit::log çağrıları da mutasyon kanıtı sayılır.
        $bodyIsRealMutation = false;
        if (preg_match_all('/(?:->|::)\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $body, $calls)) {
            foreach ($calls[1] as $callee) {
                if (in_array(strtolower($callee), ['create', 'update', 'delete', 'resetpassword', 'archive', 'setstatus'], true)) {
                    $bodyIsRealMutation = true;
                    break;
                }
                if ($classifyAction->invoke(null, $callee) !== 'VIEW') {
                    $bodyIsRealMutation = true;
                    break;
                }
            }
        }
        if (!$bodyIsRealMutation && preg_match('/\b(insert|update|softDelete)\s*\(/', $body)) {
            $bodyIsRealMutation = true;
        }
        if (!$bodyIsRealMutation && preg_match('/->query\s*\(\s*["\'][^"\']*\b(INSERT|UPDATE|DELETE)\b/i', $body)) {
            $bodyIsRealMutation = true;
        }

        if (!$bodyIsRealMutation) {
            continue; // Sadece form gösteriyor / salt okunur yardımcı çağrılar yapıyor.
        }
        $mutatingCount++;

        // 2a) Csrf::isRequired() üretim fonksiyonu — GET isteği simülasyonu.
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $csrfBlocksGet = Csrf::isRequired($className, $methodName);

        // 2b) Kaynak kodunda HTTP-metod bekçisi var mı.
        $hasMethodGuard = (bool)preg_match('/REQUEST_METHOD/', $body);

        if (!$csrfBlocksGet && !$hasMethodGuard) {
            $failures[] = [
                'controller' => $className,
                'method'     => $methodName,
                'action'     => $action,
                'file'       => str_replace($root . '/', '', $file),
                'line'       => $start,
            ];
        }
    }
}

echo "=== CSRF / GET-mutation regresyon taraması ===\n";
echo "Taranan controller dosyası: " . count($controllerFiles) . "\n";
echo "Taranan public metod:       {$checked}\n";
echo "Mutasyon olarak sınıflandırılan metod: {$mutatingCount}\n\n";

if (empty($failures)) {
    echo "PASSED - Mutasyon olarak sınıflandırılan {$mutatingCount} metodun tamamı GET isteğine karşı korunuyor.\n";
    exit(0);
}

echo "FAILED - " . count($failures) . " metod GET isteğiyle mutasyon yapabilir (ne HTTP-metod bekçisi ne de GET-CSRF koruması var):\n\n";
foreach ($failures as $f) {
    echo "  - {$f['controller']}::{$f['method']}() [{$f['action']}]  {$f['file']}:{$f['line']}\n";
}
exit(1);
