<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ImportBatchStatus;
use App\Enums\ImportRowStatus;
use App\Jobs\ProcessImportRowJob;
use App\Jobs\ProcessVehicleImportJob;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Services\VehicleImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessVehicleImportJobTest extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create(['name' => 'PTT', 'code' => 'PTT']);
    }

    public function test_processes_every_pending_row_and_completes_the_batch(): void
    {
        $batch = $this->makeBatch();
        $this->makeRow($batch, 2, $this->vin(1), '34 ABC 001');
        $this->makeRow($batch, 3, $this->vin(2), '34 ABC 002');

        (new ProcessVehicleImportJob($batch))->handle();

        $batch->refresh();

        $this->assertSame(ImportBatchStatus::Completed, $batch->status);
        $this->assertSame(0, $batch->rows()->where('status', ImportRowStatus::Pending)->count());
        $this->assertSame(2, $batch->rows()->where('status', ImportRowStatus::Processed)->count());
    }

    public function test_completes_the_batch_immediately_when_no_rows_are_pending(): void
    {
        $batch = $this->makeBatch();
        // A row already processed by an earlier (retried) run — rows()
        // exists, so seeding is skipped, and there is nothing left pending.
        $this->makeRow($batch, 2, $this->vin(3), '34 ABC 003', ImportRowStatus::Processed);

        (new ProcessVehicleImportJob($batch))->handle();

        $this->assertSame(ImportBatchStatus::Completed, $batch->fresh()->status);
    }

    public function test_batch_completes_only_once_every_row_job_has_finished(): void
    {
        $batch = $this->makeBatch();
        $batch->update(['status' => ImportBatchStatus::Processing]);
        $rowOne = $this->makeRow($batch, 2, $this->vin(4), '34 ABC 004');
        $rowTwo = $this->makeRow($batch, 3, $this->vin(5), '34 ABC 005');

        $institutionCodeToId = ['PTT' => $this->institution->id];

        (new ProcessImportRowJob($rowOne, $institutionCodeToId))->handle(app(VehicleImportService::class));
        $this->assertSame(ImportBatchStatus::Processing, $batch->fresh()->status);

        (new ProcessImportRowJob($rowTwo, $institutionCodeToId))->handle(app(VehicleImportService::class));
        $this->assertSame(ImportBatchStatus::Completed, $batch->fresh()->status);
    }

    private function makeBatch(): ImportBatch
    {
        return ImportBatch::create([
            'original_filename' => 'test.xlsx',
            'stored_path' => 'imports/test.xlsx',
            'status' => ImportBatchStatus::Pending,
        ]);
    }

    private function makeRow(
        ImportBatch $batch,
        int $rowNumber,
        string $vin,
        string $plate,
        ImportRowStatus $status = ImportRowStatus::Pending,
    ): ImportRow {
        return $batch->rows()->create([
            'row_number' => $rowNumber,
            'raw_data' => [
                'sasi_no' => $vin,
                'plaka' => $plate,
                'marka' => 'Ford',
                'model' => 'Transit',
                'kurum_kodu' => 'PTT',
            ],
            'status' => $status,
        ]);
    }

    private function vin(int $n): string
    {
        return 'JOBVIN'.str_pad((string) $n, 11, '0', STR_PAD_LEFT);
    }
}
