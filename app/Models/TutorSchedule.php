<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TutorSchedule extends Model
{
    protected $fillable = ['tutor_id', 'weekday', 'start_time', 'end_time', 'timezone', 'active'];

    protected function casts(): array
    {
        return ['weekday' => 'integer', 'active' => 'boolean'];
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }
}
