@extends('layouts.admin')

@section('title', 'Admin NOC Banterpool - Dashboard Operasional')
@section('page-title', 'Dashboard Operasional NOC')

@section('content')
<div class="space-y-8">

  <!-- ============================================== -->
  <!-- 0. PENCARIAN GLOBAL & AUTOCOMPLETE OPERASIONAL -->
  <!-- ============================================== -->
  <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs relative"
       x-data="{
         searchQuery: '{{ addslashes(request('q', '')) }}',
         categoryFilter: 'all',
         suggestions: [],
         showDropdown: false,
         loading: false,
         selectedIndex: -1,
         hasSearched: false,

         async fetchSuggestions() {
           const query = this.searchQuery.trim();
           if (!query || query.length < 1) {
             this.suggestions = [];
             this.showDropdown = false;
             this.selectedIndex = -1;
             this.hasSearched = false;
             return;
           }

           this.loading = true;
           try {
             const url = `{{ route('admin.dashboard') }}?autocomplete=1&q=${encodeURIComponent(query)}&type=${encodeURIComponent(this.categoryFilter)}`;
             const res = await fetch(url, {
               headers: { 'X-Requested-With': 'XMLHttpRequest' }
             });
             if (res.ok) {
               this.suggestions = await res.json();
               this.showDropdown = true;
               this.selectedIndex = -1;
               this.hasSearched = true;
             }
           } catch (err) {
             console.error('Error fetching dashboard suggestions:', err);
             this.suggestions = [];
           } finally {
             this.loading = false;
           }
         },

         setCategory(cat) {
           this.categoryFilter = cat;
           if (this.searchQuery.trim().length > 0) {
             this.fetchSuggestions();
           }
         },

         selectItem(item) {
           if (item && item.url) {
             this.showDropdown = false;
             window.location.href = item.url;
           }
         },

         submitForm() {
           if (this.selectedIndex >= 0 && this.suggestions[this.selectedIndex]) {
             this.selectItem(this.suggestions[this.selectedIndex]);
             return;
           }
           if (this.searchQuery.trim().length > 0) {
             this.$refs.searchForm.submit();
           }
         },

         onKeyDown(e) {
           if (!this.showDropdown) {
             if (e.key === 'ArrowDown' && this.suggestions.length > 0) {
               this.showDropdown = true;
               this.selectedIndex = 0;
               e.preventDefault();
             }
             return;
           }

           if (e.key === 'ArrowDown') {
             e.preventDefault();
             if (this.suggestions.length > 0) {
               this.selectedIndex = (this.selectedIndex + 1) % this.suggestions.length;
               this.scrollSelectedIntoView();
             }
           } else if (e.key === 'ArrowUp') {
             e.preventDefault();
             if (this.suggestions.length > 0) {
               this.selectedIndex = (this.selectedIndex - 1 + this.suggestions.length) % this.suggestions.length;
               this.scrollSelectedIntoView();
             }
           } else if (e.key === 'Enter') {
             if (this.selectedIndex >= 0 && this.suggestions[this.selectedIndex]) {
               e.preventDefault();
               this.selectItem(this.suggestions[this.selectedIndex]);
             }
           } else if (e.key === 'Escape') {
             this.showDropdown = false;
           }
         },

         scrollSelectedIntoView() {
           this.$nextTick(() => {
             const list = this.$refs.suggestionList;
             const active = list ? list.querySelector('.suggestion-item-active') : null;
             if (active && list) {
               active.scrollIntoView({ block: 'nearest' });
             }
           });
         },

         clearSearch() {
           this.searchQuery = '';
           this.suggestions = [];
           this.showDropdown = false;
           this.selectedIndex = -1;
           this.hasSearched = false;
           this.$refs.searchInput.focus();
         },

         highlightMatch(text, query) {
           if (!text) return '';
           if (!query) return text;
           const escaped = query.replace(/[.*+?^${}()|[\\]\\\\]/g, '\\\\$&');
           try {
             const regex = new RegExp(`(${escaped})`, 'gi');
             return text.replace(regex, '<mark class=\"bg-amber-100 text-amber-900 font-extrabold px-0.5 rounded\">$1</mark>');
           } catch(e) {
             return text;
           }
         }
       }"
       @click.outside="showDropdown = false"
       @keydown.window.ctrl.k.prevent="$refs.searchInput.focus(); $refs.searchInput.select()"
       @keydown.window.slash.prevent="if (document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') { $refs.searchInput.focus(); $refs.searchInput.select(); }">

    <!-- Header & Kategori Filter -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-3.5">
      <div>
        <div class="flex items-center gap-2">
          <div class="w-7 h-7 rounded-xl bg-brand/10 text-brand flex items-center justify-center text-xs">
            <i class="fa-solid fa-magnifying-glass"></i>
          </div>
          <h2 class="text-sm sm:text-base font-extrabold text-slate-900">Pencarian Cepat Operasional NOC</h2>
          <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse mr-1.5"></span>
            Autocomplete Aktif
          </span>
        </div>
        <p class="text-xs text-slate-400 mt-1">Cari pelanggan, pesanan baru, invoice tagihan, atau tiket masalah secara instan.</p>
      </div>

      <!-- Kategori Filter Tabs -->
      <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0 scrollbar-none text-xs">
        <button type="button"
                @click="setCategory('all')"
                :class="categoryFilter === 'all' ? 'bg-slate-900 text-white shadow-xs font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 font-medium'"
                class="px-2.5 py-1.5 rounded-xl transition shrink-0 text-[11px]">
          Semua
        </button>
        <button type="button"
                @click="setCategory('pelanggan')"
                :class="categoryFilter === 'pelanggan' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'bg-blue-50 text-blue-700 hover:bg-blue-100 font-medium'"
                class="px-2.5 py-1.5 rounded-xl transition shrink-0 text-[11px] flex items-center gap-1">
          <i class="fa-solid fa-user text-[10px]"></i> Pelanggan
        </button>
        <button type="button"
                @click="setCategory('pesanan')"
                :class="categoryFilter === 'pesanan' ? 'bg-emerald-600 text-white shadow-xs font-bold' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-medium'"
                class="px-2.5 py-1.5 rounded-xl transition shrink-0 text-[11px] flex items-center gap-1">
          <i class="fa-solid fa-cart-shopping text-[10px]"></i> Pesanan
        </button>
        <button type="button"
                @click="setCategory('tagihan')"
                :class="categoryFilter === 'tagihan' ? 'bg-amber-600 text-white shadow-xs font-bold' : 'bg-amber-50 text-amber-700 hover:bg-amber-100 font-medium'"
                class="px-2.5 py-1.5 rounded-xl transition shrink-0 text-[11px] flex items-center gap-1">
          <i class="fa-solid fa-receipt text-[10px]"></i> Tagihan
        </button>
        <button type="button"
                @click="setCategory('laporan')"
                :class="categoryFilter === 'laporan' ? 'bg-red-600 text-white shadow-xs font-bold' : 'bg-red-50 text-red-700 hover:bg-red-100 font-medium'"
                class="px-2.5 py-1.5 rounded-xl transition shrink-0 text-[11px] flex items-center gap-1">
          <i class="fa-solid fa-headset text-[10px]"></i> Tiket Masalah
        </button>
      </div>
    </div>

    <!-- Form Search Input Box -->
    <form method="GET" action="{{ route('admin.dashboard') }}" x-ref="searchForm" @submit.prevent="submitForm()" class="relative">
      <div class="relative flex items-center">
        <!-- Search Icon -->
        <div class="absolute left-3.5 sm:left-4 text-slate-400 pointer-events-none text-sm sm:text-base">
          <i class="fa-solid fa-magnifying-glass"></i>
        </div>

        <!-- Input Field -->
        <input type="text"
               name="q"
               x-ref="searchInput"
               x-model="searchQuery"
               @input.debounce.150ms="fetchSuggestions()"
               @keydown="onKeyDown($event)"
               @focus="if (suggestions.length > 0) showDropdown = true"
               autocomplete="off"
               placeholder="Ketik nama pelanggan, user PPPoE, No. HP, No. Tagihan (INV-...), ID Tiket (TCK-...)..."
               class="w-full pl-10 sm:pl-11 pr-28 sm:pr-32 py-2.5 sm:py-3 text-xs sm:text-sm bg-slate-50 hover:bg-slate-100/70 focus:bg-white border border-slate-300 rounded-2xl focus:ring-2 focus:ring-brand focus:border-brand shadow-inner transition placeholder:text-slate-400 font-medium text-slate-800">

        <!-- Action Tools di Kanan Input -->
        <div class="absolute right-2.5 flex items-center gap-1.5">
          <!-- Spinner Loading -->
          <div x-show="loading" style="display: none;" class="text-slate-400 text-xs px-1">
            <i class="fa-solid fa-spinner fa-spin text-brand"></i>
          </div>

          <!-- Clear Button -->
          <button type="button"
                  x-show="searchQuery.length > 0"
                  style="display: none;"
                  @click="clearSearch()"
                  class="w-6 h-6 rounded-lg bg-slate-200/80 hover:bg-slate-300 text-slate-600 flex items-center justify-center text-xs transition"
                  title="Hapus pencarian">
            <i class="fa-solid fa-xmark"></i>
          </button>

          <!-- Shortcut Pill (Desktop) -->
          <kbd class="hidden md:inline-flex items-center px-1.5 py-0.5 text-[10px] font-bold text-slate-400 bg-slate-200/60 border border-slate-300/80 rounded-md">
            Ctrl+K
          </kbd>

          <!-- Tombol Cari -->
          <button type="submit"
                  class="px-3 py-1.5 bg-brand hover:bg-red-700 text-white rounded-xl text-xs font-bold shadow-xs hover:shadow transition flex items-center gap-1">
            <span>Cari</span>
            <i class="fa-solid fa-arrow-right text-[10px]"></i>
          </button>
        </div>
      </div>

      <!-- Dropdown Autocomplete Search Suggestions -->
      <div x-show="showDropdown && (suggestions.length > 0 || (hasSearched && searchQuery.trim().length >= 2))"
           style="display: none;"
           x-transition:enter="transition ease-out duration-150"
           x-transition:enter-start="transform opacity-0 -translate-y-1 scale-98"
           x-transition:enter-end="transform opacity-100 translate-y-0 scale-100"
           x-transition:leave="transition ease-in duration-100"
           x-transition:leave-start="transform opacity-100 translate-y-0 scale-100"
           x-transition:leave-end="transform opacity-0 -translate-y-1 scale-98"
           class="absolute left-0 right-0 top-full mt-2 bg-white border border-slate-200/90 rounded-2xl shadow-2xl z-50 overflow-hidden divide-y divide-slate-100">

        <!-- Top Header Info Dropdown -->
        <div class="px-4 py-2 bg-slate-50 text-[11px] font-semibold text-slate-500 flex items-center justify-between border-b border-slate-100">
          <div class="flex items-center gap-1.5">
            <i class="fa-solid fa-wand-magic-sparkles text-brand text-xs"></i>
            <span>Saran Pencarian untuk "<span class="text-slate-900 font-extrabold" x-text="searchQuery"></span>"</span>
            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-slate-200 text-slate-700" x-text="suggestions.length + ' hasil'"></span>
          </div>
          <div class="hidden sm:flex items-center gap-2 text-[10px] text-slate-400 font-medium">
            <span>Navigasi: <kbd class="px-1 bg-white border border-slate-300 rounded text-[9px]">↑</kbd> <kbd class="px-1 bg-white border border-slate-300 rounded text-[9px]">↓</kbd></span>
            <span>Buka: <kbd class="px-1 bg-white border border-slate-300 rounded text-[9px]">Enter</kbd></span>
            <span>Tutup: <kbd class="px-1 bg-white border border-slate-300 rounded text-[9px]">Esc</kbd></span>
          </div>
        </div>

        <!-- Suggestion Items List -->
        <div class="max-h-80 sm:max-h-96 overflow-y-auto divide-y divide-slate-100" x-ref="suggestionList">
          <!-- Item Suggestions Template -->
          <template x-for="(item, idx) in suggestions" :key="item.id">
            <div @click="selectItem(item)"
                 :class="{ 'suggestion-item-active bg-red-50/70 border-l-4 border-brand': selectedIndex === idx, 'hover:bg-slate-50/90 border-l-4 border-transparent': selectedIndex !== idx }"
                 class="px-4 py-3 cursor-pointer flex items-center justify-between transition group">
              
              <!-- Left: Icon & Title/Subtitle -->
              <div class="flex items-center gap-3 min-w-0 pr-2">
                <!-- Icon Category -->
                <div :class="item.icon_bg" class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 text-xs shadow-2xs">
                  <i :class="item.icon"></i>
                </div>

                <!-- Text Info -->
                <div class="min-w-0">
                  <div class="flex items-center gap-2">
                    <span :class="item.badge_class" class="px-1.5 py-0.5 rounded-md text-[9px] font-extrabold uppercase tracking-wide border shrink-0" x-text="item.badge"></span>
                    <h4 class="text-xs sm:text-sm font-bold text-slate-900 truncate group-hover:text-brand transition"
                        x-html="highlightMatch(item.title, searchQuery)"></h4>
                  </div>
                  <p class="text-[11px] text-slate-500 truncate mt-0.5" x-text="item.subtitle"></p>
                </div>
              </div>

              <!-- Right: Meta Info & Action Link -->
              <div class="text-right shrink-0 flex items-center gap-3">
                <div>
                  <span class="text-xs font-extrabold text-slate-800 font-mono block" x-text="item.meta"></span>
                  <span class="text-[10px] text-slate-400 block">Klik untuk membuka &rarr;</span>
                </div>
                <div class="w-7 h-7 rounded-lg bg-slate-100 group-hover:bg-brand group-hover:text-white text-slate-400 flex items-center justify-center text-xs transition">
                  <i class="fa-solid fa-chevron-right text-[10px]"></i>
                </div>
              </div>
            </div>
          </template>

          <!-- Empty State Inside Dropdown -->
          <div x-show="suggestions.length === 0 && !loading && hasSearched" class="p-6 text-center">
            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-500 mx-auto flex items-center justify-center text-lg mb-2">
              <i class="fa-solid fa-magnifying-glass"></i>
            </div>
            <h4 class="text-xs sm:text-sm font-bold text-slate-800">Tidak ada data yang cocok dengan "<span x-text="searchQuery"></span>"</h4>
            <p class="text-[11px] text-slate-400 mt-1 max-w-sm mx-auto">
              Coba gunakan kata kunci lain seperti Nama Pelanggan, NIK, No. HP, No. Tagihan (<span class="font-mono text-slate-600">INV-...</span>), atau ID Tiket (<span class="font-mono text-slate-600">TCK-...</span>).
            </p>
          </div>
        </div>

        <!-- Footer Dropdown: Quick Module Links -->
        <div class="px-4 py-2.5 bg-slate-50/80 flex flex-wrap items-center justify-between gap-2 text-[11px]">
          <span class="text-slate-400 font-medium">Buka langsung modul:</span>
          <div class="flex items-center gap-2">
            <a :href="`{{ route('admin.pelanggan') }}?q=${encodeURIComponent(searchQuery)}`"
               class="text-blue-600 hover:text-blue-800 font-bold hover:underline flex items-center gap-1">
              <i class="fa-solid fa-user text-[10px]"></i> Data Pelanggan
            </a>
            <span class="text-slate-300">&bull;</span>
            <a :href="`{{ route('admin.pesanan') }}?q=${encodeURIComponent(searchQuery)}`"
               class="text-emerald-600 hover:text-emerald-800 font-bold hover:underline flex items-center gap-1">
              <i class="fa-solid fa-cart-shopping text-[10px]"></i> Pesanan
            </a>
            <span class="text-slate-300">&bull;</span>
            <a :href="`{{ route('admin.tagihan') }}?q=${encodeURIComponent(searchQuery)}`"
               class="text-amber-600 hover:text-amber-800 font-bold hover:underline flex items-center gap-1">
              <i class="fa-solid fa-receipt text-[10px]"></i> Tagihan
            </a>
            <span class="text-slate-300">&bull;</span>
            <a :href="`{{ route('admin.laporan') }}?q=${encodeURIComponent(searchQuery)}`"
               class="text-red-600 hover:text-red-800 font-bold hover:underline flex items-center gap-1">
              <i class="fa-solid fa-headset text-[10px]"></i> Laporan Masalah
            </a>
          </div>
        </div>

      </div>
    </form>
  </div>

  <!-- ============================================== -->
  <!-- 1. KPI CARDS (STATISTIK UTAMA)                 -->
  <!-- ============================================== -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
    
    <!-- KPI 1: Pelanggan Aktif -->
    <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
      <div>
        <p class="text-xs font-semibold text-slate-500">Pelanggan Aktif</p>
        <h3 class="text-xl sm:text-2xl font-black text-slate-900 mt-1">{{ $stats['active_customers'] ?? 0 }} <span class="text-xs font-medium text-slate-400">/ {{ $stats['total_customers'] ?? 0 }}</span></h3>
        <a href="{{ route('admin.pelanggan') }}" class="text-[10px] sm:text-[11px] text-brand hover:underline font-bold mt-1 inline-block">
          Kelola Data Pelanggan &rarr;
        </a>
      </div>
      <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg sm:text-xl shrink-0">
        <i class="fa-solid fa-users"></i>
      </div>
    </div>

    <!-- KPI 2: Pesanan Baru Masuk -->
    <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
      <div>
        <p class="text-xs font-semibold text-slate-500">Pesanan Masuk</p>
        <h3 class="text-xl sm:text-2xl font-black text-emerald-600 mt-1">{{ $stats['pending_orders_count'] ?? 0 }} <span class="text-xs font-normal text-slate-400">Baru</span></h3>
        <a href="{{ route('admin.pesanan') }}" class="text-[10px] sm:text-[11px] text-brand hover:underline font-bold mt-1 inline-block">
          Pantau Pesanan &rarr;
        </a>
      </div>
      <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg sm:text-xl shrink-0">
        <i class="fa-solid fa-cart-shopping"></i>
      </div>
    </div>

    <!-- KPI 3: Tagihan Menunggu Verifikasi -->
    <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
      <div>
        <p class="text-xs font-semibold text-slate-500">Tagihan Dicek</p>
        <h3 class="text-xl sm:text-2xl font-black text-amber-500 mt-1">{{ $stats['pending_bills_count'] ?? 0 }} <span class="text-xs font-normal text-slate-400">Inv</span></h3>
        <a href="{{ route('admin.tagihan', ['status' => 'Menunggu Verifikasi']) }}" class="text-[10px] sm:text-[11px] text-brand hover:underline font-bold mt-1 inline-block">
          Verifikasi &rarr;
        </a>
      </div>
      <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg sm:text-xl shrink-0">
        <i class="fa-solid fa-receipt"></i>
      </div>
    </div>

    <!-- KPI 4: Laporan Gangguan Terbuka -->
    <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
      <div>
        <p class="text-xs font-semibold text-slate-500">Laporan Masalah</p>
        <h3 class="text-xl sm:text-2xl font-black text-red-600 mt-1">{{ $stats['open_tickets_count'] ?? 0 }} <span class="text-xs font-normal text-slate-400">Tiket</span></h3>
        <p class="text-[10px] sm:text-[11px] text-red-500 font-bold mt-1 flex items-center gap-1">
          <i class="fa-solid fa-triangle-exclamation"></i> {{ $stats['critical_tickets_count'] ?? 0 }} kritis
        </p>
      </div>
      <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-red-50 text-brand flex items-center justify-center text-lg sm:text-xl shrink-0">
        <i class="fa-solid fa-headset"></i>
      </div>
    </div>

    <!-- KPI 5: MRTG Total Bandwidth -->
    <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
      <div>
        <p class="text-xs font-semibold text-slate-500">Trafik Bandwidth</p>
        <h3 class="text-xl sm:text-2xl font-black text-slate-900 mt-1">{{ $stats['bandwidth_in'] ?? '0 Mbps' }}</h3>
        <p class="text-[10px] sm:text-[11px] text-slate-500 font-medium mt-1">
          <span class="text-emerald-600 font-bold">In</span> &bull; 
          <span class="text-blue-600 font-bold">{{ $stats['bandwidth_out'] ?? '0 Mbps' }} Out</span>
        </p>
      </div>
      <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-slate-100 text-slate-700 flex items-center justify-center text-lg sm:text-xl shrink-0">
        <i class="fa-solid fa-network-wired"></i>
      </div>
    </div>

  </div>

  <!-- ============================================== -->
  <!-- 2. QUICK ACCESS CARDS: MRTG & ODC MAP          -->
  <!-- ============================================== -->
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    
    <!-- Preview MRTG Mini-Chart (Col 7) -->
    <div class="lg:col-span-7 bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs flex flex-col justify-between">
      <div class="flex items-center justify-between mb-4">
        <div>
          <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <h3 class="text-sm font-extrabold text-slate-900">MRTG Real-Time Traffic (Core Router)</h3>
          </div>
          <p class="text-xs text-slate-400 mt-0.5">Throughput uplink 10G SFP+ ISP Tier-1</p>
        </div>
        <a href="{{ route('admin.mrtg') }}" class="text-xs text-brand hover:underline font-bold flex items-center gap-1">
          Buka MRTG Lengkap <i class="fa-solid fa-arrow-right text-[10px]"></i>
        </a>
      </div>

      <div class="h-56 relative">
        <canvas id="dashboardMrtgChart"></canvas>
      </div>

      <div class="grid grid-cols-3 gap-3 pt-4 border-t border-slate-100 text-center text-xs mt-3">
        <div class="bg-slate-50 p-2 rounded-xl">
          <p class="text-[10px] text-slate-400 font-medium">Kapasitas Port</p>
          <p class="font-extrabold text-slate-800 mt-0.5">10.000 Mbps</p>
        </div>
        <div class="bg-emerald-50/60 p-2 rounded-xl border border-emerald-100">
          <p class="text-[10px] text-emerald-700 font-medium">Download (Inbound)</p>
          <p class="font-extrabold text-emerald-600 mt-0.5">{{ $stats['bandwidth_in'] }}</p>
        </div>
        <div class="bg-blue-50/60 p-2 rounded-xl border border-blue-100">
          <p class="text-[10px] text-blue-700 font-medium">Upload (Outbound)</p>
          <p class="font-extrabold text-blue-600 mt-0.5">{{ $stats['bandwidth_out'] }}</p>
        </div>
      </div>
    </div>

    <!-- Preview ODC GIS Map Banner (Col 5) -->
    <div class="lg:col-span-5 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-950 rounded-2xl p-6 text-white shadow-md flex flex-col justify-between relative overflow-hidden">
      <!-- Background Graphic -->
      <div class="absolute -right-6 -bottom-6 opacity-10 text-white pointer-events-none">
        <i class="fa-solid fa-map-location-dot text-[180px]"></i>
      </div>

      <div>
        <div class="inline-flex items-center gap-2 bg-red-500/20 text-red-300 border border-red-500/30 text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider mb-4">
          <i class="fa-solid fa-satellite-dish"></i> Infrastruktur Fiber Optik
        </div>
        <h3 class="text-xl font-black text-white leading-snug">Peta Persebaran ODC & ODP Fiber</h3>
        <p class="text-xs text-slate-300 mt-2 leading-relaxed">
          Pantau {{ $stats['odc_count'] ?? 0 }} Optical Distribution Cabinet (ODC) dan {{ $stats['odp_count'] ?? 0 }} Optical Distribution Point (ODP) di seluruh area jaringan Banterpool.
        </p>

        <!-- Status Warning Fiber Cut -->
        <div class="bg-red-500/10 border border-red-500/30 rounded-xl p-3 mt-4 flex items-center gap-3 text-xs">
          <div class="w-8 h-8 rounded-lg bg-red-500/20 text-red-400 flex items-center justify-center shrink-0">
            <i class="fa-solid fa-triangle-exclamation"></i>
          </div>
          <div>
            <p class="font-bold text-red-300">Peringatan: ODP-CLK-08 Terputus</p>
            <p class="text-[11px] text-slate-300">Redaman -99.0 dBm pada area Jatisaba, Cilongok</p>
          </div>
        </div>
      </div>

      <div class="pt-6">
        <a href="{{ route('admin.odc-map') }}"
           class="w-full bg-brand hover:bg-brand-700 text-white font-bold text-xs py-3 px-4 rounded-xl shadow-lg transition flex items-center justify-center gap-2">
          <i class="fa-solid fa-map-location-dot"></i>
          <span>Buka Peta Interaktif ODC & Jalur Fiber</span>
        </a>
      </div>
    </div>

  </div>

  <!-- ============================================== -->
  <!-- 3. TABEL MONITORING PESANAN TERBARU            -->
  <!-- ============================================== -->
  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
    <div class="p-5 border-b border-slate-100 flex items-center justify-between">
      <div>
        <div class="flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
          <h3 class="text-sm font-extrabold text-slate-900">Pesanan Pelanggan Baru Masuk (Live Monitoring)</h3>
        </div>
        <p class="text-xs text-slate-400 mt-0.5">Daftar pemesanan paket WiFi dari web yang baru masuk</p>
      </div>
      <a href="{{ route('admin.pesanan') }}" class="text-xs text-brand hover:underline font-bold flex items-center gap-1">
        Lihat Semua Pesanan ({{ $stats['total_orders'] ?? 0 }}) &rarr;
      </a>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50 text-slate-400 uppercase font-bold text-[10px] border-b border-slate-100">
          <tr>
            <th class="px-5 py-3">No. Order & Waktu</th>
            <th class="px-4 py-3">Pelanggan</th>
            <th class="px-4 py-3">Paket Internet</th>
            <th class="px-4 py-3">Jadwal Pasang</th>
            <th class="px-4 py-3">Pembayaran</th>
            <th class="px-4 py-3">Status Pesanan</th>
            <th class="px-4 py-3 text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 font-medium">
          @forelse($recentOrders as $rOrder)
            <tr class="hover:bg-slate-50 transition">
              <td class="px-5 py-3.5 whitespace-nowrap">
                <span class="font-mono font-bold text-slate-800 text-xs block">{{ $rOrder->order_number }}</span>
                <span class="text-[10px] text-slate-400">{{ $rOrder->created_at ? $rOrder->created_at->diffForHumans() : '-' }}</span>
              </td>
              <td class="px-4 py-3.5">
                <p class="font-bold text-slate-900">{{ $rOrder->customer_name }}</p>
                <p class="text-[10px] text-slate-400">{{ $rOrder->customer_phone }}</p>
              </td>
              <td class="px-4 py-3.5 whitespace-nowrap">
                <span class="font-bold text-slate-800 block">{{ $rOrder->package_name }}</span>
                <span class="text-brand font-black text-xs">Rp{{ number_format($rOrder->total, 0, ',', '.') }}</span>
              </td>
              <td class="px-4 py-3.5 whitespace-nowrap">
                <span class="text-slate-700 block">{{ $rOrder->installation_date ? $rOrder->installation_date->format('d M Y') : '-' }}</span>
                <span class="text-[10px] text-slate-400 uppercase font-bold">{{ $rOrder->installation_time ?? 'pagi' }}</span>
              </td>
              <td class="px-4 py-3.5 whitespace-nowrap">
                @if($rOrder->payment_status === 'Lunas')
                  <span class="bg-emerald-50 text-emerald-700 text-[10px] font-bold px-2 py-0.5 rounded-full border border-emerald-200">Lunas</span>
                @else
                  <span class="bg-amber-50 text-amber-700 text-[10px] font-bold px-2 py-0.5 rounded-full border border-amber-200">{{ $rOrder->payment_status }}</span>
                @endif
              </td>
              <td class="px-4 py-3.5 whitespace-nowrap">
                @if($rOrder->status === 'Menunggu Konfirmasi')
                  <span class="bg-amber-100 text-amber-800 font-extrabold text-[10px] px-2 py-0.5 rounded-full animate-pulse">Menunggu Konfirmasi</span>
                @elseif($rOrder->status === 'Selesai')
                  <span class="bg-emerald-100 text-emerald-800 font-bold text-[10px] px-2 py-0.5 rounded-full">Selesai / Aktif</span>
                @else
                  <span class="bg-blue-50 text-blue-700 font-bold text-[10px] px-2 py-0.5 rounded-full">{{ $rOrder->status }}</span>
                @endif
              </td>
              <td class="px-4 py-3.5 text-right whitespace-nowrap">
                <a href="{{ route('admin.pesanan', ['q' => $rOrder->order_number]) }}" class="text-xs text-brand hover:underline font-bold">
                  Kelola &rarr;
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-6 text-slate-400">Belum ada pesanan masuk.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <!-- ============================================== -->
  <!-- 4. DUA TABEL PENGINTAIAN: TAGIHAN & TIKET     -->
  <!-- ============================================== -->
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
    
    <!-- Kolom Kiri (7 Col): Pengintaian Tagihan Terbaru -->
    <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
      <div class="p-5 border-b border-slate-100 flex items-center justify-between">
        <div>
          <h3 class="text-sm font-extrabold text-slate-900">Pengintaian Tagihan Pelanggan</h3>
          <p class="text-xs text-slate-400 mt-0.5">Status pembayaran invoice pelanggan terkini</p>
        </div>
        <a href="{{ route('admin.tagihan') }}" class="text-xs text-brand hover:underline font-bold">
          Lihat Semua &rarr;
        </a>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-50 text-slate-400 uppercase font-bold text-[10px] border-b border-slate-100">
            <tr>
              <th class="px-5 py-3">Invoice & Pelanggan</th>
              <th class="px-4 py-3">Paket</th>
              <th class="px-4 py-3">Nominal</th>
              <th class="px-4 py-3">Status</th>
              <th class="px-4 py-3 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            @forelse(array_slice($bills, 0, 5) as $bill)
              <tr class="hover:bg-slate-50/70 transition">
                <td class="px-5 py-3.5">
                  <p class="font-bold text-slate-900 leading-tight">{{ $bill['customer_name'] }}</p>
                  <p class="text-[10px] text-slate-400 font-mono mt-0.5">{{ $bill['id'] }}</p>
                </td>
                <td class="px-4 py-3.5 text-slate-600">{{ $bill['package_name'] }}</td>
                <td class="px-4 py-3.5 font-bold text-slate-900">Rp{{ $bill['total'] }}</td>
                <td class="px-4 py-3.5">
                  @if($bill['status'] === 'Lunas')
                    <span class="bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold px-2 py-0.5 rounded-md">Lunas</span>
                  @elseif($bill['status'] === 'Menunggu Verifikasi')
                    <span class="bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-bold px-2 py-0.5 rounded-md">Verifikasi</span>
                  @elseif($bill['status'] === 'Jatuh Tempo')
                    <span class="bg-red-50 text-red-700 border border-red-200 text-[10px] font-bold px-2 py-0.5 rounded-md">Jatuh Tempo</span>
                  @else
                    <span class="bg-slate-100 text-slate-600 border border-slate-200 text-[10px] font-bold px-2 py-0.5 rounded-md">Belum Bayar</span>
                  @endif
                </td>
                <td class="px-4 py-3.5 text-right">
                  <a href="{{ route('admin.tagihan', ['q' => $bill['id']]) }}" class="text-xs text-brand hover:underline font-bold">
                    Cek
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center py-8 text-slate-400">
                  <i class="fa-solid fa-receipt text-3xl mb-1.5 text-slate-300 block"></i>
                  <span>Belum ada data tagihan.</span>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <!-- Kolom Kanan (5 Col): Laporan Gangguan / Trouble Ticket Terkini -->
    <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
      <div class="p-5 border-b border-slate-100 flex items-center justify-between">
        <div>
          <h3 class="text-sm font-extrabold text-slate-900">Tiket Gangguan Pelanggan</h3>
          <p class="text-xs text-slate-400 mt-0.5">Laporan masalah jaringan masuk</p>
        </div>
        <a href="{{ route('admin.laporan') }}" class="text-xs text-brand hover:underline font-bold">
          Kelola Tiket &rarr;
        </a>
      </div>

      <div class="divide-y divide-slate-100 text-xs">
        @forelse(array_slice($tickets, 0, 4) as $ticket)
          <div class="p-4 hover:bg-slate-50 transition">
            <div class="flex items-start justify-between gap-2">
              <div>
                <span class="text-[10px] font-bold font-mono text-slate-400">{{ $ticket['id'] }}</span>
                <h4 class="font-bold text-slate-900 mt-0.5">{{ $ticket['type'] }}</h4>
              </div>
              <span class="text-[10px] font-bold px-2 py-0.5 rounded-md shrink-0 {{ $ticket['priority'] === 'Kritis' ? 'bg-red-100 text-red-700' : ($ticket['priority'] === 'Tinggi' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700') }}">
                {{ $ticket['priority'] }}
              </span>
            </div>
            <p class="text-[11px] text-slate-500 mt-1 line-clamp-2">{{ $ticket['description'] }}</p>
            <div class="flex items-center justify-between mt-2.5 pt-2 border-t border-slate-50 text-[10px] text-slate-400">
              <span class="font-medium text-slate-600">{{ $ticket['customer_name'] }} ({{ $ticket['odp'] }})</span>
              <span class="font-semibold text-brand">{{ $ticket['status'] }}</span>
            </div>
          </div>
        @empty
          <div class="text-center py-8 text-slate-400">
            <i class="fa-solid fa-headset text-3xl mb-2 text-slate-300 block"></i>
            <span>Belum ada laporan gangguan masuk.</span>
          </div>
        @endforelse
      </div>
    </div>

  </div>

</div>

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('dashboardMrtgChart');
    if (ctx) {
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: @json($mrtg['interfaces'][0]['history']['labels']),
          datasets: [
            {
              label: 'Download / Inbound (Mbps)',
              data: @json($mrtg['interfaces'][0]['history']['inbound']),
              borderColor: '#10b981',
              backgroundColor: 'rgba(16, 185, 129, 0.15)',
              borderWidth: 2,
              fill: true,
              tension: 0.35,
              pointRadius: 2,
            },
            {
              label: 'Upload / Outbound (Mbps)',
              data: @json($mrtg['interfaces'][0]['history']['outbound']),
              borderColor: '#3b82f6',
              backgroundColor: 'rgba(59, 130, 246, 0.15)',
              borderWidth: 2,
              fill: true,
              tension: 0.35,
              pointRadius: 2,
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'top',
              labels: { font: { family: 'Poppins', size: 10 }, boxWidth: 12 }
            },
            tooltip: {
              callbacks: {
                label: (ctx) => `${ctx.dataset.label}: ${ctx.parsed.y} Mbps`
              }
            }
          },
          scales: {
            x: { grid: { display: false }, ticks: { font: { size: 9 } } },
            y: {
              grid: { color: '#f1f5f9' },
              ticks: { font: { size: 9 }, callback: (v) => v + 'M' }
            }
          }
        }
      });
    }
  });
</script>
@endpush
@endsection
