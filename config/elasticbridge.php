<?php

return [

    'driver' => env('SEARCH_DRIVER', 'elasticsearch'),

    /**
     * Authentication method for search client
     * Supported: basic-auth, api-key
     */
    'auth_method' => env('SEARCH_AUTH_METHOD', 'basic-auth'),

    /**
     * Search engine host(s)
     */
    'host' => [env('SEARCH_HOST', 'https://localhost:9200')],

    /**
     * Search engine username
     */
    'username' => env('SEARCH_USERNAME', 'elastic'),

    /**
     * Search engine password
     */
    'password' => env('SEARCH_PASSWORD', 'secret'),

    /**
     * API key authentication
     * When using auth_method => 'api-key'
     */
    'api_key' => env('SEARCH_API_KEY', null),

    /**
     * SSL certificate verification
     */
    'verify_ssl' => env('SEARCH_VERIFY_SSL', false),

    /**
     * Path to SSL certificate file
     */
    'certificate' => env('SEARCH_SSL_CERT', null),

    /**
     * Bridge files namespace
     */
    'namespace' => env('SEARCH_BRIDGE_NAMESPACE', 'App\\Bridges'),
];
