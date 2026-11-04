<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TeamPackageMember extends Model
{
    protected $fillable = ['purchase_id', 'user_id', 'status', 'joined_at', 'accepted_at'];

    protected function casts(): array
    {
        return ['joined_at' => 'immutable_datetime', 'accepted_at' => 'immutable_datetime'];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(TeamPackagePurchase::class, 'purchase_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
