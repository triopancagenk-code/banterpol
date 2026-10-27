<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'google_id',
        'phone',
        'role',
        'is_active',
        'avatar',
        'password',
        'password_set_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password_set_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Dapatkan URL foto profil yang valid (mendukung Google avatar URL maupun local storage upload).
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if (empty($this->avatar)) {
            return null;
        }

        if (Str::startsWith($this->avatar, ['http://', 'https://'])) {
            return $this->avatar;
        }

        return asset('storage/' . $this->avatar);
    }

    /**
     * Memeriksa apakah pengguna sudah pernah mengatur kata sandi sendiri.
     * Jika pengguna mendaftar melalui Google dan belum pernah membuat kata sandi, bernilai false.
     */
    public function hasSetPassword(): bool
    {
        if (empty($this->google_id)) {
            return true;
        }

        return !is_null($this->password_set_at);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin' || $this->role === 'direktur' || $this->email === 'admin' || $this->email === 'direktur' || $this->email === 'direktur@banterpool.net';
    }

    public function isDirektur(): bool
    {
        return $this->role === 'direktur' || $this->email === 'direktur' || $this->email === 'direktur@banterpool.net';
    }

    public function isTechnician(): bool
    {
        return $this->role === 'technician' || $this->role === 'teknisi' || $this->isAdmin() || strtolower($this->email) === 'admindaily' || strtolower($this->email) === 'admindaily@banterpool.net';
    }

    public function isCollector(): bool
    {
        return $this->role === 'collector' || $this->role === 'kolektor' || $this->isAdmin();
    }

    public function assignedOrders()
    {
        return $this->hasMany(Order::class, 'technician_id');
    }
}
