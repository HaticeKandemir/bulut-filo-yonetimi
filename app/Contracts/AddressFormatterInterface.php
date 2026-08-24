<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Exceptions\AddressResolutionException;
use App\Exceptions\RateLimitedException;

interface AddressFormatterInterface
{
    /**
     * @throws AddressResolutionException when the address cannot be normalised.
     * @throws RateLimitedException when the upstream service is temporarily unavailable.
     */
    public function normalize(string $rawAddress): string;
}
