<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database\Paginator;
use App\Domain\ArtCriteria;
use App\Domain\Dimension;
use App\Domain\Page;
use App\Domain\Pagination;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

final class ArtworkRepository
{
    private const COLUMNS = <<<'SQL'
        a.id, a.title, a.date, a.technique, a.url,
        a.author_id, au.author, au.born_died,
        a.form_id, f.form,
        a.location_id, l.location,
        a.school_id, s.school,
        a.timeframe_id, t.timeframe,
        a.type_id, ty.type
        SQL;

    private const JOINS = <<<'SQL'
        LEFT JOIN author    au ON au.id = a.author_id
        LEFT JOIN form      f  ON f.id  = a.form_id
        LEFT JOIN location  l  ON l.id  = a.location_id
        LEFT JOIN school    s  ON s.id  = a.school_id
        LEFT JOIN timeframe t  ON t.id  = a.timeframe_id
        LEFT JOIN type      ty ON ty.id = a.type_id
        SQL;

    public function __construct(
        private readonly Connection $db,
        private readonly Paginator $paginator,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $row = $this->db->fetchAssociative(
            'SELECT ' . self::COLUMNS . ' FROM art a ' . self::JOINS . ' WHERE a.id = :id',
            ['id' => $id],
            ['id' => ParameterType::INTEGER],
        );

        return $row === false ? null : $row;
    }

    /**
     * Lists artworks matching the taxonomy filters and/or the full-text query.
     * Counting runs on the "art" table alone; joins are only paid for one page.
     */
    public function search(ArtCriteria $criteria, Pagination $pagination): Page
    {
        [$where, $params, $types] = $this->conditions($criteria);
        $whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);

        $order = $criteria->query !== null
            ? "ORDER BY ts_rank(a.search_vector, websearch_to_tsquery('english', :q)) DESC, a.id ASC"
            : 'ORDER BY a.id ASC';

        return $this->paginator->paginate(
            "SELECT COUNT(*) FROM art a $whereSql",
            'SELECT ' . self::COLUMNS . ' FROM art a ' . self::JOINS . " $whereSql $order LIMIT :limit OFFSET :offset",
            $params,
            $types,
            $pagination,
        );
    }

    /**
     * Random sample without sorting the fully joined table: ids are picked from
     * the narrow primary-key index first, then joined.
     *
     * @return list<array<string, mixed>>
     */
    public function random(int $limit): array
    {
        return $this->db->fetchAllAssociative(
            'SELECT ' . self::COLUMNS . ' FROM (SELECT id FROM art ORDER BY random() LIMIT :limit) r '
            . 'JOIN art a ON a.id = r.id ' . self::JOINS . ' ORDER BY random()',
            ['limit' => $limit],
            ['limit' => ParameterType::INTEGER],
        );
    }

    /**
     * @return array{0: list<string>, 1: array<string, mixed>, 2: array<string, ParameterType>}
     */
    private function conditions(ArtCriteria $criteria): array
    {
        $where = [];
        $params = [];
        $types = [];

        foreach ($criteria->filters as $key => $id) {
            $dimension = Dimension::from($key);
            $name = $dimension->queryParam();

            $where[] = sprintf('a.%s = :%s', $dimension->foreignKey(), $name);
            $params[$name] = $id;
            $types[$name] = ParameterType::INTEGER;
        }

        if ($criteria->query !== null) {
            // Artwork text (stemmed) OR author name (unstemmed). The author ids are
            // resolved first so both branches can use their GIN / btree indexes.
            $where[] = "(a.search_vector @@ websearch_to_tsquery('english', :q)"
                . ' OR a.author_id = ANY(ARRAY(SELECT x.id FROM author x'
                . " WHERE x.search_vector @@ websearch_to_tsquery('simple', :q))))";
            $params['q'] = $criteria->query;
        }

        return [$where, $params, $types];
    }
}
