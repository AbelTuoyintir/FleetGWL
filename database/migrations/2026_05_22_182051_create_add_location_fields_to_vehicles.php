<?php
// database/migrations/2024_01_01_000004_add_location_fields_to_vehicles.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            if (!Schema::hasColumn('vehicles', 'region_id')) {
                $table->unsignedBigInteger('region_id')->nullable()->after('district_id');
                $table->foreign('region_id')->references('id')->on('regions')->onDelete('set null');
            }
            
            if (!Schema::hasColumn('vehicles', 'station_id')) {
                $table->unsignedBigInteger('station_id')->nullable()->after('region_id');
                $table->foreign('station_id')->references('id')->on('stations')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
            $table->dropForeign(['station_id']);
            $table->dropColumn(['region_id', 'station_id']);
        });
    }
};