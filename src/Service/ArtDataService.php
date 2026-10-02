<?php

namespace App\Service;

use PDOException;
use Symfony\Component\HttpFoundation\JsonResponse;

class ArtDataService
{
    public function __construct(private readonly DatabaseService $db)
    {
    }

    public function jsonPaginated(string $countSql, string $dataSql, array $bindings = [], int $page = 1, int $limit = 10): JsonResponse
    {
        $page = max(1, $page);
        $limit = max(1, min($limit, 100));
        $offset = ($page - 1) * $limit;

        try {
            $dataBindings = array_merge($bindings, [':lim' => $limit, ':offset' => $offset]);
            $total = $this->db->executeCount($countSql, $bindings);
            $records = $total > 0 ? $this->db->executeQuery($dataSql, $dataBindings) : [];

            if ($total > 0 && count($records) > 0) {
                return new JsonResponse([
                    'success' => true,
                    'records' => $records,
                    'pagination' => [
                        'total' => $total,
                        'page' => $page,
                        'limit' => $limit,
                        'pages' => (int) ceil($total / $limit),
                    ],
                ], 200);
            }

            return new JsonResponse(['success' => false, 'message' => 'No records found'], 204);
        } catch (PDOException $e) {
            return new JsonResponse(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
        }
    }

    public function logQuery(string $operation, string $queryParam): JsonResponse
    {
        $queryParam = urldecode($queryParam);
        $ip = $this->getClientIp();

        try {
            $this->db->executeQuery(
                'INSERT INTO LOG_TABLE (CATEGORY, VALUE, IP) VALUES (:op, :param, :ip)',
                [':op' => $operation, ':param' => $queryParam, ':ip' => $ip]
            );

            return new JsonResponse(['success' => true, 'message' => 'Query logged'], 200);
        } catch (PDOException $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getClientIp(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return (string) $_SERVER['HTTP_CLIENT_IP'];
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return (string) $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        return (string) ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
    }
}
