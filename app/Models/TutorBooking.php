<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TutorBooking extends Model
{
    protected $fillable = [
        'tutor_id', 'learner_id', 'subject_id', 'scheduled_at',
        'duration_minutes', 'fee', 'status', 'meeting_url',
    ];

    protected function casts(): array
    {
        return ['scheduled_at' => 'immutable_datetime', 'fee' => 'decimal:2', 'duration_minutes' => 'integer'];
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function learner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'learner_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(TutorSubject::class, 'subject_id');
    }
}
