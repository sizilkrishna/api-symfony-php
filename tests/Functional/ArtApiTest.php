<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class ArtApiTest extends ApiTestCase
{
    public function testListEnvelopeAndHeaders(): void
    {
        $response = $this->request('GET', '/api/art/all');
        $body = $this->decode($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->headers->get('Content-Type'));
        self::assertTrue($body['success']);
        self::assertSame([1, 2, 3, 4, 5], $this->column($body, 'id'));
        self::assertSame(['total' => 5, 'page' => 1, 'limit' => 10, 'pages' => 1], $body['pagination']);
        self::assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        self::assertTrue($response->headers->hasCacheControlDirective('public'));
        self::assertNotNull($response->headers->get('ETag'));
    }

    public function testRecordShape(): void
    {
        $record = $this->get('/api/art/all?limit=1')['records'][0];

        self::assertSame(
            ['id', 'title', 'date', 'technique', 'url', 'author_id', 'author', 'born_died', 'form_id', 'form',
                'location_id', 'location', 'school_id', 'school', 'timeframe_id', 'timeframe', 'type_id', 'type'],
            array_keys($record),
        );
    }

    public function testPagination(): void
    {
        $body = $this->get('/api/art/all?limit=2&page=3');

        self::assertSame([5], $this->column($body, 'id'));
        self::assertSame(['total' => 5, 'page' => 3, 'limit' => 2, 'pages' => 3], $body['pagination']);
    }

    public function testPageBeyondTheEndIsAnEmpty200(): void
    {
        $response = $this->request('GET', '/api/art/all?limit=2&page=50');
        $body = $this->decode($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame([], $body['records']);
        self::assertSame(5, $body['pagination']['total']);
    }

    public function testLimitIsClamped(): void
    {
        self::assertSame(100, $this->get('/api/art/all?limit=100000')['pagination']['limit']);
    }

    public function testInvalidPaginationIs400ProblemJson(): void
    {
        foreach (['page=0', 'page=abc', 'limit=abc', 'page[]=1'] as $query) {
            $response = $this->request('GET', '/api/art/all?' . $query);
            $body = $this->decode($response);

            self::assertSame(400, $response->getStatusCode(), $query);
            self::assertSame('application/problem+json', $response->headers->get('Content-Type'));
            self::assertFalse($body['success']);
            self::assertSame(400, $body['status']);
        }
    }

    public function testItem(): void
    {
        $body = $this->get('/api/art/all/2');

        self::assertSame('The Night Watch', $body['record']['title']);
        self::assertSame('REMBRANDT Harmenszoon van Rijn', $body['record']['author']);
    }

    public function testItemNotFound(): void
    {
        $response = $this->request('GET', '/api/art/all/999');

        self::assertSame(404, $response->getStatusCode());
        self::assertFalse($this->decode($response)['success']);
    }

    public function testOversizedIdIsA404NotA500(): void
    {
        self::assertSame(404, $this->request('GET', '/api/art/all/99999999999999999999')->getStatusCode());
    }

    public function testByDimension(): void
    {
        self::assertSame([1, 2, 5], $this->column($this->get('/api/art/author/1'), 'id'));
        self::assertSame([3, 4], $this->column($this->get('/api/art/school/2'), 'id'));
        self::assertSame([5], $this->column($this->get('/api/art/form/2'), 'id'));
        self::assertSame([1, 2, 5], $this->column($this->get('/api/art/type/1'), 'id'));
        self::assertSame([3, 4], $this->column($this->get('/api/art/timeframe/2'), 'id'));
        self::assertSame([3, 4], $this->column($this->get('/api/art/location/2'), 'id'));
    }

    public function testByDimensionWithoutMatchesIsEmpty200(): void
    {
        $response = $this->request('GET', '/api/art/author/3');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(0, $this->decode($response)['pagination']['total']);
    }

    public function testUnknownDimensionIs404(): void
    {
        self::assertSame(404, $this->request('GET', '/api/art/bogus/1')->getStatusCode());
    }

    public function testConditionalGetReturns304(): void
    {
        $etag = (string) $this->request('GET', '/api/art/all?limit=1')->headers->get('ETag');
        $response = $this->request('GET', '/api/art/all?limit=1', null, ['HTTP_IF_NONE_MATCH' => $etag]);

        self::assertSame(304, $response->getStatusCode());
        self::assertSame('', $response->getContent());
    }

    public function testWrongMethodIs405WithAllowHeader(): void
    {
        $response = $this->request('POST', '/api/art/all');

        self::assertSame(405, $response->getStatusCode());
        self::assertSame('GET', $response->headers->get('Allow'));
        self::assertSame('application/problem+json', $response->headers->get('Content-Type'));
    }

    public function testUnknownRouteIsProblemJson404(): void
    {
        $response = $this->request('GET', '/api/nope');

        self::assertSame(404, $response->getStatusCode());
        self::assertSame('application/problem+json', $response->headers->get('Content-Type'));
    }

    public function testCorsPreflightAndOriginPolicy(): void
    {
        $preflight = $this->request('OPTIONS', '/api/art/all', null, [
            'HTTP_ORIGIN' => 'http://localhost:3000',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
        ]);
        self::assertSame('http://localhost:3000', $preflight->headers->get('Access-Control-Allow-Origin'));

        $evil = $this->request('GET', '/api/art/all', null, ['HTTP_ORIGIN' => 'https://evil.example']);
        self::assertNull($evil->headers->get('Access-Control-Allow-Origin'));
    }
}
