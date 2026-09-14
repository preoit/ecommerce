<?php
return [
    'providers' => [
        'steadfast' => ['service'=>\App\Modules\Courier\Services\SteadfastCourierService::class, 'live'=>'https://portal.packzy.com/api/v1', 'sandbox'=>null, 'fields'=>['api_key','secret_key']],
        'pathao' => ['service'=>\App\Modules\Courier\Services\PathaoCourierService::class, 'live'=>'https://api-hermes.pathao.com', 'sandbox'=>'https://courier-api-sandbox.pathao.com', 'fields'=>['client_id','client_secret','store_id']],
    ],
];
