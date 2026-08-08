<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Tests\Fixtures;

use Lacasera\ElasticBridge\ElasticBridge;

class WriteScopedLog extends ElasticBridge
{
    protected $index = 'logs-*';

    protected $writeIndex = 'logs-write';
}
