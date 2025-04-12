<?php

/**
 * for more information visit
 * https://www.elastic.co/guide/en/elasticsearch/reference/current/run-elasticsearch-locally.html
 */
return [

    /**
     * elastic search hosts.
     * you can separate them by comma's if you have multiple hosts
     * https://localhost:9200,https://localhost:93000
     */
    'hosts' => env('ELASTICSEARCH_HOSTS'),

    /**
     * elasticsearch api key
     */
    'api_key' => env('ELASTICSEARCH_API_KEY'),

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
