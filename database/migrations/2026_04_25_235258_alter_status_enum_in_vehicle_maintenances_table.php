<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableNames = ['vehicle_maintenances', 'maintenances'];
        
        foreach ($tableNames as $tableName) {
            if (Schema::hasTable($tableName)) {
                // Add "waiting", "dispatched", and "deleted" to the ENUM values
                if (DB::getDriverName() !== 'sqlite') {
                    DB::statement("ALTER TABLE `{$tableName}` MODIFY COLUMN status ENUM('scheduled', 'waiting', 'dispatched', 'completed', 'cancelled', 'deleted') NOT NULL DEFAULT 'scheduled'");
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = ['vehicle_maintenances', 'maintenances'];
        
        foreach ($tableNames as $tableName) {
            if (Schema::hasTable($tableName)) {
                // Remove the additional values from the ENUM
                if (DB::getDriverName() !== 'sqlite') {
                    DB::statement("ALTER TABLE `{$tableName}` MODIFY COLUMN status ENUM('scheduled', 'completed', 'cancelled') NOT NULL DEFAULT 'scheduled'");
                }
            }
        }
    }
};
