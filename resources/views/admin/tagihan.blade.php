@extends('layouts.admin')

@section('title', 'Admin NOC Banterpool - Pengintaian Tagihan')
@section('page-title', 'Pengintaian & Manajemen Tagihan Pelanggan')

@section('content')
<div class="space-y-6"
     x-data="{
        openModal: false,
        selectedBill: null,
        openProofLightbox: false,
        proofUrl: '',
        
        viewBill(bill) {
            this.selectedBill = bill;
            this.openModal = true;
        },

        viewProof(url) {
            this.proofUrl = url;
            this.openProofLightbox = true;
        }
     }">

  <!-- ============================================== -->
  <!-- 1. SUMMARY BADGES & FILTER BAR                 -->
  <!-- ============================================== -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h2 class="text-xl font-black text-slate-900 tracking-tight">Daftar Tagihan Pelanggan</h2>
      <p class="text-xs text-slate-500 mt-0.5">Pantau status pembayaran invoice dan verifikasi transfer dari pelanggan.</p>
    </div>

    <!-- Export or Refresh Button -->
    <div class="flex items-center gap-2">
      <a href="{{ route('admin.tagihan.export', request()->query()) }}"
         class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 shadow-2xs">
        <i class="fa-solid fa-file-excel"></i>
        <span>Export Excel</span>
      </a>
      <button type="button" onclick="window.print()" class="bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-bold px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 shadow-2xs">
        <i class="fa-solid fa-print"></i> Cetak Laporan
      </button>
      <a href="{{ route('admin.tagihan') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold p-2 rounded-xl transition" title="Refresh">
        <i class="fa-solid fa-rotate-right"></i>
      </a>
    </div>
  </div>

  <!-- Filter Tabs Bar -->
  <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
    
    <!-- Status Pills -->
    <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
      <a href="{{ route('admin.tagihan', ['status' => 'all', 'q' => $search]) }}"
         class="px-3.5 py-1.5 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'all' ? 'bg-brand text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
        <span>Semua</span>
        <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $statusFilter === 'all' ? 'bg-white/20' : 'bg-slate-200' }}">{{ $counts['all'] }}</span>
      </a>

      <a href="{{ route('admin.tagihan', ['status' => 'Menunggu Verifikasi', 'q' => $search]) }}"
         class="px-3.5 py-1.5 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'Menunggu Verifikasi' ? 'bg-amber-500 text-white font-bold shadow-xs' : 'bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100' }}">
        <i class="fa-regular fa-clock"></i>
        <span>Menunggu Verifikasi</span>
        <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $statusFilter === 'Menunggu Verifikasi' ? 'bg-white/20' : 'bg-amber-200' }}">{{ $counts['menunggu'] }}</span>
      </a>

      <a href="{{ route('admin.tagihan', ['status' => 'Belum Bayar', 'q' => $search]) }}"
         class="px-3.5 py-1.5 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'Belum Bayar' ? 'bg-slate-800 text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
        <span>Belum Bayar</span>
        <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $statusFilter === 'Belum Bayar' ? 'bg-white/20' : 'bg-slate-200' }}">{{ $counts['belum_bayar'] }}</span>
      </a>

      <a href="{{ route('admin.tagihan', ['status' => 'Lunas', 'q' => $search]) }}"
         class="px-3.5 py-1.5 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'Lunas' ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100' }}">
        <i class="fa-solid fa-check"></i>
        <span>Lunas</span>
        <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $statusFilter === 'Lunas' ? 'bg-white/20' : 'bg-emerald-200' }}">{{ $counts['lunas'] }}</span>
      </a>

      <a href="{{ route('admin.tagihan', ['status' => 'Jatuh Tempo', 'q' => $search]) }}"
         class="px-3.5 py-1.5 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'Jatuh Tempo' ? 'bg-red-600 text-white font-bold shadow-xs' : 'bg-red-50 text-red-800 border border-red-200 hover:bg-red-100' }}">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <span>Jatuh Tempo</span>
        <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $statusFilter === 'Jatuh Tempo' ? 'bg-white/20' : 'bg-red-200' }}">{{ $counts['jatuh_tempo'] }}</span>
      </a>
    </div>

    <!-- Search Form -->
    <form method="GET" action="{{ route('admin.tagihan') }}" class="flex items-center gap-2">
      <input type="hidden" name="status" value="{{ $statusFilter }}">
      <div class="relative w-full sm:w-64">
        <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400 text-xs"></i>
        <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama, invoice, no hp..."
               class="w-full pl-9 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand">
      </div>
      <button type="submit" class="bg-brand text-white px-3 py-1.5 rounded-xl text-xs font-bold hover:bg-brand-700 transition">
        Cari
      </button>
      @if($search)
        <a href="{{ route('admin.tagihan', ['status' => $statusFilter]) }}" class="text-xs text-slate-400 hover:text-red-500 font-bold">Reset</a>
      @endif
    </form>

  </div>

  <!-- ============================================== -->
  <!-- 2. TABEL PENGINTAIAN TAGIHAN                   -->
  <!-- ============================================== -->
  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50 text-slate-500 uppercase font-bold text-[10px] tracking-wider border-b border-slate-200">
          <tr>
            <th class="px-5 py-3.5">Invoice & Tanggal</th>
            <th class="px-4 py-3.5">Pelanggan</th>
            <th class="px-4 py-3.5">Paket & ODP</th>
            <th class="px-4 py-3.5">Jatuh Tempo</th>
            <th class="px-4 py-3.5">Total Tagihan</th>
            <th class="px-4 py-3.5">Metode Bayar</th>
            <th class="px-4 py-3.5">Status</th>
            <th class="px-5 py-3.5 text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 font-medium">
          @forelse($bills as $bill)
            <tr class="hover:bg-slate-50/70 transition">
              <!-- Invoice & Tanggal -->
              <td class="px-5 py-4">
                <p class="font-bold text-slate-900 font-mono leading-tight">{{ $bill['id'] }}</p>
                <p class="text-[10px] text-slate-400 mt-0.5">{{ $bill['created_at'] }}</p>
              </td>

              <!-- Pelanggan -->
              <td class="px-4 py-4">
                <p class="font-bold text-slate-900">{{ $bill['customer_name'] }}</p>
                <div class="flex items-center gap-1.5 text-[11px] text-slate-500 mt-0.5">
                  <i class="fa-brands fa-whatsapp text-emerald-600"></i>
                  <span>{{ $bill['customer_phone'] }}</span>
                </div>
              </td>

              <!-- Paket & ODP -->
              <td class="px-4 py-4">
                <span class="font-semibold text-slate-800">{{ $bill['package_name'] }}</span>
                <p class="text-[10px] text-slate-400 mt-0.5">
                  <i class="fa-solid fa-network-wired text-brand text-[9px]"></i> {{ $bill['odp'] }}
                </p>
              </td>

              <!-- Jatuh Tempo -->
              <td class="px-4 py-4 text-slate-600">
                {{ $bill['due_date'] }}
              </td>

              <!-- Total Tagihan -->
              <td class="px-4 py-4 font-black text-slate-900 text-sm">
                Rp{{ $bill['total'] }}
              </td>

              <!-- Metode Bayar & Bukti -->
              <td class="px-4 py-4">
                <p class="text-slate-700 font-medium">{{ $bill['payment_method'] }}</p>
                @if($bill['proof_image'])
                  <button type="button" @click="viewProof('{{ $bill['proof_image'] }}')"
                          class="inline-flex items-center gap-1 text-[10px] text-blue-600 hover:underline font-bold mt-1">
                    <i class="fa-regular fa-image"></i> Lihat Bukti
                  </button>
                @endif
              </td>

              <!-- Status -->
              <td class="px-4 py-4">
                @if($bill['status'] === 'Lunas')
                  <span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold px-2.5 py-1 rounded-md">
                    <i class="fa-solid fa-check text-[9px]"></i> Lunas
                  </span>
                @elseif($bill['status'] === 'Menunggu Verifikasi')
                  <span class="inline-flex items-center gap-1 bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-bold px-2.5 py-1 rounded-md animate-pulse">
                    <i class="fa-regular fa-clock text-[9px]"></i> Menunggu Verifikasi
                  </span>
                @elseif($bill['status'] === 'Jatuh Tempo')
                  <span class="inline-flex items-center gap-1 bg-red-50 text-red-700 border border-red-200 text-[10px] font-bold px-2.5 py-1 rounded-md">
                    <i class="fa-solid fa-triangle-exclamation text-[9px]"></i> Jatuh Tempo
                  </span>
                @else
                  <span class="inline-flex items-center gap-1 bg-slate-100 text-slate-600 border border-slate-200 text-[10px] font-bold px-2.5 py-1 rounded-md">
                    Belum Bayar
                  </span>
                @endif
              </td>

              <!-- Aksi -->
              <td class="px-5 py-4 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button type="button" @click="viewBill(@js($bill))"
                          class="bg-brand/10 hover:bg-brand text-brand hover:text-white font-bold text-xs px-3 py-1.5 rounded-lg transition flex items-center gap-1">
                    <i class="fa-regular fa-eye"></i> Detail
                  </button>

                  <a href="https://wa.me/{{ preg_replace('/^0/', '62', $bill['customer_phone']) }}?text=Halo%20Bapak/Ibu%20{{ urlencode($bill['customer_name']) }},%20kami%20dari%20Banterpool%20mengingatkan%20tagihan%20internet%20{{ $bill['id'] }}%20sebesar%20Rp{{ $bill['total'] }}%20jatuh%20tempo%20pada%20{{ urlencode($bill['due_date']) }}.%20Terima%20kasih."
                     target="_blank" title="Kirim Pengingat WhatsApp"
                     class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white flex items-center justify-center transition">
                    <i class="fa-brands fa-whatsapp text-sm"></i>
                  </a>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center py-12 text-slate-400">
                <i class="fa-solid fa-receipt text-3xl mb-2 text-slate-300"></i>
                <p>Tidak ada tagihan yang cocok dengan filter atau pencarian.</p>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <!-- ============================================== -->
  <!-- 3. MODAL DETAIL & VERIFIKASI TAGIHAN           -->
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
         x-show="selectedBill">

      <!-- Header Modal -->
      <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <div>
          <span class="text-[10px] font-mono font-bold text-slate-400 uppercase">Rincian Tagihan</span>
          <h3 class="text-base font-black text-slate-900" x-text="selectedBill?.id"></h3>
        </div>
        <button type="button" @click="openModal = false" class="text-slate-400 hover:text-black text-xl">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <!-- Content Rincian -->
      <div class="space-y-4 text-xs">
        
        <!-- Info Pelanggan -->
        <div class="bg-slate-50 rounded-xl p-4 space-y-2 border border-slate-200/80">
          <div class="flex justify-between">
            <span class="text-slate-500 font-medium">Nama Pelanggan:</span>
            <span class="font-bold text-slate-900" x-text="selectedBill?.customer_name"></span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500 font-medium">Nomor Telepon:</span>
            <span class="font-bold text-slate-900" x-text="selectedBill?.customer_phone"></span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500 font-medium">Alamat Pemasangan:</span>
            <span class="font-medium text-slate-800 text-right max-w-xs" x-text="selectedBill?.address"></span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500 font-medium">Titik ODP:</span>
            <span class="font-bold text-brand" x-text="selectedBill?.odp"></span>
          </div>
        </div>

        <!-- Rincian Biaya -->
        <div class="space-y-2 border border-slate-200/80 rounded-xl p-4">
          <div class="flex justify-between text-slate-600">
            <span>Paket Layanan</span>
            <span class="font-bold text-slate-900" x-text="selectedBill?.package_name"></span>
          </div>
          <div class="flex justify-between text-slate-600">
            <span>Periode Berlangganan</span>
            <span class="text-slate-800 font-medium" x-text="selectedBill?.period"></span>
          </div>
          <div class="flex justify-between text-slate-600">
            <span>Metode Pembayaran</span>
            <span class="font-bold text-slate-900" x-text="selectedBill?.payment_method"></span>
          </div>
          <div class="border-t border-slate-100 pt-2 flex justify-between font-black text-sm text-slate-900">
            <span>Total Tagihan:</span>
            <span class="text-brand text-base" x-text="'Rp' + selectedBill?.total"></span>
          </div>
        </div>

        <!-- Bukti Pembayaran Box jika ada -->
        <template x-if="selectedBill?.proof_image">
          <div class="border border-amber-200 bg-amber-50/50 rounded-xl p-3 space-y-2">
            <div class="flex items-center justify-between">
              <span class="font-bold text-amber-900 text-xs">Bukti Transfer Pelanggan</span>
              <button type="button" @click="viewProof(selectedBill.proof_image)" class="text-brand hover:underline font-bold text-[11px]">
                Perbesar Gambar &rarr;
              </button>
            </div>
            <img :src="selectedBill.proof_image" alt="Bukti Transfer" class="w-full h-36 object-cover rounded-lg border border-amber-200">
          </div>
        </template>

      </div>

      <!-- Action Buttons -->
      <div class="pt-2 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
        <form :action="'/admin/tagihan/' + selectedBill?.id + '/status'" method="POST" class="w-full flex items-center gap-2">
          @csrf
          <input type="hidden" name="status" value="Lunas">
          <button type="submit"
                  class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs py-2.5 px-4 rounded-xl transition flex items-center justify-center gap-1.5 shadow-sm">
            <i class="fa-solid fa-check"></i> Verifikasi & Tandai Lunas
          </button>
        </form>

        <button type="button" @click="openModal = false"
                class="w-full sm:w-auto border border-slate-300 text-slate-700 font-bold text-xs py-2.5 px-4 rounded-xl hover:bg-slate-50 transition">
          Tutup
        </button>
      </div>

    </div>

  </div>

  <!-- LIGHTBOX BUKTI BAYAR -->
  <div x-show="openProofLightbox" style="display: none;"
       @click.away="openProofLightbox = false"
       class="fixed inset-0 z-60 overflow-y-auto flex items-center justify-center p-4 bg-black/85 backdrop-blur-sm">
    <div class="relative max-w-2xl w-full">
      <button type="button" @click="openProofLightbox = false" class="absolute -top-10 right-0 text-white text-2xl font-bold">
        <i class="fa-solid fa-xmark"></i>
      </button>
      <img :src="proofUrl" alt="Bukti Transfer" class="w-full max-h-[85vh] object-contain rounded-2xl shadow-2xl">
    </div>
  </div>

</div>
@endsection
