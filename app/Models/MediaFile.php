<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MediaFile extends Model
{
    protected $fillable = [
        'user_id', 'disk', 'path', 'original_name',
        'mime_type', 'size_bytes', 'alt_text', 'visibility',
    ];

    protected function casts(): array
    {
        return ['size_bytes' => 'integer'];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
