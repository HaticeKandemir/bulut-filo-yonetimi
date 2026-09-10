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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class VehicleImportService
{
    public function __construct(
        private readonly PlateTransferService $plateTransfer,
    ) {}

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

            // Denormalized pointer to this vehicle's most recently processed
            // row, so a vehicle-centric read (fleet map, routes screen) can
            // reach its current start/end coordinates and route in one join
            // instead of a "latest row per vehicle" subquery. Rows within a
            // batch are processed in row_number order, so this is simply
            // the most recent successful assignment.
            $vehicle->update(['latest_import_row_id' => $row->id]);
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
     * (with a log entry) if it wasn't active, and transfers the plate via
     * PlateTransferService if it changed (running the same conflict check
     * used for brand-new VINs).
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

        $this->plateTransfer->transferPlate($vehicle, $data->plate);

        return $vehicle;
    }

    /**
     * Scenarios 2/3/4: VIN is new. The plate's current active holder (if
     * any) decides the branch inside PlateTransferService — Passive/
     * LeftFleet means transfer (2), Active means PlateConflictException (3),
     * not found means the plate is free (4).
     */
    private function createVehicleWithPlate(int $institutionId, VehicleImportRowData $data): Vehicle
    {
        $vehicle = Vehicle::create([
            'vin' => $data->vin,
            'brand' => $data->brand,
            'model' => $data->model,
            'institution_id' => $institutionId,
            'status' => VehicleStatus::Active,
        ]);

        $this->plateTransfer->transferPlate($vehicle, $data->plate);

        return $vehicle;
    }
}
