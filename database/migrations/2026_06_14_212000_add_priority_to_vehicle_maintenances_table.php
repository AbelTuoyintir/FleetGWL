<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_maintenances', function (Blueprint $table) {
            if (!Schema::hasColumn('vehicle_maintenances', 'priority')) {
                $table->string('priority')->nullable()->after('status')->default('medium');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_maintenances', function (Blueprint $table) {
            if (Schema::hasColumn('vehicle_maintenances', 'priority')) {
                $table->dropColumn('priority');
            }
        });
    }
};
