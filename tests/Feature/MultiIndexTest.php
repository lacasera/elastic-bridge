<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Tests\Feature;

use Lacasera\ElasticBridge\Exceptions\AmbiguousWriteIndex;
use Lacasera\ElasticBridge\Tests\Fixtures\Log;
use Lacasera\ElasticBridge\Tests\Fixtures\WriteScopedLog;
use Lacasera\ElasticBridge\Tests\Room;
use Lacasera\ElasticBridge\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class MultiIndexTest extends TestCase
{
    private function emptyHits(): array
    {
        return ['hits' => ['total' => ['value' => 0, 'relation' => 'eq'], 'hits' => []]];
    }

    #[Test]
    public function it_reads_across_a_wildcard_index(): void
    {
        $fake = Log::fake($this->emptyHits());

        Log::asBoolean()->matchAll()->get();

        $this->assertSame('logs-*', $fake->requests['search'][0]['index']);
    }

    #[Test]
    public function it_refuses_to_write_to_a_wildcard_index(): void
    {
        Log::fake([]);

        $this->expectException(AmbiguousWriteIndex::class);

        Log::create(['message' => 'boom']);
    }

    #[Test]
    public function it_writes_to_the_configured_write_index(): void
    {
        $fake = WriteScopedLog::fake(['_id' => '1', 'result' => 'created']);

        WriteScopedLog::create(['message' => 'hi']);

        $this->assertSame('logs-write', $fake->requests['index'][0]['index']);
    }

    #[Test]
    public function the_configured_bridge_still_reads_the_wildcard(): void
    {
        $fake = WriteScopedLog::fake($this->emptyHits());

        WriteScopedLog::asBoolean()->matchAll()->get();

        $this->assertSame('logs-*', $fake->requests['search'][0]['index']);
    }

    #[Test]
    public function from_overrides_the_search_index_per_call(): void
    {
        $fake = Room::fake($this->emptyHits());

        Room::from(['a', 'b'])->asBoolean()->matchAll()->get();

        $this->assertSame('a,b', $fake->requests['search'][0]['index']);
    }

    #[Test]
    public function into_overrides_the_write_index_per_call(): void
    {
        $fake = Room::fake(['_id' => '1', 'result' => 'created']);

        Room::into('logs-x')->create(['a' => 1]);

        $this->assertSame('logs-x', $fake->requests['index'][0]['index']);
    }

    #[Test]
    public function into_applies_to_bulk_action_lines(): void
    {
        $fake = Room::fake(['items' => [['index' => ['_id' => '1', 'status' => 201]]]]);

        Room::into('logs-x')->bulk([['a' => 1]]);

        $this->assertSame(['index' => ['_index' => 'logs-x']], $fake->requests['bulk'][0]['body'][0]);
    }

    #[Test]
    public function an_existing_document_writes_back_to_its_origin_index(): void
    {
        $fake = Log::fake(['result' => 'updated']);

        $log = new Log;
        $log->exists = true;
        $log->setRawAttributes([
            '_index' => 'logs-2025.02.02',
            '_id' => '1',
            '_source' => ['count' => 1],
        ]);

        $log->increment('count');

        $this->assertSame('logs-2025.02.02', $fake->requests['update'][0]['index']);
    }

    #[Test]
    public function a_single_index_bridge_reads_and_writes_the_same_index(): void
    {
        $fakeConnection = Room::fake($this->emptyHits());
        Room::asBoolean()->matchAll()->get();
        $this->assertSame('rooms', $fakeConnection->requests['search'][0]['index']);

        $write = Room::fake(['_id' => '1', 'result' => 'created']);
        Room::create(['a' => 1]);
        $this->assertSame('rooms', $write->requests['index'][0]['index']);
    }
}
