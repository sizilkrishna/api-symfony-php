<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class InfoApiTest extends ApiTestCase
{
    public function testAuthorsHaveCountsSchoolAndSkipAuthorsWithoutArt(): void
    {
        $body = $this->get('/api/info/author');

        self::assertSame(2, $body['pagination']['total']);
        self::assertSame(['MONET, Claude', 'REMBRANDT Harmenszoon van Rijn'], $this->column($body, 'author'));
        self::assertSame([2, 3], $this->column($body, 'count'));
        self::assertSame(['French', 'Dutch'], $this->column($body, 'school'));
    }

    public function testAuthorsByLetterIsCaseInsensitive(): void
    {
        self::assertSame(['MONET, Claude'], $this->column($this->get('/api/info/author/m'), 'author'));
        self::assertSame(['MONET, Claude'], $this->column($this->get('/api/info/author/M'), 'author'));
        self::assertSame([], $this->get('/api/info/author/z')['records']);
    }

    public function testAuthorItem(): void
    {
        $record = $this->get('/api/info/author/1')['record'];

        self::assertSame(3, $record['count']);
        self::assertSame('Dutch', $record['school']);
    }

    public function testAuthorWithoutArtworksStillResolvesWithZeroCount(): void
    {
        $record = $this->get('/api/info/author/3')['record'];

        self::assertSame(0, $record['count']);
        self::assertNull($record['school']);
    }

    public function testTaxonomyListsAndFeatureImage(): void
    {
        $types = $this->get('/api/info/type');

        self::assertSame(['landscape', 'portrait'], $this->column($types, 'type'));
        self::assertSame([null, 'https://example.org/a/rembrandt/1.jpg'], $this->column($types, 'fimage'));

        foreach (['form', 'location', 'school', 'timeframe'] as $name) {
            self::assertSame(2, $this->get('/api/info/' . $name)['pagination']['total'], $name);
        }
    }

    public function testTaxonomyItem(): void
    {
        $record = $this->get('/api/info/location/1')['record'];

        self::assertSame(['id' => 1, 'location' => 'Amsterdam', 'fimage' => null, 'count' => 3], $record);
    }

    public function testUnknownTaxonomyItemIs404(): void
    {
        self::assertSame(404, $this->request('GET', '/api/info/type/99')->getStatusCode());
        self::assertSame(404, $this->request('GET', '/api/info/bogus')->getStatusCode());
        self::assertSame(404, $this->request('GET', '/api/info/author/ab')->getStatusCode());
    }

    public function testPagination(): void
    {
        $body = $this->get('/api/info/author?limit=1&page=2');

        self::assertSame(['REMBRANDT Harmenszoon van Rijn'], $this->column($body, 'author'));
        self::assertSame(2, $body['pagination']['pages']);
    }
}
