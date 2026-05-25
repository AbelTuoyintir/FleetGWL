<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_maintenances', function (Blueprint $table) {
            $table->id();

            // References
            $table->unsignedBigInteger('vehicle_id');
            $table->unsignedBigInteger('driver_id')->nullable();

            // Maintenance details
            $table->string('maintenance_type')->nullable();
            $table->date('maintenance_date')->nullable();
            $table->integer('mileage_at_service')->nullable();
            $table->text('description')->nullable();
            $table->text('parts_replaced')->nullable();
            $table->json('checklist')->nullable();
            $table->date('date')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->string('service_provider')->nullable();
            $table->integer('next_expected_mileage')->nullable();
           
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled', 'waiting', 'dispatched', 'deleted'])->default('scheduled');
            $table->text('attachments')->nullable();

            // Audit columns
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('vehicle_id')
                  ->references('id')
                  ->on('vehicles')
                  ->onDelete('cascade');

            $table->foreign('driver_id')
                  ->references('id')
                  ->on('drivers')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_maintenances');
    }
};
