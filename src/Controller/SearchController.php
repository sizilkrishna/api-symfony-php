<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\ArtCriteria;
use App\Domain\Pagination;
use App\EventSubscriber\SearchLogSubscriber;
use App\Http\ApiResponder;
use App\Http\QueryParams;
use App\Repository\ArtworkRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[Route('/api', methods: ['GET'])]
final class SearchController
{
    private const MAX_RANDOM = 50;

    public function __construct(
        private readonly ArtworkRepository $artworks,
        private readonly ApiResponder $responder,
    ) {
    }

    /** Full-text search (?q=), optionally narrowed by taxonomy filters (au, fo, lo, sc, ti, ty). */
    #[Route('/search', name: 'api_search')]
    public function search(Request $request, Pagination $pagination): JsonResponse
    {
        $term = QueryParams::searchTerm($request)
            ?? throw new BadRequestHttpException('Query parameter "q" is required.');

        $page = $this->artworks->search(new ArtCriteria(QueryParams::filters($request), $term), $pagination);

        // Written after the response has been sent (see SearchLogSubscriber).
        if ($pagination->page === 1) {
            $request->attributes->set(SearchLogSubscriber::ATTRIBUTE, $term);
        }

        return $this->responder->page($page, 60);
    }

    /** Taxonomy filters only: ?au=&fo=&lo=&sc=&ti=&ty= (at least one required). */
    #[Route('/filter', name: 'api_filter')]
    public function filter(Request $request, Pagination $pagination): JsonResponse
    {
        $filters = QueryParams::filters($request);

        if ($filters === []) {
            throw new BadRequestHttpException('At least one filter parameter (au, fo, lo, sc, ti, ty) is required.');
        }

        return $this->responder->page($this->artworks->search(new ArtCriteria($filters), $pagination), 300);
    }

    #[Route('/random', name: 'api_random')]
    public function random(Request $request): JsonResponse
    {
        $limit = QueryParams::int($request, 'limit', 1, 1, self::MAX_RANDOM, clamp: true);

        return $this->responder->records($this->artworks->random($limit));
    }
}
