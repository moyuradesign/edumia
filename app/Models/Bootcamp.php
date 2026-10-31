<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Bootcamp extends Model
{
    protected $fillable = [
        'instructor_id', 'category_id', 'title', 'slug', 'description',
        'price', 'start_date', 'end_date', 'capacity', 'status',
    ];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'start_date' => 'date', 'end_date' => 'date', 'capacity' => 'integer'];
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function modules(): HasMany
    {
        return $this->hasMany(BootcampModule::class)->orderBy('sort_order')->orderBy('id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(BootcampLiveClass::class)->orderBy('starts_at');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(BootcampPurchase::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function isFree(): bool
    {
        return (float) $this->price === 0.0;
    }
}
