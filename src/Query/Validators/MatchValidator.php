<?php
declare(strict_types=1);

namespace Lacasera\ElasticBridge\Query\Validators;

class MatchValidator implements ValidatorInterface
{
    public function handle(array $payload)
    {
        $keys = data_get($payload, 'body.query.match');

        // throw exception
    }
}
