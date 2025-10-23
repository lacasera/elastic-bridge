<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Tests;

use Lacasera\ElasticBridge\ElasticBridgeServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Override;

class TestCase extends Orchestra
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Override]
    protected function getPackageProviders($app)
    {
        return [
            ElasticBridgeServiceProvider::class,
        ];
    }

    #[Override]
    protected function getEnvironmentSetUp($app)
    {
        //
    }

    protected function getFakeData(): array
    {
        $path = dirname(__FILE__, 2).DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'fake'.DIRECTORY_SEPARATOR.'data.json';

        return json_decode(file_get_contents($path), true);
    }
}
