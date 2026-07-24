<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Query\Validators;

use Illuminate\Support\Str;

class QueryValidator
{
    public function validate(string $term, array $payload): void
    {
        $classname = Str::of($term)
            ->headline()
            ->replace(' ', '')
            ->prepend(__NAMESPACE__.'\\')
            ->append('Validator')
            ->value();

        // Only validate terms that have a dedicated validator. Terms without
        // one (ids, fuzzy, raw, range, ...) are passed through untouched.
        if (! class_exists($classname)) {
            return;
        }

        (new $classname)->handle($payload);
    }
}
