<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge;

use Lacasera\ElasticBridge\Commands\ElasticBridgeCommand;
use Lacasera\ElasticBridge\Connection\ConnectionInterface;
use Lacasera\ElasticBridge\Connection\ElasticConnection;
use Override;
use Spatie\LaravelPackageTools\Exceptions\InvalidPackage;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ElasticBridgeServiceProvider extends PackageServiceProvider
{
    #[Override]
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

    /**
     * @throws InvalidPackage
     */
    #[Override]
    public function register(): void
    {
        parent::register();

        $this->app->bind(ConnectionInterface::class, ElasticConnection::class);
    }
}
