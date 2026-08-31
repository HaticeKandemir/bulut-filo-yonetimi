<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Institution;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class InstitutionTreeService
{
    public const string CACHE_TAG = 'institutions';

    /**
     * Root institutions with `children` populated recursively. Loaded as a
     * single flat query regardless of tree depth, then nested in memory, so
     * depth is never hardcoded.
     *
     * @return Collection<int, Institution>
     */
    public function tree(): Collection
    {
        return $this->buildBranch($this->groupedByParent(), 0);
    }

    /**
     * @return array<int, int> The given institution's id plus every descendant's id.
     */
    public function descendantIds(int $institutionId): array
    {
        $byParent = $this->groupedByParent();
        $ids = [$institutionId];
        $this->appendDescendantIds($byParent, $institutionId, $ids);

        return $ids;
    }

    /**
     * Cached as plain attribute arrays, not Institution instances: with
     * config('cache.serializable_classes') at its secure default of false,
     * Illuminate\Cache\RedisStore::unserialize() rejects every object class,
     * silently handing back __PHP_Incomplete_Class on a cache hit. Rehydrating
     * via Institution::hydrate() below reconstructs real models without a
     * second query.
     *
     * @return Collection<int, Institution>
     */
    private function all(): Collection
    {
        $rows = Cache::tags([self::CACHE_TAG])->remember(
            'institutions:flat',
            now()->addDay(),
            fn () => Institution::query()->select(['id', 'name', 'code', 'parent_id'])->get()->toArray(),
        );

        return Institution::hydrate($rows);
    }

    /**
     * Groups by parent_id, normalising null (root) to 0 — Collection::groupBy
     * coerces a null column value to the string key "", not null, so relying
     * on ->get(null) for roots would silently return an empty collection.
     *
     * @return Collection<int, Collection<int, Institution>>
     */
    private function groupedByParent(): Collection
    {
        return $this->all()->groupBy(fn (Institution $institution): int => $institution->parent_id ?? 0);
    }

    /**
     * @param  Collection<int, Collection<int, Institution>>  $byParent
     * @return Collection<int, Institution>
     */
    private function buildBranch(Collection $byParent, int $parentKey): Collection
    {
        return $byParent->get($parentKey, collect())
            ->map(function (Institution $institution) use ($byParent): Institution {
                $institution->setRelation('children', $this->buildBranch($byParent, $institution->id));

                return $institution;
            })
            ->values();
    }

    /**
     * @param  Collection<int, Collection<int, Institution>>  $byParent
     * @param  array<int, int>  $ids
     */
    private function appendDescendantIds(Collection $byParent, int $parentId, array &$ids): void
    {
        foreach ($byParent->get($parentId, collect()) as $child) {
            $ids[] = $child->id;
            $this->appendDescendantIds($byParent, $child->id, $ids);
        }
    }
}
