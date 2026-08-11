<?php
$items = $tedarikciler ?? [];
$cariListConfig = [
    'route' => 'tedarikci',
    'module' => 'TEDARIKCI',
    'entityPlural' => 'Tedarikçiler',
    'entitySingular' => 'Tedarikçi',
    'nameHeader' => 'Tedarikçi Adı / Ünvanı',
    'totalLabel' => 'Toplam Tedarikçi',
    'activeLabel' => 'Aktif Tedarikçi',
    'thirdLabel' => 'Borçlu Tedarikçi',
    'thirdValueKey' => 'borclu_sayisi',
    'thirdIcon' => 'fa-file-invoice-dollar',
    'icon' => 'fa-industry',
    'rowIcon' => 'fa-industry',
    'emptyText' => 'Henüz tedarikçi kaydı yok.',
    'emptySearchText' => 'Aramanızla eşleşen tedarikçi bulunamadı.',
    'firstAddText' => 'İlk Tedarikçiyi Ekle',
    'addText' => 'Yeni Tedarikçi Ekle',
    'excelText' => 'Excelden Tedarikçi Yükle',
    'footerName' => 'tedarikçiden',
    'theme' => 'supplier',
];
require VIEWS_PATH . '/partials/cari_liste.php';
