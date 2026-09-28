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

    /**
     * Cek apakah pesanan terkait tagihan ini sudah berstatus 'Selesai' / 'Selesai / Aktif'.
     * Tagihan pada monitoring tagihan HANYA muncul jika pesanan pemasangannya sudah selesai.
     */
    public function isOrderCompleted(): bool
    {
        if (!$this->order_id) {
            return true;
        }

        $order = $this->order ?: Order::find($this->order_id);
        if (!$order) {
            return true;
        }

        $st = strtolower(trim((string) $order->status));
        return in_array($st, ['selesai', 'selesai / aktif', 'selesai/aktif', 'aktif']);
    }

    /**
     * Scope query untuk tagihan yang siap tampil di Monitoring Tagihan (status order selesai atau tagihan manual tanpa order)
     */
    public function scopeActiveForMonitoring($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('order_id')
              ->orWhereHas('order', function ($oq) {
                  $oq->whereIn('status', ['Selesai', 'Selesai / Aktif', 'Selesai/Aktif', 'selesai', 'aktif', 'Aktif']);
              });
        });
    }
}
