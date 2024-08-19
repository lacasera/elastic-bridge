<?php

namespace Lacasera\ElasticBridge\Connection;

use Elastic\Elasticsearch\Client;

interface ConnectionInterface
{
    public function getClient(): Client;
}
