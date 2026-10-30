<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Certificate extends Model {
    protected $fillable=['course_id','user_id','certificate_number','verification_token','issued_at','revoked_at'];
    protected function casts(): array {return ['issued_at'=>'datetime','revoked_at'=>'datetime'];}
    public function course(): BelongsTo {return $this->belongsTo(Course::class);}
    public function user(): BelongsTo {return $this->belongsTo(User::class);}
}
