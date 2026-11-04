<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PaymentProviderCheck extends Model
{
    protected $fillable = ['actor_id','provider','mode','result','country','charges_enabled','checked_at'];

    protected function casts(): array
    {
        return ['charges_enabled' => 'boolean', 'checked_at' => 'immutable_datetime'];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
