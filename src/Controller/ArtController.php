<?php

namespace App\Controller;

use App\Service\ArtDataService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/art')]
class ArtController
{
    public function __construct(private readonly ArtDataService $artDataService)
    {
    }

    #[Route('/all', name: 'api_art_all', methods: ['GET'])]
    public function all(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 10));
        $countSql = 'SELECT COUNT(*) AS COUNT FROM ARTDATA';
        $dataSql = 'SELECT * FROM ARTDATA ORDER BY ID ASC LIMIT :lim OFFSET :offset';

        return $this->artDataService->jsonPaginated($countSql, $dataSql, [], $page, $limit);
    }

    #[Route('/all/{id}', name: 'api_art_all_id', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function allById(int $id, Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 10));
        $countSql = 'SELECT COUNT(*) AS COUNT FROM ARTDATA WHERE ID = :id';
        $dataSql = 'SELECT * FROM ARTDATA WHERE ID = :id ORDER BY ID ASC LIMIT :lim OFFSET :offset';

        return $this->artDataService->jsonPaginated($countSql, $dataSql, [':id' => $id], $page, $limit);
    }

    #[Route('/author/{id}', name: 'api_art_author', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function byAuthor(int $id, Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 10));
        $countSql = 'SELECT COUNT(*) AS COUNT FROM ARTDATA WHERE AUTHOR_ID = :id';
        $dataSql = 'SELECT * FROM ARTDATA WHERE AUTHOR_ID = :id ORDER BY ID ASC LIMIT :lim OFFSET :offset';

        return $this->artDataService->jsonPaginated($countSql, $dataSql, [':id' => $id], $page, $limit);
    }

    #[Route('/type/{id}', name: 'api_art_type', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function byType(int $id, Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 10));
        $countSql = 'SELECT COUNT(*) AS COUNT FROM ARTDATA WHERE TYPE_ID = :id';
        $dataSql = 'SELECT * FROM ARTDATA WHERE TYPE_ID = :id ORDER BY ID ASC LIMIT :lim OFFSET :offset';

        return $this->artDataService->jsonPaginated($countSql, $dataSql, [':id' => $id], $page, $limit);
    }

    #[Route('/school/{id}', name: 'api_art_school', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function bySchool(int $id, Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 10));
        $countSql = 'SELECT COUNT(*) AS COUNT FROM ARTDATA WHERE SCHOOL_ID = :id';
        $dataSql = 'SELECT * FROM ARTDATA WHERE SCHOOL_ID = :id ORDER BY ID ASC LIMIT :lim OFFSET :offset';

        return $this->artDataService->jsonPaginated($countSql, $dataSql, [':id' => $id], $page, $limit);
    }

    #[Route('/timeframe/{id}', name: 'api_art_timeframe', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function byTimeframe(int $id, Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 10));
        $countSql = 'SELECT COUNT(*) AS COUNT FROM ARTDATA WHERE TIMEFRAME_ID = :id';
        $dataSql = 'SELECT * FROM ARTDATA WHERE TIMEFRAME_ID = :id ORDER BY ID ASC LIMIT :lim OFFSET :offset';

        return $this->artDataService->jsonPaginated($countSql, $dataSql, [':id' => $id], $page, $limit);
    }

    #[Route('/location/{id}', name: 'api_art_location', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function byLocation(int $id, Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 10));
        $countSql = 'SELECT COUNT(*) AS COUNT FROM ARTDATA WHERE LOCATION_ID = :id';
        $dataSql = 'SELECT * FROM ARTDATA WHERE LOCATION_ID = :id ORDER BY ID ASC LIMIT :lim OFFSET :offset';

        return $this->artDataService->jsonPaginated($countSql, $dataSql, [':id' => $id], $page, $limit);
    }

    #[Route('/form/{id}', name: 'api_art_form', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function byForm(int $id, Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 10));
        $countSql = 'SELECT COUNT(*) AS COUNT FROM ARTDATA WHERE FORM_ID = :id';
        $dataSql = 'SELECT * FROM ARTDATA WHERE FORM_ID = :id ORDER BY ID ASC LIMIT :lim OFFSET :offset';

        return $this->artDataService->jsonPaginated($countSql, $dataSql, [':id' => $id], $page, $limit);
    }
}
