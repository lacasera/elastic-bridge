<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Enums;

use Lacasera\ElasticBridge\Query\Traits\HasValues;
use Lacasera\ElasticBridge\Query\Traits\ValidateOperator;

enum OrderOperator: string
{
    use HasValues;
    use ValidateOperator;

    case ASC = 'asc';
    case DESC = 'desc';
}
