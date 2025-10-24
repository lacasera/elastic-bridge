<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Connection;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\AuthenticationException;
use Lacasera\ElasticBridge\Exceptions\MissingEnvException;
use Override;

class ElasticConnection implements ConnectionInterface
{
    private readonly Client $client;

    /**
     * @throws AuthenticationException
     */
    public function __construct()
    {
        $verifySsl = config('elasticbridge.verify_ssl', false);

        $clientBuilder = ClientBuilder::create()
            ->setSSLVerification($verifySsl)
            ->setHosts((array) config('elasticbridge.host'));

        if ($verifySsl) {

            if (is_null(config('elasticbridge.certificate'))) {
                throw new MissingEnvException('ELASTICSEARCH_SSL_CERT is required if verify_ssl is true');
            }

            $clientBuilder->setSSLCert(config('elasticbridge.certificate'));
        }

        $authMethod = (string) config('elasticbridge.auth_method', 'basic-auth');

        if ($authMethod === 'api-key') {
            $apiKey = config('elasticbridge.api_key');

            if (is_null($apiKey)) {
                throw new MissingEnvException('missing value for ELASTICSEARCH_API_KEY env');
            }

            if (is_string($apiKey)) {
                $clientBuilder->setApiKey($apiKey);
            }
        } else {
            $username = config('elasticbridge.username');
            $password = config('elasticbridge.password');

            if (is_string($username) && is_string($password)) {
                $clientBuilder->setBasicAuthentication($username, $password);
            }
        }

        $this->client = $clientBuilder->build();
    }

    /**
     * @throws AuthenticationException
     */
    #[Override]
    public function getClient(): Client
    {
        return $this->client;
    }
}
