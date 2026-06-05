
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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('registration_number')->unique();
            $table->string('make');
            $table->string('model');
            $table->integer('year');
            $table->string('color')->nullable();
            $table->string('chassis_number')->unique();
            $table->string('engine_number')->unique()->nullable();
            $table->integer('mileage')->nullable();
            $table->date('registration_date')->nullable();
            $table->date('insurance_expiry_date')->nullable();
            $table->date('next_inspection_date')->nullable();
            $table->decimal('fuel_consumption', 8, 2)->nullable();
            $table->string('vehicle_type');
            $table->enum('status', ['active', 'inactive', 'maintenance', 'disposed', 'deleted'])->default('active');
            $table->text('notes')->nullable();
            
            
            // Relationships
            $table->foreignId('region_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('district_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('assigned_driver_id')->nullable()->constrained('drivers')->onDelete('set null');
            
            // Financial
            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->date('purchase_date')->nullable();

            // Audit columns
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
