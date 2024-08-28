<?php

declare(strict_types=1);

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

        $value = data_get($this->attributes['_source'], $key);

        if (is_array($value)) {
            return json_decode(json_encode($value), false);
        }

        return $value;
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
