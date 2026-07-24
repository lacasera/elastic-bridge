<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Query\Validators;

use Lacasera\ElasticBridge\Exceptions\InvalidQuery;
use Override;

class TermsSetValidator implements ValidatorInterface
{
    /**
     * @throws InvalidQuery
     */
    #[Override]
    public function handle(array $payload): void
    {
        $termsSet = data_get($payload, 'body.query.terms_set');

        if (! is_array($termsSet) || $termsSet === []) {
            throw new InvalidQuery('terms_set query must target at least one field.');
        }
    }
}
