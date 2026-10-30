@extends('layouts.admin')
@section('title', 'Admin NOC Banterpool - Peta ODC, ODP & Jaringan Fiber Optik')
@section('page-title', 'Peta Geospasial Infrastruktur Threefive Network (GIS NOC)')

@push('styles')
<!-- CSS Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    .map-container {
        width: 100%;
        height: 78vh;
        min-height: 650px;
        border-radius: 1rem;
        overflow: hidden;
        position: relative;
        background: #0b0e14;
    }
    #odcGisMap {
        width: 100%;
        height: 100%;
        background: #0b0e14;
    }

    /* Dark Mode Filter Ringan OSM */
    .map-dark-theme {
        filter: brightness(0.6) invert(1) contrast(3) hue-rotate(200deg) saturate(0.3) brightness(0.7);
    }

    /* Animasi Kabel Fiber Optik */
    @keyframes cableFlow {
        to { stroke-dashoffset: -28; }
    }
    .cable-flow {
        stroke-dasharray: 6 8 !important;
        animation: cableFlow 1.5s linear infinite !important;
    }

    /* Pewarnaan Solid Jalur Kabel */
    .cable-main-server { stroke: #ef4444 !important; stroke-width: 3.5px !important; }
    .cable-dropcore    { stroke: #38bdf8 !important; stroke-width: 2.5px !important; }
    .cable-kasegeran   { stroke: #d946ef !important; }
    .cable-jatisaba    { stroke: #a3e635 !important; }
    .cable-pageraji    { stroke: #00f3ff !important; }
    .cable-karangendep { stroke: #fb923c !important; }
    .cable-panusupan   { stroke: #22c55e !important; }
    .cable-cipete      { stroke: #f43f5e !important; }
    .cable-cikidang    { stroke: #facc15 !important; }
    .cable-default     { stroke: #ffffff !important; }

    /* Ikon Tiang Bendera Solid */
    .flag-pole-icon {
        width: 16px;
        height: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .flag-pole-icon svg {
        width: 100%;
        height: 100%;
        fill: #facc15;
    }

    /* Ikon Server Utama / Node Server */
    .server-tower-icon {
        width: 26px;
        height: 26px;
        background: #1e1b4b;
        border: 2px solid #ef4444;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 0 10px #ef4444;
    }
    .server-tower-icon svg {
        width: 15px;
        height: 15px;
        fill: #38bdf8;
    }

    /* Label Nama Klien & ODP */
    .client-label {
        background: rgba(10, 15, 24, 0.92) !important;
        border: 1px solid rgba(0, 243, 255, 0.5) !important;
        color: #e0f2fe !important;
        font-size: 11px !important;
        font-weight: 600 !important;
        padding: 2px 6px !important;
        border-radius: 4px !important;
        white-space: nowrap;
        pointer-events: none;
    }
    .client-label::before {
        display: none !important;
    }

    /* Label Server */
    .server-label {
        background: #7f1d1d !important;
        border: 1px solid #ef4444 !important;
        color: #fef2f2 !important;
        font-weight: bold !important;
        font-size: 11px !important;
        box-shadow: 0 0 8px rgba(239, 68, 68, 0.6) !important;
    }

    /* Kontrol Zoom */
    .hide-map-labels .client-label {
        display: none !important;
    }
    .hide-map-labels .server-label {
        display: block !important;
    }

    /* Indikator Mode Tarik Kabel & Interaksi Snapping */
    .drawing-mode-active #odcGisMap {
        cursor: crosshair !important;
    }
    .drawing-mode-active .snappable-node {
        cursor: cell !important;
    }

    /* Banner Penarikan Kabel Interaktif */
    #cableDrawingBanner {
        display: none;
        position: absolute;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 1000;
        background: rgba(15, 23, 42, 0.95);
        color: #fff;
        padding: 8px 18px;
        border-radius: 30px;
        font-size: 12px;
        font-weight: 600;
        border: 1px solid #7c3aed;
        box-shadow: 0 8px 20px rgba(0,0,0,0.6);
        align-items: center;
        gap: 12px;
    }

    .banner-btn {
        border: none;
        border-radius: 20px;
        padding: 4px 12px;
        font-size: 11px;
        font-weight: bold;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: 0.2s;
    }
    .banner-btn-finish { background: #059669; color: #fff; }
    .banner-btn-finish:hover { background: #10b981; }
    .banner-btn-cancel { background: #dc2626; color: #fff; }
    .banner-btn-cancel:hover { background: #ef4444; }

    .custom-overlay-btn {
        position: absolute;
        top: 20px;
        right: 20px;
        z-index: 1000;
        background: rgba(13, 17, 23, 0.9);
        color: #00ffcc;
        border: 1px solid #00ffcc;
        padding: 8px 15px;
        border-radius: 6px;
        font-weight: bold;
        font-size: 12px;
    }

    .legend-box {
        position: absolute;
        bottom: 20px;
        right: 20px;
        background: rgba(13, 17, 23, 0.94);
        border: 1px solid #1f2937;
        border-radius: 8px;
        padding: 10px 14px;
        color: #ffffff;
        font-size: 11px;
        max-height: 55vh;
        overflow-y: auto;
        z-index: 1000;
    }
    .legend-row { display: flex; align-items: center; margin-bottom: 5px; }
    .legend-row:last-child { margin-bottom: 0; }
    .legend-sym { width: 22px; margin-right: 8px; display: flex; justify-content: center; align-items: center; }
    .legend-sym img { max-width: 16px; max-height: 16px; }
    .line-badge { width: 16px; height: 3px; border-radius: 2px; }

    /* Popup Leaflet Style */
    .leaflet-popup-content-wrapper {
        background: #101726;
        color: #f3f4f6;
        border: 1px solid #1e293b;
        border-radius: 10px;
        padding: 6px;
    }
    .leaflet-popup-tip { background: #101726; }
    
    .popup-actions-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 6px;
        margin-top: 8px;
    }
    .btn-act-popup {
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 6px 4px;
        border-radius: 5px;
        font-size: 11px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: opacity 0.2s;
    }
    .btn-act-popup:hover { opacity: 0.85; }
    .btn-add-client { background: #2563eb; color: #ffffff !important; }
    .btn-add-odp    { background: #059669; color: #ffffff !important; }
    .btn-add-pole   { background: #d97706; color: #ffffff !important; }
    .btn-add-cable  { background: #7c3aed; color: #ffffff !important; }
    .btn-add-server { background: #dc2626; color: #ffffff !important; }
    .btn-copy-coord {
        grid-column: span 2;
        background: #1f2937;
        color: #38bdf8 !important;
        border: 1px solid #374151;
    }

    .btn-delete-marker {
        width: 100%;
        background: #dc2626;
        color: #ffffff !important;
        margin-top: 6px;
        padding: 6px 8px;
        border-radius: 4px;
        border: none;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
    }
    .btn-delete-marker:hover { background: #b91c1c; }

    .btn-edit-cable {
        width: 100%;
        background: #2563eb;
        color: #ffffff !important;
        margin-top: 6px;
        padding: 6px 8px;
        border-radius: 4px;
        border: none;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
    }
    .btn-edit-cable:hover { background: #1d4ed8; }

    /* Tombol Sambung Cepat Dropcore & Tiang */
    .btn-connect-client {
        width: 100%;
        background: #059669;
        color: #ffffff !important;
        border: 1px solid #34d399;
        margin-top: 6px;
        padding: 7px 10px;
        border-radius: 5px;
        font-size: 11px;
        font-weight: bold;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        box-shadow: 0 0 10px rgba(5, 150, 105, 0.4);
    }
    .btn-connect-client:hover { background: #10b981; }

    .btn-connect-pole-step {
        width: 100%;
        background: #d97706;
        color: #ffffff !important;
        border: 1px solid #f59e0b;
        margin-top: 6px;
        padding: 6px 10px;
        border-radius: 5px;
        font-size: 11px;
        font-weight: bold;
        cursor: pointer;
    }
    .btn-connect-pole-step:hover { background: #b45309; }

    .btn-connect-pole-finish {
        width: 100%;
        background: #059669;
        color: #ffffff !important;
        border: 1px solid #34d399;
        margin-top: 5px;
        padding: 6px 10px;
        border-radius: 5px;
        font-size: 11px;
        font-weight: bold;
        cursor: pointer;
    }
    .btn-connect-pole-finish:hover { background: #10b981; }

    /* Modal Dialog Ubah / Tambah Kabel FO */
    .cable-modal-backdrop {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.65);
        justify-content: center;
        align-items: center;
    }
    .cable-modal-content {
        background: #111827;
        border: 1px solid #374151;
        padding: 16px 20px;
        border-radius: 8px;
        width: 330px;
        color: #fff;
        box-shadow: 0 10px 25px rgba(0,0,0,0.8);
    }
    .cable-select-input {
        width: 100%;
        background: #1f2937;
        border: 1px solid #4b5563;
        color: #fff;
        padding: 7px 10px;
        border-radius: 5px;
        margin-top: 5px;
        margin-bottom: 12px;
        font-size: 12px;
    }
</style>
@endpush

@section('content')
<div class="card bg-dark border-0">
    <div class="card-body p-0">
        <div class="map-container">
            <!-- Banner Mode Penarikan Kabel Interaktif -->
            <div id="cableDrawingBanner">
                <span id="bannerCableStatus">⚡ Tarik Kabel Aktif: Arahkan kursor ke Pelanggan atau Tiang</span>
                <button type="button" onclick="finishCableDrawing()" class="banner-btn banner-btn-finish">✓ Selesai</button>
                <button type="button" onclick="cancelCableDrawing()" class="banner-btn banner-btn-cancel">✖ Batal</button>
            </div>

            <button class="custom-overlay-btn">Mode Malam: AKTIF</button>
            
            <div id="odcGisMap"></div>

            <div class="legend-box">
                <div style="font-weight: bold; margin-bottom: 6px; color: #00ffff; font-size: 11px;">PERANGKAT UTAMA</div>
                <div class="legend-row">
                    <div class="legend-sym">
                        <div class="server-tower-icon" style="width: 18px; height: 18px;">
                            <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14h2v2h-2v-2zm0-10h2v8h-2V6z"/></svg>
                        </div>
                    </div>
                    <span style="color: #f87171; font-weight: bold;">Server / POP NOC</span>
                </div>
                <div class="legend-row">
                    <div class="legend-sym"><img src="/peta_jaringan/files/odp.png" alt="ODP" onerror="this.style.display='none'"></div>
                    <span>ODP</span>
                </div>
                <div class="legend-row">
                    <div class="legend-sym">
                        <div class="flag-pole-icon">
                            <svg viewBox="0 0 24 24"><path d="M14.4 6L14 4H5v17h2v-7h5.6l.4 2h7V6z"/></svg>
                        </div>
                    </div>
                    <span>Tiang (Bendera)</span>
                </div>
                <div class="legend-row">
                    <div class="legend-sym"><img src="/peta_jaringan/files/location_4985966.png" alt="Klien" onerror="this.style.display='none'"></div>
                    <span>Klien / Pelanggan</span>
                </div>

                <div style="font-weight: bold; margin: 8px 0 5px 0; color: #00ffff; font-size: 11px;">RUTE KABEL FO (DESA)</div>
                <div class="legend-row"><div class="legend-sym"><div class="line-badge" style="background: #ef4444;"></div></div><span style="color: #fca5a5; font-weight: bold;">Main Feeder Sulis Tower</span></div>
                <div class="legend-row"><div class="legend-sym"><div class="line-badge" style="background: #38bdf8;"></div></div><span style="color: #7dd3fc; font-weight: bold;">Dropcore Pelanggan</span></div>
                <div class="legend-row"><div class="legend-sym"><div class="line-badge" style="background: #d946ef;"></div></div><span>Kasegeran (Ungu)</span></div>
                <div class="legend-row"><div class="legend-sym"><div class="line-badge" style="background: #a3e635;"></div></div><span>Jatisaba (Lime)</span></div>
                <div class="legend-row"><div class="legend-sym"><div class="line-badge" style="background: #00f3ff;"></div></div><span>Pageraji (Cyan)</span></div>
                <div class="legend-row"><div class="legend-sym"><div class="line-badge" style="background: #fb923c;"></div></div><span>Karangendep (Oranye)</span></div>
                <div class="legend-row"><div class="legend-sym"><div class="line-badge" style="background: #22c55e;"></div></div><span>Panusupan (Hijau)</span></div>
                <div class="legend-row"><div class="legend-sym"><div class="line-badge" style="background: #f43f5e;"></div></div><span>Cipete (Pink)</span></div>
                <div class="legend-row"><div class="legend-sym"><div class="line-badge" style="background: #facc15;"></div></div><span>Cikidang / Batuanten</span></div>
                <div class="legend-row"><div class="legend-sym"><div class="line-badge" style="background: #ffffff;"></div></div><span>Backbone Lainnya</span></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Kabel FO Baru -->
<div id="cableColorModal" class="cable-modal-backdrop">
    <div class="cable-modal-content">
        <h6 style="color: #00ffff; font-size: 13px; font-weight: bold; margin-bottom: 10px;">Tambah Jalur Kabel FO</h6>
        <label style="font-size: 11px; color: #9ca3af;">Nama Jalur Kabel:</label>
        <input type="text" id="cableNameInput" class="cable-select-input" placeholder="Contoh: Feeder ODC-01">

        <label style="font-size: 11px; color: #9ca3af;">Warna / Wilayah Desa:</label>
        <select id="cableClassSelect" class="cable-select-input">
            <option value="cable-default">Putih (Backbone Umum)</option>
            <option value="cable-dropcore">Cyan Biru (Dropcore Pelanggan)</option>
            <option value="cable-main-server">Merah (Feeder Server Utama)</option>
            <option value="cable-kasegeran">Ungu (Kasegeran)</option>
            <option value="cable-jatisaba">Lime (Jatisaba)</option>
            <option value="cable-pageraji">Cyan (Pageraji)</option>
            <option value="cable-karangendep">Oranye (Karangendep)</option>
            <option value="cable-panusupan">Hijau (Panusupan)</option>
            <option value="cable-cipete">Pink (Cipete)</option>
            <option value="cable-cikidang">Kuning (Cikidang / Batuanten)</option>
        </select>

        <div style="display: flex; justify-content: flex-end; gap: 8px;">
            <button type="button" onclick="cancelCableModal()" style="background: #374151; color: #fff; border: none; padding: 6px 12px; border-radius: 4px; font-size: 11px; cursor: pointer;">Batal</button>
            <button type="button" onclick="confirmCableModal()" style="background: #7c3aed; color: #fff; border: none; padding: 6px 12px; border-radius: 4px; font-size: 11px; font-weight: bold; cursor: pointer;">Mulai Tarik</button>
        </div>
    </div>
</div>

<!-- Modal Edit Warna & Nama Kabel FO Eksisting -->
<div id="editCableModal" class="cable-modal-backdrop">
    <div class="cable-modal-content">
        <h6 style="color: #38bdf8; font-size: 13px; font-weight: bold; margin-bottom: 10px;">🎨 Edit Warna & Nama Kabel FO</h6>
        <input type="hidden" id="editCableLayerId">
        
        <label style="font-size: 11px; color: #9ca3af;">Nama Jalur Kabel:</label>
        <input type="text" id="editCableNameInput" class="cable-select-input">

        <label style="font-size: 11px; color: #9ca3af;">Pilih Warna / Rute Desa:</label>
        <select id="editCableClassSelect" class="cable-select-input">
            <option value="cable-default">Putih (Backbone Umum)</option>
            <option value="cable-dropcore">Cyan Biru (Dropcore Pelanggan)</option>
            <option value="cable-main-server">Merah (Feeder Server Utama)</option>
            <option value="cable-kasegeran">Ungu (Kasegeran)</option>
            <option value="cable-jatisaba">Lime (Jatisaba)</option>
            <option value="cable-pageraji">Cyan (Pageraji)</option>
            <option value="cable-karangendep">Oranye (Karangendep)</option>
            <option value="cable-panusupan">Hijau (Panusupan)</option>
            <option value="cable-cipete">Pink (Cipete)</option>
            <option value="cable-cikidang">Kuning (Cikidang / Batuanten)</option>
        </select>

        <div style="display: flex; justify-content: flex-end; gap: 8px;">
            <button type="button" onclick="closeEditCableModal()" style="background: #374151; color: #fff; border: none; padding: 6px 12px; border-radius: 4px; font-size: 11px; cursor: pointer;">Batal</button>
            <button type="button" onclick="saveEditedCable()" style="background: #2563eb; color: #fff; border: none; padding: 6px 12px; border-radius: 4px; font-size: 11px; font-weight: bold; cursor: pointer;">Simpan Perubahan</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-omnivore@0.3.4/leaflet-omnivore.min.js"></script>

<script>
    var map;
    var odpIcon, clientIcon, flagPoleIcon, serverTowerIcon;
    var markersList = {};

    var isDrawingCable = false;
    var cablePoints = [];
    var tempCablePolyline = null;
    var currentCableName = '';
    var currentCableClass = 'cable-default';
    var pendingCableLat = null;
    var pendingCableLng = null;

    var cableOriginType = null;
    var cableOriginName = '';

    function copyCoordinate(lat, lng) {
        var text = lat + ', ' + lng;
        navigator.clipboard.writeText(text).then(function() {
            alert('Koordinat berhasil disalin:\n' + text);
        }).catch(function() {
            prompt('Salin manual koordinat ini:', text);
        });
    }

    function removeMarker(layerId) {
        if (confirm('Apakah Anda yakin ingin menghapus elemen kabel/titik ini?')) {
            if (markersList[layerId]) {
                map.removeLayer(markersList[layerId]);
                delete markersList[layerId];
            }
        }
    }

    // Modal Edit Warna Kabel
    function openEditCableModal(lineId, currentName) {
        map.closePopup();
        document.getElementById('editCableLayerId').value = lineId;
        document.getElementById('editCableNameInput').value = currentName;
        document.getElementById('editCableModal').style.display = 'flex';
    }

    function closeEditCableModal() {
        document.getElementById('editCableModal').style.display = 'none';
    }

    function saveEditedCable() {
        var lineId = document.getElementById('editCableLayerId').value;
        var newName = document.getElementById('editCableNameInput').value.trim() || 'Jalur Kabel FO';
        var newClass = document.getElementById('editCableClassSelect').value;

        var layer = markersList[lineId];
        if (layer && layer instanceof L.Polyline) {
            // Hapus semua class warna lama lalu terapkan class warna baru
            var el = layer.getElement ? layer.getElement() : null;
            if (el) {
                el.setAttribute('class', 'leaflet-interactive cable-flow ' + newClass);
            }
            layer.options.className = 'cable-flow ' + newClass;

            // Update konten popup kabel
            layer.bindPopup(buildCablePopupContent(lineId, newName));
        }

        closeEditCableModal();
    }

    function buildCablePopupContent(lineId, name) {
        return `
            <div style="font-size: 12px; min-width: 160px;">
                <b style="color: #38bdf8;">${name}</b><br>
                <button type="button" onclick="openEditCableModal(${lineId}, '${name.replace(/'/g, "\\'")}')" class="btn-edit-cable">
                    🎨 Ubah Warna / Desa
                </button>
                <button type="button" onclick="removeMarker(${lineId})" class="btn-delete-marker">
                    🗑️ Putus / Hapus Kabel Ini
                </button>
            </div>
        `;
    }

    function startCableFromMarker(lat, lng, defaultPrefix, nodeType) {
        map.closePopup();
        pendingCableLat = lat;
        pendingCableLng = lng;
        cableOriginType = nodeType || 'node';
        cableOriginName = defaultPrefix || '';

        var nameInput = document.getElementById('cableNameInput');
        var selectClass = document.getElementById('cableClassSelect');

        if (nodeType === 'odp') {
            nameInput.value = 'Dropcore: ' + defaultPrefix + ' ke ';
            selectClass.value = 'cable-dropcore';
        } else {
            nameInput.value = defaultPrefix ? (defaultPrefix + ' ke ') : '';
        }

        document.getElementById('cableColorModal').style.display = 'flex';
    }

    function connectOdpToClient(clientLat, clientLng, clientName) {
        if (!isDrawingCable || !tempCablePolyline) return;

        cablePoints.push([clientLat, clientLng]);
        tempCablePolyline.setLatLngs(cablePoints);

        // Pencegahan nama berulang (misal: "Dropcore: ODP ke Yuli ke Yuli")
        if (!currentCableName.includes(clientName)) {
            if (currentCableName.endsWith(' ke ')) {
                currentCableName += clientName;
            } else {
                currentCableName += ' ke ' + clientName;
            }
        }

        map.closePopup();
        finishCableDrawing();
    }

    function passThroughPole(poleLat, poleLng, poleName) {
        if (!isDrawingCable || !tempCablePolyline) return;

        cablePoints.push([poleLat, poleLng]);
        tempCablePolyline.setLatLngs(cablePoints);
        map.closePopup();

        document.getElementById('bannerCableStatus').innerHTML = `⚡ Tersambung ke <b>${poleName}</b>: Lanjutkan ke titik/tiang/pelanggan berikutnya`;
    }

    function finishAtPole(poleLat, poleLng, poleName) {
        if (!isDrawingCable || !tempCablePolyline) return;

        cablePoints.push([poleLat, poleLng]);
        tempCablePolyline.setLatLngs(cablePoints);

        if (!currentCableName.includes(poleName)) {
            if (currentCableName.endsWith(' ke ')) {
                currentCableName += poleName;
            } else {
                currentCableName += ' ke ' + poleName;
            }
        }

        map.closePopup();
        finishCableDrawing();
    }

    function finishCableDrawing() {
        if (!isDrawingCable || !tempCablePolyline) return;

        if (cablePoints.length < 2) {
            alert('Titik kabel minimal harus ada 2 titik!');
            return;
        }

        var lineId = L.stamp(tempCablePolyline);
        markersList[lineId] = tempCablePolyline;

        tempCablePolyline.bindPopup(buildCablePopupContent(lineId, currentCableName));

        tempCablePolyline.on('mouseover', function() { if (!isDrawingCable) this.setStyle({ weight: 6 }); });
        tempCablePolyline.on('mouseout', function() { if (!isDrawingCable) this.setStyle({ weight: 3 }); });

        isDrawingCable = false;
        cableOriginType = null;
        cableOriginName = '';
        document.getElementById('odcGisMap').classList.remove('drawing-mode-active');
        cablePoints = [];
        tempCablePolyline = null;
        document.getElementById('cableDrawingBanner').style.display = 'none';
    }

    function cancelCableDrawing() {
        if (isDrawingCable && tempCablePolyline) {
            map.removeLayer(tempCablePolyline);
        }
        isDrawingCable = false;
        cableOriginType = null;
        cableOriginName = '';
        document.getElementById('odcGisMap').classList.remove('drawing-mode-active');
        cablePoints = [];
        tempCablePolyline = null;
        document.getElementById('cableDrawingBanner').style.display = 'none';
    }

    function attachDeletablePopup(layer, title, subtitle, canDrawCable, lat, lng, nodeType) {
        var layerId = L.stamp(layer);
        markersList[layerId] = layer;

        var hoverTimeout = null;

        function renderPopupContent() {
            if (isDrawingCable && nodeType === 'client') {
                return `
                    <div class="hover-action-card" style="font-size: 12px; min-width: 185px;">
                        <div style="font-weight: bold; color: #38bdf8; font-size: 13px;">Pelanggan: ${title}</div>
                        <div style="color: #94a3b8; font-size: 11px; margin-top: 2px;">
                            ${cableOriginType === 'odp' ? 'Sumber: <b>' + cableOriginName + '</b>' : 'Sambungkan jalur'}
                        </div>
                        <button type="button" onclick="connectOdpToClient(${lat}, ${lng}, '${title}')" class="btn-connect-client">
                            🔌 Sambungkan ke ${title}
                        </button>
                    </div>
                `;
            }

            if (isDrawingCable && nodeType === 'pole') {
                return `
                    <div class="hover-action-card" style="font-size: 12px; min-width: 190px;">
                        <div style="font-weight: bold; color: #facc15; font-size: 13px;">Tiang: ${title}</div>
                        <div style="color: #94a3b8; font-size: 11px; margin-top: 2px;">Pilihan Sambungan Tiang:</div>
                        <button type="button" onclick="passThroughPole(${lat}, ${lng}, '${title}')" class="btn-connect-pole-step">
                            🚩 Sambungkan & Lewati Tiang
                        </button>
                        <button type="button" onclick="finishAtPole(${lat}, ${lng}, '${title}')" class="btn-connect-pole-finish">
                            🏁 Sambungkan & Selesai di Tiang
                        </button>
                    </div>
                `;
            }

            var drawCableBtnHtml = '';
            if (canDrawCable && lat && lng) {
                drawCableBtnHtml = `
                    <button type="button" onclick="startCableFromMarker(${lat}, ${lng}, '${title}', '${nodeType}')" style="width: 100%; background: #7c3aed; color: #ffffff !important; margin-top: 6px; padding: 6px 8px; border-radius: 4px; border: none; font-size: 11px; font-weight: 600; cursor: pointer;">
                        ⚡ Tarik Kabel FO Dari Sini
                    </button>
                `;
            }

            return `
                <div class="hover-action-card" style="font-size: 12px; min-width: 175px;">
                    <div style="font-weight: bold; color: #00ffff; font-size: 13px;">${title}</div>
                    <div style="color: #94a3b8; font-size: 11px; margin-top: 2px;">${subtitle}</div>
                    ${drawCableBtnHtml}
                    <button type="button" onclick="removeMarker(${layerId})" class="btn-delete-marker">
                        🗑️ Hapus Titik Ini
                    </button>
                </div>
            `;
        }

        layer.bindPopup(renderPopupContent, {
            autoPan: false,
            closeButton: false,
            className: 'interactive-hover-popup'
        });

        layer.on('mouseover', function(e) {
            if (isDrawingCable && (nodeType !== 'client' && nodeType !== 'pole')) return;

            clearTimeout(hoverTimeout);
            layer.setPopupContent(renderPopupContent());
            layer.openPopup();

            var popupEl = layer.getPopup() ? layer.getPopup().getElement() : null;
            if (popupEl) {
                popupEl.addEventListener('mouseenter', function() {
                    clearTimeout(hoverTimeout);
                });
                popupEl.addEventListener('mouseleave', function() {
                    hoverTimeout = setTimeout(function() {
                        layer.closePopup();
                    }, 250);
                });
            }
        });

        layer.on('mouseout', function(e) {
            hoverTimeout = setTimeout(function() {
                layer.closePopup();
            }, 300);
        });

        layer.on('click', function(e) {
            if (isDrawingCable) {
                if (e.originalEvent) {
                    e.originalEvent.stopPropagation();
                    e.originalEvent.preventDefault();
                }
                
                if (nodeType === 'client') {
                    connectOdpToClient(lat, lng, title);
                } else if (nodeType === 'pole') {
                    passThroughPole(lat, lng, title);
                }
                return false;
            }
        });
    }

    function cancelCableModal() {
        document.getElementById('cableColorModal').style.display = 'none';
    }

    function confirmCableModal() {
        var nameInput = document.getElementById('cableNameInput').value.trim();
        currentCableName = nameInput || 'Kabel FO Baru';
        currentCableClass = document.getElementById('cableClassSelect').value;

        document.getElementById('cableColorModal').style.display = 'none';

        isDrawingCable = true;
        document.getElementById('odcGisMap').classList.add('drawing-mode-active');
        map.closePopup();

        cablePoints = [[pendingCableLat, pendingCableLng]];
        
        var bannerStatus = document.getElementById('bannerCableStatus');
        if (cableOriginType === 'odp') {
            bannerStatus.innerHTML = `⚡ Tarik Dropcore dari <b>${cableOriginName}</b>: Arahkan ke Pelanggan atau Tiang`;
        } else {
            bannerStatus.innerHTML = `⚡ Mode Tarik Kabel: Klik rute atau arahkan ke Tiang / Pelanggan`;
        }

        document.getElementById('cableDrawingBanner').style.display = 'flex';

        tempCablePolyline = L.polyline(cablePoints, {
            weight: 3,
            className: 'cable-flow cable-current-drawing ' + currentCableClass
        }).addTo(map);
    }

    function placeDeviceOnMap(type, lat, lng) {
        var marker;
        if (type === 'client') {
            var name = prompt('Masukkan Nama Klien/Pelanggan:', 'Klien Baru');
            if (name === null) return;
            marker = L.marker([lat, lng], { icon: clientIcon }).addTo(map);
            marker.bindTooltip(name, { permanent: true, direction: 'bottom', offset: [0, 10], className: 'client-label' });
            attachDeletablePopup(marker, name, 'Klien Baru (' + lat + ', ' + lng + ')', false, lat, lng, 'client');
            marker.openPopup();
        } else if (type === 'odp') {
            var odpName = prompt('Masukkan Kode/Nama ODP:', 'ODP-NEW-01');
            if (odpName === null) return;
            marker = L.marker([lat, lng], { icon: odpIcon }).addTo(map);
            marker.bindTooltip(odpName, { permanent: true, direction: 'bottom', offset: [0, 12], className: 'client-label' });
            attachDeletablePopup(marker, odpName, 'ODP Baru (' + lat + ', ' + lng + ')', true, lat, lng, 'odp');
            marker.openPopup();
        } else if (type === 'pole') {
            var poleName = prompt('Masukkan Kode Tiang (contoh: T-TF 15):', 'T-TF New');
            if (poleName === null) return;
            marker = L.marker([lat, lng], { icon: flagPoleIcon }).addTo(map);
            marker.bindTooltip(poleName, { permanent: true, direction: 'bottom', offset: [0, 8], className: 'client-label' });
            attachDeletablePopup(marker, poleName, 'Tiang Baru (' + lat + ', ' + lng + ')', true, lat, lng, 'pole');
            marker.openPopup();
        } else if (type === 'server') {
            var serverName = prompt('Masukkan Nama Server/POP:', 'SERVER UTAMA / POP');
            if (serverName === null) return;
            marker = L.marker([lat, lng], { icon: serverTowerIcon }).addTo(map);
            marker.bindTooltip(serverName, { permanent: true, direction: 'top', offset: [0, -14], className: 'client-label server-label' });
            attachDeletablePopup(marker, serverName, 'Server Node (' + lat + ', ' + lng + ')', true, lat, lng, 'server');
            marker.openPopup();
        } else if (type === 'cable') {
            startCableFromMarker(lat, lng, '', 'free');
        }

        map.closePopup();
    }

    function updateLabelVisibility() {
        var mapEl = document.getElementById('odcGisMap');
        if (map.getZoom() < 16) {
            mapEl.classList.add('hide-map-labels');
        } else {
            mapEl.classList.remove('hide-map-labels');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        map = L.map('odcGisMap', {
            preferCanvas: false,
            doubleClickZoom: false,
            maxZoom: 22
        }).setView([-7.435926, 109.153747], 17);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxNativeZoom: 19,
            maxZoom: 22,
            className: 'map-dark-theme'
        }).addTo(map);

        odpIcon = L.icon({
            iconUrl: '/peta_jaringan/files/odp.png',
            iconSize: [22, 22],
            iconAnchor: [11, 11],
            popupAnchor: [0, -11],
            className: 'snappable-node'
        });

        clientIcon = L.icon({
            iconUrl: '/peta_jaringan/files/location_4985966.png',
            iconSize: [20, 20],
            iconAnchor: [10, 10],
            popupAnchor: [0, -10],
            className: 'snappable-node'
        });

        flagPoleIcon = L.divIcon({
            className: 'snappable-node',
            html: '<div class="flag-pole-icon"><svg viewBox="0 0 24 24"><path d="M14.4 6L14 4H5v17h2v-7h5.6l.4 2h7V6z"/></svg></div>',
            iconSize: [16, 16],
            iconAnchor: [4, 16]
        });

        serverTowerIcon = L.divIcon({
            className: 'snappable-node',
            html: '<div class="server-tower-icon"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14h2v2h-2v-2zm0-10h2v8h-2V6z"/></svg></div>',
            iconSize: [26, 26],
            iconAnchor: [13, 13]
        });

        // Server Utama Sulis Tower
        var sulisTowerCoords = [-7.435926, 109.153747];
        var nearestPoleCoords = [-7.435965, 109.153915];

        var serverMarker = L.marker(sulisTowerCoords, { icon: serverTowerIcon }).addTo(map);
        serverMarker.bindTooltip('SERVER UTAMA (Sulis Tower)', {
            permanent: true,
            direction: 'top',
            offset: [0, -14],
            className: 'client-label server-label'
        });
        attachDeletablePopup(serverMarker, 'SERVER UTAMA NOC', 'Lokasi: Sulis Tower (-7.435926, 109.153747)', true, sulisTowerCoords[0], sulisTowerCoords[1], 'server');

        var serverCablePolyline = L.polyline([sulisTowerCoords, nearestPoleCoords], {
            weight: 3.5,
            className: 'cable-flow cable-main-server'
        }).addTo(map);
        
        var serverCableId = L.stamp(serverCablePolyline);
        markersList[serverCableId] = serverCablePolyline;
        serverCablePolyline.bindPopup(buildCablePopupContent(serverCableId, 'Kabel Feeder Server Utama (Sulis Tower)'));
        serverCablePolyline.on('mouseover', function() { if (!isDrawingCable) this.setStyle({ weight: 7 }); });
        serverCablePolyline.on('mouseout', function() { if (!isDrawingCable) this.setStyle({ weight: 3.5 }); });

        // Layer KML Infrastruktur
        var customLayer = L.geoJson(null, {
            pointToLayer: function (feature, latlng) {
                var props = feature.properties || {};
                var name = (props.name || '').trim();
                var nameLower = name.toLowerCase();
                var desc = (props.description || '').toLowerCase();
                var styleUrl = (props.styleUrl || '').toLowerCase();

                if (nameLower.includes('odp') || desc.includes('odp') || styleUrl.includes('odp')) {
                    var marker = L.marker(latlng, { icon: odpIcon });
                    if (name) {
                        marker.bindTooltip(name, {
                            permanent: true,
                            direction: 'bottom',
                            offset: [0, 12],
                            className: 'client-label'
                        });
                    }
                    attachDeletablePopup(marker, name || 'ODP', 'Lat: ' + latlng.lat.toFixed(6) + ', Lng: ' + latlng.lng.toFixed(6), true, latlng.lat, latlng.lng, 'odp');
                    return marker;
                }
                
                if (
                    nameLower.includes('untitled placemark') ||
                    nameLower.includes('t-tf') ||
                    nameLower.startsWith('ttf') ||
                    nameLower.includes('tiang') ||
                    nameLower.startsWith('t.') ||
                    nameLower.startsWith('t ') ||
                    nameLower.includes('pole') ||
                    desc.includes('tiang') ||
                    desc.includes('t-tf') ||
                    styleUrl.includes('flag')
                ) {
                    var marker = L.marker(latlng, { icon: flagPoleIcon });
                    if (name && !nameLower.includes('untitled placemark')) {
                        marker.bindTooltip(name, {
                            permanent: true,
                            direction: 'bottom',
                            offset: [0, 8],
                            className: 'client-label'
                        });
                    }
                    attachDeletablePopup(marker, name || 'Tiang', 'Lat: ' + latlng.lat.toFixed(6) + ', Lng: ' + latlng.lng.toFixed(6), true, latlng.lat, latlng.lng, 'pole');
                    return marker;
                }

                var marker = L.marker(latlng, { icon: clientIcon });
                if (name) {
                    marker.bindTooltip(name, {
                        permanent: true,
                        direction: 'bottom',
                        offset: [0, 10],
                        className: 'client-label'
                    });
                }
                attachDeletablePopup(marker, name || 'Klien', 'Lat: ' + latlng.lat.toFixed(6) + ', Lng: ' + latlng.lng.toFixed(6), false, latlng.lat, latlng.lng, 'client');
                return marker;
            },
            style: function (feature) {
                var props = feature.properties || {};
                var name = (props.name || '').toLowerCase();
                var desc = (props.description || '').toLowerCase();

                var colorClass = 'cable-default';

                if (name.includes('dropcore') || desc.includes('dropcore')) colorClass = 'cable-dropcore';
                else if (name.includes('kasegeran') || desc.includes('kasegeran')) colorClass = 'cable-kasegeran';
                else if (name.includes('jatisaba') || desc.includes('jatisaba')) colorClass = 'cable-jatisaba';
                else if (name.includes('pageraji') || desc.includes('pageraji')) colorClass = 'cable-pageraji';
                else if (name.includes('karangendep') || desc.includes('karangendep')) colorClass = 'cable-karangendep';
                else if (name.includes('panusupan') || desc.includes('panusupan')) colorClass = 'cable-panusupan';
                else if (name.includes('cipete') || desc.includes('cipete')) colorClass = 'cable-cipete';
                else if (name.includes('cikidang') || name.includes('batuanten') || desc.includes('cikidang')) colorClass = 'cable-cikidang';

                return {
                    weight: 3,
                    className: 'cable-flow ' + colorClass
                };
            },
            onEachFeature: function (feature, layer) {
                if (layer instanceof L.Polyline) {
                    var lineId = L.stamp(layer);
                    markersList[lineId] = layer;

                    layer.bindPopup(buildCablePopupContent(lineId, feature.properties.name || 'Jalur Kabel FO'));

                    layer.on('mouseover', function() { if (!isDrawingCable) this.setStyle({ weight: 6 }); });
                    layer.on('mouseout', function() { if (!isDrawingCable) this.setStyle({ weight: 3 }); });
                }
            }
        });

        var kmlLayer = omnivore.kml('/peta_jaringan/doc.kml', null, customLayer)
            .on('ready', function() {
                updateLabelVisibility();
            })
            .addTo(map);

        map.on('zoomend', updateLabelVisibility);

        map.on('click', function(e) {
            if (isDrawingCable) {
                cablePoints.push([e.latlng.lat, e.latlng.lng]);
                tempCablePolyline.setLatLngs(cablePoints);
                return;
            }

            var lat = e.latlng.lat.toFixed(6);
            var lng = e.latlng.lng.toFixed(6);

            var popupContent = `
                <div style="font-size: 12px; min-width: 210px;">
                    <div style="font-weight: bold; color: #00ffff; margin-bottom: 2px;">Titik Koordinat Baru</div>
                    <div style="color: #94a3b8; font-size: 11px;">
                        Lat: <code>${lat}</code><br>
                        Lng: <code>${lng}</code>
                    </div>
                    <hr style="border: 0; border-top: 1px solid #1e293b; margin: 6px 0;">
                    
                    <div class="popup-actions-grid">
                        <button type="button" onclick="copyCoordinate('${lat}', '${lng}')" class="btn-act-popup btn-copy-coord">
                            📋 Salin Koordinat
                        </button>
                        <button type="button" onclick="placeDeviceOnMap('server', ${lat}, ${lng})" class="btn-act-popup btn-add-server">
                            + Server
                        </button>
                        <button type="button" onclick="placeDeviceOnMap('odp', ${lat}, ${lng})" class="btn-act-popup btn-add-odp">
                            + ODP
                        </button>
                        <button type="button" onclick="placeDeviceOnMap('pole', ${lat}, ${lng})" class="btn-act-popup btn-add-pole">
                            + Tiang
                        </button>
                        <button type="button" onclick="placeDeviceOnMap('client', ${lat}, ${lng})" class="btn-act-popup btn-add-client">
                            + Klien
                        </button>
                        <button type="button" onclick="placeDeviceOnMap('cable', ${lat}, ${lng})" class="btn-act-popup btn-add-cable" style="grid-column: span 2;">
                            + Jalur FO
                        </button>
                    </div>
                </div>
            `;

            L.popup()
                .setLatLng(e.latlng)
                .setContent(popupContent)
                .openOn(map);
        });

        map.on('dblclick', function(e) {
            if (isDrawingCable && tempCablePolyline) {
                cablePoints.push([e.latlng.lat, e.latlng.lng]);
                tempCablePolyline.setLatLngs(cablePoints);
                finishCableDrawing();
            }
        });

        setTimeout(function() {
            map.invalidateSize();
            updateLabelVisibility();
        }, 300);
    });
</script>
@endpush