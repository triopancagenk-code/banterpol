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
        'customer_nik',
        'customer_phone',
        'customer_address',
        'pppoe_username',
        'service_plan',
        'service_price',
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
        'service_price' => 'float',
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
        return 'Rp ' . number_format((float)($this->material_total_cost ?? 0), 0, ',', '.');
    }

    public function getFormattedServicePriceAttribute(): string
    {
        return 'Rp ' . number_format((float)($this->service_price ?? 0), 0, ',', '.');
    }

    public function getFormattedCashAttribute(): string
    {
        return 'Rp ' . number_format((float)($this->cash_amount ?? 0), 0, ',', '.');
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

    public function getMaterialsCountAttribute(): int
    {
        if (empty($this->materials_json) || !is_array($this->materials_json)) {
            return !empty($this->additional_materials) ? 1 : 0;
        }

        $count = 0;
        foreach ($this->materials_json as $item) {
            if ((float)($item['qty'] ?? 0) > 0) {
                $count++;
            }
        }
        if (!empty($this->additional_materials)) {
            $count++;
        }
        return $count;
    }

    public function getMaterialsShortSummaryAttribute(): string
    {
        if (empty($this->materials_json) || !is_array($this->materials_json)) {
            return !empty($this->additional_materials) ? $this->additional_materials : '-';
        }

        $items = [];
        foreach ($this->materials_json as $item) {
            $name = trim($item['name'] ?? '');
            $qty = (float)($item['qty'] ?? 0);
            $unit = trim($item['unit'] ?? '');
            if ($qty > 0 && !empty($name)) {
                $u = strtolower($unit) === 'meter' || strtolower($unit) === 'm' ? 'm' : (strtolower($unit) === 'unit' ? ' unit' : ' pcs');
                $items[] = "{$name} ({$qty}{$u})";
            }
        }

        if (!empty($this->additional_materials)) {
            $items[] = trim($this->additional_materials);
        }

        $totalItems = count($items);
        if ($totalItems === 0) {
            return '-';
        }

        if ($totalItems === 1) {
            return $items[0];
        }

        if ($totalItems === 2) {
            return $items[0] . ', ' . $items[1];
        }

        $sisa = $totalItems - 2;
        return $items[0] . ', ' . $items[1] . " (+{$sisa} lainnya)";
    }
}
