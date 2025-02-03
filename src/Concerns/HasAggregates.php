<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Concerns;

use Lacasera\ElasticBridge\DTO\Bucket;
use Lacasera\ElasticBridge\DTO\Stats;

trait HasAggregates
{
    /**
     * @param string $field
     * @return float
     */
    public function avg(string $field): float
    {
        if ($this->query->hasPayload()) {
            return $this->getAggregateForASpecificQuery('avg', $field);
        }

        return $this->primitiveAggregate('avg', $field);
    }

    /**
     * @param string $field
     * @return float
     */
    public function max(string $field): float
    {
        if ($this->query->hasPayload()) {
            return $this->getAggregateForASpecificQuery('max', $field);
        }

        return $this->primitiveAggregate('max', $field);
    }

    /**
     * @param string $field
     * @return float
     */
    public function min(string $field): float
    {
        if ($this->query->hasPayload()) {
            return $this->getAggregateForASpecificQuery('min', $field);
        }

        return $this->primitiveAggregate('min', $field);
    }

    /**
     * @param mixed $field
     * @return float
     */
    public function sum($field): float
    {
        if ($this->query->hasPayload()) {
            return $this->getAggregateForASpecificQuery('sum', $field);
        }

        return $this->primitiveAggregate('sum', $field);
    }

    /**
     * @param string $type
     * @param string $field
     * @param array $options
     * @return self;
     */
    public function withAggregate(string $type, string $field, array $options = []): self
    {
        $this->query->setAggregate($this->getAggregateQuery($type, $field, $options));

        return $this;
    }

    /**
     * @return mixed
     */
    private function primitiveAggregate(string $type, string $field, $options = [])
    {
        $key = $this->getAggregateKey($type, $field);

        return $this->query
            ->setTerm('raw')
            ->setPayload($key, [
                $type => [
                    'field' => $field,
                    ...$options
                ],
            ])
            ->makeAggregateRequest($key, $this->getBridge()->getIndex());
    }

    /**
     * @param string $field
     * @return Stats
     */
    public function stats(string $field) : Stats
    {
        if ($this->query->hasPayload()) {
            return $this->getAggregateForASpecificQuery('stats', $field);
        }

        return new Stats($this->primitiveAggregate('stats', $field));
    }

    /**
     * @param string $field
     * @param float $interval
     * @return \Illuminate\Support\Collection<Bucket::class>
     */
    public function histogram(string $field, float $interval)
    {
        $options = [
            'interval' => $interval,
        ];

        if ($this->query->hasPayload()) {
            return $this->getAggregateForASpecificQuery('histogram', $field, $options);
        }

        $buckets =  data_get($this->primitiveAggregate('histogram',$field, $options), 'buckets');

        return collect($buckets)->mapInto(Bucket::class)->collect();
    }

    /**
     * @param string $type
     * @param string $field
     * @return array[]
     */
    private function getAggregateQuery(string $type, string $field, $options = [])
    {
        $key = $this->getAggregateKey($type, $field);

        return [
            "$key" => [
                $type => [
                    'field' => $field,
                    ...$options,
                ],
            ],
        ];
    }

    public function range(string $field, string $operator, $value)
    {
        $this->query->range($field, $operator, $value);

        return $this;
    }

    private function getAggregateKey(string $type, string $field): string
    {
        return "{$field}_{$type}";
    }
}
