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
        $tableName = Schema::hasTable('vehicle_maintenances')
            ? 'vehicle_maintenances'
            : (Schema::hasTable('maintenances') ? 'maintenances' : null);

        if ($tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'checklist')) {
                    $table->json('checklist')->nullable()->after('status');
                }
            });

            // Convert any existing 'in_progress' to 'dispatched' before removing the enum value
            \Illuminate\Support\Facades\DB::table($tableName)->where('status', 'in_progress')->update(['status' => 'scheduled']);
            
            // Remove in_progress, add waiting and dispatched
            if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') {
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE {$tableName} MODIFY COLUMN status ENUM('scheduled', 'completed', 'cancelled', 'deleted', 'waiting', 'dispatched') NOT NULL DEFAULT 'waiting'");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = Schema::hasTable('vehicle_maintenances')
            ? 'vehicle_maintenances'
            : (Schema::hasTable('maintenances') ? 'maintenances' : null);

        if (!$tableName) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            if (Schema::hasColumn($tableName, 'checklist')) {
                $table->dropColumn('checklist');
            }
        });
        
        \Illuminate\Support\Facades\DB::table($tableName)->whereIn('status', ['waiting', 'dispatched'])->update(['status' => 'scheduled']);
        
        if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE {$tableName} MODIFY COLUMN status ENUM('scheduled', 'in_progress', 'completed', 'cancelled', 'deleted') NOT NULL DEFAULT 'scheduled'");
        }
    }
};
