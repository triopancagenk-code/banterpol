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
        $user = auth()->user();
        $isTechnician = $user && ($user->role === 'technician' || $user->role === 'teknisi');

        $activeStatuses = ['Menunggu Konfirmasi', 'Jadwal Pemasangan', 'Jadwal Teknisi', 'Sedang Dipasang', 'Kendala Lapangan'];
        $baseOrdersQuery = Order::where('order_number', 'not like', 'PLG-%');

        // Orders relevant for metrics: if user is field technician, focus on their assigned tasks
        if ($isTechnician && !$user->isAdmin()) {
            $relevantOrders = (clone $baseOrdersQuery)->forTechnician($user)->get();
        } else {
            $relevantOrders = (clone $baseOrdersQuery)->get();
        }

        $tickets = $this->getTroubleTickets();

        $pendingInstallations = $relevantOrders->whereIn('status', $activeStatuses)->count();
        $completedInstallations = $relevantOrders->where('status', 'Selesai')->count();

        // Metrik Gangguan
        $activeTroubles = collect($tickets)->whereIn('status', ['Menunggu Respon', 'Sedang Ditangani'])->count();
        $criticalTroubles = collect($tickets)->where('priority', 'Kritis')->where('status', '!=', 'Selesai')->count();

        // Tugas Instalasi Aktif Terbaru
        $activeOrdersQuery = Order::where('order_number', 'not like', 'PLG-%')
            ->whereIn('status', $activeStatuses);

        if ($isTechnician && !$user->isAdmin()) {
            $activeOrdersQuery->forTechnician($user);
        }

        $activeOrders = $activeOrdersQuery->latest()->take(6)->get();

        // Tiket yang baru ditugaskan ke akun teknisi ini
        $newAssignedOrders = $activeOrders->filter(function ($ord) {
            return $ord->assigned_at && $ord->assigned_at->gt(now()->subHours(48));
        });

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
            'my_assigned_count' => $pendingInstallations,
        ];

        return view('technician.dashboard', compact('stats', 'activeOrders', 'urgentTickets', 'newAssignedOrders', 'isTechnician'));
    }

    /**
     * Daftar Tiket Pemasangan Baru (Instalasi Pelanggan)
     */
    public function pemasangan(Request $request)
    {
        $user = auth()->user();
        $isTechnician = $user && ($user->role === 'technician' || $user->role === 'teknisi');

        // Scope filter: 'my' = Tugas Saya, 'all' = Semua Tim Lapangan
        // Default ke 'my' untuk akun teknisi, 'all' untuk admin
        $scope = $request->input('scope', ($isTechnician && !$user->isAdmin()) ? 'my' : 'all');
        $statusFilter = $request->input('status', 'all');
        $search = $request->input('q', '');

        $query = Order::where('order_number', 'not like', 'PLG-%')->latest();

        if ($scope === 'my' && $user) {
            $query->forTechnician($user);
        }

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
                  ->orWhere('technician', 'like', "%{$search}%")
                  ->orWhere('assigned_odp', 'like', "%{$search}%");
            });
        }

        $orders = $query->paginate(15)->withQueryString();

        $scopedBase = Order::where('order_number', 'not like', 'PLG-%');
        if ($scope === 'my' && $user) {
            $scopedBase->forTechnician($user);
        }
        $scopedOrders = $scopedBase->get();

        $myCount = Order::where('order_number', 'not like', 'PLG-%')->forTechnician($user)->count();
        $allCount = Order::where('order_number', 'not like', 'PLG-%')->count();

        $counts = [
            'all' => $scopedOrders->count(),
            'jadwal' => $scopedOrders->whereIn('status', ['Menunggu Konfirmasi', 'Jadwal Pemasangan', 'Jadwal Teknisi'])->count(),
            'proses' => $scopedOrders->where('status', 'Sedang Dipasang')->count(),
            'selesai' => $scopedOrders->where('status', 'Selesai')->count(),
            'kendala' => $scopedOrders->where('status', 'Kendala Lapangan')->count(),
            'my_total' => $myCount,
            'all_total' => $allCount,
        ];

        return view('technician.pemasangan', compact('orders', 'counts', 'statusFilter', 'search', 'scope', 'isTechnician'));
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
                $order->technician_id = auth()->id();
            }
            if (empty($order->village) && !empty($order->address)) {
                $order->village = \App\Services\CustomerImportService::resolveVillage(null, null, $order->address);
            }
        } elseif ($status === 'Sedang Dipasang') {
            if (empty($order->technician)) {
                $order->technician = auth()->user()->name;
                $order->technician_id = auth()->id();
            }
        }

        $order->save();

        if ($status === 'Selesai') {
            BillingService::generateBillForOrder($order);
        }

        $msg = ($status === 'Selesai')
            ? "Status pemasangan order {$order->order_number} berhasil diselesaikan dan resmi masuk ke Data Pelanggan."
            : "Status pemasangan order {$order->order_number} berhasil diperbarui menjadi {$status}.";

        return redirect()->back()->with('success', $msg);
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
