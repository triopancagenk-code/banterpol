<nav class="bg-white/85 backdrop-blur-md border-b border-gray-200/60 sticky top-0 z-50 py-3 transition duration-150" x-data="{ mobileMenuOpen: false }">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center">

    <!-- Logo & Slogan Banterpool -->
    <a href="{{ route('home') }}" class="flex items-center gap-2">
      <i class="fa-solid fa-wifi text-brand text-2xl"></i>
      <div>
        <span class="font-extrabold text-xl text-black">BANTER<span class="text-brand">POOL</span></span>
        <p class="text-[10px] text-gray-500 leading-none">WiFi Super Kencang, Koneksi Tanpa Batas</p>
      </div>
    </a>

    <!-- Menu Navigasi Desktop -->
    <div class="hidden md:flex gap-6 text-sm font-medium">
      <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'text-brand font-bold border-b-2 border-brand pb-1' : 'text-gray-700 hover:text-brand' }}">Beranda</a>
      <a href="{{ route('paket') }}" class="{{ request()->routeIs('paket') ? 'text-brand font-bold border-b-2 border-brand pb-1' : 'text-gray-700 hover:text-brand' }}">Berlangganan</a>
      <a href="{{ auth()->check() ? route('tagihan') : route('login') }}" class="{{ request()->routeIs('tagihan*') ? 'text-brand font-bold border-b-2 border-brand pb-1' : 'text-gray-700 hover:text-brand' }}">Tagihan</a>
      <a href="{{ auth()->check() ? route('laporan.index') : route('login') }}" class="{{ request()->routeIs('laporan*') ? 'text-brand font-bold border-b-2 border-brand pb-1' : 'text-gray-700 hover:text-brand' }}">Laporan Masalah</a>
      <a href="{{ route('tentang-kami') }}" class="{{ request()->routeIs('tentang-kami*') ? 'text-brand font-bold border-b-2 border-brand pb-1' : 'text-gray-700 hover:text-brand' }}">Tentang Kami</a>
    </div>

    <!-- Area Kanan: Garis 3 Menu Mobile & Profil User -->
    <div class="flex items-center gap-2 sm:gap-3">

      <!-- Tombol Garis 3 (Hamburger Menu Mobile) di sebelah kiri profil -->
      <button type="button"
              @click="mobileMenuOpen = !mobileMenuOpen"
              class="md:hidden w-8 h-8 sm:w-9 sm:h-9 rounded-xl border border-gray-200 text-gray-700 hover:text-brand hover:border-brand flex items-center justify-center transition focus:outline-none bg-gray-50 active:scale-95"
              aria-label="Menu Utama">
        <i class="fa-solid text-sm transition duration-200" :class="mobileMenuOpen ? 'fa-xmark text-brand text-base' : 'fa-bars text-gray-700'"></i>
      </button>

      @auth
        <!-- Dynamic Notification Bell Pelanggan -->
        @php
          $custNotifications = [];
          try {
            $cBills = \App\Models\Bill::where('user_id', auth()->id())->where('status', 'Belum Bayar')->latest()->take(2)->get();
            foreach ($cBills as $cb) {
              $custNotifications[] = [
                'title' => 'Tagihan Siap Dibayar',
                'desc' => $cb->bill_number . ' - Rp ' . number_format($cb->amount ?? 0, 0, ',', '.'),
                'time' => $cb->created_at ? $cb->created_at->diffForHumans() : 'Baru saja',
                'url' => route('tagihan.payment', ['id' => $cb->id]),
                'icon' => 'fa-solid fa-file-invoice-dollar',
                'icon_bg' => 'bg-amber-100 text-amber-600',
              ];
            }

            $cOrders = \App\Models\Order::where('user_id', auth()->id())->latest()->take(2)->get();
            foreach ($cOrders as $co) {
              $custNotifications[] = [
                'title' => 'Pesanan ' . $co->order_number,
                'desc' => ($co->package_name ?? 'WiFi') . ' • Status: ' . $co->status,
                'time' => $co->created_at ? $co->created_at->diffForHumans() : 'Baru saja',
                'url' => route('tagihan'),
                'icon' => 'fa-solid fa-box',
                'icon_bg' => 'bg-blue-100 text-blue-600',
              ];
            }

            $cTickets = Cache::get('trouble_tickets') ?: session('admin_tickets') ?: [];
            $cUserTickets = collect($cTickets)->where('user_id', auth()->id())->take(2);
            foreach ($cUserTickets as $ct) {
              $custNotifications[] = [
                'title' => 'Laporan: ' . ($ct['id'] ?? 'Tiket'),
                'desc' => ($ct['type'] ?? 'Gangguan') . ' • ' . ($ct['status'] ?? 'Diproses'),
                'time' => $ct['created_at'] ?? 'Baru saja',
                'url' => route('laporan.index'),
                'icon' => 'fa-solid fa-triangle-exclamation',
                'icon_bg' => 'bg-red-100 text-brand',
              ];
            }
          } catch (\Exception $e) {}
          $custNotifCount = count($custNotifications);
        @endphp

        <div class="relative" x-data="{ openNotify: false }">
          <button type="button" @click="openNotify = !openNotify" @click.away="openNotify = false"
                  class="w-9 h-9 rounded-xl border border-gray-200 hover:border-brand text-gray-600 hover:text-brand flex items-center justify-center relative transition bg-gray-50/50"
                  title="Notifikasi & Pemberitahuan">
            <i class="fa-regular fa-bell text-sm"></i>
            @if($custNotifCount > 0)
              <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-brand animate-pulse"></span>
            @endif
          </button>

          <!-- Dropdown Notif Pelanggan -->
          <div x-show="openNotify" style="display: none;"
               x-transition:enter="transition ease-out duration-150"
               x-transition:enter-start="transform opacity-0 scale-95"
               x-transition:enter-end="transform opacity-100 scale-100"
               class="absolute right-0 mt-2 w-72 sm:w-80 bg-white border border-gray-200 rounded-2xl shadow-xl py-3 z-50 text-gray-800">
            <div class="px-4 pb-2 border-b border-gray-100 flex items-center justify-between">
              <p class="font-bold text-xs text-gray-900">Notifikasi Anda</p>
              @if($custNotifCount > 0)
                <span class="bg-red-100 text-brand text-[10px] font-bold px-2 py-0.5 rounded-full">{{ $custNotifCount }} Baru</span>
              @else
                <span class="text-gray-400 text-[10px] font-medium">0 Baru</span>
              @endif
            </div>
            <div class="divide-y divide-gray-100 text-xs max-h-80 overflow-y-auto">
              @forelse($custNotifications as $notif)
                <a href="{{ $notif['url'] }}" class="p-3 flex items-start gap-3 hover:bg-gray-50 transition block">
                  <div class="w-7 h-7 rounded-lg {{ $notif['icon_bg'] }} flex items-center justify-center shrink-0 text-xs">
                    <i class="{{ $notif['icon'] }}"></i>
                  </div>
                  <div class="flex-1">
                    <p class="font-bold text-gray-900 leading-tight">{{ $notif['title'] }}</p>
                    <p class="text-[11px] text-gray-500 mt-0.5">{{ $notif['desc'] }}</p>
                    <span class="text-[9px] text-gray-400 mt-0.5 block">{{ $notif['time'] }}</span>
                  </div>
                </a>
              @empty
                <div class="py-7 text-center text-xs text-gray-400 space-y-1">
                  <i class="fa-regular fa-bell-slash text-2xl text-gray-300 block"></i>
                  <p class="font-medium text-gray-600">Tidak ada notifikasi baru</p>
                  <p class="text-[10px] text-gray-400">Semua tagihan dan layanan Anda aktif normal</p>
                </div>
              @endforelse
            </div>
          </div>
        </div>

        <div class="relative" x-data="{ open: false }">
          <button @click="open = !open"
                  @click.away="open = false"
                  class="flex items-center gap-2 text-sm font-semibold text-gray-700 hover:text-black focus:outline-none py-1">

            <!-- Tampilan Foto Profil Dinamis di Navbar -->
            @if(auth()->user()->avatar)
              <img src="{{ \Illuminate\Support\Str::startsWith(auth()->user()->avatar, ['http://', 'https://']) ? auth()->user()->avatar : asset('storage/' . auth()->user()->avatar) }}" alt="{{ auth()->user()->name }}" class="w-8 h-8 rounded-full object-cover border border-gray-300">
            @else
              <div class="w-8 h-8 rounded-full bg-gray-200 border border-gray-300 flex items-center justify-center text-gray-600">
                <i class="fa-regular fa-user text-xs"></i>
              </div>
            @endif

            <div class="text-left hidden sm:block">
              <p class="text-xs font-bold leading-none text-black">{{ auth()->user()->name }}</p>
              <p class="text-[10px] text-gray-400 leading-tight">{{ auth()->user()->email }}</p>
            </div>
            <i class="fa-solid fa-chevron-down text-[10px] text-gray-500 transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
          </button>

          <!-- Dropdown Menu -->
          <div x-show="open"
               x-transition:enter="transition ease-out duration-100"
               x-transition:enter-start="transform opacity-0 scale-95"
               x-transition:enter-end="transform opacity-100 scale-100"
               x-transition:leave="transition ease-in duration-75"
               x-transition:leave-start="transform opacity-100 scale-100"
               x-transition:leave-end="transform opacity-0 scale-95"
               class="absolute right-0 mt-2 w-52 bg-white border border-gray-100 rounded-xl shadow-lg py-2 z-50"
               style="display: none;">

            @if(auth()->user()->isAdmin())
              <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs text-brand hover:bg-red-50 font-bold transition">
                <i class="fa-solid fa-chart-pie text-sm text-brand"></i>
                <span>Admin NOC Panel</span>
              </a>
            @endif

            @if(auth()->user()->isTechnician())
              <a href="{{ route('teknisi.dashboard') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs text-amber-600 hover:bg-amber-50 font-bold transition">
                <i class="fa-solid fa-screwdriver-wrench text-sm text-amber-600"></i>
                <span>Portal Teknisi</span>
              </a>
            @endif

            @if(auth()->user()->isCollector())
              <a href="{{ route('kolektor.dashboard') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs text-emerald-600 hover:bg-emerald-50 font-bold transition">
                <i class="fa-solid fa-money-bill-wave text-sm text-emerald-600"></i>
                <span>Portal Kolektor</span>
              </a>
            @endif

            <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs text-gray-700 hover:bg-gray-50 hover:text-brand font-semibold transition">
              <i class="fa-solid fa-gear text-sm text-gray-400"></i>
              <span>Pengaturan</span>
            </a>

            <hr class="my-1 border-gray-100">

            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button type="submit" class="w-full text-left flex items-center gap-2.5 px-4 py-2 text-xs text-red-600 hover:bg-red-50 font-semibold transition">
                <i class="fa-solid fa-right-from-bracket text-sm text-red-500"></i>
                <span>Logout</span>
              </button>
            </form>
          </div>
        </div>
      @else
        <a href="{{ route('login') }}" class="text-sm font-semibold text-gray-700 hover:text-brand">Masuk</a>
        <a href="{{ route('register') }}" class="bg-brand hover:bg-brand-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">Daftar</a>
      @endauth
    </div>

  </div>

  <!-- Menu Navigasi Mobile (Muncul saat tombol garis 3 diklik) -->
  <div x-show="mobileMenuOpen"
       x-cloak
       @click.away="mobileMenuOpen = false"
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="opacity-0 -translate-y-2"
       x-transition:enter-end="opacity-100 translate-y-0"
       x-transition:leave="transition ease-in duration-150"
       x-transition:leave-start="opacity-100 translate-y-0"
       x-transition:leave-end="opacity-0 -translate-y-2"
       class="md:hidden border-t border-gray-100 bg-white/95 backdrop-blur-md px-4 pt-3 pb-4 space-y-1 shadow-xl mt-3">

    <p class="text-[10px] font-extrabold uppercase tracking-wider text-gray-400 px-3 py-1">Menu Utama</p>

    <a href="{{ route('home') }}"
       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('home') ? 'bg-red-50 text-brand' : 'text-gray-700 hover:bg-gray-50 hover:text-brand' }}">
      <i class="fa-solid fa-house text-sm w-5 text-center {{ request()->routeIs('home') ? 'text-brand' : 'text-gray-400' }}"></i>
      <span>Beranda</span>
    </a>

    <a href="{{ route('paket') }}"
       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('paket') ? 'bg-red-50 text-brand' : 'text-gray-700 hover:bg-gray-50 hover:text-brand' }}">
      <i class="fa-solid fa-box-archive text-sm w-5 text-center {{ request()->routeIs('paket') ? 'text-brand' : 'text-gray-400' }}"></i>
      <span>Berlangganan</span>
    </a>

    <a href="{{ auth()->check() ? route('tagihan') : route('login') }}"
       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('tagihan*') ? 'bg-red-50 text-brand' : 'text-gray-700 hover:bg-gray-50 hover:text-brand' }}">
      <i class="fa-solid fa-file-invoice-dollar text-sm w-5 text-center {{ request()->routeIs('tagihan*') ? 'text-brand' : 'text-gray-400' }}"></i>
      <span>Tagihan</span>
    </a>

    <a href="{{ auth()->check() ? route('laporan.index') : route('login') }}"
       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('laporan*') ? 'bg-red-50 text-brand' : 'text-gray-700 hover:bg-gray-50 hover:text-brand' }}">
      <i class="fa-solid fa-triangle-exclamation text-sm w-5 text-center {{ request()->routeIs('laporan*') ? 'text-brand' : 'text-gray-400' }}"></i>
      <span>Laporan Masalah</span>
    </a>

    <a href="{{ route('tentang-kami') }}"
       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('tentang-kami*') ? 'bg-red-50 text-brand' : 'text-gray-700 hover:bg-gray-50 hover:text-brand' }}">
      <i class="fa-solid fa-circle-info text-sm w-5 text-center {{ request()->routeIs('tentang-kami*') ? 'text-brand' : 'text-gray-400' }}"></i>
      <span>Tentang Kami</span>
    </a>
  </div>
</nav>
