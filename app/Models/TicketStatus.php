<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TicketStatus extends Model
{
    protected $table = 'ticket_status';

    protected $fillable = ['name', 'slug', 'is_closed'];

    protected function casts(): array
    {
        return ['is_closed' => 'boolean'];
    }
}
