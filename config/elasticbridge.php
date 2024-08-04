<?php

return [

    'host' => env('ELASTICSEARCH_HOST', 'https://localhost:9200'),

    'username' => env('ELASTICSEARCH_USERNAME', 'elastic'),

    'password' => env('ELASTICSEARCH_PASSWORD', null),

    'certificate_path' => storage_path(),
];
