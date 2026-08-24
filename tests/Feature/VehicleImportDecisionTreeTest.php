<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ImportRowStatus;
use App\Enums\VehicleStatus;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Models\Vehicle;
use App\Models\VehiclePlate;
use App\Services\VehicleImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleImportDecisionTreeTest extends TestCase
{
    use RefreshDatabase;

    private VehicleImportService $service;

    private Institution $institution;

    /** @var array<string, int> */
    private array $institutionCodeToId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new VehicleImportService;
        $this->institution = Institution::create(['name' => 'PTT', 'code' => 'PTT']);
        $this->institutionCodeToId = ['PTT' => $this->institution->id];
    }

    public function test_scenario_1_updates_existing_vehicle_when_plate_is_unchanged(): void
    {
        $vehicle = $this->makeVehicle($this->vin(1), VehicleStatus::Active);
        $this->makePlate($vehicle, '34 ABC 123');

        $row = $this->makeRow($this->vin(1), '34 ABC 123');
        $this->service->processRow($row, $this->institutionCodeToId);

        $row->refresh();
        $vehicle->refresh();

        $this->assertSame(ImportRowStatus::Processed, $row->status);
        $this->assertSame('Transit', $vehicle->model);
        $this->assertSame(1, $vehicle->plates()->count());
    }

    public function test_scenario_1_reactivates_passive_vehicle_and_transfers_its_plate(): void
    {
        $vehicle = $this->makeVehicle($this->vin(2), VehicleStatus::Passive);
        $this->makePlate($vehicle, '34 OLD 001');

        $row = $this->makeRow($this->vin(2), '34 NEW 002');
        $this->service->processRow($row, $this->institutionCodeToId);

        $vehicle->refresh();

        $this->assertSame(VehicleStatus::Active, $vehicle->status);
        $this->assertSame('34 NEW 002', $vehicle->activePlate->plate);
        $this->assertNotNull(
            $vehicle->plates()->where('plate', '34 OLD 001')->first()->released_at,
        );
    }

    public function test_scenario_2_transfers_plate_from_a_left_fleet_vehicle(): void
    {
        $oldVehicle = $this->makeVehicle($this->vin(3), VehicleStatus::LeftFleet);
        $this->makePlate($oldVehicle, '34 SHR 001');

        $row = $this->makeRow($this->vin(4), '34 SHR 001');
        $this->service->processRow($row, $this->institutionCodeToId);

        $row->refresh();
        $newVehicle = Vehicle::where('vin', $this->vin(4))->first();

        $this->assertSame(ImportRowStatus::Processed, $row->status);
        $this->assertNotNull($newVehicle);
        $this->assertSame('34 SHR 001', $newVehicle->activePlate->plate);
        $this->assertNull($oldVehicle->fresh()->activePlate);
    }

    public function test_scenario_3_flags_conflict_when_plate_is_active_on_another_vehicle(): void
    {
        $activeVehicle = $this->makeVehicle($this->vin(5), VehicleStatus::Active);
        $this->makePlate($activeVehicle, '34 BSY 001');

        $row = $this->makeRow($this->vin(6), '34 BSY 001');
        $this->service->processRow($row, $this->institutionCodeToId);

        $row->refresh();

        $this->assertSame(ImportRowStatus::NeedsReview, $row->status);
        $this->assertSame($activeVehicle->id, $row->conflicting_vehicle_id);
        $this->assertNull(Vehicle::where('vin', $this->vin(6))->first());
    }

    public function test_scenario_4_assigns_a_free_plate_directly(): void
    {
        $row = $this->makeRow($this->vin(7), '34 FRE 001');
        $this->service->processRow($row, $this->institutionCodeToId);

        $row->refresh();
        $vehicle = Vehicle::where('vin', $this->vin(7))->first();

        $this->assertSame(ImportRowStatus::Processed, $row->status);
        $this->assertNotNull($vehicle);
        $this->assertSame(VehicleStatus::Active, $vehicle->status);
        $this->assertSame('34 FRE 001', $vehicle->activePlate->plate);
    }

    public function test_scenario_1_plate_change_conflicts_when_new_plate_is_active_elsewhere(): void
    {
        $existingVehicle = $this->makeVehicle($this->vin(8), VehicleStatus::Active);
        $this->makePlate($existingVehicle, '34 MIN 001');

        $otherVehicle = $this->makeVehicle($this->vin(9), VehicleStatus::Active);
        $this->makePlate($otherVehicle, '34 TKN 001');

        $row = $this->makeRow($this->vin(8), '34 TKN 001');
        $this->service->processRow($row, $this->institutionCodeToId);

        $row->refresh();

        $this->assertSame(ImportRowStatus::NeedsReview, $row->status);
        $this->assertSame($otherVehicle->id, $row->conflicting_vehicle_id);
        $this->assertSame('34 MIN 001', $existingVehicle->fresh()->activePlate->plate);
    }

    private function vin(int $n): string
    {
        return 'VIN'.str_pad((string) $n, 14, '0', STR_PAD_LEFT);
    }

    private function makeVehicle(string $vin, VehicleStatus $status): Vehicle
    {
        return Vehicle::create([
            'vin' => $vin,
            'brand' => 'Ford',
            'model' => 'Transit',
            'institution_id' => $this->institution->id,
            'status' => $status,
        ]);
    }

    private function makePlate(Vehicle $vehicle, string $plate): VehiclePlate
    {
        return VehiclePlate::create([
            'vehicle_id' => $vehicle->id,
            'plate' => $plate,
            'assigned_at' => now(),
        ]);
    }

    private function makeRow(string $vin, string $plate): ImportRow
    {
        $batch = ImportBatch::create([
            'original_filename' => 'test.xlsx',
            'stored_path' => 'imports/test.xlsx',
        ]);

        return $batch->rows()->create([
            'row_number' => 2,
            'raw_data' => [
                'sasi_no' => $vin,
                'plaka' => $plate,
                'marka' => 'Ford',
                'model' => 'Transit',
                'kurum_kodu' => 'PTT',
            ],
            'status' => ImportRowStatus::Pending,
        ]);
    }
}
