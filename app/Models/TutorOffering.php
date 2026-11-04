<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TutorOffering extends Model
{
    protected $table = 'tutor_can_teach';

    protected $fillable = ['tutor_id', 'subject_id', 'rate_per_hour', 'currency_code'];

    protected function casts(): array
    {
        return ['rate_per_hour' => 'decimal:2'];
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(TutorSubject::class, 'subject_id');
    }
}
