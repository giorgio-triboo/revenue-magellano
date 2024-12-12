<?php

return [
    'roles' => [
        'admin' => [
            'name' => 'Administrator',
            'permissions' => ['*'] // accesso completo
        ],
        'operative' => [
            'name' => 'Operative',
            'permissions' => [
                'reports.view',
                'reports.create',
                'reports.edit',
                'publishers.view'
            ]
        ],
        'publisher' => [
            'name' => 'Publisher',
            'permissions' => [
                'reports.view',
                'publisher.profile.edit'
            ]
        ]
    ]
];