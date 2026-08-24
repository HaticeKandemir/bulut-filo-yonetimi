<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\RouteProviderInterface;
use App\DTOs\Coordinates;
use App\Enums\AddressResolutionStatus;
use App\Enums\RouteComputationStatus;
use App\Exceptions\RouteComputationException;
use App\Models\GeocodedAddress;
use App\Models\ImportRow;
use App\Models\Route;
use Illuminate\Database\QueryException;

final class RouteComputationService
{
    public function __construct(
        private readonly RouteProviderInterface $provider,
    ) {}

    /**
     * Computes (or reuses a cached) route for a row's start/end addresses.
     * Never throws for domain failures (recorded on the row as Failed);
     * RateLimitedException is left to propagate so the caller can release
     * the job back to the queue.
     */
    public function resolveRow(ImportRow $row): void
    {
        if ($row->address_resolution_status !== AddressResolutionStatus::Resolved
            || $row->start_geocoded_address_id === null
            || $row->end_geocoded_address_id === null) {
            $row->update(['route_computation_status' => RouteComputationStatus::Skipped]);

            return;
        }

        try {
            $route = $this->findOrComputeRoute($row->startGeocodedAddress, $row->endGeocodedAddress);

            $row->update([
                'route_id' => $route->id,
                'route_computation_status' => RouteComputationStatus::Computed,
            ]);
        } catch (RouteComputationException $e) {
            $row->update([
                'route_computation_status' => RouteComputationStatus::Failed,
                'route_computation_error' => $e->getMessage(),
            ]);
        }
    }

    private function findOrComputeRoute(GeocodedAddress $start, GeocodedAddress $end): Route
    {
        $existing = $this->findRoute($start->id, $end->id);

        if ($existing !== null) {
            return $existing;
        }

        $details = $this->provider->computeRoute(
            new Coordinates($start->latitude, $start->longitude),
            new Coordinates($end->latitude, $end->longitude),
        );

        try {
            return Route::create([
                'start_geocoded_address_id' => $start->id,
                'end_geocoded_address_id' => $end->id,
                'distance_meters' => $details->distanceMeters,
                'duration_seconds' => $details->durationSeconds,
                'polyline' => $details->polyline,
            ]);
        } catch (QueryException $e) {
            // Unique constraint race: a concurrent job computed the same
            // (start, end) pair first — reuse its result instead of failing.
            return $this->findRoute($start->id, $end->id) ?? throw $e;
        }
    }

    private function findRoute(int $startId, int $endId): ?Route
    {
        return Route::where('start_geocoded_address_id', $startId)
            ->where('end_geocoded_address_id', $endId)
            ->first();
    }
}
