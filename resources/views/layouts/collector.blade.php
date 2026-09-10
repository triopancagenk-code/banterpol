<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-100">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Portal Kolektor Lapangan - Banterpool')</title>

  <!-- Google Fonts: Poppins -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- Tailwind & App JS -->
  @vite(['resources/css/app.css', 'resources/js/app.js'])

  <!-- Alpine JS -->
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

  <style>
    body { font-family: 'Poppins', sans-serif; }
    [x-cloak] { display: none !important; }
    @media print {
      body * { visibility: hidden; }
      #printable-area, #printable-area * { visibility: visible; }
      #printable-area { position: absolute; left: 0; top: 0; width: 100%; }
      .no-print { display: none !important; }
    }
  </style>
  @stack('styles')
</head>
<body class="h-full bg-slate-100 text-slate-800 antialiased flex flex-col pb-20 md:pb-8">

  <!-- ============================================== -->
  <!-- TOP NAVBAR KOLEKTOR                            -->
  <!-- ============================================== -->
  <header class="bg-slate-900 text-white sticky top-0 z-40 border-b border-slate-800 shadow-sm no-print">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
      
      <!-- Brand & Role Badge -->
      <div class="flex items-center gap-3">
        <a href="{{ route('kolektor.dashboard') }}" class="flex items-center gap-2.5">
          <div class="w-9 h-9 rounded-xl bg-emerald-500 flex items-center justify-center text-slate-950 font-black shadow-md shadow-emerald-500/20">
            <i class="fa-solid fa-money-bill-wave text-base"></i>
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="font-extrabold text-base tracking-wider text-white">BANTER<span class="text-brand">POOL</span></span>
              <span class="bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-[9px] font-black px-2 py-0.5 rounded tracking-wider uppercase">KOLEKTOR</span>
            </div>
            <p class="text-[10px] text-slate-400 hidden sm:block">Cash Collection & Field Billing Portal</p>
          </div>
        </a>
      </div>

      <!-- Desktop Nav Menu -->
      <nav class="hidden md:flex items-center gap-1 text-xs font-semibold">
        <a href="{{ route('kolektor.dashboard') }}"
           class="px-3.5 py-2 rounded-xl transition flex items-center gap-2 {{ request()->routeIs('kolektor.dashboard') ? 'bg-slate-800 text-emerald-400 font-bold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
          <i class="fa-solid fa-chart-line text-sm"></i>
          <span>Dashboard</span>
        </a>

        <a href="{{ route('kolektor.tagihan') }}"
           class="px-3.5 py-2 rounded-xl transition flex items-center gap-2 {{ request()->routeIs('kolektor.tagihan*') ? 'bg-slate-800 text-emerald-400 font-bold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
          <i class="fa-solid fa-file-invoice-dollar text-sm"></i>
          <span>Tagihan & Tunai</span>
        </a>

        <a href="{{ route('kolektor.pemasangan') }}"
           class="px-3.5 py-2 rounded-xl transition flex items-center gap-2 {{ request()->routeIs('kolektor.pemasangan*') ? 'bg-slate-800 text-emerald-400 font-bold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
          <i class="fa-solid fa-calendar-check text-sm"></i>
          <span>Tiket Pasang</span>
        </a>

        <a href="{{ route('kolektor.gangguan') }}"
           class="px-3.5 py-2 rounded-xl transition flex items-center gap-2 {{ request()->routeIs('kolektor.gangguan*') ? 'bg-slate-800 text-emerald-400 font-bold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
          <i class="fa-solid fa-triangle-exclamation text-sm"></i>
          <span>Tiket Gangguan</span>
        </a>
      </nav>

      <!-- Right Area: Switchers & Profile Dropdown -->
      <div class="flex items-center gap-2.5">
        
        <!-- Quick Switcher to Admin (if Admin) -->
        @if(auth()->user()->isAdmin())
          <a href="{{ route('admin.dashboard') }}"
             class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-medium border border-slate-700 transition"
             title="Kembali ke Panel NOC Admin">
            <i class="fa-solid fa-shield-halved text-brand"></i>
            <span>NOC Admin</span>
          </a>

          <a href="{{ route('teknisi.dashboard') }}"
             class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-medium border border-slate-700 transition"
             title="Buka Portal Teknisi">
            <i class="fa-solid fa-screwdriver-wrench text-amber-400"></i>
            <span>Teknisi</span>
          </a>

          <a href="{{ route('home') }}"
             class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-medium border border-slate-700 transition"
             title="Buka Beranda Pelanggan">
            <i class="fa-solid fa-globe"></i>
            <span>Web Utama</span>
          </a>
        @endif

        <!-- Notification Bell Kolektor -->
        @php
          $collectorNotifications = [];
          try {
            $cBills = \App\Models\Bill::whereIn('status', ['Menunggu Verifikasi', 'Belum Bayar'])->latest()->take(3)->get();
            foreach ($cBills as $cb) {
              $collectorNotifications[] = [
                'title' => 'Tagihan: ' . $cb->bill_number,
                'desc' => $cb->customer_name . ' - Rp ' . number_format($cb->amount ?? 0, 0, ',', '.'),
                'time' => $cb->created_at ? $cb->created_at->diffForHumans() : 'Baru saja',
                'url' => route('kolektor.tagihan', ['q' => $cb->bill_number]),
                'icon' => 'fa-solid fa-file-invoice-dollar',
                'icon_bg' => 'bg-emerald-100 text-emerald-600',
              ];
            }

            $cOrders = \App\Models\Order::whereIn('status', ['Menunggu Konfirmasi', 'Jadwal Pemasangan'])->latest()->take(3)->get();
            foreach ($cOrders as $co) {
              $collectorNotifications[] = [
                'title' => 'Order Pasang: ' . $co->order_number,
                'desc' => $co->customer_name . ' (' . ($co->package_name ?? 'WiFi') . ')',
                'time' => $co->created_at ? $co->created_at->diffForHumans() : 'Baru saja',
                'url' => route('kolektor.pemasangan', ['q' => $co->order_number]),
                'icon' => 'fa-solid fa-calendar-check',
                'icon_bg' => 'bg-blue-100 text-blue-600',
              ];
            }
          } catch (\Exception $e) {}
          $collectorNotifCount = count($collectorNotifications);
        @endphp

        <div class="relative" x-data="{ openNotify: false }">
          <button type="button" @click="openNotify = !openNotify" @click.away="openNotify = false"
                  class="w-9 h-9 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white flex items-center justify-center relative transition"
                  title="Pemberitahuan Tagihan Kolektor">
            <i class="fa-regular fa-bell text-sm"></i>
            @if($collectorNotifCount > 0)
              <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            @endif
          </button>

          <!-- Dropdown Notif -->
          <div x-show="openNotify" style="display: none;"
               x-transition:enter="transition ease-out duration-150"
               x-transition:enter-start="transform opacity-0 scale-95"
               x-transition:enter-end="transform opacity-100 scale-100"
               class="absolute right-0 mt-2 w-80 bg-white border border-slate-200 rounded-2xl shadow-xl py-3 z-50 text-slate-800">
            <div class="px-4 pb-2 border-b border-slate-100 flex items-center justify-between">
              <p class="font-bold text-xs text-slate-900">Notifikasi Penagihan</p>
              @if($collectorNotifCount > 0)
                <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded-full">{{ $collectorNotifCount }} Baru</span>
              @else
                <span class="text-slate-400 text-[10px] font-medium">0 Baru</span>
              @endif
            </div>
            <div class="divide-y divide-slate-100 text-xs max-h-80 overflow-y-auto">
              @forelse($collectorNotifications as $notif)
                <a href="{{ $notif['url'] }}" class="p-3 flex items-start gap-3 hover:bg-slate-50 transition block">
                  <div class="w-7 h-7 rounded-lg {{ $notif['icon_bg'] }} flex items-center justify-center shrink-0 text-xs">
                    <i class="{{ $notif['icon'] }}"></i>
                  </div>
                  <div class="flex-1">
                    <p class="font-bold text-slate-900 leading-tight">{{ $notif['title'] }}</p>
                    <p class="text-[11px] text-slate-500 mt-0.5">{{ $notif['desc'] }}</p>
                    <span class="text-[9px] text-slate-400 mt-0.5 block">{{ $notif['time'] }}</span>
                  </div>
                </a>
              @empty
                <div class="py-7 text-center text-xs text-slate-400 space-y-1">
                  <i class="fa-regular fa-bell-slash text-2xl text-slate-300 block"></i>
                  <p class="font-medium text-slate-600">Tidak ada tagihan tertunda</p>
                  <p class="text-[10px] text-slate-400">Semua setoran & tagihan beres</p>
                </div>
              @endforelse
            </div>
          </div>
        </div>

        <!-- User Dropdown -->
        <div class="relative" x-data="{ open: false }">
          <button @click="open = !open" @click.away="open = false"
                  class="flex items-center gap-2 p-1 rounded-xl hover:bg-slate-800 transition focus:outline-none">
            @if(auth()->user()->avatar)
              <img src="{{ \Illuminate\Support\Str::startsWith(auth()->user()->avatar, ['http://', 'https://']) ? auth()->user()->avatar : asset('storage/' . auth()->user()->avatar) }}"
                   alt="{{ auth()->user()->name }}" class="w-8 h-8 rounded-full object-cover border border-slate-600">
            @else
              <div class="w-8 h-8 rounded-full bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 flex items-center justify-center text-xs font-black">
                {{ substr(auth()->user()->name, 0, 1) }}
              </div>
            @endif
            <div class="text-left hidden lg:block">
              <p class="text-xs font-bold text-white leading-tight">{{ auth()->user()->name }}</p>
              <p class="text-[10px] text-emerald-400 font-medium leading-none">Kolektor Lapangan</p>
            </div>
            <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 ml-1"></i>
          </button>

          <!-- Dropdown Menu -->
          <div x-show="open"
               x-transition:enter="transition ease-out duration-100"
               x-transition:enter-start="transform opacity-0 scale-95"
               x-transition:enter-end="transform opacity-100 scale-100"
               x-transition:leave="transition ease-in duration-75"
               x-transition:leave-start="transform opacity-100 scale-100"
               x-transition:leave-end="transform opacity-0 scale-95"
               class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-slate-100 py-2 z-50 text-slate-700 text-xs font-medium"
               style="display: none;">
            
            <div class="px-4 py-2 border-b border-slate-100">
              <p class="font-bold text-slate-900">{{ auth()->user()->name }}</p>
              <p class="text-[11px] text-slate-500 truncate">{{ auth()->user()->email }}</p>
              <span class="inline-block mt-1 bg-emerald-100 text-emerald-800 text-[9px] font-bold px-2 py-0.5 rounded-md uppercase">Field Collector</span>
            </div>

            @if(auth()->user()->isAdmin())
              <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 px-4 py-2 hover:bg-slate-50 text-brand font-semibold">
                <i class="fa-solid fa-shield-halved"></i>
                <span>Beralih ke Admin NOC</span>
              </a>
              <a href="{{ route('teknisi.dashboard') }}" class="flex items-center gap-2.5 px-4 py-2 hover:bg-slate-50 text-amber-600 font-semibold">
                <i class="fa-solid fa-screwdriver-wrench"></i>
                <span>Beralih ke Portal Teknisi</span>
              </a>
              <a href="{{ route('home') }}" class="flex items-center gap-2.5 px-4 py-2 hover:bg-slate-50 text-slate-700">
                <i class="fa-solid fa-house"></i>
                <span>Kembali ke Beranda Utama</span>
              </a>
            @endif

            <hr class="my-1 border-slate-100">

            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button type="submit" class="w-full text-left flex items-center gap-2.5 px-4 py-2 text-red-600 hover:bg-red-50 font-semibold">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Logout</span>
              </button>
            </form>
          </div>
        </div>

      </div>

    </div>
  </header>

  <!-- Flash Notification Message -->
  @if(session('success'))
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 no-print">
      <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold p-4 rounded-2xl flex items-center gap-3 shadow-xs">
        <i class="fa-solid fa-circle-check text-emerald-600 text-base shrink-0"></i>
        <span>{{ session('success') }}</span>
      </div>
    </div>
  @endif

  @if(session('error'))
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 no-print">
      <div class="bg-red-50 border border-red-200 text-red-800 text-xs font-semibold p-4 rounded-2xl flex items-center gap-3 shadow-xs">
        <i class="fa-solid fa-circle-exclamation text-red-600 text-base shrink-0"></i>
        <span>{{ session('error') }}</span>
      </div>
    </div>
  @endif

  <!-- ============================================== -->
  <!-- MAIN CONTENT AREA                              -->
  <!-- ============================================== -->
  <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
    @yield('content')
  </main>

  <!-- ============================================== -->
  <!-- MOBILE BOTTOM NAVIGATION (Like Native App)     -->
  <!-- ============================================== -->
  <nav class="md:hidden fixed bottom-0 inset-x-0 bg-slate-900 text-slate-400 border-t border-slate-800 z-50 flex items-center justify-around h-16 shadow-lg no-print">
    
    <a href="{{ route('kolektor.dashboard') }}"
       class="flex flex-col items-center justify-center flex-1 py-1 {{ request()->routeIs('kolektor.dashboard') ? 'text-emerald-400 font-bold' : 'hover:text-white' }}">
      <i class="fa-solid fa-chart-line text-lg mb-0.5"></i>
      <span class="text-[10px]">Dashboard</span>
    </a>

    <a href="{{ route('kolektor.tagihan') }}"
       class="flex flex-col items-center justify-center flex-1 py-1 {{ request()->routeIs('kolektor.tagihan*') ? 'text-emerald-400 font-bold' : 'hover:text-white' }}">
      <i class="fa-solid fa-file-invoice-dollar text-lg mb-0.5"></i>
      <span class="text-[10px]">Tagihan</span>
    </a>

    <a href="{{ route('kolektor.pemasangan') }}"
       class="flex flex-col items-center justify-center flex-1 py-1 {{ request()->routeIs('kolektor.pemasangan*') ? 'text-emerald-400 font-bold' : 'hover:text-white' }}">
      <i class="fa-solid fa-calendar-check text-lg mb-0.5"></i>
      <span class="text-[10px]">Pasang</span>
    </a>

    <a href="{{ route('kolektor.gangguan') }}"
       class="flex flex-col items-center justify-center flex-1 py-1 {{ request()->routeIs('kolektor.gangguan*') ? 'text-emerald-400 font-bold' : 'hover:text-white' }}">
      <i class="fa-solid fa-triangle-exclamation text-lg mb-0.5"></i>
      <span class="text-[10px]">Gangguan</span>
    </a>

  </nav>

  @stack('scripts')
</body>
</html>
