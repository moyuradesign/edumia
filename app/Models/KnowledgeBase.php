<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class KnowledgeBase extends Model
{
    protected $fillable = ['title', 'slug', 'content', 'category', 'published'];

    protected function casts(): array
    {
        return ['published' => 'boolean'];
    }

    public function topics(): HasMany
    {
        return $this->hasMany(KnowledgeBaseTopic::class)->orderBy('sort_order')->orderBy('id');
    }
}
