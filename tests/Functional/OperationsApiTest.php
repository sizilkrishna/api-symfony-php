<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Database\ConnectionFactory;
use App\Database\Migrator;

final class OperationsApiTest extends ApiTestCase
{
    private const JSON = ['CONTENT_TYPE' => 'application/json'];

    protected function tearDown(): void
    {
        foreach (['RATE_LIMIT_ENABLED' => '0', 'RATE_LIMIT_API_PER_MINUTE' => '120', 'API_KEY_CASE' => 'lower'] as $key => $value) {
            $_SERVER[$key] = $_ENV[$key] = $value;
            putenv("$key=$value");
        }

        parent::tearDown();
    }

    public function testHealth(): void
    {
        self::assertSame('ok', $this->get('/api/health')['status']);
        self::assertSame('ready', $this->get('/api/health/ready')['status']);
    }

    public function testLoggerValidation(): void
    {
        $post = fn (string $body) => $this->request('POST', '/api/logger', $body, self::JSON)->getStatusCode();

        self::assertSame(201, $post('{"category":"it-test","value":"hello wörld"}'));
        self::assertSame(400, $post('{not json'));
        self::assertSame(400, $post('[]'));
        self::assertSame(400, $post('{"category":"it-test"}'));
        self::assertSame(400, $post('{"category":"bad category!","value":"x"}'));
        self::assertSame(400, $post(json_encode(['category' => 'it-test', 'value' => str_repeat('x', 1001)], JSON_THROW_ON_ERROR)));
    }

    public function testLogsRequireTheApiKey(): void
    {
        self::assertSame(401, $this->request('GET', '/api/logs')->getStatusCode());
        self::assertSame(401, $this->request('GET', '/api/logs', null, ['HTTP_X_API_KEY' => 'wrong'])->getStatusCode());
        self::assertSame(401, $this->request('GET', '/api/logs', null, ['HTTP_X_API_KEY' => ''])->getStatusCode());
    }

    public function testLoggedEntriesAreReadable(): void
    {
        $value = 'entry-' . bin2hex(random_bytes(4));
        $this->request('POST', '/api/logger', json_encode(['category' => 'it-test', 'value' => $value], JSON_THROW_ON_ERROR), self::JSON);

        $response = $this->request('GET', '/api/logs?category=it-test', null, ['HTTP_X_API_KEY' => self::API_KEY]);
        $body = $this->decode($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertContains($value, $this->column($body, 'value'));
        self::assertTrue($response->headers->hasCacheControlDirective('no-store'));
        self::assertSame(400, $this->request('GET', '/api/logs?category=bad!', null, ['HTTP_X_API_KEY' => self::API_KEY])->getStatusCode());
    }

    public function testRateLimitReturns429WithRetryAfter(): void
    {
        foreach (['RATE_LIMIT_ENABLED' => '1', 'RATE_LIMIT_API_PER_MINUTE' => '3'] as $key => $value) {
            $_SERVER[$key] = $_ENV[$key] = $value;
            putenv("$key=$value");
        }

        $ip = '10.' . random_int(1, 250) . '.' . random_int(1, 250) . '.' . random_int(1, 250);
        $codes = [];
        for ($i = 0; $i < 5; ++$i) {
            $response = $this->request('GET', '/api/art/all?limit=1', null, ['REMOTE_ADDR' => $ip]);
            $codes[] = $response->getStatusCode();
        }

        self::assertSame([200, 200, 200, 429, 429], $codes);
        self::assertNotNull($response->headers->get('Retry-After'));
        self::assertSame(200, $this->request('GET', '/api/health', null, ['REMOTE_ADDR' => $ip])->getStatusCode(), 'health checks are never limited');
    }

    public function testKeyCaseCanBeSwitchedToUpper(): void
    {
        $_SERVER['API_KEY_CASE'] = $_ENV['API_KEY_CASE'] = 'upper';
        putenv('API_KEY_CASE=upper');

        $record = $this->get('/api/art/all?limit=1')['records'][0];

        self::assertArrayHasKey('TITLE', $record);
        self::assertArrayNotHasKey('title', $record);
    }

    public function testMigrationsAreIdempotent(): void
    {
        $connection = ConnectionFactory::create((string) $_SERVER['DATABASE_URL']);
        $migrator = new Migrator($connection, dirname(__DIR__, 2) . '/migrations');

        self::assertSame([], $migrator->migrate());
    }
}
