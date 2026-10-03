<?php

declare(strict_types=1);

namespace App\Http;

use App\Domain\Page;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Builds every JSON response so the envelope, key casing, encoding flags and
 * cache headers are consistent across the API.
 */
final class ApiResponder
{
    private const JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
        | JSON_HEX_TAG | JSON_HEX_AMP;

    private readonly bool $upper;

    public function __construct(#[Autowire(env: 'API_KEY_CASE')] string $keyCase)
    {
        if (!\in_array($keyCase, ['lower', 'upper'], true)) {
            throw new \InvalidArgumentException('API_KEY_CASE must be "lower" or "upper".');
        }

        $this->upper = $keyCase === 'upper';
    }

    public function page(Page $page, int $ttl = 0): JsonResponse
    {
        return $this->data([
            'success' => true,
            'records' => array_map($this->row(...), $page->records),
            'pagination' => [
                'total' => $page->total,
                'page' => $page->pagination->page,
                'limit' => $page->pagination->limit,
                'pages' => $page->pages(),
            ],
        ], $ttl);
    }

    /**
     * @param array<string, mixed> $record
     */
    public function item(array $record, int $ttl = 0): JsonResponse
    {
        return $this->data(['success' => true, 'record' => $this->row($record)], $ttl);
    }

    /**
     * @param list<array<string, mixed>> $records
     */
    public function records(array $records, int $ttl = 0): JsonResponse
    {
        return $this->data(['success' => true, 'records' => array_map($this->row(...), $records)], $ttl);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function data(array $payload, int $ttl = 0, int $status = 200): JsonResponse
    {
        $response = new JsonResponse(null, $status);
        $response->setEncodingOptions(self::JSON_FLAGS);
        $response->setData($payload);

        if ($ttl > 0) {
            $response->setPublic();
            $response->setMaxAge($ttl);
            $response->setSharedMaxAge($ttl);
        } else {
            $response->headers->set('Cache-Control', 'no-store');
        }

        return $response;
    }

    /**
     * RFC 9457 problem details (with an extra "success": false for convenience).
     *
     * @param array<string, string> $headers
     */
    public static function problem(int $status, string $title, ?string $detail = null, array $headers = []): JsonResponse
    {
        $body = ['type' => 'about:blank', 'title' => $title, 'status' => $status, 'success' => false];

        if ($detail !== null) {
            $body['detail'] = $detail;
        }

        $response = new JsonResponse(null, $status, $headers);
        $response->setEncodingOptions(self::JSON_FLAGS);
        $response->setData($body);
        $response->headers->set('Content-Type', 'application/problem+json');
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function row(array $row): array
    {
        return array_change_key_case($row, $this->upper ? CASE_UPPER : CASE_LOWER);
    }
}
