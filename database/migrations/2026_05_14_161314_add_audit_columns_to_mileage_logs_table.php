<?php

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
        Schema::table('mileage_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->after('service_alert');
            $table->unsignedBigInteger('modified_by')->nullable()->after('created_by');
           
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mileage_logs', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'modified_by', 'deleted_by']);
        });
    }
};
