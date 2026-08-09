<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Exceptions;

use Exception;

class AmbiguousWriteIndex extends Exception
{
    public static function for(string $index): self
    {
        return new self(sprintf(
            'Cannot write to the multi-index pattern [%s]. Set a concrete write index via the '.
            '$writeIndex property, by overriding getWriteIndex(), or per-call with ->into(\'index\').',
            $index
        ));
    }
}
