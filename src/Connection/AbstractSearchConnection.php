<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Connection;

use Lacasera\ElasticBridge\Contracts\SearchConnectionInterface;

abstract class AbstractSearchConnection implements SearchConnectionInterface
{
    protected array $config;

    protected mixed $client;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->client = $this->createClient();
    }

    /**
     * Create the search engine client
     */
    abstract protected function createClient(): mixed;

    /**
     * Get the underlying client instance
     */
    public function getClient(): mixed
    {
        return $this->client;
    }

    /**
     * Get configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Build authentication configuration
     */
    protected function buildAuthConfig(): array
    {
        $authMethod = $this->config['auth_method'] ?? 'basic-auth';

        return match ($authMethod) {
            'api-key' => $this->buildApiKeyAuth(),
            'basic-auth' => $this->buildBasicAuth(),
            default => []
        };
    }

    /**
     * Build API key authentication
     */
    protected function buildApiKeyAuth(): array
    {
        if (empty($this->config['api_key'])) {
            return [];
        }

        return [
            'Authorization' => 'ApiKey '.$this->config['api_key'],
        ];
    }

    /**
     * Build basic authentication
     */
    protected function buildBasicAuth(): array
    {
        $username = $this->config['username'] ?? null;
        $password = $this->config['password'] ?? null;

        if (empty($username) || empty($password)) {
            return [];
        }

        return [
            'Authorization' => 'Basic '.base64_encode($username.':'.$password),
        ];
    }

    /**
     * Build SSL configuration
     */
    protected function buildSslConfig(): array
    {
        $sslConfig = [];

        if (isset($this->config['verify_ssl'])) {
            $sslConfig['verify'] = $this->config['verify_ssl'];
        }

        if (! empty($this->config['certificate'])) {
            $sslConfig['cert'] = $this->config['certificate'];
        }

        return $sslConfig;
    }
}
