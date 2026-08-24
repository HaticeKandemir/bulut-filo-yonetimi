<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ImportBatchStatus;
use App\Enums\ImportRowStatus;
use App\Imports\VehicleRowsImport;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Services\VehicleImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class ProcessVehicleImportJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ImportBatch $importBatch,
    ) {}

    public function handle(VehicleImportService $service): void
    {
        $this->importBatch->update(['status' => ImportBatchStatus::Processing]);

        if ($this->importBatch->rows()->doesntExist()) {
            $this->seedRows();
        }

        $institutionCodeToId = Institution::pluck('id', 'code')->all();

        $this->importBatch->rows()
            ->where('status', ImportRowStatus::Pending)
            ->orderBy('row_number')
            ->each(function (ImportRow $row) use ($service, $institutionCodeToId): void {
                $service->processRow($row, $institutionCodeToId);

                // Address resolution is independent of the VIN/plate outcome —
                // a needs_review/failed row can still have a valid address.
                ResolveImportRowAddressesJob::dispatch($row);
            });

        $this->importBatch->update(['status' => ImportBatchStatus::Completed]);
    }

    /**
     * Reads the uploaded file and inserts one Pending ImportRow per data
     * row. Only runs once per batch (guarded by the caller) so a retried
     * job never re-seeds and double-processes rows.
     */
    private function seedRows(): void
    {
        $sheets = Excel::toCollection(new VehicleRowsImport, $this->importBatch->stored_path, 'local');
        $rows = $sheets->first() ?? collect();

        $now = Carbon::now();

        $this->importBatch->rows()->insert(
            $rows->values()->map(fn (Collection $row, int $index) => [
                'import_batch_id' => $this->importBatch->id,
                'row_number' => $index + 2, // +1 for 1-index, +1 for the heading row
                'raw_data' => json_encode($row->all()),
                'status' => ImportRowStatus::Pending->value,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all()
        );
    }
}
