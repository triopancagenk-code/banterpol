<?php

namespace App\Http\Controllers\Collector;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Order;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class CollectorController extends Controller
{
    /**
     * Trouble Tickets Lapangan (Sinkronisasi dengan tim teknisi)
     */
    private function getTroubleTickets()
    {
        $defaultTickets = [];

        return session('collector_tickets', Cache::get('trouble_tickets', $defaultTickets));
    }

    /**
     * Dashboard Kolektor Lapangan
     */
    public function dashboard()
    {
        // Otomatis sinkronkan pesanan selesai agar tagihan terbit
        BillingService::syncCompletedOrdersWithoutBills();

        $allBills = Bill::all();
        $orders = Order::all();
        $tickets = $this->getTroubleTickets();

        // Metrik Keuangan Tunai Kolektor
        $cashBillsToday = $allBills->filter(function ($b) {
            return $b->payment_method === 'Tunai (Kolektor)' &&
                   $b->paid_at &&
                   $b->paid_at->isToday();
        });

        $totalCashToday = $cashBillsToday->sum('total');
        $cashTransactionCount = $cashBillsToday->count();

        // Tagihan Tertunda
        $unpaidBills = $allBills->where('status', 'Belum Bayar');
        $dueBills = $allBills->where('status', 'Jatuh Tempo');
        $paidBills = $allBills->where('status', 'Lunas');

        $stats = [
            'total_cash_today' => $totalCashToday,
            'cash_count_today' => $cashTransactionCount,
            'unpaid_count' => $unpaidBills->count(),
            'unpaid_nominal' => $unpaidBills->sum('total'),
            'due_count' => $dueBills->count(),
            'due_nominal' => $dueBills->sum('total'),
            'paid_count' => $paidBills->count(),
            'total_bills' => $allBills->count(),
            'pending_installations' => $orders->whereIn('status', ['Menunggu Konfirmasi', 'Jadwal Teknisi'])->count(),
            'active_troubles' => collect($tickets)->where('status', '!=', 'Selesai')->count(),
        ];

        // Daftar tagihan prioritas kunjungan penagihan hari ini (Jatuh tempo & belum bayar)
        $priorityBills = Bill::whereIn('status', ['Jatuh Tempo', 'Belum Bayar'])
            ->orderByRaw("CASE WHEN status = 'Jatuh Tempo' THEN 1 ELSE 2 END")
            ->orderBy('id', 'asc')
            ->take(5)
            ->get();

        // Tiket instalasi baru yang perlu dipantau kolektor (terkait pembayaran pasang baru)
        $recentOrders = Order::latest()->take(3)->get();

        // Tiket kendala lapangan
        $urgentTickets = collect($tickets)->where('status', '!=', 'Selesai')->take(3);

        return view('collector.dashboard', compact('stats', 'priorityBills', 'recentOrders', 'urgentTickets'));
    }

    /**
     * Daftar Tagihan Pelanggan & Form Pembayaran Tunai
     */
    public function tagihan(Request $request)
    {
        // Otomatis sinkronkan pesanan selesai agar tagihan terbit
        BillingService::syncCompletedOrdersWithoutBills();

        $statusFilter = $request->input('status', 'all');
        $search = $request->input('q', '');

        $query = Bill::query()->latest();

        if ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('bill_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('package_name', 'like', "%{$search}%")
                  ->orWhere('receipt_number', 'like', "%{$search}%");
            });
        }

        $bills = $query->paginate(15)->withQueryString();
        $allBills = Bill::all();

        $counts = [
            'all' => $allBills->count(),
            'belum_bayar' => $allBills->where('status', 'Belum Bayar')->count(),
            'jatuh_tempo' => $allBills->where('status', 'Jatuh Tempo')->count(),
            'lunas' => $allBills->where('status', 'Lunas')->count(),
            'total_tunai' => $allBills->where('payment_method', 'Tunai (Kolektor)')->sum('total'),
        ];

        return view('collector.tagihan', compact('bills', 'counts', 'statusFilter', 'search'));
    }

    /**
     * Input Manual Pembayaran Tunai dari Pelanggan
     */
    public function bayarTunai(Request $request)
    {
        $request->validate([
            'bill_id' => 'required|exists:bills,id',
            'cash_amount' => 'required|numeric|min:0',
            'paid_date' => 'required|date',
            'collector_notes' => 'nullable|string|max:500',
        ]);

        $bill = Bill::findOrFail($request->input('bill_id'));

        $receiptNumber = 'KWT-' . date('Ym') . '-' . str_pad($bill->id, 3, '0', STR_PAD_LEFT);

        $bill->status = 'Lunas';
        $bill->payment_method = 'Tunai (Kolektor)';
        $bill->paid_at = Carbon::parse($request->input('paid_date', now()));
        $bill->collected_by = auth()->user()->name;
        $bill->receipt_number = $receiptNumber;

        $notes = $request->input('collector_notes');
        $bill->collector_notes = $notes ?: ('Diterima tunai oleh ' . auth()->user()->name . ' pada ' . now()->translatedFormat('d M Y, H:i'));

        $bill->save();

        return redirect()->route('kolektor.tagihan.kuitansi', $bill->id)
            ->with('success', "Pembayaran tunai sebesar Rp " . number_format($bill->total, 0, ',', '.') . " untuk {$bill->customer_name} berhasil dicatat. Nomor Kuitansi: {$receiptNumber}.");
    }

    /**
     * Input Manual Tagihan Baru (Pelanggan Tunai Fisik / Luar Sistem)
     */
    public function inputManualTagihan(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:25',
            'address' => 'required|string|max:500',
            'package_name' => 'required|string|max:100',
            'speed' => 'nullable|string|max:50',
            'period' => 'required|string|max:100',
            'due_date' => 'required|string|max:50',
            'total' => 'required|numeric|min:1000',
            'is_paid_immediately' => 'nullable|boolean',
            'collector_notes' => 'nullable|string|max:500',
        ]);

        $billNumber = 'INV-' . date('Ym') . '-' . rand(100, 999);
        $total = (float) $request->input('total');
        $amount = $total;
        $tax = 0;

        $packageName = $request->input('package_name');
        $speed = $request->input('speed');
        if (empty($speed)) {
            if (str_contains($packageName, '50')) {
                $speed = '50 Mbps';
            } elseif (str_contains($packageName, '30')) {
                $speed = '30 Mbps';
            } else {
                $speed = '20 Mbps';
            }
        }

        $isPaid = $request->boolean('is_paid_immediately');

        $bill = new Bill();
        $bill->bill_number = $billNumber;
        $bill->customer_name = $request->input('customer_name');
        $bill->customer_phone = $request->input('customer_phone');
        $bill->customer_email = $request->input('customer_email');
        $bill->address = $request->input('address');
        $bill->package_name = $packageName;
        $bill->speed = $speed;
        $bill->period = $request->input('period');
        $dueDate = $request->input('due_date');
        if (!preg_match('/^0?5\s+/i', $dueDate)) {
            $dueDate = preg_replace('/^\d{1,2}\s+/', '05 ', $dueDate);
        }
        $bill->due_date = $dueDate;
        $bill->bill_date = now()->translatedFormat('d M Y');
        $bill->amount = $amount;
        $bill->tax = $tax;
        $bill->total = $total;

        if ($isPaid) {
            $bill->status = 'Lunas';
            $bill->payment_method = 'Tunai (Kolektor)';
            $bill->paid_at = now();
            $bill->collected_by = auth()->user()->name;
            $bill->receipt_number = 'KWT-' . date('Ym') . '-' . rand(100, 999);
            $bill->collector_notes = $request->input('collector_notes') ?: ('Diterima tunai langsung saat input tagihan oleh ' . auth()->user()->name);
        } else {
            $bill->status = 'Belum Bayar';
            $bill->collector_notes = $request->input('collector_notes') ?: 'Tagihan manual diinput oleh kolektor lapangan.';
        }

        $bill->save();

        if ($isPaid) {
            return redirect()->route('kolektor.tagihan.kuitansi', $bill->id)
                ->with('success', "Tagihan manual dan pembayaran tunai berhasil dibuat! Nomor Kuitansi: {$bill->receipt_number}");
        }

        return redirect()->route('kolektor.tagihan')
            ->with('success', "Tagihan manual baru untuk {$bill->customer_name} berhasil diterbitkan dengan nomor {$bill->bill_number}.");
    }

    /**
     * Cetak / Lihat Kuitansi Tanda Terima Tunai Digital
     */
    public function kuitansi($id)
    {
        $bill = Bill::findOrFail($id);

        return view('collector.kuitansi', compact('bill'));
    }

    /**
     * Tiket Pemasangan Baru (Monitoring Lapangan untuk Koordinasi Kolektor)
     */
    public function pemasangan(Request $request)
    {
        $statusFilter = $request->input('status', 'all');
        $search = $request->input('q', '');

        $query = Order::query()->latest();

        if ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('package_name', 'like', "%{$search}%");
            });
        }

        $orders = $query->paginate(15)->withQueryString();
        $allOrders = Order::all();

        $counts = [
            'all' => $allOrders->count(),
            'menunggu' => $allOrders->where('status', 'Menunggu Konfirmasi')->count(),
            'jadwal' => $allOrders->where('status', 'Jadwal Teknisi')->count(),
            'proses' => $allOrders->where('status', 'Sedang Dipasang')->count(),
            'selesai' => $allOrders->where('status', 'Selesai')->count(),
        ];

        return view('collector.pemasangan', compact('orders', 'counts', 'statusFilter', 'search'));
    }

    /**
     * Tiket Gangguan Lapangan (Untuk pengecekan kolektor saat penagihan)
     */
    public function gangguan(Request $request)
    {
        $statusFilter = $request->input('status', 'all');
        $search = $request->input('q', '');

        $allTickets = $this->getTroubleTickets();
        $filteredTickets = collect($allTickets);

        if ($statusFilter !== 'all') {
            $filteredTickets = $filteredTickets->where('status', $statusFilter);
        }

        if (!empty($search)) {
            $filteredTickets = $filteredTickets->filter(function ($t) use ($search) {
                return str_contains(strtolower($t['id']), strtolower($search)) ||
                       str_contains(strtolower($t['customer_name']), strtolower($search)) ||
                       str_contains(strtolower($t['type']), strtolower($search)) ||
                       str_contains(strtolower($t['address']), strtolower($search));
            });
        }

        $counts = [
            'all' => count($allTickets),
            'aktif' => collect($allTickets)->where('status', '!=', 'Selesai')->count(),
            'kritis' => collect($allTickets)->where('priority', 'Kritis')->where('status', '!=', 'Selesai')->count(),
            'selesai' => collect($allTickets)->where('status', 'Selesai')->count(),
        ];

        return view('collector.gangguan', [
            'tickets' => $filteredTickets->values(),
            'counts' => $counts,
            'statusFilter' => $statusFilter,
            'search' => $search
        ]);
    }
}
