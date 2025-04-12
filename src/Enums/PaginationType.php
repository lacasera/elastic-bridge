<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Enums;

enum PaginationType: string
{
    case SIMPLE = 'simple';

    case CURSOR = 'cursor';
}
