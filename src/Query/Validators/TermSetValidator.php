<?php

namespace Lacasera\ElasticBridge\Query\Validators;

class TermSetValidator implements ValidatorInterface
{

    public function handle(array $payload)
    {
        $query = data_get($payload, 'body.query.match');

        if (!array_key_exists('', $query))
        dd($query);
    }
}
