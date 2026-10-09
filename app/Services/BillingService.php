<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Carbon;

class BillingService
{
    /**
     * Parsing string tanggal jatuh tempo ke objek Carbon (selalu dipastikan tanggal 5).
     */
    public static function parseDueDate(string $dueDateStr): Carbon
    {
        $idToEn = [
            'Januari' => 'January', 'Februari' => 'February', 'Maret' => 'March',
            'April' => 'April', 'Mei' => 'May', 'Juni' => 'June',
            'Juli' => 'July', 'Agustus' => 'August', 'September' => 'September',
            'Oktober' => 'October', 'November' => 'November', 'Desember' => 'December',
            'Jan' => 'Jan', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Apr',
            'Agu' => 'Aug', 'Ags' => 'Aug', 'Sep' => 'Sep', 'Okt' => 'Oct',
            'Nov' => 'Nov', 'Des' => 'Dec'
        ];

        $cleaned = trim($dueDateStr);
        foreach ($idToEn as $id => $en) {
            $cleaned = preg_replace('/\b' . $id . '\b/i', $en, $cleaned);
        }

        try {
            $carbon = Carbon::parse($cleaned);
            return $carbon->day(5);
        } catch (\Exception $e) {
            return now('Asia/Jakarta')->day(5)->addMonth();
        }
    }

    /**
     * Menerbitkan tagihan pertama untuk pesanan baru / pendaftaran langganan.
     * Jatuh tempo selalu diatur pada tanggal 5 bulan berikutnya (contoh: 05 Okt 2026).
     */
    public static function generateBillForOrder(Order $order): Bill
    {
        // Pastikan idempoten: jika tagihan untuk order ini sudah ada, kembalikan yang ada
        $existingBill = Bill::where('order_id', $order->id)->first();
        if ($existingBill) {
            return $existingBill;
        }

        // Tentukan tanggal acuan pemasangan / pendaftaran
        $baseDate = $order->installed_at 
            ? Carbon::parse($order->installed_at) 
            : ($order->created_at ? Carbon::parse($order->created_at) : now('Asia/Jakarta'));

        // Jatuh tempo: selalu tanggal 5 bulan depan
        $nextMonth = $baseDate->copy()->addMonth();
        $dueDateStr = '05 ' . $nextMonth->translatedFormat('M Y'); // Contoh: "05 Okt 2026"

        // Periode langganan: 1 bulan
        $periodEnd = $baseDate->copy()->addMonth();
        $periodStr = $baseDate->translatedFormat('d M Y') . ' – ' . $periodEnd->translatedFormat('d M Y');

        // Tanggal tagihan diterbitkan
        $billDateStr = $baseDate->translatedFormat('d M Y');

        // Nomor invoice terformat unik: INV-YYYYMM-XXX
        $prefix = 'INV-' . $baseDate->format('Ym') . '-';
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

        $userId = $order->user_id;
        if (!$userId && !empty($order->customer_email) && $order->customer_email !== '-') {
            $userId = User::where('email', $order->customer_email)->value('id');
        }

        $bill = Bill::create([
            'user_id' => $userId,
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
            'collector_notes' => 'Tagihan langganan baru. Batas bayar tanggal 5 bulan depan (' . $dueDateStr . ').',
        ]);

        return $bill;
    }

    /**
     * Menerbitkan tagihan untuk bulan selanjutnya setelah suatu tagihan dinyatakan 'Lunas'.
     * Jatuh tempo selalu tanggal 5 bulan berikutnya.
     */
    public static function generateNextBillForPaidBill(Bill $paidBill): Bill
    {
        // Hitung tanggal jatuh tempo bulan berikutnya
        $currentDue = self::parseDueDate($paidBill->due_date ?: ($paidBill->paid_at ? $paidBill->paid_at->format('d M Y') : now()->format('d M Y')));
        $nextDue = $currentDue->copy()->addMonth();
        $nextDueDateStr = '05 ' . $nextDue->translatedFormat('M Y'); // Contoh: "05 Nov 2026"

        // Cek apakah tagihan dengan due_date tersebut sudah pernah dibuat untuk order / customer ini
        $existingNextBill = Bill::where(function ($q) use ($paidBill) {
            if ($paidBill->order_id) {
                $q->where('order_id', $paidBill->order_id);
            } else {
                $q->where('customer_email', $paidBill->customer_email)
                  ->orWhere('customer_name', $paidBill->customer_name);
            }
        })
        ->where('due_date', $nextDueDateStr)
        ->first();

        if ($existingNextBill) {
            return $existingNextBill;
        }

        // Nomor invoice terformat unik: INV-YYYYMM-XXX
        $prefix = 'INV-' . $nextDue->format('Ym') . '-';
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

        $periodEnd = $nextDue->copy()->addMonth();
        $periodStr = $nextDue->translatedFormat('d M Y') . ' – ' . $periodEnd->translatedFormat('d M Y');
        $billDateStr = now('Asia/Jakarta')->translatedFormat('d M Y');

        $nextBill = Bill::create([
            'user_id' => $paidBill->user_id,
            'bill_number' => $billNumber,
            'order_id' => $paidBill->order_id,
            'customer_name' => $paidBill->customer_name,
            'customer_phone' => $paidBill->customer_phone,
            'customer_email' => $paidBill->customer_email,
            'address' => $paidBill->address,
            'package_name' => $paidBill->package_name,
            'speed' => $paidBill->speed,
            'period' => $periodStr,
            'due_date' => $nextDueDateStr,
            'bill_date' => $billDateStr,
            'amount' => $paidBill->amount,
            'tax' => $paidBill->tax,
            'total' => $paidBill->total,
            'status' => 'Belum Bayar',
            'collector_notes' => 'Tagihan bulan selanjutnya setelah pelunasan ' . $paidBill->bill_number . '. Jatuh tempo tanggal ' . $nextDueDateStr . '.',
        ]);

        return $nextBill;
    }

    /**
     * Sinkronisasi tagihan pelanggan (POV Pelanggan):
     * 1. Jika pelanggan mendaftar langganan (punya Order) tapi belum ada tagihan -> langsung terbitkan tagihan bulan depan.
     * 2. Jika tagihan terakhir sudah 'Lunas' dan belum ada tagihan aktif berikutnya -> otomatis terbitkan tagihan bulan selanjutnya.
     */
    public static function syncCustomerBills(User $user): void
    {
        self::syncCustomerBillsForCustomer($user->email, $user->name, $user->phone, null, $user->id);
    }

    /**
     * Sinkronisasi tagihan pelanggan berdasarkan data kontak / order:
     * 1. Jika pelanggan mendaftar langganan (punya Order) tapi belum ada tagihan -> langsung terbitkan tagihan bulan depan.
     * 2. Jika tagihan terakhir sudah 'Lunas' dan belum ada tagihan aktif berikutnya -> otomatis terbitkan tagihan bulan selanjutnya.
     */
    public static function syncCustomerBillsForCustomer(?string $customerEmail, ?string $customerName = null, ?string $customerPhone = null, ?int $orderId = null, ?int $userId = null): void
    {
        if (empty($customerEmail) && empty($customerName) && empty($customerPhone) && empty($orderId) && empty($userId)) {
            return;
        }

        // 1. Cek pesanan milik pelanggan
        $ordersQuery = Order::where('order_number', 'not like', 'PLG-%');
        $ordersQuery->where(function ($q) use ($customerEmail, $customerName, $customerPhone, $orderId, $userId) {
            $hasCondition = false;
            if (!empty($userId)) {
                $q->where('user_id', $userId);
                $hasCondition = true;
            }
            if (!empty($orderId)) {
                $hasCondition ? $q->orWhere('id', $orderId) : $q->where('id', $orderId);
                $hasCondition = true;
            }
            if (!empty($customerEmail)) {
                $hasCondition ? $q->orWhere('customer_email', $customerEmail) : $q->where('customer_email', $customerEmail);
                $hasCondition = true;
            }
            if (!empty($customerName)) {
                $hasCondition ? $q->orWhere('customer_name', $customerName) : $q->where('customer_name', $customerName);
                $hasCondition = true;
            }
            if (!empty($customerPhone)) {
                $hasCondition ? $q->orWhere('customer_phone', $customerPhone) : $q->where('customer_phone', $customerPhone);
                $hasCondition = true;
            }
        });
        $orders = $ordersQuery->get();

        foreach ($orders as $order) {
            if ($userId && !$order->user_id) {
                $order->user_id = $userId;
                $order->save();
            }

            $isCompleted = in_array(strtolower(trim((string) $order->status)), ['selesai', 'selesai / aktif', 'aktif']);
            if ($isCompleted) {
                $hasBill = Bill::where('order_id', $order->id)->exists();
                if (!$hasBill) {
                    self::generateBillForOrder($order);
                }
            } else {
                // Sesuai aturan sistem: jika status pesanan belum 'Selesai', tidak boleh muncul tagihan
                Bill::where('order_id', $order->id)->whereIn('status', ['Belum Bayar', 'Jatuh Tempo', 'Menunggu Verifikasi'])->delete();
            }
        }

        // 2. Cek apakah ada tagihan aktif (Belum Bayar / Jatuh Tempo / Menunggu Verifikasi)
        $completedOrderIds = $orders->filter(function ($o) {
            return in_array(strtolower(trim((string) $o->status)), ['selesai', 'selesai / aktif', 'aktif']);
        })->pluck('id');

        $activeBill = Bill::activeForMonitoring()
            ->whereIn('status', ['Belum Bayar', 'Jatuh Tempo', 'Menunggu Verifikasi'])
            ->where(function ($query) use ($customerEmail, $customerName, $customerPhone, $completedOrderIds, $userId) {
                $hasCondition = false;
                if (!empty($userId)) {
                    $query->where('user_id', $userId);
                    $hasCondition = true;
                }
                if ($completedOrderIds->isNotEmpty()) {
                    $hasCondition ? $query->orWhereIn('order_id', $completedOrderIds) : $query->whereIn('order_id', $completedOrderIds);
                    $hasCondition = true;
                }
                if (!empty($customerEmail)) {
                    $hasCondition ? $query->orWhere('customer_email', $customerEmail) : $query->where('customer_email', $customerEmail);
                    $hasCondition = true;
                }
                if (!empty($customerName)) {
                    $hasCondition ? $query->orWhere('customer_name', $customerName) : $query->where('customer_name', $customerName);
                    $hasCondition = true;
                }
                if (!empty($customerPhone)) {
                    $hasCondition ? $query->orWhere('customer_phone', $customerPhone) : $query->where('customer_phone', $customerPhone);
                    $hasCondition = true;
                }
            })
            ->latest('id')
            ->first();

        // 3. Jika TIDAK ADA tagihan aktif belum bayar, tapi ADA tagihan yang sudah LUNAS:
        // Otomatis terbitkan tagihan untuk bulan selanjutnya!
        if (!$activeBill && $completedOrderIds->isNotEmpty()) {
            $latestPaidBill = Bill::activeForMonitoring()
                ->where('status', 'Lunas')
                ->where(function ($query) use ($customerEmail, $customerName, $customerPhone, $completedOrderIds, $userId) {
                    $hasCondition = false;
                    if (!empty($userId)) {
                        $query->where('user_id', $userId);
                        $hasCondition = true;
                    }
                    if ($completedOrderIds->isNotEmpty()) {
                        $query->whereIn('order_id', $completedOrderIds);
                        $hasCondition = true;
                    }
                    if (!empty($customerEmail)) {
                        $hasCondition ? $query->orWhere('customer_email', $customerEmail) : $query->where('customer_email', $customerEmail);
                        $hasCondition = true;
                    }
                    if (!empty($customerName)) {
                        $hasCondition ? $query->orWhere('customer_name', $customerName) : $query->where('customer_name', $customerName);
                        $hasCondition = true;
                    }
                    if (!empty($customerPhone)) {
                        $hasCondition ? $query->orWhere('customer_phone', $customerPhone) : $query->where('customer_phone', $customerPhone);
                        $hasCondition = true;
                    }
                })
                ->latest('id')
                ->first();

            if ($latestPaidBill) {
                self::generateNextBillForPaidBill($latestPaidBill);
            }
        }

        // Pastikan semua tanggal jatuh tempo berformat tanggal 05
        self::normalizeAllBillDueDates();
    }

    /**
     * Sinkronisasi otomatis: periksa semua pesanan berstatus 'Selesai' yang belum memiliki tagihan,
     * lalu otomatis terbitkan tagihannya.
     */
    public static function syncCompletedOrdersWithoutBills(): int
    {
        $existingOrderIds = Bill::whereNotNull('order_id')->pluck('order_id')->toArray();

        $completedOrders = Order::where('status', 'Selesai')
            ->where('order_number', 'not like', 'PLG-%')
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
