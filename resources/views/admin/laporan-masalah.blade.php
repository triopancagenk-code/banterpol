@extends('layouts.admin')

@section('title', 'Admin NOC Banterpool - Pengintaian Laporan Masalah')
@section('page-title', 'Pengintaian & Penanganan Laporan Masalah (Trouble Tickets)')

@section('content')
<div class="space-y-6"
     x-data="laporanMasalahApp(@js($tickets), @js($counts), '{{ $statusFilter }}')">

  <!-- ============================================== -->
  <!-- 1. ALERT NOTIFIKASI                            -->
  <!-- ============================================== -->
  @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl flex items-center justify-between shadow-xs">
      <div class="flex items-center gap-2.5">
        <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
        <span class="text-xs font-bold">{{ session('success') }}</span>
      </div>
      <button type="button" @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 text-sm">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
  @endif

  @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-2xl flex items-center justify-between shadow-xs">
      <div class="flex items-center gap-2.5">
        <i class="fa-solid fa-circle-xmark text-red-600 text-base"></i>
        <span class="text-xs font-bold">{{ session('error') }}</span>
      </div>
      <button type="button" @click="$el.parentElement.remove()" class="text-red-500 hover:text-red-700 text-sm">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
  @endif

  <!-- ============================================== -->
  <!-- 2. HEADER & ACTION BUTTONS                     -->
  <!-- ============================================== -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <div class="flex items-center gap-2 flex-wrap">
        <h2 class="text-xl font-black text-slate-900 tracking-tight">Daftar Tiket Gangguan Jaringan</h2>
        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-red-100 text-brand border border-red-200 uppercase tracking-wider">Trouble Tickets</span>
        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 flex items-center gap-1 shadow-xs" title="Sistem otomatis membersihkan riwayat tiket berstatus Selesai yang telah berusia lebih dari 3 hari">
          <i class="fa-solid fa-clock-rotate-left text-emerald-600"></i>
          <span>Auto-Hapus 3 Hari (Tiket Selesai)</span>
        </span>
      </div>
      <p class="text-xs text-slate-500 mt-1">
        Kelola laporan kendala pelanggan secara real-time. Sistem otomatis menghapus riwayat laporan berstatus Selesai yang berusia lebih dari 3 hari. Admin & Direktur tetap dapat menghapus pilihan tiket maupun seluruh data laporan secara manual kapan saja.
      </p>
    </div>

    <div class="flex flex-wrap items-center gap-2.5 shrink-0">
      <!-- Live Server Time -->
      <div class="hidden lg:flex items-center gap-2 bg-slate-900 text-white text-xs px-3.5 py-2 rounded-xl border border-slate-800 shadow-xs font-mono">
        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
        <span class="text-slate-400 font-sans text-[10px]">Waktu Real-Time:</span>
        <span class="font-bold text-emerald-300" x-text="liveDate + ' • ' + liveTime"></span>
      </div>

      <!-- Live Syncing Indicator & Refresh Button -->
      <button type="button" @click="pollTickets()"
              class="h-10 px-3.5 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-xl transition flex items-center gap-2 border border-slate-200/80 shadow-xs"
              title="Sinkronisasi Data Real-Time Sekarang">
        <i class="fa-solid fa-arrows-rotate text-brand" :class="isPolling ? 'fa-spin' : ''"></i>
        <span>Sinkronisasi</span>
      </button>

      <!-- Tombol Hapus Pilihan (Selalu Terlihat) -->
      <button type="button"
              @click="selectedIds.length > 0 ? confirmBulkDelete(false) : showToast('Silakan centang minimal satu tiket pada kolom kotak centang tabel.', 'error')"
              :class="selectedIds.length > 0 ? 'bg-red-600 hover:bg-red-700 text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-500 border border-slate-200/80'"
              class="h-10 px-3.5 text-xs font-bold rounded-xl transition flex items-center gap-1.5 cursor-pointer"
              title="Hapus Tiket Laporan Kendala Terpilih">
        <i class="fa-solid fa-trash-can" :class="selectedIds.length > 0 ? 'text-white' : 'text-slate-400'"></i>
        <span>Hapus Pilihan</span>
        <template x-if="selectedIds.length > 0">
          <span class="bg-black/20 text-white px-1.5 py-0.5 rounded-full text-[10px] font-mono ml-0.5" x-text="selectedIds.length"></span>
        </template>
      </button>

      <!-- Tombol Hapus Semua Data (POV Admin & Direktur) -->
      <button type="button" @click="confirmBulkDelete(true)"
              :disabled="tickets.length === 0"
              class="h-10 px-3.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl transition flex items-center gap-1.5 shadow-xs disabled:opacity-50 disabled:cursor-not-allowed"
              title="Hapus Seluruh Data Laporan Masalah">
        <i class="fa-solid fa-trash-arrow-up text-white"></i>
        <span>Hapus Semua Data</span>
      </button>
    </div>
  </div>

  <!-- ============================================== -->
  <!-- 3. STATISTIC KPI CARDS (Serupa Data Pelanggan) -->
  <!-- ============================================== -->
  <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4">
    
    <!-- 1. Total Laporan -->
    <a href="{{ route('admin.laporan', ['status' => 'all']) }}"
       class="bg-white p-4 rounded-2xl border transition duration-150 shadow-xs flex items-center justify-between {{ $statusFilter === 'all' ? 'border-brand ring-2 ring-brand/10' : 'border-slate-200/80 hover:border-slate-300' }}">
      <div>
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Tiket</p>
        <h3 class="text-2xl font-black text-slate-900 mt-1" x-text="counts.all">{{ $counts['all'] }}</h3>
        <span class="text-[10px] text-slate-500 font-medium">Laporan Gangguan Masuk</span>
      </div>
      <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
        <i class="fa-solid fa-headset"></i>
      </div>
    </a>

    <!-- 2. Menunggu Respon -->
    <a href="{{ route('admin.laporan', ['status' => 'Menunggu Respon']) }}"
       class="bg-white p-4 rounded-2xl border transition duration-150 shadow-xs flex items-center justify-between {{ $statusFilter === 'Menunggu Respon' ? 'border-amber-500 ring-2 ring-amber-500/10' : 'border-slate-200/80 hover:border-slate-300' }}">
      <div>
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Menunggu Respon</p>
        <h3 class="text-2xl font-black text-amber-600 mt-1" x-text="counts.menunggu">{{ $counts['menunggu'] }}</h3>
        <span class="text-[10px] text-slate-500 font-medium">Antrian Perlu Ditangani</span>
      </div>
      <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
        <i class="fa-regular fa-clock"></i>
      </div>
    </a>

    <!-- 3. Sedang Ditangani -->
    <a href="{{ route('admin.laporan', ['status' => 'Sedang Ditangani']) }}"
       class="bg-white p-4 rounded-2xl border transition duration-150 shadow-xs flex items-center justify-between {{ $statusFilter === 'Sedang Ditangani' ? 'border-blue-600 ring-2 ring-blue-600/10' : 'border-slate-200/80 hover:border-slate-300' }}">
      <div>
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Sedang Ditangani</p>
        <h3 class="text-2xl font-black text-blue-600 mt-1" x-text="counts.proses">{{ $counts['proses'] }}</h3>
        <span class="text-[10px] text-slate-500 font-medium">Teknisi Sedang Proses</span>
      </div>
      <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg">
        <i class="fa-solid fa-person-digging"></i>
      </div>
    </a>

    <!-- 4. Selesai Ditangani -->
    <a href="{{ route('admin.laporan', ['status' => 'Selesai']) }}"
       class="bg-white p-4 rounded-2xl border transition duration-150 shadow-xs flex items-center justify-between {{ $statusFilter === 'Selesai' ? 'border-emerald-500 ring-2 ring-emerald-500/10' : 'border-slate-200/80 hover:border-slate-300' }}">
      <div>
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Selesai Normal</p>
        <h3 class="text-2xl font-black text-emerald-600 mt-1" x-text="counts.selesai">{{ $counts['selesai'] }}</h3>
        <span class="text-[10px] text-slate-500 font-medium">Koneksi Sudah Pulih</span>
      </div>
      <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
        <i class="fa-solid fa-circle-check"></i>
      </div>
    </a>

    <!-- 5. Gangguan Kritis -->
    <a href="{{ route('admin.laporan', ['priority' => 'Kritis']) }}"
       class="bg-white p-4 rounded-2xl border transition duration-150 shadow-xs flex items-center justify-between col-span-2 sm:col-span-2 lg:col-span-1 {{ $priorityFilter === 'Kritis' ? 'border-red-500 ring-2 ring-red-500/10' : 'border-slate-200/80 hover:border-slate-300' }}">
      <div>
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Gangguan Kritis</p>
        <h3 class="text-2xl font-black text-brand mt-1" x-text="counts.kritis">{{ $counts['kritis'] }}</h3>
        <span class="text-[10px] text-slate-500 font-medium">Prioritas Darurat NOC</span>
      </div>
      <div class="w-10 h-10 rounded-xl bg-red-50 text-brand flex items-center justify-center text-lg">
        <i class="fa-solid fa-triangle-exclamation"></i>
      </div>
    </a>

  </div>

  <!-- ============================================== -->
  <!-- 4. SEARCH & FILTER BAR (4 Kolom Sesuai Pelanggan) -->
  <!-- ============================================== -->
  <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs">
    <form method="GET" action="{{ route('admin.laporan') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
      
      <!-- 1. Pencarian Laporan Masalah (lg:col-span-4) -->
      <div class="sm:col-span-2 lg:col-span-4">
        <label class="block text-[11px] font-bold text-slate-700 mb-1">Pencarian Laporan Masalah</label>
        <div class="relative">
          <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400 text-xs"></i>
          <input type="text" name="q" value="{{ $search }}"
                 placeholder="Cari ID Tiket, Nama, No. HP, Titik ODP, Kendala..."
                 class="w-full pl-9 pr-3 py-2 text-xs bg-slate-50 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand transition">
        </div>
      </div>

      <!-- 2. Filter Status Tiket (lg:col-span-3) -->
      <div class="sm:col-span-1 lg:col-span-3">
        <label class="block text-[11px] font-bold text-slate-700 mb-1">Status Tiket</label>
        <select name="status" onchange="this.form.submit()" class="w-full text-xs py-2 px-3 bg-slate-50 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand cursor-pointer">
          <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>Semua Status ({{ $counts['all'] }} Tiket)</option>
          <option value="Menunggu Respon" {{ $statusFilter === 'Menunggu Respon' ? 'selected' : '' }}>Menunggu Respon ({{ $counts['menunggu'] }})</option>
          <option value="Sedang Ditangani" {{ $statusFilter === 'Sedang Ditangani' ? 'selected' : '' }}>Sedang Ditangani ({{ $counts['proses'] }})</option>
          <option value="Selesai" {{ $statusFilter === 'Selesai' ? 'selected' : '' }}>Selesai / Pulih ({{ $counts['selesai'] }})</option>
        </select>
      </div>

      <!-- 3. Tingkat Prioritas (lg:col-span-2) -->
      <div class="sm:col-span-1 lg:col-span-2">
        <label class="block text-[11px] font-bold text-slate-700 mb-1">Tingkat Prioritas</label>
        <select name="priority" onchange="this.form.submit()" class="w-full text-xs py-2 px-3 bg-slate-50 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand cursor-pointer">
          <option value="all" {{ $priorityFilter === 'all' ? 'selected' : '' }}>Semua Prioritas</option>
          <option value="Kritis" {{ $priorityFilter === 'Kritis' ? 'selected' : '' }}>Kritis ({{ $counts['kritis'] }})</option>
          <option value="Tinggi" {{ $priorityFilter === 'Tinggi' ? 'selected' : '' }}>Tinggi</option>
          <option value="Normal" {{ $priorityFilter === 'Normal' ? 'selected' : '' }}>Normal</option>
        </select>
      </div>

      <!-- 4. Kategori Kendala & Reset (lg:col-span-3) -->
      <div class="sm:col-span-2 lg:col-span-3 flex items-end gap-2">
        <div class="flex-1 min-w-0">
          <label class="block text-[11px] font-bold text-slate-700 mb-1">Kategori Kendala</label>
          <select name="category" onchange="this.form.submit()" class="w-full text-xs py-2 px-3 bg-slate-50 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand cursor-pointer">
            <option value="all" {{ ($categoryFilter ?? 'all') === 'all' ? 'selected' : '' }}>Semua Kendala</option>
            <option value="LOS" {{ ($categoryFilter ?? '') === 'LOS' ? 'selected' : '' }}>LOS / Lampu Merah</option>
            <option value="Lambat" {{ ($categoryFilter ?? '') === 'Lambat' ? 'selected' : '' }}>Koneksi Lambat</option>
            <option value="WiFi" {{ ($categoryFilter ?? '') === 'WiFi' ? 'selected' : '' }}>WiFi Lemah / Putus</option>
            <option value="Kabel" {{ ($categoryFilter ?? '') === 'Kabel' ? 'selected' : '' }}>Kabel Putus / Fisik</option>
          </select>
        </div>
        @if($search || $statusFilter !== 'all' || $priorityFilter !== 'all' || ($categoryFilter ?? 'all') !== 'all')
          <a href="{{ route('admin.laporan') }}" class="py-2 px-3 bg-slate-100 hover:bg-red-50 text-slate-500 hover:text-red-600 rounded-xl text-xs font-bold transition shrink-0 flex items-center gap-1.5 border border-slate-200" title="Reset Semua Filter">
            <i class="fa-solid fa-arrow-rotate-left text-[11px]"></i>
            <span>Reset</span>
          </a>
        @endif
      </div>

    </form>
  </div>

  <!-- Selection Status Bar (Aktif ketika ada data yang dipilih) -->
  <div x-show="selectedIds.length > 0"
       x-cloak
       class="bg-slate-900 text-white p-3.5 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-lg border border-slate-700">
    <div class="flex items-center gap-3">
      <div class="w-8 h-8 rounded-xl bg-brand/30 border border-brand/50 text-brand flex items-center justify-center font-black text-xs shrink-0">
        <span x-text="selectedIds.length"></span>
      </div>
      <div class="text-xs">
        <p class="font-extrabold text-white">
          <span x-text="selectedIds.length"></span> laporan kendala dipilih
        </p>
        <template x-if="tickets.length > selectedIds.length">
          <p class="text-[11px] text-slate-400 mt-0.5">
            Ingin memilih seluruh data?
            <button type="button" @click="selectAllTickets()" class="text-amber-400 hover:underline font-bold ml-1">
              Pilih Semua (<span x-text="tickets.length"></span> Laporan)
            </button>
          </p>
        </template>
      </div>
    </div>

    <div class="flex items-center gap-2 shrink-0">
      <button type="button" @click="clearSelection()"
              class="bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-semibold px-3 py-1.5 rounded-xl transition">
        Batal Pilihan
      </button>
      <button type="button" @click="confirmBulkDelete(false)"
              class="bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-4 py-1.5 rounded-xl transition flex items-center gap-1.5 shadow-sm">
        <i class="fa-solid fa-trash-can"></i>
        <span>Hapus Pilihan (<span x-text="selectedIds.length"></span>)</span>
      </button>
    </div>
  </div>

  <!-- ============================================== -->
  <!-- 5. TABEL MASTER LAPORAN MASALAH                -->
  <!-- ============================================== -->
  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-center text-xs">
        <thead class="bg-slate-50 text-slate-500 font-extrabold uppercase text-[10px] border-b border-slate-200 tracking-wider">
          <tr>
            <!-- Checkbox Select All Column (Sticky Left) -->
            <th class="py-3.5 px-3 text-center w-12 min-w-[48px] sticky left-0 bg-slate-50 z-20 shadow-[2px_0_4px_-1px_rgba(0,0,0,0.08)] border-r border-slate-200">
              <div class="flex items-center justify-center">
                <input type="checkbox"
                       :checked="tickets.length > 0 && selectedIds.length === tickets.length"
                       @change="toggleSelectAll()"
                       class="w-4 h-4 rounded border-slate-300 text-brand focus:ring-brand cursor-pointer"
                       title="Pilih Semua Laporan Kendala">
              </div>
            </th>
            <th class="py-3.5 px-4 text-center whitespace-nowrap">ID Tiket & Waktu Lapor</th>
            <th class="py-3.5 px-4 text-center">Nama Pelanggan</th>
            <th class="py-3.5 px-4 text-center">No Handphone</th>
            <th class="py-3.5 px-4 text-center">Jenis Kendala & Bukti</th>
            <th class="py-3.5 px-4 text-center">Titik ODP</th>
            <th class="py-3.5 px-4 text-center">Prioritas</th>
            <th class="py-3.5 px-4 text-center">Status & Update</th>
            <th class="py-3.5 px-4 text-center">Teknisi Bertugas</th>
            <th class="py-3.5 px-4 text-center w-28">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 font-medium">
          <template x-for="(ticket, index) in tickets" :key="ticket.id">
            <tr class="hover:bg-slate-50/80 transition duration-150"
                :class="selectedIds.includes(ticket.id) ? 'bg-red-50/50' : (ticket.is_new_incoming ? 'bg-amber-50/40' : (ticket.is_recently_updated ? 'bg-emerald-50/30' : ''))">
              
              <!-- Checkbox Select Row (Sticky Left) -->
              <td class="py-3.5 px-3 text-center w-12 min-w-[48px] sticky left-0 z-10 shadow-[2px_0_4px_-1px_rgba(0,0,0,0.08)] border-r border-slate-200"
                  :class="selectedIds.includes(ticket.id) ? '!bg-red-100' : '!bg-white'"
                  @click.stop>
                <div class="flex items-center justify-center">
                  <input type="checkbox"
                         :value="ticket.id"
                         x-model="selectedIds"
                         class="w-4 h-4 rounded border-slate-300 text-brand focus:ring-brand cursor-pointer">
                </div>
              </td>

              <!-- 1. ID Tiket & Waktu Lapor -->
              <td class="py-3.5 px-4 whitespace-nowrap text-center">
                <div class="inline-flex items-center justify-center gap-1.5">
                  <span class="font-mono text-xs font-bold bg-slate-100 text-slate-800 px-2 py-0.5 rounded border border-slate-200"
                        x-text="ticket.id"></span>
                  <template x-if="ticket.is_new_incoming">
                    <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-100 text-amber-800 border border-amber-300 animate-pulse">
                      Baru
                    </span>
                  </template>
                </div>
                <div class="mt-1 space-y-0.5 text-center">
                  <div class="text-[11px] font-semibold text-slate-700 flex items-center justify-center gap-1">
                    <i class="fa-regular fa-calendar-days text-[10px] text-brand shrink-0"></i>
                    <span x-text="getFormattedDate(ticket.created_date || ticket.created_at_full || ticket.created_at)"></span>
                  </div>
                  <div class="text-[10px] text-slate-400 font-mono flex items-center justify-center gap-1">
                    <i class="fa-regular fa-clock text-[9px] text-slate-400"></i>
                    <span x-text="getFormattedTime(ticket.created_time || ticket.created_at_full || ticket.created_at)"></span>
                  </div>
                </div>
              </td>

              <!-- 2. Nama Pelanggan & Alamat -->
              <td class="py-3.5 px-4 text-center">
                <div class="font-bold text-slate-900 text-xs" x-text="ticket.customer_name"></div>
                <div class="text-[10px] text-slate-400 max-w-[200px] truncate mx-auto mt-0.5"
                     x-text="ticket.address"
                     :title="ticket.address"></div>
              </td>

              <!-- 3. No Handphone (WhatsApp Button persis seperti Data Pelanggan) -->
              <td class="py-3.5 px-4 whitespace-nowrap text-center">
                <template x-if="ticket.customer_phone && ticket.customer_phone !== '-'">
                  <a :href="'https://wa.me/' + formatWaPhone(ticket.customer_phone) + '?text=' + encodeURIComponent('Halo Bapak/Ibu ' + ticket.customer_name + ', terkait laporan tiket ' + ticket.id + ' (' + ticket.type + '), tim operasional Banterpool Fiber sedang menindaklanjuti. Terima kasih.')"
                     target="_blank"
                     class="inline-flex items-center justify-center gap-1 text-emerald-600 hover:text-emerald-700 font-bold text-xs bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200/80 transition"
                     title="Hubungi Pelanggan via WhatsApp">
                    <i class="fa-brands fa-whatsapp text-sm"></i>
                    <span x-text="ticket.customer_phone"></span>
                  </a>
                </template>
                <template x-if="!ticket.customer_phone || ticket.customer_phone === '-'">
                  <span class="text-[11px] text-slate-400 italic">-</span>
                </template>
              </td>

              <!-- 4. Jenis Kendala & Bukti Gambar -->
              <td class="py-3.5 px-4 text-center max-w-xs">
                <div class="font-bold text-slate-900 text-xs" x-text="ticket.type"></div>
                <p class="text-[11px] text-slate-500 truncate max-w-[220px] mx-auto mt-0.5"
                   x-text="ticket.description"
                   :title="ticket.description"></p>

                <!-- Lampiran Foto / PDF jika ada -->
                <template x-if="ticket.attachment_url">
                  <div class="mt-1.5 flex items-center justify-center gap-1.5">
                    <template x-if="ticket.attachment_type === 'image' || !ticket.attachment_type || ticket.attachment_type === 'file'">
                      <button type="button" @click.stop="openLightbox(ticket.attachment_url, ticket.id, ticket.attachment || 'Foto Bukti Kendala', ticket.customer_name)"
                              class="inline-flex items-center gap-1 py-0.5 px-2 rounded-lg bg-red-50 hover:bg-red-100 text-brand border border-red-200/80 text-[10px] font-bold transition group"
                              title="Klik untuk perbesar foto bukti">
                        <img :src="ticket.attachment_url" alt="Bukti" class="w-4 h-4 rounded object-cover border border-red-300 shrink-0">
                        <i class="fa-solid fa-camera text-[9px]"></i>
                        <span>Foto Bukti</span>
                      </button>
                    </template>
                    <template x-if="ticket.attachment_type === 'pdf'">
                      <a :href="ticket.attachment_url" target="_blank" @click.stop
                         class="inline-flex items-center gap-1 text-[10px] font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200 px-2 py-0.5 rounded-lg transition"
                         title="Buka Dokumen PDF Lampiran">
                        <i class="fa-solid fa-file-pdf text-red-500 text-[10px]"></i>
                        <span>File PDF</span>
                      </a>
                    </template>
                  </div>
                </template>
              </td>

              <!-- 5. Titik ODP -->
              <td class="py-3.5 px-4 whitespace-nowrap text-center">
                <a href="{{ route('admin.odc-map') }}" title="Cek di Peta ODC & Fiber"
                   class="inline-flex items-center justify-center gap-1 font-bold text-xs text-brand hover:underline bg-red-50/60 px-2 py-0.5 rounded-lg border border-red-100">
                  <i class="fa-solid fa-map-pin text-[10px]"></i>
                  <span x-text="ticket.odp"></span>
                </a>
              </td>

              <!-- 6. Prioritas -->
              <td class="py-3.5 px-4 whitespace-nowrap text-center">
                <template x-if="ticket.priority === 'Kritis'">
                  <span class="inline-flex items-center gap-1 bg-red-100 text-red-700 border border-red-200 text-[10px] font-extrabold px-2.5 py-1 rounded-full">
                    <span class="w-1.5 h-1.5 rounded-full bg-red-600 animate-pulse"></span>
                    Kritis
                  </span>
                </template>
                <template x-if="ticket.priority === 'Tinggi'">
                  <span class="inline-flex items-center gap-1 bg-amber-100 text-amber-800 border border-amber-300 text-[10px] font-extrabold px-2.5 py-1 rounded-full">
                    <i class="fa-solid fa-arrow-up text-[8px]"></i>
                    Tinggi
                  </span>
                </template>
                <template x-if="ticket.priority !== 'Kritis' && ticket.priority !== 'Tinggi'">
                  <span class="inline-flex items-center gap-1 bg-slate-100 text-slate-700 border border-slate-200 text-[10px] font-extrabold px-2.5 py-1 rounded-full">
                    <i class="fa-solid fa-minus text-[8px]"></i>
                    Normal
                  </span>
                </template>
              </td>

              <!-- 7. Status & Waktu Update -->
              <td class="py-3.5 px-4 whitespace-nowrap text-center">
                <div>
                  <template x-if="ticket.status === 'Selesai'">
                    <span class="bg-emerald-100 text-emerald-800 text-[10px] font-extrabold px-2.5 py-1 rounded-full border border-emerald-300 inline-flex items-center gap-1">
                      <i class="fa-solid fa-circle text-[6px]"></i> Selesai
                    </span>
                  </template>
                  <template x-if="ticket.status === 'Sedang Ditangani'">
                    <span class="bg-indigo-100 text-indigo-800 text-[10px] font-extrabold px-2.5 py-1 rounded-full border border-indigo-300 inline-flex items-center gap-1">
                      <i class="fa-solid fa-screwdriver-wrench text-[9px]"></i> Diproses
                    </span>
                  </template>
                  <template x-if="ticket.status === 'Menunggu Respon'">
                    <span class="bg-amber-100 text-amber-800 text-[10px] font-extrabold px-2.5 py-1 rounded-full border border-amber-300 inline-flex items-center gap-1">
                      <i class="fa-regular fa-clock text-[9px]"></i> Menunggu
                    </span>
                  </template>
                </div>

                <!-- Update Timestamp Mini -->
                <div class="mt-1 text-[10px] text-slate-400 font-mono"
                     :title="'Waktu Update Terakhir: ' + (ticket.updated_at_full || ticket.updated_at)">
                  <span x-text="getFormattedDate(ticket.updated_date || ticket.updated_at_full || ticket.updated_at)"></span>
                </div>
              </td>

              <!-- 8. Teknisi Bertugas -->
              <td class="py-3.5 px-4 text-center whitespace-nowrap">
                <span class="font-semibold text-slate-700 text-xs" x-text="ticket.technician || '-'"></span>
              </td>

              <!-- 9. Aksi (Persis seperti ikon tombol Data Pelanggan) -->
              <td class="py-3.5 px-4 whitespace-nowrap text-center w-28">
                <div class="flex items-center justify-center gap-1.5">
                  
                  <!-- Tombol Tangani / Detail Modal -->
                  <button type="button" @click="viewTicket(ticket)"
                          class="w-7 h-7 rounded-lg bg-brand hover:bg-brand-700 text-white flex items-center justify-center transition shadow-2xs"
                          title="Tangani / Ubah Status Tiket">
                    <i class="fa-solid fa-screwdriver-wrench text-xs"></i>
                  </button>

                  <!-- Tombol Hubungi WhatsApp -->
                  <template x-if="ticket.customer_phone && ticket.customer_phone !== '-'">
                    <a :href="'https://wa.me/' + formatWaPhone(ticket.customer_phone) + '?text=' + encodeURIComponent('Halo Bapak/Ibu ' + ticket.customer_name + ', terkait laporan kendala ' + ticket.id + ' (' + ticket.type + '), tim teknisi Banterpool sedang menangani. Mohon ditunggu. Terima kasih.')"
                       target="_blank"
                       class="w-7 h-7 rounded-lg bg-emerald-50 hover:bg-emerald-600 text-emerald-600 hover:text-white flex items-center justify-center transition border border-emerald-200/80"
                       title="Kirim Pesan WhatsApp">
                      <i class="fa-brands fa-whatsapp text-xs"></i>
                    </a>
                  </template>

                  <!-- Tombol Cek Peta ODC -->
                  <a href="{{ route('admin.odc-map') }}"
                     class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center transition"
                     title="Cek Lokasi di Peta ODC">
                    <i class="fa-solid fa-map-location-dot text-xs"></i>
                  </a>

                  <!-- Tombol Hapus Tiket -->
                  <button type="button" @click="confirmDeleteTicket(ticket)"
                          class="w-7 h-7 rounded-lg bg-red-50 hover:bg-red-600 text-red-600 hover:text-white flex items-center justify-center transition border border-red-200/80"
                          title="Hapus Laporan Kendala">
                    <i class="fa-solid fa-trash-can text-xs"></i>
                  </button>

                </div>
              </td>

            </tr>
          </template>

          <!-- Empty State -->
          <template x-if="tickets.length === 0">
            <tr>
              <td colspan="10" class="text-center py-16 text-slate-400">
                <div class="max-w-xs mx-auto space-y-2">
                  <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto text-xl">
                    <i class="fa-solid fa-headset"></i>
                  </div>
                  <p class="font-bold text-slate-700 text-sm">Tidak Ada Laporan Gangguan</p>
                  <p class="text-xs text-slate-400">Tidak ada tiket laporan kendala yang cocok dengan kriteria pencarian dan filter saat ini.</p>
                </div>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ============================================== -->
  <!-- 6. MODAL DETAIL & UPDATE STATUS TIKET          -->
  <!-- ============================================== -->
  <div x-show="openModal" style="display: none;"
       x-transition:enter="ease-out duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="ease-in duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0"
       class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-xs">

    <div @click.away="openModal = false"
         class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-7 shadow-2xl relative space-y-5"
         x-show="selectedTicket">

      <!-- Header Modal -->
      <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <div>
          <span class="text-[10px] font-mono font-bold text-slate-400 uppercase tracking-wider">Penanganan Tiket Masalah</span>
          <h3 class="text-base font-black text-slate-900" x-text="selectedTicket?.id + ' - ' + selectedTicket?.type"></h3>
        </div>
        <button type="button" @click="openModal = false" class="text-slate-400 hover:text-black text-xl p-1">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <!-- Detail Tiket -->
      <div class="space-y-4 text-xs">
        
        <!-- Info Pelanggan & Lokasi -->
        <div class="bg-slate-50 rounded-xl p-4 space-y-2 border border-slate-200/80">
          <div class="flex justify-between items-center">
            <span class="text-slate-500 font-medium">Pelanggan:</span>
            <span class="font-bold text-slate-900" x-text="selectedTicket?.customer_name + ' (' + selectedTicket?.customer_phone + ')'"></span>
          </div>
          <div class="flex justify-between items-start">
            <span class="text-slate-500 font-medium">Alamat:</span>
            <span class="font-medium text-slate-800 text-right max-w-xs" x-text="selectedTicket?.address"></span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-slate-500 font-medium">ODP & Koordinat:</span>
            <a href="{{ route('admin.odc-map') }}" class="font-bold text-brand hover:underline" x-text="selectedTicket?.odp + (selectedTicket?.coordinates ? ' [' + selectedTicket?.coordinates + ']' : '')"></a>
          </div>
          <div class="flex justify-between items-center pt-1 border-t border-slate-200/60">
            <span class="text-slate-500 font-medium">Waktu Dilaporkan:</span>
            <span class="font-bold text-slate-800 font-mono text-right" x-text="selectedTicket?.created_at_full || selectedTicket?.created_at"></span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-slate-500 font-medium">Update Terakhir:</span>
            <span class="font-bold text-emerald-800 font-mono bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200/80 text-right" x-text="selectedTicket?.updated_at_full || selectedTicket?.updated_at || '-'"></span>
          </div>
        </div>

        <!-- Deskripsi Keluhan -->
        <div class="border border-slate-200/80 rounded-xl p-4 space-y-1">
          <span class="font-bold text-slate-900 block">Keluhan Pelanggan:</span>
          <p class="text-slate-600 leading-relaxed" x-text="selectedTicket?.description"></p>
        </div>

        <!-- FOTO BUKTI / LAMPIRAN GAMBAR DARI PELANGGAN -->
        <div class="border border-slate-200/80 rounded-2xl p-4 space-y-3 bg-slate-50/70">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="w-6 h-6 rounded-lg bg-red-100 text-brand flex items-center justify-center text-xs">
                <i class="fa-solid fa-camera"></i>
              </div>
              <span class="font-bold text-slate-900 text-xs">Foto Bukti / Lampiran Kendala Pelanggan</span>
            </div>
            <template x-if="selectedTicket?.attachment_url">
              <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold flex items-center gap-1">
                <i class="fa-solid fa-check text-[8px]"></i>
                <span>Ada Lampiran</span>
              </span>
            </template>
          </div>

          <!-- Jika Ada Lampiran Foto/Gambar -->
          <template x-if="selectedTicket?.attachment_url && (selectedTicket?.attachment_type === 'image' || !selectedTicket?.attachment_type || selectedTicket?.attachment_type === 'file')">
            <div class="space-y-2.5">
              <div class="relative group rounded-xl overflow-hidden border border-slate-200 bg-white shadow-2xs">
                <img :src="selectedTicket.attachment_url"
                     alt="Foto Bukti Pelanggan"
                     class="w-full max-h-60 object-contain bg-slate-950/5 p-1 transition duration-200 cursor-pointer"
                     @click="openLightbox(selectedTicket.attachment_url, selectedTicket.id, selectedTicket.attachment, selectedTicket.customer_name)">
                
                <div class="absolute inset-0 bg-slate-900/60 opacity-0 group-hover:opacity-100 transition duration-200 flex items-center justify-center gap-3">
                  <button type="button"
                          @click="openLightbox(selectedTicket.attachment_url, selectedTicket.id, selectedTicket.attachment, selectedTicket.customer_name)"
                          class="bg-white text-slate-900 px-3 py-1.5 rounded-xl text-xs font-bold hover:bg-slate-100 transition shadow flex items-center gap-1.5">
                    <i class="fa-solid fa-magnifying-glass-plus text-brand"></i>
                    <span>Perbesar Foto</span>
                  </button>
                  <a :href="selectedTicket.attachment_url" target="_blank"
                     class="bg-brand text-white px-3 py-1.5 rounded-xl text-xs font-bold hover:bg-brand-700 transition shadow flex items-center gap-1.5">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    <span>Buka Tab Baru</span>
                  </a>
                </div>
              </div>

              <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1">
                <div class="flex items-center gap-1.5 truncate max-w-xs">
                  <i class="fa-regular fa-file-image text-slate-400"></i>
                  <span class="font-medium text-slate-700 truncate" x-text="selectedTicket.attachment || 'Foto Bukti'"></span>
                  <span class="text-slate-400" x-text="selectedTicket.attachment_size ? '(' + selectedTicket.attachment_size + ')' : ''"></span>
                </div>
                <button type="button"
                        @click="openLightbox(selectedTicket.attachment_url, selectedTicket.id, selectedTicket.attachment, selectedTicket.customer_name)"
                        class="text-brand hover:underline font-bold text-[11px] flex items-center gap-1 shrink-0">
                  <i class="fa-solid fa-expand text-[10px]"></i>
                  <span>Lihat Ukuran Penuh</span>
                </button>
              </div>
            </div>
          </template>

          <!-- Jika Ada Lampiran Dokumen PDF -->
          <template x-if="selectedTicket?.attachment_url && selectedTicket?.attachment_type === 'pdf'">
            <div class="p-3 bg-white rounded-xl border border-slate-200 flex items-center justify-between gap-3">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center text-lg shrink-0">
                  <i class="fa-solid fa-file-pdf"></i>
                </div>
                <div>
                  <p class="font-bold text-slate-900 text-xs truncate max-w-xs" x-text="selectedTicket.attachment || 'Dokumen Bukti.pdf'"></p>
                  <p class="text-[10px] text-slate-400" x-text="selectedTicket.attachment_size || 'Dokumen PDF'"></p>
                </div>
              </div>
              <a :href="selectedTicket.attachment_url" target="_blank"
                 class="bg-slate-900 hover:bg-black text-white px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shrink-0">
                <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
                <span>Buka PDF</span>
              </a>
            </div>
          </template>

          <!-- Jika Pelanggan Tidak Melampirkan Foto/File -->
          <template x-if="!selectedTicket?.attachment_url">
            <div class="py-3 px-4 rounded-xl border border-dashed border-slate-200 text-center bg-white/60">
              <p class="text-[11px] text-slate-400 flex items-center justify-center gap-1.5">
                <i class="fa-regular fa-image text-slate-300 text-sm"></i>
                <span>Pelanggan tidak melampirkan foto bukti pada tiket pengaduan ini.</span>
              </p>
            </div>
          </template>

        </div>

        <!-- LIVE CLOCK CARD: Waktu Real-Time Pencatatan Update -->
        <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 rounded-2xl p-4 text-white border border-slate-700 shadow-md flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 flex items-center justify-center text-base shrink-0">
              <i class="fa-solid fa-clock text-emerald-400"></i>
            </div>
            <div>
              <div class="flex items-center gap-1.5">
                <span class="text-[10px] font-extrabold uppercase tracking-widest text-slate-300">Waktu Update Status (Real-Time)</span>
                <span class="flex h-2 w-2 relative">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <span class="text-[9px] bg-emerald-500/20 text-emerald-300 px-1.5 py-0.2 rounded font-mono font-bold">SAAT INI</span>
              </div>
              <p class="text-xs font-bold text-slate-200 mt-1 flex items-center gap-1.5">
                <i class="fa-regular fa-calendar-days text-emerald-400 text-xs"></i>
                <span x-text="liveDate"></span>
              </p>
              <p class="text-sm font-black font-mono text-emerald-300 tracking-wide" x-text="liveTime"></p>
            </div>
          </div>
          <div class="text-right hidden sm:block">
            <span class="text-[11px] font-mono text-emerald-400 font-bold block">Zona Waktu WIB</span>
            <span class="text-[10px] text-slate-400 font-sans">NOC Banyumas (UTC+7)</span>
          </div>
        </div>

        <!-- Form Update Status & Teknisi -->
        <form @submit.prevent="submitUpdate"
              :action="'/admin/laporan-masalah/' + selectedTicket?.id + '/status'" method="POST" class="space-y-3">
          @csrf

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-[11px] font-bold text-slate-700 mb-1">Ubah Status Tiket</label>
              <select name="status" x-model="formStatus"
                      class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl p-2.5 font-semibold focus:ring-brand focus:border-brand">
                <option value="Sedang Ditangani">Sedang Ditangani (Proses)</option>
                <option value="Selesai">Selesai (Sudah Normal)</option>
                <option value="Menunggu Respon">Menunggu Respon (Antrian)</option>
              </select>
            </div>

            <div>
              <label class="block text-[11px] font-bold text-slate-700 mb-1">Tugaskan Teknisi</label>
              <select name="technician" x-model="formTechnician"
                      class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl p-2.5 font-semibold focus:ring-brand focus:border-brand">
                <option value="Mamat (Tim Fiber)">Mamat (Tim Fiber)</option>
                <option value="Fajar & Tim Lapangan">Fajar & Tim Lapangan</option>
                <option value="Bambang Irawan (Perangkat)">Bambang Irawan (Perangkat)</option>
                <option value="NOC Helpdesk (Remote)">NOC Helpdesk (Remote)</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-[11px] font-bold text-slate-700 mb-1">Catatan Penanganan / Update Teknisi</label>
            <textarea name="notes" x-model="formNotes" rows="2" placeholder="Tuliskan tindakan yang dilakukan teknisi atau hasil pengecekan..."
                      class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl p-2.5 focus:ring-brand focus:border-brand resize-none"></textarea>
          </div>

          <!-- Indikator Waktu Eksekusi Real-Time -->
          <div class="bg-blue-50 border border-blue-200/80 rounded-xl p-2.5 flex items-center gap-2 text-[11px] text-blue-800">
            <i class="fa-solid fa-circle-info text-blue-600 text-xs shrink-0"></i>
            <span>
              Waktu status diperbarui akan otomatis tercatat secara real-time:
              <strong class="font-mono text-blue-950 font-bold" x-text="liveDate + ', ' + liveTime"></strong> saat tombol simpan ditekan.
            </span>
          </div>

          <!-- Riwayat Pembaruan Status (History Log) -->
          <template x-if="selectedTicket?.status_history && selectedTicket.status_history.length > 0">
            <div class="border border-slate-200 rounded-xl p-3 bg-slate-50/60 space-y-2">
              <div class="flex items-center justify-between">
                <span class="font-bold text-slate-700 text-[11px] flex items-center gap-1.5">
                  <i class="fa-solid fa-timeline text-slate-400"></i>
                  <span>Riwayat Update Status Terkini</span>
                </span>
                <span class="text-[10px] text-slate-400 font-mono" x-text="selectedTicket.status_history.length + ' Catatan'"></span>
              </div>
              <div class="max-h-28 overflow-y-auto space-y-1.5 custom-scrollbar pr-1">
                <template x-for="(item, hIdx) in selectedTicket.status_history" :key="hIdx">
                  <div class="bg-white p-2 rounded-lg border border-slate-200 text-[10px] flex items-start justify-between gap-2">
                    <div>
                      <div class="flex items-center gap-1.5 font-bold">
                        <span class="px-1.5 py-0.2 rounded text-[9px]"
                              :class="item.status === 'Selesai' ? 'bg-emerald-100 text-emerald-800' : (item.status === 'Sedang Ditangani' ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800')"
                              x-text="item.status"></span>
                        <span class="text-slate-800 font-semibold" x-text="item.technician"></span>
                      </div>
                      <p class="text-slate-500 mt-0.5" x-text="item.notes || '-'"></p>
                    </div>
                    <span class="text-slate-400 font-mono shrink-0 text-[9px]" x-text="item.updated_at"></span>
                  </div>
                </template>
              </div>
            </div>
          </template>

          <div class="pt-2 flex items-center justify-end gap-2.5">
            <button type="button" @click="openModal = false" :disabled="isSubmitting"
                    class="border border-slate-300 text-slate-700 font-bold text-xs py-2 px-4 rounded-xl hover:bg-slate-50 transition">
              Batal
            </button>
            <button type="submit" :disabled="isSubmitting"
                    class="bg-brand hover:bg-brand-700 text-white font-bold text-xs py-2 px-5 rounded-xl shadow-xs transition flex items-center gap-1.5">
              <template x-if="isSubmitting">
                <i class="fa-solid fa-circle-notch fa-spin text-xs"></i>
              </template>
              <template x-if="!isSubmitting">
                <i class="fa-solid fa-floppy-disk text-xs"></i>
              </template>
              <span x-text="isSubmitting ? 'Menyimpan...' : 'Simpan & Perbarui Tiket'"></span>
            </button>
          </div>

        </form>

      </div>

    </div>

  </div>

  <!-- ============================================== -->
  <!-- 7. FLOATING TOAST NOTIFIKASI REAL-TIME         -->
  <!-- ============================================== -->
  <div x-show="toast.show" style="display: none;"
       x-transition:enter="ease-out duration-300"
       x-transition:enter-start="opacity-0 translate-y-4 scale-95"
       x-transition:enter-end="opacity-100 translate-y-0 scale-100"
       x-transition:leave="ease-in duration-200"
       x-transition:leave-start="opacity-100 translate-y-0 scale-100"
       x-transition:leave-end="opacity-0 translate-y-4 scale-95"
       class="fixed bottom-6 right-6 z-50 max-w-md bg-slate-900 text-white p-4 rounded-2xl shadow-2xl border border-slate-700 flex items-start gap-3">
    <div class="w-8 h-8 rounded-xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 flex items-center justify-center shrink-0">
      <i class="fa-solid fa-circle-check text-sm" x-show="toast.type !== 'info'"></i>
      <i class="fa-solid fa-bell text-sm" x-show="toast.type === 'info'"></i>
    </div>
    <div class="flex-1 text-xs">
      <p class="font-bold text-slate-100" x-text="toast.type === 'info' ? 'Laporan Kendala Baru Masuk' : 'Status Tiket Berhasil Diperbarui'"></p>
      <p class="text-slate-300 text-[11px] mt-0.5 leading-relaxed" x-text="toast.message"></p>
    </div>
    <button type="button" @click="toast.show = false" class="text-slate-400 hover:text-white text-sm p-1">
      <i class="fa-solid fa-xmark"></i>
    </button>
  </div>

  <!-- ============================================== -->
  <!-- 8. MODAL LIGHTBOX PREVIEW FOTO BUKTI           -->
  <!-- ============================================== -->
  <div x-show="lightbox.open" style="display: none;"
       x-transition:enter="ease-out duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="ease-in duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0"
       @keydown.escape.window="lightbox.open = false"
       class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-sm">

    <div @click.away="lightbox.open = false"
         class="bg-slate-900 text-white rounded-3xl max-w-3xl w-full p-5 sm:p-6 shadow-2xl border border-slate-700 relative space-y-4">
      
      <!-- Header Lightbox -->
      <div class="flex items-center justify-between border-b border-slate-800 pb-3">
        <div class="flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-xl bg-red-500/20 text-red-400 border border-red-500/30 flex items-center justify-center text-sm">
            <i class="fa-solid fa-camera"></i>
          </div>
          <div>
            <h4 class="font-bold text-sm text-slate-100" x-text="'Foto Bukti Kendala: ' + lightbox.ticketId"></h4>
            <p class="text-[11px] text-slate-400" x-text="'Pelanggan: ' + (lightbox.customerName || 'Pelanggan') + ' • ' + lightbox.fileName"></p>
          </div>
        </div>
        
        <div class="flex items-center gap-2">
          <a :href="lightbox.imageUrl" target="_blank"
             class="w-8 h-8 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white flex items-center justify-center text-xs transition" title="Buka Gambar di Tab Baru">
            <i class="fa-solid fa-arrow-up-right-from-square"></i>
          </a>
          <button type="button" @click="lightbox.open = false"
                  class="w-8 h-8 rounded-xl bg-slate-800 hover:bg-red-500/20 text-slate-400 hover:text-red-400 flex items-center justify-center text-sm transition" title="Tutup">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
      </div>

      <!-- Preview Image Container -->
      <div class="bg-black/60 rounded-2xl p-2 flex items-center justify-center overflow-hidden border border-slate-800 min-h-[300px] max-h-[70vh]">
        <img :src="lightbox.imageUrl"
             :alt="lightbox.fileName"
             class="max-w-full max-h-[66vh] object-contain rounded-xl shadow-lg">
      </div>

      <!-- Footer Info Lightbox -->
      <div class="flex items-center justify-between text-xs text-slate-400 pt-1">
        <span class="text-[11px]">Foto bukti yang dikirim langsung oleh pelanggan melalui formulir pengaduan kendala.</span>
        <button type="button" @click="lightbox.open = false"
                class="bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs px-4 py-1.5 rounded-xl transition">
          Tutup Preview
        </button>
      </div>
    </div>
  </div>

  <!-- ============================================== -->
  <!-- 9. MODAL KONFIRMASI HAPUS SINGLE TIKET         -->
  <!-- ============================================== -->
  <div x-show="openDeleteModal" style="display: none;"
       x-transition:enter="ease-out duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="ease-in duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0"
       class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-xs">
    <div @click.away="openDeleteModal = false"
         class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl relative text-center space-y-4">
      <div class="w-14 h-14 rounded-2xl bg-red-100 text-red-600 flex items-center justify-center mx-auto text-2xl">
        <i class="fa-solid fa-triangle-exclamation"></i>
      </div>
      <div>
        <h3 class="text-base font-black text-slate-900">Hapus Laporan Gangguan?</h3>
        <p class="text-xs text-slate-500 mt-1">
          Apakah Anda yakin ingin menghapus tiket <strong class="text-slate-900" x-text="selectedTicketToDelete?.id"></strong> milik <strong class="text-slate-900" x-text="selectedTicketToDelete?.customer_name"></strong>? Tindakan ini tidak dapat dibatalkan.
        </p>
      </div>
      <form :action="'/admin/laporan-masalah/' + (selectedTicketToDelete ? selectedTicketToDelete.id : '')"
            method="POST"
            @submit.prevent="executeDeleteSingle($event)"
            class="flex items-center justify-center gap-3 pt-2">
        @csrf
        @method('DELETE')
        <button type="button" @click="openDeleteModal = false" :disabled="isDeleting"
                class="px-4 py-2 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition">
          Batal
        </button>
        <button type="submit" :disabled="isDeleting"
                class="px-5 py-2 text-xs font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl transition shadow-xs flex items-center gap-1.5">
          <template x-if="isDeleting">
            <i class="fa-solid fa-circle-notch fa-spin text-xs"></i>
          </template>
          <span x-text="isDeleting ? 'Menghapus...' : 'Ya, Hapus Laporan'"></span>
        </button>
      </form>
    </div>
  </div>

  <!-- ============================================== -->
  <!-- 10. MODAL KONFIRMASI HAPUS MASSAL & PILIHAN   -->
  <!-- ============================================== -->
  <div x-show="openBulkDeleteModal" style="display: none;"
       x-transition:enter="ease-out duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="ease-in duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0"
       class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-xs">
    <div @click.away="openBulkDeleteModal = false"
         class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl relative text-center space-y-4">
      <div class="w-14 h-14 rounded-2xl bg-red-100 text-red-600 flex items-center justify-center mx-auto text-2xl">
        <i class="fa-solid fa-trash-can"></i>
      </div>
      <div>
        <h3 class="text-base font-black text-slate-900">
          <span x-text="bulkDeleteAll ? 'Hapus Seluruh Data Laporan Masalah?' : 'Hapus ' + selectedIds.length + ' Laporan Terpilih?'"></span>
        </h3>
        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
          <span x-show="!bulkDeleteAll">
            Apakah Anda yakin ingin menghapus <strong class="text-slate-900"><span x-text="selectedIds.length"></span> laporan kendala</strong> yang dipilih?
          </span>
          <span x-show="bulkDeleteAll">
            Apakah Anda yakin ingin membersihkan <strong class="text-red-600">SELURUH data laporan kendala jaringan</strong> (<span x-text="tickets.length"></span> tiket)?
          </span>
          Tindakan ini tidak dapat dibatalkan.
        </p>
        <div class="mt-2.5 p-2.5 bg-slate-50 border border-slate-200/80 rounded-xl text-left">
          <p class="text-[11px] text-slate-600 flex items-start gap-2">
            <i class="fa-solid fa-circle-info text-blue-500 mt-0.5 shrink-0"></i>
            <span><strong>Info Otomatis:</strong> Sistem secara berkala hanya menghapus riwayat laporan yang telah berstatus <strong>Selesai</strong> lebih dari <strong>3 hari</strong>. Tombol Hapus Pilihan dan Hapus Semua Data ini memberikan kontrol penuh bagi Admin & Direktur untuk menghapus data secara manual.</span>
          </p>
        </div>
      </div>
      
      <form action="{{ route('admin.laporan.bulk-delete') }}" method="POST" @submit.prevent="executeBulkDelete($event)" class="pt-2 flex items-center justify-center gap-3">
        @csrf
        <input type="hidden" name="delete_all" :value="bulkDeleteAll ? '1' : '0'">
        <input type="hidden" name="ids_json" :value="JSON.stringify(selectedIds)">
        <template x-for="id in selectedIds" :key="id">
          <input type="hidden" name="ids[]" :value="id">
        </template>

        <button type="button" @click="openBulkDeleteModal = false" :disabled="isDeleting"
                class="px-4 py-2 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition">
          Batal
        </button>
        <button type="submit" :disabled="isDeleting || (!bulkDeleteAll && selectedIds.length === 0)"
                class="px-5 py-2 text-xs font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl transition shadow-xs flex items-center gap-1.5 disabled:opacity-50">
          <template x-if="isDeleting">
            <i class="fa-solid fa-circle-notch fa-spin text-xs"></i>
          </template>
          <span x-text="isDeleting ? 'Menghapus...' : (bulkDeleteAll ? 'Ya, Bersihkan Semua' : 'Ya, Hapus Pilihan')"></span>
        </button>
      </form>
    </div>
  </div>

</div>

@push('scripts')
<script>
function laporanMasalahApp(initialTickets, initialCounts, statusFilter) {
  return {
    tickets: initialTickets || [],
    counts: initialCounts || { all: 0, menunggu: 0, proses: 0, selesai: 0, kritis: 0 },
    statusFilter: statusFilter || 'all',
    openModal: false,
    selectedTicket: null,
    formStatus: '',
    formTechnician: '',
    formNotes: '',
    isSubmitting: false,
    isPolling: false,
    liveTime: '',
    liveTimeFull: '',
    liveDate: '',
    toast: {
      show: false,
      message: '',
      type: 'success'
    },
    lightbox: {
      open: false,
      imageUrl: '',
      ticketId: '',
      fileName: '',
      customerName: ''
    },
    selectedIds: [],
    bulkDeleteAll: false,
    openDeleteModal: false,
    openBulkDeleteModal: false,
    selectedTicketToDelete: null,
    isDeleting: false,

    init() {
      this.updateClock();
      setInterval(() => {
        this.updateClock();
      }, 1000);

      // Polling tiket baru secara real-time setiap 6 detik
      setInterval(() => {
        this.pollTickets();
      }, 6000);
    },

    toggleSelectAll() {
      if (this.tickets.length > 0 && this.selectedIds.length === this.tickets.length) {
        this.selectedIds = [];
      } else {
        this.selectedIds = this.tickets.map(t => t.id);
      }
    },

    selectAllTickets() {
      this.selectedIds = this.tickets.map(t => t.id);
    },

    clearSelection() {
      this.selectedIds = [];
    },

    confirmBulkDelete(all = false) {
      if (all) {
        this.bulkDeleteAll = true;
        this.openBulkDeleteModal = true;
      } else {
        if (this.selectedIds.length === 0) {
          this.showToast('Silakan pilih minimal satu laporan untuk dihapus.', 'error');
          return;
        }
        this.bulkDeleteAll = false;
        this.openBulkDeleteModal = true;
      }
    },

    confirmBulkDeleteAll() {
      this.confirmBulkDelete(true);
    },

    async pollTickets() {
      if (this.openModal || this.openDeleteModal || this.openBulkDeleteModal || this.isSubmitting || this.isDeleting) return;
      this.isPolling = true;

      try {
        const url = '{{ route("admin.laporan") }}?status=' + encodeURIComponent(this.statusFilter) + '&q=' + encodeURIComponent('{{ $search }}');
        const response = await fetch(url, {
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          }
        });

        if (response.ok) {
          const data = await response.json();
          if (data.tickets && Array.isArray(data.tickets)) {
            const currentIds = new Set(this.tickets.map(t => t.id));
            const newTickets = data.tickets.filter(t => !currentIds.has(t.id));

            if (newTickets.length > 0) {
              newTickets.forEach(t => t.is_new_incoming = true);
              this.showToast('Laporan kendala baru masuk dari ' + newTickets[0].customer_name + ' (' + newTickets[0].id + ')', 'info');
            }

            const recentMap = {};
            this.tickets.forEach(t => {
              if (t.is_recently_updated) recentMap[t.id] = true;
            });

            this.tickets = data.tickets.map(t => {
              if (recentMap[t.id]) t.is_recently_updated = true;
              return t;
            });

            // Sinkronkan selectedIds dengan tiket yang masih ada
            if (this.selectedIds.length > 0) {
              const liveIds = new Set(this.tickets.map(t => t.id));
              this.selectedIds = this.selectedIds.filter(id => liveIds.has(id));
            }

            if (data.counts) {
              this.counts = data.counts;
            }
          }
        }
      } catch (e) {
        // Silent
      } finally {
        this.isPolling = false;
      }
    },

    confirmDeleteTicket(ticket) {
      this.selectedTicketToDelete = ticket;
      this.openDeleteModal = true;
    },

    async executeDeleteSingle(event) {
      if (!this.selectedTicketToDelete) return;
      this.isDeleting = true;

      const ticketId = this.selectedTicketToDelete.id;
      const url = '/admin/laporan-masalah/' + encodeURIComponent(ticketId);
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

      try {
        const response = await fetch(url, {
          method: 'DELETE',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
          }
        });

        if (response.ok) {
          const resData = await response.json();
          this.tickets = this.tickets.filter(t => t.id !== ticketId);
          this.selectedIds = this.selectedIds.filter(id => id !== ticketId);
          if (resData.counts) {
            this.counts = resData.counts;
          } else {
            this.recalculateCounts();
          }
          this.showToast('Tiket ' + ticketId + ' berhasil dihapus.', 'success');
          this.openDeleteModal = false;
          this.selectedTicketToDelete = null;
        } else {
          if (event && event.target && event.target.tagName === 'FORM') {
            event.target.submit();
          } else {
            window.location.reload();
          }
        }
      } catch (err) {
        if (event && event.target && event.target.tagName === 'FORM') {
          event.target.submit();
        } else {
          window.location.reload();
        }
      } finally {
        this.isDeleting = false;
      }
    },

    async executeBulkDelete(event) {
      this.isDeleting = true;

      const url = '{{ route("admin.laporan.bulk-delete") }}';
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
      const payload = this.bulkDeleteAll 
        ? { delete_all: 1 } 
        : { ids: this.selectedIds, ids_json: JSON.stringify(this.selectedIds) };

      try {
        const response = await fetch(url, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify(payload)
        });

        if (response.ok) {
          const resData = await response.json();
          if (this.bulkDeleteAll) {
            this.tickets = [];
            this.selectedIds = [];
            this.counts = { all: 0, menunggu: 0, proses: 0, selesai: 0, kritis: 0 };
          } else {
            const removedIds = new Set(this.selectedIds);
            this.tickets = this.tickets.filter(t => !removedIds.has(t.id));
            this.selectedIds = [];
            if (resData.counts) {
              this.counts = resData.counts;
            } else {
              this.recalculateCounts();
            }
          }
          this.showToast(resData.message || 'Laporan masalah berhasil dihapus.', 'success');
          this.openBulkDeleteModal = false;
        } else {
          this.fallbackSubmitBulkDelete(event);
        }
      } catch (err) {
        this.fallbackSubmitBulkDelete(event);
      } finally {
        this.isDeleting = false;
      }
    },

    fallbackSubmitBulkDelete(event) {
      let form = (event && event.target && event.target.tagName === 'FORM') 
        ? event.target 
        : document.querySelector('form[action="{{ route("admin.laporan.bulk-delete") }}"]');
      if (form) {
        if (!this.bulkDeleteAll && this.selectedIds.length > 0) {
          this.selectedIds.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = id;
            form.appendChild(input);
          });
        }
        form.submit();
      } else {
        window.location.reload();
      }
    },

    updateClock() {
      const now = new Date();
      this.liveTime = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' WIB';
      this.liveTimeFull = this.liveTime;
      this.liveDate = now.toLocaleDateString('id-ID', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' });
      this.liveDateTimeFull = this.liveDate + ', ' + this.liveTime;
    },

    getFormattedDate(dateStr) {
      if (!dateStr) return this.liveDate;
      if (typeof dateStr === 'string' && dateStr.includes(',')) {
        const parts = dateStr.split(',');
        if (parts.length >= 2) {
          return (parts[0] + ',' + parts[1]).trim();
        }
      }
      return dateStr;
    },

    getFormattedTime(dateStr) {
      if (!dateStr) return '';
      if (typeof dateStr === 'string' && dateStr.includes('WIB')) {
        const matches = dateStr.match(/\d{1,2}:\d{2}(?::\d{2})?\s*WIB/);
        if (matches) return matches[0];
      }
      return dateStr;
    },

    formatFullDateTime(str) {
      if (!str) return '-';
      return str;
    },

    viewTicket(ticket) {
      const freshTicket = this.tickets.find(t => t.id === ticket.id) || ticket;
      this.selectedTicket = JSON.parse(JSON.stringify(freshTicket));
      this.formStatus = this.selectedTicket.status;
      this.formTechnician = this.selectedTicket.technician || 'Mamat (Tim Fiber)';
      this.formNotes = this.selectedTicket.notes || '';
      this.openModal = true;
    },

    openLightbox(url, ticketId, fileName, customerName) {
      if (!url) return;
      this.lightbox.imageUrl = url;
      this.lightbox.ticketId = ticketId || '';
      this.lightbox.fileName = fileName || 'Foto Bukti Kendala';
      this.lightbox.customerName = customerName || '';
      this.lightbox.open = true;
    },

    closeLightbox() {
      this.lightbox.open = false;
    },

    formatWaPhone(phone) {
      if (!phone) return '';
      return String(phone).replace(/\D/g, '').replace(/^0/, '62');
    },

    showToast(message, type = 'success') {
      this.toast.message = message;
      this.toast.type = type;
      this.toast.show = true;
      setTimeout(() => {
        this.toast.show = false;
      }, 5000);
    },

    async submitUpdate(event) {
      if (!this.selectedTicket) return;
      this.isSubmitting = true;

      const url = '/admin/laporan-masalah/' + encodeURIComponent(this.selectedTicket.id) + '/status';
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

      try {
        const response = await fetch(url, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({
            status: this.formStatus,
            technician: this.formTechnician,
            notes: this.formNotes,
            client_date: this.liveDate,
            client_time: this.liveTime,
            client_datetime: this.liveDateTimeFull
          })
        });

        if (!response.ok) {
          throw new Error('Server returned HTTP ' + response.status);
        }

        const data = await response.json();

        if (data.success && data.ticket) {
          data.ticket.is_recently_updated = true;
          data.ticket.is_new_incoming = false;

          const idx = this.tickets.findIndex(t => t.id === data.ticket.id);
          if (idx !== -1) {
            this.tickets[idx] = data.ticket;
          } else {
            this.tickets.unshift(data.ticket);
          }

          if (data.counts) {
            this.counts = data.counts;
          }

          this.showToast(data.message || ('Tiket ' + data.ticket.id + ' status berhasil diperbarui.'), 'success');
          this.openModal = false;
        } else {
          event.target.submit();
        }
      } catch (err) {
        console.warn('AJAX update failed, falling back to form submit:', err);
        event.target.submit();
      } finally {
        this.isSubmitting = false;
      }
    },

    recalculateCounts() {
      this.counts.all = this.tickets.length;
      this.counts.menunggu = this.tickets.filter(t => t.status === 'Menunggu Respon').length;
      this.counts.proses = this.tickets.filter(t => t.status === 'Sedang Ditangani').length;
      this.counts.selesai = this.tickets.filter(t => t.status === 'Selesai').length;
      this.counts.kritis = this.tickets.filter(t => t.priority === 'Kritis' && t.status !== 'Selesai').length;
    }
  };
}
</script>
@endpush
@endsection
