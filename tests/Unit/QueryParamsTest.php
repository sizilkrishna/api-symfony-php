<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Http\QueryParams;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class QueryParamsTest extends TestCase
{
    /**
     * @param array<string, mixed> $query
     */
    private function request(array $query): Request
    {
        return new Request($query);
    }

    public function testPaginationDefaults(): void
    {
        $p = QueryParams::pagination($this->request([]));

        self::assertSame([1, 10], [$p->page, $p->limit]);
    }

    public function testLimitIsClampedButPageIsValidated(): void
    {
        $p = QueryParams::pagination($this->request(['page' => '2', 'limit' => '9999']));
        self::assertSame([2, 100], [$p->page, $p->limit]);

        $this->expectException(BadRequestHttpException::class);
        QueryParams::pagination($this->request(['page' => '0']));
    }

    public function testNonNumericIsRejected(): void
    {
        $this->expectException(BadRequestHttpException::class);
        QueryParams::pagination($this->request(['limit' => 'abc']));
    }

    public function testArraysAreRejected(): void
    {
        $this->expectException(BadRequestHttpException::class);
        QueryParams::int($this->request(['page' => ['1']]), 'page', 1, 1, 10);
    }

    public function testFilters(): void
    {
        $filters = QueryParams::filters($this->request(['au' => '5', 'ty' => '0', 'sc' => '2']));

        self::assertSame(['author' => 5, 'school' => 2], $filters);
    }

    public function testNegativeFilterIsRejected(): void
    {
        $this->expectException(BadRequestHttpException::class);
        QueryParams::filters($this->request(['au' => '-1']));
    }

    public function testSearchTerm(): void
    {
        self::assertNull(QueryParams::searchTerm($this->request([])));
        self::assertNull(QueryParams::searchTerm($this->request(['q' => '   '])));
        self::assertSame('night watch', QueryParams::searchTerm($this->request(['q' => '  night watch '])));
    }

    public function testSearchTermLimits(): void
    {
        $this->expectException(BadRequestHttpException::class);
        QueryParams::searchTerm($this->request(['q' => str_repeat('a', 201)]));
    }

    public function testSearchTermRejectsNullBytesAndBadEncoding(): void
    {
        foreach (["a\0b", "\xff\xfe"] as $bad) {
            try {
                QueryParams::searchTerm($this->request(['q' => $bad]));
                self::fail('Expected BadRequestHttpException');
            } catch (BadRequestHttpException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
