<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Connection;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\AuthenticationException;
use Override;

class ElasticConnection implements ConnectionInterface
{
    private readonly Client $client;

    /**
     * @throws AuthenticationException
     */
    public function __construct()
    {
        $this->client = ClientBuilder::create()
            ->setBasicAuthentication(config('elasticbridge.username'), config('elasticbridge.password'))
            ->setHosts(config('elasticbridge.host'))
            ->setCABundle(config('elasticbridge.certificate_path'))
            ->build();
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
