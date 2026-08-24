<?php

declare(strict_types=1);

use App\Enums\RouteComputationStatus;
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
        Schema::table('import_rows', function (Blueprint $table) {
            $table->foreignId('route_id')->nullable()->constrained('routes')->nullOnDelete();
            $table->string('route_computation_status')->default(RouteComputationStatus::Pending->value);
            $table->text('route_computation_error')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_rows', function (Blueprint $table) {
            $table->dropConstrainedForeignId('route_id');
            $table->dropColumn(['route_computation_status', 'route_computation_error']);
        });
    }
};
