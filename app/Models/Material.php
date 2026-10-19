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
        'month',
        'year',
        'initial_stock',
        'total_in',
        'total_out',
        'stock',
        'min_stock',
        'unit_price',
        'location',
        'description',
    ];

    protected $casts = [
        'year' => 'integer',
        'initial_stock' => 'float',
        'total_in' => 'float',
        'total_out' => 'float',
        'stock' => 'float',
        'min_stock' => 'float',
        'unit_price' => 'float',
    ];

    public function mutations(): HasMany
    {
        return $this->hasMany(MaterialMutation::class)->latest();
    }

    public function monthlyStocks(): HasMany
    {
        return $this->hasMany(MaterialMonthlyStock::class);
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
     * Tambah stok manual / Barang Masuk
     */
    public function addStock(float $amount, ?string $notes = null, ?int $userId = null, ?string $transactionDate = null): MaterialMutation
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Jumlah penambahan stok harus lebih besar dari 0.');
        }

        $stockBefore = $this->stock;
        $stockAfter = $stockBefore + $amount;
        $totalIn = ($this->total_in ?? 0) + $amount;

        $this->update([
            'stock' => $stockAfter,
            'total_in' => $totalIn,
        ]);

        return $this->mutations()->create([
            'user_id' => $userId,
            'type' => 'in',
            'transaction_date' => $transactionDate ?: now()->toDateString(),
            'amount' => $amount,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'notes' => $notes ?: 'Barang masuk / Penambahan stok',
        ]);
    }

    /**
     * Kurang stok manual / Barang Keluar
     */
    public function reduceStock(float $amount, ?string $notes = null, ?int $userId = null, ?string $transactionDate = null, bool $allowNegative = true): MaterialMutation
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Jumlah pengurangan stok harus lebih besar dari 0.');
        }

        if (!$allowNegative && $amount > $this->stock) {
            throw new \InvalidArgumentException("Stok tidak mencukupi. Stok saat ini: {$this->stock} {$this->unit}, pengurangan yang diminta: {$amount} {$this->unit}.");
        }

        $stockBefore = $this->stock;
        $stockAfter = $stockBefore - $amount;
        $totalOut = ($this->total_out ?? 0) + $amount;

        $this->update([
            'stock' => $stockAfter,
            'total_out' => $totalOut,
        ]);

        return $this->mutations()->create([
            'user_id' => $userId,
            'type' => 'out',
            'transaction_date' => $transactionDate ?: now()->toDateString(),
            'amount' => $amount,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'notes' => $notes ?: 'Barang keluar / Pemakaian lapangan',
        ]);
    }

    /**
     * Penyesuaian stok opname manual
     */
    public function adjustStock(float $newStock, ?string $notes = null, ?int $userId = null): MaterialMutation
    {
        $stockBefore = $this->stock;
        $diff = $newStock - $stockBefore;
        $type = $diff >= 0 ? 'in' : 'out';

        $this->update([
            'stock' => $newStock,
        ]);

        return $this->mutations()->create([
            'user_id' => $userId,
            'type' => 'adjustment',
            'transaction_date' => now()->toDateString(),
            'amount' => abs($diff),
            'stock_before' => $stockBefore,
            'stock_after' => $newStock,
            'notes' => $notes ?: ('Penyesuaian stok opname (' . ($diff >= 0 ? "+{$diff}" : "{$diff}") . ')'),
        ]);
    }

    public function getTotalAssetValueAttribute(): float
    {
        return (float) (max(0, $this->stock) * ($this->unit_price ?? 0));
    }

    public static function formatQty(float|int|null $qty): string
    {
        if ($qty === null) {
            return '0';
        }
        return (float) $qty == (int) $qty
            ? number_format($qty, 0, ',', '.')
            : number_format($qty, 1, ',', '.');
    }

    public function getFormattedStockAttribute(): string
    {
        return self::formatQty($this->stock);
    }

    public function getFormattedInitialStockAttribute(): string
    {
        return self::formatQty($this->initial_stock);
    }

    public function getFormattedTotalInAttribute(): string
    {
        return self::formatQty($this->total_in);
    }

    public function getFormattedTotalOutAttribute(): string
    {
        return self::formatQty($this->total_out);
    }
}
