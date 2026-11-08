<?php

declare(strict_types=1);

return [
    'uygulama' => [
        'ad' => 'Vampir Köylü',
        // Hareketsiz kalan oturum bu kadar saniye sonra kapanır.
        'oturum_zaman_asimi' => 1800,
        // true olursa beklenmeyen hatalarda ayrıntı gösterilir (yalnızca geliştirmede).
        'hata_ayrintisi' => false,
    ],

    // json (Aşama 2) ya da mysql (Aşama 3)
    'depolama' => 'json',

    'json' => [
        'klasor' => __DIR__ . '/../storage/veri',
    ],

    'mysql' => [
        'sunucu' => '127.0.0.1',
        'port' => 3306,
        'veritabani' => 'vampir_koylu',
        'kullanici' => 'root',
        'sifre' => '',
    ],

    // ['gece' => 25, 'gece_secim' => 15, 'gunduz' => 75, 'oylama' => 30]
    'sureler' => [],
];
