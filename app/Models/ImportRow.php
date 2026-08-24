<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AddressResolutionStatus;
use App\Enums\ImportRowStatus;
use App\Enums\RouteComputationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'import_batch_id',
    'row_number',
    'raw_data',
    'status',
    'vehicle_id',
    'conflicting_vehicle_id',
    'error_message',
    'start_geocoded_address_id',
    'end_geocoded_address_id',
    'address_resolution_status',
    'address_resolution_error',
    'route_id',
    'route_computation_status',
    'route_computation_error',
])]
class ImportRow extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'raw_data' => 'array',
            'status' => ImportRowStatus::class,
            'address_resolution_status' => AddressResolutionStatus::class,
            'route_computation_status' => RouteComputationStatus::class,
        ];
    }

    /** @return BelongsTo<ImportBatch, $this> */
    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }

    /** @return BelongsTo<Vehicle, $this> */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /** @return BelongsTo<Vehicle, $this> */
    public function conflictingVehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'conflicting_vehicle_id');
    }

    /** @return BelongsTo<GeocodedAddress, $this> */
    public function startGeocodedAddress(): BelongsTo
    {
        return $this->belongsTo(GeocodedAddress::class, 'start_geocoded_address_id');
    }

    /** @return BelongsTo<GeocodedAddress, $this> */
    public function endGeocodedAddress(): BelongsTo
    {
        return $this->belongsTo(GeocodedAddress::class, 'end_geocoded_address_id');
    }

    /** @return BelongsTo<Route, $this> */
    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }
}
