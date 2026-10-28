<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>WiFi Banterpool — Daily Report Teknisi & Sinkronisasi Stok</title>

  <!-- Google Fonts: Poppins (Banterpool Standard) & JetBrains Mono -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  @vite(['resources/css/app.css', 'resources/js/app.js'])

  <style>
    *, *::before, *::after { box-sizing: border-box; }
    [x-cloak] { display: none !important; }

    :root {
      color-scheme: light;
      --brand:       #D31818;
      --brand-hover: #b01010;
      --brand-50:    #fff1f1;
      --brand-100:   #ffe1e1;
      --bg:          #ffffff;
      --surface:     #ffffff;
      --surface-2:   #f8fafc;
      --border:      #e2e8f0;
      --border-md:   #cbd5e1;
      --text:        #0f172a;
      --text-muted:  #64748b;
      --subtle:      #94a3b8;
    }

    html, body {
      min-height: 100%;
      background-color: var(--bg);
      color: var(--text);
      max-width: 100%;
      overflow-x: hidden;
      position: relative;
    }

    body {
      font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
      overflow-x: hidden;
    }

    /* ── Latar Belakang Gelombang Garis Merah Banterpool ── */
    .bg-wave-container {
      position: fixed;
      inset: 0;
      width: 100%;
      height: 100%;
      overflow: hidden;
      pointer-events: none;
      z-index: 0;
    }

    .bg-wave-pattern {
      position: absolute;
      top: 0;
      left: -10%;
      width: 120%;
      height: 260%;
      background-image: url('{{ asset('image/bg-red-wave.jpg') }}');
      background-size: cover;
      background-position: center top;
      background-repeat: repeat-y;
      opacity: 0.30;
      pointer-events: none;
      will-change: transform;
      transform-origin: center center;
      transition: opacity 0.3s ease;
    }

    .bg-wave-glow {
      position: absolute;
      inset: -10%;
      background: radial-gradient(circle at 70% 30%, rgba(211, 24, 24, 0.06) 0%, rgba(239, 68, 68, 0.02) 40%, transparent 70%);
      pointer-events: none;
      will-change: transform;
    }

    /* ── Custom Scrollbar ── */
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
    ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

    /* ── No Scrollbar Utility ── */
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

    /* ── Line Clamp Utility ── */
    .line-clamp-2 {
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    /* ── Dropdown Text Styling (Light Theme) ── */
    select,
    select.inp {
      background-color: #ffffff !important;
      color: #0f172a !important;
      appearance: none;
      -webkit-appearance: none;
      -moz-appearance: none;
    }

    select option,
    select.inp option {
      background-color: #ffffff !important;
      color: #0f172a !important;
      padding: 10px 14px;
    }

    select optgroup {
      background-color: #f8fafc !important;
      color: #64748b !important;
      font-weight: 700;
    }

    /* ── Card Styling ── */
    .card {
      background: #ffffff;
      border: 1px solid var(--border);
      border-radius: 18px;
      box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.04), 0 2px 6px -1px rgba(0, 0, 0, 0.02);
      max-width: 100%;
    }
    .card-lg { border-radius: 18px; }
    @media (min-width: 640px) {
      .card-lg { border-radius: 24px; }
    }

    /* ── Inputs ── */
    .inp {
      width: 100%;
      max-width: 100%;
      background: #ffffff;
      border: 1px solid #d1d5db;
      border-radius: 12px;
      padding: 10px 14px;
      color: #0f172a;
      font-size: 13px;
      font-family: 'Poppins', sans-serif;
      transition: border-color .15s, box-shadow .15s, background-color .15s;
      outline: none;
    }
    .inp:focus {
      border-color: var(--brand);
      background-color: #ffffff;
      box-shadow: 0 0 0 3px rgba(211, 24, 24, 0.15);
    }
    .inp::placeholder { color: #94a3b8; }

    /* ── Labels ── */
    .lbl {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 11px;
      font-weight: 700;
      color: #475569;
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
      color: var(--brand);
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 16px;
    }
    .section-title::after {
      content: '';
      flex: 1;
      height: 1px;
      background: #e2e8f0;
    }

    /* ── Metric Card ── */
    .metric-card {
      background: #ffffff;
      border: 1px solid var(--border);
      border-radius: 18px;
      padding: 16px;
      position: relative;
      overflow: hidden;
      box-shadow: 0 4px 18px -2px rgba(0, 0, 0, 0.04);
      transition: border-color .2s, transform .15s, box-shadow .2s;
    }
    @media (min-width: 640px) {
      .metric-card {
        border-radius: 20px;
        padding: 22px;
      }
    }
    .metric-card:hover {
      border-color: #cbd5e1;
      transform: translateY(-2px);
      box-shadow: 0 8px 24px -4px rgba(0, 0, 0, 0.08);
    }

    /* ── Table ── */
    .tbl { width: 100%; border-collapse: collapse; font-size: 12px; }
    .tbl thead th {
      padding: 13px 14px;
      text-align: left;
      font-size: 11px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: .06em;
      color: #64748b;
      border-bottom: 1px solid var(--border);
      background: #f8fafc;
    }
    .tbl tbody td {
      padding: 14px 14px;
      border-bottom: 1px solid #f1f5f9;
      vertical-align: middle;
      color: #334155;
    }
    .tbl tbody tr:hover td { background: rgba(255, 241, 241, 0.35); }
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
    .badge-brand { background: #fff1f1; color: var(--brand); border: 1px solid #fecaca; }
    .badge-green { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
    .badge-amber { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
    .badge-blue  { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
    .badge-slate { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

    /* ── Buttons ── */
    .btn-primary {
      background: var(--brand);
      color: #ffffff;
      border: none;
      border-radius: 12px;
      padding: 12px 24px;
      font-size: 13px;
      font-weight: 800;
      cursor: pointer;
      box-shadow: 0 6px 18px -2px rgba(211, 24, 24, 0.32);
      transition: background .15s, transform .1s, box-shadow .15s;
    }
    .btn-primary:hover {
      background: var(--brand-hover);
      box-shadow: 0 8px 22px -2px rgba(211, 24, 24, 0.42);
    }
    .btn-primary:active { transform: scale(.98); }

    .btn-ghost {
      background: #ffffff;
      border: 1px solid var(--border);
      color: #334155;
      border-radius: 12px;
      padding: 8px 14px;
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
      transition: background .15s, color .15s, border-color .15s, box-shadow .15s;
    }
    .btn-ghost:hover {
      background: #f8fafc;
      color: #0f172a;
      border-color: #cbd5e1;
    }

    .icon-btn {
      width: 36px; height: 36px;
      border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      background: #ffffff;
      border: 1px solid var(--border);
      color: #64748b;
      cursor: pointer;
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
      transition: background .15s, color .15s, border-color .15s, transform .1s;
    }
    @media (min-width: 640px) {
      .icon-btn {
        width: 38px;
        height: 38px;
      }
    }
    .icon-btn:hover {
      background: #f8fafc;
      color: var(--brand);
      border-color: rgba(211, 24, 24, 0.35);
      transform: translateY(-1px);
    }

    /* ── Tabs ── */
    .tab-btn {
      padding: 12px 8px;
      font-size: 13px;
      font-weight: 700;
      color: #64748b;
      background: none;
      border: none;
      border-bottom: 2px solid transparent;
      cursor: pointer;
      display: flex; align-items: center; gap: 8px;
      transition: color .15s, border-color .15s;
    }
    .tab-btn.active { color: var(--brand); border-bottom-color: var(--brand); }

    /* ── Material Item Card ── */
    .mat-card {
      background: #ffffff;
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 12px 14px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
      transition: border-color .15s, background-color .15s, box-shadow .15s;
    }
    .mat-card:hover {
      border-color: #cbd5e1;
      background-color: #fdfdfd;
    }
    .mat-card.checked {
      border-color: rgba(211, 24, 24, 0.4);
      background-color: rgba(255, 241, 241, 0.55);
      box-shadow: 0 2px 8px rgba(211, 24, 24, 0.06);
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
  <script>
    function dailyReportApp() {
      return {
        openSettings: false,
        openUserMenu: false,
        openAddMaterialModal: false,
        isFullscreen: false,
        activeTab: '{{ $tab === 'cash' ? 'cash' : 'jobs' }}',
        gsWebhook: localStorage.getItem('gs_webhook_url') || '',
        isSyncing: false,

        // Form fields
        reportDate: '{{ now()->format('Y-m-d') }}',
        technicianName: '{{ addslashes(auth()->user()->name ?? 'Budi Setiawan') }}',
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

        // Mengelompokkan item katalog langsung per kategori secara vertikal mengikuti urutan stok barang (POV Admin)
        get categorizedCatalog() {
          const groups = {};
          this.catalog.forEach(item => {
            const cat = item.category || 'Material Pembantu & Instalasi';
            if (!groups[cat]) {
              groups[cat] = [];
            }
            groups[cat].push(item);
          });

          const result = [];
          // 1. Urutkan berdasarkan urutan kategori pada stok barang (POV Admin)
          if (Array.isArray(this.stockCategories)) {
            this.stockCategories.forEach(cat => {
              if (groups[cat] && groups[cat].length > 0) {
                result.push({
                  name: cat,
                  items: groups[cat]
                });
                delete groups[cat];
              }
            });
          }

          // 2. Kategori lainnya jika ada item kustom
          Object.keys(groups).forEach(cat => {
            if (groups[cat] && groups[cat].length > 0) {
              result.push({
                name: cat,
                items: groups[cat]
              });
            }
          });

          return result;
        },

        // Helper Icon Tiap Kategori
        getCategoryIcon(cat) {
          const map = {
            'Adapter & Konektor Optik': 'fa-solid fa-circle-nodes text-blue-600',
            'Aksesoris & Bracket': 'fa-solid fa-wrench text-slate-600',
            'Clamp & Aksesoris': 'fa-solid fa-paperclip text-amber-600',
            'Joint Closure': 'fa-solid fa-box-archive text-emerald-600',
            'Kabel Drop & Fiber Optik': 'fa-solid fa-lines-leaning text-brand',
            'Kabel LAN & UTP': 'fa-solid fa-network-wired text-indigo-600',
            'Kabel Ties': 'fa-solid fa-link text-slate-500',
            'Material Optik & Jaringan': 'fa-solid fa-diagram-project text-cyan-600',
            'Material Pembantu & Instalasi': 'fa-solid fa-toolbox text-orange-600',
            'Media Converter (HTB)': 'fa-solid fa-arrows-rotate text-purple-600',
            'Modem & ONT GPON': 'fa-solid fa-wifi text-brand',
            'ODP & Box Pasif': 'fa-solid fa-server text-teal-600',
            'Patchcord & Pigtail': 'fa-solid fa-wave-square text-rose-600',
            'Perangkat Jaringan & Switch': 'fa-solid fa-ethernet text-blue-700',
            'Power & Adaptor': 'fa-solid fa-bolt text-amber-500',
            'Protection Sleeve (Smuft)': 'fa-solid fa-shield-halved text-emerald-700',
            'Router & AP Wireless': 'fa-solid fa-tower-broadcast text-brand',
            'Splitter Optik': 'fa-solid fa-sitemap text-indigo-500',
            'Tiang & Bracket': 'fa-solid fa-monument text-slate-700',
          };
          return map[cat] || 'fa-solid fa-boxes-stacked text-gray-600';
        },

        // Buka modal tambah barang dengan kategori langsung terpilih
        openAddForCategory(categoryName) {
          this.newMat.category = categoryName;
          this.newMat.materialId = '';
          this.newMat.name = '';
          this.newMat.unit = 'pcs';
          this.newMat.price = 0;
          this.newMat.qty = 1;
          this.openAddMaterialModal = true;
        },

        // Form tambah barang baru (manual / dari stok admin)
        addMode: 'stock',
        newMat: {
          category: '',
          materialId: '',
          name: '',
          unit: 'pcs',
          price: 0,
          qty: 1
        },

        // Material Catalog List (Dinamis dari Barang Masuk Bulan Sekarang)
        catalog: {{ Js::from(collect($defaultCatalog)->map(function($i) {
            return [
                'key' => $i['key'],
                'material_id' => $i['material_id'] ?? null,
                'name' => $i['name'],
                'category' => $i['category'],
                'price' => (float)$i['price'],
                'unit' => $i['unit'],
                'qty' => (float)$i['default_qty'],
                'max_qty' => isset($i['max_qty']) && $i['max_qty'] !== null ? (float)$i['max_qty'] : null,
                'checked' => (bool)$i['checked'],
                'isEditing' => false,
            ];
        })) }},

        // Validasi & batasi Qty agar tidak melebihi stok barang masuk bulan ini
        validateItemQty(item) {
          if (item.max_qty !== undefined && item.max_qty !== null) {
            const max = Number(item.max_qty);
            if (Number(item.qty) > max) {
              item.qty = max;
              alert('Jumlah ' + item.name + ' tidak boleh melebihi stok barang masuk bulan ini (Maks. ' + max + ' ' + item.unit + ')!');
            }
          }
          if (Number(item.qty) < 0) {
            item.qty = 0;
          }
        },

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

        isSubmittingMaterial: false,

        // Tambah Barang ke Katalog (Manual / Dari Stok) & Sinkron ke Log Barang Masuk Admin Bulan Ini
        async submitAddMaterial() {
          if (!this.newMat.name || !this.newMat.name.trim()) {
            alert('Nama barang tidak boleh kosong.');
            return;
          }
          const qty = Number(this.newMat.qty) || 1;
          if (qty <= 0) {
            alert('Jumlah (Qty) harus lebih dari 0.');
            return;
          }

          this.isSubmittingMaterial = true;

          try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch('{{ route('daily-report.tambah-barang-masuk') }}', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token || ''
              },
              body: JSON.stringify({
                material_id: this.newMat.materialId || null,
                name: this.newMat.name.trim(),
                category: this.newMat.category || '',
                unit: this.newMat.unit || 'pcs',
                price: Number(this.newMat.price) || 0,
                qty: qty,
                notes: 'Input Manual Daily Report (' + (this.technicianName || 'Teknisi') + ')'
              })
            });

            const data = await res.json();
            if (data && data.success && data.material) {
              const existingIndex = this.catalog.findIndex(i => 
                (data.material.material_id && i.material_id === data.material.material_id) || 
                (i.name.toLowerCase() === data.material.name.toLowerCase())
              );

              if (existingIndex !== -1) {
                this.catalog[existingIndex].qty = Number(this.catalog[existingIndex].qty) + qty;
                this.catalog[existingIndex].max_qty = Number(data.material.max_qty || (Number(this.catalog[existingIndex].max_qty || 0) + qty));
                this.catalog[existingIndex].checked = true;
                if (data.material.price > 0) {
                  this.catalog[existingIndex].price = Number(data.material.price);
                }
              } else {
                this.catalog.push({
                  key: data.material.key || ('mat_' + Date.now()),
                  material_id: data.material.material_id,
                  name: data.material.name,
                  category: data.material.category,
                  price: Number(data.material.price) || 0,
                  unit: data.material.unit || 'pcs',
                  qty: qty,
                  max_qty: Number(data.material.max_qty) || qty,
                  checked: true,
                  isEditing: false
                });
              }

              if (data.material.category && !this.stockCategories.includes(data.material.category)) {
                this.stockCategories.push(data.material.category);
              }

              // Reset form & tutup modal
              this.newMat = {
                category: '',
                materialId: '',
                name: '',
                unit: 'pcs',
                price: 0,
                qty: 1
              };
              this.openAddMaterialModal = false;
            } else {
              alert(data.message || 'Gagal menambahkan barang.');
            }
          } catch (err) {
            console.error('Error adding material:', err);
            // Fallback lokal jika offline
            const newKey = 'item_' + Date.now();
            this.catalog.push({
              key: newKey,
              material_id: this.newMat.materialId || null,
              name: this.newMat.name.trim(),
              category: this.newMat.category || 'Material Pembantu & Instalasi',
              price: Number(this.newMat.price) || 0,
              unit: this.newMat.unit || 'pcs',
              qty: qty,
              checked: true,
              isEditing: false
            });
            this.openAddMaterialModal = false;
          } finally {
            this.isSubmittingMaterial = false;
          }
        },

        // Hapus item dari katalog berdasarkan key
        deleteMaterial(itemToDelete) {
          const targetName = itemToDelete && itemToDelete.name ? itemToDelete.name : 'barang';
          if (confirm("Hapus barang '" + targetName + "' dari daftar laporan?")) {
            const key = typeof itemToDelete === 'object' ? itemToDelete.key : null;
            if (key) {
              const idx = this.catalog.findIndex(i => i.key === key);
              if (idx !== -1) {
                this.catalog.splice(idx, 1);
                return;
              }
            }
            if (typeof itemToDelete === 'number') {
              this.catalog.splice(itemToDelete, 1);
            }
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
      };
    }
  </script>
</head>

<body class="flex flex-col min-h-screen relative selection:bg-brand selection:text-white"
      x-data="dailyReportApp()"
      @fullscreenchange.window="isFullscreen = !!document.fullscreenElement">

  <!-- ============================================== -->
  <!-- BACKGROUND GELOMBANG MERAH INTERAKTIF          -->
  <!-- ============================================== -->
  <div class="bg-wave-container" aria-hidden="true">
    <div id="bgWavePattern" class="bg-wave-pattern"></div>
    <div id="bgWaveGlow" class="bg-wave-glow"></div>
  </div>

  <!-- ============================================== -->
  <!-- 1. TOPBAR / HEADER NAVIGATION (BANTERPOOL THEME) -->
  <!-- ============================================== -->
  <header class="sticky top-0 z-40 border-b border-gray-200/80 bg-white/90 backdrop-blur-md shadow-xs">
    <div class="max-w-7xl mx-auto px-3.5 sm:px-6 lg:px-8 flex items-center justify-between h-14 sm:h-16 gap-2 sm:gap-4">

      <!-- Left Branding: Banterpool Logo & Slogan -->
      <a href="{{ route('daily-report.index') }}" class="flex items-center gap-2 sm:gap-3 min-w-0">
        <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center bg-red-50 border border-red-200/80 text-brand shadow-xs shrink-0">
          <i class="fa-solid fa-wifi text-base sm:text-lg"></i>
        </div>
        <div class="min-w-0">
          <div class="flex items-center gap-1.5 sm:gap-2">
            <span class="font-extrabold text-sm sm:text-base md:text-lg text-black tracking-tight whitespace-nowrap">
              BANTER<span class="text-brand">POOL</span>
            </span>
            <span class="text-[9px] sm:text-[10px] font-black px-1.5 sm:px-2 py-0.5 rounded-full bg-red-50 text-brand border border-red-200 whitespace-nowrap">
              DAILY REPORT
            </span>
          </div>
          <p class="text-[11px] text-gray-500 hidden sm:block font-medium truncate">
            ISP Field <span class="text-brand font-semibold">Technician</span> Portal &bull; Sinkronisasi Stok &amp; Kas
          </p>
        </div>
      </a>

      <!-- Right Action Controls -->
      <div class="flex items-center gap-1.5 sm:gap-2.5 shrink-0">
        
        <!-- Google Sheets Demo Mode Badge -->
        <button type="button" @click="openSettings = true"
                class="hidden md:flex items-center gap-2 btn-ghost text-xs bg-amber-50/70 text-amber-800 border-amber-200 hover:bg-amber-100"
                title="Status Sinkronisasi Spreadsheet">
          <span class="dot-pulse bg-amber-500"></span>
          <span>Google Sheets: Demo Mode</span>
        </button>

        <!-- Sync Button -->
        <button type="button" @click="refreshSync()" class="icon-btn" title="Sinkronkan dengan Database & Stok">
          <i class="fa-solid fa-rotate text-xs" :class="isSyncing ? 'fa-spin text-brand' : ''"></i>
        </button>

        <!-- Fullscreen Button -->
        <button type="button" @click="toggleFullscreen()" class="icon-btn hidden sm:flex"
                :title="isFullscreen ? 'Keluar Mode Layar Penuh' : 'Mode Layar Penuh (Fullscreen)'">
          <i class="fa-solid text-xs" :class="isFullscreen ? 'fa-compress text-brand' : 'fa-expand'"></i>
        </button>

        <!-- Settings Button -->
        <button type="button" @click="openSettings = true" class="icon-btn" title="Pengaturan Integrasi">
          <i class="fa-solid fa-sliders text-xs"></i>
        </button>

        <!-- User Menu Avatar Dropdown -->
        <div class="relative" @click.outside="openUserMenu = false">
          <button type="button" @click="openUserMenu = !openUserMenu"
                  class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center text-xs font-black text-white shadow-md shadow-brand/20 bg-brand hover:bg-[#b01010] transition"
                  title="Menu Pengguna & Switch POV">
            {{ strtoupper(substr(auth()->user()->name ?? 'TK', 0, 2)) }}
          </button>

          <!-- Dropdown Popup -->
          <div x-show="openUserMenu" x-cloak
               x-transition:enter="transition ease-out duration-100"
               x-transition:enter-start="opacity-0 scale-95"
               x-transition:enter-end="opacity-100 scale-100"
               class="absolute right-0 top-11 sm:top-12 w-64 max-w-[calc(100vw-2rem)] card p-2 z-50 text-xs shadow-2xl divide-y divide-gray-100">
            
            <div class="px-3 py-2.5">
              <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Masuk Sebagai</span>
              <span class="font-extrabold text-gray-900 block truncate text-sm">{{ auth()->user()->name }}</span>
              <span class="text-[11px] text-gray-500 font-mono block truncate">{{ auth()->user()->email }}</span>
            </div>

            <div class="py-1.5 space-y-0.5">
              @if(auth()->user()->isAdmin())
                <a href="{{ route('admin.dashboard') }}"
                   class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-gray-700 hover:bg-red-50 hover:text-brand transition font-medium">
                  <i class="fa-solid fa-shield-halved w-4 text-center text-brand"></i>
                  <span>Panel NOC Admin</span>
                </a>
              @endif

              <a href="{{ route('teknisi.dashboard') }}"
                 class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-gray-700 hover:bg-amber-50 hover:text-amber-700 transition font-medium">
                <i class="fa-solid fa-screwdriver-wrench w-4 text-center text-amber-500"></i>
                <span>Portal Teknisi Lapangan</span>
              </a>

              <a href="{{ route('kolektor.dashboard') }}"
                 class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-gray-700 hover:bg-emerald-50 hover:text-emerald-700 transition font-medium">
                <i class="fa-solid fa-money-bill-wave w-4 text-center text-emerald-600"></i>
                <span>Portal Kolektor Lapangan</span>
              </a>

              <a href="{{ route('home') }}"
                 class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-gray-700 hover:bg-red-50 hover:text-brand transition font-medium">
                <i class="fa-solid fa-house w-4 text-center text-brand"></i>
                <span>Beranda WiFi Banterpool</span>
              </a>
            </div>

            <div class="pt-1.5">
              <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-brand hover:bg-red-50 transition text-left font-bold">
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
  <main class="flex-1 max-w-7xl mx-auto w-full px-3.5 sm:px-6 lg:px-8 py-5 sm:py-7 lg:py-8 space-y-6 sm:space-y-8 relative z-10 min-w-0">

    <!-- Flash Messages -->
    @if(session('success'))
      <div class="flex items-center gap-3 p-3.5 sm:p-4 rounded-2xl text-xs sm:text-sm bg-emerald-50 border border-emerald-200 text-emerald-800 shadow-xs"
           x-data="{ show: true }" x-show="show">
        <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
          <i class="fa-solid fa-circle-check text-sm sm:text-base"></i>
        </div>
        <span class="flex-1 font-semibold leading-snug">{{ session('success') }}</span>
        <button type="button" @click="show = false" class="text-emerald-500 hover:text-emerald-800 shrink-0">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>
    @endif

    @if($errors->any())
      <div class="p-3.5 sm:p-4 rounded-2xl text-xs sm:text-sm bg-red-50 border border-red-200 text-red-800 shadow-xs"
           x-data="{ show: true }" x-show="show">
        <div class="flex items-center justify-between mb-2">
          <span class="font-extrabold flex items-center gap-2 text-brand">
            <i class="fa-solid fa-triangle-exclamation"></i> Mohon periksa kembali formulir:
          </span>
          <button type="button" @click="show = false" class="text-red-500 hover:text-brand">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
        <ul class="space-y-1 list-disc list-inside text-xs text-red-700">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <!-- ============================================== -->
    <!-- 3. KPI METRIC CARDS (CLEAN BANTERPOOL AESTHETICS) -->
    <!-- ============================================== -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-5">

      <!-- Card 1: Total Pekerjaan Hari Ini -->
      <div class="metric-card">
        <div class="flex items-center justify-between mb-2.5 sm:mb-3">
          <span class="lbl mb-0 text-[10px] sm:text-[11px]">Total Pekerjaan Hari Ini</span>
          <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl flex items-center justify-center bg-red-50 text-brand border border-red-100 shadow-xs shrink-0">
            <i class="fa-solid fa-briefcase text-xs sm:text-sm"></i>
          </div>
        </div>
        <div class="flex items-baseline justify-between mt-1 sm:mt-2">
          <span class="text-2xl sm:text-3xl lg:text-4xl font-black text-black tracking-tight leading-none">{{ $totalJobsToday }}</span>
          <span class="text-[10px] sm:text-xs font-bold text-brand bg-red-50 border border-red-200 px-2 sm:px-2.5 py-0.5 rounded-full flex items-center gap-1 shrink-0">
            <i class="fa-solid fa-arrow-trend-up text-[9px]"></i> +{{ $totalJobsToday }}
          </span>
        </div>
        <p class="text-[11px] text-gray-500 mt-2 font-medium">Order instalasi &amp; tiket lapangan</p>
      </div>

      <!-- Card 2: Kas Terkumpul (Teknisi) -->
      <div class="metric-card">
        <div class="flex items-center justify-between mb-2.5 sm:mb-3">
          <span class="lbl mb-0 text-[10px] sm:text-[11px]">Kas Terkumpul (Teknisi)</span>
          <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl flex items-center justify-center bg-emerald-50 text-emerald-600 border border-emerald-100 shadow-xs shrink-0">
            <i class="fa-solid fa-wallet text-xs sm:text-sm"></i>
          </div>
        </div>
        <div class="flex items-baseline justify-between mt-1 sm:mt-2">
          <span class="text-xl sm:text-2xl lg:text-3xl font-black text-emerald-600 tracking-tight leading-none truncate" title="Rp {{ number_format($cashCollectedToday, 0, ',', '.') }}">
            Rp {{ number_format($cashCollectedToday, 0, ',', '.') }}
          </span>
        </div>
        <p class="text-[11px] text-gray-500 mt-2 font-medium">Tunai, QRIS &amp; transfer hari ini</p>
      </div>

      <!-- Card 3: Perlu Penyetoran / Pending -->
      <div class="metric-card">
        <div class="flex items-center justify-between mb-2.5 sm:mb-3">
          <span class="lbl mb-0 text-[10px] sm:text-[11px]">Perlu Penyetoran / Pending</span>
          <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl flex items-center justify-center bg-amber-50 text-amber-600 border border-amber-100 shadow-xs shrink-0">
            <i class="fa-regular fa-clock text-xs sm:text-sm"></i>
          </div>
        </div>
        <div class="flex items-baseline justify-between mt-1 sm:mt-2">
          <span class="text-2xl sm:text-3xl lg:text-4xl font-black text-black tracking-tight leading-none">{{ $pendingDepositCount }}</span>
          <span class="badge badge-amber font-bold text-[10px] sm:text-xs">Belum Setor</span>
        </div>
        <p class="text-[11px] text-gray-500 mt-2 font-medium">Menunggu konfirmasi setor ke admin</p>
      </div>

      <!-- Card 4: Est. Biaya Material (Hari Ini) -->
      <div class="metric-card">
        <div class="flex items-center justify-between mb-2.5 sm:mb-3">
          <span class="lbl mb-0 text-[10px] sm:text-[11px]">Est. Biaya Material (Hari Ini)</span>
          <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl flex items-center justify-center bg-red-50 text-brand border border-red-100 shadow-xs shrink-0">
            <i class="fa-solid fa-boxes-stacked text-xs sm:text-sm"></i>
          </div>
        </div>
        <div class="flex items-baseline justify-between mt-1 sm:mt-2">
          <span class="text-xl sm:text-2xl lg:text-3xl font-black text-gray-900 tracking-tight leading-none truncate" title="Rp {{ number_format($materialCostToday, 0, ',', '.') }}">
            Rp {{ number_format($materialCostToday, 0, ',', '.') }}
          </span>
        </div>
        <p class="text-[11px] text-gray-500 mt-2 font-medium">Estimasi pemakaian material harian</p>
      </div>

    </div>

    <!-- ============================================== -->
    <!-- 4. FORM INPUT LAPORAN & PEMBUKUAN              -->
    <!-- ============================================== -->
    <div class="card card-lg p-4 sm:p-6 lg:p-8">

      <!-- Form Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4 pb-4 sm:pb-5 border-b border-gray-200 mb-5 sm:mb-6">
        <div>
          <h2 class="text-base sm:text-lg md:text-xl font-black text-black flex items-center gap-2.5">
            <span class="w-8 h-8 rounded-xl bg-red-50 text-brand flex items-center justify-center text-sm shadow-xs border border-red-100 shrink-0">
              <i class="fa-solid fa-pen-to-square"></i>
            </span>
            <span>Input Laporan & Pembukuan</span>
          </h2>
          <p class="text-xs text-gray-500 mt-1 font-medium">
            Pencatatan aktivitas pekerjaan, kredensial ONT, biaya material & kas lapangan
          </p>
        </div>

        <div class="flex items-center gap-2 w-full sm:w-auto">
          <!-- Quick Autofill from Recent Tickets -->
          @if($recentOrders->isNotEmpty())
            <div class="relative w-full sm:w-auto" x-data="{ openPick: false }">
              <button type="button" @click="openPick = !openPick"
                      class="btn-ghost w-full sm:w-auto justify-center flex items-center gap-2 text-xs font-bold text-gray-700 hover:text-brand hover:border-brand/40">
                <i class="fa-solid fa-bolt text-brand"></i>
                <span>Pilih Tiket Pemasangan</span>
                <i class="fa-solid fa-chevron-down text-[10px] ml-1"></i>
              </button>
              <div x-show="openPick" @click.outside="openPick = false" x-cloak
                   class="absolute left-0 sm:left-auto right-0 z-30 mt-2 w-full sm:w-80 max-w-[calc(100vw-2.5rem)] card p-2 text-xs divide-y divide-gray-100 max-h-72 overflow-y-auto shadow-2xl">
                @foreach($recentOrders as $ro)
                  <button type="button" @click="autofillFromOrder({{ Js::from($ro) }}); openPick = false"
                          class="w-full text-left p-2.5 rounded-xl hover:bg-red-50/60 transition flex flex-col gap-0.5 group">
                    <span class="font-extrabold text-gray-900 group-hover:text-brand">{{ $ro->order_number }} — {{ $ro->customer_name }}</span>
                    <span class="text-[10px] text-gray-500 truncate">{{ $ro->address }}</span>
                  </button>
                @endforeach
              </div>
            </div>
          @endif
        </div>
      </div>

      <!-- Form Element -->
      <form action="{{ route('daily-report.store') }}" method="POST" class="space-y-5 sm:space-y-6">
        @csrf
        <input type="hidden" name="order_id" :value="selectedOrderId">
        <input type="hidden" name="materials_json" :value="materialsJsonPayload">
        <input type="hidden" name="material_total_cost" :value="totalMaterialCost">

        <!-- Baris 1: Tanggal & Nama Teknisi -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5">
          <div>
            <label class="lbl">
              <i class="fa-regular fa-calendar text-brand"></i> Hari & Tanggal
            </label>
            <input type="date" name="report_date" x-model="reportDate" required class="inp font-medium">
          </div>

          <div>
            <label class="lbl">
              <i class="fa-solid fa-helmet-safety text-brand"></i> Nama Teknisi
            </label>
            <input type="text" name="technician_name" x-model="technicianName" required placeholder="Nama Teknisi Lapangan" class="inp font-medium">
          </div>
        </div>

        <!-- Baris 2: Mitra Perusahaan & Tipe Pekerjaan -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5">
          <div>
            <label class="lbl">
              <i class="fa-solid fa-building text-brand"></i> Perusahaan / PT Mitra (ISP)
            </label>
            <div class="relative">
              <select name="partner_company" x-model="partnerCompany" required
                      class="inp pr-9 font-semibold text-gray-800">
                @foreach($partners as $p)
                  <option value="{{ $p }}">
                    {{ $p }}
                  </option>
                @endforeach
              </select>
              <i class="fa-solid fa-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
            </div>
          </div>

          <div>
            <label class="lbl">
              <i class="fa-solid fa-wrench text-brand"></i> Tipe Pekerjaan
            </label>
            <div class="relative">
              <select name="job_type" x-model="jobType" required
                      class="inp pr-9 font-semibold text-gray-800">
                @foreach($jobTypes as $jt)
                  <option value="{{ $jt }}">
                    {{ $jt }}
                  </option>
                @endforeach
              </select>
              <i class="fa-solid fa-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
            </div>
          </div>
        </div>

        <!-- ========================================== -->
        <!-- SECTION: INFORMASI PELANGGAN & KREDENSIAL  -->
        <!-- ========================================== -->
        <div class="pt-2">
          <p class="section-title">
            <i class="fa-regular fa-id-card text-brand"></i>
            <span>Informasi Pelanggan & Kredensial</span>
          </p>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5">
            <div>
              <label class="lbl">Nama Pelanggan / ID</label>
              <input type="text" name="customer_name" x-model="customerName" required placeholder="Bpk Herman / CST-109" class="inp font-semibold">
            </div>

            <div>
              <label class="lbl">Nomor HP / WhatsApp</label>
              <input type="text" name="customer_phone" x-model="customerPhone" placeholder="08xxxxxxxxxx" class="inp font-medium">
            </div>

            <div class="sm:col-span-2">
              <label class="lbl">Alamat Lengkap</label>
              <input type="text" name="customer_address" x-model="customerAddress" placeholder="Jl. Merdeka No. 45, Purwokerto" class="inp font-medium">
            </div>

            <div>
              <label class="lbl">Secret PPPoE (Username)</label>
              <input type="text" name="pppoe_username" x-model="pppoeUsername" placeholder="herman_net"
                     class="inp font-mono font-semibold text-brand">
            </div>

            <div>
              <label class="lbl">ID ODP & Port</label>
              <input type="text" name="odp_port" x-model="odpPort" placeholder="ODP-TMB-04 / Port 2"
                     class="inp font-mono font-semibold text-gray-900">
            </div>
          </div>
        </div>

        <!-- ========================================== -->
        <!-- SECTION: PARAMETER TEKNIS & HARGA MATERIAL -->
        <!-- ========================================== -->
        <div class="pt-2">
          <p class="section-title">
            <i class="fa-solid fa-microchip text-brand"></i>
            <span>Parameter Teknis & Harga Material</span>
          </p>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5 mb-5">
            <div>
              <label class="lbl">Rx Power (dBm)</label>
              <input type="text" name="rx_power" x-model="rxPower" placeholder="-19.5"
                     class="inp font-mono font-bold text-amber-700">
            </div>

            <div>
              <label class="lbl">SN Perangkat ONT</label>
              <input type="text" name="ont_sn" x-model="ontSn" placeholder="ZTEGC819A2B"
                     class="inp font-mono font-bold text-gray-900">
            </div>
          </div>

          <!-- KATALOG MATERIAL (Sinkron dengan Stok Admin + Edit Manual + Tambah Manual) -->
          <div class="p-3.5 sm:p-5 rounded-2xl bg-gray-50/70 border border-gray-200 space-y-4">
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-gray-200">
              <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-8 h-8 rounded-xl bg-red-50 text-brand flex items-center justify-center text-xs border border-red-100 shrink-0">
                  <i class="fa-solid fa-boxes-stacked"></i>
                </div>
                <div class="min-w-0">
                  <span class="font-extrabold text-black text-xs sm:text-sm block truncate">Katalog Material & Harga Satuan</span>
                  <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                    <span class="inline-flex items-center gap-1 text-[9px] sm:text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">
                      <i class="fa-solid fa-arrow-down-long text-[8px] text-emerald-600"></i>
                      Barang Masuk: {{ $currentMonthName ?? 'Bulan Sekarang' }}
                    </span>
                    <span class="text-[9px] sm:text-[10px] text-gray-500 font-medium">(POV Admin)</span>
                  </div>
                </div>
              </div>

              <div class="flex items-center justify-between sm:justify-end gap-2 flex-wrap">
                <!-- Total Biaya Badge -->
                <span class="text-xs font-black text-emerald-800 bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-xl shadow-xs whitespace-nowrap">
                  Total: <span x-text="rp(totalMaterialCost)"></span>
                </span>

                <!-- Tambah Barang Manual Button -->
                <button type="button" @click="openAddMaterialModal = true"
                        class="btn-ghost flex items-center gap-1.5 text-xs py-1.5 px-3 border-red-200 text-brand hover:bg-red-50 font-bold shrink-0">
                  <i class="fa-solid fa-plus text-[10px]"></i>
                  <span>Tambah Barang</span>
                </button>
              </div>
            </div>

            <!-- Kategori-Kategori Tersusun Mengikuti Urutan Stok Barang -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3.5 sm:gap-4 pt-1 items-start">
              <template x-for="group in categorizedCatalog" :key="group.name">
                <div class="p-3.5 sm:p-5 rounded-2xl bg-white border border-gray-200 shadow-xs space-y-3 flex flex-col justify-between">
                  
                  <!-- Header Kategori -->
                  <div class="flex items-center justify-between gap-3 pb-2.5 border-b border-gray-100">
                    <div class="flex items-center gap-2.5 min-w-0">
                      <div class="w-8 h-8 rounded-xl bg-gray-50 flex items-center justify-center text-sm border border-gray-200/80 shadow-xs shrink-0">
                        <i :class="getCategoryIcon(group.name)"></i>
                      </div>
                      <div class="min-w-0">
                        <h4 class="font-extrabold text-xs sm:text-sm text-gray-900 tracking-tight truncate" x-text="group.name"></h4>
                        <div class="flex items-center gap-2 mt-0.5">
                          <span class="text-[10px] text-gray-400 font-semibold shrink-0" x-text="group.items.length + ' item'"></span>
                          <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full shrink-0"
                                x-show="group.items.some(i => i.checked && Number(i.qty) > 0)">
                            <i class="fa-solid fa-check text-[8px]"></i> Digunakan
                          </span>
                        </div>
                      </div>
                    </div>

                    <!-- Quick Button Tambah Barang di Kategori Ini -->
                    <button type="button" @click="openAddForCategory(group.name)"
                            class="btn-ghost flex items-center gap-1.5 text-[11px] py-1 px-2.5 text-gray-600 hover:text-brand hover:border-red-200 font-bold shrink-0"
                            title="Tambah barang khusus kategori ini">
                      <i class="fa-solid fa-plus text-[10px] text-brand"></i>
                      <span class="hidden sm:inline">Tambah</span>
                    </button>
                  </div>

                  <!-- Daftar Barang dalam Kategori Ini -->
                  <div class="space-y-2.5 pt-1">
                    <template x-for="item in group.items" :key="item.key">
                      <div class="mat-card flex flex-col justify-between gap-2.5"
                           :class="item.checked ? 'checked' : ''">
                        
                        <!-- Normal View (When not in edit mode) -->
                        <div x-show="!item.isEditing">
                          <div class="flex items-start justify-between gap-2">
                            
                            <!-- Checkbox & Name -->
                            <label class="flex items-start gap-2.5 sm:gap-3 cursor-pointer flex-1 min-w-0 select-none">
                              <input type="checkbox" x-model="item.checked"
                                     class="w-4 h-4 rounded text-brand border-gray-300 mt-0.5 accent-brand shrink-0 focus:ring-brand">
                              <div class="min-w-0">
                                <p class="text-xs font-extrabold truncate leading-tight"
                                   :class="item.checked ? 'text-black' : 'text-gray-800'"
                                   x-text="item.name"></p>
                                <div class="flex items-center gap-1.5 sm:gap-2 mt-1 flex-wrap">
                                  <span class="text-[10px] sm:text-[11px] font-mono font-medium text-gray-500" x-text="rp(item.price) + ' / ' + item.unit"></span>
                                  <template x-if="item.max_qty !== undefined && item.max_qty !== null">
                                    <span class="inline-flex items-center gap-1 text-[9px] sm:text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded-md"
                                          :title="'Batas stok barang masuk periode ini: ' + item.max_qty + ' ' + item.unit">
                                      <i class="fa-solid fa-boxes-stacked text-[8px] sm:text-[9px] text-amber-600"></i>
                                      Stok: <span x-text="item.max_qty + ' ' + item.unit"></span>
                                    </span>
                                  </template>
                                </div>
                              </div>
                            </label>

                            <!-- Action Buttons: Edit & Delete -->
                            <div class="flex items-center gap-1 shrink-0">
                              <button type="button" @click="toggleEditMaterial(item)"
                                      class="w-7 h-7 rounded-lg flex items-center justify-center text-gray-400 hover:text-brand hover:bg-red-50 transition"
                                      title="Edit Manual Barang & Harga">
                                <i class="fa-solid fa-pen text-[10px]"></i>
                              </button>
                              <button type="button" @click="deleteMaterial(item)"
                                      class="w-7 h-7 rounded-lg flex items-center justify-center text-gray-400 hover:text-brand hover:bg-red-50 transition"
                                      title="Hapus dari Daftar">
                                <i class="fa-regular fa-trash-can text-[10px]"></i>
                              </button>
                            </div>

                          </div>

                          <!-- Quantity Input & Subtotal (Visible when checked) -->
                          <div class="flex flex-wrap items-center justify-between gap-2 pt-2 mt-2 border-t border-gray-100" x-show="item.checked" x-cloak>
                            <div class="flex flex-col min-w-0">
                              <span class="text-[11px] text-gray-600 font-medium">
                                Subtotal: <strong class="text-emerald-700 font-extrabold" x-text="rp(item.price * item.qty)"></strong>
                              </span>
                              <template x-if="item.max_qty !== undefined && item.max_qty !== null">
                                <span class="text-[9px] text-gray-400 font-medium mt-0.5"
                                      :class="Number(item.qty) >= Number(item.max_qty) ? 'text-amber-600 font-bold' : ''">
                                  Batas: <span x-text="'Maks. ' + item.max_qty + ' ' + item.unit"></span>
                                </span>
                              </template>
                            </div>
                            <div class="flex items-center gap-1.5 shrink-0 ml-auto">
                              <span class="text-[10px] text-gray-500 font-bold uppercase">Qty:</span>
                              <input type="number" x-model="item.qty" min="1" step="any"
                                     :max="item.max_qty || null"
                                     @input="if (item.max_qty !== undefined && item.max_qty !== null && Number(item.qty) > Number(item.max_qty)) { item.qty = item.max_qty; }"
                                     @change="validateItemQty(item)"
                                     class="w-16 inp text-center py-1 px-1.5 text-xs font-bold font-mono"
                                     :class="item.max_qty && Number(item.qty) >= Number(item.max_qty) ? 'border-amber-400 bg-amber-50/40' : ''">
                              <span class="text-[11px] text-gray-600 font-semibold" x-text="item.unit"></span>
                            </div>
                          </div>
                        </div>

                        <!-- Inline Edit View (When user clicks Edit Manual) -->
                        <div x-show="item.isEditing" x-cloak class="p-3 rounded-xl bg-white border border-red-200 shadow-xs space-y-2.5">
                          <div class="flex items-center justify-between pb-1.5 border-b border-gray-100">
                            <span class="text-[11px] font-extrabold text-brand flex items-center gap-1">
                              <i class="fa-solid fa-pen-to-square"></i> Edit Manual Material
                            </span>
                            <button type="button" @click="toggleEditMaterial(item)"
                                    class="text-[11px] font-extrabold text-emerald-700 hover:underline flex items-center gap-1">
                              <i class="fa-solid fa-check"></i> Selesai
                            </button>
                          </div>

                          <div>
                            <label class="text-[10px] font-bold text-gray-600 block mb-1">Nama Barang:</label>
                            <input type="text" x-model="item.name" class="inp py-1.5 text-xs">
                          </div>

                          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div>
                              <label class="text-[10px] font-bold text-gray-600 block mb-1">Kategori:</label>
                              <select x-model="item.category" class="inp py-1.5 text-xs">
                                <template x-for="cat in stockCategories" :key="cat">
                                  <option :value="cat" x-text="cat"></option>
                                </template>
                              </select>
                            </div>

                            <div>
                              <label class="text-[10px] font-bold text-gray-600 block mb-1">Satuan:</label>
                              <input type="text" x-model="item.unit" placeholder="m, pcs, unit, roll" class="inp py-1.5 text-xs">
                            </div>
                          </div>

                          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div>
                              <label class="text-[10px] font-bold text-gray-600 block mb-1">Harga Satuan (Rp):</label>
                              <input type="number" x-model="item.price" min="0" step="500" class="inp py-1.5 text-xs font-mono font-bold">
                            </div>

                            <div>
                              <div class="flex items-center justify-between mb-1">
                                <label class="text-[10px] font-bold text-gray-600 block">Jumlah (Qty):</label>
                                <template x-if="item.max_qty !== undefined && item.max_qty !== null">
                                  <span class="text-[9px] text-amber-700 font-bold" x-text="'Maks. ' + item.max_qty + ' ' + item.unit"></span>
                                </template>
                              </div>
                              <input type="number" x-model="item.qty" min="1" step="any"
                                     :max="item.max_qty || null"
                                     @input="if (item.max_qty !== undefined && item.max_qty !== null && Number(item.qty) > Number(item.max_qty)) { item.qty = item.max_qty; }"
                                     @change="validateItemQty(item)"
                                     class="inp py-1.5 text-xs font-mono font-bold">
                            </div>
                          </div>
                        </div>

                      </div>
                    </template>
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
            <div class="p-3.5 rounded-xl bg-white border border-gray-200 text-xs leading-relaxed shadow-xs">
              <span class="text-brand font-black">Rincian: </span>
              <span class="text-gray-700 font-medium" x-text="materialSummary"></span>
            </div>

          </div>
        </div>

        <!-- ========================================== -->
        <!-- SECTION: PEMBUKUAN / TRANSAKSI LAPANGAN    -->
        <!-- ========================================== -->
        <div class="pt-2">
          <p class="section-title">
            <i class="fa-solid fa-money-check-dollar text-emerald-600"></i>
            <span>Pembukuan / Transaksi Lapangan</span>
          </p>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5">
            <div>
              <label class="lbl">Jenis Transaksi</label>
              <div class="relative">
                <select name="transaction_type" x-model="transactionType" required
                        class="inp pr-9 font-semibold text-gray-800">
                  <option value="Biaya Pasang Baru (PSB)">Biaya Pasang Baru (PSB)</option>
                  <option value="Biaya Perbaikan / Visit">Biaya Perbaikan / Visit</option>
                  <option value="Penjualan Router / ONT Tambahan">Penjualan Router / ONT Tambahan</option>
                  <option value="Penjualan Kabel FO Tambahan">Penjualan Kabel FO Tambahan</option>
                  <option value="Bebas Biaya / Free Garansi">Bebas Biaya / Free Garansi</option>
                </select>
                <i class="fa-solid fa-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
              </div>
            </div>

            <div>
              <label class="lbl">Nominal Kas (Rp)</label>
              <input type="number" name="cash_amount" x-model="cashAmount" required min="0" step="1000"
                     class="inp font-mono font-black text-emerald-600 text-base">
            </div>

            <div>
              <label class="lbl">Metode Bayar</label>
              <div class="relative">
                <select name="payment_method" x-model="paymentMethod" required
                        class="inp pr-9 font-semibold text-gray-800">
                  <option value="Tunai (Cash)">💵 Tunai (Cash)</option>
                  <option value="QRIS">📱 QRIS</option>
                  <option value="Transfer Bank">🏦 Transfer Bank</option>
                  <option value="Non-Tunai / Bebas Biaya">🆓 Non-Tunai / Bebas Biaya</option>
                </select>
                <i class="fa-solid fa-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
              </div>
            </div>

            <div>
              <label class="lbl">Status Setor Kas</label>
              <div class="relative">
                <select name="cash_deposit_status" x-model="cashDepositStatus" required
                        class="inp pr-9 font-semibold text-gray-800">
                  <option value="Belum Disetor">⏳ Belum Disetor</option>
                  <option value="Sudah Disetor">✅ Sudah Disetor</option>
                </select>
                <i class="fa-solid fa-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
              </div>
            </div>

            <div class="sm:col-span-2">
              <label class="lbl">Catatan Tambahan (Opsional)</label>
              <input type="text" name="notes" placeholder="Catatan pekerjaan atau kendala di lapangan" class="inp font-medium">
            </div>
          </div>
        </div>

        <!-- Submit Button (Matching Banterpool Brand CTA) -->
        <div class="pt-3">
          <button type="submit"
                  class="btn-primary w-full flex items-center justify-center gap-2.5 text-sm sm:text-base py-3 sm:py-3.5 rounded-xl shadow-lg shadow-brand/25">
            <i class="fa-solid fa-paper-plane text-sm"></i>
            <span>Simpan & Kirim ke Spreadsheet</span>
          </button>
        </div>

      </form>
    </div>

    <!-- ============================================== -->
    <!-- 5. LOG PEKERJAAN & PEMBUKUAN (TABS & TABLE)    -->
    <!-- ============================================== -->
    <div class="card card-lg p-4 sm:p-6 lg:p-8 min-w-0">

      <!-- Search & Action Bar -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-5 sm:mb-6">
        
        <!-- Search Input Form -->
        <form method="GET" action="{{ route('daily-report.index') }}" class="flex-1 flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
          <input type="hidden" name="tab" :value="activeTab">
          
          <div class="relative flex-1 min-w-0">
            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
            <input type="text" name="q" value="{{ $search }}" placeholder="Cari Pelanggan, PPPoE, Alamat, atau Mitra..."
                   class="inp pl-9 text-xs w-full">
          </div>

          <div class="flex items-center gap-2">
            <!-- Filter Job Type Dropdown -->
            <div class="relative flex-1 sm:w-56 md:w-60">
              <select name="job_type" onchange="this.form.submit()"
                      class="inp pr-8 text-xs font-semibold text-gray-800 w-full truncate">
                <option value="all" {{ $jobTypeFilter === 'all' ? 'selected' : '' }}>
                  Semua Tipe Pekerjaan
                </option>
                @foreach($jobTypes as $jt)
                  <option value="{{ $jt }}" {{ $jobTypeFilter === $jt ? 'selected' : '' }}>
                    {{ $jt }}
                  </option>
                @endforeach
              </select>
              <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-[10px]"></i>
            </div>

            @if(!empty($search) || $jobTypeFilter !== 'all')
              <a href="{{ route('daily-report.index') }}" class="icon-btn shrink-0" title="Reset Filter">
                <i class="fa-solid fa-xmark text-xs"></i>
              </a>
            @endif
          </div>
        </form>

        <!-- Export CSV Button -->
        <a href="{{ route('daily-report.export') }}" class="btn-ghost flex items-center justify-center gap-2 text-xs font-bold text-emerald-700 border-emerald-200 hover:bg-emerald-50 shrink-0 shadow-xs py-2.5 sm:py-2">
          <i class="fa-solid fa-file-csv text-base text-emerald-600"></i>
          <span>Export Spreadsheet</span>
        </a>

      </div>

      <!-- Tabs Navigation -->
      <div class="flex items-center gap-2 sm:gap-6 border-b border-gray-200 mb-5 sm:mb-6 overflow-x-auto no-scrollbar -mx-4 px-4 sm:mx-0 sm:px-0">
        <button type="button" class="tab-btn whitespace-nowrap text-xs sm:text-sm py-2.5 sm:py-3" :class="activeTab === 'jobs' ? 'active' : ''" @click="activeTab = 'jobs'">
          <i class="fa-solid fa-wrench" :class="activeTab === 'jobs' ? 'text-brand' : 'text-gray-400'"></i>
          <span>Log Pekerjaan Teknis</span>
          <span class="badge" :class="activeTab === 'jobs' ? 'badge-brand' : 'badge-slate'">
            {{ $reports->total() }}
          </span>
        </button>

        <button type="button" class="tab-btn whitespace-nowrap text-xs sm:text-sm py-2.5 sm:py-3" :class="activeTab === 'cash' ? 'active' : ''" @click="activeTab = 'cash'">
          <i class="fa-solid fa-wallet" :class="activeTab === 'cash' ? 'text-emerald-600' : 'text-gray-400'"></i>
          <span>Pembukuan Kas Lapangan</span>
          <span class="badge" :class="activeTab === 'cash' ? 'badge-green' : 'badge-slate'">
            {{ $cashTransactions->total() }}
          </span>
        </button>
      </div>

      <!-- TAB 1: Log Pekerjaan Teknis -->
      <div x-show="activeTab === 'jobs'">
        
        <!-- Mobile Card View (HP: screen < 768px) -->
        <div class="md:hidden space-y-3">
          @forelse($reports as $r)
            <div class="p-3.5 sm:p-4 rounded-xl border border-gray-200 bg-white shadow-2xs space-y-2.5 transition hover:border-gray-300">
              
              <!-- Card Header: Tgl & Mitra + Status Setor -->
              <div class="flex items-start justify-between gap-2 border-b border-gray-100 pb-2">
                <div class="min-w-0">
                  <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="text-xs font-black text-gray-900">{{ $r->report_date ? $r->report_date->format('d/m/Y') : '-' }}</span>
                    <span class="badge badge-brand text-[9px] font-bold">{{ $r->partner_company }}</span>
                  </div>
                  <p class="text-[10px] sm:text-[11px] text-gray-500 font-medium mt-0.5">Oleh: <strong class="text-gray-700">{{ $r->technician_name }}</strong></p>
                </div>
                
                <div class="shrink-0">
                  @if($r->cash_amount > 0)
                    @if($r->cash_deposit_status === 'Sudah Disetor')
                      <span class="badge badge-green font-bold text-[9px]">
                        <i class="fa-solid fa-check text-[8px]"></i> Disetor
                      </span>
                    @else
                      <span class="badge badge-amber font-bold text-[9px]">
                        <i class="fa-regular fa-clock text-[8px]"></i> Belum Setor
                      </span>
                    @endif
                  @else
                    <span class="text-gray-400 text-[10px]">—</span>
                  @endif
                </div>
              </div>

              <!-- Pelanggan & Alamat -->
              <div>
                <h4 class="font-extrabold text-xs sm:text-sm text-gray-900">{{ $r->customer_name }}</h4>
                <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                  <span class="text-xs font-mono text-brand font-semibold">{{ $r->pppoe_username ?: '-' }}</span>
                  @if($r->customer_phone)
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $r->customer_phone) }}" target="_blank"
                       class="text-[11px] text-emerald-600 font-semibold hover:underline flex items-center gap-1">
                      <i class="fa-brands fa-whatsapp"></i> {{ $r->customer_phone }}
                    </a>
                  @endif
                </div>
                @if($r->customer_address)
                  <p class="text-[11px] text-gray-500 mt-1 line-clamp-2 leading-snug">{{ $r->customer_address }}</p>
                @endif
              </div>

              <!-- Detail Grid: Tipe Pekerjaan & Rx Power -->
              <div class="grid grid-cols-2 gap-2 p-2.5 rounded-lg bg-gray-50/80 text-xs">
                <div class="min-w-0">
                  <span class="text-[9px] text-gray-400 font-bold uppercase block">Pekerjaan</span>
                  <span class="font-bold text-gray-800 text-[11px] block truncate mt-0.5">{{ $r->job_type }}</span>
                  <span class="text-[10px] text-gray-500 font-mono block mt-0.5 truncate">{{ $r->odp_port ?: '-' }}</span>
                </div>
                <div class="min-w-0">
                  <span class="text-[9px] text-gray-400 font-bold uppercase block">Rx Power / ONT</span>
                  <span class="font-mono font-bold text-amber-700 text-[11px] block mt-0.5 truncate">{{ $r->rx_power ? $r->rx_power . ' dBm' : '-' }}</span>
                  <span class="text-[10px] text-gray-500 font-mono block mt-0.5 truncate">{{ $r->ont_sn ?: '-' }}</span>
                </div>
              </div>

              <!-- Biaya Material & Kas Lapangan -->
              <div class="flex items-center justify-between gap-2 pt-1 border-t border-gray-100 text-xs">
                <div class="min-w-0">
                  <span class="text-[9px] text-gray-400 font-bold uppercase block">Material Digunakan</span>
                  <span class="font-extrabold text-gray-900 text-xs">{{ $r->formatted_material_cost }}</span>
                  <span class="text-[10px] text-gray-500 block truncate max-w-[150px] sm:max-w-[200px]" title="{{ $r->materials_summary_text }}">{{ $r->materials_summary_text }}</span>
                </div>
                <div class="text-right shrink-0">
                  <span class="text-[9px] text-gray-400 font-bold uppercase block">Kas Lapangan</span>
                  <span class="font-black {{ $r->cash_amount > 0 ? 'text-emerald-700' : 'text-gray-400' }} text-xs sm:text-sm block">
                    {{ $r->formatted_cash }}
                  </span>
                  <span class="text-[10px] text-gray-500 font-medium block">{{ $r->payment_method }}</span>
                </div>
              </div>

              <!-- Action Buttons Row -->
              <div class="flex items-center justify-end gap-2 pt-1.5 border-t border-gray-100">
                @if($r->cash_amount > 0 && $r->cash_deposit_status !== 'Sudah Disetor')
                  <form action="{{ route('daily-report.setor', $r->id) }}" method="POST" onsubmit="return confirm('Tandai kas sebesar {{ $r->formatted_cash }} sudah disetor?')">
                    @csrf
                    <button type="submit" class="btn-ghost flex items-center gap-1.5 text-xs py-1.5 px-3 font-bold text-emerald-700 border-emerald-300 hover:bg-emerald-50">
                      <i class="fa-solid fa-check text-xs"></i>
                      <span>Setor Kas</span>
                    </button>
                  </form>
                @endif

                <form action="{{ route('daily-report.destroy', $r->id) }}" method="POST" onsubmit="return confirm('Hapus data laporan harian ini?')">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="icon-btn w-8 h-8 text-brand hover:border-red-300 hover:bg-red-50" title="Hapus Laporan">
                    <i class="fa-regular fa-trash-can text-xs"></i>
                  </button>
                </form>
              </div>

            </div>
          @empty
            <div class="text-center py-10 text-gray-400 bg-gray-50/50 rounded-xl border border-dashed border-gray-200">
              <i class="fa-regular fa-folder-open text-3xl mb-2 text-gray-300 block"></i>
              <span class="text-xs">Belum ada data log pekerjaan teknis tercatat.</span>
            </div>
          @endforelse
        </div>

        <!-- Tablet & Desktop Table View (screen >= 768px) -->
        <div class="hidden md:block overflow-x-auto w-full rounded-xl border border-gray-100 shadow-2xs">
          <table class="tbl min-w-[780px]">
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
                    <span class="font-extrabold text-gray-900 block">{{ $r->report_date ? $r->report_date->format('d/m/Y') : '-' }}</span>
                    <span class="text-[11px] text-brand font-bold block">{{ $r->partner_company }}</span>
                    <span class="text-[10px] text-gray-500 block">Oleh: {{ $r->technician_name }}</span>
                  </td>

                  <td>
                    <span class="font-extrabold text-gray-900 block">{{ $r->customer_name }}</span>
                    <span class="text-[11px] text-gray-500 block font-mono font-medium">{{ $r->pppoe_username ?: '-' }}</span>
                    <span class="text-[10px] text-gray-400 block truncate max-w-[200px]">{{ $r->customer_address }}</span>
                  </td>

                  <td class="whitespace-nowrap">
                    <span class="badge badge-slate font-semibold">{{ $r->job_type }}</span>
                    <span class="text-[10px] text-gray-500 block mt-1 font-mono font-medium">{{ $r->odp_port ?: '-' }}</span>
                  </td>

                  <td class="whitespace-nowrap">
                    <span class="font-mono font-bold text-amber-700 block">{{ $r->rx_power ? $r->rx_power . ' dBm' : '-' }}</span>
                    <span class="text-[10px] text-gray-500 font-mono block">{{ $r->ont_sn ?: '-' }}</span>
                  </td>

                  <td class="whitespace-nowrap">
                    <span class="font-extrabold text-gray-900 block">{{ $r->formatted_material_cost }}</span>
                    <span class="text-[10px] text-gray-500 block truncate max-w-[170px]" title="{{ $r->materials_summary_text }}">
                      {{ $r->materials_summary_text }}
                    </span>
                  </td>

                  <td class="whitespace-nowrap">
                    <span class="font-black {{ $r->cash_amount > 0 ? 'text-emerald-700' : 'text-gray-400' }} block text-sm">
                      {{ $r->formatted_cash }}
                    </span>
                    <span class="text-[10px] text-gray-500 block font-medium">{{ $r->payment_method }}</span>
                  </td>

                  <td class="whitespace-nowrap">
                    @if($r->cash_amount > 0)
                      @if($r->cash_deposit_status === 'Sudah Disetor')
                        <span class="badge badge-green font-bold">Disetor</span>
                      @else
                        <span class="badge badge-amber font-bold">Belum Setor</span>
                      @endif
                    @else
                      <span class="text-gray-400 text-xs">—</span>
                    @endif
                  </td>

                  <td style="text-align: center;" class="whitespace-nowrap">
                    <div class="flex items-center justify-center gap-1.5">
                      @if($r->cash_amount > 0 && $r->cash_deposit_status !== 'Sudah Disetor')
                        <form action="{{ route('daily-report.setor', $r->id) }}" method="POST" onsubmit="return confirm('Tandai kas sebesar {{ $r->formatted_cash }} sudah disetor?')">
                          @csrf
                          <button type="submit" class="icon-btn w-8 h-8 text-emerald-600 hover:border-emerald-300 hover:bg-emerald-50" title="Tandai Sudah Disetor">
                            <i class="fa-solid fa-check text-xs"></i>
                          </button>
                        </form>
                      @endif

                      <form action="{{ route('daily-report.destroy', $r->id) }}" method="POST" onsubmit="return confirm('Hapus data laporan harian ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="icon-btn w-8 h-8 text-brand hover:border-red-300 hover:bg-red-50" title="Hapus Laporan">
                          <i class="fa-regular fa-trash-can text-xs"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="8" class="text-center py-12 text-gray-400">
                    <i class="fa-regular fa-folder-open text-3xl mb-2 text-gray-300 block"></i>
                    <span>Belum ada data log pekerjaan teknis tercatat.</span>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="pt-4 overflow-x-auto w-full">
          {{ $reports->links() }}
        </div>
      </div>

      <!-- TAB 2: Pembukuan Kas Lapangan -->
      <div x-show="activeTab === 'cash'" x-cloak>
        
        <!-- Mobile Card View (HP: screen < 768px) -->
        <div class="md:hidden space-y-3">
          @forelse($cashTransactions as $c)
            <div class="p-3.5 sm:p-4 rounded-xl border border-gray-200 bg-white shadow-2xs space-y-2.5 transition hover:border-gray-300">
              
              <!-- Card Header: Tgl, Jam, Status -->
              <div class="flex items-start justify-between gap-2 border-b border-gray-100 pb-2">
                <div>
                  <span class="text-xs font-black text-gray-900 block">{{ $c->report_date ? $c->report_date->format('d/m/Y') : '-' }}</span>
                  <span class="text-[10px] text-gray-500 font-medium">{{ $c->created_at ? $c->created_at->format('H:i') : '' }} WIB</span>
                </div>
                <div>
                  @if($c->cash_deposit_status === 'Sudah Disetor')
                    <span class="badge badge-green font-bold text-[9px]">
                      <i class="fa-solid fa-check text-[8px]"></i> Disetor
                    </span>
                  @else
                    <span class="badge badge-amber font-bold text-[9px]">
                      <i class="fa-regular fa-clock text-[8px]"></i> Belum Setor
                    </span>
                  @endif
                </div>
              </div>

              <!-- Detail Teknisi & Mitra & Pelanggan -->
              <div class="grid grid-cols-2 gap-2 text-xs">
                <div class="min-w-0">
                  <span class="text-[9px] text-gray-400 font-bold uppercase block">Teknisi &amp; Mitra</span>
                  <span class="font-extrabold text-gray-900 text-xs block truncate mt-0.5">{{ $c->technician_name }}</span>
                  <span class="text-[10px] text-brand font-bold block mt-0.5 truncate">{{ $c->partner_company }}</span>
                </div>
                <div class="min-w-0">
                  <span class="text-[9px] text-gray-400 font-bold uppercase block">Pelanggan</span>
                  <span class="font-extrabold text-gray-900 text-xs block truncate mt-0.5">{{ $c->customer_name }}</span>
                  <span class="text-[10px] text-gray-500 block mt-0.5 truncate">{{ $c->customer_phone ?: '-' }}</span>
                </div>
              </div>

              <!-- Transaksi & Nominal -->
              <div class="flex items-center justify-between gap-2 p-2.5 rounded-lg bg-emerald-50/60 border border-emerald-100 text-xs">
                <div class="min-w-0">
                  <span class="badge badge-slate text-[9px] font-semibold truncate">{{ $c->transaction_type }}</span>
                  <span class="text-[10px] text-gray-600 block mt-1 font-medium">{{ $c->payment_method }}</span>
                </div>
                <div class="text-right shrink-0">
                  <span class="text-[9px] text-emerald-800 font-bold uppercase block">Nominal Kas</span>
                  <span class="font-black text-emerald-700 text-sm sm:text-base block">{{ $c->formatted_cash }}</span>
                </div>
              </div>

              <!-- Action Button (if not yet deposited) -->
              @if($c->cash_deposit_status !== 'Sudah Disetor')
                <div class="flex items-center justify-end pt-1">
                  <form action="{{ route('daily-report.setor', $c->id) }}" method="POST" onsubmit="return confirm('Konfirmasi setor kas sebesar {{ $c->formatted_cash }}?')">
                    @csrf
                    <button type="submit" class="btn-ghost w-full sm:w-auto text-xs py-1.5 px-3 font-bold text-emerald-700 border-emerald-300 hover:bg-emerald-50 flex items-center justify-center gap-1.5">
                      <i class="fa-solid fa-check text-xs"></i>
                      <span>Konfirmasi Setor Kas</span>
                    </button>
                  </form>
                </div>
              @endif
            </div>
          @empty
            <div class="text-center py-10 text-gray-400 bg-gray-50/50 rounded-xl border border-dashed border-gray-200">
              <i class="fa-solid fa-wallet text-3xl mb-2 text-gray-300 block"></i>
              <span class="text-xs">Tidak ada catatan kas lapangan.</span>
            </div>
          @endforelse
        </div>

        <!-- Tablet & Desktop Table View (screen >= 768px) -->
        <div class="hidden md:block overflow-x-auto w-full rounded-xl border border-gray-100 shadow-2xs">
          <table class="tbl min-w-[780px]">
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
                  <span class="font-extrabold text-gray-900 block">{{ $c->report_date ? $c->report_date->format('d/m/Y') : '-' }}</span>
                  <span class="text-[10px] text-gray-500 font-medium">{{ $c->created_at ? $c->created_at->format('H:i') : '' }} WIB</span>
                </td>

                <td class="whitespace-nowrap">
                  <span class="font-extrabold text-gray-900 block">{{ $c->technician_name }}</span>
                  <span class="text-[11px] text-brand font-bold">{{ $c->partner_company }}</span>
                </td>

                <td>
                  <span class="font-extrabold text-gray-900 block">{{ $c->customer_name }}</span>
                  <span class="text-[10px] text-gray-500 block">{{ $c->customer_phone ?: '-' }}</span>
                </td>

                <td class="whitespace-nowrap">
                  <span class="badge badge-slate font-semibold">{{ $c->transaction_type }}</span>
                </td>

                <td class="whitespace-nowrap">
                  <span class="text-xs font-bold text-gray-700">{{ $c->payment_method }}</span>
                </td>

                <td class="whitespace-nowrap">
                  <span class="font-black text-emerald-700 text-sm">{{ $c->formatted_cash }}</span>
                </td>

                <td class="whitespace-nowrap">
                  @if($c->cash_deposit_status === 'Sudah Disetor')
                    <span class="badge badge-green font-bold">
                      <i class="fa-solid fa-check text-[9px]"></i> Sudah Disetor
                    </span>
                  @else
                    <span class="badge badge-amber font-bold">
                      <i class="fa-regular fa-clock text-[9px]"></i> Belum Disetor
                    </span>
                  @endif
                </td>

                <td style="text-align: center;" class="whitespace-nowrap">
                  @if($c->cash_deposit_status !== 'Sudah Disetor')
                    <form action="{{ route('daily-report.setor', $c->id) }}" method="POST" onsubmit="return confirm('Konfirmasi setor kas sebesar {{ $c->formatted_cash }}?')">
                      @csrf
                      <button type="submit" class="btn-ghost text-xs py-1.5 px-3 font-bold text-emerald-700 border-emerald-300 hover:bg-emerald-50">
                        Setor Kas
                      </button>
                    </form>
                  @else
                    <span class="text-gray-400 text-xs italic">Selesai</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center py-12 text-gray-400">
                  <i class="fa-solid fa-wallet text-3xl mb-2 text-gray-300 block"></i>
                  <span>Tidak ada catatan kas lapangan.</span>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="pt-4 overflow-x-auto w-full">
        {{ $cashTransactions->links() }}
      </div>
    </div>

    </div>

  </main>

  <!-- ============================================== -->
  <!-- MODAL: TAMBAH BARANG MANUAL / DARI STOK ADMIN  -->
  <!-- ============================================== -->
  <div x-show="openAddMaterialModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4">
    <div class="fixed inset-0 bg-black/40 backdrop-blur-xs" @click="openAddMaterialModal = false"></div>
    <div class="relative card card-lg w-full max-w-lg p-4 sm:p-6 text-gray-800 z-10 shadow-2xl max-h-[92vh] overflow-y-auto">
      
      <div class="flex items-center justify-between pb-4 border-b border-gray-200">
        <div class="flex items-center gap-2.5">
          <div class="w-9 h-9 rounded-xl bg-red-50 text-brand flex items-center justify-center text-sm border border-red-100 shrink-0">
            <i class="fa-solid fa-boxes-packing"></i>
          </div>
          <div>
            <h3 class="font-extrabold text-black text-sm sm:text-base">Tambah Barang ke Laporan</h3>
            <p class="text-[11px] sm:text-xs text-gray-500 font-medium">Pilih dari Stok Barang POV Admin atau input manual</p>
          </div>
        </div>
        <button type="button" @click="openAddMaterialModal = false" class="icon-btn w-8 h-8 shrink-0">
          <i class="fa-solid fa-xmark text-xs"></i>
        </button>
      </div>

      <!-- Mode Selector: Dari Stok Admin vs Input Manual Sepenuhnya -->
      <div class="flex items-center gap-2 mt-4 p-1 rounded-xl bg-gray-100 border border-gray-200">
        <button type="button" @click="addMode = 'stock'"
                class="flex-1 py-1.5 rounded-lg text-xs font-bold transition flex items-center justify-center gap-1.5"
                :class="addMode === 'stock' ? 'bg-brand text-white shadow-xs' : 'text-gray-600 hover:text-black'">
          <i class="fa-solid fa-database text-[10px]"></i>
          <span>Pilih dari Stok Admin</span>
        </button>
        <button type="button" @click="addMode = 'custom'"
                class="flex-1 py-1.5 rounded-lg text-xs font-bold transition flex items-center justify-center gap-1.5"
                :class="addMode === 'custom' ? 'bg-brand text-white shadow-xs' : 'text-gray-600 hover:text-black'">
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
                <select x-model="newMat.category" class="inp pr-9 text-xs font-semibold">
                  <option value="">-- Pilih Kategori --</option>
                  <template x-for="cat in stockCategories" :key="cat">
                    <option :value="cat" x-text="cat"></option>
                  </template>
                </select>
                <i class="fa-solid fa-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
              </div>
            </div>

            <div>
              <label class="lbl">Pilih Barang dari Stok Admin</label>
              <div class="relative">
                <select x-model="newMat.materialId" @change="onSelectStockItem()" class="inp pr-9 text-xs font-semibold">
                  <option value="">-- Pilih Barang (135+ item tersedia) --</option>
                  <template x-for="m in filteredStockMaterials" :key="m.id">
                    <option :value="m.id" x-text="m.name + ' (' + m.category + ' - Stok: ' + (m.stock || 0) + ' ' + m.unit + ')'"></option>
                  </template>
                </select>
                <i class="fa-solid fa-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
              </div>
            </div>
          </div>
        </template>

        <!-- Form fields: Nama, Satuan, Harga, Qty -->
        <div>
          <label class="lbl">Nama Barang</label>
          <input type="text" x-model="newMat.name" placeholder="Contoh: Modem ZTE F670L / Fast Connector" class="inp text-xs font-semibold">
        </div>

        <template x-if="addMode === 'custom'">
          <div>
            <label class="lbl">Kategori Barang</label>
            <div class="relative">
              <select x-model="newMat.category" class="inp pr-9 text-xs font-semibold">
                <option value="">-- Pilih Kategori --</option>
                <template x-for="cat in stockCategories" :key="cat">
                  <option :value="cat" x-text="cat"></option>
                </template>
              </select>
              <i class="fa-solid fa-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
            </div>
          </div>
        </template>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 sm:gap-3">
          <div>
            <label class="lbl">Satuan</label>
            <input type="text" x-model="newMat.unit" placeholder="pcs, m, unit" class="inp text-xs font-semibold">
          </div>

          <div>
            <label class="lbl">Harga Satuan (Rp)</label>
            <input type="number" x-model="newMat.price" min="0" step="500" class="inp text-xs font-mono font-bold">
          </div>

          <div>
            <label class="lbl">Jumlah (Qty)</label>
            <input type="number" x-model="newMat.qty" min="1" step="any" class="inp text-xs font-mono font-extrabold">
          </div>
        </div>

        <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-[11px] text-emerald-800 leading-relaxed font-medium">
          <i class="fa-solid fa-circle-check text-emerald-600 mr-1"></i>
          Barang yang ditambahkan otomatis terdaftar ke <strong>Log Stok Barang Masuk</strong> di Dashboard Admin untuk periode saat ini ({{ $currentMonthName ?? 'Bulan Sekarang' }}).
        </div>

      </div>

      <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-gray-200">
        <button type="button" @click="openAddMaterialModal = false" class="btn-ghost text-xs" :disabled="isSubmittingMaterial">
          Batal
        </button>
        <button type="button" @click="submitAddMaterial()" :disabled="isSubmittingMaterial" class="btn-primary text-xs py-2 px-4 flex items-center gap-1.5 disabled:opacity-50">
          <i class="fa-solid fa-spinner fa-spin text-[10px]" x-show="isSubmittingMaterial" x-cloak></i>
          <i class="fa-solid fa-plus text-[10px]" x-show="!isSubmittingMaterial"></i>
          <span x-text="isSubmittingMaterial ? 'Menyimpan & Mencatat...' : 'Tambahkan ke Laporan & Stok Masuk'"></span>
        </button>
      </div>

    </div>
  </div>

  <!-- ============================================== -->
  <!-- MODAL: PENGATURAN SPREADSHEET & WEBHOOK        -->
  <!-- ============================================== -->
  <div x-show="openSettings" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4">
    <div class="fixed inset-0 bg-black/40 backdrop-blur-xs" @click="openSettings = false"></div>
    <div class="relative card card-lg w-full max-w-md p-4 sm:p-6 text-gray-800 z-10 shadow-2xl max-h-[92vh] overflow-y-auto">
      <div class="flex items-center justify-between pb-4 border-b border-gray-200">
        <div class="flex items-center gap-2.5">
          <div class="w-9 h-9 rounded-xl bg-red-50 text-brand flex items-center justify-center text-sm border border-red-100 shrink-0">
            <i class="fa-solid fa-sliders"></i>
          </div>
          <div>
            <h3 class="text-sm font-extrabold text-black">Integrasi Spreadsheet</h3>
            <p class="text-[11px] text-gray-500 font-medium">Google Sheets / Apps Script Webhook</p>
          </div>
        </div>
        <button type="button" @click="openSettings = false" class="icon-btn w-8 h-8 shrink-0">
          <i class="fa-solid fa-xmark text-xs"></i>
        </button>
      </div>

      <div class="space-y-4 my-5 text-xs">
        <div>
          <label class="lbl">Webhook URL</label>
          <input type="url" x-model="gsWebhook" placeholder="https://script.google.com/macros/s/.../exec"
                 class="inp font-mono text-xs">
          <span class="text-[10px] text-gray-500 mt-1 block">
            Kosongkan untuk tetap menggunakan <strong>Demo Mode (Local Banterpool Sync)</strong>.
          </span>
        </div>

        <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-[11px] text-emerald-800 leading-relaxed font-medium">
          <p class="font-extrabold text-emerald-900 mb-1 flex items-center gap-1">
            <i class="fa-solid fa-circle-check text-emerald-600"></i> Status Aktif:
          </p>
          Setiap laporan tersimpan ke database lokal dan dapat langsung di-export sebagai file spreadsheet CSV kapan saja.
        </div>
      </div>

      <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-200">
        <button type="button" @click="openSettings = false" class="btn-ghost text-xs">Tutup</button>
        <button type="button" @click="saveSettings()" class="btn-primary text-xs py-2 px-4">Simpan</button>
      </div>
    </div>
  </div>

  <!-- ============================================== -->
  <!-- FOOTER (BANTERPOOL THEME)                      -->
  <!-- ============================================== -->
  <footer class="border-t border-gray-200/80 py-4 sm:py-5 text-center text-xs text-gray-500 bg-white/80 relative z-10">
    <div class="max-w-7xl mx-auto px-3.5 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-2">
      <span class="font-semibold text-gray-700">WiFi <strong class="text-brand">BANTERPOOL</strong> &bull; Daily Report Portal v2.6</span>
      <span>Sinkronisasi Master Stok Barang &amp; Mutasi Lapangan</span>
    </div>
  </footer>

  <!-- Script Animasi Background Garis Merah saat Scroll (Bergerak Halus) -->
  <script>
    (function () {
      const wave = document.getElementById('bgWavePattern');
      if (!wave) return;
      let ticking = false;

      window.addEventListener('scroll', function () {
        if (!ticking) {
          window.requestAnimationFrame(function () {
            const scrollY = window.pageYOffset || document.documentElement.scrollTop || 0;
            wave.style.transform = `translate3d(0, -${scrollY * 0.12}px, 0)`;
            ticking = false;
          });
          ticking = true;
        }
      }, { passive: true });
    })();
  </script>

</body>
</html>
