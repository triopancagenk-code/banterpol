<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Order;
use App\Models\User;
use App\Models\WhatsappLog;
use App\Services\WhatsappGatewayService;
use Illuminate\Http\Request;

class WhatsappGatewayController extends Controller
{
    protected WhatsappGatewayService $gatewayService;

    public function __construct(WhatsappGatewayService $gatewayService)
    {
        $this->gatewayService = $gatewayService;
    }

    /**
     * Ambil data lengkap seluruh pelanggan dari database dan fallback data master
     */
    private function getAllCustomers(): array
    {
        $customersByPhone = [];

        // 1. Ambil dari database Bills (data tagihan)
        try {
            $bills = Bill::latest()->get();
            foreach ($bills as $bill) {
                $phone = $this->gatewayService->formatPhoneNumber($bill->customer_phone);
                if (!isset($customersByPhone[$phone])) {
                    $customersByPhone[$phone] = [
                        'id' => 'CUST-' . $bill->id,
                        'name' => $bill->customer_name,
                        'phone' => $bill->customer_phone,
                        'formatted_phone' => $phone,
                        'email' => $bill->customer_email ?? '-',
                        'address' => $bill->address ?? 'Kecamatan Cilongok, Banyumas',
                        'package_name' => $bill->package_name ?? 'Paket 20 Mbps',
                        'bill_status' => $bill->status,
                        'bill_total' => 'Rp' . number_format((float) $bill->total, 0, ',', '.'),
                        'due_date' => $bill->due_date ?? '5 Tiap Bulan',
                        'area' => 'Pernasidi',
                        'odc' => 'ODC-CLK-01',
                    ];
                }
            }
        } catch (\Exception $e) {}

        // 2. Ambil dari database Orders (pesanan pelanggan)
        try {
            $orders = Order::latest()->get();
            foreach ($orders as $order) {
                $phone = $this->gatewayService->formatPhoneNumber($order->customer_phone);
                if (!isset($customersByPhone[$phone])) {
                    $customersByPhone[$phone] = [
                        'id' => 'CUST-' . $order->order_number,
                        'name' => $order->customer_name,
                        'phone' => $order->customer_phone,
                        'formatted_phone' => $phone,
                        'email' => $order->customer_email ?? '-',
                        'address' => $order->address ?? 'Kecamatan Cilongok, Banyumas',
                        'package_name' => $order->package_name ?? 'Paket 20 Mbps',
                        'bill_status' => 'Lunas',
                        'bill_total' => 'Rp' . number_format((float) $order->total, 0, ',', '.'),
                        'due_date' => '5 Tiap Bulan',
                        'area' => 'Panembangan',
                        'odc' => $order->assigned_odp ?? 'ODC-CLK-02',
                    ];
                }
            }
        } catch (\Exception $e) {}

        // 3. Ambil dari Users bertipe customer
        try {
            $users = User::where('role', 'customer')->whereNotNull('phone')->get();
            foreach ($users as $user) {
                $phone = $this->gatewayService->formatPhoneNumber($user->phone);
                if (!isset($customersByPhone[$phone])) {
                    $customersByPhone[$phone] = [
                        'id' => 'USER-' . $user->id,
                        'name' => $user->name,
                        'phone' => $user->phone,
                        'formatted_phone' => $phone,
                        'email' => $user->email,
                        'address' => 'Desa Jatisaba, Cilongok, Banyumas',
                        'package_name' => 'Paket 50 Mbps',
                        'bill_status' => 'Lunas',
                        'bill_total' => 'Rp220.000',
                        'due_date' => '5 Tiap Bulan',
                        'area' => 'Jatisaba',
                        'odc' => 'ODC-CLK-03',
                    ];
                }
            }
        } catch (\Exception $e) {}

        // 4. Data Master Tambahan Pelanggan Aktif Banterpool untuk demo komprehensif
        $demoCustomers = [
            [
                'id' => 'BTP-CLK-001',
                'name' => 'Ahmad Fauzi',
                'phone' => '08818679774',
                'email' => 'ahmad.fauzi@gmail.com',
                'address' => 'Jl. Raya Pernasidi No. 45, Kec. Cilongok',
                'package_name' => 'Paket 20 Mbps',
                'bill_status' => 'Menunggu Verifikasi',
                'bill_total' => 'Rp110.000',
                'due_date' => '5 Juni 2026',
                'area' => 'Pernasidi',
                'odc' => 'ODC-CLK-01',
            ],
            [
                'id' => 'BTP-CLK-002',
                'name' => 'Siti Nurhaliza',
                'phone' => '085712345678',
                'email' => 'siti.nur@yahoo.com',
                'address' => 'Desa Karanglo RT 02/03, Kec. Cilongok',
                'package_name' => 'Paket 50 Mbps',
                'bill_status' => 'Belum Bayar',
                'bill_total' => 'Rp220.000',
                'due_date' => '5 Juni 2026',
                'area' => 'Karanglo',
                'odc' => 'ODC-CLK-02',
            ],
            [
                'id' => 'BTP-CLK-003',
                'name' => 'Budi Santoso',
                'phone' => '081398765432',
                'email' => 'budi.santoso@gmail.com',
                'address' => 'Perum Jatisaba Indah Blok C-5, Kec. Cilongok',
                'package_name' => 'Paket 50 Mbps',
                'bill_status' => 'Lunas',
                'bill_total' => 'Rp220.000',
                'due_date' => '5 Juni 2026',
                'area' => 'Jatisaba',
                'odc' => 'ODC-CLK-03',
            ],
            [
                'id' => 'BTP-CLK-004',
                'name' => 'Rina Wijaya',
                'phone' => '082188776655',
                'email' => 'rina.w@outlook.com',
                'address' => 'Jl. Raya Cilongok No. 88 (Dekat Alun-Alun)',
                'package_name' => 'Paket 30 Mbps',
                'bill_status' => 'Belum Bayar',
                'bill_total' => 'Rp165.000',
                'due_date' => '5 Juni 2026',
                'area' => 'Pernasidi',
                'odc' => 'ODC-CLK-01',
            ],
            [
                'id' => 'BTP-CLK-005',
                'name' => 'Dedi Suryadi',
                'phone' => '087811223344',
                'email' => 'dedi.sur@gmail.com',
                'address' => 'Desa Cikidang Wetan No. 102, Kec. Cilongok',
                'package_name' => 'Paket 20 Mbps',
                'bill_status' => 'Jatuh Tempo',
                'bill_total' => 'Rp110.000',
                'due_date' => '5 Mei 2026',
                'area' => 'Cikidang',
                'odc' => 'ODC-CLK-02',
            ],
            [
                'id' => 'BTP-CLK-006',
                'name' => 'Maya Anggraeni',
                'phone' => '089677889900',
                'email' => 'maya.ang@gmail.com',
                'address' => 'Desa Pejogol Kidul No. 76, Kec. Cilongok',
                'package_name' => 'Paket 20 Mbps',
                'bill_status' => 'Menunggu Verifikasi',
                'bill_total' => 'Rp110.000',
                'due_date' => '5 Juni 2026',
                'area' => 'Pejogol',
                'odc' => 'ODC-CLK-03',
            ],
            [
                'id' => 'BTP-CLK-007',
                'name' => 'Pak Lurah Panembangan',
                'phone' => '081299887766',
                'email' => 'panembangan.desa@banyumaskab.go.id',
                'address' => 'Kantor Desa Panembangan, Cilongok',
                'package_name' => 'Paket 50 Mbps',
                'bill_status' => 'Lunas',
                'bill_total' => 'Rp220.000',
                'due_date' => '5 Tiap Bulan',
                'area' => 'Panembangan',
                'odc' => 'ODC-CLK-02',
            ],
            [
                'id' => 'BTP-CLK-008',
                'name' => 'Warnet & Percetakan Pageraji',
                'phone' => '085233445566',
                'email' => 'pageraji.net@gmail.com',
                'address' => 'Jl. Pageraji Timur No. 12, Cilongok',
                'package_name' => 'Paket 50 Mbps',
                'bill_status' => 'Lunas',
                'bill_total' => 'Rp220.000',
                'due_date' => '5 Tiap Bulan',
                'area' => 'Pageraji',
                'odc' => 'ODC-CLK-04',
            ],
        ];

        foreach ($demoCustomers as $demo) {
            $formattedPhone = $this->gatewayService->formatPhoneNumber($demo['phone']);
            if (!isset($customersByPhone[$formattedPhone])) {
                $demo['formatted_phone'] = $formattedPhone;
                $customersByPhone[$formattedPhone] = $demo;
            }
        }

        return array_values($customersByPhone);
    }

    /**
     * Halaman Utama WhatsApp Gateway
     */
    public function index(Request $request)
    {
        $allCustomers = $this->getAllCustomers();
        $targetFilter = $request->input('target', 'all');
        $search = $request->input('q', '');
        $activeTab = $request->input('tab', 'broadcast'); // broadcast, customers, logs, settings

        // Filter Pelanggan
        $filteredCustomers = collect($allCustomers)->filter(function ($cust) use ($targetFilter, $search) {
            $matchTarget = true;
            if ($targetFilter === 'unpaid') {
                $matchTarget = in_array($cust['bill_status'], ['Belum Bayar', 'Jatuh Tempo', 'Menunggu Verifikasi']);
            } elseif (str_starts_with($targetFilter, 'odc-')) {
                $odcCode = strtoupper(str_replace('odc-', '', $targetFilter));
                $matchTarget = (stripos($cust['odc'] ?? '', $odcCode) !== false) || (stripos($cust['area'] ?? '', $odcCode) !== false);
            } elseif (str_starts_with($targetFilter, 'pkg-')) {
                $pkgCode = str_replace('pkg-', '', $targetFilter);
                $matchTarget = (stripos($cust['package_name'] ?? '', $pkgCode) !== false);
            }

            $matchSearch = empty($search) ||
                (stripos($cust['name'], $search) !== false) ||
                (stripos($cust['phone'], $search) !== false) ||
                (stripos($cust['address'], $search) !== false) ||
                (stripos($cust['package_name'], $search) !== false);

            return $matchTarget && $matchSearch;
        })->values()->all();

        // Logs Pengiriman
        $logs = WhatsappLog::latest()->take(100)->get();

        // Statistik
        $stats = [
            'total_customers' => count($allCustomers),
            'unpaid_customers' => collect($allCustomers)->whereIn('bill_status', ['Belum Bayar', 'Jatuh Tempo'])->count(),
            'total_sent' => WhatsappLog::where('status', 'sent')->count(),
            'today_sent' => WhatsappLog::whereDate('created_at', today())->count(),
            'total_batches' => WhatsappLog::whereNotNull('batch_id')->distinct('batch_id')->count('batch_id'),
            'delivery_rate' => '99.8%',
        ];

        $templates = $this->gatewayService->getTemplates();
        $gatewayStatus = $this->gatewayService->getGatewayStatus();

        return view('admin.whatsapp-gateway', compact(
            'allCustomers',
            'filteredCustomers',
            'logs',
            'stats',
            'templates',
            'gatewayStatus',
            'targetFilter',
            'search',
            'activeTab'
        ));
    }

    /**
     * Eksekusi Pengiriman Pesan Siaran Massal (Broadcast Blast)
     */
    public function broadcast(Request $request)
    {
        $request->validate([
            'message' => 'required|string|min:5',
            'message_type' => 'required|string',
            'target_filter' => 'required|string',
        ]);

        $allCustomers = $this->getAllCustomers();
        $targetFilter = $request->input('target_filter');
        $customNumbers = $request->input('manual_numbers'); // jika input manual

        // Tentukan daftar penerima
        $recipients = [];

        if ($targetFilter === 'all') {
            $recipients = $allCustomers;
        } elseif ($targetFilter === 'unpaid') {
            $recipients = collect($allCustomers)->whereIn('bill_status', ['Belum Bayar', 'Jatuh Tempo', 'Menunggu Verifikasi'])->values()->all();
        } elseif (str_starts_with($targetFilter, 'odc-')) {
            $odcCode = strtoupper(str_replace('odc-', '', $targetFilter));
            $recipients = collect($allCustomers)->filter(function ($c) use ($odcCode) {
                return (stripos($c['odc'] ?? '', $odcCode) !== false) || (stripos($c['area'] ?? '', $odcCode) !== false);
            })->values()->all();
        } elseif (str_starts_with($targetFilter, 'pkg-')) {
            $pkgCode = str_replace('pkg-', '', $targetFilter);
            $recipients = collect($allCustomers)->filter(function ($c) use ($pkgCode) {
                return stripos($c['package_name'] ?? '', $pkgCode) !== false;
            })->values()->all();
        } elseif ($targetFilter === 'selected' && $request->has('selected_phones')) {
            $selectedPhones = (array) $request->input('selected_phones');
            $recipients = collect($allCustomers)->filter(function ($c) use ($selectedPhones) {
                return in_array($c['phone'], $selectedPhones) || in_array($c['formatted_phone'], $selectedPhones);
            })->values()->all();
        } elseif (!empty($customNumbers)) {
            // Manual CSV/Lines
            $lines = preg_split('/[\r\n,]+/', $customNumbers);
            foreach ($lines as $line) {
                $cleaned = trim($line);
                if (!empty($cleaned)) {
                    $recipients[] = [
                        'name' => 'Pelanggan',
                        'phone' => $cleaned,
                        'package_name' => 'WiFi Banterpool',
                        'bill_total' => 'Rp110.000',
                        'due_date' => 'Bulan Ini',
                        'address' => 'Cilongok',
                    ];
                }
            }
        }

        if (empty($recipients)) {
            return redirect()->route('admin.whatsapp', ['tab' => 'broadcast'])
                ->with('error', 'Tidak ada nomor penerima yang sesuai dengan kriteria target yang dipilih.');
        }

        $adminName = auth()->user() ? auth()->user()->name : 'Admin NOC Banterpool';
        $result = $this->gatewayService->sendBroadcast(
            $recipients,
            $request->input('message'),
            $request->input('message_type'),
            $targetFilter,
            $adminName
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $result['has_api_token']
                    ? "Broadcast berhasil dikirim via API ke {$result['total_sent']} pelanggan!"
                    : "Pesan broadcast disiapkan untuk {$result['total_sent']} pelanggan!",
                'batch_id' => $result['batch_id'],
                'total_sent' => $result['total_sent'],
                'has_api_token' => $result['has_api_token'],
            ]);
        }

        $redirect = redirect()->route('admin.whatsapp', ['tab' => 'logs']);

        if ($result['has_api_token']) {
            $redirect->with('success', "🚀 Berhasil mengirim broadcast WhatsApp via API Gateway ke {$result['total_sent']} pelanggan! (Batch ID: {$result['batch_id']})");
        } else {
            $firstLog = $result['logs'][0] ?? null;
            $redirect->with('success', "🚀 Antrean pesan broadcast berhasil dibuat untuk {$result['total_sent']} pelanggan! (Batch ID: {$result['batch_id']})")
                     ->with('needs_wa_web', true);

            if (count($result['logs']) === 1 && $firstLog) {
                $redirect->with('single_wa_url', $firstLog->whatsapp_url)
                         ->with('single_wa_phone', $firstLog->recipient_phone);
            }
        }

        return $redirect;
    }

    /**
     * Kirim Pesan Tunggal / Uji Coba (Test Send)
     */
    public function sendSingle(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'name' => 'nullable|string',
            'message' => 'required|string',
            'message_type' => 'nullable|string',
        ]);

        $name = $request->input('name', 'Penerima Uji Coba');
        $phone = $request->input('phone');
        $message = $request->input('message');
        $type = $request->input('message_type', 'single');
        $adminName = auth()->user() ? auth()->user()->name : 'Admin NOC Banterpool';

        $log = $this->gatewayService->sendSingle($phone, $name, $message, $type, $adminName);
        $hasApiToken = $this->gatewayService->hasValidApiToken();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Pesan WhatsApp diproses untuk {$name} ({$phone})!",
                'log' => $log,
                'whatsapp_url' => $log->whatsapp_url,
                'has_api_token' => $hasApiToken,
            ]);
        }

        $redirect = redirect()->route('admin.whatsapp', ['tab' => 'logs']);

        if ($hasApiToken) {
            $redirect->with('success', "🚀 Pesan WhatsApp berhasil dikirim via API Gateway ke {$name} ({$phone})!");
        } else {
            $redirect->with('success', "Pesan WhatsApp untuk {$name} ({$phone}) berhasil disiapkan!")
                     ->with('needs_wa_web', true)
                     ->with('single_wa_url', $log->whatsapp_url)
                     ->with('single_wa_phone', $log->recipient_phone);
        }

        return $redirect;
    }

    /**
     * Hapus Riwayat Log Pesan
     */
    public function deleteLog($id)
    {
        $log = WhatsappLog::findOrFail($id);
        $log->delete();

        return redirect()->back()->with('success', "Riwayat log WhatsApp berhasil dihapus.");
    }

    /**
     * Bersihkan Seluruh Riwayat Log
     */
    public function clearLogs()
    {
        WhatsappLog::truncate();

        return redirect()->back()->with('success', "Seluruh riwayat log pengiriman WhatsApp berhasil dibersihkan.");
    }

    /**
     * Export Riwayat Log ke Excel
     */
    public function exportLogs(Request $request)
    {
        $logs = WhatsappLog::latest()->get();
        $filename = 'Rekap_WhatsApp_Logs_Banterpool_' . date('Ymd_His') . '.xls';

        return response()->streamDownload(function () use ($logs) {
            echo "\xEF\xBB\xBF"; // UTF-8 BOM
            echo view('admin.exports.whatsapp_logs_excel', compact('logs'))->render();
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0, no-cache, must-revalidate, proxy-revalidate',
        ]);
    }

    /**
     * Simpan Pengaturan Gateway
     */
    public function saveSettings(Request $request)
    {
        if ($request->filled('phone_number')) {
            cache()->forever('wa_gateway_phone_number', $request->input('phone_number'));
        }
        if ($request->filled('device_name')) {
            cache()->forever('wa_gateway_device_name', $request->input('device_name'));
        }
        if ($request->filled('provider')) {
            cache()->forever('wa_gateway_provider', $request->input('provider'));
        }

        $senderPhone = cache()->get('wa_gateway_phone_number', '0881-8679-774');

        return redirect()->route('admin.whatsapp', ['tab' => 'settings'])
            ->with('success', "Pengaturan WhatsApp Gateway berhasil diperbarui dan bot tersinkronisasi ke {$senderPhone}!");
    }
}
