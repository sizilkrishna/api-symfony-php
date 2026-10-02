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

        $countSql = 'SELECT COUNT(*) AS COUNT FROM ARTDATA WHERE MATCH (TITLE) AGAINST (:keyword IN NATURAL LANGUAGE MODE) OR MATCH (TECHNIQUE) AGAINST (:keyword IN NATURAL LANGUAGE MODE) OR MATCH (AUTHOR) AGAINST (:keyword IN NATURAL LANGUAGE MODE) OR MATCH (FORM) AGAINST (:keyword IN NATURAL LANGUAGE MODE) OR MATCH (LOCATION) AGAINST (:keyword IN NATURAL LANGUAGE MODE) OR MATCH (SCHOOL) AGAINST (:keyword IN NATURAL LANGUAGE MODE) OR MATCH (TYPE) AGAINST (:keyword IN NATURAL LANGUAGE MODE)';
        $dataSql = 'SELECT ID, TITLE, DATE, TECHNIQUE, URL, AUTHOR, AUTHOR_ID, BORN_DIED, FORM, FORM_ID, LOCATION, LOCATION_ID, SCHOOL, SCHOOL_ID, TIMEFRAME, TIMEFRAME_ID, TYPE, TYPE_ID, CONCAT_WS(",", CASE WHEN MATCH(TITLE) AGAINST (:keyword IN NATURAL LANGUAGE MODE) > 0 THEN "TITLE" END, CASE WHEN MATCH(TECHNIQUE) AGAINST (:keyword IN NATURAL LANGUAGE MODE) > 0 THEN "TECHNIQUE" END, CASE WHEN MATCH(AUTHOR) AGAINST (:keyword IN NATURAL LANGUAGE MODE) > 0 THEN "AUTHOR" END, CASE WHEN MATCH(FORM) AGAINST (:keyword IN NATURAL LANGUAGE MODE) > 0 THEN "FORM" END, CASE WHEN MATCH(LOCATION) AGAINST (:keyword IN NATURAL LANGUAGE MODE) > 0 THEN "LOCATION" END, CASE WHEN MATCH(SCHOOL) AGAINST (:keyword IN NATURAL LANGUAGE MODE) > 0 THEN "SCHOOL" END, CASE WHEN MATCH(TYPE) AGAINST (:keyword IN NATURAL LANGUAGE MODE) > 0 THEN "TYPE" END) AS FOUND_IN FROM ARTDATA WHERE MATCH (TITLE) AGAINST (:keyword IN NATURAL LANGUAGE MODE) OR MATCH (TECHNIQUE) AGAINST (:keyword IN NATURAL LANGUAGE MODE) OR MATCH (AUTHOR) AGAINST (:keyword IN NATURAL LANGUAGE MODE) OR MATCH (FORM) AGAINST (:keyword IN NATURAL LANGUAGE MODE) OR MATCH (LOCATION) AGAINST (:keyword IN NATURAL LANGUAGE MODE) OR MATCH (SCHOOL) AGAINST (:keyword IN NATURAL LANGUAGE MODE) OR MATCH (TYPE) AGAINST (:keyword IN NATURAL LANGUAGE MODE) LIMIT :lim OFFSET :offset';

        return $this->artDataService->jsonPaginated($countSql, $dataSql, [':keyword' => $keywords], $page, $limit);
    }

    #[Route('/random', name: 'api_random', methods: ['GET'])]
    public function random(): JsonResponse
    {
        return $this->artDataService->jsonPaginated(
            "SELECT '1' AS COUNT",
            'SELECT * FROM ARTDATA ORDER BY RAND() LIMIT :lim OFFSET :offset',
            [],
            1,
            1
        );
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
            $conditions[] = 'AUTHOR_ID = :au';
            $bindings[':au'] = $author;
        }
        if ($form > 0) {
            $conditions[] = 'FORM_ID = :fo';
            $bindings[':fo'] = $form;
        }
        if ($location > 0) {
            $conditions[] = 'LOCATION_ID = :lo';
            $bindings[':lo'] = $location;
        }
        if ($school > 0) {
            $conditions[] = 'SCHOOL_ID = :sc';
            $bindings[':sc'] = $school;
        }
        if ($timeframe > 0) {
            $conditions[] = 'TIMEFRAME_ID = :ti';
            $bindings[':ti'] = $timeframe;
        }
        if ($type > 0) {
            $conditions[] = 'TYPE_ID = :ty';
            $bindings[':ty'] = $type;
        }

        $whereClause = implode(' AND ', $conditions);
        $countSql = 'SELECT COUNT(*) AS COUNT FROM ARTDATA WHERE ' . $whereClause;
        $dataSql = 'SELECT * FROM ARTDATA WHERE ' . $whereClause . ' ORDER BY ID ASC LIMIT :lim OFFSET :offset';

        return $this->artDataService->jsonPaginated($countSql, $dataSql, $bindings, $page, $limit);
    }

    #[Route('/logs', name: 'api_logs', methods: ['GET'])]
    public function logs(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 10));

        return $this->artDataService->jsonPaginated(
            'SELECT COUNT(L.ID) AS COUNT FROM LOG_TABLE L',
            'SELECT L.ID, L.CATEGORY, L.VALUE, L.IP FROM LOG_TABLE L ORDER BY L.ID DESC LIMIT :lim OFFSET :offset',
            [],
            $page,
            $limit
        );
    }
}
