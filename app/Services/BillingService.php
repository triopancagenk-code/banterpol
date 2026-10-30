<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Carbon;

class BillingService
{
    /**
     * Parsing string tanggal jatuh tempo ke objek Carbon (batas pembayaran maksimal tanggal 20).
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
            return $carbon->day(20);
        } catch (\Exception $e) {
            return now('Asia/Jakarta')->day(20);
        }
    }

    /**
     * Menerbitkan tagihan pertama untuk pesanan baru / pendaftaran langganan.
     * Terbit tanggal 05, batas pembayaran maksimal selalu tanggal 20.
     */
    public static function generateBillForOrder(Order $order): Bill
    {
        // Pastikan idempoten: jika tagihan untuk order ini sudah ada, kembalikan yang ada
        $existingBill = Bill::where('order_id', $order->id)->first();
        if ($existingBill) {
            return $existingBill;
        }

        $now = now('Asia/Jakarta');
        $baseDate = $order->installed_at 
            ? Carbon::parse($order->installed_at) 
            : ($order->created_at ? Carbon::parse($order->created_at) : $now);

        // Jika hari ini >= 5, gunakan bulan sekarang; jika belum, gunakan bulan pemasangan
        $billMonth = ($now->day >= 5) ? $now->copy()->startOfMonth() : $baseDate->copy()->startOfMonth();

        // Tanggal tagihan terbit: selalu tanggal 05
        $billDateStr = '05 ' . $billMonth->translatedFormat('M Y');

        // Jatuh tempo: selalu tanggal 20 setiap bulannya
        $dueDateStr = '20 ' . $billMonth->translatedFormat('M Y');

        // Periode langganan: 1 bulan penuh
        $periodEnd = $billMonth->copy()->endOfMonth();
        $periodStr = '01 ' . $billMonth->translatedFormat('M Y') . ' – ' . $periodEnd->translatedFormat('d M Y');

        // Nomor invoice terformat unik: INV-YYYYMM-XXX
        $prefix = 'INV-' . $billMonth->format('Ym') . '-';
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

        // Status: jika hari ini sudah melewati tanggal 20, status Jatuh Tempo, sebaliknya Belum Bayar
        $status = ($now->day > 20) ? 'Jatuh Tempo' : 'Belum Bayar';

        $bill = Bill::create([
            'user_id' => $userId,
            'bill_number' => $billNumber,
            'order_id' => $order->id,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'customer_email' => $order->customer_email,
            'address' => $order->address,
            'package_name' => $order->package_name ?: 'Paket 20 Mbps',
            'speed' => $speed,
            'period' => $periodStr,
            'due_date' => $dueDateStr,
            'bill_date' => $billDateStr,
            'amount' => $amount,
            'tax' => $tax,
            'total' => $total,
            'status' => $status,
            'collector_notes' => 'Tagihan langganan. Terbit tanggal 05, batas bayar maksimal tanggal 20 (' . $dueDateStr . ').',
        ]);

        return $bill;
    }

    /**
     * Menerbitkan tagihan untuk bulan selanjutnya setelah suatu tagihan dinyatakan 'Lunas'.
     * Terbit tanggal 05 bulan berikutnya, batas pembayaran tanggal 20 bulan berikutnya.
     */
    public static function generateNextBillForPaidBill(Bill $paidBill): Bill
    {
        $currentDue = self::parseDueDate($paidBill->due_date ?: ($paidBill->paid_at ? $paidBill->paid_at->format('d M Y') : now()->format('d M Y')));
        $nextMonth = $currentDue->copy()->addMonth()->startOfMonth();

        $nextBillDateStr = '05 ' . $nextMonth->translatedFormat('M Y');
        $nextDueDateStr = '20 ' . $nextMonth->translatedFormat('M Y');

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
        $prefix = 'INV-' . $nextMonth->format('Ym') . '-';
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

        $periodEnd = $nextMonth->copy()->endOfMonth();
        $periodStr = '01 ' . $nextMonth->translatedFormat('M Y') . ' – ' . $periodEnd->translatedFormat('d M Y');

        $now = now('Asia/Jakarta');
        $status = ($now->greaterThan($nextMonth->copy()->day(20)->endOfDay())) ? 'Jatuh Tempo' : 'Belum Bayar';

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
            'bill_date' => $nextBillDateStr,
            'amount' => $paidBill->amount,
            'tax' => $paidBill->tax,
            'total' => $paidBill->total,
            'status' => $status,
            'collector_notes' => 'Tagihan bulan selanjutnya setelah pelunasan ' . $paidBill->bill_number . '. Terbit tanggal 05, jatuh tempo tanggal 20 (' . $nextDueDateStr . ').',
        ]);

        return $nextBill;
    }

    /**
     * Sinkronisasi Tagihan Bulanan Berdasarkan Data Pelanggan (Master Data):
     * - Setiap tanggal 5 setiap bulannya: tagihan diterbitkan otomatis untuk seluruh pelanggan di Data Pelanggan.
     * - Tanggal tagihan (bill_date): 05 [Bulan] [Tahun].
     * - Batas pembayaran maksimal (due_date / jatuh tempo): 20 [Bulan] [Tahun].
     * - Periode pemakaian (period): 01 [Bulan] [Tahun] – [Akhir Bulan] [Bulan] [Tahun].
     * - Status otomatis:
     *   * Jika hari ini <= 20: 'Belum Bayar' (atau 'Menunggu Verifikasi')
     *   * Jika hari ini > 20: 'Jatuh Tempo'
     *   * Jika sudah bayar / diverifikasi: 'Lunas' (otomatis masuk ke Rekap Pembayaran)
     */
    public static function syncMonthlyBillsFromDataPelanggan(?Carbon $now = null): int
    {
        $now = $now ? $now->copy()->setTimezone('Asia/Jakarta') : now('Asia/Jakarta');
        $createdCount = 0;

        // Tagihan terbit mulai tanggal 5 setiap bulannya
        if ($now->day >= 5) {
            $month = $now->copy()->startOfMonth();
            $billDateStr = '05 ' . $month->translatedFormat('M Y');
            $dueDateStr = '20 ' . $month->translatedFormat('M Y');
            $periodEnd = $month->copy()->endOfMonth();
            $periodStr = '01 ' . $month->translatedFormat('M Y') . ' – ' . $periodEnd->translatedFormat('d M Y');

            $monthPrefix = 'INV-' . $month->format('Ym') . '-';
            $customers = Order::forCustomerData()->get();

            foreach ($customers as $customer) {
                // Cari apakah sudah ada tagihan untuk pelanggan ini pada bulan berjalan
                $existingBill = Bill::where(function ($q) use ($customer) {
                    $q->where('order_id', $customer->id);
                    if (!empty($customer->customer_phone) && $customer->customer_phone !== '-') {
                        $q->orWhere('customer_phone', $customer->customer_phone);
                    }
                    if (!empty($customer->customer_name)) {
                        $q->orWhere('customer_name', $customer->customer_name);
                    }
                })->where(function ($q) use ($dueDateStr, $month) {
                    $q->where('due_date', $dueDateStr)
                      ->orWhere('period', 'like', '%' . $month->translatedFormat('M Y') . '%');
                })->first();

                if ($existingBill) {
                    // Update due_date jika masih format tanggal lama
                    if (!preg_match('/^20\s+/i', (string) $existingBill->due_date)) {
                        $existingBill->due_date = $dueDateStr;
                    }
                    // Jika belum bayar dan hari ini > 20, update status ke 'Jatuh Tempo'
                    if ($existingBill->status === 'Belum Bayar' && $now->day > 20) {
                        $existingBill->status = 'Jatuh Tempo';
                    }
                    if (empty($existingBill->bill_date)) {
                        $existingBill->bill_date = $billDateStr;
                    }
                    $existingBill->save();
                } else {
                    // Buat tagihan baru bulan berjalan untuk pelanggan ini
                    $seq = Bill::where('bill_number', 'like', "{$monthPrefix}%")->count() + 1;
                    $billNumber = $monthPrefix . str_pad($seq, 3, '0', STR_PAD_LEFT);
                    while (Bill::where('bill_number', $billNumber)->exists()) {
                        $seq++;
                        $billNumber = $monthPrefix . str_pad($seq, 3, '0', STR_PAD_LEFT);
                    }

                    $amount = (float) $customer->price;
                    if ($amount <= 0) {
                        if (str_contains($customer->package_name, '50')) {
                            $amount = 220000;
                        } elseif (str_contains($customer->package_name, '30')) {
                            $amount = 165000;
                        } else {
                            $amount = 110000;
                        }
                    }

                    $speed = $customer->speed;
                    if (empty($speed)) {
                        if (str_contains($customer->package_name, '50')) {
                            $speed = '50 Mbps';
                        } elseif (str_contains($customer->package_name, '30')) {
                            $speed = '30 Mbps';
                        } else {
                            $speed = '20 Mbps';
                        }
                    }

                    $tax = (float) ($customer->tax ?? 0);
                    $total = $amount + $tax;
                    $status = ($now->day > 20) ? 'Jatuh Tempo' : 'Belum Bayar';

                    Bill::create([
                        'user_id' => $customer->user_id,
                        'bill_number' => $billNumber,
                        'order_id' => $customer->id,
                        'customer_name' => $customer->customer_name,
                        'customer_phone' => $customer->customer_phone,
                        'customer_email' => $customer->customer_email,
                        'address' => $customer->address,
                        'package_name' => $customer->package_name ?: 'Paket 20 Mbps',
                        'speed' => $speed,
                        'period' => $periodStr,
                        'due_date' => $dueDateStr,
                        'bill_date' => $billDateStr,
                        'amount' => $amount,
                        'tax' => $tax,
                        'total' => $total,
                        'status' => $status,
                        'payment_method' => null,
                        'collector_notes' => 'Tagihan bulanan per tanggal 05. Batas bayar tanggal 20 (' . $dueDateStr . ').',
                    ]);

                    $createdCount++;
                }
            }
        }

        // Periksa dan perbarui status semua tagihan aktif yang melewati tanggal jatuh tempo
        self::updateOverdueBills($now);

        // Pastikan semua tagihan jatuh temponya berada pada tanggal 20
        self::normalizeAllBillDueDates();

        return $createdCount;
    }

    /**
     * Update tagihan 'Belum Bayar' menjadi 'Jatuh Tempo' jika hari ini sudah melewati tanggal 20.
     */
    public static function updateOverdueBills(?Carbon $now = null): int
    {
        $now = $now ? $now->copy()->setTimezone('Asia/Jakarta') : now('Asia/Jakarta');
        $bills = Bill::where('status', 'Belum Bayar')->get();
        $updated = 0;

        foreach ($bills as $bill) {
            $due = self::parseDueDate((string) $bill->due_date);
            if ($now->greaterThan($due->endOfDay())) {
                $bill->status = 'Jatuh Tempo';
                $bill->save();
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Sinkronisasi tagihan pelanggan (POV Pelanggan Web):
     * Sinkronkan tagihan bulanan dari data pelanggan.
     */
    public static function syncCustomerBills(User $user): void
    {
        self::syncMonthlyBillsFromDataPelanggan();
    }

    /**
     * Sinkronisasi tagihan pelanggan berdasarkan data kontak / order:
     */
    public static function syncCustomerBillsForCustomer(?string $customerEmail, ?string $customerName = null, ?string $customerPhone = null, ?int $orderId = null, ?int $userId = null): void
    {
        self::syncMonthlyBillsFromDataPelanggan();
    }

    /**
     * Sinkronisasi otomatis dari order pendaftaran dan master data pelanggan.
     */
    public static function syncCompletedOrdersWithoutBills(): int
    {
        return self::syncMonthlyBillsFromDataPelanggan();
    }

    /**
     * Pastikan semua tagihan jatuh temponya berada pada tanggal 20.
     */
    public static function normalizeAllBillDueDates(): int
    {
        $bills = Bill::all();
        $updated = 0;
        foreach ($bills as $bill) {
            $oldDue = trim((string) $bill->due_date);
            if (!empty($oldDue) && !preg_match('/^20\s+/i', $oldDue)) {
                $bill->due_date = preg_replace('/^\d{1,2}\s+/', '20 ', $oldDue);
                $bill->save();
                $updated++;
            }
        }
        return $updated;
    }
}
