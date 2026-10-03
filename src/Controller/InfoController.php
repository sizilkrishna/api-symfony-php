<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Dimension;
use App\Domain\Pagination;
use App\Http\ApiResponder;
use App\Http\Patterns;
use App\Repository\TaxonomyRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\EnumRequirement;

#[AsController]
#[Route('/api/info', methods: ['GET'])]
final class InfoController
{
    private const TTL = 3600;

    public function __construct(
        private readonly TaxonomyRepository $taxonomies,
        private readonly ApiResponder $responder,
    ) {
    }

    /** Authors whose name starts with a letter, e.g. /api/info/author/r */
    #[Route('/author/{letter}', name: 'api_info_author_letter', requirements: ['letter' => '[a-zA-Z]'])]
    public function authorsByLetter(string $letter, Pagination $pagination): JsonResponse
    {
        return $this->responder->page($this->taxonomies->list(Dimension::Author, $pagination, $letter), self::TTL);
    }

    /** /api/info/{author|form|location|school|timeframe|type} */
    #[Route('/{dimension}', name: 'api_info_list', requirements: ['dimension' => new EnumRequirement(Dimension::class)])]
    public function list(Dimension $dimension, Pagination $pagination): JsonResponse
    {
        return $this->responder->page($this->taxonomies->list($dimension, $pagination), self::TTL);
    }

    #[Route(
        '/{dimension}/{id}',
        name: 'api_info_item',
        requirements: ['dimension' => new EnumRequirement(Dimension::class), 'id' => Patterns::ID],
    )]
    public function item(Dimension $dimension, int $id): JsonResponse
    {
        $record = $this->taxonomies->find($dimension, $id)
            ?? throw new NotFoundHttpException(sprintf('%s not found.', ucfirst($dimension->value)));

        return $this->responder->item($record, self::TTL);
    }
}
