<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Attempt extends Model {
    protected $fillable=['exam_id','user_id','started_at','submitted_at','score','is_released','token_verified','violations','force_submitted','violation_log'];
    protected function casts(): array { return ['started_at'=>'datetime','submitted_at'=>'datetime','score'=>'decimal:2','is_released'=>'boolean','token_verified'=>'boolean','force_submitted'=>'boolean','violation_log'=>'array']; }
    public function exam(){ return $this->belongsTo(Exam::class); }
    public function user(){ return $this->belongsTo(User::class); }
    public function answers(){ return $this->hasMany(Answer::class); }
}
