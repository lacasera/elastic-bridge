<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge;

use Lacasera\ElasticBridge\Commands\ElasticBridgeCommand;
use Lacasera\ElasticBridge\Connection\ConnectionFactory;
use Lacasera\ElasticBridge\Connection\ConnectionInterface;
use Lacasera\ElasticBridge\Contracts\SearchConnectionInterface;
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

        // Register the search connection using factory
        $this->app->singleton(SearchConnectionInterface::class, function ($app) {
            $config = $app['config']['elasticbridge'];

            return ConnectionFactory::make($config);
        });

        // Keep backward compatibility
        $this->app->bind(ConnectionInterface::class, SearchConnectionInterface::class);
    }
}
