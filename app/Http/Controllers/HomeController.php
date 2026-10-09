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

    public function tagihan(Request $request)
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
        $statusFilter = $request->input('status', 'all');
        $search = $request->input('q', '');

        $isAdminPreview = false;
        $adminPreviewCustomer = null;
        $availablePreviewCustomers = [];
        $targetEmail = null;
        $targetName = null;
        $targetPhone = null;
        $targetOrderId = null;
        $targetUserId = null;

        // 1. Cek parameter eksplisit di URL query
        if ($request->has('customer_email')) {
            $targetEmail = $request->input('customer_email');
        }
        if ($request->has('order_id')) {
            $targetOrderId = (int) $request->input('order_id');
        }

        // 2. Tentukan target pelanggan yang tagihannya sedang dilihat
        if ($user && ($user->isAdmin() || $user->role === 'admin' || $user->role === 'direktur')) {
            $hasOwnBills = Bill::where('user_id', $user->id)->orWhere('customer_email', $user->email)->exists();
            if (!$hasOwnBills) {
                // Admin sedang meninjau POV Pelanggan (Mode Pratinjau Admin)
                $isAdminPreview = true;

                $availablePreviewCustomers = Bill::select('customer_name', 'customer_email', 'customer_phone', 'order_id')
                    ->latest('id')
                    ->get()
                    ->unique(function ($item) {
                        return $item->customer_email ?: $item->customer_name;
                    })
                    ->values();

                if (empty($targetEmail) && empty($targetOrderId)) {
                    $selectedCust = $availablePreviewCustomers->first();
                    if ($selectedCust) {
                        $targetEmail = $selectedCust->customer_email;
                        $targetName = $selectedCust->customer_name;
                        $targetPhone = $selectedCust->customer_phone;
                        $targetOrderId = $selectedCust->order_id;
                    }
                } else {
                    $selectedCust = $availablePreviewCustomers->first(function ($c) use ($targetEmail, $targetOrderId) {
                        return ($targetEmail && $c->customer_email === $targetEmail) || ($targetOrderId && $c->order_id === $targetOrderId);
                    });
                    if ($selectedCust) {
                        $targetName = $selectedCust->customer_name;
                        $targetPhone = $selectedCust->customer_phone;
                    }
                }

                if ($targetName || $targetEmail) {
                    $adminPreviewCustomer = [
                        'name' => $targetName ?: 'Pelanggan',
                        'email' => $targetEmail ?: '-',
                        'phone' => $targetPhone ?: '-',
                    ];
                }
            } else {
                $targetUserId = $user->id;
                $targetEmail = $user->email;
                $targetName = $user->name;
                $targetPhone = $user->phone;
            }
        } elseif ($user) {
            // User adalah Pelanggan (Customer)
            $targetUserId = $user->id;
            $targetEmail = $user->email;
            $targetName = $user->name;
            $targetPhone = $user->phone;

            // Cek dari session order jika baru selesai checkout
            if (session()->has('customer_order_id')) {
                $sessOrder = Order::find(session('customer_order_id'));
                if ($sessOrder) {
                    $targetOrderId = $sessOrder->id;
                    if ($sessOrder->customer_email) $targetEmail = $sessOrder->customer_email;
                    if ($sessOrder->customer_name) $targetName = $sessOrder->customer_name;
                    if ($sessOrder->customer_phone) $targetPhone = $sessOrder->customer_phone;
                }
            }
        } else {
            // Guest / Tanpa Login
            if (session()->has('customer_order_id')) {
                $sessOrder = Order::find(session('customer_order_id'));
                if ($sessOrder) {
                    $targetEmail = $sessOrder->customer_email;
                    $targetName = $sessOrder->customer_name;
                    $targetPhone = $sessOrder->customer_phone;
                    $targetOrderId = $sessOrder->id;
                }
            }

            // Jika guest mencari q tertentu (no invoice atau hp)
            if (!empty($search)) {
                $searchBill = Bill::where('bill_number', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%")
                    ->latest('id')
                    ->first();
                if ($searchBill) {
                    $targetEmail = $searchBill->customer_email;
                    $targetName = $searchBill->customer_name;
                    $targetPhone = $searchBill->customer_phone;
                    $targetOrderId = $searchBill->order_id;
                }
            }
        }

        // 3. Otomatis sinkronkan tagihan pelanggan (tagihan pendaftaran awal & tagihan bulan selanjutnya setelah lunas)
        if (!empty($targetEmail) || !empty($targetName) || !empty($targetPhone) || !empty($targetOrderId) || !empty($targetUserId)) {
            BillingService::syncCustomerBillsForCustomer($targetEmail, $targetName, $targetPhone, $targetOrderId, $targetUserId);
        } elseif ($user) {
            BillingService::syncCustomerBills($user);
        }

        // 4. Ambil seluruh tagihan milik pelanggan ini dari database (HANYA jika pesanan terkait sudah 'Selesai' / aktif)
        $userAllBills = Bill::activeForMonitoring()
            ->where(function ($query) use ($targetEmail, $targetName, $targetPhone, $targetOrderId, $targetUserId, $user, $isAdminPreview) {
                if ($user && !$isAdminPreview) {
                    $query->where('user_id', $user->id)
                          ->orWhereHas('order', function ($oq) use ($user) {
                              $oq->where('user_id', $user->id);
                          });
                    if (!empty($targetOrderId)) {
                        $query->orWhere('order_id', $targetOrderId);
                    }
                    if (!empty($targetEmail)) {
                        $query->orWhere('customer_email', $targetEmail);
                    }
                    if (!empty($targetName)) {
                        $query->orWhere('customer_name', $targetName);
                    }
                    if (!empty($targetPhone)) {
                        $query->orWhere('customer_phone', $targetPhone);
                    }
                } else {
                    $matched = false;
                    if (!empty($targetOrderId)) {
                        $query->where('order_id', $targetOrderId);
                        $matched = true;
                    }
                    if (!empty($targetEmail)) {
                        $matched ? $query->orWhere('customer_email', $targetEmail) : $query->where('customer_email', $targetEmail);
                        $matched = true;
                    }
                    if (!empty($targetName)) {
                        $matched ? $query->orWhere('customer_name', $targetName) : $query->where('customer_name', $targetName);
                        $matched = true;
                    }
                    if (!empty($targetPhone)) {
                        $matched ? $query->orWhere('customer_phone', $targetPhone) : $query->where('customer_phone', $targetPhone);
                        $matched = true;
                    }
                    if (!$matched) {
                        $query->whereRaw('1 = 0');
                    }
                }
            })
            ->latest('id')
            ->get()
            ->filter(fn($b) => $b->isOrderCompleted())
            ->values();

        // Cek apakah pelanggan memiliki pesanan yang masih dalam proses pemasangan (belum selesai)
        $inProgressOrder = null;
        if ($user && !$isAdminPreview) {
            $inProgressOrder = Order::where(function ($q) use ($user, $targetOrderId, $targetEmail, $targetPhone) {
                $q->where('user_id', $user->id);
                if (!empty($targetOrderId)) $q->orWhere('id', $targetOrderId);
                if (!empty($targetEmail)) $q->orWhere('customer_email', $targetEmail);
                if (!empty($targetPhone)) $q->orWhere('customer_phone', $targetPhone);
            })->whereNotIn('status', ['Selesai', 'Selesai / Aktif', 'Aktif', 'Dibatalkan'])->latest('id')->first();
        }

        // Tagihan belum bayar / jatuh tempo aktif pengguna (tagihan bulan selanjutnya jika sebelumnya sudah lunas)
        $activeBillModel = $userAllBills->whereIn('status', ['Belum Bayar', 'Jatuh Tempo', 'Menunggu Verifikasi'])->first();
        $userPaidBills = $userAllBills->where('status', 'Lunas');

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
                'status' => $activeBillModel->status === 'Jatuh Tempo' ? 'Jatuh Tempo' : ($activeBillModel->status === 'Menunggu Verifikasi' ? 'Menunggu Verifikasi' : 'Belum Dibayar'),
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

        // Susun daftar tagihan terstruktur seperti pada POV Admin dan Direktur
        $allBillsData = $userAllBills->map(function ($b) {
            return [
                'id' => $b->bill_number,
                'customer_name' => $b->customer_name,
                'customer_phone' => $b->customer_phone,
                'customer_email' => $b->customer_email ?? '-',
                'package_name' => $b->package_name,
                'speed' => $b->speed ?? '20 Mbps',
                'period' => $b->period,
                'due_date' => $b->due_date,
                'bill_date' => $b->bill_date,
                'amount' => number_format((float) $b->amount, 0, ',', '.'),
                'tax' => number_format((float) $b->tax, 0, ',', '.'),
                'total' => number_format((float) $b->total, 0, ',', '.'),
                'total_raw' => (float) $b->total,
                'status' => $b->status,
                'payment_method' => $b->payment_method ?? 'BRI Virtual Account',
                'proof_image' => null,
                'created_at' => $b->created_at ? $b->created_at->format('Y-m-d H:i') : '-',
                'paid_date' => $b->paid_at ? \Carbon\Carbon::parse($b->paid_at)->translatedFormat('d M Y') : '-',
                'address' => $b->address,
                'odp' => 'ODP-CLK-01',
            ];
        });

        // Filter tab dan pencarian seperti POV Admin & Direktur
        $bills = $allBillsData->filter(function ($item) use ($statusFilter, $search) {
            $matchStatus = ($statusFilter === 'all') || ($item['status'] === $statusFilter);
            $matchSearch = empty($search) ||
                (stripos($item['package_name'], $search) !== false) ||
                (stripos($item['id'], $search) !== false) ||
                (stripos($item['period'], $search) !== false) ||
                (stripos($item['due_date'], $search) !== false) ||
                (stripos($item['payment_method'], $search) !== false) ||
                (stripos($item['status'], $search) !== false);

            return $matchStatus && $matchSearch;
        })->values()->all();

        $counts = [
            'all' => $allBillsData->count(),
            'menunggu' => $allBillsData->where('status', 'Menunggu Verifikasi')->count(),
            'belum_bayar' => $allBillsData->where('status', 'Belum Bayar')->count(),
            'lunas' => $allBillsData->where('status', 'Lunas')->count(),
            'jatuh_tempo' => $allBillsData->where('status', 'Jatuh Tempo')->count(),
        ];

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
                'payment_method' => $b->payment_method ?? 'BRI Virtual Account',
                'status' => $b->status,
            ];
        })->values()->toArray();

        return view('pages.tagihan', compact(
            'activeBill', 'historyBills', 'bills', 'statusFilter', 'search', 'counts',
            'isAdminPreview', 'adminPreviewCustomer', 'availablePreviewCustomers', 'inProgressOrder'
        ));
    }

    public function paymentTagihan(Request $request)
    {
        $invoice = $request->input('invoice');
        $dbBill = null;
        if ($invoice) {
            $dbBill = Bill::activeForMonitoring()->where('bill_number', $invoice)->first();
        } elseif (auth()->check()) {
            $user = auth()->user();
            $dbBill = Bill::activeForMonitoring()->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('order', function ($oq) use ($user) {
                      $oq->where('user_id', $user->id);
                  })
                  ->orWhere('customer_email', $user->email)
                  ->orWhere('customer_name', $user->name);
            })->whereIn('status', ['Belum Bayar', 'Jatuh Tempo', 'Menunggu Verifikasi'])->latest('id')->first();
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
            'due_date' => $dbBill ? $dbBill->due_date : $request->input('due_date', '05 ' . now()->locale('id')->addMonth()->translatedFormat('M Y')),
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

    /**
     * Konfirmasi Pembayaran Tagihan dari Pelanggan & Terbitkan Tagihan Bulan Selanjutnya
     */
    public function confirmPaymentTagihan(Request $request)
    {
        $invoice = $request->input('invoice');
        $paymentMethod = $request->input('payment_method', 'BRI Virtual Account');

        $bill = null;
        if ($invoice) {
            $bill = Bill::activeForMonitoring()->where('bill_number', $invoice)->first();
        }

        if (!$bill && auth()->check()) {
            $user = auth()->user();
            $bill = Bill::activeForMonitoring()->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('order', function ($oq) use ($user) {
                      $oq->where('user_id', $user->id);
                  })
                  ->orWhere('customer_email', $user->email)
                  ->orWhere('customer_name', $user->name);
            })->whereIn('status', ['Belum Bayar', 'Jatuh Tempo'])->latest('id')->first();
        }

        if ($bill) {
            $bill->status = 'Lunas';
            $bill->paid_at = now('Asia/Jakarta');
            $bill->payment_method = $paymentMethod;
            $bill->save();

            // Terbitkan otomatis tagihan untuk bulan selanjutnya dengan jatuh tempo tanggal 5
            BillingService::generateNextBillForPaidBill($bill);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Pembayaran berhasil dikonfirmasi.',
                'invoice' => $bill ? $bill->bill_number : $invoice,
            ]);
        }

        return redirect()->route('tagihan')->with('success', 'Pembayaran berhasil dikonfirmasi! Tagihan bulan selanjutnya telah diterbitkan.');
    }

    public function checkout(Request $request)
    {
        $customerData = [
            'name' => $request->input('name', 'Nama Pelanggan'),
            'phone' => $request->input('phone', '08xxxxxxxxxx'),
            'email' => $request->input('email', 'emailpelanggan@gmail.com'),
            'id_card_number' => $request->input('id_card_number'),
            'birth_place' => $request->input('birth_place'),
            'birth_date' => $request->input('birth_date'),
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
            'id_card_number' => $request->input('id_card_number'),
            'birth_place' => $request->input('birth_place'),
            'birth_date' => $request->input('birth_date'),
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
                $address = $request->input('address', (auth()->user() && auth()->user()->address) ? auth()->user()->address : 'Jl. Raya Pernasidi No. 45, Kec. Cilongok, Kab. Banyumas');
                $village = \App\Services\CustomerImportService::resolveVillage(null, null, $address);

                $order = Order::updateOrCreate(
                    ['order_number' => $orderNumber],
                    [
                        'user_id' => auth()->id(),
                        'customer_name' => $request->input('name', auth()->user() ? auth()->user()->name : 'Pelanggan Baru'),
                        'customer_phone' => $request->input('phone', (auth()->user() && auth()->user()->phone) ? auth()->user()->phone : '081234567890'),
                        'customer_email' => $request->input('email', auth()->user() ? auth()->user()->email : 'pelanggan@gmail.com'),
                        'id_card_number' => $request->input('id_card_number'),
                        'birth_place' => $request->input('birth_place'),
                        'birth_date' => $request->input('birth_date'),
                        'address' => $address,
                        'village' => $village,
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
                        'payment_method' => $request->input('payment_method', 'BRI Virtual Account'),
                        'payment_status' => 'Lunas',
                        'status' => 'Menunggu Konfirmasi',
                        'admin_notes' => 'Pesanan baru masuk dari website pelanggan.',
                    ]
                );

                // Catatan alur: Tagihan terbit saat status pesanan sudah 'Selesai'
                if ($order->status === 'Selesai') {
                    BillingService::generateBillForOrder($order);
                }

                session([
                    'customer_order_id' => $order->id,
                    'customer_order_number' => $order->order_number,
                    'customer_email' => $order->customer_email,
                    'customer_name' => $order->customer_name,
                    'customer_phone' => $order->customer_phone,
                ]);

                if (auth()->check()) {
                    $currUser = auth()->user();
                    if (empty($currUser->phone) && !empty($order->customer_phone)) {
                        $currUser->phone = $order->customer_phone;
                        $currUser->save();
                    }
                }
            } catch (\Exception $e) {
                // Resilient fallback
            }
        }

        $customerData = [
            'order_number' => $orderNumber ?: 'ORD-' . date('Ymd') . '-000123',
            'name' => $request->input('name', 'Nama Pelanggan'),
            'phone' => $request->input('phone', '08xxxxxxxxxx'),
            'email' => $request->input('email', 'emailpelanggan@gmail.com'),
            'id_card_number' => $request->input('id_card_number'),
            'birth_place' => $request->input('birth_place'),
            'birth_date' => $request->input('birth_date'),
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
                $order = Order::where('order_number', $orderNumber)->where('order_number', 'not like', 'PLG-%')->first();
            } catch (\Exception $e) {}
        } elseif (auth()->check()) {
            try {
                $order = Order::where(function ($q) {
                    $q->where('user_id', auth()->id())
                      ->orWhere('customer_email', auth()->user()->email);
                })
                ->where('order_number', 'not like', 'PLG-%')
                ->latest()
                ->first();
            } catch (\Exception $e) {}
        }

        $hasOrder = ($order !== null);

        $customerData = [
            'order_number' => $order ? $order->order_number : ($orderNumber ?: '-'),
            'name' => $order ? $order->customer_name : $request->input('name', (auth()->user() ? auth()->user()->name : 'Nama Pelanggan')),
            'phone' => $order ? $order->customer_phone : $request->input('phone', (auth()->user() && auth()->user()->phone ? auth()->user()->phone : '-')),
            'email' => $order ? $order->customer_email : $request->input('email', (auth()->user() ? auth()->user()->email : '-')),
            'id_card_number' => $order ? $order->id_card_number : $request->input('id_card_number'),
            'birth_place' => $order ? $order->birth_place : $request->input('birth_place'),
            'birth_date' => $order ? ($order->birth_date ? $order->birth_date->format('Y-m-d') : null) : $request->input('birth_date'),
            'address' => $order ? $order->address : $request->input('address', (auth()->user() && auth()->user()->address ? auth()->user()->address : '-')),
            'latitude' => $order ? $order->latitude : $request->input('latitude', null),
            'longitude' => $order ? $order->longitude : $request->input('longitude', null),
            'installation_date' => $order ? ($order->installation_date ? $order->installation_date->format('Y-m-d') : null) : $request->input('installation_date'),
            'installation_time' => $order ? $order->installation_time : $request->input('installation_time', 'pagi'),
            'package_name' => $order ? $order->package_name : $request->input('package_name', 'Paket 20 Mbps'),
            'speed' => $order ? $order->speed : $request->input('package_speed', '20 Mbps'),
            'price' => $order ? number_format($order->price, 0, ',', '.') : $request->input('package_price', '110.000'),
            'tax' => $order ? number_format($order->tax, 0, ',', '.') : '0',
            'total' => $order ? number_format($order->total, 0, ',', '.') : $request->input('package_price', '110.000'),
            'status' => $order ? $order->status : 'Menunggu Konfirmasi',
            'payment_status' => $order ? $order->payment_status : 'Lunas',
            'payment_method' => $order ? $order->payment_method : 'BRI Virtual Account',
            'technician' => $order ? $order->technician : null,
        ];

        return view('pages.order-detail', compact('customerData', 'order', 'hasOrder'));
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
        $myTickets = \App\Http\Controllers\Admin\AdminController::pruneOldTickets($myTickets, 3);
        session(['my_tickets' => $myTickets]);

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
            'created_at_iso' => $now->toIso8601String(),
            'created_timestamp' => $now->timestamp,
            'updated_timestamp' => $now->timestamp,
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

        // 1. Simpan ke Global Cache & Session Admin (POV Admin & Direktur)
        $adminTickets = Cache::get('trouble_tickets') ?: session('admin_tickets') ?: (new AdminController)->getTicketsData();
        // Prune otomatis riwayat tiket > 3 hari sebelum menambah tiket baru
        $adminTickets = AdminController::pruneOldTickets($adminTickets, 3);
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

