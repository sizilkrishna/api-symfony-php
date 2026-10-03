<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database\Paginator;
use App\Domain\Page;
use App\Domain\Pagination;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

final class LogRepository
{
    private const PRUNE_BATCH = 10000;

    public function __construct(
        private readonly Connection $db,
        private readonly Paginator $paginator,
    ) {
    }

    public function insert(string $category, string $value, ?string $ip): void
    {
        $this->db->executeStatement(
            'INSERT INTO log_table (category, value, ip) VALUES (:category, :value, :ip)',
            ['category' => $category, 'value' => $value, 'ip' => $ip],
        );
    }

    public function list(?string $category, Pagination $pagination): Page
    {
        $where = $category !== null ? 'WHERE category = :category' : '';
        $params = $category !== null ? ['category' => $category] : [];

        return $this->paginator->paginate(
            "SELECT COUNT(*) FROM log_table $where",
            "SELECT id, category, value, ip, created_at FROM log_table $where ORDER BY id DESC LIMIT :limit OFFSET :offset",
            $params,
            [],
            $pagination,
        );
    }

    /**
     * Deletes log rows older than $days in small batches (keeps locks and
     * statement time short). Returns the number of deleted rows.
     */
    public function prune(int $days): int
    {
        $total = 0;

        do {
            $deleted = (int) $this->db->executeStatement(
                'DELETE FROM log_table WHERE id IN ('
                . ' SELECT id FROM log_table WHERE created_at < now() - make_interval(days => :days) LIMIT :batch)',
                ['days' => $days, 'batch' => self::PRUNE_BATCH],
                ['days' => ParameterType::INTEGER, 'batch' => ParameterType::INTEGER],
            );
            $total += $deleted;
        } while ($deleted === self::PRUNE_BATCH);

        return $total;
    }
}
