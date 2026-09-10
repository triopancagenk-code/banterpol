<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Order;
use Illuminate\Support\Carbon;

class BillingService
{
    /**
     * Menerbitkan tagihan baru untuk pesanan pemasangan yang sudah berstatus 'Selesai'.
     * Jatuh tempo diatur sebelum tanggal 5 bulan berikutnya (contoh: 05 Okt 2026).
     */
    public static function generateBillForOrder(Order $order): Bill
    {
        // Pastikan idempoten: jika tagihan untuk order ini sudah ada, kembalikan yang ada
        $existingBill = Bill::where('order_id', $order->id)->first();
        if ($existingBill) {
            return $existingBill;
        }

        // Tentukan tanggal pemasangan / penyelesaian
        $installedAt = $order->installed_at ? Carbon::parse($order->installed_at) : now('Asia/Jakarta');

        // Jatuh tempo: sebelum tanggal 5 bulan depan (tanggal 5 bulan berikutnya)
        $nextMonth = $installedAt->copy()->addMonth();
        $dueDateStr = '05 ' . $nextMonth->translatedFormat('M Y'); // Contoh: "05 Okt 2026"

        // Periode langganan: 1 bulan sejak pemasangan
        $periodEnd = $installedAt->copy()->addMonth();
        $periodStr = $installedAt->translatedFormat('d M Y') . ' – ' . $periodEnd->translatedFormat('d M Y');

        // Tanggal tagihan diterbitkan
        $billDateStr = $installedAt->translatedFormat('d M Y');

        // Nomor invoice terformat unik: INV-YYYYMM-XXX
        $prefix = 'INV-' . $installedAt->format('Ym') . '-';
        $latestBill = Bill::where('bill_number', 'like', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();

        $nextSeq = 1;
        if ($latestBill) {
            $parts = explode('-', $latestBill->bill_number);
            $lastPart = end($parts);
            if (is_numeric($lastPart)) {
                $nextSeq = (int) $lastPart + 1;
            }
        }

        $billNumber = $prefix . str_pad($nextSeq, 3, '0', STR_PAD_LEFT);
        while (Bill::where('bill_number', $billNumber)->exists()) {
            $nextSeq++;
            $billNumber = $prefix . str_pad($nextSeq, 3, '0', STR_PAD_LEFT);
        }

        $amount = (float) $order->price;
        if ($amount <= 0) {
            if (str_contains($order->package_name, '50')) {
                $amount = 220000;
            } elseif (str_contains($order->package_name, '30')) {
                $amount = 165000;
            } else {
                $amount = 110000;
            }
        }
        $tax = (float) ($order->tax ?? 0);
        $total = $amount + $tax;

        $speed = $order->speed;
        if (empty($speed)) {
            if (str_contains($order->package_name, '50')) {
                $speed = '50 Mbps';
            } elseif (str_contains($order->package_name, '30')) {
                $speed = '30 Mbps';
            } else {
                $speed = '20 Mbps';
            }
        }

        $technicianName = $order->technician ?? 'lapangan';

        $bill = Bill::create([
            'bill_number' => $billNumber,
            'order_id' => $order->id,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'customer_email' => $order->customer_email,
            'address' => $order->address,
            'package_name' => $order->package_name,
            'speed' => $speed,
            'period' => $periodStr,
            'due_date' => $dueDateStr,
            'bill_date' => $billDateStr,
            'amount' => $amount,
            'tax' => $tax,
            'total' => $total,
            'status' => 'Belum Bayar',
            'collector_notes' => 'Pemasangan selesai oleh teknisi ' . $technicianName . '. Batas bayar sebelum tanggal 5 bulan depan (' . $dueDateStr . ').',
        ]);

        return $bill;
    }

    /**
     * Sinkronisasi otomatis: periksa semua pesanan berstatus 'Selesai' yang belum memiliki tagihan,
     * lalu otomatis terbitkan tagihannya.
     */
    public static function syncCompletedOrdersWithoutBills(): int
    {
        $existingOrderIds = Bill::whereNotNull('order_id')->pluck('order_id')->toArray();

        $completedOrders = Order::where('status', 'Selesai')
            ->whereNotIn('id', $existingOrderIds)
            ->get();

        $count = 0;
        foreach ($completedOrders as $order) {
            if (!$order->installed_at) {
                $order->installed_at = $order->updated_at ?? now('Asia/Jakarta');
                $order->save();
            }
            self::generateBillForOrder($order);
            $count++;
        }

        // Pastikan semua tagihan jatuh temponya selalu pada setiap tanggal 5
        self::normalizeAllBillDueDates();

        return $count;
    }

    /**
     * Pastikan semua tagihan jatuh temponya berada pada tanggal 5.
     */
    public static function normalizeAllBillDueDates(): int
    {
        $bills = Bill::all();
        $updated = 0;
        foreach ($bills as $bill) {
            $oldDue = trim((string) $bill->due_date);
            if (!empty($oldDue) && !preg_match('/^0?5\s+/i', $oldDue)) {
                $bill->due_date = preg_replace('/^\d{1,2}\s+/', '05 ', $oldDue);
                $bill->save();
                $updated++;
            }
        }
        return $updated;
    }
}
