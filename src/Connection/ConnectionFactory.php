<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Connection;

use InvalidArgumentException;

class ConnectionFactory
{
    /**
     * Resolve the connection for the configured driver.
     */
    public static function make(array $config): ConnectionInterface
    {
        $driver = $config['driver'] ?? 'elasticsearch';

        return match ($driver) {
            'elasticsearch' => new ElasticConnection,
            'opensearch' => new OpenSearchConnection,
            'meilisearch' => new MeilisearchConnection,
            default => throw new InvalidArgumentException(
                sprintf('Unsupported elastic-bridge driver [%s]. Supported: elasticsearch, opensearch, meilisearch.', $driver)
            ),
        };
    }
}
