<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('fuel_logs', function (Blueprint $table) {
            $table->id();

            // Vehicle reference
            $table->unsignedBigInteger('vehicle_id');

            // Fuel information
            $table->date('date');
            $table->decimal('odometer', 10, 2);
            $table->decimal('previous_odometer', 10, 2)->nullable();
            $table->decimal('distance_traveled', 10, 2)->nullable();

            // Fuel details
            $table->decimal('fuel_quantity', 8, 3); // in liters/gallons
            $table->decimal('fuel_cost', 10, 2);
            $table->decimal('fuel_price_per_unit', 8, 3);
            $table->string('fuel_type')->default('petrol'); // petrol, diesel, electric, hybrid

            // Station information
            $table->string('fuel_station')->nullable();
            $table->string('location')->nullable();
            $table->string('receipt_number')->nullable();

            // Driver information
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->unsignedBigInteger('logged_by')->nullable();

            // Fuel efficiency calculations
            $table->decimal('fuel_efficiency', 8, 2)->nullable(); // km/l or mpg
            $table->decimal('cost_per_distance', 10, 2)->nullable();

            // Additional information
            $table->text('notes')->nullable();
            $table->boolean('is_full_tank')->default(true);
            $table->boolean('is_maintenance_fuel')->default(false);
            $table->string('payment_method')->nullable(); // cash, card, company_account,tomb card

            // Status
            $table->enum('status', ['recorded', 'verified', 'rejected'])->default('recorded');

            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('cascade');
            $table->foreign('driver_id')->nullable()->references('id')->on('users')->onDelete('set null');
            $table->foreign('logged_by')->nullable()->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index(['vehicle_id', 'date']);
            $table->index('date');
            $table->index('fuel_type');
            $table->index('status');
        });

        

        // Create fuel station favorites
        Schema::create('fuel_stations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('fuel_stations');
        Schema::dropIfExists('fuel_efficiency_records');
        Schema::dropIfExists('fuel_logs');
    }
};
