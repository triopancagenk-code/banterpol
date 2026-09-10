<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipient_name',
        'recipient_phone',
        'message_type',
        'message',
        'status',
        'batch_id',
        'target_filter',
        'sent_by',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    /**
     * Get direct WhatsApp chat URL
     */
    public function getWhatsappUrlAttribute(): string
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', $this->recipient_phone);
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '62' . substr($cleanPhone, 1);
        }
        return 'https://wa.me/' . $cleanPhone . '?text=' . urlencode($this->message);
    }
}
