<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;
    protected $fillable = ['name', 'email', 'password', 'locale'];
    protected $hidden = ['password', 'remember_token', 'google_id'];
    protected function casts(): array { return ['email_verified_at'=>'datetime', 'password'=>'hashed']; }
    public function courses(): HasMany { return $this->hasMany(Course::class, 'instructor_id'); }
    public function enrollments(): HasMany { return $this->hasMany(Enrollment::class); }
    public function certificates(): HasMany { return $this->hasMany(Certificate::class); }
    public function isActive(): bool { return $this->status === 'active'; }
    public function isAdmin(): bool { return $this->role === 'admin' && $this->isActive(); }
    public function canTeach(): bool { return in_array($this->role, ['admin','instructor'], true) && $this->isActive(); }
}
