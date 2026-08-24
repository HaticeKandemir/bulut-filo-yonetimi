<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ImportRowStatus;
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
}
