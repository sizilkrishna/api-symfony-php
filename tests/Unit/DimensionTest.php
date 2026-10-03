<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Dimension;
use PHPUnit\Framework\TestCase;

final class DimensionTest extends TestCase
{
    public function testQueryParamsAreUnique(): void
    {
        $params = array_map(static fn (Dimension $d) => $d->queryParam(), Dimension::cases());

        self::assertCount(6, $params);
        self::assertSame($params, array_unique($params));
    }

    public function testForeignKeysAreSafeIdentifiers(): void
    {
        foreach (Dimension::cases() as $dimension) {
            self::assertMatchesRegularExpression('/^[a-z_]+$/', $dimension->table());
            self::assertSame($dimension->value . '_id', $dimension->foreignKey());
        }
    }
}
