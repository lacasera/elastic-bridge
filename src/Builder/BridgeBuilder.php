<?php
declare(strict_types=1);

namespace Lacasera\ElasticBridge\Builder;

use Illuminate\Support\Traits\ForwardsCalls;
use Lacasera\ElasticBridge\Concerns\SetsTerm;
use Lacasera\ElasticBridge\ElasticBridge;
use Lacasera\ElasticBridge\Query\QueryBuilder;
use Lacasera\ElasticBridge\Query\Traits\HasFilters;

class BridgeBuilder implements BridgeBuilderInterface
{
    use ForwardsCalls;
    use HasFilters;
    use SetsTerm;

    protected $bridge;

    protected QueryBuilder $query;

    private bool $isPaginating = false;

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
    public function all($columns = ['*'])
    {
         return $this->asBoolean()
            ->shouldMatchAll()
            ->cursorPaginate($this->count())
             ->get($columns);
    }

    /**
     * @return self
     */
    public function shouldMatch(string $field, $value): self
    {
        $this->query->setPayload('should', ['match' => [$field => $value]]);

        return $this;
    }

    public function take(int $size = 15)
    {
        $this->query->setPagination(['size' => $size]);

        return $this;
    }

    /**
     * @return self
     */
    public function shouldMatchAll($boost = 1.0): self
    {
        $this->query->setPayload('should', ['match_all' => ['boost' => $boost]]);

        return $this;
    }

    /**
     * @return self
     */
    public function mustMatch(string $field, $value): self
    {
        $this->asBoolean();

        $this->query->setPayload('must', ['match' => [$field => $value]]);

        return $this;
    }

    /**
     * @return self
     */
    public function matchAll(float $boost = 1.0): self
    {
        $this->query->setPayload('must', ['match_all' => ['boost' => $boost]]);

        return $this;
    }

    /**
     * @return self
     */
    public function raw(array $query): self
    {
        $this->query->setRawPayload($query);

        return $this;
    }

    /**
     * @return self
     */
    public function mustExist(string $field): self
    {
        $this->query->setPayload('must', ['exists' => ['field' => $field]]);

        return $this;
    }

    /**
     * @return self
     */
    public function shouldExist(string $field): self
    {
        $this->query->setPayload(key: 'should', payload: ['exists' => ['field' => $field]]);

        return $this;
    }

    /**
     * @return self
     */
    public function match(string $field, string $query): self
    {
        $this->query->setPayload(key: 'match', payload: [$field => $query]);

        return $this;
    }

    /**
     * @return mixed
     */
    public function find($ids)
    {
        $this->query->setTerm('ids');

        $this->withValues($ids);

        return is_array($ids) ? $this->get() : $this->get()->first();
    }

    /**
     * @return self
     */
    public function multiMatch($field, string $query): self
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
     * @return self
     */
    public function withValues($values, $field = null, $options = []): self
    {
        if (! $field) {
            $this->query->setPayload('values', $values);

            return $this;
        }

        $this->query->setPayload(key: $field, payload: ['values' => $values, ...$options]);

        return $this;
    }

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
     * @return int
     */
    public function count()
    {
        return $this->query->count($this->getBridge()->getIndex());
    }

    /**
     * @return $this
     */
    public function simplePaginate(int $size = QueryBuilder::PAGINATION_SIZE, int $from = 0)
    {
        $this->query->setPagination(['from' => $from, 'size' => $size]);
        $this->isPaginating = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function cursorPaginate(int $size = QueryBuilder::PAGINATION_SIZE, array $sort = []): BridgeBuilder
    {
        $paginate['size'] = $size;

        if (! empty($sort)) {
            $paginate['search_after'] = $sort;
        }

        $this->isPaginating = true;
        $this->query->setPagination($paginate);

        return $this;
    }

    /**
     * @return $this
     */
    public function orderBy(string $field, string $direction = 'ASC'): BridgeBuilder
    {
        $this->query->setSort([
            $field => [
                'order' => $direction,
            ],
        ]);

        return $this;
    }

    /**
     * @param  string[]  $columns
     */
    public function getBridges(array $columns = ['*']): mixed
    {
        return $this->bridge->hydrate(
            $this->query->get($this->getBridge()->getIndex(), $columns),
            $this->isPaginating
        )->all();
    }

    public function toQuery(bool $asJson = false)
    {
        $query = $this->query->getRawPayload();

        return $asJson ? json_encode($query) : $query;
    }
}
