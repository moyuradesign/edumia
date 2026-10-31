<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class BootcampModule extends Model
{
    protected $fillable = ['bootcamp_id', 'title', 'description', 'sort_order'];

    public function bootcamp(): BelongsTo
    {
        return $this->belongsTo(Bootcamp::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(BootcampResource::class, 'module_id')->orderBy('sort_order');
    }
}
