<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_settings', function (Blueprint $table) {
            $table->id();
            $table->string('school_name')->default('CBT Sekolah');
            $table->string('school_address')->nullable();
            $table->string('school_logo')->nullable(); // path ke file logo
            $table->timestamps();
        });

        // Seed baris pertama & satu-satunya
        \DB::table('school_settings')->insert([
            'school_name'    => 'CBT Sekolah',
            'school_address' => null,
            'school_logo'    => null,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('school_settings');
    }
};
