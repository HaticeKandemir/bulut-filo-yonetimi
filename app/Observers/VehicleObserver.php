<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Vehicle;
use Illuminate\Support\Facades\Cache;

class VehicleObserver
{
    public function saved(Vehicle $vehicle): void
    {
        Cache::tags([Vehicle::CACHE_TAG])->flush();
    }

    public function deleted(Vehicle $vehicle): void
    {
        Cache::tags([Vehicle::CACHE_TAG])->flush();
    }
}
