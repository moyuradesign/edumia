<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class TeamTrainingPackage extends Model
{
    protected $fillable = ['course_id', 'name', 'description', 'seats', 'price', 'status'];

    protected function casts(): array
    {
        return ['seats' => 'integer', 'price' => 'decimal:2'];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(TeamPackagePurchase::class, 'package_id');
    }
}
