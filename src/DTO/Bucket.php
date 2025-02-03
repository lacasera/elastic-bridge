<?php
declare(strict_types=1);

namespace Lacasera\ElasticBridge\DTO;


class Bucket
{
    protected $key = null;
    protected $doc_count = null;

    protected $to = null;

    protected $from = null;

    public function __construct(array $item)
    {
        foreach ($item as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * @return float|null
     */
    public function count(): ?float
    {
        return $this->doc_count;
    }

    /**
     * @return string|null
     */
    public function key(): ?string
    {
        return $this->key;
    }

    /**
     * @return float|null
     */
    public function to(): ?float
    {
        return $this->to;
    }

    /**
     * @return float|null
     */
    public function from(): ?float
    {
        return $this->from;
    }
}
