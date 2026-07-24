<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge;

class PaginatedCollection extends Collection
{
    public function links(): array
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
        if ($this->isEmpty()) {
            return 0;
        }

        return data_get($this->first()->getMeta(), 'value');
    }

    /**
     * @return array|mixed
     */
    public function previousSort()
    {
        if ($this->isEmpty()) {
            return [];
        }

        return data_get($this->first()->getRawAttributes(), 'sort');
    }

    /**
     * @return array|mixed
     */
    public function nextSort()
    {
        if ($this->isEmpty()) {
            return [];
        }

        return data_get($this->last()->getRawAttributes(), 'sort');
    }
}
