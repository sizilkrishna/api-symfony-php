<?php

declare(strict_types=1);

namespace App\Domain;

final readonly class Page
{
    /**
     * @param list<array<string, mixed>> $records
     */
    public function __construct(
        public array $records,
        public int $total,
        public Pagination $pagination,
    ) {
    }

    public function pages(): int
    {
        return (int) ceil($this->total / $this->pagination->limit);
    }
}
