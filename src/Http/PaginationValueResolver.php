<?php

declare(strict_types=1);

namespace App\Http;

use App\Domain\Pagination;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/** Lets controller actions simply type-hint `Pagination $pagination`. */
final class PaginationValueResolver implements ValueResolverInterface
{
    /**
     * @return iterable<int, Pagination>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ($argument->getType() !== Pagination::class) {
            return [];
        }

        return [QueryParams::pagination($request)];
    }
}
