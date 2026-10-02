<?php

namespace App\Controller;

use App\Service\ArtDataService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class SearchController
{
    public function __construct(private readonly ArtDataService $artDataService)
    {
    }

    #[Route('/search', name: 'api_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 10));
        $keywords = trim((string) $request->query->get('q', ''));

        if ($keywords === '') {
            return new JsonResponse(['success' => false, 'message' => 'Search query is required'], 400);
        }

        $this->artDataService->logQuery('search', $keywords);

        $countSql = '
            SELECT COUNT(*) AS COUNT
            FROM "ART" a
            WHERE to_tsvector(\'english\', coalesce(a."TITLE", \'\') || \' \' || coalesce(a."TECHNIQUE", \'\') || \' \' || coalesce(a."URL", \'\'))
                @@ plainto_tsquery(\'english\', :keyword)
        ';

        $dataSql = '
            SELECT
                a."ID",
                a."TITLE",
                a."DATE",
                a."TECHNIQUE",
                a."URL",
                au."AUTHOR",
                a."AUTHOR_ID",
                au."BORN_DIED",
                f."FORM",
                a."FORM_ID",
                l."LOCATION",
                a."LOCATION_ID",
                s."SCHOOL",
                a."SCHOOL_ID",
                t."TIMEFRAME",
                a."TIMEFRAME_ID",
                ty."TYPE",
                a."TYPE_ID"
            FROM "ART" a
            LEFT JOIN "AUTHOR" au ON a."AUTHOR_ID" = au."ID"
            LEFT JOIN "FORM" f ON a."FORM_ID" = f."ID"
            LEFT JOIN "LOCATION" l ON a."LOCATION_ID" = l."ID"
            LEFT JOIN "SCHOOL" s ON a."SCHOOL_ID" = s."ID"
            LEFT JOIN "TIMEFRAME" t ON a."TIMEFRAME_ID" = t."ID"
            LEFT JOIN "TYPE" ty ON a."TYPE_ID" = ty."ID"
            WHERE to_tsvector(\'english\', coalesce(a."TITLE", \'\') || \' \' || coalesce(a."TECHNIQUE", \'\') || \' \' || coalesce(a."URL", \'\'))
                @@ plainto_tsquery(\'english\', :keyword)
            ORDER BY a."ID" ASC
            LIMIT :lim OFFSET :offset
        ';

        return $this->artDataService->jsonPaginated($countSql, $dataSql, [':keyword' => $keywords], $page, $limit);
    }

    #[Route('/random', name: 'api_random', methods: ['GET'])]
    public function random(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 1));

        $countSql = 'SELECT COUNT(*) AS COUNT FROM "ART"';
        $dataSql = '
            SELECT *
            FROM "ARTDATA"
            ORDER BY RANDOM()
            LIMIT :lim OFFSET :offset
        ';

        return $this->artDataService->jsonPaginated($countSql, $dataSql, [], $page, $limit);
    }

    #[Route('/logger', name: 'api_logger', methods: ['POST'])]
    public function logger(Request $request): JsonResponse
    {
        try {
            $payload = json_decode((string) $request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid JSON'], 400);
        }

        $category = trim((string) ($payload['category'] ?? $request->request->get('category', '')));
        $value = trim((string) ($payload['value'] ?? $request->request->get('value', '')));

        if ($category === '' || $value === '') {
            return new JsonResponse(['success' => false, 'message' => 'Category and value are required'], 400);
        }

        return $this->artDataService->logQuery($category, $value);
    }

    #[Route('/filter', name: 'api_filter', methods: ['GET'])]
    public function filter(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 10));

        $author = max(0, (int) $request->query->get('au', 0));
        $form = max(0, (int) $request->query->get('fo', 0));
        $location = max(0, (int) $request->query->get('lo', 0));
        $school = max(0, (int) $request->query->get('sc', 0));
        $timeframe = max(0, (int) $request->query->get('ti', 0));
        $type = max(0, (int) $request->query->get('ty', 0));

        if (!($author || $form || $location || $school || $timeframe || $type)) {
            return new JsonResponse(['success' => false, 'message' => 'At least one filter parameter is required'], 400);
        }

        $conditions = [];
        $bindings = [];

        if ($author > 0) {
            $conditions[] = 'a."AUTHOR_ID" = :au';
            $bindings[':au'] = $author;
        }
        if ($form > 0) {
            $conditions[] = 'a."FORM_ID" = :fo';
            $bindings[':fo'] = $form;
        }
        if ($location > 0) {
            $conditions[] = 'a."LOCATION_ID" = :lo';
            $bindings[':lo'] = $location;
        }
        if ($school > 0) {
            $conditions[] = 'a."SCHOOL_ID" = :sc';
            $bindings[':sc'] = $school;
        }
        if ($timeframe > 0) {
            $conditions[] = 'a."TIMEFRAME_ID" = :ti';
            $bindings[':ti'] = $timeframe;
        }
        if ($type > 0) {
            $conditions[] = 'a."TYPE_ID" = :ty';
            $bindings[':ty'] = $type;
        }

        $whereClause = implode(' AND ', $conditions);
        $countSql = 'SELECT COUNT(*) AS COUNT FROM "ART" a WHERE ' . $whereClause;
        $dataSql = '
            SELECT *
            FROM "ARTDATA"
            WHERE ' . $whereClause . '
            ORDER BY "ID" ASC
            LIMIT :lim OFFSET :offset
        ';

        return $this->artDataService->jsonPaginated($countSql, $dataSql, $bindings, $page, $limit);
    }

    #[Route('/logs', name: 'api_logs', methods: ['GET'])]
    public function logs(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 10));

        return $this->artDataService->jsonPaginated(
            'SELECT COUNT(L."ID") AS COUNT FROM "LOG_TABLE" L',
            'SELECT L."ID", L."CATEGORY", L."VALUE", L."IP" FROM "LOG_TABLE" L ORDER BY L."ID" DESC LIMIT :lim OFFSET :offset',
            [],
            $page,
            $limit
        );
    }
}
