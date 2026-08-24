<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['vehicle_id', 'plate', 'assigned_at', 'released_at'])]
class VehiclePlate extends Model
{
    /**
     * Sentinel `released_at` value marking a plate assignment as currently active.
     * Paired with the UNIQUE(plate, released_at) index to guarantee only one
     * active assignment can exist per plate at a time.
     */
    public const string ACTIVE_SENTINEL = '9999-12-31 00:00:00';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Vehicle, $this> */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
