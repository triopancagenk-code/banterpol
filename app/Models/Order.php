<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'customer_name',
        'customer_phone',
        'customer_email',
        'id_card_number',
        'birth_place',
        'birth_date',
        'address',
        'village',
        'latitude',
        'longitude',
        'package_name',
        'speed',
        'price',
        'installation_fee',
        'tax',
        'total',
        'installation_date',
        'installation_time',
        'payment_method',
        'payment_status',
        'status',
        'technician',
        'technician_id',
        'assigned_at',
        'assigned_odp',
        'admin_notes',
        'ont_sn',
        'opm_dbm',
        'technician_notes',
        'installed_at',
    ];

    protected $casts = [
        'price' => 'float',
        'installation_fee' => 'float',
        'tax' => 'float',
        'total' => 'float',
        'installation_date' => 'date',
        'birth_date' => 'date',
        'assigned_at' => 'datetime',
        'installed_at' => 'datetime',
    ];

    /**
     * Helper formatting rupiah
     */
    public function getFormattedTotalAttribute(): string
    {
        return 'Rp' . number_format($this->total, 0, ',', '.');
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'Rp' . number_format($this->price, 0, ',', '.');
    }

    public function technicianUser()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function scopeForTechnician($query, $user)
    {
        if (!$user) {
            return $query;
        }

        $userName = $user->name;
        $firstName = explode(' ', trim($userName))[0] ?? '';

        return $query->where(function ($q) use ($user, $userName, $firstName) {
            $q->where('technician_id', $user->id)
              ->orWhere('technician', $userName)
              ->orWhere('technician', 'like', "%{$userName}%");
            if (!empty($firstName) && strlen($firstName) >= 3) {
                $q->orWhere('technician', 'like', "%{$firstName}%");
            }
        });
    }

    public function bill()
    {
        return $this->hasOne(Bill::class);
    }

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }
}
