<?php

namespace elish\helpers;

class ListView
{
    private $current;
    private $pageSize;

    /**
     * @var string 当前页参数
     */
    private $pageKey = 'page';
    private $totalRecords;

    /**
     * @var int 前后显示页数
     */
    private $adjacent = 2;

    /**
     * @param int $pageSize
     * @param int $totalRecords
     */
    public function __construct(int $totalRecords, int $pageSize = 20)
    {
        $this->totalRecords = $totalRecords;
        $this->pageSize = $pageSize;
    }


    /**
     * @return null
     */
    public function getCurrent(): int
    {
        if ($this->current) {
            return $this->current;
        } else {
            return $_GET[$this->pageKey] ?? 1;
        }
    }

    /**
     * @param null $current
     * @return ListView
     */
    public function setCurrent($current): ListView
    {
        $this->current = $current;
        return $this;
    }

    /**
     * @return int
     */
    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    /**
     * @param int $pageSize
     * @return ListView
     */
    public function setPageSize(int $pageSize): ListView
    {
        $this->pageSize = $pageSize;
        return $this;
    }

    /**
     * @return string
     */
    public function getPageKey(): string
    {
        return $this->pageKey;
    }

    /**
     * @param string $pageKey
     * @return ListView
     */
    public function setPageKey(string $pageKey): ListView
    {
        $this->pageKey = $pageKey;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getOffset()
    {
        return ($this->current - 1) * $this->pageSize;
    }

    /**
     * @return mixed
     */
    public function getStartRecord()
    {
        return $this->totalRecords ? $this->getOffset() + 1 : 0;
    }

    /**
     * @return mixed
     */
    public function getEndRecord()
    {
        if ($this->getOffset() + $this->pageSize > $this->totalRecords) {
            return $this->totalRecords;
        } else {
            return $this->getOffset() + $this->pageSize;
        }
    }

    /**
     * @return mixed
     */
    public function getTotalRecords(): int
    {
        return $this->totalRecords;
    }

    /**
     * @param mixed $totalRecords
     * @return ListView
     */
    public function setTotalRecords($totalRecords): ListView
    {
        $this->totalRecords = $totalRecords;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTotalPages()
    {
        return intval(ceil($this->totalRecords / $this->pageSize));
    }

    /**
     * @return int
     */
    public function getAdjacent(): int
    {
        return $this->adjacent;
    }

    /**
     * @param int $adjacent
     * @return ListView
     */
    public function setAdjacent(int $adjacent): ListView
    {
        $this->adjacent = $adjacent;
        return $this;
    }

}
