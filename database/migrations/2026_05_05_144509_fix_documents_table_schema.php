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
        // For SQLite, the easiest way to fix corrupted schema/foreign keys is to recreate the table
        Schema::dropIfExists('documents');
        
        Schema::create('documents', function (Blueprint $table) {
            $table->id();

            // Basic info
            $table->string('title');
            $table->string('slug')->unique(); // For URLs
            $table->text('description')->nullable();

            // File info
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type'); // mime type
            $table->string('extension', 10);
            $table->unsignedInteger('file_size')->nullable();

            // Categorization
            $table->enum('document_type', [
                'insurance',
                'registration',
                'invoice',
                'receipt',
                'contract',
                'certificate',
                'license',
                'permit',
                'road_worthy',
                'manual',
                'report',
                'other'
            ])->default('other');

            // References
            $table->string('reference_number')->nullable();
            $table->unsignedBigInteger('vehicle_id')->nullable();

            // Dates
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->boolean('is_expired')->default(false);
            $table->date('reminder_date')->nullable();

            // Status & Visibility
            $table->enum('status', ['active', 'expired', 'archived', 'draft'])->default('active');
            $table->boolean('is_public')->default(false);
            $table->boolean('requires_acknowledgement')->default(false);
            $table->timestamp('acknowledged_at')->nullable();
            $table->unsignedBigInteger('acknowledged_by')->nullable();

            // Metadata
            $table->json('metadata')->nullable();
            $table->string('tags')->nullable();

            // Versioning
            $table->unsignedInteger('version')->default(1);
            $table->unsignedBigInteger('previous_version_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->nullOnDelete();
            $table->foreign('previous_version_id')->references('id')->on('documents')->nullOnDelete();
            $table->foreign('acknowledged_by')->references('id')->on('users')->nullOnDelete();

            // Indexes
            $table->index(['vehicle_id', 'status', 'expiry_date']);
            $table->index(['document_type', 'status']);
            $table->index('expiry_date');
            $table->index('reference_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
