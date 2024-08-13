<?php

namespace Lacasera\ElasticBridge\Builder;

use Illuminate\Support\Traits\ForwardsCalls;
use Lacasera\ElasticBridge\Concerns\SetsTerm;
use Lacasera\ElasticBridge\ElasticBridge;
use Lacasera\ElasticBridge\Query\QueryBuilder;

class BridgeBuilder implements BridgeBuilderInterface
{
    use ForwardsCalls;
    use SetsTerm;

    protected $bridge;

    protected QueryBuilder $query;

    public function __construct()
    {
        $this->query = app()->make(QueryBuilder::class);
    }

    /**
     * @param ElasticBridge $bridge
     * @return $this
     */
    public function setBridge(ElasticBridge $bridge): static
    {
        $this->bridge = $bridge;

        return $this;
    }

    /**
     * @return ElasticBridge
     */
    public function getBridge(): ElasticBridge
    {
        return $this->bridge;
    }

    /**
     * @return mixed
     */
    public function all()
    {
        return $this->getBridges();
    }

    /**
     * @return $this
     */
    public function shouldMatch(string $field, $value): BridgeBuilder
    {
        $this->query->setPayload('should', ['match' => [$field => $value]]);

        return $this;
    }

    /**
     * @return $this
     */
    public function shouldMatchAll($boost = 1.0): BridgeBuilder
    {
        $this->query->setPayload('should', ['match_all' => ['boost' => $boost]]);

        return $this;
    }

    /**
     * @return BridgeBuilder
     */
    public function mustMatch(string $field, $value): BridgeBuilder
    {
        $this->query->setPayload('must', ['match' => [$field => $value]]);

        return $this;
    }

    /**
     * @param float $boost
     * @return BridgeBuilder
     */
    public function matchAll(float $boost = 1.0): BridgeBuilder
    {
        $this->query->setPayload('must', ['match_all' => ['boost' => $boost]]);

        return $this;
    }

    /**
     * @param array $query
     * @return BridgeBuilder
     */
    public function raw(array $query): BridgeBuilder
    {
        $this->query->setRawPayload($query);

        return $this;
    }

    /**
     * @param string $field
     * @return BridgeBuilder
     */
    public function mustExist(string $field): BridgeBuilder
    {
        $this->query->setPayload('must', ['exists' => ['field' => $field]]);

        return $this;
    }

    /**
     * @param string $field
     * @return BridgeBuilder
     */
    public function shouldExist(string $field): BridgeBuilder
    {
        $this->query->setPayload(key: 'should', payload: ['exists' => ['field' => $field]]);

        return $this;
    }

    /**
     * @param string $field
     * @param string $query
     * @return BridgeBuilder
     */
    public function match(string $field, string $query): BridgeBuilder
    {
        $this->query->setPayload(key: $field, payload: $query, asArray: false);

        return $this;
    }

    /**
     * @param $field
     * @param string $query
     * @return BridgeBuilder
     */
    public function multiMatch($field, string $query): BridgeBuilder
    {
        if (!is_array($field)) {
            $field = [$field];
        }

        $this->query->setPayload('multi_match', [
            'query' => $query,
            'fields' => $field
        ]);

        return $this;
    }

    /**
     * @param array $values
     * @return BridgeBuilder
     */
    public function withValues(array $values = []): BridgeBuilder
    {
        $this->query->setPayload('values', $values, false);

        return $this;
    }


    /**
     * @return void
     */
    public function filter() {}

    /**
     * @param string[] $columns
     * @return mixed
     */
    public function get(array $columns = ['*']): mixed
    {
        $builder = clone $this;

        $bridges = $builder->getBridges($columns);

        return $builder->getBridge()->newCollection($bridges);
    }

    /**
     * @param string[] $columns
     * @return mixed
     */
    public function getBridges(array $columns = ['*']): mixed
    {
        return $this->bridge->hydrate(
            $this->query->get($this->getBridge()->getIndex(), $columns)
        )->all();
    }
}
