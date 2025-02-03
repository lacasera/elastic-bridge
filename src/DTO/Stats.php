<?php
declare(strict_types=1);

namespace Lacasera\ElasticBridge\DTO;

use Illuminate\Support\Collection;

class Stats
{
    /**
     * @var float
     */
    protected float $min;

    /**
     * @var float
     */
    protected float $max;

    /**
     * @var float
     */
    protected float $count;

    /**
     * @var float
     */
    protected float $avg;

    /**
     * @var float
     */
    protected float $sum;

    public function __construct(array $stats)
    {
        foreach ($stats as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * @return float
     */
    public function avg(): float
    {
        return $this->avg;
    }

    /**
     * @return float
     */
    public function count(): float
    {
        return $this->count;
    }

    /**
     * @return float
     */
    public function max(): float
    {
        return $this->max;
    }

    /**
     * @return float
     */
    public function min(): float
    {
        return $this->min;
    }

    /**
     * @return float
     */
    public function sum(): float
    {
        return $this->sum;
    }

    /**
     * @return array
     */
    public function toArray() : array
    {
       return [
            'count' => $this->count,
            'max' => $this->max,
            'min' => $this->min,
            'sum' => $this->sum,
            'avg' => $this->avg
       ];
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function toCollection(): Collection
    {
        return collect($this->toArray());
    }
}
