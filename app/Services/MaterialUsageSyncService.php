<?php

namespace App\Services;

use App\Models\Material;
use App\Models\MaterialMonthlyStock;
use App\Models\MaterialMutation;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MaterialUsageSyncService
{
    /**
     * Sinkronkan penggunaan material dari tiket teknisi dengan mutasi Barang Keluar (Admin POV)
     */
    public static function syncOrderMaterials(Order $order, ?string $materialUsedString, $materialItems = null, ?int $userId = null): void
    {
        // 1. Revert mutasi lama untuk order ini agar stok tidak berkurang ganda jika diedit
        self::revertOrderMutations($order);

        // 2. Normalisasi daftar item material
        $items = self::normalizeItems($materialUsedString, $materialItems);
        if (empty($items)) {
            return;
        }

        $technicianName = $order->technician ?: (auth()->user()?->name ?: 'Teknisi');
        $txDate = $order->installation_date ? $order->installation_date->toDateString() : now()->toDateString();
        $finalUserId = $userId ?: (auth()->id() ?: $order->technician_id);

        // 3. Untuk setiap material, cari di master data material dan kurangi stok (Barang Keluar)
        foreach ($items as $item) {
            $type = trim($item['type'] ?? '');
            $qtyStr = trim($item['qty'] ?? '');

            if (empty($type)) {
                continue;
            }

            $amount = self::parseQuantity($qtyStr, $type);
            if ($amount <= 0) {
                continue;
            }

            $material = self::resolveMaterial($type, $qtyStr);
            if (!$material) {
                continue;
            }

            $notes = "Pemasangan {$order->order_number} - {$order->customer_name} (Teknisi: {$technicianName})";

            try {
                $mutation = $material->reduceStock($amount, $notes, $finalUserId, $txDate);
                $mutation->order_id = $order->id;
                $mutation->save();

                // Perbarui snapshot bulanan jika ada
                self::updateMonthlyStock($material->id, $amount, $txDate);
            } catch (\Throwable $e) {
                Log::warning("Gagal mengurangi stok material {$material->name} untuk order {$order->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Kembalikan stok dari mutasi order sebelumnya
     */
    public static function revertOrderMutations(Order $order): void
    {
        $existingMutations = MaterialMutation::where('order_id', $order->id)
            ->where('type', 'out')
            ->get();

        foreach ($existingMutations as $mut) {
            $material = $mut->material;
            if ($material) {
                $material->stock = (float)$material->stock + (float)$mut->amount;
                $material->total_out = max(0, (float)$material->total_out - (float)$mut->amount);
                $material->save();

                if ($mut->transaction_date) {
                    try {
                        $txCarbon = Carbon::parse($mut->transaction_date);
                        $mStock = MaterialMonthlyStock::where('material_id', $material->id)
                            ->where('month', $txCarbon->month)
                            ->where('year', $txCarbon->year)
                            ->first();
                        if ($mStock) {
                            $mStock->total_out = max(0, (float)$mStock->total_out - (float)$mut->amount);
                            $mStock->final_stock = (float)$mStock->initial_stock + (float)$mStock->total_in - (float)$mStock->total_out;
                            $mStock->save();
                        }
                    } catch (\Throwable $e) {}
                }
            }
            $mut->delete();
        }
    }

    /**
     * Helper resolve material master dari jenis input teknisi
     */
    public static function resolveMaterial(string $type, string $qtyStr = ''): ?Material
    {
        $lowerType = strtolower(trim($type));
        $lowerQty = strtolower(trim($qtyStr));

        // 0. Cek apakah type cocok langsung dengan Category di master material
        $catMatches = Material::where('category', $type)->get();
        if ($catMatches->isNotEmpty()) {
            if (!empty($lowerQty)) {
                $subMatch = $catMatches->first(function ($m) use ($lowerQty) {
                    $mName = strtolower($m->name);
                    return strlen($mName) >= 3 && str_contains($lowerQty, $mName);
                });
                if ($subMatch) {
                    return $subMatch;
                }
            }
            $withStock = $catMatches->first(fn($m) => (float)$m->stock > 0);
            return $withStock ?: $catMatches->first();
        }

        // 1. Drop core per meter
        if (str_contains($lowerType, 'drop') || str_contains($lowerType, 'drop core') || str_contains($lowerType, 'dc')) {
            $mat = Material::where('code', 'A044')->first(); // DC 1C
            if (!$mat) {
                $mat = Material::where('name', 'like', '%DC 1C%')->first();
            }
            if (!$mat) {
                $mat = Material::where('name', 'like', '%DC %')->orWhere('name', 'like', '%Drop Core%')->first();
            }
            return $mat;
        }

        // 2. Smurf (Protection sleeve / selongsong fiber optik)
        if (str_contains($lowerType, 'smurf') || str_contains($lowerType, 'smuft')) {
            $mat = Material::where('code', 'A103')->first(); // Smuft Kecil
            if (!$mat) {
                $mat = Material::where('name', 'like', '%Smuft Kecil%')->first();
            }
            if (!$mat) {
                $mat = Material::where('name', 'like', '%Smuft%')->first();
            }
            return $mat;
        }

        // 3. Modem (ONT)
        if (str_contains($lowerType, 'modem')) {
            $mat = Material::where('code', 'A096')->first(); // Modem Zte F670L
            if (!$mat || $mat->stock <= 0) {
                $available = Material::where('category', 'like', '%Modem%')
                    ->where('stock', '>', 0)
                    ->first();
                if ($available) return $available;
            }
            return $mat ?: Material::where('name', 'like', '%Modem%')->first();
        }

        // 4. Adaptor
        if (str_contains($lowerType, 'adaptor')) {
            $mat = Material::where('code', 'A094')->first(); // Adaptor 12v
            if (!$mat) {
                $mat = Material::where('name', 'like', '%Adaptor 12v%')->first();
            }
            if (!$mat) {
                $mat = Material::where('name', 'like', '%Adaptor%')->first();
            }
            return $mat;
        }

        // 5. Dll (Lainnya)
        if (!empty($lowerQty)) {
            // Cari master barang yang namanya ada di deskripsi qty
            $found = Material::all()->first(function ($m) use ($lowerQty) {
                $mName = strtolower($m->name);
                return strlen($mName) >= 3 && str_contains($lowerQty, $mName);
            });
            if ($found) {
                return $found;
            }
        }

        // Fallback untuk Dll: ambil item aksesoris / material pembantu atau item pertama
        return Material::where('category', 'Material Pembantu & Instalasi')
            ->orWhere('name', 'like', '%Roset%')
            ->orWhere('name', 'like', '%Klamp%')
            ->first() ?: Material::first();
    }

    /**
     * Parse numeric quantity dari string seperti '85 Meter', '2 Pcs', '1 Unit', '50'
     */
    public static function parseQuantity(string $qtyStr, string $type = ''): float
    {
        $clean = trim($qtyStr);
        if ($clean === '') {
            return 1.0;
        }

        // Ganti koma desimal dengan titik
        $clean = str_replace(',', '.', $clean);

        if (preg_match('/(\d+(?:\.\d+)?)/', $clean, $matches)) {
            $val = (float) $matches[1];
            return $val > 0 ? $val : 1.0;
        }

        return 1.0;
    }

    /**
     * Normalisasi format items
     */
    protected static function normalizeItems(?string $materialUsedString, $materialItems): array
    {
        if (is_array($materialItems) && !empty($materialItems)) {
            return $materialItems;
        }

        if (is_string($materialItems) && !empty($materialItems)) {
            $decoded = json_decode($materialItems, true);
            if (is_array($decoded) && !empty($decoded)) {
                return $decoded;
            }
        }

        if (empty($materialUsedString)) {
            return [];
        }

        $parts = preg_split('/,\s*(?![^()]*\))/', $materialUsedString);
        $items = [];
        $validOptions = ['Drop core per meter', 'Smurf', 'Modem', 'Adaptor', 'Dll'];

        foreach ($parts as $part) {
            $p = trim($part);
            if (empty($p)) continue;

            $matched = false;
            foreach ($validOptions as $opt) {
                if (stripos($p, $opt) === 0) {
                    $remainder = trim(substr($p, strlen($opt)));
                    $remainder = ltrim($remainder, ": -\t\n\r\0\x0B");
                    $remainder = preg_replace('/^\((.*)\)$/', '$1', $remainder);
                    $items[] = ['type' => $opt, 'qty' => trim($remainder)];
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                $items[] = ['type' => 'Dll', 'qty' => $p];
            }
        }

        return $items;
    }

    /**
     * Update snapshot bulanan
     */
    protected static function updateMonthlyStock(int $materialId, float $amount, string $txDate): void
    {
        try {
            $txCarbon = Carbon::parse($txDate);
            $mStock = MaterialMonthlyStock::where('material_id', $materialId)
                ->where('month', $txCarbon->month)
                ->where('year', $txCarbon->year)
                ->first();
            if ($mStock) {
                $mStock->total_out = (float)$mStock->total_out + $amount;
                $mStock->final_stock = (float)$mStock->initial_stock + (float)$mStock->total_in - (float)$mStock->total_out;
                $mStock->save();
            }
        } catch (\Throwable $e) {}
    }
}
