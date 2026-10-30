<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Question extends Model {
    protected $fillable=['quiz_id','question','type','options','correct_answer','points','sort_order'];
    protected function casts(): array {return ['options'=>'array','correct_answer'=>'array','points'=>'integer'];}
    public function quiz(): BelongsTo {return $this->belongsTo(Quiz::class);}
}
