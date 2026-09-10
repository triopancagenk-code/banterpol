@extends('layouts.admin')

@section('title', 'Admin NOC - WhatsApp Gateway & Broadcast Pelanggan')

@section('content')
<div x-data="whatsappGatewayApp()" class="space-y-6 pb-12">

  <!-- ============================================== -->
  <!-- 1. HEADER & STATUS BAR GATEWAY                 -->
  <!-- ============================================== -->
  <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-slate-700/60 relative overflow-hidden">
    <!-- Glow Background Accents -->
    <div class="absolute -right-16 -top-16 w-64 h-64 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -left-16 -bottom-16 w-64 h-64 bg-brand/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
      <div>
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 text-xs font-bold mb-3 tracking-wide">
          <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
          <i class="fa-brands fa-whatsapp text-sm"></i>
          <span>WHATSAPP GATEWAY ENGINE &bull; NOC CLUSTER</span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white flex items-center gap-3">
          WhatsApp Gateway &amp; Broadcast
        </h1>
        <p class="text-slate-300 text-xs sm:text-sm mt-1.5 max-w-2xl leading-relaxed">
          Kirim pengumuman pemeliharaan (*maintenance*), pengingat tagihan bulanan, notifikasi darurat kabel putus, dan promo massal (*blast*) ke seluruh pelanggan Banterpool secara instan.
        </p>
      </div>

      <!-- Quick Action Buttons -->
      <div class="flex flex-wrap items-center gap-3">
        <button type="button" @click="activeTab = 'broadcast'"
                class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-lg shadow-emerald-900/40 transition flex items-center gap-2">
          <i class="fa-solid fa-paper-plane text-xs"></i>
          <span>Kirim Broadcast</span>
        </button>

        <button type="button" @click="openTestModal = true"
                class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-bold text-xs transition flex items-center gap-2">
          <i class="fa-solid fa-vial text-xs text-amber-400"></i>
          <span>Uji Coba Pesan</span>
        </button>

        <a href="{{ route('admin.whatsapp.logs.export') }}"
           class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-bold text-xs transition flex items-center gap-2">
          <i class="fa-solid fa-file-excel text-xs text-emerald-400"></i>
          <span>Export Excel</span>
        </a>
      </div>
    </div>

    <!-- Status Cards Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-6 pt-6 border-t border-slate-800/80">
      <!-- Status Bot -->
      <div class="bg-slate-800/50 backdrop-blur-xs rounded-2xl p-4 border border-slate-700/50">
        <div class="flex items-center justify-between text-slate-400 text-xs font-semibold mb-1">
          <span>Status Gateway</span>
          <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
        </div>
        <div class="text-emerald-400 font-black text-base flex items-center gap-1.5">
          <i class="fa-solid fa-circle-check text-xs"></i>
          <span>{{ $gatewayStatus['status'] }}</span>
        </div>
        <p class="text-[10px] text-slate-400 mt-1 font-mono">{{ $gatewayStatus['phone_number'] }}</p>
      </div>

      <!-- Total Kontak Pelanggan -->
      <div class="bg-slate-800/50 backdrop-blur-xs rounded-2xl p-4 border border-slate-700/50">
        <div class="text-slate-400 text-xs font-semibold mb-1">Total Pelanggan</div>
        <div class="text-white font-black text-lg sm:text-xl">{{ $stats['total_customers'] }} <span class="text-xs font-normal text-slate-400">Nomor</span></div>
        <p class="text-[10px] text-amber-400 mt-1 font-medium">{{ $stats['unpaid_customers'] }} Belum Bayar Tagihan</p>
      </div>

      <!-- Pesan Terkirim -->
      <div class="bg-slate-800/50 backdrop-blur-xs rounded-2xl p-4 border border-slate-700/50">
        <div class="text-slate-400 text-xs font-semibold mb-1">Pesan Terkirim</div>
        <div class="text-white font-black text-lg sm:text-xl">{{ $stats['total_sent'] }} <span class="text-xs font-normal text-slate-400">Logs</span></div>
        <p class="text-[10px] text-emerald-400 mt-1 font-medium">{{ $stats['today_sent'] }} Terkirim Hari Ini</p>
      </div>

      <!-- Delivery Rate -->
      <div class="bg-slate-800/50 backdrop-blur-xs rounded-2xl p-4 border border-slate-700/50">
        <div class="text-slate-400 text-xs font-semibold mb-1">Delivery Success</div>
        <div class="text-white font-black text-lg sm:text-xl">{{ $stats['delivery_rate'] }}</div>
        <p class="text-[10px] text-blue-400 mt-1 font-medium">Anti-Spam Delay Aktif</p>
      </div>
    </div>
  </div>

  <!-- Flash Alerts -->
  @if(session('success'))
    <div class="space-y-3">
      <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-800 px-5 py-4 rounded-2xl flex items-center justify-between text-xs sm:text-sm font-semibold shadow-xs">
        <div class="flex items-center gap-3">
          <i class="fa-solid fa-circle-check text-emerald-600 text-lg"></i>
          <span>{{ session('success') }}</span>
        </div>
        <button @click="$el.closest('.space-y-3').remove()" class="text-emerald-600 hover:text-emerald-800 text-base">&times;</button>
      </div>

      @if(session('needs_wa_web') && session('single_wa_url'))
        <div class="p-4 sm:p-5 rounded-2xl bg-emerald-50 border-2 border-emerald-500/80 text-slate-900 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="space-y-1">
            <div class="flex items-center gap-2 text-emerald-700 font-black text-sm sm:text-base">
              <i class="fa-brands fa-whatsapp text-xl text-emerald-600"></i>
              <span>Kirim Pesan Sekarang ke {{ session('single_wa_phone') }}</span>
            </div>
            <p class="text-xs text-slate-600">
              Karena sistem menggunakan mode <b>Direct WhatsApp Web</b>, klik tombol di sebelah kanan untuk langsung membuka WhatsApp dan mengirimkan pesan ini.
            </p>
          </div>
          <a href="{{ session('single_wa_url') }}" target="_blank"
             class="shrink-0 inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs sm:text-sm shadow-md transition active:scale-95">
            <i class="fa-brands fa-whatsapp text-lg"></i>
            <span>Buka &amp; Kirim di WhatsApp &rarr;</span>
          </a>
        </div>
      @endif
    </div>
  @endif

  @if(session('error'))
    <div class="bg-red-500/10 border border-red-500/30 text-red-800 px-5 py-4 rounded-2xl flex items-center justify-between text-xs sm:text-sm font-semibold shadow-xs">
      <div class="flex items-center gap-3">
        <i class="fa-solid fa-triangle-exclamation text-red-600 text-lg"></i>
        <span>{{ session('error') }}</span>
      </div>
      <button @click="$el.parentElement.remove()" class="text-red-600 hover:text-red-800 text-base">&times;</button>
    </div>
  @endif

  <!-- ============================================== -->
  <!-- 2. TAB NAVIGASI                                -->
  <!-- ============================================== -->
  <div class="border-b border-slate-200">
    <div class="flex items-center gap-2 sm:gap-4 overflow-x-auto custom-scrollbar pb-px text-xs sm:text-sm font-bold">
      <!-- Tab 1: Kirim Broadcast -->
      <button type="button" @click="activeTab = 'broadcast'"
              class="py-3 px-4 rounded-t-xl transition border-b-2 flex items-center gap-2 whitespace-nowrap"
              :class="activeTab === 'broadcast' ? 'border-emerald-600 text-emerald-700 bg-white shadow-xs' : 'border-transparent text-slate-500 hover:text-slate-800 hover:bg-slate-100/60'">
        <i class="fa-solid fa-bullhorn text-sm" :class="activeTab === 'broadcast' ? 'text-emerald-600' : 'text-slate-400'"></i>
        <span>Kirim Broadcast (Blast)</span>
        <span class="bg-emerald-100 text-emerald-800 text-[10px] px-2 py-0.5 rounded-full font-extrabold">Utama</span>
      </button>

      <!-- Tab 2: Daftar Pelanggan -->
      <button type="button" @click="activeTab = 'customers'"
              class="py-3 px-4 rounded-t-xl transition border-b-2 flex items-center gap-2 whitespace-nowrap"
              :class="activeTab === 'customers' ? 'border-emerald-600 text-emerald-700 bg-white shadow-xs' : 'border-transparent text-slate-500 hover:text-slate-800 hover:bg-slate-100/60'">
        <i class="fa-solid fa-address-book text-sm" :class="activeTab === 'customers' ? 'text-emerald-600' : 'text-slate-400'"></i>
        <span>Daftar Pelanggan</span>
        <span class="bg-slate-100 text-slate-700 text-[10px] px-2 py-0.5 rounded-full">{{ count($allCustomers) }}</span>
      </button>

      <!-- Tab 3: Riwayat Log -->
      <button type="button" @click="activeTab = 'logs'"
              class="py-3 px-4 rounded-t-xl transition border-b-2 flex items-center gap-2 whitespace-nowrap"
              :class="activeTab === 'logs' ? 'border-emerald-600 text-emerald-700 bg-white shadow-xs' : 'border-transparent text-slate-500 hover:text-slate-800 hover:bg-slate-100/60'">
        <i class="fa-solid fa-clock-rotate-left text-sm" :class="activeTab === 'logs' ? 'text-emerald-600' : 'text-slate-400'"></i>
        <span>Riwayat Log Pengiriman</span>
        <span class="bg-slate-100 text-slate-700 text-[10px] px-2 py-0.5 rounded-full">{{ count($logs) }}</span>
      </button>

      <!-- Tab 4: Pengaturan Gateway -->
      <button type="button" @click="activeTab = 'settings'"
              class="py-3 px-4 rounded-t-xl transition border-b-2 flex items-center gap-2 whitespace-nowrap"
              :class="activeTab === 'settings' ? 'border-emerald-600 text-emerald-700 bg-white shadow-xs' : 'border-transparent text-slate-500 hover:text-slate-800 hover:bg-slate-100/60'">
        <i class="fa-solid fa-sliders text-sm" :class="activeTab === 'settings' ? 'text-emerald-600' : 'text-slate-400'"></i>
        <span>Konfigurasi Engine</span>
      </button>
    </div>
  </div>

  <!-- ======================================================= -->
  <!-- TAB 1: FORM BROADCAST MASSAL                        -->
  <!-- ======================================================= -->
  <div x-show="activeTab === 'broadcast'" x-cloak class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

    <!-- Kolom Kiri: Form Composer -->
    <div class="lg:col-span-7 bg-white rounded-3xl p-6 sm:p-8 shadow-xs border border-slate-200">
      

      <form action="{{ route('admin.whatsapp.broadcast') }}" method="POST" @submit="onFormSubmit($event)">
        @csrf

        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-5">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-sm">
              <i class="fa-solid fa-bullhorn"></i>
            </div>
            <div>
              <h2 class="font-extrabold text-base text-slate-900">Form Broadcast WhatsApp Massal</h2>
              <p class="text-[11px] text-slate-500">Kirim pesan terstruktur sekaligus ke grup atau seluruh pelanggan.</p>
            </div>
          </div>
          <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-700">
            Target: <strong x-text="getTargetSummary()"></strong>
          </span>
        </div>

        <!-- 1. Sasaran Penerima / Target Filter -->
        <div class="mb-5">
          <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">
            1. Sasaran Penerima (Target Filter)
          </label>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 text-xs">
            <!-- Semua Pelanggan -->
            <label class="cursor-pointer border rounded-2xl p-3 flex flex-col justify-between transition"
                   :class="targetFilter === 'all' ? 'border-emerald-600 bg-emerald-50/50 text-emerald-900 font-bold shadow-xs' : 'border-slate-200 hover:bg-slate-50 text-slate-700'">
              <input type="radio" name="target_filter" value="all" x-model="targetFilter" class="sr-only">
              <div class="flex items-center justify-between mb-1">
                <span class="flex items-center gap-1.5"><i class="fa-solid fa-users text-emerald-600"></i> Semua Pelanggan</span>
                <span x-show="targetFilter === 'all'" class="w-2 h-2 rounded-full bg-emerald-600"></span>
              </div>
              <span class="text-[10px] text-slate-500 font-normal">Seluruh {{ count($allCustomers) }} pelanggan aktif</span>
            </label>

            <!-- Belum Bayar / Tagihan -->
            <label class="cursor-pointer border rounded-2xl p-3 flex flex-col justify-between transition"
                   :class="targetFilter === 'unpaid' ? 'border-amber-600 bg-amber-50/50 text-amber-900 font-bold shadow-xs' : 'border-slate-200 hover:bg-slate-50 text-slate-700'">
              <input type="radio" name="target_filter" value="unpaid" x-model="targetFilter" class="sr-only">
              <div class="flex items-center justify-between mb-1">
                <span class="flex items-center gap-1.5"><i class="fa-solid fa-file-invoice-dollar text-amber-600"></i> Belum Bayar</span>
                <span x-show="targetFilter === 'unpaid'" class="w-2 h-2 rounded-full bg-amber-600"></span>
              </div>
              <span class="text-[10px] text-slate-500 font-normal">{{ $stats['unpaid_customers'] }} Jatuh Tempo/Tunggakan</span>
            </label>

            <!-- Area ODC-01 (Pernasidi) -->
            <label class="cursor-pointer border rounded-2xl p-3 flex flex-col justify-between transition"
                   :class="targetFilter === 'odc-01' ? 'border-blue-600 bg-blue-50/50 text-blue-900 font-bold shadow-xs' : 'border-slate-200 hover:bg-slate-50 text-slate-700'">
              <input type="radio" name="target_filter" value="odc-01" x-model="targetFilter" class="sr-only">
              <div class="flex items-center justify-between mb-1">
                <span class="flex items-center gap-1.5"><i class="fa-solid fa-map-pin text-blue-600"></i> ODC-01 (Pernasidi)</span>
                <span x-show="targetFilter === 'odc-01'" class="w-2 h-2 rounded-full bg-blue-600"></span>
              </div>
              <span class="text-[10px] text-slate-500 font-normal">Area Pernasidi &amp; Sekitarnya</span>
            </label>

            <!-- Area ODC-02 (Panembangan & Karanglo) -->
            <label class="cursor-pointer border rounded-2xl p-3 flex flex-col justify-between transition"
                   :class="targetFilter === 'odc-02' ? 'border-blue-600 bg-blue-50/50 text-blue-900 font-bold shadow-xs' : 'border-slate-200 hover:bg-slate-50 text-slate-700'">
              <input type="radio" name="target_filter" value="odc-02" x-model="targetFilter" class="sr-only">
              <div class="flex items-center justify-between mb-1">
                <span class="flex items-center gap-1.5"><i class="fa-solid fa-map-pin text-blue-600"></i> ODC-02 (Panembangan)</span>
                <span x-show="targetFilter === 'odc-02'" class="w-2 h-2 rounded-full bg-blue-600"></span>
              </div>
              <span class="text-[10px] text-slate-500 font-normal">Panembangan &amp; Karanglo</span>
            </label>

            <!-- Area ODC-03 (Jatisaba & Pejogol) -->
            <label class="cursor-pointer border rounded-2xl p-3 flex flex-col justify-between transition"
                   :class="targetFilter === 'odc-03' ? 'border-blue-600 bg-blue-50/50 text-blue-900 font-bold shadow-xs' : 'border-slate-200 hover:bg-slate-50 text-slate-700'">
              <input type="radio" name="target_filter" value="odc-03" x-model="targetFilter" class="sr-only">
              <div class="flex items-center justify-between mb-1">
                <span class="flex items-center gap-1.5"><i class="fa-solid fa-map-pin text-blue-600"></i> ODC-03 (Jatisaba)</span>
                <span x-show="targetFilter === 'odc-03'" class="w-2 h-2 rounded-full bg-blue-600"></span>
              </div>
              <span class="text-[10px] text-slate-500 font-normal">Jatisaba &amp; Pejogol</span>
            </label>

            <!-- Manual / Input Nomor Kustom -->
            <label class="cursor-pointer border rounded-2xl p-3 flex flex-col justify-between transition"
                   :class="targetFilter === 'manual' ? 'border-purple-600 bg-purple-50/50 text-purple-900 font-bold shadow-xs' : 'border-slate-200 hover:bg-slate-50 text-slate-700'">
              <input type="radio" name="target_filter" value="manual" x-model="targetFilter" class="sr-only">
              <div class="flex items-center justify-between mb-1">
                <span class="flex items-center gap-1.5"><i class="fa-solid fa-keyboard text-purple-600"></i> Input Manual</span>
                <span x-show="targetFilter === 'manual'" class="w-2 h-2 rounded-full bg-purple-600"></span>
              </div>
              <span class="text-[10px] text-slate-500 font-normal">Input nomor per baris</span>
            </label>
          </div>

          <!-- Input Box jika pilih Manual -->
          <div x-show="targetFilter === 'manual'" x-cloak class="mt-3">
            <textarea name="manual_numbers" rows="2" placeholder="Ketikkan nomor WhatsApp pisahkan dengan koma atau baris baru, contoh: 08818679774, 085712345678"
                      class="w-full text-xs rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 p-2.5"></textarea>
          </div>
        </div>

        <!-- 2. Pilihan Template Pesan Siap Pakai -->
        <div class="mb-5">
          <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">
            2. Pilihan Template Pesan
          </label>
          <div class="flex flex-wrap gap-2 text-xs">
            @foreach($templates as $key => $tpl)
              <button type="button" @click="selectTemplate('{{ $key }}')"
                      class="px-3 py-2 rounded-xl border transition flex items-center gap-1.5 text-xs font-bold"
                      :class="selectedTemplateKey === '{{ $key }}' ? 'bg-slate-900 text-white border-slate-900 shadow-xs' : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200'">
                <i class="fa-solid {{ $tpl['icon'] }}"></i>
                <span>{{ $tpl['badge'] }}</span>
              </button>
            @endforeach
          </div>
          <input type="hidden" name="message_type" :value="selectedTemplateKey">
        </div>

        <!-- 3. Smart Dynamic Variable Tags -->
        <div class="mb-4">
          <div class="flex items-center justify-between mb-1.5">
            <label class="text-xs font-bold text-slate-800 uppercase tracking-wider">
              3. Variabel Otomatis (Klik untuk Sisipkan)
            </label>
            <span class="text-[10px] text-slate-400">Otomatis diganti sesuai data pelanggan</span>
          </div>
          <div class="flex flex-wrap gap-1.5 text-[11px]">
            <button type="button" @click="insertVariable('{nama}')" class="px-2.5 py-1 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 hover:bg-emerald-100 font-mono transition">
              + {nama}
            </button>
            <button type="button" @click="insertVariable('{tagihan}')" class="px-2.5 py-1 rounded-lg bg-blue-50 border border-blue-200 text-blue-800 hover:bg-blue-100 font-mono transition">
              + {tagihan}
            </button>
            <button type="button" @click="insertVariable('{paket}')" class="px-2.5 py-1 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 hover:bg-amber-100 font-mono transition">
              + {paket}
            </button>
            <button type="button" @click="insertVariable('{jatuh_tempo}')" class="px-2.5 py-1 rounded-lg bg-purple-50 border border-purple-200 text-purple-800 hover:bg-purple-100 font-mono transition">
              + {jatuh_tempo}
            </button>
            <button type="button" @click="insertVariable('{alamat}')" class="px-2.5 py-1 rounded-lg bg-slate-100 border border-slate-300 text-slate-700 hover:bg-slate-200 font-mono transition">
              + {alamat}
            </button>
            <button type="button" @click="insertVariable('{link_bayar}')" class="px-2.5 py-1 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 hover:bg-emerald-100 font-mono transition">
              + {link_bayar}
            </button>
          </div>
        </div>

        <!-- 4. Textarea Pesan -->
        <div class="mb-6">
          <div class="flex items-center justify-between mb-1.5">
            <label class="text-xs font-bold text-slate-800 uppercase tracking-wider">
              4. Isi Pesan WhatsApp
            </label>
            <span class="text-[11px] text-slate-400 font-mono" x-text="messageText.length + ' karakter'"></span>
          </div>
          <textarea id="broadcast-message-area" name="message" rows="8" x-model="messageText" required
                    class="w-full text-xs font-mono leading-relaxed rounded-2xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 p-4 custom-scrollbar shadow-inner bg-slate-50/50"
                    placeholder="Tuliskan pesan WhatsApp Anda di sini..."></textarea>
          <p class="text-[11px] text-slate-400 mt-1">Format WhatsApp didukung: *tebal*, _miring_, ~coret~, ```kode```.</p>
        </div>

        <!-- Submit & Actions -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 border-t border-slate-100">
          <div class="flex items-center gap-2 text-slate-500 text-xs">
            <i class="fa-solid fa-shield-halved text-emerald-600"></i>
            <span>Aman dari banned: antrean dengan jeda otomatis</span>
          </div>

          <div class="flex items-center gap-2.5 w-full sm:w-auto">
            <button type="button" @click="openTestModal = true"
                    class="w-full sm:w-auto px-4 py-2.5 rounded-xl border border-slate-300 hover:bg-slate-100 text-slate-700 font-bold text-xs transition">
              <i class="fa-solid fa-vial text-amber-500 mr-1.5"></i> Tes Kirim
            </button>

            <button type="submit" :disabled="isSending"
                    class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white font-extrabold text-xs shadow-lg shadow-emerald-900/30 transition flex items-center justify-center gap-2">
              <template x-if="!isSending">
                <span class="flex items-center gap-2">
                  <i class="fa-brands fa-whatsapp text-sm"></i>
                  <span>Kirim Broadcast Sekarang</span>
                </span>
              </template>
              <template x-if="isSending">
                <span class="flex items-center gap-2">
                  <i class="fa-solid fa-spinner fa-spin text-sm"></i>
                  <span>Mengirim Broadcast...</span>
                </span>
              </template>
            </button>
          </div>
        </div>

      </form>
    </div>

    <!-- Kolom Kanan: Interactive Live WhatsApp Phone Mockup -->
    <div class="lg:col-span-5 flex flex-col items-center">
      <div class="sticky top-6 w-full max-w-sm">

        <div class="text-center mb-3">
          <span class="inline-flex items-center gap-1.5 text-xs font-extrabold text-slate-700 bg-white px-3 py-1 rounded-full border border-slate-200 shadow-xs">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            Live WhatsApp Mobile Preview
          </span>
        </div>

        <!-- Phone Frame Device Mockup -->
        <div class="bg-slate-900 rounded-[44px] p-3.5 shadow-2xl border-4 border-slate-800 relative w-full">
          <!-- Speaker & Camera Notch -->
          <div class="absolute top-5 left-1/2 -translate-x-1/2 w-24 h-4 bg-black rounded-full z-30 flex items-center justify-center">
            <div class="w-3 h-3 rounded-full bg-slate-900 mr-2"></div>
            <div class="w-2 h-2 rounded-full bg-blue-950"></div>
          </div>

          <!-- Phone Screen -->
          <div class="bg-[#efeae2] rounded-[34px] overflow-hidden flex flex-col h-[540px] relative border border-slate-800/40">
            <!-- Screen Header (WhatsApp Header) -->
            <div class="bg-[#075e54] text-white pt-7 pb-2.5 px-3 flex items-center gap-2.5 shadow-md z-20">
              <i class="fa-solid fa-arrow-left text-xs cursor-pointer"></i>
              <div class="w-8 h-8 rounded-full bg-emerald-700 border border-emerald-400 flex items-center justify-center text-white text-xs font-bold">
                <i class="fa-solid fa-wifi text-xs"></i>
              </div>
              <div class="flex-1 min-w-0">
                <div class="font-bold text-xs truncate flex items-center gap-1">
                  <span>Banterpool NOC Bot</span>
                  <i class="fa-solid fa-circle-check text-[10px] text-emerald-300"></i>
                </div>
                <div class="text-[9px] text-emerald-200">Online &bull; Akun Resmi</div>
              </div>
              <div class="flex items-center gap-3 text-xs text-white/90">
                <i class="fa-solid fa-video"></i>
                <i class="fa-solid fa-phone"></i>
                <i class="fa-solid fa-ellipsis-vertical"></i>
              </div>
            </div>

            <!-- Chat Area with Subtle WhatsApp Doodle Background -->
            <div class="flex-1 p-3 overflow-y-auto custom-scrollbar flex flex-col justify-end space-y-2.5 bg-[#efeae2]">
              <!-- Date Pill -->
              <div class="text-center my-1">
                <span class="bg-white/80 backdrop-blur-xs text-[9px] font-bold text-slate-600 px-2.5 py-0.5 rounded-md shadow-2xs uppercase tracking-wider">
                  Hari Ini
                </span>
              </div>

              <!-- Security Notice -->
              <div class="bg-[#ffeecd] border border-amber-300/40 rounded-lg p-2 text-center text-[9px] text-amber-900 leading-tight shadow-2xs">
                <i class="fa-solid fa-lock text-[8px]"></i> Pesan ini dikirim langsung oleh sistem otomatis Banterpool ISP.
              </div>

              <!-- WhatsApp Chat Bubble (Received from Bot) -->
              <div class="self-start max-w-[88%] bg-white rounded-2xl rounded-tl-xs p-3 shadow-xs border border-slate-200/60 relative">
                <!-- Sender label -->
                <div class="text-[10px] font-bold text-emerald-700 mb-1 flex items-center justify-between">
                  <span>Banterpool NOC Official</span>
                  <span class="text-[8px] text-slate-400">Terverifikasi</span>
                </div>

                <!-- Message Body Preview -->
                <div class="text-xs text-slate-800 whitespace-pre-line leading-relaxed font-sans" x-text="getPreviewText()"></div>

                <!-- Bubble Footer (Time & Status) -->
                <div class="flex items-center justify-end gap-1 text-[9px] text-slate-400 mt-2 font-mono">
                  <span x-text="currentTime"></span>
                  <i class="fa-solid fa-check-double text-blue-500 text-[10px]"></i>
                </div>
              </div>
            </div>

            <!-- Screen Input Box (Read-only aesthetic) -->
            <div class="bg-[#f0f2f5] p-2 flex items-center gap-2 border-t border-slate-200">
              <div class="w-6 h-6 rounded-full flex items-center justify-center text-slate-500 text-xs">
                <i class="fa-regular fa-face-smile"></i>
              </div>
              <div class="flex-1 bg-white rounded-full px-3 py-1.5 text-[10px] text-slate-400 border border-slate-200 truncate">
                Balas pesan ke Banterpool NOC...
              </div>
              <div class="w-7 h-7 rounded-full bg-[#00a884] text-white flex items-center justify-center text-xs shadow-xs">
                <i class="fa-solid fa-microphone"></i>
              </div>
            </div>

          </div>
        </div>

      </div>
    </div>

  </div>

  <!-- ======================================================= -->
  <!-- TAB 2: DAFTAR SELURUH PELANGGAN                        -->
  <!-- ======================================================= -->
  <div x-show="activeTab === 'customers'" x-cloak class="bg-white rounded-3xl p-6 shadow-xs border border-slate-200">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
      <div>
        <h2 class="font-extrabold text-base text-slate-900 flex items-center gap-2">
          <span>Daftar Kontak Seluruh Pelanggan</span>
          <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold">{{ count($filteredCustomers) }} Pelanggan</span>
        </h2>
        <p class="text-xs text-slate-500 mt-0.5">Database seluruh pelanggan terhubung yang siap menerima notifikasi WhatsApp.</p>
      </div>

      <!-- Search & Filters -->
      <div class="flex items-center gap-2">
        <form action="{{ route('admin.whatsapp') }}" method="GET" class="flex items-center gap-2">
          <input type="hidden" name="tab" value="customers">
          <div class="relative">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama, no WA, desa..."
                   class="pl-8 pr-3 py-1.5 text-xs rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 w-48 sm:w-64">
          </div>
          <button type="submit" class="px-3 py-1.5 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-slate-800">
            Cari
          </button>
        </form>
      </div>
    </div>

    <div class="overflow-x-auto custom-scrollbar">
      <table class="w-full text-left text-xs border-collapse">
        <thead>
          <tr class="bg-slate-50 text-slate-600 font-extrabold border-b border-slate-200">
            <th class="p-3.5 text-center w-12">No</th>
            <th class="p-3.5">Pelanggan</th>
            <th class="p-3.5">Nomor WhatsApp</th>
            <th class="p-3.5">Paket Internet</th>
            <th class="p-3.5">Wilayah / ODC</th>
            <th class="p-3.5">Status Tagihan</th>
            <th class="p-3.5 text-center">Aksi Cepat</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse($filteredCustomers as $index => $customer)
            <tr class="hover:bg-slate-50/80 transition">
              <td class="p-3.5 text-center text-slate-400 font-mono">{{ $index + 1 }}</td>
              <td class="p-3.5">
                <div class="font-extrabold text-slate-900">{{ $customer['name'] }}</div>
                <div class="text-[11px] text-slate-400 truncate max-w-xs">{{ $customer['address'] }}</div>
              </td>
              <td class="p-3.5 font-mono font-bold text-slate-700">
                <div class="flex items-center gap-1.5">
                  <i class="fa-brands fa-whatsapp text-emerald-600 text-sm"></i>
                  <span>{{ $customer['phone'] }}</span>
                </div>
              </td>
              <td class="p-3.5">
                <span class="px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 border border-blue-200 text-[11px] font-bold">
                  {{ $customer['package_name'] }}
                </span>
              </td>
              <td class="p-3.5">
                <div class="font-semibold text-slate-800">{{ $customer['area'] }}</div>
                <div class="text-[10px] text-slate-400 font-mono">{{ $customer['odc'] }}</div>
              </td>
              <td class="p-3.5">
                @if(in_array($customer['bill_status'], ['Belum Bayar', 'Jatuh Tempo']))
                  <span class="px-2 py-0.5 rounded-md bg-amber-50 text-amber-700 border border-amber-200 text-[11px] font-bold">
                    {{ $customer['bill_status'] }}
                  </span>
                @else
                  <span class="px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200 text-[11px] font-bold">
                    {{ $customer['bill_status'] }}
                  </span>
                @endif
              </td>
              <td class="p-3.5 text-center">
                <div class="flex items-center justify-center gap-1.5">
                  <!-- Buka Chat WA Langsung -->
                  @php
                    $directPhone = preg_replace('/[^0-9]/', '', $customer['phone']);
                    if (str_starts_with($directPhone, '0')) {
                        $directPhone = '62' . substr($directPhone, 1);
                    }
                    $defaultMsg = "Halo Kak {$customer['name']}, perkenalkan kami dari tim Admin Banterpool ISP Cilongok.";
                    $directUrl = "https://wa.me/{$directPhone}?text=" . urlencode($defaultMsg);
                  @endphp
                  <a href="{{ $directUrl }}" target="_blank"
                     class="px-2.5 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-[11px] font-bold transition flex items-center gap-1"
                     title="Chat langsung di WhatsApp Web">
                    <i class="fa-brands fa-whatsapp text-xs"></i>
                    <span>Chat</span>
                  </a>

                  <!-- Tes Kirim ke Pelanggan ini -->
                  <button type="button" @click="openSingleModal('{{ $customer['phone'] }}', '{{ $customer['name'] }}')"
                          class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-bold transition"
                          title="Kirim pesan khusus">
                    <i class="fa-solid fa-paper-plane text-[10px]"></i>
                  </button>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="p-8 text-center text-slate-400">
                Tidak ada data pelanggan yang sesuai dengan pencarian Anda.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <!-- ======================================================= -->
  <!-- TAB 3: RIWAYAT LOG PENGIRIMAN                          -->
  <!-- ======================================================= -->
  <div x-show="activeTab === 'logs'" x-cloak class="bg-white rounded-3xl p-6 shadow-xs border border-slate-200">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
      <div>
        <h2 class="font-extrabold text-base text-slate-900 flex items-center gap-2">
          <span>Riwayat Log Pengiriman WhatsApp</span>
          <span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-800 text-xs font-bold">{{ count($logs) }} Pesan</span>
        </h2>
        <p class="text-xs text-slate-500 mt-0.5">Seluruh pesan yang dikirim tercatat secara persisten di database.</p>
      </div>

      <div class="flex items-center gap-2">
        <a href="{{ route('admin.whatsapp.logs.export') }}"
           class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5">
          <i class="fa-solid fa-file-excel"></i>
          <span>Unduh Excel</span>
        </a>

        @if(count($logs) > 0)
          <form action="{{ route('admin.whatsapp.logs.clear') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus SELURUH riwayat log pengiriman WhatsApp?')">
            @csrf
            <button type="submit" class="px-3.5 py-2 rounded-xl bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 font-bold text-xs transition flex items-center gap-1.5">
              <i class="fa-solid fa-trash-can"></i>
              <span>Bersihkan Log</span>
            </button>
          </form>
        @endif
      </div>
    </div>

    <div class="overflow-x-auto custom-scrollbar">
      <table class="w-full text-left text-xs border-collapse">
        <thead>
          <tr class="bg-slate-50 text-slate-600 font-extrabold border-b border-slate-200">
            <th class="p-3.5 text-center w-12">No</th>
            <th class="p-3.5">Waktu Kirim</th>
            <th class="p-3.5">Penerima</th>
            <th class="p-3.5">Tipe Pesan</th>
            <th class="p-3.5">Isi Pesan</th>
            <th class="p-3.5 text-center">Status</th>
            <th class="p-3.5 text-center">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse($logs as $index => $log)
            <tr class="hover:bg-slate-50/80 transition">
              <td class="p-3.5 text-center text-slate-400 font-mono">{{ $index + 1 }}</td>
              <td class="p-3.5 font-mono text-[11px] text-slate-600 whitespace-nowrap">
                {{ $log->created_at ? $log->created_at->format('d M Y H:i:s') : '-' }}
              </td>
              <td class="p-3.5">
                <div class="font-extrabold text-slate-900">{{ $log->recipient_name }}</div>
                <div class="text-[11px] text-emerald-700 font-mono flex items-center gap-1">
                  <i class="fa-brands fa-whatsapp text-xs"></i>
                  <span>{{ $log->recipient_phone }}</span>
                </div>
              </td>
              <td class="p-3.5">
                <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase
                  {{ $log->message_type === 'maintenance' ? 'bg-amber-100 text-amber-800' : '' }}
                  {{ $log->message_type === 'billing' ? 'bg-blue-100 text-blue-800' : '' }}
                  {{ $log->message_type === 'outage' ? 'bg-red-100 text-red-800' : '' }}
                  {{ $log->message_type === 'promo' ? 'bg-emerald-100 text-emerald-800' : '' }}
                  {{ in_array($log->message_type, ['custom', 'single', 'broadcast']) ? 'bg-slate-100 text-slate-800' : '' }}">
                  {{ $log->message_type }}
                </span>
                @if($log->batch_id)
                  <div class="text-[9px] text-slate-400 font-mono mt-0.5">{{ $log->batch_id }}</div>
                @endif
              </td>
              <td class="p-3.5 max-w-xs sm:max-w-md">
                <div class="text-slate-700 font-sans line-clamp-2 text-xs" title="{{ $log->message }}">
                  {{ $log->message }}
                </div>
              </td>
              <td class="p-3.5 text-center">
                @if($log->status === 'sent')
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                    <i class="fa-solid fa-circle-check text-[10px]"></i> Terkirim / Siap
                  </span>
                @elseif($log->status === 'failed')
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-800">
                    <i class="fa-solid fa-circle-xmark text-[10px]"></i> Gagal API
                  </span>
                @else
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-800">
                    {{ $log->status }}
                  </span>
                @endif
              </td>
              <td class="p-3.5 text-center">
                <div class="flex items-center justify-center gap-2">
                  <a href="{{ $log->whatsapp_url }}" target="_blank"
                     class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold text-xs shadow-xs transition active:scale-95"
                     title="Buka Chat di WhatsApp Web untuk Mengirim">
                    <i class="fa-brands fa-whatsapp text-sm"></i>
                    <span>Kirim ke WA</span>
                  </a>
                  <form action="{{ route('admin.whatsapp.logs.delete', $log->id) }}" method="POST" onsubmit="return confirm('Hapus riwayat log ini?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-7 h-7 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 flex items-center justify-center text-xs transition" title="Hapus">
                      <i class="fa-solid fa-trash-can"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="p-8 text-center text-slate-400">
                Belum ada riwayat pengiriman pesan WhatsApp. Silakan kirimkan broadcast atau pesan uji coba pertama Anda!
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <!-- ======================================================= -->
  <!-- TAB 4: KONFIGURASI ENGINE GATEWAY                      -->
  <!-- ======================================================= -->
  <div x-show="activeTab === 'settings'" x-cloak class="max-w-3xl bg-white rounded-3xl p-6 sm:p-8 shadow-xs border border-slate-200">
    <div class="flex items-center gap-3 border-b border-slate-100 pb-4 mb-6">
      <div class="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-lg">
        <i class="fa-solid fa-sliders"></i>
      </div>
      <div>
        <h2 class="font-extrabold text-base text-slate-900">Konfigurasi WhatsApp Gateway Engine</h2>
        <p class="text-xs text-slate-500">Atur parameter koneksi, token API provider, nomor sender, dan fitur otomatisasi.</p>
      </div>
    </div>

    <div class="mb-6 p-4 rounded-2xl bg-blue-50/70 border border-blue-200 text-blue-950 text-xs space-y-2">
      <div class="font-bold flex items-center gap-2 text-blue-900">
        <i class="fa-solid fa-circle-info text-blue-600"></i>
        <span>Panduan Menghubungkan WhatsApp Gateway Otomatis (Fonnte)</span>
      </div>
      <p class="text-blue-800 leading-relaxed text-[11px]">
        Agar pesan WhatsApp otomatis terkirim langsung ke nomor pelanggan tanpa perlu membuka WhatsApp Web satu per satu:
      </p>
      <ol class="list-decimal list-inside text-[11px] text-blue-900 space-y-1 font-medium">
        <li>Buka <a href="https://fonnte.com" target="_blank" class="underline font-bold text-blue-700">fonnte.com</a> dan buat akun (tersedia free trial).</li>
        <li>Pada dashboard Fonnte, tambahkan Device dengan nomor Anda (<b>08818679774</b>) dan scan QR Code di aplikasi WhatsApp HP Anda.</li>
        <li>Salin <b>API Token</b> dari Fonnte, tempelkan ke kolom <i>API Token</i> di bawah ini, lalu klik <b>Simpan Konfigurasi</b>.</li>
      </ol>
    </div>

    <form action="{{ route('admin.whatsapp.settings') }}" method="POST" class="space-y-5">
      @csrf

      <div>
        <label class="block text-xs font-bold text-slate-800 mb-1.5">Provider WhatsApp Engine</label>
        <select name="provider" class="w-full text-xs rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 p-3 bg-slate-50">
          <option value="Fonnte Official Gateway (fonnte.com)" {{ stripos($gatewayStatus['provider'], 'fonnte') !== false ? 'selected' : '' }}>Fonnte Official Gateway (fonnte.com) - Otomatis via API</option>
          <option value="Banterpool Local Gateway (Direct WA Web)" {{ stripos($gatewayStatus['provider'], 'local') !== false ? 'selected' : '' }}>Banterpool Local Gateway (Mode Direct WA Web / Manual)</option>
          <option value="Wablas Gateway API (wablas.com)" {{ stripos($gatewayStatus['provider'], 'wablas') !== false ? 'selected' : '' }}>Wablas Gateway API (wablas.com)</option>
          <option value="Baileys Self-Hosted Node.js Server" {{ stripos($gatewayStatus['provider'], 'baileys') !== false ? 'selected' : '' }}>Baileys Self-Hosted Node.js Server</option>
          <option value="Custom Webhook API (HTTP POST)" {{ stripos($gatewayStatus['provider'], 'custom') !== false ? 'selected' : '' }}>Custom Webhook API (HTTP POST)</option>
        </select>
        <p class="text-[11px] text-slate-400 mt-1">Pilih provider yang Anda gunakan untuk mengirimkan pesan WhatsApp.</p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-800 mb-1.5">Nomor Pengirim (Sender Bot)</label>
          <input type="text" name="phone_number" value="{{ $gatewayStatus['phone_number'] }}"
                 class="w-full text-xs font-mono rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 p-3 bg-slate-50">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-800 mb-1.5">Nama Perangkat Bot</label>
          <input type="text" name="device_name" value="{{ $gatewayStatus['device_name'] }}"
                 class="w-full text-xs rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 p-3 bg-slate-50">
        </div>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-800 mb-1.5">API Token / Secret Key</label>
        <input type="text" name="api_token" value="{{ cache('wa_gateway_api_token') }}"
               placeholder="Contoh: abcd1234efgh5678 (dari dashboard fonnte.com / wablas.com)"
               class="w-full text-xs font-mono rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 p-3 bg-slate-50">
        <p class="text-[11px] text-slate-400 mt-1">Kosongkan jika Anda ingin menggunakan pengiriman manual langsung melalui WhatsApp Web.</p>
      </div>

      <div class="pt-4 border-t border-slate-100 space-y-3">
        <label class="flex items-center gap-3 cursor-pointer">
          <input type="checkbox" checked class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4">
          <span class="text-xs font-bold text-slate-800">Aktifkan Pengingat Tagihan Otomatis H-3 Sebelum Jatuh Tempo</span>
        </label>
        <label class="flex items-center gap-3 cursor-pointer">
          <input type="checkbox" checked class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4">
          <span class="text-xs font-bold text-slate-800">Aktifkan Delay Acak Anti-Banned (1 - 3 detik antar pesan saat broadcast)</span>
        </label>
      </div>

      <div class="pt-4 border-t border-slate-100 flex justify-end">
        <button type="submit" class="px-6 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs transition">
          Simpan Konfigurasi
        </button>
      </div>
    </form>
  </div>

  <!-- ======================================================= -->
  <!-- MODAL: UJI COBA PESAN (TEST SEND)                      -->
  <!-- ======================================================= -->
  <div x-show="openTestModal" x-cloak
       class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
    <div @click.away="openTestModal = false"
         class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
      <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-xs">
            <i class="fa-solid fa-vial"></i>
          </div>
          <div>
            <h3 class="font-extrabold text-slate-900 text-sm">Kirim Pesan Uji Coba WhatsApp</h3>
            <p class="text-[11px] text-slate-500">Kirimkan sampel pesan ke nomor Anda sendiri.</p>
          </div>
        </div>
        <button type="button" @click="openTestModal = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
      </div>

      <form action="{{ route('admin.whatsapp.send-single') }}" method="POST" class="space-y-4">
        @csrf
        <div>
          <label class="block text-xs font-bold text-slate-800 mb-1">Nomor WhatsApp Tujuan</label>
          <input type="text" name="phone" value="08818679774" placeholder="Contoh: 08818679774" required
                 class="w-full text-xs font-mono rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 p-2.5">
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-800 mb-1">Nama Penerima</label>
          <input type="text" name="name" value="Admin Tester" required
                 class="w-full text-xs rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 p-2.5">
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-800 mb-1">Isi Pesan Uji Coba</label>
          <textarea name="message" rows="4" required
                    class="w-full text-xs font-mono rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 p-2.5"
                    x-model="messageText"></textarea>
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <button type="button" @click="openTestModal = false"
                  class="px-4 py-2 rounded-xl border border-slate-300 text-xs font-bold text-slate-600 hover:bg-slate-100">
            Batal
          </button>
          <button type="submit"
                  class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-xs">
            Kirim Tes Sekarang
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- ======================================================= -->
  <!-- MODAL: KIRIM PESAN KHUSUS PELANGGAN                    -->
  <!-- ======================================================= -->
  <div x-show="openSingleTargetModal" x-cloak
       class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
    <div @click.away="openSingleTargetModal = false"
         class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
      <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs">
            <i class="fa-brands fa-whatsapp"></i>
          </div>
          <div>
            <h3 class="font-extrabold text-slate-900 text-sm">Kirim Pesan WhatsApp Langsung</h3>
            <p class="text-[11px] text-slate-500">Pesan terkirim tercatat langsung di log sistem.</p>
          </div>
        </div>
        <button type="button" @click="openSingleTargetModal = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
      </div>

      <form action="{{ route('admin.whatsapp.send-single') }}" method="POST" class="space-y-4">
        @csrf
        <div>
          <label class="block text-xs font-bold text-slate-800 mb-1">Penerima</label>
          <input type="text" name="name" x-model="singleTargetName" readonly
                 class="w-full text-xs font-bold rounded-xl border-slate-200 bg-slate-50 p-2.5">
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-800 mb-1">Nomor WhatsApp</label>
          <input type="text" name="phone" x-model="singleTargetPhone" readonly
                 class="w-full text-xs font-mono font-bold text-emerald-700 rounded-xl border-slate-200 bg-slate-50 p-2.5">
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-800 mb-1">Isi Pesan</label>
          <textarea name="message" rows="4" required
                    class="w-full text-xs font-mono rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 p-2.5"
                    placeholder="Tuliskan pesan khusus untuk pelanggan ini..."></textarea>
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <button type="button" @click="openSingleTargetModal = false"
                  class="px-4 py-2 rounded-xl border border-slate-300 text-xs font-bold text-slate-600 hover:bg-slate-100">
            Batal
          </button>
          <button type="submit"
                  class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-xs">
            Kirim Pesan
          </button>
        </div>
      </form>
    </div>
  </div>

</div>

@push('scripts')
<script>
function whatsappGatewayApp() {
    return {
        activeTab: '{{ $activeTab ?? "broadcast" }}',
        targetFilter: 'all',
        selectedTemplateKey: 'maintenance',
        messageText: '',
        currentTime: '',
        openTestModal: false,
        openSingleTargetModal: false,
        singleTargetName: '',
        singleTargetPhone: '',
        isSending: false,

        templates: @json($templates),

        init() {
            // Set default template text
            if (this.templates['maintenance']) {
                this.messageText = this.templates['maintenance'].message;
            }

            // Update clock
            const now = new Date();
            this.currentTime = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
        },

        selectTemplate(key) {
            this.selectedTemplateKey = key;
            if (this.templates[key]) {
                this.messageText = this.templates[key].message;
            }
        },

        insertVariable(tag) {
            const textarea = document.getElementById('broadcast-message-area');
            if (!textarea) return;

            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = this.messageText;

            this.messageText = text.substring(0, start) + tag + text.substring(end);

            this.$nextTick(() => {
                textarea.focus();
                textarea.setSelectionRange(start + tag.length, start + tag.length);
            });
        },

        getPreviewText() {
            if (!this.messageText) return 'Pesan belum diisi...';

            return this.messageText
                .replace(/{nama}/g, 'Bpk. Ahmad Fauzi')
                .replace(/{tagihan}/g, 'Rp110.000')
                .replace(/{paket}/g, 'Paket 20 Mbps')
                .replace(/{jatuh_tempo}/g, '5 Juni 2026')
                .replace(/{alamat}/g, 'Jl. Raya Pernasidi No. 45, Cilongok')
                .replace(/{nomor_layanan}/g, 'BTP-CLK-001')
                .replace(/{link_bayar}/g, 'https://banterpool.net/tagihan');
        },

        getTargetSummary() {
            switch(this.targetFilter) {
                case 'all': return 'Seluruh Pelanggan (Semua)';
                case 'unpaid': return 'Pelanggan Belum Bayar';
                case 'odc-01': return 'Wilayah ODC-01 (Pernasidi)';
                case 'odc-02': return 'Wilayah ODC-02 (Panembangan)';
                case 'odc-03': return 'Wilayah ODC-03 (Jatisaba)';
                case 'manual': return 'Input Manual Nomor';
                default: return this.targetFilter;
            }
        },

        openSingleModal(phone, name) {
            this.singleTargetPhone = phone;
            this.singleTargetName = name;
            this.openSingleTargetModal = true;
        },

        onFormSubmit(e) {
            if (!confirm('Apakah Anda yakin ingin mengirimkan pesan broadcast WhatsApp ini ke ' + this.getTargetSummary() + '?')) {
                e.preventDefault();
                return;
            }
            this.isSending = true;
        }
    };
}
</script>
@endpush
@endsection
