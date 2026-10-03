<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database\Paginator;
use App\Domain\Dimension;
use App\Domain\Page;
use App\Domain\Pagination;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

final class TaxonomyRepository
{
    /**
     * Most common school of an author. `p` is the author row of the outer query.
     */
    private const AUTHOR_SCHOOL = <<<'SQL'
        LEFT JOIN LATERAL (
            SELECT s.id AS school_id, s.school
            FROM art a2
            JOIN school s ON s.id = a2.school_id
            WHERE a2.author_id = p.id
            GROUP BY s.id, s.school
            ORDER BY COUNT(*) DESC, s.id ASC
            LIMIT 1
        ) ps ON TRUE
        SQL;

    public function __construct(
        private readonly Connection $db,
        private readonly Paginator $paginator,
    ) {
    }

    /**
     * Taxonomy rows that have at least one artwork, with artwork counts.
     *
     * @param string|null $letter Only for authors: names starting with this letter
     */
    public function list(Dimension $dimension, Pagination $pagination, ?string $letter = null): Page
    {
        return $dimension === Dimension::Author
            ? $this->listAuthors($pagination, $letter)
            : $this->listGeneric($dimension, $pagination);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(Dimension $dimension, int $id): ?array
    {
        if ($dimension === Dimension::Author) {
            $row = $this->db->fetchAssociative(
                'SELECT p.id, p.author, p.born_died, ps.school_id, ps.school, p.cnt AS count FROM ('
                . ' SELECT au.id, au.author, au.born_died, (SELECT COUNT(*) FROM art a WHERE a.author_id = au.id) AS cnt'
                . ' FROM author au WHERE au.id = :id) p ' . self::AUTHOR_SCHOOL,
                ['id' => $id],
                ['id' => ParameterType::INTEGER],
            );
        } else {
            [$t, $l, $fk] = [$dimension->table(), $dimension->label(), $dimension->foreignKey()];

            $row = $this->db->fetchAssociative(
                "SELECT t.id, t.$l AS $l, fa.url AS fimage, (SELECT COUNT(*) FROM art a WHERE a.$fk = t.id) AS count"
                . " FROM $t t LEFT JOIN art fa ON fa.id = t.fimage WHERE t.id = :id",
                ['id' => $id],
                ['id' => ParameterType::INTEGER],
            );
        }

        return $row === false ? null : $row;
    }

    private function listGeneric(Dimension $dimension, Pagination $pagination): Page
    {
        [$t, $l, $fk] = [$dimension->table(), $dimension->label(), $dimension->foreignKey()];

        return $this->paginator->paginate(
            "SELECT COUNT(*) FROM $t t WHERE EXISTS (SELECT 1 FROM art a WHERE a.$fk = t.id)",
            "SELECT t.id, t.$l AS $l, fa.url AS fimage, c.cnt AS count"
            . " FROM $t t"
            . " JOIN (SELECT $fk AS id, COUNT(*) AS cnt FROM art GROUP BY $fk) c ON c.id = t.id"
            . ' LEFT JOIN art fa ON fa.id = t.fimage'
            . " ORDER BY t.$l ASC, t.id ASC LIMIT :limit OFFSET :offset",
            [],
            [],
            $pagination,
        );
    }

    private function listAuthors(Pagination $pagination, ?string $letter): Page
    {
        $params = [];
        $filter = '';

        if ($letter !== null) {
            $filter = 'AND au.author ILIKE :prefix';
            $params['prefix'] = $letter . '%';
        }

        // The page is cut first (CTE with LIMIT), only then is the per-author
        // school lookup evaluated, so it runs for at most `limit` authors.
        return $this->paginator->paginate(
            "SELECT COUNT(*) FROM author au WHERE EXISTS (SELECT 1 FROM art a WHERE a.author_id = au.id) $filter",
            'WITH p AS ('
            . ' SELECT au.id, au.author, au.born_died, c.cnt'
            . ' FROM author au'
            . ' JOIN (SELECT author_id AS id, COUNT(*) AS cnt FROM art GROUP BY author_id) c ON c.id = au.id'
            . " WHERE TRUE $filter"
            . ' ORDER BY au.author ASC, au.id ASC LIMIT :limit OFFSET :offset)'
            . ' SELECT p.id, p.author, p.born_died, ps.school_id, ps.school, p.cnt AS count FROM p '
            . self::AUTHOR_SCHOOL
            . ' ORDER BY p.author ASC, p.id ASC',
            $params,
            [],
            $pagination,
        );
    }
}
