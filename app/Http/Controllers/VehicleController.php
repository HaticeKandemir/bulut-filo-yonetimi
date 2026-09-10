<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\IndexVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Models\Vehicle;
use App\Repositories\VehicleRepository;
use App\Services\PlateTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class VehicleController extends Controller
{
    public function index(IndexVehicleRequest $request, VehicleRepository $vehicles): JsonResponse
    {
        return response()->json($vehicles->paginate($request, $request->integer('per_page', 15)));
    }

    public function show(Vehicle $vehicle, VehicleRepository $vehicles): JsonResponse
    {
        return response()->json($vehicles->loadForShow($vehicle));
    }

    public function mapPins(VehicleRepository $vehicles): JsonResponse
    {
        return response()->json($vehicles->forMap());
    }

    public function routes(IndexVehicleRequest $request, VehicleRepository $vehicles): JsonResponse
    {
        return response()->json($vehicles->forRoutes($request, $request->integer('per_page', 15)));
    }

    public function update(
        UpdateVehicleRequest $request,
        Vehicle $vehicle,
        PlateTransferService $plateTransfer,
        VehicleRepository $vehicles,
    ): JsonResponse {
        DB::transaction(function () use ($request, $vehicle, $plateTransfer): void {
            $vehicle->update($request->safe()->only(['brand', 'model', 'institution_id', 'status']));
            $plateTransfer->transferPlate($vehicle, $request->validated('plate'));
        });

        return response()->json($vehicles->loadForShow($vehicle));
    }
}
