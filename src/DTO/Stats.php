<?php
declare(strict_types=1);

namespace Lacasera\ElasticBridge\DTO;

class Stats
{
    public float $min;

    public float $max;

    public float $count;

    public float $avg;

    public float $sum;

    public function __construct(array $stats)
    {
        foreach ($stats as $key => $value) {
            $this->{$key} = $value;
        }
    }

    public function __toString(): string
    {
        return json_encode([
            'count' => $this->count,
            'max' => $this->max,
            'min' => $this->min,
            'sum' => $this->sum,
            'avg' => $this->avg
        ]);
    }
}

