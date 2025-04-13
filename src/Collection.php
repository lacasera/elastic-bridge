<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge;

use Illuminate\Support\Collection as BaseCollection;

class Collection extends BaseCollection
{
    /**
     * @return array|mixed
     */
    public function total()
    {
        return data_get($this->first()?->getMeta(), 'value');
    }

    /**
     * @return array
     */
    public function items()
    {
        return $this->all();
    }
}
