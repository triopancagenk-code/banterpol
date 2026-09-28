<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
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
        return $this->role === 'technician' || $this->role === 'teknisi' || $this->isAdmin();
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
