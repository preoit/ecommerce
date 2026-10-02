<?php

return [
    'lock_file' => storage_path('app/private/installed.json'),
    'progress_file' => storage_path('app/private/installing.json'),
    'required_php' => '8.3.0',
    'required_extensions' => [
        'ctype',
        'curl',
        'dom',
        'fileinfo',
        'filter',
        'hash',
        'mbstring',
        'openssl',
        'pdo',
        'pdo_mysql',
        'session',
        'tokenizer',
        'xml',
    ],
    'testing_uninstalled' => false,
];
