<?php
// database/migrations/2024_01_01_000000_add_maintenance_alert_fields_to_vehicles.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->integer('last_maintenance_mileage')->nullable()->after('mileage');
            $table->date('maintenance_due_date')->nullable()->after('last_maintenance_mileage');
            $table->timestamp('maintenance_alert_sent_at')->nullable()->after('maintenance_due_date');
        });

        // Create maintenance alerts table
        Schema::create('maintenance_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_id');
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->integer('current_mileage');
            $table->integer('mileage_since_maintenance');
            $table->string('alert_type')->default('mileage_exceeded');
            $table->string('status')->default('pending'); // pending, sent, acknowledged, resolved
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('cascade');
            $table->foreign('driver_id')->references('id')->on('drivers')->onDelete('set null');
            $table->index(['vehicle_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('maintenance_alerts');
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['last_maintenance_mileage', 'maintenance_due_date', 'maintenance_alert_sent_at']);
        });
    }
};