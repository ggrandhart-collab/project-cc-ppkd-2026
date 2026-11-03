<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLES = ['creator', 'editor', 'admin'];

    // "role" sengaja tidak mass-assignable (mencegah privilege escalation lewat form register).
    protected $fillable = ['name', 'email', 'password_hash'];

    protected $hidden = ['password_hash', 'remember_token'];

    protected function casts(): array
    {
        return ['password_hash' => 'hashed'];
    }

    /** Kolom password di tabel ini bernama password_hash (sesuai spesifikasi). */
    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isEditor(): bool
    {
        return $this->role === 'editor';
    }

    public function isCreator(): bool
    {
        return $this->role === 'creator';
    }

    public function channels(): HasMany
    {
        return $this->hasMany(Channel::class);
    }

    public function contents(): HasMany
    {
        return $this->hasMany(Content::class);
    }
}
