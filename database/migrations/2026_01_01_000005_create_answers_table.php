<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::create('answers',function(Blueprint $t){
$t->id();$t->foreignId('attempt_id')->constrained()->cascadeOnDelete();$t->foreignId('question_id')->constrained()->cascadeOnDelete();$t->string('answer_value',10)->nullable();$t->boolean('is_correct')->nullable();$t->decimal('earned_score',7,2)->default(0);$t->timestamps();$t->unique(['attempt_id','question_id']);
});} public function down():void{Schema::dropIfExists('answers');}};
