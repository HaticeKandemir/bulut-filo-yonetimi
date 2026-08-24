<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Exceptions\RateLimitedException;
use App\Models\ImportRow;
use App\Services\RouteComputationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ComputeImportRowRouteJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ImportRow $importRow,
    ) {}

    public function handle(RouteComputationService $service): void
    {
        try {
            $service->resolveRow($this->importRow);
        } catch (RateLimitedException $e) {
            $this->release($e->retryAfterSeconds);
        }
    }
}
