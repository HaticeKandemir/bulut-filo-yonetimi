<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Vehicle $resource
 */
class VehicleMapPinResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $address = $this->resource->latestImportRow->startGeocodedAddress;

        return [
            'id' => $this->resource->id,
            'vin' => $this->resource->vin,
            'brand' => $this->resource->brand,
            'model' => $this->resource->model,
            'plate' => $this->resource->activePlate?->plate,
            'institution' => [
                'id' => $this->resource->institution->id,
                'name' => $this->resource->institution->name,
                'code' => $this->resource->institution->code,
            ],
            'start' => [
                'lat' => $address->latitude,
                'lng' => $address->longitude,
            ],
        ];
    }
}
