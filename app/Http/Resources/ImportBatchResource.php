<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ImportBatch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property ImportBatch $resource
 */
class ImportBatchResource extends JsonResource
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
            'original_filename' => $this->resource->original_filename,
            'status' => $this->resource->status->value,
            'created_at' => $this->resource->created_at,
        ];
    }
}
