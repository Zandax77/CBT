<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Question extends Model {
    protected $fillable = [
        'exam_id', 'position', 'type', 'question_text',
        'image_path', 'options', 'answer_key', 'weight',
    ];

    protected function casts(): array {
        return ['options' => 'array', 'weight' => 'decimal:2'];
    }

    public function exam() { return $this->belongsTo(Exam::class); }
}
