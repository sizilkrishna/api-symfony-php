<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Http\ApiResponder;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\DriverException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/** Turns every exception into an RFC 9457 problem+json response. */
final class ExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        #[Autowire('%kernel.debug%')]
        private readonly bool $debug,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => ['onException', 10]];
    }

    public function onException(ExceptionEvent $event): void
    {
        $e = $event->getThrowable();
        $headers = [];
        $detail = null;

        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();
            $headers = array_map(strval(...), $e->getHeaders());
            $detail = $e->getMessage() !== '' ? $e->getMessage() : null;
        } elseif ($e instanceof ConnectionException) {
            $status = Response::HTTP_SERVICE_UNAVAILABLE;
            $detail = 'The database is currently unavailable.';
        } elseif ($e instanceof DriverException && $e->getSQLState() === '57014') {
            $status = Response::HTTP_SERVICE_UNAVAILABLE;
            $detail = 'The query took too long. Please narrow your request.';
        } else {
            $status = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        if ($status >= 500) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            $detail = $detail ?? 'An unexpected error occurred.';

            if ($this->debug) {
                $detail .= sprintf(' [%s: %s]', $e::class, $e->getMessage());
            }
        }

        $title = Response::$statusTexts[$status] ?? 'Error';

        $event->setResponse(ApiResponder::problem($status, $title, $detail, $headers));
    }
}
