<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VehicleStatus;
use App\Observers\VehicleObserver;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['vin', 'brand', 'model', 'institution_id', 'status'])]
#[ObservedBy(VehicleObserver::class)]
class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory;

    /**
     * Cache tag covering every cached read of vehicle data (list queries and
     * single-record loads). Flushed by VehicleObserver/VehiclePlateObserver
     * whenever a Vehicle or VehiclePlate row is written.
     */
    public const string CACHE_TAG = 'vehicles';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => VehicleStatus::class,
        ];
    }

    /** @return BelongsTo<Institution, $this> */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /** @return HasMany<VehiclePlate, $this> */
    public function plates(): HasMany
    {
        return $this->hasMany(VehiclePlate::class);
    }

    /** @return HasOne<VehiclePlate, $this> */
    public function activePlate(): HasOne
    {
        return $this->hasOne(VehiclePlate::class)
            ->where('released_at', VehiclePlate::ACTIVE_SENTINEL);
    }
}
