<?php

declare(strict_types=1);

use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleImportController;
use Illuminate\Support\Facades\Route;

Route::post('vehicle-imports', [VehicleImportController::class, 'store']);
Route::get('vehicle-imports/{importBatch}', [VehicleImportController::class, 'show']);
Route::get('vehicle-imports/{importBatch}/rows', [VehicleImportController::class, 'rows']);

Route::get('vehicles', [VehicleController::class, 'index']);
Route::get('vehicles/{vehicle}', [VehicleController::class, 'show']);

Route::get('institutions', [InstitutionController::class, 'index']);
