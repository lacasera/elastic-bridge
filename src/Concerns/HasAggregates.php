<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Concerns;

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
     * @param mixed $type
     * @param mixed $field
     * @return self;
     */
    public function withAggregate($type, $field): self
    {
        $this->query->setAggregate($this->getAggregateQuery($type, $field));

        return $this;
    }

    /**
     * @return mixed
     */
    private function primitiveAggregate(string $type, string $field)
    {
        $key = "{$type}_{$field}";

        return $this->query
            ->setTerm('raw')
            ->setPayload($key, [
                $type => [
                    'field' => $field,
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
     * @param string $type
     * @param string $field
     * @return array[]
     */
    private function getAggregateQuery(string $type, string $field)
    {
        $key = "{$type}_{$field}";

        return [
            "$key" => [
                $type => [
                    'field' => $field,
                ],
            ],
        ];
    }
}
