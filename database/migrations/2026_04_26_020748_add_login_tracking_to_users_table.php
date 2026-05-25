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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'last_login_ip')) {
                $table->string('last_login_ip')->nullable();
            }
            if (!Schema::hasColumn('users', 'login_status')) {
                $table->string('login_status')->default('active'); // active, inactive, suspended
            }
            if (!Schema::hasColumn('users', 'login_count')) {
                $table->integer('login_count')->default(0);
            }
            if (!Schema::hasColumn('users', 'status')) {
                $table->string('status')->default('active');
            }
            if (!Schema::hasColumn('users', 'trusted_devices')) {
                $table->json('trusted_devices')->nullable();
            }
            if (!Schema::hasColumn('users', 'trusted_device_types')) {
                $table->json('trusted_device_types')->nullable();
            }
            if (!Schema::hasColumn('users', 'last_login_device')) {
                $table->string('last_login_device')->nullable();
            }
            if (!Schema::hasColumn('users', 'last_login_location')) {
                $table->string('last_login_location')->nullable();
            }
            if (!Schema::hasColumn('users', 'last_login_user_agent')) {
                $table->string('last_login_user_agent')->nullable();
            }
            if (!Schema::hasColumn('users', 'last_security_notification_at')) {
                $table->timestamp('last_security_notification_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'last_risk_level')) {
                $table->string('last_risk_level')->nullable();
            }
            if (!Schema::hasColumn('users', 'last_notification_context')) {
                $table->json('last_notification_context')->nullable();
            }
            if (!Schema::hasColumn('users', 'last_security_score')) {
                $table->integer('last_security_score')->nullable(); // active, inactive, suspended
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
