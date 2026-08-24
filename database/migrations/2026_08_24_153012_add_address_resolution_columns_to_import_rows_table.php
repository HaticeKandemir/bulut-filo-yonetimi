<?php

declare(strict_types=1);

use App\Enums\AddressResolutionStatus;
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
            $table->foreignId('start_geocoded_address_id')->nullable()->constrained('geocoded_addresses')->nullOnDelete();
            $table->foreignId('end_geocoded_address_id')->nullable()->constrained('geocoded_addresses')->nullOnDelete();
            $table->string('address_resolution_status')->default(AddressResolutionStatus::Pending->value);
            $table->text('address_resolution_error')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_rows', function (Blueprint $table) {
            $table->dropConstrainedForeignId('start_geocoded_address_id');
            $table->dropConstrainedForeignId('end_geocoded_address_id');
            $table->dropColumn(['address_resolution_status', 'address_resolution_error']);
        });
    }
};
