<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\ArtCriteria;
use App\Domain\Dimension;
use App\Domain\Pagination;
use App\Http\ApiResponder;
use App\Http\Patterns;
use App\Repository\ArtworkRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\EnumRequirement;

#[AsController]
#[Route('/api/art', methods: ['GET'])]
final class ArtController
{
    private const TTL = 300;

    public function __construct(
        private readonly ArtworkRepository $artworks,
        private readonly ApiResponder $responder,
    ) {
    }

    #[Route('/all', name: 'api_art_all')]
    public function all(Pagination $pagination): JsonResponse
    {
        return $this->responder->page($this->artworks->search(new ArtCriteria(), $pagination), self::TTL);
    }

    #[Route('/all/{id}', name: 'api_art_item', requirements: ['id' => Patterns::ID])]
    public function item(int $id): JsonResponse
    {
        $artwork = $this->artworks->find($id) ?? throw new NotFoundHttpException('Artwork not found.');

        return $this->responder->item($artwork, self::TTL);
    }

    /** /api/art/{author|form|location|school|timeframe|type}/{id} */
    #[Route(
        '/{dimension}/{id}',
        name: 'api_art_by_dimension',
        requirements: ['dimension' => new EnumRequirement(Dimension::class), 'id' => Patterns::ID],
    )]
    public function byDimension(Dimension $dimension, int $id, Pagination $pagination): JsonResponse
    {
        $page = $this->artworks->search(ArtCriteria::forDimension($dimension, $id), $pagination);

        return $this->responder->page($page, self::TTL);
    }
}
