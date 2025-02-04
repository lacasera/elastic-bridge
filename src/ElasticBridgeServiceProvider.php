<?php

namespace Lacasera\ElasticBridge;

use Lacasera\ElasticBridge\Commands\ElasticBridgeCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ElasticBridgeServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('elastic-bridge')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_elastic_bridge_table')
            ->hasCommand(ElasticBridgeCommand::class);
    }
}
