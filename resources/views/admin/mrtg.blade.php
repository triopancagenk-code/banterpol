@extends('layouts.admin')

@section('title', 'Admin NOC Banterpool - MikroTik Monitoring')
@section('page-title', 'MikroTik Monitoring (Grafana)')

@section('content')
<div class="space-y-6" x-data="grafanaApp()">

  <!-- ============================================== -->
  <!-- 1. GRAFANA HEADER & DASHBOARD CONTROL BAR       -->
  <!-- ============================================== -->
  <div class="bg-[#111217] text-[#d8d9da] rounded-2xl p-4 sm:p-5 border border-[#22252b] shadow-xl space-y-4">
    
    <!-- Top Row: Breadcrumb, Title & Live Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-[#22252b]">
      <div class="flex items-center gap-3">
        <!-- Grafana Logo Icon -->
        <div class="w-9 h-9 rounded-xl bg-[#F05A28]/15 border border-[#F05A28]/30 flex items-center justify-center shrink-0">
          <svg class="w-5 h-5 text-[#F05A28]" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
          </svg>
        </div>

        <div>
          <div class="flex items-center gap-2">
            <span class="text-[11px] font-mono font-medium text-slate-400">Dashboards /</span>
            <h2 class="text-base sm:text-lg font-black text-white tracking-tight">Mikrotik monitoring</h2>
            <span class="text-amber-400 text-xs" title="Favorite"><i class="fa-solid fa-star"></i></span>
          </div>
          <div class="flex flex-wrap items-center gap-2 text-[11px] text-slate-400 mt-0.5">
            <span class="font-mono text-emerald-400">uid: nR3NRDGaz</span>
            <span>&bull;</span>
            <span class="px-1.5 py-0.2 rounded bg-[#1e2228] text-slate-300 font-mono text-[10px]">SNMP</span>
            <span class="px-1.5 py-0.2 rounded bg-[#1e2228] text-slate-300 font-mono text-[10px]">Prometheus</span>
            <span class="px-1.5 py-0.2 rounded bg-[#1e2228] text-slate-300 font-mono text-[10px]">RouterOS v7</span>
          </div>
        </div>
      </div>

      <!-- Right Action Controls -->
      <div class="flex flex-wrap items-center gap-2.5 text-xs font-semibold">
        <!-- Live Countdown & Refresh -->
        <div class="flex items-center gap-2 bg-[#181b1f] border border-[#2c323d] px-3 py-1.5 rounded-xl text-slate-300">
          <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
          <span class="text-[11px] text-slate-400">Refresh:</span>
          <span class="font-mono text-emerald-400 font-bold" x-text="countdown + 's'"></span>
          <button type="button" @click="toggleAutoRefresh()" class="hover:text-white transition" :title="isAutoRefresh ? 'Jeda Auto-Refresh' : 'Aktifkan Auto-Refresh'">
            <i class="fa-solid" :class="isAutoRefresh ? 'fa-pause text-amber-400' : 'fa-play text-emerald-400'"></i>
          </button>
        </div>

        <!-- Manual Trigger Button -->
        <button type="button" @click="fetchNewData()" class="bg-[#262a33] hover:bg-[#323846] text-white px-3 py-1.5 rounded-xl transition flex items-center gap-1.5 border border-[#3b4252]">
          <i class="fa-solid fa-rotate-right text-xs" :class="{ 'fa-spin': isRefreshing }"></i>
          <span>Refresh</span>
        </button>

        <!-- Time Range Selector (Last 30m) -->
        <div class="flex items-center gap-1.5 bg-[#181b1f] border border-[#2c323d] px-3 py-1.5 rounded-xl text-slate-200">
          <i class="fa-regular fa-clock text-slate-400 text-xs"></i>
          <span class="font-mono text-xs text-amber-300">now-30m &rarr; now</span>
        </div>
      </div>
    </div>

    <!-- Bottom Row: Template Variables Bar (Exact match with link parameters) -->
    <div class="flex flex-wrap items-center gap-2.5 pt-1 text-xs">
      
      <!-- Datasource Variable -->
      <div class="flex items-center bg-[#181b1f] border border-[#262a33] rounded-xl px-2.5 py-1.5 gap-2">
        <span class="text-[11px] font-bold text-slate-400 uppercase">DS_PROMETHEUS</span>
        <span class="font-mono text-xs text-cyan-400 font-semibold">{{ $grafanaParams['datasource'] }}</span>
      </div>

      <!-- Job Variable -->
      <div class="flex items-center bg-[#181b1f] border border-[#262a33] rounded-xl px-2.5 py-1.5 gap-2">
        <span class="text-[11px] font-bold text-slate-400 uppercase">Job</span>
        <span class="font-mono text-xs text-emerald-400 font-bold">Mikrotik</span>
      </div>

      <!-- Instance Selector Variable -->
      <div class="flex items-center bg-[#181b1f] border border-[#262a33] rounded-xl px-2.5 py-1.5 gap-2">
        <span class="text-[11px] font-bold text-slate-400 uppercase">instance</span>
        <select x-model="selectedInstance" @change="onFilterChange()"
                class="bg-transparent border-none text-white font-mono text-xs font-bold focus:ring-0 p-0 cursor-pointer">
          @foreach($mrtgData['instances'] as $ip => $desc)
            <option value="{{ $ip }}" class="bg-[#181b1f] text-white" {{ $grafanaParams['instance'] === $ip ? 'selected' : '' }}>{{ $desc }}</option>
          @endforeach
        </select>
      </div>

      <!-- Interface Variable -->
      <div class="flex items-center bg-[#181b1f] border border-[#262a33] rounded-xl px-2.5 py-1.5 gap-2">
        <span class="text-[11px] font-bold text-slate-400 uppercase">Interface</span>
        <select x-model="selectedInterface" @change="onFilterChange()"
                class="bg-transparent border-none text-amber-300 font-mono text-xs font-bold focus:ring-0 p-0 cursor-pointer">
          <option value="$__all" class="bg-[#181b1f] text-white">$__all (Semua Port)</option>
          @foreach($mrtgData['interfaces'] as $iface)
            <option value="{{ $iface['id'] }}" class="bg-[#181b1f] text-white" {{ $activeInterface['id'] === $iface['id'] ? 'selected' : '' }}>{{ $iface['name'] }}</option>
          @endforeach
        </select>
      </div>

      <!-- Simple Queue Variable -->
      <div class="flex items-center bg-[#181b1f] border border-[#262a33] rounded-xl px-2.5 py-1.5 gap-2">
        <span class="text-[11px] font-bold text-slate-400 uppercase">queuesimple_name</span>
        <select x-model="selectedSimpleQueue" @change="onFilterChange()"
                class="bg-transparent border-none text-purple-300 font-mono text-xs font-bold focus:ring-0 p-0 cursor-pointer">
          <option value="$__all" class="bg-[#181b1f] text-white">$__all (Semua Simple Queue)</option>
          @foreach($mrtgData['simple_queues'] as $q)
            <option value="{{ $q['name'] }}" class="bg-[#181b1f] text-white">{{ $q['name'] }}</option>
          @endforeach
        </select>
      </div>

      <!-- Tree Queue Variable -->
      <div class="flex items-center bg-[#181b1f] border border-[#262a33] rounded-xl px-2.5 py-1.5 gap-2">
        <span class="text-[11px] font-bold text-slate-400 uppercase">queuetree_name</span>
        <select x-model="selectedTreeQueue" @change="onFilterChange()"
                class="bg-transparent border-none text-blue-300 font-mono text-xs font-bold focus:ring-0 p-0 cursor-pointer">
          <option value="$__all" class="bg-[#181b1f] text-white">$__all (Semua Tree Queue)</option>
          @foreach($mrtgData['tree_queues'] as $tq)
            <option value="{{ $tq['name'] }}" class="bg-[#181b1f] text-white">{{ $tq['name'] }}</option>
          @endforeach
        </select>
      </div>

    </div>

  </div>

  <!-- ============================================== -->
  <!-- 2. SECTION: SYSTEM OVERVIEW (Grafana Stat Grid) -->
  <!-- ============================================== -->
  <div class="bg-[#111217] rounded-2xl p-4 sm:p-5 border border-[#22252b] shadow-xl space-y-3">
    
    <!-- Section Header Accordion -->
    <div class="flex items-center justify-between cursor-pointer" @click="toggleSection('system')">
      <div class="flex items-center gap-2">
        <i class="fa-solid text-xs text-slate-400" :class="sections.system ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
        <h3 class="text-xs font-extrabold text-white uppercase tracking-wider">System Overview & Router Specs</h3>
        <span class="text-[10px] bg-emerald-500/20 text-emerald-400 px-2 py-0.5 rounded font-mono font-bold">ONLINE</span>
      </div>
      <span class="text-[11px] text-slate-400 font-mono" x-text="currentTime"></span>
    </div>

    <!-- Stat Panels Grid -->
    <div x-show="sections.system" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2.5 pt-2">
      
      <!-- Uptime -->
      <div class="bg-[#181b1f] border border-[#262a33] p-3 rounded-xl border-l-4 border-l-emerald-500">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Uptime</p>
        <p class="text-sm font-black text-emerald-400 font-mono mt-1">{{ $mrtgData['summary']['uptime'] }}</p>
      </div>

      <!-- Status Device -->
      <div class="bg-[#181b1f] border border-[#262a33] p-3 rounded-xl border-l-4 border-l-emerald-500">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Status Device</p>
        <p class="text-sm font-black text-emerald-400 mt-1">{{ $mrtgData['router_info']['status'] }}</p>
      </div>

      <!-- Device Errors -->
      <div class="bg-[#181b1f] border border-[#262a33] p-3 rounded-xl border-l-4 border-l-cyan-500">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Device Errors</p>
        <p class="text-sm font-black text-cyan-300 font-mono mt-1">{{ $mrtgData['router_info']['device_errors'] }}</p>
      </div>

      <!-- System Identity -->
      <div class="bg-[#181b1f] border border-[#262a33] p-3 rounded-xl border-l-4 border-l-purple-500">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">System Identity</p>
        <p class="text-sm font-black text-purple-300 truncate mt-1">{{ $mrtgData['router_info']['identity'] }}</p>
      </div>

      <!-- Model -->
      <div class="bg-[#181b1f] border border-[#262a33] p-3 rounded-xl border-l-4 border-l-amber-500">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Model</p>
        <p class="text-sm font-black text-amber-300 truncate mt-1">{{ $mrtgData['router_info']['model'] }}</p>
      </div>

      <!-- Serial Number -->
      <div class="bg-[#181b1f] border border-[#262a33] p-3 rounded-xl border-l-4 border-l-slate-500">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Serial Number</p>
        <p class="text-sm font-black text-slate-200 font-mono truncate mt-1">{{ $mrtgData['router_info']['serial_number'] }}</p>
      </div>

      <!-- Board Ver -->
      <div class="bg-[#181b1f] border border-[#262a33] p-3 rounded-xl border-l-4 border-l-blue-500">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Board Ver</p>
        <p class="text-xs font-bold text-blue-300 font-mono mt-1">{{ $mrtgData['router_info']['board_ver'] }}</p>
      </div>

      <!-- CPU Frequency -->
      <div class="bg-[#181b1f] border border-[#262a33] p-3 rounded-xl border-l-4 border-l-indigo-500">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">CPU Frequency</p>
        <p class="text-xs font-bold text-indigo-300 truncate mt-1">{{ $mrtgData['router_info']['cpu_freq'] }}</p>
      </div>

      <!-- Active Fan -->
      <div class="bg-[#181b1f] border border-[#262a33] p-3 rounded-xl border-l-4 border-l-emerald-500">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Active Fan</p>
        <p class="text-xs font-bold text-emerald-300 truncate mt-1">{{ $mrtgData['router_info']['active_fan'] }}</p>
      </div>

      <!-- License Level -->
      <div class="bg-[#181b1f] border border-[#262a33] p-3 rounded-xl border-l-4 border-l-yellow-500">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">License Level</p>
        <p class="text-xs font-bold text-yellow-300 font-mono mt-1">{{ $mrtgData['router_info']['license_level'] }}</p>
      </div>

      <!-- Wi-Fi Client Count -->
      <div class="bg-[#181b1f] border border-[#262a33] p-3 rounded-xl border-l-4 border-l-pink-500">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Wi-Fi Client Count</p>
        <p class="text-sm font-black text-pink-300 font-mono mt-1">{{ $mrtgData['system_gauges']['wifi_clients'] }} Online</p>
      </div>

      <!-- Gateway Ping -->
      <div class="bg-[#181b1f] border border-[#262a33] p-3 rounded-xl border-l-4 border-l-teal-500">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Latency Gateway</p>
        <p class="text-sm font-black text-teal-300 font-mono mt-1">{{ $mrtgData['summary']['gateway_ping'] }}</p>
      </div>

    </div>

  </div>

  <!-- ============================================== -->
  <!-- 3. SECTION: HARDWARE GAUGES & HEALTH           -->
  <!-- ============================================== -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
    
    <!-- CPU Load Gauge -->
    <div class="bg-[#111217] rounded-2xl p-5 border border-[#22252b] shadow-xl flex flex-col justify-between text-center relative overflow-hidden">
      <div class="flex items-center justify-between text-xs text-slate-400 border-b border-[#22252b] pb-2">
        <span class="font-bold uppercase tracking-wider text-[11px]">CPU Load</span>
        <span class="font-mono text-[10px] text-emerald-400">avg(hrProcessorLoad)</span>
      </div>
      <div class="py-4 flex flex-col items-center justify-center">
        <div class="relative w-32 h-32 flex items-center justify-center">
          <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
            <path class="text-[#22252b]" stroke-width="3.5" stroke="currentColor" fill="none"
                  d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
            <path class="text-emerald-500 transition-all duration-1000 ease-out" stroke-dasharray="18, 100" stroke-width="3.5" stroke-linecap="round" stroke="currentColor" fill="none"
                  d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
          </svg>
          <div class="absolute flex flex-col items-center">
            <span class="text-3xl font-black text-white font-mono" x-text="cpuLoad + '%'">18%</span>
            <span class="text-[10px] text-emerald-400 font-bold uppercase">Optimal</span>
          </div>
        </div>
        <p class="text-xs text-slate-400 mt-2">Tilera 36-Core Processor @ 1.2GHz</p>
      </div>
    </div>

    <!-- RAM Load Gauge -->
    <div class="bg-[#111217] rounded-2xl p-5 border border-[#22252b] shadow-xl flex flex-col justify-between text-center relative overflow-hidden">
      <div class="flex items-center justify-between text-xs text-slate-400 border-b border-[#22252b] pb-2">
        <span class="font-bold uppercase tracking-wider text-[11px]">RAM Load</span>
        <span class="font-mono text-[10px] text-blue-400">hrStorageUsed/hrStorageSize</span>
      </div>
      <div class="py-4 flex flex-col items-center justify-center">
        <div class="relative w-32 h-32 flex items-center justify-center">
          <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
            <path class="text-[#22252b]" stroke-width="3.5" stroke="currentColor" fill="none"
                  d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
            <path class="text-blue-500 transition-all duration-1000 ease-out" stroke-dasharray="28, 100" stroke-width="3.5" stroke-linecap="round" stroke="currentColor" fill="none"
                  d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
          </svg>
          <div class="absolute flex flex-col items-center">
            <span class="text-3xl font-black text-white font-mono">28%</span>
            <span class="text-[10px] text-blue-400 font-bold uppercase">{{ $mrtgData['system_gauges']['ram_used'] }}</span>
          </div>
        </div>
        <p class="text-xs text-slate-400 mt-2">Kapasitas Total: 16.0 GB DDR3 ECC</p>
      </div>
    </div>

    <!-- Disk / NAND Load Gauge -->
    <div class="bg-[#111217] rounded-2xl p-5 border border-[#22252b] shadow-xl flex flex-col justify-between text-center relative overflow-hidden">
      <div class="flex items-center justify-between text-xs text-slate-400 border-b border-[#22252b] pb-2">
        <span class="font-bold uppercase tracking-wider text-[11px]">System Disk Load</span>
        <span class="font-mono text-[10px] text-amber-400">NAND Storage</span>
      </div>
      <div class="py-4 flex flex-col items-center justify-center">
        <div class="relative w-32 h-32 flex items-center justify-center">
          <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
            <path class="text-[#22252b]" stroke-width="3.5" stroke="currentColor" fill="none"
                  d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
            <path class="text-amber-500 transition-all duration-1000 ease-out" stroke-dasharray="14, 100" stroke-width="3.5" stroke-linecap="round" stroke="currentColor" fill="none"
                  d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
          </svg>
          <div class="absolute flex flex-col items-center">
            <span class="text-3xl font-black text-white font-mono">14%</span>
            <span class="text-[10px] text-amber-400 font-bold uppercase">{{ $mrtgData['system_gauges']['disk_used'] }}</span>
          </div>
        </div>
        <p class="text-xs text-slate-400 mt-2">Sisa Ruang: 880 MB / 1024 MB</p>
      </div>
    </div>

    <!-- CPU Temperature & POE -->
    <div class="bg-[#111217] rounded-2xl p-5 border border-[#22252b] shadow-xl flex flex-col justify-between text-center relative overflow-hidden">
      <div class="flex items-center justify-between text-xs text-slate-400 border-b border-[#22252b] pb-2">
        <span class="font-bold uppercase tracking-wider text-[11px]">CPU Temp & Power</span>
        <span class="font-mono text-[10px] text-red-400">Hardware Health</span>
      </div>
      <div class="py-4 flex flex-col items-center justify-center">
        <div class="relative w-32 h-32 flex items-center justify-center">
          <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
            <path class="text-[#22252b]" stroke-width="3.5" stroke="currentColor" fill="none"
                  d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
            <path class="text-emerald-400 transition-all duration-1000 ease-out" stroke-dasharray="46, 100" stroke-width="3.5" stroke-linecap="round" stroke="currentColor" fill="none"
                  d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
          </svg>
          <div class="absolute flex flex-col items-center">
            <span class="text-3xl font-black text-white font-mono">46°C</span>
            <span class="text-[10px] text-emerald-400 font-bold uppercase">Aman</span>
          </div>
        </div>
        <div class="flex items-center justify-center gap-3 text-[11px] text-slate-400 mt-2 font-mono">
          <span>{{ $mrtgData['system_gauges']['poe_voltage'] }}</span>
          <span>&bull;</span>
          <span>{{ $mrtgData['system_gauges']['poe_power'] }}</span>
        </div>
      </div>
    </div>

  </div>

  <!-- ============================================== -->
  <!-- 4. SECTION: NETWORK TRAFFIC BASIC & REALTIME    -->
  <!-- ============================================== -->
  <div class="bg-[#111217] rounded-2xl p-5 border border-[#22252b] shadow-xl space-y-4">
    
    <!-- Graph Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-[#22252b]">
      <div>
        <div class="flex items-center gap-2">
          <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
          <h3 class="text-sm sm:text-base font-extrabold text-white">Network Traffic Basic: {{ $activeInterface['name'] }}</h3>
          <span class="text-xs font-mono text-slate-400">({{ $activeInterface['mac'] }})</span>
        </div>
        <p class="text-xs text-slate-400 mt-0.5">
          SNMP OID: <strong class="text-slate-300 font-mono">ifHCInOctets / ifHCOutOctets (bps)</strong> &bull; Link Speed: <strong class="text-emerald-400 font-mono">{{ $activeInterface['speed'] }}</strong>
        </p>
      </div>

      <!-- Quick Port Switcher Pills -->
      <div class="flex flex-wrap items-center gap-1.5 text-xs font-mono">
        @foreach($mrtgData['interfaces'] as $iface)
          <button type="button" @click="changeInterface('{{ $iface['id'] }}')"
                  class="px-2.5 py-1 rounded-lg transition text-[11px] font-bold {{ $activeInterface['id'] === $iface['id'] ? 'bg-emerald-500 text-black font-black' : 'bg-[#181b1f] text-slate-400 hover:text-white border border-[#262a33]' }}">
            {{ $iface['name'] }}
          </button>
        @endforeach
      </div>
    </div>

    <!-- Chart Canvas Container -->
    <div class="h-80 sm:h-96 relative w-full">
      <canvas id="grafanaTrafficChart"></canvas>
    </div>

    <!-- Grafana Legend Summary -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-2 text-xs font-mono">
      
      <!-- Inbound / Rx -->
      <div class="bg-[#181b1f] border border-[#262a33] p-3 rounded-xl border-l-4 border-l-emerald-500 flex flex-col justify-between">
        <div class="flex items-center justify-between">
          <span class="font-extrabold text-emerald-400 flex items-center gap-2">
            <span class="w-2.5 h-2.5 bg-emerald-500 rounded-xs"></span>
            Inbound / Download (Rx)
          </span>
          <span class="text-emerald-400 font-bold" x-text="currentRxFormatted">4.82 Gbps</span>
        </div>
        <div class="grid grid-cols-3 gap-2 pt-2 border-t border-[#262a33] text-[11px] text-slate-400 mt-2">
          <div>
            <span class="block text-[10px] text-slate-500">Current:</span>
            <strong class="text-white" x-text="currentRxFormatted">4.82 Gbps</strong>
          </div>
          <div>
            <span class="block text-[10px] text-slate-500">Average:</span>
            <strong class="text-white">{{ $activeInterface['rx_avg'] }} Mbps</strong>
          </div>
          <div>
            <span class="block text-[10px] text-slate-500">Peak:</span>
            <strong class="text-emerald-300">{{ $activeInterface['rx_peak'] }} Mbps</strong>
          </div>
        </div>
      </div>

      <!-- Outbound / Tx -->
      <div class="bg-[#181b1f] border border-[#262a33] p-3 rounded-xl border-l-4 border-l-blue-500 flex flex-col justify-between">
        <div class="flex items-center justify-between">
          <span class="font-extrabold text-blue-400 flex items-center gap-2">
            <span class="w-2.5 h-2.5 bg-blue-500 rounded-xs"></span>
            Outbound / Upload (Tx)
          </span>
          <span class="text-blue-400 font-bold" x-text="currentTxFormatted">2.94 Gbps</span>
        </div>
        <div class="grid grid-cols-3 gap-2 pt-2 border-t border-[#262a33] text-[11px] text-slate-400 mt-2">
          <div>
            <span class="block text-[10px] text-slate-500">Current:</span>
            <strong class="text-white" x-text="currentTxFormatted">2.94 Gbps</strong>
          </div>
          <div>
            <span class="block text-[10px] text-slate-500">Average:</span>
            <strong class="text-white">{{ $activeInterface['tx_avg'] }} Mbps</strong>
          </div>
          <div>
            <span class="block text-[10px] text-slate-500">Peak:</span>
            <strong class="text-blue-300">{{ $activeInterface['tx_peak'] }} Mbps</strong>
          </div>
        </div>
      </div>

    </div>

  </div>

  <!-- ============================================== -->
  <!-- 5. SECTION: INTERFACES STATUS TABLE (SNMP)     -->
  <!-- ============================================== -->
  <div class="bg-[#111217] rounded-2xl border border-[#22252b] shadow-xl overflow-hidden">
    <div class="p-4 sm:p-5 border-b border-[#22252b] flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div class="flex items-center gap-2">
        <i class="fa-solid fa-network-wired text-emerald-400 text-sm"></i>
        <h3 class="text-sm font-extrabold text-white">Interfaces Table (SNMP MIKROTIK-MIB)</h3>
      </div>
      <span class="text-xs font-mono text-slate-400">Total {{ count($mrtgData['interfaces']) }} Interfaces Terpantau</span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs font-mono text-slate-300">
        <thead class="bg-[#181b1f] text-slate-400 uppercase font-bold text-[10px] tracking-wider border-b border-[#22252b]">
          <tr>
            <th class="px-5 py-3">Interface</th>
            <th class="px-4 py-3">Device</th>
            <th class="px-4 py-3">Status</th>
            <th class="px-4 py-3">Speed</th>
            <th class="px-4 py-3">MAC Address</th>
            <th class="px-4 py-3 text-right">Rx Rate (In)</th>
            <th class="px-4 py-3 text-right">Tx Rate (Out)</th>
            <th class="px-4 py-3 text-right">Packets</th>
            <th class="px-5 py-3 text-center">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[#22252b]">
          @foreach($mrtgData['interfaces'] as $iface)
            <tr class="hover:bg-[#181b1f] transition {{ $activeInterface['id'] === $iface['id'] ? 'bg-[#181b1f] ring-1 ring-emerald-500/40' : '' }}">
              <td class="px-5 py-3 font-bold text-white flex items-center gap-2">
                <span class="w-2 h-2 rounded-full {{ $iface['status'] === 'UP' ? 'bg-emerald-400' : 'bg-red-500' }}"></span>
                {{ $iface['name'] }}
              </td>
              <td class="px-4 py-3 text-slate-400">{{ $iface['device'] }}</td>
              <td class="px-4 py-3">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $iface['status'] === 'UP' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-red-500/20 text-red-400 border border-red-500/30' }}">
                  {{ $iface['status'] }}
                </span>
              </td>
              <td class="px-4 py-3 text-slate-400">{{ $iface['speed'] }}</td>
              <td class="px-4 py-3 text-slate-500">{{ $iface['mac'] }}</td>
              <td class="px-4 py-3 text-right font-extrabold text-emerald-400">{{ $iface['rx_current'] }} Mbps</td>
              <td class="px-4 py-3 text-right font-extrabold text-blue-400">{{ $iface['tx_current'] }} Mbps</td>
              <td class="px-4 py-3 text-right text-slate-400">{{ $iface['rx_packets'] }}</td>
              <td class="px-5 py-3 text-center">
                <button type="button" @click="changeInterface('{{ $iface['id'] }}')"
                        class="px-2.5 py-1 rounded bg-[#262a33] hover:bg-emerald-600 hover:text-black font-bold text-[11px] transition">
                  Grafik
                </button>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <!-- ============================================== -->
  <!-- 6. SECTION: SIMPLE QUEUE & TREE QUEUE          -->
  <!-- ============================================== -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    
    <!-- Simple Queue Panel -->
    <div class="bg-[#111217] rounded-2xl border border-[#22252b] shadow-xl p-5 space-y-4">
      <div class="flex items-center justify-between pb-3 border-b border-[#22252b]">
        <div class="flex items-center gap-2">
          <i class="fa-solid fa-layer-group text-purple-400 text-sm"></i>
          <h3 class="text-xs font-extrabold text-white uppercase tracking-wider">Simple Queue (In/Out/Dropped/PCQ)</h3>
        </div>
        <span class="text-[10px] font-mono text-purple-400 bg-purple-500/10 px-2 py-0.5 rounded border border-purple-500/20">mtxrQueueSimple</span>
      </div>

      <div class="space-y-3">
        @foreach($mrtgData['simple_queues'] as $sq)
          <div class="bg-[#181b1f] border border-[#262a33] p-3 rounded-xl space-y-2">
            <div class="flex items-center justify-between">
              <div>
                <p class="font-bold text-white text-xs font-mono">{{ $sq['name'] }}</p>
                <p class="text-[10px] font-mono text-slate-400">Target: {{ $sq['target'] }} &bull; Max Limit: {{ $sq['max_limit'] }}</p>
              </div>
              <div class="text-right">
                <span class="text-emerald-400 font-mono font-bold text-xs"><i class="fa-solid fa-arrow-down text-[10px]"></i> {{ $sq['rx_rate'] }}</span>
                <span class="text-blue-400 font-mono font-bold text-xs ml-2"><i class="fa-solid fa-arrow-up text-[10px]"></i> {{ $sq['tx_rate'] }}</span>
              </div>
            </div>

            <!-- Throughput Bar -->
            <div class="w-full bg-[#262a33] h-1.5 rounded-full overflow-hidden flex">
              <div class="bg-emerald-500 h-full" style="width: {{ ($sq['rx_num'] / 50) * 100 }}%"></div>
            </div>

            <div class="flex items-center justify-between text-[10px] font-mono text-slate-400 pt-1">
              <span>PCQ Queues: <strong class="text-white">{{ $sq['pcq_in'] }} In / {{ $sq['pcq_out'] }} Out</strong></span>
              <span class="text-emerald-400">Dropped: 0 pkts</span>
            </div>
          </div>
        @endforeach
      </div>
    </div>

    <!-- Tree Queue Panel -->
    <div class="bg-[#111217] rounded-2xl border border-[#22252b] shadow-xl p-5 space-y-4">
      <div class="flex items-center justify-between pb-3 border-b border-[#22252b]">
        <div class="flex items-center gap-2">
          <i class="fa-solid fa-sitemap text-blue-400 text-sm"></i>
          <h3 class="text-xs font-extrabold text-white uppercase tracking-wider">Tree Queue (Bandwidth Allocation)</h3>
        </div>
        <span class="text-[10px] font-mono text-blue-400 bg-blue-500/10 px-2 py-0.5 rounded border border-blue-500/20">mtxrQueueTree</span>
      </div>

      <div class="space-y-3">
        @foreach($mrtgData['tree_queues'] as $tq)
          <div class="bg-[#181b1f] border border-[#262a33] p-3 rounded-xl space-y-2">
            <div class="flex items-center justify-between">
              <div>
                <p class="font-bold text-white text-xs font-mono flex items-center gap-1.5">
                  <span class="w-1.5 h-1.5 rounded-full bg-cyan-400"></span>
                  {{ $tq['name'] }}
                </p>
                <p class="text-[10px] font-mono text-slate-400">Parent: {{ $tq['parent'] }} &bull; Flow: {{ $tq['flow'] }}</p>
              </div>
              <div class="text-right">
                <span class="text-cyan-300 font-mono font-bold text-xs">{{ $tq['current'] }}</span>
                <span class="text-slate-400 text-[10px] block">Limit: {{ $tq['max_limit'] }}</span>
              </div>
            </div>

            <!-- Progress bar -->
            <div class="w-full bg-[#262a33] h-1.5 rounded-full overflow-hidden flex">
              <div class="bg-cyan-500 h-full" style="width: 48%"></div>
            </div>

            <div class="flex items-center justify-between text-[10px] font-mono text-slate-400 pt-1">
              <span>PCQ: <strong class="text-white">{{ $tq['pcq'] }}</strong> &bull; {{ $tq['packets'] }}</span>
              <span class="text-emerald-400">Dropped: {{ $tq['dropped'] }}</span>
            </div>
          </div>
        @endforeach
      </div>
    </div>

  </div>

  <!-- ============================================== -->
  <!-- 7. SECTION: NEIGHBORS (MNDP / LLDP DISCOVERY)  -->
  <!-- ============================================== -->
  <div class="bg-[#111217] rounded-2xl border border-[#22252b] shadow-xl p-5 space-y-4">
    <div class="flex items-center justify-between pb-3 border-b border-[#22252b]">
      <div class="flex items-center gap-2">
        <i class="fa-solid fa-diagram-project text-amber-400 text-sm"></i>
        <h3 class="text-xs font-extrabold text-white uppercase tracking-wider">Neighbors Discovery (MNDP / LLDP)</h3>
      </div>
      <span class="text-[10px] font-mono text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded border border-amber-500/20">mtxrNeighbor</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
      @foreach($mrtgData['neighbors'] as $nb)
        <div class="bg-[#181b1f] border border-[#262a33] p-3.5 rounded-xl space-y-1.5 font-mono text-xs">
          <div class="flex items-center justify-between">
            <span class="font-bold text-amber-300 text-xs truncate">{{ $nb['identity'] }}</span>
            <span class="text-[10px] text-slate-400">{{ $nb['interface'] }}</span>
          </div>
          <p class="text-slate-300 text-[11px]"><i class="fa-solid fa-globe text-slate-500 mr-1"></i> IP: {{ $nb['ip'] }}</p>
          <p class="text-slate-400 text-[10px]"><i class="fa-solid fa-fingerprint text-slate-500 mr-1"></i> MAC: {{ $nb['mac'] }}</p>
          <div class="pt-1.5 border-t border-[#262a33] flex items-center justify-between text-[10px] text-slate-400">
            <span>{{ $nb['platform'] }}</span>
            <span class="text-emerald-400 font-bold">{{ $nb['software'] }}</span>
          </div>
        </div>
      @endforeach
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
  function grafanaApp() {
    return {
      selectedInstance: '{{ $grafanaParams['instance'] }}',
      selectedInterface: '{{ $activeInterface['id'] }}',
      selectedSimpleQueue: '{{ $grafanaParams['queuesimple_name'] }}',
      selectedTreeQueue: '{{ $grafanaParams['queuetree_name'] }}',
      isAutoRefresh: true,
      isRefreshing: false,
      countdown: 10,
      intervalId: null,
      chartInstance: null,
      currentRx: {{ $activeInterface['rx_current'] }},
      currentTx: {{ $activeInterface['tx_current'] }},
      cpuLoad: {{ $mrtgData['system_gauges']['cpu_load'] }},
      currentTime: '{{ now()->format('d M Y H:i:s') }} WIB',
      sections: {
        system: true,
      },

      get currentRxFormatted() {
        if (this.currentRx >= 1000) {
          return (this.currentRx / 1000).toFixed(2) + ' Gbps';
        }
        return this.currentRx.toFixed(0) + ' Mbps';
      },

      get currentTxFormatted() {
        if (this.currentTx >= 1000) {
          return (this.currentTx / 1000).toFixed(2) + ' Gbps';
        }
        return this.currentTx.toFixed(0) + ' Mbps';
      },

      init() {
        this.renderChart();
        this.startTimer();
      },

      startTimer() {
        this.intervalId = setInterval(() => {
          if (this.isAutoRefresh) {
            if (this.countdown > 1) {
              this.countdown--;
            } else {
              this.countdown = 10;
              this.fetchNewData();
            }
          }
        }, 1000);
      },

      toggleAutoRefresh() {
        this.isAutoRefresh = !this.isAutoRefresh;
        if (this.isAutoRefresh) {
          this.countdown = 10;
        }
      },

      toggleSection(section) {
        this.sections[section] = !this.sections[section];
      },

      changeInterface(ifId) {
        const url = new URL(window.location.href);
        url.searchParams.set('interface', ifId);
        window.location.href = url.toString();
      },

      onFilterChange() {
        const url = new URL(window.location.href);
        url.searchParams.set('instance', this.selectedInstance);
        url.searchParams.set('interface', this.selectedInterface);
        url.searchParams.set('queuesimple_name', this.selectedSimpleQueue);
        url.searchParams.set('queuetree_name', this.selectedTreeQueue);
        window.location.href = url.toString();
      },

      fetchNewData() {
        this.isRefreshing = true;

        // Simulasi live stream jitter
        setTimeout(() => {
          const rxJitter = (Math.random() * 200) - 100;
          const txJitter = (Math.random() * 120) - 60;
          const cpuJitter = (Math.random() * 4) - 2;

          this.currentRx = Math.max(500, Math.round({{ $activeInterface['rx_current'] }} + rxJitter));
          this.currentTx = Math.max(300, Math.round({{ $activeInterface['tx_current'] }} + txJitter));
          this.cpuLoad = Math.min(95, Math.max(10, Math.round({{ $mrtgData['system_gauges']['cpu_load'] }} + cpuJitter)));

          const now = new Date();
          const timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
          this.currentTime = now.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) + ' ' + timeStr + ' WIB';

          if (this.chartInstance) {
            const labels = this.chartInstance.data.labels;
            const rxData = this.chartInstance.data.datasets[0].data;
            const txData = this.chartInstance.data.datasets[1].data;

            labels.push(timeStr);
            rxData.push(this.currentRx);
            txData.push(this.currentTx);

            if (labels.length > 15) {
              labels.shift();
              rxData.shift();
              txData.shift();
            }

            this.chartInstance.update('none');
          }

          this.isRefreshing = false;
        }, 500);
      },

      renderChart() {
        const ctx = document.getElementById('grafanaTrafficChart');
        if (!ctx) return;

        const historyLabels = @json($activeInterface['history']['labels']);
        const rxHistory = @json($activeInterface['history']['inbound']);
        const txHistory = @json($activeInterface['history']['outbound']);

        // Gradient Inbound (Emerald)
        const gradientRx = ctx.getContext('2d').createLinearGradient(0, 0, 0, 360);
        gradientRx.addColorStop(0, 'rgba(16, 185, 129, 0.45)');
        gradientRx.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

        // Gradient Outbound (Blue)
        const gradientTx = ctx.getContext('2d').createLinearGradient(0, 0, 0, 360);
        gradientTx.addColorStop(0, 'rgba(59, 130, 246, 0.45)');
        gradientTx.addColorStop(1, 'rgba(59, 130, 246, 0.0)');

        this.chartInstance = new Chart(ctx, {
          type: 'line',
          data: {
            labels: historyLabels,
            datasets: [
              {
                label: 'Inbound / Download (Rx)',
                data: rxHistory,
                borderColor: '#10b981',
                borderWidth: 2,
                backgroundColor: gradientRx,
                fill: true,
                tension: 0.35,
                pointRadius: 2,
                pointHoverRadius: 6,
                pointHoverBackgroundColor: '#10b981',
              },
              {
                label: 'Outbound / Upload (Tx)',
                data: txHistory,
                borderColor: '#3b82f6',
                borderWidth: 2,
                backgroundColor: gradientTx,
                fill: true,
                tension: 0.35,
                pointRadius: 2,
                pointHoverRadius: 6,
                pointHoverBackgroundColor: '#3b82f6',
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
              mode: 'index',
              intersect: false,
            },
            plugins: {
              legend: {
                display: false
              },
              tooltip: {
                backgroundColor: 'rgba(17, 18, 23, 0.95)',
                titleColor: '#ffffff',
                bodyColor: '#d8d9da',
                borderColor: '#2c323d',
                borderWidth: 1,
                padding: 12,
                boxPadding: 6,
                usePointStyle: true,
                callbacks: {
                  label: (c) => ` ${c.dataset.label}: ${c.parsed.y >= 1000 ? (c.parsed.y/1000).toFixed(2) + ' Gbps' : c.parsed.y + ' Mbps'}`
                }
              }
            },
            scales: {
              x: {
                grid: {
                  color: 'rgba(255, 255, 255, 0.05)',
                },
                ticks: {
                  color: '#8e8e93',
                  font: {
                    family: 'monospace',
                    size: 10
                  }
                }
              },
              y: {
                grid: {
                  color: 'rgba(255, 255, 255, 0.06)',
                },
                ticks: {
                  color: '#8e8e93',
                  font: {
                    family: 'monospace',
                    size: 10
                  },
                  callback: (v) => v >= 1000 ? (v / 1000) + ' Gbps' : v + ' Mbps'
                }
              }
            }
          }
        });
      }
    };
  }
</script>
@endpush
