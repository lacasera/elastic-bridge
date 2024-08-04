<?php

namespace Lacasera\ElasticBridge\Builder;

use Lacasera\ElasticBridge\ElasticBridge;
use Lacasera\ElasticBridge\Query\QueryBuilder;

class BridgeBuilder implements BridgeBuilderInterface
{
    protected $bridge;

    protected QueryBuilder $query;

    public function __construct()
    {
        $this->query = app()->make(QueryBuilder::class);
    }

    public function setBridge(ElasticBridge $bridge): static
    {
        $this->bridge = $bridge;

        return $this;
    }
}
