<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ImportRow;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property ImportRow $resource
 */
class ImportRowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'row_number' => $this->resource->row_number,
            'vin' => $this->resource->raw_data['sasi_no'] ?? null,
            'plate' => $this->resource->raw_data['plaka'] ?? null,
            'brand' => $this->resource->raw_data['marka'] ?? null,
            'model' => $this->resource->raw_data['model'] ?? null,
            'institution_code' => $this->resource->raw_data['kurum_kodu'] ?? null,
            'start_address' => $this->resource->raw_data['baslangic_adresi'] ?? null,
            'end_address' => $this->resource->raw_data['bitis_adresi'] ?? null,
            'status' => $this->resource->status->value,
            'error_message' => $this->resource->error_message,
            'vehicle_id' => $this->resource->vehicle_id,
            'conflicting_vehicle_vin' => $this->whenLoaded(
                'conflictingVehicle',
                fn ($conflictingVehicle) => $conflictingVehicle->vin,
            ),
            'address_resolution_status' => $this->resource->address_resolution_status->value,
            'address_resolution_error' => $this->resource->address_resolution_error,
            'start_coordinates' => $this->whenLoaded(
                'startGeocodedAddress',
                fn ($address) => ['lat' => $address->latitude, 'lng' => $address->longitude],
            ),
            'end_coordinates' => $this->whenLoaded(
                'endGeocodedAddress',
                fn ($address) => ['lat' => $address->latitude, 'lng' => $address->longitude],
            ),
            'route_computation_status' => $this->resource->route_computation_status->value,
            'route_computation_error' => $this->resource->route_computation_error,
            'route' => $this->whenLoaded(
                'route',
                fn ($route) => $route === null ? null : [
                    'distance_meters' => $route->distance_meters,
                    'duration_seconds' => $route->duration_seconds,
                    'polyline' => $route->polyline,
                ],
            ),
        ];
    }
}
