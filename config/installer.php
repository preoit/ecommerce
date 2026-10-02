<?php

return [
    'lock_file' => storage_path('app/private/installed.json'),
    'progress_file' => storage_path('app/private/installing.json'),
    'bootstrap_key_file' => storage_path('app/private/installer.key'),
    'key_is_temporary' => false,
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
