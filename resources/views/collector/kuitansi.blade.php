@extends('layouts.collector')

@section('title', 'Kuitansi Pembayaran Tunai - ' . $bill->receipt_number)

@section('content')
<div class="max-w-3xl mx-auto space-y-4">

  <!-- Top Action Bar (No Print) -->
  <div class="flex items-center justify-between no-print bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
    <a href="{{ route('kolektor.tagihan') }}" class="text-xs font-bold text-slate-600 hover:text-slate-900 flex items-center gap-1.5">
      <i class="fa-solid fa-arrow-left"></i>
      <span>Kembali ke Daftar Tagihan</span>
    </a>

    <div class="flex items-center gap-2">
      <!-- WhatsApp Send Receipt -->
      <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $bill->customer_phone) }}?text=Halo%20Bpk%2FIbu%20{{ urlencode($bill->customer_name) }}%2C%20berikut%20adalah%20tanda%20terima%20kuitansi%20pembayaran%20tunai%20WiFi%20Banterpool%20Anda%3A%0A%0A*No.%20Kuitansi%3A*%20{{ urlencode($bill->receipt_number) }}%0A*No.%20Invoice%3A*%20{{ urlencode($bill->bill_number) }}%0A*Paket%3A*%20{{ urlencode($bill->package_name) }}%0A*Periode%3A*%20{{ urlencode($bill->period) }}%0A*Nominal%20Tunai%3A*%20Rp%20{{ number_format($bill->total, 0, ',', '.') }}%0A*Status%3A*%20LUNAS%0A*Penerima%3A*%20{{ urlencode($bill->collected_by ?? 'Kolektor Banterpool') }}%0A%0ATerima%20kasih%20atas%20pembayaran%20Anda."
         target="_blank"
         class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 shadow-2xs">
        <i class="fa-brands fa-whatsapp text-sm"></i>
        <span>Kirim via WA</span>
      </a>

      <!-- Print Receipt -->
      <button type="button" onclick="window.print()"
              class="bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs px-4 py-2 rounded-xl transition flex items-center gap-1.5 shadow-2xs">
        <i class="fa-solid fa-print"></i>
        <span>Cetak Kuitansi</span>
      </button>
    </div>
  </div>

  <!-- ============================================== -->
  <!-- FORMAL DIGITAL RECEIPT (PRINTABLE)             -->
  <!-- ============================================== -->
  <div id="printable-area" class="bg-white rounded-3xl border border-slate-200 p-8 sm:p-10 shadow-sm relative overflow-hidden">
    
    <!-- Watermark LUNAS Background -->
    <div class="absolute inset-0 flex items-center justify-center opacity-5 pointer-events-none select-none">
      <span class="text-8xl font-black tracking-widest text-emerald-900 uppercase -rotate-12 border-8 border-emerald-900 p-8 rounded-3xl">LUNAS</span>
    </div>

    <div class="relative z-10">
      
      <!-- Receipt Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-6 border-b-2 border-slate-900 gap-4">
        <div>
          <div class="flex items-center gap-2">
            <span class="font-black text-2xl tracking-wider text-slate-900">BANTER<span class="text-red-600">POOL</span></span>
            <span class="bg-emerald-100 text-emerald-800 text-[10px] font-black px-2 py-0.5 rounded tracking-wider uppercase">FIBER BROADBAND</span>
          </div>
          <p class="text-xs text-slate-500 mt-1 font-medium">PT Banterpool Network Indonesia</p>
          <p class="text-[11px] text-slate-400">Jl. Raya Pernasidi, Kec. Cilongok, Kab. Banyumas • CS: 0812-3456-7890</p>
        </div>

        <div class="sm:text-right">
          <span class="inline-block bg-slate-900 text-white text-[11px] font-black px-3 py-1 rounded-lg uppercase tracking-wider mb-1">
            KUITANSI PEMBAYARAN TUNAI
          </span>
          <p class="font-mono font-black text-emerald-700 text-sm sm:text-base">{{ $bill->receipt_number ?? 'KWT-' . date('Ym') . '-001' }}</p>
          <p class="text-[11px] text-slate-500">Tgl: {{ $bill->paid_at ? $bill->paid_at->translatedFormat('d F Y, H:i') : now()->translatedFormat('d F Y') }} WIB</p>
        </div>
      </div>

      <!-- Customer & Bill Info Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 my-6 text-xs">
        <div class="space-y-1.5 bg-slate-50 p-4 rounded-2xl border border-slate-100">
          <p class="font-extrabold text-slate-400 uppercase text-[10px] tracking-wider mb-1">Telah Diterima Dari:</p>
          <p class="text-sm font-black text-slate-900">{{ $bill->customer_name }}</p>
          <p class="text-slate-600 flex items-center gap-1.5">
            <i class="fa-solid fa-phone text-slate-400"></i>
            <span>{{ $bill->customer_phone }}</span>
          </p>
          <p class="text-slate-600 flex items-start gap-1.5">
            <i class="fa-solid fa-location-dot text-red-500 mt-0.5 shrink-0"></i>
            <span>{{ $bill->address }}</span>
          </p>
        </div>

        <div class="space-y-1.5 bg-slate-50 p-4 rounded-2xl border border-slate-100">
          <p class="font-extrabold text-slate-400 uppercase text-[10px] tracking-wider mb-1">Keterangan Layanan:</p>
          <div class="flex justify-between">
            <span class="text-slate-500">No. Tagihan:</span>
            <span class="font-mono font-bold text-slate-900">{{ $bill->bill_number }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Paket Internet:</span>
            <span class="font-bold text-slate-900">{{ $bill->package_name }} ({{ $bill->speed ?? '20 Mbps' }})</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Periode Tagihan:</span>
            <span class="font-medium text-slate-800">{{ $bill->period }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Metode Bayar:</span>
            <span class="font-bold text-emerald-700 uppercase">{{ $bill->payment_method ?? 'Tunai (Kolektor)' }}</span>
          </div>
        </div>
      </div>

      <!-- Financial Breakdown Table -->
      <div class="border border-slate-200 rounded-2xl overflow-hidden mb-6">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-100 text-slate-700 font-bold uppercase text-[10px] tracking-wider border-b border-slate-200">
            <tr>
              <th class="p-3.5">Deskripsi Transaksi</th>
              <th class="p-3.5 text-right">Jumlah</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr>
              <td class="p-3.5 text-slate-800">
                <span class="font-bold block">Biaya Berlangganan Internet WiFi ({{ $bill->package_name }})</span>
                <span class="text-[11px] text-slate-500">Pemakaian periode {{ $bill->period }}</span>
              </td>
              <td class="p-3.5 text-right font-medium text-slate-900">
                Rp {{ number_format($bill->amount, 0, ',', '.') }}
              </td>
            </tr>
            @if(!empty($bill->tax) && $bill->tax > 0)
            <tr>
              <td class="p-3.5 text-slate-600">
                <span>PPN 11%</span>
              </td>
              <td class="p-3.5 text-right font-medium text-slate-900">
                Rp {{ number_format($bill->tax, 0, ',', '.') }}
              </td>
            </tr>
            @endif
            <tr class="bg-emerald-50/60 text-emerald-950 font-black text-sm border-t-2 border-slate-300">
              <td class="p-4">
                <span>TOTAL DITERIMA TUNAI</span>
              </td>
              <td class="p-4 text-right text-emerald-700 text-base">
                Rp {{ number_format($bill->total, 0, ',', '.') }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Terbilang Box -->
      <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 mb-8">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Terbilang:</p>
        <p class="text-xs font-bold text-slate-800 italic capitalize">
          # {{ number_format($bill->total, 0, ',', '.') }} Rupiah #
        </p>
        @if($bill->collector_notes)
          <p class="text-[11px] text-slate-500 mt-1 border-t border-slate-200/80 pt-1">
            <span class="font-semibold text-slate-700">Catatan:</span> {{ $bill->collector_notes }}
          </p>
        @endif
      </div>

      <!-- Footer Signatures & Official Stamp -->
      <div class="grid grid-cols-2 gap-4 items-end pt-4 border-t border-slate-200 text-xs">
        
        <!-- Pelanggan -->
        <div class="text-center">
          <p class="text-slate-500 text-[11px] mb-14">Pelanggan,</p>
          <p class="font-bold text-slate-900 underline">{{ $bill->customer_name }}</p>
          <p class="text-[10px] text-slate-400">Tanda Tangan Pelanggan</p>
        </div>

        <!-- Petugas Kolektor & Stempel Lunas -->
        <div class="text-center relative">
          
          <!-- Stempel Lunas -->
          <div class="absolute left-1/2 -top-4 -translate-x-1/2 border-2 border-emerald-600 text-emerald-600 rounded-xl px-4 py-1.5 rotate-[-8deg] opacity-85 select-none pointer-events-none">
            <p class="text-[10px] font-black tracking-widest uppercase">BANTERPOOL</p>
            <p class="text-sm font-black tracking-wider uppercase">LUNAS TUNAI</p>
            <p class="text-[8px] font-bold">{{ $bill->paid_at ? $bill->paid_at->format('d/m/Y') : date('d/m/Y') }}</p>
          </div>

          <p class="text-slate-500 text-[11px] mb-14">Petugas Kolektor Lapangan,</p>
          <p class="font-bold text-slate-900 underline">{{ $bill->collected_by ?? auth()->user()->name }}</p>
          <p class="text-[10px] text-slate-400">ID Kolektor: BTP-COL-{{ str_pad(auth()->id(), 3, '0', STR_PAD_LEFT) }}</p>
        </div>

      </div>

      <!-- Note -->
      <p class="text-[10px] text-center text-slate-400 mt-8">
        Kuitansi ini merupakan bukti pembayaran resmi yang sah diterbitkan oleh sistem penagihan lapangan Banterpool Network.
      </p>

    </div>

  </div>

</div>
@endsection
