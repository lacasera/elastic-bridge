<?php

namespace Lacasera\ElasticBridge\Connection;

use Elastic\Elasticsearch\ClientInterface;

interface ConnectionInterface
{
    public function getClient(): ClientInterface;
}
