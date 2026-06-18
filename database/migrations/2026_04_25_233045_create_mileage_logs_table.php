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
        Schema::create('mileage_logs', function (Blueprint $table) {
            $table->id();

            // FK definitions made explicit to avoid MySQL errno 150 (incorrectly formed FK)
            $table->unsignedBigInteger('vehicle_id');
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('cascade');

            $table->unsignedBigInteger('driver_id')->nullable();
            $table->foreign('driver_id')->references('id')->on('drivers')->onDelete('cascade');
            $table->date('date')->nullable();
            $table->integer('start_mileage')->nullable();
            $table->integer('end_mileage')->nullable();
            $table->text('notes')->nullable();
            $table->string('recorded_by')->nullable();
            $table->boolean('service_alert')->default(false);
            $table->timestamps();
        });
    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mileage_logs');
    }
};
