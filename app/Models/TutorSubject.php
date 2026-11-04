<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class TutorSubject extends Model
{
    protected $fillable = ['name', 'slug', 'category_id'];

    public function offerings(): HasMany
    {
        return $this->hasMany(TutorOffering::class, 'subject_id');
    }
}
