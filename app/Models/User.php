<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'bio'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $attributes = [
        'role' => 'member',
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function accessTokens()
    {
        return $this->hasMany(MemberAccessToken::class);
    }

    public function socialIdentities()
    {
        return $this->hasMany(SocialIdentity::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin' && $this->is_active;
    }

    public function isMember(): bool
    {
        // Administrator accounts inherit member access, including the mobile API.
        // Web administration still requires isAdmin() and an admin browser session.
        return $this->is_active && in_array($this->role, ['member', 'admin'], true);
    }

    public function publicProfile(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'bio' => $this->bio,
            'avatar_url' => $this->avatar_path ? asset('storage/'.$this->avatar_path) : null,
            'email_verified' => $this->email_verified_at !== null,
            'role' => $this->role,
            'auth_provider' => $this->socialIdentities()->value('provider') ?? 'email',
        ];
    }
}
