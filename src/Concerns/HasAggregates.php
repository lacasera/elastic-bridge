<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Concerns;

trait HasAggregates
{
    public function avg(string $field)
    {
        if ($this->query->hasPayload()) {
            return $this->getAggregateForASpecificQuery('avg', $field);
        }

        return $this->primitiveAggregate('avg', $field);
    }

    public function max(string $field)
    {
        if ($this->query->hasPayload()) {
            return $this->getAggregateForASpecificQuery('max', $field);
        }

        return $this->primitiveAggregate('max', $field);
    }

    public function min(string $field)
    {
        if ($this->query->hasPayload()) {
            return $this->getAggregateForASpecificQuery('min', $field);
        }

        return $this->primitiveAggregate('min', $field);
    }

    /**
     * @return mixed
     */
    public function sum($field)
    {
        if ($this->query->hasPayload()) {
            return $this->getAggregateForASpecificQuery('sum', $field);
        }

        return $this->primitiveAggregate('sum', $field);
    }

    public function withAggregate($type, $field)
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
