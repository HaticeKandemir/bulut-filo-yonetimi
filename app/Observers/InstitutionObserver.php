<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Institution;
use App\Services\InstitutionTreeService;
use Illuminate\Support\Facades\Cache;

class InstitutionObserver
{
    public function saved(Institution $institution): void
    {
        Cache::tags([InstitutionTreeService::CACHE_TAG])->flush();
    }

    public function deleted(Institution $institution): void
    {
        Cache::tags([InstitutionTreeService::CACHE_TAG])->flush();
    }
}
