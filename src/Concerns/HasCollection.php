<?php
declare(strict_types=1);

namespace Lacasera\ElasticBridge\Concerns;

trait HasCollection
{
    public function newCollection(array $models = [])
    {
        return new static::$collectionClass($models);
    }
}
