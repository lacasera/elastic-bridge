<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Connection;

use Elastic\Elasticsearch\Client;

interface ConnectionInterface
{
    public function getClient(): Client;
}
