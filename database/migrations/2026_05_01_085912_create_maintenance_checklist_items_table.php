<?php
// database/migrations/2024_01_01_000002_create_maintenance_checklist_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->string('category');
            $table->string('item_name');
            $table->string('item_code')->unique();
            $table->text('description')->nullable();
            $table->decimal('estimated_hours', 5, 2)->nullable();
            $table->decimal('default_cost', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_checklist_items');
    }
};