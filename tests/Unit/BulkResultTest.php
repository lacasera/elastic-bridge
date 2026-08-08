<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Tests\Unit;

use Lacasera\ElasticBridge\DTO\BulkResult;
use Lacasera\ElasticBridge\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class BulkResultTest extends TestCase
{
    private function items(): array
    {
        return [
            ['index' => ['_id' => '1', 'status' => 201, 'result' => 'created']],
            ['index' => ['_id' => '2', 'status' => 200, 'result' => 'updated']],
            ['index' => ['_id' => '3', 'status' => 409, 'error' => ['type' => 'version_conflict_engine_exception']]],
        ];
    }

    #[Test]
    public function it_reports_successful_and_failed_items(): void
    {
        $bulkResult = new BulkResult($this->items());

        $this->assertSame(3, $bulkResult->total());
        $this->assertSame(2, $bulkResult->count());
        $this->assertCount(2, $bulkResult->successful());
        $this->assertCount(1, $bulkResult->failed());
        $this->assertTrue($bulkResult->hasErrors());

        $this->assertSame('3', $bulkResult->failed()->first()['_id']);
    }

    #[Test]
    public function it_has_no_errors_when_all_items_succeed(): void
    {
        $bulkResult = new BulkResult([
            ['index' => ['_id' => '1', 'status' => 201, 'result' => 'created']],
        ]);

        $this->assertFalse($bulkResult->hasErrors());
        $this->assertSame(1, $bulkResult->count());
    }

    #[Test]
    public function an_empty_result_is_well_formed(): void
    {
        $bulkResult = new BulkResult;

        $this->assertSame(0, $bulkResult->total());
        $this->assertSame(0, $bulkResult->count());
        $this->assertFalse($bulkResult->hasErrors());
        $this->assertSame([], $bulkResult->toArray());
    }
}
