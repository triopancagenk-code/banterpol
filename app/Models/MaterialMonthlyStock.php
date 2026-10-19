<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialMonthlyStock extends Model
{
    use HasFactory;

    protected $table = 'material_monthly_stocks';

    protected $fillable = [
        'material_id',
        'month',
        'month_name',
        'year',
        'initial_stock',
        'total_in',
        'total_out',
        'final_stock',
    ];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'initial_stock' => 'float',
        'total_in' => 'float',
        'total_out' => 'float',
        'final_stock' => 'float',
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
