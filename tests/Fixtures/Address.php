<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Tests\Fixtures;

class Address
{
    public function __construct(
        public string $lineOne,
        public string $lineTwo,
    ) {}
}
