<?php
$items = $musteriler ?? [];
$cariListConfig = [
    'route' => 'musteri',
    'entityPlural' => 'Müşteriler',
    'entitySingular' => 'Müşteri',
    'nameHeader' => 'İsim / Unvan',
    'totalLabel' => 'Toplam Müşteri',
    'activeLabel' => 'Aktif Müşteri',
    'thirdLabel' => 'E-ticaret Entegre',
    'thirdValueKey' => 'eticaret_sayisi',
    'thirdIcon' => 'fa-cart-shopping',
    'icon' => 'fa-users',
    'rowIcon' => 'fa-user',
    'emptyText' => 'Henüz müşteri kaydı yok.',
    'emptySearchText' => 'Aramanızla eşleşen müşteri bulunamadı.',
    'firstAddText' => 'İlk Müşteriyi Ekle',
    'addText' => 'Yeni Müşteri Ekle',
    'excelText' => 'Excelden Müşteri Yükle',
    'footerName' => 'müşteriden',
    'theme' => 'customer',
];
require VIEWS_PATH . '/partials/cari_liste.php';
