<?php

declare(strict_types=1);

use App\Http\Controllers\VehicleImportController;
use Illuminate\Support\Facades\Route;

Route::post('vehicle-imports', [VehicleImportController::class, 'store']);
Route::get('vehicle-imports/{importBatch}', [VehicleImportController::class, 'show']);
Route::get('vehicle-imports/{importBatch}/rows', [VehicleImportController::class, 'rows']);
