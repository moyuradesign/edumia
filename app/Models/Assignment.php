<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Assignment extends Model
{
    protected $fillable = ['course_id', 'lesson_id', 'title', 'instructions', 'due_at', 'max_score', 'published'];

    protected function casts(): array
    {
        return ['due_at' => 'immutable_datetime', 'published' => 'boolean', 'max_score' => 'integer'];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(SubmittedAssignment::class);
    }
}
