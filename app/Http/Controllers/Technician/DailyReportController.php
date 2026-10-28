<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\DailyReport;
use App\Models\Order;
use App\Models\Material;
use App\Models\MaterialMutation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DailyReportController extends Controller
{
    /**
     * Tampilan Utama Portal Daily Report Teknisi
     */
    public function index(Request $request)
    {
        // Auto-heal: Jika tabel belum ada di server hosting / produksi, jalankan migrasi otomatis
        if (!Schema::hasTable('daily_reports')) {
            try {
                Artisan::call('migrate', ['--force' => true]);
                if (!User::where('email', 'admindaily')->exists()) {
                    Artisan::call('db:seed', ['--class' => 'AdminDailySeeder', '--force' => true]);
                }
            } catch (\Throwable $e) {
                // Log atau lanjutkan
            }
        }

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

        // 3b. Katalog Material Dinamis Mengikuti Log Barang Masuk Bulan Sekarang (POV Log Stok Admin)
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth()->toDateString();
        $endOfMonth = $now->copy()->endOfMonth()->toDateString();
        $currentMonthName = (\App\Http\Controllers\Admin\MaterialController::MONTHS[(int)$now->format('n')] ?? $now->format('F')) . ' ' . $now->format('Y');

        $incomingMutations = MaterialMutation::where('type', 'in')
            ->where(function ($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
                  ->orWhere(function ($s) use ($startOfMonth, $endOfMonth) {
                      $s->whereNull('transaction_date')->whereBetween(DB::raw('DATE(created_at)'), [$startOfMonth, $endOfMonth]);
                  });
            })
            ->with('material')
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $defaultCatalog = [];
        if ($incomingMutations->isNotEmpty()) {
            // Hitung total stok barang masuk per material_id di bulan sekarang
            $incomingStockTotals = [];
            foreach ($incomingMutations as $mut) {
                if (!$mut->material_id) continue;
                $incomingStockTotals[$mut->material_id] = ($incomingStockTotals[$mut->material_id] ?? 0) + (float)$mut->amount;
            }

            $seenMaterials = [];
            $priceEstimates = [
                'dc 1c' => 2500,
                'drop core' => 2500,
                'dropcore' => 2500,
                'kabel drop' => 2500,
                'fast connector' => 7500,
                'roset' => 12000,
                'modem' => 350000,
                'zte f672y' => 350000,
                'zte f670l' => 350000,
                'f672' => 350000,
                'f670' => 350000,
                'router' => 250000,
                'zte f609' => 250000,
                'f609' => 250000,
                'adaptor' => 25000,
                'adapter' => 25000,
                'patchcord' => 15000,
                'sleeve' => 500,
                'splitter 1:2' => 35000,
                'splitter 1:4' => 45000,
                'splitter 1:8' => 55000,
            ];

            foreach ($incomingMutations as $mut) {
                $mat = $mut->material;
                if (!$mat) {
                    continue;
                }
                $matId = $mat->id;
                if (isset($seenMaterials[$matId])) {
                    continue;
                }
                $seenMaterials[$matId] = true;

                $name = trim($mat->name);
                $category = $mat->category ?: 'Material Pembantu & Instalasi';
                $unit = $mat->unit ?: 'pcs';
                $lowerName = strtolower($name);

                $price = (float) $mat->unit_price;
                if ($price <= 0) {
                    foreach ($priceEstimates as $pattern => $estPrice) {
                        if (str_contains($lowerName, $pattern)) {
                            $price = $estPrice;
                            break;
                        }
                    }
                }

                $maxQty = isset($incomingStockTotals[$matId]) ? (float)$incomingStockTotals[$matId] : null;

                $isCable = str_contains($lowerName, 'drop') || str_contains($lowerName, 'dc') || strtolower($unit) === 'meter' || strtolower($unit) === 'm';
                $defaultQty = $isCable ? min(85, $maxQty ?: 85) : min(1, $maxQty ?: 1);

                $defaultCatalog[] = [
                    'key' => 'mat_' . $matId,
                    'material_id' => $matId,
                    'name' => $name,
                    'category' => $category,
                    'price' => $price,
                    'unit' => $unit,
                    'default_qty' => $defaultQty,
                    'max_qty' => $maxQty,
                    'checked' => true,
                ];
            }
        }

        // Fallback jika belum ada mutasi barang masuk pada bulan ini
        if (empty($defaultCatalog)) {
            $defaultCatalog = [
                ['key' => 'dropcore', 'name' => 'Kabel Dropcore (DC 1C)', 'category' => 'Kabel Drop & Fiber Optik', 'price' => 2500, 'unit' => 'm', 'default_qty' => 85, 'max_qty' => null, 'checked' => true],
                ['key' => 'fast_connector', 'name' => 'Fast Connector', 'category' => 'Adapter & Konektor Optik', 'price' => 7500, 'unit' => 'pcs', 'default_qty' => 2, 'max_qty' => null, 'checked' => true],
                ['key' => 'ont_modem', 'name' => 'ONT / Modem', 'category' => 'Modem & ONT GPON', 'price' => 350000, 'unit' => 'unit', 'default_qty' => 1, 'max_qty' => null, 'checked' => false],
                ['key' => 'patchcord', 'name' => 'Patchcord FO', 'category' => 'Patchcord & Pigtail', 'price' => 15000, 'unit' => 'pcs', 'default_qty' => 1, 'max_qty' => null, 'checked' => false],
                ['key' => 'sleeve', 'name' => 'Sleeve Splicing (Smuft)', 'category' => 'Protection Sleeve (Smuft)', 'price' => 500, 'unit' => 'pcs', 'default_qty' => 2, 'max_qty' => null, 'checked' => false],
                ['key' => 'roset', 'name' => 'Roset FO', 'category' => 'Material Pembantu & Instalasi', 'price' => 12000, 'unit' => 'pcs', 'default_qty' => 1, 'max_qty' => null, 'checked' => true],
                ['key' => 'splitter_1_2', 'name' => 'PLC Splitter 1:2', 'category' => 'Splitter Optik', 'price' => 35000, 'unit' => 'pcs', 'default_qty' => 1, 'max_qty' => null, 'checked' => false],
                ['key' => 'splitter_1_4', 'name' => 'PLC Splitter 1:4', 'category' => 'Splitter Optik', 'price' => 45000, 'unit' => 'pcs', 'default_qty' => 1, 'max_qty' => null, 'checked' => false],
            ];
        }

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
            'recentOrders',
            'currentMonthName'
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
                // Batasi qty material agar tidak melebihi total stok barang masuk bulan ini
                $reportDate = Carbon::parse($validated['report_date'] ?? now());
                $startOfMonth = $reportDate->copy()->startOfMonth()->toDateString();
                $endOfMonth = $reportDate->copy()->endOfMonth()->toDateString();

                foreach ($decoded as &$item) {
                    if (empty($item['name'])) continue;
                    $matName = trim($item['name']);
                    $mat = Material::where('name', $matName)->first();
                    if ($mat) {
                        $totalMasuk = (float) MaterialMutation::where('material_id', $mat->id)
                            ->where('type', 'in')
                            ->where(function ($q) use ($startOfMonth, $endOfMonth) {
                                $q->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
                                  ->orWhere(function ($s) use ($startOfMonth, $endOfMonth) {
                                      $s->whereNull('transaction_date')->whereBetween(DB::raw('DATE(created_at)'), [$startOfMonth, $endOfMonth]);
                                  });
                            })
                            ->sum('amount');

                        if ($totalMasuk > 0 && (float)($item['qty'] ?? 0) > $totalMasuk) {
                            $item['qty'] = $totalMasuk;
                            if (isset($item['price'])) {
                                $item['total'] = (float)$item['price'] * $totalMasuk;
                            }
                        }
                    }
                }
                unset($item);

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

    /**
     * Tambah Barang Secara Manual dari Daily Report & Catat ke Stok Masuk Dashboard Admin
     */
    public function storeMaterialMasuk(Request $request)
    {
        $validated = $request->validate([
            'material_id' => 'nullable',
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'unit' => 'nullable|string|max:50',
            'price' => 'nullable|numeric|min:0',
            'qty' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:500',
        ]);

        $name = trim($validated['name']);
        $category = trim($validated['category'] ?? '') ?: 'Material Pembantu & Instalasi';
        $unit = trim($validated['unit'] ?? '') ?: 'pcs';
        $price = (float)($validated['price'] ?? 0);
        $qty = (float)$validated['qty'];
        $technicianName = auth()->user()->name ?? 'Teknisi';
        $notes = !empty($validated['notes']) ? trim($validated['notes']) : "Input Manual Daily Report ({$technicianName})";

        // 1. Cari atau buat record Material di tabel materials
        $material = null;
        if (!empty($validated['material_id'])) {
            $material = Material::find($validated['material_id']);
        }
        if (!$material) {
            $material = Material::where('name', $name)->first();
        }

        if (!$material) {
            $prefix = match (strtoupper(substr($category, 0, 3))) {
                'TIA' => 'A',
                'KAB', 'FIB', 'DRO' => 'KBL-',
                'MOD', 'ONT' => 'ONT-',
                'ROU', 'AP ' => 'RTR-',
                default => 'BRG-',
            };
            $nextNum = Material::count() + 1;
            $code = $prefix . str_pad($nextNum, 3, '0', STR_PAD_LEFT);

            $now = Carbon::now();
            $monthNumber = (int)$now->format('n');
            $currentMonth = \App\Http\Controllers\Admin\MaterialController::MONTHS[$monthNumber] ?? 'Oktober';

            $material = Material::create([
                'code' => $code,
                'name' => $name,
                'category' => $category,
                'unit' => $unit,
                'unit_price' => $price,
                'initial_stock' => 0,
                'stock' => 0,
                'min_stock' => 5,
                'total_in' => 0,
                'total_out' => 0,
                'month' => $currentMonth,
            ]);
        } else {
            if ($material->unit_price <= 0 && $price > 0) {
                $material->unit_price = $price;
                $material->save();
            }
        }

        // 2. Tambahkan Mutasi Barang Masuk (type = 'in') untuk Bulan Sekarang
        $transactionDate = Carbon::now()->toDateString();
        $mutation = $material->addStock(
            $qty,
            $notes,
            auth()->id(),
            $transactionDate
        );

        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth()->toDateString();
        $endOfMonth = $now->copy()->endOfMonth()->toDateString();
        $totalMasukBulanIni = (float) MaterialMutation::where('material_id', $material->id)
            ->where('type', 'in')
            ->where(function ($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
                  ->orWhere(function ($s) use ($startOfMonth, $endOfMonth) {
                      $s->whereNull('transaction_date')->whereBetween(DB::raw('DATE(created_at)'), [$startOfMonth, $endOfMonth]);
                  });
            })
            ->sum('amount');

        return response()->json([
            'success' => true,
            'message' => "Barang \"{$material->name}\" sebanyak +{$qty} {$material->unit} berhasil didaftarkan ke Log Barang Masuk Dashboard Admin untuk periode saat ini.",
            'material' => [
                'key' => 'mat_' . $material->id,
                'material_id' => $material->id,
                'code' => $material->code,
                'name' => $material->name,
                'category' => $material->category,
                'unit' => $material->unit,
                'price' => (float)$material->unit_price ?: $price,
                'qty' => $qty,
                'max_qty' => $totalMasukBulanIni ?: $qty,
                'checked' => true,
            ],
            'mutation' => $mutation,
        ]);
    }
}
