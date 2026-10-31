<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    protected $fillable = ['course_id', 'lesson_id', 'title', 'instructions', 'passing_score', 'time_limit_minutes', 'attempts_allowed', 'is_published'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean', 'passing_score' => 'integer', 'attempts_allowed' => 'integer'];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('sort_order')->orderBy('id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(QuizSubmission::class);
    }
}
