<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$exam = App\Models\Exam::find(8);
view()->share('errors', new \Illuminate\Support\ViewErrorBag());

// 1. New question
$qNew = new App\Models\Question();
echo "Testing new question: " . strlen(view('questions.form', ['exam' => $exam, 'question' => $qNew])->render()) . " bytes OK\n";

// 2. All questions in exam 8
foreach ($exam->questions as $q) {
    echo "Question {$q->id} ({$q->type}): " . strlen(view('questions.form', ['exam' => $exam, 'question' => $q])->render()) . " bytes OK\n";
}
