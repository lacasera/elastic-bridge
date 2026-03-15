<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Connection;

use Lacasera\ElasticBridge\Exceptions\MissingEnvException;
use OpenSearch\Client;
use OpenSearch\ClientBuilder;

class OpenSearchConnection extends AbstractSearchConnection
{
    protected function createClient(): Client
    {
        $verifySsl = $this->config['verify_ssl'] ?? false;

        $clientBuilder = ClientBuilder::create()
            ->setSSLVerification($verifySsl)
            ->setHosts((array) $this->config['host']);

        if ($verifySsl) {
            if (empty($this->config['certificate'])) {
                throw new MissingEnvException('SEARCH_SSL_CERT is required if verify_ssl is true');
            }

            $clientBuilder->setSSLCert($this->config['certificate']);
        }

        $authMethod = $this->config['auth_method'] ?? 'basic-auth';

        // For now, OpenSearch connection supports basic authentication
        // API key support can be added later based on OpenSearch documentation
        if ($authMethod === 'basic-auth') {
            $username = $this->config['username'] ?? null;
            $password = $this->config['password'] ?? null;

            if (! empty($username) && ! empty($password)) {
                $clientBuilder->setBasicAuthentication($username, $password);
            }
        }

        return $clientBuilder->build();
    }

    public function search(array $params): array
    {
        return $this->client->search($params);
    }

    public function index(array $params): array
    {
        return $this->client->index($params);
    }

    public function get(array $params): array
    {
        return $this->client->get($params);
    }

    public function delete(array $params): array
    {
        return $this->client->delete($params);
    }

    public function indexExists(string $index): bool
    {
        return $this->client->indices()->exists(['index' => $index]);
    }

    public function createIndex(string $index, array $body = []): array
    {
        $params = ['index' => $index];
        if (! empty($body)) {
            $params['body'] = $body;
        }

        return $this->client->indices()->create($params);
    }

    public function deleteIndex(string $index): array
    {
        return $this->client->indices()->delete(['index' => $index]);
    }

    public function info(): array
    {
        return $this->client->info();
    }

    public function bulk(array $params): array
    {
        return $this->client->bulk($params);
    }

    public function count(array $params): array
    {
        return $this->client->count($params);
    }

    public function update(array $params): array
    {
        return $this->client->update($params);
    }
}
