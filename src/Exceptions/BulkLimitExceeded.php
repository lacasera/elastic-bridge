<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Exceptions;

use Exception;

class BulkLimitExceeded extends Exception
{
    public static function make(int $count, int $max): self
    {
        return new self(sprintf(
            'bulk operation received %d documents, which exceeds the configured limit of %d. '.
            'Reduce the batch or raise elasticbridge.bulk.max.',
            $count,
            $max
        ));
    }
}
