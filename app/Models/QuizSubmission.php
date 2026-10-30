<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class QuizSubmission extends Model {
    protected $fillable=['quiz_id','user_id','attempt_number','answers','score','passed','started_at','submitted_at'];
    protected function casts(): array {return ['answers'=>'array','score'=>'decimal:2','passed'=>'boolean','started_at'=>'datetime','submitted_at'=>'datetime'];}
    public function quiz(): BelongsTo {return $this->belongsTo(Quiz::class);}
    public function user(): BelongsTo {return $this->belongsTo(User::class);}
}
