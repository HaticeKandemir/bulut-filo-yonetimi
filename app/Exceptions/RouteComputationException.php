<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * A permanent route computation failure — retrying with the same input
 * would produce the same result. Marks the import row as Failed.
 */
final class RouteComputationException extends RuntimeException
{
    public static function notFound(): self
    {
        return new self('No route could be found between the given points.');
    }

    public static function invalidArgument(): self
    {
        return new self('Route request was invalid.');
    }

    public static function emptyResponse(): self
    {
        return new self('Route provider returned no routes.');
    }

    public static function malformedDuration(string $value): self
    {
        return new self("Could not parse route duration \"{$value}\".");
    }
}
