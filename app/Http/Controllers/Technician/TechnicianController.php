<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TechnicianController extends Controller
{
    /**
     * Data Master Tiket Gangguan Lapangan (Simulasi terintegrasi session)
     */
    private function getTroubleTickets()
    {
        $defaultTickets = [];

        return session('technician_tickets', Cache::get('trouble_tickets', $defaultTickets));
    }

    /**
     * Dashboard Utama Teknisi Lapangan
     */
    public function dashboard()
    {
        $allOrders = Order::all();
        $tickets = $this->getTroubleTickets();

        // Metrik Pemasangan (Semua pesanan aktif yang butuh instalasi/penanganan)
        $activeStatuses = ['Menunggu Konfirmasi', 'Jadwal Pemasangan', 'Jadwal Teknisi', 'Sedang Dipasang', 'Kendala Lapangan'];
        $pendingInstallations = $allOrders->whereIn('status', $activeStatuses)->count();
        $completedInstallations = $allOrders->where('status', 'Selesai')->count();

        // Metrik Gangguan
        $activeTroubles = collect($tickets)->whereIn('status', ['Menunggu Respon', 'Sedang Ditangani'])->count();
        $criticalTroubles = collect($tickets)->where('priority', 'Kritis')->where('status', '!=', 'Selesai')->count();

        // Tugas Instalasi Aktif Terbaru
        $activeOrders = Order::whereIn('status', $activeStatuses)
            ->latest()
            ->take(4)
            ->get();

        // Tiket Gangguan Butuh Penanganan Segera
        $urgentTickets = collect($tickets)
            ->where('status', '!=', 'Selesai')
            ->take(4);

        $stats = [
            'pending_installations' => $pendingInstallations,
            'completed_installations' => $completedInstallations,
            'active_troubles' => $activeTroubles,
            'critical_troubles' => $criticalTroubles,
            'total_tasks_today' => $pendingInstallations + $activeTroubles,
        ];

        return view('technician.dashboard', compact('stats', 'activeOrders', 'urgentTickets'));
    }

    /**
     * Daftar Tiket Pemasangan Baru (Instalasi Pelanggan)
     */
    public function pemasangan(Request $request)
    {
        $statusFilter = $request->input('status', 'all');
        $search = $request->input('q', '');

        $query = Order::query()->latest();

        if ($statusFilter !== 'all') {
            if ($statusFilter === 'Jadwal Teknisi') {
                $query->whereIn('status', ['Menunggu Konfirmasi', 'Jadwal Pemasangan', 'Jadwal Teknisi']);
            } else {
                $query->where('status', $statusFilter);
            }
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%")
                  ->orWhere('package_name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('assigned_odp', 'like', "%{$search}%");
            });
        }

        $orders = $query->paginate(15)->withQueryString();
        $allOrders = Order::all();

        $counts = [
            'all' => $allOrders->count(),
            'jadwal' => $allOrders->whereIn('status', ['Menunggu Konfirmasi', 'Jadwal Pemasangan', 'Jadwal Teknisi'])->count(),
            'proses' => $allOrders->where('status', 'Sedang Dipasang')->count(),
            'selesai' => $allOrders->where('status', 'Selesai')->count(),
            'kendala' => $allOrders->where('status', 'Kendala Lapangan')->count(),
        ];

        return view('technician.pemasangan', compact('orders', 'counts', 'statusFilter', 'search'));
    }

    /**
     * Update Status & Catatan Pemasangan dari Teknisi
     */
    public function updatePemasanganStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $status = $request->input('status', $order->status);
        $order->status = $status;

        if ($request->filled('ont_sn')) {
            $order->ont_sn = $request->input('ont_sn');
        }

        if ($request->filled('opm_dbm')) {
            $order->opm_dbm = $request->input('opm_dbm');
        }

        if ($request->filled('assigned_odp')) {
            $order->assigned_odp = $request->input('assigned_odp');
        }

        if ($request->filled('technician_notes')) {
            $order->technician_notes = $request->input('technician_notes');
        }

        if ($status === 'Selesai') {
            $order->installed_at = now();
            if (empty($order->technician)) {
                $order->technician = auth()->user()->name;
            }
        } elseif ($status === 'Sedang Dipasang') {
            if (empty($order->technician)) {
                $order->technician = auth()->user()->name;
            }
        }

        $order->save();

        if ($status === 'Selesai') {
            BillingService::generateBillForOrder($order);
        }

        return redirect()->back()->with('success', "Status pemasangan order {$order->order_number} berhasil diperbarui menjadi {$status}.");
    }

    /**
     * Daftar Tiket Gangguan Jaringan (Trouble Tickets)
     */
    public function gangguan(Request $request)
    {
        $statusFilter = $request->input('status', 'all');
        $priorityFilter = $request->input('priority', 'all');
        $search = $request->input('q', '');

        $allTickets = $this->getTroubleTickets();

        $filteredTickets = collect($allTickets);

        if ($statusFilter !== 'all') {
            $filteredTickets = $filteredTickets->where('status', $statusFilter);
        }

        if ($priorityFilter !== 'all') {
            $filteredTickets = $filteredTickets->where('priority', $priorityFilter);
        }

        if (!empty($search)) {
            $filteredTickets = $filteredTickets->filter(function ($t) use ($search) {
                return str_contains(strtolower($t['id']), strtolower($search)) ||
                       str_contains(strtolower($t['customer_name']), strtolower($search)) ||
                       str_contains(strtolower($t['type']), strtolower($search)) ||
                       str_contains(strtolower($t['address']), strtolower($search)) ||
                       str_contains(strtolower($t['odp'] ?? ''), strtolower($search));
            });
        }

        $counts = [
            'all' => count($allTickets),
            'menunggu' => collect($allTickets)->where('status', 'Menunggu Respon')->count(),
            'proses' => collect($allTickets)->where('status', 'Sedang Ditangani')->count(),
            'selesai' => collect($allTickets)->where('status', 'Selesai')->count(),
            'kritis' => collect($allTickets)->where('priority', 'Kritis')->where('status', '!=', 'Selesai')->count(),
        ];

        return view('technician.gangguan', [
            'tickets' => $filteredTickets->values(),
            'counts' => $counts,
            'statusFilter' => $statusFilter,
            'priorityFilter' => $priorityFilter,
            'search' => $search
        ]);
    }

    /**
     * Update Status & Solusi Penanganan Gangguan
     */
    public function updateGangguanStatus(Request $request, $id)
    {
        $allTickets = $this->getTroubleTickets();

        foreach ($allTickets as &$ticket) {
            if ($ticket['id'] === $id) {
                $status = $request->input('status', $ticket['status']);
                $ticket['status'] = $status;
                $ticket['updated_at'] = now()->translatedFormat('d M Y, H:i') . ' WIB';
                $ticket['technician'] = auth()->user()->name;

                if ($request->filled('opm_result')) {
                    $ticket['opm_result'] = $request->input('opm_result');
                }

                if ($request->filled('notes')) {
                    $ticket['notes'] = $request->input('notes');
                }
                break;
            }
        }

        session(['technician_tickets' => $allTickets]);
        Cache::put('trouble_tickets', $allTickets);

        return redirect()->back()->with('success', "Tiket gangguan {$id} berhasil diperbarui.");
    }
}
