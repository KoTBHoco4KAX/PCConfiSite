<?php

declare(strict_types=1);

namespace App\Models;

use App\Notifications\VerifyEmailNotification;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmailContract
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use MustVerifyEmail;

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function savedBuilds(): HasMany
    {
        return $this->hasMany(SavedBuild::class);
    }

    public function hasRole(string $slug): bool
    {
        return $this->roles->contains(fn ($role) => $role->slug === $slug);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isSeller(): bool
    {
        return $this->hasRole('seller') || $this->isAdmin();
    }

    public function isBuyer(): bool
    {
        return $this->hasRole('buyer');
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification());
    }
}