<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ForumReply extends Model
{
    protected $fillable = ['forum_id', 'user_id', 'body', 'status'];

    public function forum(): BelongsTo
    {
        return $this->belongsTo(Forum::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
