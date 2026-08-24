<?php

declare(strict_types=1);

use App\Models\VehiclePlate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vehicle_plates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained();
            $table->string('plate', 20);
            $table->dateTime('assigned_at');
            $table->dateTime('released_at')->default(VehiclePlate::ACTIVE_SENTINEL);
            $table->timestamps();
            $table->unique(['plate', 'released_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_plates');
    }
};
