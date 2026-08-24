<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Vehicle;
use RuntimeException;

final class PlateConflictException extends RuntimeException
{
    private function __construct(
        string $message,
        public readonly Vehicle $conflictingVehicle,
    ) {
        parent::__construct($message);
    }

    public static function forPlate(string $plate, Vehicle $conflictingVehicle): self
    {
        return new self(
            "Plate \"{$plate}\" is currently active on vehicle VIN {$conflictingVehicle->vin}.",
            $conflictingVehicle,
        );
    }
}
