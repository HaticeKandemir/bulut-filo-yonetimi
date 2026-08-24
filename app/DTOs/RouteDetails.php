<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class RouteDetails
{
    public function __construct(
        public int $distanceMeters,
        public int $durationSeconds,
        public string $polyline,
    ) {}
}
