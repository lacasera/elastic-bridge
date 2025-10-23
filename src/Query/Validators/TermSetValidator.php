<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Query\Validators;

use Override;

class TermSetValidator implements ValidatorInterface
{
    #[Override]
    public function handle(array $payload): void
    {
        $query = data_get($payload, 'body.query.match');

        if (! array_key_exists('', $query)) {
            // throw some exception..
        }
    }
}
