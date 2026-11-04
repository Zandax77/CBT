<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::create('exams',function(Blueprint $t){
$t->id();$t->string('title');$t->string('subject');$t->string('class_name')->nullable()->index();$t->dateTime('starts_at');$t->dateTime('ends_at');$t->unsignedInteger('duration_minutes');$t->string('token',32);$t->boolean('is_published')->default(false);$t->boolean('results_released')->default(false);$t->foreignId('created_by')->constrained('users')->cascadeOnDelete();$t->timestamps();
});} public function down():void{Schema::dropIfExists('exams');}};
