<?php
/**
 * Controller: TeklifController
 * --------------------------------------------------------
 * Teklifler (proforma) listesi — Fatura modelindeki belge_tipi='proforma'
 * kayıtlarını gösterir. Ayrı bir "teklif" tablosu/modeli yok; proforma
 * belgeler stok ve cari bakiyesini etkilemez (Fatura::STOK_ETKILEYEN_TIPLER
 * ve recomputeCariBalance() bu tipi kapsamaz), bu yüzden mevcut fatura
 * altyapısını güvenle yeniden kullanır.
 */
require_once MODELS_PATH . '/Fatura.php';

class TeklifController extends Controller
{
    private Fatura $fatura;

    public function __construct()
    {
        $this->fatura = new Fatura();
    }

    public function index(): void
    {
        $limit  = 50;
        $sayfa  = max(1, (int)($_GET['sayfa'] ?? 1));
        $arama  = trim($_GET['ara'] ?? '');
        $offset = ($sayfa - 1) * $limit;

        $toplam      = $this->fatura->say('proforma', $arama, '', 'tumu');
        $teklifler   = $this->fatura->listele('proforma', $arama, '', 'tumu', false, $limit, $offset);
        $sayfaSayisi = max(1, (int)ceil($toplam / $limit));

        $this->view('teklifler/index', [
            'pageTitle'   => 'Teklifler',
            'activeMenu'  => 'teklifler',
            'topbarTitle' => 'Teklifler',
            'topbarIcon'  => 'fa-solid fa-folder-open',
            'teklifler'   => $teklifler,
            'toplam'      => $toplam,
            'arama'       => $arama,
            'sayfa'       => $sayfa,
            'sayfaSayisi' => $sayfaSayisi,
        ]);
    }
}
