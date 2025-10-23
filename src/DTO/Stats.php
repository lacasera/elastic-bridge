<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\DTO;

use Illuminate\Support\Collection;
use Override;
use Stringable;

class Stats implements Stringable
{
    protected float $min;

    protected float $max;

    protected float $count;

    protected float $avg;

    protected float $sum;

    public function __construct(array $stats)
    {
        foreach ($stats as $key => $value) {
            $this->{$key} = $value;
        }
    }

    #[Override]
    public function __toString(): string
    {
        return (string) json_encode($this->toArray());
    }

    public function avg(): float
    {
        return $this->avg;
    }

    public function count(): float
    {
        return $this->count;
    }

    public function max(): float
    {
        return $this->max;
    }

    public function min(): float
    {
        return $this->min;
    }

    public function sum(): float
    {
        return $this->sum;
    }

    public function toArray(): array
    {
        return [
            'count' => $this->count,
            'max' => $this->max,
            'min' => $this->min,
            'sum' => $this->sum,
            'avg' => $this->avg,
        ];
    }

    public function toCollection(): Collection
    {
        return collect($this->toArray());
    }
}
