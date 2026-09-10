<footer class="relative z-20 bg-white border-t border-gray-200 py-12 mt-16 shadow-[0_-10px_35px_rgba(0,0,0,0.03)]">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-3 gap-8">
    <div>
      <div class="flex items-center gap-2 mb-2">
        <i class="fa-solid fa-wifi text-brand text-xl"></i>
        <span class="font-extrabold text-lg">BANTER<span class="text-brand">POOL</span></span>
      </div>
      <p class="text-xs text-gray-500">WiFi Super Kencang, Koneksi Tanpa Batas</p>
    </div>
    <div>
      <h4 class="text-brand text-sm font-bold mb-3">Navigasi</h4>
      <ul class="text-xs text-gray-600 space-y-2">
        <li><a href="{{ route('home') }}" class="hover:text-brand transition">Beranda</a></li>
        <li><a href="{{ route('paket') }}" class="hover:text-brand transition">Berlangganan</a></li>
        <li><a href="{{ auth()->check() ? route('tagihan') : route('login') }}" class="hover:text-brand transition">Cek Tagihan</a></li>
        <li><a href="{{ auth()->check() ? route('laporan.index') : route('login') }}" class="hover:text-brand transition">Laporan Masalah</a></li>
        <li><a href="{{ route('tentang-kami') }}" class="hover:text-brand transition">Tentang Kami</a></li>
      </ul>
    </div>
    <div>
      <h4 class="text-brand text-sm font-bold mb-3">Hubungi Kami</h4>
      <ul class="text-xs text-gray-600 space-y-2">
        <li>
          <a href="https://wa.me/628818679774" target="_blank" class="hover:text-emerald-600 transition flex items-center">
            <i class="fa-brands fa-whatsapp mr-1.5 text-emerald-500 text-sm"></i> 0881-8679-774
          </a>
        </li>
        <li><i class="fa-regular fa-envelope mr-1 text-brand"></i> banterpool@gmail.com</li>
        <li><i class="fa-solid fa-location-dot mr-1 text-brand"></i> Cilongok, Banyumas, Jawa Tengah</li>
      </ul>
    </div>
  </div>

  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-10 pt-6 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between text-[11px] text-gray-400 gap-3">
    <p>&copy; {{ date('Y') }} BANTERPOOL (PT Saga Infrastruktur Media Selaras). Seluruh hak cipta dilindungi.</p>
    <div class="flex items-center gap-3">
      <span class="flex items-center gap-1.5 text-emerald-600 font-medium">
        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
        Jaringan Fiber Optik Aktif
      </span>
    </div>
  </div>
</footer>
