<?php

namespace Lacasera\ElasticBridge\Query\Validators;

interface ValidatorInterface
{
    public function handle(array $payload);
}
