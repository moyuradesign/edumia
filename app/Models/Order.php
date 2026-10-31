<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Order extends Model
{
    protected $fillable = [
        'user_id',
        'reference',
        'status',
        'currency_code',
        'subtotal',
        'total',
        'expires_at',
        'paid_at',
        'payment_gateway',
        'gateway_session_id',
        'gateway_checkout_url',
        'gateway_payment_reference',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
            'expires_at' => 'immutable_datetime',
            'paid_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
