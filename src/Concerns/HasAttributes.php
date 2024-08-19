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
        if ($key == 'id' && ! array_key_exists('id', $this->attributes)) {
            return data_get($this->attributes, '_id');
        }

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
