<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Exceptions;

use Exception;
use Lacasera\ElasticBridge\ElasticBridge;

class ErrorEncodingJson extends Exception
{
    public static function forBridge(ElasticBridge $bridge, $message)
    {
        return new static('Error encoding bridge ['.get_class($bridge).'] with index ['.$bridge->getIndex().'] to JSON: '.$message);
    }
}
