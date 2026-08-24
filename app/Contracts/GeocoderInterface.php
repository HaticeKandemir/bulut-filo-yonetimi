<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\Coordinates;
use App\Exceptions\AddressResolutionException;
use App\Exceptions\RateLimitedException;

interface GeocoderInterface
{
    /**
     * @throws AddressResolutionException when the address cannot be geocoded.
     * @throws RateLimitedException when the upstream service is temporarily unavailable.
     */
    public function geocode(string $normalizedAddress): Coordinates;
}
