<?php

namespace Lacasera\ElasticBridge\Query\Validators;

use Lacasera\ElasticBridge\Exceptions\MalformedQueryException;

class BoolValidator implements ValidatorInterface
{
    /**
     * @throws MalformedQueryException
     */
    public function handle(array $payload)
    {
        $required = ['should', 'must'];

        $keys = data_get($payload, 'body.query.bool');

        if (count(array_diff(array_keys($keys), $required))) {
            throw new MalformedQueryException(
                'boolean term level must have a must or should clause. consider using boolean query method'
            );
        }
    }
}
