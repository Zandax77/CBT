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
        Schema::table('exams', function (Blueprint $table) {
            $table->boolean('randomize_by_type')->default(false)->after('results_released');
        });

        Schema::table('attempts', function (Blueprint $table) {
            $table->json('question_order')->nullable()->after('violation_log');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn('randomize_by_type');
        });

        Schema::table('attempts', function (Blueprint $table) {
            $table->dropColumn('question_order');
        });
    }
};
