<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Connection;

use InvalidArgumentException;
use Lacasera\ElasticBridge\Contracts\SearchConnectionInterface;

class ConnectionFactory
{
    /**
     * Create a search connection instance
     */
    public static function make(array $config): SearchConnectionInterface
    {
        $driver = $config['driver'] ?? 'elasticsearch';

        return match ($driver) {
            'elasticsearch' => new ElasticConnection($config),
            'opensearch' => new OpenSearchConnection($config),
            default => throw new InvalidArgumentException("Unsupported search driver: {$driver}")
        };
    }

    /**
     * Get available drivers
     */
    public static function getAvailableDrivers(): array
    {
        return ['elasticsearch', 'opensearch'];
    }

    /**
     * Check if a driver is supported
     */
    public static function isDriverSupported(string $driver): bool
    {
        return in_array($driver, static::getAvailableDrivers());
    }
}
