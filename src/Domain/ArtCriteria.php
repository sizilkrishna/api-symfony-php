<?php

declare(strict_types=1);

namespace App\Domain;

final readonly class ArtCriteria
{
    /**
     * @param array<string, int> $filters Dimension value => taxonomy id
     * @param string|null        $query   Full-text search term
     */
    public function __construct(
        public array $filters = [],
        public ?string $query = null,
    ) {
    }

    public static function forDimension(Dimension $dimension, int $id): self
    {
        return new self([$dimension->value => $id]);
    }
}
