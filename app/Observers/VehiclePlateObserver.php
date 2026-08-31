<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Vehicle;
use App\Models\VehiclePlate;
use Illuminate\Support\Facades\Cache;

/**
 * A plate transfer during import can close a VehiclePlate belonging to a
 * different vehicle than the one being processed (see
 * VehicleImportService::findTransferablePlate()) without ever saving that
 * other Vehicle row. Watching VehiclePlate separately from Vehicle is what
 * catches that case.
 */
class VehiclePlateObserver
{
    public function saved(VehiclePlate $plate): void
    {
        Cache::tags([Vehicle::CACHE_TAG])->flush();
    }

    public function deleted(VehiclePlate $plate): void
    {
        Cache::tags([Vehicle::CACHE_TAG])->flush();
    }
}
