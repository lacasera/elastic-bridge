<?php

namespace Lacasera\ElasticBridge\Builder;

use Illuminate\Support\Traits\ForwardsCalls;
use Lacasera\ElasticBridge\ElasticBridge;
use Lacasera\ElasticBridge\Query\QueryBuilder;

/**
 * @template TModel of ElasticBridge
 */
class BridgeBuilder implements BridgeBuilderInterface
{
    use ForwardsCalls;

    /**
     * @var TModel
     */
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

    public function all()
    {
        echo 'hello there';
    }

    public function shouldMatch(string $field, $value, bool $boost = true): BridgeBuilder
    {
        $this->query->set('should', ['match' => [$field => $value]]);

        return $this;
    }

    public function shouldMatchAll(string $field, $value, bool $boost = true) {}

    public function mustMatch(string $field, $value): BridgeBuilder
    {
        $this->query->set('must', ['match' => [$field => $value]]);

        return $this;
    }

    public function matchAll()
    {
        echo 'in the match all';
    }

    public function matchNone() {}

    public function filter() {}

    public function get()
    {
        $params = [
            'index' => $this->bridge->getIndex(),
            'body' => $this->query->getPayload(),
        ];

        return $this->query->getConnection()->getClient()->search($params)->asArray();
    }
}
