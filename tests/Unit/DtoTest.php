<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Tests\Unit;

use Lacasera\ElasticBridge\DTO\Bucket;
use Lacasera\ElasticBridge\DTO\Stats;
use Lacasera\ElasticBridge\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class DtoTest extends TestCase
{
    #[Test]
    public function stats_converts_and_serializes_correctly(): void
    {
        $stats = new Stats([
            'min' => 1.0,
            'max' => 3.0,
            'count' => 2.0,
            'avg' => 2.0,
            'sum' => 4.0,
        ]);

        $expected = [
            'count' => 2.0,
            'max' => 3.0,
            'min' => 1.0,
            'sum' => 4.0,
            'avg' => 2.0,
        ];

        $this->assertSame($expected, $stats->toArray());
        $this->assertSame(json_encode($expected), (string) $stats);
        $this->assertSame($expected['avg'], $stats->avg());
        $this->assertSame($expected['max'], $stats->max());
        $this->assertSame($expected['min'], $stats->min());
        $this->assertSame($expected['sum'], $stats->sum());
        $this->assertSame($expected['count'], $stats->count());
        $this->assertSame($expected, $stats->toCollection()->all());
    }

    #[Test]
    public function bucket_maps_values_to_getters(): void
    {
        $bucket = new Bucket([
            'key' => '10-20',
            'doc_count' => 5,
            'from' => 10.0,
            'to' => 20.0,
        ]);

        $this->assertSame('10-20', $bucket->key());
        $this->assertSame(5.0, $bucket->count());
        $this->assertSame(10.0, $bucket->from());
        $this->assertSame(20.0, $bucket->to());
    }
}
