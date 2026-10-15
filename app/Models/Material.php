<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Material extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'category',
        'unit',
        'stock',
        'min_stock',
        'unit_price',
        'location',
        'description',
    ];

    protected $casts = [
        'stock' => 'integer',
        'min_stock' => 'integer',
        'unit_price' => 'float',
    ];

    public function mutations(): HasMany
    {
        return $this->hasMany(MaterialMutation::class)->latest();
    }

    public function isLowStock(): bool
    {
        return $this->stock <= $this->min_stock && $this->stock > 0;
    }

    public function isOutOfStock(): bool
    {
        return $this->stock <= 0;
    }

    public function isSafeStock(): bool
    {
        return $this->stock > $this->min_stock;
    }

    /**
     * Tambah stok manual
     */
    public function addStock(int $amount, ?string $notes = null, ?int $userId = null): MaterialMutation
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Jumlah penambahan stok harus lebih besar dari 0.');
        }

        $stockBefore = $this->stock;
        $stockAfter = $stockBefore + $amount;

        $this->update(['stock' => $stockAfter]);

        return $this->mutations()->create([
            'user_id' => $userId,
            'type' => 'in',
            'amount' => $amount,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'notes' => $notes ?: 'Penambahan stok manual',
        ]);
    }

    /**
     * Kurang stok manual
     */
    public function reduceStock(int $amount, ?string $notes = null, ?int $userId = null): MaterialMutation
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Jumlah pengurangan stok harus lebih besar dari 0.');
        }

        if ($amount > $this->stock) {
            throw new \InvalidArgumentException("Stok tidak mencukupi. Stok saat ini: {$this->stock} {$this->unit}, pengurangan yang diminta: {$amount} {$this->unit}.");
        }

        $stockBefore = $this->stock;
        $stockAfter = $stockBefore - $amount;

        $this->update(['stock' => $stockAfter]);

        return $this->mutations()->create([
            'user_id' => $userId,
            'type' => 'out',
            'amount' => $amount,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'notes' => $notes ?: 'Pengurangan stok manual',
        ]);
    }

    /**
     * Penyesuaian stok opname manual
     */
    public function adjustStock(int $newStock, ?string $notes = null, ?int $userId = null): MaterialMutation
    {
        if ($newStock < 0) {
            throw new \InvalidArgumentException('Jumlah stok baru tidak boleh negatif.');
        }

        $stockBefore = $this->stock;
        $diff = $newStock - $stockBefore;
        $type = $diff >= 0 ? 'in' : 'out';

        $this->update(['stock' => $newStock]);

        return $this->mutations()->create([
            'user_id' => $userId,
            'type' => 'adjustment',
            'amount' => abs($diff),
            'stock_before' => $stockBefore,
            'stock_after' => $newStock,
            'notes' => $notes ?: ('Penyesuaian stok opname (' . ($diff >= 0 ? "+{$diff}" : "{$diff}") . ')'),
        ]);
    }

    public function getTotalAssetValueAttribute(): float
    {
        return (float) ($this->stock * ($this->unit_price ?? 0));
    }
}
