<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::create('questions',function(Blueprint $t){
$t->id();$t->foreignId('exam_id')->constrained()->cascadeOnDelete();$t->unsignedInteger('position');$t->string('type',20);$t->text('question_text');$t->json('options');$t->string('answer_key',10);$t->decimal('weight',7,2)->default(1);$t->timestamps();$t->unique(['exam_id','position']);
});} public function down():void{Schema::dropIfExists('questions');}};
