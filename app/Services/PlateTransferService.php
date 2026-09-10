<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\VehicleStatus;
use App\Exceptions\PlateConflictException;
use App\Models\Vehicle;
use App\Models\VehiclePlate;

final class PlateTransferService
{
    /**
     * Assigns $newPlate to $vehicle as its active plate. No-op if $vehicle
     * already holds $newPlate. Otherwise closes $vehicle's current active
     * assignment (if any) and, if $newPlate is currently held by another
     * vehicle, transfers it away from that vehicle — unless that vehicle is
     * still Active, in which case the plate is genuinely in use elsewhere
     * and the change is rejected rather than silently overwritten.
     *
     * Shared by the import decision tree (VehicleImportService) and the
     * vehicle detail edit endpoint, so both paths enforce the same
     * uniqueness/conflict rule.
     *
     * @throws PlateConflictException when $newPlate is currently active on
     *                                another Active vehicle.
     */
    public function transferPlate(Vehicle $vehicle, string $newPlate): void
    {
        $currentPlate = $vehicle->activePlate;

        if ($currentPlate !== null && $currentPlate->plate === $newPlate) {
            return;
        }

        $transferablePlate = $this->findTransferablePlate($newPlate, excludingVehicleId: $vehicle->id);

        if ($currentPlate !== null) {
            $this->closePlate($currentPlate);
        }

        if ($transferablePlate !== null) {
            $this->closePlate($transferablePlate);
        }

        $this->openPlate($vehicle, $newPlate);
    }

    /**
     * Finds $plate's current active assignment. Returns null when the
     * plate is free, or already held by $excludingVehicleId (no-op case).
     *
     * @throws PlateConflictException when the current holder is Active.
     */
    private function findTransferablePlate(string $plate, ?int $excludingVehicleId): ?VehiclePlate
    {
        $activePlate = VehiclePlate::where('plate', $plate)
            ->where('released_at', VehiclePlate::ACTIVE_SENTINEL)
            ->lockForUpdate()
            ->first();

        if ($activePlate === null || $activePlate->vehicle_id === $excludingVehicleId) {
            return null;
        }

        if ($activePlate->vehicle->status === VehicleStatus::Active) {
            throw PlateConflictException::forPlate($plate, $activePlate->vehicle);
        }

        return $activePlate;
    }

    private function closePlate(VehiclePlate $plate): void
    {
        $plate->released_at = now();
        $plate->save();
    }

    private function openPlate(Vehicle $vehicle, string $plate): void
    {
        VehiclePlate::create([
            'vehicle_id' => $vehicle->id,
            'plate' => $plate,
            'assigned_at' => now(),
        ]);
    }
}
