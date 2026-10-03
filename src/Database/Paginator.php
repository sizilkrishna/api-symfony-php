<?php

declare(strict_types=1);

namespace App\Database;

use App\Domain\Page;
use App\Domain\Pagination;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

/**
 * Runs a count query followed by a LIMIT/OFFSET data query. The data query is
 * skipped entirely when the page cannot contain rows.
 */
final class Paginator
{
    public function __construct(private readonly Connection $db)
    {
    }

    /**
     * @param string                                  $dataSql must contain ":limit" and ":offset"
     * @param array<string, mixed>                    $params  shared by both queries
     * @param array<string, ParameterType|int|string> $types
     */
    public function paginate(string $countSql, string $dataSql, array $params, array $types, Pagination $pagination): Page
    {
        $total = (int) $this->db->fetchOne($countSql, $params, $types);

        if ($total === 0 || $pagination->offset() >= $total) {
            return new Page([], $total, $pagination);
        }

        $rows = $this->db->fetchAllAssociative(
            $dataSql,
            $params + ['limit' => $pagination->limit, 'offset' => $pagination->offset()],
            $types + ['limit' => ParameterType::INTEGER, 'offset' => ParameterType::INTEGER],
        );

        return new Page($rows, $total, $pagination);
    }
}
