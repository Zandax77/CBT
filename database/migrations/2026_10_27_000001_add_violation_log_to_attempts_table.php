<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('attempts', function (Blueprint $table) {
            // Log detail setiap pelanggaran: [{at, reason}, ...]
            $table->json('violation_log')->nullable()->after('force_submitted');
        });
    }
    public function down(): void {
        Schema::table('attempts', function (Blueprint $table) {
            $table->dropColumn('violation_log');
        });
    }
};
