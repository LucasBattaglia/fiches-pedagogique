<?php
// config/config.php

/*return [
    'db' => [
        'host'     => getenv('DB_HOST')     ?: 'localhost',
        'port'     => getenv('DB_PORT')     ?: '5432',
        'dbname'   => getenv('DB_NAME')     ?: 'fiches_pedagogiques',
        'user'     => getenv('DB_USER')     ?: 'postgres',
        'password' => getenv('DB_PASSWORD') ?: '',
    ],
    'app' => [
        'name'       => 'Fiches Pédagogiques',
        'url'        => getenv('APP_URL') ?: 'http://developpement.mapreparation.eduscol.org',
        'secret_key' => getenv('APP_SECRET') ?: 'fiches_pedag_secret_2025',
    ],
    'oauth' => [
        'google' => [
            'client_id'     => getenv('GOOGLE_CLIENT_ID')     ?: '',
            'client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: '',
            'redirect_uri'  => (getenv('APP_URL') ?: 'http://developpement.mapreparation.eduscol.org') . '/auth/google/callback',
        ],
    ],
    'session' => [
        'lifetime' => 86400 * 7,
    ],
];*/

return [
    'db' => [
        'host'     => 'localhost',
        'port'     => '5432',
        'dbname'   => 'fiches_pedagogiques',
        'user'     => 'postgres',
        'password' => 'RKtFj8mXTRdjVtlmAK1N',
    ],
    'app' => [
        'name'       => 'Fiches Pédagogiques',
        'url'        => 'http://developpement.mapreparation.eduscol.org',
        'secret_key' => 'fiches_pedag_secret_2025',
    ],
    'oauth' => [
        'google' => [
            'client_id'     => getenv('GOOGLE_CLIENT_ID')     ?: '',
            'client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: '',
            'redirect_uri'  => (getenv('APP_URL') ?: 'http://developpement.mapreparation.eduscol.org') . '/auth/google/callback',
        ],
    ],
    'session' => [
        'lifetime' => 86400 * 7,
    ],
];