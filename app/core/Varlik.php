<?php
/**
 * Varlık (statik dosya) adres yardımcısı
 * --------------------------------------------------------
 * CSS/JS/ikon gibi dosyalar güncellendiğinde tarayıcı ve service worker
 * eski kopyayı göstermeye devam edebiliyor. Adrese elle '?v=2' eklemek
 * unutulmaya açık bir yöntem; bunun yerine dosyanın değişiklik zamanını
 * (filemtime) otomatik ekliyoruz — dosya değişince adres de değişir,
 * değişmediği sürece önbellek geçerli kalır.
 *
 * Kullanım:  <link rel="stylesheet" href="<?= varlik('/css/panel-ui.css') ?>">
 */

if (!function_exists('varlik')) {
    /**
     * @param string $yol public/ dizinine göre yol (ör. '/css/panel-ui.css')
     * @return string BASE_URL ön ekli, sürüm damgalı tam adres
     */
    function varlik(string $yol): string
    {
        $yol = '/' . ltrim($yol, '/');
        $tam = ROOT_PATH . DIRECTORY_SEPARATOR . 'public' . str_replace('/', DIRECTORY_SEPARATOR, $yol);

        $damga = @filemtime($tam);
        $adres = (defined('BASE_URL') ? BASE_URL : '') . $yol;

        return $damga ? $adres . '?v=' . $damga : $adres;
    }
}
