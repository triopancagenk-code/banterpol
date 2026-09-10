@extends('layouts.admin')

@section('title', 'Admin NOC Banterpool - Pengintaian Laporan Masalah')
@section('page-title', 'Pengintaian & Penanganan Laporan Masalah (Trouble Tickets)')

@section('content')
<div class="space-y-6"
     x-data="laporanMasalahApp(@js($tickets), @js($counts), '{{ $statusFilter }}')">

  <!-- ============================================== -->
  <!-- 1. HEADER & FILTER BAR                         -->
  <!-- ============================================== -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h2 class="text-xl font-black text-slate-900 tracking-tight">Daftar Tiket Gangguan Jaringan</h2>
      <p class="text-xs text-slate-500 mt-0.5">Kelola laporan kendala pelanggan yang masuk secara real-time, penugasan teknisi lapangan, dan update status tiket.</p>
    </div>

    <!-- Live Alert Status & Real-Time Sync Indicator -->
    <div class="flex items-center gap-2">
      <!-- Live Server Time: Format Hari, Tanggal, Bulan, Tahun & Jam Real-Time -->
      <div class="hidden lg:flex items-center gap-2.5 bg-slate-900 text-white text-xs px-3.5 py-1.5 rounded-xl border border-slate-800 shadow-xs font-mono">
        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
        <span class="text-slate-400 font-sans text-[10px]">Waktu Real-Time:</span>
        <span class="font-bold text-emerald-300" x-text="liveDate + ' • ' + liveTime"></span>
      </div>

      <!-- Live Syncing Indicator -->
      <div class="hidden sm:flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl bg-slate-100 text-slate-600 text-[11px] font-semibold border border-slate-200">
        <i class="fa-solid fa-arrows-rotate text-[10px] text-brand" :class="isPolling ? 'fa-spin' : ''"></i>
      <template x-if="counts.kritis > 0">
        <span class="bg-red-50 text-red-700 border border-red-200 text-xs font-bold px-3 py-1.5 rounded-xl flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-red-500 animate-ping"></span>
          <span x-text="counts.kritis + ' Gangguan Kritis Terbuka'"></span>
        </span>
      </template>
    </div>
  </div>

  <!-- Filter Tabs Bar -->
  <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
    
    <!-- Status Pills -->
    <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
      <a href="{{ route('admin.laporan', ['status' => 'all', 'q' => $search]) }}"
         class="px-3.5 py-1.5 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'all' ? 'bg-brand text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
        <span>Semua</span>
        <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $statusFilter === 'all' ? 'bg-white/20' : 'bg-slate-200' }}" x-text="counts.all">{{ $counts['all'] }}</span>
      </a>

      <a href="{{ route('admin.laporan', ['status' => 'Menunggu Respon', 'q' => $search]) }}"
         class="px-3.5 py-1.5 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'Menunggu Respon' ? 'bg-amber-500 text-white font-bold shadow-xs' : 'bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100' }}">
        <i class="fa-regular fa-clock"></i>
        <span>Menunggu Respon</span>
        <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $statusFilter === 'Menunggu Respon' ? 'bg-white/20' : 'bg-amber-200' }}" x-text="counts.menunggu">{{ $counts['menunggu'] }}</span>
      </a>

      <a href="{{ route('admin.laporan', ['status' => 'Sedang Ditangani', 'q' => $search]) }}"
         class="px-3.5 py-1.5 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'Sedang Ditangani' ? 'bg-blue-600 text-white font-bold shadow-xs' : 'bg-blue-50 text-blue-800 border border-blue-200 hover:bg-blue-100' }}">
        <i class="fa-solid fa-person-digging"></i>
        <span>Sedang Ditangani</span>
        <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $statusFilter === 'Sedang Ditangani' ? 'bg-white/20' : 'bg-blue-200' }}" x-text="counts.proses">{{ $counts['proses'] }}</span>
      </a>

      <a href="{{ route('admin.laporan', ['status' => 'Selesai', 'q' => $search]) }}"
         class="px-3.5 py-1.5 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'Selesai' ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100' }}">
        <i class="fa-solid fa-check"></i>
        <span>Selesai</span>
        <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $statusFilter === 'Selesai' ? 'bg-white/20' : 'bg-emerald-200' }}" x-text="counts.selesai">{{ $counts['selesai'] }}</span>
      </a>
    </div>

    <!-- Search Form -->
    <form method="GET" action="{{ route('admin.laporan') }}" class="flex items-center gap-2">
      <input type="hidden" name="status" value="{{ $statusFilter }}">
      <div class="relative w-full sm:w-64">
        <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400 text-xs"></i>
        <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama, tiket, deskripsi..."
               class="w-full pl-9 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand">
      </div>
      <button type="submit" class="bg-brand text-white px-3 py-1.5 rounded-xl text-xs font-bold hover:bg-brand-700 transition">
        Cari
      </button>
      @if($search)
        <a href="{{ route('admin.laporan', ['status' => $statusFilter]) }}" class="text-xs text-slate-400 hover:text-red-500 font-bold">Reset</a>
      @endif
    </form>

  </div>

  <!-- ============================================== -->
  <!-- 2. TABEL PENGINTAIAN TIKET GANGGUAN            -->
  <!-- ============================================== -->
  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50 text-slate-500 uppercase font-bold text-[10px] tracking-wider border-b border-slate-200">
          <tr>
            <th class="px-5 py-3.5">ID Tiket & Waktu Lapor</th>
            <th class="px-4 py-3.5">Pelanggan</th>
            <th class="px-4 py-3.5">Jenis Kendala</th>
            <th class="px-4 py-3.5">Titik ODP</th>
            <th class="px-4 py-3.5">Prioritas</th>
            <th class="px-4 py-3.5">Status & Waktu Update</th>
            <th class="px-4 py-3.5">Teknisi Bertugas</th>
            <th class="px-5 py-3.5 text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 font-medium">
          <template x-for="ticket in tickets" :key="ticket.id">
            <tr class="hover:bg-slate-50/70 transition duration-150"
                :class="ticket.is_new_incoming ? 'bg-amber-50/40' : (ticket.is_recently_updated ? 'bg-emerald-50/30' : '')">
              <!-- ID Tiket & Waktu Lapor -->
              <td class="px-5 py-4">
                <div class="flex items-center gap-1.5">
                  <p class="font-bold text-slate-900 font-mono leading-tight" x-text="ticket.id"></p>
                  <template x-if="ticket.is_new_incoming">
                    <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-100 text-amber-800 border border-amber-300 animate-pulse flex items-center gap-1">
                      <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                      <span>Laporan Baru</span>
                    </span>
                  </template>
                  <template x-if="!ticket.is_new_incoming && ticket.is_recently_updated">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping" title="Baru saja diperbarui"></span>
                  </template>
                </div>
                <!-- Tanggal Lapor: Hari, Tanggal, Bulan, Tahun & Jam Real-Time -->
                <div class="mt-1.5 space-y-0.5">
                  <div class="text-[11px] font-semibold text-slate-700 flex items-center gap-1.5">
                    <i class="fa-regular fa-calendar-days text-[11px] text-brand shrink-0"></i>
                    <span x-text="getFormattedDate(ticket.created_date || ticket.created_at_full || ticket.created_at)"></span>
                  </div>
                  <div class="text-[10px] text-slate-500 font-mono flex items-center gap-1.5 pl-4">
                    <i class="fa-regular fa-clock text-[9px] text-slate-400"></i>
                    <span x-text="getFormattedTime(ticket.created_time || ticket.created_at_full || ticket.created_at)"></span>
                  </div>
                </div>
              </td>

              <!-- Pelanggan -->
              <td class="px-4 py-4">
                <p class="font-bold text-slate-900" x-text="ticket.customer_name"></p>
                <div class="flex items-center gap-1.5 text-[11px] text-slate-500 mt-0.5">
                  <i class="fa-brands fa-whatsapp text-emerald-600"></i>
                  <span x-text="ticket.customer_phone"></span>
                </div>
              </td>

              <!-- Jenis Kendala & Bukti Gambar -->
              <td class="px-4 py-4 max-w-xs">
                <span class="font-bold text-slate-900 block" x-text="ticket.type"></span>
                <p class="text-[11px] text-slate-500 truncate mt-0.5" x-text="ticket.description"></p>

                <!-- Indikator / Thumbnail Foto Bukti Lampiran Pelanggan -->
                <template x-if="ticket.attachment_url">
                  <div class="mt-1.5 flex items-center gap-1.5">
                    <template x-if="ticket.attachment_type === 'image' || !ticket.attachment_type || ticket.attachment_type === 'file'">
                      <button type="button" @click.stop="openLightbox(ticket.attachment_url, ticket.id, ticket.attachment || 'Foto Bukti Kendala', ticket.customer_name)"
                              class="inline-flex items-center gap-1.5 py-0.5 px-2 rounded-lg bg-red-50 hover:bg-red-100 text-brand border border-red-200/80 text-[10px] font-bold transition shadow-2xs group"
                              title="Klik untuk perbesar foto bukti">
                        <img :src="ticket.attachment_url" alt="Bukti" class="w-5 h-5 rounded object-cover border border-red-300 group-hover:scale-110 transition shrink-0">
                        <span class="flex items-center gap-1">
                          <i class="fa-solid fa-camera text-[9px]"></i>
                          <span>Foto Bukti</span>
                        </span>
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

              <!-- Titik ODP -->
              <td class="px-4 py-4">
                <a href="{{ route('admin.odc-map') }}" title="Cek di Peta" class="inline-flex items-center gap-1 font-bold text-brand hover:underline">
                  <i class="fa-solid fa-map-pin text-xs"></i>
                  <span x-text="ticket.odp"></span>
                </a>
              </td>

              <!-- Prioritas -->
              <td class="px-4 py-4">
                <template x-if="ticket.priority === 'Kritis'">
                  <span class="inline-flex items-center gap-1 bg-red-100 text-red-700 border border-red-200 text-[10px] font-bold px-2.5 py-0.5 rounded-md">
                    <span class="w-1.5 h-1.5 rounded-full bg-red-600 animate-pulse"></span>
                    Kritis
                  </span>
                </template>
                <template x-if="ticket.priority === 'Tinggi'">
                  <span class="bg-amber-100 text-amber-800 border border-amber-200 text-[10px] font-bold px-2.5 py-0.5 rounded-md">
                    Tinggi
                  </span>
                </template>
                <template x-if="ticket.priority !== 'Kritis' && ticket.priority !== 'Tinggi'">
                  <span class="bg-slate-100 text-slate-700 border border-slate-200 text-[10px] font-bold px-2.5 py-0.5 rounded-md">
                    Normal
                  </span>
                </template>
              </td>

              <!-- Status Tiket & WAKTU UPDATE REAL-TIME -->
              <td class="px-4 py-4">
                <!-- Status Badge -->
                <div>
                  <template x-if="ticket.status === 'Selesai'">
                    <span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold px-2.5 py-1 rounded-md">
                      <i class="fa-solid fa-check text-[9px]"></i> Selesai
                    </span>
                  </template>
                  <template x-if="ticket.status === 'Sedang Ditangani'">
                    <span class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-bold px-2.5 py-1 rounded-md">
                      <i class="fa-solid fa-spinner fa-spin text-[9px]"></i> Diproses
                    </span>
                  </template>
                  <template x-if="ticket.status === 'Menunggu Respon'">
                    <span class="inline-flex items-center gap-1 bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-bold px-2.5 py-1 rounded-md animate-pulse">
                      <i class="fa-regular fa-clock text-[9px]"></i> Menunggu
                    </span>
                  </template>
                </div>

                <!-- Waktu Update Terakhir (Real-Time Timestamp: Hari, Tanggal, Bulan, Tahun & Jam) -->
                <div class="mt-1.5 space-y-1">
                  <div class="inline-flex items-start gap-1.5 text-[10px] text-slate-700 bg-slate-50 border border-slate-200 px-2 py-1 rounded-lg font-mono shadow-2xs"
                       :title="'Waktu update status: ' + (ticket.updated_at_full || ticket.updated_at)">
                    <i class="fa-solid fa-clock-rotate-left text-[9px] text-brand mt-0.5 shrink-0"></i>
                    <div>
                      <div class="font-bold text-slate-900 font-sans text-[10px]">
                        <span x-text="getFormattedDate(ticket.updated_date || ticket.updated_at_full || ticket.updated_at)"></span>
                      </div>
                      <div class="text-slate-500 font-mono text-[9px] flex items-center gap-1">
                        <span class="text-slate-400">Pukul:</span>
                        <span class="font-bold text-slate-800" x-text="getFormattedTime(ticket.updated_time || ticket.updated_at_full || ticket.updated_at)"></span>
                      </div>
                    </div>
                  </div>
                  
                  <template x-if="ticket.is_recently_updated">
                    <div>
                      <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-100 text-emerald-700 border border-emerald-200 animate-pulse inline-flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <span>Update Real-Time</span>
                      </span>
                    </div>
                  </template>
                </div>
              </td>

              <!-- Teknisi -->
              <td class="px-4 py-4 text-slate-700">
                <span class="font-medium" x-text="ticket.technician"></span>
              </td>

              <!-- Aksi -->
              <td class="px-5 py-4 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button type="button" @click="viewTicket(ticket)"
                          class="bg-brand hover:bg-brand-700 text-white font-bold text-xs px-3 py-1.5 rounded-lg transition flex items-center gap-1 shadow-2xs">
                    <i class="fa-solid fa-screwdriver-wrench"></i> Tangani
                  </button>

                  <a :href="'https://wa.me/' + formatWaPhone(ticket.customer_phone) + '?text=' + encodeURIComponent('Halo Bapak/Ibu ' + ticket.customer_name + ', terkait laporan tiket ' + ticket.id + ' (' + ticket.type + '), tim teknisi Banterpool sedang menindaklanjuti. Terima kasih.')"
                     target="_blank" title="Hubungi Pelanggan via WA"
                     class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white flex items-center justify-center transition">
                    <i class="fa-brands fa-whatsapp text-sm"></i>
                  </a>
                </div>
              </td>
            </tr>
          </template>

          <!-- Empty State -->
          <template x-if="tickets.length === 0">
            <tr>
              <td colspan="8" class="text-center py-12 text-slate-400">
                <i class="fa-solid fa-headset text-3xl mb-2 text-slate-300"></i>
                <p>Tidak ada laporan gangguan yang cocok dengan pencarian.</p>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ============================================== -->
  <!-- 3. MODAL DETAIL & UPDATE STATUS TIKET          -->
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
            <a href="{{ route('admin.odc-map') }}" class="font-bold text-brand hover:underline" x-text="selectedTicket?.odp + ' [' + selectedTicket?.coordinates + ']'"></a>
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
              <!-- Thumbnail & Preview Container -->
              <div class="relative group rounded-xl overflow-hidden border border-slate-200 bg-white shadow-2xs">
                <img :src="selectedTicket.attachment_url"
                     alt="Foto Bukti Pelanggan"
                     class="w-full max-h-60 object-contain bg-slate-950/5 p-1 transition duration-200 cursor-pointer"
                     @click="openLightbox(selectedTicket.attachment_url, selectedTicket.id, selectedTicket.attachment, selectedTicket.customer_name)">
                
                <!-- Hover Overlay Buttons -->
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

              <!-- Metadata File & Action Info -->
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
              <!-- Format Hari, Tanggal, Bulan, Tahun Real-Time -->
              <p class="text-xs font-bold text-slate-200 mt-1 flex items-center gap-1.5">
                <i class="fa-regular fa-calendar-days text-emerald-400 text-xs"></i>
                <span x-text="liveDate"></span>
              </p>
              <!-- Format Jam Real-Time Detik -->
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
                <option value="Randi Pratama (Tim Fiber)">Randi Pratama (Tim Fiber)</option>
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
  <!-- 4. FLOATING TOAST NOTIFIKASI REAL-TIME         -->
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
  <!-- 5. MODAL LIGHTBOX PREVIEW FOTO BUKTI           -->
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

    async pollTickets() {
      if (this.openModal || this.isSubmitting) return;
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

    viewTicket(ticket) {
      const freshTicket = this.tickets.find(t => t.id === ticket.id) || ticket;
      this.selectedTicket = JSON.parse(JSON.stringify(freshTicket));
      this.formStatus = this.selectedTicket.status;
      this.formTechnician = this.selectedTicket.technician || 'Randi Pratama (Tim Fiber)';
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
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

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
    }
  };
}
</script>
@endpush
@endsection
