<!DOCTYPE html>
<html lang="id" class="h-full" style="color-scheme: dark;">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>ISP Field Technician Portal — Daily Report</title>

  <!-- Google Fonts: Inter & JetBrains Mono -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
  
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  @vite(['resources/css/app.css', 'resources/js/app.js'])

  <style>
    *, *::before, *::after { box-sizing: border-box; }
    [x-cloak] { display: none !important; }

    :root {
      color-scheme: dark;
      --bg:        #090d16;
      --surface:   #0f172a;
      --surface-2: #141f36;
      --border:    rgba(255, 255, 255, 0.08);
      --border-md: rgba(255, 255, 255, 0.14);
      --text:      #f1f5f9;
      --subtle:    #94a3b8;
      --muted:     #64748b;
      --accent:    #3b82f6;
      --accent-hover: #2563eb;
      --green:     #10b981;
      --amber:     #f59e0b;
      --red:       #ef4444;
      --indigo:    #818cf8;
    }

    html, body {
      min-height: 100%;
      background-color: var(--bg);
      color: var(--text);
    }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
    }

    /* ── Dropdown Text Visibility & Styling ── */
    select,
    select.inp,
    select.input-dark {
      background-color: #0f172a !important;
      color: #f8fafc !important;
      appearance: none;
      -webkit-appearance: none;
      -moz-appearance: none;
    }

    select option,
    select.inp option,
    select.input-dark option {
      background-color: #0f172a !important;
      color: #f8fafc !important;
      padding: 10px 14px;
    }

    select optgroup {
      background-color: #090d16 !important;
      color: #94a3b8 !important;
      font-weight: 700;
    }

    /* ── Custom Scrollbar ── */
    ::-webkit-scrollbar { width: 5px; height: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.12); border-radius: 9999px; }
    ::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.25); }

    /* ── Card Styling ── */
    .card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 16px;
    }
    .card-lg { border-radius: 22px; }

    /* ── Inputs ── */
    .inp {
      width: 100%;
      background: #0b1222;
      border: 1px solid var(--border-md);
      border-radius: 10px;
      padding: 10px 14px;
      color: #f8fafc;
      font-size: 13px;
      font-family: 'Inter', sans-serif;
      transition: border-color .15s, box-shadow .15s, background-color .15s;
      outline: none;
    }
    .inp:focus {
      border-color: var(--accent);
      background-color: #0d162a;
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
    }
    .inp::placeholder { color: #64748b; }

    /* ── Labels ── */
    .lbl {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 11px;
      font-weight: 700;
      color: #94a3b8;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 6px;
    }

    /* ── Section Title ── */
    .section-title {
      font-size: 11px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: #94a3b8;
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 16px;
    }
    .section-title::after {
      content: '';
      flex: 1;
      height: 1px;
      background: var(--border);
    }

    /* ── Metric Card ── */
    .metric-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 20px;
      position: relative;
      overflow: hidden;
      transition: border-color .2s, transform .15s;
    }
    .metric-card:hover {
      border-color: rgba(255, 255, 255, 0.16);
      transform: translateY(-1px);
    }

    /* ── Table ── */
    .tbl { width: 100%; border-collapse: collapse; font-size: 12px; }
    .tbl thead th {
      padding: 12px 14px;
      text-align: left;
      font-size: 10px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: .08em;
      color: #94a3b8;
      border-bottom: 1px solid var(--border);
      background: #090e1a;
    }
    .tbl tbody td {
      padding: 13px 14px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.04);
      vertical-align: middle;
      color: #cbd5e1;
    }
    .tbl tbody tr:hover td { background: rgba(255, 255, 255, 0.02); }
    .tbl tbody tr:last-child td { border-bottom: none; }

    /* ── Badges ── */
    .badge {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 3px 9px;
      border-radius: 9999px;
      font-size: 10px;
      font-weight: 700;
    }
    .badge-green { background: rgba(16, 185, 129, 0.12); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.25); }
    .badge-amber { background: rgba(245, 158, 11, 0.12); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.25); }
    .badge-blue  { background: rgba(59, 130, 246, 0.12); color: #93c5fd; border: 1px solid rgba(59, 130, 246, 0.25); }
    .badge-slate { background: rgba(100, 116, 139, 0.15); color: #94a3b8; border: 1px solid rgba(100, 116, 139, 0.25); }
    .badge-indigo { background: rgba(129, 140, 248, 0.12); color: #a5b4fc; border: 1px solid rgba(129, 140, 248, 0.25); }

    /* ── Buttons ── */
    .btn-primary {
      background: var(--accent);
      color: #ffffff;
      border: none;
      border-radius: 12px;
      padding: 12px 24px;
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
      transition: background .15s, transform .1s;
    }
    .btn-primary:hover { background: var(--accent-hover); }
    .btn-primary:active { transform: scale(.98); }

    .btn-ghost {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid var(--border-md);
      color: #cbd5e1;
      border-radius: 10px;
      padding: 8px 14px;
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      transition: background .15s, color .15s, border-color .15s;
    }
    .btn-ghost:hover { background: rgba(255, 255, 255, 0.09); color: #ffffff; border-color: rgba(255, 255, 255, 0.25); }

    .icon-btn {
      width: 36px; height: 36px;
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid var(--border-md);
      color: #94a3b8;
      cursor: pointer;
      transition: background .15s, color .15s;
    }
    .icon-btn:hover { background: rgba(255, 255, 255, 0.1); color: #ffffff; }

    /* ── Tabs ── */
    .tab-btn {
      padding: 10px 6px;
      font-size: 12px;
      font-weight: 700;
      color: #64748b;
      background: none;
      border: none;
      border-bottom: 2px solid transparent;
      cursor: pointer;
      display: flex; align-items: center; gap: 8px;
      transition: color .15s, border-color .15s;
    }
    .tab-btn.active { color: #ffffff; border-bottom-color: var(--accent); }

    /* ── Material Item Card ── */
    .mat-card {
      background: #0c1322;
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 12px 14px;
      transition: border-color .15s, background-color .15s;
    }
    .mat-card:hover {
      border-color: rgba(255, 255, 255, 0.14);
      background-color: #0f182d;
    }
    .mat-card.checked {
      border-color: rgba(59, 130, 246, 0.35);
      background-color: rgba(59, 130, 246, 0.05);
    }

    .dot-pulse {
      width: 7px; height: 7px; border-radius: 50%;
      animation: pulse 1.8s ease-in-out infinite;
    }
    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: .35; }
    }
  </style>
</head>

<body class="flex flex-col min-h-screen"
      x-data="{
        openSettings: false,
        openUserMenu: false,
        openAddMaterialModal: false,
        isFullscreen: false,
        activeTab: '{{ $tab === 'cash' ? 'cash' : 'jobs' }}',
        gsWebhook: localStorage.getItem('gs_webhook_url') || '',
        isSyncing: false,

        // Form fields
        reportDate: '{{ now()->format('Y-m-d') }}',
        technicianName: '{{ auth()->user()->name ?? 'Budi Setiawan' }}',
        partnerCompany: 'BANTERPOL',
        jobType: 'Pasang Baru (PSB / Installation)',
        customerName: 'Bpk Herman / CST-109',
        customerPhone: '081234567890',
        customerAddress: 'Jl. Merdeka No. 45, Purwokerto',
        pppoeUsername: 'herman_net',
        odpPort: 'ODP-TMB-04 / Port 2',
        rxPower: '-19.5',
        ontSn: 'ZTEGC819A2B',
        additionalMaterials: '',
        transactionType: 'Biaya Pasang Baru (PSB)',
        cashAmount: 250000,
        paymentMethod: 'Tunai (Cash)',
        cashDepositStatus: 'Belum Disetor',
        selectedOrderId: '',

        // Master Stok Barang & Kategori dari POV Admin
        stockCategories: {{ Js::from($stockCategories) }},
        stockMaterials: {{ Js::from($stockMaterials) }},

        // Active filter category in catalog
        selectedCategoryFilter: 'all',

        // Form tambah barang baru (manual / dari stok admin)
        addMode: 'stock', // 'stock' or 'custom'
        newMat: {
          category: '',
          materialId: '',
          name: '',
          unit: 'pcs',
          price: 0,
          qty: 1
        },

        // Material Catalog List
        catalog: {{ Js::from(collect($defaultCatalog)->map(function($i) {
            return [
                'key' => $i['key'],
                'name' => $i['name'],
                'category' => $i['category'],
                'price' => (float)$i['price'],
                'unit' => $i['unit'],
                'qty' => (float)$i['default_qty'],
                'checked' => (bool)$i['checked'],
                'isEditing' => false,
            ];
        })) }},

        // Total Biaya Material
        get totalMaterialCost() {
          return this.catalog.reduce((total, item) => {
            if (item.checked && Number(item.qty) > 0) {
              return total + (Number(item.price) * Number(item.qty));
            }
            return total;
          }, 0);
        },

        // Format Rupiah
        rp(v) {
          return 'Rp ' + Number(v || 0).toLocaleString('id-ID');
        },

        // Filtered catalog by category
        get filteredCatalog() {
          if (this.selectedCategoryFilter === 'all') {
            return this.catalog;
          }
          return this.catalog.filter(i => i.category === this.selectedCategoryFilter);
        },

        // Filtered stock materials based on selected newMat.category
        get filteredStockMaterials() {
          if (!this.newMat.category) return this.stockMaterials;
          return this.stockMaterials.filter(m => m.category === this.newMat.category);
        },

        // On select stock item in modal
        onSelectStockItem() {
          const found = this.stockMaterials.find(m => String(m.id) === String(this.newMat.materialId));
          if (found) {
            this.newMat.name = found.name;
            this.newMat.category = found.category || this.newMat.category;
            this.newMat.unit = found.unit ? found.unit.toLowerCase() : 'pcs';
            if (found.unit_price && Number(found.unit_price) > 0) {
              this.newMat.price = Number(found.unit_price);
            }
          }
        },

        // Tambah Barang ke Katalog (Manual / Dari Stok)
        submitAddMaterial() {
          if (!this.newMat.name.trim()) {
            alert('Nama barang tidak boleh kosong.');
            return;
          }
          if (Number(this.newMat.qty) <= 0) {
            this.newMat.qty = 1;
          }

          const newKey = 'item_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
          this.catalog.push({
            key: newKey,
            name: this.newMat.name.trim(),
            category: this.newMat.category || 'Material Pembantu & Instalasi',
            price: Number(this.newMat.price) || 0,
            unit: this.newMat.unit || 'pcs',
            qty: Number(this.newMat.qty) || 1,
            checked: true,
            isEditing: false
          });

          // Reset newMat
          this.newMat = {
            category: '',
            materialId: '',
            name: '',
            unit: 'pcs',
            price: 0,
            qty: 1
          };
          this.openAddMaterialModal = false;
        },

        // Hapus item dari katalog
        deleteMaterial(index) {
          if (confirm('Hapus barang ini dari daftar laporan?')) {
            this.catalog.splice(index, 1);
          }
        },

        // Toggle edit status
        toggleEditMaterial(item) {
          item.isEditing = !item.isEditing;
        },

        // Ringkasan Material String
        get materialSummary() {
          const parts = [];
          this.catalog.forEach(item => {
            if (item.checked && Number(item.qty) > 0) {
              const subtotal = Number(item.price) * Number(item.qty);
              parts.push(item.name + ' (' + item.qty + ' ' + item.unit + ' - ' + this.rp(subtotal) + ')');
            }
          });
          if (this.additionalMaterials && this.additionalMaterials.trim() !== '') {
            parts.push(this.additionalMaterials.trim());
          }
          return parts.length > 0 ? parts.join(', ') : 'Belum ada material dipilih';
        },

        // JSON payload for submission
        get materialsJsonPayload() {
          const selected = this.catalog
            .filter(i => i.checked && Number(i.qty) > 0)
            .map(i => ({
              name: i.name,
              category: i.category,
              price: Number(i.price),
              qty: Number(i.qty),
              unit: i.unit,
              total: Number(i.price) * Number(i.qty)
            }));
          return JSON.stringify(selected);
        },

        // Quick auto-fill from existing order ticket
        autofillFromOrder(order) {
          if (!order) return;
          this.selectedOrderId = order.id;
          this.customerName = order.customer_name + ' / ' + (order.order_number || '');
          this.customerPhone = order.customer_phone || '';
          this.customerAddress = order.address || '';
          this.pppoeUsername = order.pppoe || '';
          this.odpPort = order.assigned_odp || '';
          this.rxPower = order.opm_dbm ? order.opm_dbm.replace(/[^0-9.\-]/g, '') : '-19.5';
          this.ontSn = order.ont_sn || 'ZTEGC' + Math.floor(100000 + Math.random() * 900000);
        },

        // Fullscreen Toggle
        toggleFullscreen() {
          if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen().then(() => this.isFullscreen = true).catch(()=>{});
          } else {
            if (document.exitFullscreen) {
              document.exitFullscreen().then(() => this.isFullscreen = false).catch(()=>{});
            }
          }
        },

        // Refresh / Sync Demo
        refreshSync() {
          this.isSyncing = true;
          setTimeout(() => {
            this.isSyncing = false;
            alert('Sinkronisasi Berhasil! Data terhubung dengan Stok Barang POV Admin.');
          }, 800);
        },

        // Simpan Settings GS
        saveSettings() {
          localStorage.setItem('gs_webhook_url', this.gsWebhook);
          this.openSettings = false;
          alert('Pengaturan Webhook Spreadsheet tersimpan!');
        }
      }"
      @fullscreenchange.window="isFullscreen = !!document.fullscreenElement">

  <!-- ============================================== -->
  <!-- 1. TOPBAR / HEADER NAVIGATION                  -->
  <!-- ============================================== -->
  <header class="sticky top-0 z-40 border-b border-white/[0.08]" style="background: rgba(9, 13, 22, 0.92); backdrop-filter: blur(16px);">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16 gap-4">

      <!-- Left Branding -->
      <div class="flex items-center gap-3 shrink-0">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center bg-blue-600/20 border border-blue-500/30 text-blue-400 shadow-sm">
          <i class="fa-solid fa-wifi text-sm"></i>
        </div>
        <div>
          <div class="flex items-center gap-2">
            <h1 class="font-bold text-sm sm:text-base text-white tracking-tight">
              ISP Field <span class="text-blue-400">Technician</span> Portal
            </h1>
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-600/20 text-blue-300 border border-blue-500/30">
              v2.6
            </span>
          </div>
          <p class="text-[11px] text-slate-400 hidden sm:block">
            Daily Report &amp; Sinkronisasi Stok Barang Lapangan
          </p>
        </div>
      </div>

      <!-- Right Action Controls -->
      <div class="flex items-center gap-2.5">
        
        <!-- Google Sheets Demo Mode Badge -->
        <button type="button" @click="openSettings = true"
                class="hidden md:flex items-center gap-2 btn-ghost text-xs"
                title="Status Sinkronisasi Spreadsheet">
          <span class="dot-pulse bg-amber-400"></span>
          <span>Google Sheets: Demo Mode</span>
        </button>

        <!-- Sync Button -->
        <button type="button" @click="refreshSync()" class="icon-btn" title="Sinkronkan dengan Database & Stok">
          <i class="fa-solid fa-rotate text-xs" :class="isSyncing ? 'fa-spin text-blue-400' : ''"></i>
        </button>

        <!-- Fullscreen Button -->
        <button type="button" @click="toggleFullscreen()" class="icon-btn hidden sm:flex"
                :title="isFullscreen ? 'Keluar Mode Layar Penuh' : 'Mode Layar Penuh (Fullscreen)'">
          <i class="fa-solid text-xs" :class="isFullscreen ? 'fa-compress text-blue-400' : 'fa-expand'"></i>
        </button>

        <!-- Settings Button -->
        <button type="button" @click="openSettings = true" class="icon-btn" title="Pengaturan Integrasi">
          <i class="fa-solid fa-sliders text-xs"></i>
        </button>

        <!-- User Menu Avatar Dropdown -->
        <div class="relative" @click.outside="openUserMenu = false">
          <button type="button" @click="openUserMenu = !openUserMenu"
                  class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-bold text-white shadow-sm border border-white/10 transition"
                  style="background: linear-gradient(135deg, #2563eb, #6366f1);"
                  title="Menu Pengguna & Switch POV">
            {{ strtoupper(substr(auth()->user()->name ?? 'TK', 0, 2)) }}
          </button>

          <!-- Dropdown Popup -->
          <div x-show="openUserMenu" x-cloak
               x-transition:enter="transition ease-out duration-100"
               x-transition:enter-start="opacity-0 scale-95"
               x-transition:enter-end="opacity-100 scale-100"
               class="absolute right-0 top-11 w-64 card p-2 z-50 text-xs shadow-2xl divide-y divide-white/[0.06]">
            
            <div class="px-3 py-2">
              <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Masuk Sebagai</span>
              <span class="font-bold text-white block truncate text-sm">{{ auth()->user()->name }}</span>
              <span class="text-[11px] text-slate-400 font-mono block truncate">{{ auth()->user()->email }}</span>
            </div>

            <div class="py-1.5 space-y-0.5">
              @if(auth()->user()->isAdmin())
                <a href="{{ route('admin.dashboard') }}"
                   class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-300 hover:bg-white/5 hover:text-white transition">
                  <i class="fa-solid fa-shield-halved w-4 text-center text-rose-400"></i>
                  <span class="font-medium">Panel NOC Admin</span>
                </a>
              @endif

              <a href="{{ route('teknisi.dashboard') }}"
                 class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-300 hover:bg-white/5 hover:text-amber-300 transition">
                <i class="fa-solid fa-screwdriver-wrench w-4 text-center text-amber-400"></i>
                <span class="font-medium">Portal Teknisi Lapangan</span>
              </a>

              <a href="{{ route('kolektor.dashboard') }}"
                 class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-300 hover:bg-white/5 hover:text-emerald-300 transition">
                <i class="fa-solid fa-money-bill-wave w-4 text-center text-emerald-400"></i>
                <span class="font-medium">Portal Kolektor Lapangan</span>
              </a>
            </div>

            <div class="pt-1.5">
              <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-rose-400 hover:bg-rose-500/10 hover:text-rose-300 transition text-left font-medium">
                  <i class="fa-solid fa-arrow-right-from-bracket w-4 text-center"></i>
                  <span>Keluar / Logout</span>
                </button>
              </form>
            </div>

          </div>
        </div>

      </div>
    </div>
  </header>

  <!-- ============================================== -->
  <!-- 2. MAIN CONTENT CONTAINER                      -->
  <!-- ============================================== -->
  <main class="flex-1 max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    <!-- Flash Messages -->
    @if(session('success'))
      <div class="flex items-center gap-3 p-4 rounded-xl text-sm bg-emerald-950/60 border border-emerald-500/30 text-emerald-300"
           x-data="{ show: true }" x-show="show">
        <i class="fa-solid fa-circle-check text-emerald-400 shrink-0 text-base"></i>
        <span class="flex-1 font-medium">{{ session('success') }}</span>
        <button type="button" @click="show = false" class="text-emerald-400 hover:text-white">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>
    @endif

    @if($errors->any())
      <div class="p-4 rounded-xl text-sm bg-rose-950/60 border border-rose-500/30 text-rose-300"
           x-data="{ show: true }" x-show="show">
        <div class="flex items-center justify-between mb-2">
          <span class="font-bold flex items-center gap-2 text-rose-400">
            <i class="fa-solid fa-triangle-exclamation"></i> Mohon periksa kembali formulir:
          </span>
          <button type="button" @click="show = false" class="text-rose-400 hover:text-white">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
        <ul class="space-y-1 list-disc list-inside text-xs text-rose-200">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <!-- ============================================== -->
    <!-- 3. KPI METRIC CARDS                            -->
    <!-- ============================================== -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">

      <!-- Total Pekerjaan Hari Ini -->
      <div class="metric-card">
        <div class="flex items-center justify-between mb-3">
          <span class="lbl mb-0">Total Pekerjaan Hari Ini</span>
          <div class="w-7 h-7 rounded-lg flex items-center justify-center bg-blue-500/10 text-blue-400">
            <i class="fa-solid fa-briefcase text-xs"></i>
          </div>
        </div>
        <div class="flex items-baseline justify-between mt-2">
          <span class="text-3xl font-extrabold text-white tracking-tight">{{ $totalJobsToday }}</span>
          <span class="text-xs font-bold text-blue-400 flex items-center gap-1">
            <i class="fa-solid fa-arrow-trend-up text-[10px]"></i> +{{ $totalJobsToday }}
          </span>
        </div>
        <p class="text-[11px] text-slate-400 mt-2">Order instalasi &amp; tiket lapangan</p>
      </div>

      <!-- Kas Terkumpul (Teknisi) -->
      <div class="metric-card">
        <div class="flex items-center justify-between mb-3">
          <span class="lbl mb-0">Kas Terkumpul (Teknisi)</span>
          <div class="w-7 h-7 rounded-lg flex items-center justify-center bg-emerald-500/10 text-emerald-400">
            <i class="fa-solid fa-wallet text-xs"></i>
          </div>
        </div>
        <div class="flex items-baseline justify-between mt-2">
          <span class="text-2xl sm:text-3xl font-extrabold text-emerald-400 tracking-tight">
            Rp {{ number_format($cashCollectedToday, 0, ',', '.') }}
          </span>
        </div>
        <p class="text-[11px] text-slate-400 mt-2">Tunai, QRIS &amp; transfer hari ini</p>
      </div>

      <!-- Perlu Penyetoran / Pending -->
      <div class="metric-card">
        <div class="flex items-center justify-between mb-3">
          <span class="lbl mb-0">Perlu Penyetoran / Pending</span>
          <div class="w-7 h-7 rounded-lg flex items-center justify-center bg-amber-500/10 text-amber-400">
            <i class="fa-regular fa-clock text-xs"></i>
          </div>
        </div>
        <div class="flex items-baseline justify-between mt-2">
          <span class="text-3xl font-extrabold text-white tracking-tight">{{ $pendingDepositCount }}</span>
          <span class="badge badge-amber">Belum Setor</span>
        </div>
        <p class="text-[11px] text-slate-400 mt-2">Menunggu konfirmasi setor ke admin</p>
      </div>

      <!-- Est. Biaya Material (Hari Ini) -->
      <div class="metric-card">
        <div class="flex items-center justify-between mb-3">
          <span class="lbl mb-0">Est. Biaya Material (Hari Ini)</span>
          <div class="w-7 h-7 rounded-lg flex items-center justify-center bg-indigo-500/10 text-indigo-400">
            <i class="fa-solid fa-boxes-stacked text-xs"></i>
          </div>
        </div>
        <div class="flex items-baseline justify-between mt-2">
          <span class="text-2xl sm:text-3xl font-extrabold text-indigo-300 tracking-tight">
            Rp {{ number_format($materialCostToday, 0, ',', '.') }}
          </span>
        </div>
        <p class="text-[11px] text-slate-400 mt-2">Estimasi pemakaian material harian</p>
      </div>

    </div>

    <!-- ============================================== -->
    <!-- 4. FORM INPUT LAPORAN & PEMBUKUAN              -->
    <!-- ============================================== -->
    <div class="card card-lg p-6 sm:p-8">

      <!-- Form Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-5 border-b border-white/[0.08] mb-6">
        <div>
          <h2 class="text-base sm:text-lg font-bold text-white flex items-center gap-2.5">
            <i class="fa-solid fa-pen-to-square text-blue-400"></i>
            <span>Input Laporan & Pembukuan</span>
          </h2>
          <p class="text-xs text-slate-400 mt-0.5">
            Pencatatan aktivitas pekerjaan, kredensial ONT, biaya material & kas lapangan
          </p>
        </div>

        <div class="flex items-center gap-2">
          <!-- Quick Autofill from Recent Tickets -->
          @if($recentOrders->isNotEmpty())
            <div class="relative" x-data="{ openPick: false }">
              <button type="button" @click="openPick = !openPick"
                      class="btn-ghost flex items-center gap-2 text-xs">
                <i class="fa-solid fa-bolt text-amber-400"></i>
                <span>Pilih Tiket Pemasangan</span>
                <i class="fa-solid fa-chevron-down text-[10px] ml-1"></i>
              </button>
              <div x-show="openPick" @click.outside="openPick = false" x-cloak
                   class="absolute right-0 z-30 mt-2 w-72 card p-2 text-xs divide-y divide-white/[0.06] max-h-64 overflow-y-auto shadow-2xl">
                @foreach($recentOrders as $ro)
                  <button type="button" @click="autofillFromOrder({{ Js::from($ro) }}); openPick = false"
                          class="w-full text-left p-2.5 rounded-lg hover:bg-white/5 transition flex flex-col gap-0.5">
                    <span class="font-bold text-white">{{ $ro->order_number }} — {{ $ro->customer_name }}</span>
                    <span class="text-[10px] text-slate-400 truncate">{{ $ro->address }}</span>
                  </button>
                @endforeach
              </div>
            </div>
          @endif
        </div>
      </div>

      <!-- Form Element -->
      <form action="{{ route('daily-report.store') }}" method="POST" class="space-y-6">
        @csrf
        <input type="hidden" name="order_id" :value="selectedOrderId">
        <input type="hidden" name="materials_json" :value="materialsJsonPayload">
        <input type="hidden" name="material_total_cost" :value="totalMaterialCost">

        <!-- Baris 1: Tanggal & Nama Teknisi -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
          <div>
            <label class="lbl">
              <i class="fa-regular fa-calendar text-slate-400"></i> Hari & Tanggal
            </label>
            <input type="date" name="report_date" x-model="reportDate" required class="inp">
          </div>

          <div>
            <label class="lbl">
              <i class="fa-solid fa-helmet-safety text-slate-400"></i> Nama Teknisi
            </label>
            <input type="text" name="technician_name" x-model="technicianName" required placeholder="Nama Teknisi Lapangan" class="inp">
          </div>
        </div>

        <!-- Baris 2: Mitra Perusahaan & Tipe Pekerjaan (Dropdown Fixed Visibility) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
          <div>
            <label class="lbl">
              <i class="fa-solid fa-building text-slate-400"></i> Perusahaan / PT Mitra (ISP)
            </label>
            <div class="relative">
              <select name="partner_company" x-model="partnerCompany" required
                      class="inp pr-9 font-medium"
                      style="background-color: #0f172a; color: #f8fafc;">
                @foreach($partners as $p)
                  <option value="{{ $p }}" class="bg-slate-900 text-slate-100 py-1.5" style="background-color: #0f172a; color: #f8fafc;">
                    {{ $p }}
                  </option>
                @endforeach
              </select>
              <i class="fa-solid fa-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
            </div>
          </div>

          <div>
            <label class="lbl">
              <i class="fa-solid fa-wrench text-slate-400"></i> Tipe Pekerjaan
            </label>
            <div class="relative">
              <select name="job_type" x-model="jobType" required
                      class="inp pr-9 font-medium"
                      style="background-color: #0f172a; color: #f8fafc;">
                @foreach($jobTypes as $jt)
                  <option value="{{ $jt }}" class="bg-slate-900 text-slate-100 py-1.5" style="background-color: #0f172a; color: #f8fafc;">
                    {{ $jt }}
                  </option>
                @endforeach
              </select>
              <i class="fa-solid fa-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
            </div>
          </div>
        </div>

        <!-- ========================================== -->
        <!-- SECTION: INFORMASI PELANGGAN & KREDENSIAL  -->
        <!-- ========================================== -->
        <div class="pt-2">
          <p class="section-title">
            <i class="fa-regular fa-id-card text-blue-400"></i>
            <span>Informasi Pelanggan & Kredensial</span>
          </p>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
              <label class="lbl">Nama Pelanggan / ID</label>
              <input type="text" name="customer_name" x-model="customerName" required placeholder="Bpk Herman / CST-109" class="inp">
            </div>

            <div>
              <label class="lbl">Nomor HP / WhatsApp</label>
              <input type="text" name="customer_phone" x-model="customerPhone" placeholder="08xxxxxxxxxx" class="inp">
            </div>

            <div class="sm:col-span-2">
              <label class="lbl">Alamat Lengkap</label>
              <input type="text" name="customer_address" x-model="customerAddress" placeholder="Jl. Merdeka No. 45, Purwokerto" class="inp">
            </div>

            <div>
              <label class="lbl">Secret PPPoE (Username)</label>
              <input type="text" name="pppoe_username" x-model="pppoeUsername" placeholder="herman_net"
                     class="inp font-mono" style="color: #93c5fd;">
            </div>

            <div>
              <label class="lbl">ID ODP & Port</label>
              <input type="text" name="odp_port" x-model="odpPort" placeholder="ODP-TMB-04 / Port 2"
                     class="inp font-mono" style="color: #93c5fd;">
            </div>
          </div>
        </div>

        <!-- ========================================== -->
        <!-- SECTION: PARAMETER TEKNIS & HARGA MATERIAL -->
        <!-- ========================================== -->
        <div class="pt-2">
          <p class="section-title">
            <i class="fa-solid fa-microchip text-indigo-400"></i>
            <span>Parameter Teknis & Harga Material</span>
          </p>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-5">
            <div>
              <label class="lbl">Rx Power (dBm)</label>
              <input type="text" name="rx_power" x-model="rxPower" placeholder="-19.5"
                     class="inp font-mono font-bold" style="color: #fbbf24;">
            </div>

            <div>
              <label class="lbl">SN Perangkat ONT</label>
              <input type="text" name="ont_sn" x-model="ontSn" placeholder="ZTEGC819A2B"
                     class="inp font-mono font-bold" style="color: #93c5fd;">
            </div>
          </div>

          <!-- KATALOG MATERIAL (Sinkron dengan Stok Admin + Edit Manual + Tambah Manual) -->
          <div class="p-5 rounded-2xl bg-[#090e1a] border border-white/[0.08] space-y-4">
            
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-3 border-b border-white/[0.06]">
              <div class="flex items-center gap-2">
                <i class="fa-solid fa-boxes-stacked text-blue-400"></i>
                <span class="font-bold text-white text-sm">Katalog Material & Harga Satuan</span>
                <span class="text-[10px] text-slate-400 hidden sm:inline">(Stok Barang POV Admin)</span>
              </div>

              <div class="flex items-center gap-2.5 flex-wrap">
                <!-- Total Biaya Badge -->
                <span class="text-xs font-bold text-emerald-400 bg-emerald-950/70 border border-emerald-500/30 px-3 py-1.5 rounded-xl">
                  Total Est: <span x-text="rp(totalMaterialCost)"></span>
                </span>

                <!-- Tambah Barang Manual Button -->
                <button type="button" @click="openAddMaterialModal = true"
                        class="btn-ghost flex items-center gap-1.5 text-xs py-1.5 px-3 border-blue-500/40 text-blue-300 hover:text-white hover:bg-blue-600/20">
                  <i class="fa-solid fa-plus text-[11px]"></i>
                  <span>Tambah Barang Manual</span>
                </button>
              </div>
            </div>

            <!-- Category Filter Bar (Samakan dengan Stok Admin) -->
            <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs">
              <span class="text-[11px] font-bold text-slate-400 shrink-0 uppercase tracking-wider">
                Kategori:
              </span>
              <button type="button" @click="selectedCategoryFilter = 'all'"
                      class="px-2.5 py-1 rounded-lg text-xs font-semibold whitespace-nowrap transition"
                      :class="selectedCategoryFilter === 'all' ? 'bg-blue-600 text-white' : 'bg-white/5 text-slate-400 hover:text-white'">
                Semua Kategori ({{ count($defaultCatalog) }}+)
              </button>
              <template x-for="cat in stockCategories" :key="cat">
                <button type="button" @click="selectedCategoryFilter = cat"
                        class="px-2.5 py-1 rounded-lg text-xs font-semibold whitespace-nowrap transition"
                        :class="selectedCategoryFilter === cat ? 'bg-blue-600 text-white' : 'bg-white/5 text-slate-400 hover:text-white'"
                        x-text="cat">
                </button>
              </template>
            </div>

            <!-- List of Materials (Editable in-place) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-1">
              <template x-for="(item, index) in filteredCatalog" :key="item.key">
                <div class="mat-card flex flex-col justify-between gap-2.5"
                     :class="item.checked ? 'checked' : ''">
                  
                  <!-- Normal View (When not in edit mode) -->
                  <div x-show="!item.isEditing">
                    <div class="flex items-start justify-between gap-2">
                      
                      <!-- Checkbox & Name -->
                      <label class="flex items-start gap-3 cursor-pointer flex-1 min-w-0 select-none">
                        <input type="checkbox" x-model="item.checked"
                               class="w-4 h-4 rounded text-blue-600 bg-slate-900 border-slate-700 mt-0.5 accent-blue-600 shrink-0">
                        <div class="min-w-0">
                          <p class="text-xs font-bold truncate leading-tight"
                             :class="item.checked ? 'text-white' : 'text-slate-300'"
                             x-text="item.name"></p>
                          <div class="flex items-center gap-2 mt-1 flex-wrap">
                            <span class="badge badge-slate text-[9px] py-0.5 px-2 font-medium truncate max-w-[170px]" x-text="item.category"></span>
                            <span class="text-[11px] font-mono" style="color: #94a3b8;" x-text="rp(item.price) + ' / ' + item.unit"></span>
                          </div>
                        </div>
                      </label>

                      <!-- Action Buttons: Edit & Delete -->
                      <div class="flex items-center gap-1 shrink-0">
                        <button type="button" @click="toggleEditMaterial(item)"
                                class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-400 hover:text-blue-400 hover:bg-white/5 transition"
                                title="Edit Manual Barang & Harga">
                          <i class="fa-solid fa-pen text-[10px]"></i>
                        </button>
                        <button type="button" @click="deleteMaterial(index)"
                                class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-500 hover:text-rose-400 hover:bg-rose-500/10 transition"
                                title="Hapus dari Daftar">
                          <i class="fa-regular fa-trash-can text-[10px]"></i>
                        </button>
                      </div>

                    </div>

                    <!-- Quantity Input & Subtotal (Visible when checked) -->
                    <div class="flex items-center justify-between pt-2 mt-2 border-t border-white/[0.04]" x-show="item.checked" x-cloak>
                      <span class="text-[11px] text-slate-400">
                        Subtotal: <strong class="text-emerald-400" x-text="rp(item.price * item.qty)"></strong>
                      </span>
                      <div class="flex items-center gap-1.5">
                        <span class="text-[10px] text-slate-400 font-bold uppercase">Qty:</span>
                        <input type="number" x-model="item.qty" min="1" step="any"
                               class="w-16 inp text-center py-1 px-1.5 text-xs font-bold font-mono">
                        <span class="text-[11px] text-slate-400 font-semibold" x-text="item.unit"></span>
                      </div>
                    </div>
                  </div>

                  <!-- Inline Edit View (When user clicks Edit Manual) -->
                  <div x-show="item.isEditing" x-cloak class="p-2.5 rounded-xl bg-slate-900 border border-blue-500/30 space-y-2.5">
                    <div class="flex items-center justify-between pb-1 border-b border-white/[0.06]">
                      <span class="text-[11px] font-bold text-blue-400 flex items-center gap-1">
                        <i class="fa-solid fa-pen-to-square"></i> Edit Manual Material
                      </span>
                      <button type="button" @click="toggleEditMaterial(item)"
                              class="text-[11px] font-bold text-emerald-400 hover:underline flex items-center gap-1">
                        <i class="fa-solid fa-check"></i> Selesai
                      </button>
                    </div>

                    <div>
                      <label class="text-[10px] font-bold text-slate-400 block mb-1">Nama Barang:</label>
                      <input type="text" x-model="item.name" class="inp py-1.5 text-xs">
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                      <div>
                        <label class="text-[10px] font-bold text-slate-400 block mb-1">Kategori:</label>
                        <select x-model="item.category" class="inp py-1.5 text-xs" style="background-color: #0f172a; color: #f8fafc;">
                          <template x-for="cat in stockCategories" :key="cat">
                            <option :value="cat" x-text="cat" class="bg-slate-900 text-slate-100" style="background-color: #0f172a; color: #f8fafc;"></option>
                          </template>
                        </select>
                      </div>

                      <div>
                        <label class="text-[10px] font-bold text-slate-400 block mb-1">Satuan:</label>
                        <input type="text" x-model="item.unit" placeholder="m, pcs, unit, roll" class="inp py-1.5 text-xs">
                      </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                      <div>
                        <label class="text-[10px] font-bold text-slate-400 block mb-1">Harga Satuan (Rp):</label>
                        <input type="number" x-model="item.price" min="0" step="500" class="inp py-1.5 text-xs font-mono">
                      </div>

                      <div>
                        <label class="text-[10px] font-bold text-slate-400 block mb-1">Jumlah (Qty):</label>
                        <input type="number" x-model="item.qty" min="1" step="any" class="inp py-1.5 text-xs font-mono">
                      </div>
                    </div>
                  </div>

                </div>
              </template>
            </div>

            <!-- Material Tambahan Lain Textfield -->
            <div>
              <input type="text" name="additional_materials" x-model="additionalMaterials"
                     placeholder="Material tambahan lain (contoh: Klem 5x, Isolasi 1x, Fisher 4x)"
                     class="inp text-xs">
            </div>

            <!-- Dynamic Rincian Preview Box -->
            <div class="p-3 rounded-xl bg-slate-950/80 border border-white/[0.06] text-xs leading-relaxed">
              <span class="text-blue-400 font-bold">Rincian: </span>
              <span class="text-slate-300 font-medium" x-text="materialSummary"></span>
            </div>

          </div>
        </div>

        <!-- ========================================== -->
        <!-- SECTION: PEMBUKUAN / TRANSAKSI LAPANGAN    -->
        <!-- ========================================== -->
        <div class="pt-2">
          <p class="section-title">
            <i class="fa-solid fa-money-check-dollar text-emerald-400"></i>
            <span>Pembukuan / Transaksi Lapangan</span>
          </p>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
              <label class="lbl">Jenis Transaksi</label>
              <div class="relative">
                <select name="transaction_type" x-model="transactionType" required
                        class="inp pr-9 font-medium"
                        style="background-color: #0f172a; color: #f8fafc;">
                  <option value="Biaya Pasang Baru (PSB)" class="bg-slate-900 text-slate-100" style="background-color: #0f172a; color: #f8fafc;">Biaya Pasang Baru (PSB)</option>
                  <option value="Biaya Perbaikan / Visit" class="bg-slate-900 text-slate-100" style="background-color: #0f172a; color: #f8fafc;">Biaya Perbaikan / Visit</option>
                  <option value="Penjualan Router / ONT Tambahan" class="bg-slate-900 text-slate-100" style="background-color: #0f172a; color: #f8fafc;">Penjualan Router / ONT Tambahan</option>
                  <option value="Penjualan Kabel FO Tambahan" class="bg-slate-900 text-slate-100" style="background-color: #0f172a; color: #f8fafc;">Penjualan Kabel FO Tambahan</option>
                  <option value="Bebas Biaya / Free Garansi" class="bg-slate-900 text-slate-100" style="background-color: #0f172a; color: #f8fafc;">Bebas Biaya / Free Garansi</option>
                </select>
                <i class="fa-solid fa-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
              </div>
            </div>

            <div>
              <label class="lbl">Nominal Kas (Rp)</label>
              <input type="number" name="cash_amount" x-model="cashAmount" required min="0" step="1000"
                     class="inp font-mono font-bold" style="color: #34d399;">
            </div>

            <div>
              <label class="lbl">Metode Bayar</label>
              <div class="relative">
                <select name="payment_method" x-model="paymentMethod" required
                        class="inp pr-9 font-medium"
                        style="background-color: #0f172a; color: #f8fafc;">
                  <option value="Tunai (Cash)" class="bg-slate-900 text-slate-100" style="background-color: #0f172a; color: #f8fafc;">💵 Tunai (Cash)</option>
                  <option value="QRIS" class="bg-slate-900 text-slate-100" style="background-color: #0f172a; color: #f8fafc;">📱 QRIS</option>
                  <option value="Transfer Bank" class="bg-slate-900 text-slate-100" style="background-color: #0f172a; color: #f8fafc;">🏦 Transfer Bank</option>
                  <option value="Non-Tunai / Bebas Biaya" class="bg-slate-900 text-slate-100" style="background-color: #0f172a; color: #f8fafc;">🆓 Non-Tunai / Bebas Biaya</option>
                </select>
                <i class="fa-solid fa-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
              </div>
            </div>

            <div>
              <label class="lbl">Status Setor Kas</label>
              <div class="relative">
                <select name="cash_deposit_status" x-model="cashDepositStatus" required
                        class="inp pr-9 font-medium"
                        style="background-color: #0f172a; color: #f8fafc;">
                  <option value="Belum Disetor" class="bg-slate-900 text-slate-100" style="background-color: #0f172a; color: #f8fafc;">⏳ Belum Disetor</option>
                  <option value="Sudah Disetor" class="bg-slate-900 text-slate-100" style="background-color: #0f172a; color: #f8fafc;">✅ Sudah Disetor</option>
                </select>
                <i class="fa-solid fa-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
              </div>
            </div>

            <div class="sm:col-span-2">
              <label class="lbl">Catatan Tambahan (Opsional)</label>
              <input type="text" name="notes" placeholder="Catatan pekerjaan atau kendala di lapangan" class="inp">
            </div>
          </div>
        </div>

        <!-- Submit Button -->
        <div class="pt-3">
          <button type="submit"
                  class="btn-primary w-full flex items-center justify-center gap-2.5 text-sm sm:text-base py-3.5 shadow-lg shadow-blue-600/25">
            <i class="fa-solid fa-paper-plane text-sm"></i>
            <span>Simpan & Kirim ke Spreadsheet</span>
          </button>
        </div>

      </form>
    </div>

    <!-- ============================================== -->
    <!-- 5. LOG PEKERJAAN & PEMBUKUAN (TABS & TABLE)    -->
    <!-- ============================================== -->
    <div class="card card-lg p-6 sm:p-8">

      <!-- Search & Action Bar -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-6">
        
        <!-- Search Input Form -->
        <form method="GET" action="{{ route('daily-report.index') }}" class="flex-1 flex items-center gap-2">
          <input type="hidden" name="tab" :value="activeTab">
          <div class="relative flex-1">
            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            <input type="text" name="q" value="{{ $search }}" placeholder="Cari Pelanggan, PPPoE, Alamat, atau Mitra..."
                   class="inp pl-9 text-xs">
          </div>

          <!-- Filter Job Type Dropdown -->
          <div class="relative w-48 sm:w-60">
            <select name="job_type" onchange="this.form.submit()"
                    class="inp pr-8 text-xs font-medium"
                    style="background-color: #0f172a; color: #f8fafc;">
              <option value="all" {{ $jobTypeFilter === 'all' ? 'selected' : '' }} class="bg-slate-900 text-slate-100" style="background-color: #0f172a; color: #f8fafc;">
                Semua Tipe Pekerjaan
              </option>
              @foreach($jobTypes as $jt)
                <option value="{{ $jt }}" {{ $jobTypeFilter === $jt ? 'selected' : '' }} class="bg-slate-900 text-slate-100" style="background-color: #0f172a; color: #f8fafc;">
                  {{ $jt }}
                </option>
              @endforeach
            </select>
            <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-[10px]"></i>
          </div>

          @if(!empty($search) || $jobTypeFilter !== 'all')
            <a href="{{ route('daily-report.index') }}" class="icon-btn shrink-0" title="Reset Filter">
              <i class="fa-solid fa-xmark text-xs"></i>
            </a>
          @endif
        </form>

        <!-- Export CSV Button -->
        <a href="{{ route('daily-report.export') }}" class="btn-ghost flex items-center gap-2 text-xs shrink-0">
          <i class="fa-solid fa-file-csv text-emerald-400 text-sm"></i>
          <span>Export Spreadsheet</span>
        </a>

      </div>

      <!-- Tabs Navigation -->
      <div class="flex items-center gap-6 border-b border-white/[0.08] mb-6">
        <button type="button" class="tab-btn" :class="activeTab === 'jobs' ? 'active' : ''" @click="activeTab = 'jobs'">
          <i class="fa-solid fa-wrench text-blue-400"></i>
          <span>Log Pekerjaan Teknis</span>
          <span class="badge" :class="activeTab === 'jobs' ? 'badge-blue' : 'badge-slate'">
            {{ $reports->total() }}
          </span>
        </button>

        <button type="button" class="tab-btn" :class="activeTab === 'cash' ? 'active' : ''" @click="activeTab = 'cash'">
          <i class="fa-solid fa-wallet text-emerald-400"></i>
          <span>Pembukuan Kas Lapangan</span>
          <span class="badge" :class="activeTab === 'cash' ? 'badge-green' : 'badge-slate'">
            {{ $cashTransactions->total() }}
          </span>
        </button>
      </div>

      <!-- TAB 1: Log Pekerjaan Teknis -->
      <div x-show="activeTab === 'jobs'" class="overflow-x-auto">
        <table class="tbl">
          <thead>
            <tr>
              <th>TGL &amp; MITRA</th>
              <th>PELANGGAN / ID</th>
              <th>TIPE PEKERJAAN</th>
              <th>RX POWER</th>
              <th>BIAYA MATERIAL</th>
              <th>KAS LAPANGAN</th>
              <th>STATUS SETOR</th>
              <th style="text-align: center;">AKSI</th>
            </tr>
          </thead>
          <tbody>
            @forelse($reports as $r)
              <tr>
                <td class="whitespace-nowrap">
                  <span class="font-bold text-white block">{{ $r->report_date ? $r->report_date->format('d/m/Y') : '-' }}</span>
                  <span class="text-[11px] text-blue-400 font-semibold block">{{ $r->partner_company }}</span>
                  <span class="text-[10px] text-slate-400 block">Oleh: {{ $r->technician_name }}</span>
                </td>

                <td>
                  <span class="font-bold text-white block">{{ $r->customer_name }}</span>
                  <span class="text-[11px] text-slate-400 block font-mono">{{ $r->pppoe_username ?: '-' }}</span>
                  <span class="text-[10px] text-slate-400 block truncate max-w-[200px]">{{ $r->customer_address }}</span>
                </td>

                <td class="whitespace-nowrap">
                  <span class="badge badge-slate">{{ $r->job_type }}</span>
                  <span class="text-[10px] text-slate-400 block mt-1 font-mono">{{ $r->odp_port ?: '-' }}</span>
                </td>

                <td class="whitespace-nowrap">
                  <span class="font-mono font-bold text-amber-300 block">{{ $r->rx_power ? $r->rx_power . ' dBm' : '-' }}</span>
                  <span class="text-[10px] text-slate-400 font-mono block">{{ $r->ont_sn ?: '-' }}</span>
                </td>

                <td class="whitespace-nowrap">
                  <span class="font-bold text-indigo-300 block">{{ $r->formatted_material_cost }}</span>
                  <span class="text-[10px] text-slate-400 block truncate max-w-[170px]" title="{{ $r->materials_summary_text }}">
                    {{ $r->materials_summary_text }}
                  </span>
                </td>

                <td class="whitespace-nowrap">
                  <span class="font-bold {{ $r->cash_amount > 0 ? 'text-emerald-400' : 'text-slate-400' }} block">
                    {{ $r->formatted_cash }}
                  </span>
                  <span class="text-[10px] text-slate-400 block">{{ $r->payment_method }}</span>
                </td>

                <td class="whitespace-nowrap">
                  @if($r->cash_amount > 0)
                    @if($r->cash_deposit_status === 'Sudah Disetor')
                      <span class="badge badge-green">Disetor</span>
                    @else
                      <span class="badge badge-amber">Belum Setor</span>
                    @endif
                  @else
                    <span class="text-slate-500 text-xs">—</span>
                  @endif
                </td>

                <td style="text-align: center;" class="whitespace-nowrap">
                  <div class="flex items-center justify-center gap-1.5">
                    @if($r->cash_amount > 0 && $r->cash_deposit_status !== 'Sudah Disetor')
                      <form action="{{ route('daily-report.setor', $r->id) }}" method="POST" onsubmit="return confirm('Tandai kas sebesar {{ $r->formatted_cash }} sudah disetor?')">
                        @csrf
                        <button type="submit" class="icon-btn w-8 h-8 text-emerald-400 hover:bg-emerald-500/20" title="Tandai Sudah Disetor">
                          <i class="fa-solid fa-check text-xs"></i>
                        </button>
                      </form>
                    @endif

                    <form action="{{ route('daily-report.destroy', $r->id) }}" method="POST" onsubmit="return confirm('Hapus data laporan harian ini?')">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="icon-btn w-8 h-8 text-rose-400 hover:bg-rose-500/20" title="Hapus Laporan">
                        <i class="fa-regular fa-trash-can text-xs"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center py-12 text-slate-400">
                  <i class="fa-regular fa-folder-open text-3xl mb-2 text-slate-500 block"></i>
                  <span>Belum ada data log pekerjaan teknis tercatat.</span>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>

        <div class="pt-4">
          {{ $reports->links() }}
        </div>
      </div>

      <!-- TAB 2: Pembukuan Kas Lapangan -->
      <div x-show="activeTab === 'cash'" x-cloak class="overflow-x-auto">
        <table class="tbl">
          <thead>
            <tr>
              <th>TGL &amp; JAM</th>
              <th>TEKNISI &amp; MITRA</th>
              <th>SUMBER / PELANGGAN</th>
              <th>JENIS TRANSAKSI</th>
              <th>METODE BAYAR</th>
              <th>NOMINAL KAS</th>
              <th>STATUS SETOR</th>
              <th style="text-align: center;">AKSI</th>
            </tr>
          </thead>
          <tbody>
            @forelse($cashTransactions as $c)
              <tr>
                <td class="whitespace-nowrap">
                  <span class="font-bold text-white block">{{ $c->report_date ? $c->report_date->format('d/m/Y') : '-' }}</span>
                  <span class="text-[10px] text-slate-400">{{ $c->created_at ? $c->created_at->format('H:i') : '' }} WIB</span>
                </td>

                <td class="whitespace-nowrap">
                  <span class="font-bold text-white block">{{ $c->technician_name }}</span>
                  <span class="text-[11px] text-blue-400">{{ $c->partner_company }}</span>
                </td>

                <td>
                  <span class="font-bold text-white block">{{ $c->customer_name }}</span>
                  <span class="text-[10px] text-slate-400 block">{{ $c->customer_phone ?: '-' }}</span>
                </td>

                <td class="whitespace-nowrap">
                  <span class="badge badge-slate">{{ $c->transaction_type }}</span>
                </td>

                <td class="whitespace-nowrap">
                  <span class="text-xs font-semibold text-slate-300">{{ $c->payment_method }}</span>
                </td>

                <td class="whitespace-nowrap">
                  <span class="font-bold text-emerald-400 text-sm">{{ $c->formatted_cash }}</span>
                </td>

                <td class="whitespace-nowrap">
                  @if($c->cash_deposit_status === 'Sudah Disetor')
                    <span class="badge badge-green">
                      <i class="fa-solid fa-check text-[9px]"></i> Sudah Disetor
                    </span>
                  @else
                    <span class="badge badge-amber">
                      <i class="fa-regular fa-clock text-[9px]"></i> Belum Disetor
                    </span>
                  @endif
                </td>

                <td style="text-align: center;" class="whitespace-nowrap">
                  @if($c->cash_deposit_status !== 'Sudah Disetor')
                    <form action="{{ route('daily-report.setor', $c->id) }}" method="POST" onsubmit="return confirm('Konfirmasi setor kas sebesar {{ $c->formatted_cash }}?')">
                      @csrf
                      <button type="submit" class="btn-ghost text-xs py-1 px-3 text-emerald-400 border-emerald-500/30 hover:bg-emerald-600/20">
                        Setor Kas
                      </button>
                    </form>
                  @else
                    <span class="text-slate-500 text-xs italic">Selesai</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center py-12 text-slate-400">
                  <i class="fa-solid fa-wallet text-3xl mb-2 text-slate-500 block"></i>
                  <span>Tidak ada catatan kas lapangan.</span>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>

        <div class="pt-4">
          {{ $cashTransactions->links() }}
        </div>
      </div>

    </div>

  </main>

  <!-- ============================================== -->
  <!-- MODAL: TAMBAH BARANG MANUAL / DARI STOK ADMIN  -->
  <!-- ============================================== -->
  <div x-show="openAddMaterialModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm" @click="openAddMaterialModal = false"></div>
    <div class="relative card card-lg w-full max-w-lg p-6 text-slate-200 z-10 shadow-2xl">
      
      <div class="flex items-center justify-between pb-4 border-b border-white/[0.08]">
        <div>
          <h3 class="font-bold text-white text-base flex items-center gap-2">
            <i class="fa-solid fa-boxes-packing text-blue-400"></i>
            <span>Tambah Barang ke Laporan</span>
          </h3>
          <p class="text-xs text-slate-400 mt-0.5">
            Pilih dari Stok Barang POV Admin atau input manual
          </p>
        </div>
        <button type="button" @click="openAddMaterialModal = false" class="icon-btn w-8 h-8">
          <i class="fa-solid fa-xmark text-xs"></i>
        </button>
      </div>

      <!-- Mode Selector: Dari Stok Admin vs Input Manual Sepenuhnya -->
      <div class="flex items-center gap-2 mt-4 p-1 rounded-xl bg-slate-950/80 border border-white/[0.06]">
        <button type="button" @click="addMode = 'stock'"
                class="flex-1 py-1.5 rounded-lg text-xs font-bold transition flex items-center justify-center gap-1.5"
                :class="addMode === 'stock' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-400 hover:text-white'">
          <i class="fa-solid fa-database text-[10px]"></i>
          <span>Pilih dari Stok Admin</span>
        </button>
        <button type="button" @click="addMode = 'custom'"
                class="flex-1 py-1.5 rounded-lg text-xs font-bold transition flex items-center justify-center gap-1.5"
                :class="addMode === 'custom' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-400 hover:text-white'">
          <i class="fa-solid fa-keyboard text-[10px]"></i>
          <span>Ketik Manual Bebas</span>
        </button>
      </div>

      <div class="space-y-4 my-4">
        
        <!-- Opsi 1: Dropdown Stok Admin -->
        <template x-if="addMode === 'stock'">
          <div class="space-y-3.5">
            <div>
              <label class="lbl">Kategori Stok (POV Admin)</label>
              <div class="relative">
                <select x-model="newMat.category" class="inp pr-9 text-xs"
                        style="background-color: #0f172a; color: #f8fafc;">
                  <option value="" class="bg-slate-900 text-slate-100" style="background-color: #0f172a; color: #f8fafc;">-- Pilih Kategori --</option>
                  <template x-for="cat in stockCategories" :key="cat">
                    <option :value="cat" x-text="cat" class="bg-slate-900 text-slate-100" style="background-color: #0f172a; color: #f8fafc;"></option>
                  </template>
                </select>
                <i class="fa-solid fa-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
              </div>
            </div>

            <div>
              <label class="lbl">Pilih Barang dari Stok Admin</label>
              <div class="relative">
                <select x-model="newMat.materialId" @change="onSelectStockItem()" class="inp pr-9 text-xs"
                        style="background-color: #0f172a; color: #f8fafc;">
                  <option value="" class="bg-slate-900 text-slate-100" style="background-color: #0f172a; color: #f8fafc;">-- Pilih Barang (135+ item tersedia) --</option>
                  <template x-for="m in filteredStockMaterials" :key="m.id">
                    <option :value="m.id" x-text="m.name + ' (' + m.category + ' - Stok: ' + (m.stock || 0) + ' ' + m.unit + ')'"
                            class="bg-slate-900 text-slate-100" style="background-color: #0f172a; color: #f8fafc;"></option>
                  </template>
                </select>
                <i class="fa-solid fa-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
              </div>
            </div>
          </div>
        </template>

        <!-- Form fields: Nama, Satuan, Harga, Qty -->
        <div>
          <label class="lbl">Nama Barang</label>
          <input type="text" x-model="newMat.name" placeholder="Contoh: Modem ZTE F670L / Fast Connector" class="inp text-xs">
        </div>

        <template x-if="addMode === 'custom'">
          <div>
            <label class="lbl">Kategori Barang</label>
            <div class="relative">
              <select x-model="newMat.category" class="inp pr-9 text-xs" style="background-color: #0f172a; color: #f8fafc;">
                <option value="" class="bg-slate-900 text-slate-100" style="background-color: #0f172a; color: #f8fafc;">-- Pilih Kategori --</option>
                <template x-for="cat in stockCategories" :key="cat">
                  <option :value="cat" x-text="cat" class="bg-slate-900 text-slate-100" style="background-color: #0f172a; color: #f8fafc;"></option>
                </template>
              </select>
              <i class="fa-solid fa-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
            </div>
          </div>
        </template>

        <div class="grid grid-cols-3 gap-3">
          <div>
            <label class="lbl">Satuan</label>
            <input type="text" x-model="newMat.unit" placeholder="pcs, m, unit" class="inp text-xs">
          </div>

          <div>
            <label class="lbl">Harga Satuan (Rp)</label>
            <input type="number" x-model="newMat.price" min="0" step="500" class="inp text-xs font-mono">
          </div>

          <div>
            <label class="lbl">Jumlah (Qty)</label>
            <input type="number" x-model="newMat.qty" min="1" step="any" class="inp text-xs font-mono font-bold">
          </div>
        </div>

        <div class="p-3 rounded-xl bg-blue-950/20 border border-blue-500/20 text-[11px] text-blue-300">
          <i class="fa-solid fa-circle-info mr-1"></i>
          Barang yang ditambahkan akan otomatis diikutsertakan dalam kalkulasi total biaya dan laporan spreadsheet.
        </div>

      </div>

      <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-white/[0.08]">
        <button type="button" @click="openAddMaterialModal = false" class="btn-ghost text-xs">
          Batal
        </button>
        <button type="button" @click="submitAddMaterial()" class="btn-primary text-xs py-2 px-4 flex items-center gap-1.5">
          <i class="fa-solid fa-plus text-[10px]"></i>
          <span>Tambahkan ke Laporan</span>
        </button>
      </div>

    </div>
  </div>

  <!-- ============================================== -->
  <!-- MODAL: PENGATURAN SPREADSHEET & WEBHOOK        -->
  <!-- ============================================== -->
  <div x-show="openSettings" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm" @click="openSettings = false"></div>
    <div class="relative card card-lg w-full max-w-md p-6 text-slate-300 z-10 shadow-2xl">
      <div class="flex items-center justify-between pb-4 border-b border-white/[0.08]">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-lg bg-blue-600/20 text-blue-400 flex items-center justify-center text-sm">
            <i class="fa-solid fa-sliders"></i>
          </div>
          <div>
            <h3 class="text-sm font-bold text-white">Integrasi Spreadsheet</h3>
            <p class="text-[11px] text-slate-400">Google Sheets / Apps Script Webhook</p>
          </div>
        </div>
        <button type="button" @click="openSettings = false" class="icon-btn w-8 h-8">
          <i class="fa-solid fa-xmark text-xs"></i>
        </button>
      </div>

      <div class="space-y-4 my-5 text-xs">
        <div>
          <label class="lbl">Webhook URL</label>
          <input type="url" x-model="gsWebhook" placeholder="https://script.google.com/macros/s/.../exec"
                 class="inp font-mono text-xs">
          <span class="text-[10px] text-slate-400 mt-1 block">
            Kosongkan untuk tetap menggunakan <strong>Demo Mode (Local Banterpool Sync)</strong>.
          </span>
        </div>

        <div class="p-3 rounded-xl bg-blue-950/20 border border-blue-500/20 text-[11px] text-slate-300">
          <p class="font-bold text-blue-400 mb-1"><i class="fa-solid fa-circle-check mr-1"></i> Status Aktif:</p>
          Setiap laporan tersimpan ke database lokal dan dapat langsung di-export sebagai file spreadsheet CSV kapan saja.
        </div>
      </div>

      <div class="flex items-center justify-end gap-2 pt-3 border-t border-white/[0.08]">
        <button type="button" @click="openSettings = false" class="btn-ghost text-xs">Tutup</button>
        <button type="button" @click="saveSettings()" class="btn-primary text-xs py-2 px-4">Simpan</button>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer class="border-t border-white/[0.06] py-5 text-center text-xs text-slate-400 bg-[#070b13]">
    <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2">
      <span>Daily Report — ISP Field Technician Portal v2.6</span>
      <span>Terhubung dengan Master Stok Barang &amp; Mutasi Banterpool</span>
    </div>
  </footer>

</body>
</html>
