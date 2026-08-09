<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Query;

use Elastic\Elasticsearch\Exception\AuthenticationException;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Lacasera\ElasticBridge\Connection\ConnectionInterface;
use Lacasera\ElasticBridge\ElasticBridge;
use Lacasera\ElasticBridge\Enums\RangeOperator;
use Lacasera\ElasticBridge\Exceptions\InvalidQuery;
use Lacasera\ElasticBridge\Exceptions\MissingTermLevelQuery;
use Lacasera\ElasticBridge\Query\Validators\QueryValidator;

class QueryBuilder
{
    public const RAW_TERM_LEVEL = 'raw';

    public const PAGINATION_SIZE = 15;

    /**
     * @var array|array[]
     */
    protected array $payload = [];

    protected array $sort = [];

    protected array $filters = [];

    protected array $paginate = [];

    protected array $aggregates = [];

    protected array $range = [];

    protected ?string $term = null;

    protected string $type = 'query';

    public function __construct(public ConnectionInterface $connection) {}

    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }

    /**
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function get(string $index, $columns = ['*']): mixed
    {
        return $this->makeSearchRequest($index, $columns);
    }

    public function setRawPayload(array $query): void
    {
        $this->payload = $query;
    }

    /**
     * @return $this
     */
    public function setPayload(string $key, mixed $payload): static
    {
        // Boolean occupant clauses must always be arrays of clause objects so
        // that chaining multiple clauses of the same type stays valid DSL.
        $arrayClauses = ['must', 'should', 'must_not', 'filter'];

        if (in_array($key, $arrayClauses, true)) {
            $this->payload[$key] ??= [];
            $this->payload[$key][] = $payload;

            return $this;
        }

        $this->payload[$key] = is_array($payload) ? $payload : [$payload];

        return $this;
    }

    public function setSort(array $query): void
    {
        $this->sort[] = $query;
    }

    /**
     * @return mixed
     *
     * @throws MissingTermLevelQuery
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function count(string $index)
    {
        // Build the full query when a term context is set (so filters/range are
        // included even when no must/should clause was added); otherwise count all.
        $full = $this->term !== null ? $this->getPayload() : $this->defaultPayload();

        // The _count API only accepts a `query`; sort/size/aggs/_source would
        // be rejected, so send the query sub-object alone.
        $body = ['query' => $full['query'] ?? []];

        return $this->getConnection()
            ->count([
                'index' => $index,
                'body' => $body,
            ])['count'];
    }

    /**
     * Build the query payload deterministically.
     *
     *
     * @throws MissingTermLevelQuery
     */
    public function getPayload($columns = ['*']): array
    {
        if ($this->term === null) {
            throw new MissingTermLevelQuery('set term level query');
        }

        if ($this->term === self::RAW_TERM_LEVEL) {
            $body = $this->payload;
        } elseif ($this->term === 'bool') {
            $body = ['bool' => $this->payload];
        } else {
            $body = [$this->term => $this->payload];
        }

        $filters = $this->filters;

        $rangeEntries = $this->range;

        if ($this->term === 'range' && $rangeEntries !== []) {
            if (count($rangeEntries) === 1) {
                $body = ['range' => $rangeEntries];
            } else {
                $body = ['bool' => []];
                foreach ($rangeEntries as $field => $conditions) {
                    $filters[] = ['range' => [$field => $conditions]];
                }
            }
        }

        if ($filters !== []) {
            // Filters are only valid inside a bool query. Promote any existing
            // non-bool query into bool.must so filters are never silently dropped.
            if (! array_key_exists('bool', $body)) {
                $existing = $body;
                $body = ['bool' => []];

                if ($existing !== []) {
                    $body['bool']['must'] = [$existing];
                }
            }

            $body['bool']['filter'] = $filters;
        }

        $this->validateQuery($body);

        $payload = [$this->type => $body];

        if ($this->hasSort()) {
            $payload['sort'] = $this->sort;
        }

        if ($this->shouldAttachAggregate()) {
            $payload['aggs'] = $this->aggregates;
        }

        if ($this->isPaginating()) {
            $payload = array_merge($payload, $this->paginate);
        }

        if ($this->isSelectingFields(collect($columns))) {
            $payload['_source'] = $columns;
        }

        return $payload;
    }

    /**
     * @return $this
     */
    public function setTerm(string $term): static
    {
        $this->term = $term;

        return $this;
    }

    /**
     * @return $this
     */
    public function setAggregate(array $payload): self
    {
        $this->aggregates = $payload;

        return $this;
    }

    public function setFilter($type, $field, $value, $operator = null): void
    {
        if ($type === 'term') {
            $this->filters[] = [
                'term' => [$field => $value],
            ];
        }

        if ($type === 'range') {
            $this->filters[] = [
                'range' => [
                    $field => [
                        $operator => $value,
                    ],
                ],
            ];
        }
    }

    public function setRawFilters(array $payload): void
    {
        $this->filters[] = $payload;
    }

    /**
     * @return array[]
     */
    public function getRawPayload(): array
    {
        return $this->getPayload();
    }

    public function setPagination(array $payload): void
    {
        $this->paginate = $payload;
    }

    /**
     * @return $this
     */
    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    private function hasSort(): bool
    {
        return $this->sort !== [];
    }

    /**
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    protected function makeSearchRequest(string $index, $columns = ['*']): mixed
    {
        return $this->makeRequest($index, $columns);
    }

    /**
     * @return mixed
     *
     * @throws MissingTermLevelQuery
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function makeAggregateRequest(string $type, string $index)
    {
        $complexAggregates = ['stats', 'histogram', 'range'];

        $results = $this->setType('aggs')->makeRequest($index)['aggregations'][$type];

        $type = Arr::first(explode('_', $type));

        if (! in_array($type, $complexAggregates)) {
            return $results['value'];
        }

        return $results;
    }

    /**
     * @throws MissingTermLevelQuery
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function makeRequest(string $index, $columns = ['*']): array
    {
        return $this->getConnection()
            ->search([
                'index' => $index,
                'body' => $this->getPayload($columns),
            ]);
    }

    /**
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function save(ElasticBridge $elasticBridge, ?string $index = null): bool
    {
        return $this->update($index ?? $elasticBridge->getWriteIndex(), [
            'doc' => $elasticBridge->attributesToArray(),
        ], $elasticBridge->id);
    }

    public function hasPayload(): bool
    {
        return $this->payload !== [];
    }

    /**
     * @return $this
     */
    public function range(string $field, string $operator, $value): static
    {
        if (! RangeOperator::isValid($operator)) {
            throw new InvalidQuery(
                sprintf('invalid range operator [%s]. allowed: %s.', $operator, implode(', ', RangeOperator::values()))
            );
        }

        // Collect range constraints by field. Supports chaining to merge ops.
        $existing = $this->range[$field] ?? [];
        $this->range[$field] = array_merge($existing, [$operator => $value]);

        return $this;
    }

    /**
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function update(string $index, array $body, $id): bool
    {
        $query = [
            'index' => $index,
            'id' => $id,
            'body' => $body,
        ];

        return $this->getConnection()->update($query);
    }

    public function indexRequest(array $body, bool $asArray = true): array|bool
    {
        $result = $this->getConnection()->index($body);

        return $asArray ? $result : in_array(data_get($result, 'result'), ['created', 'updated', 'noop'], true);
    }

    private function isSelectingFields(Collection $columns): bool
    {
        return $columns->isNotEmpty() && ! $columns->contains('*');
    }

    private function isPaginating(): bool
    {
        return $this->paginate !== [];
    }

    /**
     * @return array[]
     */
    private function defaultPayload(): array
    {
        return [
            'query' => [
                'bool' => [
                    'should' => [
                        'match_all' => [
                            'boost' => 1,
                        ],
                    ],
                ],
            ],
        ];
    }

    private function shouldAttachAggregate(): bool
    {
        return $this->aggregates !== [];
    }

    /**
     * Run the validator (if one exists) against the assembled body.
     *
     * The validator is chosen from the body's actual top-level shape rather
     * than the requested term, since filter promotion can turn a non-bool
     * term into a bool query.
     */
    private function validateQuery(array $body): void
    {
        if ($this->term === null || $this->term === self::RAW_TERM_LEVEL || $body === []) {
            return;
        }

        $effectiveTerm = (string) array_key_first($body);

        (new QueryValidator)->validate($effectiveTerm, [
            'body' => [$this->type => $body],
        ]);
    }
}
