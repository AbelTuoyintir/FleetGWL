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
        $tableNames = ['vehicle_maintenances', 'maintenances'];
        
        foreach ($tableNames as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (!Schema::hasColumn($tableName, 'other_maintenance_type')) {
                        $table->string('other_maintenance_type')->nullable()->after('checklist');
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
        $tableNames = ['vehicle_maintenances', 'maintenances'];
        
        foreach ($tableNames as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('other_maintenance_type');
                });
            }
        }
    }
};
