@extends('layouts.admin')

@section('title', 'Admin NOC Banterpool - Peta ODC & Jalur Fiber Optik')
@section('page-title', 'Peta Geospasial ODC, ODP & Jalur Fiber Optik (GIS NOC)')

@push('styles')
<style>
  #odcGisMap {
    height: 650px;
    width: 100%;
    border-radius: 1rem;
    z-index: 10;
  }
  /* Hilangkan watermark & overlay abu-abu 'For development purposes only' Google Maps */
  .gm-style-pbc,
  .gm-style-moc {
    display: none !important;
  }
  .gm-style div[style*="background-color: rgba(0, 0, 0"] {
    background-color: transparent !important;
  }
  .gm-style div[style*="z-index: 1000001"] {
    display: none !important;
  }
  .gm-err-container,
  .gm-err-content {
    display: none !important;
  }
</style>
@endpush

@section('content')
<div class="space-y-6" x-data="odcMapApp()">

  <!-- ============================================== -->
  <!-- 1. HEADER & GIS CONTROL BAR                    -->
  <!-- ============================================== -->
  <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
    <div>
      <div class="flex items-center gap-2">
        <i class="fa-solid fa-map-location-dot text-brand text-lg"></i>
        <h2 class="text-xl font-black text-slate-900 tracking-tight">Peta Persebaran ODC, ODP & Jalur Kabel Fiber Optik</h2>
      </div>
      <p class="text-xs text-slate-500 mt-0.5">
        Visualisasi pemetaan jaringan FTTH (*Fiber to the Home*) Banterpool area Kecamatan Cilongok, Kabupaten Banyumas.
      </p>
    </div>

    <!-- Quick Search & Reset Map View -->
    <div class="flex items-center gap-2.5">
      <div class="relative w-64">
        <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400 text-xs"></i>
        <select @change="focusNode($event.target.value)"
                class="w-full pl-9 pr-3 py-2 text-xs bg-white border border-slate-300 rounded-xl font-semibold text-slate-700 shadow-2xs focus:ring-brand focus:border-brand">
          <option value="">-- Cari ODC / ODP Jaringan --</option>
          <optgroup label="Optical Distribution Cabinet (ODC)">
            @foreach($mapData['odcs'] as $odc)
              <option value="odc:{{ $odc['id'] }}">{{ $odc['id'] }} - {{ $odc['name'] }}</option>
            @endforeach
          </optgroup>
          <optgroup label="Optical Distribution Point (ODP)">
            @foreach($mapData['odps'] as $odp)
              <option value="odp:{{ $odp['id'] }}">{{ $odp['id'] }} - {{ $odp['name'] }} ({{ $odp['status'] }})</option>
            @endforeach
          </optgroup>
        </select>
      </div>

      <button type="button" @click="resetView()"
              class="bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 font-bold text-xs px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 shadow-2xs">
        <i class="fa-solid fa-compress"></i>
        <span class="hidden sm:inline">Reset Posisi</span>
      </button>
    </div>
  </div>

  <!-- ============================================== -->
  <!-- 2. QUICK STATS & STATUS INFRASTRUKTUR          -->
  <!-- ============================================== -->
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
    
    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex items-center gap-3">
      <div class="w-10 h-10 rounded-xl bg-red-50 text-brand flex items-center justify-center text-lg shrink-0">
        <i class="fa-solid fa-server"></i>
      </div>
      <div>
        <p class="text-slate-400 font-medium">Total ODC Aktif</p>
        <p class="text-base font-black text-slate-900 mt-0.5">{{ count($mapData['odcs']) }} Cabinet <span class="text-[10px] text-slate-400 font-normal">(408 Core)</span></p>
      </div>
    </div>

    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex items-center gap-3">
      <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg shrink-0">
        <i class="fa-solid fa-boxes-stacked"></i>
      </div>
      <div>
        <p class="text-slate-400 font-medium">Total ODP Tersebar</p>
        <p class="text-base font-black text-slate-900 mt-0.5">{{ count($mapData['odps']) }} Titik <span class="text-[10px] text-slate-400 font-normal">(112 Port)</span></p>
      </div>
    </div>

    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex items-center gap-3">
      <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg shrink-0">
        <i class="fa-solid fa-route"></i>
      </div>
      <div>
        <p class="text-slate-400 font-medium">Jalur Kabel Utama</p>
        <p class="text-base font-black text-slate-900 mt-0.5">{{ count($mapData['cables']) }} Segmen FO</p>
      </div>
    </div>

    <!-- Alert Fiber Cut Indicator -->
    <div class="bg-red-50 rounded-2xl p-4 border border-red-200 shadow-xs flex items-center gap-3 cursor-pointer hover:bg-red-100 transition"
         @click="focusNode('odp:ODP-CLK-08')">
      <div class="w-10 h-10 rounded-xl bg-red-600 text-white flex items-center justify-center text-lg shrink-0 animate-bounce">
        <i class="fa-solid fa-triangle-exclamation"></i>
      </div>
      <div>
        <p class="text-red-600 font-bold">1 Gangguan Kabel (LOS)</p>
        <p class="text-[11px] text-red-700 mt-0.5 font-medium underline">Klik untuk cek ODP-CLK-08 &rarr;</p>
      </div>
    </div>

  </div>

  <!-- ============================================== -->
  <!-- 3. INTERACTIVE LEAFLET MAP & SIDEBAR DRAWER    -->
  <!-- ============================================== -->
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
    
    <!-- Peta Utama (8 Col di Desktop) -->
    <div class="lg:col-span-8 bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs space-y-4">
      
      <!-- Filter Layer Legenda -->
      <div class="flex flex-wrap items-center justify-between gap-3 text-xs bg-slate-50 p-3 rounded-xl border border-slate-200">
        <span class="font-bold text-slate-700">Filter Tampilan Peta:</span>

        <div class="flex flex-wrap items-center gap-4 text-[11px] font-semibold">
          <label class="flex items-center gap-1.5 cursor-pointer">
            <input type="checkbox" x-model="layers.odc" @change="toggleLayers()" class="rounded text-brand focus:ring-brand">
            <span class="w-2.5 h-2.5 rounded-full bg-brand"></span>
            <span>ODC (Kabinet)</span>
          </label>

          <label class="flex items-center gap-1.5 cursor-pointer">
            <input type="checkbox" x-model="layers.odp" @change="toggleLayers()" class="rounded text-emerald-600 focus:ring-emerald-500">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span>
            <span>ODP (Tiang)</span>
          </label>

          <label class="flex items-center gap-1.5 cursor-pointer">
            <input type="checkbox" x-model="layers.feeder" @change="toggleLayers()" class="rounded text-blue-600 focus:ring-blue-500">
            <span class="w-4 h-1 bg-blue-600 rounded"></span>
            <span>Kabel Feeder (Biru)</span>
          </label>

          <label class="flex items-center gap-1.5 cursor-pointer">
            <input type="checkbox" x-model="layers.distribution" @change="toggleLayers()" class="rounded text-green-600 focus:ring-green-500">
            <span class="w-4 h-1 bg-green-600 rounded"></span>
            <span>Kabel Distribusi</span>
          </label>
        </div>
      </div>

      <!-- Wadah Peta Leaflet -->
      <div class="relative rounded-xl overflow-hidden border border-slate-300 shadow-inner">
        <div id="odcGisMap"></div>
      </div>

    </div>

    <!-- Panel Informasi Detail Node (4 Col di Desktop) -->
    <div class="lg:col-span-4 bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs space-y-5">
      
      <!-- Panel Header -->
      <div class="flex items-center justify-between pb-3 border-b border-slate-100">
        <div>
          <span class="text-[10px] font-mono font-bold text-slate-400 uppercase">Detail Node Jaringan</span>
          <h3 class="text-base font-black text-slate-900" x-text="selectedItem ? selectedItem.name : 'Pilih Node di Peta'"></h3>
        </div>
        <template x-if="selectedItem">
          <span class="text-[10px] font-bold px-2 py-0.5 rounded-md"
                :class="selectedItem.status === 'LOS / Putus' ? 'bg-red-100 text-red-700' : (selectedItem.status === 'Warning' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700')"
                x-text="selectedItem.status"></span>
        </template>
      </div>

      <!-- Jika Belum Memilih Node -->
      <div x-show="!selectedItem" class="text-center py-12 text-slate-400 text-xs space-y-2">
        <i class="fa-solid fa-hand-pointer text-3xl text-slate-300 mb-1"></i>
        <p class="font-bold text-slate-600">Klik salah satu titik ODC atau ODP pada peta</p>
        <p class="text-[11px] text-slate-400">Untuk melihat kapasitas core, redaman optik, pelanggan terhubung, dan riwayat gangguan.</p>
      </div>

      <!-- Jika Memilih ODC -->
      <div x-show="selectedItem && selectedItem.type === 'odc'" class="space-y-4 text-xs" style="display: none;">
        
        <div class="bg-red-50/60 rounded-xl p-4 border border-red-200/80 space-y-2">
          <div class="flex justify-between">
            <span class="text-slate-500 font-medium">Kode ODC:</span>
            <span class="font-mono font-bold text-brand" x-text="selectedItem?.id"></span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500 font-medium">Lokasi Fisik:</span>
            <span class="font-bold text-slate-800 text-right max-w-[200px]" x-text="selectedItem?.location_desc"></span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500 font-medium">Kabel Feeder:</span>
            <span class="font-mono text-slate-700" x-text="selectedItem?.feeder_cable"></span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500 font-medium">Tingkat Redaman:</span>
            <span class="font-bold text-emerald-600 font-mono" x-text="selectedItem?.attenuation"></span>
          </div>
        </div>

        <!-- Progress Core Capacity Bar -->
        <div class="space-y-1.5">
          <div class="flex justify-between font-bold text-slate-700 text-[11px]">
            <span>Kapasitas Core Optik</span>
            <span x-text="selectedItem?.used + ' / ' + selectedItem?.capacity + ' Core Terpakai'"></span>
          </div>
          <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden flex">
            <div class="bg-brand h-full transition-all duration-500" :style="'width: ' + ((selectedItem?.used / selectedItem?.capacity) * 100) + '%'"></div>
          </div>
          <div class="flex justify-between text-[10px] text-slate-400 pt-0.5">
            <span x-text="'Tersedia: ' + selectedItem?.available + ' Core'"></span>
            <span x-text="Math.round((selectedItem?.used / selectedItem?.capacity) * 100) + '% Terisi'"></span>
          </div>
        </div>

        <!-- Splitter Specs -->
        <div class="border border-slate-200/80 rounded-xl p-3 bg-slate-50 text-[11px] space-y-1">
          <p class="font-bold text-slate-800">Spesifikasi Splitter & Modul:</p>
          <p class="text-slate-600" x-text="selectedItem?.splitters"></p>
          <p class="text-slate-400">Tahun Instalasi: <span x-text="selectedItem?.installed_year"></span></p>
        </div>

      </div>

      <!-- Jika Memilih ODP -->
      <div x-show="selectedItem && selectedItem.type === 'odp'" class="space-y-4 text-xs" style="display: none;">
        
        <div class="bg-slate-50 rounded-xl p-4 border border-slate-200/80 space-y-2">
          <div class="flex justify-between">
            <span class="text-slate-500 font-medium">ID ODP:</span>
            <span class="font-mono font-bold text-slate-900" x-text="selectedItem?.id"></span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500 font-medium">Induk ODC:</span>
            <span class="font-bold text-brand" x-text="selectedItem?.parent_odc"></span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500 font-medium">Kode Tiang:</span>
            <span class="font-mono text-slate-700" x-text="selectedItem?.pole_code"></span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500 font-medium">Redaman Input:</span>
            <span class="font-bold font-mono" :class="selectedItem?.attenuation === '-99.0 dBm' ? 'text-red-600 font-black' : 'text-emerald-600'" x-text="selectedItem?.attenuation"></span>
          </div>
        </div>

        <!-- Port Usage -->
        <div class="space-y-1.5">
          <div class="flex justify-between font-bold text-slate-700 text-[11px]">
            <span>Pemakaian Port Drop Core</span>
            <span x-text="selectedItem?.used + ' / ' + selectedItem?.capacity + ' Port'"></span>
          </div>
          <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden flex">
            <div class="bg-emerald-600 h-full transition-all duration-500" :style="'width: ' + ((selectedItem?.used / selectedItem?.capacity) * 100) + '%'"></div>
          </div>
        </div>

        <!-- Pelanggan Terkoneksi -->
        <div class="border border-slate-200/80 rounded-xl p-3 space-y-2">
          <p class="font-bold text-slate-800 text-xs flex items-center justify-between">
            <span>Pelanggan Terkoneksi:</span>
            <span class="text-[10px] text-slate-400 font-normal" x-text="selectedItem?.customers?.length + ' Pelanggan'"></span>
          </p>
          <ul class="divide-y divide-slate-100 text-[11px] space-y-1">
            <template x-for="cust in selectedItem?.customers" :key="cust">
              <li class="py-1 flex items-center gap-1.5 text-slate-700">
                <i class="fa-solid fa-house-signal text-brand text-[10px]"></i>
                <span x-text="cust"></span>
              </li>
            </template>
          </ul>
        </div>

        <!-- Tindakan Jika Terjadi Gangguan -->
        <template x-if="selectedItem?.status === 'LOS / Putus'">
          <div class="p-3 bg-red-50 border border-red-200 rounded-xl space-y-2">
            <p class="font-bold text-red-800 text-xs">Peringatan Gangguan FO Terdeteksi!</p>
            <p class="text-[11px] text-red-700">Kabel distribusi menuju ODP ini terputus (LOS). Cek antrean tiket gangguan untuk penugasan tim perbaikan.</p>
            <a href="{{ route('admin.laporan') }}" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 rounded-lg text-center block transition">
              Buka Tiket Perbaikan &rarr;
            </a>
          </div>
        </template>

      </div>

    </div>

  </div>

</div>

@push('scripts')
<script>
  // Cegah popup alert error bawaan Google Maps jika belum ada kartu kredit
  const _origAlert = window.alert;
  window.alert = function(msg) {
    if (typeof msg === 'string' && (msg.includes("Google Maps") || msg.includes("development purposes") || msg.includes("billing"))) {
      console.warn("Google Maps notice suppressed:", msg);
      return;
    }
    _origAlert.apply(window, arguments);
  };
</script>
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps_key') }}&libraries=places,geometry"></script>
<script>
  function odcMapApp() {
    return {
      map: null,
      selectedItem: null,
      infoWindow: null,
      layers: {
        odc: true,
        odp: true,
        feeder: true,
        distribution: true
      },
      mapMarkers: [],
      mapLines: [],

      init() {
        if (window.google && window.google.maps) {
          this.initMap();
        } else {
          window.addEventListener('load', () => this.initMap());
        }
      },

      initMap() {
        const mapContainer = document.getElementById('odcGisMap');
        if (!mapContainer || !window.google || !window.google.maps) return;

        const centerData = @json($mapData['center']);
        const zoomLevel = @json($mapData['zoom']);
        const center = { lat: parseFloat(centerData[0]), lng: parseFloat(centerData[1]) };

        this.infoWindow = new google.maps.InfoWindow();

        this.map = new google.maps.Map(mapContainer, {
          center: center,
          zoom: zoomLevel,
          mapTypeId: google.maps.MapTypeId.ROADMAP,
          mapTypeControl: true,
          mapTypeControlOptions: {
            style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR,
            position: google.maps.ControlPosition.TOP_RIGHT,
          },
          fullscreenControl: true,
          streetViewControl: true,
          zoomControl: true,
        });

        this.drawCables();
        this.drawMarkers();
      },

      drawCables() {
        const cables = @json($mapData['cables']);
        cables.forEach(c => {
          const path = c.coords.map(coord => ({ lat: parseFloat(coord[0]), lng: parseFloat(coord[1]) }));

          let polylineOptions = {
            path: path,
            geodesic: true,
            strokeColor: c.color,
            strokeOpacity: 0.9,
            strokeWeight: c.weight,
            map: this.map,
          };

          if (c.dashArray) {
            polylineOptions.strokeOpacity = 0;
            polylineOptions.icons = [{
              icon: {
                path: 'M 0,-1 0,1',
                strokeOpacity: 1,
                strokeColor: c.color,
                scale: 3
              },
              offset: '0',
              repeat: '14px'
            }];
          }

          const polyline = new google.maps.Polyline(polylineOptions);

          polyline.addListener('click', (e) => {
            const statusColor = c.status.includes('CUT') ? '#dc2626' : (c.status.includes('Warning') ? '#ca8a04' : '#16a34a');
            this.infoWindow.setContent(`
              <div style="font-family: 'Poppins', sans-serif; font-size: 12px; line-height: 1.4; color: #0f172a; padding: 4px;">
                <strong style="color: #0f172a; font-size: 13px;">${c.name}</strong><br>
                <span style="color: #64748b;">Tipe: <strong>${c.type.toUpperCase()}</strong></span><br>
                <span style="color: #64748b;">Status: <strong style="color: ${statusColor}">${c.status}</strong></span>
              </div>
            `);
            this.infoWindow.setPosition(e.latLng);
            this.infoWindow.open(this.map);
          });

          this.mapLines.push({ type: c.type, polyline });
        });
      },

      drawMarkers() {
        const odcs = @json($mapData['odcs']);
        const odps = @json($mapData['odps']);

        // 1. Draw ODCs
        const odcIcon = {
          url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 36 36">
              <filter id="odcShadow" x="-20%" y="-20%" width="140%" height="140%">
                <feDropShadow dx="0" dy="2" stdDeviation="2" flood-color="#000000" flood-opacity="0.3"/>
              </filter>
              <circle cx="18" cy="18" r="16" fill="#D31818" stroke="#ffffff" stroke-width="2.5" filter="url(#odcShadow)"/>
              <path d="M11 11h14v3.5H11zm0 5.2h14v3.5H11zm0 5.3h14V25H11z" fill="#ffffff"/>
              <circle cx="22" cy="12.7" r="1" fill="#22c55e"/>
              <circle cx="22" cy="18" r="1" fill="#22c55e"/>
              <circle cx="22" cy="23.2" r="1" fill="#22c55e"/>
            </svg>
          `),
          scaledSize: new google.maps.Size(36, 36),
          anchor: new google.maps.Point(18, 18),
        };

        odcs.forEach(odc => {
          const position = { lat: parseFloat(odc.lat), lng: parseFloat(odc.lng) };
          const marker = new google.maps.Marker({
            position: position,
            map: this.map,
            title: `${odc.id} - ${odc.name}`,
            icon: odcIcon,
            zIndex: 100,
          });

          marker.addListener('click', () => {
            this.selectedItem = { ...odc, type: 'odc' };
            this.infoWindow.setContent(`
              <div style="font-family: 'Poppins', sans-serif; font-size: 12px; line-height: 1.4; padding: 4px; color: #0f172a; max-width: 220px;">
                <div style="font-weight: 800; font-size: 13px; color: #dc2626;">${odc.id}</div>
                <div style="font-weight: 600; color: #1e293b;">${odc.name}</div>
                <div style="color: #64748b; font-size: 11px; margin-top: 2px;">Kapasitas: ${odc.used}/${odc.capacity} Core</div>
                <div style="color: #16a34a; font-weight: bold; font-size: 11px;">Redaman: ${odc.attenuation}</div>
              </div>
            `);
            this.infoWindow.open(this.map, marker);
          });

          this.mapMarkers.push({
            type: 'odc',
            id: odc.id,
            lat: parseFloat(odc.lat),
            lng: parseFloat(odc.lng),
            marker: marker,
            data: { ...odc, type: 'odc' }
          });
        });

        // 2. Draw ODPs
        odps.forEach(odp => {
          let pinColor = '#16a34a'; // Green (Normal)
          if (odp.status === 'LOS / Putus') {
            pinColor = '#dc2626'; // Red (Danger)
          } else if (odp.status === 'Warning' || odp.status === 'Penuh') {
            pinColor = '#ca8a04'; // Yellow (Warning)
          }

          const odpIcon = {
            url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
              <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 30 30">
                <filter id="odpShadow" x="-20%" y="-20%" width="140%" height="140%">
                  <feDropShadow dx="0" dy="2" stdDeviation="2" flood-color="#000000" flood-opacity="0.3"/>
                </filter>
                <circle cx="15" cy="15" r="13" fill="${pinColor}" stroke="#ffffff" stroke-width="2.5" filter="url(#odpShadow)"/>
                <circle cx="15" cy="15" r="5" fill="#ffffff"/>
              </svg>
            `),
            scaledSize: new google.maps.Size(30, 30),
            anchor: new google.maps.Point(15, 15),
          };

          const position = { lat: parseFloat(odp.lat), lng: parseFloat(odp.lng) };
          const marker = new google.maps.Marker({
            position: position,
            map: this.map,
            title: `${odp.id} (${odp.status})`,
            icon: odpIcon,
            zIndex: 50,
          });

          marker.addListener('click', () => {
            this.selectedItem = { ...odp, type: 'odp' };
            this.infoWindow.setContent(`
              <div style="font-family: 'Poppins', sans-serif; font-size: 12px; line-height: 1.4; padding: 4px; color: #0f172a; max-width: 220px;">
                <div style="font-weight: 800; font-size: 13px; color: ${pinColor};">${odp.id}</div>
                <div style="font-weight: 600; color: #1e293b;">${odp.name}</div>
                <div style="color: #64748b; font-size: 11px; margin-top: 2px;">Induk ODC: <strong>${odp.parent_odc}</strong></div>
                <div style="color: #64748b; font-size: 11px;">Port Terpakai: ${odp.used}/${odp.capacity}</div>
                <div style="font-weight: bold; font-size: 11px; color: ${odp.attenuation === '-99.0 dBm' ? '#dc2626' : '#16a34a'};">Redaman: ${odp.attenuation}</div>
              </div>
            `);
            this.infoWindow.open(this.map, marker);
          });

          this.mapMarkers.push({
            type: 'odp',
            id: odp.id,
            lat: parseFloat(odp.lat),
            lng: parseFloat(odp.lng),
            marker: marker,
            data: { ...odp, type: 'odp' }
          });
        });
      },

      focusNode(val) {
        if (!val) return;
        const [type, id] = val.split(':');
        const target = this.mapMarkers.find(m => m.id === id);
        if (target && this.map) {
          this.map.panTo({ lat: target.lat, lng: target.lng });
          this.map.setZoom(17);
          this.selectedItem = target.data;
          google.maps.event.trigger(target.marker, 'click');
        }
      },

      resetView() {
        if (!this.map) return;
        const centerData = @json($mapData['center']);
        const zoomLevel = @json($mapData['zoom']);
        this.map.panTo({ lat: parseFloat(centerData[0]), lng: parseFloat(centerData[1]) });
        this.map.setZoom(zoomLevel);
        this.selectedItem = null;
        if (this.infoWindow) this.infoWindow.close();
      },

      toggleLayers() {
        this.mapMarkers.forEach(m => {
          if (m.type === 'odc') {
            m.marker.setMap(this.layers.odc ? this.map : null);
          }
          if (m.type === 'odp') {
            m.marker.setMap(this.layers.odp ? this.map : null);
          }
        });

        this.mapLines.forEach(l => {
          if (l.type === 'feeder') {
            l.polyline.setMap(this.layers.feeder ? this.map : null);
          }
          if (l.type === 'distribution') {
            l.polyline.setMap(this.layers.distribution ? this.map : null);
          }
        });
      }
    };
  }
</script>
@endpush
@endsection
