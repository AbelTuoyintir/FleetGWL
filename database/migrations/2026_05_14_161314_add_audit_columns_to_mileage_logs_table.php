<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**\n     * Run the migrations.\n     */
    public function up(): void
    {
        Schema::table('mileage_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->after('service_alert');
            $table->unsignedBigInteger('modified_by')->nullable()->after('created_by');
        });
    }

    /**\n     * Reverse the migrations.\n     */
    public function down(): void
    {
        Schema::table('mileage_logs', function (Blueprint $table) {
            // Drop constraints first, then columns
            if (Schema::hasColumn('mileage_logs', 'created_by')) {
                $table->dropForeign(['mileage_logs_created_by_foreign']);
                $table->dropColumn('created_by');
            }
            if (Schema::hasColumn('mileage_logs', 'modified_by')) {
                $table->dropForeign(['mileage_logs_modified_by_foreign']);
                $table->dropColumn('modified_by');
            }
        });
    }
};