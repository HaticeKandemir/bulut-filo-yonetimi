<?php

declare(strict_types=1);

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
        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('start_geocoded_address_id')->constrained('geocoded_addresses');
            $table->foreignId('end_geocoded_address_id')->constrained('geocoded_addresses');
            $table->unsignedInteger('distance_meters');
            $table->unsignedInteger('duration_seconds');
            $table->text('polyline');
            $table->timestamps();

            $table->unique(['start_geocoded_address_id', 'end_geocoded_address_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
};
