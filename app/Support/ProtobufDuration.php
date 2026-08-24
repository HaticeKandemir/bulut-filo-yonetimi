<?php

declare(strict_types=1);

namespace App\Support;

use App\Exceptions\RouteComputationException;

/**
 * Parses a protobuf Duration JSON value (e.g. "165s", "165.400s") — NOT an
 * ISO-8601 duration, so Carbon/CarbonInterval must not be used here.
 */
final class ProtobufDuration
{
    public static function seconds(string $value): int
    {
        if (preg_match('/^(\d+(?:\.\d+)?)s$/', $value, $matches) !== 1) {
            throw RouteComputationException::malformedDuration($value);
        }

        return (int) round((float) $matches[1]);
    }
}
