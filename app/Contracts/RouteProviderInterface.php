<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\Coordinates;
use App\DTOs\RouteDetails;
use App\Exceptions\RateLimitedException;
use App\Exceptions\RouteComputationException;

interface RouteProviderInterface
{
    /**
     * @throws RouteComputationException when no route can be computed.
     * @throws RateLimitedException when the upstream service is temporarily unavailable.
     */
    public function computeRoute(Coordinates $origin, Coordinates $destination): RouteDetails;
}
