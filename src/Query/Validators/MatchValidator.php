<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Query\Validators;

use Override;

class MatchValidator implements ValidatorInterface
{
    #[Override]
    public function handle(array $payload): void
    {
        data_get($payload, 'body.query.match');

        // throw exception
    }
}
