<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\DailyReport;
use App\Models\Order;
use App\Models\Material;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DailyReportController extends Controller
{
    /**
     * Tampilan Utama Portal Daily Report Teknisi
     */
    public function index(Request $request)
    {
        $search = $request->input('q', '');
        $jobTypeFilter = $request->input('job_type', 'all');
        $tab = $request->input('tab', 'jobs'); // 'jobs' or 'cash'

        // 1. KPI Metrics
        $today = now()->toDateString();
        $totalJobsToday = DailyReport::whereDate('report_date', $today)->count();
        $cashCollectedToday = (float) DailyReport::whereDate('report_date', $today)
            ->whereIn('payment_method', ['Tunai (Cash)', 'QRIS', 'Transfer Bank'])
            ->sum('cash_amount');
        $pendingDepositCount = DailyReport::where('cash_deposit_status', 'Belum Disetor')
            ->where('cash_amount', '>', 0)
            ->count();
        $materialCostToday = (float) DailyReport::whereDate('report_date', $today)
            ->sum('material_total_cost');

        // 2. Query Log Pekerjaan & Kas
        $query = DailyReport::query()->with('order');

        if (!empty($search)) {
            $s = trim($search);
            $query->where(function ($q) use ($s) {
                $q->where('customer_name', 'like', "%{$s}%")
                  ->orWhere('pppoe_username', 'like', "%{$s}%")
                  ->orWhere('customer_phone', 'like', "%{$s}%")
                  ->orWhere('customer_address', 'like', "%{$s}%")
                  ->orWhere('technician_name', 'like', "%{$s}%")
                  ->orWhere('partner_company', 'like', "%{$s}%")
                  ->orWhere('odp_port', 'like', "%{$s}%")
                  ->orWhere('ont_sn', 'like', "%{$s}%");
            });
        }

        if ($jobTypeFilter !== 'all') {
            $query->where('job_type', $jobTypeFilter);
        }

        $reports = (clone $query)->orderBy('report_date', 'desc')->latest('id')->paginate(15, ['*'], 'page_jobs')->withQueryString();
        $cashTransactions = (clone $query)->where('cash_amount', '>', 0)->orderBy('report_date', 'desc')->latest('id')->paginate(15, ['*'], 'page_cash')->withQueryString();

        // 3. Data Master Katalog Material & Stok Barang (POV Admin)
        $stockCategories = Material::select('category')->distinct()->orderBy('category')->pluck('category')->filter()->values();
        if ($stockCategories->isEmpty()) {
            $stockCategories = collect([
                'Adapter & Konektor Optik',
                'Aksesoris & Bracket',
                'Clamp & Aksesoris',
                'Joint Closure',
                'Kabel Drop & Fiber Optik',
                'Kabel LAN & UTP',
                'Kabel Ties',
                'Material Optik & Jaringan',
                'Material Pembantu & Instalasi',
                'Media Converter (HTB)',
                'Modem & ONT GPON',
                'ODP & Box Pasif',
                'Patchcord & Pigtail',
                'Perangkat Jaringan & Switch',
                'Power & Adaptor',
                'Protection Sleeve (Smuft)',
                'Router & AP Wireless',
                'Splitter Optik',
                'Tiang & Bracket',
            ]);
        }

        $stockMaterials = Material::select('id', 'code', 'name', 'category', 'unit', 'unit_price', 'stock')
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        $defaultCatalog = [
            ['key' => 'dropcore', 'name' => 'Kabel Dropcore (DC 1C)', 'category' => 'Kabel Drop & Fiber Optik', 'price' => 2500, 'unit' => 'm', 'default_qty' => 85, 'checked' => true],
            ['key' => 'fast_connector', 'name' => 'Fast Connector', 'category' => 'Adapter & Konektor Optik', 'price' => 7500, 'unit' => 'pcs', 'default_qty' => 2, 'checked' => true],
            ['key' => 'ont_modem', 'name' => 'ONT / Modem', 'category' => 'Modem & ONT GPON', 'price' => 350000, 'unit' => 'unit', 'default_qty' => 1, 'checked' => false],
            ['key' => 'patchcord', 'name' => 'Patchcord FO', 'category' => 'Patchcord & Pigtail', 'price' => 15000, 'unit' => 'pcs', 'default_qty' => 1, 'checked' => false],
            ['key' => 'sleeve', 'name' => 'Sleeve Splicing (Smuft)', 'category' => 'Protection Sleeve (Smuft)', 'price' => 500, 'unit' => 'pcs', 'default_qty' => 2, 'checked' => false],
            ['key' => 'roset', 'name' => 'Roset FO', 'category' => 'Material Pembantu & Instalasi', 'price' => 12000, 'unit' => 'pcs', 'default_qty' => 1, 'checked' => true],
            ['key' => 'splitter_1_2', 'name' => 'PLC Splitter 1:2', 'category' => 'Splitter Optik', 'price' => 35000, 'unit' => 'pcs', 'default_qty' => 1, 'checked' => false],
            ['key' => 'splitter_1_4', 'name' => 'PLC Splitter 1:4', 'category' => 'Splitter Optik', 'price' => 45000, 'unit' => 'pcs', 'default_qty' => 1, 'checked' => false],
        ];

        if (!auth()->user()->isTechnician() && !auth()->user()->isAdmin() && strtolower(auth()->user()->email) !== 'admindaily') {
            abort(403, 'Akses ditolak. Halaman ini khusus untuk Tim Lapangan dan Administrator Banterpool.');
        }

        // 4. Partner Companies & Job Types
        $partners = [
            'ThreeFive',
            'INKA',
            'BANTERPOL',
            'TRIPANCA',
            'DANA ID',
            'OBORNET',
            'JANE HOME',
            'KIDANG MAS',
            'BUMDES PERNASIDI',
            'TX ID',
            'BUMDES CIPETE',
            'MAHSUS',
            'BERKAH JAYA',
        ];

        $jobTypes = [
            'Pasang Baru (PSB / Installation)',
            'Maintenance / Perbaikan Gangguan',
            'Dismantle / Cabut Perangkat',
            'Survey & Penarikan Kabel FO',
            'Relokasi Titik Pasang',
        ];

        // 5. Active orders for quick autofill
        $recentOrders = Order::latest('id')->take(20)->get(['id', 'order_number', 'customer_name', 'customer_phone', 'address', 'pppoe', 'assigned_odp', 'ont_sn', 'opm_dbm']);

        return view('technician.daily-report', compact(
            'reports',
            'cashTransactions',
            'totalJobsToday',
            'cashCollectedToday',
            'pendingDepositCount',
            'materialCostToday',
            'search',
            'jobTypeFilter',
            'tab',
            'defaultCatalog',
            'stockCategories',
            'stockMaterials',
            'partners',
            'jobTypes',
            'recentOrders'
        ));
    }

    /**
     * Simpan Laporan Harian & Pembukuan Kas Baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'report_date' => 'required|date',
            'technician_name' => 'required|string|max:100',
            'partner_company' => 'required|string|max:150',
            'job_type' => 'required|string|max:100',
            'customer_name' => 'required|string|max:150',
            'customer_phone' => 'nullable|string|max:30',
            'customer_address' => 'nullable|string|max:500',
            'pppoe_username' => 'nullable|string|max:100',
            'odp_port' => 'nullable|string|max:100',
            'rx_power' => 'nullable|string|max:50',
            'ont_sn' => 'nullable|string|max:100',
            'materials_json' => 'nullable|string',
            'additional_materials' => 'nullable|string|max:500',
            'material_total_cost' => 'nullable|numeric|min:0',
            'transaction_type' => 'required|string|max:100',
            'cash_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'required|string|max:50',
            'cash_deposit_status' => 'required|string|max:50',
            'order_id' => 'nullable|exists:orders,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $materialsArray = [];
        if (!empty($validated['materials_json'])) {
            $decoded = json_decode($validated['materials_json'], true);
            if (is_array($decoded)) {
                $materialsArray = $decoded;
            }
        }

        $report = DailyReport::create([
            'report_date' => $validated['report_date'],
            'technician_name' => $validated['technician_name'],
            'technician_id' => auth()->id(),
            'partner_company' => $validated['partner_company'],
            'job_type' => $validated['job_type'],
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $validated['customer_phone'] ?? null,
            'customer_address' => $validated['customer_address'] ?? null,
            'pppoe_username' => $validated['pppoe_username'] ?? null,
            'odp_port' => $validated['odp_port'] ?? null,
            'rx_power' => $validated['rx_power'] ?? null,
            'ont_sn' => $validated['ont_sn'] ?? null,
            'materials_json' => $materialsArray,
            'additional_materials' => $validated['additional_materials'] ?? null,
            'material_total_cost' => (float)($validated['material_total_cost'] ?? 0),
            'transaction_type' => $validated['transaction_type'],
            'cash_amount' => (float)($validated['cash_amount'] ?? 0),
            'payment_method' => $validated['payment_method'],
            'cash_deposit_status' => $validated['cash_deposit_status'],
            'order_id' => $validated['order_id'] ?? null,
            'synced_to_sheets' => true, // marked as synced
            'synced_at' => now(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('daily-report.index')
            ->with('success', "Laporan {$report->job_type} untuk {$report->customer_name} berhasil disimpan dan disinkronkan!");
    }

    /**
     * Konfirmasi Setor Kas Lapangan ke Admin
     */
    public function setorKas($id)
    {
        $report = DailyReport::findOrFail($id);
        $report->cash_deposit_status = 'Sudah Disetor';
        $report->save();

        return redirect()->back()->with('success', "Kas sebesar {$report->formatted_cash} untuk {$report->customer_name} berhasil ditandai Sudah Disetor.");
    }

    /**
     * Hapus Laporan Harian
     */
    public function destroy($id)
    {
        $report = DailyReport::findOrFail($id);
        $name = $report->customer_name;
        $report->delete();

        return redirect()->back()->with('success', "Laporan untuk {$name} berhasil dihapus.");
    }

    /**
     * Export Log Daily Report ke CSV / Spreadsheet
     */
    public function export(Request $request): StreamedResponse
    {
        $reports = DailyReport::orderBy('report_date', 'desc')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="daily_report_teknisi_' . date('Ymd_His') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($reports) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel UTF-8

            // Header baris CSV
            fputcsv($handle, [
                'Tanggal',
                'Nama Teknisi',
                'Perusahaan Mitra',
                'Tipe Pekerjaan',
                'Nama Pelanggan',
                'No WhatsApp',
                'Alamat',
                'PPPoE',
                'ODP & Port',
                'Rx Power (dBm)',
                'SN ONT',
                'Material Digunakan',
                'Total Biaya Material (Rp)',
                'Jenis Transaksi',
                'Nominal Kas (Rp)',
                'Metode Bayar',
                'Status Setor',
            ]);

            foreach ($reports as $r) {
                fputcsv($handle, [
                    $r->report_date ? $r->report_date->format('Y-m-d') : '',
                    $r->technician_name,
                    $r->partner_company,
                    $r->job_type,
                    $r->customer_name,
                    $r->customer_phone,
                    $r->customer_address,
                    $r->pppoe_username,
                    $r->odp_port,
                    $r->rx_power,
                    $r->ont_sn,
                    $r->materials_summary_text,
                    $r->material_total_cost,
                    $r->transaction_type,
                    $r->cash_amount,
                    $r->payment_method,
                    $r->cash_deposit_status,
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
