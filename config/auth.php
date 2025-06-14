<?php

return [

    'defaults' => [
        'guard' => 'api',  // Usa 'api' como el guard predeterminado
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver' => 'jwt',
            'provider' => 'users',
        ],

        // Asegúrate de que este guard esté configurado correctamente para JWT
        'api' => [
            'driver' => 'jwt',  // Este guard usa 'jwt' como driver
            'provider' => 'users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],
];
