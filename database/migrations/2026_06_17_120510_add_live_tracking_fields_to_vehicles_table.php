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
            $table->decimal('current_latitude', 10, 8)->nullable()->after('notes');
            $table->decimal('current_longitude', 11, 8)->nullable()->after('current_latitude');
            $table->timestamp('last_seen_at')->nullable()->after('current_longitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['current_latitude', 'current_longitude', 'last_seen_at']);
        });
    }
};
