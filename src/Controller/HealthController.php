<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\ApiResponder;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[Route('/api/health', methods: ['GET'])]
final class HealthController
{
    public function __construct(
        private readonly ApiResponder $responder,
        private readonly Connection $db,
        private readonly LoggerInterface $logger,
    ) {
    }

    /** Liveness: the process is up. Does not touch the database. */
    #[Route('', name: 'api_health')]
    public function live(): JsonResponse
    {
        return $this->responder->data(['status' => 'ok']);
    }

    /** Readiness: the database answers. */
    #[Route('/ready', name: 'api_health_ready')]
    public function ready(): JsonResponse
    {
        try {
            $this->db->fetchOne('SELECT 1');
        } catch (\Throwable $e) {
            $this->logger->error('Readiness check failed.', ['exception' => $e]);

            throw new ServiceUnavailableHttpException(null, 'Database unavailable.');
        }

        return $this->responder->data(['status' => 'ready']);
    }
}
