<?php

return [
    'host' => env('FTP_HOST', ''),
    'username' => env('FTP_USERNAME', ''),
    'password' => env('FTP_PASSWORD', ''),
    'remote_path' => env('FTP_REMOTE_PATH', '/'),
    'port' => env('FTP_PORT', 21),
];