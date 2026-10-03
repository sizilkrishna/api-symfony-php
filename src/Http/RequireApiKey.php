<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Marks a controller (or action) as protected by the X-API-Key header.
 *
 * @see \App\EventSubscriber\ApiKeySubscriber
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_CLASS)]
final class RequireApiKey
{
}
