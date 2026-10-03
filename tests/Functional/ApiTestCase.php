<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Database\ConnectionFactory;
use App\Database\Migrator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\TerminableInterface;

/**
 * Boots the real kernel against a real PostgreSQL database (DATABASE_URL in
 * .env.test), migrates it and loads tests/Fixtures/seed.sql once per run.
 */
abstract class ApiTestCase extends KernelTestCase
{
    protected const API_KEY = 'test-key';

    private static bool $prepared = false;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (self::$prepared) {
            return;
        }

        $connection = ConnectionFactory::create((string) $_SERVER['DATABASE_URL']);
        (new Migrator($connection, dirname(__DIR__, 2) . '/migrations'))->migrate();
        $connection->executeStatement((string) file_get_contents(dirname(__DIR__) . '/Fixtures/seed.sql'));
        $connection->close();

        self::$prepared = true;
    }

    /**
     * @param array<string, string> $server
     */
    protected function request(string $method, string $uri, ?string $body = null, array $server = []): Response
    {
        self::bootKernel();
        $kernel = self::$kernel;
        self::assertInstanceOf(TerminableInterface::class, $kernel);

        $request = Request::create($uri, $method, [], [], [], $server + ['REMOTE_ADDR' => '10.0.0.5'], $body);
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        return $response;
    }

    /**
     * @return array<string, mixed>
     */
    protected function get(string $uri): array
    {
        return $this->decode($this->request('GET', $uri));
    }

    /**
     * @return array<string, mixed>
     */
    protected function decode(Response $response): array
    {
        $data = json_decode((string) $response->getContent(), true);
        self::assertIsArray($data, 'Response body is not JSON: ' . $response->getContent());

        return $data;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return list<mixed>
     */
    protected function column(array $payload, string $key): array
    {
        return array_column($payload['records'], $key);
    }
}
