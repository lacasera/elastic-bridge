<?php

namespace Lacasera\ElasticBridge\Concerns;

class PaginatedCollection extends Collection
{
    /**
     * @return array
     */
    public function links()
    {
        return [
            'previous' => $this->previousSort(),

            'next' => $this->nextSort(),

            'total' => $this->total(),
        ];
    }

    /**
     * @return array|mixed
     */
    public function total()
    {
        return data_get($this->first()->getMeta(), 'value');
    }

    /**
     * @return array|mixed
     */
    public function previousSort()
    {
        return data_get($this->first()->toArray(), 'sort');
    }

    /**
     * @return array|mixed
     */
    public function nextSort()
    {
        return data_get($this->last()->toArray(), 'sort');
    }
}
