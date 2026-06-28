<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_chats', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('session_id')->nullable()->after('user_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('support_chats', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
            $table->dropColumn('session_id');
        });
    }
};
