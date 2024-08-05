<?php

/**
 * for more information visit
 * https://www.elastic.co/guide/en/elasticsearch/reference/current/run-elasticsearch-locally.html
 */
return [

    /**
     * elastic host
     */
    'host' => [env('ELASTICSEARCH_HOST', 'https://localhost:9200')],

    /**
     * elastic username
     */
    'username' => env('ELASTICSEARCH_USERNAME', 'elastic'),

    /**
     * elastic password
     */
    'password' => env('ELASTICSEARCH_PASSWORD', null),

    /**
     * path to certificate file generated when installing elastic
     * https://www.elastic.co/guide/en/elasticsearch/reference/current/targz.html#_use_the_ca_certificate
     */
    'certificate_path' => storage_path(),


    /**
     * where should bridge files be located
     */
    'namespace' => 'App\\Bridges',
];
