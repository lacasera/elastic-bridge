<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Exceptions;

use Exception;
use Lacasera\ElasticBridge\ElasticBridge;

class ErrorEncodingJson extends Exception
{
    public static function forBridge(ElasticBridge $elasticBridge, string $message): static
    {
        return new static('Error encoding bridge ['.$elasticBridge::class.'] with index ['.$elasticBridge->getIndex().'] to JSON: '.$message);
    }
}
