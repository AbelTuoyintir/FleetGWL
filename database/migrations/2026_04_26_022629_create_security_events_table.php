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
        Schema::create('security_events', function (Blueprint $table) {
            $table->id();
            $table->string('user_identifier'); // email or user_id
            $table->string('event_type'); // successful_login, failed_login, logout, password_change
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('location')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['user_identifier', 'event_type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_events');
    }
};
