<?php

namespace Lacasera\ElasticBridge\Concerns;

trait HasAttributes
{
    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * @var array
     */
    protected $meta = [];

    /**
     * @param array $attributes
     * @param bool $sync
     * @return $this
     */
    public function setRawAttributes(array $attributes = [], bool $sync = true)
    {

        $this->attributes = $attributes;

        $this->meta = data_get($attributes, 'total');
        return $this;
    }

    /**
     * @param $key
     * @return array|mixed
     */
    public function getAttribute($key)
    {
        return data_get($this->attributes['_source'], $key);
    }

    /**
     * @return float|null
     */
    public function getScore(): float|null
    {
        return data_get($this->attributes, '_score');
    }
}
