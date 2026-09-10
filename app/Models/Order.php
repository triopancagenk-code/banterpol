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
        'address',
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

    public function bill()
    {
        return $this->hasOne(Bill::class);
    }

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }
}
