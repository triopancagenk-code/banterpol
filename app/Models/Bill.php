<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    use HasFactory;

    protected $fillable = [
        'bill_number',
        'order_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'address',
        'package_name',
        'speed',
        'period',
        'due_date',
        'bill_date',
        'amount',
        'tax',
        'total',
        'status',
        'payment_method',
        'paid_at',
        'collected_by',
        'collector_notes',
        'receipt_number',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
