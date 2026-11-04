<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::create('attempts',function(Blueprint $t){
$t->id();$t->foreignId('exam_id')->constrained()->cascadeOnDelete();$t->foreignId('user_id')->constrained()->cascadeOnDelete();$t->dateTime('started_at');$t->dateTime('submitted_at')->nullable();$t->decimal('score',7,2)->nullable();$t->boolean('is_released')->default(false);$t->boolean('token_verified')->default(false);$t->timestamps();$t->unique(['exam_id','user_id']);
});} public function down():void{Schema::dropIfExists('attempts');}};
