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
     * @param string $field
     * @param $value
     * @return $this
     */
    public function mustMatch(string $field, $value): BridgeBuilder
    {
        $this->query->setPayload('must', ['match' => [$field => $value]]);

        return $this;
    }

    /**
     * @param float $boost
     * @return $this
     */
    public function matchAll(float $boost = 1.0): BridgeBuilder
    {
        $this->query->setPayload('must', ['match_all' => ['boost' => $boost]]);

        return $this;
    }

    /**
     * @param array $query
     * @return $this
     */
    public function raw(array $query): BridgeBuilder
    {
        $this->query->setRawPayload($query);

        return $this;
    }

    /**
     * @param string $field
     * @return $this
     */
    public function mustExist(string $field): BridgeBuilder
    {
        $this->query->setPayload('must', ['exists' => ['field' => $field]]);

        return $this;
    }

    /**
     * @param string $field
     * @return $this
     */
    public function shouldExist(string $field): BridgeBuilder
    {
        $this->query->setPayload(key: 'should', payload: ['exists' => ['field' => $field]]);

        return $this;
    }

    /**
     * @param string $field
     * @param string $query
     * @return $this
     */
    public function match(string $field, string $query): BridgeBuilder
    {
        $this->query->setPayload(key: $field, payload: $query, asArray: false);

        return $this;
    }

    /**
     * @param $ids
     * @return mixed
     */
    public function find($ids)
    {
        $this->query->setTerm('ids');

        $this->withValues($ids, is_array($ids));

        return is_array($ids) ? $this->get() : $this->get()->first();
    }

    /**
     * @param $field
     * @param string $query
     * @return $this
     */
    public function multiMatch($field, string $query): BridgeBuilder
    {
        if (! is_array($field)) {
            $field = [$field];
        }

        $this->query->setPayload('multi_match', [
            'query' => $query,
            'fields' => $field,
        ]);

        return $this;
    }

    /**
     * @param array $values
     * @param bool $asArray
     * @return $this
     */
    public function withValues($values, bool $asArray = false): BridgeBuilder
    {
        $this->query->setPayload('values', $values, $asArray);

        return $this;
    }

    /**
     * @return void
     */
    public function filter() {}

    /**
     * @param  string[]  $columns
     */
    public function get(array $columns = ['*']): mixed
    {
        $builder = clone $this;

        $bridges = $builder->getBridges($columns);

        return $builder->getBridge()->newCollection($bridges);
    }

    /**
     * @param  string[]  $columns
     */
    public function getBridges(array $columns = ['*']): mixed
    {
        return $this->bridge->hydrate(
            $this->query->get($this->getBridge()->getIndex(), $columns)
        )->all();
    }
}
