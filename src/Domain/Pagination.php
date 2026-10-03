<?php

declare(strict_types=1);

namespace App\Domain;

final readonly class Pagination
{
    public const DEFAULT_LIMIT = 10;
    public const MAX_LIMIT = 100;
    public const MAX_PAGE = 10000;

    public int $page;
    public int $limit;

    public function __construct(int $page = 1, int $limit = self::DEFAULT_LIMIT)
    {
        $this->page = max(1, min($page, self::MAX_PAGE));
        $this->limit = max(1, min($limit, self::MAX_LIMIT));
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->limit;
    }
}
