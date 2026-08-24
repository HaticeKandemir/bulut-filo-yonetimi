<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\Vehicle;
use App\Models\VehiclePlate;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehiclePlateUniqueActiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_plate_cannot_have_two_active_assignments(): void
    {
        $institution = Institution::create(['name' => 'PTT', 'code' => 'PTT']);

        $firstVehicle = Vehicle::create([
            'vin' => 'NM0LXXTTFLGY12345',
            'brand' => 'Ford',
            'model' => 'Transit 350L',
            'institution_id' => $institution->id,
        ]);

        $secondVehicle = Vehicle::create([
            'vin' => 'WV1ZZZ7HZKH045821',
            'brand' => 'Volkswagen',
            'model' => 'Crafter',
            'institution_id' => $institution->id,
        ]);

        VehiclePlate::create([
            'vehicle_id' => $firstVehicle->id,
            'plate' => '34 ABC 123',
            'assigned_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        VehiclePlate::create([
            'vehicle_id' => $secondVehicle->id,
            'plate' => '34 ABC 123',
            'assigned_at' => now(),
        ]);
    }
}
