<?php
// database/migrations/xxxx_xx_xx_000000_create_login_logs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoginLogsTable extends Migration
{
    public function up()
    {
        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->boolean('success')->default(true);
            $table->timestamps();
            
            $table->index('email');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('login_logs');
    }
}