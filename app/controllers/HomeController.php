<?php
/**
 * HomeController
 * --------------------------------------------------------
 * Eski /home rotası artık çok dilli public siteye yönlendirir.
 * /tr varsayılan; tarayıcı dil tercihi varsa /en veya /ru.
 *
 * Yeni public site: WebsiteController + /tr /en /ru rotaları.
 */
class HomeController extends Controller
{
    public function index(): void
    {
        $accept = strtolower((string)($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));
        if     (str_starts_with($accept, 'ru')) $loc = 'ru';
        elseif (str_starts_with($accept, 'en')) $loc = 'en';
        else                                    $loc = 'tr';

        header('Location: ' . BASE_URL . '/' . $loc, true, 302);
        exit;
    }
}
