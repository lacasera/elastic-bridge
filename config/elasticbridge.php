<?php

/**
 * for more information visit
 * https://www.elastic.co/guide/en/elasticsearch/reference/current/run-elasticsearch-locally.html
 */
return [

    /**
     * Authentication method for Elasticsearch client
     * Supported: basic-auth, api-key
     */
    'auth_method' => env('ELASTICSEARCH_AUTH_METHOD', 'basic-auth'),

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
     * API key auth
     * When using auth_method => 'api-key', set either:
     */
    'api_key' => env('ELASTICSEARCH_API_KEY', null),

    /**
     * Should elastic verify ssl certificate during connection
     * ELASTICSEARCH_SSL_CERT is required if set to true
     */
    'verify_ssl' => env('ELASTICSEARCH_VERIFY_SSL', false),

    /**
     * path to certificate file generated when installing elastic
     * https://www.elastic.co/guide/en/elasticsearch/reference/current/targz.html#_use_the_ca_certificate
     */
    'certificate' => env('ELASTICSEARCH_SSL_CERT', null),

    /**
     * where should bridge files be located
     */
    'namespace' => 'App\\Bridges',
];
