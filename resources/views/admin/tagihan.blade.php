@extends('layouts.admin')

@section('title', 'Admin NOC Banterpool - Pengintaian Tagihan')
@section('page-title', 'Pengintaian & Manajemen Tagihan Pelanggan')

@section('content')
<script>
function tagihanManager() {
    return {
        openModal: false,
        openCreateModal: false,
        openPayModal: false,
        openBroadcastBuktiModal: false,
        openImportModal: false,
        openWaFormatModal: false,
        payingBill: null,
        selectedBill: null,
        openProofLightbox: false,
        proofUrl: '',
        selectedBillIds: [],
        selectedBuktiCustomerIds: [],
        billsList: @js($bills),
        paidBillsList: @js(collect($bills)->where('status', 'Lunas')->values()),
        showDoneModal: true,

        isAllSelected() {
            return this.billsList.length > 0 && this.selectedBillIds.length === this.billsList.length;
        },

        toggleSelectAll(e) {
            if (e.target.checked) {
                this.selectedBillIds = this.billsList.map(b => b.id);
            } else {
                this.selectedBillIds = [];
            }
        },

        getSelectedCustomers() {
            return this.billsList.filter(b => this.selectedBillIds.includes(b.id));
        },

        selectAllPaidCustomers() {
            this.selectedBuktiCustomerIds = this.paidBillsList.map(b => b.id);
        },

        clearSelectedPaidCustomers() {
            this.selectedBuktiCustomerIds = [];
        },

        openQuickPay(bill) {
            this.payingBill = bill;
            this.openPayModal = true;
        },

        handleToolbarBroadcast() {
            if (this.selectedBillIds.length === 0) {
                alert('Silakan centang minimal 1 pelanggan pada tabel di bawah terlebih dahulu, atau klik tombol "Broadcast Belum Bayar" pada kartu Kirim Tagihan di atas.');
                return;
            }
            const selected = this.getSelectedCustomers();
            const allPaid = selected.every(b => b.status === 'Lunas');
            if (allPaid) {
                this.selectedBuktiCustomerIds = [...this.selectedBillIds];
                this.openBroadcastBuktiModal = true;
            } else {
                if (confirm('Kirim pesan tagihan WhatsApp resmi ke ' + this.selectedBillIds.length + ' pelanggan terpilih?')) {
                    const form = document.getElementById('floatingBroadcastForm');
                    if (form) {
                        form.submit();
                    }
                }
            }
        },

        openBroadcastInGateway() {
            const selected = this.getSelectedCustomers();
            const phones = selected.map(b => b.customer_phone).filter(p => !!p).join(',');
            const tpl = (selected.some(b => b.status === 'Lunas')) ? 'receipt' : 'billing';
            window.location.href = '{{ route("admin.whatsapp") }}?tab=broadcast&target=manual&template=' + tpl + '&numbers=' + encodeURIComponent(phones);
        },

        // Form Input Tagihan Manual
        billForm: {
            mode: 'registered',
            orderId: '',
            customerName: '',
            customerPhone: '',
            customerEmail: '',
            address: '',
            packageName: 'Paket 20 Mbps',
            speed: '20 Mbps',
            period: '01 ' + new Date().toLocaleDateString('id-ID', { month: 'short', year: 'numeric' }) + ' – ' + new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }),
            dueDate: '20 ' + new Date().toLocaleDateString('id-ID', { month: 'short', year: 'numeric' }),
            billDate: '05 ' + new Date().toLocaleDateString('id-ID', { month: 'short', year: 'numeric' }),
            amount: 110000,
            tax: 0,
            status: 'Belum Bayar',
            paymentMethod: 'Transfer Bank (BCA)',
            collectorNotes: '',
            total() {
                return Number(this.amount || 0) + Number(this.tax || 0);
            }
        },

        onSelectCustomer(e) {
            const opt = e.target.selectedOptions ? e.target.selectedOptions[0] : null;
            if (!opt || !opt.value) {
                this.billForm.orderId = '';
                return;
            }
            this.billForm.orderId = opt.value;
            this.billForm.customerName = opt.getAttribute('data-name') || '';
            this.billForm.customerPhone = opt.getAttribute('data-phone') || '';
            this.billForm.customerEmail = opt.getAttribute('data-email') || '';
            this.billForm.address = opt.getAttribute('data-address') || '';
            this.billForm.packageName = opt.getAttribute('data-package') || 'Paket 20 Mbps';
            this.billForm.speed = opt.getAttribute('data-speed') || '20 Mbps';
            this.billForm.amount = Number(opt.getAttribute('data-price') || 110000);
        },

        onPackageSelect(e) {
            const val = e.target.value;
            this.billForm.packageName = val;
            if (val.includes('50')) {
                this.billForm.speed = '50 Mbps';
                this.billForm.amount = 220000;
            } else if (val.includes('30')) {
                this.billForm.speed = '30 Mbps';
                this.billForm.amount = 165000;
            } else {
                this.billForm.speed = '20 Mbps';
                this.billForm.amount = 110000;
            }
        },

        viewBill(bill) {
            this.selectedBill = bill;
            this.openModal = true;
        },

        viewProof(url) {
            this.proofUrl = url;
            this.openProofLightbox = true;
        },

        // Template Tagihan Belum Terbayar
        getWaBillingReminderUrl(bill) {
            if (!bill) return '#';
            const rawPhone = bill.customer_phone ? String(bill.customer_phone).replace(/[^0-9]/g, '') : '';
            const phone = rawPhone.startsWith('0') ? '62' + rawPhone.slice(1) : (rawPhone.startsWith('62') ? rawPhone : '62' + rawPhone);
            const displayPhone = rawPhone.startsWith('62') ? '0' + rawPhone.slice(2) : (rawPhone.startsWith('0') ? rawPhone : '0' + rawPhone);
            const customerName = bill.customer_name || 'Pelanggan';
            const packageName = bill.package_name || 'Paket 20 Mbps';
            const rawPrice = bill.total ? (String(bill.total).includes('Rp') ? bill.total : 'Rp ' + bill.total) : 'Rp 110.000';

            const now = new Date();
            const mNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            const currentMonth = mNames[now.getMonth()] + ' ' + now.getFullYear();
            const dueMonth = mNames[now.getMonth()];
            const year = now.getFullYear();

            const message = `Yth. Bpk/Ibu ${customerName}\n` +
`${displayPhone}\n\n` +
`PEMBERITAHUAN\n` +
`Tagihan Bulan : ${currentMonth}\n` +
`Jenis Paket : ${packageName}\n` +
`Biaya Paket : ${rawPrice}\n` +
`Besar Tagihan : ${rawPrice}\n\n` +
`Masa aktif s/d 20 ${dueMonth} ${year}\n\n` +
`Nomor Virtual Acount : 1691101${displayPhone}\n\n` +
`Pastikan untuk membayar menggunakan Virtual Acount seperti BRIVA, DANA dll\n\n` +
`Ket : BELUM TERBAYAR\n\n` +
`INFO TAMBAHAN\n` +
`Untuk menghindari isolir otomatis, sikahkan lalukan pembayaran sebelum tanggal jatuh tempo, dan pastikan mengirim bukti transaksi melalui nomor ini. \n\n` +
`Terimakasih`;

            return 'https://wa.me/' + phone + '?text=' + encodeURIComponent(message);
        },

        // Template Bukti Lunas & Nota Elektronik
        getWaBuktiLunasUrl(bill) {
            if (!bill) return '#';
            const rawPhone = bill.customer_phone ? String(bill.customer_phone).replace(/[^0-9]/g, '') : '';
            const phone = rawPhone.startsWith('0') ? '62' + rawPhone.slice(1) : (rawPhone.startsWith('62') ? rawPhone : '62' + rawPhone);
            const displayPhone = rawPhone.startsWith('62') ? '0' + rawPhone.slice(2) : (rawPhone.startsWith('0') ? rawPhone : '0' + rawPhone);
            const customerName = bill.customer_name || 'Pelanggan';
            const rawPrice = bill.total ? (String(bill.total).includes('Rp') ? bill.total : 'Rp. ' + bill.total) : 'Rp. 110.000';

            const origin = window.location.origin;
            const strukLink = origin + '/struk/' + encodeURIComponent(bill.id);

            let dateStr = '05 Nov 2026';
            let timeStr = '17:10 WIB';
            if (bill.paid_at_formatted && bill.paid_at_formatted !== '-') {
                const parts = bill.paid_at_formatted.split(', ');
                if (parts.length >= 2) {
                    dateStr = parts[0];
                    timeStr = parts[1];
                } else {
                    dateStr = bill.paid_at_formatted;
                }
            } else {
                const now = new Date();
                const mNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                dateStr = String(now.getDate()).padStart(2, '0') + ' ' + mNames[now.getMonth()] + ' ' + now.getFullYear();
                timeStr = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0') + ' WIB';
            }

            let monthTagihan = 'Nov 2026';
            if (bill.month_key) {
                const mParts = String(bill.month_key).split('-');
                if (mParts.length === 2) {
                    const mIdx = parseInt(mParts[1], 10) - 1;
                    const mNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                    monthTagihan = (mNames[mIdx] || 'Nov') + ' ' + mParts[0];
                }
            } else if (bill.month_label) {
                const mShortMap = {
                    'januari': 'Jan', 'februari': 'Feb', 'maret': 'Mar', 'april': 'Apr',
                    'mei': 'Mei', 'juni': 'Jun', 'juli': 'Jul', 'agustus': 'Agu',
                    'september': 'Sep', 'oktober': 'Okt', 'november': 'Nov', 'desember': 'Des'
                };
                monthTagihan = bill.month_label.replace(/[a-zA-Z]+/g, m => mShortMap[m.toLowerCase()] || m);
            }

            const area = bill.wilayah || bill.address || 'Wangon RT 05/01';
            const packageName = bill.package_name || 'Paket 20 Mbps';
            const adminBy = bill.collected_by || 'Atun';

            const message = `*BANTERPOOL* \n\n` +
`NOTA ELEKTRONIK\n` +
`${strukLink}\n\n` +
`Yth. Bapak / Ibu\n` +
`${customerName}\n` +
`${displayPhone}\n` +
`null\n` +
`Area : ${area}\n\n` +
`*PEMBAYARAN*\n` +
`Tanggal Bayar :${dateStr} | ${timeStr}\n\n` +
`Tagihan Bulan : \n` +
`- ${monthTagihan}\n` +
`Jenis Paket : ${packageName}\n` +
`Harga Paket : ${rawPrice}\n` +
`*Sudah Termasuk Pajak\n` +
`Diskon  : Rp. 0\n` +
`Total Biaya : ${rawPrice}\n` +
`Ket : *LUNAS* \n` +
`Admin By : ${adminBy}\n\n` +
`terimakasih telah menggunakan layanan internet dari BANTERPOOL\n\n` +
`SUPPORT BY :\n` +
`Admin BANTERPOOL`;

            return 'https://wa.me/' + phone + '?text=' + encodeURIComponent(message);
        },

        getWaUrl(bill) {
            if (!bill) return '#';
            if (bill.status === 'Lunas') {
                return this.getWaBuktiLunasUrl(bill);
            }
            return this.getWaBillingReminderUrl(bill);
        }
    };
}
</script>

<div class="space-y-6" x-data="tagihanManager()">

  <!-- ============================================== -->
  <!-- 1. HEADER BAR & ACTION SHORTCUTS               -->
  <!-- ============================================== -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
    <!-- Left: Title & Subtitle -->
    <div class="flex items-center gap-3">
      <div class="w-11 h-11 rounded-2xl bg-brand/10 text-brand border border-brand/20 flex items-center justify-center text-xl shrink-0 shadow-xs">
        <i class="fa-solid fa-wifi"></i>
      </div>
      <div>
        <div class="flex items-center gap-2">
          <h2 class="text-xl font-black text-slate-900 tracking-tight">
            BANTERPOOL Billing
          </h2>
          <span class="bg-brand text-white text-[10px] font-black px-2 py-0.5 rounded-md uppercase tracking-wider">
            NOC Panel
          </span>
        </div>
        <p class="text-xs text-slate-500 font-medium mt-0.5">
          RT/RW NET & ROUTEROS API &bull; Monitoring Tagihan & Keuangan Pelanggan
        </p>
      </div>
    </div>

    <!-- Right: Router Indicator & Quick Navigation Buttons -->
    <div class="flex flex-wrap items-center gap-2">
      <!-- Router Status Pill -->
      <a href="{{ Route::has('admin.odc-map') ? route('admin.odc-map') : url('/admin/odc-map') }}" title="Lihat Peta MikroTik Live & Status Winbox"
         class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 text-xs font-bold hover:bg-emerald-100 transition shadow-2xs">
        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
        <span>{{ $routerInfo['model'] ?? 'RB750Gr3' }} ({{ $routerInfo['host'] ?? '172.14.10.3' }})</span>
        <i class="fa-solid fa-gear text-slate-400 hover:text-slate-600 text-xs"></i>
      </a>

      <!-- Tombol Pelanggan -->
      <a href="{{ route('admin.pelanggan') }}"
         class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-xs">
        <i class="fa-solid fa-user-group text-xs"></i>
        <span>Pelanggan</span>
      </a>

      <!-- Tombol Format WA -->
      <button type="button" @click="openWaFormatModal = true"
              class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-xs font-bold transition shadow-2xs">
        <i class="fa-brands fa-whatsapp text-emerald-600 text-sm"></i>
        <span>Format WA</span>
      </button>

      <a href="{{ route('admin.tagihan', ['status' => $statusFilter]) }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold p-2 rounded-xl transition" title="Refresh Halaman">
        <i class="fa-solid fa-rotate-right"></i>
      </a>
    </div>
  </div>

  <!-- Alert Notifications -->
  @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl flex items-center justify-between shadow-xs">
      <div class="flex items-center gap-2">
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
      <div class="flex items-center gap-2">
        <i class="fa-solid fa-circle-exclamation text-red-600 text-base"></i>
        <span class="text-xs font-bold">{{ session('error') }}</span>
      </div>
      <button type="button" @click="$el.parentElement.remove()" class="text-red-500 hover:text-red-700 text-sm">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
  @endif

  @if(isset($errors) && $errors->any())
    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-2xl shadow-xs">
      <div class="flex items-center gap-2 mb-1">
        <i class="fa-solid fa-triangle-exclamation text-red-600"></i>
        <span class="text-xs font-bold">Terjadi kesalahan input tagihan:</span>
      </div>
      <ul class="list-disc list-inside text-xs text-red-700 pl-4 space-y-0.5">
        @foreach($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <!-- ============================================== -->
  <!-- 2. 4 STATISTIC CARDS (POTENSI OMSET, DLL)      -->
  <!-- ============================================== -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <!-- Card 1: POTENSI OMSET -->
    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
      <div>
        <p class="text-[11px] font-black tracking-wider text-slate-400 uppercase">POTENSI OMSET</p>
        <h3 class="text-2xl font-black text-slate-900 mt-1 font-mono">
          Rp {{ number_format($potensiOmset ?? 0, 0, ',', '.') }}
        </h3>
        <p class="text-xs text-slate-500 font-medium mt-1">
          <span class="font-bold text-slate-800">{{ $totalPelangganAktif ?? 0 }}</span> Pelanggan Aktif
        </p>
      </div>
      <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-xl shrink-0 shadow-2xs">
        <i class="fa-solid fa-wallet"></i>
      </div>
    </div>

    <!-- Card 2: SUDAH TERBAYAR -->
    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
      <div>
        <p class="text-[11px] font-black tracking-wider text-emerald-700 uppercase">SUDAH TERBAYAR</p>
        <h3 class="text-2xl font-black text-emerald-600 mt-1 font-mono">
          Rp {{ number_format($sudahTerbayarNominal ?? 0, 0, ',', '.') }}
        </h3>
        <p class="text-xs text-slate-500 font-medium mt-1">
          <span class="font-bold text-emerald-700">{{ $sudahTerbayarCount ?? 0 }}</span> Pelanggan Lunas
        </p>
      </div>
      <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xl shrink-0 shadow-2xs">
        <i class="fa-solid fa-circle-check"></i>
      </div>
    </div>

    <!-- Card 3: BELUM TERBAYAR -->
    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
      <div>
        <p class="text-[11px] font-black tracking-wider text-rose-700 uppercase">BELUM TERBAYAR</p>
        <h3 class="text-2xl font-black text-brand mt-1 font-mono">
          Rp {{ number_format($belumTerbayarNominal ?? 0, 0, ',', '.') }}
        </h3>
        <p class="text-xs text-slate-500 font-medium mt-1">
          <span class="font-bold text-brand">{{ $belumTerbayarCount ?? 0 }}</span> Pelanggan
        </p>
      </div>
      <div class="w-12 h-12 rounded-2xl bg-red-50 text-brand border border-red-100 flex items-center justify-center text-xl shrink-0 shadow-2xs">
        <i class="fa-solid fa-clock-rotate-left"></i>
      </div>
    </div>

    <!-- Card 4: KIRIM TAGIHAN -->
    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex flex-col justify-between gap-3">
      <div class="flex items-center justify-between">
        <p class="text-[11px] font-black tracking-wider text-slate-400 uppercase">KIRIM TAGIHAN</p>
        <span class="bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-extrabold px-2 py-0.5 rounded-full">
          Ready
        </span>
      </div>
      <form action="{{ route('admin.tagihan.broadcast-wa') }}" method="POST"
            onsubmit="return confirm('Kirim pengingat tagihan WhatsApp otomatis ke seluruh pelanggan yang BELUM BAYAR ({{ $belumTerbayarCount ?? 0 }} pelanggan)?')">
        @csrf
        <input type="hidden" name="broadcast_type" value="billing">
        <input type="hidden" name="target_filter" value="unpaid">
        <button type="submit"
                class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs py-2.5 px-3 rounded-xl transition flex items-center justify-center gap-2 shadow-xs">
          <i class="fa-brands fa-whatsapp text-base"></i>
          <span>Broadcast Belum Bayar</span>
        </button>
      </form>
    </div>
  </div>

  <!-- ============================================== -->
  <!-- 3. TOOLBAR PENCARIAN, FILTER WILAYAH & AKSI   -->
  <!-- ============================================== -->
  <div class="bg-white rounded-2xl p-3 sm:p-4 border border-slate-200/80 shadow-xs">
    <form method="GET" action="{{ route('admin.tagihan') }}" id="tagihanFilterForm" class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
      @if(!empty($monthFilter) && $monthFilter !== 'all')
        <input type="hidden" name="month" value="{{ $monthFilter }}">
      @endif

      <!-- Kiri: Input Search & Filter Dropdowns -->
      <div class="flex flex-1 flex-wrap items-center gap-2.5 min-w-0">
        <!-- Input Search -->
        <div class="relative flex-1 min-w-[220px]">
          <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400 text-xs"></i>
          <input type="text" name="q" value="{{ $search }}"
                 placeholder="Cari nama, user PPPoE, No. WA, wilayah..."
                 class="h-9 w-full pl-9 pr-3 text-xs bg-slate-50 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand font-medium text-slate-800">
        </div>

        <!-- Dropdown: Semua Wilayah -->
        <div class="shrink-0">
          <select name="wilayah" onchange="document.getElementById('tagihanFilterForm').submit()"
                  class="h-9 text-xs font-bold bg-slate-50 border border-slate-300 rounded-xl px-3 text-slate-700 focus:ring-brand focus:border-brand cursor-pointer">
            <option value="all">Semua Wilayah</option>
            @foreach($availableWilayah ?? [] as $w)
              <option value="{{ $w }}" {{ ($wilayahFilter ?? 'all') === $w ? 'selected' : '' }}>
                {{ $w }}
              </option>
            @endforeach
          </select>
        </div>

        <!-- Dropdown: Semua Status -->
        <div class="shrink-0">
          <select name="status" onchange="document.getElementById('tagihanFilterForm').submit()"
                  class="h-9 text-xs font-bold bg-slate-50 border border-slate-300 rounded-xl px-3 text-slate-700 focus:ring-brand focus:border-brand cursor-pointer">
            <option value="all" {{ ($statusFilter ?? 'all') === 'all' ? 'selected' : '' }}>Semua Status Aktif</option>
            <option value="semua_termasuk_lunas" {{ ($statusFilter ?? '') === 'semua_termasuk_lunas' ? 'selected' : '' }}>Semua Termasuk Lunas</option>
            <option value="Belum Bayar" {{ ($statusFilter ?? '') === 'Belum Bayar' ? 'selected' : '' }}>Belum Bayar</option>
            <option value="Lunas" {{ ($statusFilter ?? '') === 'Lunas' || ($statusFilter ?? '') === 'rekap' ? 'selected' : '' }}>Sudah Terbayar / Lunas</option>
            <option value="Menunggu Verifikasi" {{ ($statusFilter ?? '') === 'Menunggu Verifikasi' ? 'selected' : '' }}>Menunggu Verifikasi</option>
            <option value="Jatuh Tempo" {{ ($statusFilter ?? '') === 'Jatuh Tempo' ? 'selected' : '' }}>Jatuh Tempo</option>
            <option value="isolir" {{ ($statusFilter ?? '') === 'isolir' ? 'selected' : '' }}>Terisolir</option>
          </select>
        </div>

        <button type="submit" class="h-9 bg-slate-800 hover:bg-slate-700 text-white px-3.5 rounded-xl text-xs font-bold transition shrink-0">
          Filter
        </button>

        @if($search || ($wilayahFilter ?? 'all') !== 'all' || ($statusFilter ?? 'all') !== 'all')
          <a href="{{ route('admin.tagihan') }}" class="text-xs text-slate-400 hover:text-red-500 font-bold shrink-0 ml-1" title="Reset Semua Filter">
            <i class="fa-solid fa-rotate-left mr-0.5"></i> Reset
          </a>
        @endif
      </div>

      <!-- Kanan: Tombol Aksi (Import, Export, Broadcast (X), + Pelanggan Baru) -->
      <div class="flex items-center flex-wrap gap-2 shrink-0">
        <!-- Tombol Import -->
        <button type="button" @click="openImportModal = true"
                class="h-9 px-3 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs transition flex items-center gap-1.5 shadow-2xs">
          <i class="fa-solid fa-file-import text-blue-600"></i>
          <span>Import</span>
        </button>

        <!-- Tombol Export -->
        <a href="{{ route('admin.tagihan.export', request()->query()) }}"
           class="h-9 px-3 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs transition flex items-center gap-1.5 shadow-2xs">
          <i class="fa-solid fa-file-excel text-emerald-600"></i>
          <span>Export</span>
        </a>

        <!-- Tombol Broadcast ( X ) -->
        <button type="button" @click="handleToolbarBroadcast()"
                class="h-9 px-3.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs transition flex items-center gap-1.5 shadow-xs">
          <i class="fa-brands fa-whatsapp text-sm"></i>
          <span>Broadcast ( <span x-text="selectedBillIds.length"></span> )</span>
        </button>

        <!-- Tombol + Pelanggan Baru / Tambah Tagihan -->
        <button type="button" @click="openCreateModal = true"
                class="h-9 px-3.5 rounded-xl bg-brand hover:bg-red-700 text-white font-bold text-xs transition flex items-center gap-1.5 shadow-xs">
          <i class="fa-solid fa-plus text-xs"></i>
          <span>+ Pelanggan Baru</span>
        </button>
      </div>
    </form>
  </div>

  <!-- Status Tabs Bar (Pills Cepat) -->
  <div class="flex items-center gap-2 text-xs font-semibold flex-nowrap overflow-x-auto pb-1">
    <a href="{{ route('admin.tagihan', ['status' => 'all', 'q' => $search, 'wilayah' => $wilayahFilter ?? 'all']) }}"
       title="Semua Tagihan Aktif Belum Lunas"
       class="h-8 px-3 rounded-xl transition flex items-center gap-1.5 whitespace-nowrap shrink-0 {{ $statusFilter === 'all' ? 'bg-brand text-white font-bold shadow-xs' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' }}">
      <span>Semua Aktif</span>
      <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $statusFilter === 'all' ? 'bg-white/20' : 'bg-slate-100' }}">{{ $counts['all'] }}</span>
    </a>

    <a href="{{ route('admin.tagihan', ['status' => 'Menunggu Verifikasi', 'q' => $search, 'wilayah' => $wilayahFilter ?? 'all']) }}"
       class="h-8 px-3 rounded-xl transition flex items-center gap-1.5 whitespace-nowrap shrink-0 {{ $statusFilter === 'Menunggu Verifikasi' ? 'bg-amber-500 text-white font-bold shadow-xs' : 'bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100' }}">
      <i class="fa-regular fa-clock"></i>
      <span>Menunggu Verifikasi</span>
      <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $statusFilter === 'Menunggu Verifikasi' ? 'bg-white/20' : 'bg-amber-200' }}">{{ $counts['menunggu'] }}</span>
    </a>

    <a href="{{ route('admin.tagihan', ['status' => 'Belum Bayar', 'q' => $search, 'wilayah' => $wilayahFilter ?? 'all']) }}"
       class="h-8 px-3 rounded-xl transition flex items-center gap-1.5 whitespace-nowrap shrink-0 {{ $statusFilter === 'Belum Bayar' ? 'bg-slate-800 text-white font-bold shadow-xs' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' }}">
      <span>Belum Bayar</span>
      <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $statusFilter === 'Belum Bayar' ? 'bg-white/20' : 'bg-slate-100' }}">{{ $counts['belum_bayar'] }}</span>
    </a>

    <a href="{{ route('admin.tagihan', ['status' => 'Jatuh Tempo', 'q' => $search, 'wilayah' => $wilayahFilter ?? 'all']) }}"
       class="h-8 px-3 rounded-xl transition flex items-center gap-1.5 whitespace-nowrap shrink-0 {{ $statusFilter === 'Jatuh Tempo' ? 'bg-red-600 text-white font-bold shadow-xs' : 'bg-red-50 text-red-800 border border-red-200 hover:bg-red-100' }}">
      <i class="fa-solid fa-triangle-exclamation"></i>
      <span>Jatuh Tempo</span>
      <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $statusFilter === 'Jatuh Tempo' ? 'bg-white/20' : 'bg-red-200' }}">{{ $counts['jatuh_tempo'] }}</span>
    </a>

    <a href="{{ route('admin.tagihan', ['status' => 'rekap', 'q' => $search, 'wilayah' => $wilayahFilter ?? 'all']) }}"
       title="Data Rekapitulasi Tagihan yang Sudah Terverifikasi Lunas"
       class="h-8 px-3 rounded-xl transition flex items-center gap-1.5 whitespace-nowrap shrink-0 {{ ($statusFilter === 'rekap' || $statusFilter === 'Lunas') ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100' }}">
      <i class="fa-solid fa-receipt"></i>
      <span>Riwayat / Rekap Lunas</span>
      <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ ($statusFilter === 'rekap' || $statusFilter === 'Lunas') ? 'bg-white/20' : 'bg-emerald-200' }}">{{ $counts['rekap'] ?? $counts['lunas'] }}</span>
    </a>
  </div>

  <!-- Banner & Filter Rekap Bulanan (Muncul Khusus di Tab Riwayat/Rekap Pembayaran) -->
  @if($statusFilter === 'rekap' || $statusFilter === 'Lunas')
    <div class="bg-emerald-50/70 border border-emerald-200/90 rounded-2xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-2xs">
      <div class="flex items-center gap-3.5">
        <div class="w-11 h-11 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-lg shrink-0 shadow-xs">
          <i class="fa-solid fa-file-invoice-dollar"></i>
        </div>
        <div>
          <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-emerald-950 uppercase tracking-wider">Rekapitulasi Pembayaran Per Bulan</span>
            <span class="bg-emerald-200/70 text-emerald-900 text-[10px] font-bold px-2 py-0.5 rounded-full">Terverifikasi Lunas</span>
          </div>
          <p class="text-xs text-slate-600 mt-0.5">
            Total Pemasukan: <span class="font-black text-emerald-700 text-sm">Rp{{ number_format($rekapNominal ?? 0, 0, ',', '.') }}</span>
            &bull; <span class="font-bold text-slate-700">{{ $rekapCount ?? 0 }} Tagihan</span> Telah Terverifikasi
          </p>
        </div>
      </div>

      <!-- Dropdown Pilih Bulan Rekap -->
      <form method="GET" action="{{ route('admin.tagihan') }}" class="flex items-center gap-2 shrink-0">
        <input type="hidden" name="status" value="rekap">
        @if(!empty($search))
          <input type="hidden" name="q" value="{{ $search }}">
        @endif
        <div class="flex items-center gap-1.5 bg-white border border-emerald-300 rounded-xl px-3 py-1.5 shadow-2xs">
          <i class="fa-regular fa-calendar text-emerald-600 text-xs"></i>
          <span class="text-xs font-bold text-slate-600">Bulan:</span>
          <select name="month" onchange="this.form.submit()"
                  class="text-xs font-bold bg-transparent border-0 p-0 text-slate-800 focus:ring-0 cursor-pointer">
            <option value="all">Semua Bulan</option>
            @foreach($availableMonths ?? [] as $m)
              <option value="{{ $m['key'] }}" {{ ($monthFilter ?? 'all') === $m['key'] ? 'selected' : '' }}>
                {{ $m['label'] }}
              </option>
            @endforeach
          </select>
        </div>
        @if(($monthFilter ?? 'all') !== 'all')
          <a href="{{ route('admin.tagihan', ['status' => 'rekap', 'q' => $search]) }}"
             class="text-xs text-slate-500 hover:text-red-600 font-bold px-2 py-1 rounded-lg hover:bg-red-50 transition" title="Tampilkan Semua Bulan">
            <i class="fa-solid fa-xmark"></i>
          </a>
        @endif
      </form>
    </div>
  @endif

  <!-- ============================================== -->
  <!-- 2. TABEL PENGINTAIAN & REKAP TAGIHAN          -->
  <!-- ============================================== -->
  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-xs">
        <thead class="bg-slate-50 text-slate-500 uppercase font-bold text-[10px] tracking-wider border-b border-slate-200">
          <tr>
            <th class="w-12 px-4 py-3.5 text-center">
              <input type="checkbox"
                     @change="toggleSelectAll($event)"
                     :checked="isAllSelected()"
                     title="Pilih Semua Pelanggan"
                     class="w-4 h-4 rounded border-slate-300 text-brand focus:ring-brand/30 cursor-pointer">
            </th>
            <th class="px-4 py-3.5 text-left font-bold text-slate-700">Pelanggan</th>
            <th class="px-4 py-3.5 text-center font-bold text-slate-700">Wilayah</th>
            <th class="px-4 py-3.5 text-left font-bold text-slate-700">Paket & PPPoE</th>
            <th class="px-4 py-3.5 text-center font-bold text-slate-700">Tagihan Nett</th>
            <th class="px-4 py-3.5 text-center font-bold text-slate-700">Jatuh Tempo</th>
            <th class="px-4 py-3.5 text-center font-bold text-slate-700">Status</th>
            <th class="px-5 py-3.5 text-center font-bold text-slate-700">Aksi Cepat</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
          @forelse($bills as $bill)
            <tr class="hover:bg-slate-50/70 transition">
              <!-- Checkbox Kolom Multi-Select -->
              <td class="w-12 px-4 py-4 text-center">
                <input type="checkbox"
                       value="{{ $bill['id'] }}"
                       x-model="selectedBillIds"
                       class="w-4 h-4 rounded border-slate-300 text-brand focus:ring-brand/30 cursor-pointer">
              </td>

              <!-- Pelanggan: Nama & No WhatsApp -->
              <td class="px-4 py-4 text-left">
                <p class="font-bold text-slate-900 text-sm leading-tight">{{ $bill['customer_name'] }}</p>
                <div class="flex items-center gap-1.5 text-xs text-slate-500 mt-1 font-mono">
                  <i class="fa-brands fa-whatsapp text-emerald-600 text-sm"></i>
                  <span>{{ $bill['customer_phone'] }}</span>
                </div>
              </td>

              <!-- Wilayah / Area -->
              <td class="px-4 py-4 text-center">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-red-50 text-brand border border-red-200/80 shadow-2xs whitespace-nowrap">
                  <i class="fa-solid fa-location-dot text-brand text-xs"></i>
                  <span>{{ $bill['wilayah'] ?? $bill['address'] }}</span>
                </span>
              </td>

              <!-- Paket & PPPoE -->
              <td class="px-4 py-4 text-left">
                <p class="font-bold text-slate-900 text-xs">{{ $bill['package_name'] }}</p>
                <p class="text-[11px] font-mono text-slate-400 mt-0.5">{{ $bill['pppoe'] ?? '-' }}</p>
              </td>

              <!-- Tagihan Nett -->
              <td class="px-4 py-4 text-center">
                <p class="font-black text-slate-900 text-sm font-mono">Rp {{ $bill['total'] }}</p>
                <p class="text-[10px] text-slate-400 font-medium mt-0.5">*(Disc/Tax)</p>
              </td>

              <!-- Jatuh Tempo -->
              <td class="px-4 py-4 text-center">
                <span class="font-bold text-slate-700 text-xs font-mono">
                  @if(preg_match('/^\d+/', $bill['due_date'], $m))
                    Tgl {{ $m[0] }}
                  @else
                    {{ $bill['due_date'] }}
                  @endif
                </span>
              </td>

              <!-- Status: Dual Badges (Bayar & Jaringan Aktif/Isolir) -->
              <td class="px-4 py-4 text-center">
                <div class="flex flex-col items-center justify-center gap-1.5">
                  <!-- Status Pembayaran (Atas) -->
                  @if($bill['status'] === 'Lunas')
                    <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-2xs">
                      LUNAS
                    </span>
                  @elseif($bill['status'] === 'Menunggu Verifikasi')
                    <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide bg-amber-50 text-amber-700 border border-amber-200 shadow-2xs animate-pulse">
                      MENUNGGU
                    </span>
                  @elseif($bill['status'] === 'Jatuh Tempo')
                    <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide bg-red-50 text-red-700 border border-red-200 shadow-2xs">
                      JATUH TEMPO
                    </span>
                  @else
                    <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide bg-red-50 text-brand border border-red-200 shadow-2xs">
                      BELUM BAYAR
                    </span>
                  @endif

                  <!-- Status Jaringan Pelanggan (Bawah) -->
                  @if($bill['is_isolir'])
                    <span class="inline-flex items-center justify-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide bg-amber-50 text-amber-800 border border-amber-300 shadow-2xs">
                      <i class="fa-solid fa-lock text-[9px]"></i> ISOLIR
                    </span>
                  @else
                    <span class="inline-flex items-center justify-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide bg-sky-50 text-sky-700 border border-sky-200 shadow-2xs">
                      <i class="fa-solid fa-wifi text-[9px]"></i> AKTIF
                    </span>
                  @endif
                </div>
              </td>

              <!-- Aksi Cepat -->
              <td class="px-5 py-4 text-center">
                <div class="flex items-center justify-center gap-1.5">
                  <!-- 1. Quick Pay (Bayar Cepat) / Undo jika sudah lunas -->
                  @if($bill['status'] !== 'Lunas')
                    <button type="button" @click="openQuickPay(@js($bill))"
                            title="Aksi Cepat: Bayar Tagihan Sekarang"
                            class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white flex items-center justify-center transition shadow-2xs hover:scale-105">
                      <i class="fa-solid fa-hand-holding-dollar text-sm"></i>
                    </button>
                  @else
                    <form action="{{ route('admin.tagihan.status', $bill['id']) }}" method="POST" class="inline">
                      @csrf
                      <input type="hidden" name="status" value="Belum Bayar">
                      <button type="submit"
                              title="Batalkan Pelunasan (Kembalikan ke Belum Bayar)"
                              onclick="return confirm('Kembalikan status tagihan ini ke Belum Bayar?')"
                              class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-600 hover:text-white flex items-center justify-center transition shadow-2xs hover:scale-105">
                        <i class="fa-solid fa-rotate-left text-sm"></i>
                      </button>
                    </form>
                  @endif

                  <!-- 2. WhatsApp Notification / Receipt -->
                  <a :href="getWaUrl(@js($bill))"
                     target="_blank"
                     title="{{ $bill['status'] === 'Lunas' ? 'Kirim Bukti Lunas WA' : 'Kirim Pengingat Tagihan WA' }}"
                     class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white flex items-center justify-center transition shadow-2xs hover:scale-105">
                    <i class="fa-brands fa-whatsapp text-sm"></i>
                  </a>

                  <!-- 3. Cetak Struk / Nota Elektronik -->
                  <a href="{{ route('tagihan.struk', $bill['id']) }}"
                     target="_blank"
                     title="Cetak Struk / Nota Elektronik"
                     class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 hover:bg-sky-600 hover:text-white flex items-center justify-center transition shadow-2xs hover:scale-105">
                    <i class="fa-solid fa-print text-sm"></i>
                  </a>

                  <!-- 4. Detail Info Tagihan -->
                  <button type="button" @click="viewBill(@js($bill))"
                          title="Lihat Detail Lengkap Tagihan"
                          class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900 flex items-center justify-center transition shadow-2xs hover:scale-105">
                    <i class="fa-solid fa-circle-info text-sm"></i>
                  </button>

                  <!-- 5. Toggle Isolir / Aktif -->
                  <form action="{{ route('admin.tagihan.toggle-isolir', $bill['id']) }}" method="POST" class="inline">
                    @csrf
                    @if($bill['is_isolir'])
                      <button type="submit"
                              title="Buka Isolir (Aktifkan Jaringan)"
                              onclick="return confirm('Aktifkan kembali koneksi internet pelanggan {{ $bill['customer_name'] }}?')"
                              class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white flex items-center justify-center transition shadow-2xs hover:scale-105">
                        <i class="fa-solid fa-lock-open text-sm"></i>
                      </button>
                    @else
                      <button type="submit"
                              title="Isolir Jaringan Pelanggan"
                              onclick="return confirm('Apakah Anda yakin ingin MENGISOLIR koneksi internet pelanggan {{ $bill['customer_name'] }}?')"
                              class="w-8 h-8 rounded-lg bg-red-50 text-brand hover:bg-brand hover:text-white flex items-center justify-center transition shadow-2xs hover:scale-105">
                        <i class="fa-solid fa-lock text-sm"></i>
                      </button>
                    @endif
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center py-12 text-slate-400">
                <i class="fa-solid fa-receipt text-3xl mb-2 text-slate-300"></i>
                <p class="font-medium text-xs text-slate-500">Tidak ada tagihan yang cocok dengan filter atau pencarian.</p>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <!-- ============================================== -->
  <!-- FLOATING ACTION BAR FOR WHATSAPP BROADCAST     -->
  <!-- ============================================== -->
  <div x-show="selectedBillIds.length > 0"
       x-transition:enter="ease-out duration-300"
       x-transition:enter-start="opacity-0 translate-y-8"
       x-transition:enter-end="opacity-100 translate-y-0"
       x-transition:leave="ease-in duration-200"
       x-transition:leave-start="opacity-100 translate-y-0"
       x-transition:leave-end="opacity-0 translate-y-8"
       class="fixed bottom-6 inset-x-0 z-40 max-w-2xl mx-auto px-4"
       style="display: none;">
    <div class="bg-white/95 backdrop-blur-md border-2 border-brand/20 text-slate-900 px-5 py-3.5 rounded-2xl shadow-2xl flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-2.5">
        <span class="w-8 h-8 rounded-xl bg-red-50 text-brand border border-red-200 flex items-center justify-center text-xs font-bold">
          <i class="fa-solid fa-check"></i>
        </span>
        <div>
          <p class="text-xs font-bold text-slate-900 leading-tight">
            <span class="text-brand font-black text-sm" x-text="selectedBillIds.length"></span> Pelanggan Dipilih
          </p>
          <p class="text-[10px] text-slate-500">Siap kirim broadcast WhatsApp resmi Banterpool</p>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <!-- Form Kirim Broadcast WA / Bukti Pembayaran -->
        <form action="{{ route('admin.tagihan.broadcast-wa') }}" method="POST" id="floatingBroadcastForm" class="inline flex items-center gap-2">
          @csrf
          <input type="hidden" name="broadcast_type" id="floating_broadcast_type" value="{{ ($statusFilter === 'rekap' || $statusFilter === 'Lunas') ? 'receipt' : 'auto' }}">
          <template x-for="id in selectedBillIds" :key="id">
            <input type="hidden" name="selected_bills[]" :value="id">
          </template>

          @if($statusFilter === 'rekap' || $statusFilter === 'Lunas')
            <button type="submit"
                    class="bg-brand hover:bg-red-700 text-white text-xs font-bold px-3.5 py-2.5 rounded-xl transition flex items-center gap-1.5 shadow-md shadow-brand/20">
              <i class="fa-solid fa-receipt text-amber-200 text-sm"></i>
              <span>Kirim Bukti Pembayaran WA (<span x-text="selectedBillIds.length"></span>)</span>
            </button>
          @else
            <button type="submit" @click="document.getElementById('floating_broadcast_type').value = 'receipt'"
                    title="Kirim Nota Elektronik / Bukti Pembayaran Lunas"
                    class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-3.5 py-2.5 rounded-xl transition flex items-center gap-1.5 shadow-md shadow-emerald-600/20">
              <i class="fa-solid fa-receipt text-amber-200 text-sm"></i>
              <span>Kirim Bukti Pembayaran</span>
            </button>

            <button type="submit" @click="document.getElementById('floating_broadcast_type').value = 'billing'"
                    title="Kirim Pengingat Tagihan Bulanan"
                    class="bg-brand hover:bg-red-700 text-white text-xs font-bold px-3.5 py-2.5 rounded-xl transition flex items-center gap-1.5 shadow-md shadow-brand/20">
              <i class="fa-brands fa-whatsapp text-sm"></i>
              <span>Kirim Tagihan</span>
            </button>
          @endif
        </form>

        <!-- Buka di WhatsApp Gateway -->
        <button type="button" @click="openBroadcastInGateway()"
                class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold px-3 py-2.5 rounded-xl transition flex items-center gap-1.5 border border-slate-300">
          <i class="fa-solid fa-paper-plane text-xs text-sky-600"></i>
          <span>Buka di Gateway</span>
        </button>

        <!-- Tombol Batal Pilih -->
        <button type="button" @click="selectedBillIds = []"
                class="text-slate-400 hover:text-slate-700 text-xs p-2 transition rounded-lg hover:bg-slate-100" title="Batal Pilih Semua">
          <i class="fa-solid fa-xmark text-sm"></i>
        </button>
      </div>
    </div>
  </div>

  <!-- ============================================== -->
  <!-- MODAL AKSI CEPAT: PROCESS PEMBAYARAN TAGIHAN  -->
  <!-- ============================================== -->
  <div x-show="openPayModal" style="display: none;"
       x-transition:enter="ease-out duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="ease-in duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0"
       class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">

    <div @click.away="openPayModal = false"
         class="bg-white border border-slate-200 rounded-3xl max-w-md w-full p-6 shadow-2xl relative space-y-5"
         x-show="payingBill">

      <!-- Header Modal -->
      <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <div class="flex items-center gap-2">
          <i class="fa-solid fa-hand-holding-dollar text-brand text-base"></i>
          <h3 class="text-sm font-bold text-slate-900 tracking-wide">Process Pembayaran Tagihan</h3>
        </div>
        <button type="button" @click="openPayModal = false" class="text-slate-400 hover:text-slate-700 text-base">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <!-- Summary Box Pelanggan & Paket -->
      <div class="bg-red-50/50 rounded-2xl p-4 space-y-2 border border-red-100 text-xs">
        <div class="flex justify-between items-center">
          <span class="text-slate-500 font-medium">Pelanggan:</span>
          <span class="font-bold text-slate-900 text-right" x-text="payingBill?.customer_name"></span>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-slate-500 font-medium">Paket Internet:</span>
          <span class="font-medium text-slate-700 text-right" x-text="payingBill?.package_name"></span>
        </div>
        <div class="flex justify-between items-center pt-2 border-t border-red-100 font-semibold">
          <span class="text-slate-600">Total Tagihan Final:</span>
          <span class="font-black text-brand text-base font-mono" x-text="'Rp ' + payingBill?.total"></span>
        </div>
      </div>

      <!-- Form Bayar Tagihan -->
      <form :action="'/admin/tagihan/' + payingBill?.id + '/pay'" method="POST" class="space-y-4 text-xs">
        @csrf
        <input type="hidden" name="status" value="Lunas">

        <!-- Dropdown Metode Pembayaran -->
        <div>
          <label class="block font-semibold text-slate-700 mb-1.5">
            Metode Pembayaran (Via) <span class="text-brand">*</span>
          </label>
          <select name="payment_method" required
                  class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2.5 text-xs text-slate-900 focus:ring-brand focus:border-brand font-medium">
            <option value="Tunai / Cash (Kolektor Lapangan)">Tunai / Cash (Kolektor Lapangan)</option>
            <option value="Transfer Bank BCA">Transfer Bank BCA</option>
            <option value="Transfer Bank BRI">Transfer Bank BRI</option>
            <option value="Transfer Bank Mandiri">Transfer Bank Mandiri</option>
            <option value="QRIS / E-Wallet">QRIS / E-Wallet</option>
            <option value="BRIVA">BRIVA</option>
            <option value="Verifikasi Admin">Verifikasi Admin</option>
          </select>
        </div>

        <!-- Input Admin Penerima -->
        <div>
          <label class="block font-semibold text-slate-700 mb-1.5">
            Admin / Penerima Pembayaran <span class="text-brand">*</span>
          </label>
          <input type="text" name="collected_by" required
                 value="{{ auth()->user()->name ?? 'Admin Billing Banterpool' }}"
                 class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2.5 text-xs text-slate-900 focus:ring-brand focus:border-brand font-medium">
        </div>

        <!-- Buttons -->
        <div class="pt-2 flex items-center justify-end gap-2.5 border-t border-slate-100">
          <button type="button" @click="openPayModal = false"
                  class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold px-4 py-2.5 rounded-xl transition">
            Batal
          </button>
          <button type="submit"
                  class="bg-brand hover:bg-red-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition flex items-center gap-1.5 shadow-sm">
            <i class="fa-solid fa-check"></i>
            <span>Bayar Sekarang</span>
          </button>
        </div>
      </form>

    </div>
  </div>

  <!-- ======================================================== -->
  <!-- MODAL SELECT NAMA PELANGGAN: BROADCAST BUKTI PEMBAYARAN -->
  <!-- ======================================================== -->
  <div x-show="openBroadcastBuktiModal" style="display: none;"
       x-transition:enter="ease-out duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="ease-in duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0"
       class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">

    <div @click.away="openBroadcastBuktiModal = false"
         class="bg-white border border-slate-200 rounded-3xl max-w-xl w-full p-6 sm:p-7 shadow-2xl relative space-y-5">

      <!-- Header Modal -->
      <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <div class="flex items-center gap-2.5">
          <div class="w-10 h-10 rounded-xl bg-red-50 text-brand border border-red-100 flex items-center justify-center text-lg">
            <i class="fa-solid fa-receipt"></i>
          </div>
          <div>
            <h3 class="text-sm sm:text-base font-bold text-slate-900 tracking-wide">Kirim Bukti Pembayaran via Broadcast WhatsApp</h3>
            <p class="text-[11px] text-slate-500">Pilih nama pelanggan lunas untuk dikirimi Nota Elektronik resmi via WhatsApp.</p>
          </div>
        </div>
        <button type="button" @click="openBroadcastBuktiModal = false" class="text-slate-400 hover:text-slate-700 text-base">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <!-- Form Broadcast Bukti Pembayaran -->
      <form action="{{ route('admin.tagihan.broadcast-wa') }}" method="POST" class="space-y-4 text-xs">
        @csrf
        <input type="hidden" name="broadcast_type" value="receipt">

        <!-- Section 1: Pilih Nama Pelanggan -->
        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <label class="block font-bold text-slate-800 text-xs">
              Pilih Nama Pelanggan Penerima Bukti Lunas <span class="text-brand">*</span>
            </label>
            <div class="flex items-center gap-2 text-[11px]">
              <button type="button" @click="selectAllPaidCustomers()" class="text-brand hover:text-red-700 font-bold hover:underline">
                Pilih Semua Lunas
              </button>
              <span class="text-slate-300">&bull;</span>
              <button type="button" @click="clearSelectedPaidCustomers()" class="text-slate-500 hover:text-slate-700 hover:underline">
                Bersihkan
              </button>
            </div>
          </div>

          <!-- Multi-select Box Daftar Pelanggan Lunas -->
          <div class="bg-slate-50 border border-slate-200 rounded-2xl p-2.5 max-h-56 overflow-y-auto space-y-1.5 custom-scrollbar">
            @php
              $allLunas = collect($bills)->where('status', 'Lunas')->values();
              if ($allLunas->isEmpty()) {
                  $allLunas = collect($bills);
              }
            @endphp

            @forelse($allLunas as $lb)
              <label class="flex items-center justify-between p-2.5 rounded-xl hover:bg-white transition cursor-pointer border border-transparent hover:border-slate-200 shadow-2xs">
                <div class="flex items-center gap-3">
                  <input type="checkbox" name="selected_bills[]" value="{{ $lb['id'] }}"
                         x-model="selectedBuktiCustomerIds"
                         class="w-4 h-4 rounded border-slate-300 text-brand focus:ring-brand/30 cursor-pointer">
                  <div>
                    <div class="flex items-center gap-2">
                      <span class="font-bold text-slate-900 text-xs">{{ $lb['customer_name'] }}</span>
                      <span class="text-[10px] px-1.5 py-0.2 rounded font-mono {{ $lb['status'] === 'Lunas' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold' : 'bg-slate-100 text-slate-600' }}">
                        {{ $lb['status'] }}
                      </span>
                    </div>
                    <p class="text-[11px] text-slate-500 font-mono flex items-center gap-1.5 mt-0.5">
                      <i class="fa-brands fa-whatsapp text-emerald-600 text-xs"></i>
                      <span>{{ $lb['customer_phone'] }}</span>
                      <span class="text-slate-300">&bull;</span>
                      <span>{{ $lb['package_name'] }}</span>
                    </p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="font-black text-slate-900 text-xs font-mono">Rp {{ $lb['total'] }}</p>
                  <span class="text-[10px] font-mono text-slate-400">{{ $lb['receipt_number'] ?? $lb['id'] }}</span>
                </div>
              </label>
            @empty
              <div class="text-center py-6 text-slate-400">
                <i class="fa-solid fa-receipt text-2xl mb-1 text-slate-300"></i>
                <p class="text-xs text-slate-500">Belum ada pelanggan dengan status Lunas.</p>
              </div>
            @endforelse
          </div>

          <div class="flex items-center justify-between text-[11px] px-1">
            <span class="text-slate-500">Pelanggan terpilih: <b class="text-brand font-bold" x-text="selectedBuktiCustomerIds.length"></b> orang</span>
            <span class="text-slate-400 font-mono text-[10px]">Terkirim via WhatsApp Gateway API</span>
          </div>
        </div>

        <!-- Section 2: Preview Format Pesan Nota Elektronik -->
        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-3.5 space-y-1.5 text-[11px]">
          <span class="text-slate-500 font-bold block text-[10px] uppercase tracking-wider">Preview Template Nota Elektronik:</span>
          <div class="font-mono text-slate-800 bg-white p-2.5 rounded-xl border border-slate-200 leading-relaxed max-h-36 overflow-y-auto whitespace-pre-wrap text-[10px] select-all">
*BANTERPOOL* 

NOTA ELEKTRONIK
{{ url('/struk/{id}') }}

Yth. Bapak / Ibu {nama}
{no_pelanggan}
null
Area : {area}

*PEMBAYARAN*
Tanggal Bayar :{{ date('d M Y') }} | {{ date('H:i') }} WIB

Tagihan Bulan : 
- {{ date('M Y') }}
Jenis Paket : {JENIS PAKET}
Harga Paket : {HARGA PAKET}
*Sudah Termasuk Pajak
Diskon  : Rp. 0
Total Biaya : {TOTAL BIAYA}
Ket : *LUNAS* 
Admin By : {{ auth()->user()->name ?? 'Admin Banterpool' }}

terimakasih telah menggunakan layanan internet dari BANTERPOOL

SUPPORT BY :
Admin BANTERPOOL
          </div>
        </div>

        <!-- Section 3: Action Buttons -->
        <div class="pt-2 flex items-center justify-end gap-2.5 border-t border-slate-100">
          <button type="button" @click="openBroadcastBuktiModal = false"
                  class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold px-4 py-2.5 rounded-xl transition">
            Batal
          </button>
          <button type="submit" :disabled="selectedBuktiCustomerIds.length === 0"
                  class="bg-brand hover:bg-red-700 disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs font-bold px-4 py-2.5 rounded-xl transition flex items-center gap-1.5 shadow-sm">
            <i class="fa-solid fa-paper-plane text-xs"></i>
            <span>Kirim Bukti Pembayaran Sekarang</span>
          </button>
        </div>

      </form>

    </div>
  </div>

  <!-- ============================================== -->
  <!-- POST-PAYMENT SUCCESS CONFIRMATION & STRUK POPUP-->
  <!-- ============================================== -->
  @if(session('print_struk_id'))
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        // Otomatis buka struk nota elektronik di tab baru setelah bayar
        const strukUrl = '{{ route("tagihan.struk", session("print_struk_id")) }}';
        window.open(strukUrl, '_blank');
      });
    </script>

    <div x-show="showDoneModal" style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
      <div class="bg-white border border-emerald-300 rounded-3xl max-w-md w-full p-6 text-center space-y-4 shadow-2xl">
        <div class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-2xl mx-auto flex items-center justify-center text-2xl border border-emerald-200">
          <i class="fa-solid fa-circle-check"></i>
        </div>
        <div>
          <h3 class="text-base font-black text-slate-900">Pembayaran Berhasil Diverifikasi!</h3>
          <p class="text-xs text-slate-600 mt-1">
            Tagihan <span class="font-bold text-slate-900">{{ session('print_struk_id') }}</span> atas nama <span class="font-bold text-brand">{{ session('paid_customer_name') }}</span> sebesar <span class="font-black text-slate-900">Rp{{ session('paid_total') }}</span> telah lunas.
          </p>

          <!-- Status Notifikasi WhatsApp Otomatis -->
          @if(session('wa_sent'))
            <div class="mt-3 p-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center justify-center gap-2">
              <i class="fa-brands fa-whatsapp text-emerald-600 text-base"></i>
              <span>Invoice otomatis <b>terkirim ke WhatsApp</b> pelanggan!</span>
            </div>
          @elseif(session('paid_customer_phone'))
            <div class="mt-3 p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 text-xs flex items-center justify-center gap-2">
              <i class="fa-brands fa-whatsapp text-emerald-600 text-base"></i>
              <span>WhatsApp Pelanggan: <b>{{ session('paid_customer_phone') }}</b></span>
            </div>
          @endif
        </div>
        <div class="flex flex-col sm:flex-row gap-2 pt-2">
          <a href="{{ route('tagihan.struk', session('print_struk_id')) }}" target="_blank"
             class="flex-1 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold py-2.5 px-3 rounded-xl transition flex items-center justify-center gap-1.5 shadow-sm">
            <i class="fa-solid fa-receipt text-sky-400"></i> Cetak Ulang Struk
          </a>
          @if(session('paid_customer_phone'))
            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', session('paid_customer_phone')) }}?text={{ rawurlencode(session('wa_message') ?? '') }}"
               target="_blank"
               class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold py-2.5 px-3 rounded-xl transition flex items-center justify-center gap-1.5 shadow-sm">
              <i class="fa-brands fa-whatsapp text-sm"></i> Buka Chat WA
            </a>
          @endif
          <button type="button" @click="showDoneModal = false"
                  class="flex-1 border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-bold py-2.5 px-3 rounded-xl transition">
            Selesai
          </button>
        </div>
      </div>
    </div>
  @endif

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
            <span class="text-brand text-base" x-text="'Rp ' + selectedBill?.total"></span>
          </div>
        </div>

        <!-- Informasi Riwayat Rekap Pelunasan jika Lunas -->
        <template x-if="selectedBill?.status === 'Lunas'">
          <div class="border border-emerald-200 bg-emerald-50/70 rounded-xl p-4 space-y-2.5">
            <div class="flex items-center justify-between pb-2 border-b border-emerald-200/60">
              <span class="inline-flex items-center gap-1.5 text-emerald-800 font-bold text-xs">
                <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i> Pembayaran Terverifikasi Lunas
              </span>
              <span class="text-[10px] font-mono font-bold bg-white text-emerald-800 px-2 py-0.5 rounded border border-emerald-300"
                    x-text="selectedBill?.receipt_number"></span>
            </div>
            <div class="grid grid-cols-2 gap-2 text-slate-600">
              <div>
                <span class="text-[10px] text-slate-400 block">Waktu Pelunasan:</span>
                <span class="font-bold text-slate-800" x-text="selectedBill?.paid_at_formatted || selectedBill?.paid_date || '-'"></span>
              </div>
              <div>
                <span class="text-[10px] text-slate-400 block">Bulan Rekap:</span>
                <span class="font-bold text-emerald-700" x-text="selectedBill?.month_label"></span>
              </div>
              <div>
                <span class="text-[10px] text-slate-400 block">Diverifikasi Oleh:</span>
                <span class="font-bold text-slate-800" x-text="selectedBill?.collected_by || 'Admin NOC'"></span>
              </div>
              <div>
                <span class="text-[10px] text-slate-400 block">Catatan:</span>
                <span class="font-medium text-slate-700 truncate block" x-text="selectedBill?.collector_notes || '-'"></span>
              </div>
            </div>
          </div>
        </template>

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
        <!-- Tombol Verifikasi jika status belum lunas -->
        <template x-if="selectedBill?.status !== 'Lunas'">
          <form :action="'/admin/tagihan/' + selectedBill?.id + '/status'" method="POST" class="w-full sm:flex-1 flex items-center gap-2">
            @csrf
            <input type="hidden" name="status" value="Lunas">
            <button type="submit"
                    class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs py-2.5 px-4 rounded-xl transition flex items-center justify-center gap-1.5 shadow-sm">
              <i class="fa-solid fa-check"></i> Verifikasi & Pindahkan ke Rekap
            </button>
          </form>
        </template>

        <!-- Aksi jika sudah lunas -->
        <template x-if="selectedBill?.status === 'Lunas'">
          <div class="w-full sm:flex-1 flex flex-wrap sm:flex-nowrap items-center gap-2">
            <!-- Tombol Cetak Struk -->
            <a :href="'/struk/' + selectedBill?.id" target="_blank"
               class="flex-1 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs py-2.5 px-3 rounded-xl transition flex items-center justify-center gap-1.5 shadow-sm text-center">
              <i class="fa-solid fa-receipt"></i> Cetak Struk
            </a>

            <!-- Tombol Kirim Bukti Lunas WA Template Baru -->
            <a :href="getWaBuktiLunasUrl(selectedBill)"
               target="_blank"
               class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs py-2.5 px-3 rounded-xl transition flex items-center justify-center gap-1.5 shadow-sm text-center">
              <i class="fa-brands fa-whatsapp text-sm"></i> Kirim Bukti Lunas WA
            </a>
          </div>
        </template>

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

  <!-- ============================================== -->
  <!-- 4. MODAL INPUT MANUAL TAGIHAN BARU             -->
  <!-- ============================================== -->
  <div x-show="openCreateModal"
       style="display: none;"
       class="relative z-50"
       role="dialog"
       aria-modal="true">
    <div x-show="openCreateModal"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
         @click="openCreateModal = false"></div>

    <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4"
         @click.self="openCreateModal = false">
      <div x-show="openCreateModal"
           x-transition:enter="ease-out duration-200"
           x-transition:enter-start="opacity-0 scale-95"
           x-transition:enter-end="opacity-100 scale-100"
           x-transition:leave="ease-in duration-150"
           x-transition:leave-start="opacity-100 scale-100"
           x-transition:leave-end="opacity-0 scale-95"
           class="bg-white rounded-3xl max-w-3xl w-full p-6 sm:p-8 relative shadow-2xl max-h-[92vh] overflow-y-auto"
           @click.stop>

        <!-- Close Button -->
        <button type="button" @click="openCreateModal = false"
                class="absolute top-6 right-6 text-slate-400 hover:text-slate-700 text-xl font-bold">
          <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="space-y-6">
          <!-- Header Modal -->
          <div class="border-b border-slate-100 pb-4">
            <div class="flex items-center gap-2 mb-1">
              <span class="text-xs font-bold bg-red-100 text-brand px-2.5 py-0.5 rounded-lg flex items-center gap-1">
                <i class="fa-solid fa-file-invoice-dollar"></i> Input Tagihan Manual
              </span>
              <span class="text-xs font-bold text-slate-400">Keuangan & Billing NOC</span>
            </div>
            <h3 class="text-xl font-black text-slate-900">Terbitkan Tagihan Baru</h3>
            <p class="text-xs text-slate-500 mt-0.5">
              Pilih dari data pelanggan terdaftar atau input manual tagihan untuk pelanggan fisik luar sistem.
            </p>
          </div>

          <!-- Mode Toggle: Terdaftar vs Manual -->
          <div class="flex items-center gap-2 p-1.5 bg-slate-100 rounded-2xl">
            <button type="button" @click="billForm.mode = 'registered'"
                    :class="billForm.mode === 'registered' ? 'bg-white text-slate-900 font-bold shadow-xs' : 'text-slate-500 hover:text-slate-800 font-semibold'"
                    class="flex-1 py-2 px-3 text-xs rounded-xl transition flex items-center justify-center gap-1.5">
              <i class="fa-solid fa-users text-brand"></i>
              <span>Pilih Pelanggan Terdaftar (Otomatis)</span>
            </button>
            <button type="button" @click="billForm.mode = 'manual'; billForm.orderId = ''"
                    :class="billForm.mode === 'manual' ? 'bg-white text-slate-900 font-bold shadow-xs' : 'text-slate-500 hover:text-slate-800 font-semibold'"
                    class="flex-1 py-2 px-3 text-xs rounded-lg transition flex items-center justify-center gap-1.5">
              <i class="fa-solid fa-pen-to-square text-brand"></i>
              <span>Input Bebas Manual</span>
            </button>
          </div>

          <!-- Form Store Tagihan -->
          <form action="{{ route('admin.tagihan.store') }}" method="POST" class="space-y-5 text-xs">
            @csrf
            <input type="hidden" name="order_id" x-model="billForm.orderId">

            <!-- Dropdown Pilihan Pelanggan Terdaftar (Jika Mode Registered) -->
            <div x-show="billForm.mode === 'registered'" class="p-3.5 bg-blue-50/60 rounded-2xl border border-blue-200/80 space-y-1.5">
              <label class="block font-bold text-blue-950 text-xs">
                <i class="fa-solid fa-magnifying-glass mr-1"></i> Cari & Pilih Pelanggan Terdaftar
              </label>
              <select @change="onSelectCustomer($event)"
                      x-model="billForm.orderId"
                      class="w-full text-xs p-2.5 border border-blue-300 rounded-xl focus:ring-brand focus:border-brand bg-white font-bold text-slate-800 shadow-2xs">
                <option value="">-- Klik untuk memilih pelanggan ({{ count($registeredCustomers ?? []) }} Pelanggan) --</option>
                @foreach($registeredCustomers ?? [] as $c)
                  <option value="{{ $c->id }}"
                          data-name="{{ $c->customer_name }}"
                          data-phone="{{ $c->customer_phone }}"
                          data-email="{{ $c->customer_email }}"
                          data-address="{{ $c->address }}"
                          data-package="{{ $c->package_name }}"
                          data-speed="{{ $c->speed }}"
                          data-price="{{ $c->price }}">
                    {{ $c->customer_name }} ({{ $c->order_number }}) - {{ $c->package_name }} - {{ Str::limit($c->address, 35) }}
                  </option>
                @endforeach
              </select>
              <p class="text-[10px] text-blue-700">Nama, No. WhatsApp, Alamat, Paket, dan Biaya akan terisi otomatis begitu dipilih.</p>
            </div>

            <!-- Section 1: Data Identitas Pelanggan -->
            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80 space-y-3">
              <div class="flex items-center gap-1.5 font-bold text-slate-800 text-xs border-b border-slate-200/60 pb-2">
                <i class="fa-solid fa-user text-brand"></i>
                <span>1. Data Pelanggan Penerima Tagihan</span>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <!-- Nama Pelanggan -->
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Nama Pelanggan <span class="text-red-500">*</span></label>
                  <input type="text" name="customer_name" x-model="billForm.customerName" required placeholder="Nama lengkap pelanggan"
                         class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white font-semibold">
                </div>

                <!-- No WhatsApp / HP -->
                <div>
                  <label class="block font-bold text-slate-700 mb-1">No. WhatsApp / HP <span class="text-red-500">*</span></label>
                  <input type="tel" name="customer_phone" x-model="billForm.customerPhone" required placeholder="08xxxxxxxxxx"
                         class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white font-mono font-semibold">
                </div>

                <!-- Email Pelanggan -->
                <div class="sm:col-span-2">
                  <label class="block font-bold text-slate-700 mb-1">Email Pelanggan (Opsional)</label>
                  <input type="email" name="customer_email" x-model="billForm.customerEmail" placeholder="pelanggan@gmail.com"
                         class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white">
                </div>

                <!-- Alamat Pemasangan -->
                <div class="sm:col-span-2">
                  <label class="block font-bold text-slate-700 mb-1">Alamat Pemasangan <span class="text-red-500">*</span></label>
                  <textarea name="address" x-model="billForm.address" required rows="2" placeholder="Nama Jalan, RT/RW, Dusun, Desa, Kec. Cilongok"
                            class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white resize-none"></textarea>
                </div>
              </div>
            </div>

            <!-- Section 2: Informasi Paket & Periode Invoice -->
            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80 space-y-3">
              <div class="flex items-center gap-1.5 font-bold text-slate-800 text-xs border-b border-slate-200/60 pb-2">
                <i class="fa-solid fa-wifi text-brand"></i>
                <span>2. Paket Layanan & Periode Tagihan</span>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <!-- Paket Layanan -->
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Paket Layanan <span class="text-red-500">*</span></label>
                  <select name="package_name" x-model="billForm.packageName" required @change="onPackageSelect($event)"
                          class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white font-bold text-slate-800">
                    <option value="Paket 20 Mbps">Paket 20 Mbps</option>
                    <option value="Paket 30 Mbps">Paket 30 Mbps</option>
                    <option value="Paket 50 Mbps">Paket 50 Mbps</option>
                  </select>
                </div>

                <!-- Kecepatan -->
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Kecepatan</label>
                  <input type="text" name="speed" x-model="billForm.speed" placeholder="20 Mbps"
                         class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white">
                </div>

                <!-- Tanggal Terbit Tagihan -->
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Tanggal Terbit</label>
                  <input type="text" name="bill_date" x-model="billForm.billDate" placeholder="Contoh: 05 Okt 2026"
                         class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white">
                  <span class="text-[10px] text-slate-400">Terbit setiap tanggal 05</span>
                </div>

                <!-- Periode Tagihan -->
                <div class="sm:col-span-2">
                  <label class="block font-bold text-slate-700 mb-1">Periode Pemakaian <span class="text-red-500">*</span></label>
                  <input type="text" name="period" x-model="billForm.period" required placeholder="Contoh: 01 Okt 2026 – 31 Okt 2026"
                         class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white font-semibold">
                </div>

                <!-- Batas Jatuh Tempo -->
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Jatuh Tempo <span class="text-red-500">*</span></label>
                  <input type="text" name="due_date" x-model="billForm.dueDate" required placeholder="Contoh: 20 Okt 2026"
                         class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white font-bold text-red-600">
                  <span class="text-[10px] text-slate-400">Batas bayar maksimal tanggal 20 setiap bulannya</span>
                </div>
              </div>
            </div>

            <!-- Section 3: Rincian Nominal & Total -->
            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80 space-y-3">
              <div class="flex items-center gap-1.5 font-bold text-slate-800 text-xs border-b border-slate-200/60 pb-2">
                <i class="fa-solid fa-calculator text-brand"></i>
                <span>3. Nominal & Total Tagihan</span>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <!-- Tarif Paket -->
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Tarif Langganan (Rp) <span class="text-red-500">*</span></label>
                  <input type="number" name="amount" x-model="billForm.amount" required min="0" step="1000"
                         class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white font-bold font-mono">
                </div>

                <!-- Biaya Lain / Pajak -->
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Biaya Tambahan / Pajak (Rp)</label>
                  <input type="number" name="tax" x-model="billForm.tax" min="0" step="1000" placeholder="0"
                         class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white font-mono">
                </div>
              </div>

              <!-- Input Hidden Total & Preview Box -->
              <input type="hidden" name="total" :value="billForm.total()">
              <div class="p-3 bg-red-50/60 rounded-xl border border-red-100 flex items-center justify-between">
                <div>
                  <span class="font-bold text-slate-700">Total Nominal Tagihan:</span>
                  <p class="text-[10px] text-slate-400">Nominal yang wajib dibayar pelanggan sebelum jatuh tempo</p>
                </div>
                <span class="text-brand font-black text-xl">
                  Rp<span x-text="billForm.total().toLocaleString('id-ID')"></span>
                </span>
              </div>
            </div>

            <!-- Section 4: Status Tagihan & Pembayaran -->
            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80 space-y-3">
              <div class="flex items-center gap-1.5 font-bold text-slate-800 text-xs border-b border-slate-200/60 pb-2">
                <i class="fa-solid fa-receipt text-brand"></i>
                <span>4. Status & Metode Pembayaran</span>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <!-- Status Tagihan -->
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Status Tagihan <span class="text-red-500">*</span></label>
                  <select name="status" x-model="billForm.status" required
                          class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white font-bold">
                    <option value="Belum Bayar">Belum Bayar</option>
                    <option value="Lunas">Lunas (Terbit Kuitansi Langsung)</option>
                    <option value="Menunggu Verifikasi">Menunggu Verifikasi</option>
                    <option value="Jatuh Tempo">Jatuh Tempo</option>
                  </select>
                </div>

                <!-- Metode Pembayaran -->
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Metode Pembayaran</label>
                  <select name="payment_method" x-model="billForm.paymentMethod"
                          class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white font-semibold">
                    <option value="Transfer Bank (BCA)">Transfer Bank (BCA)</option>
                    <option value="Tunai (Kolektor/Admin)">Tunai (Kolektor/Admin)</option>
                    <option value="QRIS">QRIS</option>
                    <option value="Verifikasi Admin">Verifikasi Admin</option>
                  </select>
                </div>

                <!-- Catatan Kolektor / Admin -->
                <div class="sm:col-span-2">
                  <label class="block font-bold text-slate-700 mb-1">Catatan Tagihan / Keterangan</label>
                  <textarea name="collector_notes" x-model="billForm.collectorNotes" rows="2" placeholder="Catatan bukti setor, nomor referensi, atau perjanjian bayar..."
                            class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white resize-none"></textarea>
                </div>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2.5">
              <button type="button" @click="openCreateModal = false"
                  class="px-5 py-2.5 border border-slate-300 text-slate-700 font-bold rounded-xl hover:bg-slate-50 transition">
                Batal
              </button>
              <button type="submit"
                      class="bg-brand hover:bg-red-700 text-white font-bold px-6 py-2.5 rounded-xl shadow-xs transition flex items-center gap-1.5">
                <i class="fa-solid fa-file-invoice"></i>
                <span>Terbitkan Tagihan</span>
              </button>
            </div>

          </form>

        </div>

      </div>
    </div>
  </div>

  <!-- ============================================== -->
  <!-- 5. MODAL IMPORT EXCEL / CSV                     -->
  <!-- ============================================== -->
  <div x-show="openImportModal" style="display: none;"
       x-transition:enter="ease-out duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="ease-in duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0"
       class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
    <div @click.away="openImportModal = false"
         class="bg-white border border-slate-200 rounded-3xl max-w-md w-full p-6 shadow-2xl relative space-y-4">
      <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <div class="flex items-center gap-2">
          <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-base">
            <i class="fa-solid fa-file-import"></i>
          </div>
          <div>
            <h3 class="text-sm font-bold text-slate-900">Import Data Pelanggan & Tagihan</h3>
            <p class="text-[11px] text-slate-500">Unggah berkas Excel (.xlsx / .xls / .csv)</p>
          </div>
        </div>
        <button type="button" @click="openImportModal = false" class="text-slate-400 hover:text-slate-700 text-base">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <form action="{{ Route::has('admin.tagihan.import') ? route('admin.tagihan.import') : url('/admin/tagihan/import') }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
        @csrf
        <div class="border-2 border-dashed border-slate-300 hover:border-brand rounded-2xl p-6 text-center transition bg-slate-50">
          <i class="fa-solid fa-cloud-arrow-up text-3xl text-slate-400 mb-2"></i>
          <p class="font-bold text-slate-700 text-xs">Pilih berkas Excel / CSV dari komputer</p>
          <p class="text-[10px] text-slate-400 mt-0.5">Format didukung: .xlsx, .xls, .csv</p>
          <input type="file" name="file" required accept=".xlsx,.xls,.csv" class="mt-3 text-xs w-full cursor-pointer">
        </div>

        <div class="bg-blue-50/60 border border-blue-100 rounded-xl p-3 text-[11px] text-slate-600 space-y-1">
          <p class="font-bold text-blue-900"><i class="fa-solid fa-circle-info mr-1"></i> Informasi Sinkronisasi:</p>
          <p>Setelah berkas diimpor, sistem akan otomatis menyinkronkan seluruh tagihan aktif pelanggan dan akun PPPoE MikroTik.</p>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
          <button type="button" @click="openImportModal = false"
                  class="px-4 py-2 border border-slate-300 rounded-xl text-slate-700 font-bold hover:bg-slate-50 transition">
            Batal
          </button>
          <button type="submit"
                  class="px-4 py-2 bg-brand hover:bg-red-700 text-white rounded-xl font-bold transition flex items-center gap-1.5 shadow-xs">
            <i class="fa-solid fa-upload"></i> Unggah & Impor
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- ============================================== -->
  <!-- 6. MODAL FORMAT PESAN WHATSAPP                 -->
  <!-- ============================================== -->
  <div x-show="openWaFormatModal" style="display: none;"
       x-transition:enter="ease-out duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="ease-in duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0"
       class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
    <div @click.away="openWaFormatModal = false"
         class="bg-white border border-slate-200 rounded-3xl max-w-xl w-full p-6 sm:p-7 shadow-2xl relative space-y-4">
      <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <div class="flex items-center gap-2.5">
          <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xl">
            <i class="fa-brands fa-whatsapp"></i>
          </div>
          <div>
            <h3 class="text-sm sm:text-base font-bold text-slate-900">Format Template WhatsApp Resmi</h3>
            <p class="text-[11px] text-slate-500">Template pesan pengingat tagihan & bukti pelunasan.</p>
          </div>
        </div>
        <button type="button" @click="openWaFormatModal = false" class="text-slate-400 hover:text-slate-700 text-base">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <div class="space-y-3 text-xs" x-data="{ formatTab: 'billing' }">
        <div class="flex items-center gap-2 p-1 bg-slate-100 rounded-xl">
          <button type="button" @click="formatTab = 'billing'"
                  :class="formatTab === 'billing' ? 'bg-white text-slate-900 font-bold shadow-xs' : 'text-slate-600 font-semibold'"
                  class="flex-1 py-1.5 rounded-lg text-xs transition">
            1. Pengingat Tagihan Bulanan
          </button>
          <button type="button" @click="formatTab = 'receipt'"
                  :class="formatTab === 'receipt' ? 'bg-white text-slate-900 font-bold shadow-xs' : 'text-slate-600 font-semibold'"
                  class="flex-1 py-1.5 rounded-lg text-xs transition">
            2. Nota Elektronik (Bukti Lunas)
          </button>
        </div>

        <!-- Template 1: Billing -->
        <div x-show="formatTab === 'billing'" class="bg-slate-50 border border-slate-200 rounded-2xl p-3.5 space-y-1.5">
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Format Pesan Tagihan:</span>
          <pre class="font-mono text-slate-800 bg-white p-3 rounded-xl border border-slate-200 text-[11px] leading-relaxed whitespace-pre-wrap select-all max-h-56 overflow-y-auto">Yth. Bpk/Ibu {nama}
{no hp pelanggan}

PEMBERITAHUAN
Tagihan Bulan : {{ date('F Y') }}
Jenis Paket : {jenis paket}
Biaya Paket : {biaya paket}
Besar Tagihan : {tagihan}

Masa aktif s/d 20 {{ date('F Y') }}

Nomor Virtual Acount : 1691101{no hp pelanggan}

Pastikan untuk membayar menggunakan Virtual Acount seperti BRIVA, DANA dll

Ket : BELUM TERBAYAR

INFO TAMBAHAN
Untuk menghindari isolir otomatis, sikahkan lalukan pembayaran sebelum tanggal jatuh tempo, dan pastikan mengirim bukti transaksi melalui nomor ini. 

Terimakasih</pre>
        </div>

        <!-- Template 2: Receipt -->
        <div x-show="formatTab === 'receipt'" class="bg-slate-50 border border-slate-200 rounded-2xl p-3.5 space-y-1.5" style="display: none;">
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Format Pesan Nota Elektronik:</span>
          <pre class="font-mono text-slate-800 bg-white p-3 rounded-xl border border-slate-200 text-[11px] leading-relaxed whitespace-pre-wrap select-all max-h-56 overflow-y-auto">*BANTERPOOL* 

NOTA ELEKTRONIK
{{ url('/struk/{id}') }}

Yth. Bapak / Ibu
{nama}
{no pelanggan}
null
Area : {area/daerah}

*PEMBAYARAN*
Tanggal Bayar :{{ date('d M Y') }} | {{ date('H:i') }} WIB

Tagihan Bulan : 
- {{ date('M Y') }}
Jenis Paket : {JENIS PAKET}
Harga Paket : {HARGA PAKET}
*Sudah Termasuk Pajak
Diskon  : Rp. 0
Total Biaya : {TOTAL BIAYA}
Ket : *LUNAS* 
Admin By : Atun

terimakasih telah menggunakan layanan internet dari BANTERPOOL

SUPPORT BY :
Admin BANTERPOOL</pre>
        </div>

        <div class="flex items-center justify-between pt-2 border-t border-slate-100">
          <a href="{{ route('admin.whatsapp') }}" class="text-brand hover:underline font-bold text-xs flex items-center gap-1">
            <span>Buka Konfigurasi WhatsApp Gateway</span> &rarr;
          </a>
          <button type="button" @click="openWaFormatModal = false" class="px-4 py-2 border border-slate-300 rounded-xl text-slate-700 font-bold hover:bg-slate-50 transition">
            Tutup
          </button>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection
