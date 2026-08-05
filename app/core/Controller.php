<?php
/**
 * Controller (Base Class)
 * --------------------------------------------------------
 * Tüm kontrolcülerin miras aldığı temel sınıf.
 * View render, model yükleme ve redirect yardımcıları sağlar.
 */

abstract class Controller
{
    /**
     * Bir modeli yükler ve örneğini döner.
     */
    protected function model(string $name): object
    {
        $file = MODELS_PATH . DIRECTORY_SEPARATOR . $name . '.php';

        if (!file_exists($file)) {
            throw new RuntimeException("Model bulunamadı: {$name}");
        }

        require_once $file;
        return new $name();
    }

    /**
     * View render — layout içine gömer.
     *
     * @param string $view   "dashboard/index" gibi dizin/dosya
     * @param array  $data   View'e aktarılacak değişkenler
     * @param string $layout Layout dosyası (varsayılan: main)
     */
    protected function view(string $view, array $data = [], string $layout = 'main'): void
    {
        $viewFile   = VIEWS_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $view) . '.php';
        $layoutFile = VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . $layout . '.php';

        if (!file_exists($viewFile)) {
            throw new RuntimeException("View bulunamadı: {$view}");
        }
        if (!file_exists($layoutFile)) {
            throw new RuntimeException("Layout bulunamadı: {$layout}");
        }

        // View değişkenlerini parçala (XSS için view'lerde htmlspecialchars kullanılmalı)
        extract($data, EXTR_SKIP);

        // İçeriği tampona yaz, sonra layout'a ver
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        require $layoutFile;
    }

    /**
     * Redirect helper.
     */
    protected function redirect(string $path): void
    {
        header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
        exit;
    }

    /**
     * Flash mesaj seti
     */
    protected function setFlash(string $tip, string $mesaj): void
    {
        $_SESSION['flash'] = ['tip' => $tip, 'mesaj' => $mesaj];
    }

    /**
     * Flash mesajı al ve temizle
     */
    protected function getFlash(): array
    {
        $flash = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $flash;
    }
}
