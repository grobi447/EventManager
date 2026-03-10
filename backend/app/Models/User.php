<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'mfa_enabled',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'mfa_enabled'       => 'boolean',
        ];
    }

    // JWT required methods
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'email' => $this->email,
            'role'  => $this->role,
            'name'  => $this->name,
        ];
    }

    // Role helpers
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isAgent(): bool
    {
        return $this->role === 'helpdesk_agent' || $this->isAdmin();
    }

    // Relationships
    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function chats()
    {
        return $this->hasMany(Chat::class);
    }

    public function mfaSecret()
    {
        return $this->hasOne(MfaSecret::class);
    }
}