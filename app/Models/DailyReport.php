<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_date',
        'technician_name',
        'technician_id',
        'partner_company',
        'job_type',
        'customer_name',
        'customer_phone',
        'customer_address',
        'pppoe_username',
        'odp_port',
        'rx_power',
        'ont_sn',
        'materials_json',
        'additional_materials',
        'material_total_cost',
        'transaction_type',
        'cash_amount',
        'payment_method',
        'cash_deposit_status',
        'order_id',
        'synced_to_sheets',
        'synced_at',
        'notes',
    ];

    protected $casts = [
        'report_date' => 'date',
        'materials_json' => 'array',
        'material_total_cost' => 'float',
        'cash_amount' => 'float',
        'synced_to_sheets' => 'boolean',
        'synced_at' => 'datetime',
    ];

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function getFormattedMaterialCostAttribute(): string
    {
        return 'Rp ' . number_format($this->material_total_cost, 0, ',', '.');
    }

    public function getFormattedCashAttribute(): string
    {
        return 'Rp ' . number_format($this->cash_amount, 0, ',', '.');
    }

    public function getMaterialsSummaryTextAttribute(): string
    {
        if (empty($this->materials_json) || !is_array($this->materials_json)) {
            return $this->additional_materials ?: '-';
        }

        $parts = [];
        foreach ($this->materials_json as $item) {
            $name = $item['name'] ?? '';
            $qty = $item['qty'] ?? 0;
            $unit = $item['unit'] ?? '';
            $total = (float)($item['total'] ?? 0);
            if ($qty > 0) {
                $parts[] = "{$name} ({$qty} {$unit} - Rp " . number_format($total, 0, ',', '.') . ")";
            }
        }

        if (!empty($this->additional_materials)) {
            $parts[] = $this->additional_materials;
        }

        return implode(', ', $parts);
    }
}
