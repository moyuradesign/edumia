<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class BootcampPurchase extends Model
{
    protected $fillable = ['bootcamp_id', 'user_id', 'amount', 'currency_code', 'payment_reference', 'status'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function bootcamp(): BelongsTo
    {
        return $this->belongsTo(Bootcamp::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
