<?php

class TeklifController extends Controller
{

    public function index()
    {
        $this->view('teklifler/index', [
            'pageTitle'   => 'Teklifler',
            'activeMenu'  => 'teklifler',
            'topbarTitle' => 'Teklifler',
            'topbarIcon'  => 'fa-solid fa-folder-open'
        ]);
    }
}
