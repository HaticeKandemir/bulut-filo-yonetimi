<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ImportBatchStatus;
use App\Http\Requests\StoreVehicleImportRequest;
use App\Http\Resources\ImportBatchResource;
use App\Http\Resources\ImportRowResource;
use App\Jobs\ProcessVehicleImportJob;
use App\Models\ImportBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VehicleImportController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $batches = ImportBatch::query()
            ->when(
                $request->query('status'),
                fn ($query, $status) => $query->where('status', $status),
            )
            ->orderByDesc('created_at')
            ->paginate();

        return ImportBatchResource::collection($batches);
    }

    public function store(StoreVehicleImportRequest $request): JsonResponse
    {
        $file = $request->file('file');

        $batch = ImportBatch::create([
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $file->store('imports', 'local'),
            'status' => ImportBatchStatus::Pending,
        ]);

        ProcessVehicleImportJob::dispatch($batch);

        return (new ImportBatchResource($batch))->response()->setStatusCode(202);
    }

    public function show(ImportBatch $importBatch): ImportBatchResource
    {
        return new ImportBatchResource($importBatch);
    }

    public function rows(Request $request, ImportBatch $importBatch): AnonymousResourceCollection
    {
        $rows = $importBatch->rows()
            ->with(['conflictingVehicle', 'startGeocodedAddress', 'endGeocodedAddress', 'route'])
            ->when(
                $request->query('status'),
                fn ($query, $status) => $query->where('status', $status),
            )
            ->orderBy('row_number')
            ->paginate();

        return ImportRowResource::collection($rows);
    }
}
