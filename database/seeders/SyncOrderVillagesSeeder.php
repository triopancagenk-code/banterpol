<?php

namespace Database\Seeders;

use App\Models\Order;
use Illuminate\Database\Seeder;

class SyncOrderVillagesSeeder extends Seeder
{
    /**
     * Sinkronisasi nama desa ke kolom 'village' tabel orders berdasarkan alamat yang ada.
     */
    public function run(): void
    {
        $orders = Order::all();
        foreach ($orders as $order) {
            // Perbaiki jika ada alamat yang salah tertulis 'Desa Template Pelanggan'
            if (str_contains($order->address, 'Template Pelanggan')) {
                if (str_contains(strtolower($order->customer_name), 'umiyati')) {
                    $order->address = 'Desa Batuanten';
                    $order->village = 'Batuanten';
                } elseif (str_contains(strtolower($order->customer_name), 'narti')) {
                    $order->address = 'Desa Sawangan';
                    $order->village = 'Sawangan';
                } else {
                    $order->address = '-';
                    $order->village = null;
                }
                $order->save();
                continue;
            }

            $village = self::detectVillage($order->address, $order->admin_notes);
            if (!empty($village)) {
                $order->village = $village;
                $order->save();
            }
        }
    }

    /**
     * Deteksi nama desa standar dari string alamat/catatan.
     */
    public static function detectVillage(?string $address, ?string $notes = null): ?string
    {
        $addr = strtolower(trim((string)$address));
        $nt = strtolower(trim((string)$notes));

        if (empty($addr) || $addr === '-') {
            // Coba cek catatan admin jika alamat kosong
            if (!empty($nt)) {
                return self::detectVillage($nt, '');
            }
            return null;
        }

        // 1. Prioritas khusus jika mengandung nama desa spesifik
        if (str_contains($addr, 'linggasari') || str_contains($nt, 'linggasari')) {
            return 'Linggasari';
        }
        if (str_contains($addr, 'bantarwuni') || str_contains($nt, 'bantarwuni') || str_contains($addr, 'glempang')) {
            return 'Bantarwuni';
        }
        if (str_contains($addr, 'kasegeran') || str_contains($nt, 'kasegeran')) {
            return 'Kasegeran';
        }
        if (str_contains($addr, 'cipete') || str_contains($nt, 'cipete')) {
            return 'Cipete';
        }
        if (str_contains($addr, 'pageraji') || str_contains($nt, 'pageraji')) {
            return 'Pageraji';
        }
        if (str_contains($addr, 'sudimara') || str_contains($nt, 'sudimara')) {
            return 'Sudimara';
        }
        if (str_contains($addr, 'notog') || str_contains($nt, 'notog')) {
            return 'Notog';
        }
        if (str_contains($addr, 'sawangan') || str_contains($nt, 'sawangan')) {
            return 'Sawangan';
        }
        if (str_contains($addr, 'jatisaba') || str_contains($nt, 'jatisaba')) {
            return 'Jatisaba';
        }
        if (str_contains($addr, 'batuanten') || str_contains($addr, 'bantuanten') || str_contains($nt, 'batuanten')) {
            return 'Batuanten';
        }
        if (
            str_contains($addr, 'karangendep') || str_contains($addr, 'karanggendep') || 
            str_contains($addr, 'karang endep') || str_contains($addr, 'karang endp') ||
            str_contains($addr, 'krangendep') || str_contains($addr, 'karang ednep') ||
            str_contains($addr, 'ronten') || str_contains($nt, 'karangendep')
        ) {
            return 'Karangendep';
        }
        if (
            str_contains($addr, 'penusupan') || str_contains($addr, 'panusupan') ||
            str_contains($addr, 'pecikalan') || str_contains($addr, 'tinggar jaya') ||
            str_contains($addr, 'bojongsari') || str_contains($addr, 'legok') ||
            str_contains($nt, 'penusupan')
        ) {
            return 'Penusupan';
        }

        // 2. Deteksi umum jika alamat diawali dengan "Desa [Nama]" atau "[Nama] RT..."
        if (preg_match('/(?:desa|kelurahan|kel\.?)\s+([A-Za-z0-9\s]+?)(?:,|\.|\/|\s+(?:rt|rw|\d)|$)/i', $address, $m)) {
            $candidate = trim($m[1]);
            if (!empty($candidate) && strlen($candidate) >= 3) {
                return ucwords(strtolower($candidate));
            }
        }

        // 3. Potong kata pertama sebelum koma / tanda baca
        $parts = preg_split('/[,.\/]/', $address);
        if (!empty($parts[0])) {
            $first = trim(preg_replace('/^(?:desa|kelurahan|kel\.?)\s+/i', '', $parts[0]));
            if (strlen($first) >= 3 && !in_array(strtolower($first), ['rt', 'rw', 'jl', 'jalan', 'kec', 'kecamatan'])) {
                return ucwords(strtolower($first));
            }
        }

        return null;
    }
}
