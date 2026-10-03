<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Http\RequireApiKey;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Guards controllers marked #[RequireApiKey]. When LOGS_API_KEY is empty the
 * protected endpoints are disabled and behave as if they did not exist.
 */
final class ApiKeySubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire(env: 'LOGS_API_KEY')]
        private readonly string $apiKey,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::CONTROLLER => 'onController'];
    }

    public function onController(ControllerEvent $event): void
    {
        if (!isset($event->getAttributes()[RequireApiKey::class])) {
            return;
        }

        if ($this->apiKey === '') {
            throw new NotFoundHttpException();
        }

        $provided = (string) $event->getRequest()->headers->get('X-API-Key', '');

        if (!hash_equals($this->apiKey, $provided)) {
            throw new UnauthorizedHttpException('X-API-Key', 'A valid X-API-Key header is required.');
        }
    }
}
