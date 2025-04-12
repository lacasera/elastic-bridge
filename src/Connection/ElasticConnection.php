<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Connection;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\AuthenticationException;

class ElasticConnection implements ConnectionInterface
{
    private Client $connection;

    /**
     * @throws \Elastic\Elasticsearch\Exception\AuthenticationException
     */
    public function __construct()
    {
        $conn = ClientBuilder::create()
            ->setApiKey(config('elasticbridge.api_key'))
            ->setHosts(explode(',', config('elasticbridge.hosts')));

        if (config('elasticbridge.certificate_path')) {
            $conn->setCABundle(config('elasticbridge.certificate_path'));
        }

        $this->connection = $conn->build();
    }

    /**
     * @throws AuthenticationException
     */
    public function getClient(): Client
    {
        return $this->connection;
    }
}
