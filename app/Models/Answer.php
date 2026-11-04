<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Answer extends Model {
    protected $fillable=['attempt_id','question_id','answer_value','is_correct','earned_score'];
    protected function casts(): array { return ['is_correct'=>'boolean','earned_score'=>'decimal:2']; }
}
