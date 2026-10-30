<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Course extends Model {
    protected $fillable=['instructor_id','category_id','title','slug','summary','description','language_code','level','price','currency_code','status','visibility','published_at'];
    protected function casts(): array {return ['price'=>'decimal:2','published_at'=>'datetime'];}
    public function instructor(): BelongsTo {return $this->belongsTo(User::class,'instructor_id');}
    public function category(): BelongsTo {return $this->belongsTo(Category::class);}
    public function sections(): HasMany {return $this->hasMany(Section::class)->orderBy('sort_order')->orderBy('id');}
    public function lessons(): HasMany {return $this->hasMany(Lesson::class)->orderBy('sort_order')->orderBy('id');}
    public function enrollments(): HasMany {return $this->hasMany(Enrollment::class);}
    public function quizzes(): HasMany {return $this->hasMany(Quiz::class);}
    public function certificates(): HasMany {return $this->hasMany(Certificate::class);}
    public function scopePublished(Builder $query): Builder {return $query->where('status','published')->where('visibility','public')->whereNotNull('published_at');}
    public function isFree(): bool {return (float)$this->price === 0.0;}
}
