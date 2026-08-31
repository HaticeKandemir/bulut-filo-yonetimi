<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Vehicle;
use App\Models\VehiclePlate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Vehicle $resource
 */
class VehicleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'vin' => $this->resource->vin,
            'brand' => $this->resource->brand,
            'model' => $this->resource->model,
            'status' => $this->resource->status->value,
            'institution' => $this->whenLoaded(
                'institution',
                fn () => [
                    'id' => $this->resource->institution->id,
                    'name' => $this->resource->institution->name,
                    'code' => $this->resource->institution->code,
                ],
            ),
            'active_plate' => $this->whenLoaded(
                'activePlate',
                fn () => $this->resource->activePlate === null ? null : [
                    'plate' => $this->resource->activePlate->plate,
                    'assigned_at' => $this->resource->activePlate->assigned_at,
                ],
            ),
            'plate_history' => $this->whenLoaded(
                'plates',
                fn () => $this->resource->plates
                    ->sortByDesc('assigned_at')
                    ->values()
                    ->map(fn (VehiclePlate $plate) => [
                        'plate' => $plate->plate,
                        'assigned_at' => $plate->assigned_at,
                        'released_at' => $plate->released_at,
                        'is_active' => $plate->released_at->equalTo(VehiclePlate::ACTIVE_SENTINEL),
                    ]),
            ),
            'created_at' => $this->resource->created_at,
        ];
    }
}
