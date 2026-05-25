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
        $tables = [
            'users',
            'regions',
            'districts',  
            'departments',
            'drivers',
            'vehicles',
            'documents',
            'fuel_logs',
            'mileage_logs',
            'notifications',
            'maintenance_records', 
            
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (!Schema::hasColumn($tableName, 'status')) {
                        $table->string('status')->default('active')->after('id');
                    }
                    if (!Schema::hasColumn($tableName, 'deleted_by')) {
                        $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'users',
            'regions',
            'districts',
            'offices',
            'departments',
            'categories',
            'assets',
            'maintenances',
            'maintenance_history',
            'vendors',
            'repairs',
            'drivers',
            'vehicles',
            'vehicle_submissions',
            'documents',
            'vehicle_maintenances',
            'fuel_logs',
            'mileage_logs',
            'notifications',
            'maintenance_records',
            'insurance_records',
            'fuel_stations'
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (Schema::hasColumn($tableName, 'deleted_by')) {
                        $table->dropForeign([$tableName . '_deleted_by_foreign']);
                        $table->dropColumn('deleted_by');
                    }
                    // We might not want to drop 'status' if it existed before, 
                    // but for a fresh project we can be a bit more aggressive.
                    // However, it's safer to only drop what we added.
                });
            }
        }
    }
};
