<?php

declare(strict_types=1);

namespace App\Http;

final class Patterns
{
    /** Positive integer that always fits a PostgreSQL INTEGER (max 9 digits). */
    public const ID = '[1-9]\d{0,8}';

    private function __construct()
    {
    }
}
