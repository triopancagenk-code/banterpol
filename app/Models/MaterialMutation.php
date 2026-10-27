<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialMutation extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_id',
        'user_id',
        'order_id',
        'type', // 'in', 'out', 'adjustment'
        'transaction_date',
        'amount',
        'stock_before',
        'stock_after',
        'notes',
    ];

    protected $casts = [
        'amount' => 'float',
        'stock_before' => 'float',
        'stock_after' => 'float',
        'transaction_date' => 'date',
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'in' => 'Barang Masuk',
            'out' => 'Barang Keluar',
            'adjustment' => 'Penyesuaian Manual',
            default => ucfirst($this->type),
        };
    }

    public function getFormattedAmountAttribute(): string
    {
        return Material::formatQty($this->amount);
    }
}
