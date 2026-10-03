<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Pagination;
use App\Http\ApiResponder;
use App\Http\RequireApiKey;
use App\Repository\LogRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[Route('/api')]
final class LogController
{
    private const MAX_VALUE_LENGTH = 1000;

    public function __construct(
        private readonly LogRepository $logs,
        private readonly ApiResponder $responder,
        private readonly RateLimiterFactory $loggerLimiter,
        #[Autowire(env: 'bool:RATE_LIMIT_ENABLED')]
        private readonly bool $rateLimitEnabled,
    ) {
    }

    #[Route('/logger', name: 'api_logger', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        if ($this->rateLimitEnabled) {
            $limit = $this->loggerLimiter->create($request->getClientIp() ?? 'unknown')->consume();

            if (!$limit->isAccepted()) {
                $retryAfter = max(1, $limit->getRetryAfter()->getTimestamp() - time());

                throw new TooManyRequestsHttpException($retryAfter, 'Too many log requests. Try again later.');
            }
        }

        $payload = $this->payload($request);
        $category = $payload['category'] ?? null;
        $value = $payload['value'] ?? null;

        if (!\is_string($category) || !\is_string($value)) {
            throw new BadRequestHttpException('"category" and "value" are required strings.');
        }

        $category = trim($category);
        $value = trim($value);

        if (!preg_match('/^[A-Za-z0-9_.:-]{1,50}$/', $category)) {
            throw new BadRequestHttpException('"category" must be 1-50 characters of A-Z a-z 0-9 _ . : -');
        }

        if ($value === '' || mb_strlen($value) > self::MAX_VALUE_LENGTH
            || !mb_check_encoding($value, 'UTF-8') || str_contains($value, "\0")) {
            throw new BadRequestHttpException(sprintf('"value" must be 1-%d valid characters.', self::MAX_VALUE_LENGTH));
        }

        $this->logs->insert($category, $value, $request->getClientIp());

        return $this->responder->data(['success' => true, 'message' => 'Query logged'], 0, 201);
    }

    #[Route('/logs', name: 'api_logs', methods: ['GET'])]
    #[RequireApiKey]
    public function list(Request $request, Pagination $pagination): JsonResponse
    {
        $category = $request->query->all()['category'] ?? null;

        if ($category !== null && (!\is_string($category) || !preg_match('/^[A-Za-z0-9_.:-]{1,50}$/', $category))) {
            throw new BadRequestHttpException('"category" is invalid.');
        }

        return $this->responder->page($this->logs->list($category, $pagination));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Request $request): array
    {
        $content = $request->getContent();

        if ($content === '') {
            return $request->request->all();
        }

        try {
            $payload = json_decode($content, true, 8, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new BadRequestHttpException('Request body must be valid JSON.');
        }

        if (!\is_array($payload)) {
            throw new BadRequestHttpException('Request body must be a JSON object.');
        }

        return $payload;
    }
}
