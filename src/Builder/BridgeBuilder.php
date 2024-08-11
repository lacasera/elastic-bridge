<?php

namespace Lacasera\ElasticBridge\Builder;

use Illuminate\Support\Traits\ForwardsCalls;
use Lacasera\ElasticBridge\ElasticBridge;
use Lacasera\ElasticBridge\Query\QueryBuilder;

class BridgeBuilder implements BridgeBuilderInterface
{
    use ForwardsCalls;

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

    public function getBridge()
    {
        return $this->bridge;
    }

    public function all()
    {

        return $this->getBridges();
    }

    /**
     * @param string $field
     * @param $value
     * @param bool $boost
     * @return $this
     */
    public function shouldMatch(string $field, $value, bool $boost = true): BridgeBuilder
    {
        $this->query->setPayload('should', ['match' => [$field => $value]]);

        return $this;
    }

    /**
     * @param string $field
     * @param $value
     * @param bool $boost
     * @return $this
     */
    public function shouldMatchAll(string $field, $value, bool $boost = true): BridgeBuilder
    {
        $this->query->setPayload('should', ['match_all' => [$field => $value]]);

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
     * @param $boost
     * @return $this
     */
    public function matchAll($boost = 1.0)
    {
        $this->query->setPayload('must',['match_all' => ['boost' => $boost]]);

        return $this;
    }

    /**
     * @return void
     */
    public function matchNone() {}

    /**
     * @return void
     */
    public function filter() {}

    /**
     * @param $columns
     * @return mixed
     */
    public function get($columns = ['*'])
    {
        $builder = clone $this;

        $bridges = $builder->getBridges($columns);

        return $builder->getBridge()->newCollection($bridges);
    }

    /**
     * @param $columns
     * @return mixed
     */
    public function getBridges($columns = ['*'])
    {
        return $this->bridge->hydrate(
            $this->query->get($this->getBridge()->getIndex(), $columns)
        )->all();
    }
}
