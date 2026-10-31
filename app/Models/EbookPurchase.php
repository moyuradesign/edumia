<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class EbookPurchase extends Model
{
    protected $fillable = ['ebook_id', 'user_id', 'amount', 'currency_code', 'payment_reference', 'status', 'purchased_at'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'purchased_at' => 'immutable_datetime'];
    }

    public function ebook(): BelongsTo
    {
        return $this->belongsTo(Ebook::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
