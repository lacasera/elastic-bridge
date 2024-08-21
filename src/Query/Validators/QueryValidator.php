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

        (new $classname)->handle($payload);
    }
}
