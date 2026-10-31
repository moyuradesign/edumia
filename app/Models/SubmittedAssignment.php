<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SubmittedAssignment extends Model
{
    protected $table = 'submitted_assignments';

    protected $fillable = [
        'assignment_id', 'user_id', 'response', 'score', 'feedback', 'graded_by',
        'submitted_at', 'graded_at',
    ];

    protected function casts(): array
    {
        return ['score' => 'decimal:2', 'submitted_at' => 'datetime', 'graded_at' => 'datetime'];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }
}
