<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class BootcampResource extends Model
{
    protected $fillable = ['module_id', 'title', 'external_url', 'sort_order'];

    public function module(): BelongsTo
    {
        return $this->belongsTo(BootcampModule::class, 'module_id');
    }
}
