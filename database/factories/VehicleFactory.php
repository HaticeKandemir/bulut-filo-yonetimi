<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\VehicleStatus;
use App\Models\Institution;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vin' => fake()->unique()->regexify('[A-HJ-NPR-Z0-9]{17}'),
            'brand' => fake()->randomElement(['Ford', 'Volkswagen', 'Fiat', 'Renault']),
            'model' => fake()->word(),
            'institution_id' => Institution::factory(),
            'status' => VehicleStatus::Active,
        ];
    }
}
