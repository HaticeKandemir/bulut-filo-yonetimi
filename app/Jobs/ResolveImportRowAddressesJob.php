<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Exceptions\RateLimitedException;
use App\Models\ImportRow;
use App\Services\AddressResolutionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ResolveImportRowAddressesJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ImportRow $importRow,
    ) {}

    public function handle(AddressResolutionService $service): void
    {
        try {
            $service->resolveRow($this->importRow);
        } catch (RateLimitedException $e) {
            $this->release($e->retryAfterSeconds);

            return;
        }

        // Always dispatch — RouteComputationService itself decides
        // Computed/Skipped/Failed (an address Failed/Skipped or missing one
        // side must still flip route_computation_status away from its
        // Pending default, otherwise the row looks stuck "processing"
        // forever instead of reporting that no route will be computed).
        ComputeImportRowRouteJob::dispatch($this->importRow);
    }
}
