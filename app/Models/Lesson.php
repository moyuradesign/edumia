<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Lesson extends Model {
    protected $fillable=['course_id','section_id','title','slug','content','video_url','duration_seconds','sort_order','is_preview','is_published'];
    protected function casts(): array {return ['is_preview'=>'boolean','is_published'=>'boolean'];}
    public function course(): BelongsTo {return $this->belongsTo(Course::class);}
    public function section(): BelongsTo {return $this->belongsTo(Section::class);}
}
