<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * A permanent address resolution failure — retrying with the same input
 * would produce the same result. Marks the import row as Failed.
 */
final class AddressResolutionException extends RuntimeException
{
    public static function notResolvable(string $rawAddress): self
    {
        return new self("Address could not be normalised: \"{$rawAddress}\".");
    }

    public static function zeroResults(string $normalizedAddress): self
    {
        return new self("Geocoding returned no results for \"{$normalizedAddress}\".");
    }

    public static function invalidRequest(string $normalizedAddress): self
    {
        return new self("Geocoding request was invalid for \"{$normalizedAddress}\".");
    }
}
