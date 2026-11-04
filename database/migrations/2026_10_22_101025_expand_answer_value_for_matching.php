<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            // Perlebar answer_value agar bisa menampung jawaban menjodohkan
            // Format: "A:1,B:3,C:2" (premise:jawaban)
            $table->string('answer_value', 500)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            $table->string('answer_value', 10)->nullable()->change();
        });
    }
};
