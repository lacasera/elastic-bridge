<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Concerns;

trait HasCollection
{
    public function newCollection(array $items, array $pagination)
    {
        $collection = static::$collectionClass::make($items);

        if (method_exists($collection, 'setPagination')) {
            $collection->setPagination($pagination);
        }

        return $collection;
    }
}
