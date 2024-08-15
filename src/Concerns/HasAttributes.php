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
    public function setRawAttributes(array $attributes = [], array $meta = [], bool $sync = true)
    {
        $this->attributes = $attributes;

        $this->meta = $meta;

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

    public function getMeta(): array
    {
        return $this->meta;
    }
}
