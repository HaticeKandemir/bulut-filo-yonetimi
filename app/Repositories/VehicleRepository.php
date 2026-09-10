<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\RouteComputationStatus;
use App\Enums\VehicleStatus;
use App\Http\Resources\VehicleMapPinResource;
use App\Http\Resources\VehicleResource;
use App\Http\Resources\VehicleRouteResource;
use App\Models\Vehicle;
use App\Services\InstitutionTreeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\Exceptions\InvalidFilterQuery;
use Spatie\QueryBuilder\Exceptions\InvalidSortQuery;
use Spatie\QueryBuilder\QueryBuilder;

final class VehicleRepository
{
    private const int CACHE_TTL_MINUTES = 5;

    public function __construct(
        private readonly InstitutionTreeService $institutionTree,
    ) {}

    /**
     * Caches the already-Resource-transformed payload, not the Eloquent
     * paginator: with config('cache.serializable_classes') at its secure
     * default of false, Illuminate\Cache\RedisStore::unserialize() rejects
     * every object class, silently handing back __PHP_Incomplete_Class on a
     * cache hit. Caching the plain response array sidesteps that entirely
     * and also skips re-running the Resource transformation on a hit.
     *
     * @return array<string, mixed>
     *
     * @throws InvalidFilterQuery
     * @throws InvalidSortQuery
     */
    public function paginate(Request $request, int $perPage): array
    {
        return Cache::tags([Vehicle::CACHE_TAG])->remember(
            $this->cacheKey('vehicles:index:', $request, $perPage),
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn () => VehicleResource::collection(
                $this->query($request)->paginate($perPage)->withQueryString(),
            )->response()->getData(true),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function loadForShow(Vehicle $vehicle): array
    {
        return Cache::tags([Vehicle::CACHE_TAG])->remember(
            "vehicles:show:{$vehicle->id}",
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn () => (new VehicleResource($vehicle->load(['institution', 'activePlate', 'plates'])))->response()->getData(true),
        );
    }

    /**
     * Active vehicles whose most recently processed import row resolved a
     * start address — one pin per vehicle for the fleet map.
     *
     * @return array<string, mixed>
     */
    public function forMap(): array
    {
        return Cache::tags([Vehicle::CACHE_TAG])->remember(
            'vehicles:map',
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn () => VehicleMapPinResource::collection(
                Vehicle::query()
                    ->where('status', VehicleStatus::Active)
                    ->whereHas('latestImportRow', fn (Builder $query) => $query->whereNotNull('start_geocoded_address_id'))
                    ->with(['institution', 'activePlate', 'latestImportRow.startGeocodedAddress'])
                    ->get(),
            )->response()->getData(true),
        );
    }

    /**
     * Active vehicles whose most recently processed import row has a
     * computed route — paginated/filterable/sortable the same way as the
     * main vehicle list, for the fleet-wide "Güzergahlar" screen.
     *
     * @return array<string, mixed>
     *
     * @throws InvalidFilterQuery
     * @throws InvalidSortQuery
     */
    public function forRoutes(Request $request, int $perPage): array
    {
        return Cache::tags([Vehicle::CACHE_TAG])->remember(
            $this->cacheKey('vehicles:routes:', $request, $perPage),
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn () => VehicleRouteResource::collection(
                $this->query($request)
                    ->where('status', VehicleStatus::Active)
                    ->whereHas(
                        'latestImportRow',
                        fn (Builder $query) => $query->where('route_computation_status', RouteComputationStatus::Computed),
                    )
                    ->with(['latestImportRow.startGeocodedAddress', 'latestImportRow.endGeocodedAddress', 'latestImportRow.route'])
                    ->paginate($perPage)
                    ->withQueryString(),
            )->response()->getData(true),
        );
    }

    /**
     * @return QueryBuilder<Vehicle>
     */
    private function query(Request $request): QueryBuilder
    {
        /** @var QueryBuilder<Vehicle> $query */
        $query = QueryBuilder::for(Vehicle::class, $request);

        $query->allowedFilters(...$this->filters());
        $query->allowedSorts(...$this->sorts());
        $query->defaultSort('vin');
        $query->with(['institution', 'activePlate']);

        return $query;
    }

    /**
     * @return array<int, AllowedFilter>
     */
    private function filters(): array
    {
        return [
            AllowedFilter::partial('vin'),
            AllowedFilter::partial('brand'),
            AllowedFilter::partial('model'),
            AllowedFilter::partial('plate', 'activePlate.plate'),
            AllowedFilter::exact('status'),
            AllowedFilter::callback('institution_id', $this->institutionCascadeFilter()),
        ];
    }

    /**
     * @return array<int, AllowedSort>
     */
    private function sorts(): array
    {
        return [
            AllowedSort::field('vin'),
            AllowedSort::field('brand'),
            AllowedSort::field('model'),
            AllowedSort::field('status'),
            AllowedSort::field('created_at'),
        ];
    }

    /**
     * Selecting a non-leaf institution must surface every vehicle under it,
     * not just rows whose institution_id literally matches that node —
     * otherwise filtering by a parent institution returns almost nothing.
     *
     * @return callable(Builder<Model>, mixed, string): mixed
     */
    private function institutionCascadeFilter(): callable
    {
        return function (Builder $query, mixed $value, string $property): void {
            $ids = collect(Arr::wrap($value))
                ->map(fn (mixed $id): int => (int) $id)
                ->filter(fn (int $id): bool => $id > 0)
                ->flatMap(fn (int $id): array => $this->institutionTree->descendantIds($id))
                ->unique()
                ->values()
                ->all();

            if ($ids === []) {
                $query->whereRaw('1 = 0');

                return;
            }

            $query->whereIn('institution_id', $ids);
        };
    }

    private function cacheKey(string $prefix, Request $request, int $perPage): string
    {
        $params = $request->only(['filter', 'sort', 'page']);
        $params['per_page'] = $perPage;
        ksort($params);

        return $prefix.md5(http_build_query($params));
    }
}
