<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\VehicleImportRowData;
use App\Enums\ImportRowStatus;
use App\Enums\VehicleStatus;
use App\Exceptions\InvalidImportRowException;
use App\Exceptions\PlateConflictException;
use App\Models\ImportRow;
use App\Models\Vehicle;
use App\Models\VehiclePlate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class VehicleImportService
{
    /**
     * Applies the VIN/plate decision tree to a single import row and
     * records the outcome on the row itself. Never throws — all failure
     * modes are captured as a row status so the batch can keep going.
     *
     * @param  array<string, int>  $institutionCodeToId
     */
    public function processRow(ImportRow $row, array $institutionCodeToId): void
    {
        try {
            $data = VehicleImportRowData::fromRawRow($row->raw_data);

            $institutionId = $institutionCodeToId[$data->institutionCode]
                ?? throw InvalidImportRowException::unknownInstitutionCode($data->institutionCode);

            $vehicle = DB::transaction(fn () => $this->applyDecisionTree($institutionId, $data));

            $row->update([
                'status' => ImportRowStatus::Processed,
                'vehicle_id' => $vehicle->id,
            ]);
        } catch (PlateConflictException $e) {
            $row->update([
                'status' => ImportRowStatus::NeedsReview,
                'conflicting_vehicle_id' => $e->conflictingVehicle->id,
                'error_message' => $e->getMessage(),
            ]);
        } catch (Throwable $e) {
            $row->update([
                'status' => ImportRowStatus::Failed,
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    private function applyDecisionTree(int $institutionId, VehicleImportRowData $data): Vehicle
    {
        $vehicle = Vehicle::where('vin', $data->vin)->lockForUpdate()->first();

        if ($vehicle !== null) {
            return $this->updateExistingVehicle($vehicle, $institutionId, $data);
        }

        return $this->createVehicleWithPlate($institutionId, $data);
    }

    /**
     * Scenario 1: VIN already exists. Updates the vehicle, reactivates it
     * (with a log entry) if it wasn't active, and — if the plate changed —
     * closes the old assignment and opens the new one, running the same
     * conflict check used for brand-new VINs.
     */
    private function updateExistingVehicle(Vehicle $vehicle, int $institutionId, VehicleImportRowData $data): Vehicle
    {
        $wasInactive = $vehicle->status !== VehicleStatus::Active;

        $vehicle->fill([
            'brand' => $data->brand,
            'model' => $data->model,
            'institution_id' => $institutionId,
            'status' => VehicleStatus::Active,
        ]);
        $vehicle->save();

        if ($wasInactive) {
            Log::info('Vehicle reactivated during import', [
                'vehicle_id' => $vehicle->id,
                'vin' => $vehicle->vin,
            ]);
        }

        $currentPlate = $vehicle->activePlate;

        if ($currentPlate === null || $currentPlate->plate !== $data->plate) {
            $transferablePlate = $this->findTransferablePlate($data->plate, excludingVehicleId: $vehicle->id);

            if ($currentPlate !== null) {
                $this->closePlate($currentPlate);
            }

            if ($transferablePlate !== null) {
                $this->closePlate($transferablePlate);
            }

            $this->openPlate($vehicle, $data->plate);
        }

        return $vehicle;
    }

    /**
     * Scenarios 2/3/4: VIN is new. The plate's current active holder (if
     * any) decides the branch inside findTransferablePlate() — Passive/
     * LeftFleet means transfer (2), Active means PlateConflictException (3),
     * not found means the plate is free (4).
     */
    private function createVehicleWithPlate(int $institutionId, VehicleImportRowData $data): Vehicle
    {
        $transferablePlate = $this->findTransferablePlate($data->plate, excludingVehicleId: null);

        $vehicle = Vehicle::create([
            'vin' => $data->vin,
            'brand' => $data->brand,
            'model' => $data->model,
            'institution_id' => $institutionId,
            'status' => VehicleStatus::Active,
        ]);

        if ($transferablePlate !== null) {
            $this->closePlate($transferablePlate);
        }

        $this->openPlate($vehicle, $data->plate);

        return $vehicle;
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
