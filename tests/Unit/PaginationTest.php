<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Pagination;
use PHPUnit\Framework\TestCase;

final class PaginationTest extends TestCase
{
    public function testDefaults(): void
    {
        $p = new Pagination();

        self::assertSame(1, $p->page);
        self::assertSame(10, $p->limit);
        self::assertSame(0, $p->offset());
    }

    public function testOffset(): void
    {
        self::assertSame(40, (new Pagination(5, 10))->offset());
    }

    public function testValuesAreClamped(): void
    {
        $p = new Pagination(-3, 5000);

        self::assertSame(1, $p->page);
        self::assertSame(Pagination::MAX_LIMIT, $p->limit);
        self::assertSame(Pagination::MAX_PAGE, (new Pagination(10 ** 9))->page);
    }
}
