<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'start_geocoded_address_id',
    'end_geocoded_address_id',
    'distance_meters',
    'duration_seconds',
    'polyline',
])]
class Route extends Model
{
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
}
