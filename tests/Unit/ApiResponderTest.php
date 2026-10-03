<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Page;
use App\Domain\Pagination;
use App\Http\ApiResponder;
use PHPUnit\Framework\TestCase;

final class ApiResponderTest extends TestCase
{
    public function testPageEnvelopeAndCaching(): void
    {
        $page = new Page([['ID' => 1, 'Title' => 'é/x']], 25, new Pagination(2, 10));
        $response = (new ApiResponder('lower'))->page($page, 60);
        $body = json_decode((string) $response->getContent(), true);

        self::assertTrue($body['success']);
        self::assertSame([['id' => 1, 'title' => 'é/x']], $body['records']);
        self::assertSame(['total' => 25, 'page' => 2, 'limit' => 10, 'pages' => 3], $body['pagination']);
        self::assertStringContainsString('é/x', (string) $response->getContent(), 'unicode and slashes stay unescaped');
        self::assertTrue($response->headers->hasCacheControlDirective('public'));
        self::assertSame('60', $response->headers->getCacheControlDirective('max-age'));
    }

    public function testUpperCaseKeys(): void
    {
        $body = json_decode((string) (new ApiResponder('upper'))->item(['id' => 1, 'title' => 'x'])->getContent(), true);

        self::assertSame(['ID' => 1, 'TITLE' => 'x'], $body['record']);
    }

    public function testInvalidKeyCaseIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ApiResponder('camel');
    }

    public function testInvalidUtf8IsSubstitutedInsteadOfFailing(): void
    {
        $response = (new ApiResponder('lower'))->records([['title' => "bad \xff byte"]]);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString("bad \u{FFFD} byte", (string) $response->getContent());
    }

    public function testProblemDetails(): void
    {
        $response = ApiResponder::problem(404, 'Not Found', 'Nope.');
        $body = json_decode((string) $response->getContent(), true);

        self::assertSame('application/problem+json', $response->headers->get('Content-Type'));
        self::assertSame(['type' => 'about:blank', 'title' => 'Not Found', 'status' => 404, 'success' => false, 'detail' => 'Nope.'], $body);
    }
}
