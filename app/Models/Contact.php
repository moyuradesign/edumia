<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Contact extends Model
{
    protected $fillable = ['name', 'email', 'subject', 'message'];

    protected function casts(): array
    {
        return ['responded_at' => 'datetime'];
    }
}
