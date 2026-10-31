<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Ebook extends Model
{
    protected $fillable = ['category_id', 'author_id', 'title', 'slug', 'description', 'file_path', 'cover_path', 'price', 'status', 'published_at'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'published_at' => 'immutable_datetime'];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(EbookPurchase::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function isFree(): bool
    {
        return (float) $this->price === 0.0;
    }
}
