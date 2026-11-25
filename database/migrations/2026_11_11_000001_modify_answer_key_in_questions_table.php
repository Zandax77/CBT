<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add a temporary JSON column.
        Schema::table('questions', function (Blueprint $table) {
            $table->json('answer_key_tmp')->nullable();
        });

        // 2. Migrate existing data: wrap the old string value into a JSON array.
        DB::table('questions')
            ->whereNotNull('answer_key')
            ->update([
                'answer_key_tmp' => DB::raw('JSON_ARRAY(answer_key)')
            ]);

        // 3. Drop the old column and rename the new one.
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('answer_key');
        });
        Schema::table('questions', function (Blueprint $table) {
            $table->renameColumn('answer_key_tmp', 'answer_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Add a temporary string column.
        Schema::table('questions', function (Blueprint $table) {
            $table->string('answer_key_tmp', 10)->nullable();
        });

        // 2. Convert JSON array back to a single string (take first element).
        DB::table('questions')
            ->whereNotNull('answer_key')
            ->update([
                'answer_key_tmp' => DB::raw("JSON_UNQUOTE(JSON_EXTRACT(answer_key, '$[0]'))")
            ]);

        // 3. Drop the JSON column and rename the temp string column.
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('answer_key');
        });
        Schema::table('questions', function (Blueprint $table) {
            $table->renameColumn('answer_key_tmp', 'answer_key');
        });
    }
};