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
        Schema::table('vehicles', function (Blueprint $table) {
            $table->decimal('speed', 6, 2)->nullable()->after('last_seen_at')->comment('Current GPS speed in km/h');
            $table->integer('heading')->nullable()->after('speed')->comment('Current GPS heading in degrees (0-360)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['speed', 'heading']);
        });
    }
};
