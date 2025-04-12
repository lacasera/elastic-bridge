<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge;

class SimplePaginatedCollection extends Collection
{
    protected $pagination = [];

    /**
     * @return array|mixed
     */
    public function nextPage()
    {
        return $this->size() + $this->from();
    }

    /**
     * @return bool|int
     */
    public function previousPage()
    {
        $result =  $this->from() - $this->size();

        return $result > 0 ?: 0;
    }

    /**
     * @return bool
     */
    public function hasMorePages()
    {
        return ($this->size() + $this->from()) < $this->total();
    }

    public function hasPreviousPage()
    {
        return $this->from() > 0;
    }

    /**
     * @return array
     */
    public function links()
    {
        $links = [];

        for ($page = 0; $page < $this->totalPages(); $page++) {
            $links['pages'][] = [
                'page' => $page,
                'from' => $page * $this->size()
            ];
        }

        $links['current_page'] = $this->currentPage();
        $links['has_more'] = $this->hasMorePages();
        $links['has_previous'] = $this->hasPreviousPage();
        $links['total_pages'] = $this->totalPages();

        return $links;
    }

    /**
     * @return int
     */
    public function currentPage(): int
    {
        $from = $this->from();
        $size = $this->size();

        if(!$from || $size) {
            return 1;
        }

       return (int) floor($from / $size) + 1;
    }

    /**
     * @return int
     */
    public function totalPages()
    {
        return (int) ceil($this->total() / $this->size());
    }

    public function setPagination($data = null)
    {
        $this->pagination = $data ?? [];
    }

    /**
     * @return array|mixed
     */
    protected function size()
    {
        return data_get($this->pagination, 'size');
    }

    /**
     * @return array|mixed
     */
    protected function from()
    {
        return data_get($this->pagination, 'from');
    }
}
