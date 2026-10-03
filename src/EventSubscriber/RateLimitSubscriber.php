<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;

/** Per-client-IP sliding-window limit on every /api request except health checks. */
final class RateLimitSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RateLimiterFactory $apiLimiter,
        #[Autowire(env: 'bool:RATE_LIMIT_ENABLED')]
        private readonly bool $enabled,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        // Before routing (32) so that scanners hitting unknown URLs are limited too.
        return [KernelEvents::REQUEST => ['onRequest', 40]];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$this->enabled || !$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        if (!str_starts_with($path, '/api/') || str_starts_with($path, '/api/health') || $request->isMethod('OPTIONS')) {
            return;
        }

        $limit = $this->apiLimiter->create($request->getClientIp() ?? 'unknown')->consume();

        if (!$limit->isAccepted()) {
            $retryAfter = max(1, $limit->getRetryAfter()->getTimestamp() - time());

            throw new TooManyRequestsHttpException($retryAfter, 'Rate limit exceeded. Try again later.');
        }
    }
}
