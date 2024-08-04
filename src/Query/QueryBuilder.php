<?php

namespace Lacasera\ElasticBridge\Query;

use Lacasera\ElasticBridge\Connection\ConnectionInterface;

class QueryBuilder
{
    public function __construct(public ConnectionInterface $connection) {}
}
