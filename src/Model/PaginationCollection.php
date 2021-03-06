<?php

namespace App\Model;

class PaginationCollection
{
    private $collection = [];

    private $total = 0;

    public function getCollection(): array
    {
        return $this->collection;
    }

    public function setCollection(array $collection): void
    {
        $this->collection = $collection;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function setTotal(int $total): void
    {
        $this->total = $total;
    }
}
