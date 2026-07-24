<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Query\Validators;

use Lacasera\ElasticBridge\Exceptions\InvalidQuery;
use Override;

class BoolValidator implements ValidatorInterface
{
    /**
     * @throws InvalidQuery
     */
    #[Override]
    public function handle(array $payload): void
    {
        $allowed = ['must', 'should', 'must_not', 'filter', 'minimum_should_match', 'boost'];

        $clauses = data_get($payload, 'body.query.bool');

        if (! is_array($clauses) || $clauses === []) {
            throw new InvalidQuery(
                'boolean query must contain at least one clause (must, should, must_not or filter).'
            );
        }

        $invalid = array_diff(array_keys($clauses), $allowed);

        if ($invalid !== []) {
            throw new InvalidQuery(
                'invalid boolean clause(s): '.implode(', ', $invalid).'. allowed: '.implode(', ', $allowed).'.'
            );
        }
    }
}
