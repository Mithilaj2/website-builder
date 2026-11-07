<?php
// Reference only. public/setup.php writes config/config.php for you.
// If you configure manually: copy to config/config.php, fill in values, and set permissions to 600.
return [
    'app' => [
        'name' => 'Channel Softech Website Builder',
        'base_url' => 'https://builder.example.com', // no trailing slash
        'debug' => false,
        'timezone' => 'Asia/Kolkata',
        'key' => '', // base64_encode(random_bytes(32))
    ],
    'db' => [
        'host' => 'localhost', 'port' => 3306,
        'name' => 'cpaneluser_wbmaster', 'user' => 'cpaneluser_wb', 'pass' => '',
        'charset' => 'utf8mb4',
    ],
    'session' => ['name' => 'CSWBSESSID', 'idle_timeout' => 1800],
    // Developers only: local imitation of the payment gateways (tools/payment-emulator.php) used by
    // generated websites in TEST mode. Never set this on a production builder.
    // 'payment_emulator' => 'http://127.0.0.1:8090',
];
