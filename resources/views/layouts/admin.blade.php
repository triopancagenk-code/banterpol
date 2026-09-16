<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Admin NOC Banterpool')</title>

  <!-- Google Fonts: Poppins -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- Tailwind & App JS -->
  @vite(['resources/css/app.css', 'resources/js/app.js'])

  <!-- Alpine JS -->
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

  <!-- Chart.js for MRTG Graphs -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <style>
    body { font-family: 'Poppins', sans-serif; }
    [x-cloak] { display: none !important; }
    .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
  </style>
  @stack('styles')
</head>
<body class="h-full bg-[#f8fafc] text-slate-800 antialiased" x-data="{ sidebarOpen: false }">

  <div class="min-h-full flex">

    <!-- BACKDROP MOBILE -->
    <div x-show="sidebarOpen"
         @click="sidebarOpen = false"
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-40 lg:hidden"
         style="display: none;"></div>

    <!-- ============================================== -->
    <!-- SIDEBAR ADMIN                                 -->
    <!-- ============================================== -->
    <aside class="fixed inset-y-0 left-0 z-50 w-72 bg-slate-900 text-white flex flex-col transition-transform duration-300 transform lg:translate-x-0"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">

      <!-- Sidebar Header / Logo -->
      <div class="h-20 px-6 flex items-center justify-between border-b border-slate-800/80">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-brand flex items-center justify-center text-white shadow-lg shadow-red-900/40">
            <i class="fa-solid fa-wifi text-lg"></i>
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="font-extrabold text-lg tracking-wider text-white">BANTER<span class="text-brand">POOL</span></span>
              <span class="bg-red-500/20 text-red-400 border border-red-500/30 text-[9px] font-black px-1.5 py-0.5 rounded tracking-widest uppercase">NOC</span>
            </div>
            <p class="text-[10px] text-slate-400 font-medium">ISP Management Panel</p>
          </div>
        </a>

        <button type="button" @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-white text-xl">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <!-- Navigation Links -->
      <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto custom-scrollbar text-xs font-semibold">
        
        <p class="px-3 pb-2 text-[10px] font-extrabold tracking-widest text-slate-400 uppercase">Utama</p>

        <!-- 1. Dashboard Overview -->
        <a href="{{ route('admin.dashboard') }}"
           class="flex items-center justify-between px-3.5 py-3 rounded-xl transition duration-150 {{ request()->routeIs('admin.dashboard') ? 'bg-brand text-white shadow-md shadow-red-950/40 font-bold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
          <div class="flex items-center gap-3">
            <i class="fa-solid fa-chart-pie text-base w-5 text-center {{ request()->routeIs('admin.dashboard') ? 'text-white' : 'text-slate-400' }}"></i>
            <span>Dashboard NOC</span>
          </div>
          @if(request()->routeIs('admin.dashboard'))
            <span class="w-2 h-2 rounded-full bg-white"></span>
          @endif
        </a>

        <p class="px-3 pt-5 pb-2 text-[10px] font-extrabold tracking-widest text-slate-400 uppercase">Manajemen Operasional</p>

        <!-- 2. Monitoring Pesanan Pelanggan -->
        @php
          $pendingOrdersCount = 0;
          try {
              $pendingOrdersCount = \App\Models\Order::where('order_number', 'not like', 'PLG-%')->where('status', 'Menunggu Konfirmasi')->count();
          } catch (\Exception $e) {}
        @endphp
        <a href="{{ route('admin.pesanan') }}"
           class="flex items-center justify-between px-3.5 py-3 rounded-xl transition duration-150 {{ request()->routeIs('admin.pesanan*') ? 'bg-brand text-white shadow-md shadow-red-950/40 font-bold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
          <div class="flex items-center gap-3">
            <i class="fa-solid fa-cart-shopping text-base w-5 text-center {{ request()->routeIs('admin.pesanan*') ? 'text-white' : 'text-slate-400' }}"></i>
            <span>Monitoring Pesanan</span>
          </div>
          @if($pendingOrdersCount > 0)
            <span class="bg-emerald-500/20 text-emerald-300 text-[10px] font-bold px-2 py-0.5 rounded-full border border-emerald-500/30">{{ $pendingOrdersCount }} Baru</span>
          @endif
        </a>

        <!-- 3. Data Pelanggan Terdaftar -->
        @php
          $totalCustomersCount = 0;
          try {
              $totalCustomersCount = \App\Models\Order::whereNotNull('id_card_number')->count() ?: \App\Models\Order::count();
          } catch (\Exception $e) {}
        @endphp
        <a href="{{ route('admin.pelanggan') }}"
           class="flex items-center justify-between px-3.5 py-3 rounded-xl transition duration-150 {{ request()->routeIs('admin.pelanggan*') ? 'bg-brand text-white shadow-md shadow-red-950/40 font-bold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
          <div class="flex items-center gap-3">
            <i class="fa-solid fa-users text-base w-5 text-center {{ request()->routeIs('admin.pelanggan*') ? 'text-white' : 'text-slate-400' }}"></i>
            <span>Data Pelanggan</span>
          </div>
          @if($totalCustomersCount > 0)
            <span class="bg-slate-700/80 text-slate-200 text-[10px] font-bold px-2 py-0.5 rounded-full border border-slate-600">{{ $totalCustomersCount }}</span>
          @endif
        </a>

        <!-- 3. Tagihan Pelanggan -->
        @php
          $pendingBillsCount = 0;
          try {
              $pendingBillsCount = \App\Models\Bill::whereIn('status', ['Menunggu Verifikasi', 'Belum Bayar'])->count();
          } catch (\Exception $e) {}
        @endphp
        <a href="{{ route('admin.tagihan') }}"
           class="flex items-center justify-between px-3.5 py-3 rounded-xl transition duration-150 {{ request()->routeIs('admin.tagihan*') ? 'bg-brand text-white shadow-md shadow-red-950/40 font-bold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
          <div class="flex items-center gap-3">
            <i class="fa-solid fa-file-invoice-dollar text-base w-5 text-center {{ request()->routeIs('admin.tagihan*') ? 'text-white' : 'text-slate-400' }}"></i>
            <span>Monitoring Tagihan</span>
          </div>
          @if($pendingBillsCount > 0)
            <span class="bg-amber-400/20 text-amber-300 text-[10px] font-bold px-2 py-0.5 rounded-full border border-amber-400/30">{{ $pendingBillsCount }} Cek</span>
          @endif
        </a>

        <!-- 4. Laporan Masalah / Trouble Ticket -->
        @php
          $activeTicketsCount = 0;
          try {
              $adminTickets = Cache::get('trouble_tickets') ?: session('admin_tickets') ?: [];
              $activeTicketsCount = collect($adminTickets)->whereIn('status', ['Menunggu Respon', 'Sedang Ditangani'])->count();
          } catch (\Exception $e) {}
        @endphp
        <a href="{{ route('admin.laporan') }}"
           class="flex items-center justify-between px-3.5 py-3 rounded-xl transition duration-150 {{ request()->routeIs('admin.laporan*') ? 'bg-brand text-white shadow-md shadow-red-950/40 font-bold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
          <div class="flex items-center gap-3">
            <i class="fa-solid fa-triangle-exclamation text-base w-5 text-center {{ request()->routeIs('admin.laporan*') ? 'text-white' : 'text-slate-400' }}"></i>
            <span>Laporan Masalah</span>
          </div>
          @if($activeTicketsCount > 0)
            <span class="bg-red-500/20 text-red-300 text-[10px] font-bold px-2 py-0.5 rounded-full border border-red-500/30">{{ $activeTicketsCount }} Aktif</span>
          @endif
        </a>

        <!-- WhatsApp Gateway & Broadcast Pelanggan -->
        <a href="{{ route('admin.whatsapp') }}"
           class="flex items-center justify-between px-3.5 py-3 rounded-xl transition duration-150 {{ request()->routeIs('admin.whatsapp*') ? 'bg-emerald-600 text-white shadow-md shadow-emerald-950/40 font-bold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
          <div class="flex items-center gap-3">
            <i class="fa-brands fa-whatsapp text-base w-5 text-center {{ request()->routeIs('admin.whatsapp*') ? 'text-white' : 'text-emerald-400' }}"></i>
            <span>WhatsApp Gateway</span>
          </div>
          <span class="bg-emerald-500/20 text-emerald-300 text-[10px] font-bold px-2 py-0.5 rounded-full border border-emerald-500/30">BLAST</span>
        </a>

        <p class="px-3 pt-5 pb-2 text-[10px] font-extrabold tracking-widest text-slate-400 uppercase">Jaringan & Infrastruktur</p>

        <!-- 4. MRTG Bandwidth Monitoring -->
        <a href="{{ route('admin.mrtg') }}"
           class="flex items-center justify-between px-3.5 py-3 rounded-xl transition duration-150 {{ request()->routeIs('admin.mrtg*') ? 'bg-brand text-white shadow-md shadow-red-950/40 font-bold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
          <div class="flex items-center gap-3">
            <i class="fa-solid fa-chart-line text-base w-5 text-center {{ request()->routeIs('admin.mrtg*') ? 'text-white' : 'text-slate-400' }}"></i>
            <span>MikroTik Monitoring</span>
          </div>
          <span class="flex items-center gap-1 text-[10px] text-emerald-400">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
            SNMP
          </span>
        </a>

        <!-- 5. ODC Map & Fiber Optik -->
        <a href="{{ route('admin.odc-map') }}"
           class="flex items-center justify-between px-3.5 py-3 rounded-xl transition duration-150 {{ request()->routeIs('admin.odc-map*') ? 'bg-brand text-white shadow-md shadow-red-950/40 font-bold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
          <div class="flex items-center gap-3">
            <i class="fa-solid fa-map-location-dot text-base w-5 text-center {{ request()->routeIs('admin.odc-map*') ? 'text-white' : 'text-slate-400' }}"></i>
            <span>Peta ODC & Fiber</span>
          </div>
          <span class="bg-blue-500/20 text-blue-300 text-[10px] font-bold px-1.5 py-0.5 rounded border border-blue-500/30">GIS</span>
        </a>

        <p class="px-3 pt-5 pb-2 text-[10px] font-extrabold tracking-widest text-slate-400 uppercase">Operasional Lapangan</p>

        <!-- 6. Portal Teknisi Lapangan -->
        <a href="{{ route('teknisi.dashboard') }}"
           class="flex items-center justify-between px-3.5 py-3 rounded-xl transition duration-150 text-amber-300 hover:bg-slate-800 hover:text-amber-200 border border-amber-500/20 bg-amber-500/10">
          <div class="flex items-center gap-3">
            <i class="fa-solid fa-screwdriver-wrench text-base w-5 text-center text-amber-400"></i>
            <span class="font-bold">Portal Teknisi</span>
          </div>
          <span class="bg-amber-500/20 text-amber-300 text-[9px] font-bold px-1.5 py-0.5 rounded border border-amber-500/30 uppercase">POV</span>
        </a>

        <!-- 7. Portal Kolektor Lapangan -->
        <a href="{{ route('kolektor.dashboard') }}"
           class="flex items-center justify-between px-3.5 py-3 rounded-xl transition duration-150 text-emerald-300 hover:bg-slate-800 hover:text-emerald-200 border border-emerald-500/20 bg-emerald-500/10">
          <div class="flex items-center gap-3">
            <i class="fa-solid fa-money-bill-wave text-base w-5 text-center text-emerald-400"></i>
            <span class="font-bold">Portal Kolektor</span>
          </div>
          <span class="bg-emerald-500/20 text-emerald-300 text-[9px] font-bold px-1.5 py-0.5 rounded border border-emerald-500/30 uppercase">POV</span>
        </a>

      </nav>

      <!-- Sidebar Footer: Switcher & Profile -->
      <div class="p-4 border-t border-slate-800/80 bg-slate-950/40 space-y-3">
        <!-- Switch to Client Website -->
        <a href="{{ route('home') }}" target="_blank"
           class="w-full flex items-center justify-center gap-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold py-2.5 px-3 rounded-xl transition border border-slate-700">
          <i class="fa-solid fa-globe text-xs"></i>
          <span>Lihat Web Pelanggan</span>
          <i class="fa-solid fa-arrow-up-right-from-square text-[10px] text-slate-400"></i>
        </a>

        <!-- Admin Info -->
        <div class="flex items-center justify-between pt-1">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-full bg-brand/20 border border-brand/40 text-brand flex items-center justify-center font-bold text-xs">
              AD
            </div>
            <div>
              <p class="text-xs font-bold text-white leading-tight">Admin NOC</p>
              <p class="text-[10px] text-slate-400 leading-tight">noc@banterpool.net</p>
            </div>
          </div>
          
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" title="Keluar" class="w-8 h-8 rounded-lg text-slate-400 hover:text-red-400 hover:bg-red-500/10 flex items-center justify-center transition">
              <i class="fa-solid fa-right-from-bracket text-xs"></i>
            </button>
          </form>
        </div>
      </div>

    </aside>

    <!-- ============================================== -->
    <!-- CONTENT AREA                                  -->
    <!-- ============================================== -->
    <div class="flex-1 flex flex-col min-w-0 lg:pl-72">

      <!-- TOPBAR ADMIN -->
      <header class="h-20 bg-white border-b border-slate-200/80 sticky top-0 z-30 px-4 sm:px-8 flex items-center justify-between">
        
        <!-- Sisi Kiri: Hamburger + Breadcrumb Info -->
        <div class="flex items-center gap-4">
          <button type="button" @click="sidebarOpen = true" class="lg:hidden text-slate-600 hover:text-black text-xl p-1">
            <i class="fa-solid fa-bars"></i>
          </button>
          
          <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 font-medium">
              <a href="{{ route('admin.dashboard') }}" class="hover:text-brand">Admin</a>
              <i class="fa-solid fa-chevron-right text-[9px] text-slate-400"></i>
              <span class="text-slate-900 font-bold">@yield('page-title', 'Dashboard')</span>
            </div>
            <h1 class="text-lg font-black text-slate-900 tracking-tight hidden sm:block">
              @yield('page-title', 'Dashboard')
            </h1>
          </div>
        </div>

        <!-- Sisi Kanan: NOC Indicator + Clock + Notification + Profile -->
        <div class="flex items-center gap-3 sm:gap-5 text-xs">

          <!-- NOC Status Live Pill -->
          <div class="hidden md:flex items-center gap-2 bg-emerald-50 text-emerald-700 border border-emerald-200 px-3 py-1.5 rounded-full font-bold">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span>NOC Online: 99.98%</span>
          </div>

          <!-- Digital Clock & Real-Time Date Widget -->
          <div class="hidden sm:flex flex-col text-right font-mono"
               x-data="{
                 time: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' WIB',
                 date: new Date().toLocaleDateString('id-ID', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' })
               }"
               x-init="setInterval(() => {
                 time = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' WIB';
                 date = new Date().toLocaleDateString('id-ID', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' });
               }, 1000)">
            <span class="font-extrabold text-slate-900 text-sm tracking-tight" x-text="time"></span>
            <span class="text-[10px] text-slate-500 font-semibold font-sans" x-text="date + ' (Banyumas)'"></span>
          </div>

          <div class="h-6 w-px bg-slate-200 hidden sm:block"></div>

          @php
            $recentNotifications = [];
            try {
              $pOrders = \App\Models\Order::where('order_number', 'not like', 'PLG-%')->where('status', 'Menunggu Konfirmasi')->latest()->take(3)->get();
              foreach ($pOrders as $po) {
                $recentNotifications[] = [
                  'title' => 'Pesanan Baru: ' . $po->order_number,
                  'desc' => $po->customer_name . ' (' . ($po->package_name ?? 'WiFi') . ')',
                  'time' => $po->created_at ? $po->created_at->diffForHumans() : 'Baru saja',
                  'url' => route('admin.pesanan'),
                  'icon' => 'fa-solid fa-cart-shopping',
                  'icon_bg' => 'bg-emerald-100 text-emerald-600',
                ];
              }

              $pBills = \App\Models\Bill::where('status', 'Menunggu Verifikasi')->latest()->take(3)->get();
              foreach ($pBills as $pb) {
                $recentNotifications[] = [
                  'title' => 'Bukti Transfer Masuk',
                  'desc' => $pb->bill_number . ' - ' . $pb->customer_name,
                  'time' => $pb->created_at ? $pb->created_at->diffForHumans() : 'Baru saja',
                  'url' => route('admin.tagihan'),
                  'icon' => 'fa-solid fa-receipt',
                  'icon_bg' => 'bg-amber-100 text-amber-600',
                ];
              }

              $cTickets = Cache::get('trouble_tickets') ?: session('admin_tickets') ?: [];
              $pTickets = collect($cTickets)->where('status', 'Menunggu Respon')->take(3);
              foreach ($pTickets as $pt) {
                $recentNotifications[] = [
                  'title' => 'Laporan Kendala: ' . ($pt['type'] ?? 'Gangguan'),
                  'desc' => ($pt['id'] ?? '') . ' - ' . ($pt['customer_name'] ?? 'Pelanggan'),
                  'time' => $pt['created_at'] ?? 'Baru saja',
                  'url' => route('admin.laporan'),
                  'icon' => 'fa-solid fa-triangle-exclamation',
                  'icon_bg' => 'bg-red-100 text-brand',
                ];
              }
            } catch (\Exception $e) {}
            $totalNotifCount = count($recentNotifications);
          @endphp

          <!-- Notification Bell -->
          <div class="relative" x-data="{ openNotify: false }">
            <button type="button" @click="openNotify = !openNotify" @click.away="openNotify = false"
                    class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center relative transition"
                    title="Pemberitahuan Sistem">
              <i class="fa-regular fa-bell text-sm"></i>
              @if($totalNotifCount > 0)
                <span class="absolute top-2 right-2 w-2 h-2 rounded-full bg-brand animate-pulse"></span>
              @endif
            </button>

            <!-- Dropdown Notif -->
            <div x-show="openNotify" style="display: none;"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="transform opacity-0 scale-95"
                 x-transition:enter-end="transform opacity-100 scale-100"
                 class="absolute right-0 mt-2 w-80 bg-white border border-slate-200 rounded-2xl shadow-xl py-3 z-50">
              <div class="px-4 pb-2 border-b border-slate-100 flex items-center justify-between">
                <p class="font-bold text-xs text-slate-900">Pemberitahuan Sistem</p>
                @if($totalNotifCount > 0)
                  <span class="bg-red-100 text-brand text-[10px] font-bold px-2 py-0.5 rounded-full">{{ $totalNotifCount }} Baru</span>
                @else
                  <span class="text-slate-400 text-[10px] font-medium">0 Baru</span>
                @endif
              </div>
              <div class="divide-y divide-slate-100 text-xs max-h-80 overflow-y-auto">
                @forelse($recentNotifications as $notif)
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
                  <div class="py-8 text-center text-xs text-slate-400 space-y-1.5">
                    <i class="fa-regular fa-bell-slash text-2xl text-slate-300 block"></i>
                    <p class="font-medium text-slate-600">Tidak ada notifikasi baru</p>
                    <p class="text-[10px] text-slate-400">Semua laporan kendala, pesanan, dan tagihan telah terproses</p>
                  </div>
                @endforelse
              </div>
            </div>
          </div>

          <!-- Quick Link Web Pelanggan -->
          <a href="{{ route('home') }}" class="bg-brand hover:bg-brand-700 text-white font-bold text-xs px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 shadow-sm">
            <i class="fa-solid fa-globe"></i>
            <span class="hidden sm:inline">Web Pelanggan</span>
          </a>

        </div>

      </header>

      <!-- FLASH MESSAGES -->
      @if(session('success'))
        <div class="mx-4 sm:mx-8 mt-6 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold px-4 py-3 rounded-xl flex items-center justify-between shadow-sm">
          <div class="flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
            <span>{{ session('success') }}</span>
          </div>
          <button type="button" onclick="this.parentElement.remove()" class="text-emerald-700 hover:text-emerald-900 font-bold">×</button>
        </div>
      @endif

      <!-- MAIN PAGE CONTENT -->
      <main class="flex-1 p-4 sm:p-8">
        @yield('content')
      </main>

    </div>

  </div>

  @stack('scripts')
</body>
</html>
