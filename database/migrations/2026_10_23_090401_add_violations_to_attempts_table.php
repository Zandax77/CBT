<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('attempts', function (Blueprint $table) {
            // Jumlah pelanggaran (ganti tab, keluar fullscreen, dst)
            $table->unsignedTinyInteger('violations')->default(0)->after('token_verified');
            // Apakah dikumpulkan otomatis karena pelanggaran berlebih
            $table->boolean('force_submitted')->default(false)->after('violations');
        });
    }
    public function down(): void {
        Schema::table('attempts', function (Blueprint $table) {
            $table->dropColumn(['violations', 'force_submitted']);
        });
    }
};
