<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class TeamPackagePurchase extends Model
{
    protected $fillable = [
        'package_id', 'organization_name', 'purchaser_id',
        'seats_purchased', 'amount', 'status', 'purchased_at',
    ];

    protected function casts(): array
    {
        return ['purchased_at' => 'immutable_datetime', 'seats_purchased' => 'integer', 'amount' => 'decimal:2'];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(TeamTrainingPackage::class, 'package_id');
    }

    public function purchaser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'purchaser_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(TeamPackageMember::class, 'purchase_id');
    }
}
