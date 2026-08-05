<?php

class FihristController extends Controller
{

    public function index()
    {
        $this->view('fihrist/index', [
            'pageTitle'   => 'Fihrist',
            'activeMenu'  => 'fihrist',
            'topbarTitle' => 'Fihrist',
            'topbarIcon'  => 'fa-solid fa-address-book'
        ]);
    }
}
