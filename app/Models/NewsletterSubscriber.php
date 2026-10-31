<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class NewsletterSubscriber extends Model
{
    protected $fillable = ['email', 'name', 'verified_at', 'unsubscribed_at'];

    protected function casts(): array
    {
        return ['verified_at' => 'immutable_datetime', 'unsubscribed_at' => 'immutable_datetime'];
    }

    public function isSubscribed(): bool
    {
        return $this->verified_at !== null && $this->unsubscribed_at === null;
    }
}
