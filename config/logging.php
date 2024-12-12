<?php

return [
    'default' => env('LOG_CHANNEL', 'stack'),

    'channels' => [
        // Canale principale
        'stack' => [
            'driver' => 'stack',
            'channels' => ['daily'],
        ],

        // Canale per i caricamenti
        'upload' => [
            'driver' => 'daily',
            'path' => storage_path('logs/upload.log'),
            'level' => 'debug',
            'days' => 14,
        ],

        // Canale per eventi e listener
        'event' => [
            'driver' => 'daily',
            'path' => storage_path('logs/event.log'),
            'level' => 'debug',
            'days' => 14,
        ],

        // Canale per i job
        'job' => [
            'driver' => 'daily',
            'path' => storage_path('logs/job.log'),
            'level' => 'debug',
            'days' => 14,
        ],

        // Canale per la generazione del file AX
        'ax_export' => [
            'driver' => 'daily',
            'path' => storage_path('logs/ax_export.log'),
            'level' => 'debug',
            'days' => 14,
        ],
        'listener' => [
            'driver' => 'daily',
            'path' => storage_path('logs/listener.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],
        'sftp' => [
        'driver' => 'daily',
        'path' => storage_path('logs/sftp.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 14,
    ],
    ],
];
