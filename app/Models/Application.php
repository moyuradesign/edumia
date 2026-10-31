<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Application extends Model
{
    protected $fillable = ['user_id', 'type', 'details', 'status', 'reviewed_by', 'reviewed_at'];

    protected function casts(): array
    {
        return ['details' => 'array', 'reviewed_at' => 'immutable_datetime'];
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
