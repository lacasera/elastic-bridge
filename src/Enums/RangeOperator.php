<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Enums;

use Lacasera\ElasticBridge\Query\Traits\ValidateOperator;
use Lacasera\ElasticBridge\Query\Traits\HasValues;

enum RangeOperator: string
{
    use HasValues;
    use ValidateOperator;

    case GT = 'gt';
    case GTE = 'gte';
    case LT = 'lt';
    case LTE = 'lts';

    case FROM = 'from';

    case  TO = 'to';
}
