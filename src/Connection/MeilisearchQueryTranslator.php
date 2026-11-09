<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Connection;

use Lacasera\ElasticBridge\Exceptions\InvalidQuery;

/**
 * Translate the bridge's Elasticsearch-shaped request into Meilisearch search parameters.
 * Only clauses with equivalent Meilisearch behavior are accepted.
 */
class MeilisearchQueryTranslator
{
    /**
     * @return array{query: string|null, options: array<string, mixed>}
     */
    public function translate(array $body, string $primaryKey): array
    {
        $this->onlyKeys($body, ['query', 'sort', 'from', 'size', '_source'], 'search request');

        $query = $body['query'] ?? ['match_all' => []];

        if (! is_array($query)) {
            throw new InvalidQuery('Meilisearch query must be an object.');
        }

        $parsed = $this->query($query, $primaryKey);
        $options = ['showRankingScore' => true];

        if ($parsed['filter'] !== null) {
            $options['filter'] = $parsed['filter'];
        }

        if ($parsed['fields'] !== null) {
            $options['attributesToSearchOn'] = $parsed['fields'];
        }

        if (array_key_exists('sort', $body)) {
            $options['sort'] = $this->sort($body['sort']);
        }

        if (array_key_exists('from', $body)) {
            $options['offset'] = $this->nonNegativeInteger($body['from'], 'from');
        }

        if (array_key_exists('size', $body)) {
            $options['limit'] = $this->nonNegativeInteger($body['size'], 'size');
        }

        if (array_key_exists('_source', $body)) {
            if (! is_array($body['_source']) || $body['_source'] === []) {
                throw new InvalidQuery('Meilisearch requires _source to be a non-empty list of fields.');
            }

            foreach ($body['_source'] as $field) {
                $this->field($field);
            }

            $options['attributesToRetrieve'] = array_values(array_unique([...$body['_source'], $primaryKey]));
        }

        return ['query' => $parsed['text'], 'options' => $options];
    }

    /**
     * @return array{text: string|null, fields: array<int, string>|null, filter: string|null}
     */
    private function query(array $query, string $primaryKey): array
    {
        if (count($query) !== 1) {
            throw new InvalidQuery('Meilisearch requires exactly one query clause.');
        }

        $type = (string) array_key_first($query);
        $value = $query[$type];

        return match ($type) {
            'match_all' => $this->matchAll($value),
            'match' => $this->match($value),
            'match_phrase' => $this->matchPhrase($value),
            'multi_match' => $this->multiMatch($value),
            'term', 'terms', 'ids', 'exists', 'range' => [
                'text' => null,
                'fields' => null,
                'filter' => $this->filter($type, $value, $primaryKey),
            ],
            'bool' => $this->boolean($value, $primaryKey),
            default => throw new InvalidQuery(sprintf('Meilisearch does not support the [%s] query clause.', $type)),
        };
    }

    private function matchAll(mixed $value): array
    {
        if (! is_array($value) || array_diff(array_keys($value), ['boost']) !== [] || (float) ($value['boost'] ?? 1) !== 1.0) {
            throw new InvalidQuery('Meilisearch does not support match_all options or boosting.');
        }

        return ['text' => null, 'fields' => null, 'filter' => null];
    }

    private function match(mixed $value): array
    {
        if (! is_array($value) || count($value) !== 1) {
            throw new InvalidQuery('Meilisearch match requires one field.');
        }

        $field = $this->field(array_key_first($value));
        $payload = $value[$field];

        if (is_array($payload)) {
            $this->onlyKeys($payload, ['query'], 'match clause');
            $payload = $payload['query'] ?? null;
        }

        if (! is_string($payload)) {
            throw new InvalidQuery('Meilisearch match requires a string query.');
        }

        return ['text' => $payload, 'fields' => [$field], 'filter' => null];
    }

    private function matchPhrase(mixed $value): array
    {
        $match = $this->match($value);
        $match['text'] = json_encode($match['text'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        return $match;
    }

    private function multiMatch(mixed $value): array
    {
        if (! is_array($value)) {
            throw new InvalidQuery('Meilisearch multi_match requires query and fields.');
        }

        $this->onlyKeys($value, ['query', 'fields'], 'multi_match clause');

        if (! is_string($value['query'] ?? null) || ! is_array($value['fields'] ?? null) || $value['fields'] === []) {
            throw new InvalidQuery('Meilisearch multi_match requires a string query and fields.');
        }

        foreach ($value['fields'] as $field) {
            $this->field($field);
        }

        return ['text' => $value['query'], 'fields' => array_values($value['fields']), 'filter' => null];
    }

    private function boolean(mixed $value, string $primaryKey): array
    {
        if (! is_array($value)) {
            throw new InvalidQuery('Meilisearch bool requires an object.');
        }

        $this->onlyKeys($value, ['must', 'filter', 'should', 'must_not'], 'bool clause');

        $text = null;
        $fields = null;
        $filters = [];
        $hasRequiredClauses = $this->clauses($value['must'] ?? []) !== []
            || $this->clauses($value['filter'] ?? []) !== [];

        foreach (['must', 'filter', 'must_not'] as $operator) {
            foreach ($this->clauses($value[$operator] ?? []) as $clause) {
                $parsed = $this->query($clause, $primaryKey);

                if ($parsed['text'] !== null) {
                    if ($operator !== 'must' || $text !== null) {
                        throw new InvalidQuery('Meilisearch supports only one required full-text clause.');
                    }

                    $text = $parsed['text'];
                    $fields = $parsed['fields'];
                }

                if ($operator === 'must_not' && $parsed['filter'] === null) {
                    throw new InvalidQuery('Meilisearch cannot exclude a match_all clause.');
                }

                if ($parsed['filter'] !== null) {
                    $filters[] = $operator === 'must_not'
                        ? 'NOT ('.$parsed['filter'].')'
                        : $parsed['filter'];
                }
            }
        }

        $shouldClauses = $this->clauses($value['should'] ?? []);

        if ($shouldClauses !== []) {
            if ($hasRequiredClauses || $text !== null || $filters !== []) {
                throw new InvalidQuery('Meilisearch cannot combine optional should clauses with required clauses.');
            }

            $optionalFilters = [];

            foreach ($shouldClauses as $clause) {
                $parsed = $this->query($clause, $primaryKey);

                if ($parsed['text'] === null && $parsed['filter'] === null && count($shouldClauses) > 1) {
                    throw new InvalidQuery('Meilisearch cannot combine match_all with other should clauses.');
                }

                if ($parsed['text'] !== null) {
                    if ($text !== null || $optionalFilters !== [] || count($shouldClauses) > 1) {
                        throw new InvalidQuery('Meilisearch supports only one optional full-text clause.');
                    }

                    $text = $parsed['text'];
                    $fields = $parsed['fields'];

                    if ($parsed['filter'] !== null) {
                        $filters[] = $parsed['filter'];
                    }
                } elseif ($parsed['filter'] !== null) {
                    if ($text !== null) {
                        throw new InvalidQuery('Meilisearch cannot mix optional text and filter clauses.');
                    }

                    $optionalFilters[] = '('.$parsed['filter'].')';
                }
            }

            if ($optionalFilters !== []) {
                $filters[] = '('.implode(' OR ', $optionalFilters).')';
            }
        }

        return ['text' => $text, 'fields' => $fields, 'filter' => $filters === [] ? null : implode(' AND ', array_map(fn (string $filter): string => '('.$filter.')', $filters))];
    }

    private function filter(string $type, mixed $value, string $primaryKey): string
    {
        if (! is_array($value)) {
            throw new InvalidQuery(sprintf('Meilisearch %s requires an object.', $type));
        }

        if ($type === 'ids') {
            $this->onlyKeys($value, ['values'], 'ids clause');

            return $this->in($primaryKey, $value['values'] ?? null);
        }

        if ($type === 'exists') {
            $this->onlyKeys($value, ['field'], 'exists clause');

            return $this->field($value['field'] ?? null).' EXISTS';
        }

        if (count($value) !== 1) {
            throw new InvalidQuery(sprintf('Meilisearch %s requires one field.', $type));
        }

        $field = $this->field(array_key_first($value));
        $operand = $value[$field];

        return match ($type) {
            'term' => $field.' = '.$this->literal($operand),
            'terms' => $this->in($field, $operand),
            'range' => $this->range($field, $operand),
            default => throw new InvalidQuery(sprintf('Meilisearch does not support the [%s] filter.', $type)),
        };
    }

    private function range(string $field, mixed $conditions): string
    {
        if (! is_array($conditions) || $conditions === []) {
            throw new InvalidQuery('Meilisearch range requires at least one comparison.');
        }

        $operators = ['gt' => '>', 'gte' => '>=', 'lt' => '<', 'lte' => '<='];
        $parts = [];

        foreach ($conditions as $operator => $value) {
            if (! isset($operators[$operator])) {
                throw new InvalidQuery(sprintf('Meilisearch does not support the [%s] range operator.', $operator));
            }

            $parts[] = $field.' '.$operators[$operator].' '.$this->literal($value);
        }

        return implode(' AND ', $parts);
    }

    private function in(string $field, mixed $values): string
    {
        if (! is_array($values) || $values === []) {
            throw new InvalidQuery('Meilisearch IN requires a non-empty list of values.');
        }

        return $field.' IN ['.implode(', ', array_map($this->literal(...), $values)).']';
    }

    private function literal(mixed $value): string
    {
        return match (true) {
            is_string($value) => json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            is_int($value), is_float($value) => (string) $value,
            is_bool($value) => $value ? 'true' : 'false',
            default => throw new InvalidQuery('Meilisearch filters support only string, number, and boolean values.'),
        };
    }

    private function field(mixed $field): string
    {
        if (! is_string($field) || ! preg_match('/^[a-zA-Z_][a-zA-Z0-9_.-]*$/', $field)) {
            throw new InvalidQuery('Meilisearch field names must contain only letters, numbers, underscores, dots, or hyphens.');
        }

        return $field;
    }

    private function sort(mixed $sort): array
    {
        if (! is_array($sort)) {
            throw new InvalidQuery('Meilisearch sort requires a list.');
        }

        $result = [];

        foreach ($sort as $entry) {
            if (! is_array($entry) || count($entry) !== 1) {
                throw new InvalidQuery('Meilisearch sort requires one field per entry.');
            }

            $field = $this->field(array_key_first($entry));
            $options = $entry[$field];

            if (! is_array($options)) {
                throw new InvalidQuery('Meilisearch sort requires an order option.');
            }

            $this->onlyKeys($options, ['order'], 'sort clause');
            $direction = strtolower((string) ($options['order'] ?? ''));

            if (! in_array($direction, ['asc', 'desc'], true)) {
                throw new InvalidQuery('Meilisearch sort direction must be asc or desc.');
            }

            $result[] = $field.':'.$direction;
        }

        return $result;
    }

    private function clauses(mixed $clauses): array
    {
        if (! is_array($clauses)) {
            throw new InvalidQuery('Meilisearch bool clauses must be objects or lists.');
        }

        return array_is_list($clauses) ? $clauses : [$clauses];
    }

    private function nonNegativeInteger(mixed $value, string $name): int
    {
        if (! is_int($value) || $value < 0) {
            throw new InvalidQuery(sprintf('Meilisearch %s must be a non-negative integer.', $name));
        }

        return $value;
    }

    private function onlyKeys(array $value, array $keys, string $name): void
    {
        $unsupported = array_diff(array_keys($value), $keys);

        if ($unsupported !== []) {
            throw new InvalidQuery(sprintf('Meilisearch does not support [%s] in %s.', implode(', ', $unsupported), $name));
        }
    }
}
