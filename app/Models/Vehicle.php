<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VehicleStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['vin', 'brand', 'model', 'institution_id', 'status'])]
class Vehicle extends Model
{
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
