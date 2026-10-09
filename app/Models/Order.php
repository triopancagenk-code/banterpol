<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_number',
        'customer_name',
        'customer_phone',
        'customer_email',
        'id_card_number',
        'birth_place',
        'birth_date',
        'address',
        'village',
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
        'technician_id',
        'assigned_at',
        'assigned_odp',
        'admin_notes',
        'ont_sn',
        'opm_dbm',
        'technician_notes',
        'installed_at',
        'pppoe',
    ];

    protected $casts = [
        'price' => 'float',
        'installation_fee' => 'float',
        'tax' => 'float',
        'total' => 'float',
        'installation_date' => 'date',
        'birth_date' => 'date',
        'assigned_at' => 'datetime',
        'installed_at' => 'datetime',
    ];

    /**
     * Generate standard PPPoE username dari nama pelanggan
     */
    public static function generatePppoeUsername(?string $name, ?string $orderNumber = null, int $id = 0): string
    {
        $clean = trim((string) $name);
        $clean = preg_replace('/\s*[\(\/\-].*$/', '', $clean);
        $clean = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $clean));

        if (empty($clean)) {
            if (!empty($orderNumber)) {
                $clean = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $orderNumber));
            } else {
                $clean = 'user' . ($id ?: rand(100, 999));
            }
        }

        return $clean;
    }

    /**
     * Accessor untuk atribut pppoe
     */
    public function getPppoeAttribute(?string $value): string
    {
        if (!empty($value)) {
            return $value;
        }

        return static::generatePppoeUsername($this->customer_name, $this->order_number, (int) $this->id);
    }

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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function technicianUser()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function scopeForTechnician($query, $user)
    {
        if (!$user) {
            return $query;
        }

        $userName = $user->name;
        $firstName = explode(' ', trim($userName))[0] ?? '';

        return $query->where(function ($q) use ($user, $userName, $firstName) {
            $q->where('technician_id', $user->id)
              ->orWhere('technician', $userName)
              ->orWhere('technician', 'like', "%{$userName}%");
            if (!empty($firstName) && strlen($firstName) >= 3) {
                $q->orWhere('technician', 'like', "%{$firstName}%");
            }
        });
    }

    public function bill()
    {
        return $this->hasOne(Bill::class);
    }

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }

    /**
     * Scope query untuk Data Pelanggan.
     * Alur bisnis:
     * - Pesanan baru masuk ke Monitoring Pesanan.
     * - Ketika status pesanan belum 'Selesai', pesanan BELUM masuk ke Data Pelanggan.
     * - Ketika status pesanan sudah 'Selesai', pesanan MASUK ke Data Pelanggan.
     */
    public function scopeForCustomerData($query)
    {
        return $query->where(function ($q) {
            $q->whereIn('status', ['Selesai', 'Selesai / Aktif', 'Aktif', 'selesai', 'aktif'])
              ->orWhere(function ($sub) {
                  $sub->where('order_number', 'like', 'PLG-%')
                      ->whereNotIn('status', ['Menunggu Konfirmasi', 'Jadwal Teknisi', 'Sedang Dipasang', 'Kendala Lapangan', 'Dibatalkan']);
              });
        });
    }

    /**
     * Cek apakah pesanan ini sudah berstatus selesai dan masuk data pelanggan
     */
    public function isCompletedCustomer(): bool
    {
        $st = strtolower(trim((string) $this->status));
        if (in_array($st, ['selesai', 'selesai / aktif', 'aktif'])) {
            return true;
        }

        if (str_starts_with((string) $this->order_number, 'PLG-')) {
            return !in_array($this->status, ['Menunggu Konfirmasi', 'Jadwal Teknisi', 'Sedang Dipasang', 'Kendala Lapangan', 'Dibatalkan']);
        }

        return false;
    }
}
