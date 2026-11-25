<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$questions = \App\Models\Question::select('id', 'exam_id', 'position', 'type', 'answer_key')->get();
foreach ($questions as $q) {
    echo "ID: {$q->id} | Pos: {$q->position} | Type: {$q->type} | Key: " . json_encode($q->answer_key) . "\n";
}
