<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AddressResolutionStatus;
use App\Enums\RouteComputationStatus;
use App\Jobs\ComputeImportRowRouteJob;
use App\Models\GeocodedAddress;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Route;
use App\Services\RouteComputationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RouteComputationTest extends TestCase
{
    use RefreshDatabase;

    public function test_computes_and_persists_a_route(): void
    {
        Http::fake([
            'routes.googleapis.com/*' => Http::response($this->routesPayload(772, '165s', 'encodedPolylineHere'), 200),
        ]);

        $row = $this->makeResolvedRow();

        app(RouteComputationService::class)->resolveRow($row);
        $row->refresh();

        $this->assertSame(RouteComputationStatus::Computed, $row->route_computation_status);
        $this->assertNotNull($row->route_id);
        $this->assertSame(772, $row->route->distance_meters);
        $this->assertSame(165, $row->route->duration_seconds);
        $this->assertSame('encodedPolylineHere', $row->route->polyline);
    }

    public function test_reuses_an_existing_route_for_the_same_address_pair(): void
    {
        Http::fake([
            'routes.googleapis.com/*' => Http::response($this->routesPayload(772, '165s', 'poly'), 200),
        ]);

        $row1 = $this->makeResolvedRow();
        $start = GeocodedAddress::find($row1->start_geocoded_address_id);
        $end = GeocodedAddress::find($row1->end_geocoded_address_id);
        $row2 = $this->makeResolvedRow($start, $end);

        $service = app(RouteComputationService::class);
        $service->resolveRow($row1);
        $service->resolveRow($row2);

        Http::assertSentCount(1);
        $row1->refresh();
        $row2->refresh();
        $this->assertSame($row1->route_id, $row2->route_id);
    }

    public function test_reuses_an_existing_route_row_without_calling_the_provider(): void
    {
        Http::fake();

        $row = $this->makeResolvedRow();
        Route::create([
            'start_geocoded_address_id' => $row->start_geocoded_address_id,
            'end_geocoded_address_id' => $row->end_geocoded_address_id,
            'distance_meters' => 500,
            'duration_seconds' => 60,
            'polyline' => 'existingPolyline',
        ]);

        app(RouteComputationService::class)->resolveRow($row);
        $row->refresh();

        $this->assertSame(RouteComputationStatus::Computed, $row->route_computation_status);
        $this->assertSame('existingPolyline', $row->route->polyline);
        Http::assertNothingSent();
    }

    public function test_marks_row_failed_when_no_route_is_found(): void
    {
        Http::fake([
            'routes.googleapis.com/*' => Http::response(['error' => ['code' => 404, 'status' => 'NOT_FOUND']], 404),
        ]);

        $row = $this->makeResolvedRow();

        app(RouteComputationService::class)->resolveRow($row);
        $row->refresh();

        $this->assertSame(RouteComputationStatus::Failed, $row->route_computation_status);
        $this->assertNotNull($row->route_computation_error);
    }

    public function test_job_releases_without_marking_row_failed_when_rate_limited(): void
    {
        Http::fake([
            'routes.googleapis.com/*' => Http::response(['error' => ['code' => 429, 'status' => 'RESOURCE_EXHAUSTED']], 429),
        ]);

        $row = $this->makeResolvedRow();

        $job = new ComputeImportRowRouteJob($row);
        $job->withFakeQueueInteractions();
        $job->handle(app(RouteComputationService::class));

        $job->assertReleased();
        $row->refresh();
        $this->assertNotSame(RouteComputationStatus::Failed, $row->route_computation_status);
    }

    public function test_skips_row_when_address_not_resolved(): void
    {
        Http::fake();

        $batch = ImportBatch::create(['original_filename' => 'test.xlsx', 'stored_path' => 'imports/test.xlsx']);
        $row = $batch->rows()->create([
            'row_number' => 2,
            'raw_data' => [],
            'address_resolution_status' => AddressResolutionStatus::Failed,
        ]);

        app(RouteComputationService::class)->resolveRow($row);
        $row->refresh();

        $this->assertSame(RouteComputationStatus::Skipped, $row->route_computation_status);
        Http::assertNothingSent();
    }

    private function makeResolvedRow(?GeocodedAddress $start = null, ?GeocodedAddress $end = null): ImportRow
    {
        $start ??= GeocodedAddress::create([
            'normalized_address_hash' => bin2hex(random_bytes(16)),
            'normalized_address' => 'Start Address',
            'latitude' => 41.0,
            'longitude' => 29.0,
        ]);

        $end ??= GeocodedAddress::create([
            'normalized_address_hash' => bin2hex(random_bytes(16)),
            'normalized_address' => 'End Address',
            'latitude' => 38.4,
            'longitude' => 27.1,
        ]);

        $batch = ImportBatch::create([
            'original_filename' => 'test.xlsx',
            'stored_path' => 'imports/test.xlsx',
        ]);

        return $batch->rows()->create([
            'row_number' => 2,
            'raw_data' => [],
            'address_resolution_status' => AddressResolutionStatus::Resolved,
            'start_geocoded_address_id' => $start->id,
            'end_geocoded_address_id' => $end->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function routesPayload(int $distanceMeters, string $duration, string $polyline): array
    {
        return [
            'routes' => [
                [
                    'distanceMeters' => $distanceMeters,
                    'duration' => $duration,
                    'polyline' => ['encodedPolyline' => $polyline],
                ],
            ],
        ];
    }
}
