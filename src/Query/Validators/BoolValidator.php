<?php
declare(strict_types=1);

namespace Lacasera\ElasticBridge\Query\Validators;

use Lacasera\ElasticBridge\Exceptions\InvalidQuery;

class BoolValidator implements ValidatorInterface
{
    /**
     * @throws InvalidQuery
     */
    public function handle(array $payload)
    {
        $required = ['should', 'must'];

        $keys = data_get($payload, 'body.query.bool');

        if (count(array_diff(array_keys($keys), $required))) {
            throw new InvalidQuery(
                'boolean term level must have a must or should clause. consider using boolean query method'
            );
        }
    }
}
