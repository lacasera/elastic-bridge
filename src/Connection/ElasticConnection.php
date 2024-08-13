<?php

namespace Lacasera\ElasticBridge\Connection;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\AuthenticationException;

class ElasticConnection implements ConnectionInterface
{
    protected string $index;

    private Client $connection;

    /**
     * @throws \Elastic\Elasticsearch\Exception\AuthenticationException
     */
    public function __construct()
    {
        $this->connection = ClientBuilder::create()
            ->setBasicAuthentication(config('elasticbridge.username'), config('elasticbridge.password'))
            ->setHosts(config('elasticbridge.host'))
            ->setCABundle(config('elasticbridge.certificate_path'))
            ->build();
    }

    /**
     * @throws AuthenticationException
     */
    public function getClient(): Client
    {
        return $this->connection;
    }
}
