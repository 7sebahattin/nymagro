<?php
/**
 * AuthController
 * --------------------------------------------------------
 * Login / Logout işlemleri
 *
 * URL'ler:
 *   /giris        → form (GET) | login işlemi (POST)
 *   /cikis        → oturum kapat
 *   /auth/login   → form (GET)
 *   /auth/giris   → POST endpoint
 */
class AuthController extends Controller
{
    public function index(): void
    {
        $this->login();
    }

    public function login(): void
    {
        if (AuthGuard::isLoggedIn()) {
            $this->redirect('dashboard');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $next     = $this->normalizeNext($_POST['next'] ?? '');

            $result = AuthGuard::attempt($username, $password);
            if ($result['ok']) {
                $target = $next !== '' ? $next : 'dashboard';
                // Güvenlik: tam URL veya başka host'a yönlendirme yapma
                if (preg_match('#^https?://#i', $target) || str_starts_with($target, '//')) {
                    $target = 'dashboard';
                }
                $this->redirect($target);
            }

            $this->renderLogin($result['message'], $username, $next);
            return;
        }

        $next = $this->normalizeNext($_GET['next'] ?? '');
        $this->renderLogin(null, '', $next);
    }

    public function logout(): void
    {
        AuthGuard::logout();
        $this->redirect('giris');
    }

    private function renderLogin(?string $error, string $username, string $next): void
    {
        // Auth sayfası özel layout'la render edilir (sidebar/topbar yok)
        $viewFile = VIEWS_PATH . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'login.php';
        if (!file_exists($viewFile)) {
            throw new RuntimeException('Login view bulunamadı.');
        }

        $error    = $error;
        $username = htmlspecialchars($username);
        $next     = htmlspecialchars($next);
        require $viewFile;
    }

    private function normalizeNext(mixed $next): string
    {
        $next = trim((string)$next);
        if ($next === '' || preg_match('#^https?://#i', $next) || str_starts_with($next, '//')) {
            return '';
        }

        $parts = parse_url($next);
        $path  = isset($parts['path']) ? str_replace('\\', '/', (string)$parts['path']) : '';
        $query = isset($parts['query']) ? (string)$parts['query'] : '';
        $path  = ltrim($path, '/');

        $basePath = (string)(parse_url(BASE_URL, PHP_URL_PATH) ?? '');
        $baseName = trim(basename($basePath), '/');
        foreach (array_filter([$baseName, 'public']) as $prefix) {
            while (strtolower(strtok($path, '/')) === strtolower($prefix)) {
                $path = ltrim(substr($path, strlen(strtok($path, '/'))), '/');
            }
        }

        $firstSegment = strtolower(strtok($path, '/') ?: '');
        if ($path === '' || in_array($firstSegment, ['giris', 'cikis', 'auth'], true)) {
            return '';
        }

        return $path . ($query !== '' ? '?' . $query : '');
    }
}
