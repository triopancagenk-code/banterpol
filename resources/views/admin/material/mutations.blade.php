@extends('layouts.admin')

@section('title', 'Admin NOC Banterpool - Barang Keluar & Masuk')
@section('page-title', 'Barang Keluar & Masuk')

@section('content')
<div class="space-y-6">

  <!-- Header -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <div class="flex items-center gap-2.5">
        <div class="w-9 h-9 rounded-xl bg-slate-800 text-white flex items-center justify-center text-lg shadow-xs">
          <i class="fa-solid fa-arrow-right-arrow-left"></i>
        </div>
        <h2 class="text-xl font-black text-slate-900 tracking-tight">Barang Keluar & Masuk</h2>
        <span class="bg-slate-100 text-slate-600 border border-slate-200 text-[10px] font-black px-2 py-0.5 rounded uppercase tracking-wider">
          POV {{ Auth::user() && Auth::user()->isDirektur() ? 'Direktur Utama' : 'Admin NOC' }}
        </span>
      </div>
      <p class="text-xs text-slate-500 mt-1">
        Log audit pergerakan barang keluar & masuk, penambahan restock manual, pemakaian lapangan, dan penyesuaian opname stok barang.
      </p>
    </div>

    <div class="flex items-center gap-2">
      <a href="{{ route('admin.material.index') }}"
         class="h-10 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition flex items-center gap-2">
        <i class="fa-solid fa-arrow-left text-xs"></i>
        <span>Kembali ke Stok Barang</span>
      </a>
    </div>
  </div>

  <!-- Filter Toolbar -->
  <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
    <form method="GET" action="{{ route('admin.material.mutations') }}" class="flex flex-wrap items-center gap-3">
      
      <!-- Filter Barang -->
      <div>
        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Filter Barang</label>
        <select name="material_id" onchange="this.form.submit()"
                class="h-9 text-xs rounded-xl border-slate-200 bg-slate-50 focus:border-brand focus:ring-brand font-medium">
          <option value="">Semua Barang</option>
          @foreach($materials as $mat)
            <option value="{{ $mat->id }}" {{ $materialId == $mat->id ? 'selected' : '' }}>
              {{ $mat->code }} - {{ $mat->name }}
            </option>
          @endforeach
        </select>
      </div>

      <!-- Filter Tipe Mutasi -->
      <div>
        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Tipe Mutasi</label>
        <select name="type" onchange="this.form.submit()"
                class="h-9 text-xs rounded-xl border-slate-200 bg-slate-50 focus:border-brand focus:ring-brand font-medium">
          <option value="all" {{ $type === 'all' ? 'selected' : '' }}>Semua Tipe</option>
          <option value="in" {{ $type === 'in' ? 'selected' : '' }}>Masuk / Tambah Stok</option>
          <option value="out" {{ $type === 'out' ? 'selected' : '' }}>Keluar / Kurang Stok</option>
          <option value="adjustment" {{ $type === 'adjustment' ? 'selected' : '' }}>Penyesuaian Opname</option>
        </select>
      </div>

      @if(!empty($materialId) || $type !== 'all')
        <div class="pt-5">
          <a href="{{ route('admin.material.mutations') }}"
             class="h-9 px-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold flex items-center gap-1.5 transition">
            <i class="fa-solid fa-rotate-left"></i>
            <span>Reset</span>
          </a>
        </div>
      @endif

    </form>
  </div>

  <!-- Tabel Riwayat Mutasi -->
  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse text-xs">
        <thead>
          <tr class="bg-slate-50/80 text-slate-500 font-bold border-b border-slate-200/80">
            <th class="py-3.5 px-4 w-12 text-center">No</th>
            <th class="py-3.5 px-4">Tanggal & Waktu</th>
            <th class="py-3.5 px-4">Barang / Perangkat</th>
            <th class="py-3.5 px-4 text-center">Jenis Mutasi</th>
            <th class="py-3.5 px-4 text-right">Jumlah Mutasi</th>
            <th class="py-3.5 px-4 text-center">Perubahan Stok</th>
            <th class="py-3.5 px-4">Keterangan / Alasan</th>
            <th class="py-3.5 px-4">Petugas / Operator</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
          @forelse($mutations as $index => $mut)
            <tr class="hover:bg-slate-50/70 transition">
              <td class="py-3.5 px-4 text-center text-slate-400">
                {{ $mutations->firstItem() + $index }}
              </td>

              <!-- Tanggal & Jam -->
              <td class="py-3.5 px-4">
                <span class="font-bold text-slate-900 block">{{ $mut->created_at->translatedFormat('d M Y') }}</span>
                <span class="text-[10px] font-mono text-slate-400">{{ $mut->created_at->format('H:i:s') }} WIB</span>
              </td>

              <!-- Material -->
              <td class="py-3.5 px-4">
                @if($mut->material)
                  <a href="{{ route('admin.material.index', ['q' => $mut->material->code]) }}"
                     class="font-extrabold text-slate-900 hover:text-brand transition block">
                    {{ $mut->material->name }}
                  </a>
                  <span class="font-mono text-[10px] text-slate-400 bg-slate-100 px-1 py-0.5 rounded border border-slate-200">
                    {{ $mut->material->code }}
                  </span>
                @else
                  <span class="text-slate-400 italic">Barang telah dihapus</span>
                @endif
              </td>

              <!-- Jenis Mutasi -->
              <td class="py-3.5 px-4 text-center">
                @if($mut->type === 'in')
                  <span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold px-2 py-0.5 rounded-full">
                    <i class="fa-solid fa-arrow-down text-[9px]"></i> Masuk / Tambah
                  </span>
                @elseif($mut->type === 'out')
                  <span class="inline-flex items-center gap-1 bg-rose-50 text-rose-700 border border-rose-200 text-[10px] font-bold px-2 py-0.5 rounded-full">
                    <i class="fa-solid fa-arrow-up text-[9px]"></i> Keluar / Kurang
                  </span>
                @else
                  <span class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-bold px-2 py-0.5 rounded-full">
                    <i class="fa-solid fa-sliders text-[9px]"></i> Penyesuaian Opname
                  </span>
                @endif
              </td>

              <!-- Jumlah -->
              <td class="py-3.5 px-4 text-right">
                <span class="font-black text-sm {{ $mut->type === 'in' ? 'text-emerald-600' : ($mut->type === 'out' ? 'text-rose-600' : 'text-blue-600') }}">
                  {{ $mut->type === 'in' ? '+' : ($mut->type === 'out' ? '-' : '') }}{{ number_format($mut->amount, 0, ',', '.') }}
                </span>
                <span class="text-[10px] text-slate-400 font-semibold">{{ $mut->material->unit ?? 'Unit' }}</span>
              </td>

              <!-- Perubahan Stok -->
              <td class="py-3.5 px-4 text-center font-mono text-slate-600 text-xs">
                <span>{{ $mut->stock_before }}</span>
                <span class="text-slate-400 mx-1">&rarr;</span>
                <strong class="text-slate-900">{{ $mut->stock_after }}</strong>
              </td>

              <!-- Keterangan -->
              <td class="py-3.5 px-4 text-slate-600 text-xs">
                {{ $mut->notes ?: '-' }}
              </td>

              <!-- Operator -->
              <td class="py-3.5 px-4 text-slate-600 text-xs">
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 rounded-full bg-slate-200 text-slate-700 flex items-center justify-center text-[10px] font-bold">
                    {{ strtoupper(substr($mut->user->name ?? 'OP', 0, 2)) }}
                  </div>
                  <div>
                    <span class="font-bold text-slate-800 text-[11px] block">{{ $mut->user->name ?? 'Sistem / Admin' }}</span>
                    <span class="text-[9px] text-slate-400 capitalize">{{ $mut->user->role ?? 'admin' }}</span>
                  </div>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="py-12 text-center text-slate-400">
                <i class="fa-solid fa-arrow-right-arrow-left text-2xl text-slate-300 block mb-1"></i>
                <p class="font-bold text-slate-700 text-sm">Belum Ada Riwayat Barang Keluar & Masuk</p>
                <p class="text-xs text-slate-400">Semua pergerakan barang keluar & masuk serta perubahan stok manual akan otomatis dicatat di sini.</p>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($mutations->hasPages())
      <div class="p-4 border-t border-slate-200/80 bg-slate-50/50">
        {{ $mutations->links() }}
      </div>
    @endif
  </div>

</div>
@endsection
