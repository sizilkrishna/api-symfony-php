<?php

namespace App\Controller;

use App\Service\ArtDataService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/info')]
class InfoController
{
    public function __construct(private readonly ArtDataService $artDataService)
    {
    }

    #[Route('/author', name: 'api_info_author', methods: ['GET'])]
    public function author(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 10));

        $countSql = 'SELECT COUNT(*) AS COUNT FROM AUTHOR AU LEFT JOIN (SELECT AUTHOR_ID, COUNT(*) AS CNT FROM ART GROUP BY AUTHOR_ID) AR ON AU.ID = AR.AUTHOR_ID WHERE AR.CNT > 0';
        $dataSql = 'SELECT AU.ID, AU.AUTHOR, AU.BORN_DIED, AR.SCHOOL_ID, AR.SCHOOL, COALESCE(AR.CNT, 0) AS COUNT FROM AUTHOR AU LEFT JOIN (SELECT AUTHOR_ID, SCHOOL_ID, SCHOOL, COUNT(*) AS CNT FROM ARTDATA GROUP BY AUTHOR_ID) AR ON AU.ID = AR.AUTHOR_ID WHERE AR.CNT > 0 ORDER BY AU.ID ASC LIMIT :lim OFFSET :offset';

        return $this->artDataService->jsonPaginated($countSql, $dataSql, [], $page, $limit);
    }

    #[Route('/author/{id}', name: 'api_info_author_id', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function authorById(int $id, Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 10));

        $countSql = 'SELECT COUNT(*) AS COUNT FROM AUTHOR WHERE ID = :id';
        $dataSql = 'SELECT AU.ID, AU.AUTHOR, AU.BORN_DIED, AR.SCHOOL_ID, AR.SCHOOL, COALESCE(AR.CNT, 0) AS COUNT FROM AUTHOR AU LEFT JOIN (SELECT AUTHOR_ID, SCHOOL_ID, SCHOOL, COUNT(*) AS CNT FROM ARTDATA GROUP BY AUTHOR_ID) AR ON AU.ID = AR.AUTHOR_ID WHERE AU.ID = :id ORDER BY AU.ID ASC LIMIT :lim OFFSET :offset';

        return $this->artDataService->jsonPaginated($countSql, $dataSql, [':id' => $id], $page, $limit);
    }

    #[Route('/author/{char}', name: 'api_info_author_char', requirements: ['char' => '[a-zA-Z]'], methods: ['GET'])]
    public function authorByChar(string $char, Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 10));

        $countSql = 'SELECT COUNT(*) AS COUNT FROM AUTHOR AU LEFT JOIN (SELECT AUTHOR_ID, COUNT(*) AS CNT FROM ART GROUP BY AUTHOR_ID) AR ON AU.ID = AR.AUTHOR_ID WHERE AR.CNT > 0 AND AUTHOR LIKE :char';
        $dataSql = 'SELECT AU.ID, AU.AUTHOR, AU.BORN_DIED, AR.SCHOOL_ID, AR.SCHOOL, COALESCE(AR.CNT, 0) AS COUNT FROM AUTHOR AU LEFT JOIN (SELECT AUTHOR_ID, SCHOOL_ID, SCHOOL, COUNT(*) AS CNT FROM ARTDATA GROUP BY AUTHOR_ID) AR ON AU.ID = AR.AUTHOR_ID WHERE AU.AUTHOR LIKE :char AND AR.CNT > 0 ORDER BY AU.AUTHOR ASC LIMIT :lim OFFSET :offset';

        return $this->artDataService->jsonPaginated($countSql, $dataSql, [':char' => $char . '%'], $page, $limit);
    }

    #[Route('/type', name: 'api_info_type', methods: ['GET'])]
    public function type(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 10));

        $countSql = 'SELECT COUNT(*) AS COUNT FROM TYPE TY LEFT JOIN (SELECT TYPE_ID, COUNT(*) AS CNT FROM ART GROUP BY TYPE_ID) AR ON TY.ID = AR.TYPE_ID WHERE AR.CNT > 0';
        $dataSql = 'SELECT TY.ID, TY.TYPE, (SELECT URL FROM ART WHERE ART.ID = TY.FIMAGE) AS FIMAGE, COALESCE(AR.CNT, 0) AS COUNT FROM TYPE TY LEFT JOIN (SELECT TYPE_ID, COUNT(*) AS CNT FROM ART GROUP BY TYPE_ID) AR ON TY.ID = AR.TYPE_ID WHERE AR.CNT > 0 ORDER BY TY.TYPE ASC LIMIT :lim OFFSET :offset';

        return $this->artDataService->jsonPaginated($countSql, $dataSql, [], $page, $limit);
    }

    #[Route('/type/{id}', name: 'api_info_type_id', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function typeById(int $id, Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 10));

        $countSql = 'SELECT COUNT(*) AS COUNT FROM TYPE WHERE ID = :id';
        $dataSql = 'SELECT TY.ID, TY.TYPE, (SELECT URL FROM ART WHERE ART.ID = TY.FIMAGE) AS FIMAGE, COALESCE(AR.CNT, 0) AS COUNT FROM TYPE TY LEFT JOIN (SELECT TYPE_ID, COUNT(*) AS CNT FROM ART GROUP BY TYPE_ID) AR ON TY.ID = AR.TYPE_ID WHERE TY.ID = :id ORDER BY TY.TYPE ASC LIMIT :lim OFFSET :offset';

        return $this->artDataService->jsonPaginated($countSql, $dataSql, [':id' => $id], $page, $limit);
    }

    #[Route('/school', name: 'api_info_school', methods: ['GET'])]
    public function school(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 10));

        $countSql = 'SELECT COUNT(*) AS COUNT FROM SCHOOL SC LEFT JOIN (SELECT SCHOOL_ID, COUNT(*) AS CNT FROM ART GROUP BY SCHOOL_ID) AR ON SC.ID = AR.SCHOOL_ID WHERE AR.CNT > 0';
        $dataSql = 'SELECT SC.ID, SC.SCHOOL, (SELECT URL FROM ART WHERE ART.ID = SC.FIMAGE) AS FIMAGE, COALESCE(AR.CNT, 0) AS COUNT FROM SCHOOL SC LEFT JOIN (SELECT SCHOOL_ID, COUNT(*) AS CNT FROM ART GROUP BY SCHOOL_ID) AR ON SC.ID = AR.SCHOOL_ID WHERE AR.CNT > 0 ORDER BY SC.SCHOOL ASC LIMIT :lim OFFSET :offset';

        return $this->artDataService->jsonPaginated($countSql, $dataSql, [], $page, $limit);
    }

    #[Route('/school/{id}', name: 'api_info_school_id', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function schoolById(int $id, Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 10));

        $countSql = 'SELECT COUNT(*) AS COUNT FROM SCHOOL WHERE ID = :id';
        $dataSql = 'SELECT SC.ID, SC.SCHOOL, (SELECT URL FROM ART WHERE ART.ID = SC.FIMAGE) AS FIMAGE, COALESCE(AR.CNT, 0) AS COUNT FROM SCHOOL SC LEFT JOIN (SELECT SCHOOL_ID, COUNT(*) AS CNT FROM ART GROUP BY SCHOOL_ID) AR ON SC.ID = AR.SCHOOL_ID WHERE SC.ID = :id ORDER BY SC.SCHOOL ASC LIMIT :lim OFFSET :offset';

        return $this->artDataService->jsonPaginated($countSql, $dataSql, [':id' => $id], $page, $limit);
    }

    #[Route('/location', name: 'api_info_location', methods: ['GET'])]
    public function location(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 10));

        $countSql = 'SELECT COUNT(*) AS COUNT FROM LOCATION LO LEFT JOIN (SELECT LOCATION_ID, COUNT(*) AS CNT FROM ART GROUP BY LOCATION_ID) AR ON LO.ID = AR.LOCATION_ID WHERE AR.CNT > 0';
        $dataSql = 'SELECT LO.ID, LO.LOCATION, (SELECT URL FROM ART WHERE ART.ID = LO.FIMAGE) AS FIMAGE, COALESCE(AR.CNT, 0) AS COUNT FROM LOCATION LO LEFT JOIN (SELECT LOCATION_ID, COUNT(*) AS CNT FROM ART GROUP BY LOCATION_ID) AR ON LO.ID = AR.LOCATION_ID WHERE AR.CNT > 0 ORDER BY LO.LOCATION ASC LIMIT :lim OFFSET :offset';

        return $this->artDataService->jsonPaginated($countSql, $dataSql, [], $page, $limit);
    }

    #[Route('/location/{id}', name: 'api_info_location_id', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function locationById(int $id, Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 10));

        $countSql = 'SELECT COUNT(*) AS COUNT FROM LOCATION WHERE ID = :id';
        $dataSql = 'SELECT LO.ID, LO.LOCATION, (SELECT URL FROM ART WHERE ART.ID = LO.FIMAGE) AS FIMAGE, COALESCE(AR.CNT, 0) AS COUNT FROM LOCATION LO LEFT JOIN (SELECT LOCATION_ID, COUNT(*) AS CNT FROM ART GROUP BY LOCATION_ID) AR ON LO.ID = AR.LOCATION_ID WHERE LO.ID = :id ORDER BY LO.LOCATION ASC LIMIT :lim OFFSET :offset';

        return $this->artDataService->jsonPaginated($countSql, $dataSql, [':id' => $id], $page, $limit);
    }

    #[Route('/form', name: 'api_info_form', methods: ['GET'])]
    public function form(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 10));

        $countSql = 'SELECT COUNT(*) AS COUNT FROM FORM FO LEFT JOIN (SELECT FORM_ID, COUNT(*) AS CNT FROM ART GROUP BY FORM_ID) AR ON FO.ID = AR.FORM_ID WHERE AR.CNT > 0';
        $dataSql = 'SELECT FO.ID, FO.FORM, (SELECT URL FROM ART WHERE ART.ID = FO.FIMAGE) AS FIMAGE, COALESCE(AR.CNT, 0) AS COUNT FROM FORM FO LEFT JOIN (SELECT FORM_ID, COUNT(*) AS CNT FROM ART GROUP BY FORM_ID) AR ON FO.ID = AR.FORM_ID WHERE AR.CNT > 0 ORDER BY FO.FORM ASC LIMIT :lim OFFSET :offset';

        return $this->artDataService->jsonPaginated($countSql, $dataSql, [], $page, $limit);
    }

    #[Route('/form/{id}', name: 'api_info_form_id', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function formById(int $id, Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 10));

        $countSql = 'SELECT COUNT(*) AS COUNT FROM FORM WHERE ID = :id';
        $dataSql = 'SELECT FO.ID, FO.FORM, (SELECT URL FROM ART WHERE ART.ID = FO.FIMAGE) AS FIMAGE, COALESCE(AR.CNT, 0) AS COUNT FROM FORM FO LEFT JOIN (SELECT FORM_ID, COUNT(*) AS CNT FROM ART GROUP BY FORM_ID) AR ON FO.ID = AR.FORM_ID WHERE FO.ID = :id ORDER BY FO.FORM ASC LIMIT :lim OFFSET :offset';

        return $this->artDataService->jsonPaginated($countSql, $dataSql, [':id' => $id], $page, $limit);
    }

    #[Route('/timeframe', name: 'api_info_timeframe', methods: ['GET'])]
    public function timeframe(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 10));

        $countSql = 'SELECT COUNT(*) AS COUNT FROM TIMEFRAME TI LEFT JOIN (SELECT TIMEFRAME_ID, COUNT(*) AS CNT FROM ART GROUP BY TIMEFRAME_ID) AR ON TI.ID = AR.TIMEFRAME_ID WHERE AR.CNT > 0';
        $dataSql = 'SELECT TI.ID, TI.TIMEFRAME, (SELECT URL FROM ART WHERE ART.ID = TI.FIMAGE) AS FIMAGE, COALESCE(AR.CNT, 0) AS COUNT FROM TIMEFRAME TI LEFT JOIN (SELECT TIMEFRAME_ID, COUNT(*) AS CNT FROM ART GROUP BY TIMEFRAME_ID) AR ON TI.ID = AR.TIMEFRAME_ID WHERE AR.CNT > 0 ORDER BY TI.TIMEFRAME ASC LIMIT :lim OFFSET :offset';

        return $this->artDataService->jsonPaginated($countSql, $dataSql, [], $page, $limit);
    }

    #[Route('/timeframe/{id}', name: 'api_info_timeframe_id', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function timeframeById(int $id, Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 10));

        $countSql = 'SELECT COUNT(*) AS COUNT FROM TIMEFRAME WHERE ID = :id';
        $dataSql = 'SELECT TI.ID, TI.TIMEFRAME, (SELECT URL FROM ART WHERE ART.ID = TI.FIMAGE) AS FIMAGE, COALESCE(AR.CNT, 0) AS COUNT FROM TIMEFRAME TI LEFT JOIN (SELECT TIMEFRAME_ID, COUNT(*) AS CNT FROM ART GROUP BY TIMEFRAME_ID) AR ON TI.ID = AR.TIMEFRAME_ID WHERE TI.ID = :id ORDER BY TI.TIMEFRAME ASC LIMIT :lim OFFSET :offset';

        return $this->artDataService->jsonPaginated($countSql, $dataSql, [':id' => $id], $page, $limit);
    }
}
