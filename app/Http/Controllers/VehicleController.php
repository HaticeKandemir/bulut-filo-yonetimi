<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\IndexVehicleRequest;
use App\Models\Vehicle;
use App\Repositories\VehicleRepository;
use Illuminate\Http\JsonResponse;

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
}
