<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class SearchApiTest extends ApiTestCase
{
    public function testSearchMatchesTitle(): void
    {
        self::assertSame([3], $this->column($this->get('/api/search?q=water'), 'id'));
    }

    public function testSearchIsStemmed(): void
    {
        self::assertSame([3], $this->column($this->get('/api/search?q=lily'), 'id'));
    }

    public function testSearchMatchesAuthorName(): void
    {
        self::assertSame([1, 2, 5], $this->column($this->get('/api/search?q=rembrandt'), 'id'));
    }

    public function testSearchSyntax(): void
    {
        self::assertSame([2], $this->column($this->get('/api/search?q=' . urlencode('"night watch" -etching')), 'id'));
    }

    public function testSearchCombinedWithFilters(): void
    {
        self::assertSame([4], $this->column($this->get('/api/search?q=sunrise&ty=2'), 'id'));
        self::assertSame([], $this->column($this->get('/api/search?q=sunrise&ty=1'), 'id'));
    }

    public function testNoResultsIsEmpty200(): void
    {
        $response = $this->request('GET', '/api/search?q=xylophone');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(0, $this->decode($response)['pagination']['total']);
    }

    public function testSqlMetacharactersAreHarmless(): void
    {
        $response = $this->request('GET', '/api/search?q=' . urlencode("'; DROP TABLE art; --"));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(5, $this->get('/api/art/all')['pagination']['total']);
    }

    public function testQueryIsRequiredAndBounded(): void
    {
        self::assertSame(400, $this->request('GET', '/api/search')->getStatusCode());
        self::assertSame(400, $this->request('GET', '/api/search?q=' . str_repeat('a', 201))->getStatusCode());
        self::assertSame(400, $this->request('GET', '/api/search?q[]=a')->getStatusCode());
    }

    public function testFirstPageSearchIsLoggedAfterTheResponse(): void
    {
        $term = 'logcheck' . bin2hex(random_bytes(3));
        $this->request('GET', '/api/search?q=' . $term);

        $logs = $this->decode($this->request('GET', '/api/logs?category=search', null, ['HTTP_X_API_KEY' => self::API_KEY]));

        self::assertContains($term, $this->column($logs, 'value'));
    }

    public function testFilter(): void
    {
        self::assertSame([3, 4], $this->column($this->get('/api/filter?au=2'), 'id'));
        self::assertSame([1, 2], $this->column($this->get('/api/filter?au=1&fo=1'), 'id'));
        self::assertSame(400, $this->request('GET', '/api/filter')->getStatusCode());
        self::assertSame(400, $this->request('GET', '/api/filter?au=-1')->getStatusCode());
    }

    public function testRandom(): void
    {
        self::assertCount(1, $this->get('/api/random')['records']);
        self::assertCount(3, $this->get('/api/random?limit=3')['records']);
        self::assertCount(5, $this->get('/api/random?limit=500')['records']);
    }
}
