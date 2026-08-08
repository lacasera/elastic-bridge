<?php

/**
 * ElasticBridge works with both Elasticsearch and OpenSearch. The connection
 * settings below are backend-agnostic and shared by both drivers.
 */
return [

    /**
     * Search backend driver
     * Supported: elasticsearch, opensearch
     */
    'driver' => env('SEARCH_DRIVER', 'elasticsearch'),

    /**
     * Authentication method for the search client
     * Supported: basic-auth, api-key, sigv4 (opensearch only)
     */
    'auth_method' => env('SEARCH_AUTH_METHOD', 'basic-auth'),

    /**
     * Search cluster host(s)
     *
     * Accepts a comma-separated list, e.g. SEARCH_HOST="https://a:9200,https://b:9200".
     * Elasticsearch load-balances across all listed hosts (round-robin + failover);
     * OpenSearch uses the first host only — its client is single-endpoint by design,
     * so point it at a managed endpoint or a load balancer in front of the cluster.
     */
    'host' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('SEARCH_HOST', 'https://localhost:9200'))
    ))),

    /**
     * Basic-auth username
     */
    'username' => env('SEARCH_USERNAME', 'elastic'),

    /**
     * Basic-auth password
     */
    'password' => env('SEARCH_PASSWORD', null),

    /**
     * API key auth
     * When using auth_method => 'api-key', set this value.
     */
    'api_key' => env('SEARCH_API_KEY', null),

    /**
     * Should the client verify the SSL certificate during connection
     * SEARCH_SSL_CERT is required if set to true
     */
    'verify_ssl' => env('SEARCH_VERIFY_SSL', false),

    /**
     * Path to the CA certificate file generated when installing the cluster
     */
    'certificate' => env('SEARCH_SSL_CERT', null),

    /**
     * AWS SigV4 signing (OpenSearch only, when auth_method => 'sigv4')
     * Requires the aws/aws-sdk-php package: composer require aws/aws-sdk-php
     */
    'sig_v4' => [
        'region' => env('SEARCH_AWS_REGION'),
        'service' => env('SEARCH_AWS_SERVICE', 'es'), // 'es' (managed) | 'aoss' (serverless)
    ],

    /**
     * Bulk insert / upsert limits
     * - max: hard cap on documents accepted by bulk()/upsert() in a single call
     * - chunk_size: documents per bulk request when the input is chunked
     */
    'bulk' => [
        'max' => env('SEARCH_BULK_MAX', 10000),
        'chunk_size' => env('SEARCH_BULK_CHUNK_SIZE', 500),
    ],

    /**
     * where should bridge files be located
     */
    'namespace' => 'App\\Bridges',
];
