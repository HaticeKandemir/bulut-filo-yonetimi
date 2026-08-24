<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AddressResolutionStatus;
use App\Jobs\ResolveImportRowAddressesJob;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Services\AddressResolutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AddressResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_start_and_end_addresses_and_persists_coordinates(): void
    {
        $this->fakeSuccessfulResponses('Kadıköy Mah., İstanbul');

        $row = $this->makeRow('kadıköy istanbul', 'beşiktaş istanbul');

        app(AddressResolutionService::class)->resolveRow($row);
        $row->refresh();

        $this->assertSame(AddressResolutionStatus::Resolved, $row->address_resolution_status);
        $this->assertNotNull($row->start_geocoded_address_id);
        $this->assertNotNull($row->end_geocoded_address_id);
        $this->assertSame(41.0082, $row->startGeocodedAddress->latitude);
    }

    public function test_does_not_call_external_services_twice_for_the_same_address(): void
    {
        $this->fakeSuccessfulResponses('Aynı Normalize Adres');

        $sameAddress = 'aynı adres istanbul';
        $service = app(AddressResolutionService::class);

        $service->resolveRow($this->makeRow($sameAddress, ''));
        $service->resolveRow($this->makeRow($sameAddress, ''));

        // 1 OpenAI call + 1 Google call total — the second row hits the cache.
        Http::assertSentCount(2);
    }

    public function test_marks_row_failed_when_geocoding_returns_zero_results(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response($this->openAiPayload('Bilinmeyen Adres'), 200),
            'maps.googleapis.com/*' => Http::response(['status' => 'ZERO_RESULTS', 'results' => []], 200),
        ]);

        $row = $this->makeRow('anlamsız girdi', '');

        app(AddressResolutionService::class)->resolveRow($row);
        $row->refresh();

        $this->assertSame(AddressResolutionStatus::Failed, $row->address_resolution_status);
        $this->assertNotNull($row->address_resolution_error);
    }

    public function test_skips_row_when_both_addresses_are_empty(): void
    {
        Http::fake();

        $row = $this->makeRow('', '');

        app(AddressResolutionService::class)->resolveRow($row);
        $row->refresh();

        $this->assertSame(AddressResolutionStatus::Skipped, $row->address_resolution_status);
        Http::assertNothingSent();
    }

    public function test_job_releases_without_marking_row_failed_when_rate_limited(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response($this->openAiPayload('Bir Adres'), 200),
            'maps.googleapis.com/*' => Http::response(['status' => 'OVER_QUERY_LIMIT', 'results' => []], 200),
        ]);

        $row = $this->makeRow('bir adres istanbul', '');

        $job = new ResolveImportRowAddressesJob($row);
        $job->withFakeQueueInteractions();
        $job->handle(app(AddressResolutionService::class));

        $job->assertReleased();
        $row->refresh();
        $this->assertNotSame(AddressResolutionStatus::Failed, $row->address_resolution_status);
    }

    private function makeRow(string $start, string $end): ImportRow
    {
        $batch = ImportBatch::create([
            'original_filename' => 'test.xlsx',
            'stored_path' => 'imports/test.xlsx',
        ]);

        return $batch->rows()->create([
            'row_number' => 2,
            'raw_data' => [
                'sasi_no' => 'VIN00000000000001',
                'plaka' => '34 ABC 123',
                'marka' => 'Ford',
                'model' => 'Transit',
                'kurum_kodu' => 'PTT',
                'baslangic_adresi' => $start,
                'bitis_adresi' => $end,
            ],
        ]);
    }

    private function fakeSuccessfulResponses(string $normalizedAddress): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response($this->openAiPayload($normalizedAddress), 200),
            'maps.googleapis.com/*' => Http::response([
                'status' => 'OK',
                'results' => [['geometry' => ['location' => ['lat' => 41.0082, 'lng' => 28.9784]]]],
            ], 200),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function openAiPayload(string $normalizedAddress): array
    {
        return [
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'normalized_address' => $normalizedAddress,
                            'is_resolvable' => true,
                        ]),
                    ],
                ],
            ],
        ];
    }
}
