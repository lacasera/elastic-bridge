<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Tests\Fixtures;

use Lacasera\ElasticBridge\ElasticBridge;

class Log extends ElasticBridge
{
    protected $index = 'logs-*';
}
