<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Connection;

use Aws\Credentials\CredentialProvider;
use Lacasera\ElasticBridge\Exceptions\MissingEnvException;
use OpenSearch\Client;
use OpenSearch\GuzzleClientFactory;
use Override;
use RuntimeException;

class OpenSearchConnection implements ConnectionInterface
{
    private readonly Client $client;

    public function __construct()
    {
        $this->client = (new GuzzleClientFactory)->create($this->buildOptions());
    }

    #[Override]
    public function search(array $params): array
    {
        return $this->client->search($params);
    }

    #[Override]
    public function count(array $params): array
    {
        return $this->client->count($params);
    }

    #[Override]
    public function index(array $params): array
    {
        return $this->client->index($params);
    }

    #[Override]
    public function update(array $params): bool
    {
        $result = $this->client->update($params);

        return in_array(data_get($result, 'result'), ['updated', 'created', 'noop'], true);
    }

    /**
     * Escape hatch for advanced use — not part of ConnectionInterface.
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * Build the PSR-18 (Guzzle) client options from configuration.
     *
     * @return array<string, mixed>
     */
    private function buildOptions(): array
    {
        $hosts = (array) config('elasticbridge.host');

        // OpenSearch's PSR-18 client is single-endpoint by design (no client-side
        // node pool), so only the first host is used. Front a multi-node cluster
        // with a managed endpoint or load balancer for high availability.
        $options = [
            'base_uri' => (string) ($hosts[0] ?? 'https://localhost:9200'),
            'verify' => $this->sslVerification(),
        ];

        return array_merge($options, $this->authOptions());
    }

    private function sslVerification(): false|string
    {
        if (! config('elasticbridge.verify_ssl', false)) {
            return false;
        }

        if (is_null(config('elasticbridge.certificate'))) {
            throw new MissingEnvException('SEARCH_SSL_CERT is required if verify_ssl is true');
        }

        return (string) config('elasticbridge.certificate');
    }

    /**
     * @return array<string, mixed>
     */
    private function authOptions(): array
    {
        $authMethod = (string) config('elasticbridge.auth_method', 'basic-auth');

        if ($authMethod === 'sigv4') {
            return ['auth_aws' => $this->sigV4Options()];
        }

        if ($authMethod === 'api-key') {
            $apiKey = config('elasticbridge.api_key');

            if (is_null($apiKey)) {
                throw new MissingEnvException('missing value for SEARCH_API_KEY env');
            }

            return ['headers' => ['Authorization' => 'ApiKey '.$apiKey]];
        }

        $username = config('elasticbridge.username');
        $password = config('elasticbridge.password');

        if (is_string($username) && is_string($password)) {
            return ['auth' => [$username, $password]];
        }

        return [];
    }

    /**
     * @return array<string, string>
     */
    private function sigV4Options(): array
    {
        $region = config('elasticbridge.sig_v4.region');

        if (is_null($region)) {
            throw new MissingEnvException('SEARCH_AWS_REGION is required for SigV4 authentication');
        }

        if (! class_exists(CredentialProvider::class)) {
            throw new RuntimeException(
                'The aws/aws-sdk-php package is required for OpenSearch SigV4 authentication. '.
                'Install it with: composer require aws/aws-sdk-php'
            );
        }

        return [
            'region' => (string) $region,
            'service' => (string) config('elasticbridge.sig_v4.service', 'es'),
        ];
    }
}
