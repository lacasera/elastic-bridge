<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\DTO;

class Bucket
{
    protected $key;

    protected $doc_count;

    protected $to;

    protected $from;

    public function __construct(array $item)
    {
        foreach ($item as $key => $value) {
            $this->{$key} = $value;
        }
    }

    public function count(): ?float
    {
        return $this->doc_count;
    }

    public function key(): ?string
    {
        return $this->key;
    }

    public function to(): ?float
    {
        return $this->to;
    }

    public function from(): ?float
    {
        return $this->from;
    }
}
