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
     * @return $this
     */
    public function setRawAttributes(array $attributes = [], bool $sync = true)
    {

        $this->attributes = $attributes;

        $this->meta = data_get($attributes, 'total');

        return $this;
    }

    /**
     * @return array|mixed
     */
    public function getAttribute($key)
    {
        return data_get($this->attributes['_source'], $key);
    }

    public function getScore(): ?float
    {
        return data_get($this->attributes, '_score');
    }
}
