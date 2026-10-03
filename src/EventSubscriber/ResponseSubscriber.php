<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/** Security headers on every response and ETag / 304 handling for cacheable JSON. */
final class ResponseSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => ['onResponse', -10]];
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();
        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Referrer-Policy', 'no-referrer');
        $headers->set('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'");

        if ($response instanceof JsonResponse
            && $request->isMethodCacheable()
            && $response->getStatusCode() === 200
            && !$headers->has('ETag')
            && !$headers->hasCacheControlDirective('no-store')
        ) {
            $response->setEtag(md5((string) $response->getContent()));
            $response->isNotModified($request);
        }
    }
}
