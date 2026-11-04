<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            // Target kelompok peserta: umum, tingkat, jurusan, kelas
            $table->string('target_type', 30)->default('umum')->after('subject');
            // Tingkat/kelompok kelas (misal: 10, 11, 12 atau X, XI, XII)
            $table->string('target_level', 20)->nullable()->after('target_type');
            // Jurusan/program keahlian (misal: RPL, TKJ, IPA, IPS)
            $table->string('target_major', 60)->nullable()->after('target_level');

            // Masa aktif token (dalam menit)
            $table->unsignedSmallInteger('token_valid_minutes')->nullable()->after('token_rotate_minutes');
            // Waktu kedaluwarsa token
            $table->timestamp('token_expires_at')->nullable()->after('token_valid_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn([
                'target_type',
                'target_level',
                'target_major',
                'token_valid_minutes',
                'token_expires_at',
            ]);
        });
    }
};
