<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ImportBatchStatus;
use App\Enums\ImportRowStatus;
use App\Models\ImportRow;
use App\Services\VehicleImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessImportRowJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, int>  $institutionCodeToId
     */
    public function __construct(
        public readonly ImportRow $importRow,
        public readonly array $institutionCodeToId,
    ) {}

    public function handle(VehicleImportService $service): void
    {
        $service->processRow($this->importRow, $this->institutionCodeToId);

        // Address resolution is independent of the VIN/plate outcome —
        // a needs_review/failed row can still have a valid address.
        ResolveImportRowAddressesJob::dispatch($this->importRow);

        $this->completeBatchIfFinished();
    }

    /**
     * Rows in a batch are processed by independent, concurrently-running
     * jobs — whichever job happens to finish the last pending row is the
     * one that flips the batch to Completed. A tie between two jobs both
     * observing zero pending rows is harmless: the update is idempotent.
     */
    private function completeBatchIfFinished(): void
    {
        $batch = $this->importRow->importBatch;

        if ($batch->rows()->where('status', ImportRowStatus::Pending)->doesntExist()) {
            $batch->update(['status' => ImportBatchStatus::Completed]);
        }
    }
}
