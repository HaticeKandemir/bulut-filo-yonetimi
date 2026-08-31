<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\VehicleStatus;
use App\Models\Institution;
use App\Models\Vehicle;
use App\Models\VehiclePlate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleControllerTest extends TestCase
{
    use RefreshDatabase;

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
}
