<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RouteComputationStatus;
use App\Enums\VehicleStatus;
use App\Models\GeocodedAddress;
use App\Models\ImportBatch;
use App\Models\Institution;
use App\Models\Route;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehiclePlate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(), 'sanctum');
    }

    public function test_index_returns_paginated_vehicles_with_institution_and_active_plate(): void
    {
        $institution = Institution::factory()->create(['name' => 'PTT']);
        $vehicle = Vehicle::factory()->create(['institution_id' => $institution->id]);
        VehiclePlate::create([
            'vehicle_id' => $vehicle->id,
            'plate' => '34 ABC 123',
            'assigned_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/vehicles');

        $response->assertOk();
        $response->assertJsonStructure(['data', 'links', 'meta']);
        $response->assertJsonPath('data.0.id', $vehicle->id);
        $response->assertJsonPath('data.0.institution.name', 'PTT');
        $response->assertJsonPath('data.0.active_plate.plate', '34 ABC 123');
    }

    public function test_index_filters_by_vin_partial_match(): void
    {
        $match = Vehicle::factory()->create(['vin' => 'WVWZZZ1JZXW000001']);
        Vehicle::factory()->create(['vin' => 'YYYYYYYYYYYYYYYYY']);

        $response = $this->getJson('/api/v1/vehicles?filter[vin]=1JZXW');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $match->id);
    }

    public function test_index_filters_by_brand_partial_match(): void
    {
        $match = Vehicle::factory()->create(['brand' => 'Volkswagen']);
        Vehicle::factory()->create(['brand' => 'Ford']);

        $response = $this->getJson('/api/v1/vehicles?filter[brand]=Volks');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $match->id);
    }

    public function test_index_filters_by_status_exact_match(): void
    {
        $active = Vehicle::factory()->create(['status' => VehicleStatus::Active]);
        Vehicle::factory()->create(['status' => VehicleStatus::Passive]);

        $response = $this->getJson('/api/v1/vehicles?filter[status]=active');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $active->id);
    }

    public function test_index_filters_by_plate_matches_only_the_active_assignment(): void
    {
        $vehicle = Vehicle::factory()->create();
        VehiclePlate::create([
            'vehicle_id' => $vehicle->id,
            'plate' => '34 OLD 001',
            'assigned_at' => now()->subDays(10),
            'released_at' => now(),
        ]);
        VehiclePlate::create([
            'vehicle_id' => $vehicle->id,
            'plate' => '34 NEW 002',
            'assigned_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/vehicles?filter[plate]=OLD');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }

    public function test_index_institution_filter_cascades_to_descendants(): void
    {
        $ptt = Institution::factory()->create(['name' => 'PTT']);
        $eAvm = Institution::factory()->create(['name' => 'PTT E-AVM', 'parent_id' => $ptt->id]);
        $pttem = Institution::factory()->create(['name' => 'PTTEM', 'parent_id' => $eAvm->id]);
        $anadolum = Institution::factory()->create(['name' => 'PTT ANADOLUM', 'parent_id' => $ptt->id]);

        $eAvmVehicle = Vehicle::factory()->create(['institution_id' => $eAvm->id]);
        $pttemVehicle = Vehicle::factory()->create(['institution_id' => $pttem->id]);
        $siblingVehicle = Vehicle::factory()->create(['institution_id' => $anadolum->id]);

        $response = $this->getJson("/api/v1/vehicles?filter[institution_id]={$eAvm->id}");

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($eAvmVehicle->id));
        $this->assertTrue($ids->contains($pttemVehicle->id));
        $this->assertFalse($ids->contains($siblingVehicle->id));
    }

    public function test_index_rejects_unknown_filter_with_400(): void
    {
        $this->getJson('/api/v1/vehicles?filter[unknown_field]=x')->assertStatus(400);
    }

    public function test_index_rejects_unknown_sort_with_400(): void
    {
        $this->getJson('/api/v1/vehicles?sort=unknown_field')->assertStatus(400);
    }

    public function test_index_sorts_by_vin_ascending_by_default(): void
    {
        $second = Vehicle::factory()->create(['vin' => 'BBBBBBBBBBBBBBBBB']);
        $first = Vehicle::factory()->create(['vin' => 'AAAAAAAAAAAAAAAAA']);

        $response = $this->getJson('/api/v1/vehicles');

        $response->assertOk();
        $response->assertJsonPath('data.0.id', $first->id);
        $response->assertJsonPath('data.1.id', $second->id);
    }

    public function test_index_sorts_descending_when_requested(): void
    {
        $first = Vehicle::factory()->create(['brand' => 'Aaa']);
        $second = Vehicle::factory()->create(['brand' => 'Zzz']);

        $response = $this->getJson('/api/v1/vehicles?sort=-brand');

        $response->assertOk();
        $response->assertJsonPath('data.0.id', $second->id);
        $response->assertJsonPath('data.1.id', $first->id);
    }

    public function test_index_respects_per_page_bounds(): void
    {
        Vehicle::factory()->count(3)->create();

        $this->getJson('/api/v1/vehicles?per_page=100')->assertOk();
        $this->getJson('/api/v1/vehicles?per_page=101')->assertStatus(422);
        $this->getJson('/api/v1/vehicles?per_page=0')->assertStatus(422);
    }

    public function test_index_avoids_n_plus_one_queries(): void
    {
        $institution = Institution::factory()->create();
        Vehicle::factory()->count(20)->create(['institution_id' => $institution->id]);

        $this->expectsDatabaseQueryCount(4);

        $this->getJson('/api/v1/vehicles')->assertOk();
    }

    public function test_index_reuses_cached_result_on_identical_query(): void
    {
        $institution = Institution::factory()->create();
        Vehicle::factory()->count(5)->create(['institution_id' => $institution->id]);

        $this->expectsDatabaseQueryCount(4);

        $this->getJson('/api/v1/vehicles?filter[status]=active')->assertOk();
        $this->getJson('/api/v1/vehicles?filter[status]=active')->assertOk();
    }

    public function test_show_returns_vehicle_with_institution_and_active_plate(): void
    {
        $institution = Institution::factory()->create(['name' => 'PTT']);
        $vehicle = Vehicle::factory()->create(['institution_id' => $institution->id]);
        VehiclePlate::create([
            'vehicle_id' => $vehicle->id,
            'plate' => '34 ABC 123',
            'assigned_at' => now(),
        ]);

        $response = $this->getJson("/api/v1/vehicles/{$vehicle->id}");

        $response->assertOk();
        $response->assertJsonPath('data.id', $vehicle->id);
        $response->assertJsonPath('data.institution.name', 'PTT');
        $response->assertJsonPath('data.active_plate.plate', '34 ABC 123');
    }

    public function test_show_returns_plate_history_newest_first_with_active_flag(): void
    {
        $vehicle = Vehicle::factory()->create();
        $oldPlate = VehiclePlate::create([
            'vehicle_id' => $vehicle->id,
            'plate' => '34 OLD 001',
            'assigned_at' => now()->subDays(10),
            'released_at' => now()->subDay(),
        ]);
        $currentPlate = VehiclePlate::create([
            'vehicle_id' => $vehicle->id,
            'plate' => '34 NEW 002',
            'assigned_at' => now(),
        ]);

        $response = $this->getJson("/api/v1/vehicles/{$vehicle->id}");

        $response->assertOk();
        $response->assertJsonCount(2, 'data.plate_history');
        $response->assertJsonPath('data.plate_history.0.plate', $currentPlate->plate);
        $response->assertJsonPath('data.plate_history.0.is_active', true);
        $response->assertJsonPath('data.plate_history.1.plate', $oldPlate->plate);
        $response->assertJsonPath('data.plate_history.1.is_active', false);
    }

    public function test_show_returns_404_for_missing_vehicle(): void
    {
        $this->getJson('/api/v1/vehicles/999999')->assertNotFound();
    }

    public function test_map_returns_active_vehicles_with_resolved_start_coordinates(): void
    {
        $vehicle = Vehicle::factory()->create(['status' => VehicleStatus::Active]);
        VehiclePlate::create(['vehicle_id' => $vehicle->id, 'plate' => '34 ABC 123', 'assigned_at' => now()]);
        $this->attachLatestImportRow($vehicle, lat: 41.0082, lng: 28.9784);

        $response = $this->getJson('/api/v1/vehicles/map');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $vehicle->id);
        $response->assertJsonPath('data.0.plate', '34 ABC 123');
        $response->assertJsonPath('data.0.start.lat', 41.0082);
        $response->assertJsonPath('data.0.start.lng', 28.9784);
    }

    public function test_map_excludes_passive_and_left_fleet_vehicles(): void
    {
        $passive = Vehicle::factory()->create(['status' => VehicleStatus::Passive]);
        $this->attachLatestImportRow($passive);
        $leftFleet = Vehicle::factory()->create(['status' => VehicleStatus::LeftFleet]);
        $this->attachLatestImportRow($leftFleet);

        $response = $this->getJson('/api/v1/vehicles/map');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }

    public function test_map_excludes_vehicles_without_a_resolved_start_address(): void
    {
        Vehicle::factory()->create(['status' => VehicleStatus::Active]);

        $response = $this->getJson('/api/v1/vehicles/map');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }

    public function test_map_avoids_n_plus_one_queries(): void
    {
        $institution = Institution::factory()->create();
        Vehicle::factory()
            ->count(5)
            ->create(['institution_id' => $institution->id, 'status' => VehicleStatus::Active])
            ->each(fn (Vehicle $vehicle) => $this->attachLatestImportRow($vehicle));

        $this->expectsDatabaseQueryCount(5);

        $this->getJson('/api/v1/vehicles/map')->assertOk();
    }

    private function attachLatestImportRow(Vehicle $vehicle, float $lat = 41.0, float $lng = 29.0): void
    {
        $address = GeocodedAddress::create([
            'normalized_address_hash' => bin2hex(random_bytes(16)),
            'normalized_address' => 'Test Address',
            'latitude' => $lat,
            'longitude' => $lng,
        ]);

        $batch = ImportBatch::create(['original_filename' => 'test.xlsx', 'stored_path' => 'imports/test.xlsx']);

        $row = $batch->rows()->create([
            'row_number' => 2,
            'raw_data' => [],
            'vehicle_id' => $vehicle->id,
            'start_geocoded_address_id' => $address->id,
        ]);

        $vehicle->update(['latest_import_row_id' => $row->id]);
    }

    public function test_routes_returns_active_vehicles_with_computed_routes(): void
    {
        $vehicle = Vehicle::factory()->create(['status' => VehicleStatus::Active]);
        VehiclePlate::create(['vehicle_id' => $vehicle->id, 'plate' => '34 ABC 123', 'assigned_at' => now()]);
        $this->attachLatestRoute($vehicle);

        $response = $this->getJson('/api/v1/vehicles/routes');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $vehicle->id);
        $response->assertJsonPath('data.0.plate', '34 ABC 123');
        $response->assertJsonPath('data.0.route.distance_meters', 12000);
    }

    public function test_routes_excludes_vehicles_without_a_computed_route(): void
    {
        $vehicle = Vehicle::factory()->create(['status' => VehicleStatus::Active]);
        $this->attachLatestImportRow($vehicle);

        $response = $this->getJson('/api/v1/vehicles/routes');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }

    public function test_routes_excludes_passive_vehicles(): void
    {
        $vehicle = Vehicle::factory()->create(['status' => VehicleStatus::Passive]);
        $this->attachLatestRoute($vehicle);

        $response = $this->getJson('/api/v1/vehicles/routes');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }

    public function test_routes_filters_by_vin_partial_match(): void
    {
        $match = Vehicle::factory()->create(['vin' => 'WVWZZZ1JZXW000001', 'status' => VehicleStatus::Active]);
        $this->attachLatestRoute($match);
        $other = Vehicle::factory()->create(['vin' => 'YYYYYYYYYYYYYYYYY', 'status' => VehicleStatus::Active]);
        $this->attachLatestRoute($other);

        $response = $this->getJson('/api/v1/vehicles/routes?filter[vin]=1JZXW');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $match->id);
    }

    public function test_routes_avoids_n_plus_one_queries(): void
    {
        $institution = Institution::factory()->create();
        Vehicle::factory()
            ->count(5)
            ->create(['institution_id' => $institution->id, 'status' => VehicleStatus::Active])
            ->each(fn (Vehicle $vehicle) => $this->attachLatestRoute($vehicle));

        $this->expectsDatabaseQueryCount(8);

        $this->getJson('/api/v1/vehicles/routes')->assertOk();
    }

    private function attachLatestRoute(Vehicle $vehicle): void
    {
        $start = GeocodedAddress::create([
            'normalized_address_hash' => bin2hex(random_bytes(16)),
            'normalized_address' => 'Start Address',
            'latitude' => 41.0082,
            'longitude' => 28.9784,
        ]);
        $end = GeocodedAddress::create([
            'normalized_address_hash' => bin2hex(random_bytes(16)),
            'normalized_address' => 'End Address',
            'latitude' => 38.4192,
            'longitude' => 27.1287,
        ]);
        $route = Route::create([
            'start_geocoded_address_id' => $start->id,
            'end_geocoded_address_id' => $end->id,
            'distance_meters' => 12000,
            'duration_seconds' => 1800,
            'polyline' => 'encoded-polyline',
        ]);

        $batch = ImportBatch::create(['original_filename' => 'test.xlsx', 'stored_path' => 'imports/test.xlsx']);

        $row = $batch->rows()->create([
            'row_number' => 2,
            'raw_data' => [],
            'vehicle_id' => $vehicle->id,
            'start_geocoded_address_id' => $start->id,
            'end_geocoded_address_id' => $end->id,
            'route_id' => $route->id,
            'route_computation_status' => RouteComputationStatus::Computed,
        ]);

        $vehicle->update(['latest_import_row_id' => $row->id]);
    }

    public function test_update_changes_brand_model_and_institution(): void
    {
        $oldInstitution = Institution::factory()->create();
        $newInstitution = Institution::factory()->create();
        $vehicle = Vehicle::factory()->create(['brand' => 'Ford', 'model' => 'Transit', 'institution_id' => $oldInstitution->id]);
        VehiclePlate::create(['vehicle_id' => $vehicle->id, 'plate' => '34 ABC 123', 'assigned_at' => now()]);

        $response = $this->patchJson("/api/v1/vehicles/{$vehicle->id}", [
            'brand' => 'Volkswagen',
            'model' => 'Crafter',
            'institution_id' => $newInstitution->id,
            'plate' => '34 ABC 123',
            'status' => 'active',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.brand', 'Volkswagen');
        $response->assertJsonPath('data.model', 'Crafter');
        $response->assertJsonPath('data.institution.id', $newInstitution->id);
        $response->assertJsonPath('data.active_plate.plate', '34 ABC 123');
        $this->assertSame(1, $vehicle->plates()->count());
    }

    public function test_update_changes_status_to_passive(): void
    {
        $vehicle = Vehicle::factory()->create(['status' => VehicleStatus::Active]);
        VehiclePlate::create(['vehicle_id' => $vehicle->id, 'plate' => '34 ABC 123', 'assigned_at' => now()]);

        $response = $this->patchJson("/api/v1/vehicles/{$vehicle->id}", [
            'brand' => $vehicle->brand,
            'model' => $vehicle->model,
            'institution_id' => $vehicle->institution_id,
            'plate' => '34 ABC 123',
            'status' => 'passive',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.status', 'passive');
        $this->assertSame(VehicleStatus::Passive, $vehicle->fresh()->status);
    }

    public function test_update_returns_422_for_invalid_status(): void
    {
        $vehicle = Vehicle::factory()->create();

        $response = $this->patchJson("/api/v1/vehicles/{$vehicle->id}", [
            'brand' => $vehicle->brand,
            'model' => $vehicle->model,
            'institution_id' => $vehicle->institution_id,
            'plate' => '34 ABC 123',
            'status' => 'scrapped',
        ]);

        $response->assertStatus(422);
    }

    public function test_update_transfers_plate_and_closes_old_assignment(): void
    {
        $vehicle = Vehicle::factory()->create();
        VehiclePlate::create(['vehicle_id' => $vehicle->id, 'plate' => '34 OLD 001', 'assigned_at' => now()]);

        $response = $this->patchJson("/api/v1/vehicles/{$vehicle->id}", [
            'brand' => $vehicle->brand,
            'model' => $vehicle->model,
            'institution_id' => $vehicle->institution_id,
            'plate' => '34 NEW 002',
            'status' => $vehicle->status->value,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.active_plate.plate', '34 NEW 002');
        $this->assertNotNull($vehicle->plates()->where('plate', '34 OLD 001')->first()->released_at);
    }

    public function test_update_is_a_no_op_when_plate_is_unchanged(): void
    {
        $vehicle = Vehicle::factory()->create();
        VehiclePlate::create(['vehicle_id' => $vehicle->id, 'plate' => '34 ABC 123', 'assigned_at' => now()]);

        $response = $this->patchJson("/api/v1/vehicles/{$vehicle->id}", [
            'brand' => $vehicle->brand,
            'model' => $vehicle->model,
            'institution_id' => $vehicle->institution_id,
            'plate' => '34 ABC 123',
            'status' => $vehicle->status->value,
        ]);

        $response->assertOk();
        $this->assertSame(1, $vehicle->plates()->count());
    }

    public function test_update_returns_409_when_plate_is_active_on_another_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create();
        $otherVehicle = Vehicle::factory()->create(['status' => VehicleStatus::Active]);
        VehiclePlate::create(['vehicle_id' => $otherVehicle->id, 'plate' => '34 TKN 001', 'assigned_at' => now()]);

        $response = $this->patchJson("/api/v1/vehicles/{$vehicle->id}", [
            'brand' => $vehicle->brand,
            'model' => $vehicle->model,
            'institution_id' => $vehicle->institution_id,
            'plate' => '34 TKN 001',
            'status' => $vehicle->status->value,
        ]);

        $response->assertStatus(409);
        $response->assertJsonPath('conflicting_vehicle_vin', $otherVehicle->vin);
        $this->assertNull($vehicle->fresh()->activePlate);
    }

    public function test_update_returns_422_for_invalid_institution_id(): void
    {
        $vehicle = Vehicle::factory()->create();

        $response = $this->patchJson("/api/v1/vehicles/{$vehicle->id}", [
            'brand' => 'Ford',
            'model' => 'Transit',
            'institution_id' => 999999,
            'plate' => '34 ABC 123',
            'status' => 'active',
        ]);

        $response->assertStatus(422);
    }

    public function test_update_returns_404_for_missing_vehicle(): void
    {
        $this->patchJson('/api/v1/vehicles/999999', [
            'brand' => 'Ford',
            'model' => 'Transit',
            'institution_id' => Institution::factory()->create()->id,
            'plate' => '34 ABC 123',
            'status' => 'active',
        ])->assertNotFound();
    }
}
