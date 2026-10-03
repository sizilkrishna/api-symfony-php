<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Repository\LogRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Records search terms after the response has been sent, so logging never
 * slows a request down and a logging failure can never break a search.
 */
final class SearchLogSubscriber implements EventSubscriberInterface
{
    public const ATTRIBUTE = '_log_search';

    public function __construct(
        private readonly LogRepository $logs,
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::TERMINATE => 'onTerminate'];
    }

    public function onTerminate(TerminateEvent $event): void
    {
        $request = $event->getRequest();
        $term = $request->attributes->get(self::ATTRIBUTE);

        if (!\is_string($term) || $event->getResponse()->getStatusCode() >= 400) {
            return;
        }

        try {
            $this->logs->insert('search', mb_substr($term, 0, 255), $request->getClientIp());
        } catch (\Throwable $e) {
            $this->logger->warning('Could not write search log entry.', ['exception' => $e]);
        }
    }
}
