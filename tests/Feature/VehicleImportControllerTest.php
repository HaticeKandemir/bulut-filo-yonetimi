<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ImportBatchStatus;
use App\Enums\ImportRowStatus;
use App\Models\ImportBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleImportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_batches_ordered_newest_first(): void
    {
        $older = $this->makeBatch('old.xlsx', ImportBatchStatus::Completed);
        $older->created_at = now()->subDay();
        $older->save();

        $newer = $this->makeBatch('new.xlsx', ImportBatchStatus::Pending);

        $response = $this->getJson('/api/v1/vehicle-imports');

        $response->assertOk();
        $response->assertJsonPath('data.0.id', $newer->id);
        $response->assertJsonPath('data.1.id', $older->id);
    }

    public function test_index_filters_by_status(): void
    {
        $this->makeBatch('a.xlsx', ImportBatchStatus::Completed);
        $pending = $this->makeBatch('b.xlsx', ImportBatchStatus::Pending);

        $response = $this->getJson('/api/v1/vehicle-imports?status=pending');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $pending->id);
    }

    public function test_rows_include_raw_row_fields(): void
    {
        $batch = $this->makeBatch('test.xlsx', ImportBatchStatus::Completed);
        $batch->rows()->create([
            'row_number' => 2,
            'raw_data' => [
                'sasi_no' => 'WVWZZZ1JZXW000001',
                'plaka' => '34 ABC 123',
                'marka' => 'Volkswagen',
                'model' => 'Transporter',
                'kurum_kodu' => 'PTT',
                'baslangic_adresi' => 'Kızılay Mah. Ankara',
                'bitis_adresi' => 'Alsancak Mah. İzmir',
            ],
            'status' => ImportRowStatus::Processed,
        ]);

        $response = $this->getJson("/api/v1/vehicle-imports/{$batch->id}/rows");

        $response->assertOk();
        $response->assertJsonPath('data.0.vin', 'WVWZZZ1JZXW000001');
        $response->assertJsonPath('data.0.plate', '34 ABC 123');
        $response->assertJsonPath('data.0.brand', 'Volkswagen');
        $response->assertJsonPath('data.0.model', 'Transporter');
        $response->assertJsonPath('data.0.institution_code', 'PTT');
        $response->assertJsonPath('data.0.start_address', 'Kızılay Mah. Ankara');
        $response->assertJsonPath('data.0.end_address', 'Alsancak Mah. İzmir');
    }

    private function makeBatch(string $filename, ImportBatchStatus $status): ImportBatch
    {
        return ImportBatch::create([
            'original_filename' => $filename,
            'stored_path' => "imports/{$filename}",
            'status' => $status,
        ]);
    }
}
