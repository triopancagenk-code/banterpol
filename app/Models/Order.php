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
        'material_used',
        'installed_at',
        'pppoe',
        'virtual_account',
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
     * Generate standard Virtual Account BRI (BRIVA) Banterpool (Kode BRIVA: 1699101)
     */
    public static function generateVirtualAccount(?string $phone = null, ?int $id = 0): string
    {
        $cleanPhone = preg_replace('/\D/', '', (string) $phone);
        if (str_starts_with($cleanPhone, '62')) {
            $cleanPhone = substr($cleanPhone, 2);
        } elseif (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = substr($cleanPhone, 1);
        }

        if (!empty($cleanPhone)) {
            return '1699101' . $cleanPhone;
        }

        $suffix = str_pad((string) ($id ?: mt_rand(10000000, 99999999)), 10, '0', STR_PAD_LEFT);
        return '1699101' . $suffix;
    }

    /**
     * Accessor untuk atribut virtual_account
     */
    public function getVirtualAccountAttribute(?string $value): string
    {
        if (!empty($value) && $value !== '-') {
            return $value;
        }

        return static::generateVirtualAccount($this->customer_phone, (int) $this->id);
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

    public function materialMutations()
    {
        return $this->hasMany(MaterialMutation::class);
    }

    /**
     * Scope query untuk Data Pelanggan.
     * - Akun ThreeFive: Menampilkan SEMUA data pelanggan.
     * - Akun Banterpool: Khusus hanya data pelanggan Banterpool.
     */
    public function scopeForCustomerData($query)
    {
        $query->where(function ($q) {
            $q->whereIn('status', ['Selesai', 'Selesai / Aktif', 'Aktif', 'selesai', 'aktif', 'Non-Aktif', 'non-aktif', 'Isolir', 'isolir'])
              ->orWhere(function ($sub) {
                  $sub->where('order_number', 'like', 'PLG-%')
                      ->whereNotIn('status', ['Menunggu Konfirmasi', 'Jadwal Teknisi', 'Sedang Dipasang', 'Kendala Lapangan', 'Dibatalkan']);
              });
        });

        // Filter khusus: jika admin yang sedang login adalah akun Banterpool, batasi hanya pelanggan Banterpool
        if (auth()->check() && auth()->user()->isBanterpoolAdmin()) {
            $query->where(function ($b) {
                $b->where('pppoe', 'like', '%banterpool%')
                  ->orWhere('order_number', 'like', 'PLG-%')
                  ->orWhere('package_name', 'like', '%banterpool%')
                  ->orWhere('admin_notes', 'like', '%banterpool%')
                  ->orWhere('customer_email', 'like', '%banterpool%');
            });
        }

        return $query;
    }

    /**
     * Memeriksa apakah order ini merupakan pelanggan Banterpool
     */
    public function isBanterpoolCustomer(): bool
    {
        $pppoe = strtolower((string) $this->pppoe);
        $orderNumber = strtoupper((string) $this->order_number);
        $packageName = strtolower((string) $this->package_name);
        $notes = strtolower((string) $this->admin_notes);
        $email = strtolower((string) $this->customer_email);

        return str_contains($pppoe, 'banterpool')
            || str_starts_with($orderNumber, 'PLG-')
            || str_contains($packageName, 'banterpool')
            || str_contains($notes, 'banterpool')
            || str_contains($email, 'banterpool');
    }

    /**
     * Cek apakah pesanan ini sudah berstatus selesai dan masuk data pelanggan
     */
    public function isCompletedCustomer(): bool
    {
        $st = strtolower(trim((string) $this->status));
        if (in_array($st, ['selesai', 'selesai / aktif', 'aktif', 'non-aktif', 'isolir'])) {
            return true;
        }

        if (str_starts_with((string) $this->order_number, 'PLG-')) {
            return !in_array($this->status, ['Menunggu Konfirmasi', 'Jadwal Teknisi', 'Sedang Dipasang', 'Kendala Lapangan', 'Dibatalkan']);
        }

        return false;
    }
}
