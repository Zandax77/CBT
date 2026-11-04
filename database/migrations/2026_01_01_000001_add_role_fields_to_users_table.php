<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::table('users', function(Blueprint $table){
    $table->string('role')->default('siswa')->index();
    $table->string('student_number')->nullable()->unique();
    $table->string('class_name')->nullable()->index();
 });}
 public function down(): void { Schema::table('users', function(Blueprint $table){
    $table->dropUnique(['student_number']); $table->dropIndex(['role']); $table->dropIndex(['class_name']);
    $table->dropColumn(['role','student_number','class_name']);
 });}
};
