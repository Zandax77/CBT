<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            // Durasi rotasi token dalam menit (null = tidak rotasi otomatis)
            $table->unsignedSmallInteger('token_rotate_minutes')->nullable()->after('token');
            // Waktu terakhir token dirotasi
            $table->timestamp('token_last_rotated_at')->nullable()->after('token_rotate_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn(['token_rotate_minutes', 'token_last_rotated_at']);
        });
    }
};
