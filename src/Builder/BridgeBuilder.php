<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Builder;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;
use Lacasera\ElasticBridge\Concerns\HasAggregates;
use Lacasera\ElasticBridge\Concerns\SetsTerm;
use Lacasera\ElasticBridge\ElasticBridge;
use Lacasera\ElasticBridge\Query\QueryBuilder;
use Lacasera\ElasticBridge\Query\Traits\HasFilters;

class BridgeBuilder implements BridgeBuilderInterface
{
    use ForwardsCalls;
    use HasAggregates;
    use HasFilters;
    use SetsTerm;

    protected ElasticBridge $bridge;

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

    public function getBridge(): ElasticBridge
    {
        return $this->bridge;
    }

    /**
     * @return mixed
     */
    public function all(array $columns = ['*'])
    {
        return $this->asBoolean()
            ->shouldMatchAll()
            ->cursorPaginate($this->count())
            ->get($columns);
    }

    /**
     * @return $this
     */
    public function shouldMatch(string $field, $value): self
    {
        $this->query->setPayload('should', ['match' => [$field => $value]]);

        return $this;
    }

    /**
     * @return $this
     */
    public function take(int $size)
    {
        $this->query->setPagination(['size' => $size]);

        return $this;
    }

    /**
     * @return $this
     */
    public function skip(int $size): self
    {
        $this->query->setPagination(['from' => $size]);

        return $this;
    }

    /**
     * @return $this
     */
    public function limit(int $size): self
    {
        $this->query->setPagination(['size' => $size]);

        return $this;
    }

    /**
     * @return $this
     */
    public function offset(int $size): self
    {
        $this->query->setPagination(['from' => $size]);

        return $this;
    }

    /**
     * @return $this
     */
    public function shouldMatchAll($boost = 1.0): self
    {
        $this->query->setPayload('should', ['match_all' => ['boost' => $boost]]);

        return $this;
    }

    /**
     * @return $this
     */
    public function mustMatch(string $field, $value): self
    {
        $this->asBoolean();

        $this->query->setPayload('must', ['match' => [
            $field => [
                'query' => $value,
            ],
        ]]);

        return $this;
    }

    /**
     * @return $this
     */
    public function mustNot(string $query, string $field, array $payload): self
    {
        $this->query->setPayload('must_not', [
            $query => [
                $field => $payload,
            ],
        ]);

        return $this;
    }

    /**
     * @return $this
     */
    public function must(string $query, string $field, string $value): self
    {
        $this->asBoolean();

        $this->query->setPayload('must', [
            $query => [
                $field => [
                    'query' => $value,
                ],
            ],
        ]);

        return $this;
    }

    /**
     * @return $this
     */
    public function matchAll(float $boost = 1.0): self
    {
        $this->query->setPayload('must', ['match_all' => ['boost' => $boost]]);

        return $this;
    }

    /**
     * @return $this
     */
    public function raw(array $query): self
    {
        $this->query->setRawPayload($query);

        return $this;
    }

    /**
     * @return $this
     */
    public function mustExist(string $field): self
    {
        $this->query->setPayload('must', ['exists' => ['field' => $field]]);

        return $this;
    }

    /**
     * @return $this
     */
    public function shouldExist(string $field): self
    {
        $this->query->setPayload(key: 'should', payload: ['exists' => ['field' => $field]]);

        return $this;
    }

    /**
     * @return $this
     */
    public function match(string $field, string $query, array $options = []): self
    {
        $this->query->setPayload(key: 'match', payload: [
            $field => [
                'query' => $query,
                ...$options,
            ],
        ]);

        return $this;
    }

    /**
     * @return $this
     */
    public function orMatch(string $field, string $query): self
    {
        $this->query->setPayload(key: 'match', payload: [$field => [
            'query' => $query,
            'operator' => 'or',
        ]]);

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
     * @return $this
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
     * @return $this
     */
    public function matchPhrase(string $field, string $query, array $options = []): self
    {
        $this->query->setPayload('match_phrase', [
            $field => [
                'query' => $query,
                ...$options,
            ],
        ]);

        return $this;
    }

    /**
     * @return $this
     */
    public function withValues($values, ?string $field = null, array $options = []): self
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

    public function count(): int
    {
        return $this->query->count($this->getBridge()->getIndex());
    }

    /**
     * @return $this
     */
    public function simplePaginate(int $size = QueryBuilder::PAGINATION_SIZE, int $from = 0): self
    {
        $this->query->setPagination(['from' => $from, 'size' => $size]);
        $this->isPaginating = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function cursorPaginate(int $size = QueryBuilder::PAGINATION_SIZE, array $sort = []): self
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
    public function orderBy(string $field, string $direction = 'ASC'): self
    {
        $this->query->setSort([
            $field => [
                'order' => $direction,
            ],
        ]);

        return $this;
    }

    /**
     * @throws \Elastic\Elasticsearch\Exception\AuthenticationException
     * @throws \Elastic\Elasticsearch\Exception\ClientResponseException
     * @throws \Elastic\Elasticsearch\Exception\ServerResponseException
     */
    public function getBridges(array $columns = ['*']): mixed
    {
        return $this->bridge->hydrate(
            $this->query->get($this->getBridge()->getIndex(), $columns),
            $this->isPaginating
        )->all();
    }

    /**
     * @return array[]|false|string
     */
    public function toQuery(bool $asJson = false): array|false|string
    {
        $query = $this->query->getRawPayload();

        return $asJson ? json_encode($query) : $query;
    }

    /**
     * @return mixed|null
     *
     * @throws \Elastic\Elasticsearch\Exception\ClientResponseException
     * @throws \Elastic\Elasticsearch\Exception\MissingParameterException
     * @throws \Elastic\Elasticsearch\Exception\ServerResponseException
     */
    public function create(array $attributes)
    {
        $payload = [
            'index' => $this->bridge->getIndex(),
        ];

        if (Arr::has($attributes, 'id')) {
            $payload['id'] = data_get($attributes, 'id');
            data_forget($attributes, 'id');
        }

        $payload['body'] = $attributes;

        $res = $this->query->getConnection()->getClient()->index($payload)->asArray();

        return data_get($res, '_id');
    }

    /**
     * increases the value of a field by the counter provided
     */
    public function increment(string $field, int $counter = 1): bool
    {
        return $this->scriptRequest("ctx._source.$field += params.count", ['count' => $counter]);
    }

    /**
     * decreases the value of a field by the counter provided
     *
     * @throws \Elastic\Elasticsearch\Exception\ClientResponseException
     * @throws \Elastic\Elasticsearch\Exception\MissingParameterException
     * @throws \Elastic\Elasticsearch\Exception\ServerResponseException
     */
    public function decrement(string $field, int $counter = 1): bool
    {
        return $this->scriptRequest("ctx._source.$field -= params.count", ['count' => $counter]);
    }

    /**
     * @throws \Elastic\Elasticsearch\Exception\ClientResponseException
     * @throws \Elastic\Elasticsearch\Exception\MissingParameterException
     * @throws \Elastic\Elasticsearch\Exception\ServerResponseException
     */
    public function save(): bool
    {
        $id = $this->bridge->id;

        $res = $this->find($id);

        if (! $res) {
            $id = $this->create($this->bridge->attributesToArray()['_source']);

            return boolval($id);
        }

        return $this->query->save($this->bridge);
    }

    /**
     * @return bool
     *
     * @throws \Elastic\Elasticsearch\Exception\ClientResponseException
     * @throws \Elastic\Elasticsearch\Exception\MissingParameterException
     * @throws \Elastic\Elasticsearch\Exception\ServerResponseException
     */
    public function scriptRequest(string $source, array $params)
    {
        return $this->query->update($this->bridge->getIndex(), [
            'script' => [
                'source' => $source,
                'params' => $params,
            ],
        ], $this->bridge->id);
    }

    /**
     * @return mixed
     */
    public function getAggregateForASpecificQuery(string $type, string $field, $options = [])
    {
        $this->take(0);

        $this->query->setAggregate($this->getAggregateQuery($type, $field, $options));

        $results = $this->get();

        $marco = Str::camel("{$type}_$field");

        return $results->$marco();
    }
}
