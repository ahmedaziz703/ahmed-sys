<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Broadcasting
    |--------------------------------------------------------------------------
    |
    | By uncommenting the Laravel Echo configuration, you may connect Filament
    | to any Pusher-compatible websockets server.
    |
    | This will allow your users to receive real-time notifications.
    |
    */

    'broadcasting' => [

        // 'echo' => [
        //     'broadcaster' => 'pusher',
        //     'key' => env('VITE_PUSHER_APP_KEY'),
        //     'cluster' => env('VITE_PUSHER_APP_CLUSTER'),
        //     'wsHost' => env('VITE_PUSHER_HOST'),
        //     'wsPort' => env('VITE_PUSHER_PORT'),
        //     'wssPort' => env('VITE_PUSHER_PORT'),
        //     'authEndpoint' => '/broadcasting/auth',
        //     'disableStats' => true,
        //     'encrypted' => true,
        //     'forceTLS' => true,
        // ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | This is the storage disk Filament will use to store files. You may use
    | any of the disks defined in the `config/filesystems.php`.
    |
    */

    'default_filesystem_disk' => env('FILAMENT_FILESYSTEM_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Assets Path
    |--------------------------------------------------------------------------
    |
    | This is the directory where Filament's assets will be published to. It
    | is relative to the `public` directory of your Laravel application.
    |
    | After changing the path, you should run `php artisan filament:assets`.
    |
    */

    'assets_path' => null,

    /*
    |--------------------------------------------------------------------------
    | Cache Path
    |--------------------------------------------------------------------------
    |
    | This is the directory that Filament will use to store cache files that
    | are used to optimize the registration of components.
    |
    | After changing the path, you should run `php artisan filament:cache-components`.
    |
    */

    'cache_path' => base_path('bootstrap/cache/filament'),

    /*
    |--------------------------------------------------------------------------
    | Livewire Loading Delay
    |--------------------------------------------------------------------------
    |
    | This sets the delay before loading indicators appear.
    |
    | Setting this to 'none' makes indicators appear immediately, which can be
    | desirable for high-latency connections. Setting it to 'default' applies
    | Livewire's standard 200ms delay.
    |
    */

    'livewire_loading_delay' => 'default',

    'colors' => [
        'danger' => [
            50 => '254 242 242',   // red-50
            100 => '254 226 226',  // red-100
            200 => '254 202 202',  // red-200
            300 => '252 165 165',  // red-300
            400 => '248 113 113',  // red-400
            500 => '239 68 68',    // red-500
            600 => '220 38 38',    // red-600
            700 => '185 28 28',    // red-700
            800 => '153 27 27',    // red-800
            900 => '127 29 29',    // red-900
        ],
        'primary' => [
            50 => '239 246 255',   // blue-50
            100 => '219 234 254',  // blue-100
            200 => '191 219 254',  // blue-200
            300 => '147 197 253',  // blue-300
            400 => '96 165 250',   // blue-400
            500 => '59 130 246',   // blue-500
            600 => '37 99 235',    // blue-600
            700 => '29 78 216',    // blue-700
            800 => '30 64 175',    // blue-800
            900 => '30 58 138',    // blue-900
        ],
        'success' => [
            50 => '240 253 244',   // green-50
            100 => '220 252 231',  // green-100
            200 => '187 247 208',  // green-200
            300 => '134 239 172',  // green-300
            400 => '74 222 128',   // green-400
            500 => '34 197 94',    // green-500
            600 => '22 163 74',    // green-600
            700 => '21 128 61',    // green-700
            800 => '22 101 52',    // green-800
            900 => '20 83 45',     // green-900
        ],
        'warning' => [
            50 => '254 252 232',   // yellow-50
            100 => '254 249 195',  // yellow-100
            200 => '254 240 138',  // yellow-200
            300 => '253 224 71',   // yellow-300
            400 => '250 204 21',   // yellow-400
            500 => '234 179 8',    // yellow-500
            600 => '202 138 4',    // yellow-600
            700 => '161 98 7',     // yellow-700
            800 => '133 77 14',    // yellow-800
            900 => '113 63 18',    // yellow-900
        ],
    ],

  
];
