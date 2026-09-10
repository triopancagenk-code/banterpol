<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\AdminController;
use App\Models\Bill;
use App\Models\Order;
use App\Models\Package;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    private function redirectIfStaff()
    {
        if (!auth()->check()) {
            return null;
        }

        if (auth()->user()->role === 'technician' || auth()->user()->role === 'teknisi') {
            return redirect()->route('teknisi.dashboard');
        }

        if (auth()->user()->role === 'collector' || auth()->user()->role === 'kolektor') {
            return redirect()->route('kolektor.dashboard');
        }

        return null;
    }

    public function index()
    {
        if ($redirect = $this->redirectIfStaff()) {
            return $redirect;
        }

        return view('pages.home');
    }

    public function tentangKami()
    {
        return view('pages.tentang-kami');
    }

    public function paket()
    {
        try {
            $packages = Package::where('is_active', true)->get();
        } catch (\Exception $e) {
            $packages = collect();
        }

        return view('pages.paket', compact('packages'));
    }

    public function tagihan()
    {
        if (auth()->check()) {
            if (auth()->user()->role === 'collector' || auth()->user()->role === 'kolektor') {
                return redirect()->route('kolektor.tagihan');
            }
            if (auth()->user()->role === 'technician' || auth()->user()->role === 'teknisi') {
                return redirect()->route('teknisi.dashboard');
            }
        }

        // Otomatis sinkronkan pesanan selesai agar tagihan terbit
        BillingService::syncCompletedOrdersWithoutBills();

        $user = auth()->user();
        $activeBillModel = null;
        $userPaidBills = collect();

        if ($user) {
            // Cari tagihan belum bayar / jatuh tempo milik pengguna
            $activeBillModel = Bill::whereIn('status', ['Belum Bayar', 'Jatuh Tempo'])
                ->where(function ($query) use ($user) {
                    $query->where('customer_email', $user->email);
                    if (!empty($user->phone)) {
                        $query->orWhere('customer_phone', $user->phone);
                    }
                    $query->orWhere('customer_name', $user->name);

                    $orderIds = Order::where('customer_email', $user->email)
                        ->orWhere('customer_name', $user->name)
                        ->pluck('id');
                    if ($orderIds->isNotEmpty()) {
                        $query->orWhereIn('order_id', $orderIds);
                    }
                })
                ->latest()
                ->first();

            // Riwayat pembayaran lunas milik pengguna dari database
            $userPaidBills = Bill::where('status', 'Lunas')
                ->where(function ($query) use ($user) {
                    $query->where('customer_email', $user->email)
                        ->orWhere('customer_name', $user->name);
                    if (!empty($user->phone)) {
                        $query->orWhere('customer_phone', $user->phone);
                    }
                })
                ->latest()
                ->get();
        }

        if ($activeBillModel) {
            $activeBill = [
                'id' => $activeBillModel->bill_number,
                'package_name' => $activeBillModel->package_name,
                'speed' => $activeBillModel->speed ?? '20 Mbps',
                'period' => 'Periode ' . $activeBillModel->period,
                'due_date' => $activeBillModel->due_date,
                'bill_date' => $activeBillModel->bill_date,
                'price' => number_format($activeBillModel->amount, 0, ',', '.'),
                'tax' => number_format($activeBillModel->tax, 0, ',', '.'),
                'total' => number_format($activeBillModel->total, 0, ',', '.'),
                'total_raw' => (int) $activeBillModel->total,
                'status' => $activeBillModel->status === 'Jatuh Tempo' ? 'Jatuh Tempo' : 'Belum Dibayar',
                'has_unpaid' => true,
            ];
        } else {
            $activeBill = [
                'id' => '-',
                'package_name' => '-',
                'speed' => '-',
                'period' => '-',
                'due_date' => '-',
                'bill_date' => '-',
                'price' => '0',
                'tax' => '0',
                'total' => '0',
                'total_raw' => 0,
                'status' => 'Lunas',
                'has_unpaid' => false,
            ];
        }

        $historyBills = $userPaidBills->map(function ($b) {
            return [
                'id' => $b->bill_number,
                'period' => $b->period,
                'year' => $b->created_at ? $b->created_at->format('Y') : date('Y'),
                'package_name' => $b->package_name,
                'bill_date' => $b->bill_date,
                'due_date' => $b->due_date,
                'paid_date' => $b->paid_at ? \Carbon\Carbon::parse($b->paid_at)->translatedFormat('d M Y') : '-',
                'total' => 'Rp' . number_format((float) $b->total, 0, ',', '.'),
                'payment_method' => $b->payment_method ?? 'Transfer Bank',
                'status' => $b->status,
            ];
        })->toArray();

        return view('pages.tagihan', compact('activeBill', 'historyBills'));
    }

    public function paymentTagihan(Request $request)
    {
        $invoice = $request->input('invoice');
        $dbBill = null;
        if ($invoice) {
            $dbBill = Bill::where('bill_number', $invoice)->first();
        } elseif (auth()->check()) {
            $dbBill = Bill::where('user_id', auth()->id())->where('status', 'Belum Bayar')->latest()->first();
            if ($dbBill) {
                $invoice = $dbBill->bill_number;
            }
        }

        if (!$dbBill && !$invoice) {
            return redirect()->route('tagihan')->with('info', 'Tidak ada tagihan tertunda yang perlu dibayar.');
        }

        $billData = [
            'invoice' => $invoice ?? ('INV-' . date('Ym') . '-0001'),
            'package_name' => $dbBill ? $dbBill->package_name : $request->input('package', 'Paket WiFi Banterpool'),
            'period' => $dbBill ? $dbBill->period : $request->input('period', now()->locale('id')->translatedFormat('F Y')),
            'due_date' => $dbBill ? $dbBill->due_date : $request->input('due_date', '10 ' . now()->locale('id')->translatedFormat('F Y')),
            'price' => $dbBill ? number_format($dbBill->amount, 0, ',', '.') : $request->input('price', '150.000'),
            'price_raw' => $dbBill ? (int) $dbBill->amount : (int) $request->input('price_raw', 150000),
            'tax' => $dbBill ? number_format($dbBill->tax, 0, ',', '.') : $request->input('tax', '0'),
            'tax_raw' => $dbBill ? (int) $dbBill->tax : (int) $request->input('tax_raw', 0),
            'installation_fee' => 'Rp.0 (Gratis)',
            'total' => $dbBill ? number_format($dbBill->total, 0, ',', '.') : $request->input('total', '150.000'),
            'total_raw' => $dbBill ? (int) $dbBill->total : (int) $request->input('total_raw', 150000),
            'status' => 'Menunggu Pembayaran',
        ];

        return view('pages.payment-tagihan', compact('billData'));
    }

    public function checkout(Request $request)
    {
        $customerData = [
            'name' => $request->input('name', 'Nama Pelanggan'),
            'phone' => $request->input('phone', '08xxxxxxxxxx'),
            'email' => $request->input('email', 'emailpelanggan@gmail.com'),
            'address' => $request->input('address', 'Jl. Raya Pernasidi No. 45, Kec. Cilongok, Kab. Banyumas'),
            'latitude' => $request->input('latitude', '-7.413200'),
            'longitude' => $request->input('longitude', '109.138800'),
            'installation_date' => $request->input('installation_date'),
            'installation_time' => $request->input('installation_time', 'pagi'),
        ];

        return view('pages.checkout', compact('customerData'));
    }

    public function payment(Request $request)
    {
        if ($request->has('invoice') || $request->input('type') === 'tagihan') {
            return redirect()->route('tagihan.payment', $request->query());
        }

        $customerData = [
            'name' => $request->input('name', 'Nama Pelanggan'),
            'phone' => $request->input('phone', '08xxxxxxxxxx'),
            'email' => $request->input('email', 'emailpelanggan@gmail.com'),
            'address' => $request->input('address', 'Jl. Raya Pernasidi No. 45, Kec. Cilongok, Kab. Banyumas'),
            'latitude' => $request->input('latitude', '-7.413200'),
            'longitude' => $request->input('longitude', '109.138800'),
            'installation_date' => $request->input('installation_date'),
            'installation_time' => $request->input('installation_time', 'pagi'),
        ];

        return view('pages.payment', compact('customerData'));
    }

    public function paymentStatus(Request $request)
    {
        $isSuccess = $request->input('status') !== 'failed';
        $packageName = $request->input('package_name', 'Paket 20 Mbps');
        $speed = $request->input('package_speed', '20 Mbps');
        
        $priceRaw = (float) str_replace(['.', ','], ['', '.'], $request->input('package_price', '110000'));
        if ($priceRaw < 1000) {
            $priceRaw = $priceRaw * 1000;
        }
        if ($priceRaw <= 0) {
            $priceRaw = 110000;
        }
        $tax = 0;
        $total = $priceRaw;

        $orderNumber = $request->input('order_number');
        
        if (!$orderNumber && $isSuccess) {
            $orderNumber = 'ORD-' . date('Ymd') . '-' . rand(100, 999);
            
            try {
                Order::updateOrCreate(
                    ['order_number' => $orderNumber],
                    [
                        'user_id' => auth()->id(),
                        'customer_name' => $request->input('name', auth()->user() ? auth()->user()->name : 'Pelanggan Baru'),
                        'customer_phone' => $request->input('phone', (auth()->user() && auth()->user()->phone) ? auth()->user()->phone : '081234567890'),
                        'customer_email' => $request->input('email', auth()->user() ? auth()->user()->email : 'pelanggan@gmail.com'),
                        'address' => $request->input('address', (auth()->user() && auth()->user()->address) ? auth()->user()->address : 'Jl. Raya Pernasidi No. 45, Kec. Cilongok, Kab. Banyumas'),
                        'latitude' => $request->input('latitude', '-7.413200'),
                        'longitude' => $request->input('longitude', '109.138800'),
                        'package_name' => $packageName,
                        'speed' => $speed,
                        'price' => $priceRaw,
                        'installation_fee' => 0,
                        'tax' => $tax,
                        'total' => $total,
                        'installation_date' => $request->input('installation_date', now()->addDay()->format('Y-m-d')),
                        'installation_time' => $request->input('installation_time', 'pagi'),
                        'payment_method' => $request->input('payment_method', 'Transfer Bank (BCA)'),
                        'payment_status' => 'Lunas',
                        'status' => 'Menunggu Konfirmasi',
                        'admin_notes' => 'Pesanan baru masuk dari website pelanggan.',
                    ]
                );
            } catch (\Exception $e) {
                // Resilient fallback
            }
        }

        $customerData = [
            'order_number' => $orderNumber ?: 'ORD-' . date('Ymd') . '-000123',
            'name' => $request->input('name', 'Nama Pelanggan'),
            'phone' => $request->input('phone', '08xxxxxxxxxx'),
            'email' => $request->input('email', 'emailpelanggan@gmail.com'),
            'address' => $request->input('address', 'Jl. Raya Pernasidi No. 45, Kec. Cilongok, Kab. Banyumas'),
            'latitude' => $request->input('latitude', '-7.413200'),
            'longitude' => $request->input('longitude', '109.138800'),
            'installation_date' => $request->input('installation_date'),
            'installation_time' => $request->input('installation_time', 'pagi'),
            'package_name' => $packageName,
            'package_speed' => $speed,
            'price' => number_format($priceRaw, 0, ',', '.'),
            'tax' => number_format($tax, 0, ',', '.'),
            'total' => number_format($total, 0, ',', '.'),
        ];

        return view('pages.payment-status', compact('customerData'));
    }

    public function orderDetail(Request $request)
    {
        $orderNumber = $request->input('order_number');
        $order = null;
        if ($orderNumber) {
            try {
                $order = Order::where('order_number', $orderNumber)->first();
            } catch (\Exception $e) {}
        }

        $customerData = [
            'order_number' => $order ? $order->order_number : ($orderNumber ?: 'ORD-' . date('Ymd') . '-000123'),
            'name' => $order ? $order->customer_name : $request->input('name', 'Nama Pelanggan'),
            'phone' => $order ? $order->customer_phone : $request->input('phone', '08xxxxxxxxxx'),
            'email' => $order ? $order->customer_email : $request->input('email', 'emailpelanggan@gmail.com'),
            'address' => $order ? $order->address : $request->input('address', 'Jl. Raya Pernasidi No. 45, Kec. Cilongok, Kab. Banyumas'),
            'latitude' => $order ? $order->latitude : $request->input('latitude', '-7.413200'),
            'longitude' => $order ? $order->longitude : $request->input('longitude', '109.138800'),
            'installation_date' => $order ? ($order->installation_date ? $order->installation_date->format('Y-m-d') : null) : $request->input('installation_date'),
            'installation_time' => $order ? $order->installation_time : $request->input('installation_time', 'pagi'),
            'package_name' => $order ? $order->package_name : $request->input('package_name', 'Paket 20 Mbps'),
            'speed' => $order ? $order->speed : $request->input('package_speed', '20 Mbps'),
            'price' => $order ? number_format($order->price, 0, ',', '.') : $request->input('package_price', '110.000'),
            'tax' => $order ? number_format($order->tax, 0, ',', '.') : '0',
            'total' => $order ? number_format($order->total, 0, ',', '.') : $request->input('package_price', '110.000'),
            'status' => $order ? $order->status : 'Menunggu Konfirmasi',
            'payment_status' => $order ? $order->payment_status : 'Lunas',
            'payment_method' => $order ? $order->payment_method : 'Transfer Bank (BCA)',
            'technician' => $order ? $order->technician : null,
        ];

        return view('pages.order-detail', compact('customerData', 'order'));
    }

    public function laporanMasalah(Request $request)
    {
        if (auth()->check()) {
            if (auth()->user()->role === 'technician' || auth()->user()->role === 'teknisi') {
                return redirect()->route('teknisi.gangguan');
            }
            if (auth()->user()->role === 'collector' || auth()->user()->role === 'kolektor') {
                return redirect()->route('kolektor.gangguan');
            }
        }

        $defaultTickets = [];

        $myTickets = session('my_tickets', $defaultTickets);

        return view('pages.laporan-masalah', compact('myTickets'));
    }

    public function storeLaporanMasalah(Request $request)
    {
        $category = $request->input('category') ?: $request->input('type', 'Gangguan Koneksi');
        $subCategory = $request->input('sub_category') ?: '-';
        $description = $request->input('description', '');
        $ticketId = 'TCK-' . date('Ym') . '-' . rand(100, 999);

        // Upload & Penyimpanan Foto Bukti Gambar / Lampiran dari Pelanggan
        $attachmentName = null;
        $attachmentUrl = null;
        $attachmentType = null;
        $attachmentSize = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            if ($file->isValid()) {
                $originalName = $file->getClientOriginalName();
                $extension = strtolower($file->getClientOriginalExtension());
                $bytes = $file->getSize();
                $attachmentSize = $bytes > 1048576 
                    ? number_format($bytes / 1048576, 2) . ' MB' 
                    : number_format($bytes / 1024, 0) . ' KB';

                $safeName = pathinfo($originalName, PATHINFO_FILENAME);
                $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $safeName);
                $filename = 'tiket_' . date('Ymd_His') . '_' . rand(100, 999) . '_' . $safeName . '.' . $extension;

                $destinationPath = public_path('uploads/laporan');
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                $file->move($destinationPath, $filename);

                $attachmentName = $originalName;
                $attachmentUrl = '/uploads/laporan/' . $filename;
                $attachmentType = in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif']) ? 'image' : ($extension === 'pdf' ? 'pdf' : 'file');
            }
        }

        $now = now('Asia/Jakarta')->locale('id');
        $createdAtFormatted = $now->translatedFormat('l, d F Y, H:i') . ' WIB';
        $createdAtWithSeconds = $now->translatedFormat('l, d F Y, H:i:s') . ' WIB';
        $createdDateOnly = $now->translatedFormat('l, d F Y');
        $timeOnly = $now->format('H:i:s') . ' WIB';

        $user = auth()->user();
        $customerName = $user ? $user->name : 'Pelanggan Banterpool';
        $customerPhone = ($user && $user->phone) ? $user->phone : '081234567890';
        $customerAddress = ($user && $user->address) ? $user->address : 'Desa Cilongok RT 02/03, Kec. Cilongok, Banyumas';

        $priority = 'Normal';
        if (stripos($category, 'LOS') !== false || stripos($category, 'Putus') !== false || stripos($subCategory, 'LOS') !== false || stripos($subCategory, 'Putus') !== false) {
            $priority = 'Kritis';
        } elseif (stripos($category, 'Lambat') !== false || stripos($category, 'Tanpa Internet') !== false || stripos($subCategory, 'Drop') !== false) {
            $priority = 'Tinggi';
        }

        $newTicket = [
            'id' => $ticketId,
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'address' => $customerAddress,
            'odp' => 'ODP-CLK-01',
            'type' => $category,
            'category' => $subCategory,
            'priority' => $priority,
            'description' => $description,
            'status' => 'Menunggu Respon',
            'technician' => 'Belum Ditugaskan',
            'created_at' => $createdAtFormatted,
            'created_date' => $createdDateOnly,
            'created_time' => $timeOnly,
            'created_at_full' => $createdAtWithSeconds,
            'created_at_short' => $createdAtWithSeconds,
            'updated_at' => $createdAtFormatted,
            'updated_date' => $createdDateOnly,
            'updated_time' => $timeOnly,
            'updated_at_full' => $createdAtWithSeconds,
            'updated_at_short' => $createdAtWithSeconds,
            'updated_at_iso' => $now->toIso8601String(),
            'notes' => 'Laporan kendala baru masuk dari portal pelanggan.',
            'coordinates' => '-7.4132, 109.1388',
            'attachment' => $attachmentName,
            'attachment_url' => $attachmentUrl,
            'attachment_type' => $attachmentType,
            'attachment_size' => $attachmentSize,
            'is_new_incoming' => true,
            'is_recently_updated' => true,
            'status_history' => [
                [
                    'status' => 'Menunggu Respon',
                    'technician' => 'Belum Ditugaskan',
                    'notes' => 'Laporan kendala dikirim oleh pelanggan.',
                    'updated_at' => $createdAtWithSeconds,
                    'admin' => 'Pelanggan (' . $customerName . ')'
                ]
            ]
        ];

        // 1. Simpan ke Global Cache & Session Admin (POV Admin)
        $adminTickets = Cache::get('trouble_tickets') ?: session('admin_tickets') ?: (new AdminController)->getTicketsData();
        array_unshift($adminTickets, $newTicket);
        Cache::forever('trouble_tickets', $adminTickets);
        session(['admin_tickets' => $adminTickets]);

        // 2. Sinkronkan juga ke Session Teknisi Lapangan jika ada
        $techTickets = session('technician_tickets');
        if ($techTickets && is_array($techTickets)) {
            array_unshift($techTickets, $newTicket);
            session(['technician_tickets' => $techTickets]);
        }

        // 3. Simpan ke Session Pelanggan (POV Pelanggan)
        $existingTickets = session('my_tickets', []);

        array_unshift($existingTickets, $newTicket);
        session(['my_tickets' => $existingTickets]);

        return redirect()->back()->with('ticket_success', [
            'id' => $ticketId,
            'category' => $category,
            'sub_category' => $subCategory,
            'message' => 'Laporan kendala berhasil dikirimkan ke Tim Dukungan & NOC Banterpool pada ' . $timeOnly . '. Teknisi kami akan segera memproses laporan Anda.'
        ]);
    }
}

