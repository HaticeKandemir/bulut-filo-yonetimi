<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Vehicle $resource
 */
class VehicleRouteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $row = $this->resource->latestImportRow;
        $route = $row->route;

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
                'lat' => $row->startGeocodedAddress->latitude,
                'lng' => $row->startGeocodedAddress->longitude,
            ],
            'end' => [
                'lat' => $row->endGeocodedAddress->latitude,
                'lng' => $row->endGeocodedAddress->longitude,
            ],
            'route' => [
                'distance_meters' => $route->distance_meters,
                'duration_seconds' => $route->duration_seconds,
                'polyline' => $route->polyline,
            ],
        ];
    }
}
