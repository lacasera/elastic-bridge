<?php

namespace Lacasera\ElasticBridge;

use Illuminate\Contracts\Support\DeferrableProvider;
use Lacasera\ElasticBridge\Commands\ElasticBridgeCommand;
use Lacasera\ElasticBridge\Connection\ConnectionInterface;
use Lacasera\ElasticBridge\Connection\ElasticConnection;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ElasticBridgeServiceProvider extends PackageServiceProvider implements DeferrableProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         */
        $package
            ->name('elastic-bridge')
            ->hasConfigFile('elasticbridge')
            ->hasCommand(ElasticBridgeCommand::class);
    }

    public function bootingPackage(): void
    {
        $this->app->bind(ConnectionInterface::class, ElasticConnection::class);
    }

    /**
     * @return string[]
     */
    public function provides(): array
    {
        return [ElasticConnection::class];
    }
}
