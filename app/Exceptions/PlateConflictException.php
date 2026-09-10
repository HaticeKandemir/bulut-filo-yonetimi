<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    /**
     * Reached when this exception escapes to an HTTP-facing controller
     * (currently only the vehicle update endpoint — VehicleImportService
     * always catches it internally and records a needs_review row instead).
     */
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'conflicting_vehicle_vin' => $this->conflictingVehicle->vin,
        ], 409);
    }
}
