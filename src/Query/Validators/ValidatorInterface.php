<?php
declare(strict_types=1);

namespace Lacasera\ElasticBridge\Query\Validators;

interface ValidatorInterface
{
    public function handle(array $payload);
}
