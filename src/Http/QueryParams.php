<?php

declare(strict_types=1);

namespace App\Http;

use App\Domain\Dimension;
use App\Domain\Pagination;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Strict, centralised parsing of query-string input. Every failure is a 400.
 */
final class QueryParams
{
    public const MAX_SEARCH_LENGTH = 200;

    private function __construct()
    {
    }

    public static function int(Request $request, string $name, int $default, int $min, int $max, bool $clamp = false): int
    {
        $raw = $request->query->all()[$name] ?? null;

        if ($raw === null || $raw === '') {
            return $default;
        }

        if (!\is_string($raw) || !preg_match('/^-?\d{1,9}$/', $raw)) {
            throw new BadRequestHttpException(sprintf('Query parameter "%s" must be an integer.', $name));
        }

        $value = (int) $raw;

        if ($clamp) {
            return max($min, min($max, $value));
        }

        if ($value < $min || $value > $max) {
            throw new BadRequestHttpException(sprintf('Query parameter "%s" must be between %d and %d.', $name, $min, $max));
        }

        return $value;
    }

    /** "page" is validated strictly, "limit" is clamped to the allowed maximum. */
    public static function pagination(Request $request, int $defaultLimit = Pagination::DEFAULT_LIMIT): Pagination
    {
        return new Pagination(
            self::int($request, 'page', 1, 1, Pagination::MAX_PAGE),
            self::int($request, 'limit', $defaultLimit, 1, Pagination::MAX_LIMIT, clamp: true),
        );
    }

    /**
     * Taxonomy filters (au, fo, lo, sc, ti, ty). A value of 0 means "not set".
     *
     * @return array<string, int> Dimension value => id
     */
    public static function filters(Request $request): array
    {
        $filters = [];

        foreach (Dimension::cases() as $dimension) {
            $id = self::int($request, $dimension->queryParam(), 0, 0, 999_999_999);

            if ($id > 0) {
                $filters[$dimension->value] = $id;
            }
        }

        return $filters;
    }

    public static function searchTerm(Request $request, string $name = 'q'): ?string
    {
        $raw = $request->query->all()[$name] ?? null;

        if ($raw === null) {
            return null;
        }

        if (!\is_string($raw)) {
            throw new BadRequestHttpException(sprintf('Query parameter "%s" must be a string.', $name));
        }

        $value = trim($raw);

        if ($value === '') {
            return null;
        }

        if (!mb_check_encoding($value, 'UTF-8') || str_contains($value, "\0")) {
            throw new BadRequestHttpException(sprintf('Query parameter "%s" contains invalid characters.', $name));
        }

        if (mb_strlen($value) > self::MAX_SEARCH_LENGTH) {
            throw new BadRequestHttpException(sprintf('Query parameter "%s" must not exceed %d characters.', $name, self::MAX_SEARCH_LENGTH));
        }

        return $value;
    }
}
