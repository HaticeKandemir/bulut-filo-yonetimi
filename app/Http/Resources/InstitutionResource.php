<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Institution $resource
 */
class InstitutionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'code' => $this->resource->code,
            'children' => InstitutionResource::collection($this->whenLoaded('children')),
        ];
    }
}
